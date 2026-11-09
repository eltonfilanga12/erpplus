<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/RubricaSalarial.php';

require_once __DIR__
    . '/../controllers/RubricaSalarialController.php';

function responderJson(
    bool $sucesso,
    string $mensagem = '',
    mixed $dados = null,
    int $statusHttp = 200
): never {
    http_response_code($statusHttp);

    $resposta = [
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ];

    if ($dados !== null) {
        $resposta['dados'] = $dados;
    }

    echo json_encode(
        $resposta,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function obterDadosRequisicao(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (
        str_contains(
            strtolower($contentType),
            'application/json'
        )
    ) {
        $conteudo = file_get_contents('php://input');
        $dados = json_decode($conteudo ?: '{}', true);

        if (!is_array($dados)) {
            throw new InvalidArgumentException(
                'O corpo JSON enviado é inválido.'
            );
        }

        return $dados;
    }

    return $_POST;
}

try {
    /*
     * Ajuste estes nomes conforme as variáveis reais
     * utilizadas pela autenticação do ERP.
     */
    $empresaId = isset($_SESSION['empresa_id'])
        ? (int) $_SESSION['empresa_id']
        : 0;

    $usuarioId = isset($_SESSION['usuario_id'])
        ? (int) $_SESSION['usuario_id']
        : null;

    if ($empresaId <= 0) {
        responderJson(
            false,
            'Empresa não identificada na sessão.',
            null,
            401
        );
    }

    $dados = obterDadosRequisicao();

    $acao = strtolower(
        trim(
            (string) (
                $dados['acao']
                ?? $_GET['acao']
                ?? ''
            )
        )
    );

    if ($acao === '') {
        responderJson(
            false,
            'Nenhuma ação foi informada.',
            null,
            400
        );
    }

    $controller = new RubricaSalarialController();

    switch ($acao) {
        case 'listar':
            $filtros = [
                'codigo' => $dados['codigo']
                    ?? $_GET['codigo']
                    ?? '',

                'nome' => $dados['nome']
                    ?? $_GET['nome']
                    ?? '',

                'tipo' => $dados['tipo']
                    ?? $_GET['tipo']
                    ?? '',

                'categoria' => $dados['categoria']
                    ?? $_GET['categoria']
                    ?? '',

                'modo_calculo' => $dados['modo_calculo']
                    ?? $_GET['modo_calculo']
                    ?? '',

                'status_id' => $dados['status_id']
                    ?? $_GET['status_id']
                    ?? '',
            ];

            $resposta = $controller->listar(
                $empresaId,
                $filtros
            );

            responderJson(
                true,
                '',
                $resposta['dados']
            );

        case 'listar_ativas':
            $tipo = trim(
                (string) (
                    $dados['tipo']
                    ?? $_GET['tipo']
                    ?? ''
                )
            );

            $resposta = $controller->listarAtivas(
                $empresaId,
                $tipo !== '' ? $tipo : null
            );

            responderJson(
                true,
                '',
                $resposta['dados']
            );

        case 'buscar':
            $resposta = $controller->buscar(
                $empresaId,
                [
                    'id' => $dados['id']
                        ?? $_GET['id']
                        ?? 0,

                    'uuid' => $dados['uuid']
                        ?? $_GET['uuid']
                        ?? '',
                ]
            );

            responderJson(
                true,
                '',
                $resposta['dados']
            );

        case 'criar':
            $resposta = $controller->criar(
                $empresaId,
                $usuarioId,
                $dados
            );

            responderJson(
                true,
                $resposta['mensagem'],
                $resposta['dados'],
                201
            );

        case 'atualizar':
        case 'actualizar':
            $resposta = $controller->atualizar(
                $empresaId,
                $usuarioId,
                $dados
            );

            responderJson(
                true,
                $resposta['mensagem'],
                $resposta['dados']
            );

        case 'ativar':
        case 'activar':
            $resposta = $controller->ativar(
                $empresaId,
                $usuarioId,
                $dados
            );

            responderJson(
                true,
                $resposta['mensagem'],
                $resposta['dados']
            );

        case 'inativar':
        case 'inactivar':
            $resposta = $controller->inativar(
                $empresaId,
                $usuarioId,
                $dados
            );

            responderJson(
                true,
                $resposta['mensagem'],
                $resposta['dados']
            );

        case 'listar_estados':
            $resposta = $controller->listarEstados();

            responderJson(
                true,
                '',
                $resposta['dados']
            );

        case 'resumo':
            $resposta = $controller->resumo(
                $empresaId
            );

            responderJson(
                true,
                '',
                $resposta['dados']
            );

        case 'calcular':
            $resposta = $controller->calcular(
                $empresaId,
                $dados
            );

            responderJson(
                true,
                '',
                $resposta['dados']
            );

        default:
            responderJson(
                false,
                'A ação informada não é válida.',
                null,
                400
            );
    }
} catch (InvalidArgumentException $erro) {
    responderJson(
        false,
        $erro->getMessage(),
        null,
        422
    );
} catch (RuntimeException $erro) {
    responderJson(
        false,
        $erro->getMessage(),
        null,
        400
    );
} catch (PDOException $erro) {
    error_log(
        '[Rubricas Salariais][PDO] '
            . $erro->getMessage()
    );

    responderJson(
        false,
        'Ocorreu um erro ao aceder à base de dados.',
        null,
        500
    );
} catch (Throwable $erro) {
    error_log(
        '[Rubricas Salariais][Geral] '
            . $erro->getMessage()
            . ' em '
            . $erro->getFile()
            . ':'
            . $erro->getLine()
    );

    responderJson(
        false,
        'Ocorreu um erro interno no servidor.',
        null,
        500
    );
}
