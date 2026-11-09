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

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/Perfil.php';

require_once __DIR__
    . '/../controllers/PerfilController.php';

/*
|--------------------------------------------------------------------------
| Resposta JSON
|--------------------------------------------------------------------------
*/

function responderPerfilJson(
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

$metodoHttp =
    strtoupper(
        $_SERVER['REQUEST_METHOD']
        ?? 'GET'
    );

if (
    !in_array(
        $metodoHttp,
        ['GET', 'POST'],
        true
    )
) {
    responderPerfilJson(
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
    responderPerfilJson(
        [
            'sucesso' => false,
            'mensagem' =>
                'Nenhuma ação foi informada.'
        ],
        400
    );
}

$controller =
    new PerfilController();

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

        case 'guardar':
            $resposta =
                $controller->guardar();
            break;

        case 'eliminar':
            $resposta =
                $controller->eliminar();
            break;

        case 'status':
            $resposta =
                $controller->status();
            break;

        default:
            responderPerfilJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                        'Ação inválida.'
                ],
                400
            );
    }

    $statusHttp =
        !empty($resposta['sucesso'])
            ? 200
            : 422;

    responderPerfilJson(
        $resposta,
        $statusHttp
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[perfil_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    responderPerfilJson(
        [
            'sucesso' => false,
            'mensagem' =>
                'Ocorreu um erro interno ao processar a solicitação.'
        ],
        500
    );
}