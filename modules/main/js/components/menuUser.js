const menuUser = (() => {
    async function logout(e) {
        e.preventDefault();
        if (!await generalUtils.confirmDialog({ title: "Terminar sessão", text: "Tem a certeza que deseja terminar sessão?" })) return;
        
        const request = API.auth.logout()
            .done(response => {
                location.href = `${BASE_URL ?? ''}/`;
            })
            .fail(xhr => {
                generalUtils.alertDialog({ icon: "error", text: "Erro ao terminar sessão. Tente novamente." });
            });
    }

    return { logout };
})();
