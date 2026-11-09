<?php

declare(strict_types=1);


if (
    session_status()
    !== PHP_SESSION_ACTIVE
) {
    session_start();
}


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
    . '/../models/Cotacao.php';

require_once __DIR__
    . '/../models/Cliente.php';

require_once __DIR__
    . '/../models/Produto.php';

require_once __DIR__
    . '/../controllers/CotacaoController.php';


function responderCotacaoJson(
    array $resposta,
    int $statusHttp = 200
): never {

    if (
        !headers_sent()
    ) {

        header(
            'Content-Type: application/json; charset=utf-8'
        );
    }


    http_response_code(
        $statusHttp
    );


    echo json_encode(
        $resposta,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );


    exit;
}


try {

    // ==================================================
    // MÉTODO HTTP
    // ==================================================

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

        responderCotacaoJson(
            [
                'sucesso' => false,

                'mensagem' =>
                'Método HTTP não permitido.'
            ],
            405
        );
    }


    // ==================================================
    // AÇÃO
    // ==================================================

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

        responderCotacaoJson(
            [
                'sucesso' => false,

                'mensagem' =>
                'Nenhuma ação foi informada.'
            ],
            400
        );
    }


    // ==================================================
    // CONTROLLER
    // ==================================================

    $controller =
        new CotacaoController();


    // ==================================================
    // IMPRESSÃO
    // ==================================================

    /*
     * A impressão precisa ser processada antes
     * de qualquer resposta JSON.
     *
     * O controller envia diretamente o PDF.
     */

    if (
        $acao === 'imprimir'
        || $acao === 'pdf'
    ) {

        if (
            $metodo !== 'GET'
            && $metodo !== 'POST'
        ) {

            responderCotacaoJson(
                [
                    'sucesso' => false,

                    'mensagem' =>
                    'Método HTTP não permitido.'
                ],
                405
            );
        }


        $controller->imprimir();

        exit;
    }


    // ==================================================
    // SWITCH
    // ==================================================

    switch ($acao) {

        // ==============================================
        // LISTAR
        // ==============================================

        case 'listar':

            $resposta =
                $controller->listar();

            break;


        // ==============================================
        // BUSCAR
        // ==============================================

        case 'buscar':
        case 'obter':

            $resposta =
                $controller->buscar();

            break;


        // ==============================================
        // CLIENTES
        // ==============================================

        case 'clientes':
        case 'pesquisar_clientes':

            $resposta =
                $controller->clientes();

            break;


        // ==============================================
        // PRODUTOS
        // ==============================================

        case 'produtos':
        case 'pesquisar_produtos':

            $resposta =
                $controller->produtos();

            break;


        // ==============================================
        // STATUS
        // ==============================================

        case 'status':

            $resposta =
                $controller->status();

            break;


        // ==============================================
        // GERAR NÚMERO
        // ==============================================

        case 'gerar_numero':

            $resposta =
                $controller->gerarNumero();

            break;


        // ==============================================
        // SALVAR
        // ==============================================

        case 'salvar':
        case 'guardar':

            if (
                $metodo !== 'POST'
            ) {

                responderCotacaoJson(
                    [
                        'sucesso' => false,

                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }


            $resposta =
                $controller->salvar();

            break;


        // ==============================================
        // ELIMINAR
        // ==============================================

        case 'eliminar':

            if (
                $metodo !== 'POST'
            ) {

                responderCotacaoJson(
                    [
                        'sucesso' => false,

                        'mensagem' =>
                        'Método HTTP não permitido.'
                    ],
                    405
                );
            }


            $resposta =
                $controller->eliminar();

            break;


        // ==============================================
        // AÇÃO INVÁLIDA
        // ==============================================

        default:

            responderCotacaoJson(
                [
                    'sucesso' => false,

                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }


    // ==================================================
    // RESPOSTA JSON
    // ==================================================

    responderCotacaoJson(
        $resposta,

        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {

    error_log(
        sprintf(
            '[cotacao_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    /*
     * Se por alguma razão o erro acontecer durante
     * uma tentativa de PDF, ainda devolvemos HTML.
     */

    if (
        $acao === 'imprimir'
        || $acao === 'pdf'
    ) {

        http_response_code(500);

        header(
            'Content-Type: text/html; charset=utf-8'
        );

        echo '
            <h3>
                Erro ao gerar a cotação.
            </h3>

            <p>
                '
            . htmlspecialchars(
                $e->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            )
            . '
            </p>
        ';

        exit;
    }


    responderCotacaoJson(
        [
            'sucesso' => false,

            'mensagem' =>
            $e->getMessage(),

            'arquivo' =>
            basename(
                $e->getFile()
            ),

            'linha' =>
            $e->getLine()
        ],
        500
    );
}
