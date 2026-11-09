<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <link rel="shortcut icon" type="image/x-icon" href="public/assets/img/minilogo.png">
    <title>Registar | Nhongar Marketplace</title>

    <link rel="stylesheet" type="text/css" href="public/assets/auth/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="public/assets/auth/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" type="text/css" href="public/assets/auth/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="public/assets/auth/css/style.css">

    <style>
        .social-login-divider {
            position: relative;
            text-align: center;
            margin: 20px 0;
        }

        .social-login-divider span {
            background: #fff;
            padding: 0 12px;
            position: relative;
            z-index: 2;
            color: #777;
            font-size: 13px;
        }

        .social-login-divider:before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            height: 1px;
            background: #e5e5e5;
            z-index: 1;
        }

        .social-btn {
            width: 100%;
            height: 46px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
            color: #333;
            text-decoration: none !important;
            margin-bottom: 12px;
            transition: .2s ease;
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
                    <form id="registerForm" class="form-signin">
                        <div class="account-logo">
                            <a href="../index.php"><img src="public/assets/img/logoNhongar.png" alt="Nhongar"></a>
                        </div>
                        <div id="registerFeedback"></div>
                        <div class="form-group">
                            <label>Nome completo</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Telefone</label>
                            <input type="text" name="telefone" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Senha</label>
                            <input type="password" name="senha" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Confirmar senha</label>
                            <input type="password" name="confirmar_senha" class="form-control" required>
                        </div>

                        <div class="form-group checkbox">
                            <label>
                                <input type="checkbox" name="aceitou_termos" required> Li e aceito os Termos e Condições
                            </label>
                        </div>

                        <div class="form-group text-center">
                            <button class="btn btn-primary account-btn" type="submit">Criar conta</button>
                        </div>

                        <div class="social-login-divider">
                            <span>ou registar com</span>
                        </div>

                        <a href="index.php?page=auth_google" class="social-btn google-btn">
                            <i class="fab fa-google"></i>
                            <span>Continuar com Google</span>
                        </a>

                        <a href="index.php?page=auth_facebook" class="social-btn facebook-btn">
                            <i class="fab fa-facebook-f"></i>
                            <span>Continuar com Facebook</span>
                        </a>

                        <div class="text-center login-link mt-3">
                            Já tem conta? <a href="index.php?page=login">Entrar</a>
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