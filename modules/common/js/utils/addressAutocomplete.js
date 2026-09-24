class AddressAutocomplete {
    // Os atributos `name` dos campos do formulário refletem, de forma transparente,
    // as chaves que a API espera (ver CustomerAddressService::validateInput e
    // os mappers do servidor). Alterar aqui = alterar o contrato do formulário.
    static FIELD_NAMES = {
        street:     'street',
        doorNumber: 'doorNumber',
        floor:      'floor',
        zipCode:    'zipCode',
        cityName:   'cityName'
    };

    formSelector;
    savedAddresses;
    onSelect;
    $form;
    $street;
    $door;
    $floor;
    $zipCode;
    $city;
    $district;
    $dropdown;
    #instanceId;
    #debounceTimer;
    #$allFields;

    constructor({ formSelector, savedAddresses=[], onSelect }) {
        this.#instanceId = Math.random().toString(36).substring(2, 9);
        this.formSelector = formSelector;
        this.savedAddresses = savedAddresses;
        this.onSelect = onSelect;
        this.#debounceTimer = null;

        this.#initDOM();
        this.#initEvents();
    }

    #initDOM() {
        const anchorName = `--address-${this.#instanceId}`;

        this.$form = $(this.formSelector);
        this.$street = this.$form.find(`[name="${AddressAutocomplete.FIELD_NAMES.street}"]`);

        this.$street.css('anchor-name', anchorName);

        this.$door    = this.$form.find(`[name="${AddressAutocomplete.FIELD_NAMES.doorNumber}"]`);
        this.$floor   = this.$form.find(`[name="${AddressAutocomplete.FIELD_NAMES.floor}"]`);
        this.$zipCode = this.$form.find(`[name="${AddressAutocomplete.FIELD_NAMES.zipCode}"]`);
        this.$city    = this.$form.find(`[name="${AddressAutocomplete.FIELD_NAMES.cityName}"]`);
        this.$district = this.$form.find('[name="district"]');

        this.#$allFields = this.$street
            .add(this.$door)
            .add(this.$floor)
            .add(this.$zipCode)
            .add(this.$city)
            .add(this.$district);

        this.$dropdown = $(`<ul>`)
            .addClass('autocomplete-dropdown dropdown-menu show')
            .css('--current-anchor', anchorName);

        $('body').append(this.$dropdown);
    }

    #initEvents() {
        const namespace = `.addressAutocomplete_${this.#instanceId}`;

        this.$dropdown.off(namespace);
        this.$street.off(namespace);
        $(document.body).off(namespace);

        this.$dropdown.on(`click${namespace}`, 'li', (e) => {
            const $li = $(e.currentTarget);
            const selectedData = $li.data('address');
            
            this.$street.val(this.formatAddressInputText(selectedData) || '');
            this.$door.val(selectedData.doorNumber || '');
            this.$floor.val(selectedData.floor || '');
            this.$zipCode.val(selectedData.zipCode || '');
            this.$city.val(selectedData.cityName || '');
            this.$district.val(selectedData.district || '');
            
            this.$dropdown.hide().empty();

            const data = { fromAutocomplete: true };
            this.#$allFields.data(data);
            
            if (typeof this.onSelect === 'function') {
                this.onSelect(selectedData);
            } else {
                this.#$allFields.trigger('change', data);
            }
        });

        this.$street.on(`input${namespace}`, () => {
            this.#$allFields.removeData('fromAutocomplete');

            const query = this.$street.val().trim();
            clearTimeout(this.#debounceTimer);

            if (query.length < 3) {
                this.renderList(this.savedAddresses);
                return;
            }

            this.#debounceTimer = setTimeout(() => {
                this.fetchResults(query);
            }, 300);
        });

        this.$street.on(`focus${namespace}`, () => {
            if (this.$street.val().trim().length < 3 && this.savedAddresses.length > 0) {
                this.renderList(this.savedAddresses);
            }
        });

        $(document.body).on(`click${namespace}`, (e) => {
            if (!$(e.target).closest(this.$street).length && !$(e.target).closest(this.$dropdown).length) {
                this.$dropdown.hide();
            }
        });
    }

    #formatAddress(addressObj, { includeAll }) {
        const parts = [
            addressObj.street,
            addressObj.doorNumber && (includeAll || this.$door.length === 0) ? `Nº ${addressObj.doorNumber}` : null,
            addressObj.zipCode && (includeAll || this.$zipCode.length === 0) ? addressObj.zipCode : null,
            addressObj.cityName && (includeAll || this.$city.length === 0) ? addressObj.cityName : null
        ];

        return parts.filter(Boolean).join(', ');
    }

    formatDropdownText(addressObj) {
        return this.#formatAddress(addressObj, { includeAll: true });
    }

    formatAddressInputText(addressObj) {
        return this.#formatAddress(addressObj, { includeAll: false });
    }

    #mapApiResponseToAddress(item) {
        const addr = item.address || {};

        // Rua: preferir o nome da via; o nome do resultado é o fallback.
        const streetParts = [
            addr.road || addr.pedestrian || addr.square,
            addr.industrial,
            addr.leisure
        ].filter(Boolean);

        return {
            isSaved: false,
            street: [...new Set(streetParts)].join(', ') || item.name || '',
            doorNumber: addr.house_number || '',
            floor: '',
            zipCode: addr.postcode || '',
            cityName: addr.city || addr.town || addr.village || addr.hamlet || '',
            district: addr.county || '',
            raw: item
        };
    }

    fetchResults(query) {
        $.get('https://nominatim.openstreetmap.org/search', {
            q: query,
            countrycodes: 'pt',
            format: 'json',
            addressdetails: 1,
            limit: 5
        })
        .done((response) => {
            const apiResults = (Array.isArray(response) ? response : [])
                .slice(0, 5)
                .map(item => this.#mapApiResponseToAddress(item));
            
            const combined = [
                ...this.savedAddresses
                    .map(a => ({ ...a }))
                    .filter(a => (a.street || '').toLowerCase().includes(query.toLowerCase())),
                ...apiResults
            ];

            this.renderList(combined);
        })
        .fail(() => {
            this.$dropdown.hide();
        });
    }

    renderList(items) {
        this.$dropdown.empty();

        if (items.length === 0) {
            this.$dropdown.hide();
            return;
        }

        for (const item of items) {
            const text = this.formatDropdownText(item);
            const isSaved = item.isSaved ? ' <span class="badge bg-secondary ms-2">Guardada</span>' : '';

            const $li = $('<li>')
                .addClass('dropdown-item cursor-pointer text-wrap text-break py-2')
                .html(`${generalUtils.escapeHtml(text)} ${isSaved}`)
                .data('address', item);

            this.$dropdown.append($li);
        }

        this.$dropdown.show();
    }
}