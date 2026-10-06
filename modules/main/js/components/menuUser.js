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

    /**
     * Badge dos lembretes no menu do cliente (C-07 · F9).
     *
     * Só corre para clientes — a API do cliente responde 403 a gestor/funcionário,
     * e aqui não se fazem pedidos que se sabem falhados.
     */
    async function loadClientAlertsBadge() {
        const badge = document.getElementById("menuUserAlertsBadge");
        if (!badge) return;

        try {
            const response = await API.customer.alertsCount();
            const count = Number(response?.count || 0);

            badge.textContent = count;
            badge.classList.toggle("d-none", count === 0);
        } catch (error) {
            badge.classList.add("d-none");
        }
    }

    $(() => {
        loadClientAlertsBadge();
    });

    return { logout, loadClientAlertsBadge };
})();
