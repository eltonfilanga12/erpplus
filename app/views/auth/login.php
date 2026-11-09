<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <link rel="shortcut icon" type="image/x-icon" href="public/assets/img/minilogo.png">
    <title>ERP Plus | Sistema Integrado de Gestão Empresarial</title>

    <meta name="description" content="ERP Plus - Sistema Integrado de Gestão Empresarial. Gestão de Empresas, Vendas, Compras, Estoque, Finanças, Recursos Humanos e Relatórios.">

    <meta name="keywords" content="ERP, ERP Plus, Gestão Empresarial, Sistema ERP, Vendas, Compras, Estoque, Finanças, RH">

    <link rel="stylesheet" type="text/css" href="public/assets/auth/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="public/assets/auth/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" type="text/css" href="public/assets/auth/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="public/assets/auth/css/style.css">

    <style>
        .social-login-separator {
            position: relative;
            text-align: center;
            margin: 20px 0;
        }

        .social-login-separator span {
            background: #fff;
            padding: 0 12px;
            position: relative;
            z-index: 2;
            color: #888;
            font-size: 13px;
        }

        .social-login-separator:before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 1px;
            background: #e5e5e5;
            z-index: 1;
        }

        .social-btn {
            width: 100%;
            border-radius: 6px;
            font-weight: 600;
            padding: 10px 14px;
            margin-bottom: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none !important;
        }

        .social-btn:hover {
            background: #f8f9fa;
            color: #111;
        }

        .google-btn i {
            color: #db4437;
        }

        .facebook-btn i {
            color: #1877f2;
        }
    </style>
</head>

<body>
    <div class="main-wrapper account-wrapper">
        <div class="account-page">
            <div class="account-center">
                <div class="account-box">

                    <form id="loginForm" class="form-signin" >
                        <div class="account-logo">
                            <a href="index.php?page=login">
                                <img src="public/assets/img/logo.png" alt="ERP Plus | Sistema Integrado de Gestão Empresarial">
                            </a>
                        </div>

                        <div class="form-group">
                            <label>Email ou Nome de Utilizador</label>
                            <input type="text" name="login" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Palavra-passe</label>
                            <input type="password" name="senha" class="form-control" required>
                        </div>

                        <div class="form-group text-center">
                            <a href="index.php?page=forgot_password">Esqueceu a sua palavra-passe?</a>
                        </div>
                        <div id="loginFeedback"></div>
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary account-btn">Entrar</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="public/assets/auth/js/jquery-3.5.1.min.js"></script>
    <script src="public/assets/auth/js/popper.min.js"></script>
    <script src="public/assets/auth/js/bootstrap.min.js"></script>
    <script src="public/assets/auth/js/app.js"></script>
    <script src="public/assets/js/auth.js"></script>
</body>

</html>