<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Resposta HTTP
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

/*
|--------------------------------------------------------------------------
| Dependências
|--------------------------------------------------------------------------
*/


require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/PeriodoSalarial.php';

require_once __DIR__
    . '/../controllers/PeriodoSalarialController.php';

/*
|--------------------------------------------------------------------------
| Sessão
|--------------------------------------------------------------------------
*/

if (method_exists(Auth::class, 'iniciarSessao')) {
    Auth::iniciarSessao();
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Funções auxiliares
|--------------------------------------------------------------------------
*/

function responderJson(
    bool $sucesso,
    string $mensagem = '',
    mixed $dados = null,
    int $codigoHttp = 200,
    array $extras = []
): never {
    http_response_code($codigoHttp);

    $resposta = [
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ];

    if ($dados !== null) {
        $resposta['dados'] = $dados;
    }

    if ($extras !== []) {
        $resposta = array_merge($resposta, $extras);
    }

    echo json_encode(
        $resposta,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

function obterDadosRequisicao(): array
{
    $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');

    if (stripos($contentType, 'application/json') !== false) {
        $conteudo = file_get_contents('php://input');

        if ($conteudo === false || trim($conteudo) === '') {
            return [];
        }

        $dados = json_decode($conteudo, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            responderJson(
                false,
                'O conteúdo JSON enviado é inválido.',
                null,
                400
            );
        }

        return is_array($dados) ? $dados : [];
    }

    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
        return $_POST;
    }

    return $_GET;
}

function obterId(array $dados, string $campo = 'id'): int
{
    $id = filter_var(
        $dados[$campo] ?? null,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
            ],
        ]
    );

    if ($id === false) {
        responderJson(
            false,
            'O identificador informado é inválido.',
            null,
            422,
            [
                'erros' => [
                    $campo => 'Informe um identificador válido.',
                ],
            ]
        );
    }

    return (int) $id;
}

function prepararDadosPeriodo(array $dados): array
{
    $camposPermitidos = [
        'codigo',
        'descricao',
        'ano',
        'mes',
        'data_inicio',
        'data_fim',
        'data_limite_assiduidade',
        'data_prevista_pagamento',
        'dias_uteis',
        'observacoes',
    ];

    return array_intersect_key(
        $dados,
        array_flip($camposPermitidos)
    );
}

function obterEmpresaIdSessao(): int
{
    if (method_exists(Auth::class, 'empresaId')) {
        return (int) (Auth::empresaId() ?? 0);
    }

    return (int) (
        $_SESSION['empresa_id']
        ?? $_SESSION['usuario']['empresa_id']
        ?? 0
    );
}

function obterUsuarioIdSessao(): int
{
    if (method_exists(Auth::class, 'usuarioId')) {
        return (int) (Auth::usuarioId() ?? 0);
    }

    return (int) (
        $_SESSION['usuario_id']
        ?? $_SESSION['usuario']['id']
        ?? 0
    );
}

function validarCsrf(array $dados): void
{
    $tokenSessao = (string) ($_SESSION['csrf_token'] ?? '');

    $tokenRecebido = (string) (
        $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $dados['csrf_token']
        ?? ''
    );

    if (
        $tokenSessao === ''
        || $tokenRecebido === ''
        || !hash_equals($tokenSessao, $tokenRecebido)
    ) {
        responderJson(
            false,
            'A validação de segurança falhou. Atualize a página e tente novamente.',
            null,
            419,
            [
                'csrf_invalido' => true,
            ]
        );
    }
}

function codigoHttpResultado(array $resultado, int $sucesso = 200): int
{
    if (($resultado['sucesso'] ?? false) === true) {
        return $sucesso;
    }

    if (isset($resultado['erros']) && is_array($resultado['erros'])) {
        return 422;
    }

    return 400;
}

/*
|--------------------------------------------------------------------------
| Método HTTP
|--------------------------------------------------------------------------
*/

$metodo = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if (!in_array($metodo, ['GET', 'POST'], true)) {
    responderJson(
        false,
        'Método HTTP não permitido.',
        null,
        405
    );
}

/*
|--------------------------------------------------------------------------
| Autenticação
|--------------------------------------------------------------------------
*/

$usuarioId = obterUsuarioIdSessao();
$empresaId = obterEmpresaIdSessao();

if ($usuarioId <= 0) {
    responderJson(
        false,
        'A sessão do utilizador expirou. Faça login novamente.',
        null,
        401,
        [
            'sessao_expirada' => true,
        ]
    );
}

if ($empresaId <= 0) {
    responderJson(
        false,
        'Não foi possível identificar a empresa da sessão.',
        null,
        403
    );
}

/*
|--------------------------------------------------------------------------
| Requisição
|--------------------------------------------------------------------------
*/

$dadosRequisicao = obterDadosRequisicao();

$acao = trim((string) (
    $dadosRequisicao['acao']
    ?? $_GET['acao']
    ?? ''
));

if ($acao === '') {
    responderJson(
        false,
        'Nenhuma ação foi informada.',
        null,
        400
    );
}

if ($metodo === 'POST') {
    validarCsrf($dadosRequisicao);
}

/*
|--------------------------------------------------------------------------
| Inicialização
|--------------------------------------------------------------------------
*/

try {
    $db = Database::conn();

    if (!$db instanceof PDO) {
        throw new RuntimeException(
            'A ligação com a base de dados não devolveu uma instância PDO.'
        );
    }

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $controller = new PeriodoSalarialController($db);
} catch (Throwable $e) {
    error_log(sprintf(
        '[PeriodoSalarial AJAX][Inicialização] %s em %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    responderJson(
        false,
        'Não foi possível inicializar o módulo de períodos salariais.',
        null,
        500
    );
}

/*
|--------------------------------------------------------------------------
| Processamento das ações
|--------------------------------------------------------------------------
*/

try {
    switch ($acao) {
        case 'listar':
            $filtros = [
                'ano' => isset($dadosRequisicao['ano'])
                    && $dadosRequisicao['ano'] !== ''
                    ? (int) $dadosRequisicao['ano']
                    : null,

                'mes' => isset($dadosRequisicao['mes'])
                    && $dadosRequisicao['mes'] !== ''
                    ? (int) $dadosRequisicao['mes']
                    : null,

                'status_id' => isset($dadosRequisicao['status_id'])
                    && $dadosRequisicao['status_id'] !== ''
                    ? (int) $dadosRequisicao['status_id']
                    : null,
            ];

            $periodos = $controller->listar($filtros);

            responderJson(
                true,
                'Períodos salariais carregados com sucesso.',
                $periodos,
                200,
                [
                    'data' => $periodos,
                ]
            );

        case 'buscar':
            $id = obterId($dadosRequisicao);
            $periodo = $controller->buscar($id);

            if ($periodo === null) {
                responderJson(
                    false,
                    'Período salarial não encontrado.',
                    null,
                    404
                );
            }

            responderJson(
                true,
                'Período salarial carregado com sucesso.',
                $periodo
            );

        case 'criar':
            if ($metodo !== 'POST') {
                responderJson(false, 'A criação deve ser enviada através de POST.', null, 405);
            }

            $resultado = $controller->criar(
                prepararDadosPeriodo($dadosRequisicao)
            );

            responderJson(
                (bool) ($resultado['sucesso'] ?? false),
                (string) ($resultado['mensagem'] ?? 'Operação concluída.'),
                $resultado['dados'] ?? null,
                codigoHttpResultado($resultado, 201),
                array_filter([
                    'erros' => $resultado['erros'] ?? null,
                ])
            );

        case 'atualizar':
        case 'editar':
            if ($metodo !== 'POST') {
                responderJson(false, 'A atualização deve ser enviada através de POST.', null, 405);
            }

            $id = obterId($dadosRequisicao);
            $resultado = $controller->atualizar(
                $id,
                prepararDadosPeriodo($dadosRequisicao)
            );

            responderJson(
                (bool) ($resultado['sucesso'] ?? false),
                (string) ($resultado['mensagem'] ?? 'Operação concluída.'),
                $resultado['dados'] ?? null,
                codigoHttpResultado($resultado),
                array_filter([
                    'erros' => $resultado['erros'] ?? null,
                ])
            );

        case 'fechar':
            if ($metodo !== 'POST') {
                responderJson(false, 'Esta operação deve ser enviada através de POST.', null, 405);
            }

            $resultado = $controller->fechar(
                obterId($dadosRequisicao)
            );

            responderJson(
                (bool) ($resultado['sucesso'] ?? false),
                (string) ($resultado['mensagem'] ?? 'Operação concluída.'),
                $resultado['dados'] ?? null,
                codigoHttpResultado($resultado)
            );

        case 'reabrir':
            if ($metodo !== 'POST') {
                responderJson(false, 'Esta operação deve ser enviada através de POST.', null, 405);
            }

            $resultado = $controller->reabrir(
                obterId($dadosRequisicao)
            );

            responderJson(
                (bool) ($resultado['sucesso'] ?? false),
                (string) ($resultado['mensagem'] ?? 'Operação concluída.'),
                $resultado['dados'] ?? null,
                codigoHttpResultado($resultado)
            );

        case 'processar':
        case 'iniciar_processamento':
            if ($metodo !== 'POST') {
                responderJson(false, 'Esta operação deve ser enviada através de POST.', null, 405);
            }

            $resultado = $controller->iniciarProcessamento(
                obterId($dadosRequisicao)
            );

            responderJson(
                (bool) ($resultado['sucesso'] ?? false),
                (string) ($resultado['mensagem'] ?? 'Operação concluída.'),
                $resultado['dados'] ?? null,
                codigoHttpResultado($resultado)
            );

        case 'concluir_processamento':
            if ($metodo !== 'POST') {
                responderJson(false, 'Esta operação deve ser enviada através de POST.', null, 405);
            }

            $resultado = $controller->concluirProcessamento(
                obterId($dadosRequisicao)
            );

            responderJson(
                (bool) ($resultado['sucesso'] ?? false),
                (string) ($resultado['mensagem'] ?? 'Operação concluída.'),
                $resultado['dados'] ?? null,
                codigoHttpResultado($resultado)
            );

        case 'cancelar':
            if ($metodo !== 'POST') {
                responderJson(false, 'Esta operação deve ser enviada através de POST.', null, 405);
            }

            $id = obterId($dadosRequisicao);
            $motivo = trim((string) (
                $dadosRequisicao['motivo_cancelamento']
                ?? $dadosRequisicao['motivo']
                ?? ''
            ));

            $resultado = $controller->cancelar($id, $motivo);

            responderJson(
                (bool) ($resultado['sucesso'] ?? false),
                (string) ($resultado['mensagem'] ?? 'Operação concluída.'),
                $resultado['dados'] ?? null,
                codigoHttpResultado($resultado),
                array_filter([
                    'erros' => $resultado['erros'] ?? null,
                ])
            );

        case 'listar_estados':
        case 'estados':
            $estados = $controller->listarEstados();

            responderJson(
                true,
                'Estados carregados com sucesso.',
                $estados
            );

        case 'listar_anos':
        case 'anos':
            $anos = $controller->listarAnos();

            responderJson(
                true,
                'Anos carregados com sucesso.',
                $anos
            );

        case 'resumo':
            $resumo = $controller->resumo();

            responderJson(
                true,
                'Resumo carregado com sucesso.',
                $resumo
            );

        default:
            responderJson(
                false,
                'A ação solicitada não é válida.',
                null,
                404
            );
    }
} catch (RuntimeException $e) {
    responderJson(
        false,
        $e->getMessage(),
        null,
        422
    );
} catch (Throwable $e) {
    error_log(sprintf(
        '[PeriodoSalarial AJAX][%s] %s em %s:%d',
        $acao,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    responderJson(
        false,
        'Ocorreu um erro interno ao processar a solicitação.',
        null,
        500
    );
}
