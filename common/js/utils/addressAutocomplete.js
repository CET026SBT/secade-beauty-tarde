class AddressAutocomplete {
    formSelector;
    savedAddresses;
    onSelect;
    $form;
    $address;
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
        this.$address = this.$form.find('[name="morada"]');
        
        this.$address.css('anchor-name', anchorName);

        this.$door = this.$form.find('[name="numPorta"]');
        this.$floor = this.$form.find('[name="andarBloco"]');
        this.$zipCode = this.$form.find('[name="codigoPostal"]');
        this.$city = this.$form.find('[name="cidade"]');
        this.$district = this.$form.find('[name="distrito"]');

        this.#$allFields = this.$address
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
        this.$address.off(namespace);
        $(document.body).off(namespace);

        this.$dropdown.on(`click${namespace}`, 'li', (e) => {
            const $li = $(e.currentTarget);
            const selectedData = $li.data('address');
            
            this.$address.val(this.formatAddressInputText(selectedData) || '');
            this.$door.val(selectedData.numPorta || '');
            this.$floor.val(selectedData.andarBloco || '');
            this.$zipCode.val(selectedData.codigoPostal || '');
            this.$city.val(selectedData.cidade || '');
            this.$district.val(selectedData.distrito || '');
            
            this.$dropdown.hide().empty();

            this.#$allFields.data(selectedData);
            
            if (typeof this.onSelect === 'function') {
                this.onSelect(selectedData);
            } else {
                this.#$allFields.trigger('change', data);
            }
        });

        this.$address.on(`input${namespace}`, () => {
            this.#$allFields.removeData('fromAutocomplete');

            const query = this.$address.val().trim();
            clearTimeout(this.#debounceTimer);

            if (query.length < 3) {
                this.renderList(this.savedAddresses);
                return;
            }

            this.#debounceTimer = setTimeout(() => {
                this.fetchResults(query);
            }, 300);
        });

        this.$address.on(`focus${namespace}`, () => {
            if (this.$address.val().trim().length < 3 && this.savedAddresses.length > 0) {
                this.renderList(this.savedAddresses);
            }
        });

        $(document.body).on(`click${namespace}`, (e) => {
            if (!$(e.target).closest(this.$address).length && !$(e.target).closest(this.$dropdown).length) {
                this.$dropdown.hide();
            }
        });
    }

    #formatAddress(addressObj, { includeAll }) {
        const parts = [
            addressObj.morada,
            addressObj.numPorta && (includeAll || this.$door.length === 0) ? `Nº ${addressObj.numPorta}` : null,
            addressObj.codigoPostal && (includeAll || this.$zipCode.length === 0) ? addressObj.codigoPostal : null,
            addressObj.cidade && (includeAll || this.$city.length === 0) ? addressObj.cidade : null
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

        const moradaParts = [
            item.name,
            addr.road || addr.pedestrian || addr.square,
            addr.industrial,
            addr.leisure
        ].filter(Boolean);

        const uniqueMorada = [...new Set(moradaParts)].join(', ');
        
        return {
            isSaved: false,
            morada: uniqueMorada,
            numPorta: addr.house_number || '',
            andarBloco: '',
            codigoPostal: addr.postcode || '',
            cidade: addr.city || addr.town || addr.village || addr.hamlet || '',
            distrito: addr.county || '',
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
                    .filter(a => (a.morada || '').toLowerCase().includes(query.toLowerCase())),
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
                .html(`${domUtils.escapeHtml(text)} ${isSaved}`)
                .data('address', item);

            this.$dropdown.append($li);
        }

        this.$dropdown.show();
    }
}