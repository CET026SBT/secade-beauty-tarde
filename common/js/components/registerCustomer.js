const customerRegister = {
    supportedCities: ['Évora'],
    customerAddresses: [],
    form: new Form('#customerRegisterForm', {
        validators: {
            ...userValidators,
            ...customerValidators
        },
        submit(payload) {
            API.auth.registerCustomer(payload);
        }
    }),
    init() {
        const { form, customerAddresses } = customerRegister;
        new AddressAutocomplete({
            formSelector: form.selector,
            savedAddresses: customerAddresses,
            onSelect(selectedData) {
                form.fields.morada = this.formatAddressInputText(selectedData);
                form.fields.moradaRaw = selectedData.morada;
                form.fields.numPorta = selectedData.numPorta;
                form.fields.andarBloco = selectedData.andarBloco;
                form.fields.codigoPostal = selectedData.codigoPostal;
                form.fields.cidade = selectedData.cidade;
                form.fields.distrito = selectedData.distrito;
            }
        });
    }
};

$(customerRegister.init);
