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
    . '/../models/RelatorioRH.php';

require_once __DIR__
    . '/../controllers/RelatorioRHController.php';


/*
|--------------------------------------------------------------------------
| RESPONDER
|--------------------------------------------------------------------------
*/

function responderRelatorioRH(
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
| LER PAYLOAD
|--------------------------------------------------------------------------
*/

function lerPayloadRelatorioRH(): array
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

            responderRelatorioRH(
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

        responderRelatorioRH(
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

        responderRelatorioRH(
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
        lerPayloadRelatorioRH();


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

        responderRelatorioRH(
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
        new RelatorioRHController(
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

                responderRelatorioRH(
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
        | DEPARTAMENTOS
        |--------------------------------------------------------------------------
        */

        case 'departamentos':

            $resposta =
                $controller->departamentos(
                    $empresaId,
                    $payload
                );

            break;


        /*
        |--------------------------------------------------------------------------
        | CARGOS
        |--------------------------------------------------------------------------
        */

        case 'cargos':

            $resposta =
                $controller->cargos(
                    $empresaId,
                    $payload
                );

            break;


        /*
        |--------------------------------------------------------------------------
        | FUNCIONÁRIOS
        |--------------------------------------------------------------------------
        */

        case 'funcionarios':

            $resposta =
                $controller->funcionarios(
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
        | OPÇÕES
        |--------------------------------------------------------------------------
        */

        case 'opcoes':

            $resposta =
                $controller->opcoes();

            break;


        /*
        |--------------------------------------------------------------------------
        | INVÁLIDA
        |--------------------------------------------------------------------------
        */

        default:

            responderRelatorioRH(
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
    | STATUS HTTP
    |--------------------------------------------------------------------------
    */

    $statusHttp =
        !empty(
            $resposta['success']
        )
            ? 200
            : 422;


    /*
    |--------------------------------------------------------------------------
    | RESPONDER
    |--------------------------------------------------------------------------
    */

    responderRelatorioRH(
        $resposta,
        $statusHttp
    );


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | LOG
    |--------------------------------------------------------------------------
    */

    error_log(
        sprintf(
            '[relatorios_rh_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    /*
    |--------------------------------------------------------------------------
    | ERRO
    |--------------------------------------------------------------------------
    */

    responderRelatorioRH(
        [
            'success' => false,

            /*
            |--------------------------------------------------------------------------
            | Durante o desenvolvimento podemos mostrar
            | a mensagem real. Em produção, depois trocamos.
            |--------------------------------------------------------------------------
            */

            'message' =>
                $e->getMessage(),

            'data' => null
        ],
        500
    );
}