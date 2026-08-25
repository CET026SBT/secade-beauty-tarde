const [FormValidators, Form] = (() => {
    function normalize($el) {
        const type = $el.prop('type');
        const tag = $el.prop('tagName').toLowerCase();
        const val = $el.val();

        if (type === 'file') {
            const files = $el[0].files;
            if (!files || files.length === 0) return null;
            return $el.prop('multiple') ? [...files] : files[0];
        }

        if (tag === 'select' && $el.prop('multiple')) {
            return val || [];
        }

        if ($el.length > 1 && type === 'checkbox') {
            return [...$el.filter(':checked')].map(el => el.value);
        }

        if ($el.length > 1 && type === 'radio') {
            return $el.filter(':checked').val() || '';
        }

        if (type === 'checkbox') return $el.is(':checked');
        if (type === 'radio') return $el.is(':checked') ? val : '';
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

        #validate(selector, validationFn, fields, event, data) {
            const $el = $(selector);
            const val = normalize($el);
            const $feedback = $el.siblings('.invalid-feedback');
            
            const errorMsg = validationFn.call(this, val, { $el, fields, event, data });
            $feedback.text(errorMsg || '');

            $el.toggleClass('is-invalid', !!errorMsg);
            return !errorMsg;
        }

        isEmail(value) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(String(value).trim());
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
    },
    class Form {
        selector;
        validators;
        fields;
        #submit;
        
        constructor(selector, { validators, submit }) {
            this.selector = selector;
            this.#submit = submit;
            
            this.fields = new Proxy({}, {
                get: (target, prop) => {
                    const $el = $(`${this.selector} [name="${prop}"]`);
                    if ($el.length > 0) return normalize($el);
                    return target[prop];
                },
                set: (target, prop, value) => {
                    const $el = $(`${this.selector} [name="${prop}"]`);
                    if ($el.length > 0) {
                        $el.val(value).trigger('change');
                    }
                    target[prop] = value;
                    return true;
                }
            });

            this.validators = new FormValidators(validators, this.fields, selector);

            $(() => {
                for (const fieldName of Object.keys(validators)) {
                    const $el = $(this.selector).find(`[name="${fieldName}"]`);
                    const event = $el.attr('sb-validate-on') || 'input change';
                    $(this.selector).on(event, `[name="${fieldName}"]`, this.validators[fieldName]);
                }
            });
        }

        #getActiveStep() {
            return $(`${this.selector} .form-step.active`);
        }

        #getActiveStepIndex() {
            return this.#getActiveStep().index(`${this.selector} .form-step`);
        }

        validateStep() {
            const $formStep = this.#getActiveStep();
            let isValid = true;

            for (const field of Object.keys(this.validators)) {
                const $field = $formStep.find(`[name="${field}"]`);
                if ($field.length > 0 && !this.validators[field]()) {
                    isValid = false;
                }
            }

            return isValid;
        }

        goTo(targetIndex) {
            const $steps = $(`${this.selector} .form-step`);
            const currentIndex = this.#getActiveStepIndex();

            if (targetIndex < 0 || targetIndex >= $steps.length) return false;
            if (targetIndex > currentIndex && !this.validateStep()) return false;

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
            const $steps = $(`${this.selector} .form-step`);
            
            if (this.#getActiveStepIndex() !== $steps.length - 1 || !this.validateStep()) {
                return false;
            }

            return this.#submit?.() ?? true;
        }
    }];
})();
