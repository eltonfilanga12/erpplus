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
    . '/../models/MovimentoStock.php';


require_once __DIR__
    . '/../controllers/MovimentoStockController.php';


function responderMovimentoStockJson(
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

    $acao =
        strtolower(
            trim(
                (string)(
                    $_GET['acao']
                    ?? $_POST['acao']
                    ?? ''
                )
            )
        );


    if ($acao === '') {

        responderMovimentoStockJson(
            [
                'sucesso' => false,
                'mensagem' =>
                'Nenhuma ação foi informada.'
            ],
            400
        );
    }


    $controller =
        new MovimentoStockController();


    switch ($acao) {

        case 'listar':

            $resposta =
                $controller->listar();

            break;


        case 'buscar':
        case 'detalhes':

            $resposta =
                $controller->buscar();

            break;


        case 'resumo':

            $resposta =
                $controller->resumo();

            break;


        case 'armazens':

            $resposta =
                $controller->armazens();

            break;


        default:

            responderMovimentoStockJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }


    responderMovimentoStockJson(
        $resposta,

        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {

    error_log(
        sprintf(
            '[movimento_stock_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    responderMovimentoStockJson(
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
