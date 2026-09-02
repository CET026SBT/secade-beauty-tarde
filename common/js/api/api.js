const API = ((baseApi) => ({
    auth: {
        register: (formData) => baseApi.post('?action=auth-register', JSON.stringify(formData)),
        login: (formData) => baseApi.post('?action=auth-login', JSON.stringify(formData)),
        logout: () => baseApi.post('?action=auth-logout')
    }
}))(new ApiClient(`${BASE_URL ?? ''}/api`));
