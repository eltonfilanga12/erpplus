<?php

declare(strict_types=1);

final class Balancete
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // GERAR BALANCETE
    // ==================================================

    public function gerar(
        int $empresaId,
        string $dataInicio,
        string $dataFim,
        ?int $filialId = null,
        ?int $planoContasId = null
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarData(
            $dataInicio,
            'Data inicial'
        );

        $this->validarData(
            $dataFim,
            'Data final'
        );

        if ($dataFim < $dataInicio) {
            throw new RuntimeException(
                'A data final não pode ser anterior à data inicial.'
            );
        }

        if (
            $filialId !== null
            && $filialId <= 0
        ) {
            throw new RuntimeException(
                'Filial inválida.'
            );
        }

        if (
            $planoContasId !== null
            && $planoContasId <= 0
        ) {
            throw new RuntimeException(
                'Plano de contas inválido.'
            );
        }

        if ($filialId !== null) {
            $this->validarFilial(
                $filialId,
                $empresaId
            );
        }

        if ($planoContasId !== null) {
            $this->validarPlano(
                $planoContasId,
                $empresaId
            );
        }

        $statusContabilizado =
            $this->statusLancamento(
                'Contabilizado'
            );

        $sql = "
            SELECT
                conta.id,
                conta.codigo,
                conta.nome,
                conta.tipo,
                conta.natureza,
                conta.grupo,

                COALESCE(
                    SUM(
                        CASE

                            WHEN lancamento.data_lancamento
                                < :data_inicio_saldo

                            THEN item.debito
                                 - item.credito

                            ELSE 0

                        END
                    ),
                    0
                ) AS saldo_inicial_liquido,

                COALESCE(
                    SUM(
                        CASE

                            WHEN lancamento.data_lancamento
                                BETWEEN
                                    :data_inicio_debito
                                    AND
                                    :data_fim_debito

                            THEN item.debito

                            ELSE 0

                        END
                    ),
                    0
                ) AS debitos_periodo,

                COALESCE(
                    SUM(
                        CASE

                            WHEN lancamento.data_lancamento
                                BETWEEN
                                    :data_inicio_credito
                                    AND
                                    :data_fim_credito

                            THEN item.credito

                            ELSE 0

                        END
                    ),
                    0
                ) AS creditos_periodo

            FROM contas_contabilisticas conta

            LEFT JOIN lancamento_contabilistico_itens item
                ON item.conta_contabil_id =
                    conta.id

            LEFT JOIN lancamentos_contabilisticos lancamento
                ON lancamento.id =
                    item.lancamento_id

               AND lancamento.empresa_id =
                    conta.empresa_id

               AND lancamento.status_id =
                    :status_contabilizado

               AND lancamento.data_lancamento
                    <= :data_fim_limite

            WHERE conta.empresa_id =
                :empresa_id

              AND conta.tipo =
                'ANALITICA'
        ";

        $params = [
            ':data_inicio_saldo' =>
            $dataInicio,

            ':data_inicio_debito' =>
            $dataInicio,

            ':data_fim_debito' =>
            $dataFim,

            ':data_inicio_credito' =>
            $dataInicio,

            ':data_fim_credito' =>
            $dataFim,

            ':data_fim_limite' =>
            $dataFim,

            ':status_contabilizado' =>
            $statusContabilizado,

            ':empresa_id' =>
            $empresaId
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

        $sql .= "
            GROUP BY
                conta.id,
                conta.codigo,
                conta.nome,
                conta.tipo,
                conta.natureza,
                conta.grupo

            HAVING
                ABS(
                    saldo_inicial_liquido
                ) > 0.009

                OR ABS(
                    debitos_periodo
                ) > 0.009

                OR ABS(
                    creditos_periodo
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

        return $this->calcularSaldos(
            $linhas,
            $dataInicio,
            $dataFim,
            $filialId,
            $planoContasId
        );
    }

    // ==================================================
    // CALCULAR SALDOS
    // ==================================================

    private function calcularSaldos(
        array $linhas,
        string $dataInicio,
        string $dataFim,
        ?int $filialId,
        ?int $planoContasId
    ): array {

        $resultado = [];

        $totais = [
            'saldo_inicial_devedor' =>
            0.0,

            'saldo_inicial_credor' =>
            0.0,

            'debitos' =>
            0.0,

            'creditos' =>
            0.0,

            'saldo_final_devedor' =>
            0.0,

            'saldo_final_credor' =>
            0.0
        ];

        foreach ($linhas as $linha) {

            $saldoInicialLiquido =
                round(
                    (float)$linha['saldo_inicial_liquido'],
                    2
                );

            $debitos =
                round(
                    (float)$linha['debitos_periodo'],
                    2
                );

            $creditos =
                round(
                    (float)$linha['creditos_periodo'],
                    2
                );

            $saldoFinalLiquido =
                round(
                    $saldoInicialLiquido
                        + $debitos
                        - $creditos,
                    2
                );

            $saldoInicialDevedor =
                $saldoInicialLiquido > 0
                ? $saldoInicialLiquido
                : 0.0;

            $saldoInicialCredor =
                $saldoInicialLiquido < 0
                ? abs(
                    $saldoInicialLiquido
                )
                : 0.0;

            $saldoFinalDevedor =
                $saldoFinalLiquido > 0
                ? $saldoFinalLiquido
                : 0.0;

            $saldoFinalCredor =
                $saldoFinalLiquido < 0
                ? abs(
                    $saldoFinalLiquido
                )
                : 0.0;

            $resultado[] = [
                'id' =>
                (int)$linha['id'],

                'codigo' =>
                (string)$linha['codigo'],

                'nome' =>
                (string)$linha['nome'],

                'tipo' =>
                (string)$linha['tipo'],

                'natureza' =>
                (string)$linha['natureza'],

                'grupo' =>
                (string)$linha['grupo'],

                'saldo_inicial_devedor' =>
                $saldoInicialDevedor,

                'saldo_inicial_credor' =>
                $saldoInicialCredor,

                'debitos' =>
                $debitos,

                'creditos' =>
                $creditos,

                'saldo_final_devedor' =>
                $saldoFinalDevedor,

                'saldo_final_credor' =>
                $saldoFinalCredor
            ];

            $totais['saldo_inicial_devedor'] += $saldoInicialDevedor;

            $totais['saldo_inicial_credor'] += $saldoInicialCredor;

            $totais['debitos'] +=
                $debitos;

            $totais['creditos'] +=
                $creditos;

            $totais['saldo_final_devedor'] += $saldoFinalDevedor;

            $totais['saldo_final_credor'] += $saldoFinalCredor;
        }

        foreach ($totais as $campo => $valor) {
            $totais[$campo] =
                round(
                    $valor,
                    2
                );
        }

        $diferencaMovimentos =
            round(
                $totais['debitos']
                    - $totais['creditos'],
                2
            );

        $diferencaSaldosFinais =
            round(
                $totais['saldo_final_devedor']
                    - $totais['saldo_final_credor'],
                2
            );

        return [
            'filtros' => [
                'data_inicio' =>
                $dataInicio,

                'data_fim' =>
                $dataFim,

                'filial_id' =>
                $filialId,

                'plano_contas_id' =>
                $planoContasId
            ],

            'linhas' =>
            $resultado,

            'totais' =>
            $totais,

            'diferenca_movimentos' =>
            $diferencaMovimentos,

            'diferenca_saldos_finais' =>
            $diferencaSaldosFinais,

            'equilibrado' =>
            abs(
                $diferencaMovimentos
            ) <= 0.009
        ];
    }

    // ==================================================
    // LISTAR PLANOS
    // ==================================================

    public function listarPlanos(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $stmt =
            $this->pdo->prepare("
                SELECT
                    plano.id,
                    plano.codigo,
                    plano.nome,
                    plano.padrao,
                    plano.status_id,
                    status.nome AS status_nome

                FROM planos_contas plano

                INNER JOIN status
                    ON status.id =
                        plano.status_id

                   AND status.tipo =
                        'plano_contas'

                WHERE plano.empresa_id =
                    :empresa_id

                  AND LOWER(status.nome)
                        IN
                        (
                            'activo',
                            'ativo',
                            'activa',
                            'ativa'
                        )

                ORDER BY
                    plano.padrao DESC,
                    plano.codigo ASC
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
    // LISTAR FILIAIS
    // ==================================================

    public function listarFiliais(
        int $empresaId
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

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
    // VALIDAR PLANO
    // ==================================================

    private function validarPlano(
        int $planoContasId,
        int $empresaId
    ): void {

        $stmt =
            $this->pdo->prepare("
                SELECT id

                FROM planos_contas

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                LIMIT 1
            ");

        $stmt->execute([
            ':id' =>
            $planoContasId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'O plano de contas selecionado não pertence à empresa.'
            );
        }
    }

    // ==================================================
    // VALIDAR FILIAL
    // ==================================================

    private function validarFilial(
        int $filialId,
        int $empresaId
    ): void {

        $stmt =
            $this->pdo->prepare("
                SELECT id

                FROM filiais

                WHERE id =
                    :id

                  AND empresa_id =
                    :empresa_id

                LIMIT 1
            ");

        $stmt->execute([
            ':id' =>
            $filialId,

            ':empresa_id' =>
            $empresaId
        ]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException(
                'A filial selecionada não pertence à empresa.'
            );
        }
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
            (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException(
                "Status '{$nome}' do lançamento contabilístico não encontrado."
            );
        }

        return $id;
    }

    // ==================================================
    // VALIDAÇÕES GERAIS
    // ==================================================

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para gerar o balancete.'
            );
        }
    }

    private function validarData(
        string $data,
        string $campo
    ): void {

        $objeto =
            DateTime::createFromFormat(
                'Y-m-d',
                $data
            );

        if (
            !$objeto
            || $objeto->format('Y-m-d')
            !== $data
        ) {
            throw new RuntimeException(
                $campo . ' inválida.'
            );
        }
    }
}
