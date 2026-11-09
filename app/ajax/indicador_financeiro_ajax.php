<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/IndicadorFinanceiro.php';

require_once __DIR__
    . '/../controllers/IndicadorFinanceiroController.php';

Auth::iniciarSessao();

function responderIndicador(
    bool $success,
    array $dados = [],
    int $codigo = 200
): void {

    http_response_code(
        $codigo
    );

    echo json_encode(
        [
            'success' =>
            $success
        ] + $dados,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}

if (!Auth::check()) {
    responderIndicador(
        false,
        [
            'mensagem' =>
            'Sessão expirada. Faça login novamente.'
        ],
        401
    );
}

try {
    $pdo =
        Database::conn();

    $controller =
        new IndicadorFinanceiroController(
            $pdo
        );

    $acao =
        strtolower(
            trim(
                (string)(
                    $_REQUEST['acao']
                    ?? 'painel'
                )
            )
        );

    $dataInicial =
        trim(
            (string)(
                $_REQUEST['data_inicial']
                ?? date('Y-m-01')
            )
        );

    $dataFinal =
        trim(
            (string)(
                $_REQUEST['data_final']
                ?? date('Y-m-d')
            )
        );

    $meses =
        (int)(
            $_REQUEST['meses']
            ?? 12
        );

    $limite =
        (int)(
            $_REQUEST['limite']
            ?? 20
        );

    switch ($acao) {

        // =====================================
        // PAINEL COMPLETO
        // =====================================

        case 'painel':

            responderIndicador(
                true,
                [
                    'data' =>
                    $controller->painel(
                        $dataInicial,
                        $dataFinal,
                        $meses
                    )
                ]
            );

            break;


        // =====================================
        // RESUMO
        // =====================================

        case 'resumo':

            responderIndicador(
                true,
                [
                    'data' =>
                    $controller->resumo(
                        $dataInicial,
                        $dataFinal
                    )
                ]
            );

            break;


        // =====================================
        // FLUXO MENSAL
        // =====================================

        case 'fluxo_mensal':

            responderIndicador(
                true,
                [
                    'data' =>
                    $controller
                        ->fluxoMensal(
                            $meses
                        )
                ]
            );

            break;


        // =====================================
        // POR CENTRO DE CUSTO
        // =====================================

        case 'centros_custo':
        case 'por_centro_custo':

            responderIndicador(
                true,
                [
                    'data' =>
                    $controller
                        ->porCentroCusto(
                            $dataInicial,
                            $dataFinal
                        )
                ]
            );

            break;


        // =====================================
        // CONTAS VENCIDAS
        // =====================================

        case 'contas_vencidas':

            responderIndicador(
                true,
                [
                    'data' =>
                    $controller
                        ->contasVencidas(
                            $limite
                        )
                ]
            );

            break;


        // =====================================
        // CLIENTES COM MAIOR DÍVIDA
        // =====================================

        case 'clientes_maior_divida':

            responderIndicador(
                true,
                [
                    'data' =>
                    $controller
                        ->clientesMaiorDivida(
                            $limite
                        )
                ]
            );

            break;


        // =====================================
        // FORNECEDORES COM MAIOR DÍVIDA
        // =====================================

        case 'fornecedores_maior_divida':

            responderIndicador(
                true,
                [
                    'data' =>
                    $controller
                        ->fornecedoresMaiorDivida(
                            $limite
                        )
                ]
            );

            break;


        // =====================================
        // AÇÃO INVÁLIDA
        // =====================================

        default:

            responderIndicador(
                false,
                [
                    'mensagem' =>
                    'Acção inválida para indicadores financeiros.'
                ],
                400
            );
    }
} catch (Throwable $e) {
    responderIndicador(
        false,
        [
            'mensagem' =>
            $e->getMessage(),

            'linha' =>
            $e->getLine(),

            'arquivo' =>
            $e->getFile()
        ],
        500
    );
}
