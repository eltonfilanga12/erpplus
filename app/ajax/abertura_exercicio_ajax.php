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
    . '/../models/AberturaExercicio.php';

require_once __DIR__
    . '/../controllers/AberturaExercicioController.php';

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
        new AberturaExercicioController(
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
        // LISTAR EXERCÍCIOS
        // =====================================

        case 'listar':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->listar()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // DIAGNOSTICAR ABERTURA
        // =====================================

        case 'diagnosticar':

            $exercicioOrigemId =
                (int)(
                    $_GET['exercicio_origem_id']
                    ?? $_GET['exercicio_id']
                    ?? $_GET['id']
                    ?? 0
                );

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->diagnosticar(
                        $exercicioOrigemId
                    )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // GERAR ABERTURA
        // =====================================

        case 'abrir':

            if (
                ($_SERVER['REQUEST_METHOD'] ?? '')
                !== 'POST'
            ) {
                http_response_code(405);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Método inválido para gerar a abertura do exercício.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            $exercicioOrigemId =
                (int)(
                    $_POST['exercicio_origem_id']
                    ?? $_POST['exercicio_id']
                    ?? $_POST['id']
                    ?? 0
                );

            $resultado =
                $controller
                ->abrir(
                    $exercicioOrigemId
                );

            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Abertura do exercício gerada com sucesso.',

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
                'Acção inválida para a Abertura do Exercício.'
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
