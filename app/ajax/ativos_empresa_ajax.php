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
    . '/../models/AtivoEmpresa.php';

require_once __DIR__
    . '/../controllers/AtivoEmpresaController.php';

Auth::iniciarSessao();


// ==================================================
// RESPONDER JSON
// ==================================================

function responderAtivoJson(
    bool $success,
    string $mensagem = '',
    mixed $dados = null,
    int $statusHttp = 200
): never {

    http_response_code(
        $statusHttp
    );

    $resposta = [
        'success' =>
        $success
    ];

    if ($mensagem !== '') {

        $resposta['mensagem'] =
            $mensagem;
    }

    if ($dados !== null) {

        $resposta['data'] =
            $dados;
    }

    echo json_encode(
        $resposta,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}


// ==================================================
// AUTENTICAÇÃO
// ==================================================

if (!Auth::check()) {

    responderAtivoJson(
        false,
        'Sessão expirada. Faça login novamente.',
        null,
        401
    );
}


// ==================================================
// MÉTODO
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

    responderAtivoJson(
        false,
        'Método HTTP não permitido.',
        null,
        405
    );
}


// ==================================================
// DADOS DA REQUISIÇÃO
// ==================================================

function obterDadosAtivo(): array
{
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


        if (
            $conteudo === false
            || trim(
                $conteudo
            ) === ''
        ) {

            return [];
        }


        $dados =
            json_decode(
                $conteudo,
                true
            );


        if (
            json_last_error()
            !== JSON_ERROR_NONE
        ) {

            throw new RuntimeException(
                'Os dados JSON enviados são inválidos.'
            );
        }


        return is_array(
            $dados
        )
            ? $dados
            : [];
    }


    return is_array(
        $_POST
    )
        ? $_POST
        : [];
}


// ==================================================
// PROCESSAMENTO
// ==================================================

try {

    $pdo =
        Database::conn();


    $controller =
        new AtivoEmpresaController(
            $pdo
        );


    $dados =
        $metodo === 'POST'
        ? obterDadosAtivo()
        : $_GET;


    $acao =
        strtolower(
            trim(
                (string)(
                    $dados['acao']
                    ?? $_GET['acao']
                    ?? ''
                )
            )
        );


    if ($acao === '') {

        responderAtivoJson(
            false,
            'Nenhuma ação foi informada.',
            null,
            400
        );
    }


    switch ($acao) {

        // =====================================
        // DADOS INICIAIS
        // =====================================

        case 'dados_iniciais':

            responderAtivoJson(
                true,
                '',
                $controller
                    ->dadosIniciais()
            );


            // =====================================
            // LISTAR
            // =====================================

        case 'listar_depreciacoes':

            responderAtivoJson(
                true,
                '',
                $controller
                    ->listarDepreciacoes(
                        $dados
                    )
            );


        case 'processar_depreciacao':

            if ($metodo !== 'POST') {

                responderAtivoJson(
                    false,
                    'Método inválido para processar a depreciação/amortização.',
                    null,
                    405
                );
            }


            responderAtivoJson(
                true,
                'Depreciação/Amortização processada com sucesso.',
                $controller
                    ->processarDepreciacao(
                        $dados
                    )
            );


        case 'processar_depreciacoes_lote':

            if ($metodo !== 'POST') {

                responderAtivoJson(
                    false,
                    'Método inválido para processar o lote.',
                    null,
                    405
                );
            }


            responderAtivoJson(
                true,
                'Processamento concluído.',
                $controller
                    ->processarDepreciacoesLote(
                        $dados
                    )
            );

        case 'configuracoes_contabeis':

            responderAtivoJson(
                true,
                '',
                $controller
                    ->configuracoesContabeis()
            );


        case 'guardar_configuracao_contabil':

            if ($metodo !== 'POST') {

                responderAtivoJson(
                    false,
                    'Método inválido.',
                    null,
                    405
                );
            }


            responderAtivoJson(
                true,
                'Configuração contabilística guardada com sucesso.',
                $controller
                    ->guardarConfiguracaoContabil(
                        $dados
                    )
            );

        case 'estornar_depreciacao':

            if ($metodo !== 'POST') {

                responderAtivoJson(
                    false,
                    'Método inválido para estornar.',
                    null,
                    405
                );
            }


            responderAtivoJson(
                true,
                'Depreciação/Amortização estornada com sucesso.',
                $controller
                    ->estornarDepreciacao(
                        $dados
                    )
            );

        case 'ativos_movimentacao':

            responderAtivoJson(
                true,
                '',
                $controller
                    ->ativosParaMovimentacao()
            );


        case 'listar_movimentacoes':

            responderAtivoJson(
                true,
                '',
                $controller
                    ->listarMovimentacoes(
                        $dados
                    )
            );


        case 'criar_movimentacao':

            if ($metodo !== 'POST') {

                responderAtivoJson(
                    false,
                    'Método inválido para registar a movimentação.',
                    null,
                    405
                );
            }


            responderAtivoJson(
                true,
                'Movimentação registada com sucesso.',
                $controller
                    ->criarMovimentacao(
                        $dados
                    )
            );

        case 'listar_manutencoes':

            responderAtivoJson(
                true,
                '',
                $controller
                    ->listarManutencoes(
                        $dados
                    )
            );


        case 'buscar_manutencao':

            $id =
                (int)(
                    $_GET['id']
                    ?? $_POST['id']
                    ?? 0
                );


            $manutencao =
                $controller
                ->buscarManutencao(
                    $id
                );


            if (!$manutencao) {

                responderAtivoJson(
                    false,
                    'Manutenção não encontrada.',
                    null,
                    404
                );
            }


            responderAtivoJson(
                true,
                '',
                $manutencao
            );


        case 'guardar_manutencao':

            if ($metodo !== 'POST') {

                responderAtivoJson(
                    false,
                    'Método inválido para guardar a manutenção.',
                    null,
                    405
                );
            }


            responderAtivoJson(
                true,
                'Manutenção guardada com sucesso.',
                $controller
                    ->guardarManutencao(
                        $dados
                    )
            );

        case 'listar_baixas':

            responderAtivoJson(
                true,
                '',
                $controller
                    ->listarBaixas(
                        $dados
                    )
            );


        case 'buscar_baixa':

            $id =
                (int)(
                    $_GET['id']
                    ?? $_POST['id']
                    ?? 0
                );


            $baixa =
                $controller
                ->buscarBaixa(
                    $id
                );


            if (!$baixa) {

                responderAtivoJson(
                    false,
                    'Baixa/Alienação não encontrada.',
                    null,
                    404
                );
            }


            responderAtivoJson(
                true,
                '',
                $baixa
            );


        case 'criar_baixa':

            if ($metodo !== 'POST') {

                responderAtivoJson(
                    false,
                    'Método inválido para registar a baixa.',
                    null,
                    405
                );
            }


            responderAtivoJson(
                true,
                'Baixa/Alienação registada com sucesso.',
                $controller
                    ->criarBaixa(
                        $dados
                    )
            );

        case 'dados_ativo_baixa':

            $ativoId =
                (int)(
                    $_GET['ativo_id']
                    ?? $_POST['ativo_id']
                    ?? 0
                );


            responderAtivoJson(
                true,
                '',
                $controller
                    ->dadosAtivoParaBaixa(
                        $ativoId
                    )
            );

        case 'configuracao_baixas':

            responderAtivoJson(
                true,
                '',
                $controller
                    ->configuracaoBaixas()
            );


        case 'guardar_configuracao_baixas':

            if ($metodo !== 'POST') {

                responderAtivoJson(
                    false,
                    'Método inválido.',
                    null,
                    405
                );
            }


            $controller
                ->guardarConfiguracaoBaixas(
                    $dados
                );


            responderAtivoJson(
                true,
                'Configuração das baixas guardada com sucesso.'
            );

        case 'listar':

            responderAtivoJson(
                true,
                '',
                $controller
                    ->listar(
                        $dados
                    )
            );


            // =====================================
            // BUSCAR
            // =====================================

        case 'buscar':
        case 'obter':

            $ativoId =
                (int)(
                    $dados['id']
                    ?? $dados['ativo_id']
                    ?? 0
                );


            responderAtivoJson(
                true,
                '',
                $controller
                    ->buscar(
                        $ativoId
                    )
            );


            // =====================================
            // PRÓXIMO CÓDIGO
            // =====================================

        case 'proximo_codigo':

            responderAtivoJson(
                true,
                '',
                $controller
                    ->proximoCodigo()
            );


            // =====================================
            // CRIAR
            // =====================================

        case 'criar':

            if ($metodo !== 'POST') {

                responderAtivoJson(
                    false,
                    'Método inválido para criar o ativo.',
                    null,
                    405
                );
            }


            $resultado =
                $controller
                ->criar(
                    $dados
                );


            responderAtivoJson(
                true,
                'Ativo criado com sucesso.',
                $resultado,
                201
            );


            // =====================================
            // EDITAR
            // =====================================

        case 'editar':
        case 'atualizar':
        case 'actualizar':

            if ($metodo !== 'POST') {

                responderAtivoJson(
                    false,
                    'Método inválido para atualizar o ativo.',
                    null,
                    405
                );
            }


            $ativoId =
                (int)(
                    $dados['id']
                    ?? $dados['ativo_id']
                    ?? 0
                );


            $resultado =
                $controller
                ->editar(
                    $ativoId,
                    $dados
                );


            responderAtivoJson(
                true,
                'Ativo atualizado com sucesso.',
                $resultado
            );


            // =====================================
            // AÇÃO INVÁLIDA
            // =====================================

        default:

            responderAtivoJson(
                false,
                'Ação inválida para Ativos da Empresa.',
                null,
                400
            );
    }
} catch (
    RuntimeException $e
) {

    responderAtivoJson(
        false,
        $e->getMessage(),
        null,
        422
    );
} catch (
    PDOException $e
) {

    error_log(
        '[Ativos Empresa][PDO] '
            . $e->getMessage()
    );


    responderAtivoJson(
        false,
        'Ocorreu um erro ao aceder à base de dados.',
        null,
        500
    );
} catch (
    Throwable $e
) {

    error_log(
        sprintf(
            '[Ativos Empresa] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );


    responderAtivoJson(
        false,
        $e->getMessage(),
        null,
        500
    );
}
