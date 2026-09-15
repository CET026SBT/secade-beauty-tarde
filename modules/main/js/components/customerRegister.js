const customerRegister = (() => {
    const supportedCities = [];
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

    function fetchAndStoreSupportedCities() {
        return API.cities.getSupported().then(response => {
            const cities = response.cities || [];
            supportedCities.length = 0;
            supportedCities.push(...cities);
            return cities;
        });
    }

    function updateCitiesTooltip(cities) {
        if (!cities || cities.length === 0) return;

        const tooltipText = `Cidades suportadas: ${cities.join(', ')}`;
        const $icon = $('#citiesTooltip');
        $icon.attr('title', tooltipText);

        const tooltipInstance = bootstrap.Tooltip.getInstance($icon[0]);
        if (tooltipInstance) {
            tooltipInstance.dispose();
        }
        new bootstrap.Tooltip($icon[0]);
    }

    $(() => {
        fetchAndStoreSupportedCities()
            .then(cities => updateCitiesTooltip(cities));

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

