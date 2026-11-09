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

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/Permissao.php';

require_once __DIR__
    . '/../controllers/PermissaoController.php';

function responderPermissaoJson(
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

$acao =
    strtolower(
        trim(
            (string) (
                $_GET['acao']
                ?? $_POST['acao']
                ?? ''
            )
        )
    );

if ($acao === '') {
    responderPermissaoJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Nenhuma ação foi informada.'
        ],
        400
    );
}

$controller =
    new PermissaoController();

try {
    switch ($acao) {
        case 'perfis':
        case 'listar_perfis':
            $resposta =
                $controller->perfis();
            break;

        case 'carregar':
            $resposta =
                $controller->carregar();
            break;

        case 'guardar':
            if (
                ($_SERVER['REQUEST_METHOD'] ?? '')
                !== 'POST'
            ) {
                responderPermissaoJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }

            $conteudo =
                file_get_contents(
                    'php://input'
                );

            $entrada =
                json_decode(
                    $conteudo ?: '',
                    true
                );

            if (
                json_last_error()
                !== JSON_ERROR_NONE
                || !is_array($entrada)
            ) {
                responderPermissaoJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'O conteúdo JSON enviado é inválido.'
                    ],
                    400
                );
            }

            $resposta =
                $controller->guardar(
                    $entrada
                );
            break;

        default:
            responderPermissaoJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }

    responderPermissaoJson(
        $resposta,
        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[permissao_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    responderPermissaoJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Ocorreu um erro interno ao processar a solicitação.'
        ],
        500
    );
}
