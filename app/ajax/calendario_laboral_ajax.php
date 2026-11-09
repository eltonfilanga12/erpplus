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
    . '/../models/CalendarioLaboral.php';

require_once __DIR__
    . '/../controllers/CalendarioLaboralController.php';

Auth::iniciarSessao();

if (!Auth::check()) {

    http_response_code(401);

    responderJson([
        'success' => false,

        'mensagem' =>
        'Sessão expirada. Faça login novamente.'
    ]);

    exit;
}

try {

    $pdo =
        Database::conn();

    $controller =
        new CalendarioLaboralController(
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
        // LISTAR COM FILTROS
        // =====================================

        case 'listar':

            $filtros =
                $controller
                ->normalizarFiltros(
                    $_GET
                );

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->listar(
                        $filtros
                    )
            ]);

            break;


        // =====================================
        // BUSCAR
        // =====================================

        case 'buscar':

            $eventoId =
                (int)(
                    $_GET['id']
                    ?? $_GET['evento_id']
                    ?? 0
                );

            responderJson([
                'success' => true,

                'data' =>
                $controller
                    ->buscar(
                        $eventoId
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
                'Evento do calendário laboral criado com sucesso.',

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

            $eventoId =
                (int)(
                    $dados['id']
                    ?? $dados['evento_id']
                    ?? 0
                );

            $resultado =
                $controller
                ->editar(
                    $eventoId,
                    $dados
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Evento do calendário laboral atualizado com sucesso.',

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

            $eventoId =
                (int)(
                    $dados['id']
                    ?? $dados['evento_id']
                    ?? 0
                );

            $resultado =
                $controller
                ->eliminar(
                    $eventoId
                );

            responderJson([
                'success' => true,

                'mensagem' =>
                'Evento do calendário laboral eliminado com sucesso.',

                'data' =>
                $resultado
            ]);

            break;


        // =====================================
        // AÇÃO INVÁLIDA
        // =====================================

        default:

            http_response_code(400);

            responderJson([
                'success' => false,

                'mensagem' =>
                'Acção inválida para a gestão do calendário laboral.'
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
    $metodo =
        strtoupper(
            trim(
                (string)(
                    $_SERVER['REQUEST_METHOD']
                    ?? ''
                )
            )
        );

    if ($metodo !== 'POST') {

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
