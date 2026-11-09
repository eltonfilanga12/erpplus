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
    . '/../models/SaidaStock.php';

require_once __DIR__
    . '/../controllers/SaidaStockController.php';


function responderSaidaStockJson(
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

        responderSaidaStockJson(
            [
                'sucesso' => false,
                'mensagem' =>
                'Nenhuma ação foi informada.'
            ],
            400
        );
    }


    $controller =
        new SaidaStockController();


    switch ($acao) {

        case 'armazens':

            $resposta =
                $controller->armazens();

            break;


        case 'produtos':

            $resposta =
                $controller->produtos();

            break;


        case 'guardar':
        case 'salvar':

            if ($metodo !== 'POST') {

                responderSaidaStockJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }


            $resposta =
                $controller->guardar();

            break;


        default:

            responderSaidaStockJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }


    responderSaidaStockJson(
        $resposta,

        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {

    responderSaidaStockJson(
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
