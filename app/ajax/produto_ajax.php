<?php

declare(strict_types=1);

if (
    session_status()
    !== PHP_SESSION_ACTIVE
) {
    session_start();
}

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/Produto.php';

require_once __DIR__
    . '/../controllers/ProdutoController.php';



function responderProdutoJson(
    array $resposta,
    int $statusHttp = 200
): never {
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

    $metodo =
        strtoupper(
            $_SERVER['REQUEST_METHOD']
                ?? 'GET'
        );


    if (
        !in_array(
            $metodo,
            ['GET', 'POST'],
            true
        )
    ) {
        responderProdutoJson(
            [
                'sucesso' => false,
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

        responderProdutoJson(
            [
                'sucesso' => false,
                'mensagem' =>
                'Nenhuma ação foi informada.'
            ],
            400
        );
    }


    $controller =
        new ProdutoController();


    switch ($acao) {

        // ==============================
        // LISTAR PRODUTOS
        // ==============================

        case 'listar':

            $resposta =
                $controller->listar();

            break;



        // ==============================
        // BUSCAR PRODUTO
        // ==============================

        case 'buscar':
        case 'obter':

            $resposta =
                $controller->buscar();

            break;



        // ==============================
        // CATEGORIAS
        // ==============================

        case 'categorias':

            $resposta =
                $controller->categorias();

            break;



        // ==============================
        // MARCAS
        // ==============================

        case 'marcas':

            $resposta =
                $controller->marcas();

            break;



        // ==============================
        // UNIDADES
        // ==============================

        case 'unidades':

            $resposta =
                $controller->unidades();

            break;



        // ==============================
        // ARMAZÉNS
        // ==============================

        case 'armazens':

            $resposta =
                $controller->armazens();

            break;



        // ==============================
        // STATUS
        // ==============================

        case 'status':

            $resposta =
                $controller->status();

            break;


        // ==============================
        // GERAR SKU AUTOMATICAMENTE
        // ==============================

        case 'gerar_sku':

            $resposta =
                $controller->gerarSku();

            break;

        // ==============================
        // GUARDAR PRODUTO
        // ==============================

        case 'salvar':
        case 'guardar':

            if ($metodo !== 'POST') {

                responderProdutoJson(
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

        // ==============================
        // VALIDAR IMPORTAÇÃO EXCEL
        // ==============================

        case 'validar_importacao':

            if ($metodo !== 'POST') {
                responderProdutoJson(
                    [
                        'sucesso' => false,
                        'mensagem' => 'Método HTTP não permitido.'
                    ],
                    405
                );
            }

            require_once __DIR__ . '/../services/ProdutoImportacaoService.php';

            $resposta =
                $controller->validarImportacaoExcel();

            break;


        case 'confirmar_importacao':

            if ($metodo !== 'POST') {
                responderProdutoJson(
                    [
                        'sucesso' => false,
                        'mensagem' => 'Método HTTP não permitido.'
                    ],
                    405
                );
            }

            require_once __DIR__ . '/../services/ProdutoImportacaoService.php';

            $resposta =
                $controller->confirmarImportacaoExcel();

            break;

        case 'baixar_modelo_excel':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'GET'
            ) {

                http_response_code(405);

                echo json_encode([
                    'success' => false,
                    'mensagem' =>
                    'Método inválido para baixar o modelo Excel.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }

            require_once __DIR__ . '/../services/ProdutoImportacaoService.php';
            $controller
                ->baixarModeloImportacaoExcel();

            break;

        // ==============================
        // ELIMINAR PRODUTO
        // ==============================

        case 'eliminar':

            if ($metodo !== 'POST') {

                responderProdutoJson(
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



        // ==============================
        // AÇÃO INVÁLIDA
        // ==============================

        default:

            responderProdutoJson(
                [
                    'sucesso' => false,
                    'mensagem' =>
                    'Ação inválida.'
                ],
                400
            );
    }


    responderProdutoJson(
        $resposta,
        !empty($resposta['sucesso'])
            ? 200
            : 422
    );
} catch (Throwable $e) {

    error_log(
        sprintf(
            '[produto_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    responderProdutoJson(
        [
            'sucesso' => false,

            // Durante desenvolvimento,
            // melhor mostrar o erro real.
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
