<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| SESSÃO
|--------------------------------------------------------------------------
*/

if (
    session_status()
    !== PHP_SESSION_ACTIVE
) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| JSON
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/json; charset=utf-8'
);


error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');


/*
|--------------------------------------------------------------------------
| DEPENDÊNCIAS
|--------------------------------------------------------------------------
*/

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/RelatorioContabilistico.php';

require_once __DIR__
    . '/../controllers/RelatorioContabilisticoController.php';


/*
|--------------------------------------------------------------------------
| RESPOSTA
|--------------------------------------------------------------------------
*/

function responderRelatorioContabilistico(
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
| PAYLOAD
|--------------------------------------------------------------------------
*/

function lerPayloadRelatorioContabilistico(): array
{
    $contentType =
        $_SERVER['CONTENT_TYPE']
        ?? '';


    /*
    |--------------------------------------------------------------------------
    | JSON
    |--------------------------------------------------------------------------
    */

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

            responderRelatorioContabilistico(
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


    /*
    |--------------------------------------------------------------------------
    | GET / POST NORMAL
    |--------------------------------------------------------------------------
    */

    return array_merge(
        $_GET,
        $_POST
    );
}


/*
|--------------------------------------------------------------------------
| PROCESSAMENTO
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | MÉTODO HTTP
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
            [
                'GET',
                'POST'
            ],
            true
        )
    ) {

        responderRelatorioContabilistico(
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
    | EMPRESA
    |--------------------------------------------------------------------------
    */

    $empresaId =
        (int)(
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? $_SESSION['user']['empresa_id']
            ?? 0
        );


    if ($empresaId <= 0) {

        responderRelatorioContabilistico(
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
    | PAYLOAD
    |--------------------------------------------------------------------------
    */

    $payload =
        lerPayloadRelatorioContabilistico();


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

        responderRelatorioContabilistico(
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
    | DATABASE
    |--------------------------------------------------------------------------
    */

    $db =
        Database::conn();


    /*
    |--------------------------------------------------------------------------
    | CONTROLLER
    |--------------------------------------------------------------------------
    */

    $controller =
        new RelatorioContabilisticoController(
            $db
        );


    /*
    |--------------------------------------------------------------------------
    | ROTAS
    |--------------------------------------------------------------------------
    */

    switch ($acao) {


        /*
        |--------------------------------------------------------------------------
        | RELATÓRIO
        |--------------------------------------------------------------------------
        */

        case 'relatorio':

            if ($metodo !== 'POST') {

                responderRelatorioContabilistico(
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


        /*
        |--------------------------------------------------------------------------
        | PERÍODOS
        |--------------------------------------------------------------------------
        */

        case 'periodos':

            $resposta =
                $controller->periodos(
                    $empresaId
                );

            break;


        /*
        |--------------------------------------------------------------------------
        | DIÁRIOS
        |--------------------------------------------------------------------------
        */

        case 'diarios':

            $resposta =
                $controller->diarios(
                    $empresaId
                );

            break;


        /*
        |--------------------------------------------------------------------------
        | CONTAS
        |--------------------------------------------------------------------------
        */

        case 'contas':

            $resposta =
                $controller->contas(
                    $empresaId
                );

            break;


        /*
        |--------------------------------------------------------------------------
        | FILIAIS
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
        | INVÁLIDA
        |--------------------------------------------------------------------------
        */

        default:

            responderRelatorioContabilistico(
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
    | RESPOSTA FINAL
    |--------------------------------------------------------------------------
    */

    responderRelatorioContabilistico(
        $resposta,

        !empty($resposta['success'])
            ? 200
            : 422
    );
} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | LOG
    |--------------------------------------------------------------------------
    */

    error_log(
        sprintf(
            '[relatorios_contabilisticos_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    /*
    |--------------------------------------------------------------------------
    | Durante desenvolvimento
    |--------------------------------------------------------------------------
    */

    responderRelatorioContabilistico(
        [
            'success' => false,

            'message' =>
            $e->getMessage(),

            'data' => null
        ],
        500
    );
}
