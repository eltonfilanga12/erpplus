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
    . '/../models/RazaoContabilistico.php';

require_once __DIR__
    . '/../controllers/RazaoContabilisticoController.php';

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
        new RazaoContabilisticoController(
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
        // LISTAR CONTAS
        // =====================================

        case 'contas':

            $planoContasId =
                !empty($_REQUEST['plano_contas_id'])
                ? (int)$_REQUEST['plano_contas_id']
                : null;

            echo json_encode([
                'success' =>
                true,

                'data' =>
                $controller
                    ->contas(
                        $planoContasId
                    )
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
        // GERAR RAZÃO
        // =====================================

        case 'gerar':

            $dados =
                $controller->gerar([
                    'conta_contabil_id' =>
                    $_REQUEST['conta_contabil_id'] ?? null,

                    'data_inicio' =>
                    $_REQUEST['data_inicio'] ?? null,

                    'data_fim' =>
                    $_REQUEST['data_fim'] ?? null,

                    'filial_id' =>
                    $_REQUEST['filial_id'] ?? null
                ]);

            echo json_encode([
                'success' =>
                true,

                'mensagem' =>
                'Razão Geral gerado com sucesso.',

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
                'Acção inválida para o Razão Geral.'
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
