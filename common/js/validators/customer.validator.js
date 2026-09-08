const customerValidators = {
    morada(val, { fields, data }) {
        if (val === '') return "A morada é obrigatória.";
        if (!data?.fromAutocomplete) return "Por favor, selecione uma morada válida a partir das sugestões da lista.";
        // sbTODO: Não podemos aceder a customerRegister diretamente, pois este validator pode ser usado noutros ficheiros
        if (!customerRegister.supportedCities.includes(fields.cidade)) { 
            return `Lamentamos, mas de momento apenas aceitamos moradas nas cidades suportadas. (${customerRegister.supportedCities.join(', ')})`;
        }
    },
    codigoPostal(val) {
        if (val === '') return 'O Código Postal é obrigatório';
        if (!this.isZipCode(val)) return 'Código postal inválido (formato 0000-000).';
    },
    numPorta(val) {
        return val === '' && 'Obrigatório.';
    },
    termosCondicoes(val) {
        return !val && 'Deve aceitar os termos e condições para continuar.'; 
    },
    cc(val) {
        if (val === '') return "O Cartão de Cidadão é obrigatório.";
        if (!this.isCC(val)) return "Formato de Cartão de Cidadão inválido.";
    }
};
