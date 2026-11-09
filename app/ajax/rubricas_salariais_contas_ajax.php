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
    . '/../models/RubricaSalarialConta.php';

require_once __DIR__
    . '/../controllers/RubricaSalarialContaController.php';

function responderRubricaContaJson(
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

function lerPayloadRubricaConta(): array
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
            responderRubricaContaJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'O conteúdo JSON enviado é inválido.'
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

try {
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
        responderRubricaContaJson(
            [
                'sucesso' => false,
                'mensagem' =>
                'Método HTTP não permitido.'
            ],
            405
        );
    }

    $payload =
        lerPayloadRubricaConta();

    $acao =
        strtolower(
            trim(
                (string) (
                    $payload['acao']
                    ?? ''
                )
            )
        );

    if ($acao === '') {
        responderRubricaContaJson(
            [
                'sucesso' => false,
                'mensagem' =>
                'Nenhuma ação foi informada.'
            ],
            400
        );
    }

    $controller =
        new RubricaSalarialContaController();

    switch ($acao) {
        case 'listar':
            $resposta =
                $controller->listar();
            break;

        case 'contas':
            $resposta =
                $controller->contas();
            break;

        case 'status':
            $resposta =
                $controller->status();
            break;

        case 'guardar':
        case 'salvar':
            if ($metodo !== 'POST') {
                responderRubricaContaJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }

            $_POST =
                array_merge(
                    $_POST,
                    $payload
                );

            $resposta =
                $controller->guardar();
            break;

        case 'guardar_todos':
            if ($metodo !== 'POST') {
                responderRubricaContaJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }

            $resposta =
                $controller->guardarTodos(
                    $payload
                );
            break;

        case 'eliminar':
            if ($metodo !== 'POST') {
                responderRubricaContaJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }

            $_POST =
                array_merge(
                    $_POST,
                    $payload
                );

            $resposta =
                $controller->eliminar();
            break;

        default:
            responderRubricaContaJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }

    responderRubricaContaJson(
        $resposta,
        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[rubricas_salariais_contas_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    responderRubricaContaJson(
        [
            'sucesso' => false,
            'mensagem' =>
            'Ocorreu um erro interno ao processar a solicitação.'
        ],
        500
    );
}
