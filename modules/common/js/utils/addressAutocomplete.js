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

        // ⚠️ A classe `show` NÃO entra na construção: `.dropdown-menu.show` do Bootstrap
        // (0,2,0) vence o `display: none` de `.autocomplete-dropdown` (0,1,0) e o dropdown
        // vazio ficava visível no carregamento da página, no canto superior esquerdo.
        // A visibilidade passa a ser controlada por `hide()`/`show()` (estilo inline).
        this.$dropdown = $(`<ul>`)
            .addClass('autocomplete-dropdown dropdown-menu')
            .css('--current-anchor', anchorName);

        $('body').append(this.$dropdown);
        this.$dropdown.hide();
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

    fetchResults(query) {
        geocodingApi.search(query)
        .done((apiResults) => {
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

        this.#positionDropdown();
        this.$dropdown.show();
    }

    /**
     * Reposiciona o dropdown por baixo do campo de rua.
     *
     * O CSS (`style.css`) usa CSS Anchor Positioning, que só existe em browsers recentes.
     * Quando essa via não está disponível — ou o anchor não resolve — o `top`/`left`/
     * `width` do CSS ficam inválidos e a caixa cai na posição estática do `<body>`.
     * Este cálculo garante a posição em qualquer browser, ficando o CSS como melhoria
     * progressiva.
     */
    #positionDropdown() {
        const supportsAnchorPositioning = typeof CSS !== 'undefined'
            && typeof CSS.supports === 'function'
            && CSS.supports('position-anchor: --x');

        if (supportsAnchorPositioning) return;

        const field = this.$street[0];
        if (!field) return;

        const rect = field.getBoundingClientRect();

        this.$dropdown.css({
            position: 'fixed',
            top: `${rect.bottom}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`
        });
    }
}