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
    . '/../models/Stock.php';


require_once __DIR__
    . '/../controllers/StockController.php';


function responderStockJson(
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


try {

    $metodo =
        strtoupper(
            $_SERVER['REQUEST_METHOD']
                ?? 'GET'
        );


    if (
        !in_array(
            $metodo,
            [
                'GET',
                'POST'
            ],
            true
        )
    ) {

        responderStockJson(
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
                    $_POST['acao']
                    ?? $_GET['acao']
                    ?? ''
                )
            )
        );


    if ($acao === '') {

        responderStockJson(
            [
                'sucesso' => false,
                'mensagem' =>
                'Nenhuma ação foi informada.'
            ],
            400
        );
    }


    $controller =
        new StockController();


    switch ($acao) {


        case 'listar':

            $resposta =
                $controller->listar();

            break;



        case 'resumo':

            $resposta =
                $controller->resumo();

            break;



        case 'armazens':

            $resposta =
                $controller->armazens();

            break;



        case 'categorias':

            $resposta =
                $controller->categorias();

            break;



        default:

            responderStockJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }


    responderStockJson(
        $resposta,

        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {

    error_log(
        sprintf(
            '[stock_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    responderStockJson(
        [
            'sucesso' => false,

            'mensagem' =>
            $e->getMessage(),

            'arquivo' =>
            basename(
                $e->getFile()
            ),

            'linha' =>
            $e->getLine()
        ],
        500
    );
}
