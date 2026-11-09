<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CONFIGURAÇÃO DA RESPOSTA
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate'
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

/*
|--------------------------------------------------------------------------
| DEPENDÊNCIAS
|--------------------------------------------------------------------------
*/

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../services/EstoqueService.php';

require_once __DIR__
    . '/../models/EntradaStock.php';

require_once __DIR__
    . '/../controllers/EntradaStockController.php';


/*
|--------------------------------------------------------------------------
| SESSÃO
|--------------------------------------------------------------------------
*/

Auth::iniciarSessao();


/*
|--------------------------------------------------------------------------
| RESPOSTA JSON
|--------------------------------------------------------------------------
*/

function responderEntradaStockJson(
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
| AUTENTICAÇÃO
|--------------------------------------------------------------------------
*/

if (!Auth::check()) {

    responderEntradaStockJson(
        [
            'sucesso' => false,

            'mensagem' =>
            'Sessão expirada. Faça login novamente.'
        ],
        401
    );
}


/*
|--------------------------------------------------------------------------
| PROCESSAMENTO
|--------------------------------------------------------------------------
*/

try {

    $metodo =
        strtoupper(
            trim(
                (string)(
                    $_SERVER['REQUEST_METHOD']
                    ?? 'GET'
                )
            )
        );


    /*
    |--------------------------------------------------------------------------
    | MÉTODOS PERMITIDOS
    |--------------------------------------------------------------------------
    */

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

        responderEntradaStockJson(
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
    | AÇÃO
    |--------------------------------------------------------------------------
    */

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

        responderEntradaStockJson(
            [
                'sucesso' => false,

                'mensagem' =>
                'Nenhuma ação foi informada.'
            ],
            400
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CONTROLLER
    |--------------------------------------------------------------------------
    */

    $controller =
        new EntradaStockController();


    /*
    |--------------------------------------------------------------------------
    | ROTAS
    |--------------------------------------------------------------------------
    */

    switch ($acao) {


        /*
        |--------------------------------------------------------------------------
        | LISTAR ARMAZÉNS
        |--------------------------------------------------------------------------
        */

        case 'armazens':

            if ($metodo !== 'GET') {

                responderEntradaStockJson(
                    [
                        'sucesso' => false,

                        'mensagem' =>
                        'Método HTTP não permitido para listar armazéns.'
                    ],
                    405
                );
            }


            $resposta =
                $controller
                ->armazens();

            break;


        /*
        |--------------------------------------------------------------------------
        | LISTAR PRODUTOS
        |--------------------------------------------------------------------------
        */

        case 'produtos':

            if ($metodo !== 'GET') {

                responderEntradaStockJson(
                    [
                        'sucesso' => false,

                        'mensagem' =>
                        'Método HTTP não permitido para listar produtos.'
                    ],
                    405
                );
            }


            $resposta =
                $controller
                ->produtos();

            break;


        /*
        |--------------------------------------------------------------------------
        | LISTAR ESTADOS
        |--------------------------------------------------------------------------
        */

        case 'status':

            if ($metodo !== 'GET') {

                responderEntradaStockJson(
                    [
                        'sucesso' => false,

                        'mensagem' =>
                        'Método HTTP não permitido para listar estados.'
                    ],
                    405
                );
            }


            $resposta =
                $controller
                ->status();

            break;


        /*
        |--------------------------------------------------------------------------
        | GUARDAR ENTRADA
        |--------------------------------------------------------------------------
        */

        case 'guardar':
        case 'salvar':

            if ($metodo !== 'POST') {

                responderEntradaStockJson(
                    [
                        'sucesso' => false,

                        'mensagem' =>
                        'Método HTTP não permitido para registar a entrada.'
                    ],
                    405
                );
            }


            $resposta =
                $controller
                ->guardar();

            break;


        /*
        |--------------------------------------------------------------------------
        | AÇÃO INVÁLIDA
        |--------------------------------------------------------------------------
        */

        default:

            responderEntradaStockJson(
                [
                    'sucesso' => false,

                    'mensagem' =>
                    'Ação inválida para o módulo de entrada de stock.'
                ],
                400
            );
    }


    /*
    |--------------------------------------------------------------------------
    | ENVIAR RESULTADO
    |--------------------------------------------------------------------------
    */

    responderEntradaStockJson(
        $resposta,

        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | REGISTAR ERRO REAL NO SERVIDOR
    |--------------------------------------------------------------------------
    */

    error_log(
        sprintf(
            '[entrada_stock_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    /*
    |--------------------------------------------------------------------------
    | RESPOSTA SEGURA
    |--------------------------------------------------------------------------
    */

    responderEntradaStockJson(
        [
            'sucesso' => false,

            'mensagem' =>
            $e instanceof RuntimeException
                || $e instanceof InvalidArgumentException

                ? $e->getMessage()

                : 'Ocorreu um erro interno ao processar a entrada de stock.'
        ],
        500
    );
}
