<?php

declare(strict_types=1);

if (
    session_status()
    !== PHP_SESSION_ACTIVE
) {
    session_start();
}

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/Cliente.php';

require_once __DIR__
    . '/../controllers/ClienteController.php';

function responderClienteJson(
    array $resposta,
    int $statusHttp = 200
): never {
    http_response_code(
        $statusHttp
    );

    echo json_encode(
        $resposta,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}

$metodo =
    strtoupper(
        $_SERVER['REQUEST_METHOD']
            ?? 'GET'
    );

if (
    !in_array(
        $metodo,
        ['GET', 'POST'],
        true
    )
) {
    responderClienteJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Método HTTP não permitido.'
        ],
        405
    );
}

$acao =
    strtolower(
        trim(
            (string) (
                $_POST['acao']
                ?? $_GET['acao']
                ?? ''
            )
        )
    );

if ($acao === '') {
    responderClienteJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Nenhuma ação foi informada.'
        ],
        400
    );
}

$controller =
    new ClienteController();

try {
    switch ($acao) {
        case 'listar':
            $resposta =
                $controller->listar();
            break;

        case 'buscar':
        case 'obter':
            $resposta =
                $controller->buscar();
            break;

        case 'status':
            $resposta =
                $controller->status();
            break;

        case 'salvar':
        case 'guardar':
            if ($metodo !== 'POST') {
                responderClienteJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }

            $resposta =
                $controller->salvar();
            break;

        case 'eliminar':
            if ($metodo !== 'POST') {
                responderClienteJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }

            $resposta =
                $controller->eliminar();
            break;


        // Adicionar os casos dentro do switch($acao):
        case 'validar_importacao':
            if ($metodo !== 'POST') {
                responderClienteJson(['sucesso' => false, 'mensagem' => 'Método HTTP não permitido.'], 405);
            }
            require_once __DIR__ . '/../services/ClienteImportacaoService.php';
            $resposta = $controller->validarImportacaoExcel();
            break;

        case 'confirmar_importacao':
            if ($metodo !== 'POST') {
                responderClienteJson(['sucesso' => false, 'mensagem' => 'Método HTTP não permitido.'], 405);
            }
            require_once __DIR__ . '/../services/ClienteImportacaoService.php';
            $resposta = $controller->confirmarImportacaoExcel();
            break;

        case 'baixar_modelo_excel':
            if ($metodo !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'mensagem' => 'Método inválido.'], JSON_UNESCAPED_UNICODE);
                break;
            }
            require_once __DIR__ . '/../services/ClienteImportacaoService.php';
            $controller->baixarModeloImportacaoExcel();
            break;

        default:
            responderClienteJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }

    responderClienteJson(
        $resposta,
        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[cliente_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    responderClienteJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Ocorreu um erro interno ao processar a solicitação.'
        ],
        500
    );
}
