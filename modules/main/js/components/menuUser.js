const menuUser = (() => {
    function logout(e) {
        e.preventDefault();
        if (!confirm('Tem a certeza que deseja terminar sessão?')) return;
        
        const apiCall = API.auth.logout()
            .done(response => {
                location.href = `${BASE_URL ?? ''}/`;
            })
            .fail(xhr => {
                alert('Erro ao terminar sessão. Tente novamente.');
            });
    }

    return { logout };
})();
