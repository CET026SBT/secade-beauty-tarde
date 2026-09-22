const customerRegister = (() => {
    const supportedCities = [];
    const customerAddresses = [];

    const form = new Form('#customerRegisterForm', {
        validators: {
            ...userValidators,
            ...customerValidators({ supportedCities })
        },
        submit(formData) {
            API.auth.register(formData)
                .done(response => {
                    location.href = `${BASE_URL ?? ''}/login`;
                })
                .fail(xhr => {
                    const response = xhr.responseJSON;
                    if (response && response.errors) {
                        // Os erros das chaves de morada são apresentados no campo visível `street`
                        form.setErrors(response.errors, {
                            cityName: 'street'
                        });
                    }
                });
        }
    });

    /**
     * Guarda os NOMES das cidades suportadas (a API devolve objetos { id, name, district }).
     * O mesmo array é partilhado com o validador de `cityName`, que o lê por referência.
     */
    function fetchAndStoreSupportedCities() {
        return API.cities.getSupported().then(response => {
            const names = (response?.cities || []).map(city => city.name).filter(Boolean);

            supportedCities.length = 0;
            supportedCities.push(...names);

            return names;
        });
    }

    function updateCitiesTooltip(cityNames) {
        if (!cityNames || cityNames.length === 0) return;

        const $icon = $('#citiesTooltip');
        if ($icon.length === 0) return;

        $icon.attr('title', `Cidades suportadas: ${cityNames.join(', ')}`);

        const tooltipInstance = bootstrap.Tooltip.getInstance($icon[0]);
        if (tooltipInstance) {
            tooltipInstance.dispose();
        }
        new bootstrap.Tooltip($icon[0]);
    }

    $(() => {
        fetchAndStoreSupportedCities()
            .then(cityNames => updateCitiesTooltip(cityNames));

        // O autocomplete escreve diretamente nos campos pelos seus `name`
        // (street, doorNumber, floor, zipCode, cityName) e dispara `change`
        // para o Form reavaliar os validadores.
        new AddressAutocomplete({
            formSelector: form.selector,
            savedAddresses: customerAddresses,
            onSelect(selectedData) {
                form.fields.doorNumber = selectedData.doorNumber;
                form.fields.floor = selectedData.floor;
                form.fields.zipCode = selectedData.zipCode;
                form.fields.cityName = selectedData.cityName;
                form.fields.district = selectedData.district;
                form.fields.steetRaw = selectedData.steet;
                form.fields.street = this.formatAddressInputText(selectedData);
            }
        });
    });

    return { form };
})();
