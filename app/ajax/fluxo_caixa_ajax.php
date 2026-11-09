<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

require_once __DIR__ . '/../models/FluxoCaixa.php';
require_once __DIR__ . '/../controllers/FluxoCaixaController.php';

Auth::iniciarSessao();

/*
|--------------------------------------------------------------------------
| AJAX não deve redirecionar para HTML
|--------------------------------------------------------------------------
*/

if (!Auth::check()) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'mensagem' =>
        'Sessão expirada. Faça login novamente.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $pdo = Database::conn();

    $controller =
        new FluxoCaixaController($pdo);

    $acao = strtolower(
        trim(
            (string)(
                $_REQUEST['acao'] ?? ''
            )
        )
    );

    switch ($acao) {

        // =====================================
        // DASHBOARD
        // Cards + gráfico
        // =====================================

        case 'dashboard':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->dashboard(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // FLUXO CONSOLIDADO
        // =====================================

        case 'consolidado':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->consolidado(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // PREVISTO
        // =====================================

        case 'previsto':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->previsto(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // REALIZADO
        // =====================================

        case 'realizado':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->realizado(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // DISPONIBILIDADES TOTAIS
        // =====================================

        case 'disponibilidades':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->disponibilidades(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // SALDOS DOS CAIXAS
        // =====================================

        case 'saldos_caixas':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->saldosCaixas(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // SALDOS DOS BANCOS
        // =====================================

        case 'saldos_bancos':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->saldosBancos(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // RESUMO PREVISTO
        // =====================================

        case 'resumo_previsto':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->resumoPrevisto(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // RESUMO REALIZADO
        // =====================================

        case 'resumo_realizado':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->resumoRealizado(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // GRÁFICO
        // =====================================

        case 'grafico':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller->grafico(
                    $_REQUEST
                )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // DETALHES
        // =====================================

        case 'detalhes':

            $detalhes =
                $controller->detalhes(
                    $_REQUEST
                );

            if (!$detalhes) {
                http_response_code(404);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Movimento do fluxo de caixa não encontrado.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            echo json_encode([
                'success' => true,

                'data' => $detalhes
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // AÇÃO INVÁLIDA
        // =====================================

        default:

            http_response_code(400);

            echo json_encode([
                'success' => false,

                'mensagem' =>
                'Ação inválida para o fluxo de caixa.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,

        'mensagem' =>
        $e->getMessage(),

        /*
         * Durante o desenvolvimento podes manter
         * estes campos. Em produção, remove-os.
         */

        'linha' =>
        $e->getLine(),

        'arquivo' =>
        $e->getFile()
    ], JSON_UNESCAPED_UNICODE);
}
