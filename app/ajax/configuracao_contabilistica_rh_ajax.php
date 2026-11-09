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
    . '/../models/ConfiguracaoContabilisticaRh.php';

require_once __DIR__
    . '/../controllers/ConfiguracaoContabilisticaRhController.php';

function responderConfiguracaoRhJson(
    array $resposta,
    int $statusHttp = 200
): never {
    http_response_code($statusHttp);

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

    if (
        !in_array(
            $metodo,
            ['GET', 'POST'],
            true
        )
    ) {
        responderConfiguracaoRhJson(
            [
                'sucesso' => false,
                'mensagem' =>
                'Método HTTP não permitido.'
            ],
            405
        );
    }

    $acao =
        strtolower(
            trim(
                (string) (
                    $_POST['acao']
                    ?? $_GET['acao']
                    ?? ''
                )
            )
        );

    if ($acao === '') {
        responderConfiguracaoRhJson(
            [
                'sucesso' => false,
                'mensagem' =>
                'Nenhuma ação foi informada.'
            ],
            400
        );
    }

    $controller =
        new ConfiguracaoContabilisticaRhController();

    switch ($acao) {
        case 'buscar':
        case 'carregar':
            $resposta =
                $controller->buscar();
            break;

        case 'contas':
            $finalidade = trim(
                (string)($_GET['finalidade'] ?? '')
            );

            $resposta =
                $controller->contas($finalidade);
            break;

        case 'diarios':
            $resposta =
                $controller->diarios();
            break;

        case 'status':
            $resposta =
                $controller->status();
            break;

        case 'guardar':
        case 'salvar':
            if ($metodo !== 'POST') {
                responderConfiguracaoRhJson(
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
            responderConfiguracaoRhJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }

    responderConfiguracaoRhJson(
        $resposta,
        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[configuracao_contabilistica_rh_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    responderConfiguracaoRhJson(
        [
            'sucesso' => false,
            'mensagem' => $e->getMessage(),
            'erro' => get_class($e),
            'ficheiro' => basename($e->getFile()),
            'linha' => $e->getLine()
        ],
        500
    );
}
