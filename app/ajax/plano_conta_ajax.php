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
    . '/../models/PlanoConta.php';

require_once __DIR__
    . '/../controllers/PlanoContaController.php';

Auth::iniciarSessao();

function responderPlanoConta(
    bool $success,
    array $dados = [],
    int $codigo = 200
): void {

    http_response_code($codigo);

    echo json_encode(
        [
            'success' => $success
        ] + $dados,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}

if (!Auth::check()) {
    responderPlanoConta(
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
        new PlanoContaController($pdo);

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
        // STATUS DOS PLANOS
        // =====================================

        case 'status_planos':

            responderPlanoConta(
                true,
                [
                    'data' =>
                    $controller->statusPlanos()
                ]
            );

            break;

        // =====================================
        // IMPORTAR PGC-NIRF
        // =====================================

        case 'importar_pgc_nirf':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {

                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Método inválido.'
                    ],
                    405
                );
            }


            $planoId =
                (int)(
                    $_POST['plano_id']
                    ?? 0
                );


            if ($planoId <= 0) {

                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Seleccione o plano onde deseja importar o PGC-NIRF.'
                    ],
                    422
                );
            }


            $resultado =
                $controller
                ->importarPgcNirf(
                    $planoId
                );


            responderPlanoConta(
                true,
                [
                    'mensagem' =>
                    'PGC-NIRF importado com sucesso.',

                    'data' =>
                    $resultado
                ]
            );

            break;
        // =====================================
        // STATUS DAS CONTAS
        // =====================================

        case 'status_contas':

            responderPlanoConta(
                true,
                [
                    'data' =>
                    $controller->statusContas()
                ]
            );

            break;

        // =====================================
        // PLANOS
        // =====================================

        case 'listar_planos':

            responderPlanoConta(
                true,
                [
                    'data' =>
                    $controller->listarPlanos()
                ]
            );

            break;


        case 'buscar_plano':

            $id =
                (int)(
                    $_GET['id']
                    ?? 0
                );

            $plano =
                $controller->buscarPlano($id);

            if (!$plano) {
                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Plano de contas não encontrado.'
                    ],
                    404
                );
            }

            responderPlanoConta(
                true,
                [
                    'data' => $plano
                ]
            );

            break;


        case 'guardar_plano':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {
                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Método inválido.'
                    ],
                    405
                );
            }

            if (
                empty($_POST['status_id'])
            ) {
                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Seleccione o status do plano.'
                    ],
                    422
                );
            }

            $id =
                $controller->guardarPlano(
                    $_POST
                );

            responderPlanoConta(
                true,
                [
                    'mensagem' =>
                    !empty($_POST['id'])
                        ? 'Plano de contas atualizado com sucesso.'
                        : 'Plano de contas criado com sucesso.',

                    'data' => [
                        'id' => $id
                    ]
                ]
            );

            break;


        case 'eliminar_plano':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {
                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Método inválido.'
                    ],
                    405
                );
            }

            $resultado =
                $controller->eliminarPlano(
                    (int)(
                        $_POST['id']
                        ?? 0
                    )
                );

            responderPlanoConta(
                $resultado,
                [
                    'mensagem' =>
                    $resultado
                        ? 'Plano eliminado com sucesso.'
                        : 'Não foi possível eliminar o plano.'
                ]
            );

            break;


        // =====================================
        // CONTAS
        // =====================================

        case 'listar_contas':

            responderPlanoConta(
                true,
                [
                    'data' =>
                    $controller->listarContas(
                        (int)(
                            $_GET['plano_id']
                            ?? 0
                        )
                    )
                ]
            );

            break;


        case 'listar_arvore':

            responderPlanoConta(
                true,
                [
                    'data' =>
                    $controller->listarArvore(
                        (int)(
                            $_GET['plano_id']
                            ?? 0
                        )
                    )
                ]
            );

            break;


        case 'buscar_conta':

            $id =
                (int)(
                    $_GET['id']
                    ?? 0
                );

            $conta =
                $controller->buscarConta($id);

            if (!$conta) {
                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Conta contabilística não encontrada.'
                    ],
                    404
                );
            }

            responderPlanoConta(
                true,
                [
                    'data' => $conta
                ]
            );

            break;


        case 'guardar_conta':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {
                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Método inválido.'
                    ],
                    405
                );
            }

            if (
                empty($_POST['status_id'])
            ) {
                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Seleccione o status da conta contabilística.'
                    ],
                    422
                );
            }

            $id =
                $controller->guardarConta(
                    $_POST
                );

            responderPlanoConta(
                true,
                [
                    'mensagem' =>
                    !empty($_POST['id'])
                        ? 'Conta contabilística atualizada com sucesso.'
                        : 'Conta contabilística criada com sucesso.',

                    'data' => [
                        'id' => $id
                    ]
                ]
            );

            break;

        // =====================================
        // ALTERAR STATUS DA CONTA
        // =====================================

        case 'alterar_status_conta':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {
                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Método inválido.'
                    ],
                    405
                );
            }

            $resultado =
                $controller->alterarStatusConta(
                    (int)(
                        $_POST['id']
                        ?? 0
                    ),

                    (int)(
                        $_POST['status_id']
                        ?? 0
                    )
                );

            responderPlanoConta(
                $resultado,
                [
                    'mensagem' =>
                    $resultado
                        ? 'Status da conta atualizado com sucesso.'
                        : 'Não foi possível atualizar o status da conta.'
                ]
            );

            break;

        // =====================================
        // ALTERAR STATUS DO PLANO
        // =====================================

        case 'alterar_status_plano':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {
                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Método inválido.'
                    ],
                    405
                );
            }

            $resultado =
                $controller->alterarStatusPlano(
                    (int)(
                        $_POST['id']
                        ?? 0
                    ),

                    (int)(
                        $_POST['status_id']
                        ?? 0
                    )
                );

            responderPlanoConta(
                $resultado,
                [
                    'mensagem' =>
                    $resultado
                        ? 'Status do plano atualizado com sucesso.'
                        : 'Não foi possível atualizar o status do plano.'
                ]
            );

            break;


        case 'eliminar_conta':

            if (
                $_SERVER['REQUEST_METHOD']
                !== 'POST'
            ) {
                responderPlanoConta(
                    false,
                    [
                        'mensagem' =>
                        'Método inválido.'
                    ],
                    405
                );
            }

            $resultado =
                $controller->eliminarConta(
                    (int)(
                        $_POST['id']
                        ?? 0
                    )
                );

            responderPlanoConta(
                $resultado,
                [
                    'mensagem' =>
                    $resultado
                        ? 'Conta eliminada com sucesso.'
                        : 'Não foi possível eliminar a conta.'
                ]
            );

            break;


        case 'contas_select':

            responderPlanoConta(
                true,
                [
                    'data' =>
                    $controller->contasParaSelect(
                        (int)(
                            $_GET['plano_id']
                            ?? 0
                        ),
                        (int)(
                            $_GET['somente_analiticas']
                            ?? 0
                        ) === 1
                    )
                ]
            );

            break;


        default:

            responderPlanoConta(
                false,
                [
                    'mensagem' =>
                    'Acção inválida para o Plano de Contas.'
                ],
                400
            );
    }
} catch (Throwable $e) {
    responderPlanoConta(
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
