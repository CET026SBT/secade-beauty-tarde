// Chaves = atributos `name` dos campos = chaves que a API espera
// (ver CustomerAddressService::validateInput e CustomerService::validateInput).
const customerValidators = ({ supportedCities=[] }) => ({
    street(val, { data }) {
        if (val === '') return "A morada é obrigatória.";
        if (!data?.fromAutocomplete) return "Por favor, selecione uma morada válida a partir das sugestões da lista.";
    },
    doorNumber(val) {
        return val === '' && 'Obrigatório.';
    },
    zipCode(val) {
        if (val === '') return 'O Código Postal é obrigatório';
        if (!this.isZipCode(val)) return 'Código postal inválido (formato 0000-000).';
    },
    cityName(val) {
        if (val === '') return 'A cidade é obrigatória.';
        if (!supportedCities.includes(val)) {
            return `Lamentamos, mas de momento apenas aceitamos moradas nas cidades suportadas. (${supportedCities.join(', ')})`;
        }
    },
    termsAccepted(val) {
        return !val && 'Deve aceitar os termos e condições para continuar.'; 
    }
});
