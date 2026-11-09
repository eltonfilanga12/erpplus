<?php

declare(strict_types=1);

require_once __DIR__
    . '/RelatorioOperacional.php';

require_once __DIR__
    . '/RelatorioFinanceiro.php';


final class Dashboard
{
    private PDO $db;

    private RelatorioOperacional $operacional;

    private RelatorioFinanceiro $financeiro;


    public function __construct(
        PDO $db
    ) {
        $this->db = $db;

        $this->operacional =
            new RelatorioOperacional(
                $db
            );

        $this->financeiro =
            new RelatorioFinanceiro(
                $db
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD COMPLETO
    |--------------------------------------------------------------------------
    */

    public function carregar(
        int $empresaId,
        array $filtros = []
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $filtros =
            $this->normalizarFiltros(
                $filtros
            );


        /*
        |--------------------------------------------------------------------------
        | Operacional
        |--------------------------------------------------------------------------
        */

        $vendas =
            $this->operacional
            ->vendas(
                $empresaId,
                $filtros
            );


        $stock =
            $this->operacional
            ->stock(
                $empresaId,
                $filtros
            );


        $clientes =
            $this->operacional
            ->clientes(
                $empresaId,
                $filtros
            );


        $produtos =
            $this->operacional
            ->produtos(
                $empresaId,
                $filtros
            );


        /*
        |--------------------------------------------------------------------------
        | Financeiro
        |--------------------------------------------------------------------------
        */

        $receitasDespesas =
            $this->financeiro
            ->receitasDespesas(
                $empresaId,
                $filtros
            );


        $contasReceber =
            $this->financeiro
            ->contasReceber(
                $empresaId,
                $filtros
            );


        $contasPagar =
            $this->financeiro
            ->contasPagar(
                $empresaId,
                $filtros
            );


        $fluxoCaixa =
            $this->financeiro
            ->fluxoCaixa(
                $empresaId,
                $filtros
            );


        /*
        |--------------------------------------------------------------------------
        | RH
        |--------------------------------------------------------------------------
        */

        $funcionarios =
            $this->resumoFuncionarios(
                $empresaId,
                $filtros
            );


        /*
        |--------------------------------------------------------------------------
        | Contabilidade
        |--------------------------------------------------------------------------
        */

        $contabilidade =
            $this->resumoContabilistico(
                $empresaId,
                $filtros
            );


        /*
        |--------------------------------------------------------------------------
        | Indicadores
        |--------------------------------------------------------------------------
        */

        $vendasResumo =
            $vendas['resumo']
            ?? [];

        $stockResumo =
            $stock['resumo']
            ?? [];

        $clientesResumo =
            $clientes['resumo']
            ?? [];

        $financeiroResumo =
            $receitasDespesas['resumo']
            ?? [];

        $receberResumo =
            $contasReceber['resumo']
            ?? [];

        $pagarResumo =
            $contasPagar['resumo']
            ?? [];


        /* A mesma fonte contabilística da DRE evita omitir CMV e salários. */
        $resultadoPeriodo =
            $this->resumoResultadoPeriodo(
                $empresaId,
                $filtros
            );

        $receitas =
            (float)(
                $resultadoPeriodo['total_receitas']
                ?? 0
            );

        $despesas =
            (float)(
                $resultadoPeriodo['total_gastos']
                ?? 0
            );


        $resultado =
            $receitas
            - $despesas;

        return [

            'periodo' =>
            $filtros,


            /*
            |--------------------------------------------------------------------------
            | Cards principais
            |--------------------------------------------------------------------------
            */

            'cards' => [

                'vendas' =>
                (float)(
                    $vendasResumo['valor_total']
                    ?? 0
                ),

                'quantidade_vendas' =>
                (int)(
                    $vendasResumo['total_vendas']
                    ?? 0
                ),

                'receitas' =>
                $receitas,

                'despesas' =>
                $despesas,

                'lucro' =>
                $resultado,

                'stock' =>
                (float)(
                    $stockResumo['valor_stock']
                    ?? 0
                ),

                'clientes' =>
                (int)(
                    $clientesResumo['total_clientes']
                    ?? 0
                ),

                'contas_receber' =>
                (float)(
                    $receberResumo['total_pendente']
                    ?? 0
                ),

                'contas_pagar' =>
                (float)(
                    $pagarResumo['total_pendente']
                    ?? 0
                ),

                'funcionarios' =>
                (int)(
                    $funcionarios['total']
                    ?? 0
                )
            ],


            /*
            |--------------------------------------------------------------------------
            | Contabilidade
            |--------------------------------------------------------------------------
            */

            'contabilidade' =>
            $contabilidade,


            /*
            |--------------------------------------------------------------------------
            | RH
            |--------------------------------------------------------------------------
            */

            'rh' =>
            $funcionarios,


            /*
            |--------------------------------------------------------------------------
            | Gráficos
            |--------------------------------------------------------------------------
            */

            'graficos' => [

                'vendas' =>
                $this->graficoVendas(
                    $vendas['registos']
                        ?? []
                ),

                'receitas_despesas' =>
                [
                    'receitas' =>
                    $receitas,

                    'despesas' =>
                    $despesas
                ],

                'fluxo_caixa' =>
                $fluxoCaixa['registos']
                    ?? [],

                'top_produtos' =>
                $this->topProdutos(
                    $produtos['registos']
                        ?? []
                )
            ],


            /*
            |--------------------------------------------------------------------------
            | Dados auxiliares
            |--------------------------------------------------------------------------
            */

            'resumos' => [

                'vendas' =>
                $vendasResumo,

                'stock' =>
                $stockResumo,

                'clientes' =>
                $clientesResumo,

                'financeiro' => [
                    'total_receitas' =>
                    $receitas,

                    'total_despesas' =>
                    $despesas,

                    'resultado' =>
                    $resultado,

                    'margem' =>
                    $receitas > 0
                        ? round(
                            (
                                $resultado
                                / $receitas
                            ) * 100,
                            2
                        )
                        : 0
                ],

                'contas_receber' =>
                $receberResumo,

                'contas_pagar' =>
                $pagarResumo
            ]
        ];
    }

    private function resumoResultadoPeriodo(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'l.empresa_id = :empresa_id',
            "UPPER(s.nome) = 'CONTABILIZADO'",
            "s.tipo = 'lancamento_contabilistico'",
            "cc.grupo IN ('RENDIMENTO', 'GASTO')"
        ];

        $params = [
            ':empresa_id' => $empresaId
        ];

        if (!empty($filtros['data_inicio'])) {

            $where[] =
                'l.data_lancamento >= :data_inicio';

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {

            $where[] =
                'l.data_lancamento <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }

        if (!empty($filtros['filial_id'])) {

            $where[] =
                'l.filial_id = :filial_id';

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }

        $stmt =
            $this->db->prepare(
                "
            SELECT
                COALESCE(
                    SUM(
                        CASE
                            WHEN cc.grupo = 'RENDIMENTO'
                            THEN li.credito - li.debito
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_receitas,
                COALESCE(
                    SUM(
                        CASE
                            WHEN cc.grupo = 'GASTO'
                            THEN li.debito - li.credito
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_gastos
            FROM lancamento_contabilistico_itens li
            INNER JOIN lancamentos_contabilisticos l
                ON l.id = li.lancamento_id
            INNER JOIN contas_contabilisticas cc
                ON cc.id = li.conta_contabil_id
            INNER JOIN status s
                ON s.id = l.status_id
               AND s.tipo = 'lancamento_contabilistico'
            WHERE "
                    . implode(
                        ' AND ',
                        $where
                    )
            );

        $stmt->execute(
            $params
        );

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        ) ?: [
            'total_receitas' => 0,
            'total_gastos' => 0
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RESUMO RH
    |--------------------------------------------------------------------------
    */

    private function resumoFuncionarios(
        int $empresaId,
        array $filtros
    ): array {

        $sql = "
            SELECT
                COUNT(*) AS total
            FROM funcionarios
            WHERE empresa_id = :empresa_id
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (
            !empty($filtros['filial_id'])
        ) {

            $sql .= "
                AND filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }


        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );


        return [
            'total' =>
            (int)$stmt
                ->fetchColumn()
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | RESUMO CONTABILÍSTICO
    |--------------------------------------------------------------------------
    */

    private function resumoContabilistico(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'l.empresa_id = :empresa_id',
            "UPPER(s.nome) = 'CONTABILIZADO'"
        ];

        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (
            !empty($filtros['data_fim'])
        ) {

            $where[] =
                'l.data_lancamento <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        if (
            !empty($filtros['filial_id'])
        ) {

            $where[] =
                'l.filial_id = :filial_id';

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }


        $sql = "
            SELECT
                cc.grupo,

                cc.natureza,

                SUM(
                    li.debito
                ) AS debito,

                SUM(
                    li.credito
                ) AS credito

            FROM lancamento_contabilistico_itens li

            INNER JOIN
                lancamentos_contabilisticos l
                ON l.id =
                    li.lancamento_id

            INNER JOIN
                contas_contabilisticas cc
                ON cc.id =
                    li.conta_contabil_id

            INNER JOIN
                status s
                ON s.id =
                    l.status_id
               AND s.tipo =
                    'lancamento_contabilistico'

            WHERE "
            . implode(
                ' AND ',
                $where
            ) . "

            GROUP BY
                cc.grupo,
                cc.natureza
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );


        $linhas =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $ativo = 0.0;
        $passivo = 0.0;
        $capital = 0.0;
        $rendimentos = 0.0;
        $gastos = 0.0;


        foreach (
            $linhas
            as $linha
        ) {

            $grupo =
                strtoupper(
                    trim(
                        (string)$linha['grupo']
                    )
                );


            $natureza =
                strtoupper(
                    trim(
                        (string)$linha['natureza']
                    )
                );


            $debito =
                (float)$linha['debito'];


            $credito =
                (float)$linha['credito'];


            $saldo =
                $natureza ===
                'CREDORA'

                ? $credito
                - $debito

                : $debito
                - $credito;


            switch ($grupo) {

                case 'ATIVO':

                    $ativo +=
                        $saldo;

                    break;


                case 'PASSIVO':

                    $passivo +=
                        $saldo;

                    break;


                case 'CAPITAL_PROPRIO':

                    $capital +=
                        $saldo;

                    break;


                case 'RESULTADO':
                    break;

                case 'RENDIMENTO':

                    $rendimentos +=
                        $credito - $debito;

                    break;

                case 'GASTO':

                    $gastos +=
                        $debito - $credito;

                    break;
            }
        }

        $resultado =
            $rendimentos
            - $gastos;


        return [

            'ativo' =>
            round(
                $ativo,
                2
            ),

            'passivo' =>
            round(
                $passivo,
                2
            ),

            'capital_contabilizado' =>
            round(
                $capital,
                2
            ),

            'capital_proprio' =>
            round(
                $capital + $resultado,
                2
            ),

            'capital_proprio_total' =>
            round(
                $capital + $resultado,
                2
            ),

            'resultado_liquido' =>
            round(
                $resultado,
                2
            )
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | GRÁFICO DE VENDAS
    |--------------------------------------------------------------------------
    */

    private function graficoVendas(
        array $registos
    ): array {

        $dados = [];


        foreach (
            $registos
            as $registo
        ) {

            if (
                empty($registo['data'])
            ) {
                continue;
            }


            $mes =
                substr(
                    (string)$registo['data'],
                    0,
                    7
                );


            if (
                !isset(
                    $dados[$mes]
                )
            ) {

                $dados[$mes] = [
                    'periodo' =>
                    $mes,

                    'valor' =>
                    0.0,

                    'quantidade' =>
                    0
                ];
            }


            $dados[$mes]['valor'] +=
                (float)(
                    $registo['total']
                    ?? 0
                );


            $dados[$mes]['quantidade']++;
        }


        ksort(
            $dados
        );


        return array_values(
            $dados
        );
    }


    /*
    |--------------------------------------------------------------------------
    | TOP PRODUTOS
    |--------------------------------------------------------------------------
    */

    private function topProdutos(
        array $registos
    ): array {

        usort(
            $registos,
            static function (
                array $a,
                array $b
            ): int {

                return
                    (float)(
                        $b['valor_vendido']
                        ?? 0
                    )
                    <=>
                    (float)(
                        $a['valor_vendido']
                        ?? 0
                    );
            }
        );


        return array_slice(
            $registos,
            0,
            5
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */

    private function normalizarFiltros(
        array $filtros
    ): array {

        $hoje =
            new DateTimeImmutable(
                'today'
            );

        $periodo =
            strtolower(
                trim(
                    (string)(
                        $filtros['periodo']
                        ?? 'mes'
                    )
                )
            );

        switch ($periodo) {

            case 'ano':

                $inicio =
                    $hoje->setDate(
                        (int)$hoje->format('Y'),
                        1,
                        1
                    );

                $fim =
                    $hoje->setDate(
                        (int)$hoje->format('Y'),
                        12,
                        31
                    );

                break;

            case 'trimestre':

                $mes =
                    (int)$hoje->format('n');

                $mesInicial =
                    (int)(
                        floor(
                            ($mes - 1) / 3
                        ) * 3
                    ) + 1;

                $inicio =
                    $hoje->setDate(
                        (int)$hoje->format('Y'),
                        $mesInicial,
                        1
                    );

                $fim =
                    $inicio
                    ->modify('+2 months')
                    ->modify('last day of this month');

                break;

            case 'mes':
            default:

                $periodo = 'mes';

                $inicio =
                    $hoje->modify(
                        'first day of this month'
                    );

                $fim =
                    $hoje->modify(
                        'last day of this month'
                    );

                break;
        }

        return [
            'periodo' =>
            $periodo,

            'data_inicio' =>
            !empty($filtros['data_inicio'])
                ? $filtros['data_inicio']
                : $inicio->format('Y-m-d'),

            'data_fim' =>
            !empty($filtros['data_fim'])
                ? $filtros['data_fim']
                : $fim->format('Y-m-d'),

            'filial_id' =>
            !empty($filtros['filial_id'])
                ? (int)$filtros['filial_id']
                : null,

            'estado' =>
            null,

            'pesquisa' =>
            null
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO
    |--------------------------------------------------------------------------
    */

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {

            throw new InvalidArgumentException(
                'Empresa não informada.'
            );
        }
    }
}
