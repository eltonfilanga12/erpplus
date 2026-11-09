<!DOCTYPE html>
<html lang="pt">

<head>


    <?php


    if (
        session_status()
        !== PHP_SESSION_ACTIVE
    ) {
        session_start();
    }

    if (
        empty($_SESSION['csrf_token'])
        || !is_string(
            $_SESSION['csrf_token']
        )
    ) {
        $_SESSION['csrf_token'] =
            bin2hex(
                random_bytes(32)
            );
    }

    $csrfToken =
        $_SESSION['csrf_token'];

    ?>

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>
        ERP Plus | Sistema Integrado de Gestão Empresarial
    </title>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no">

    <meta
        name="csrf-token"
        content="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">

    <meta name="description" content="ERP Plus - Sistema Integrado de Gestão Empresarial. Gestão de Empresas, Vendas, Compras, Estoque, Finanças, Recursos Humanos e Relatórios.">

    <meta name="keywords" content="ERP, ERP Plus, Gestão Empresarial, Sistema ERP, Vendas, Compras, Estoque, Finanças, RH">

    <meta name="author" content="ERP Plus">

    <meta name="robots" content="noindex,nofollow">

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="/erpplus/public/assets/img/minilogo.png">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="/erpplus/public/assets/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="/erpplus/public/assets/css/font-awesome.min.css">

    <!-- Line Awesome -->
    <link rel="stylesheet" href="/erpplus/public/assets/css/line-awesome.min.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">
    <link
        href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
        rel="stylesheet" />

    <!-- Morris Charts -->
    <link rel="stylesheet" href="/erpplus/public/assets/plugins/morris/morris.css">

    <!-- ERP Plus CSS -->
    <link rel="stylesheet" href="/erpplus/public/assets/css/style.css">

</head>

<body>

    <div class="main-wrapper">