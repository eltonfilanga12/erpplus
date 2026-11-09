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

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/Filial.php';

require_once __DIR__
    . '/../controllers/FilialController.php';

/*
|--------------------------------------------------------------------------
| Resposta JSON
|--------------------------------------------------------------------------
*/

function responderFilialJson(
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

/*
|--------------------------------------------------------------------------
| Método HTTP
|--------------------------------------------------------------------------
*/

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
    responderFilialJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Método HTTP não permitido.'
        ],
        405
    );
}

/*
|--------------------------------------------------------------------------
| Ação
|--------------------------------------------------------------------------
*/

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
    responderFilialJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Nenhuma ação foi informada.'
        ],
        400
    );
}

$controller =
    new FilialController();

/*
|--------------------------------------------------------------------------
| Rotas
|--------------------------------------------------------------------------
*/

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
                responderFilialJson(
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
                responderFilialJson(
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

        default:
            responderFilialJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }

    responderFilialJson(
        $resposta,
        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[filial_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    responderFilialJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Ocorreu um erro interno ao processar a solicitação.'
        ],
        500
    );
}
