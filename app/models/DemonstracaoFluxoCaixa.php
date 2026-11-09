<?php

declare(strict_types=1);

require_once __DIR__
    . '/../services/ContabilidadeService.php';

final class DemonstracaoFluxoCaixa
{
    private PDO $pdo;

    private ContabilidadeService $contabilidade;

    private const CATEGORIAS_VALIDAS = [
        'OPERACIONAL',
        'INVESTIMENTO',
        'FINANCIAMENTO'
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->contabilidade =
            new ContabilidadeService($pdo);
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        return [
            'planos_contas' =>
            $this->listarPlanosContas(
                $empresaId
            ),

            'filiais' =>
            $this->listarFiliais(
                $empresaId
            ),

            'exercicios' =>
            $this->listarExercicios(
                $empresaId
            ),

            'periodos' =>
            $this->listarPeriodos(
                $empresaId
            ),

            'classificacao' =>
            $this->resumoClassificacaoContas(
                $empresaId
            )
        ];
    }

    // ==================================================
    // GERAR DEMONSTRAÇÃO
    // ==================================================

    public function gerar(
        int $empresaId,
        int $planoContasId,
        string $dataInicial,
        string $dataFinal,
        ?int $filialId = null,
        ?int $exercicioId = null
    ): array {

        $this->validarFiltros(
            $empresaId,
            $planoContasId,
            $dataInicial,
            $dataFinal,
            $filialId,
            $exercicioId
        );

        $dataInicial =
            $this->normalizarData(
                $dataInicial
            );

        $dataFinal =
            $this->normalizarData(
                $dataFinal
            );

        /*
         * O dia anterior à data inicial é utilizado
         * para calcular o saldo inicial.
         */

        $statusContabilizadoId =
            $this->contabilidade
            ->statusLancamentoId(
                'Contabilizado'
            );

        $saldoInicial =
            $this->calcularSaldoInicialPeriodo(
                $empresaId,
                $planoContasId,
                $dataInicial,
                $statusContabilizadoId,
                $filialId,
                $exercicioId
            );

        $saldoFinalContabilistico =
            $this->calcularSaldoDisponibilidades(
                $empresaId,
                $planoContasId,
                $dataFinal,
                $statusContabilizadoId,
                $filialId,
                $exercicioId,
                true
            );

        $linhas =
            $this->buscarMovimentosPeriodo(
                $empresaId,
                $planoContasId,
                $dataInicial,
                $dataFinal,
                $statusContabilizadoId,
                $filialId,
                $exercicioId
            );

        $processamento =
            $this->processarLancamentos(
                $linhas
            );

        $operacional =
            $this->finalizarCategoria(
                $processamento['OPERACIONAL']
            );

        $investimento =
            $this->finalizarCategoria(
                $processamento['INVESTIMENTO']
            );

        $financiamento =
            $this->finalizarCategoria(
                $processamento['FINANCIAMENTO']
            );

        $fluxoOperacional =
            (float)$operacional['fluxo_liquido'];

        $fluxoInvestimento =
            (float)$investimento['fluxo_liquido'];

        $fluxoFinanciamento =
            (float)$financiamento['fluxo_liquido'];

        $variacaoLiquida =
            round(
                $fluxoOperacional
                    + $fluxoInvestimento
                    + $fluxoFinanciamento,
                2
            );

        $saldoFinalCalculado =
            round(
                $saldoInicial
                    + $variacaoLiquida,
                2
            );

        $diferencaConferencia =
            round(
                $saldoFinalContabilistico
                    - $saldoFinalCalculado,
                2
            );

        return [
            'filtros' => [
                'empresa_id' =>
                $empresaId,

                'plano_contas_id' =>
                $planoContasId,

                'filial_id' =>
                $filialId,

                'exercicio_id' =>
                $exercicioId,

                'data_inicial' =>
                $dataInicial,

                'data_final' =>
                $dataFinal
            ],

            'resumo' => [
                'saldo_inicial' =>
                $saldoInicial,

                'fluxo_operacional' =>
                $fluxoOperacional,

                'fluxo_investimento' =>
                $fluxoInvestimento,

                'fluxo_financiamento' =>
                $fluxoFinanciamento,

                'variacao_liquida' =>
                $variacaoLiquida,

                'saldo_final_calculado' =>
                $saldoFinalCalculado,

                'saldo_final_contabilistico' =>
                $saldoFinalContabilistico,

                'diferenca_conferencia' =>
                $diferencaConferencia,

                'conferido' =>
                abs(
                    $diferencaConferencia
                ) <= 0.009
            ],

            'atividades' => [
                'operacional' =>
                $operacional,

                'investimento' =>
                $investimento,

                'financiamento' =>
                $financiamento
            ],

            'movimentos_nao_classificados' =>
            $processamento['NAO_CLASSIFICADO'],

            'transferencias_internas' =>
            $processamento['TRANSFERENCIAS'],

            'estatisticas' => [
                'quantidade_lancamentos' =>
                $processamento['quantidade_lancamentos'],

                'quantidade_movimentos_classificados' =>
                $processamento['quantidade_classificados'],

                'quantidade_nao_classificados' =>
                count(
                    $processamento['NAO_CLASSIFICADO']
                ),

                'quantidade_transferencias_internas' =>
                count(
                    $processamento['TRANSFERENCIAS']
                )
            ]
        ];
    }

    // ==================================================
    // PROCESSAR LANÇAMENTOS
    // ==================================================

    private function processarLancamentos(
        array $linhas
    ): array {

        $resultado = [
            'OPERACIONAL' =>
            $this->estruturaCategoria(),

            'INVESTIMENTO' =>
            $this->estruturaCategoria(),

            'FINANCIAMENTO' =>
            $this->estruturaCategoria(),

            'NAO_CLASSIFICADO' =>
            [],

            'TRANSFERENCIAS' =>
            [],

            'quantidade_lancamentos' =>
            0,

            'quantidade_classificados' =>
            0
        ];

        $lancamentos =
            [];

        foreach ($linhas as $linha) {

            $lancamentoId =
                (int)$linha['lancamento_id'];

            if (
                !isset(
                    $lancamentos[$lancamentoId]
                )
            ) {
                $lancamentos[$lancamentoId] = [
                    'cabecalho' => [
                        'id' =>
                        $lancamentoId,

                        'numero' =>
                        $linha['numero_lancamento'],

                        'numero_documento' =>
                        $linha['numero_documento'],

                        'referencia' =>
                        $linha['referencia'],

                        'data_lancamento' =>
                        $linha['data_lancamento'],

                        'descricao' =>
                        $linha['descricao_lancamento'],

                        'origem' =>
                        $linha['origem'],

                        'origem_id' =>
                        $linha['origem_id'],

                        'filial_id' =>
                        $linha['filial_id']
                    ],

                    'itens' =>
                    []
                ];
            }

            $lancamentos[$lancamentoId]['itens'][] = $linha;
        }

        foreach (
            $lancamentos
            as $lancamento
        ) {
            $resultado['quantidade_lancamentos']++;

            $this->processarLancamento(
                $lancamento,
                $resultado
            );
        }

        return $resultado;
    }

    // ==================================================
    // PROCESSAR UM LANÇAMENTO
    // ==================================================

    private function processarLancamento(
        array $lancamento,
        array &$resultado
    ): void {

        $itensDisponibilidade =
            [];

        $itensContrapartida =
            [];

        foreach (
            $lancamento['itens']
            as $item
        ) {
            if (
                $this->itemDisponibilidade(
                    $item
                )
            ) {
                $itensDisponibilidade[] =
                    $item;
            } else {
                $itensContrapartida[] =
                    $item;
            }
        }

        /*
         * Sem Caixa/Banco não existe fluxo monetário.
         */
        if ($itensDisponibilidade === []) {
            return;
        }

        /*
         * Se todos os itens forem de Caixa/Banco,
         * trata-se de transferência interna.
         */
        if ($itensContrapartida === []) {

            $movimentoCaixa =
                $this->somarMovimentoDisponibilidade(
                    $itensDisponibilidade
                );

            $resultado['TRANSFERENCIAS'][] = [
                'lancamento_id' =>
                $lancamento['cabecalho']['id'],

                'data' =>
                $lancamento['cabecalho']['data_lancamento'],

                'numero' =>
                $lancamento['cabecalho']['numero'],

                'documento' =>
                $lancamento['cabecalho']['numero_documento'],

                'descricao' =>
                $lancamento['cabecalho']['descricao'],

                'valor' =>
                abs($movimentoCaixa)
            ];

            return;
        }

        /*
         * Movimento positivo:
         * aumento de Caixa/Banco (entrada).
         *
         * Movimento negativo:
         * redução de Caixa/Banco (saída).
         */
        $movimentoCaixa =
            $this->somarMovimentoDisponibilidade(
                $itensDisponibilidade
            );

        /*
         * Um lançamento pode possuir várias contas
         * de disponibilidades. Se o valor líquido for zero,
         * houve apenas transferência interna.
         */
        if (abs($movimentoCaixa) <= 0.009) {

            $resultado['TRANSFERENCIAS'][] = [
                'lancamento_id' =>
                $lancamento['cabecalho']['id'],

                'data' =>
                $lancamento['cabecalho']['data_lancamento'],

                'numero' =>
                $lancamento['cabecalho']['numero'],

                'documento' =>
                $lancamento['cabecalho']['numero_documento'],

                'descricao' =>
                $lancamento['cabecalho']['descricao'],

                'valor' =>
                0
            ];

            return;
        }

        $basesCategorias =
            [];

        $itensNaoClassificados =
            [];

        foreach (
            $itensContrapartida
            as $item
        ) {
            $categoria =
                strtoupper(
                    trim(
                        (string)(
                            $item['fluxo_caixa_categoria'] ?? 'NENHUM'
                        )
                    )
                );

            $valorItem =
                abs(
                    round(
                        (float)$item['debito']
                            - (float)$item['credito'],
                        2
                    )
                );

            if ($valorItem <= 0.009) {
                continue;
            }

            if (
                !in_array(
                    $categoria,
                    self::CATEGORIAS_VALIDAS,
                    true
                )
            ) {
                $itensNaoClassificados[] =
                    $item;

                continue;
            }

            if (
                !isset(
                    $basesCategorias[$categoria]
                )
            ) {
                $basesCategorias[$categoria] = [
                    'valor' =>
                    0.0,

                    'descricao' =>
                    $this->descricaoFluxo(
                        $item
                    ),

                    'contas' =>
                    []
                ];
            }

            $basesCategorias[$categoria]['valor'] +=
                $valorItem;

            $basesCategorias[$categoria]['contas'][] = [
                'conta_id' =>
                (int)$item['conta_contabil_id'],

                'codigo' =>
                $item['conta_codigo'],

                'nome' =>
                $item['conta_nome'],

                'valor_base' =>
                $valorItem
            ];
        }

        if ($basesCategorias === []) {

            $resultado['NAO_CLASSIFICADO'][] = [
                'lancamento_id' =>
                $lancamento['cabecalho']['id'],

                'data' =>
                $lancamento['cabecalho']['data_lancamento'],

                'numero' =>
                $lancamento['cabecalho']['numero'],

                'documento' =>
                $lancamento['cabecalho']['numero_documento'],

                'descricao' =>
                $lancamento['cabecalho']['descricao'],

                'origem' =>
                $lancamento['cabecalho']['origem'],

                'valor' =>
                abs($movimentoCaixa),

                'tipo' =>
                $movimentoCaixa > 0
                    ? 'ENTRADA'
                    : 'SAIDA',

                'contas_sem_classificacao' =>
                array_map(
                    static function (
                        array $item
                    ): array {
                        return [
                            'codigo' =>
                            $item['conta_codigo'],

                            'nome' =>
                            $item['conta_nome'],

                            'categoria' =>
                            $item['fluxo_caixa_categoria']
                        ];
                    },
                    $itensNaoClassificados
                )
            ];

            return;
        }

        $baseTotal =
            array_sum(
                array_column(
                    $basesCategorias,
                    'valor'
                )
            );

        if ($baseTotal <= 0.009) {
            return;
        }

        $valorDistribuido =
            0.0;

        $categorias =
            array_keys(
                $basesCategorias
            );

        $ultimaCategoria =
            end($categorias);

        foreach (
            $basesCategorias
            as $categoria => $dadosCategoria
        ) {
            /*
             * Quando há mais de uma categoria no mesmo
             * lançamento, o fluxo monetário é distribuído
             * proporcionalmente pelas contrapartidas.
             */
            if (
                $categoria ===
                $ultimaCategoria
            ) {
                $valorCategoria =
                    round(
                        abs($movimentoCaixa)
                            - $valorDistribuido,
                        2
                    );
            } else {
                $percentagem =
                    (float)$dadosCategoria['valor'] / $baseTotal;

                $valorCategoria =
                    round(
                        abs($movimentoCaixa)
                            * $percentagem,
                        2
                    );

                $valorDistribuido +=
                    $valorCategoria;
            }

            if ($valorCategoria <= 0.009) {
                continue;
            }

            $tipo =
                $movimentoCaixa > 0
                ? 'ENTRADA'
                : 'SAIDA';

            $descricao =
                $dadosCategoria['descricao'];

            $chaveAgrupamento =
                $this->chaveAgrupamento(
                    $descricao
                );

            if (
                !isset(
                    $resultado[$categoria]['linhas'][$chaveAgrupamento]
                )
            ) {
                $resultado[$categoria]['linhas'][$chaveAgrupamento] = [
                    'descricao' =>
                    $descricao,

                    'entradas' =>
                    0.0,

                    'saidas' =>
                    0.0,

                    'fluxo_liquido' =>
                    0.0,

                    'movimentos' =>
                    []
                ];
            }

            if ($tipo === 'ENTRADA') {
                $resultado[$categoria]['linhas'][$chaveAgrupamento]['entradas'] +=
                    $valorCategoria;
            } else {
                $resultado[$categoria]['linhas'][$chaveAgrupamento]['saidas'] +=
                    $valorCategoria;
            }

            $resultado[$categoria]['linhas'][$chaveAgrupamento]['movimentos'][] = [
                'lancamento_id' =>
                $lancamento['cabecalho']['id'],

                'data' =>
                $lancamento['cabecalho']['data_lancamento'],

                'numero' =>
                $lancamento['cabecalho']['numero'],

                'documento' =>
                $lancamento['cabecalho']['numero_documento'],

                'referencia' =>
                $lancamento['cabecalho']['referencia'],

                'descricao' =>
                $lancamento['cabecalho']['descricao'],

                'origem' =>
                $lancamento['cabecalho']['origem'],

                'tipo' =>
                $tipo,

                'valor' =>
                $valorCategoria,

                'contas_contrapartida' =>
                $dadosCategoria['contas']
            ];

            $resultado['quantidade_classificados']++;
        }
    }

    // ==================================================
    // FINALIZAR CATEGORIA
    // ==================================================

    private function finalizarCategoria(
        array $categoria
    ): array {

        $linhas =
            [];

        $totalEntradas =
            0.0;

        $totalSaidas =
            0.0;

        foreach (
            $categoria['linhas']
            as $linha
        ) {
            $entradas =
                round(
                    (float)$linha['entradas'],
                    2
                );

            $saidas =
                round(
                    (float)$linha['saidas'],
                    2
                );

            $fluxoLiquido =
                round(
                    $entradas - $saidas,
                    2
                );

            $linha['entradas'] =
                $entradas;

            $linha['saidas'] =
                $saidas;

            $linha['fluxo_liquido'] =
                $fluxoLiquido;

            $linhas[] =
                $linha;

            $totalEntradas +=
                $entradas;

            $totalSaidas +=
                $saidas;
        }

        usort(
            $linhas,
            static function (
                array $a,
                array $b
            ): int {
                return strcmp(
                    (string)$a['descricao'],
                    (string)$b['descricao']
                );
            }
        );

        $totalEntradas =
            round($totalEntradas, 2);

        $totalSaidas =
            round($totalSaidas, 2);

        return [
            'linhas' =>
            $linhas,

            'total_entradas' =>
            $totalEntradas,

            'total_saidas' =>
            $totalSaidas,

            'fluxo_liquido' =>
            round(
                $totalEntradas
                    - $totalSaidas,
                2
            )
        ];
    }

    // ==================================================
    // BUSCAR MOVIMENTOS
    // ==================================================

    private function buscarMovimentosPeriodo(
        int $empresaId,
        int $planoContasId,
        string $dataInicial,
        string $dataFinal,
        int $statusContabilizadoId,
        ?int $filialId,
        ?int $exercicioId
    ): array {

        $sql = "
            SELECT
                lancamento.id AS lancamento_id,
                lancamento.numero AS numero_lancamento,
                lancamento.numero_documento,
                lancamento.referencia,
                lancamento.data_lancamento,
                lancamento.descricao
                    AS descricao_lancamento,
                lancamento.origem,
                lancamento.origem_id,
                lancamento.filial_id,

                item.id AS item_id,
                item.conta_contabil_id,
                item.descricao
                    AS descricao_item,
                item.debito,
                item.credito,

                conta.codigo AS conta_codigo,
                conta.nome AS conta_nome,
                conta.grupo AS conta_grupo,
                conta.natureza,
                conta.integracao_codigo,
                conta.conta_caixa,
                conta.conta_banco,
                conta.fluxo_caixa_categoria,
                conta.fluxo_caixa_descricao

            FROM lancamentos_contabilisticos
                lancamento

            INNER JOIN
                lancamento_contabilistico_itens
                item

                ON item.lancamento_id =
                    lancamento.id

            INNER JOIN
                contas_contabilisticas
                conta

                ON conta.id =
                    item.conta_contabil_id

               AND conta.empresa_id =
                    lancamento.empresa_id

            WHERE lancamento.empresa_id =
                :empresa_id

              AND conta.plano_contas_id =
                :plano_contas_id

              AND lancamento.status_id =
                :status_contabilizado_id

              AND lancamento.data_lancamento
                    BETWEEN :data_inicial
                        AND :data_final
              AND UPPER(
                    COALESCE(
                        lancamento.origem,
                        ''
                    )
                ) NOT IN (
                    'ABERTURA',
                    'SALDO_ABERTURA'
                )
                    ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':plano_contas_id' =>
            $planoContasId,

            ':status_contabilizado_id' =>
            $statusContabilizadoId,

            ':data_inicial' =>
            $dataInicial,

            ':data_final' =>
            $dataFinal
        ];

        if ($filialId !== null) {
            $sql .= "
                AND lancamento.filial_id =
                    :filial_id
            ";

            $params[':filial_id'] = $filialId;
        }

        if ($exercicioId !== null) {
            $sql .= "
                AND lancamento.exercicio_id =
                    :exercicio_id
            ";

            $params[':exercicio_id'] = $exercicioId;
        }

        $sql .= "
            AND EXISTS
            (
                SELECT 1

                FROM
                    lancamento_contabilistico_itens
                    item_caixa

                INNER JOIN
                    contas_contabilisticas
                    conta_caixa

                    ON conta_caixa.id =
                        item_caixa.conta_contabil_id

                WHERE item_caixa.lancamento_id =
                    lancamento.id

                  AND conta_caixa.empresa_id =
                    lancamento.empresa_id

                  AND conta_caixa.plano_contas_id =
                    conta.plano_contas_id

                  AND
                  (
                      conta_caixa.conta_caixa = 1

                      OR conta_caixa.conta_banco = 1

                      OR conta_caixa.integracao_codigo
                            IN
                            (
                                'CAIXA',
                                'BANCOS'
                            )
                  )
            )

            ORDER BY
                lancamento.data_lancamento ASC,
                lancamento.id ASC,
                item.id ASC
        ";

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // SALDO INICIAL DO PERÍODO
    // ==================================================

    private function calcularSaldoInicialPeriodo(
        int $empresaId,
        int $planoContasId,
        string $dataInicial,
        int $statusContabilizadoId,
        ?int $filialId,
        ?int $exercicioId
    ): float {

        /*
     * 1. Saldo que já existia até ao dia
     * anterior ao início do período.
     */

        $dataAnterior =
            date(
                'Y-m-d',
                strtotime(
                    $dataInicial
                        . ' -1 day'
                )
            );


        $saldoAnterior =
            $this->calcularSaldoDisponibilidades(
                $empresaId,
                $planoContasId,
                $dataAnterior,
                $statusContabilizadoId,
                $filialId,
                $exercicioId,
                false
            );


        /*
     * 2. Se existir lançamento de abertura
     * precisamente na data inicial, Caixa/Banco
     * desse lançamento pertence à posição inicial
     * e não à movimentação do período.
     */

        $sql = "
        SELECT
            ROUND(
                COALESCE(
                    SUM(
                        item.debito
                        - item.credito
                    ),
                    0
                ),
                2
            )

        FROM lancamento_contabilistico_itens item

        INNER JOIN lancamentos_contabilisticos lancamento
            ON lancamento.id =
                item.lancamento_id

        INNER JOIN contas_contabilisticas conta
            ON conta.id =
                item.conta_contabil_id

           AND conta.empresa_id =
                lancamento.empresa_id

        WHERE lancamento.empresa_id =
            :empresa_id

          AND conta.plano_contas_id =
            :plano_contas_id

          AND lancamento.status_id =
            :status_contabilizado_id

          AND lancamento.data_lancamento =
            :data_inicial

          AND UPPER(
                COALESCE(
                    lancamento.origem,
                    ''
                )
          ) IN (
                'ABERTURA',
                'SALDO_ABERTURA'
          )

          AND (
                conta.conta_caixa = 1

                OR conta.conta_banco = 1

                OR UPPER(
                    COALESCE(
                        conta.integracao_codigo,
                        ''
                    )
                ) IN (
                    'CAIXA',
                    'BANCOS'
                )
          )
    ";


        $params = [
            ':empresa_id' =>
            $empresaId,

            ':plano_contas_id' =>
            $planoContasId,

            ':status_contabilizado_id' =>
            $statusContabilizadoId,

            ':data_inicial' =>
            $dataInicial
        ];


        if ($filialId !== null) {

            $sql .= "
            AND lancamento.filial_id =
                :filial_id
        ";

            $params[':filial_id'] =
                $filialId;
        }


        if ($exercicioId !== null) {

            $sql .= "
            AND lancamento.exercicio_id =
                :exercicio_id
        ";

            $params[':exercicio_id'] =
                $exercicioId;
        }


        $stmt =
            $this->pdo->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        $aberturaDisponibilidades =
            round(
                (float)(
                    $stmt->fetchColumn()
                    ?: 0
                ),
                2
            );


        return round(
            $saldoAnterior
                + $aberturaDisponibilidades,
            2
        );
    }

    // ==================================================
    // SALDO DAS DISPONIBILIDADES
    // ==================================================

    private function calcularSaldoDisponibilidades(
        int $empresaId,
        int $planoContasId,
        string $dataLimite,
        int $statusContabilizadoId,
        ?int $filialId,
        ?int $exercicioId,
        bool $considerarExercicio
    ): float {

        $sql = "
            SELECT
                ROUND(
                    COALESCE(
                        SUM(
                            item.debito
                            - item.credito
                        ),
                        0
                    ),
                    2
                )

            FROM lancamento_contabilistico_itens
                item

            INNER JOIN lancamentos_contabilisticos
                lancamento

                ON lancamento.id =
                    item.lancamento_id

            INNER JOIN contas_contabilisticas
                conta

                ON conta.id =
                    item.conta_contabil_id

               AND conta.empresa_id =
                    lancamento.empresa_id

            WHERE lancamento.empresa_id =
                :empresa_id

              AND conta.plano_contas_id =
                :plano_contas_id

              AND lancamento.status_id =
                :status_contabilizado_id

              AND lancamento.data_lancamento <=
                :data_limite

              AND
              (
                  conta.conta_caixa = 1

                  OR conta.conta_banco = 1

                  OR conta.integracao_codigo IN
                  (
                      'CAIXA',
                      'BANCOS'
                  )
              )
        ";

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':plano_contas_id' =>
            $planoContasId,

            ':status_contabilizado_id' =>
            $statusContabilizadoId,

            ':data_limite' =>
            $dataLimite
        ];

        if ($filialId !== null) {
            $sql .= "
                AND lancamento.filial_id =
                    :filial_id
            ";

            $params[':filial_id'] = $filialId;
        }

        /*
         * Para o saldo inicial não devemos limitar
         * exclusivamente ao exercício atual, pois pode
         * existir lançamento de abertura ou histórico
         * transportado de exercícios anteriores.
         */
        if (
            $considerarExercicio
            && $exercicioId !== null
        ) {
            $sql .= "
                AND lancamento.exercicio_id =
                    :exercicio_id
            ";

            $params[':exercicio_id'] = $exercicioId;
        }

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        return round(
            (float)$stmt->fetchColumn(),
            2
        );
    }

    // ==================================================
    // HELPERS DE PROCESSAMENTO
    // ==================================================

    private function estruturaCategoria(): array
    {
        return [
            'linhas' => []
        ];
    }

    private function itemDisponibilidade(
        array $item
    ): bool {

        return (
            (int)(
                $item['conta_caixa']
                ?? 0
            ) === 1

            || (int)(
                $item['conta_banco']
                ?? 0
            ) === 1

            || in_array(
                strtoupper(
                    trim(
                        (string)(
                            $item['integracao_codigo'] ?? ''
                        )
                    )
                ),
                [
                    'CAIXA',
                    'BANCOS'
                ],
                true
            )
        );
    }

    private function somarMovimentoDisponibilidade(
        array $itens
    ): float {

        $total =
            0.0;

        foreach ($itens as $item) {
            $total +=
                (float)$item['debito']
                - (float)$item['credito'];
        }

        return round(
            $total,
            2
        );
    }

    private function descricaoFluxo(
        array $item
    ): string {

        $descricao =
            trim(
                (string)(
                    $item['fluxo_caixa_descricao'] ?? ''
                )
            );

        if ($descricao !== '') {
            return $descricao;
        }

        $nomeConta =
            trim(
                (string)(
                    $item['conta_nome']
                    ?? ''
                )
            );

        if ($nomeConta !== '') {
            return $nomeConta;
        }

        return 'Outros fluxos de caixa';
    }

    private function chaveAgrupamento(
        string $descricao
    ): string {

        $chave =
            mb_strtolower(
                trim($descricao)
            );

        $chave =
            preg_replace(
                '/\s+/u',
                ' ',
                $chave
            );

        return $chave
            ?: 'outros_fluxos';
    }

    // ==================================================
    // PLANOS DE CONTAS
    // ==================================================

    private function listarPlanosContas(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                plano.id,
                plano.codigo,
                plano.nome,
                plano.descricao

            FROM planos_contas plano

            WHERE plano.empresa_id =
                :empresa_id

            ORDER BY
                plano.nome ASC
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
    // FILIAIS
    // ==================================================

    private function listarFiliais(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                filial.id,
                filial.codigo,
                filial.nome

            FROM filiais filial

            WHERE filial.empresa_id =
                :empresa_id

            ORDER BY
                filial.nome ASC
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
    // EXERCÍCIOS
    // ==================================================

    private function listarExercicios(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                exercicio.id,
                exercicio.codigo,
                exercicio.descricao,
                exercicio.data_inicio,
                exercicio.data_fim,
                exercicio.status_id,

                status.nome AS status_nome

            FROM exercicios_contabilisticos
                exercicio

            INNER JOIN status
                ON status.id =
                    exercicio.status_id

               AND status.tipo =
                    'exercicio_contabilistico'

            WHERE exercicio.empresa_id =
                :empresa_id

            ORDER BY
                exercicio.data_inicio DESC
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
    // PERÍODOS
    // ==================================================

    private function listarPeriodos(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                periodo.id,
                periodo.exercicio_id,
                periodo.numero,
                periodo.nome,
                periodo.data_inicio,
                periodo.data_fim,
                periodo.status_id,

                status.nome AS status_nome

            FROM periodos_contabilisticos
                periodo

            INNER JOIN status
                ON status.id =
                    periodo.status_id

               AND status.tipo =
                    'periodo_contabilistico'

            WHERE periodo.empresa_id =
                :empresa_id

            ORDER BY
                periodo.data_inicio DESC
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
    // RESUMO DA CLASSIFICAÇÃO
    // ==================================================

    private function resumoClassificacaoContas(
        int $empresaId
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT
                fluxo_caixa_categoria
                    AS categoria,

                COUNT(*) AS quantidade

            FROM contas_contabilisticas

            WHERE empresa_id =
                :empresa_id

            GROUP BY
                fluxo_caixa_categoria
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);

        $resultado = [
            'OPERACIONAL' => 0,
            'INVESTIMENTO' => 0,
            'FINANCIAMENTO' => 0,
            'NENHUM' => 0
        ];

        foreach (
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            )
            as $linha
        ) {
            $categoria =
                strtoupper(
                    (string)$linha['categoria']
                );

            if (
                array_key_exists(
                    $categoria,
                    $resultado
                )
            ) {
                $resultado[$categoria] =
                    (int)$linha['quantidade'];
            }
        }

        return $resultado;
    }

    // ==================================================
    // VALIDAÇÕES
    // ==================================================

    private function validarFiltros(
        int $empresaId,
        int $planoContasId,
        string $dataInicial,
        string $dataFinal,
        ?int $filialId,
        ?int $exercicioId
    ): void {

        $this->validarEmpresa(
            $empresaId
        );

        if ($planoContasId <= 0) {
            throw new RuntimeException(
                'Selecione um plano de contas.'
            );
        }

        $inicio =
            $this->normalizarData(
                $dataInicial
            );

        $fim =
            $this->normalizarData(
                $dataFinal
            );

        if ($inicio > $fim) {
            throw new RuntimeException(
                'A data inicial não pode ser posterior à data final.'
            );
        }

        $this->validarPlanoContas(
            $empresaId,
            $planoContasId
        );

        if ($filialId !== null) {
            $this->validarFilial(
                $empresaId,
                $filialId
            );
        }

        if ($exercicioId !== null) {

            $exercicio =
                $this->validarExercicio(
                    $empresaId,
                    $exercicioId
                );

            if (
                $inicio <
                $exercicio['data_inicio']

                || $fim >
                $exercicio['data_fim']
            ) {
                throw new RuntimeException(
                    'O intervalo informado não pertence integralmente ao exercício selecionado.'
                );
            }
        }
    }

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para a Demonstração de Fluxos de Caixa.'
            );
        }
    }

    private function validarPlanoContas(
        int $empresaId,
        int $planoContasId
    ): void {

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM planos_contas

            WHERE id =
                :plano_contas_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':plano_contas_id' =>
            $planoContasId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Plano de contas não encontrado.'
            );
        }
    }

    private function validarFilial(
        int $empresaId,
        int $filialId
    ): void {

        if ($filialId <= 0) {
            throw new RuntimeException(
                'Filial inválida.'
            );
        }

        $stmt = $this->pdo->prepare("
            SELECT id

            FROM filiais

            WHERE id =
                :filial_id

              AND empresa_id =
                :empresa_id

            LIMIT 1
        ");

        $stmt->execute([
            ':filial_id' =>
            $filialId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'Filial não encontrada.'
            );
        }
    }

    private function validarExercicio(
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
                id,
                codigo,
                data_inicio,
                data_fim

            FROM exercicios_contabilisticos

            WHERE id =
                :exercicio_id

              AND empresa_id =
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
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$exercicio) {
            throw new RuntimeException(
                'Exercício contabilístico não encontrado.'
            );
        }

        return $exercicio;
    }

    private function normalizarData(
        string $data
    ): string {

        $data =
            trim($data);

        $objeto =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $data
            );

        $erros =
            DateTimeImmutable::getLastErrors();

        if (
            !$objeto

            || (
                is_array($erros)

                && (
                    $erros['warning_count'] > 0
                    || $erros['error_count'] > 0
                )
            )
        ) {
            throw new RuntimeException(
                'Data inválida. Utilize o formato AAAA-MM-DD.'
            );
        }

        return $objeto->format(
            'Y-m-d'
        );
    }
}
