async function fazerLogout() {
    try {
        const resultado = await Swal.fire({
            icon: "question",
            title: "Terminar sessão?",
            text: "Será necessário iniciar sessão novamente.",
            showCancelButton: true,
            confirmButtonText: "Sair",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#dc3545",
            reverseButtons: true
        });

        if (!resultado.isConfirmed) {
            return;
        }

        Swal.fire({
            title: "A terminar sessão...",
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });

        const resposta = await fetch(
            "/erpplus/app/ajax/auth.php?action=logout",
            {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            }
        );

        const texto = await resposta.text();

        let json;

        try {
            json = JSON.parse(texto);
        } catch {
            console.error(
                "Resposta do logout:",
                texto
            );

            throw new Error(
                "O servidor retornou uma resposta inválida."
            );
        }

        if (!resposta.ok || json.ok !== true) {
            throw new Error(
                json.message
                || "Não foi possível terminar a sessão."
            );
        }

        window.location.replace(
            json.redirect
            || "/erpplus/index.php?page=login"
        );

    } catch (erro) {
        Swal.fire({
            icon: "error",
            title: "Não foi possível terminar a sessão",
            text: erro.message,
            confirmButtonText: "Fechar"
        });
    }
}