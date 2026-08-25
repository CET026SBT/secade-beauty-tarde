const register = {
    supportedCities: ['Évora'],
    savedAddresses: [],
    form: new Form('#registerForm', {
        validators: {
            nome(val) {
                return val === '' && 'O nome completo é obrigatório.';
            },
            email(val) {
                if (val === '') return 'O e-mail é obrigatório.';
                if (!this.isEmail(val)) return 'Insira um endereço de e-mail válido.';
            },
            password(val, { fields }) {
                if (val === '') return 'A palavra-passe é obrigatória.';
                if (val.length < 6) return 'A palavra-passe deve ter pelo menos 6 caracteres.';
                if (fields.confirmPassword) this.confirmPassword();
            },
            confirmPassword(val, { fields }) {
                if (val === '') return 'Confirme a sua palavra-passe.';
                if (val !== fields.password) return 'As palavras-passe têm de coincidir.';
            },
            telemovel(val) {
                if (val === '') return 'O número de telemóvel é obrigatório.';
                if (!this.isPhonePT(val)) return 'Insira um número de telemóvel válido com 9 dígitos.';
            },
            morada(val, { fields, data }) {
                if (val === '') return "A morada é obrigatória.";
                if (!data?.fromAutocomplete) return "Por favor, selecione uma morada válida a partir das sugestões da lista.";
                if (!register.supportedCities.includes(fields.cidade)) {
                    return "Lamentamos, mas de momento apenas aceitamos moradas nas cidades suportadas.";
                }
            },
            numPorta(val) {
                return val === '' && 'Obrigatório.';
            },
            termosCondicoes(val) {
                return !val && 'Deve aceitar os termos e condições para continuar.';
            }
        },
        submit() {

        }
    }),
    init() {
        new AddressAutocomplete({
            formSelector: register.form.selector,
            savedAddresses: register.savedAddresses,
            onSelect(selectedData) {
                register.form.fields.morada = this.formatAddressInputText(selectedData);
                register.form.fields.numPorta = selectedData.numPorta;
                register.form.fields.andarBloco = selectedData.andarBloco;
                register.form.fields.codigoPostal = selectedData.codigoPostal;
                register.form.fields.cidade = selectedData.cidade;
                register.form.fields.distrito = selectedData.distrito;
            }
        });
    }
};

$(register.init);
