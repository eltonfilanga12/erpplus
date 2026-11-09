<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Banco.php';
require_once __DIR__ . '/../controllers/BancoController.php';

Auth::requireLogin();

try {
    $pdo = Database::conn();

    $controller = new BancoController($pdo);

    $acao = $_REQUEST['acao'] ?? '';

    switch ($acao) {

        // =====================================
        // LISTAR
        // =====================================

        case 'listar':

            echo json_encode([
                'success' => true,
                'data' => $controller->listar(
                    $_REQUEST
                )
            ]);

            break;

        // =====================================
        // BUSCAR
        // =====================================

        case 'buscar':

            $banco = $controller->buscar(
                $_REQUEST
            );

            if (!$banco) {
                throw new RuntimeException(
                    'Banco não encontrado.'
                );
            }

            echo json_encode([
                'success' => true,
                'data' => $banco
            ]);

            break;

        // =====================================
        // GUARDAR
        // =====================================

        case 'guardar':

            $edicao = !empty($_POST['id']);

            $resultado = $controller->guardar(
                $_POST
            );

            echo json_encode([
                'success' => $resultado,

                'mensagem' => $resultado
                    ? (
                        $edicao
                        ? 'Banco atualizado com sucesso.'
                        : 'Banco cadastrado com sucesso.'
                    )
                    : 'Não foi possível guardar o banco.'
            ]);

            break;

        // =====================================
        // FILIAIS
        // =====================================

        case 'filiais':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->filiais()
            ], JSON_UNESCAPED_UNICODE);

            break;

        case 'listar_status':

            echo json_encode([
                'success' => true,
                'data' => $controller->listarStatus()
            ]);

            break;
        // =====================================
        // ELIMINAR
        // =====================================

        case 'eliminar':

            $resultado = $controller->eliminar(
                $_POST
            );

            echo json_encode([
                'success' => $resultado,

                'mensagem' => $resultado
                    ? 'Banco eliminado com sucesso.'
                    : 'Não foi possível eliminar o banco.'
            ]);

            break;

        // =====================================
        // SALDO
        // =====================================

        case 'saldo':

            echo json_encode([
                'success' => true,

                'saldo' => $controller->saldo(
                    $_REQUEST
                )
            ]);

            break;

        default:

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'mensagem' => 'Ação inválida.'
            ]);
    }
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'mensagem' => $e->getMessage()
    ]);
}
