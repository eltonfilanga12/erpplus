<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);
ini_set(
    'display_errors',
    '0'
);

ini_set(
    'log_errors',
    '1'
);


require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/Despesa.php';

require_once __DIR__
    . '/../controllers/DespesaController.php';


Auth::iniciarSessao();


function responderDespesaJson(
    array $dados,
    int $statusHttp = 200
): never {

    http_response_code(
        $statusHttp
    );


    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );


    exit;
}


if (!Auth::check()) {

    responderDespesaJson(
        [
            'success' =>
            false,

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
        new DespesaController(
            $pdo
        );


    $metodo =
        strtoupper(
            $_SERVER['REQUEST_METHOD']
                ?? 'GET'
        );


    if (
        !in_array(
            $metodo,
            [
                'GET',
                'POST'
            ],
            true
        )
    ) {

        responderDespesaJson(
            [
                'success' =>
                false,

                'mensagem' =>
                'Método HTTP não permitido.'
            ],
            405
        );
    }


    $acao =
        strtolower(
            trim(
                (string)(
                    $_POST['acao']
                    ?? $_GET['acao']
                    ?? ''
                )
            )
        );


    if ($acao === '') {

        responderDespesaJson(
            [
                'success' =>
                false,

                'mensagem' =>
                'Nenhuma ação foi informada.'
            ],
            400
        );
    }


    switch ($acao) {


        // ==========================================
        // DADOS INICIAIS
        // ==========================================

        case 'dados_iniciais':

            $dados =
                $controller
                ->dadosIniciais();


            responderDespesaJson([
                'success' =>
                true,

                'data' =>
                $dados
            ]);

            break;

        // ==========================================
        // CONTABILIZAR
        // ==========================================

        case 'contabilizar':

            if ($metodo !== 'POST') {

                responderDespesaJson(
                    [
                        'success' =>
                        false,

                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }


            $id =
                (int)(
                    $_POST['id']
                    ?? 0
                );


            $resultado =
                $controller
                ->contabilizar(
                    $id
                );


            responderDespesaJson(
                $resultado
            );

            break;


        // ==========================================
        // LISTAR
        // ==========================================

        case 'listar':

            $dados =
                $controller
                ->listar();


            responderDespesaJson([
                'success' =>
                true,

                'data' =>
                $dados
            ]);

            break;


        // ==========================================
        // BUSCAR
        // ==========================================

        case 'buscar':
        case 'obter':

            $id =
                (int)(
                    $_REQUEST['id']
                    ?? 0
                );


            $despesa =
                $controller
                ->buscar(
                    $id
                );


            if (!$despesa) {

                responderDespesaJson(
                    [
                        'success' =>
                        false,

                        'mensagem' =>
                        'Despesa não encontrada.'
                    ],
                    404
                );
            }


            responderDespesaJson([
                'success' =>
                true,

                'data' =>
                $despesa
            ]);

            break;


        // ==========================================
        // GUARDAR
        // ==========================================

        case 'guardar':
        case 'salvar':

            if ($metodo !== 'POST') {

                responderDespesaJson(
                    [
                        'success' =>
                        false,

                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }


            $id =
                $controller
                ->guardar(
                    $_POST
                );


            responderDespesaJson([
                'success' =>
                true,

                'mensagem' =>
                'Despesa guardada em rascunho com sucesso.',

                'id' =>
                $id
            ]);

            break;


        // ==========================================
        // AÇÃO INVÁLIDA
        // ==========================================

        default:

            responderDespesaJson(
                [
                    'success' =>
                    false,

                    'mensagem' =>
                    'Ação inválida para o módulo de Despesas.'
                ],
                400
            );
    }
} catch (Throwable $e) {

    error_log(
        sprintf(
            '[despesa_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    responderDespesaJson(
        [
            'success' =>
            false,

            'mensagem' =>
            $e->getMessage()
        ],
        500
    );
}
