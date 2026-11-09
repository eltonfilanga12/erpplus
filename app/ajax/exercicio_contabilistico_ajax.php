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
    . '/../models/ExercicioContabilistico.php';

require_once __DIR__
    . '/../controllers/ExercicioContabilisticoController.php';

Auth::iniciarSessao();


// ==================================================
// RESPOSTA JSON
// ==================================================

function responderExercicio(
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
// AUTENTICAÇÃO
// ==================================================

if (!Auth::check()) {
    responderExercicio(
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
        new ExercicioContabilisticoController(
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
        // STATUS DOS EXERCÍCIOS
        // ==========================================

        case 'status_exercicios':

            responderExercicio(
                true,
                [
                    'data' =>
                    $controller
                        ->statusExercicios()
                ]
            );

            break;


        // ==========================================
        // STATUS DOS PERÍODOS
        // ==========================================

        case 'status_periodos':

            responderExercicio(
                true,
                [
                    'data' =>
                    $controller
                        ->statusPeriodos()
                ]
            );

            break;


        // ==========================================
        // LISTAR EXERCÍCIOS
        // ==========================================

        case 'listar':

        case 'listar_exercicios':

            responderExercicio(
                true,
                [
                    'data' =>
                    $controller->listar()
                ]
            );

            break;


        // ==========================================
        // BUSCAR EXERCÍCIO
        // ==========================================

        case 'buscar':

        case 'buscar_exercicio':

            $id =
                (int)(
                    $_GET['id']
                    ?? 0
                );

            $exercicio =
                $controller->buscar(
                    $id
                );

            if (!$exercicio) {
                responderExercicio(
                    false,
                    [
                        'mensagem' =>
                        'Exercício contabilístico não encontrado.'
                    ],
                    404
                );
            }

            responderExercicio(
                true,
                [
                    'data' =>
                    $exercicio
                ]
            );

            break;


        // ==========================================
        // GUARDAR EXERCÍCIO
        // ==========================================

        case 'guardar':

        case 'guardar_exercicio':

            validarMetodoPost();

            $id =
                $controller->guardar(
                    $_POST
                );

            responderExercicio(
                true,
                [
                    'mensagem' =>
                    !empty($_POST['id'])
                        ? 'Exercício contabilístico atualizado com sucesso.'
                        : 'Exercício contabilístico criado com sucesso.',

                    'data' => [
                        'id' => $id
                    ]
                ]
            );

            break;


        // ==========================================
        // ELIMINAR EXERCÍCIO
        // ==========================================

        case 'eliminar':

        case 'eliminar_exercicio':

            validarMetodoPost();

            $resultado =
                $controller->eliminar(
                    (int)(
                        $_POST['id']
                        ?? 0
                    )
                );

            responderExercicio(
                $resultado,
                [
                    'mensagem' =>
                    $resultado
                        ? 'Exercício contabilístico eliminado com sucesso.'
                        : 'Não foi possível eliminar o exercício contabilístico.'
                ],
                $resultado ? 200 : 422
            );

            break;


        // ==========================================
        // GERAR PERÍODOS
        // ==========================================

        case 'gerar_periodos':

            validarMetodoPost();

            $quantidade =
                $controller->gerarPeriodos(
                    (int)(
                        $_POST['exercicio_id']
                        ?? 0
                    )
                );

            responderExercicio(
                true,
                [
                    'mensagem' =>
                    $quantidade > 0
                        ? "{$quantidade} período(s) gerado(s) com sucesso."
                        : 'Os períodos deste exercício já estavam gerados.',

                    'data' => [
                        'quantidade' =>
                        $quantidade
                    ]
                ]
            );

            break;


        // ==========================================
        // ALTERAR STATUS DO EXERCÍCIO
        // ==========================================

        case 'alterar_status':

        case 'alterar_status_exercicio':

            validarMetodoPost();

            $resultado =
                $controller->alterarStatus(
                    (int)(
                        $_POST['id']
                        ?? 0
                    ),
                    (string)(
                        $_POST['status']
                        ?? ''
                    )
                );

            responderExercicio(
                $resultado,
                [
                    'mensagem' =>
                    $resultado
                        ? 'Status do exercício atualizado com sucesso.'
                        : 'Não foi possível atualizar o status do exercício.'
                ],
                $resultado ? 200 : 422
            );

            break;


        // ==========================================
        // LISTAR PERÍODOS
        // ==========================================

        case 'listar_periodos':

            $exercicioId =
                (int)(
                    $_GET['exercicio_id']
                    ?? 0
                );

            responderExercicio(
                true,
                [
                    'data' =>
                    $controller
                        ->listarPeriodos(
                            $exercicioId
                        )
                ]
            );

            break;


        // ==========================================
        // BUSCAR PERÍODO
        // ==========================================

        case 'buscar_periodo':

            $id =
                (int)(
                    $_GET['id']
                    ?? 0
                );

            $periodo =
                $controller->buscarPeriodo(
                    $id
                );

            if (!$periodo) {
                responderExercicio(
                    false,
                    [
                        'mensagem' =>
                        'Período contabilístico não encontrado.'
                    ],
                    404
                );
            }

            responderExercicio(
                true,
                [
                    'data' =>
                    $periodo
                ]
            );

            break;


        // ==========================================
        // ALTERAR STATUS DO PERÍODO
        // ==========================================

        case 'alterar_status_periodo':

            validarMetodoPost();

            $resultado =
                $controller
                ->alterarStatusPeriodo(
                    (int)(
                        $_POST['id']
                        ?? 0
                    ),
                    (string)(
                        $_POST['status']
                        ?? ''
                    )
                );

            responderExercicio(
                $resultado,
                [
                    'mensagem' =>
                    $resultado
                        ? 'Status do período atualizado com sucesso.'
                        : 'Não foi possível atualizar o status do período.'
                ],
                $resultado ? 200 : 422
            );

            break;


        // ==========================================
        // AÇÃO INVÁLIDA
        // ==========================================

        default:

            responderExercicio(
                false,
                [
                    'mensagem' =>
                    'Acção inválida para exercícios contabilísticos.'
                ],
                400
            );
    }
} catch (
    PDOException $e
) {
    responderExercicio(
        false,
        [
            'mensagem' =>
            tratarErroBancoExercicio(
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
    responderExercicio(
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
// VALIDAR MÉTODO POST
// ==================================================

function validarMetodoPost(): void
{
    if (
        ($_SERVER['REQUEST_METHOD'] ?? '')
        !== 'POST'
    ) {
        responderExercicio(
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
// TRATAR ERROS DO BANCO
// ==================================================

function tratarErroBancoExercicio(
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
            'uq_exercicio_empresa_codigo'
        )
    ) {
        return
            'Já existe um exercício com este código.';
    }

    if (
        $codigo === '23000'
        && str_contains(
            $mensagem,
            'uq_periodo_exercicio_numero'
        )
    ) {
        return
            'Este período já existe no exercício.';
    }

    if (
        $codigo === '23000'
        && str_contains(
            $mensagem,
            'fk_exercicio_status'
        )
    ) {
        return
            'O status selecionado para o exercício é inválido.';
    }

    if (
        $codigo === '23000'
        && str_contains(
            $mensagem,
            'fk_periodo_status'
        )
    ) {
        return
            'O status selecionado para o período é inválido.';
    }

    return
        'Não foi possível concluir a operação no banco de dados.';
}
