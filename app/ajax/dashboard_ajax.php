<?php

declare(strict_types=1);

session_start();

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../controllers/DashboardController.php';


/*
|--------------------------------------------------------------------------
| Resposta JSON
|--------------------------------------------------------------------------
*/

function responderDashboard(
    array $dados,
    int $status = 200
): never {

    http_response_code(
        $status
    );

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Método permitido
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {

    responderDashboard(
        [
            'success' => false,

            'message' =>
            'Método de requisição inválido.',

            'data' =>
            null
        ],
        405
    );
}


/*
|--------------------------------------------------------------------------
| Ler entrada
|--------------------------------------------------------------------------
*/

$conteudo =
    file_get_contents(
        'php://input'
    );


$dados = [];


if (
    is_string(
        $conteudo
    )
    &&
    trim(
        $conteudo
    ) !== ''
) {

    $json =
        json_decode(
            $conteudo,
            true
        );


    if (
        is_array(
            $json
        )
    ) {

        $dados =
            $json;
    }
}


/*
|--------------------------------------------------------------------------
| Compatibilidade com FormData / POST normal
|--------------------------------------------------------------------------
*/

if (
    empty($dados)
    &&
    !empty($_POST)
) {

    $dados =
        $_POST;
}


/*
|--------------------------------------------------------------------------
| Ação
|--------------------------------------------------------------------------
*/

$acao =
    strtolower(
        trim(
            (string)(
                $dados['acao']
                ?? ''
            )
        )
    );


if (
    $acao === ''
) {

    $acao =
        'carregar';
}


/*
|--------------------------------------------------------------------------
| Controller
|--------------------------------------------------------------------------
*/

try {

    $db =
        Database::conn();


    $controller =
        new DashboardController(
            $db
        );


    switch ($acao) {

        /*
        |--------------------------------------------------------------------------
        | Dashboard completo
        |--------------------------------------------------------------------------
        */

        case 'carregar':
        case 'dashboard':

            $resultado =
                $controller
                ->carregar(
                    $dados
                );

            responderDashboard(
                $resultado,
                (
                    $resultado['success']
                    ?? false
                )
                    ? 200
                    : 400
            );


            /*
        |--------------------------------------------------------------------------
        | Ação não encontrada
        |--------------------------------------------------------------------------
        */

        default:

            responderDashboard(
                [
                    'success' =>
                    false,

                    'message' =>
                    'Ação inválida para o Dashboard.',

                    'data' =>
                    null
                ],
                400
            );
    }
} catch (
    Throwable $e
) {

    error_log(
        '[dashboard_ajax] '
            . $e->getMessage()
            . ' em '
            . $e->getFile()
            . ':'
            . $e->getLine()
    );


    responderDashboard(
        [
            'success' =>
            false,

            'message' =>
            $e->getMessage(),

            'data' =>
            null
        ],
        500
    );
}
