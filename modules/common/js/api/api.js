const API = ((baseApi) => ({
    auth: {
        register: (formData) => baseApi.post('?action=auth-register', formData),
        login: (formData) => baseApi.post('?action=auth-login', formData),
        logout: () => baseApi.post('?action=auth-logout')
    }
}))(new ApiClient(`${BASE_URL ?? ''}/api`));
