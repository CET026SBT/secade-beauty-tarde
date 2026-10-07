const employeeValidators = {
    name(val) {
        if (val === '') return 'O nome completo é obrigatório.';
        if (val.length < 2) return 'O nome deve ter pelo menos 2 caracteres.';
    },
    email(val) {
        if (val === '') return 'O e-mail é obrigatório.';
        if (!this.isEmail(val)) return 'Insira um endereço de e-mail válido.';
    },
    phone(val) {
        if (val === '') return 'O número de telemóvel é obrigatório.';
        if (!this.isPhone(val)) return 'Insira um número de telemóvel válido.';
    },
    nif(val) {
        if (val !== '' && !this.isNIF(val)) return 'NIF inválido.';
    },
    cc(val) {
        if (val === '') return 'O número de Cartão de Cidadão é obrigatório.';
        const cleaned = String(val).toUpperCase().replace(/\s+/g, '');
        if (!/^\d{8}[0-9A-Z]{2}\d[0-9A-Z]$/.test(cleaned)) return 'Número de Cartão de Cidadão inválido.';
    },
    contractType(val) {
        if (!['efetivo_contratado', 'recibo_verde'].includes(val)) return 'Escolha o tipo de contrato.';
    },
    salary(val) {
        const contract = $('#employeeContractType').val();
        if (contract === 'efetivo_contratado' && (val === '' || val === null)) return 'O salário base é obrigatório para efetivos.';
        if (val !== '' && !/^\d{0,8}(\.\d{1,2})?$/.test(String(val))) return 'Salário inválido.';
    },
    commissionPercentage(val) {
        if (val !== '' && !/^\d{0,3}(\.\d{1,2})?$/.test(String(val))) return 'Percentagem inválida.';
    },
    irsRate(val) {
        if (val !== '' && !/^\d{0,3}(\.\d{1,2})?$/.test(String(val))) return 'Taxa de IRS inválida.';
    },
    password(val) {
        if (val === '') return 'A palavra-passe é obrigatória.';
        if (!this.isPassword(val)) return 'A password deve ter pelo menos 8 caracteres, conter pelo menos 1 letra, 1 número e 1 símbolo válido.';
    }
};
