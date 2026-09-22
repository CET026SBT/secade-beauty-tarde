const login = (() => {
    const form = new Form('#loginForm', {
        validators: loginValidators,
        submit(formData) {
            const request = API.auth.login(formData)
                .done(response => {
                    location.href = `${BASE_URL ?? ''}/`;
                })
                .fail(xhr => {
                    const response = xhr.responseJSON;
                    if (response && response.errors) {
                        form.setErrors(response.errors);
                    }
                });

            // sbTODO: Testar
            //$('#loginForm').preloader('.jq-overlay-process', request);
        }
    });

    return { form };
})();

