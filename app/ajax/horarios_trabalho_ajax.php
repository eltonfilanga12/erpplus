<?php

declare(strict_types=1);

header(
    'Content-Type: application/json; charset=utf-8'
);

error_reporting(E_ALL);
ini_set('display_errors', '0');


require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/HorarioTrabalho.php';

require_once __DIR__
    . '/../controllers/HorarioTrabalhoController.php';

Auth::iniciarSessao();

if (!Auth::check()) {

    http_response_code(401);

    echo json_encode(
        [
            'success' => false,

            'mensagem' =>
            'Sessão expirada. Faça login novamente.'
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

try {

    $pdo =
        Database::conn();

    $controller =
        new HorarioTrabalhoController(
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

            $horarioId =
                (int)(
                    $_GET['id']
                    ?? $_GET['horario_id']
                    ?? 0
                );

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->buscar(
                        $horarioId
                    )
            ]);

            break;


        // =====================================
        // CRIAR
        // =====================================

        case 'criar':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $resultado =
                $controller
                ->criar(
                    $dados
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Horário de trabalho criado com sucesso.',

                'data' =>
                $resultado
            ]);

            break;


        // =====================================
        // EDITAR
        // =====================================

        case 'editar':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $horarioId =
                (int)(
                    $dados['id']
                    ?? $dados['horario_id']
                    ?? 0
                );

            $resultado =
                $controller
                ->editar(
                    $horarioId,
                    $dados
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Horário de trabalho atualizado com sucesso.',

                'data' =>
                $resultado
            ]);

            break;


        // =====================================
        // ELIMINAR
        // =====================================

        case 'eliminar':

            validarMetodoPost();

            $dados =
                obterDadosRequisicao();

            $horarioId =
                (int)(
                    $dados['id']
                    ?? $dados['horario_id']
                    ?? 0
                );

            $resultado =
                $controller
                ->eliminar(
                    $horarioId
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Horário de trabalho eliminado com sucesso.',

                'data' =>
                $resultado
            ]);

            break;

        // Adicionar dentro do switch($acao):
        case 'validar_importacao':
            validarMetodoPost();
            $resposta = $controller->validarImportacaoExcel();
            responderJson($resposta);
            break;

        case 'confirmar_importacao':
            validarMetodoPost();
            $resposta = $controller->confirmarImportacaoExcel();
            responderJson($resposta);
            break;

        case 'baixar_modelo_excel':
            $controller->baixarModeloImportacaoExcel();
            break;

        // =====================================
        // AÇÃO INVÁLIDA
        // =====================================

        default:

            http_response_code(400);

            responderJson([
                'success' => false,

                'mensagem' =>
                'Acção inválida para a gestão dos horários de trabalho.'
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
        'Ocorreu um erro ao aceder à base de dados.',

        'detalhe' =>
        $e->getMessage(),

        'linha' =>
        $e->getLine(),

        'arquivo' =>
        $e->getFile()
    ]);
} catch (Throwable $e) {

    http_response_code(500);

    responderJson([
        'success' => false,

        'mensagem' =>
        $e->getMessage(),

        'linha' =>
        $e->getLine(),

        'arquivo' =>
        $e->getFile()
    ]);
}


// ==================================================
// RESPONDER JSON
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


// ==================================================
// VALIDAR MÉTODO POST
// ==================================================

function validarMetodoPost(): void
{
    if (
        strtoupper(
            (string)(
                $_SERVER['REQUEST_METHOD']
                ?? ''
            )
        )
        !== 'POST'
    ) {
        http_response_code(405);

        throw new RuntimeException(
            'Método inválido para esta operação.'
        );
    }
}


// ==================================================
// OBTER DADOS DA REQUISIÇÃO
// ==================================================

function obterDadosRequisicao(): array
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
            || trim($conteudo) === ''
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

        return is_array($dados)
            ? $dados
            : [];
    }

    return is_array($_POST)
        ? $_POST
        : [];
}
