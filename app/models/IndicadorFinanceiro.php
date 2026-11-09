<?php

declare(strict_types=1);

final class IndicadorFinanceiro
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // RESUMO PRINCIPAL
    // ==================================================

    public function resumo(
        int $empresaId,
        ?int $filialId,
        string $dataInicial,
        string $dataFinal
    ): array {

        $this->validarEmpresa($empresaId);

        $this->validarPeriodo(
            $dataInicial,
            $dataFinal
        );

        $saldoCaixa =
            $this->saldoCaixa(
                $empresaId,
                $filialId
            );

        $saldoBancario =
            $this->saldoBancario(
                $empresaId,
                $filialId
            );

        $totalReceber =
            $this->totalReceber(
                $empresaId,
                $filialId
            );

        $totalPagar =
            $this->totalPagar(
                $empresaId,
                $filialId
            );

        $recebidoPeriodo =
            $this->recebidoPeriodo(
                $empresaId,
                $dataInicial,
                $dataFinal
            );

        $pagoPeriodo =
            $this->pagoPeriodo(
                $empresaId,
                $dataInicial,
                $dataFinal
            );

        $resultadoPeriodo =
            $recebidoPeriodo
            - $pagoPeriodo;

        $vencidoReceber =
            $this->totalReceberVencido(
                $empresaId,
                $filialId
            );

        $vencidoPagar =
            $this->totalPagarVencido(
                $empresaId,
                $filialId
            );

        $saldoDisponivel =
            $saldoCaixa
            + $saldoBancario;

        $inadimplencia =
            $totalReceber > 0
            ? (
                $vencidoReceber
                / $totalReceber
            ) * 100
            : 0;

        $liquidezImediata =
            $totalPagar > 0
            ? $saldoDisponivel
            / $totalPagar
            : 0;

        $prazoMedioPagamento =
            $this->prazoMedioPagamento(
                $empresaId,
                $filialId,
                $dataInicial,
                $dataFinal
            );


        $prazoMedioRecebimento =
            $this->prazoMedioRecebimento(
                $empresaId,
                $filialId,
                $dataInicial,
                $dataFinal
            );

        return [
            'saldo_caixa' =>
            round($saldoCaixa, 2),

            'saldo_bancario' =>
            round($saldoBancario, 2),

            'saldo_disponivel' =>
            round($saldoDisponivel, 2),

            'total_receber' =>
            round($totalReceber, 2),

            'total_pagar' =>
            round($totalPagar, 2),

            'recebido_periodo' =>
            round($recebidoPeriodo, 2),

            'pago_periodo' =>
            round($pagoPeriodo, 2),

            'resultado_periodo' =>
            round($resultadoPeriodo, 2),

            'vencido_receber' =>
            round($vencidoReceber, 2),

            'vencido_pagar' =>
            round($vencidoPagar, 2),

            'total_vencido' =>
            round(
                $vencidoReceber
                    + $vencidoPagar,
                2
            ),

            'inadimplencia' =>
            round($inadimplencia, 2),

            'liquidez_imediata' =>
            round(
                $liquidezImediata,
                2
            ),

            'prazo_medio_pagamento' =>
            round(
                $prazoMedioPagamento,
                1
            ),

            'prazo_medio_recebimento' =>
            round(
                $prazoMedioRecebimento,
                1
            )
        ];
    }

    // ==================================================
    // SALDO EM CAIXA
    // ==================================================
    private function saldoCaixa(
        int $empresaId,
        ?int $filialId
    ): float {

        $sql = "
        SELECT
            COALESCE(
                SUM(caixa.saldo_atual),
                0
            ) AS saldo

        FROM caixas caixa

        WHERE caixa.empresa_id = :empresa_id
    ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
            AND caixa.filial_id = :filial_id
        ";

            $params[':filial_id'] = $filialId;
        }

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }
    // ==================================================
    // SALDO BANCÁRIO
    // ==================================================

    private function saldoBancario(
        int $empresaId,
        ?int $filialId
    ): float {

        $sql = "
        SELECT
            COALESCE(
                SUM(banco.saldo_atual),
                0
            ) AS saldo

        FROM bancos banco

        WHERE banco.empresa_id = :empresa_id
    ";

        $params = [
            ':empresa_id' => $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
            AND banco.filial_id = :filial_id
        ";

            $params[':filial_id'] = $filialId;
        }

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }

    // ==================================================
    // TOTAL A RECEBER
    // ==================================================

    private function totalReceber(
        int $empresaId,
        ?int $filialId
    ): float {

        $sql = "
            SELECT
                COALESCE(
                    SUM(saldo),
                    0
                )

            FROM contas_receber

            WHERE empresa_id =
                :empresa_id

              AND saldo > 0
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
                AND filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                $filialId;
        }

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }

    // ==================================================
    // TOTAL A PAGAR
    // ==================================================

    private function totalPagar(
        int $empresaId,
        ?int $filialId
    ): float {

        $sql = "
            SELECT
                COALESCE(
                    SUM(saldo),
                    0
                )

            FROM contas_pagar

            WHERE empresa_id =
                :empresa_id

              AND saldo > 0
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
                AND filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                $filialId;
        }

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }

    // ==================================================
    // RECEBIMENTOS DO PERÍODO
    // ==================================================

    private function recebidoPeriodo(
        int $empresaId,
        string $dataInicial,
        string $dataFinal
    ): float {

        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(
                    SUM(valor),
                    0
                )

            FROM recebimentos

            WHERE empresa_id =
                :empresa_id

              AND DATE(data_recebimento)
                  BETWEEN :data_inicial
                  AND :data_final
        ");

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':data_inicial' =>
            $dataInicial,

            ':data_final' =>
            $dataFinal
        ]);

        return (float)$stmt->fetchColumn();
    }

    // ==================================================
    // PAGAMENTOS DO PERÍODO
    // ==================================================

    private function pagoPeriodo(
        int $empresaId,
        string $dataInicial,
        string $dataFinal
    ): float {

        $stmt = $this->pdo->prepare("
        SELECT
            COALESCE(
                SUM(pagamento.valor),
                0
            )

        FROM pagamentos_contas_pagar pagamento

        WHERE pagamento.empresa_id = :empresa_id

          AND DATE(pagamento.data_pagamento)
              BETWEEN :data_inicial
              AND :data_final
    ");

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':data_inicial' => $dataInicial,
            ':data_final' => $dataFinal
        ]);

        return (float)$stmt->fetchColumn();
    }

    // ==================================================
    // PRAZO MÉDIO DE PAGAMENTO
    // ==================================================

    private function prazoMedioPagamento(
        int $empresaId,
        ?int $filialId,
        string $dataInicial,
        string $dataFinal
    ): float {

        $sql = "
        SELECT

            COALESCE(
                SUM(
                    pagamento.valor
                    *
                    GREATEST(
                        DATEDIFF(
                            DATE(
                                pagamento.data_pagamento
                            ),
                            conta.data_emissao
                        ),
                        0
                    )
                ),
                0
            )
            /
            NULLIF(
                SUM(
                    pagamento.valor
                ),
                0
            )

        FROM pagamentos_contas_pagar
            pagamento

        INNER JOIN contas_pagar conta
            ON conta.id =
                pagamento.conta_pagar_id

           AND conta.empresa_id =
                pagamento.empresa_id

        WHERE pagamento.empresa_id =
            :empresa_id

          AND DATE(
                pagamento.data_pagamento
              )
              BETWEEN :data_inicial
              AND :data_final
    ";


        $params = [
            ':empresa_id' =>
            $empresaId,

            ':data_inicial' =>
            $dataInicial,

            ':data_final' =>
            $dataFinal
        ];


        if ($filialId !== null) {

            $sql .= "
            AND conta.filial_id =
                :filial_id
        ";

            $params[':filial_id'] = $filialId;
        }


        $stmt =
            $this->pdo->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        $valor =
            $stmt->fetchColumn();


        return (
            $valor !== false
            && $valor !== null
        )
            ? (float)$valor
            : 0.0;
    }

    // ==================================================
    // PRAZO MÉDIO DE RECEBIMENTO
    // ==================================================

    private function prazoMedioRecebimento(
        int $empresaId,
        ?int $filialId,
        string $dataInicial,
        string $dataFinal
    ): float {

        $sql = "
        SELECT

            COALESCE(
                SUM(
                    recebimento.valor
                    *
                    GREATEST(
                        DATEDIFF(
                            DATE(
                                recebimento.data_recebimento
                            ),
                            conta.data_emissao
                        ),
                        0
                    )
                ),
                0
            )
            /
            NULLIF(
                SUM(
                    recebimento.valor
                ),
                0
            )

        FROM recebimentos recebimento

        INNER JOIN contas_receber conta
            ON conta.id =
                recebimento.conta_receber_id

           AND conta.empresa_id =
                recebimento.empresa_id

        WHERE recebimento.empresa_id =
            :empresa_id

          AND DATE(
                recebimento.data_recebimento
              )
              BETWEEN :data_inicial
              AND :data_final
    ";


        $params = [
            ':empresa_id' =>
            $empresaId,

            ':data_inicial' =>
            $dataInicial,

            ':data_final' =>
            $dataFinal
        ];


        if ($filialId !== null) {

            $sql .= "
            AND conta.filial_id =
                :filial_id
        ";

            $params[':filial_id'] = $filialId;
        }


        $stmt =
            $this->pdo->prepare(
                $sql
            );


        $stmt->execute(
            $params
        );


        $valor =
            $stmt->fetchColumn();


        return (
            $valor !== false
            && $valor !== null
        )
            ? (float)$valor
            : 0.0;
    }

    // ==================================================
    // CONTAS A RECEBER VENCIDAS
    // ==================================================

    private function totalReceberVencido(
        int $empresaId,
        ?int $filialId
    ): float {

        $sql = "
            SELECT
                COALESCE(
                    SUM(saldo),
                    0
                )

            FROM contas_receber

            WHERE empresa_id =
                :empresa_id

              AND saldo > 0

              AND data_vencimento
                  < CURDATE()
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
                AND filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                $filialId;
        }

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }

    // ==================================================
    // CONTAS A PAGAR VENCIDAS
    // ==================================================

    private function totalPagarVencido(
        int $empresaId,
        ?int $filialId
    ): float {

        $sql = "
            SELECT
                COALESCE(
                    SUM(saldo),
                    0
                )

            FROM contas_pagar

            WHERE empresa_id =
                :empresa_id

              AND saldo > 0

              AND data_vencimento
                  < CURDATE()
        ";

        $params = [
            ':empresa_id' =>
            $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
                AND filial_id =
                    :filial_id
            ";

            $params[':filial_id'] =
                $filialId;
        }

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute($params);

        return (float)$stmt->fetchColumn();
    }

    // ==================================================
    // FLUXO MENSAL
    // ==================================================

    public function fluxoMensal(
        int $empresaId,
        int $meses = 12
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $meses = max(
            1,
            min($meses, 36)
        );

        $dataInicial = date(
            'Y-m-01',
            strtotime(
                '-'
                    . ($meses - 1)
                    . ' months'
            )
        );

        $stmt = $this->pdo->prepare("
            SELECT
                periodo.ano_mes,

                SUM(periodo.entradas)
                    AS entradas,

                SUM(periodo.saidas)
                    AS saidas

            FROM
            (
                SELECT
                    DATE_FORMAT(
                        recebimento.data_recebimento,
                        '%Y-%m'
                    ) AS ano_mes,

                    SUM(recebimento.valor)
                        AS entradas,

                    0 AS saidas

                FROM recebimentos recebimento

                WHERE recebimento.empresa_id =
                    :empresa_recebimento

                  AND DATE(
                      recebimento.data_recebimento
                  ) >= :data_recebimento

                GROUP BY
                    DATE_FORMAT(
                        recebimento.data_recebimento,
                        '%Y-%m'
                    )


                UNION ALL


                SELECT
                    DATE_FORMAT(
                        pagamento.data_pagamento,
                        '%Y-%m'
                    ) AS ano_mes,

                    0 AS entradas,

                    SUM(pagamento.valor)
                        AS saidas

                FROM pagamentos_contas_pagar pagamento

                WHERE pagamento.empresa_id =
                    :empresa_pagamento

                  AND DATE(
                      pagamento.data_pagamento
                  ) >= :data_pagamento

                GROUP BY
                    DATE_FORMAT(
                        pagamento.data_pagamento,
                        '%Y-%m'
                    )

            ) periodo

            GROUP BY periodo.ano_mes

            ORDER BY periodo.ano_mes ASC
        ");

        $stmt->execute([
            ':empresa_recebimento' =>
            $empresaId,

            ':empresa_pagamento' =>
            $empresaId,

            ':data_recebimento' =>
            $dataInicial,

            ':data_pagamento' =>
            $dataInicial
        ]);

        $dadosBanco =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        $mapa = [];

        foreach ($dadosBanco as $linha) {
            $mapa[$linha['ano_mes']] = [
                'entradas' =>
                (float)$linha['entradas'],

                'saidas' =>
                (float)$linha['saidas']
            ];
        }

        $resultado = [];

        for (
            $indice = $meses - 1;
            $indice >= 0;
            $indice--
        ) {
            $anoMes = date(
                'Y-m',
                strtotime(
                    "-{$indice} months"
                )
            );

            $resultado[] = [
                'periodo' =>
                $anoMes,

                'label' =>
                $this->nomeMesAno(
                    $anoMes
                ),

                'entradas' =>
                round(
                    $mapa[$anoMes]['entradas']
                        ?? 0,
                    2
                ),

                'saidas' =>
                round(
                    $mapa[$anoMes]['saidas']
                        ?? 0,
                    2
                )
            ];
        }

        return $resultado;
    }

    // ==================================================
    // RECEITAS E DESPESAS POR CENTRO
    // ==================================================

    public function porCentroCusto(
        int $empresaId,
        string $dataInicial,
        string $dataFinal
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarPeriodo(
            $dataInicial,
            $dataFinal
        );

        $stmt = $this->pdo->prepare("
            SELECT
                centro.id,
                centro.codigo,
                centro.nome,
                centro.tipo,

                COALESCE(
                    receita.total_receitas,
                    0
                ) AS receitas,

                COALESCE(
                    despesa.total_despesas,
                    0
                ) AS despesas

            FROM centros_custo centro

            LEFT JOIN
            (
                SELECT
                    conta.centro_custo_id,

                    SUM(recebimento.valor)
                        AS total_receitas

                FROM recebimentos recebimento

                INNER JOIN contas_receber conta
                    ON conta.id =
                        recebimento.conta_receber_id

                WHERE recebimento.empresa_id =
                    :empresa_receita

                  AND DATE(
                      recebimento.data_recebimento
                  ) BETWEEN :receita_inicial
                    AND :receita_final

                GROUP BY
                    conta.centro_custo_id
            ) receita
                ON receita.centro_custo_id =
                    centro.id

            LEFT JOIN
(
    SELECT
        COALESCE(
            pagamento.centro_custo_id,
            conta.centro_custo_id
        ) AS centro_custo_id,

        SUM(pagamento.valor)
            AS total_despesas

    FROM pagamentos_contas_pagar pagamento

    INNER JOIN contas_pagar conta
        ON conta.id =
            pagamento.conta_pagar_id

    WHERE pagamento.empresa_id =
        :empresa_despesa

      AND pagamento.data_pagamento
          BETWEEN :despesa_inicial
          AND :despesa_final

    GROUP BY
        COALESCE(
            pagamento.centro_custo_id,
            conta.centro_custo_id
        )
) despesa
    ON despesa.centro_custo_id =
        centro.id

            WHERE centro.empresa_id =
                :empresa_centro

            ORDER BY centro.nome ASC
        ");

        $stmt->execute([
            ':empresa_receita' =>
            $empresaId,

            ':receita_inicial' =>
            $dataInicial,

            ':receita_final' =>
            $dataFinal,

            ':empresa_despesa' =>
            $empresaId,

            ':despesa_inicial' =>
            $dataInicial,

            ':despesa_final' =>
            $dataFinal,

            ':empresa_centro' =>
            $empresaId
        ]);

        $dados =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        return array_map(
            static function (
                array $linha
            ): array {

                $receitas =
                    (float)$linha['receitas'];

                $despesas =
                    (float)$linha['despesas'];

                return [
                    'id' =>
                    (int)$linha['id'],

                    'codigo' =>
                    $linha['codigo'],

                    'nome' =>
                    $linha['nome'],

                    'tipo' =>
                    $linha['tipo'],

                    'receitas' =>
                    round($receitas, 2),

                    'despesas' =>
                    round($despesas, 2),

                    'resultado' =>
                    round(
                        $receitas
                            - $despesas,
                        2
                    )
                ];
            },
            $dados
        );
    }

    // ==================================================
    // CONTAS VENCIDAS
    // ==================================================

    public function contasVencidas(
        int $empresaId,
        ?int $filialId = null,
        int $limite = 20
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $limite = max(
            1,
            min($limite, 100)
        );

        $sql = "
        SELECT
            CAST(
                'RECEBER'
                AS CHAR CHARACTER SET utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS tipo,

            conta.id,

            CAST(
                COALESCE(
                    conta.numero_documento,
                    ''
                )
                AS CHAR CHARACTER SET utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS numero_documento,

            CAST(
                cliente.nome
                AS CHAR CHARACTER SET utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS entidade,

            conta.data_vencimento,

            conta.saldo,

            DATEDIFF(
                CURDATE(),
                conta.data_vencimento
            ) AS dias_atraso

        FROM contas_receber conta

        INNER JOIN clientes cliente
            ON cliente.id =
                conta.cliente_id

        WHERE conta.empresa_id =
            :empresa_receber

          AND conta.saldo > 0

          AND conta.data_vencimento
              < CURDATE()
    ";

        $params = [
            ':empresa_receber' =>
            $empresaId,

            ':empresa_pagar' =>
            $empresaId
        ];

        if ($filialId !== null) {
            $sql .= "
            AND conta.filial_id =
                :filial_receber
        ";

            $params[':filial_receber'] =
                $filialId;
        }

        $sql .= "

        UNION ALL

        SELECT
            CAST(
                'PAGAR'
                AS CHAR CHARACTER SET utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS tipo,

            conta.id,

            CAST(
                COALESCE(
                    conta.numero_documento,
                    ''
                )
                AS CHAR CHARACTER SET utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS numero_documento,

            CAST(
                fornecedor.nome
                AS CHAR CHARACTER SET utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS entidade,

            conta.data_vencimento,

            conta.saldo,

            DATEDIFF(
                CURDATE(),
                conta.data_vencimento
            ) AS dias_atraso

        FROM contas_pagar conta

        INNER JOIN fornecedores fornecedor
            ON fornecedor.id =
                conta.fornecedor_id

        WHERE conta.empresa_id =
            :empresa_pagar

          AND conta.saldo > 0

          AND conta.data_vencimento
              < CURDATE()
    ";

        if ($filialId !== null) {
            $sql .= "
            AND conta.filial_id =
                :filial_pagar
        ";

            $params[':filial_pagar'] =
                $filialId;
        }

        $sql .= "
        ORDER BY
            dias_atraso DESC,
            saldo DESC

        LIMIT {$limite}
    ";

        $stmt =
            $this->pdo->prepare($sql);

        $stmt->execute(
            $params
        );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // CLIENTES COM MAIOR DÍVIDA
    // ==================================================

    public function clientesMaiorDivida(
        int $empresaId,
        int $limite = 10
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $limite = max(
            1,
            min($limite, 50)
        );

        $stmt = $this->pdo->prepare("
            SELECT
                cliente.id,
                cliente.nome,

                COUNT(conta.id)
                    AS quantidade_contas,

                SUM(conta.saldo)
                    AS total_divida

            FROM contas_receber conta

            INNER JOIN clientes cliente
                ON cliente.id =
                    conta.cliente_id

            WHERE conta.empresa_id =
                :empresa_id

              AND conta.saldo > 0

            GROUP BY
                cliente.id,
                cliente.nome

            ORDER BY
                total_divida DESC

            LIMIT {$limite}
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
    // FORNECEDORES COM MAIOR DÍVIDA
    // ==================================================

    public function fornecedoresMaiorDivida(
        int $empresaId,
        int $limite = 10
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $limite = max(
            1,
            min($limite, 50)
        );

        $stmt = $this->pdo->prepare("
            SELECT
                fornecedor.id,
                fornecedor.nome,

                COUNT(conta.id)
                    AS quantidade_contas,

                SUM(conta.saldo)
                    AS total_divida

            FROM contas_pagar conta

            INNER JOIN fornecedores fornecedor
                ON fornecedor.id =
                    conta.fornecedor_id

            WHERE conta.empresa_id =
                :empresa_id

              AND conta.saldo > 0

            GROUP BY
                fornecedor.id,
                fornecedor.nome

            ORDER BY
                total_divida DESC

            LIMIT {$limite}
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
    // UTILITÁRIOS
    // ==================================================

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida para os indicadores financeiros.'
            );
        }
    }

    private function validarPeriodo(
        string $dataInicial,
        string $dataFinal
    ): void {

        $this->validarData(
            $dataInicial,
            'Data inicial'
        );

        $this->validarData(
            $dataFinal,
            'Data final'
        );

        if ($dataInicial > $dataFinal) {
            throw new RuntimeException(
                'A data inicial não pode ser posterior à data final.'
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

    private function nomeMesAno(
        string $anoMes
    ): string {

        $nomes = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez'
        ];

        [$ano, $mes] =
            array_map(
                'intval',
                explode('-', $anoMes)
            );

        return $nomes[$mes]
            . '/'
            . $ano;
    }
}
