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

require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/Marca.php';

require_once __DIR__
    . '/../controllers/MarcaController.php';

function responderMarcaJson(
    array $dados,
    int $statusHttp = 200
): never {
    http_response_code(
        $statusHttp
    );

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}

try {
    if (!Auth::check()) {
        responderMarcaJson(
            [
                'sucesso' => false,
                'mensagem' =>
                'Sessão expirada ou utilizador não autenticado.'
            ],
            401
        );
    }

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
        responderMarcaJson(
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
                (string)(
                    $_POST['acao']
                    ?? $_GET['acao']
                    ?? ''
                )
            )
        );

    if ($acao === '') {
        responderMarcaJson(
            [
                'sucesso' => false,
                'mensagem' =>
                'Nenhuma ação foi informada.'
            ],
            400
        );
    }

    $controller =
        new MarcaController();

    switch ($acao) {
        case 'listar':
            $resposta =
                $controller->listar();
            break;

        case 'buscar':
        case 'obter':
            $resposta =
                $controller->buscar();
            break;

        case 'status':
            $resposta =
                $controller->status();
            break;

        case 'guardar':
        case 'salvar':
            if ($metodo !== 'POST') {
                responderMarcaJson(
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

        case 'eliminar':
            if ($metodo !== 'POST') {
                responderMarcaJson(
                    [
                        'sucesso' => false,
                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }

            $resposta =
                $controller->eliminar();
            break;


        // Adicionar no switch($acao):
        case 'validar_importacao':
            if ($metodo !== 'POST') {
                responderMarcaJson(['sucesso' => false, 'mensagem' => 'Método HTTP não permitido.'], 405);
            }
            require_once __DIR__ . '/../services/MarcaImportacaoService.php';
            $resposta = $controller->validarImportacaoExcel();
            break;

        case 'confirmar_importacao':
            if ($metodo !== 'POST') {
                responderMarcaJson(['sucesso' => false, 'mensagem' => 'Método HTTP não permitido.'], 405);
            }
            require_once __DIR__ . '/../services/MarcaImportacaoService.php';
            $resposta = $controller->confirmarImportacaoExcel();
            break;

        case 'baixar_modelo_excel':
            if ($metodo !== 'GET') {
                http_response_code(405);
                echo json_encode(['success' => false, 'mensagem' => 'Método inválido.'], JSON_UNESCAPED_UNICODE);
                break;
            }
            require_once __DIR__ . '/../services/MarcaImportacaoService.php';
            $controller->baixarModeloImportacaoExcel();
            break;

        default:
            responderMarcaJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }

    responderMarcaJson(
        $resposta,
        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[marca_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    responderMarcaJson(
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
