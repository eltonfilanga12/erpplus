<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../services/ContabilizacaoFolhaService.php';

function responder(
    array $dados,
    int $status = 200
): never {
    http_response_code($status);

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}

try {
    $folhaId = (int)($_GET['folha_id'] ?? 0);

    /*
     * Ajusta conforme a tua sessão.
     * No teu sistema pode ser:
     * $_SESSION['usuario_id']
     * $_SESSION['usuario']['id']
     * Auth::id()
     */
    $usuarioId = (int)(
        $_SESSION['usuario_id']
        ?? $_SESSION['usuario']['id']
        ?? 0
    );

    if ($folhaId <= 0) {
        responder(
            [
                'sucesso' => false,
                'mensagem' => 'Informe o ID da folha salarial.'
            ],
            400
        );
    }

    if ($usuarioId <= 0) {
        responder(
            [
                'sucesso' => false,
                'mensagem' =>
                'Não foi possível identificar o utilizador autenticado.'
            ],
            401
        );
    }

    $pdo = Database::conn();

    $service = new ContabilizacaoFolhaService(
        $pdo
    );

    $resultado =
        $service->contabilizarFolha(
            $folhaId,
            $usuarioId
        );

    responder($resultado);
} catch (Throwable $e) {
    error_log(
        '[teste_contabilizacao_folha] '
            . $e->getMessage()
    );

    responder(
        [
            'sucesso' => false,
            'mensagem' => $e->getMessage(),
            'ficheiro' => basename($e->getFile()),
            'linha' => $e->getLine()
        ],
        500
    );
}
