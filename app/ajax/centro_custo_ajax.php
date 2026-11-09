<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

require_once __DIR__ . '/../models/CentroCusto.php';
require_once __DIR__ . '/../controllers/CentroCustoController.php';

Auth::iniciarSessao();

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
        new CentroCustoController($pdo);

    $acao = strtolower(
        trim(
            (string)(
                $_REQUEST['acao'] ?? ''
            )
        )
    );

    switch ($acao) {

        // =====================================
        // LISTAR
        // =====================================

        case 'listar':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->listar(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // BUSCAR
        // =====================================

        case 'buscar':

            $centro =
                $controller->buscar(
                    $_REQUEST
                );

            if (!$centro) {
                http_response_code(404);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Centro de custo não encontrado.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            echo json_encode([
                'success' => true,

                'data' => $centro
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // GUARDAR
        // =====================================

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
                $controller->guardar(
                    $_POST
                );

            echo json_encode([
                'success' => true,

                'mensagem' =>
                $resultado['mensagem']
                    ?? 'Centro de custo guardado com sucesso.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // ELIMINAR
        // =====================================

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
                    $_POST
                );

            echo json_encode([
                'success' => true,

                'mensagem' =>
                $resultado['mensagem']
                    ?? 'Centro de custo eliminado com sucesso.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // RESUMO
        // =====================================

        case 'resumo':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->resumo(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // LISTAR PARA SELECT
        // =====================================

        case 'listar_select':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->listarParaSelect(
                        $_REQUEST
                    )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // LISTAR STATUS
        // =====================================

        case 'listar_status':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->listarStatus()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // AÇÃO INVÁLIDA
        // =====================================

        default:

            http_response_code(400);

            echo json_encode([
                'success' => false,

                'mensagem' =>
                'Ação inválida para centros de custo.'
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
         * Durante o desenvolvimento podes manter estes
         * campos. Em produção, convém removê-los.
         */

        'linha' =>
        $e->getLine(),

        'arquivo' =>
        $e->getFile()
    ], JSON_UNESCAPED_UNICODE);
}
