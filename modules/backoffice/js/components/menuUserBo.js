const boMenuUser = (() => {
    // Terminar sessão a partir do backoffice.
    // Mesmo comportamento do menu do site principal: confirmação, chamada à API
    // e regresso à página pública.
    async function logout(e) {
        e.preventDefault();
        if (!(await generalUtils.confirmDialog({ title: 'Terminar sessão?', text: 'Tem a certeza que deseja terminar sessão?', icon: 'question' }))) return;

        API.auth.logout()
            .done(response => {
                location.href = `${BASE_URL ?? ''}/`;
            })
            .fail(xhr => {
                generalUtils.errorDialog('Erro ao terminar sessão. Tente novamente.');
            });
    }

    return { logout };
})();
