<?php

declare(strict_types=1);

final class ConfiguracaoContabilisticaRh
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function buscarPorEmpresa(
        int $empresaId
    ): ?array {
        $sql = "
        SELECT
            c.*,

            d.codigo AS diario_codigo,
            d.nome AS diario_nome,

            s.nome AS status_nome

        FROM configuracoes_contabilisticas_rh c

        LEFT JOIN diarios_contabilisticos d
            ON d.id = c.diario_contabilistico_id
           AND d.empresa_id = c.empresa_id

        LEFT JOIN status s
            ON s.id = c.status_id

        WHERE c.empresa_id = :empresa_id

        LIMIT 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        $registo = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $registo ?: null;
    }

    public function listarContas(
        int $empresaId,
        string $finalidade = ''
    ): array {
        $filtros = [
            'salario_base' => "
            AND grupo = 'GASTO'
            AND natureza = 'DEVEDORA'
            AND (
                integracao_codigo = 'SALARIO_BASE'
                OR nome LIKE '%salário%'
                OR nome LIKE '%salario%'
            )
        ",

            'subsidios' => "
            AND grupo = 'GASTO'
            AND natureza = 'DEVEDORA'
            AND (
                integracao_codigo = 'SUBSIDIOS'
                OR nome LIKE '%subsídio%'
                OR nome LIKE '%subsidio%'
            )
        ",

            'horas_extras' => "
            AND grupo = 'GASTO'
            AND natureza = 'DEVEDORA'
            AND (
                integracao_codigo = 'HORAS_EXTRAS'
                OR nome LIKE '%hora%extra%'
            )
        ",

            'outros_proventos' => "
            AND grupo = 'GASTO'
            AND natureza = 'DEVEDORA'
        ",

            'encargos_patronais' => "
            AND grupo = 'GASTO'
            AND natureza = 'DEVEDORA'
        ",

            'salarios_pagar' => "
            AND grupo = 'PASSIVO'
            AND natureza = 'CREDORA'
            AND (
                integracao_codigo = 'SALARIOS_PAGAR'
                OR nome LIKE '%salário%pagar%'
                OR nome LIKE '%salario%pagar%'
            )
        ",

            'inss_pagar' => "
            AND grupo = 'PASSIVO'
            AND natureza = 'CREDORA'
            AND (
                integracao_codigo = 'INSS_PAGAR'
                OR nome LIKE '%INSS%'
            )
        ",

            'irps_pagar' => "
            AND grupo = 'PASSIVO'
            AND natureza = 'CREDORA'
            AND (
                integracao_codigo = 'IRPS_PAGAR'
                OR nome LIKE '%IRPS%'
            )
        ",

            'outros_descontos' => "
            AND grupo = 'PASSIVO'
            AND natureza = 'CREDORA'
        ",

            'banco' => "
            AND conta_banco = 1
            AND grupo = 'ATIVO'
            AND natureza = 'DEVEDORA'
        ",

            'caixa' => "
            AND conta_caixa = 1
            AND grupo = 'ATIVO'
            AND natureza = 'DEVEDORA'
        "
        ];

        $filtroFinalidade =
            $filtros[$finalidade] ?? '';

        $sql = "
        SELECT
            id,
            codigo,
            nome,
            natureza,
            grupo,
            integracao_codigo,
            conta_banco,
            conta_caixa

        FROM contas_contabilisticas

        WHERE empresa_id = :empresa_id
          AND tipo = 'ANALITICA'
          AND aceita_lancamento = 1

          {$filtroFinalidade}

        ORDER BY codigo ASC, nome ASC
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contaValida(
        int $contaId,
        int $empresaId
    ): bool {
        if ($contaId <= 0) {
            return false;
        }

        $sql = "
        SELECT COUNT(*)
        FROM contas_contabilisticas
        WHERE id = :id
          AND empresa_id = :empresa_id
          AND tipo = 'ANALITICA'
          AND aceita_lancamento = 1
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $contaId,
            ':empresa_id' => $empresaId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function diarioValido(
        int $diarioId,
        int $empresaId
    ): bool {
        if ($diarioId <= 0) {
            return false;
        }

        $sql = "
        SELECT COUNT(*)
        FROM diarios_contabilisticos
        WHERE id = :id
          AND empresa_id = :empresa_id
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $diarioId,
            ':empresa_id' => $empresaId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function statusValido(int $statusId): bool
    {
        if ($statusId <= 0) {
            return false;
        }

        $sql = "
        SELECT COUNT(*)
        FROM status
        WHERE id = :id
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $statusId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }


    public function listarDiarios(
        int $empresaId
    ): array {
        $sql = "
            SELECT
                id,
                codigo,
                nome

            FROM diarios_contabilisticos

            WHERE empresa_id = :empresa_id

            ORDER BY
                codigo ASC,
                nome ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function listarStatus(): array
    {
        $sql = "
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo = 'configuracao_contabilistica_rh'

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

    public function diarioPertenceEmpresa(
        int $diarioId,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM diarios_contabilisticos

            WHERE id = :id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':id' => $diarioId,
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

            WHERE id = :id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':id' => $contaId,
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

            WHERE id = :id
              AND tipo = 'configuracao_contabilistica_rh'
        ");

        $stmt->execute([
            ':id' => $statusId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function criar(
        int $empresaId,
        int $usuarioId,
        array $dados
    ): int {
        $sql = "
            INSERT INTO configuracoes_contabilisticas_rh (
                uuid,
                empresa_id,
                diario_contabilistico_id,
                conta_salarios_pagar_id,
                conta_salario_base_id,
                conta_inss_pagar_id,
                conta_irps_pagar_id,
                conta_outros_descontos_id,
                conta_encargos_patronais_id,
                conta_banco_padrao_id,
                conta_caixa_padrao_id,
                agrupar_por_filial,
                agrupar_por_centro_custo,
                contabilizar_por_rubrica,
                gerar_lancamento_automaticamente,
                status_id,
                criado_por,
                atualizado_por
            ) VALUES (
                :uuid,
                :empresa_id,
                :diario_contabilistico_id,
                :conta_salarios_pagar_id,
                :conta_salario_base_id,
                :conta_inss_pagar_id,
                :conta_irps_pagar_id,
                :conta_outros_descontos_id,
                :conta_encargos_patronais_id,
                :conta_banco_padrao_id,
                :conta_caixa_padrao_id,
                :agrupar_por_filial,
                :agrupar_por_centro_custo,
                :contabilizar_por_rubrica,
                :gerar_lancamento_automaticamente,
                :status_id,
                :criado_por,
                :atualizado_por
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':uuid' =>
            $this->gerarUuid(),

            ':empresa_id' =>
            $empresaId,

            ':diario_contabilistico_id' =>
            $dados['diario_contabilistico_id'],

            ':conta_salarios_pagar_id' =>
            $dados['conta_salarios_pagar_id'],

            ':conta_salario_base_id' =>
            $dados['conta_salario_base_id'],

            ':conta_inss_pagar_id' =>
            $dados['conta_inss_pagar_id'],

            ':conta_irps_pagar_id' =>
            $dados['conta_irps_pagar_id'],

            ':conta_outros_descontos_id' =>
            $dados['conta_outros_descontos_id'],

            ':conta_encargos_patronais_id' =>
            $dados['conta_encargos_patronais_id'],

            ':conta_banco_padrao_id' =>
            $dados['conta_banco_padrao_id'],

            ':conta_caixa_padrao_id' =>
            $dados['conta_caixa_padrao_id'],

            ':agrupar_por_filial' =>
            $dados['agrupar_por_filial'],

            ':agrupar_por_centro_custo' =>
            $dados['agrupar_por_centro_custo'],

            ':contabilizar_por_rubrica' =>
            $dados['contabilizar_por_rubrica'],

            ':gerar_lancamento_automaticamente' =>
            $dados['gerar_lancamento_automaticamente'],

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

    public function atualizar(
        int $id,
        int $empresaId,
        int $usuarioId,
        array $dados
    ): bool {
        $sql = "
            UPDATE configuracoes_contabilisticas_rh

            SET
                diario_contabilistico_id =
                    :diario_contabilistico_id,

                conta_salarios_pagar_id =
                    :conta_salarios_pagar_id,

                conta_salario_base_id =
                    :conta_salario_base_id,

                conta_inss_pagar_id =
                    :conta_inss_pagar_id,

                conta_irps_pagar_id =
                    :conta_irps_pagar_id,

                conta_outros_descontos_id =
                    :conta_outros_descontos_id,

                conta_encargos_patronais_id =
                    :conta_encargos_patronais_id,

                conta_banco_padrao_id =
                    :conta_banco_padrao_id,

                conta_caixa_padrao_id =
                    :conta_caixa_padrao_id,

                agrupar_por_filial =
                    :agrupar_por_filial,

                agrupar_por_centro_custo =
                    :agrupar_por_centro_custo,

                contabilizar_por_rubrica =
                    :contabilizar_por_rubrica,

                gerar_lancamento_automaticamente =
                    :gerar_lancamento_automaticamente,

                status_id =
                    :status_id,

                atualizado_por =
                    :atualizado_por

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':diario_contabilistico_id' =>
            $dados['diario_contabilistico_id'],

            ':conta_salarios_pagar_id' =>
            $dados['conta_salarios_pagar_id'],

            ':conta_salario_base_id' =>
            $dados['conta_salario_base_id'],

            ':conta_inss_pagar_id' =>
            $dados['conta_inss_pagar_id'],

            ':conta_irps_pagar_id' =>
            $dados['conta_irps_pagar_id'],

            ':conta_outros_descontos_id' =>
            $dados['conta_outros_descontos_id'],

            ':conta_encargos_patronais_id' =>
            $dados['conta_encargos_patronais_id'],

            ':conta_banco_padrao_id' =>
            $dados['conta_banco_padrao_id'],

            ':conta_caixa_padrao_id' =>
            $dados['conta_caixa_padrao_id'],

            ':agrupar_por_filial' =>
            $dados['agrupar_por_filial'],

            ':agrupar_por_centro_custo' =>
            $dados['agrupar_por_centro_custo'],

            ':contabilizar_por_rubrica' =>
            $dados['contabilizar_por_rubrica'],

            ':gerar_lancamento_automaticamente' =>
            $dados['gerar_lancamento_automaticamente'],

            ':status_id' =>
            $dados['status_id'],

            ':atualizado_por' =>
            $usuarioId,

            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);
    }

    private function gerarUuid(): string
    {
        $dados = random_bytes(16);

        $dados[6] =
            chr(
                ord($dados[6])
                    & 0x0f
                    | 0x40
            );

        $dados[8] =
            chr(
                ord($dados[8])
                    & 0x3f
                    | 0x80
            );

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(
                bin2hex($dados),
                4
            )
        );
    }
}
