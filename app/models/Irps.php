<?php

declare(strict_types=1);

final class Irps
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // TABELA VIGENTE
    // ==================================================

    public function tabelaVigente(
        ?int $ano = null
    ): ?array {

        $ano =
            $ano !== null && $ano > 0
            ? $ano
            : (int)date('Y');

        $stmt =
            $this->pdo->prepare("
                SELECT
                    id,
                    uuid,
                    nome,
                    descricao,
                    ano_inicio,
                    ano_fim,
                    moeda,
                    ativo,
                    criado_em,
                    atualizado_em

                FROM irps_tabelas

                WHERE ativo = 1

                  AND ano_inicio <= :ano_inicio

                  AND (
                        ano_fim IS NULL
                        OR ano_fim >= :ano_fim
                  )

                ORDER BY
                    ano_inicio DESC,
                    id DESC

                LIMIT 1
            ");

        $stmt->execute([
            ':ano_inicio' => $ano,
            ':ano_fim' => $ano
        ]);

        $tabela =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$tabela) {
            return null;
        }

        $tabela['escaloes'] =
            $this->listarEscaloes(
                (int)$tabela['id']
            );

        return $tabela;
    }

    // ==================================================
    // LISTAR TODAS AS TABELAS
    // ==================================================

    public function listarTabelas(): array
    {
        $stmt =
            $this->pdo->query("
                SELECT
                    id,
                    uuid,
                    nome,
                    descricao,
                    ano_inicio,
                    ano_fim,
                    moeda,
                    ativo,
                    criado_em,
                    atualizado_em

                FROM irps_tabelas

                ORDER BY
                    ano_inicio DESC,
                    id DESC
            ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // BUSCAR TABELA
    // ==================================================

    public function buscarTabela(
        int $id
    ): ?array {

        if ($id <= 0) {
            return null;
        }

        $stmt =
            $this->pdo->prepare("
                SELECT
                    id,
                    uuid,
                    nome,
                    descricao,
                    ano_inicio,
                    ano_fim,
                    moeda,
                    ativo,
                    criado_em,
                    atualizado_em

                FROM irps_tabelas

                WHERE id = :id

                LIMIT 1
            ");

        $stmt->execute([
            ':id' => $id
        ]);

        $tabela =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$tabela) {
            return null;
        }

        $tabela['escaloes'] =
            $this->listarEscaloes(
                $id
            );

        return $tabela;
    }

    // ==================================================
    // LISTAR ESCALÕES
    // ==================================================

    public function listarEscaloes(
        int $tabelaId
    ): array {

        if ($tabelaId <= 0) {
            return [];
        }

        $stmt =
            $this->pdo->prepare("
                SELECT
                    id,
                    tabela_id,
                    ordem,
                    limite_inferior,
                    limite_superior,

                    valor_base_0,
                    valor_base_1,
                    valor_base_2,
                    valor_base_3,
                    valor_base_4_mais,

                    coeficiente

                FROM irps_escaloes

                WHERE tabela_id =
                    :tabela_id

                ORDER BY
                    ordem ASC,
                    limite_inferior ASC
            ");

        $stmt->execute([
            ':tabela_id' =>
            $tabelaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // RESUMO
    // ==================================================

    public function resumo(): array
    {
        $stmt =
            $this->pdo->query("
                SELECT
                    COUNT(*) AS total_tabelas,

                    SUM(
                        CASE
                            WHEN ativo = 1
                            THEN 1
                            ELSE 0
                        END
                    ) AS tabelas_ativas

                FROM irps_tabelas
            ");

        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            ) ?: [];

        $stmtEscaloes =
            $this->pdo->query("
                SELECT
                    COUNT(*)
                FROM irps_escaloes
            ");

        return [
            'total_tabelas' =>
            (int)(
                $dados['total_tabelas']
                ?? 0
            ),

            'tabelas_ativas' =>
            (int)(
                $dados['tabelas_ativas']
                ?? 0
            ),

            'total_escaloes' =>
            (int)$stmtEscaloes
                ->fetchColumn()
        ];
    }
}
