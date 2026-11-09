<?php

declare(strict_types=1);

final class RelatorioOperacional
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }


    /*
    |--------------------------------------------------------------------------
    | Filiais
    |--------------------------------------------------------------------------
    */

    public function listarFiliais(
        int $empresaId
    ): array {

        $stmt = $this->db->prepare("
        SELECT
            f.id,
            f.nome

        FROM filiais f

        WHERE f.empresa_id = :empresa_id

        ORDER BY f.nome ASC
    ");

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VENDAS
    |--------------------------------------------------------------------------
    */

    public function vendas(
        int $empresaId,
        array $filtros
    ): array {
        $where = [
            'v.empresa_id = :empresa_id'
        ];

        $where[] = "
    COALESCE(
        LOWER(s.nome),
        ''
    ) NOT IN (
        'cancelada',
        'cancelado'
    )
";

        $params = [
            ':empresa_id' => $empresaId
        ];

        $this->aplicarFiltroDatas(
            $where,
            $params,
            $filtros,
            'v.data_venda'
        );

        $this->aplicarFiltroFilial(
            $where,
            $params,
            $filtros,
            'v.filial_id'
        );

        if (!empty($filtros['estado'])) {
            $where[] =
                's.nome = :estado';

            $params[':estado'] =
                $filtros['estado'];
        }

        if (!empty($filtros['pesquisa'])) {
            $where[] = "
                (
                    v.numero_fatura LIKE :pesquisa
                    OR v.numero_documento LIKE :pesquisa
                    OR c.nome LIKE :pesquisa
                    OR c.nome_comercial LIKE :pesquisa
                )
            ";

            $params[':pesquisa'] =
                '%' .
                $filtros['pesquisa'] .
                '%';
        }

        $sql = "
            SELECT
                v.id,

                v.data_venda AS data,

                COALESCE(
                    NULLIF(v.numero_fatura, ''),
                    NULLIF(v.numero_documento, ''),
                    CONCAT('VEN-', v.id)
                ) AS documento,

                c.nome AS cliente,

                COALESCE(
                    f.nome,
                    'Sem filial'
                ) AS filial,

                v.subtotal,
                v.desconto,
                v.imposto,
                v.total,

                s.nome AS estado

            FROM vendas v

            INNER JOIN clientes c
                ON c.id = v.cliente_id
               AND c.empresa_id = v.empresa_id

            LEFT JOIN filiais f
                ON f.id = v.filial_id
               AND f.empresa_id = v.empresa_id

            LEFT JOIN status s
                ON s.id = v.status_id

            WHERE "
            . implode(
                ' AND ',
                $where
            ) . "

            ORDER BY
                v.data_venda DESC,
                v.id DESC
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute($params);

        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        $totalVendas =
            count($registos);

        $valorTotal = 0.0;
        $totalDescontos = 0.0;

        foreach ($registos as $registo) {
            $valorTotal +=
                (float)$registo['total'];

            $totalDescontos +=
                (float)$registo['desconto'];
        }

        return [
            'resumo' => [
                'total_vendas' =>
                $totalVendas,

                'valor_total' =>
                $valorTotal,

                'total_descontos' =>
                $totalDescontos,

                'ticket_medio' =>
                $totalVendas > 0
                    ? $valorTotal / $totalVendas
                    : 0
            ],

            'registos' =>
            $registos
        ];
    }

    public function listarEstados(
        string $tipo
    ): array {
        $mapa = [
            'VENDAS' =>
            'venda',

            'COMPRAS' =>
            'compra',

            'CLIENTES' =>
            'cliente',

            'FORNECEDORES' =>
            'fornecedor',

            'PRODUTOS' =>
            'produto'
        ];

        if (!isset($mapa[$tipo])) {
            return [];
        }

        $stmt =
            $this->db->prepare("
            SELECT
                id,
                nome
            FROM status
            WHERE LOWER(tipo) = LOWER(:tipo)
            ORDER BY nome
        ");

        $stmt->execute([
            ':tipo' => $mapa[$tipo]
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COMPRAS
    |--------------------------------------------------------------------------
    */
    public function compras(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'c.empresa_id = :empresa_id'
        ];

        $params = [
            ':empresa_id' => $empresaId
        ];

        $this->aplicarFiltroDatas(
            $where,
            $params,
            $filtros,
            'c.data_compra'
        );

        $this->aplicarFiltroFilial(
            $where,
            $params,
            $filtros,
            'c.filial_id'
        );

        if (!empty($filtros['estado'])) {
            $where[] =
                's.nome = :estado';

            $params[':estado'] =
                $filtros['estado'];
        }

        if (!empty($filtros['pesquisa'])) {

            $where[] = "
            (
                c.numero_documento LIKE :pesquisa
                OR f.nome LIKE :pesquisa
                OR f.nome_comercial LIKE :pesquisa
            )
        ";

            $params[':pesquisa'] =
                '%'
                . $filtros['pesquisa']
                . '%';
        }

        $sql = "
        SELECT
            c.id,

            c.data_compra AS data,

            COALESCE(
                NULLIF(c.numero_documento, ''),
                CONCAT('COMP-', c.id)
            ) AS documento,

            f.nome AS fornecedor,

            COALESCE(
                fi.nome,
                'Sem filial'
            ) AS filial,

            c.subtotal,
            c.imposto,
            c.total,

            s.nome AS estado,

            c.condicao_pagamento,
            c.conta_pagar_id,

            COALESCE(
    cp.valor,
    0
) AS valor_conta_pagar,

COALESCE(
    cp.valor_pago,
    0
) AS valor_pago,

COALESCE(
    cp.saldo,
    0
) AS valor_pendente

        FROM compras c

        INNER JOIN fornecedores f
            ON f.id = c.fornecedor_id
           AND f.empresa_id = c.empresa_id

        LEFT JOIN filiais fi
            ON fi.id = c.filial_id
           AND fi.empresa_id = c.empresa_id

        LEFT JOIN status s
            ON s.id = c.status_id

        LEFT JOIN contas_pagar cp
            ON cp.id = c.conta_pagar_id
           AND cp.empresa_id = c.empresa_id

        WHERE "
            . implode(
                ' AND ',
                $where
            ) . "

        ORDER BY
            c.data_compra DESC,
            c.id DESC
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute($params);

        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        $valorTotal = 0.0;
        $valorPendente = 0.0;
        $fornecedores = [];
        $totalComprasValidas = 0;

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
            | Compras canceladas continuam na tabela,
            | mas não entram nos indicadores financeiros
            |--------------------------------------------------------------------------
            */

            if ($estado === 'cancelada') {
                continue;
            }

            $totalComprasValidas++;

            $valorTotal +=
                (float)$registo['total'];

            $valorPendente +=
                (float)$registo['valor_pendente'];

            $fornecedores[$registo['fornecedor']] = true;
        }

        return [
            'resumo' => [
                'total_compras' =>
                $totalComprasValidas,

                'valor_total' =>
                $valorTotal,

                'total_fornecedores' =>
                count($fornecedores),

                'valor_pendente' =>
                $valorPendente
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CLIENTES
    |--------------------------------------------------------------------------
    */

    public function clientes(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'c.empresa_id = :empresa_id'
        ];

        $params = [
            ':empresa_id' => $empresaId
        ];


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


        /*
    |--------------------------------------------------------------------------
    | Pesquisa
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['pesquisa'])) {

            $where[] = "
            (
                c.nome LIKE :pesquisa
                OR c.nome_comercial LIKE :pesquisa
                OR c.nuit LIKE :pesquisa
                OR c.email LIKE :pesquisa
                OR c.telefone LIKE :pesquisa
                OR c.contacto LIKE :pesquisa
            )
        ";

            $params[':pesquisa'] =
                '%'
                . $filtros['pesquisa']
                . '%';
        }


        /*
    |--------------------------------------------------------------------------
    | Filtros sobre vendas
    |--------------------------------------------------------------------------
    */

        $whereVenda = [
            'v.empresa_id = :empresa_venda'
        ];

        $params[':empresa_venda'] =
            $empresaId;


        if (!empty($filtros['data_inicio'])) {

            $whereVenda[] =
                'v.data_venda >= :data_inicio_venda';

            $params[':data_inicio_venda'] =
                $filtros['data_inicio']
                . ' 00:00:00';
        }


        if (!empty($filtros['data_fim'])) {

            $whereVenda[] =
                'v.data_venda <= :data_fim_venda';

            $params[':data_fim_venda'] =
                $filtros['data_fim']
                . ' 23:59:59';
        }


        if (
            !empty($filtros['filial_id'])
            && (int)$filtros['filial_id'] > 0
        ) {

            $whereVenda[] =
                'v.filial_id = :filial_venda';

            $params[':filial_venda'] =
                (int)$filtros['filial_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Consulta
    |--------------------------------------------------------------------------
    */

        $sql = "
        SELECT
            c.id,

            c.nome AS cliente,

            c.nuit,

            COALESCE(
                NULLIF(c.telefone, ''),
                NULLIF(c.contacto, ''),
                c.email,
                '-'
            ) AS contacto,

            COUNT(
                DISTINCT v.id
            ) AS total_compras,

            COALESCE(
                SUM(v.total),
                0
            ) AS valor_comprado,

            COALESCE(
                SUM(cr.saldo),
                0
            ) AS saldo,

            COALESCE(
                GROUP_CONCAT(
                    DISTINCT fv.nome
                    ORDER BY fv.nome
                    SEPARATOR ', '
                ),
                'Sem movimentação'
            ) AS filial,

            MAX(
                v.data_venda
            ) AS ultima_compra,

            s.nome AS estado

        FROM clientes c

        LEFT JOIN status s
            ON s.id = c.status_id

        LEFT JOIN vendas v
            ON v.cliente_id = c.id
           AND "
            . implode(
                ' AND ',
                $whereVenda
            ) . "

        LEFT JOIN filiais fv
            ON fv.id = v.filial_id
           AND fv.empresa_id = v.empresa_id

        LEFT JOIN contas_receber cr
            ON cr.id = v.conta_receber_id
           AND cr.empresa_id = v.empresa_id

        WHERE "
            . implode(
                ' AND ',
                $where
            ) . "

        GROUP BY
            c.id,
            c.nome,
            c.nuit,
            c.telefone,
            c.contacto,
            c.email,
            s.nome

        ORDER BY
            valor_comprado DESC,
            c.nome ASC
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

        $valorComprado = 0.0;

        $saldoReceber = 0.0;

        $activos = 0;


        foreach ($registos as $registo) {

            $valorComprado +=
                (float)$registo['valor_comprado'];


            $saldoReceber +=
                (float)$registo['saldo'];


            if (
                in_array(
                    mb_strtolower(
                        (string)$registo['estado']
                    ),
                    [
                        'activo',
                        'ativo'
                    ],
                    true
                )
            ) {
                $activos++;
            }
        }


        return [
            'resumo' => [

                'total_clientes' =>
                count($registos),

                'clientes_activos' =>
                $activos,

                'valor_comprado' =>
                $valorComprado,

                'saldo_receber' =>
                $saldoReceber
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | FORNECEDORES
    |--------------------------------------------------------------------------
    */

    public function fornecedores(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'f.empresa_id = :empresa_id'
        ];

        $params = [
            ':empresa_id' => $empresaId
        ];

        if (!empty($filtros['estado'])) {

            $where[] =
                's.nome = :estado';

            $params[':estado'] =
                $filtros['estado'];
        }

        if (!empty($filtros['pesquisa'])) {

            $where[] = "
            (
                f.nome LIKE :pesquisa
                OR f.nome_comercial LIKE :pesquisa
                OR f.nuit LIKE :pesquisa
                OR f.email LIKE :pesquisa
                OR f.telefone LIKE :pesquisa
            )
        ";

            $params[':pesquisa'] =
                '%'
                . $filtros['pesquisa']
                . '%';
        }

        $whereCompra = [
            'c.empresa_id = :empresa_compra'
        ];

        $params[':empresa_compra'] =
            $empresaId;

        if (!empty($filtros['data_inicio'])) {

            $whereCompra[] =
                'c.data_compra >= :data_inicio_compra';

            $params[':data_inicio_compra'] =
                $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {

            $whereCompra[] =
                'c.data_compra <= :data_fim_compra';

            $params[':data_fim_compra'] =
                $filtros['data_fim'];
        }

        if (
            !empty($filtros['filial_id'])
            && (int)$filtros['filial_id'] > 0
        ) {

            $whereCompra[] =
                'c.filial_id = :filial_compra';

            $params[':filial_compra'] =
                (int)$filtros['filial_id'];
        }

        $sql = "
        SELECT
            f.id,

            f.nome AS fornecedor,
            f.nuit,

            COALESCE(
                NULLIF(f.telefone, ''),
                NULLIF(f.contacto, ''),
                f.email,
                '-'
            ) AS contacto,

            COUNT(
    DISTINCT CASE
        WHEN LOWER(COALESCE(sc.nome, '')) <> 'cancelada'
        THEN c.id
        ELSE NULL
    END
) AS total_compras,

COALESCE(
    SUM(
        CASE
            WHEN LOWER(COALESCE(sc.nome, '')) <> 'cancelada'
            THEN c.total
            ELSE 0
        END
    ),
    0
) AS valor_comprado,

COALESCE(
    SUM(
        CASE
            WHEN LOWER(COALESCE(sc.nome, '')) <> 'cancelada'
            THEN COALESCE(cp.saldo, 0)
            ELSE 0
        END
    ),
    0
) AS saldo,

            COALESCE(
                GROUP_CONCAT(
                    DISTINCT fc.nome
                    ORDER BY fc.nome
                    SEPARATOR ', '
                ),
                'Sem movimentação'
            ) AS filial,

            MAX(
                c.data_compra
            ) AS ultima_compra,

            s.nome AS estado

        FROM fornecedores f

        LEFT JOIN status s
            ON s.id = f.status_id

        LEFT JOIN compras c
            ON c.fornecedor_id = f.id
           AND "
            . implode(
                ' AND ',
                $whereCompra
            ) . "

        LEFT JOIN filiais fc
            ON fc.id = c.filial_id
           AND fc.empresa_id = c.empresa_id

            LEFT JOIN status sc
    ON sc.id = c.status_id

        LEFT JOIN contas_pagar cp
            ON cp.id = c.conta_pagar_id
           AND cp.empresa_id = c.empresa_id

        WHERE "
            . implode(
                ' AND ',
                $where
            ) . "

        GROUP BY
            f.id,
            f.nome,
            f.nuit,
            f.telefone,
            f.contacto,
            f.email,
            s.nome

        ORDER BY
            valor_comprado DESC,
            f.nome ASC
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute($params);

        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        $valorComprado = 0.0;
        $saldoPagar = 0.0;
        $activos = 0;

        foreach ($registos as $registo) {

            $valorComprado +=
                (float)$registo['valor_comprado'];

            $saldoPagar +=
                (float)$registo['saldo'];

            if (
                in_array(
                    mb_strtolower(
                        (string)$registo['estado']
                    ),
                    ['activo', 'ativo'],
                    true
                )
            ) {
                $activos++;
            }
        }

        return [
            'resumo' => [
                'total_fornecedores' =>
                count($registos),

                'fornecedores_activos' =>
                $activos,

                'valor_comprado' =>
                $valorComprado,

                'saldo_pagar' =>
                $saldoPagar
            ],

            'registos' =>
            $registos
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUTOS
    |--------------------------------------------------------------------------
    */

    public function produtos(
        int $empresaId,
        array $filtros
    ): array {

        $whereProduto = [
            'p.empresa_id = :empresa_id'
        ];

        $params = [
            ':empresa_id' => $empresaId,
            ':empresa_vendas' => $empresaId,
            ':empresa_stock' => $empresaId
        ];

        if (!empty($filtros['estado'])) {
            $whereProduto[] =
                's.nome = :estado';

            $params[':estado'] =
                $filtros['estado'];
        }

        if (!empty($filtros['pesquisa'])) {

            $whereProduto[] = "
            (
                p.sku LIKE :pesquisa
                OR p.nome LIKE :pesquisa
                OR p.codigo_barras LIKE :pesquisa
                OR cat.nome LIKE :pesquisa
            )
        ";

            $params[':pesquisa'] =
                '%'
                . $filtros['pesquisa']
                . '%';
        }


        $whereVenda = [
            'v.empresa_id = :empresa_vendas'
        ];

        $whereStock = [
            'es.empresa_id = :empresa_stock'
        ];

        if (!empty($filtros['data_inicio'])) {

            $whereVenda[] =
                'v.data_venda >= :data_inicio_venda';

            $params[':data_inicio_venda'] =
                $filtros['data_inicio']
                . ' 00:00:00';
        }

        if (!empty($filtros['data_fim'])) {

            $whereVenda[] =
                'v.data_venda <= :data_fim_venda';

            $params[':data_fim_venda'] =
                $filtros['data_fim']
                . ' 23:59:59';
        }

        if (
            !empty($filtros['filial_id'])
            && (int)$filtros['filial_id'] > 0
        ) {

            $whereVenda[] =
                'v.filial_id = :filial_venda';

            $params[':filial_venda'] =
                (int)$filtros['filial_id'];

            $whereStock[] =
                'a_stock.filial_id = :filial_stock';

            $params[':filial_stock'] =
                (int)$filtros['filial_id'];
        }


        $sql = "
        SELECT
            p.id,
            p.sku AS codigo,
            p.nome AS produto,

            cat.nome AS categoria,

            COALESCE(
                vendas_produto.quantidade_vendida,
                0
            ) AS quantidade_vendida,

            COALESCE(
                vendas_produto.valor_vendido,
                0
            ) AS valor_vendido,

            COALESCE(
                vendas_produto.filiais,
                stock_produto.filiais,
                'Sem movimentação'
            ) AS filial,

            COALESCE(
    stock_produto.stock_actual,
    0
) AS stock_actual,

            s.nome AS estado

        FROM produtos p

        INNER JOIN categorias cat
            ON cat.id = p.categoria_id
           AND cat.empresa_id = p.empresa_id

        LEFT JOIN status s
            ON s.id = p.status_id

        LEFT JOIN (
            SELECT
                vi.produto_id,

                SUM(
                    vi.quantidade
                ) AS quantidade_vendida,

                SUM(
                    vi.subtotal
                    + vi.imposto
                    - vi.desconto
                ) AS valor_vendido,

                GROUP_CONCAT(
                    DISTINCT fv.nome
                    ORDER BY fv.nome
                    SEPARATOR ', '
                ) AS filiais

            FROM venda_itens vi

            INNER JOIN vendas v
                ON v.id = vi.venda_id

            LEFT JOIN filiais fv
                ON fv.id = v.filial_id
               AND fv.empresa_id = v.empresa_id

            WHERE "
            . implode(
                ' AND ',
                $whereVenda
            ) . "

            GROUP BY
                vi.produto_id

        ) vendas_produto
            ON vendas_produto.produto_id =
                p.id

                LEFT JOIN (
    SELECT
        es.empresa_id,
        es.produto_id,

        SUM(
            es.quantidade
        ) AS stock_actual,

        GROUP_CONCAT(
            DISTINCT fs.nome
            ORDER BY fs.nome
            SEPARATOR ', '
        ) AS filiais

    FROM estoque_saldos es

    INNER JOIN armazens a_stock
        ON a_stock.id = es.armazem_id
       AND a_stock.empresa_id = es.empresa_id

    LEFT JOIN filiais fs
        ON fs.id = a_stock.filial_id
       AND fs.empresa_id = a_stock.empresa_id

    WHERE "
            . implode(
                ' AND ',
                $whereStock
            ) . "

    GROUP BY
        es.empresa_id,
        es.produto_id

) stock_produto
    ON stock_produto.produto_id = p.id
   AND stock_produto.empresa_id = p.empresa_id

        WHERE "
            . implode(
                ' AND ',
                $whereProduto
            ) . "

        ORDER BY
            quantidade_vendida DESC,
            p.nome ASC
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute($params);

        $registos =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        $quantidadeVendida = 0.0;
        $valorVendido = 0.0;
        $activos = 0;

        foreach ($registos as $registo) {

            $quantidadeVendida +=
                (float)$registo['quantidade_vendida'];

            $valorVendido +=
                (float)$registo['valor_vendido'];

            if (
                in_array(
                    mb_strtolower(
                        (string)$registo['estado']
                    ),
                    ['activo', 'ativo'],
                    true
                )
            ) {
                $activos++;
            }
        }

        return [
            'resumo' => [
                'total_produtos' =>
                count($registos),

                'produtos_activos' =>
                $activos,

                'quantidade_vendida' =>
                $quantidadeVendida,

                'valor_vendido' =>
                $valorVendido
            ],

            'registos' =>
            $registos
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | STOCK
    |--------------------------------------------------------------------------
    */

    public function stock(
        int $empresaId,
        array $filtros
    ): array {

        $where = [
            'es.empresa_id = :empresa_id'
        ];

        $params = [
            ':empresa_id' => $empresaId
        ];


        /*
    |--------------------------------------------------------------------------
    | Filial
    |--------------------------------------------------------------------------
    */

        if (
            !empty($filtros['filial_id'])
            && (int)$filtros['filial_id'] > 0
        ) {
            $where[] =
                'a.filial_id = :filial_id';

            $params[':filial_id'] =
                (int)$filtros['filial_id'];
        }


        /*
    |--------------------------------------------------------------------------
    | Pesquisa
    |--------------------------------------------------------------------------
    */

        if (!empty($filtros['pesquisa'])) {

            $where[] = "
            (
                p.sku LIKE :pesquisa
                OR p.nome LIKE :pesquisa
                OR a.nome LIKE :pesquisa
                OR c.nome LIKE :pesquisa
            )
        ";

            $params[':pesquisa'] =
                '%'
                . $filtros['pesquisa']
                . '%';
        }


        /*
    |--------------------------------------------------------------------------
    | Situação
    |--------------------------------------------------------------------------
    |
    | Para STOCK, reutilizamos o filtro "estado" da interface
    | como situação de stock.
    |
    | NORMAL
    | STOCK_BAIXO
    | SEM_STOCK
    |--------------------------------------------------------------------------
    */

        $situacao =
            strtoupper(
                trim(
                    (string)(
                        $filtros['estado']
                        ?? ''
                    )
                )
            );


        if ($situacao === 'NORMAL') {

            $where[] = "
            es.quantidade
                > p.estoque_minimo
        ";
        } elseif ($situacao === 'STOCK_BAIXO') {

            $where[] = "
            es.quantidade > 0
            AND es.quantidade
                <= p.estoque_minimo
        ";
        } elseif ($situacao === 'SEM_STOCK') {

            $where[] =
                'es.quantidade <= 0';
        }


        /*
    |--------------------------------------------------------------------------
    | Consulta
    |--------------------------------------------------------------------------
    */

        $sql = "
        SELECT
            es.produto_id,

            p.sku AS codigo,

            p.nome AS produto,

            a.id AS armazem_id,

            a.nome AS armazem,

            COALESCE(
                f.nome,
                'Sem filial'
            ) AS filial,

            c.nome AS categoria,

            es.quantidade,

            p.estoque_minimo
                AS stock_minimo,

            es.custo_medio,

            (
                es.quantidade
                * es.custo_medio
            ) AS valor_stock,

            CASE

                WHEN es.quantidade <= 0
                    THEN 'SEM_STOCK'

                WHEN
                    es.quantidade
                    <= p.estoque_minimo
                    THEN 'STOCK_BAIXO'

                ELSE 'NORMAL'

            END AS situacao

        FROM estoque_saldos es

        INNER JOIN produtos p
            ON p.id = es.produto_id
           AND p.empresa_id = es.empresa_id

        INNER JOIN armazens a
            ON a.id = es.armazem_id
           AND a.empresa_id = es.empresa_id

        LEFT JOIN filiais f
            ON f.id = a.filial_id
           AND f.empresa_id = a.empresa_id

        INNER JOIN categorias c
            ON c.id = p.categoria_id
           AND c.empresa_id = p.empresa_id

        WHERE "
            . implode(
                ' AND ',
                $where
            ) . "

        ORDER BY
            p.nome ASC,
            a.nome ASC
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

        $produtos = [];

        $quantidadeStock = 0.0;

        $valorStock = 0.0;

        $abaixoMinimo = 0;


        foreach ($registos as $registo) {

            /*
        | Produto único
        */

            $produtos[(int)$registo['produto_id']] = true;


            /*
        | Quantidade total
        */

            $quantidadeStock +=
                (float)$registo['quantidade'];


            /*
        | Valor total
        */

            $valorStock +=
                (float)$registo['valor_stock'];


            /*
        | Stock baixo + sem stock
        */

            if (
                in_array(
                    $registo['situacao'],
                    [
                        'STOCK_BAIXO',
                        'SEM_STOCK'
                    ],
                    true
                )
            ) {
                $abaixoMinimo++;
            }
        }


        return [
            'resumo' => [

                'total_produtos' =>
                count($produtos),

                'quantidade_stock' =>
                $quantidadeStock,

                'valor_stock' =>
                $valorStock,

                'abaixo_minimo' =>
                $abaixoMinimo
            ],

            'registos' =>
            $registos
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Filtros auxiliares
    |--------------------------------------------------------------------------
    */

    private function aplicarFiltroDatas(
        array &$where,
        array &$params,
        array $filtros,
        string $campo
    ): void {
        if (!empty($filtros['data_inicio'])) {
            $where[] =
                "{$campo} >= :data_inicio";

            $params[':data_inicio'] =
                $filtros['data_inicio']
                . ' 00:00:00';
        }

        if (!empty($filtros['data_fim'])) {
            $where[] =
                "{$campo} <= :data_fim";

            $params[':data_fim'] =
                $filtros['data_fim']
                . ' 23:59:59';
        }
    }


    private function aplicarFiltroFilial(
        array &$where,
        array &$params,
        array $filtros,
        string $campo
    ): void {
        $filialId =
            (int)(
                $filtros['filial_id']
                ?? 0
            );

        if ($filialId <= 0) {
            return;
        }

        $where[] =
            "{$campo} = :filial_id";

        $params[':filial_id'] =
            $filialId;
    }
}
