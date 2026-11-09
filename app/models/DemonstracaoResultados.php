<?php

declare(strict_types=1);

final class DemonstracaoResultados
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ==================================================
    // GERAR DEMONSTRAÇÃO DE RESULTADOS
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

        $linhas =
            $this->listarContasResultado(
                $empresaId,
                $dataInicio,
                $dataFim,
                $statusContabilizado,
                $filialId,
                $planoContasId
            );

        $impostoRendimento =
            $this->calcularImpostoRendimento(
                $empresaId,
                $dataInicio,
                $dataFim,
                $statusContabilizado,
                $filialId,
                $planoContasId
            );

        return $this->montarResultado(
            $linhas,
            $dataInicio,
            $dataFim,
            $filialId,
            $planoContasId,
            $impostoRendimento
        );
    }

    // ==================================================
    // LISTAR CONTAS DE RESULTADO
    // ==================================================

    private function listarContasResultado(
        int $empresaId,
        string $dataInicio,
        string $dataFim,
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

                COALESCE(
                    SUM(item.debito),
                    0
                ) AS total_debito,

                COALESCE(
                    SUM(item.credito),
                    0
                ) AS total_credito

            FROM contas_contabilisticas conta

            INNER JOIN lancamento_contabilistico_itens item
                ON item.conta_contabil_id =
                    conta.id

            INNER JOIN lancamentos_contabilisticos lancamento
                ON lancamento.id =
                    item.lancamento_id

               AND lancamento.empresa_id =
                    conta.empresa_id

            WHERE conta.empresa_id =
                :empresa_id

              AND conta.tipo =
                'ANALITICA'

              AND conta.aceita_lancamento =
                1

              AND lancamento.status_id =
                :status_contabilizado

              AND lancamento.data_lancamento
                    BETWEEN
                        :data_inicio
                        AND
                        :data_fim

             AND conta.grupo IN
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

            ':data_inicio' =>
            $dataInicio,

            ':data_fim' =>
            $dataFim
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
                conta.grupo,
                conta.natureza,
                conta.classe,
                conta.integracao_codigo

            HAVING
                ABS(total_debito) > 0.009
                OR ABS(total_credito) > 0.009

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

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    // ==================================================
    // CALCULAR IMPOSTO SOBRE O RENDIMENTO
    // ==================================================

    // ==================================================
    // CALCULAR IMPOSTO SOBRE O RENDIMENTO (COM SUPORTE A ISPC)
    // ==================================================

    private function calcularImpostoRendimento(
        int $empresaId,
        string $dataInicio,
        string $dataFim,
        int $statusContabilizado,
        ?int $filialId,
        ?int $planoContasId
    ): float {

        // 1. Verificar se a empresa está enquadrada no regime ISPC para o período
        $regimeFiscal = $this->obterRegimeFiscalEmpresa($empresaId, $filialId, $dataInicio);

        if ($regimeFiscal === 'ISPC') {
            return $this->calcularISPCPeriodo($empresaId, $filialId, $dataInicio, $dataFim);
        }

        // 2. Caso contrário, mantém o cálculo padrão de IRPC via Contabilidade (contas 8.5.1 / 8.5.2)
        $sql = "
        SELECT
            COALESCE(SUM(item.debito), 0) AS total_debito,
            COALESCE(SUM(item.credito), 0) AS total_credito
        FROM contas_contabilisticas conta
        INNER JOIN lancamento_contabilistico_itens item ON item.conta_contabil_id = conta.id
        INNER JOIN lancamentos_contabilisticos lancamento ON lancamento.id = item.lancamento_id AND lancamento.empresa_id = conta.empresa_id
        WHERE conta.empresa_id = :empresa_id
          AND conta.tipo = 'ANALITICA'
          AND conta.aceita_lancamento = 1
          AND lancamento.status_id = :status_contabilizado
          AND lancamento.data_lancamento BETWEEN :data_inicio AND :data_fim
          AND (
                conta.codigo = '8.5.1'
                OR conta.codigo LIKE '8.5.1.%'
                OR conta.codigo = '8.5.2'
                OR conta.codigo LIKE '8.5.2.%'
              )
    ";

        $params = [
            ':empresa_id' => $empresaId,
            ':status_contabilizado' => $statusContabilizado,
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim
        ];

        if ($filialId !== null) {
            $sql .= " AND (lancamento.filial_id = :filial_id OR lancamento.filial_id IS NULL)";
            $params[':filial_id'] = $filialId;
        }

        if ($planoContasId !== null) {
            $sql .= " AND conta.plano_contas_id = :plano_contas_id";
            $params[':plano_contas_id'] = $planoContasId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $linha = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $debito = (float)($linha['total_debito'] ?? 0);
        $credito = (float)($linha['total_credito'] ?? 0);
        $saldo = $debito - $credito;

        return round(max(0, $saldo), 2);
    }

    // ==================================================
    // AUXILIARES DE SUPORTE AO ISPC NA DRE
    // ==================================================

    private function obterRegimeFiscalEmpresa(int $empresaId, ?int $filialId, string $data): string
    {
        $ano = (int)date('Y', strtotime($data));

        $sql = "
            SELECT regime_tributario 
            FROM configuracoes_fiscais 
            WHERE empresa_id = :empresa_id 
              AND (filial_id = :filial_id OR filial_id IS NULL)
              AND exercicio_fiscal = :ano
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':filial_id' => $filialId,
            ':ano' => $ano
        ]);

        $regime = $stmt->fetchColumn();
        return $regime ? strtoupper(trim((string)$regime)) : 'GERAL';
    }

    private function calcularISPCPeriodo(int $empresaId, ?int $filialId, string $dataInicio, string $dataFim): float
    {
        $ano = (int)date('Y', strtotime($dataInicio));

        $sql = "
            SELECT imposto_apurado 
            FROM apuramentos_ispc 
            WHERE empresa_id = :empresa_id 
              AND exercicio_fiscal = :ano
        ";
        $params = [':empresa_id' => $empresaId, ':ano' => $ano];

        if ($filialId !== null) {
            $sql .= " AND (filial_id = :filial_id OR filial_id IS NULL)";
            $params[':filial_id'] = $filialId;
        }

        $sql .= " ORDER BY id DESC LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $imposto = $stmt->fetchColumn();

        return $imposto !== false ? round((float)$imposto, 2) : 0.00;
    }

    // ==================================================
    // MONTAR RESULTADO
    // ==================================================

    private function montarResultado(
        array $linhas,
        string $dataInicio,
        string $dataFim,
        ?int $filialId,
        ?int $planoContasId,
        float $impostoRendimento
    ): array {

        $receitas = [];
        $custos = [];
        $despesas = [];

        $totalReceitas =
            0.0;

        $totalVolumeNegocios =
            0.0;

        $totalCustos =
            0.0;

        $totalDespesas =
            0.0;

        $totalAmortizacoes =
            0.0;

        $totalGastosFinanceiros =
            0.0;

        $totalRendimentosFinanceiros =
            0.0;

        foreach ($linhas as $linha) {

            $grupo =
                strtoupper(
                    trim(
                        (string)$linha['grupo']
                    )
                );

            $integracaoCodigo =
                strtoupper(
                    trim(
                        (string)(
                            $linha['integracao_codigo']
                            ?? ''
                        )
                    )
                );

            $codigo =
                trim(
                    (string)(
                        $linha['codigo']
                        ?? ''
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
             * Receita:
             * saldo normalmente credor.
             *
             * Custo e despesa:
             * saldo normalmente devedor.
             */
            if ($grupo === 'RENDIMENTO') {

                $valor =
                    round(
                        $credito - $debito,
                        2
                    );

                /*
                |--------------------------------------------------------------------------
                | VOLUME DE NEGÓCIOS
                |--------------------------------------------------------------------------
                |
                | 7.1 = Vendas
                | 7.2 = Prestação de Serviços
                |
                */

                if (
                    $integracaoCodigo === 'VENDAS'
                    || str_starts_with(
                        $codigo,
                        '7.1.'
                    )
                    || str_starts_with(
                        $codigo,
                        '7.2.'
                    )
                ) {

                    $categoria =
                        'VOLUME_NEGOCIOS';
                } elseif (
                    str_starts_with(
                        $codigo,
                        '7.8.'
                    )
                ) {

                    /*
                    * Rendimentos e ganhos financeiros.
                    */

                    $categoria =
                        'RENDIMENTO_FINANCEIRO';
                } else {

                    $categoria =
                        'OUTRO_RENDIMENTO';
                }
            } elseif ($grupo === 'GASTO') {

                $valor =
                    round(
                        $debito - $credito,
                        2
                    );

                /*
                |--------------------------------------------------------------------------
                | CUSTO DAS VENDAS
                |--------------------------------------------------------------------------
                */

                if (
                    $integracaoCodigo === 'CMV'
                    || str_starts_with(
                        $codigo,
                        '6.1.1.'
                    )
                ) {

                    $categoria =
                        'CUSTO';
                } elseif (
                    str_starts_with(
                        $codigo,
                        '6.5.'
                    )
                ) {

                    /*
                    * Amortizações do período.
                    */

                    $categoria =
                        'AMORTIZACAO';
                } elseif (
                    str_starts_with(
                        $codigo,
                        '6.9.'
                    )
                ) {

                    /*
                    * Gastos e perdas financeiros.
                    */

                    $categoria =
                        'GASTO_FINANCEIRO';
                } else {

                    $categoria =
                        'DESPESA_OPERACIONAL';
                }
            } else {

                continue;
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

                'total_debito' =>
                $debito,

                'total_credito' =>
                $credito,

                'valor' =>
                $valor
            ];

            switch ($categoria) {

                case 'VOLUME_NEGOCIOS':

                    $receitas[] =
                        $item;

                    $totalReceitas +=
                        $valor;

                    $totalVolumeNegocios +=
                        $valor;

                    break;


                case 'OUTRO_RENDIMENTO':

                    $receitas[] =
                        $item;

                    $totalReceitas +=
                        $valor;

                    break;


                case 'RENDIMENTO_FINANCEIRO':

                    $receitas[] =
                        $item;

                    $totalReceitas +=
                        $valor;

                    $totalRendimentosFinanceiros +=
                        $valor;

                    break;


                case 'CUSTO':

                    $custos[] =
                        $item;

                    $totalCustos +=
                        $valor;

                    break;


                case 'AMORTIZACAO':

                    $despesas[] =
                        $item;

                    $totalDespesas +=
                        $valor;

                    $totalAmortizacoes +=
                        $valor;

                    break;


                case 'GASTO_FINANCEIRO':

                    $despesas[] =
                        $item;

                    $totalDespesas +=
                        $valor;

                    $totalGastosFinanceiros +=
                        $valor;

                    break;


                case 'DESPESA_OPERACIONAL':

                    $despesas[] =
                        $item;

                    $totalDespesas +=
                        $valor;

                    break;
            }
        }

        $totalReceitas =
            round(
                $totalReceitas,
                2
            );

        $totalCustos =
            round(
                $totalCustos,
                2
            );

        $totalDespesas =
            round(
                $totalDespesas,
                2
            );

        $resultadoBruto =
            round(
                $totalVolumeNegocios
                    - $totalCustos,
                2
            );


        $totalVolumeNegocios =
            round(
                $totalVolumeNegocios,
                2
            );

        $totalAmortizacoes =
            round(
                $totalAmortizacoes,
                2
            );

        $totalGastosFinanceiros =
            round(
                $totalGastosFinanceiros,
                2
            );

        $totalRendimentosFinanceiros =
            round(
                $totalRendimentosFinanceiros,
                2
            );

        /*
        |--------------------------------------------------------------------------
        | GASTOS OPERACIONAIS
        |--------------------------------------------------------------------------
        |
        | totalDespesas contém:
        | - despesas operacionais;
        | - amortizações;
        | - gastos financeiros.
        |
        | Aqui retiramos apenas os gastos financeiros.
        | As amortizações continuam sendo gastos operacionais
        | para efeitos de EBIT.
        |
        */

        $gastosOperacionais =
            round(
                $totalDespesas
                    - $totalGastosFinanceiros,
                2
            );

        /*
        |--------------------------------------------------------------------------
        | EBITDA
        |--------------------------------------------------------------------------
        |
        | Resultado antes de amortizações,
        | resultado financeiro e impostos.
        |
        */

        $ebitda =
            round(
                $resultadoBruto
                    - (
                        $gastosOperacionais
                        - $totalAmortizacoes
                    ),
                2
            );

        $ebit =
            round(
                $ebitda
                    - $totalAmortizacoes,
                2
            );


        $resultadoOperacional =
            $ebit;

        $resultadoFinanceiro =
            round(
                $totalRendimentosFinanceiros
                    - $totalGastosFinanceiros,
                2
            );

        $resultadoAntesImpostos =
            round(
                $ebit
                    + $resultadoFinanceiro,
                2
            );

        $resultadoLiquido =
            round(
                $resultadoAntesImpostos
                    - $impostoRendimento,
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

            'receitas' =>
            $receitas,

            'custos' =>
            $custos,

            'despesas' =>
            $despesas,

            'totais' => [

                'volume_negocios' =>
                $totalVolumeNegocios,

                'receitas' =>
                $totalReceitas,

                'custos' =>
                $totalCustos,

                'despesas' =>
                $totalDespesas,

                'gastos_operacionais' =>
                $gastosOperacionais,

                'amortizacoes' =>
                $totalAmortizacoes,

                'rendimentos_financeiros' =>
                $totalRendimentosFinanceiros,

                'gastos_financeiros' =>
                $totalGastosFinanceiros,

                'resultado_financeiro' =>
                $resultadoFinanceiro,

                'resultado_bruto' =>
                $resultadoBruto,

                'ebitda' =>
                $ebitda,

                'ebit' =>
                $ebit,

                'resultado_operacional' =>
                $ebit,

                'resultado_antes_impostos' =>
                $resultadoAntesImpostos,

                'imposto_rendimento' =>
                $impostoRendimento,

                'resultado_liquido' =>
                $resultadoLiquido
            ],

            'resultado_positivo' =>
            $resultadoLiquido >= 0
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

                ORDER BY nome ASC
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
                'Empresa inválida para gerar a Demonstração de Resultados.'
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
