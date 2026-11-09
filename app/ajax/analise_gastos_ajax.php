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
    . '/../models/AnaliseGastos.php';

require_once __DIR__
    . '/../controllers/AnaliseGastosController.php';


Auth::iniciarSessao();


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

    $pdo =
        Database::conn();


    $controller =
        new AnaliseGastosController(
            $pdo
        );


    $acao =
        strtolower(
            trim(
                (string)(
                    $_REQUEST['acao']
                    ?? ''
                )
            )
        );


    switch ($acao) {

        // =====================================
        // DADOS INICIAIS
        // =====================================

        case 'dados_iniciais':

            echo json_encode([
                'success' => true,

                'data' =>
                    $controller
                        ->dadosIniciais()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // GERAR ANÁLISE
        // =====================================

        case 'gerar':

            $dados = [
                'data_inicial' =>
                    $_REQUEST[
                        'data_inicial'
                    ]
                    ?? null,

                'data_final' =>
                    $_REQUEST[
                        'data_final'
                    ]
                    ?? null,

                'filial_id' =>
                    $_REQUEST[
                        'filial_id'
                    ]
                    ?? null,

                'plano_contas_id' =>
                    $_REQUEST[
                        'plano_contas_id'
                    ]
                    ?? null,

                'centro_custo_id' =>
                    $_REQUEST[
                        'centro_custo_id'
                    ]
                    ?? null,

                'comparar_periodo_anterior' =>
                    $_REQUEST[
                        'comparar_periodo_anterior'
                    ]
                    ?? true
            ];


            $resultado =
                $controller
                    ->gerar(
                        $dados
                    );


            echo json_encode([
                'success' => true,

                'mensagem' =>
                    'Análise de gastos gerada com sucesso.',

                'data' =>
                    $resultado
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
                    'Acção inválida para a Análise de Gastos.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,

        'mensagem' =>
            $e->getMessage(),

        'linha' =>
            $e->getLine(),

        'arquivo' =>
            $e->getFile()
    ], JSON_UNESCAPED_UNICODE);
}