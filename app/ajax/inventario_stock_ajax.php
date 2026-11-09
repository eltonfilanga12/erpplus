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
    . '/../models/InventarioStock.php';

require_once __DIR__
    . '/../controllers/InventarioStockController.php';


function responderInventarioJson(
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

        responderInventarioJson(
            [
                'sucesso' => false,
                'mensagem' =>
                'Nenhuma ação foi informada.'
            ],
            400
        );
    }


    $controller =
        new InventarioStockController();


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


        case 'buscar':
        case 'detalhes':

            $resposta =
                $controller->buscar();

            break;


        case 'criar':

            if ($metodo !== 'POST') {

                responderInventarioJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }


            $resposta =
                $controller->criar();

            break;


        case 'guardar_contagem':

            if ($metodo !== 'POST') {

                responderInventarioJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }


            $resposta =
                $controller
                ->guardarContagem();

            break;


        case 'finalizar':

            if ($metodo !== 'POST') {

                responderInventarioJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }


            $resposta =
                $controller->finalizar();

            break;

        case 'cancelar':

            if ($metodo !== 'POST') {

                responderInventarioJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }

            $resposta =
                $controller->cancelar();

            break;


        default:

            responderInventarioJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }


    responderInventarioJson(
        $resposta,

        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {

    responderInventarioJson(
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
