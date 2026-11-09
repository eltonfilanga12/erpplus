<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header(
    'Content-Type: application/json; charset=utf-8'
);

define('APP_INIT', true);

/*
|--------------------------------------------------------------------------
| Core
|--------------------------------------------------------------------------
*/

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

/*
|--------------------------------------------------------------------------
| Controller
|--------------------------------------------------------------------------
*/

require_once __DIR__
    . '/../controllers/PerfilUsuarioController.php';

/*
|--------------------------------------------------------------------------
| Resposta JSON
|--------------------------------------------------------------------------
*/

function responderPerfilUsuario(
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
| Autenticação
|--------------------------------------------------------------------------
*/

if (!Auth::check()) {
    responderPerfilUsuario(
        [
            'sucesso' => false,
            'mensagem' =>
                'A sessão expirou. Inicie a sessão novamente.'
        ],
        401
    );
}

/*
|--------------------------------------------------------------------------
| Método HTTP
|--------------------------------------------------------------------------
*/

$metodoHttp = strtoupper(
    $_SERVER['REQUEST_METHOD']
    ?? 'GET'
);

if (
    !in_array(
        $metodoHttp,
        [
            'GET',
            'POST'
        ],
        true
    )
) {
    responderPerfilUsuario(
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
| Ação
|--------------------------------------------------------------------------
*/

$acao = strtolower(
    trim(
        (string)(
            $_POST['acao']
            ?? $_GET['acao']
            ?? ''
        )
    )
);

if ($acao === '') {
    responderPerfilUsuario(
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
| Controller
|--------------------------------------------------------------------------
*/

$controller =
    new PerfilUsuarioController();

/*
|--------------------------------------------------------------------------
| Rotas
|--------------------------------------------------------------------------
*/

try {

    switch ($acao) {

        /*
        |--------------------------------------------------------------------------
        | Carregar perfil
        |--------------------------------------------------------------------------
        */

        case 'carregar':

            $resposta =
                $controller
                    ->carregar();

            break;

        /*
        |--------------------------------------------------------------------------
        | Atualizar dados pessoais
        |--------------------------------------------------------------------------
        */

        case 'atualizar':

            $resposta =
                $controller
                    ->atualizar();

            break;

        /*
        |--------------------------------------------------------------------------
        | Alterar senha
        |--------------------------------------------------------------------------
        */

        case 'alterar_senha':

            $resposta =
                $controller
                    ->alterarSenha();

            break;

        /*
        |--------------------------------------------------------------------------
        | Alterar avatar
        |--------------------------------------------------------------------------
        */

        case 'alterar_avatar':

            $resposta =
                $controller
                    ->alterarAvatar();

            break;

        /*
        |--------------------------------------------------------------------------
        | Remover avatar
        |--------------------------------------------------------------------------
        */

        case 'remover_avatar':

            $resposta =
                $controller
                    ->removerAvatar();

            break;

        /*
        |--------------------------------------------------------------------------
        | Ação inválida
        |--------------------------------------------------------------------------
        */

        default:

            responderPerfilUsuario(
                [
                    'sucesso' => false,
                    'mensagem' =>
                        'Ação inválida para o perfil do utilizador.'
                ],
                400
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Status HTTP
    |--------------------------------------------------------------------------
    */

    $statusHttp =
        !empty(
            $resposta['sucesso']
        )
            ? 200
            : 422;

    responderPerfilUsuario(
        $resposta,
        $statusHttp
    );

} catch (Throwable $erro) {

    error_log(
        '[perfil_usuario_ajax] '
        . $erro->getMessage()
        . ' em '
        . $erro->getFile()
        . ':'
        . $erro->getLine()
    );

    responderPerfilUsuario(
        [
            'sucesso' => false,
            'mensagem' =>
                'Ocorreu um erro interno ao processar o perfil.'
        ],
        500
    );
}