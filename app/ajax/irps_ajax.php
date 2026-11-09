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
    . '/../models/Irps.php';

require_once __DIR__
    . '/../controllers/IrpsController.php';

Auth::iniciarSessao();

if (!Auth::check()) {

    http_response_code(401);

    responderJson([
        'success' => false,

        'mensagem' =>
        'Sessão expirada. Faça login novamente.'
    ]);

    exit;
}

try {

    $pdo =
        Database::conn();

    $controller =
        new IrpsController(
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

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->dadosIniciais()
            ]);

            break;


        // =====================================
        // TABELA VIGENTE
        // =====================================

        case 'tabela_vigente':

            $ano =
                isset($_GET['ano'])
                && $_GET['ano'] !== ''
                ? (int)$_GET['ano']
                : null;

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->tabelaVigente(
                        $ano
                    )
            ]);

            break;


        // =====================================
        // LISTAR
        // =====================================

        case 'listar':

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->listar()
            ]);

            break;


        // =====================================
        // BUSCAR
        // =====================================

        case 'buscar':

            $id =
                (int)(
                    $_GET['id']
                    ?? 0
                );

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->buscar(
                        $id
                    )
            ]);

            break;


        // =====================================
        // ESCALÕES
        // =====================================

        case 'escaloes':

            $tabelaId =
                (int)(
                    $_GET['tabela_id']
                    ?? $_GET['id']
                    ?? 0
                );

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->escaloes(
                        $tabelaId
                    )
            ]);

            break;


        // =====================================
        // RESUMO
        // =====================================

        case 'resumo':

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->resumo()
            ]);

            break;


        // =====================================
        // INVÁLIDA
        // =====================================

        default:

            http_response_code(400);

            responderJson([
                'success' => false,

                'mensagem' =>
                'Acção inválida para a tabela de IRPS.'
            ]);

            break;
    }
} catch (RuntimeException $e) {

    http_response_code(422);

    responderJson([
        'success' => false,

        'mensagem' =>
        $e->getMessage()
    ]);
} catch (PDOException $e) {

    http_response_code(500);

    responderJson([
        'success' => false,

        'mensagem' =>
        'Erro ao aceder à base de dados.',

        'detalhe' =>
        $e->getMessage()
    ]);
} catch (Throwable $e) {

    http_response_code(500);

    responderJson([
        'success' => false,

        'mensagem' =>
        $e->getMessage()
    ]);
}


// ==================================================
// JSON
// ==================================================

function responderJson(
    array $dados
): void {

    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );
}
