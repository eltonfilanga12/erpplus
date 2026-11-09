<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);

/*
 * Durante o desenvolvimento podes manter.
 * Em produção, usa display_errors = 0.
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
    . '/../models/ContaPagar.php';

require_once __DIR__
    . '/../controllers/ContaPagarController.php';

Auth::iniciarSessao();

/*
|--------------------------------------------------------------------------
| AJAX não deve redirecionar para uma página HTML
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

$acao = strtolower(
    trim(
        (string)(
            $_REQUEST['acao'] ?? ''
        )
    )
);

try {
    switch ($acao) {

        // =================================
        // LISTAR
        // =================================

        case 'listar':

            echo json_encode([
                'success' => true,

                'data' =>
                ContaPagarController::listar()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =================================
        // OBTER
        // =================================

        case 'obter':

            $conta =
                ContaPagarController::obter(
                    (int)(
                        $_GET['id'] ?? 0
                    )
                );

            if (!$conta) {
                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'mensagem' =>
                    'Conta a pagar não encontrada.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            echo json_encode([
                'success' => true,
                'data' => $conta
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =================================
        // GUARDAR
        // =================================

        case 'guardar':

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
                ContaPagarController::guardar(
                    $_POST
                );

            $edicao =
                !empty($_POST['id']);

            echo json_encode([
                'success' => true,

                'mensagem' =>
                $edicao
                    ? 'Conta a pagar actualizada com sucesso.'
                    : 'Conta a pagar criada com sucesso.',

                'data' => [
                    'id' =>
                    $edicao
                        ? (int)$_POST['id']
                        : (int)$resultado
                ]
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =================================
        // ELIMINAR
        // =================================

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
                ContaPagarController::eliminar(
                    (int)(
                        $_POST['id'] ?? 0
                    )
                );

            echo json_encode([
                'success' =>
                $resultado,

                'mensagem' =>
                $resultado
                    ? 'Conta a pagar eliminada com sucesso.'
                    : 'Não foi possível eliminar a conta.'
            ], JSON_UNESCAPED_UNICODE);

            break;

        // HISTÓRICO DE PAGAMENTOS
        // =================================

        case 'historico_pagamentos':

            // Chama o método estático do Controller enviando o ID
            $id = (int)($_REQUEST['id'] ?? 0);

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'mensagem' => 'ID de conta inválido.'
                ], JSON_UNESCAPED_UNICODE);
                break;
            }

            // O controller deve imprimir ou retornar o array de histórico
            $dadosHistorico = ContaPagarController::getHistoricoPagamentos($id);

            echo json_encode([
                'success' => true,
                'data' => $dadosHistorico
            ], JSON_UNESCAPED_UNICODE);

            break;

        // =================================
        // PAGAR
        // =================================

        case 'pagar':

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);

                echo json_encode([
                    'success' => false,
                    'mensagem' => 'Método inválido para pagamento.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            $resultado = ContaPagarController::pagar(
                $_POST,
                $_FILES
            );

            echo json_encode([
                'success' => $resultado,
                'mensagem' => $resultado
                    ? 'Pagamento registado com sucesso.'
                    : 'Não foi possível registar o pagamento.'
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =================================
        // AÇÃO INVÁLIDA
        // =================================

        default:

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'mensagem' =>
                'Acção inválida para contas a pagar.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,

        'mensagem' =>
        $e->getMessage(),

        'linha' =>
        $e->getLine(),

        'arquivo' =>
        $e->getFile()
    ], JSON_UNESCAPED_UNICODE);
}
