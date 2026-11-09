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
    . '/../models/Usuario.php';

require_once __DIR__
    . '/../controllers/UsuarioController.php';

function responderJson(
    array $dados,
    int $statusHttp = 200
): never {
    http_response_code(
        $statusHttp
    );

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}

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
    responderJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Método HTTP não permitido.'
        ],
        405
    );
}

$acao =
    trim(
        (string) (
            $_POST['acao']
            ?? $_GET['acao']
            ?? ''
        )
    );

if ($acao === '') {
    responderJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Nenhuma ação foi informada.'
        ],
        400
    );
}

$controller =
    new UsuarioController();

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

        case 'perfis':
            $resposta =
                $controller->perfis();
            break;

        case 'status':
            $resposta =
                $controller->status();
            break;

        case 'empresas':

            $resposta =
                $controller->empresas();

            break;

        case 'contexto':

            $resposta =
                $controller->contexto();

            break;

        case 'filiais':
            $resposta =
                $controller->filiais();
            break;

        default:
            responderJson(
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

    responderJson(
        $resposta,
        $statusHttp
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[usuario_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    responderJson(
        [
            'sucesso' => false,
            'mensagem' => $e->getMessage(),
            'arquivo' => basename(
                $e->getFile()
            ),
            'linha' => $e->getLine()
        ],
        500
    );
}
