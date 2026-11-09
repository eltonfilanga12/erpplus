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
    . '/../models/SaldosAbertura.php';

require_once __DIR__
    . '/../controllers/SaldosAberturaController.php';


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
        new SaldosAberturaController(
            $pdo
        );


    // ==================================================
    // DADOS RECEBIDOS
    // ==================================================

    $dados =
        $_POST;


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
                'Os dados enviados são inválidos.'
            );
        }


        if (is_array($json)) {
            $dados = $json;
        }
    }


    // ==================================================
    // AÇÃO
    // ==================================================

    $acao =
        strtolower(
            trim(
                (string)(
                    $dados['acao']
                    ?? $_REQUEST['acao']
                    ?? ''
                )
            )
        );


    switch ($acao) {

        // ==============================================
        // DADOS INICIAIS
        // ==============================================

        case 'dados_iniciais':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->dadosIniciais()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==============================================
        // LISTAR ABERTURAS
        // ==============================================

        case 'listar':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->listar()
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==============================================
        // BUSCAR ABERTURA
        // ==============================================

        case 'buscar':

            $id =
                filter_var(
                    $dados['id']
                        ?? $_REQUEST['id']
                        ?? null,
                    FILTER_VALIDATE_INT
                );


            if (
                $id === false
                || $id <= 0
            ) {

                throw new RuntimeException(
                    'Saldo de abertura inválido.'
                );
            }


            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->buscar(
                        (int)$id
                    )
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==============================================
        // GUARDAR RASCUNHO
        // ==============================================

        case 'guardar':

            if (
                ($_SERVER['REQUEST_METHOD'] ?? '')
                !== 'POST'
            ) {

                http_response_code(405);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Método inválido para guardar saldo de abertura.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }


            $resultado =
                $controller
                ->guardar(
                    $dados
                );


            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Saldo de abertura guardado como rascunho.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==============================================
        // ENTRADAS DE STOCK NÃO CONTABILIZADAS
        // ==============================================

        case 'entradas_stock':

            $resultado =
                $controller
                ->entradasStock([
                    'data_inicial' =>
                    $_REQUEST['data_inicial']
                        ?? $dados['data_inicial']
                        ?? null,

                    'data_final' =>
                    $_REQUEST['data_final']
                        ?? $dados['data_final']
                        ?? null,

                    'filial_id' =>
                    $_REQUEST['filial_id']
                        ?? $dados['filial_id']
                        ?? null
                ]);

            echo json_encode([
                'success' => true,

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;

        // =====================================
        // CAIXAS PARA SALDO DE ABERTURA
        // =====================================

        case 'listar_caixas_abertura':

            echo json_encode([
                'success' =>
                true,

                'data' =>
                $controller
                    ->caixasDisponiveis([
                        'data_abertura' =>
                        $_REQUEST['data_abertura']
                            ?? null,

                        'filial_id' =>
                        $_REQUEST['filial_id']
                            ?? null
                    ])
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // BANCOS PARA SALDO DE ABERTURA
        // =====================================

        case 'listar_bancos_abertura':

            echo json_encode([
                'success' =>
                true,

                'data' =>
                $controller
                    ->bancosDisponiveis([
                        'data_abertura' =>
                        $_REQUEST['data_abertura']
                            ?? null,

                        'filial_id' =>
                        $_REQUEST['filial_id']
                            ?? null
                    ])
            ], JSON_UNESCAPED_UNICODE);

            break;

        // =====================================
        // IMPORTAR CAIXA
        // =====================================

        case 'importar_caixa':

            $resultado =
                $controller
                ->importarCaixa([
                    'plano_contas_id' =>
                    $_REQUEST['plano_contas_id']
                        ?? $dados['plano_contas_id']
                        ?? null,

                    'caixa_id' =>
                    $_REQUEST['caixa_id']
                        ?? $dados['caixa_id']
                        ?? null,

                    'data_abertura' =>
                    $_REQUEST['data_abertura']
                        ?? $dados['data_abertura']
                        ?? null
                ]);


            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Saldo de caixa preparado para a abertura.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // IMPORTAR BANCO
        // =====================================

        case 'importar_banco':

            $resultado =
                $controller
                ->importarBanco([
                    'plano_contas_id' =>
                    $_REQUEST['plano_contas_id']
                        ?? $dados['plano_contas_id']
                        ?? null,

                    'banco_id' =>
                    $_REQUEST['banco_id']
                        ?? $dados['banco_id']
                        ?? null,

                    'data_abertura' =>
                    $_REQUEST['data_abertura']
                        ?? $dados['data_abertura']
                        ?? null
                ]);


            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Saldo bancário preparado para a abertura.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;

        case 'listar_clientes_abertura':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->clientesDisponiveis([
                        'data_abertura' =>
                        $_REQUEST['data_abertura']
                            ?? null,

                        'filial_id' =>
                        $_REQUEST['filial_id']
                            ?? null
                    ])
            ], JSON_UNESCAPED_UNICODE);

            break;


        case 'listar_fornecedores_abertura':

            echo json_encode([
                'success' => true,

                'data' =>
                $controller
                    ->fornecedoresDisponiveis([
                        'data_abertura' =>
                        $_REQUEST['data_abertura']
                            ?? null,

                        'filial_id' =>
                        $_REQUEST['filial_id']
                            ?? null
                    ])
            ], JSON_UNESCAPED_UNICODE);

            break;

        case 'importar_cliente':

            $resultado =
                $controller
                ->importarCliente([
                    'plano_contas_id' =>
                    $_REQUEST['plano_contas_id']
                        ?? $dados['plano_contas_id']
                        ?? null,

                    'cliente_id' =>
                    $_REQUEST['cliente_id']
                        ?? $dados['cliente_id']
                        ?? null,

                    'data_abertura' =>
                    $_REQUEST['data_abertura']
                        ?? $dados['data_abertura']
                        ?? null
                ]);

            echo json_encode([
                'success' => true,
                'mensagem' =>
                'Saldo do cliente preparado para a abertura.',
                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;


        case 'importar_fornecedor':

            $resultado =
                $controller
                ->importarFornecedor([
                    'plano_contas_id' =>
                    $_REQUEST['plano_contas_id']
                        ?? $dados['plano_contas_id']
                        ?? null,

                    'fornecedor_id' =>
                    $_REQUEST['fornecedor_id']
                        ?? $dados['fornecedor_id']
                        ?? null,

                    'data_abertura' =>
                    $_REQUEST['data_abertura']
                        ?? $dados['data_abertura']
                        ?? null
                ]);

            echo json_encode([
                'success' => true,
                'mensagem' =>
                'Saldo do fornecedor preparado para a abertura.',
                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;

        // =====================================
        // LISTAR ATIVOS PARA ABERTURA
        // =====================================

        case 'listar_ativos_abertura':

            echo json_encode([
                'success' =>
                true,

                'data' =>
                $controller
                    ->ativosDisponiveis([
                        'data_abertura' =>
                        $_REQUEST['data_abertura']
                            ?? null,

                        'filial_id' =>
                        $_REQUEST['filial_id']
                            ?? null
                    ])
            ], JSON_UNESCAPED_UNICODE);

            break;


        // =====================================
        // IMPORTAR ATIVO
        // =====================================

        case 'importar_ativo':

            $resultado =
                $controller
                ->importarAtivo([
                    'plano_contas_id' =>
                    $_REQUEST['plano_contas_id']
                        ?? $dados['plano_contas_id']
                        ?? null,

                    'ativo_id' =>
                    $_REQUEST['ativo_id']
                        ?? $dados['ativo_id']
                        ?? null,

                    'data_abertura' =>
                    $_REQUEST['data_abertura']
                        ?? $dados['data_abertura']
                        ?? null
                ]);


            echo json_encode([
                'success' =>
                true,

                'mensagem' =>
                'Ativo preparado para o saldo de abertura.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==============================================
        // IMPORTAR ENTRADA DE STOCK
        // ==============================================

        case 'importar_stock':

            $resultado =
                $controller
                ->importarStock([
                    'plano_contas_id' =>
                    $_REQUEST['plano_contas_id']
                        ?? $dados['plano_contas_id']
                        ?? null,

                    'movimento_id' =>
                    $_REQUEST['movimento_id']
                        ?? $dados['movimento_id']
                        ?? null
                ]);

            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Entrada de stock preparada para a abertura.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;

        // ==============================================
        // CONFIRMAR ABERTURA
        // ==============================================

        case 'confirmar':

            if (
                ($_SERVER['REQUEST_METHOD'] ?? '')
                !== 'POST'
            ) {

                http_response_code(405);

                echo json_encode([
                    'success' => false,

                    'mensagem' =>
                    'Método inválido para confirmar saldo de abertura.'
                ], JSON_UNESCAPED_UNICODE);

                break;
            }


            $id =
                filter_var(
                    $dados['id']
                        ?? null,
                    FILTER_VALIDATE_INT
                );


            if (
                $id === false
                || $id <= 0
            ) {

                throw new RuntimeException(
                    'Saldo de abertura inválido.'
                );
            }


            $resultado =
                $controller
                ->confirmar(
                    (int)$id
                );


            echo json_encode([
                'success' => true,

                'mensagem' =>
                'Saldo de abertura confirmado e contabilizado com sucesso.',

                'data' =>
                $resultado
            ], JSON_UNESCAPED_UNICODE);

            break;


        // ==============================================
        // AÇÃO INVÁLIDA
        // ==============================================

        default:

            http_response_code(400);

            echo json_encode([
                'success' => false,

                'mensagem' =>
                'Acção inválida para Saldos de Abertura.'
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
