<?php

declare(strict_types=1);

class Stock
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
            Database::conn();
    }


    /*
    |--------------------------------------------------------------------------
    | LISTAR STOCK
    |--------------------------------------------------------------------------
    */

    public function listar(
        int $empresaId,
        ?int $armazemId = null,
        ?int $categoriaId = null,
        ?string $situacao = null
    ): array {

        $sql = "
            SELECT

                es.empresa_id,
                es.armazem_id,
                es.produto_id,
                es.quantidade,
                es.atualizado_em,

                p.sku,
                p.nome AS produto_nome,
                p.estoque_minimo,
                p.preco_custo AS preco_custo_referencia,
                p.preco_venda,

                es.custo_medio,

                c.id AS categoria_id,
                c.nome AS categoria_nome,

                m.id AS marca_id,
                m.nome AS marca_nome,

                u.id AS unidade_id,
                u.codigo AS unidade_codigo,
                u.nome AS unidade_nome,
                u.casas_decimais AS unidade_casas_decimais,

                a.nome AS armazem_nome,

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

                END AS situacao_stock

            FROM estoque_saldos es

            INNER JOIN produtos p
                ON p.id = es.produto_id
               AND p.empresa_id = es.empresa_id

            INNER JOIN armazens a
                ON a.id = es.armazem_id
               AND a.empresa_id = es.empresa_id

            INNER JOIN categorias c
                ON c.id = p.categoria_id
               AND c.empresa_id = p.empresa_id

            LEFT JOIN marcas m
                ON m.id = p.marca_id
               AND m.empresa_id = p.empresa_id

            LEFT JOIN unidades u
                ON u.id = p.unidade_id
               AND u.empresa_id = p.empresa_id

            WHERE es.empresa_id = :empresa_id
        ";

        $parametros = [
            ':empresa_id' =>
                $empresaId
        ];


        if ($armazemId !== null) {

            $sql .= "
                AND es.armazem_id = :armazem_id
            ";

            $parametros[
                ':armazem_id'
            ] = $armazemId;
        }


        if ($categoriaId !== null) {

            $sql .= "
                AND p.categoria_id = :categoria_id
            ";

            $parametros[
                ':categoria_id'
            ] = $categoriaId;
        }


        if ($situacao === 'SEM_STOCK') {

            $sql .= "
                AND es.quantidade <= 0
            ";

        } elseif (
            $situacao === 'STOCK_BAIXO'
        ) {

            $sql .= "
                AND es.quantidade > 0
                AND es.quantidade
                    <= p.estoque_minimo
            ";

        } elseif (
            $situacao === 'NORMAL'
        ) {

            $sql .= "
                AND es.quantidade
                    > p.estoque_minimo
            ";
        }


        $sql .= "
            ORDER BY
                p.nome ASC,
                a.nome ASC
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute(
            $parametros
        );


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RESUMO
    |--------------------------------------------------------------------------
    */

    public function resumo(
        int $empresaId
    ): array {

        $sql = "
            SELECT

                COUNT(
                    CASE
                        WHEN es.quantidade > 0
                        THEN 1
                    END
                ) AS produtos_em_stock,

                COUNT(
                    CASE
                        WHEN
                            es.quantidade > 0
                            AND es.quantidade
                                <= p.estoque_minimo
                        THEN 1
                    END
                ) AS stock_baixo,

                COUNT(
                    CASE
                        WHEN es.quantidade <= 0
                        THEN 1
                    END
                ) AS sem_stock,

                COALESCE(
                    SUM(
                        es.quantidade
                        * es.custo_medio
                    ),
                    0
                ) AS valor_stock

            FROM estoque_saldos es

            INNER JOIN produtos p
                ON p.id = es.produto_id
               AND p.empresa_id = es.empresa_id

            WHERE es.empresa_id = :empresa_id
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute([
            ':empresa_id' =>
                $empresaId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: [
            'produtos_em_stock' => 0,
            'stock_baixo' => 0,
            'sem_stock' => 0,
            'valor_stock' => 0
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | ARMAZÉNS
    |--------------------------------------------------------------------------
    */

    public function armazens(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                id,
                nome

            FROM armazens

            WHERE empresa_id = :empresa_id

            ORDER BY nome ASC
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute([
            ':empresa_id' =>
                $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORIAS
    |--------------------------------------------------------------------------
    */

    public function categorias(
        int $empresaId
    ): array {

        $sql = "
            SELECT
                id,
                nome

            FROM categorias

            WHERE empresa_id = :empresa_id

            ORDER BY nome ASC
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute([
            ':empresa_id' =>
                $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ARMAZÉM PERTENCE À EMPRESA
    |--------------------------------------------------------------------------
    */

    public function armazemPertenceEmpresa(
        int $armazemId,
        int $empresaId
    ): bool {

        $stmt =
            $this->db->prepare("
                SELECT COUNT(*)

                FROM armazens

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");


        $stmt->execute([
            ':id' =>
                $armazemId,

            ':empresa_id' =>
                $empresaId
        ]);


        return
            (int)$stmt->fetchColumn()
            > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORIA PERTENCE À EMPRESA
    |--------------------------------------------------------------------------
    */

    public function categoriaPertenceEmpresa(
        int $categoriaId,
        int $empresaId
    ): bool {

        $stmt =
            $this->db->prepare("
                SELECT COUNT(*)

                FROM categorias

                WHERE id = :id
                  AND empresa_id = :empresa_id
            ");


        $stmt->execute([
            ':id' =>
                $categoriaId,

            ':empresa_id' =>
                $empresaId
        ]);


        return
            (int)$stmt->fetchColumn()
            > 0;
    }
}