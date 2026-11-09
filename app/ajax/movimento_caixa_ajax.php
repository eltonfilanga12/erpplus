<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/MovimentoCaixa.php';

require_once __DIR__
    . '/../controllers/MovimentoCaixaController.php';


Auth::requireLogin();


try {

    $pdo =
        Database::conn();


    $controller =
        new MovimentoCaixaController(
            $pdo
        );


    $acao =
        $_REQUEST['acao']
        ?? '';


    switch ($acao) {


        // =====================================
        // LISTAR
        // =====================================

        case 'listar':

            echo json_encode([
                'success' =>
                true,

                'data' =>
                $controller->listar(
                    $_REQUEST
                )
            ]);

            break;


        // =====================================
        // BUSCAR
        // =====================================

        case 'buscar':

            $movimento =
                $controller->buscar(
                    $_REQUEST
                );


            if (!$movimento) {

                throw new RuntimeException(
                    'Movimento de caixa não encontrado.'
                );
            }


            echo json_encode([
                'success' =>
                true,

                'data' =>
                $movimento
            ]);

            break;


        // =====================================
        // REGISTAR
        // =====================================

        case 'registrar':

            $movimentoId =
                $controller->registrar(
                    $_POST
                );


            echo json_encode([
                'success' =>
                true,

                'mensagem' =>
                'Movimento de caixa registado com sucesso.',

                'data' => [
                    'movimento_id' =>
                    $movimentoId
                ]
            ]);

            break;


        // =====================================
        // TRANSFERIR
        // =====================================

        case 'transferir':

            $transferencia =
                $controller->transferir(
                    $_POST
                );


            echo json_encode([
                'success' =>
                true,

                'mensagem' =>
                'Transferência entre caixas realizada com sucesso.',

                'data' =>
                $transferencia
            ]);

            break;


        // =====================================
        // EXTRATO
        // =====================================

        case 'extrato':

            echo json_encode([
                'success' =>
                true,

                'data' =>
                $controller->extrato(
                    $_REQUEST
                )
            ]);

            break;


        // =====================================
        // SALDO
        // =====================================

        case 'saldo':

            echo json_encode([
                'success' =>
                true,

                'saldo' =>
                $controller->saldo(
                    $_REQUEST
                )
            ]);

            break;


        // =====================================
        // CAIXAS ABERTOS
        // =====================================

        case 'caixas_abertos':

            echo json_encode([
                'success' =>
                true,

                'data' =>
                $controller
                    ->caixasAbertos()
            ]);

            break;


        default:

            http_response_code(
                400
            );


            echo json_encode([
                'success' =>
                false,

                'mensagem' =>
                'Ação inválida.'
            ]);
    }
} catch (Throwable $e) {

    http_response_code(
        500
    );


    echo json_encode([
        'success' =>
        false,

        'mensagem' =>
        $e->getMessage()
    ]);
}
