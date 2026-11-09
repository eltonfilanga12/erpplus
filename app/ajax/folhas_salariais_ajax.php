<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

function responder(
    bool $success,
    string $message,
    mixed $data = null,
    int $statusCode = 200
): never {
    http_response_code($statusCode);

    echo json_encode(
        [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}

register_shutdown_function(
    function (): void {
        $erro = error_get_last();

        if (
            $erro === null
            || !in_array(
                $erro['type'],
                [
                    E_ERROR,
                    E_PARSE,
                    E_CORE_ERROR,
                    E_COMPILE_ERROR
                ],
                true
            )
        ) {
            return;
        }

        if (!headers_sent()) {
            header(
                'Content-Type: application/json; charset=utf-8'
            );

            http_response_code(500);
        }

        echo json_encode(
            [
                'success' => false,
                'message' =>
                    'Erro fatal: '
                    . $erro['message']
                    . ' em '
                    . basename($erro['file'])
                    . ':'
                    . $erro['line'],
                'data' => null
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }
);

try {
    require_once __DIR__
        . '/../core/Database.php';

    require_once __DIR__
        . '/../models/FolhaSalarial.php';

    require_once __DIR__
        . '/../controllers/FolhaSalarialController.php';

    if (
        session_status()
        !== PHP_SESSION_ACTIVE
    ) {
        session_start();
    }

    if (
        ($_SERVER['REQUEST_METHOD'] ?? '')
        !== 'POST'
    ) {
        responder(
            false,
            'Método não permitido.',
            null,
            405
        );
    }

    $entrada =
        file_get_contents('php://input');

    $dadosJson =
        json_decode(
            $entrada ?: '{}',
            true
        );

    $dados =
        is_array($dadosJson)
        ? $dadosJson
        : $_POST;

    $acao =
        trim(
            (string)(
                $dados['acao'] ?? ''
            )
        );

    if ($acao === '') {
        throw new Exception(
            'Ação não informada.'
        );
    }

    $empresaId =
        (int)(
            $_SESSION['empresa_id']
            ?? 0
        );

    $utilizadorId =
        (int)(
            $_SESSION['usuario_id']
            ?? $_SESSION['utilizador_id']
            ?? 0
        );

    if ($empresaId <= 0) {
        responder(
            false,
            'Empresa não identificada na sessão.',
            null,
            401
        );
    }

    $csrfRecebido =
        (string)(
            $dados['csrf_token']
            ?? ''
        );

    $csrfSessao =
        (string)(
            $_SESSION['csrf_token']
            ?? ''
        );

    if (
        $csrfSessao !== ''
        && !hash_equals(
            $csrfSessao,
            $csrfRecebido
        )
    ) {
        responder(
            false,
            'Token CSRF inválido.',
            null,
            419
        );
    }

    $db = Database::conn();

    $controller =
        new FolhaSalarialController($db);

    $resultado =
    match ($acao) {

        'listar' =>
            $controller->listar(
                $empresaId,
                $dados
            ),

        'periodos' =>
            $controller->periodos(
                $empresaId
            ),

        'buscar' =>
            $controller->buscar(
                $empresaId,
                $dados
            ),

        'gerar' =>
            $controller->gerar(
                $empresaId,
                $utilizadorId,
                $dados
            ),

        'recalcular' =>
            $controller->recalcular(
                $empresaId,
                $utilizadorId,
                $dados
            ),

        'fechar' =>
            $controller->fechar(
                $empresaId,
                $utilizadorId,
                $dados
            ),

        'contabilizar' =>
            $controller->contabilizar(
                $empresaId,
                $utilizadorId,
                $dados
            ),

        'cancelar' =>
            $controller->cancelar(
                $empresaId,
                $utilizadorId,
                $dados
            ),

        'funcionarios' =>
            $controller->funcionarios(
                $empresaId,
                $dados
            ),

        'resumo' =>
            $controller->resumo(
                $empresaId
            ),

        default =>
            throw new Exception(
                'Ação inválida: ' . $acao
            )
    };

    responder(
        (bool)(
            $resultado['success']
            ?? true
        ),
        (string)(
            $resultado['message']
            ?? 'Operação realizada.'
        ),
        $resultado['data']
            ?? $resultado
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[FolhaSalarial] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    responder(
        false,
        $e->getMessage(),
        null,
        500
    );
}