const customerRegister = (() => {
    const supportedCities = ['Évora'];
    const customerAddresses = [];

    const form = new Form('#customerRegisterForm', {
        validators: {
            ...userValidators,
            ...customerValidators(supportedCities)
        },
        submit(formData) {
            API.auth.register(formData)
                .done(response => {
                    location.href = `${BASE_URL ?? ''}/login`;
                })
                .fail(xhr => {
                    const response = xhr.responseJSON;
                    if (response && response.errors) {
                        form.setErrors(response.errors, {
                            cidade: 'morada'
                        });
                    }
                });
        }
    });

    $(() => {
        new AddressAutocomplete({
            formSelector: form.selector,
            savedAddresses: customerAddresses,
            onSelect(selectedData) {
                form.fields.numPorta = selectedData.numPorta;
                form.fields.andarBloco = selectedData.andarBloco;
                form.fields.codigoPostal = selectedData.codigoPostal;
                form.fields.cidade = selectedData.cidade;
                form.fields.distrito = selectedData.distrito;
                form.fields.moradaRaw = selectedData.morada;
                form.fields.morada = this.formatAddressInputText(selectedData);
            }
        });
    });

    return { form };
})();

