<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';

require_once __DIR__
    . '/../services/ContabilidadeService.php';

final class AberturaExercicio
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

        $this->validarEmpresa(
            $empresaId
        );

        $stmt = $this->pdo->prepare("
            SELECT
                exercicio.id,
                exercicio.codigo,
                exercicio.descricao,
                exercicio.data_inicio,
                exercicio.data_fim,
                exercicio.status_id,
                exercicio.encerrado_em,

                status.nome AS status_nome,

                (
                    SELECT COUNT(*)

                    FROM lancamentos_contabilisticos lancamento

                    WHERE lancamento.empresa_id =
                        exercicio.empresa_id

                      AND lancamento.exercicio_id =
                        exercicio.id

                      AND lancamento.origem =
                        'ABERTURA'
                ) AS quantidade_aberturas

            FROM exercicios_contabilisticos exercicio

            INNER JOIN status
                ON status.id =
                    exercicio.status_id

               AND status.tipo =
                    'exercicio_contabilistico'

            WHERE exercicio.empresa_id =
                :empresa_id

            ORDER BY
                exercicio.data_inicio DESC,
                exercicio.id DESC
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // DIAGNOSTICAR ABERTURA
    // ==================================================

    public function diagnosticar(
        int $empresaId,
        int $exercicioOrigemId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $origem =
            $this->buscarExercicio(
                $empresaId,
                $exercicioOrigemId
            );

        $destino =
            $this->buscarExercicioSeguinte(
                $empresaId,
                $origem
            );

        $saldos =
            $this->calcularSaldosPatrimoniais(
                $empresaId,
                $exercicioOrigemId
            );

        $aberturaExistente =
            $this->buscarLancamentoAbertura(
                $empresaId,
                (int)$destino['id']
            );

        $contaResultadosConfigurada =
            $this->existeContaIntegracao(
                $empresaId,
                'RESULTADOS_TRANSITADOS'
            );

        $diarioConfigurado =
            $this->existeDiarioAbertura(
                $empresaId
            );

        $origemEncerrada =
            $this->exercicioEncerrado(
                $origem
            );

        $destinoAberto =
            $this->exercicioAberto(
                $destino
            );

        return [
            'exercicio_origem' =>
            $origem,

            'exercicio_destino' =>
            $destino,

            'saldos' => [
                'contas' =>
                $saldos['contas'],

                'quantidade_contas' =>
                count(
                    $saldos['contas']
                ),

                'total_debito' =>
                $saldos['total_debito'],

                'total_credito' =>
                $saldos['total_credito'],

                'diferenca' =>
                $saldos['diferenca'],

                'resultado_transportar' =>
                $saldos['resultado_transportar']
            ],

            'configuracao' => [
                'conta_resultados_transitados' =>
                $contaResultadosConfigurada,

                'diario_abertura' =>
                $diarioConfigurado
            ],

            'abertura_existente' =>
            $aberturaExistente,

            'pode_abrir' => (
                $origemEncerrada
                && $destinoAberto
                && $contaResultadosConfigurada
                && $diarioConfigurado
                && $aberturaExistente === null
                && abs(
                    (float)$saldos['diferenca']
                ) <= 0.009
            )
        ];
    }

    // ==================================================
    // GERAR ABERTURA
    // ==================================================

    public function abrir(
        int $empresaId,
        int $exercicioOrigemId,
        int $usuarioId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarUtilizador(
            $usuarioId
        );

        $this->pdo->beginTransaction();

        try {

            $origem =
                $this->bloquearExercicio(
                    $empresaId,
                    $exercicioOrigemId
                );

            $this->validarExercicioEncerrado(
                $origem
            );

            $destino =
                $this->buscarExercicioSeguinte(
                    $empresaId,
                    $origem,
                    true
                );

            $this->validarExercicioDestinoAberto(
                $destino
            );

            $existente =
                $this->buscarLancamentoAbertura(
                    $empresaId,
                    (int)$destino['id']
                );

            if ($existente !== null) {
                throw new RuntimeException(
                    'O exercício seguinte já possui lançamento de abertura.'
                );
            }

            /*
             * A data de abertura é o primeiro dia
             * do novo exercício.
             */
            $dataAbertura =
                (string)$destino['data_inicio'];

            $contexto =
                $this->contabilidade
                ->contextoData(
                    $empresaId,
                    $dataAbertura
                );

            if (
                (int)$contexto['exercicio_id']
                !== (int)$destino['id']
            ) {
                throw new RuntimeException(
                    'O contexto contabilístico não corresponde ao exercício de destino.'
                );
            }

            $diario =
                $this->obterDiarioAbertura(
                    $empresaId
                );

            $contaResultadosTransitados =
                $this->contabilidade
                ->contaIntegracao(
                    $empresaId,
                    'RESULTADOS_TRANSITADOS'
                );

            $saldos =
                $this->calcularSaldosPatrimoniais(
                    $empresaId,
                    $exercicioOrigemId
                );

            if (
                abs(
                    (float)$saldos['diferenca']
                ) > 0.009
            ) {
                throw new RuntimeException(
                    'Os saldos patrimoniais do exercício encerrado estão desequilibrados.'
                );
            }

            $itens =
                $this->montarItensAbertura(
                    $saldos['contas'],
                    (int)$contaResultadosTransitados['id'],
                    (string)$origem['codigo'],
                    (string)$destino['codigo']
                );

            $lancamentoId =
                null;

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
                            (int)$destino['id'],

                            'periodo_id' =>
                            (int)$contexto['periodo_id'],

                            'diario_id' =>
                            (int)$diario['id'],

                            'numero_documento' =>
                            'ABR-'
                                . $destino['codigo'],

                            'referencia' =>
                            'ABERTURA-'
                                . $destino['id'],

                            'data_lancamento' =>
                            $dataAbertura,

                            'data_documento' =>
                            $dataAbertura,

                            'descricao' =>
                            'Abertura do exercício contabilístico '
                                . $destino['codigo'],

                            'origem' =>
                            'ABERTURA',

                            /*
                                 * A origem identifica o exercício
                                 * que recebe os saldos.
                                 */
                            'origem_id' =>
                            (int)$destino['id'],

                            'usuario_id' =>
                            $usuarioId
                        ],
                        $itens
                    );
            }

            $this->registarAuditoria(
                $empresaId,
                $usuarioId,
                (int)$origem['id'],
                (int)$destino['id'],
                $lancamentoId,
                $saldos
            );

            $this->pdo->commit();

            return [
                'exercicio_origem_id' =>
                (int)$origem['id'],

                'exercicio_origem_codigo' =>
                (string)$origem['codigo'],

                'exercicio_destino_id' =>
                (int)$destino['id'],

                'exercicio_destino_codigo' =>
                (string)$destino['codigo'],

                'data_abertura' =>
                $dataAbertura,

                'lancamento_id' =>
                $lancamentoId,

                'quantidade_contas' =>
                count($saldos['contas']),

                'total_debito' =>
                $saldos['total_debito'],

                'total_credito' =>
                $saldos['total_credito'],

                'resultado_transportado' =>
                $saldos['resultado_transportar']
            ];
        } catch (Throwable $e) {

            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // ==================================================
    // BUSCAR EXERCÍCIO
    // ==================================================

    private function buscarExercicio(
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
    // BLOQUEAR EXERCÍCIO
    // ==================================================

    private function bloquearExercicio(
        int $empresaId,
        int $exercicioId
    ): array {

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
    // BUSCAR EXERCÍCIO SEGUINTE
    // ==================================================

    private function buscarExercicioSeguinte(
        int $empresaId,
        array $origem,
        bool $bloquear = false
    ): array {

        $sql = "
            SELECT
                exercicio.*,

                status.nome AS status_nome

            FROM exercicios_contabilisticos exercicio

            INNER JOIN status
                ON status.id =
                    exercicio.status_id

               AND status.tipo =
                    'exercicio_contabilistico'

            WHERE exercicio.empresa_id =
                :empresa_id

              AND exercicio.data_inicio >
                :data_fim_origem

            ORDER BY
                exercicio.data_inicio ASC,
                exercicio.id ASC

            LIMIT 1
        ";

        if ($bloquear) {
            $sql .= " FOR UPDATE";
        }

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':data_fim_origem' =>
            $origem['data_fim']
        ]);

        $destino =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$destino) {
            throw new RuntimeException(
                'Não existe exercício contabilístico seguinte para receber os saldos de abertura.'
            );
        }

        return $destino;
    }

    // ==================================================
    // CALCULAR SALDOS PATRIMONIAIS
    // ==================================================

    private function calcularSaldosPatrimoniais(
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
                  'ATIVO',
                  'PASSIVO',
                  'CAPITAL_PROPRIO',
                  'RESULTADO'
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

            ORDER BY
                conta.codigo ASC
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
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        $contas =
            [];

        $totalDebito =
            0.0;

        $totalCredito =
            0.0;

        $resultadoTransportar =
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
             * Saldo contabilístico assinado:
             *
             * positivo  = saldo devedor;
             * negativo  = saldo credor.
             */
            $saldo =
                round(
                    $debito - $credito,
                    2
                );

            if (abs($saldo) <= 0.009) {
                continue;
            }

            $conta = [
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

                'saldo' =>
                $saldo
            ];

            $contas[] =
                $conta;

            /*
             * RESULTADO não será aberto na mesma conta.
             * Será transferido para RESULTADOS_TRANSITADOS.
             */
            if ($grupo === 'RESULTADO') {

                $resultadoTransportar +=
                    $saldo;

                continue;
            }

            if ($saldo > 0) {
                $totalDebito += $saldo;
            } else {
                $totalCredito += abs($saldo);
            }
        }

        /*
         * O resultado também participa no equilíbrio,
         * mas será lançado noutra conta.
         */
        if ($resultadoTransportar > 0) {
            $totalDebito +=
                $resultadoTransportar;
        } elseif ($resultadoTransportar < 0) {
            $totalCredito +=
                abs($resultadoTransportar);
        }

        $totalDebito =
            round($totalDebito, 2);

        $totalCredito =
            round($totalCredito, 2);

        return [
            'contas' =>
            $contas,

            'total_debito' =>
            $totalDebito,

            'total_credito' =>
            $totalCredito,

            'resultado_transportar' =>
            round(
                $resultadoTransportar,
                2
            ),

            'diferenca' =>
            round(
                $totalDebito
                    - $totalCredito,
                2
            )
        ];
    }

    // ==================================================
    // MONTAR ITENS DA ABERTURA
    // ==================================================

    private function montarItensAbertura(
        array $contas,
        int $contaResultadosTransitadosId,
        string $codigoOrigem,
        string $codigoDestino
    ): array {

        $itens =
            [];

        $resultadoTransportar =
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

            if (
                strtoupper(
                    (string)$conta['grupo']
                ) === 'RESULTADO'
            ) {
                $resultadoTransportar +=
                    $saldo;

                continue;
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
                'Saldo de abertura transportado de '
                    . $codigoOrigem
                    . ' para '
                    . $codigoDestino
                    . ' - '
                    . $conta['codigo']
                    . ' '
                    . $conta['nome'],

                'debito' =>
                $saldo > 0
                    ? $saldo
                    : 0,

                'credito' =>
                $saldo < 0
                    ? abs($saldo)
                    : 0
            ];
        }

        $resultadoTransportar =
            round(
                $resultadoTransportar,
                2
            );

        if (
            abs(
                $resultadoTransportar
            ) > 0.009
        ) {

            $itens[] = [
                'conta_contabil_id' =>
                $contaResultadosTransitadosId,

                'centro_custo_id' =>
                null,

                'cliente_id' =>
                null,

                'fornecedor_id' =>
                null,

                'descricao' =>
                'Resultado transitado do exercício '
                    . $codigoOrigem,

                'debito' =>
                $resultadoTransportar > 0
                    ? $resultadoTransportar
                    : 0,

                'credito' =>
                $resultadoTransportar < 0
                    ? abs(
                        $resultadoTransportar
                    )
                    : 0
            ];
        }

        return $itens;
    }

    // ==================================================
    // BUSCAR ABERTURA EXISTENTE
    // ==================================================

    private function buscarLancamentoAbertura(
        int $empresaId,
        int $exercicioDestinoId
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

              AND lancamento.exercicio_id =
                :exercicio_id

              AND lancamento.origem =
                'ABERTURA'

              AND lancamento.origem_id =
                :origem_id

            LIMIT 1
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':exercicio_id' =>
            $exercicioDestinoId,

            ':origem_id' =>
            $exercicioDestinoId
        ]);

        $dados =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $dados ?: null;
    }

    // ==================================================
    // DIÁRIO DE ABERTURA
    // ==================================================

    private function obterDiarioAbertura(
        int $empresaId
    ): array {

        try {

            return $this->contabilidade
                ->diarioPorTipo(
                    $empresaId,
                    'ABERTURA'
                );
        } catch (Throwable $e) {

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
                    'ABR'

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
                    'Diário de Abertura não configurado.'
                );
            }

            if (
                (int)$diario['numeracao_automatica'] !== 1
            ) {
                throw new RuntimeException(
                    'O Diário de Abertura deve utilizar numeração automática.'
                );
            }

            return $diario;
        }
    }

    private function existeDiarioAbertura(
        int $empresaId
    ): bool {

        try {

            $this->obterDiarioAbertura(
                $empresaId
            );

            return true;
        } catch (Throwable $e) {

            return false;
        }
    }

    // ==================================================
    // CONTA DE RESULTADOS TRANSITADOS
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

    // ==================================================
    // ESTADOS
    // ==================================================

    private function exercicioEncerrado(
        array $exercicio
    ): bool {

        $status =
            $this->normalizarTexto(
                (string)(
                    $exercicio['status_nome']
                    ?? ''
                )
            );

        return (
            in_array(
                $status,
                [
                    'encerrado',
                    'fechado'
                ],
                true
            )
            && !empty($exercicio['encerrado_em'])
        );
    }

    private function exercicioAberto(
        array $exercicio
    ): bool {

        $status =
            $this->normalizarTexto(
                (string)(
                    $exercicio['status_nome']
                    ?? ''
                )
            );

        return (
            in_array(
                $status,
                [
                    'aberto',
                    'activo',
                    'ativo'
                ],
                true
            )
            && empty($exercicio['encerrado_em'])
        );
    }

    private function validarExercicioEncerrado(
        array $exercicio
    ): void {

        if (
            !$this->exercicioEncerrado(
                $exercicio
            )
        ) {
            throw new RuntimeException(
                'O exercício de origem ainda não está encerrado.'
            );
        }
    }

    private function validarExercicioDestinoAberto(
        array $exercicio
    ): void {

        if (
            !$this->exercicioAberto(
                $exercicio
            )
        ) {
            throw new RuntimeException(
                'O exercício seguinte não está aberto.'
            );
        }
    }

    // ==================================================
    // AUDITORIA
    // ==================================================

    private function registarAuditoria(
        int $empresaId,
        int $usuarioId,
        int $exercicioOrigemId,
        int $exercicioDestinoId,
        ?int $lancamentoId,
        array $saldos
    ): void {

        $this->auditoria
            ->insercao(
                $empresaId,
                $usuarioId,
                'exercicios_contabilisticos',
                $exercicioDestinoId,

                [
                    'exercicio_origem_id' =>
                    $exercicioOrigemId,

                    'exercicio_destino_id' =>
                    $exercicioDestinoId,

                    'lancamento_abertura_id' =>
                    $lancamentoId,

                    'total_debito' =>
                    $saldos['total_debito'],

                    'total_credito' =>
                    $saldos['total_credito'],

                    'resultado_transportado' =>
                    $saldos['resultado_transportar'],

                    'aberto_em' =>
                    date('Y-m-d H:i:s')
                ]
            );


        $this->log
            ->registrar(
                $empresaId,
                $usuarioId,
                'CONTABILIDADE',
                'ABRIR_EXERCICIO',
                'Gerou a abertura do exercício contabilístico ID '
                    . $exercicioDestinoId
                    . ' a partir do exercício ID '
                    . $exercicioOrigemId
                    . '.',
                $exercicioDestinoId
            );
    }

    // ==================================================
    // VALIDAÇÕES
    // ==================================================

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para a abertura do exercício.'
            );
        }
    }

    private function validarUtilizador(
        int $usuarioId
    ): void {

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador inválido para a abertura do exercício.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM usuarios

            WHERE id = :id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' =>
            $usuarioId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Utilizador responsável pela abertura não encontrado.'
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
