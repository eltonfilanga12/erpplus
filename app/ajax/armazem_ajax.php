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
    . '/../models/Armazem.php';

require_once __DIR__
    . '/../controllers/ArmazemController.php';

function responderArmazemJson(
    array $resposta,
    int $statusHttp = 200
): never {
    http_response_code($statusHttp);

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
    responderArmazemJson(
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
    responderArmazemJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Nenhuma ação foi informada.'
        ],
        400
    );
}

$controller =
    new ArmazemController();

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

        case 'filiais':
            $resposta =
                $controller->filiais();
            break;

        case 'status':
            $resposta =
                $controller->status();
            break;

        case 'salvar':
        case 'guardar':
            if ($metodo !== 'POST') {
                responderArmazemJson(
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
                responderArmazemJson(
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
            responderArmazemJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }

    responderArmazemJson(
        $resposta,
        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[armazem_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    responderArmazemJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Ocorreu um erro interno ao processar a solicitação.'
        ],
        500
    );
}
