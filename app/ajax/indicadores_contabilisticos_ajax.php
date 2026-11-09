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
    . '/../models/BalancoPatrimonial.php';

require_once __DIR__
    . '/../models/DemonstracaoResultados.php';

if (
    file_exists(
        __DIR__
            . '/../models/DemonstracaoFluxoCaixa.php'
    )
) {
    require_once __DIR__
        . '/../models/DemonstracaoFluxoCaixa.php';
}

require_once __DIR__
    . '/../models/IndicadoresContabilisticos.php';

require_once __DIR__
    . '/../controllers/IndicadoresContabilisticosController.php';

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
        new IndicadoresContabilisticosController(
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
        // GERAR INDICADORES
        // =====================================

        case 'gerar':

            if (
                ($_SERVER['REQUEST_METHOD'] ?? '')
                !== 'POST'
            ) {
                http_response_code(405);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Método inválido para gerar os Indicadores Contabilísticos.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            $dados =
                $_POST;

            /*
             * Compatibilidade com requisições
             * enviadas em formato JSON.
             */
            $contentType =
                strtolower(
                    trim(
                        (string)(
                            $_SERVER['CONTENT_TYPE']
                            ?? ''
                        )
                    )
                );

            if (
                str_contains(
                    $contentType,
                    'application/json'
                )
            ) {
                $conteudo =
                    file_get_contents(
                        'php://input'
                    );

                $json =
                    json_decode(
                        $conteudo ?: '',
                        true
                    );

                if (
                    json_last_error()
                    !== JSON_ERROR_NONE
                ) {
                    throw new RuntimeException(
                        'Os filtros enviados são inválidos.'
                    );
                }

                if (is_array($json)) {
                    $dados = $json;
                }
            }

            $resultado =
                $controller
                ->gerar(
                    $dados
                );

            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Indicadores Contabilísticos gerados com sucesso.',

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
                'Acção inválida para os Indicadores Contabilísticos.'
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
