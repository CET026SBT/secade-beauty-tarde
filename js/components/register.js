const formSteps = {
    currentStep: 0,
    validateStep: [
        function() {
            const vNome = validators.nome();
            const vEmail = validators.email();
            const vPass = validators.password();
            const vConfirm = validators.confirmPassword();

            return vNome && vEmail && vPass && vConfirm;
        },
        function() {
            const vTelemovel = validators.telemovel();
            const vMorada = validators.morada();
            const vPorta = validators.numPorta();
            const vTermos = validators.termosCondicoes();

            return vTelemovel && vMorada && vPorta && vTermos;
        }
    ],
    navigateTo(step) {
        if (step > this.currentStep && !this.validateStep[this.currentStep]()) {
            return;
        }

        this.currentStep = step;
        $('.form-step').removeClass('active');
        $(`#step-${step + 1}`).addClass('active');
    }
};

const validators = {
    nome() {
        const $el = $('#nome');
        const isValid = $el.val().trim() !== '';
        $el.toggleClass('is-invalid', !isValid);
        return isValid;
    },
    email() {
        const $el = $('#email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test($el.val().trim());
        $el.toggleClass('is-invalid', !isValid);
        return isValid;
    },
    password() {
        const $el = $('#password');
        const isValid = $el.val().length >= 6;
        $el.toggleClass('is-invalid', !isValid);
        
        if ($('#confirmPassword').val() !== '') {
            this.confirmPassword();
        }
        return isValid;
    },
    confirmPassword() {
        const $pass = $('#password');
        const $el = $('#confirmPassword');
        const isValid = $el.val() !== '' && $el.val() === $pass.val();
        $el.toggleClass('is-invalid', !isValid);
        return isValid;
    },
    telemovel() {
        const $el = $('#telemovel');
        const isValid = $el.val().trim() !== '';
        $el.toggleClass('is-invalid', !isValid);
        return isValid;
    },
    morada() {
        const $el = $('#morada');
        const isValid = $el.val().trim() !== '';
        $el.toggleClass('is-invalid', !isValid);
        return isValid;
    },
    numPorta() {
        const $el = $('#numPorta');
        const isValid = $el.val().trim() !== '';
        $el.toggleClass('is-invalid', !isValid);
        return isValid;
    },
    termosCondicoes() {
        const $el = $('#termosCondicoes');
        const isValid = $el.is(':checked');
        $el.toggleClass('is-invalid', !isValid);
        return isValid;
    }
};

$(document).ready(function () {
    $('#nome').on('input', () => validators.nome());
    $('#email').on('input', () => validators.email());
    $('#password').on('input', () => validators.password());
    $('#confirmPassword').on('input', () => validators.confirmPassword());
    $('#telemovel').on('input', () => validators.telemovel());
    $('#morada').on('input', () => validators.morada());
    $('#numPorta').on('input', () => validators.numPorta());
    $('#termosCondicoes').on('change', () => validators.termosCondicoes());
});