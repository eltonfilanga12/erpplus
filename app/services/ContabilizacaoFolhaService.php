<?php

declare(strict_types=1);

final class ContabilizacaoFolhaService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Contabiliza uma folha salarial já aprovada.
     */
    public function contabilizarFolha(
        int $folhaId,
        int $usuarioId
    ): array {
        if ($folhaId <= 0) {
            throw new InvalidArgumentException(
                'A folha salarial informada é inválida.'
            );
        }

        if ($usuarioId <= 0) {
            throw new InvalidArgumentException(
                'O utilizador responsável é inválido.'
            );
        }

        $iniciouTransacao = false;

        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
                $iniciouTransacao = true;
            }

            $folha = $this->buscarFolha($folhaId);

            if (!$folha) {
                throw new RuntimeException(
                    'A folha salarial não foi encontrada.'
                );
            }

            $empresaId = (int)$folha['empresa_id'];
            $periodoId = (int)$folha['periodo_id'];

            if ($this->jaFoiContabilizada($folhaId, $empresaId)) {
                throw new RuntimeException(
                    'Esta folha salarial já foi contabilizada.'
                );
            }

            $configuracao =
                $this->buscarConfiguracaoContabilistica(
                    $empresaId
                );

            if (!$configuracao) {
                throw new RuntimeException(
                    'A empresa não possui configuração contabilística de RH.'
                );
            }

            $periodoContabilistico =
                $this->buscarPeriodoContabilistico(
                    $empresaId,
                    $periodoId
                );

            if (!$periodoContabilistico) {
                throw new RuntimeException(
                    'Não foi encontrado um período contabilístico compatível.'
                );
            }

            $statusContabilizadoId =
                $this->buscarStatusId(
                    'lancamento_contabilistico',
                    'Contabilizado'
                );

            $itens =
                $this->montarItensContabilisticos(
                    $folha,
                    $configuracao
                );

            if (empty($itens)) {
                throw new RuntimeException(
                    'Não foram gerados itens contabilísticos para a folha.'
                );
            }

            $totais = $this->calcularTotais($itens);

            if (
                round($totais['debito'], 2)
                !== round($totais['credito'], 2)
            ) {
                throw new RuntimeException(
                    'O lançamento contabilístico não está equilibrado.'
                );
            }

            $lancamentoId =
                $this->criarLancamento(
                    $folha,
                    $configuracao,
                    $periodoContabilistico,
                    $statusContabilizadoId,
                    $totais,
                    $usuarioId
                );

            $this->gravarItens(
                $lancamentoId,
                $itens
            );

            if ($iniciouTransacao) {
                $this->pdo->commit();
            }

            return [
                'sucesso' => true,
                'mensagem' =>
                'Folha salarial contabilizada com sucesso.',
                'lancamento_id' => $lancamentoId,
                'total_debito' => $totais['debito'],
                'total_credito' => $totais['credito']
            ];
        } catch (Throwable $e) {
            if (
                $iniciouTransacao
                && $this->pdo->inTransaction()
            ) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function buscarFolha(int $folhaId): ?array
    {
        $sql = "
            SELECT
                fs.*,
                ps.codigo AS periodo_codigo,
                ps.descricao AS periodo_descricao,
                ps.data_inicio,
                ps.data_fim

            FROM folhas_salariais fs

            INNER JOIN periodos_salariais ps
                ON ps.id = fs.periodo_id

            WHERE fs.id = :id

            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $folhaId
        ]);

        $folha = $stmt->fetch(PDO::FETCH_ASSOC);

        return $folha ?: null;
    }

    private function jaFoiContabilizada(
        int $folhaId,
        int $empresaId
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM lancamentos_contabilisticos

            WHERE empresa_id = :empresa_id
              AND origem = 'SALARIO'
              AND origem_id = :folha_id
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':folha_id' => $folhaId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function buscarConfiguracaoContabilistica(
        int $empresaId
    ): ?array {
        $sql = "
            SELECT c.*

            FROM configuracoes_contabilisticas_rh c

            INNER JOIN status s
                ON s.id = c.status_id

            WHERE c.empresa_id = :empresa_id
              AND LOWER(s.nome) IN (
                  'activo',
                  'ativo',
                  'activa',
                  'ativa'
              )

            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        $configuracao =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $configuracao ?: null;
    }

    private function buscarPeriodoContabilistico(
        int $empresaId,
        int $periodoSalarialId
    ): ?array {
        $sql = "
            SELECT
                pc.*,
                ec.id AS exercicio_id

            FROM periodos_salariais ps

            INNER JOIN periodos_contabilisticos pc
                ON pc.empresa_id = ps.empresa_id
               AND pc.data_inicio <= ps.data_fim
               AND pc.data_fim >= ps.data_inicio

            INNER JOIN exercicios_contabilisticos ec
                ON ec.id = pc.exercicio_id

            WHERE ps.id = :periodo_salarial_id
              AND ps.empresa_id = :empresa_id

            ORDER BY pc.data_inicio DESC

            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':periodo_salarial_id' =>
            $periodoSalarialId,

            ':empresa_id' =>
            $empresaId
        ]);

        $periodo =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $periodo ?: null;
    }

    private function montarItensContabilisticos(
        array $folha,
        array $configuracao
    ): array {
        $itens = [];

        $totalSalarioBase =
            (float)($folha['total_salario_base'] ?? 0);

        $totalSubsidios =
            (float)($folha['total_subsidios'] ?? 0);

        $totalHorasExtras =
            (float)($folha['total_horas_extras'] ?? 0);

        $totalOutrosProventos =
            (float)($folha['total_outros_proventos'] ?? 0);

        $totalInss =
            (float)($folha['total_inss'] ?? 0);

        $totalInssPatronal =
            (float)($folha['total_inss_patronal'] ?? 0);

        $totalIrps =
            (float)($folha['total_irps'] ?? 0);

        $totalOutrosDescontos =
            (float)($folha['total_outros_descontos'] ?? 0);

        $totalLiquido =
            (float)($folha['total_liquido'] ?? 0);

        $this->adicionarItem(
            $itens,
            (int)$configuracao['conta_salario_base_id'],
            'Salário base da folha salarial',
            $totalSalarioBase,
            0
        );

        $this->adicionarItem(
            $itens,
            $this->idOuNull(
                $configuracao['conta_subsidios_id'] ?? null
            ),
            'Subsídios da folha salarial',
            $totalSubsidios,
            0
        );

        $this->adicionarItem(
            $itens,
            $this->idOuNull(
                $configuracao['conta_horas_extras_id'] ?? null
            ),
            'Horas extras da folha salarial',
            $totalHorasExtras,
            0
        );

        $this->adicionarItem(
            $itens,
            $this->idOuNull(
                $configuracao['conta_outros_proventos_id'] ?? null
            ),
            'Outros proventos da folha salarial',
            $totalOutrosProventos,
            0
        );

        $this->adicionarItem(
            $itens,
            $this->idOuNull(
                $configuracao['conta_inss_pagar_id'] ?? null
            ),
            'INSS retido na folha salarial',
            0,
            $totalInss
        );

        $this->adicionarItem(
            $itens,
            $this->idOuNull($configuracao['conta_encargos_patronais_id'] ?? null),
            'Encargo patronal de INSS da folha salarial',
            $totalInssPatronal,
            0
        );

        $this->adicionarItem(
            $itens,
            $this->idOuNull($configuracao['conta_inss_pagar_id'] ?? null),
            'INSS patronal a pagar',
            0,
            $totalInssPatronal
        );

        $this->adicionarItem(
            $itens,
            $this->idOuNull(
                $configuracao['conta_irps_pagar_id'] ?? null
            ),
            'IRPS retido na folha salarial',
            0,
            $totalIrps
        );

        $this->adicionarItem(
            $itens,
            $this->idOuNull(
                $configuracao['conta_outros_descontos_id'] ?? null
            ),
            'Outros descontos da folha salarial',
            0,
            $totalOutrosDescontos
        );

        $this->adicionarItem(
            $itens,
            (int)$configuracao['conta_salarios_pagar_id'],
            'Salários líquidos a pagar',
            0,
            $totalLiquido
        );

        return $itens;
    }

    private function adicionarItem(
        array &$itens,
        ?int $contaId,
        string $descricao,
        float $debito,
        float $credito
    ): void {
        if (
            $debito <= 0
            && $credito <= 0
        ) {
            return;
        }

        if (
            $contaId === null
            || $contaId <= 0
        ) {
            throw new RuntimeException(
                'Não existe conta contabilística configurada para: '
                    . $descricao
            );
        }

        $itens[] = [
            'conta_contabil_id' => $contaId,
            'centro_custo_id' => null,
            'cliente_id' => null,
            'fornecedor_id' => null,
            'descricao' => $descricao,
            'debito' => round($debito, 2),
            'credito' => round($credito, 2)
        ];
    }

    private function calcularTotais(array $itens): array
    {
        $debito = 0.0;
        $credito = 0.0;

        foreach ($itens as $item) {
            $debito += (float)$item['debito'];
            $credito += (float)$item['credito'];
        }

        return [
            'debito' => round($debito, 2),
            'credito' => round($credito, 2)
        ];
    }

    private function criarLancamento(
        array $folha,
        array $configuracao,
        array $periodoContabilistico,
        int $statusId,
        array $totais,
        int $usuarioId
    ): int {
        $numero =
            $this->proximoNumeroLancamento(
                (int)$folha['empresa_id'],
                (int)$configuracao['diario_contabilistico_id']
            );

        $codigoFolha =
            (string)($folha['codigo'] ?? $folha['id']);

        $descricao =
            'Contabilização da folha salarial '
            . $codigoFolha;

        $sql = "
            INSERT INTO lancamentos_contabilisticos (
                empresa_id,
                filial_id,
                exercicio_id,
                periodo_id,
                diario_id,
                numero,
                numero_documento,
                referencia,
                data_lancamento,
                data_documento,
                descricao,
                origem,
                origem_id,
                status_id,
                total_debito,
                total_credito,
                contabilizado_em,
                contabilizado_por,
                criado_por,
                atualizado_por
            )
            VALUES (
                :empresa_id,
                NULL,
                :exercicio_id,
                :periodo_id,
                :diario_id,
                :numero,
                :numero_documento,
                :referencia,
                :data_lancamento,
                :data_documento,
                :descricao,
                'SALARIO',
                :origem_id,
                :status_id,
                :total_debito,
                :total_credito,
                NOW(),
                :contabilizado_por,
                :criado_por,
                :atualizado_por
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            (int)$folha['empresa_id'],

            ':exercicio_id' =>
            (int)$periodoContabilistico['exercicio_id'],

            ':periodo_id' =>
            (int)$periodoContabilistico['id'],

            ':diario_id' =>
            (int)$configuracao['diario_contabilistico_id'],

            ':numero' => $numero,

            ':numero_documento' =>
            $codigoFolha,

            ':referencia' =>
            'RH-' . $codigoFolha,

            ':data_lancamento' =>
            (string)$folha['data_fim'],

            ':data_documento' =>
            (string)$folha['data_fim'],

            ':descricao' => $descricao,

            ':origem_id' =>
            (int)$folha['id'],

            ':status_id' => $statusId,

            ':total_debito' =>
            $totais['debito'],

            ':total_credito' =>
            $totais['credito'],

            ':contabilizado_por' =>
            $usuarioId,

            ':criado_por' =>
            $usuarioId,

            ':atualizado_por' =>
            $usuarioId
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function gravarItens(
        int $lancamentoId,
        array $itens
    ): void {
        $sql = "
            INSERT INTO lancamento_contabilistico_itens (
                lancamento_id,
                conta_contabil_id,
                centro_custo_id,
                cliente_id,
                fornecedor_id,
                descricao,
                debito,
                credito,
                ordem
            )
            VALUES (
                :lancamento_id,
                :conta_contabil_id,
                :centro_custo_id,
                :cliente_id,
                :fornecedor_id,
                :descricao,
                :debito,
                :credito,
                :ordem
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($itens as $indice => $item) {
            $stmt->execute([
                ':lancamento_id' =>
                $lancamentoId,

                ':conta_contabil_id' =>
                $item['conta_contabil_id'],

                ':centro_custo_id' =>
                $item['centro_custo_id'],

                ':cliente_id' =>
                $item['cliente_id'],

                ':fornecedor_id' =>
                $item['fornecedor_id'],

                ':descricao' =>
                $item['descricao'],

                ':debito' =>
                $item['debito'],

                ':credito' =>
                $item['credito'],

                ':ordem' =>
                $indice + 1
            ]);
        }
    }

    private function proximoNumeroLancamento(
        int $empresaId,
        int $diarioId
    ): int {
        $sql = "
            SELECT
                COALESCE(MAX(numero), 0) + 1

            FROM lancamentos_contabilisticos

            WHERE empresa_id = :empresa_id
              AND diario_id = :diario_id
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':diario_id' => $diarioId
        ]);

        return (int)$stmt->fetchColumn();
    }

    private function buscarStatusId(
        string $tipo,
        string $nome
    ): int {
        $sql = "
            SELECT id

            FROM status

            WHERE tipo = :tipo
              AND LOWER(nome) = LOWER(:nome)

            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':tipo' => $tipo,
            ':nome' => $nome
        ]);

        $statusId = (int)$stmt->fetchColumn();

        if ($statusId <= 0) {
            throw new RuntimeException(
                'O estado "' . $nome
                    . '" do tipo "' . $tipo
                    . '" não foi encontrado.'
            );
        }

        return $statusId;
    }

    private function idOuNull(
        mixed $valor
    ): ?int {
        $id = (int)$valor;

        return $id > 0
            ? $id
            : null;
    }
}
