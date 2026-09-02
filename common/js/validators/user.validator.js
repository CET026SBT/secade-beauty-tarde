const userValidators = {
    nome(val) {
        return val === '' && 'O nome completo é obrigatório.';
    },
    email(val) {
        if (val === '') return 'O e-mail é obrigatório.';
        if (!this.isEmail(val)) return 'Insira um endereço de e-mail válido.';
    },
    password(val, { fields }) {
        if (val === '') return 'A palavra-passe é obrigatória.';
        if (!this.isPassword(val)) return 'A password deve ter pelo menos 8 caracteres, conter pelo menos 1 letra, 1 número e 1 símbolo válido.';
        if (fields.confirmPassword) this.confirmPassword();
    },
    confirmPassword(val, { fields }) {
        if (val === '') return 'Confirme a sua palavra-passe.';
        if (val !== fields.password) return 'As palavras-passe têm de coincidir.';
    },
    telemovel(val) {
        if (val === '') return 'O número de telemóvel é obrigatório.';
        if (!this.isPhonePT(val)) return 'Insira um número de telemóvel válido com 9 dígitos.';
    },
    nif(val) {
        if (val === '') return "O NIF é obrigatório.";
        if (!this.isNIF(val)) return "NIF inválido.";
    },
    tipoPerfil(val) {
        if (!val) return 'O tipo de perfil é obrigatório.';
    }
};
