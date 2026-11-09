<?php

declare(strict_types=1);

class MovimentoStock
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
            Database::conn();
    }


    public function listar(
        int $empresaId,
        ?string $tipo = null,
        ?int $armazemId = null,
        ?string $dataInicio = null,
        ?string $dataFim = null
    ): array {

        $sql = "
            SELECT

                me.id,
                me.empresa_id,
                me.tipo,
                me.status_id,
                me.usuario_id,

                me.armazem_origem_id,
                me.armazem_destino_id,

                me.observacoes,
                me.data_movimento,
                me.criado_em,

                ao.nome AS armazem_origem_nome,
                ad.nome AS armazem_destino_nome,

                u.nome AS usuario_nome,

                s.nome AS status_nome,

                COUNT(
                    mei.id
                ) AS total_itens,

                COALESCE(
                    SUM(
                        mei.quantidade
                    ),
                    0
                ) AS quantidade_total,

                COALESCE(
                    SUM(
                        mei.subtotal
                    ),
                    0
                ) AS valor_total

            FROM movimentos_estoque me

            LEFT JOIN armazens ao
                ON ao.id = me.armazem_origem_id
               AND ao.empresa_id = me.empresa_id

            LEFT JOIN armazens ad
                ON ad.id = me.armazem_destino_id
               AND ad.empresa_id = me.empresa_id

            INNER JOIN usuarios u
                ON u.id = me.usuario_id

            INNER JOIN status s
                ON s.id = me.status_id

            LEFT JOIN movimento_estoque_itens mei
                ON mei.movimento_id = me.id

            WHERE me.empresa_id = :empresa_id
        ";


        $params = [
            ':empresa_id' =>
            $empresaId
        ];


        if ($tipo !== null) {

            $sql .= "
                AND me.tipo = :tipo
            ";

            $params[':tipo'] =
                $tipo;
        }


        if ($armazemId !== null) {

            $sql .= "
                AND (
                    me.armazem_origem_id = :armazem_id
                    OR
                    me.armazem_destino_id = :armazem_id
                )
            ";

            $params[':armazem_id'] =
                $armazemId;
        }


        if ($dataInicio !== null) {

            $sql .= "
                AND DATE(me.data_movimento)
                    >= :data_inicio
            ";

            $params[':data_inicio'] =
                $dataInicio;
        }


        if ($dataFim !== null) {

            $sql .= "
                AND DATE(me.data_movimento)
                    <= :data_fim
            ";

            $params[':data_fim'] =
                $dataFim;
        }


        $sql .= "
            GROUP BY
                me.id,
                me.empresa_id,
                me.tipo,
                me.status_id,
                me.usuario_id,
                me.armazem_origem_id,
                me.armazem_destino_id,
                me.observacoes,
                me.data_movimento,
                me.criado_em,
                ao.nome,
                ad.nome,
                u.nome,
                s.nome

            ORDER BY
                me.data_movimento DESC,
                me.id DESC
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    public function buscar(
        int $id,
        int $empresaId
    ): ?array {

        $sql = "
            SELECT

                me.*,

                ao.nome AS armazem_origem_nome,

                ad.nome AS armazem_destino_nome,

                u.nome AS usuario_nome,

                s.nome AS status_nome

            FROM movimentos_estoque me

            LEFT JOIN armazens ao
                ON ao.id = me.armazem_origem_id
               AND ao.empresa_id = me.empresa_id

            LEFT JOIN armazens ad
                ON ad.id = me.armazem_destino_id
               AND ad.empresa_id = me.empresa_id

            INNER JOIN usuarios u
                ON u.id = me.usuario_id

            INNER JOIN status s
                ON s.id = me.status_id

            WHERE me.id = :id
              AND me.empresa_id = :empresa_id

            LIMIT 1
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);


        $movimento =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $movimento ?: null;
    }


    public function itens(
        int $movimentoId,
        int $empresaId
    ): array {

        $sql = "
            SELECT

                mei.id,
                mei.produto_id,
                mei.quantidade,
                mei.custo_unitario,
                mei.subtotal,

                p.sku,
                p.nome AS produto_nome,

                u.codigo AS unidade_codigo,
                u.nome AS unidade_nome,
                u.casas_decimais

            FROM movimento_estoque_itens mei

            INNER JOIN movimentos_estoque me
                ON me.id = mei.movimento_id

            INNER JOIN produtos p
                ON p.id = mei.produto_id
               AND p.empresa_id = me.empresa_id

            LEFT JOIN unidades u
                ON u.id = p.unidade_id
               AND u.empresa_id = p.empresa_id

            WHERE mei.movimento_id = :movimento_id
              AND me.empresa_id = :empresa_id

            ORDER BY mei.id ASC
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute([
            ':movimento_id' =>
            $movimentoId,

            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    public function resumo(
        int $empresaId
    ): array {

        $sql = "
            SELECT

                COUNT(
                    CASE
                        WHEN tipo = 'ENTRADA'
                        THEN 1
                    END
                ) AS entradas,

                COUNT(
                    CASE
                        WHEN tipo = 'SAIDA'
                        THEN 1
                    END
                ) AS saidas,

                COUNT(
                    CASE
                        WHEN tipo = 'TRANSFERENCIA'
                        THEN 1
                    END
                ) AS transferencias,

                COUNT(
                    CASE
                        WHEN tipo = 'AJUSTE'
                        THEN 1
                    END
                ) AS ajustes

            FROM movimentos_estoque

            WHERE empresa_id = :empresa_id
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
            'entradas' => 0,
            'saidas' => 0,
            'transferencias' => 0,
            'ajustes' => 0
        ];
    }


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
}
