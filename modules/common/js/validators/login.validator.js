const loginValidators = {
    email(val) {
        if (val === '') return 'O e-mail é obrigatório.';
        if (!this.isEmail(val)) return 'Insira um endereço de e-mail válido.';
    },
    password(val) {
        if (val === '') return 'A palavra-passe é obrigatória.';
    }
};
