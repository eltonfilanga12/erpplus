<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__
    . '/../controllers/SalarioFuncionarioController.php';

/**
 * Envia a resposta JSON e encerra a execução.
 */
function responderJson(
    bool $success,
    string $message = '',
    mixed $data = null,
    int $httpCode = 200
): never {
    http_response_code($httpCode);

    $resposta = [
        'success' => $success,
        'message' => $message
    ];

    if ($data !== null) {
        $resposta['data'] = $data;
    }

    echo json_encode(
        $resposta,
        JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/**
 * Obtém um valor enviado por POST ou GET.
 */
function obterEntrada(
    string $chave,
    mixed $padrao = null
): mixed {
    if (array_key_exists($chave, $_POST)) {
        return $_POST[$chave];
    }

    if (array_key_exists($chave, $_GET)) {
        return $_GET[$chave];
    }

    return $padrao;
}

/**
 * Converte valor vazio para null.
 */
function valorOuNull(mixed $valor): mixed
{
    if ($valor === null) {
        return null;
    }

    if (is_string($valor) && trim($valor) === '') {
        return null;
    }

    return $valor;
}

/**
 * Converte as rubricas recebidas para array.
 *
 * O JavaScript deverá enviar:
 *
 * rubricas: JSON.stringify(rubricas)
 */
function obterRubricas(): array
{
    $rubricas = $_POST['rubricas'] ?? [];

    if (is_array($rubricas)) {
        return $rubricas;
    }

    if (!is_string($rubricas) || trim($rubricas) === '') {
        return [];
    }

    $resultado = json_decode($rubricas, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception(
            'O formato das rubricas enviadas é inválido.'
        );
    }

    if (!is_array($resultado)) {
        throw new Exception(
            'A lista de rubricas deve ser um array.'
        );
    }

    return $resultado;
}

/**
 * Prepara os dados para criação ou atualização.
 */
function prepararDadosSalario(
    int $empresaId,
    int $utilizadorId
): array {
    return [
        'empresa_id' => $empresaId,

        'funcionario_id' => (int)obterEntrada(
            'funcionario_id',
            0
        ),

        'contrato_id' => valorOuNull(
            obterEntrada('contrato_id')
        ),

        'moeda' => strtoupper(
            trim(
                (string)obterEntrada(
                    'moeda',
                    'MZN'
                )
            )
        ),

        'salario_base' => obterEntrada(
            'salario_base',
            0
        ),

        'periodicidade' => strtoupper(
            trim(
                (string)obterEntrada(
                    'periodicidade',
                    'MENSAL'
                )
            )
        ),

        'horas_mensais' => valorOuNull(
            obterEntrada('horas_mensais')
        ),

        'dias_mensais' => valorOuNull(
            obterEntrada('dias_mensais')
        ),

        'data_inicio' => trim(
            (string)obterEntrada(
                'data_inicio',
                ''
            )
        ),

        'data_fim' => valorOuNull(
            obterEntrada('data_fim')
        ),

        'motivo_alteracao' => valorOuNull(
            obterEntrada('motivo_alteracao')
        ),

        'observacoes' => valorOuNull(
            obterEntrada('observacoes')
        ),

        'utilizador_id' => $utilizadorId,

        'rubricas' => obterRubricas()
    ];
}

try {
    /*
     * Ajuste estes nomes conforme as chaves reais
     * utilizadas no login do teu sistema.
     */
    $empresaId = (int)(
        $_SESSION['empresa_id']
        ?? $_SESSION['usuario']['empresa_id']
        ?? 0
    );

    $utilizadorId = (int)(
        $_SESSION['usuario_id']
        ?? $_SESSION['utilizador_id']
        ?? $_SESSION['usuario']['id']
        ?? 0
    );

    if ($empresaId <= 0) {
        responderJson(
            false,
            'Empresa não identificada na sessão.',
            null,
            401
        );
    }

    if ($utilizadorId <= 0) {
        responderJson(
            false,
            'Utilizador não identificado na sessão.',
            null,
            401
        );
    }

    $acao = strtolower(
        trim(
            (string)obterEntrada(
                'acao',
                ''
            )
        )
    );

    if ($acao === '') {
        responderJson(
            false,
            'Ação não informada.',
            null,
            400
        );
    }

    $controller = new SalarioFuncionarioController();

    switch ($acao) {
        /*
         * LISTAR
         */
        case 'listar':
            $filtros = [
                'empresa_id' => $empresaId,

                'funcionario' => trim(
                    (string)obterEntrada(
                        'funcionario',
                        ''
                    )
                ),

                'estado' => trim(
                    (string)obterEntrada(
                        'estado',
                        ''
                    )
                )
            ];

            $resultado = $controller->listar(
                $filtros
            );

            responderJson(
                true,
                'Configurações salariais carregadas.',
                $resultado
            );

            /*
         * BUSCAR
         */
        case 'buscar':
            $id = (int)obterEntrada('id', 0);

            if ($id <= 0) {
                responderJson(
                    false,
                    'ID da configuração salarial inválido.',
                    null,
                    400
                );
            }

            $resultado = $controller->buscar($id);

            /*
             * Proteção adicional para não retornar
             * dados pertencentes a outra empresa.
             */
            if (
                (int)($resultado['empresa_id'] ?? 0)
                !== $empresaId
            ) {
                responderJson(
                    false,
                    'Configuração salarial não encontrada.',
                    null,
                    404
                );
            }

            responderJson(
                true,
                'Configuração salarial encontrada.',
                $resultado
            );

        case 'contrato_activo':
        case 'contrato_ativo':

            $funcionarioId =
                (int)obterEntrada(
                    'funcionario_id',
                    0
                );

            if ($funcionarioId <= 0) {

                responderJson(
                    false,
                    'Funcionário inválido.',
                    null,
                    400
                );
            }

            $resultado =
                $controller->contratoActivo(
                    $empresaId,
                    $funcionarioId
                );

            responderJson(
                true,
                'Contrato activo encontrado.',
                $resultado
            );

            /*
         * RESUMO
         */
        case 'resumo':
            $id = (int)obterEntrada('id', 0);

            if ($id <= 0) {
                responderJson(
                    false,
                    'ID da configuração salarial inválido.',
                    null,
                    400
                );
            }

            $salario = $controller->buscar($id);

            if (
                (int)($salario['empresa_id'] ?? 0)
                !== $empresaId
            ) {
                responderJson(
                    false,
                    'Configuração salarial não encontrada.',
                    null,
                    404
                );
            }

            $resultado = $controller->resumo($id);

            responderJson(
                true,
                'Resumo salarial calculado.',
                $resultado
            );

            /*
         * CRIAR
         */
        case 'criar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                responderJson(
                    false,
                    'Método de requisição inválido.',
                    null,
                    405
                );
            }

            $dados = prepararDadosSalario(
                $empresaId,
                $utilizadorId
            );

            $resultado = $controller->criar(
                $dados
            );

            responderJson(
                true,
                $resultado['message']
                    ?? 'Configuração salarial criada.',
                $resultado
            );

            /*
         * ATUALIZAR
         */
        case 'atualizar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                responderJson(
                    false,
                    'Método de requisição inválido.',
                    null,
                    405
                );
            }

            $id = (int)obterEntrada('id', 0);

            if ($id <= 0) {
                responderJson(
                    false,
                    'ID da configuração salarial inválido.',
                    null,
                    400
                );
            }

            $dados = prepararDadosSalario(
                $empresaId,
                $utilizadorId
            );

            $resultado = $controller->atualizar(
                $id,
                $dados
            );

            responderJson(
                true,
                $resultado['message']
                    ?? 'Configuração salarial atualizada.',
                $resultado
            );

            /*
         * ACTIVAR
         */
        case 'activar':
        case 'ativar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                responderJson(
                    false,
                    'Método de requisição inválido.',
                    null,
                    405
                );
            }

            $id = (int)obterEntrada('id', 0);

            if ($id <= 0) {
                responderJson(
                    false,
                    'ID da configuração salarial inválido.',
                    null,
                    400
                );
            }

            $resultado = $controller->activar(
                $id,
                $empresaId,
                $utilizadorId
            );

            responderJson(
                true,
                $resultado['message']
                    ?? 'Configuração salarial ativada.',
                $resultado
            );

            /*
         * INACTIVAR
         */
        case 'inactivar':
        case 'inativar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                responderJson(
                    false,
                    'Método de requisição inválido.',
                    null,
                    405
                );
            }

            $id = (int)obterEntrada('id', 0);

            if ($id <= 0) {
                responderJson(
                    false,
                    'ID da configuração salarial inválido.',
                    null,
                    400
                );
            }

            $resultado = $controller->inactivar(
                $id,
                $empresaId,
                $utilizadorId
            );

            responderJson(
                true,
                $resultado['message']
                    ?? 'Configuração salarial inativada.',
                $resultado
            );

        default:
            responderJson(
                false,
                'Ação inválida.',
                null,
                400
            );
    }
} catch (PDOException $e) {
    error_log(
        'Erro PDO em salarios_funcionarios_ajax.php: '
            . $e->getMessage()
    );

    responderJson(
        false,
        'Ocorreu um erro ao aceder à base de dados.',
        null,
        500
    );
} catch (Throwable $e) {
    error_log(
        'Erro em salarios_funcionarios_ajax.php: '
            . $e->getMessage()
    );

    responderJson(
        false,
        $e->getMessage(),
        null,
        400
    );
}
