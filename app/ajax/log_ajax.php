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
ini_set(
    'display_errors',
    '0'
);

ini_set(
    'log_errors',
    '1'
);


require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/Log.php';

require_once __DIR__
    . '/../controllers/LogController.php';


function responderLogJson(
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


if ($metodo !== 'GET') {
    responderLogJson(
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
            (string)(
                $_GET['acao']
                ?? ''
            )
        )
    );


if ($acao === '') {
    responderLogJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Nenhuma ação foi informada.'
        ],
        400
    );
}


$controller =
    new LogController();


try {

    switch ($acao) {

        case 'listar':

            $resposta =
                $controller->listar();

            break;


        case 'filtros':

            $resposta =
                $controller->filtros();

            break;


        default:

            responderLogJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }


    responderLogJson(
        $resposta,
        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {

    error_log(
        sprintf(
            '[log_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    responderLogJson(
        [
            'sucesso' => false,

            'mensagem' =>
            'Ocorreu um erro interno ao processar a solicitação.'
        ],
        500
    );
}
