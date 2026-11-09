<?php

declare(strict_types=1);

final class BalancoPatrimonial
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // GERAR BALANÇO PATRIMONIAL
    // ==================================================

    public function gerar(
        int $empresaId,
        string $dataReferencia,
        ?int $filialId = null,
        ?int $planoContasId = null
    ): array {

        $this->validarEmpresa(
            $empresaId
        );

        $this->validarData(
            $dataReferencia,
            'Data de referência'
        );

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


        /*
        |--------------------------------------------------------------------------
        | CONTAS PATRIMONIAIS
        |--------------------------------------------------------------------------
        */

        $linhasPatrimoniais =
            $this->listarContasPatrimoniais(
                $empresaId,
                $dataReferencia,
                $statusContabilizado,
                $filialId,
                $planoContasId
            );


        /*
        |--------------------------------------------------------------------------
        | RESULTADO DO PERÍODO
        |--------------------------------------------------------------------------
        |
        | Enquanto não existir lançamento de encerramento,
        | o resultado do exercício é calculado através de:
        |
        | RENDIMENTOS - GASTOS
        |
        */

        $dataInicialExercicio =
            $this->dataInicialExercicio(
                $empresaId,
                $dataReferencia
            );

        $resultadoPeriodo =
            $this->calcularResultadoPeriodo(
                $empresaId,
                $dataInicialExercicio,
                $dataReferencia,
                $statusContabilizado,
                $filialId,
                $planoContasId
            );


        return $this->montarResultado(
            $linhasPatrimoniais,
            $resultadoPeriodo,
            $dataReferencia,
            $filialId,
            $planoContasId
        );
    }


    // ==================================================
    // LISTAR CONTAS PATRIMONIAIS
    // ==================================================


    private function listarContasPatrimoniais(
        int $empresaId,
        string $dataReferencia,
        int $statusContabilizado,
        ?int $filialId,
        ?int $planoContasId
    ): array {

        $sql = "
        SELECT
            conta.id,
            conta.codigo,
            conta.nome,
            conta.grupo,
            conta.natureza,
            conta.classe,
            conta.integracao_codigo,

            COALESCE(SUM(item.debito), 0) AS total_debito,
            COALESCE(SUM(item.credito), 0) AS total_credito

        FROM contas_contabilisticas conta

        LEFT JOIN lancamento_contabilistico_itens item
            ON item.conta_contabil_id = conta.id

            AND EXISTS (
                SELECT 1
                FROM lancamentos_contabilisticos lancamento
                WHERE lancamento.id = item.lancamento_id
                  AND lancamento.empresa_id = :empresa_movimento
                  AND lancamento.status_id = :status_movimento
                  AND lancamento.data_lancamento < DATE_ADD(
                      :data_referencia,
                      INTERVAL 1 DAY
                  )
    ";

        $params = [
            ':empresa_conta' => $empresaId,
            ':empresa_movimento' => $empresaId,
            ':status_movimento' => $statusContabilizado,
            ':data_referencia' => $dataReferencia,
        ];

        if ($filialId !== null) {
            $sql .= "
                  AND (
                      lancamento.filial_id = :filial_id
                      OR lancamento.filial_id IS NULL
                  )
        ";

            $params[':filial_id'] = $filialId;
        }

        $sql .= "
            )

        WHERE conta.empresa_id = :empresa_conta
          AND conta.tipo = 'ANALITICA'
          AND conta.aceita_lancamento = 1
          AND conta.grupo IN (
              'ATIVO',
              'PASSIVO',
              'CAPITAL_PROPRIO',
              'RESULTADO'
          )
    ";

        if ($planoContasId !== null) {
            $sql .= "
          AND conta.plano_contas_id = :plano_contas_id
        ";

            $params[':plano_contas_id'] = $planoContasId;
        }

        $sql .= "
        GROUP BY
            conta.id,
            conta.codigo,
            conta.nome,
            conta.grupo,
            conta.natureza,
            conta.classe,
            conta.integracao_codigo

        HAVING
            ABS(
                COALESCE(SUM(item.debito), 0)
                - COALESCE(SUM(item.credito), 0)
            ) > 0.009

        ORDER BY conta.codigo ASC
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    // ==================================================
    // CALCULAR RESULTADO DO PERÍODO
    // ==================================================

    private function calcularResultadoPeriodo(
        int $empresaId,
        string $dataInicial,
        string $dataReferencia,
        int $statusContabilizado,
        ?int $filialId,
        ?int $planoContasId
    ): array {

        $sql = "
        SELECT

            conta.grupo,

            COALESCE(
                SUM(item.debito),
                0
            ) AS total_debito,

            COALESCE(
                SUM(item.credito),
                0
            ) AS total_credito

        FROM lancamento_contabilistico_itens item

        INNER JOIN lancamentos_contabilisticos lancamento

            ON lancamento.id =
                item.lancamento_id

           AND lancamento.empresa_id =
                :empresa_id

           AND lancamento.status_id =
                :status_contabilizado

           AND lancamento.data_lancamento
                BETWEEN
                    :data_inicial
                AND
                    :data_referencia


        INNER JOIN contas_contabilisticas conta

            ON conta.id =
                item.conta_contabil_id

           AND conta.empresa_id =
                lancamento.empresa_id


        WHERE conta.grupo IN
        (
            'RENDIMENTO',
            'GASTO'
        )
    ";


        $params = [

            ':empresa_id' =>
            $empresaId,

            ':status_contabilizado' =>
            $statusContabilizado,

            ':data_inicial' =>
            $dataInicial,

            ':data_referencia' =>
            $dataReferencia
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
            conta.grupo
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


        $totalRendimentos =
            0.0;

        $totalGastos =
            0.0;


        foreach ($linhas as $linha) {

            $grupo =
                strtoupper(
                    trim(
                        (string)$linha['grupo']
                    )
                );


            $debito =
                round(
                    (float)$linha['total_debito'],
                    2
                );


            $credito =
                round(
                    (float)$linha['total_credito'],
                    2
                );


            if ($grupo === 'RENDIMENTO') {

                $totalRendimentos +=
                    $credito - $debito;
            }


            if ($grupo === 'GASTO') {

                $totalGastos +=
                    $debito - $credito;
            }
        }


        $totalRendimentos =
            round(
                $totalRendimentos,
                2
            );


        $totalGastos =
            round(
                $totalGastos,
                2
            );


        $resultado =
            round(
                $totalRendimentos
                    - $totalGastos,
                2
            );


        return [

            'data_inicial' =>
            $dataInicial,

            'data_final' =>
            $dataReferencia,

            'rendimentos' =>
            $totalRendimentos,

            'gastos' =>
            $totalGastos,

            'resultado' =>
            $resultado
        ];
    }

    // ==================================================
    // DATA INICIAL DO EXERCÍCIO
    // ==================================================

    private function dataInicialExercicio(
        int $empresaId,
        string $dataReferencia
    ): string {

        $stmt =
            $this->pdo->prepare("
            SELECT
                data_inicio

            FROM exercicios_contabilisticos

            WHERE empresa_id =
                :empresa_id

              AND :data_referencia
                  BETWEEN data_inicio
                  AND data_fim

            ORDER BY data_inicio DESC

            LIMIT 1
        ");


        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':data_referencia' =>
            $dataReferencia
        ]);


        $dataInicio =
            $stmt->fetchColumn();


        if (
            $dataInicio !== false
            && $dataInicio !== null
            && trim((string)$dataInicio) !== ''
        ) {

            return (string)$dataInicio;
        }


        /*
     * Fallback:
     *
     * caso não exista exercício cadastrado
     * para a data informada.
     */
        return
            substr(
                $dataReferencia,
                0,
                4
            )
            . '-01-01';
    }


    // ==================================================
    // MONTAR RESULTADO
    // ==================================================

    private function montarResultado(
        array $linhas,
        array $resultadoPeriodo,
        string $dataReferencia,
        ?int $filialId,
        ?int $planoContasId
    ): array {

        $ativos = [];

        $passivos = [];

        $capitalProprio = [];

        $resultados = [];


        $totalAtivo =
            0.0;

        $totalPassivo =
            0.0;

        $totalCapitalProprio =
            0.0;

        $totalResultadoContabilizado =
            0.0;


        /*
        |--------------------------------------------------------------------------
        | CONTAS PATRIMONIAIS
        |--------------------------------------------------------------------------
        */

        foreach ($linhas as $linha) {

            $grupo =
                strtoupper(
                    trim(
                        (string)$linha['grupo']
                    )
                );


            $debito =
                round(
                    (float)$linha['total_debito'],
                    2
                );


            $credito =
                round(
                    (float)$linha['total_credito'],
                    2
                );


            /*
             * Ativos:
             *
             * Débito - Crédito
             */
            if ($grupo === 'ATIVO') {

                $valor =
                    round(
                        $debito - $credito,
                        2
                    );
            } else {

                /*
                 * Passivo, Capital e Resultado:
                 *
                 * Crédito - Débito
                 */

                $valor =
                    round(
                        $credito - $debito,
                        2
                    );
            }


            $item = [

                'id' =>
                (int)$linha['id'],

                'codigo' =>
                (string)$linha['codigo'],

                'nome' =>
                (string)$linha['nome'],

                'grupo' =>
                $grupo,

                'natureza' =>
                (string)$linha['natureza'],

                'classe' =>
                $linha['classe'],

                'integracao_codigo' =>
                $linha['integracao_codigo'],

                'total_debito' =>
                $debito,

                'total_credito' =>
                $credito,

                'valor' =>
                $valor
            ];


            switch ($grupo) {


                case 'ATIVO':

                    $ativos[] =
                        $item;

                    $totalAtivo +=
                        $valor;

                    break;


                case 'PASSIVO':

                    $passivos[] =
                        $item;

                    $totalPassivo +=
                        $valor;

                    break;


                case 'CAPITAL_PROPRIO':

                    $capitalProprio[] =
                        $item;

                    $totalCapitalProprio +=
                        $valor;

                    break;


                case 'RESULTADO':

                    $resultados[] =
                        $item;

                    $totalResultadoContabilizado +=
                        $valor;

                    break;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | RESULTADO NÃO ENCERRADO DO PERÍODO
        |--------------------------------------------------------------------------
        |
        | No teu cenário atual ainda não existe lançamento
        | para a classe RESULTADO.
        |
        | Logo, mostramos o resultado calculado através
        | dos rendimentos e gastos.
        |
        */

        $resultadoCalculado =
            round(
                (float)(
                    $resultadoPeriodo['resultado']
                    ?? 0
                ),
                2
            );


        /*
         * Só adicionamos o resultado calculado se ainda
         * não existir resultado contabilizado por encerramento.
         *
         * Isso evita duplicar resultados quando futuramente
         * implementarmos o encerramento do exercício.
         */
        if (
            abs(
                $totalResultadoContabilizado
            ) <= 0.009

            &&

            abs(
                $resultadoCalculado
            ) > 0.009
        ) {

            $resultados[] = [

                'id' =>
                0,

                'codigo' =>
                'RESULTADO_PERIODO',

                'nome' =>
                'Resultado líquido do período',

                'grupo' =>
                'RESULTADO',

                'natureza' =>
                $resultadoCalculado >= 0
                    ? 'CREDORA'
                    : 'DEVEDORA',

                'classe' =>
                null,

                'integracao_codigo' =>
                'RESULTADO_PERIODO',

                'total_debito' =>
                $resultadoCalculado < 0
                    ? abs($resultadoCalculado)
                    : 0.0,

                'total_credito' =>
                $resultadoCalculado > 0
                    ? $resultadoCalculado
                    : 0.0,

                'valor' =>
                $resultadoCalculado
            ];


            $totalResultado =
                $resultadoCalculado;
        } else {

            $totalResultado =
                $totalResultadoContabilizado;
        }


        /*
        |--------------------------------------------------------------------------
        | ARREDONDAMENTOS
        |--------------------------------------------------------------------------
        */

        $totalAtivo =
            round(
                $totalAtivo,
                2
            );


        $totalPassivo =
            round(
                $totalPassivo,
                2
            );


        $totalCapitalProprio =
            round(
                $totalCapitalProprio,
                2
            );


        $totalResultado =
            round(
                $totalResultado,
                2
            );

        // ==================================================
        // CAPITAL PRÓPRIO TOTAL
        // ==================================================

        $totalCapitalProprioComResultado =
            round(
                $totalCapitalProprio
                    + $totalResultado,
                2
            );

        /*
        |--------------------------------------------------------------------------
        | TOTAL PASSIVO + CAPITAL + RESULTADO
        |--------------------------------------------------------------------------
        */

        $totalPassivoCapital =
            round(
                $totalPassivo
                    + $totalCapitalProprioComResultado,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | DIFERENÇA
        |--------------------------------------------------------------------------
        */

        $diferenca =
            round(
                $totalAtivo
                    - $totalPassivoCapital,
                2
            );


        return [

            'filtros' => [

                'data_referencia' =>
                $dataReferencia,

                'filial_id' =>
                $filialId,

                'plano_contas_id' =>
                $planoContasId
            ],


            // ==========================================
            // DETALHES DO BALANÇO
            // ==========================================

            'ativos' =>
            $ativos,

            'passivos' =>
            $passivos,

            'capital_proprio' =>
            $capitalProprio,

            'resultados' =>
            $resultados,


            // ==========================================
            // APURAÇÃO DO RESULTADO
            // ==========================================

            'apuracao_resultado' => [

                'rendimentos' =>
                round(
                    (float)(
                        $resultadoPeriodo['rendimentos']
                        ?? 0
                    ),
                    2
                ),

                'gastos' =>
                round(
                    (float)(
                        $resultadoPeriodo['gastos']
                        ?? 0
                    ),
                    2
                ),

                'resultado' =>
                $resultadoCalculado
            ],


            // ==========================================
            // TOTAIS
            // ==========================================

            'totais' => [

                'ativo' =>
                $totalAtivo,

                'passivo' =>
                $totalPassivo,

                /*
         * Apenas contas contabilizadas
         * da Classe 5.
         */
                'capital_proprio' =>
                $totalCapitalProprio,

                'capital_proprio_base' =>
                $totalCapitalProprio,

                /*
         * Resultado do exercício/período.
         */
                'resultado' =>
                $totalResultado,

                /*
         * Capital próprio económico atual:
         *
         * Classe 5 + Resultado atual.
         */
                'capital_proprio_total' =>
                $totalCapitalProprioComResultado,

                'passivo_capital_resultado' =>
                $totalPassivoCapital,

                'diferenca' =>
                $diferenca
            ],


            'equilibrado' =>
            abs(
                $diferenca
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
                    plano.padrao

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
    // VALIDAR FILIAL
    // ==================================================

    private function validarFilial(
        int $filialId,
        int $empresaId
    ): void {

        if ($filialId <= 0) {

            throw new RuntimeException(
                'Filial inválida.'
            );
        }


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
    // VALIDAR PLANO
    // ==================================================

    private function validarPlano(
        int $planoContasId,
        int $empresaId
    ): void {

        if ($planoContasId <= 0) {

            throw new RuntimeException(
                'Plano de contas inválido.'
            );
        }


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
    // VALIDAÇÕES
    // ==================================================

    private function validarEmpresa(
        int $empresaId
    ): void {

        if ($empresaId <= 0) {

            throw new RuntimeException(
                'Empresa inválida para gerar o Balanço Patrimonial.'
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

            ||

            $objeto->format('Y-m-d')
            !== $data
        ) {

            throw new RuntimeException(
                $campo . ' inválida.'
            );
        }
    }
}
