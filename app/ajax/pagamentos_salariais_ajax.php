<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

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
| Model e Controller
|--------------------------------------------------------------------------
*/

require_once __DIR__
    . '/../models/PagamentoSalarial.php';

require_once __DIR__
    . '/../controllers/PagamentoSalarialController.php';

/*
|--------------------------------------------------------------------------
| Função de resposta
|--------------------------------------------------------------------------
*/

function responderJson(
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

/*
|--------------------------------------------------------------------------
| Autenticação
|--------------------------------------------------------------------------
*/

if (!Auth::check()) {
    responderJson(
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
| Método permitido
|--------------------------------------------------------------------------
*/

$metodoHttp = strtoupper(
    $_SERVER['REQUEST_METHOD'] ?? 'GET'
);

if (
    !in_array(
        $metodoHttp,
        ['GET', 'POST'],
        true
    )
) {
    responderJson(
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
| Ação solicitada
|--------------------------------------------------------------------------
*/

$acao = trim(
    (string) (
        $_POST['acao']
        ?? $_GET['acao']
        ?? ''
    )
);

if ($acao === '') {
    responderJson(
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
    new PagamentoSalarialController();

/*
|--------------------------------------------------------------------------
| Rotas
|--------------------------------------------------------------------------
*/

try {
    switch ($acao) {

        case 'listar':
            $resposta =
                $controller->listar();
            break;

        case 'buscar':
            $resposta =
                $controller->buscar();
            break;

        case 'criar':
            $resposta =
                $controller->criar();
            break;

        case 'atualizar':
            $resposta =
                $controller->atualizar();
            break;

        case 'confirmar':
            $resposta =
                $controller->confirmar();
            break;

        case 'sincronizar':
            $resposta =
                $controller
                ->sincronizarPagamentosConfirmados();
            break;

        case 'cancelar':
            $resposta =
                $controller->cancelar();
            break;

        case 'recibos_disponiveis':
            $resposta =
                $controller->recibosDisponiveis();
            break;

        case 'processamentos_disponiveis':
            $resposta =
                $controller->processamentosDisponiveis();
            break;

        case 'contas_pagamento':
            $resposta =
                $controller->contasPagamento();
            break;

        case 'disponiveis_lote':

            $resposta =
                $controller
                ->disponiveisLote();

            break;

        case 'confirmar_lote':

            $resposta =
                $controller
                ->confirmarLote();

            break;

        case 'criar_lote':

            $resposta =
                $controller
                ->criarLote();

            break;
        case 'status':
            $resposta =
                $controller->status();
            break;

        case 'resumo':
            $resposta =
                $controller->resumo();
            break;

        default:
            responderJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }

    $statusHttp =
        !empty($resposta['sucesso'])
        ? 200
        : 422;

    responderJson(
        $resposta,
        $statusHttp
    );
} catch (Throwable $e) {
    error_log(
        '[pagamentos_salariais_ajax] '
            . $e->getMessage()
    );

    responderJson(
        [
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ],
        500
    );
}
