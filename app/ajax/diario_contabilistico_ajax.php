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
    . '/../models/DiarioContabilistico.php';

require_once __DIR__
    . '/../controllers/DiarioContabilisticoController.php';

Auth::iniciarSessao();


// ==================================================
// RESPOSTA JSON
// ==================================================

function responderDiario(
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
// VALIDAR SESSÃO
// ==================================================

if (!Auth::check()) {
    responderDiario(
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
        new DiarioContabilisticoController(
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

        case 'listar_diarios':

            responderDiario(
                true,
                [
                    'data' =>
                    $controller->listar()
                ]
            );

            break;


        // ==========================================
        // BUSCAR
        // ==========================================

        case 'buscar':

        case 'buscar_diario':

            $id =
                (int)(
                    $_GET['id']
                    ?? 0
                );

            $diario =
                $controller->buscar(
                    $id
                );

            if (!$diario) {
                responderDiario(
                    false,
                    [
                        'mensagem' =>
                        'Diário contabilístico não encontrado.'
                    ],
                    404
                );
            }

            responderDiario(
                true,
                [
                    'data' =>
                    $diario
                ]
            );

            break;


        // ==========================================
        // GUARDAR
        // ==========================================

        case 'guardar':

        case 'guardar_diario':

            validarMetodoPostDiario();

            if (
                empty($_POST['status_id'])
            ) {
                responderDiario(
                    false,
                    [
                        'mensagem' =>
                        'Seleccione o status do diário.'
                    ],
                    422
                );
            }

            $id =
                $controller->guardar(
                    $_POST
                );

            responderDiario(
                true,
                [
                    'mensagem' =>
                    !empty($_POST['id'])
                        ? 'Diário contabilístico atualizado com sucesso.'
                        : 'Diário contabilístico criado com sucesso.',

                    'data' => [
                        'id' => $id
                    ]
                ]
            );

            break;


        // ==========================================
        // ALTERAR STATUS
        // ==========================================

        case 'alterar_status':

        case 'alterar_status_diario':

            validarMetodoPostDiario();

            $resultado =
                $controller->alterarStatus(
                    (int)(
                        $_POST['id']
                        ?? 0
                    ),

                    (int)(
                        $_POST['status_id']
                        ?? 0
                    )
                );

            responderDiario(
                $resultado,
                [
                    'mensagem' =>
                    $resultado
                        ? 'Status do diário atualizado com sucesso.'
                        : 'Não foi possível atualizar o status do diário.'
                ],
                $resultado ? 200 : 422
            );

            break;


        // ==========================================
        // ELIMINAR
        // ==========================================

        case 'eliminar':

        case 'eliminar_diario':

            validarMetodoPostDiario();

            $resultado =
                $controller->eliminar(
                    (int)(
                        $_POST['id']
                        ?? 0
                    )
                );

            responderDiario(
                $resultado,
                [
                    'mensagem' =>
                    $resultado
                        ? 'Diário contabilístico eliminado com sucesso.'
                        : 'Não foi possível eliminar o diário contabilístico.'
                ],
                $resultado ? 200 : 422
            );

            break;


        // ==========================================
        // STATUS
        // ==========================================

        case 'status':

        case 'listar_status':

        case 'status_diarios':

            responderDiario(
                true,
                [
                    'data' =>
                    $controller->status()
                ]
            );

            break;


        // ==========================================
        // PRÓXIMO NÚMERO
        // ==========================================

        case 'proximo_numero':

            validarMetodoPostDiario();

            $numero =
                $controller->proximoNumero(
                    (int)(
                        $_POST['id']
                        ?? 0
                    )
                );

            responderDiario(
                true,
                [
                    'mensagem' =>
                    'Número reservado com sucesso.',

                    'data' => [
                        'numero' =>
                        $numero
                    ]
                ]
            );

            break;


        // ==========================================
        // AÇÃO INVÁLIDA
        // ==========================================

        default:

            responderDiario(
                false,
                [
                    'mensagem' =>
                    'Acção inválida para diários contabilísticos.'
                ],
                400
            );
    }
} catch (
    PDOException $e
) {
    responderDiario(
        false,
        [
            'mensagem' =>
            tratarErroBancoDiario(
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
    responderDiario(
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
// VALIDAR POST
// ==================================================

function validarMetodoPostDiario(): void
{
    if (
        ($_SERVER['REQUEST_METHOD'] ?? '')
        !== 'POST'
    ) {
        responderDiario(
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

function tratarErroBancoDiario(
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
            'uq_diario_empresa_codigo'
        )
    ) {
        return
            'Já existe um diário contabilístico com este código.';
    }

    if (
        $codigo === '23000'
        && str_contains(
            $mensagem,
            'fk_diario_status'
        )
    ) {
        return
            'O status selecionado para o diário é inválido.';
    }

    if (
        $codigo === '23000'
        && str_contains(
            $mensagem,
            'fk_lancamento_diario'
        )
    ) {
        return
            'O diário não pode ser eliminado porque possui lançamentos contabilísticos.';
    }

    return
        'Não foi possível concluir a operação no banco de dados.';
}
