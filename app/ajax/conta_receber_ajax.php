<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);

/*
 * Evita que avisos PHP sejam misturados
 * com o JSON e provoquem:
 *
 * Unexpected token '<'
 */

ini_set(
    'display_errors',
    '0'
);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/ContaReceber.php';

require_once __DIR__
    . '/../controllers/ContaReceberController.php';

Auth::iniciarSessao();

/*
|--------------------------------------------------------------------------
| AJAX nunca deve redirecionar para HTML
|--------------------------------------------------------------------------
*/

if (!Auth::check()) {
    http_response_code(401);

    echo json_encode([
        'success' => false,

        'mensagem' =>
        'Sessão expirada. Faça login novamente.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $pdo = Database::conn();

    $controller =
        new ContaReceberController($pdo);

    $acao = strtolower(
        trim(
            (string)(
                $_REQUEST['acao'] ?? ''
            )
        )
    );

    switch ($acao) {

        // ==================================
        // LISTAR
        // ==================================

        case 'listar':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->listar()
            ], JSON_UNESCAPED_UNICODE);

            break;

        case 'parcelas':

            $contaId =
                (int)(
                    $_GET['conta_id']
                    ?? $_GET['id']
                    ?? 0
                );


            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->parcelas(
                        $contaId
                    )
            ], JSON_UNESCAPED_UNICODE);

            break;

        case 'historico_recebimentos':
            $historico = $controller->historicoRecebimentos($_GET);
            echo json_encode([
                'success' => true,
                'dados' => $historico
            ]);
            break;
        // ==================================
        // BUSCAR
        // ==================================

        case 'buscar':

            $conta =
                $controller->buscar(
                    (int)(
                        $_GET['id'] ?? 0
                    )
                );

            if (!$conta) {
                http_response_code(404);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Conta a receber não encontrada.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            echo json_encode([
                'success' => true,
                'data' => $conta
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==================================
        // SALVAR
        // ==================================

        case 'salvar':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {
                http_response_code(405);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Método inválido para guardar.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            $resultado =
                $controller->salvar(
                    $_POST
                );

            $edicao =
                !empty($_POST['id']);

            echo json_encode([
                'success' => true,

                'mensagem' =>
                $edicao
                    ? 'Conta a receber actualizada com sucesso.'
                    : 'Conta a receber criada com sucesso.',

                'data' => [
                    'id' =>
                    $edicao
                        ? (int)$_POST['id']
                        : (int)$resultado
                ]
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==================================
        // ELIMINAR
        // ==================================

        case 'eliminar':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {
                http_response_code(405);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Método inválido para eliminar.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            $resultado =
                $controller->eliminar(
                    (int)(
                        $_POST['id'] ?? 0
                    )
                );

            echo json_encode([
                'success' =>
                $resultado,

                'mensagem' =>
                $resultado
                    ? 'Conta a receber eliminada com sucesso.'
                    : 'Não foi possível eliminar a conta.'
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==================================
        // RECEBER
        // ==================================

        case 'receber':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {
                http_response_code(405);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Método inválido para recebimento.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            $resultado =
                $controller->receber(
                    $_POST
                );

            echo json_encode([
                'success' =>
                $resultado,

                'mensagem' =>
                $resultado
                    ? 'Recebimento registado com sucesso.'
                    : 'Não foi possível registar o recebimento.'
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==================================
        // CLIENTES
        // ==================================

        case 'clientes':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->clientes()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==================================
        // FORMAS DE PAGAMENTO
        // ==================================

        case 'formaspagamento':
        case 'formas_pagamento':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->formasPagamento()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==================================
        // AÇÃO INVÁLIDA
        // ==================================

        default:

            http_response_code(400);

            echo json_encode([
                'success' => false,

                'mensagem' =>
                'Acção inválida para contas a receber.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,

        'mensagem' =>
        $e->getMessage(),

        /*
         * Remova linha e arquivo em produção.
         */

        'linha' =>
        $e->getLine(),

        'arquivo' =>
        $e->getFile()
    ], JSON_UNESCAPED_UNICODE);
}
