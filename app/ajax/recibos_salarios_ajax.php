<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');


require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../controllers/ReciboSalarioController.php';

/*
|--------------------------------------------------------------------------
| Inicializar sessão
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Resposta JSON
|--------------------------------------------------------------------------
*/

function responderJson(
    array $resposta,
    int $statusHttp = 200
): never {
    http_response_code($statusHttp);

    echo json_encode(
        $resposta,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Resposta de erro
|--------------------------------------------------------------------------
*/

function responderErro(
    string $mensagem,
    int $statusHttp = 400,
    mixed $dados = null
): never {
    responderJson(
        [
            'success' => false,
            'message' => $mensagem,
            'data' => $dados
        ],
        $statusHttp
    );
}

/*
|--------------------------------------------------------------------------
| Ler dados enviados
|--------------------------------------------------------------------------
*/

function obterDadosRequisicao(): array
{
    $contentType =
        $_SERVER['CONTENT_TYPE'] ?? '';

    if (
        stripos(
            $contentType,
            'application/json'
        ) !== false
    ) {
        $conteudo =
            file_get_contents('php://input');

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
            responderErro(
                'O conteúdo JSON enviado é inválido.',
                400
            );
        }

        return is_array($dados)
            ? $dados
            : [];
    }

    return array_merge(
        $_GET,
        $_POST
    );
}

/*
|--------------------------------------------------------------------------
| Obter token CSRF
|--------------------------------------------------------------------------
*/

function obterTokenCsrf(
    array $dados
): string {
    $token =
        $dados['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? '';

    return trim((string) $token);
}

/*
|--------------------------------------------------------------------------
| Validar CSRF
|--------------------------------------------------------------------------
*/

function validarCsrf(
    array $dados
): void {
    $tokenSessao =
        $_SESSION['csrf_token'] ?? '';

    $tokenRecebido =
        obterTokenCsrf($dados);

    if (
        $tokenSessao === ''
        || $tokenRecebido === ''
        || !hash_equals(
            (string) $tokenSessao,
            $tokenRecebido
        )
    ) {
        responderErro(
            'Token CSRF inválido.',
            419
        );
    }
}

/*
|--------------------------------------------------------------------------
| Validar autenticação
|--------------------------------------------------------------------------
*/

function obterEmpresaId(): int
{
    $empresaId =
        $_SESSION['empresa_id']
        ?? $_SESSION['usuario']['empresa_id']
        ?? null;

    if (
        $empresaId === null
        || !is_numeric($empresaId)
        || (int) $empresaId <= 0
    ) {
        responderErro(
            'Empresa não identificada na sessão.',
            401
        );
    }

    return (int) $empresaId;
}

function obterUsuarioId(): int
{
    $usuarioId =
        $_SESSION['usuario_id']
        ?? $_SESSION['usuario']['id']
        ?? null;

    if (
        $usuarioId === null
        || !is_numeric($usuarioId)
        || (int) $usuarioId <= 0
    ) {
        responderErro(
            'Utilizador não autenticado.',
            401
        );
    }

    return (int) $usuarioId;
}

/*
|--------------------------------------------------------------------------
| Verificar método HTTP
|--------------------------------------------------------------------------
*/

if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !== 'POST'
) {
    responderErro(
        'Método HTTP não permitido.',
        405
    );
}

/*
|--------------------------------------------------------------------------
| Processar requisição
|--------------------------------------------------------------------------
*/

try {
    $dados =
        obterDadosRequisicao();

    $acao =
        strtolower(
            trim(
                (string) (
                    $dados['acao'] ?? ''
                )
            )
        );

    if ($acao === '') {
        responderErro(
            'Ação não informada.',
            400
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Todas as ações exigem CSRF
    |--------------------------------------------------------------------------
    */

    validarCsrf($dados);

    $empresaId =
        obterEmpresaId();

    $usuarioId =
        obterUsuarioId();

    $controller =
        new ReciboSalarioController();

    /*
    |--------------------------------------------------------------------------
    | Dispatcher
    |--------------------------------------------------------------------------
    */

    switch ($acao) {
        case 'listar':
            $resposta =
                $controller->listar(
                    $empresaId,
                    $dados
                );
            break;

        case 'buscar':
            $resposta =
                $controller->buscar(
                    $empresaId,
                    $dados
                );
            break;

        case 'periodos':
            $resposta =
                $controller->periodos(
                    $empresaId
                );
            break;

        case 'folhas':
            $resposta =
                $controller->folhas(
                    $empresaId,
                    $dados
                );
            break;

        case 'funcionarios':
            $resposta =
                $controller->funcionarios(
                    $empresaId,
                    $dados
                );
            break;

        case 'gerar':
            $resposta =
                $controller->gerar(
                    $empresaId,
                    $usuarioId,
                    $dados
                );
            break;

        case 'gerar_todos':
            $resposta =
                $controller->gerarTodos(
                    $empresaId,
                    $usuarioId,
                    $dados
                );
            break;

        case 'processar_geracao':
            $resposta =
                $controller
                    ->processarGeracao(
                        $empresaId,
                        $usuarioId,
                        $dados
                    );
            break;

        case 'marcar_enviado':
            $resposta =
                $controller
                    ->marcarEnviado(
                        $empresaId,
                        $usuarioId,
                        $dados
                    );
            break;

        case 'marcar_entregue':
            $resposta =
                $controller
                    ->marcarEntregue(
                        $empresaId,
                        $usuarioId,
                        $dados
                    );
            break;

        case 'cancelar':
            $resposta =
                $controller->cancelar(
                    $empresaId,
                    $usuarioId,
                    $dados
                );
            break;

        case 'resumo':
            $resposta =
                $controller->resumo(
                    $empresaId
                );
            break;

        default:
            responderErro(
                'Ação inválida.',
                400
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Definir código HTTP
    |--------------------------------------------------------------------------
    */

    $statusHttp =
        ($resposta['success'] ?? false)
            ? 200
            : 400;

    responderJson(
        $resposta,
        $statusHttp
    );
} catch (Throwable $e) {
    error_log(
        sprintf(
            '[recibos_salarios_ajax] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    responderErro(
        'Ocorreu um erro interno ao processar a solicitação.',
        500
    );
}