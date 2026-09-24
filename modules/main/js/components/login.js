const login = (() => {
    // Cada perfil aterra na sua área: gestor e funcionário vinham parar à home
    // pública porque o destino estava fixo em `/`.
    function homeUrlFor(profileType) {
        const base = BASE_URL ?? '';

        if (profileType === 'gestor') return `${base}/gestao/agendamentos`;
        if (profileType === 'funcionario') return `${base}/gestao/servicos`;

        return `${base}/`;
    }

    const form = new Form('#loginForm', {
        validators: loginValidators,
        submit(formData) {
            const request = API.auth.login(formData)
                .done(response => {
                    location.href = homeUrlFor(response?.user?.profileType);
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

    return { form, homeUrlFor };
})();

