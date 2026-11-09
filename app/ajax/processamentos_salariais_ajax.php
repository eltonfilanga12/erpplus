<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../controllers/ProcessamentoSalarialController.php';

/*
|--------------------------------------------------------------------------
| Funções auxiliares
|--------------------------------------------------------------------------
*/

function responderJson(
    array $resposta,
    int $statusHttp = 200
): void {
    http_response_code($statusHttp);

    echo json_encode(
        $resposta,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function obterEntrada(
    string $campo,
    mixed $padrao = null
): mixed {
    if (
        isset($_POST[$campo])
        && $_POST[$campo] !== ''
    ) {
        return $_POST[$campo];
    }

    $conteudo = file_get_contents(
        'php://input'
    );

    if ($conteudo) {
        $json = json_decode(
            $conteudo,
            true
        );

        if (
            is_array($json)
            && array_key_exists(
                $campo,
                $json
            )
        ) {
            return $json[$campo];
        }
    }

    return $padrao;
}

function obterInteiro(
    string $campo,
    int $padrao = 0
): int {
    return (int)obterEntrada(
        $campo,
        $padrao
    );
}

function obterTexto(
    string $campo,
    string $padrao = ''
): string {
    return trim(
        (string)obterEntrada(
            $campo,
            $padrao
        )
    );
}

/*
|--------------------------------------------------------------------------
| Inicialização
|--------------------------------------------------------------------------
*/

try {
    if (
        session_status()
        !== PHP_SESSION_ACTIVE
    ) {
        session_start();
    }


    /*
     * Ajuste conforme o nome do método
     * utilizado na sua classe Database.
     */
    $db = Database::conn();

    if (!$db instanceof PDO) {
        throw new Exception(
            'Não foi possível estabelecer '
                . 'ligação com a base de dados.'
        );
    }

    $controller =
        new ProcessamentoSalarialController(
            $db
        );

    /*
     * Ajuste os nomes abaixo conforme
     * as chaves reais da sua sessão.
     */
    $empresaId =
        (int)(
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? 0
        );

    $utilizadorId =
        (int)(
            $_SESSION['usuario_id']
            ?? $_SESSION['utilizador_id']
            ?? $_SESSION['usuario']['id']
            ?? 0
        );

    if ($empresaId <= 0) {
        responderJson(
            [
                'success' => false,
                'message' =>
                'Empresa não identificada.',
                'data' => null
            ],
            401
        );
    }

    $acao = strtolower(
        obterTexto('acao')
    );

    if ($acao === '') {
        responderJson(
            [
                'success' => false,
                'message' =>
                'Ação não informada.',
                'data' => null
            ],
            400
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rotas
    |--------------------------------------------------------------------------
    */

    switch ($acao) {
        /*
        |--------------------------------------------------------------------------
        | Listar
        |--------------------------------------------------------------------------
        */

        case 'listar':
            $filtros = [
                'periodo_id' =>
                obterInteiro(
                    'periodo_id'
                ),

                'funcionario_id' =>
                obterInteiro(
                    'funcionario_id'
                ),

                'estado' =>
                obterTexto(
                    'estado'
                ),

                'pesquisa' =>
                obterTexto(
                    'pesquisa'
                )
            ];

            responderJson(
                $controller->listar(
                    $empresaId,
                    $filtros
                )
            );

            /*
        |--------------------------------------------------------------------------
        | Buscar
        |--------------------------------------------------------------------------
        */

        case 'buscar':
            $id = obterInteiro('id');

            responderJson(
                $controller->buscar(
                    $id,
                    $empresaId
                )
            );

            /*
        |--------------------------------------------------------------------------
        | Processar período
        |--------------------------------------------------------------------------
        */

        case 'processar_periodo':
        case 'processar-periodo':
        case 'processar':
            $periodoId =
                obterInteiro(
                    'periodo_id'
                );

            responderJson(
                $controller
                    ->processarPeriodo(
                        $empresaId,
                        $periodoId,
                        $utilizadorId
                    )
            );

        case 'recalcular_periodo':
        case 'recalcular-periodo':
        case 'recalcular':

            $periodoId =
                obterInteiro(
                    'periodo_id'
                );

            responderJson(
                $controller
                    ->recalcularPeriodo(
                        $empresaId,
                        $periodoId,
                        $utilizadorId
                    )
            );

            /*
        |--------------------------------------------------------------------------
        | Aprovar
        |--------------------------------------------------------------------------
        */

        case 'aprovar':
            $id = obterInteiro('id');

            responderJson(
                $controller->aprovar(
                    $id,
                    $empresaId,
                    $utilizadorId
                )
            );

            /*
        |--------------------------------------------------------------------------
        | Cancelar
        |--------------------------------------------------------------------------
        */

        case 'cancelar':
            $id = obterInteiro('id');

            $motivo =
                obterTexto(
                    'motivo'
                );

            responderJson(
                $controller->cancelar(
                    $id,
                    $empresaId,
                    $utilizadorId,
                    $motivo
                )
            );

            /*
        |--------------------------------------------------------------------------
        | Resumo
        |--------------------------------------------------------------------------
        */

        case 'resumo':
            $periodoId =
                obterInteiro(
                    'periodo_id'
                );

            responderJson(
                $controller->resumo(
                    $empresaId,
                    $periodoId > 0
                        ? $periodoId
                        : null
                )
            );

            /*
        |--------------------------------------------------------------------------
        | Itens
        |--------------------------------------------------------------------------
        */

        case 'listar_itens':
        case 'itens':
            $processamentoId =
                obterInteiro(
                    'processamento_id'
                );

            if ($processamentoId <= 0) {
                $processamentoId =
                    obterInteiro('id');
            }

            responderJson(
                $controller->listarItens(
                    $processamentoId,
                    $empresaId
                )
            );

            /*
        |--------------------------------------------------------------------------
        | Ação inválida
        |--------------------------------------------------------------------------
        */

        default:
            responderJson(
                [
                    'success' => false,
                    'message' =>
                    'Ação inválida: '
                        . $acao,
                    'data' => null
                ],
                400
            );
    }
} catch (Throwable $e) {
    error_log(
        '[processamentos_salariais_ajax.php] '
            . $e->getMessage()
            . ' em '
            . $e->getFile()
            . ':'
            . $e->getLine()
    );

    responderJson(
        [
            'success' => false,
            'message' =>
            $e->getMessage(),
            'data' => null
        ],
        500
    );
}
