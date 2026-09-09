const [FormValidators, Form] = (() => {
    function normalize($field) {
        const type = $field.prop('type');
        const tag = $field.prop('tagName').toLowerCase();
        const val = $field.val();

        if (type === 'file') {
            const files = $field[0].files;
            if (!files || files.length === 0) return null;
            return $field.prop('multiple') ? [...files] : files[0];
        }

        if (tag === 'select' && $field.prop('multiple')) {
            return val || [];
        }

        if ($field.length > 1 && type === 'checkbox') {
            return [...$field.filter(':checked')].map(el => el.value);
        }

        if ($field.length > 1 && type === 'radio') {
            return $field.filter(':checked').val() || '';
        }

        if (type === 'checkbox') return $field.is(':checked');
        if (type === 'radio') return $field.is(':checked') ? val : '';
        if (typeof val !== 'string') return val ?? '';
        if (type === 'password') return val;

        return val.trim();
    }

    return [class FormValidators {
        constructor(validators={}, fields={}, selector='') {
            for (const [field, fn] of Object.entries(validators)) {
                this[field] = (event, data) => this.#validate(`${selector} [name="${field}"]`, fn, fields, event, data);
            }
        }

        #validate(selector, validationFn, fields, event, data={}) {
            const $field = $(selector);
            const val = normalize($field);
            const $feedback = $field.siblings('.invalid-feedback');
            
            const errorMsg = validationFn.call(this, val, { $field, fields, event, data: { ...data, ...$field.data() } });
            $feedback.text(errorMsg || '');

            $field.toggleClass('is-invalid', !!errorMsg);
            return !errorMsg;
        }

        isEmail(value) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(String(value).trim());
        }

        isPassword(value) {
            const val = String(value || '');
            
            const isLongEnough = val.length >= 8;
            const hasLetter    = /[a-zA-Z]/.test(val);
            const hasDigit     = /[0-9]/.test(val);
            const hasSymbol    = /[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/.test(val);

            return isLongEnough && hasLetter && hasDigit && hasSymbol;
        }

        isPhonePT(value) {
            const cleaned = String(value).replace(/\s+/g, '');
            const regex = /^(?:(?:\+|00)?351)?(2\d{8}|9[1236]\d{7})$/;
            return regex.test(cleaned);
        }

        isNIF(value) {
            const nif = String(value).replace(/\s+/g, '');
            if (!/^[12356789]\d{8}$/.test(nif)) return false;

            let sum = 0;
            for (let i = 0; i < 8; i++) {
                sum += parseInt(nif[i], 10) * (9 - i);
            }

            let calc = 11 - (sum % 11);
            if (calc >= 10) {
                calc = 0;
            }

            return calc === parseInt(nif[8], 10);
        }

        isCC(value) {
            const cleaned = String(value).toUpperCase().replace(/\s+/g, '');
            const regex = /^\d{8}[0-9A-Z]{2}\d[0-9A-Z]$/;
            return regex.test(cleaned);
        }

        isZipCode(value) {
            const regex = /^\d{4}-\d{3}$/;
            return regex.test(value);
        }

        isAccepted(value) {
            if (typeof value === 'boolean') return value;
            if (typeof value === 'number') return value === 1;
            if (typeof value === 'string') {
                const lower = value.toLowerCase().trim();
                return ['true', '1', 'yes', 'on'].includes(lower);
            }
            return false;
        }
    },
    class Form {
        selector;
        $form;
        validators;
        conditions;
        fields;
        #rawFields;
        #fieldDependencies = new Map();
        #submitHandler;
        #currentRunningRule = null;
        #directiveHandlers = {
            'form-show': (ruleName, result) => {
                this.$form.find(`[form-show="${ruleName}"]`).toggle(result);
            },
            'form-hide': (ruleName, result) => {
                this.$form.find(`[form-hide="${ruleName}"]`).toggle(!result);
            }
        };

        constructor(selector, { validators={}, conditions={}, submit }) {
            this.selector = selector;
            this.$form = $(selector);
            this.#submitHandler = submit;
            this.conditions = conditions;

            this.#rawFields = {};
            this.fields = new Proxy(this.#rawFields, {
                get: (target, prop) => {
                    if (this.#currentRunningRule && typeof prop === 'string') {
                        if (!this.#fieldDependencies.has(prop)) {
                            this.#fieldDependencies.set(prop, new Set());
                        }
                        this.#fieldDependencies.get(prop).add(this.#currentRunningRule);
                    }
                    const $field = this.$form.find(`[name="${prop}"]`);
                    if ($field.length > 0 && !$field.hasAttr('form-omit-when-hidden')) return normalize($field);
                    return target[prop];
                },
                set: (target, prop, value) => {
                    const $field = this.$form.find(`[name="${prop}"]`);
                    if ($field.length > 0) $field.val(value).trigger('change');
                    else target[prop] = value;
                    return true;
                }
            });

            this.validators = new FormValidators(validators, this.fields, selector);

            const prefixedMapping = {};
            for (const [name, fn] of Object.entries(validators)) {
                prefixedMapping[`validator:${name}`] = fn;
            }
            for (const [name, fn] of Object.entries(conditions)) {
                prefixedMapping[`condition:${name}`] = fn;
            }

            this.#registerDependencies(prefixedMapping);
            this.#evaluateAllConditions();
            this.#setupEvents();
        }

        #executeWithDependencyTracking(rule, clearExisting=false) {
            if (clearExisting) {
                for (const [field, rulesSet] of this.#fieldDependencies.entries()) {
                    for (const existingRule of rulesSet) {
                        if (existingRule.key === rule.key) {
                            rulesSet.delete(existingRule);
                        }
                    }
                    if (rulesSet.size === 0) this.#fieldDependencies.delete(field);
                }
            }

            this.#currentRunningRule = rule;
            try {
                rule.fn.call(this);
            } catch (e) {
                // Ignore errors during preliminary tracking
            } finally {
                this.#currentRunningRule = null;
            }
        }

        #evaluateRule(type, ruleName) {
            const source = type === 'condition' ? this.conditions : this.validators;
            const fn = source[ruleName];
            if (typeof fn !== 'function') return;

            this.#refreshDependenciesFor(type, ruleName, fn);

            const result = fn.call(this);

            if (type === 'condition') {
                for (const directive of Object.keys(this.#directiveHandlers)) {
                    this.#directiveHandlers[directive](this.$form, ruleName, result);
                }
            }
            return result;
        }

        #refreshDependenciesFor(type, ruleName, fn) {
            const rule = { key: `${type}:${ruleName}`, type, name: ruleName, fn };
            this.#executeWithDependencyTracking(rule, true);
        }

        #evaluateAllConditions() {
            for (const conditionName of Object.keys(this.conditions)) {
                this.#evaluateRule('condition', conditionName);
            }
        }

        #registerDependencies(mapping) {
            for (const [prefixedName, fn] of Object.entries(mapping)) {
                const [type, name] = prefixedName.split(':');
                const rule = { key: prefixedName, type, name, fn };
                this.#executeWithDependencyTracking(rule, false);
            }
        }

        #setupEvents() {
            this.$form.on('input change', '[name]', (e) => {
                const $field = $(e.target);
                if (!$field.attr('form-validate-on').split(' ').includes(e.type)) return;

                const fieldName = $field.attr('name');
                if (!fieldName) return;

                const dependentRules = this.#fieldDependencies.get(fieldName);
                if (!dependentRules) return;

                for (const rule of dependentRules) {
                    this.#evaluateRule(rule.type, rule.name);
                }
            });
        }

        #getActiveStep() {
            return this.$form.find('.form-step.active');
        }

        #getActiveStepIndex() {
            return this.$form.find('.form-step').index(this.#getActiveStep());
        }

        validate() {
            const $activeStep = this.#getActiveStep();
            const $group = $activeStep.length > 0 ? $activeStep : this.$form;
            let isValid = true;

            for (const field of Object.keys(this.validators)) {
                const $field = $group.find(`[name="${field}"]`);
                if ($field.length > 0 && !this.validators[field]()) {
                    isValid = false;
                }
            }

            return isValid;
        }

        goTo(targetIndex) {
            const $steps = this.$form.find('.form-step');
            const currentIndex = this.#getActiveStepIndex();

            if (targetIndex < 0 || targetIndex >= $steps.length) return false;
            if (targetIndex > currentIndex && !this.validate()) return false;

            $steps.removeClass('active').eq(targetIndex).addClass('active');
            return true;
        }

        nextStep() {
            return this.goTo(this.#getActiveStepIndex() + 1);
        }

        prevStep() {
            return this.goTo(this.#getActiveStepIndex() - 1);
        }

        submit() {
            const $steps = this.$form.find('.form-step');
            
            if (this.#getActiveStepIndex() !== $steps.length - 1 || !this.validate()) {
                return false;
            }

            const payload = { ...this.fields };
            return this.#submitHandler?.(payload) ?? true;
        }
    }];
})();
