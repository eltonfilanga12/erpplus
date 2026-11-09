<?php

declare(strict_types=1);

final class AnaliseGastos
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(
        int $empresaId
    ): array {

        return [
            'planos' =>
            $this->listarPlanos(
                $empresaId
            ),

            'filiais' =>
            $this->listarFiliais(
                $empresaId
            ),

            'centros_custo' =>
            $this->listarCentrosCusto(
                $empresaId
            )
        ];
    }


    // ==================================================
    // GERAR ANÁLISE
    // ==================================================

    public function gerar(
        int $empresaId,
        string $dataInicial,
        string $dataFinal,
        ?int $filialId = null,
        ?int $planoContasId = null,
        ?int $centroCustoId = null,
        bool $compararPeriodoAnterior = true
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarData(
            $dataInicial,
            'Data inicial'
        );

        $this->validarData(
            $dataFinal,
            'Data final'
        );

        if ($dataInicial > $dataFinal) {

            throw new RuntimeException(
                'A data inicial não pode ser superior à data final.'
            );
        }


        $statusContabilizado =
            $this->statusLancamento(
                'Contabilizado'
            );


        $atual =
            $this->consultarGastosPeriodo(
                $empresaId,
                $dataInicial,
                $dataFinal,
                $statusContabilizado,
                $filialId,
                $planoContasId,
                $centroCustoId
            );


        $anterior =
            null;


        if ($compararPeriodoAnterior) {

            $periodoAnterior =
                $this->calcularPeriodoAnterior(
                    $dataInicial,
                    $dataFinal
                );


            $anterior =
                $this->consultarGastosPeriodo(
                    $empresaId,
                    $periodoAnterior['data_inicial'],
                    $periodoAnterior['data_final'],
                    $statusContabilizado,
                    $filialId,
                    $planoContasId,
                    $centroCustoId
                );
        }


        return [
            'filtros' => [
                'data_inicial' =>
                $dataInicial,

                'data_final' =>
                $dataFinal,

                'filial_id' =>
                $filialId,

                'plano_contas_id' =>
                $planoContasId,

                'centro_custo_id' =>
                $centroCustoId,

                'comparar_periodo_anterior' =>
                $compararPeriodoAnterior
            ],

            'atual' =>
            $atual,

            'anterior' =>
            $anterior,

            'comparacao' =>
            $this->montarComparacao(
                $atual,
                $anterior
            )
        ];
    }


    // ==================================================
    // CONSULTAR GASTOS DO PERÍODO
    // ==================================================

    private function consultarGastosPeriodo(
        int $empresaId,
        string $dataInicial,
        string $dataFinal,
        int $statusContabilizado,
        ?int $filialId,
        ?int $planoContasId,
        ?int $centroCustoId
    ): array {

        $sql = "
            SELECT
                conta.id,
                conta.codigo,
                conta.nome,
                conta.classe,
                conta.integracao_codigo,

                COUNT(item.id)
                    AS movimentos,

                COALESCE(
                    SUM(item.debito),
                    0
                )
                    AS total_debito,

                COALESCE(
                    SUM(item.credito),
                    0
                )
                    AS total_credito

            FROM lancamento_contabilistico_itens item

            INNER JOIN lancamentos_contabilisticos lancamento
                ON lancamento.id =
                    item.lancamento_id

               AND lancamento.empresa_id =
                    :empresa_id

               AND lancamento.status_id =
                    :status_contabilizado

               AND lancamento.data_lancamento
                    BETWEEN
                        :data_inicial
                    AND
                        :data_final

            INNER JOIN contas_contabilisticas conta
                ON conta.id =
                    item.conta_contabil_id

               AND conta.empresa_id =
                    lancamento.empresa_id

            WHERE conta.grupo =
                'GASTO'

              AND conta.tipo =
                'ANALITICA'

              AND conta.aceita_lancamento =
                1
        ";


        $params = [
            ':empresa_id' =>
            $empresaId,

            ':status_contabilizado' =>
            $statusContabilizado,

            ':data_inicial' =>
            $dataInicial,

            ':data_final' =>
            $dataFinal
        ];


        if ($filialId !== null) {

            $sql .= "
                AND (
                    lancamento.filial_id = :filial_id
                    OR lancamento.filial_id IS NULL
                )
            ";

            $params[':filial_id'] =
                $filialId;
        }


        if ($planoContasId !== null) {

            $sql .= "
                AND conta.plano_contas_id =
                    :plano_contas_id
            ";

            $params[':plano_contas_id'] =
                $planoContasId;
        }


        if ($centroCustoId !== null) {

            $sql .= "
                AND item.centro_custo_id =
                    :centro_custo_id
            ";

            $params[':centro_custo_id'] =
                $centroCustoId;
        }


        $sql .= "
            GROUP BY
                conta.id,
                conta.codigo,
                conta.nome,
                conta.classe,
                conta.integracao_codigo

            HAVING
                ABS(
                    SUM(item.debito)
                    -
                    SUM(item.credito)
                ) > 0.009

            ORDER BY
                conta.codigo ASC
        ";


        $stmt =
            $this->pdo->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        $linhas =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        return $this->montarResumoGastos(
            $linhas
        );
    }


    // ==================================================
    // MONTAR RESUMO
    // ==================================================

    private function montarResumoGastos(
        array $linhas
    ): array {

        $detalhes = [];

        $totalGastos =
            0.0;

        $custoInventarios =
            0.0;

        $gastosPessoal =
            0.0;

        $fornecimentosServicos =
            0.0;

        $imparidades =
            0.0;

        $amortizacoes =
            0.0;

        $provisoes =
            0.0;

        $perdasJustoValor =
            0.0;

        $impostosTaxas =
            0.0;

        $outrosOperacionais =
            0.0;

        $gastosFinanceiros =
            0.0;

        $outrosGastos =
            0.0;


        foreach ($linhas as $linha) {

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


            $valor =
                round(
                    $debito
                        - $credito,
                    2
                );


            if (
                abs(
                    $valor
                ) <= 0.009
            ) {
                continue;
            }


            $codigo =
                trim(
                    (string)$linha['codigo']
                );


            $categoria =
                $this->categoriaPorCodigo(
                    $codigo
                );


            $item = [
                'id' =>
                (int)$linha['id'],

                'codigo' =>
                $codigo,

                'nome' =>
                (string)$linha['nome'],

                'categoria' =>
                $categoria,

                'movimentos' =>
                (int)$linha['movimentos'],

                'total_debito' =>
                $debito,

                'total_credito' =>
                $credito,

                'valor' =>
                $valor
            ];


            $detalhes[] =
                $item;


            $totalGastos +=
                $valor;


            switch ($categoria) {

                case 'CUSTO_INVENTARIOS':

                    $custoInventarios +=
                        $valor;

                    break;


                case 'PESSOAL':

                    $gastosPessoal +=
                        $valor;

                    break;


                case 'FORNECIMENTOS_SERVICOS':

                    $fornecimentosServicos +=
                        $valor;

                    break;


                case 'IMPARIDADES':

                    $imparidades +=
                        $valor;

                    break;


                case 'AMORTIZACOES':

                    $amortizacoes +=
                        $valor;

                    break;


                case 'PROVISOES':

                    $provisoes +=
                        $valor;

                    break;


                case 'JUSTO_VALOR':

                    $perdasJustoValor +=
                        $valor;

                    break;


                case 'IMPOSTOS_TAXAS':

                    $impostosTaxas +=
                        $valor;

                    break;


                case 'OUTROS_OPERACIONAIS':

                    $outrosOperacionais +=
                        $valor;

                    break;


                case 'FINANCEIROS':

                    $gastosFinanceiros +=
                        $valor;

                    break;


                default:

                    $outrosGastos +=
                        $valor;

                    break;
            }
        }


        usort(
            $detalhes,
            static function (
                array $a,
                array $b
            ): int {

                return
                    abs(
                        (float)$b['valor']
                    )
                    <=>
                    abs(
                        (float)$a['valor']
                    );
            }
        );


        $maiorGasto =
            $detalhes[0]
            ?? null;


        return [
            'total_gastos' =>
            round(
                $totalGastos,
                2
            ),

            'custo_inventarios' =>
            round(
                $custoInventarios,
                2
            ),

            /*
     * Mantemos também este nome por
     * compatibilidade com o JS atual.
     */
            'custo_mercadorias' =>
            round(
                $custoInventarios,
                2
            ),

            'gastos_pessoal' =>
            round(
                $gastosPessoal,
                2
            ),

            'fornecimentos_servicos' =>
            round(
                $fornecimentosServicos,
                2
            ),

            'imparidades' =>
            round(
                $imparidades,
                2
            ),

            'amortizacoes' =>
            round(
                $amortizacoes,
                2
            ),

            'provisoes' =>
            round(
                $provisoes,
                2
            ),

            'perdas_justo_valor' =>
            round(
                $perdasJustoValor,
                2
            ),

            'impostos_taxas' =>
            round(
                $impostosTaxas,
                2
            ),

            'outros_operacionais' =>
            round(
                $outrosOperacionais,
                2
            ),

            'gastos_financeiros' =>
            round(
                $gastosFinanceiros,
                2
            ),

            'outros_gastos' =>
            round(
                $outrosGastos,
                2
            ),

            'maior_gasto' =>
            $maiorGasto,

            'detalhes' =>
            $detalhes
        ];
    }


    // ==================================================
    // CATEGORIZAR CONTA
    // ==================================================

    private function categoriaPorCodigo(
        string $codigo
    ): string {

        $codigo =
            trim(
                $codigo
            );


        if (
            str_starts_with(
                $codigo,
                '6.1'
            )
        ) {

            return
                'CUSTO_INVENTARIOS';
        }


        if (
            str_starts_with(
                $codigo,
                '6.2'
            )
        ) {

            return
                'PESSOAL';
        }


        if (
            str_starts_with(
                $codigo,
                '6.3'
            )
        ) {

            return
                'FORNECIMENTOS_SERVICOS';
        }


        if (
            str_starts_with(
                $codigo,
                '6.4'
            )
        ) {

            return
                'IMPARIDADES';
        }


        if (
            str_starts_with(
                $codigo,
                '6.5'
            )
        ) {

            return
                'AMORTIZACOES';
        }


        if (
            str_starts_with(
                $codigo,
                '6.6'
            )
        ) {

            return
                'PROVISOES';
        }


        if (
            str_starts_with(
                $codigo,
                '6.7'
            )
        ) {

            return
                'JUSTO_VALOR';
        }


        if (
            str_starts_with(
                $codigo,
                '6.8'
            )
        ) {

            /*
         * Dentro de 6.8 destacamos impostos
         * e taxas para análise própria.
         */
            if (
                str_starts_with(
                    $codigo,
                    '6.8.2'
                )
            ) {

                return
                    'IMPOSTOS_TAXAS';
            }


            return
                'OUTROS_OPERACIONAIS';
        }


        if (
            str_starts_with(
                $codigo,
                '6.9'
            )
        ) {

            return
                'FINANCEIROS';
        }


        return
            'OUTROS';
    }


    // ==================================================
    // COMPARAÇÃO
    // ==================================================

    private function montarComparacao(
        array $atual,
        ?array $anterior
    ): array {

        if ($anterior === null) {

            return [
                'disponivel' =>
                false
            ];
        }


        $atualTotal =
            round(
                (float)(
                    $atual['total_gastos']
                    ?? 0
                ),
                2
            );


        $anteriorTotal =
            round(
                (float)(
                    $anterior['total_gastos']
                    ?? 0
                ),
                2
            );


        $variacaoValor =
            round(
                $atualTotal
                    - $anteriorTotal,
                2
            );


        $variacaoPercentual =
            null;


        if (
            abs(
                $anteriorTotal
            ) > 0.009
        ) {

            $variacaoPercentual =
                round(
                    (
                        $variacaoValor
                        / $anteriorTotal
                    ) * 100,
                    2
                );
        }


        return [
            'disponivel' =>
            true,

            'atual' =>
            $atualTotal,

            'anterior' =>
            $anteriorTotal,

            'variacao_valor' =>
            $variacaoValor,

            'variacao_percentual' =>
            $variacaoPercentual
        ];
    }


    // ==================================================
    // PERÍODO ANTERIOR
    // ==================================================

    private function calcularPeriodoAnterior(
        string $dataInicial,
        string $dataFinal
    ): array {

        $inicio =
            new DateTimeImmutable(
                $dataInicial
            );

        $fim =
            new DateTimeImmutable(
                $dataFinal
            );


        $dias =
            (int)$inicio
                ->diff(
                    $fim
                )
                ->format('%a')
            + 1;


        $fimAnterior =
            $inicio
            ->modify(
                '-1 day'
            );


        $inicioAnterior =
            $fimAnterior
            ->modify(
                '-'
                    . (
                        $dias - 1
                    )
                    . ' days'
            );


        return [
            'data_inicial' =>
            $inicioAnterior
                ->format(
                    'Y-m-d'
                ),

            'data_final' =>
            $fimAnterior
                ->format(
                    'Y-m-d'
                )
        ];
    }


    // ==================================================
    // PLANOS
    // ==================================================

    public function listarPlanos(
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    id,
                    codigo,
                    nome,
                    padrao

                FROM planos_contas

                WHERE empresa_id =
                    :empresa_id

                ORDER BY
                    padrao DESC,
                    codigo ASC
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

    public function listarFiliais(
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
                SELECT
                    id,
                    codigo,
                    nome

                FROM filiais

                WHERE empresa_id =
                    :empresa_id

                ORDER BY
                    nome ASC
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
    // CENTROS DE CUSTO
    // ==================================================

    public function listarCentrosCusto(
        int $empresaId
    ): array {

        $stmt =
            $this->pdo->prepare("
            SELECT
                cc.id,
                cc.codigo,
                cc.nome,
                cc.filial_id,
                cc.tipo,
                cc.orcamento_mensal,
                cc.status_id

            FROM centros_custo cc

            WHERE cc.empresa_id =
                :empresa_id

            ORDER BY
                cc.nome ASC
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
    // STATUS DO LANÇAMENTO
    // ==================================================

    private function statusLancamento(
        string $nome
    ): int {

        $stmt =
            $this->pdo->prepare("
                SELECT id

                FROM status

                WHERE tipo =
                    'lancamento_contabilistico'

                  AND LOWER(nome) =
                    LOWER(:nome)

                LIMIT 1
            ");


        $stmt->execute([
            ':nome' =>
            $nome
        ]);


        $id =
            (int)$stmt
                ->fetchColumn();


        if ($id <= 0) {

            throw new RuntimeException(
                "Status '{$nome}' não encontrado."
            );
        }


        return $id;
    }


    // ==================================================
    // VALIDAÇÕES
    // ==================================================

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {

            throw new RuntimeException(
                'Empresa inválida.'
            );
        }
    }


    private function validarData(
        string $data,
        string $campo
    ): void {

        $objeto =
            DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $data
            );


        if (
            !$objeto
            || $objeto->format(
                'Y-m-d'
            ) !== $data
        ) {

            throw new RuntimeException(
                "{$campo} inválida."
            );
        }
    }
}
