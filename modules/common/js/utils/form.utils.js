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

        isPhone(value) {
            const cleaned = String(value).replace(/[\s\-()]/g, '');

            const isPT = /^(?:(?:\+|00)?351)?(2\d{8}|9[1236]\d{7})$/.test(cleaned);
            if (isPT) return true;

            const isBR = /^(?:(?:\+|00)?55)?[1-9]{2}(?:9\d{8}|[2-5]\d{7})$/.test(cleaned);
            if (isBR) return true;

            const normalized = cleaned.replace(/^(\+|00)/, '');
            if (normalized.startsWith('351') || normalized.startsWith('55')) {
                return false;
            }

            return /^\+?[0-9]{7,15}$/.test(cleaned);
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
        expressions;
        fields;
        #rawFields;
        #fieldDependencies = new Map();
        #submitHandler;
        #currentRunningRule = null;
        #directiveHandlers = {
            'form-show': (ruleName, result) => {
                this.$form.find(`[form-show="${ruleName}"]`).toggle(!!result);
            },
            'form-hide': (ruleName, result) => {
                this.$form.find(`[form-hide="${ruleName}"]`).toggle(!result);
            },
            'form-disable': (ruleName, result) => {
                this.$form.find(`[form-disable="${ruleName}"]`).prop('disabled', !!result);
            },
            'form-readonly': (ruleName, result) => {
                this.$form.find(`[form-readonly="${ruleName}"]`).prop('readonly', !!result);
            },
            'form-require': (ruleName, result) => {
                this.$form.find(`[form-require="${ruleName}"]`).prop('required', !!result);
            },
            'form-class': (ruleName, result) => {
                const $target = this.$form.find(`[form-class="${ruleName}"]`);
                if (typeof result === 'string') {
                    $target.attr('class', result);
                } else {
                    const className = $target.attr('form-class-name') || 'active';
                    $target.toggleClass(className, !!result);
                }
            }
        };

        constructor(selector, { validators={}, expressions={}, submit }) {
            this.selector = selector;
            this.$form = $(selector);
            this.#submitHandler = submit;
            this.validators = validators;
            this.expressions = expressions;

            this.#rawFields = {};
            this.fields = new Proxy(this.#rawFields, {
                get: (target, prop) => {
                    if (this.#currentRunningRule && typeof prop === 'string') {
                        this.#addDependency(prop, this.#currentRunningRule);
                    }
                    const $field = this.$form.find(`[name="${prop}"]`);
                    if ($field.is('[form-omit-when-hidden]') && $field.is(':hidden')) {
                        delete target[prop];
                        return;
                    }
                    if ($field.length > 0) target[prop] = normalize($field);
                    return target[prop];
                },
                set: (target, prop, value) => {
                    const $field = this.$form.find(`[name="${prop}"]`);
                    if ($field.length > 0) $field.val(value).trigger('change');
                    else target[prop] = value;
                    return true;
                }
            });

            this.#registerDependencies();
            this.#evaluateAllExpressions();
            this.#setupEvents();

            this.validators = new FormValidators(validators, this.fields, selector);
        }

        #registerDependencies() {
            for (const type of ['validators', 'expressions']) {
                for (const name of Object.keys(this[type] || {})) {
                    this.#executeWithDependencyTracking(`${type}:${name}`, false);
                }
            }
        }

        #addDependency(fieldName, rule) {
            if (!this.#fieldDependencies.has(fieldName)) {
                this.#fieldDependencies.set(fieldName, new Set());
            }
            this.#fieldDependencies.get(fieldName).add(rule);
        }

        #executeWithDependencyTracking(rule, clearExisting=false) {
            if (clearExisting) {
                for (const [fieldName, rulesSet] of this.#fieldDependencies.entries()) {
                    if (rulesSet.has(rule)) {
                        rulesSet.delete(rule);
                    }
                    if (rulesSet.size === 0) this.#fieldDependencies.delete(fieldName);
                }
            }

            const [type, name] = rule.split(':');
            const fn = this[type][name];
            if (typeof fn !== 'function') return;

            this.#currentRunningRule = rule;

            if (type === 'validators') {
                this.#addDependency(name, rule);
            }

            try {    
                return fn.call(this[type]);
            } catch (e) {
                // Ignore errors during preliminary tracking
            } finally {
                this.#currentRunningRule = null;
            }
        }

        #refreshDependenciesFor(rule) {
            return this.#executeWithDependencyTracking(rule, true);
        }

        #evaluateAllExpressions() {
            for (const name of Object.keys(this.expressions)) {
                this.#evaluateRule(`expressions:${name}`);
            }
        }

        #evaluateRule(rule) {
            const [type, name] = rule.split(':');
            const fn = this[type][name];
            if (typeof fn !== 'function') return;

            const result = this.#refreshDependenciesFor(rule);

            if (type === 'expressions') {
                for (const directive of Object.keys(this.#directiveHandlers)) {
                    this.#directiveHandlers[directive](name, result);
                }
            }
            return result;
        }

        #setupEvents() {
            const defaultEvents = 'input change';
            const containerEvents = 'input change blur keyup focusout';

            this.$form.on(containerEvents, '[name]', (e) => {
                const $field = $(e.target);
                const fieldName = $field.attr('name');
                if (!fieldName) return;
                
                this.#rawFields[fieldName] = normalize($field);

                const targetEvents = $field.attr('form-validate-on') ?? defaultEvents;
                if (!targetEvents.includes(e.type)) return;

                $field.removeClass('is-invalid');
                $field.siblings('.invalid-feedback').text('');

                const dependentRules = this.#fieldDependencies.get(fieldName);
                if (!dependentRules) return;

                for (const rule of [...dependentRules]) {
                    this.#evaluateRule(rule);
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

        setErrors(errors, fieldMap={}) {
            let firstErrorStepIndex = null;

            for (const [field, message] of Object.entries(errors)) {
                const targetField = fieldMap[field] || field;
                const $field = this.$form.find(`[name="${targetField}"]`);
                
                if ($field.length > 0) {
                    const $feedback = $field.siblings('.invalid-feedback');
                    $feedback.text(message);
                    $field.addClass('is-invalid');

                    const $step = $field.closest('.form-step');
                    if ($step.length > 0) {
                        const stepIndex = this.$form.find('.form-step').index($step);
                        if (firstErrorStepIndex === null || stepIndex < firstErrorStepIndex) {
                            firstErrorStepIndex = stepIndex;
                        }
                    }
                }
            }

            if (firstErrorStepIndex !== null) {
                this.goTo(firstErrorStepIndex);
            }
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

        toFormData() {
            const payload = { ...this.fields };
            const formData = new FormData();

            for (const [key, value] of Object.entries(payload)) {
                if (Array.isArray(value)) {
                    value.forEach(item => formData.append(key, item));
                } else if (value !== null && value !== undefined) {
                    formData.append(key, value);
                }
            }

            return formData;
        }

        submit() {
            const $steps = this.$form.find('.form-step');
            
            if (this.#getActiveStepIndex() !== $steps.length - 1 || !this.validate()) {
                return false;
            }

            const formData = this.toFormData();
            return this.#submitHandler?.(formData) ?? true;
        }
    }];
})();
