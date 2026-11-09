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


/*
|--------------------------------------------------------------------------
| Dependências
|--------------------------------------------------------------------------
*/

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/RelatorioOperacional.php';

require_once __DIR__
    . '/../controllers/RelatorioOperacionalController.php';


/*
|--------------------------------------------------------------------------
| Resposta JSON
|--------------------------------------------------------------------------
*/

function responderRelatorioOperacional(
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
| Ler payload
|--------------------------------------------------------------------------
*/

function lerPayloadRelatorioOperacional(): array
{
    $contentType =
        $_SERVER['CONTENT_TYPE']
        ?? '';


    if (
        stripos(
            $contentType,
            'application/json'
        ) !== false
    ) {
        $conteudo =
            file_get_contents(
                'php://input'
            );


        if (
            $conteudo === false
            || trim($conteudo) === ''
        ) {
            return [];
        }


        $dados =
            json_decode(
                $conteudo,
                true
            );


        if (
            json_last_error()
            !== JSON_ERROR_NONE
        ) {
            responderRelatorioOperacional(
                [
                    'success' => false,

                    'message' =>
                    'O conteúdo JSON enviado é inválido.',

                    'data' => null
                ],
                400
            );
        }


        return is_array($dados)
            ? $dados
            : [];
    }


    return array_merge(
        $_GET,
        $_POST
    );
}


/*
|--------------------------------------------------------------------------
| Processamento
|--------------------------------------------------------------------------
*/

try {

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
        responderRelatorioOperacional(
            [
                'success' => false,

                'message' =>
                'Método HTTP não permitido.',

                'data' => null
            ],
            405
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Empresa
    |--------------------------------------------------------------------------
    */

    $empresaId =
        (int)(
            $_SESSION['empresa_id']
            ?? 0
        );


    if ($empresaId <= 0) {
        responderRelatorioOperacional(
            [
                'success' => false,

                'message' =>
                'Empresa não identificada na sessão.',

                'data' => null
            ],
            401
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Payload
    |--------------------------------------------------------------------------
    */

    $payload =
        lerPayloadRelatorioOperacional();


    $acao =
        strtolower(
            trim(
                (string)(
                    $payload['acao']
                    ?? ''
                )
            )
        );


    if ($acao === '') {
        responderRelatorioOperacional(
            [
                'success' => false,

                'message' =>
                'Nenhuma ação foi informada.',

                'data' => null
            ],
            400
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Base de dados
    |--------------------------------------------------------------------------
    */

    $db =
        Database::conn();


    /*
    |--------------------------------------------------------------------------
    | Controller
    |--------------------------------------------------------------------------
    */

    $controller =
        new RelatorioOperacionalController(
            $db
        );


    /*
    |--------------------------------------------------------------------------
    | Rotas
    |--------------------------------------------------------------------------
    */

    switch ($acao) {

        /*
        |--------------------------------------------------------------------------
        | Relatório
        |--------------------------------------------------------------------------
        */

        case 'relatorio':

            if ($metodo !== 'POST') {

                responderRelatorioOperacional(
                    [
                        'success' => false,

                        'message' =>
                        'Método HTTP não permitido.',

                        'data' => null
                    ],
                    405
                );
            }


            $resposta =
                $controller->relatorio(
                    $empresaId,
                    $payload
                );

            break;

        case 'estados':

            $resposta =
                $controller->estados(
                    $empresaId,
                    $payload
                );

            break;

        /*
        |--------------------------------------------------------------------------
        | Filiais
        |--------------------------------------------------------------------------
        */

        case 'filiais':

            $resposta =
                $controller->filiais(
                    $empresaId
                );

            break;


        /*
        |--------------------------------------------------------------------------
        | Inválida
        |--------------------------------------------------------------------------
        */

        default:

            responderRelatorioOperacional(
                [
                    'success' => false,

                    'message' =>
                    'Ação inválida.',

                    'data' => null
                ],
                400
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Resposta
    |--------------------------------------------------------------------------
    */

    responderRelatorioOperacional(
        $resposta,
        !empty($resposta['success'])
            ? 200
            : 422
    );
} catch (Throwable $e) {

    error_log(
        sprintf(
            '[relatorios_operacionais_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    responderRelatorioOperacional(
        [
            'success' => false,

            /*
            | Durante o desenvolvimento deixamos a mensagem real.
            | Depois podemos substituir por mensagem genérica.
            */
            'message' =>
            $e->getMessage(),

            'data' => null
        ],
        500
    );
}
