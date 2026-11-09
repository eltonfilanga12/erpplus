<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';

require_once __DIR__
    . '/../services/ContabilidadeService.php';

final class FechoExercicio
{
    private PDO $pdo;

    private ContabilidadeService $contabilidade;

    private AuditoriaService $auditoria;
    private LogService $log;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->contabilidade =
            new ContabilidadeService($pdo);

        $this->auditoria =
            new AuditoriaService(
                $pdo
            );

        $this->log =
            new LogService(
                $pdo
            );
    }

    // ==================================================
    // LISTAR EXERCÍCIOS
    // ==================================================

    public function listar(
        int $empresaId
    ): array {

        $this->validarEmpresa($empresaId);

        $stmt = $this->pdo->prepare("
            SELECT
                exercicio.id,
                exercicio.empresa_id,
                exercicio.codigo,
                exercicio.descricao,
                exercicio.data_inicio,
                exercicio.data_fim,
                exercicio.status_id,
                exercicio.encerrado_em,
                exercicio.encerrado_por,

                status.nome AS status_nome,

                utilizador.nome AS encerrado_por_nome,

                (
                    SELECT COUNT(*)

                    FROM periodos_contabilisticos periodo

                    WHERE periodo.exercicio_id =
                        exercicio.id

                      AND periodo.empresa_id =
                        exercicio.empresa_id
                ) AS quantidade_periodos,

                (
                    SELECT COUNT(*)

                    FROM lancamentos_contabilisticos lancamento

                    WHERE lancamento.exercicio_id =
                        exercicio.id

                      AND lancamento.empresa_id =
                        exercicio.empresa_id
                ) AS quantidade_lancamentos

            FROM exercicios_contabilisticos exercicio

            INNER JOIN status
                ON status.id =
                    exercicio.status_id

               AND status.tipo =
                    'exercicio_contabilistico'

            LEFT JOIN usuarios utilizador
                ON utilizador.id =
                    exercicio.encerrado_por

            WHERE exercicio.empresa_id =
                :empresa_id

            ORDER BY
                exercicio.data_inicio DESC,
                exercicio.id DESC
        ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // BUSCAR EXERCÍCIO
    // ==================================================

    public function buscar(
        int $empresaId,
        int $exercicioId
    ): array {

        $this->validarEmpresa($empresaId);

        if ($exercicioId <= 0) {
            throw new RuntimeException(
                'Exercício contabilístico inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                exercicio.*,

                status.nome AS status_nome

            FROM exercicios_contabilisticos exercicio

            INNER JOIN status
                ON status.id =
                    exercicio.status_id

               AND status.tipo =
                    'exercicio_contabilistico'

            WHERE exercicio.id =
                :exercicio_id

              AND exercicio.empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':exercicio_id' => $exercicioId,
            ':empresa_id'   => $empresaId
        ]);

        $exercicio =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$exercicio) {
            throw new RuntimeException(
                'Exercício contabilístico não encontrado.'
            );
        }

        return $exercicio;
    }

    // ==================================================
    // DIAGNÓSTICO DO EXERCÍCIO
    // ==================================================

    public function diagnosticar(
        int $empresaId,
        int $exercicioId
    ): array {

        $exercicio =
            $this->buscar(
                $empresaId,
                $exercicioId
            );

        $totaisResultado =
            $this->calcularContasResultado(
                $empresaId,
                $exercicioId
            );

        $pendentes =
            $this->contarLancamentosPendentes(
                $empresaId,
                $exercicioId
            );

        $periodos =
            $this->resumoPeriodos(
                $empresaId,
                $exercicioId
            );

        $lancamentoEncerramento =
            $this->buscarLancamentoEncerramento(
                $empresaId,
                $exercicioId
            );

        $contaResultadoConfigurada =
            $this->existeContaIntegracao(
                $empresaId,
                'RESULTADO_EXERCICIO'
            );

        $diarioEncerramentoConfigurado =
            $this->existeDiarioEncerramento(
                $empresaId
            );

        return [
            'exercicio' =>
            $exercicio,

            'lancamentos_pendentes' =>
            $pendentes,

            'periodos' =>
            $periodos,

            'resultado' => [
                'total_rendimentos' =>
                $totaisResultado['total_rendimentos'],

                'total_gastos' =>
                $totaisResultado['total_gastos'],

                'lucro_prejuizo' =>
                $totaisResultado['resultado'],

                'tipo_resultado' =>
                $totaisResultado['resultado'] > 0
                    ? 'LUCRO'
                    : (
                        $totaisResultado['resultado'] < 0
                        ? 'PREJUIZO'
                        : 'NULO'
                    )
            ],

            'configuracao' => [
                'conta_resultado_configurada' =>
                $contaResultadoConfigurada,

                'diario_encerramento_configurado' =>
                $diarioEncerramentoConfigurado
            ],

            'lancamento_encerramento' =>
            $lancamentoEncerramento,

            'pode_fechar' =>
            $this->podeFecharDiagnostico(
                $exercicio,
                $pendentes,
                $contaResultadoConfigurada,
                $diarioEncerramentoConfigurado,
                $lancamentoEncerramento
            )
        ];
    }

    // ==================================================
    // FECHAR EXERCÍCIO
    // ==================================================

    public function fechar(
        int $empresaId,
        int $exercicioId,
        int $usuarioId
    ): array {

        $this->validarEmpresa($empresaId);
        $this->validarUtilizador($usuarioId);

        /*
         * A transação deve abranger:
         *
         * - lançamento de encerramento;
         * - fecho dos períodos;
         * - alteração do estado do exercício.
         */
        $this->pdo->beginTransaction();

        try {

            $exercicio =
                $this->bloquearExercicio(
                    $empresaId,
                    $exercicioId
                );

            $this->validarExercicioAberto(
                $exercicio
            );

            $this->validarSemLancamentosPendentes(
                $empresaId,
                $exercicioId
            );

            $existente =
                $this->buscarLancamentoEncerramento(
                    $empresaId,
                    $exercicioId
                );

            if ($existente !== null) {
                throw new RuntimeException(
                    'O exercício já possui um lançamento de encerramento.'
                );
            }

            /*
             * Garante que a data final pertence a um exercício
             * e período ainda abertos antes de criar o lançamento.
             */
            $contexto =
                $this->contabilidade
                ->contextoData(
                    $empresaId,
                    (string)$exercicio['data_fim']
                );

            if (
                (int)$contexto['exercicio_id']
                !== $exercicioId
            ) {
                throw new RuntimeException(
                    'O contexto contabilístico da data final não corresponde ao exercício selecionado.'
                );
            }

            $contaResultado =
                $this->contabilidade
                ->contaIntegracao(
                    $empresaId,
                    'RESULTADO_EXERCICIO'
                );

            $diario =
                $this->obterDiarioEncerramento(
                    $empresaId
                );

            $contasResultado =
                $this->calcularContasResultado(
                    $empresaId,
                    $exercicioId
                );

            $itens =
                $this->montarItensEncerramento(
                    $contasResultado['contas'],
                    (int)$contaResultado['id'],
                    (string)$exercicio['codigo']
                );

            $lancamentoId =
                null;

            /*
             * Se não houve rendimentos nem gastos, não há
             * valores a zerar. O exercício pode ser fechado
             * sem lançamento contabilístico.
             */
            if ($itens !== []) {

                $lancamentoId =
                    $this->contabilidade
                    ->criarLancamento(
                        [
                            'empresa_id' =>
                            $empresaId,

                            'filial_id' =>
                            null,

                            'exercicio_id' =>
                            $exercicioId,

                            'periodo_id' =>
                            (int)$contexto['periodo_id'],

                            'diario_id' =>
                            (int)$diario['id'],

                            'numero_documento' =>
                            'ENC-'
                                . $exercicio['codigo'],

                            'referencia' =>
                            'ENCERRAMENTO-'
                                . $exercicioId,

                            'data_lancamento' =>
                            $exercicio['data_fim'],

                            'data_documento' =>
                            $exercicio['data_fim'],

                            'descricao' =>
                            'Encerramento do exercício contabilístico '
                                . $exercicio['codigo'],

                            'origem' =>
                            'ENCERRAMENTO',

                            'origem_id' =>
                            $exercicioId,

                            'usuario_id' =>
                            $usuarioId
                        ],
                        $itens
                    );
            }

            $this->fecharPeriodos(
                $empresaId,
                $exercicioId,
                $usuarioId
            );

            $this->marcarExercicioFechado(
                $empresaId,
                $exercicioId,
                $usuarioId
            );

            $this->registarAuditoria(
                $empresaId,
                $usuarioId,
                $exercicioId,
                $lancamentoId,
                $contasResultado
            );

            $this->pdo->commit();

            return [
                'exercicio_id' =>
                $exercicioId,

                'codigo_exercicio' =>
                $exercicio['codigo'],

                'lancamento_id' =>
                $lancamentoId,

                'total_rendimentos' =>
                $contasResultado['total_rendimentos'],

                'total_gastos' =>
                $contasResultado['total_gastos'],

                'resultado' =>
                $contasResultado['resultado'],

                'tipo_resultado' =>
                $contasResultado['resultado'] > 0
                    ? 'LUCRO'
                    : (
                        $contasResultado['resultado'] < 0
                        ? 'PREJUIZO'
                        : 'NULO'
                    )
            ];
        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // BLOQUEAR EXERCÍCIO
    // ==================================================

    private function bloquearExercicio(
        int $empresaId,
        int $exercicioId
    ): array {

        if ($exercicioId <= 0) {
            throw new RuntimeException(
                'Exercício contabilístico inválido.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT
                exercicio.*,
                status.nome AS status_nome

            FROM exercicios_contabilisticos exercicio

            INNER JOIN status
                ON status.id =
                    exercicio.status_id

               AND status.tipo =
                    'exercicio_contabilistico'

            WHERE exercicio.id =
                :exercicio_id

              AND exercicio.empresa_id =
                :empresa_id

            LIMIT 1

            FOR UPDATE
        ");

        $stmt->execute([
            ':exercicio_id' =>
            $exercicioId,

            ':empresa_id' =>
            $empresaId
        ]);

        $exercicio =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$exercicio) {
            throw new RuntimeException(
                'Exercício contabilístico não encontrado.'
            );
        }

        return $exercicio;
    }

    // ==================================================
    // VALIDAR EXERCÍCIO ABERTO
    // ==================================================

    private function validarExercicioAberto(
        array $exercicio
    ): void {

        $status =
            $this->normalizarTexto(
                (string)(
                    $exercicio['status_nome']
                    ?? ''
                )
            );

        if (
            !in_array(
                $status,
                [
                    'aberto',
                    'activo',
                    'ativo'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'O exercício contabilístico não está aberto.'
            );
        }

        if (!empty($exercicio['encerrado_em'])) {
            throw new RuntimeException(
                'O exercício contabilístico já foi encerrado.'
            );
        }
    }

    // ==================================================
    // VALIDAR LANÇAMENTOS PENDENTES
    // ==================================================

    private function validarSemLancamentosPendentes(
        int $empresaId,
        int $exercicioId
    ): void {

        $quantidade =
            $this->contarLancamentosPendentes(
                $empresaId,
                $exercicioId
            );

        if ($quantidade > 0) {
            throw new RuntimeException(
                'Existem '
                    . $quantidade
                    . ' lançamento(s) contabilístico(s) pendente(s) no exercício.'
            );
        }
    }

    private function contarLancamentosPendentes(
        int $empresaId,
        int $exercicioId
    ): int {

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)

            FROM lancamentos_contabilisticos lancamento

            INNER JOIN status
                ON status.id =
                    lancamento.status_id

               AND status.tipo =
                    'lancamento_contabilistico'

            WHERE lancamento.empresa_id =
                :empresa_id

              AND lancamento.exercicio_id =
                :exercicio_id

              AND LOWER(status.nome) NOT IN
              (
                  'contabilizado',
                  'contabilizada',
                  'anulado',
                  'anulada',
                  'cancelado',
                  'cancelada'
              )
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':exercicio_id' =>
            $exercicioId
        ]);

        return (int)$stmt->fetchColumn();
    }

    // ==================================================
    // CALCULAR CONTAS DE RESULTADO
    // ==================================================

    private function calcularContasResultado(
        int $empresaId,
        int $exercicioId
    ): array {

        $statusContabilizado =
            $this->contabilidade
            ->statusLancamentoId(
                'Contabilizado'
            );

        $stmt = $this->pdo->prepare("
            SELECT
                conta.id,
                conta.codigo,
                conta.nome,
                conta.grupo,
                conta.natureza,
                conta.integracao_codigo,

                ROUND(
                    COALESCE(
                        SUM(item.debito),
                        0
                    ),
                    2
                ) AS total_debito,

                ROUND(
                    COALESCE(
                        SUM(item.credito),
                        0
                    ),
                    2
                ) AS total_credito

            FROM contas_contabilisticas conta

            INNER JOIN lancamento_contabilistico_itens item
                ON item.conta_contabil_id =
                    conta.id

            INNER JOIN lancamentos_contabilisticos lancamento
                ON lancamento.id =
                    item.lancamento_id

               AND lancamento.empresa_id =
                    conta.empresa_id

            WHERE conta.empresa_id =
                :empresa_id

              AND lancamento.exercicio_id =
                :exercicio_id

              AND lancamento.status_id =
                :status_contabilizado

              AND conta.grupo IN
              (
                  'RENDIMENTO',
                  'GASTO'
              )

              AND conta.tipo =
                'ANALITICA'

              AND conta.aceita_lancamento =
                1

            GROUP BY
                conta.id,
                conta.codigo,
                conta.nome,
                conta.grupo,
                conta.natureza,
                conta.integracao_codigo

            HAVING
                ABS(total_debito - total_credito)
                > 0.009

            ORDER BY conta.codigo ASC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':exercicio_id' =>
            $exercicioId,

            ':status_contabilizado' =>
            $statusContabilizado
        ]);

        $linhas =
            $stmt->fetchAll(PDO::FETCH_ASSOC);

        $contas =
            [];

        $totalRendimentos =
            0.0;

        $totalGastos =
            0.0;

        foreach ($linhas as $linha) {

            $grupo =
                strtoupper(
                    trim(
                        (string)$linha['grupo']
                    )
                );

            $debito =
                round(
                    (float)$linha['total_debito'],
                    2
                );

            $credito =
                round(
                    (float)$linha['total_credito'],
                    2
                );

            /*
             * O saldo assinado segue a natureza normal:
             *
             * Rendimento: crédito - débito.
             * Gasto: débito - crédito.
             */
            $saldo =
                $grupo === 'RENDIMENTO'
                ? round($credito - $debito, 2)
                : round($debito - $credito, 2);

            if (abs($saldo) <= 0.009) {
                continue;
            }

            $contas[] = [
                'id' =>
                (int)$linha['id'],

                'codigo' =>
                (string)$linha['codigo'],

                'nome' =>
                (string)$linha['nome'],

                'grupo' =>
                $grupo,

                'natureza' =>
                (string)$linha['natureza'],

                'integracao_codigo' =>
                $linha['integracao_codigo'],

                'total_debito' =>
                $debito,

                'total_credito' =>
                $credito,

                'saldo' =>
                $saldo
            ];

            if ($grupo === 'RENDIMENTO') {
                $totalRendimentos += $saldo;
            } else {
                $totalGastos += $saldo;
            }
        }

        $totalRendimentos =
            round($totalRendimentos, 2);

        $totalGastos =
            round($totalGastos, 2);

        return [
            'contas' =>
            $contas,

            'total_rendimentos' =>
            $totalRendimentos,

            'total_gastos' =>
            $totalGastos,

            'resultado' =>
            round(
                $totalRendimentos
                    - $totalGastos,
                2
            )
        ];
    }

    // ==================================================
    // MONTAR ITENS DE ENCERRAMENTO
    // ==================================================

    private function montarItensEncerramento(
        array $contas,
        int $contaResultadoId,
        string $codigoExercicio
    ): array {

        $itens =
            [];

        $totalDebito =
            0.0;

        $totalCredito =
            0.0;

        foreach ($contas as $conta) {

            $saldo =
                round(
                    (float)$conta['saldo'],
                    2
                );

            if (abs($saldo) <= 0.009) {
                continue;
            }

            $grupo =
                strtoupper(
                    trim(
                        (string)$conta['grupo']
                    )
                );

            /*
             * Para zerar rendimentos:
             *
             * saldo credor positivo -> débito.
             * saldo devedor negativo -> crédito.
             */
            if ($grupo === 'RENDIMENTO') {

                if ($saldo > 0) {

                    $debito =
                        $saldo;

                    $credito =
                        0.0;
                } else {

                    $debito =
                        0.0;

                    $credito =
                        abs($saldo);
                }

                /*
             * Para zerar gastos:
             *
             * saldo devedor positivo -> crédito.
             * saldo credor negativo -> débito.
             */
            } else {

                if ($saldo > 0) {

                    $debito =
                        0.0;

                    $credito =
                        $saldo;
                } else {

                    $debito =
                        abs($saldo);

                    $credito =
                        0.0;
                }
            }

            $itens[] = [
                'conta_contabil_id' =>
                (int)$conta['id'],

                'centro_custo_id' =>
                null,

                'cliente_id' =>
                null,

                'fornecedor_id' =>
                null,

                'descricao' =>
                'Encerramento da conta '
                    . $conta['codigo']
                    . ' - '
                    . $conta['nome']
                    . ' no exercício '
                    . $codigoExercicio,

                'debito' =>
                round($debito, 2),

                'credito' =>
                round($credito, 2)
            ];

            $totalDebito += $debito;
            $totalCredito += $credito;
        }

        $diferenca =
            round(
                $totalDebito
                    - $totalCredito,
                2
            );

        /*
         * A conta Resultado do Exercício equilibra o lançamento.
         *
         * diferença positiva:
         * há mais débitos nas contas encerradas,
         * logo Resultado recebe crédito.
         *
         * diferença negativa:
         * há mais créditos nas contas encerradas,
         * logo Resultado recebe débito.
         */
        if (abs($diferenca) > 0.009) {

            $itens[] = [
                'conta_contabil_id' =>
                $contaResultadoId,

                'centro_custo_id' =>
                null,

                'cliente_id' =>
                null,

                'fornecedor_id' =>
                null,

                'descricao' =>
                $diferenca > 0
                    ? 'Lucro apurado no exercício '
                    . $codigoExercicio
                    : 'Prejuízo apurado no exercício '
                    . $codigoExercicio,

                'debito' =>
                $diferenca < 0
                    ? abs($diferenca)
                    : 0,

                'credito' =>
                $diferenca > 0
                    ? $diferenca
                    : 0
            ];
        }

        return $itens;
    }

    // ==================================================
    // OBTER DIÁRIO DE ENCERRAMENTO
    // ==================================================

    private function obterDiarioEncerramento(
        int $empresaId
    ): array {

        /*
         * Primeiro procura pelo tipo ENCERRAMENTO.
         */
        try {

            return $this->contabilidade
                ->diarioPorTipo(
                    $empresaId,
                    'ENCERRAMENTO'
                );
        } catch (Throwable $e) {

            /*
             * Compatibilidade com bases onde o diário
             * foi criado com código ENC, mas outro tipo.
             */
            $stmt = $this->pdo->prepare("
                SELECT
                    diario.id,
                    diario.codigo,
                    diario.nome,
                    diario.tipo,
                    diario.numeracao_automatica,
                    diario.ultimo_numero,
                    diario.status_id,

                    status.nome AS status_nome

                FROM diarios_contabilisticos diario

                INNER JOIN status
                    ON status.id =
                        diario.status_id

                   AND status.tipo =
                        'diario_contabilistico'

                WHERE diario.empresa_id =
                    :empresa_id

                  AND diario.codigo =
                    'ENC'

                  AND LOWER(status.nome) IN
                  (
                      'activo',
                      'ativo',
                      'activa',
                      'ativa'
                  )

                LIMIT 1
            ");

            $stmt->execute([
                ':empresa_id' =>
                $empresaId
            ]);

            $diario =
                $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$diario) {
                throw new RuntimeException(
                    'Diário de encerramento não configurado.'
                );
            }

            if (
                (int)$diario['numeracao_automatica'] !== 1
            ) {
                throw new RuntimeException(
                    'O Diário de Encerramento deve utilizar numeração automática.'
                );
            }

            return $diario;
        }
    }

    // ==================================================
    // FECHAR PERÍODOS
    // ==================================================

    private function fecharPeriodos(
        int $empresaId,
        int $exercicioId,
        int $usuarioId
    ): void {

        $statusFechado =
            $this->statusId(
                'periodo_contabilistico',
                [
                    'Fechado',
                    'Encerrado'
                ]
            );

        $stmt = $this->pdo->prepare("
            UPDATE periodos_contabilisticos

            SET
                status_id =
                    :status_id,

                fechado_em =
                    NOW(),

                fechado_por =
                    :usuario_id

            WHERE empresa_id =
                :empresa_id

              AND exercicio_id =
                :exercicio_id
        ");

        $stmt->execute([
            ':status_id' =>
            $statusFechado,

            ':usuario_id' =>
            $usuarioId,

            ':empresa_id' =>
            $empresaId,

            ':exercicio_id' =>
            $exercicioId
        ]);
    }

    // ==================================================
    // MARCAR EXERCÍCIO FECHADO
    // ==================================================

    private function marcarExercicioFechado(
        int $empresaId,
        int $exercicioId,
        int $usuarioId
    ): void {

        $statusFechado =
            $this->statusId(
                'exercicio_contabilistico',
                [
                    'Fechado',
                    'Encerrado'
                ]
            );

        $stmt = $this->pdo->prepare("
        UPDATE exercicios_contabilisticos

        SET
            status_id =
                :status_id,

            encerrado_em =
                NOW(),

            encerrado_por =
                :encerrado_por,

            atualizado_por =
                :atualizado_por

        WHERE id =
            :exercicio_id

          AND empresa_id =
            :empresa_id
    ");

        $stmt->execute([
            ':status_id' =>
            $statusFechado,

            ':encerrado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId,

            ':exercicio_id' =>
            $exercicioId,

            ':empresa_id' =>
            $empresaId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Não foi possível atualizar o estado do exercício.'
            );
        }
    }

    // ==================================================
    // RESUMO DOS PERÍODOS
    // ==================================================

    private function resumoPeriodos(
        int $empresaId,
        int $exercicioId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS total,

                SUM(
                    CASE

                        WHEN LOWER(status.nome) IN
                        (
                            'aberto',
                            'activo',
                            'ativo'
                        )
                        THEN 1

                        ELSE 0

                    END
                ) AS abertos,

                SUM(
                    CASE

                        WHEN LOWER(status.nome) IN
                        (
                            'fechado',
                            'encerrado'
                        )
                        THEN 1

                        ELSE 0

                    END
                ) AS fechados

            FROM periodos_contabilisticos periodo

            INNER JOIN status
                ON status.id =
                    periodo.status_id

               AND status.tipo =
                    'periodo_contabilistico'

            WHERE periodo.empresa_id =
                :empresa_id

              AND periodo.exercicio_id =
                :exercicio_id
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':exercicio_id' =>
            $exercicioId
        ]);

        $dados =
            $stmt->fetch(PDO::FETCH_ASSOC)
            ?: [];

        return [
            'total' =>
            (int)($dados['total'] ?? 0),

            'abertos' =>
            (int)($dados['abertos'] ?? 0),

            'fechados' =>
            (int)($dados['fechados'] ?? 0)
        ];
    }

    // ==================================================
    // BUSCAR LANÇAMENTO DE ENCERRAMENTO
    // ==================================================

    private function buscarLancamentoEncerramento(
        int $empresaId,
        int $exercicioId
    ): ?array {

        $stmt = $this->pdo->prepare("
            SELECT
                lancamento.id,
                lancamento.numero,
                lancamento.numero_documento,
                lancamento.data_lancamento,
                lancamento.total_debito,
                lancamento.total_credito,
                lancamento.status_id

            FROM lancamentos_contabilisticos lancamento

            WHERE lancamento.empresa_id =
                :empresa_id

              AND lancamento.origem =
                'ENCERRAMENTO'

              AND lancamento.origem_id =
                :exercicio_id

            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':exercicio_id' =>
            $exercicioId
        ]);

        $dados =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ?: null;
    }

    // ==================================================
    // VERIFICAR CONFIGURAÇÕES
    // ==================================================

    private function existeContaIntegracao(
        int $empresaId,
        string $codigo
    ): bool {

        try {

            $this->contabilidade
                ->contaIntegracao(
                    $empresaId,
                    $codigo
                );

            return true;
        } catch (Throwable $e) {

            return false;
        }
    }

    private function existeDiarioEncerramento(
        int $empresaId
    ): bool {

        try {

            $this->obterDiarioEncerramento(
                $empresaId
            );

            return true;
        } catch (Throwable $e) {

            return false;
        }
    }

    // ==================================================
    // STATUS
    // ==================================================

    private function statusId(
        string $tipo,
        array $nomes
    ): int {

        if ($nomes === []) {
            throw new RuntimeException(
                'Nomes de status não informados.'
            );
        }

        $placeholders =
            [];

        $params = [
            ':tipo' => $tipo
        ];

        foreach ($nomes as $indice => $nome) {

            $chave =
                ':nome_' . $indice;

            $placeholders[] =
                'LOWER(' . $chave . ')';

            $params[$chave] =
                trim((string)$nome);
        }

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM status

            WHERE tipo =
                :tipo

              AND LOWER(nome) IN
              (
                  " . implode(',', $placeholders) . "
              )

            ORDER BY id ASC

            LIMIT 1
        ");

        $stmt->execute($params);

        $id =
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                'Status não encontrado para o tipo '
                    . $tipo
                    . '.'
            );
        }

        return $id;
    }

    // ==================================================
    // AUDITORIA
    // ==================================================

    private function registarAuditoria(
        int $empresaId,
        int $usuarioId,
        int $exercicioId,
        ?int $lancamentoId,
        array $resultado
    ): void {

        $this->auditoria
            ->atualizacao(
                $empresaId,
                $usuarioId,
                'exercicios_contabilisticos',
                $exercicioId,

                [
                    'status' =>
                    'ABERTO'
                ],

                [
                    'exercicio_id' =>
                    $exercicioId,

                    'lancamento_encerramento_id' =>
                    $lancamentoId,

                    'total_rendimentos' =>
                    $resultado['total_rendimentos'],

                    'total_gastos' =>
                    $resultado['total_gastos'],

                    'resultado' =>
                    $resultado['resultado'],

                    'status' =>
                    'ENCERRADO',

                    'encerrado_em' =>
                    date('Y-m-d H:i:s')
                ]
            );


        $this->log
            ->registrar(
                $empresaId,
                $usuarioId,
                'CONTABILIDADE',
                'FECHAR_EXERCICIO',
                'Fechou o exercício contabilístico ID '
                    . $exercicioId
                    . '.',
                $exercicioId
            );
    }

    // ==================================================
    // PODE FECHAR
    // ==================================================

    private function podeFecharDiagnostico(
        array $exercicio,
        int $pendentes,
        bool $contaConfigurada,
        bool $diarioConfigurado,
        ?array $lancamentoEncerramento
    ): bool {

        $status =
            $this->normalizarTexto(
                (string)(
                    $exercicio['status_nome']
                    ?? ''
                )
            );

        $aberto =
            in_array(
                $status,
                [
                    'aberto',
                    'activo',
                    'ativo'
                ],
                true
            );

        return (
            $aberto
            && empty($exercicio['encerrado_em'])
            && $pendentes === 0
            && $contaConfigurada
            && $diarioConfigurado
            && $lancamentoEncerramento === null
        );
    }

    // ==================================================
    // VALIDAÇÕES GERAIS
    // ==================================================

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para o fecho do exercício.'
            );
        }
    }

    private function validarUtilizador(
        int $usuarioId
    ): void {

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido para o fecho do exercício.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM usuarios

            WHERE id = :id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $usuarioId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Utilizador responsável pelo fecho não encontrado.'
            );
        }
    }

    private function normalizarTexto(
        string $texto
    ): string {

        return mb_strtolower(
            trim($texto)
        );
    }
}
