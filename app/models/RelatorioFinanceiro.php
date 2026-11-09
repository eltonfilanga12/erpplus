<?php

declare(strict_types=1);

final class RelatorioFinanceiro
{
    private PDO $db;


    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /*
    |--------------------------------------------------------------------------
    | FLUXO DE CAIXA
    |--------------------------------------------------------------------------
    */
    public function fluxoCaixa(
        int $empresaId,
        array $filtros
    ): array {

        /*
    |--------------------------------------------------------------------------
    | Condições
    |--------------------------------------------------------------------------
    */

        $whereCaixa = [
            'mc.empresa_id = :empresa_caixa'
        ];

        $whereBanco = [
            'mb.empresa_id = :empresa_banco'
        ];


        $params = [
            ':empresa_caixa' =>
            $empresaId,

            ':empresa_banco' =>
            $empresaId
        ];


        /*
    |--------------------------------------------------------------------------
    | Data inicial
    |--------------------------------------------------------------------------
    */

        if (
            !empty($filtros['data_inicio'])
        ) {

            $whereCaixa[] =
                'DATE(mc.data_movimento) >= :data_inicio_caixa';

            $whereBanco[] =
                'DATE(mb.data_movimento) >= :data_inicio_banco';


            $params[':data_inicio_caixa'] =
                $filtros['data_inicio'];

            $params[':data_inicio_banco'] =
                $filtros['data_inicio'];
        }


        /*
    |--------------------------------------------------------------------------
    | Data final
    |--------------------------------------------------------------------------
    */

        if (
            !empty($filtros['data_fim'])
        ) {

            $whereCaixa[] =
                'DATE(mc.data_movimento) <= :data_fim_caixa';

            $whereBanco[] =
                'DATE(mb.data_movimento) <= :data_fim_banco';


            $params[':data_fim_caixa'] =
                $filtros['data_fim'];

            $params[':data_fim_banco'] =
                $filtros['data_fim'];
        }


        /*
    |--------------------------------------------------------------------------
    | Filial
    |--------------------------------------------------------------------------
    */

        if (
            !empty($filtros['filial_id'])
        ) {

            $whereCaixa[] =
                'mc.filial_id = :filial_caixa';

            $whereBanco[] =
                'mb.filial_id = :filial_banco';


            $params[':filial_caixa'] =
                (int)$filtros['filial_id'];

            $params[':filial_banco'] =
                (int)$filtros['filial_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | SQL
    |--------------------------------------------------------------------------
    |
    | Forçamos todas as colunas textuais dos dois lados do UNION
    | para utf8mb4_unicode_ci.
    |--------------------------------------------------------------------------
    */

        $sql = "

        SELECT
            movimentos.id,
            movimentos.data_movimento,
            movimentos.origem_financeira,
            movimentos.conta,
            movimentos.tipo,
            movimentos.origem,
            movimentos.descricao,
            movimentos.valor,
            movimentos.filial_id,
            movimentos.filial

        FROM (

            /*
            |--------------------------------------------------------------------------
            | CAIXA
            |--------------------------------------------------------------------------
            */

            SELECT

                mc.id,

                mc.data_movimento,

                CAST(
                    'CAIXA'
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS origem_financeira,

                CAST(
                    c.nome
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS conta,

                CAST(
                    mc.tipo
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS tipo,

                CAST(
                    mc.origem
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS origem,

                CAST(
                    COALESCE(
                        mc.descricao,
                        ''
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS descricao,

                mc.valor,

                mc.filial_id,

                CAST(
                    COALESCE(
                        f.nome,
                        'Sem filial'
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS filial


            FROM movimentos_caixa mc


            INNER JOIN caixas c
                ON c.id = mc.caixa_id
               AND c.empresa_id =
                    mc.empresa_id


            LEFT JOIN filiais f
                ON f.id = mc.filial_id
               AND f.empresa_id =
                    mc.empresa_id


            WHERE "
            . implode(
                ' AND ',
                $whereCaixa
            )
            . "


            UNION ALL


            /*
            |--------------------------------------------------------------------------
            | BANCO
            |--------------------------------------------------------------------------
            */

            SELECT

                mb.id,

                mb.data_movimento,

                CAST(
                    'BANCO'
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS origem_financeira,

                CAST(
                    CONCAT(
                        b.nome_banco,
                        ' - ',
                        b.conta
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS conta,

                CAST(
                    mb.tipo
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS tipo,

                CAST(
                    mb.categoria
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS origem,

                CAST(
                    COALESCE(
                        mb.descricao,
                        ''
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS descricao,

                mb.valor,

                mb.filial_id,

                CAST(
                    COALESCE(
                        f.nome,
                        ''
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS filial


            FROM movimentos_bancarios mb


            INNER JOIN bancos b
                ON b.id = mb.banco_id
               AND b.empresa_id =
                    mb.empresa_id


            LEFT JOIN filiais f
                ON f.id = mb.filial_id
               AND f.empresa_id =
                    mb.empresa_id


            WHERE "
            . implode(
                ' AND ',
                $whereBanco
            )
            . "

        ) AS movimentos


        ORDER BY
            movimentos.data_movimento DESC,
            movimentos.id DESC
    ";


        /*
    |--------------------------------------------------------------------------
    | Executar
    |--------------------------------------------------------------------------
    */

        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */

        $entradas = 0.0;
        $saidas = 0.0;


        foreach (
            $registos as $registo
        ) {

            $tipo =
                strtoupper(
                    trim(
                        (string)(
                            $registo['tipo']
                            ?? ''
                        )
                    )
                );


            $valor =
                (float)(
                    $registo['valor']
                    ?? 0
                );


            /*
        |--------------------------------------------------------------------------
        | Entradas
        |--------------------------------------------------------------------------
        */

            if (
                in_array(
                    $tipo,
                    [
                        'ENTRADA',
                        'SUPRIMENTO'
                    ],
                    true
                )
            ) {

                $entradas +=
                    $valor;

                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | Saídas
        |--------------------------------------------------------------------------
        */

            if (
                in_array(
                    $tipo,
                    [
                        'SAIDA',
                        'SANGRIA'
                    ],
                    true
                )
            ) {

                $saidas +=
                    $valor;
            }
        }


        return [

            'resumo' => [

                'total_entradas' =>
                $entradas,

                'total_saidas' =>
                $saidas,

                'saldo_periodo' =>
                $entradas
                    - $saidas,

                'total_movimentos' =>
                count($registos)
            ],

            'registos' =>
            $registos
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | CONTAS A RECEBER
    |--------------------------------------------------------------------------
    */

    public function contasReceber(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'cr.empresa_id = :empresa_id'
        ];


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (
            !empty($filtros['data_inicio'])
        ) {

            $where[] =
                'cr.data_emissao >= :data_inicio';

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        if (
            !empty($filtros['data_fim'])
        ) {

            $where[] =
                'cr.data_emissao <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        if (
            !empty($filtros['filial_id'])
        ) {

            $where[] =
                'cr.filial_id = :filial_id';

            $params[':filial_id'] =
                $filtros['filial_id'];
        }

        /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

        if (!empty($filtros['estado'])) {

            $where[] =
                's.nome = :estado';

            $params[':estado'] =
                $filtros['estado'];
        }



        $sql = "

            SELECT

                cr.id,

                cr.numero_documento,

                cr.data_emissao,

                cr.data_vencimento,

                cr.data_pagamento,

                cr.valor,

                cr.desconto,

                cr.juros,

                cr.multa,

                cr.valor_recebido,

                cr.saldo,

                cli.nome AS cliente,

                f.nome AS filial,

                s.nome AS estado,

                CASE

                    WHEN
                        cr.saldo > 0
                        AND cr.data_vencimento
                            < CURDATE()

                    THEN 1

                    ELSE 0

                END AS vencido

            FROM contas_receber cr

            INNER JOIN clientes cli
                ON cli.id = cr.cliente_id
               AND cli.empresa_id = cr.empresa_id

            LEFT JOIN filiais f
                ON f.id = cr.filial_id
               AND f.empresa_id = cr.empresa_id

            LEFT JOIN status s
                ON s.id = cr.status_id

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

            ORDER BY
                cr.data_vencimento ASC,
                cr.id DESC
        ";



        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $total = 0.0;
        $recebido = 0.0;
        $pendente = 0.0;
        $vencido = 0.0;


        foreach ($registos as $registo) {

            $estado =
                mb_strtolower(
                    trim(
                        (string)(
                            $registo['estado']
                            ?? ''
                        )
                    )
                );

            if (
                in_array(
                    $estado,
                    [
                        'cancelado',
                        'cancelada'
                    ],
                    true
                )
            ) {
                continue;
            }

            $total +=
                (float)$registo['valor'];

            $recebido +=
                (float)$registo['valor_recebido'];

            $pendente +=
                (float)$registo['saldo'];


            if (
                (int)$registo['vencido']
                === 1
            ) {

                $vencido +=
                    (float)$registo['saldo'];
            }
        }


        return [

            'resumo' => [

                'total_receber' =>
                $total,

                'total_recebido' =>
                $recebido,

                'total_pendente' =>
                $pendente,

                'total_vencido' =>
                $vencido
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CONTAS A PAGAR
    |--------------------------------------------------------------------------
    */

    public function contasPagar(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'cp.empresa_id = :empresa_id'
        ];


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if (
            !empty($filtros['data_inicio'])
        ) {

            $where[] =
                'cp.data_emissao >= :data_inicio';

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        if (
            !empty($filtros['data_fim'])
        ) {

            $where[] =
                'cp.data_emissao <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        if (
            !empty($filtros['filial_id'])
        ) {

            $where[] =
                'cp.filial_id = :filial_id';

            $params[':filial_id'] =
                $filtros['filial_id'];
        }

        /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

        if (!empty($filtros['estado'])) {

            $where[] =
                's.nome = :estado';

            $params[':estado'] =
                $filtros['estado'];
        }


        $sql = "

            SELECT

                cp.id,

                COALESCE(
                    NULLIF(cp.numero_documento, ''),
                    NULLIF(cp.referencia, ''),
                    CONCAT('CP-', cp.id)
                ) AS numero_documento,

                cp.referencia,

                cp.descricao,

                cp.data_emissao,

                cp.data_vencimento,

                cp.data_pagamento,

                cp.valor,

                cp.desconto,

                cp.juros,

                cp.multa,

                cp.valor_pago,

                cp.saldo,

                forn.nome AS fornecedor,

                f.nome AS filial,

                s.nome AS estado,

                CASE

                    WHEN
                        cp.saldo > 0
                        AND cp.data_vencimento
                            < CURDATE()

                    THEN 1

                    ELSE 0

                END AS vencido

            FROM contas_pagar cp

            INNER JOIN fornecedores forn
                ON forn.id = cp.fornecedor_id
               AND forn.empresa_id = cp.empresa_id

            LEFT JOIN filiais f
                ON f.id = cp.filial_id
               AND f.empresa_id = cp.empresa_id

            LEFT JOIN status s
                ON s.id = cp.status_id

            WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

            ORDER BY
                cp.data_vencimento ASC,
                cp.id DESC
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        $total = 0.0;
        $pago = 0.0;
        $pendente = 0.0;
        $vencido = 0.0;


        foreach ($registos as $registo) {

            $estado =
                mb_strtolower(
                    trim(
                        (string)(
                            $registo['estado']
                            ?? ''
                        )
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | Contas canceladas continuam visíveis na tabela,
            | mas não entram nos indicadores financeiros.
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $estado,
                    [
                        'cancelado',
                        'cancelada'
                    ],
                    true
                )
            ) {
                continue;
            }


            $total +=
                (float)$registo['valor'];


            $pago +=
                (float)$registo['valor_pago'];


            $pendente +=
                (float)$registo['saldo'];


            if (
                (int)$registo['vencido']
                === 1
            ) {

                $vencido +=
                    (float)$registo['saldo'];
            }
        }


        return [

            'resumo' => [

                'total_pagar' =>
                $total,

                'total_pago' =>
                $pago,

                'total_pendente' =>
                $pendente,

                'total_vencido' =>
                $vencido
            ],

            'registos' =>
            $registos
        ];
    }

    /*
|--------------------------------------------------------------------------
| MOVIMENTAÇÃO BANCÁRIA
|--------------------------------------------------------------------------
*/

    public function movimentacaoBancaria(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'mb.empresa_id = :empresa_id'
        ];

        $params = [
            ':empresa_id' => $empresaId
        ];


        /*
    |--------------------------------------------------------------------------
    | Data inicial
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['data_inicio'])) {

            $where[] =
                'DATE(mb.data_movimento) >= :data_inicio';

            $params[':data_inicio'] =
                $filtros['data_inicio'];
        }


        /*
    |--------------------------------------------------------------------------
    | Data final
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['data_fim'])) {

            $where[] =
                'DATE(mb.data_movimento) <= :data_fim';

            $params[':data_fim'] =
                $filtros['data_fim'];
        }


        /*
    |--------------------------------------------------------------------------
    | Filial
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['filial_id'])) {

            $where[] =
                'mb.filial_id = :filial_id';

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Banco
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['banco_id'])) {

            $where[] =
                'mb.banco_id = :banco_id';

            $params[':banco_id'] =
                (int)$filtros['banco_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Centro de custo
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['centro_custo_id'])) {

            $where[] =
                'mb.centro_custo_id = :centro_custo_id';

            $params[':centro_custo_id'] =
                (int)$filtros['centro_custo_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Tipo
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['tipo_movimento'])) {

            $where[] =
                'mb.tipo = :tipo_movimento';

            $params[':tipo_movimento'] =
                strtoupper(
                    (string)$filtros['tipo_movimento']
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Pesquisa
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['pesquisa'])) {

            $where[] = "
            (
                mb.descricao LIKE :pesquisa
                OR mb.documento LIKE :pesquisa
                OR mb.referencia LIKE :pesquisa
                OR mb.categoria LIKE :pesquisa
                OR mb.origem LIKE :pesquisa
                OR b.nome_banco LIKE :pesquisa
                OR b.conta LIKE :pesquisa
            )
        ";

            $params[':pesquisa'] =
                '%'
                . $filtros['pesquisa']
                . '%';
        }


        /*
    |--------------------------------------------------------------------------
    | Query
    |--------------------------------------------------------------------------
    */

        $sql = "
        SELECT
            mb.id,

            mb.data_movimento,

            mb.tipo,

            mb.categoria,

            mb.origem,

            mb.descricao,

            mb.documento,

            mb.referencia,

            mb.valor,

            mb.saldo_anterior,

            mb.saldo_posterior,

            mb.conciliado,

            mb.data_conciliacao,

            b.id AS banco_id,

            b.nome_banco,

            b.conta,

            b.moeda,

            f.nome AS filial,

            cc.nome AS centro_custo

        FROM movimentos_bancarios mb

        INNER JOIN bancos b
            ON b.id = mb.banco_id
           AND b.empresa_id = mb.empresa_id

        LEFT JOIN filiais f
            ON f.id = mb.filial_id
           AND f.empresa_id = mb.empresa_id

        LEFT JOIN centros_custo cc
            ON cc.id = mb.centro_custo_id
           AND cc.empresa_id = mb.empresa_id

        WHERE "
            . implode(
                ' AND ',
                $where
            )
            . "

        ORDER BY
            mb.data_movimento DESC,
            mb.id DESC
    ";


        $stmt =
            $this->db->prepare($sql);

        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */

        $entradas = 0.0;
        $saidas = 0.0;
        $conciliados = 0;


        foreach ($registos as $registo) {

            $valor =
                (float)$registo['valor'];


            if (
                strtoupper(
                    (string)$registo['tipo']
                ) === 'ENTRADA'
            ) {

                $entradas += $valor;
            } else {

                $saidas += $valor;
            }


            if (
                (int)$registo['conciliado']
                === 1
            ) {
                $conciliados++;
            }
        }


        return [
            'resumo' => [

                'total_entradas' =>
                $entradas,

                'total_saidas' =>
                $saidas,

                'saldo_liquido' =>
                $entradas - $saidas,

                'total_movimentos' =>
                count($registos),

                'total_conciliados' =>
                $conciliados
            ],

            'registos' =>
            $registos
        ];
    }

    /*
|--------------------------------------------------------------------------
| CENTROS DE CUSTO
|--------------------------------------------------------------------------
*/

    public function centrosCusto(
        int $empresaId,
        array $filtros
    ): array {

        $whereCentro = [
            'cc.empresa_id = :empresa_id'
        ];

        $params = [
            ':empresa_id' =>
            $empresaId,

            ':empresa_caixa' =>
            $empresaId,

            ':empresa_banco' =>
            $empresaId
        ];


        /*
    |--------------------------------------------------------------------------
    | Filial do centro
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['filial_id'])) {

            $whereCentro[] = "
            (
                cc.filial_id = :filial_id
                OR cc.filial_id IS NULL
            )
        ";

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Pesquisa
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['pesquisa'])) {

            $whereCentro[] = "
            (
                cc.codigo LIKE :pesquisa
                OR cc.nome LIKE :pesquisa
                OR cc.descricao LIKE :pesquisa
                OR cc.responsavel LIKE :pesquisa
            )
        ";

            $params[':pesquisa'] =
                '%'
                . $filtros['pesquisa']
                . '%';
        }


        /*
    |--------------------------------------------------------------------------
    | Datas dos movimentos
    |--------------------------------------------------------------------------
    */

        $whereCaixa = [
            'mc.empresa_id = :empresa_caixa'
        ];

        $whereBanco = [
            'mb.empresa_id = :empresa_banco'
        ];


        if (!empty($filtros['data_inicio'])) {

            $whereCaixa[] =
                'DATE(mc.data_movimento) >= :cc_data_inicio_caixa';

            $whereBanco[] =
                'DATE(mb.data_movimento) >= :cc_data_inicio_banco';


            $params[':cc_data_inicio_caixa'] =
                $filtros['data_inicio'];

            $params[':cc_data_inicio_banco'] =
                $filtros['data_inicio'];
        }


        if (!empty($filtros['data_fim'])) {

            $whereCaixa[] =
                'DATE(mc.data_movimento) <= :cc_data_fim_caixa';

            $whereBanco[] =
                'DATE(mb.data_movimento) <= :cc_data_fim_banco';


            $params[':cc_data_fim_caixa'] =
                $filtros['data_fim'];

            $params[':cc_data_fim_banco'] =
                $filtros['data_fim'];
        }


        if (!empty($filtros['filial_id'])) {

            $whereCaixa[] =
                'mc.filial_id = :cc_filial_caixa';

            $whereBanco[] =
                'mb.filial_id = :cc_filial_banco';


            $params[':cc_filial_caixa'] =
                (int)$filtros['filial_id'];

            $params[':cc_filial_banco'] =
                (int)$filtros['filial_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Consulta
    |--------------------------------------------------------------------------
    */

        $sql = "
        SELECT
            cc.id,

            cc.codigo,

            cc.nome,

            cc.tipo,

            cc.responsavel,

            cc.orcamento_mensal,

            f.nome AS filial,

            COALESCE(
                mov.receitas,
                0
            ) AS receitas,

            COALESCE(
                mov.despesas,
                0
            ) AS despesas,

            (
                COALESCE(mov.receitas, 0)
                -
                COALESCE(mov.despesas, 0)
            ) AS resultado,

            COALESCE(
                mov.total_movimentos,
                0
            ) AS total_movimentos

        FROM centros_custo cc

        LEFT JOIN filiais f
            ON f.id = cc.filial_id
           AND f.empresa_id = cc.empresa_id


        LEFT JOIN (

            SELECT
                centro_custo_id,

                SUM(receita) AS receitas,

                SUM(despesa) AS despesas,

                COUNT(*) AS total_movimentos

            FROM (

                /*
                |--------------------------------------------------------------------------
                | Caixa
                |--------------------------------------------------------------------------
                */

                SELECT
                    mc.centro_custo_id,

                    CASE

                        WHEN
                            mc.tipo = 'ENTRADA'
                            AND mc.origem IN (
                                'VENDA',
                                'RECEBIMENTO'
                            )

                        THEN mc.valor

                        ELSE 0

                    END AS receita,

                    CASE

                        WHEN
                            mc.tipo = 'SAIDA'
                            AND mc.origem IN (
                                'DESPESA',
                                'COMPRA',
                                'PAGAMENTO'
                            )

                        THEN mc.valor

                        ELSE 0

                    END AS despesa

                FROM movimentos_caixa mc

                WHERE "
            . implode(
                ' AND ',
                $whereCaixa
            )
            . "

                  AND mc.centro_custo_id
                      IS NOT NULL


                UNION ALL


                /*
                |--------------------------------------------------------------------------
                | Banco
                |--------------------------------------------------------------------------
                */

                SELECT
                    mb.centro_custo_id,

                    CASE

                        WHEN
                            mb.tipo = 'ENTRADA'
                            AND (
                                mb.categoria IN (
                                    'RECEBIMENTO',
                                    'JURO'
                                )

                                OR (
                                    mb.categoria = 'OUTRO'
                                    AND mb.origem = 'RECEBIMENTO'
                                )
                            )

                        THEN mb.valor

                        ELSE 0

                    END AS receita,

                    CASE

                        WHEN
                            mb.tipo = 'SAIDA'
                            AND (
                                mb.categoria IN (
                                    'PAGAMENTO',
                                    'TARIFA',
                                    'JURO'
                                )

                                OR (
                                    mb.categoria = 'OUTRO'
                                    AND mb.origem IN (
                                        'PAGAMENTO',
                                        'DESPESA',
                                        'COMPRA'
                                    )
                                )
                            )

                        THEN mb.valor

                        ELSE 0

                    END AS despesa

                FROM movimentos_bancarios mb

                WHERE "
            . implode(
                ' AND ',
                $whereBanco
            )
            . "

                  AND mb.centro_custo_id
                      IS NOT NULL

            ) movimento

            GROUP BY
                centro_custo_id

        ) mov
            ON mov.centro_custo_id =
                cc.id


        WHERE "
            . implode(
                ' AND ',
                $whereCentro
            )
            . "

        ORDER BY
            cc.codigo ASC,
            cc.nome ASC
    ";


        $stmt =
            $this->db->prepare($sql);

        $stmt->execute($params);


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */

        $receitas = 0.0;
        $despesas = 0.0;
        $orcamento = 0.0;


        foreach ($registos as &$registo) {

            $receita =
                (float)$registo['receitas'];

            $despesa =
                (float)$registo['despesas'];

            $orcamentoCentro =
                (float)$registo['orcamento_mensal'];


            $receitas +=
                $receita;

            $despesas +=
                $despesa;

            $orcamento +=
                $orcamentoCentro;


            /*
        |--------------------------------------------------------------------------
        | Percentagem do orçamento consumido
        |--------------------------------------------------------------------------
        */

            $registo['percentual_orcamento'] =
                $orcamentoCentro > 0
                ? round(
                    (
                        $despesa
                        / $orcamentoCentro
                    ) * 100,
                    2
                )
                : 0;
        }

        unset($registo);


        return [
            'resumo' => [

                'total_centros' =>
                count($registos),

                'total_receitas' =>
                $receitas,

                'total_despesas' =>
                $despesas,

                'resultado' =>
                $receitas - $despesas,

                'orcamento_total' =>
                $orcamento
            ],

            'registos' =>
            $registos
        ];
    }

    /*
|--------------------------------------------------------------------------
| RECEITAS E DESPESAS
|--------------------------------------------------------------------------
*/

    public function receitasDespesas(
        int $empresaId,
        array $filtros
    ): array {

        $whereCaixa = [
            'mc.empresa_id = :empresa_caixa'
        ];

        $whereBanco = [
            'mb.empresa_id = :empresa_banco'
        ];

        $params = [
            ':empresa_caixa' => $empresaId,
            ':empresa_banco' => $empresaId
        ];


        /*
    |--------------------------------------------------------------------------
    | Datas
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['data_inicio'])) {

            $whereCaixa[] =
                'DATE(mc.data_movimento) >= :rd_inicio_caixa';

            $whereBanco[] =
                'DATE(mb.data_movimento) >= :rd_inicio_banco';

            $params[':rd_inicio_caixa'] =
                $filtros['data_inicio'];

            $params[':rd_inicio_banco'] =
                $filtros['data_inicio'];
        }


        if (!empty($filtros['data_fim'])) {

            $whereCaixa[] =
                'DATE(mc.data_movimento) <= :rd_fim_caixa';

            $whereBanco[] =
                'DATE(mb.data_movimento) <= :rd_fim_banco';

            $params[':rd_fim_caixa'] =
                $filtros['data_fim'];

            $params[':rd_fim_banco'] =
                $filtros['data_fim'];
        }


        /*
    |--------------------------------------------------------------------------
    | Filial
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['filial_id'])) {

            $whereCaixa[] =
                'mc.filial_id = :rd_filial_caixa';

            $whereBanco[] =
                'mb.filial_id = :rd_filial_banco';

            $params[':rd_filial_caixa'] =
                (int)$filtros['filial_id'];

            $params[':rd_filial_banco'] =
                (int)$filtros['filial_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Centro de custo
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['centro_custo_id'])) {

            $whereCaixa[] =
                'mc.centro_custo_id = :rd_cc_caixa';

            $whereBanco[] =
                'mb.centro_custo_id = :rd_cc_banco';

            $params[':rd_cc_caixa'] =
                (int)$filtros['centro_custo_id'];

            $params[':rd_cc_banco'] =
                (int)$filtros['centro_custo_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Pesquisa
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['pesquisa'])) {

            $whereCaixa[] = "
            (
                mc.descricao LIKE :pesquisa_caixa
                OR mc.origem LIKE :pesquisa_caixa
            )
        ";

            $whereBanco[] = "
            (
                mb.descricao LIKE :pesquisa_banco
                OR mb.referencia LIKE :pesquisa_banco
                OR mb.documento LIKE :pesquisa_banco
                OR mb.categoria LIKE :pesquisa_banco
            )
        ";

            $params[':pesquisa_caixa'] =
                '%' . $filtros['pesquisa'] . '%';

            $params[':pesquisa_banco'] =
                '%' . $filtros['pesquisa'] . '%';
        }


        /*
    |--------------------------------------------------------------------------
    | SQL
    |--------------------------------------------------------------------------
    */

        $sql = "

        SELECT
            rd.id,
            rd.data_movimento,
            rd.fonte,
            rd.conta,
            rd.natureza,
            rd.categoria,
            rd.descricao,
            rd.valor,
            rd.filial,
            rd.centro_custo

        FROM (

            /*
            |--------------------------------------------------------------------------
            | CAIXA
            |--------------------------------------------------------------------------
            */

            SELECT

                mc.id,

                mc.data_movimento,

                CAST(
                    'CAIXA'
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS fonte,

                CAST(
                    c.nome
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS conta,

                CAST(
                    CASE

                        WHEN
                            mc.tipo = 'ENTRADA'
                            AND mc.origem IN (
                                'VENDA',
                                'RECEBIMENTO'
                            )

                        THEN 'RECEITA'

                        WHEN
                            mc.tipo = 'SAIDA'
                            AND mc.origem IN (
                                'DESPESA',
                                'COMPRA',
                                'PAGAMENTO'
                            )

                        THEN 'DESPESA'

                        ELSE ''

                    END
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS natureza,

                CAST(
                    mc.origem
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS categoria,

                CAST(
                    COALESCE(
                        mc.descricao,
                        ''
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS descricao,

                mc.valor,

                CAST(
                    COALESCE(
                        NULLIF(
                            TRIM(f.nome),
                            ''
                        ),
                        'Sem filial'
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS filial,

                CAST(
                    COALESCE(
                        cc.nome,
                        'Sem centro de custo'
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS centro_custo

            FROM movimentos_caixa mc

            INNER JOIN caixas c
                ON c.id = mc.caixa_id
               AND c.empresa_id = mc.empresa_id

            LEFT JOIN filiais f
                ON f.id = mc.filial_id
               AND f.empresa_id = mc.empresa_id

            LEFT JOIN centros_custo cc
                ON cc.id = mc.centro_custo_id
               AND cc.empresa_id = mc.empresa_id

            WHERE "
            . implode(
                ' AND ',
                $whereCaixa
            )
            . "

              AND (
                    (
                        mc.tipo = 'ENTRADA'
                        AND mc.origem IN (
                            'VENDA',
                            'RECEBIMENTO'
                        )
                    )

                    OR

                    (
                        mc.tipo = 'SAIDA'
                        AND mc.origem IN (
                            'DESPESA',
                            'COMPRA',
                            'PAGAMENTO'
                        )
                    )
              )


            UNION ALL


            /*
            |--------------------------------------------------------------------------
            | BANCO
            |--------------------------------------------------------------------------
            */

            SELECT

                mb.id,

                mb.data_movimento,

                CAST(
                    'BANCO'
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS fonte,

                CAST(
                    CONCAT(
                        b.nome_banco,
                        ' - ',
                        b.conta
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS conta,

                CAST(
                    CASE

                        WHEN
                            mb.tipo = 'ENTRADA'
                            AND (
                                mb.categoria IN (
                                    'RECEBIMENTO',
                                    'JURO'
                                )

                                OR (
                                    mb.categoria = 'OUTRO'
                                    AND mb.origem = 'RECEBIMENTO'
                                )
                            )

                        THEN 'RECEITA'

                        WHEN
                            mb.tipo = 'SAIDA'
                            AND (
                                mb.categoria IN (
                                    'PAGAMENTO',
                                    'TARIFA',
                                    'JURO'
                                )

                                OR (
                                    mb.categoria = 'OUTRO'
                                    AND mb.origem IN (
                                        'PAGAMENTO',
                                        'DESPESA',
                                        'COMPRA'
                                    )
                                )
                            )

                        THEN 'DESPESA'

                        ELSE ''

                    END
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS natureza,

                CAST(
                    CASE
                        WHEN
                            mb.categoria = 'OUTRO'
                            AND mb.origem = 'RECEBIMENTO'
                        THEN 'RECEBIMENTO'

                        WHEN
                            mb.categoria = 'OUTRO'
                            AND mb.origem IN (
                                'PAGAMENTO',
                                'DESPESA',
                                'COMPRA'
                            )
                        THEN 'PAGAMENTO'

                        ELSE mb.categoria
                    END
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS categoria,

                CAST(
                    COALESCE(
                        mb.descricao,
                        ''
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS descricao,

                mb.valor,

                CAST(
                    COALESCE(
                        f.nome,
                        'Sem filial'
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS filial,

                CAST(
                    COALESCE(
                        cc.nome,
                        'Sem centro de custo'
                    )
                    AS CHAR CHARACTER SET utf8mb4
                ) COLLATE utf8mb4_unicode_ci
                    AS centro_custo

            FROM movimentos_bancarios mb

            INNER JOIN bancos b
                ON b.id = mb.banco_id
               AND b.empresa_id = mb.empresa_id

            LEFT JOIN filiais f
                ON f.id = mb.filial_id
               AND f.empresa_id = mb.empresa_id

            LEFT JOIN centros_custo cc
                ON cc.id = mb.centro_custo_id
               AND cc.empresa_id = mb.empresa_id

            WHERE "
            . implode(
                ' AND ',
                $whereBanco
            )
            . "

              AND (
                    (
                        mb.tipo = 'ENTRADA'
                        AND (
                            mb.categoria IN (
                                'RECEBIMENTO',
                                'JURO'
                            )

                            OR (
                                mb.categoria = 'OUTRO'
                                AND mb.origem = 'RECEBIMENTO'
                            )
                        )
                    )

                    OR

                    (
                        mb.tipo = 'SAIDA'
                        AND (
                            mb.categoria IN (
                                'PAGAMENTO',
                                'TARIFA',
                                'JURO'
                            )

                            OR (
                                mb.categoria = 'OUTRO'
                                AND mb.origem IN (
                                    'PAGAMENTO',
                                    'DESPESA',
                                    'COMPRA'
                                )
                            )
                        )
                    )
              )

        ) AS rd


        ORDER BY
            rd.data_movimento DESC,
            rd.id DESC
    ";


        /*
    |--------------------------------------------------------------------------
    | Executar
    |--------------------------------------------------------------------------
    */

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute(
            $params
        );


        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        /*
    |--------------------------------------------------------------------------
    | Resumo
    |--------------------------------------------------------------------------
    */

        $receitas = 0.0;
        $despesas = 0.0;


        foreach ($registos as $registo) {

            $natureza =
                strtoupper(
                    trim(
                        (string)(
                            $registo['natureza']
                            ?? ''
                        )
                    )
                );

            $valor =
                (float)(
                    $registo['valor']
                    ?? 0
                );


            if ($natureza === 'RECEITA') {

                $receitas +=
                    $valor;
            } elseif (
                $natureza === 'DESPESA'
            ) {

                $despesas +=
                    $valor;
            }
        }


        $resultado =
            $receitas - $despesas;


        $margem =
            $receitas > 0
            ? (
                $resultado
                / $receitas
            ) * 100
            : 0;


        return [

            'resumo' => [

                'total_receitas' =>
                $receitas,

                'total_despesas' =>
                $despesas,

                'resultado' =>
                $resultado,

                'margem' =>
                round(
                    $margem,
                    2
                )
            ],

            'registos' =>
            $registos
        ];
    }
}
