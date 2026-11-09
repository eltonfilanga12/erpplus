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
    . '/../models/DemonstracaoResultados.php';

require_once __DIR__
    . '/../controllers/DemonstracaoResultadosController.php';

Auth::iniciarSessao();

if (!Auth::check()) {

    http_response_code(401);

    echo json_encode([
        'success' =>
        false,

        'mensagem' =>
        'Sessão expirada. Faça login novamente.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    $pdo =
        Database::conn();

    $controller =
        new DemonstracaoResultadosController(
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
                'success' =>
                true,

                'data' =>
                $controller
                    ->dadosIniciais()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // LISTAR PLANOS
        // =====================================

        case 'planos':

            echo json_encode([
                'success' =>
                true,

                'data' =>
                $controller
                    ->planos()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // LISTAR FILIAIS
        // =====================================

        case 'filiais':

            echo json_encode([
                'success' =>
                true,

                'data' =>
                $controller
                    ->filiais()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // GERAR DEMONSTRAÇÃO DE RESULTADOS
        // =====================================

        case 'gerar':

            $dados =
                $controller->gerar([
                    'data_inicio' =>
                    $_REQUEST['data_inicio'] ?? null,

                    'data_fim' =>
                    $_REQUEST['data_fim'] ?? null,

                    'filial_id' =>
                    $_REQUEST['filial_id'] ?? null,

                    'plano_contas_id' =>
                    $_REQUEST['plano_contas_id'] ?? null
                ]);

            echo json_encode([
                'success' =>
                true,

                'mensagem' =>
                'Demonstração de Resultados gerada com sucesso.',

                'data' =>
                $dados
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // AÇÃO INVÁLIDA
        // =====================================

        default:

            http_response_code(400);

            echo json_encode([
                'success' =>
                false,

                'mensagem' =>
                'Acção inválida para a Demonstração de Resultados.'
            ], JSON_UNESCAPED_UNICODE);

            break;
    }
} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' =>
        false,

        'mensagem' =>
        $e->getMessage(),

        'linha' =>
        $e->getLine(),

        'arquivo' =>
        $e->getFile()
    ], JSON_UNESCAPED_UNICODE);
}
