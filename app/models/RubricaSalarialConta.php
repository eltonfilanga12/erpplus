<?php

declare(strict_types=1);

final class RubricaSalarialConta
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function listar(
        int $empresaId
    ): array {
        $sql = "
            SELECT
                r.id AS rubrica_salarial_id,
                r.codigo AS rubrica_codigo,
                r.nome AS rubrica_nome,
                r.descricao AS rubrica_descricao,
                r.tipo AS rubrica_tipo,
                r.categoria AS rubrica_categoria,
                r.status_id AS rubrica_status_id,

                rc.id AS mapeamento_id,
                rc.conta_debito_id,
                rc.conta_credito_id,
                rc.usar_conta_salarios_pagar,
                rc.centro_custo_obrigatorio,
                rc.status_id AS mapeamento_status_id,

                cd.codigo AS conta_debito_codigo,
                cd.nome AS conta_debito_nome,

                cc.codigo AS conta_credito_codigo,
                cc.nome AS conta_credito_nome,

                sm.nome AS mapeamento_status_nome

            FROM rubricas_salariais r

            LEFT JOIN rubricas_salariais_contas rc
                ON rc.rubrica_salarial_id = r.id
               AND rc.empresa_id = r.empresa_id

            LEFT JOIN contas_contabilisticas cd
                ON cd.id = rc.conta_debito_id
               AND cd.empresa_id = r.empresa_id

            LEFT JOIN contas_contabilisticas cc
                ON cc.id = rc.conta_credito_id
               AND cc.empresa_id = r.empresa_id

            LEFT JOIN status sm
                ON sm.id = rc.status_id
               AND sm.tipo = 'rubrica_contabilistica'

            WHERE r.empresa_id = :empresa_id

            ORDER BY
                r.ordem ASC,
                r.tipo ASC,
                r.nome ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function buscar(
        int $empresaId,
        int $rubricaId
    ): ?array {
        $sql = "
            SELECT
                rc.*

            FROM rubricas_salariais_contas rc

            WHERE rc.empresa_id = :empresa_id
              AND rc.rubrica_salarial_id = :rubrica_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':rubrica_id' => $rubricaId
        ]);

        $registo = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $registo ?: null;
    }

    public function listarContas(
        int $empresaId
    ): array {

        $sql = "
        SELECT
            id,
            codigo,
            nome,
            natureza,
            grupo,
            integracao_codigo,
            conta_caixa,
            conta_banco

        FROM contas_contabilisticas

        WHERE empresa_id = :empresa_id

          AND tipo = 'ANALITICA'

          AND aceita_lancamento = 1

        ORDER BY
            codigo ASC,
            nome ASC
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

    public function obterRubrica(
    int $rubricaId,
    int $empresaId
): ?array {
    $stmt = $this->db->prepare("
        SELECT
            id,
            codigo,
            nome,
            tipo,
            categoria

        FROM rubricas_salariais

        WHERE id = :rubrica_id
          AND empresa_id = :empresa_id

        LIMIT 1
    ");

    $stmt->execute([
        ':rubrica_id' => $rubricaId,
        ':empresa_id' => $empresaId
    ]);

    $rubrica =
        $stmt->fetch(PDO::FETCH_ASSOC);

    return $rubrica ?: null;
}

    public function listarStatus(): array
    {
        $sql = "
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo = 'rubrica_contabilistica'

            ORDER BY
                CASE
                    WHEN nome IN ('Activo', 'Ativo')
                    THEN 1

                    WHEN nome IN ('Inactivo', 'Inativo')
                    THEN 2

                    ELSE 3
                END,
                nome ASC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rubricaPertenceEmpresa(
        int $rubricaId,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM rubricas_salariais

            WHERE id = :rubrica_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':rubrica_id' => $rubricaId,
            ':empresa_id' => $empresaId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function contaPertenceEmpresa(
        int $contaId,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM contas_contabilisticas

            WHERE id = :conta_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':conta_id' => $contaId,
            ':empresa_id' => $empresaId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function statusExiste(
        int $statusId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM status

            WHERE id = :status_id
              AND tipo = 'rubrica_contabilistica'
        ");

        $stmt->execute([
            ':status_id' => $statusId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function guardar(
        int $empresaId,
        int $rubricaId,
        int $usuarioId,
        array $dados
    ): int {
        $existente =
            $this->buscar(
                $empresaId,
                $rubricaId
            );

        if ($existente) {
            $sql = "
                UPDATE rubricas_salariais_contas

                SET
                    conta_debito_id =
                        :conta_debito_id,

                    conta_credito_id =
                        :conta_credito_id,

                    usar_conta_salarios_pagar =
                        :usar_conta_salarios_pagar,

                    centro_custo_obrigatorio =
                        :centro_custo_obrigatorio,

                    status_id =
                        :status_id,

                    atualizado_por =
                        :atualizado_por

                WHERE id = :id
                  AND empresa_id = :empresa_id

                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':conta_debito_id' =>
                $dados['conta_debito_id'],

                ':conta_credito_id' =>
                $dados['conta_credito_id'],

                ':usar_conta_salarios_pagar' =>
                $dados['usar_conta_salarios_pagar'],

                ':centro_custo_obrigatorio' =>
                $dados['centro_custo_obrigatorio'],

                ':status_id' =>
                $dados['status_id'],

                ':atualizado_por' =>
                $usuarioId,

                ':id' =>
                $existente['id'],

                ':empresa_id' =>
                $empresaId
            ]);

            return (int) $existente['id'];
        }

        $sql = "
            INSERT INTO rubricas_salariais_contas (
                empresa_id,
                rubrica_salarial_id,
                conta_debito_id,
                conta_credito_id,
                usar_conta_salarios_pagar,
                centro_custo_obrigatorio,
                status_id,
                criado_por,
                atualizado_por
            ) VALUES (
                :empresa_id,
                :rubrica_salarial_id,
                :conta_debito_id,
                :conta_credito_id,
                :usar_conta_salarios_pagar,
                :centro_custo_obrigatorio,
                :status_id,
                :criado_por,
                :atualizado_por
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':rubrica_salarial_id' =>
            $rubricaId,

            ':conta_debito_id' =>
            $dados['conta_debito_id'],

            ':conta_credito_id' =>
            $dados['conta_credito_id'],

            ':usar_conta_salarios_pagar' =>
            $dados['usar_conta_salarios_pagar'],

            ':centro_custo_obrigatorio' =>
            $dados['centro_custo_obrigatorio'],

            ':status_id' =>
            $dados['status_id'],

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);

        return (int) $this->db
            ->lastInsertId();
    }

    public function eliminar(
        int $empresaId,
        int $rubricaId
    ): bool {
        $stmt = $this->db->prepare("
            DELETE FROM rubricas_salariais_contas

            WHERE empresa_id = :empresa_id
              AND rubrica_salarial_id = :rubrica_id
        ");

        return $stmt->execute([
            ':empresa_id' => $empresaId,
            ':rubrica_id' => $rubricaId
        ]);
    }

    public function iniciarTransacao(): void
    {
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    public function confirmarTransacao(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
    }

    public function cancelarTransacao(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
