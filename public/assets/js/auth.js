(function () {

    const APP_URL = "/erpplus";
    const API = APP_URL + "/app/ajax/auth.php";

    function qs(params = {}) {
        return new URLSearchParams(params).toString();
    }

    async function loadJson(url, opts = {}) {
        const response = await fetch(url, opts);
        return await response.json();
    }

    function showMessage(target, type, message) {

        const box = document.getElementById(target);

        if (!box) return;

        box.innerHTML =
            `<div class="alert alert-${type}">${message}</div>`;
    }

    async function post(action, form) {

        const fd = new FormData(form);

        return await loadJson(
            `${API}?${qs({ action })}`,
            {
                method: "POST",
                body: fd
            }
        );
    }

    const loginForm = document.getElementById("loginForm");

    if (loginForm) {

        loginForm.addEventListener("submit", async function (e) {

            e.preventDefault();

            try {

                const json = await post("login", loginForm);

                if (!json.ok)
                    throw new Error(json.message);

                showMessage(
                    "loginFeedback",
                    "success",
                    json.message
                );

                setTimeout(function () {

                    location.href = json.redirect;

                }, 700);

            } catch (err) {

                showMessage(
                    "loginFeedback",
                    "danger",
                    err.message
                );

            }

        });

    }

    const registerForm = document.getElementById("registerForm");

    if (registerForm) {

        registerForm.addEventListener("submit", async function (e) {

            e.preventDefault();

            try {

                const json = await post("register", registerForm);

                if (!json.ok)
                    throw new Error(json.message);

                showMessage(
                    "registerFeedback",
                    "success",
                    json.message
                );

                setTimeout(function () {

                    location.href = json.redirect;

                }, 900);

            } catch (err) {

                showMessage(
                    "registerFeedback",
                    "danger",
                    err.message
                );

            }

        });

    }

})();