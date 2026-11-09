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
    . '/../models/LancamentoContabilistico.php';

require_once __DIR__
    . '/../controllers/LancamentoContabilisticoController.php';

Auth::iniciarSessao();


// ==================================================
// RESPOSTA JSON
// ==================================================

function responderLancamento(
    bool $success,
    array $dados = [],
    int $codigoHttp = 200
): never {

    http_response_code(
        $codigoHttp
    );

    echo json_encode(
        [
            'success' => $success
        ] + $dados,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}


// ==================================================
// VALIDAR POST
// ==================================================

function validarMetodoPostLancamento(): void
{
    if (
        ($_SERVER['REQUEST_METHOD'] ?? '')
        !== 'POST'
    ) {
        responderLancamento(
            false,
            [
                'mensagem' =>
                'Método inválido para esta operação.'
            ],
            405
        );
    }
}


// ==================================================
// AUTENTICAÇÃO
// ==================================================

if (!Auth::check()) {
    responderLancamento(
        false,
        [
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
        new LancamentoContabilisticoController(
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

        // ==========================================
        // LISTAR
        // ==========================================

        case 'listar':

        case 'listar_lancamentos':

            $filtros = [
                'exercicio_id' =>
                (int)(
                    $_GET['exercicio_id']
                    ?? 0
                ),

                'periodo_id' =>
                (int)(
                    $_GET['periodo_id']
                    ?? 0
                ),

                'diario_id' =>
                (int)(
                    $_GET['diario_id']
                    ?? 0
                ),

                'status_id' =>
                (int)(
                    $_GET['status_id']
                    ?? 0
                ),

                'origem' =>
                trim(
                    (string)(
                        $_GET['origem']
                        ?? ''
                    )
                ),

                'data_inicio' =>
                trim(
                    (string)(
                        $_GET['data_inicio']
                        ?? ''
                    )
                ),

                'data_fim' =>
                trim(
                    (string)(
                        $_GET['data_fim']
                        ?? ''
                    )
                )
            ];

            responderLancamento(
                true,
                [
                    'data' =>
                    $controller->listar(
                        $filtros
                    )
                ]
            );

            break;


        // ==========================================
        // BUSCAR
        // ==========================================

        case 'buscar':

        case 'buscar_lancamento':

            $lancamento =
                $controller->buscar(
                    (int)(
                        $_GET['id']
                        ?? 0
                    )
                );

            if (!$lancamento) {
                responderLancamento(
                    false,
                    [
                        'mensagem' =>
                        'Lançamento contabilístico não encontrado.'
                    ],
                    404
                );
            }

            responderLancamento(
                true,
                [
                    'data' =>
                    $lancamento
                ]
            );

            break;


        // ==========================================
        // GUARDAR
        // ==========================================

        case 'guardar':

        case 'guardar_lancamento':

            validarMetodoPostLancamento();

            $dados =
                $_POST;

            if (
                isset($dados['itens'])
                && is_string(
                    $dados['itens']
                )
            ) {
                $itens =
                    json_decode(
                        $dados['itens'],
                        true
                    );

                if (
                    json_last_error()
                    !== JSON_ERROR_NONE
                ) {
                    responderLancamento(
                        false,
                        [
                            'mensagem' =>
                            'A lista dos itens contabilísticos é inválida.'
                        ],
                        422
                    );
                }

                $dados['itens'] =
                    is_array($itens)
                    ? $itens
                    : [];
            } else {
                $dados['itens'] = [];
            }

            $id =
                $controller->guardar(
                    $dados
                );

            responderLancamento(
                true,
                [
                    'mensagem' =>
                    !empty($_POST['id'])
                        ? 'Lançamento contabilístico atualizado com sucesso.'
                        : 'Lançamento contabilístico criado em rascunho.',

                    'data' => [
                        'id' => $id
                    ]
                ]
            );

            break;


        // ==========================================
        // CONTABILIZAR
        // ==========================================

        case 'contabilizar':

        case 'contabilizar_lancamento':

            validarMetodoPostLancamento();

            $resultado =
                $controller->contabilizar(
                    (int)(
                        $_POST['id']
                        ?? 0
                    )
                );

            responderLancamento(
                $resultado,
                [
                    'mensagem' =>
                    $resultado
                        ? 'Lançamento contabilizado com sucesso.'
                        : 'Não foi possível contabilizar o lançamento.'
                ],
                $resultado ? 200 : 422
            );

            break;


        // ==========================================
        // ANULAR
        // ==========================================

        case 'anular':
        case 'anular_lancamento':
        case 'estornar_lancamento':

            validarMetodoPostLancamento();

            $estornoId =
                $controller->anular(
                    (int)(
                        $_POST['id']
                        ?? 0
                    ),

                    (string)(
                        $_POST['motivo']
                        ?? ''
                    ),

                    (string)(
                        $_POST['data_estorno']
                        ?? ''
                    )
                );

            responderLancamento(
                true,
                [
                    'mensagem' =>
                    'Lançamento estornado com sucesso.',

                    'data' => [
                        'estorno_id' =>
                        $estornoId
                    ]
                ]
            );

            break;


        // ==========================================
        // ELIMINAR
        // ==========================================

        case 'eliminar':

        case 'eliminar_lancamento':

            validarMetodoPostLancamento();

            $resultado =
                $controller->eliminar(
                    (int)(
                        $_POST['id']
                        ?? 0
                    )
                );

            responderLancamento(
                $resultado,
                [
                    'mensagem' =>
                    $resultado
                        ? 'Lançamento contabilístico eliminado com sucesso.'
                        : 'Não foi possível eliminar o lançamento.'
                ],
                $resultado ? 200 : 422
            );

            break;


        // ==========================================
        // EXERCÍCIOS
        // ==========================================

        case 'exercicios':

        case 'listar_exercicios':

            responderLancamento(
                true,
                [
                    'data' =>
                    $controller->exercicios()
                ]
            );

            break;


        // ==========================================
        // PERÍODOS
        // ==========================================

        case 'periodos':

        case 'listar_periodos':

            responderLancamento(
                true,
                [
                    'data' =>
                    $controller->periodos(
                        (int)(
                            $_GET['exercicio_id']
                            ?? 0
                        )
                    )
                ]
            );

            break;


        // ==========================================
        // DIÁRIOS
        // ==========================================

        case 'diarios':

        case 'listar_diarios':

            responderLancamento(
                true,
                [
                    'data' =>
                    $controller->diarios()
                ]
            );

            break;


        // ==========================================
        // CONTAS
        // ==========================================

        case 'contas':

        case 'listar_contas':

            responderLancamento(
                true,
                [
                    'data' =>
                    $controller->contas()
                ]
            );

            break;


        // ==========================================
        // STATUS
        // ==========================================

        case 'status':

        case 'listar_status':

            responderLancamento(
                true,
                [
                    'data' =>
                    $controller->status()
                ]
            );

            break;


        // ==========================================
        // FILIAIS
        // ==========================================

        case 'filiais':

            responderLancamento(
                true,
                [
                    'data' =>
                    $controller->filiais()
                ]
            );

            break;


        // ==========================================
        // CENTROS DE CUSTO
        // ==========================================

        case 'centros_custo':

            responderLancamento(
                true,
                [
                    'data' =>
                    $controller->centrosCusto()
                ]
            );

            break;


        // ==========================================
        // CLIENTES
        // ==========================================

        case 'clientes':

            responderLancamento(
                true,
                [
                    'data' =>
                    $controller->clientes()
                ]
            );

            break;


        // ==========================================
        // FORNECEDORES
        // ==========================================

        case 'fornecedores':

            responderLancamento(
                true,
                [
                    'data' =>
                    $controller->fornecedores()
                ]
            );

            break;


        // ==========================================
        // AÇÃO INVÁLIDA
        // ==========================================

        default:

            responderLancamento(
                false,
                [
                    'mensagem' =>
                    'Acção inválida para lançamentos contabilísticos.'
                ],
                400
            );
    }
} catch (
    PDOException $e
) {
    responderLancamento(
        false,
        [
            'mensagem' =>
            tratarErroBancoLancamento(
                $e
            ),

            'erro_tecnico' =>
            $e->getMessage(),

            'linha' =>
            $e->getLine(),

            'arquivo' =>
            $e->getFile()
        ],
        500
    );
} catch (
    Throwable $e
) {
    responderLancamento(
        false,
        [
            'mensagem' =>
            $e->getMessage(),

            'linha' =>
            $e->getLine(),

            'arquivo' =>
            $e->getFile()
        ],
        500
    );
}


// ==================================================
// ERROS DO BANCO
// ==================================================

function tratarErroBancoLancamento(
    PDOException $e
): string {

    $codigo =
        (string)$e->getCode();

    $mensagem =
        $e->getMessage();

    if (
        $codigo === '23000'
        && str_contains(
            $mensagem,
            'uq_lancamento_diario_numero'
        )
    ) {
        return
            'Já existe um lançamento com este número no diário selecionado.';
    }

    if (
        $codigo === '23000'
        && str_contains(
            $mensagem,
            'uq_lancamento_origem'
        )
    ) {
        return
            'Esta operação já possui um lançamento contabilístico.';
    }

    if (
        $codigo === '23000'
        && str_contains(
            $mensagem,
            'fk_lancamento_status'
        )
    ) {
        return
            'O status do lançamento contabilístico é inválido.';
    }

    if (
        $codigo === '23000'
        && str_contains(
            $mensagem,
            'fk_item_conta'
        )
    ) {
        return
            'Uma das contas contabilísticas selecionadas é inválida.';
    }

    if (
        str_contains(
            $mensagem,
            'chk_item_debito_credito'
        )
    ) {
        return
            'Cada item deve possuir apenas débito ou apenas crédito.';
    }

    if (
        str_contains(
            $mensagem,
            'chk_item_valores_nao_negativos'
        )
    ) {
        return
            'Os valores de débito e crédito não podem ser negativos.';
    }

    return
        'Não foi possível concluir a operação no banco de dados.';
}
