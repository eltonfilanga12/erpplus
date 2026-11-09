<?php

declare(strict_types=1);

require_once __DIR__ . '/BalancoPatrimonial.php';
require_once __DIR__ . '/DemonstracaoResultados.php';

if (file_exists(__DIR__ . '/DemonstracaoFluxoCaixa.php')) {
    require_once __DIR__ . '/DemonstracaoFluxoCaixa.php';
}

final class IndicadoresContabilisticos
{
    private PDO $pdo;
    private BalancoPatrimonial $balanco;
    private DemonstracaoResultados $dre;
    private ?DemonstracaoFluxoCaixa $dfc = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->balanco = new BalancoPatrimonial($pdo);
        $this->dre = new DemonstracaoResultados($pdo);

        if (class_exists(DemonstracaoFluxoCaixa::class)) {
            $this->dfc = new DemonstracaoFluxoCaixa($pdo);
        }
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(int $empresaId): array
    {
        $this->validarEmpresa($empresaId);

        return [
            'planos_contas' => $this->balanco->listarPlanos($empresaId),
            'filiais' => $this->balanco->listarFiliais($empresaId),
            'exercicios' => $this->listarExercicios($empresaId)
        ];
    }

    // ==================================================
    // GERAR INDICADORES
    // ==================================================

    public function gerar(
        int $empresaId,
        string $dataInicial,
        string $dataFinal,
        ?int $filialId = null,
        ?int $planoContasId = null,
        ?int $exercicioId = null,
        bool $compararPeriodoAnterior = true
    ): array {

        $this->validarFiltros(
            $empresaId,
            $dataInicial,
            $dataFinal,
            $filialId,
            $planoContasId,
            $exercicioId
        );

        $balanco = $this->balanco->gerar($empresaId, $dataFinal, $filialId, $planoContasId);
        $dre = $this->dre->gerar($empresaId, $dataInicial, $dataFinal, $filialId, $planoContasId);

        $estrutura = $this->montarEstruturaFinanceira($balanco, $dre);
        $disponibilidades = $this->calcularDisponibilidades($empresaId, $dataFinal, $filialId, $planoContasId);
        $inventarios = $this->calcularInventarios($balanco);

        $clientes = $this->calcularPorIntegracao($balanco['ativos'] ?? [], ['CLIENTES']);
        $fornecedores = $this->calcularPorIntegracao($balanco['passivos'] ?? [], ['FORNECEDORES']);

        $estruturaCorrente = $this->calcularEstruturaCorrente($empresaId, $dataFinal, $filialId, $planoContasId);
        $fluxoCaixa = $this->gerarFluxoCaixa($empresaId, $planoContasId, $dataInicial, $dataFinal, $filialId, $exercicioId);

        $indicadores = $this->calcularIndicadores(
            $estrutura,
            $estruturaCorrente,
            $disponibilidades,
            $inventarios,
            $clientes,
            $fornecedores,
            $fluxoCaixa
        );

        $comparativo = null;

        if ($compararPeriodoAnterior) {
            $periodoAnterior = $this->calcularPeriodoAnterior($dataInicial, $dataFinal);
            $comparativo = $this->gerarComparativo(
                $empresaId,
                $periodoAnterior['data_inicial'],
                $periodoAnterior['data_final'],
                $filialId,
                $planoContasId,
                $estrutura,
                $indicadores
            );
        }

        $alertas = $this->gerarAlertas($estrutura, $indicadores, $fluxoCaixa);
        $saudeFinanceira = $this->calcularSaudeFinanceira($alertas, $indicadores);

        return [
            'filtros' => [
                'empresa_id' => $empresaId,
                'data_inicial' => $dataInicial,
                'data_final' => $dataFinal,
                'filial_id' => $filialId,
                'plano_contas_id' => $planoContasId,
                'exercicio_id' => $exercicioId,
                'comparar_periodo_anterior' => $compararPeriodoAnterior
            ],
            'balanco' => [
                'ativo_total' => $estrutura['ativo_total'],
                'ativo_corrente' => $estruturaCorrente['ativo_corrente'],
                'ativo_nao_corrente' => $estruturaCorrente['ativo_nao_corrente'],
                'realizavel_longo_prazo' => $estruturaCorrente['realizavel_longo_prazo'],
                'passivo_total' => $estrutura['passivo_total'],
                'passivo_corrente' => $estruturaCorrente['passivo_corrente'],
                'passivo_nao_corrente' => $estruturaCorrente['passivo_nao_corrente'],
                'capital_proprio' => $estrutura['capital_proprio'],
                'capital_proprio_contabilizado' => $estrutura['capital_proprio_contabilizado'],
                'capital_proprio_total' => $estrutura['capital_proprio_total'],
                'resultado_balanco' => $estrutura['resultado_balanco'],
                'passivo_capital_resultado' => $estrutura['passivo_capital_resultado'],
                'diferenca' => $estrutura['diferenca_balanco'],
                'equilibrado' => $estrutura['balanco_equilibrado']
            ],
            'dre' => [
                'volume_negocios' => $estrutura['volume_negocios'],
                'rendimentos' => $estrutura['rendimentos'],
                'receitas' => $estrutura['rendimentos'],
                'custos' => $estrutura['custos'],
                'despesas' => $estrutura['despesas'],
                'gastos_totais' => $estrutura['gastos_totais'],
                'gastos_operacionais' => $estrutura['gastos_operacionais'],
                'amortizacoes' => $estrutura['amortizacoes'],
                'rendimentos_financeiros' => $estrutura['rendimentos_financeiros'],
                'gastos_financeiros' => $estrutura['gastos_financeiros'],
                'resultado_financeiro' => $estrutura['resultado_financeiro'],
                'resultado_bruto' => $estrutura['resultado_bruto'],
                'ebitda' => $estrutura['ebitda'],
                'ebit' => $estrutura['ebit'],
                'resultado_operacional' => $estrutura['resultado_operacional'],
                'resultado_antes_impostos' => $estrutura['resultado_antes_impostos'],
                'imposto_rendimento' => $estrutura['imposto_rendimento'],
                'resultado_liquido' => $estrutura['resultado_liquido']
            ],
            'saldos_relevantes' => [
                'disponibilidades' => $disponibilidades,
                'inventarios' => $inventarios,
                'clientes' => $clientes,
                'fornecedores' => $fornecedores,
                'fundo_maneio' => $indicadores['fundo_maneio'],
                'capital_investido' => $indicadores['capital_investido']
            ],
            'indicadores' => $indicadores,
            'fluxo_caixa' => $fluxoCaixa,
            'comparativo' => $comparativo,
            'alertas' => $alertas,
            'saude_financeira' => $saudeFinanceira,
            'dados_origem' => [
                'balanco' => $balanco,
                'dre' => $dre
            ]
        ];
    }

    // ==================================================
    // MONTAR ESTRUTURA FINANCEIRA
    // ==================================================

    private function montarEstruturaFinanceira(array $balanco, array $dre): array
    {
        $totaisBalanco = $balanco['totais'] ?? [];
        $totaisDre = $dre['totais'] ?? [];

        $ativoTotal = round((float)($totaisBalanco['ativo'] ?? 0), 2);
        $passivoTotal = round((float)($totaisBalanco['passivo'] ?? 0), 2);

        $capitalProprioContabilizado = round((float)(
            $totaisBalanco['capital_proprio_base'] ?? $totaisBalanco['capital_proprio'] ?? 0
        ), 2);

        $resultadoBalanco = round((float)($totaisBalanco['resultado'] ?? 0), 2);

        $capitalProprioTotal = round((float)(
            $totaisBalanco['capital_proprio_total'] ?? ($capitalProprioContabilizado + $resultadoBalanco)
        ), 2);

        $rendimentos = round((float)($totaisDre['receitas'] ?? 0), 2);
        $custos = round((float)($totaisDre['custos'] ?? 0), 2);
        $despesas = round((float)($totaisDre['despesas'] ?? 0), 2);

        $resultadoBruto = round((float)($totaisDre['resultado_bruto'] ?? ($rendimentos - $custos)), 2);
        $resultadoOperacional = round((float)($totaisDre['resultado_operacional'] ?? ($resultadoBruto - $despesas)), 2);
        $resultadoLiquido = round((float)($totaisDre['resultado_liquido'] ?? $resultadoOperacional), 2);

        $volumeNegocios = round((float)($totaisDre['volume_negocios'] ?? 0), 2);
        $gastosOperacionais = round((float)($totaisDre['gastos_operacionais'] ?? 0), 2);
        $amortizacoes = round((float)($totaisDre['amortizacoes'] ?? 0), 2);
        $rendimentosFinanceiros = round((float)($totaisDre['rendimentos_financeiros'] ?? 0), 2);
        $gastosFinanceiros = round((float)($totaisDre['gastos_financeiros'] ?? 0), 2);
        $resultadoFinanceiro = round((float)($totaisDre['resultado_financeiro'] ?? 0), 2);
        $ebitda = round((float)($totaisDre['ebitda'] ?? 0), 2);
        $ebit = round((float)($totaisDre['ebit'] ?? $resultadoOperacional), 2);
        $resultadoAntesImpostos = round((float)($totaisDre['resultado_antes_impostos'] ?? $ebit), 2);
        $impostoRendimento = round((float)($totaisDre['imposto_rendimento'] ?? 0), 2);

        return [
            'ativo_total' => $ativoTotal,
            'passivo_total' => $passivoTotal,
            'capital_proprio' => $capitalProprioTotal,
            'capital_proprio_contabilizado' => $capitalProprioContabilizado,
            'capital_proprio_total' => $capitalProprioTotal,
            'resultado_balanco' => $resultadoBalanco,
            'passivo_capital_resultado' => round((float)($totaisBalanco['passivo_capital_resultado'] ?? ($passivoTotal + $capitalProprioTotal)), 2),
            'diferenca_balanco' => round((float)($totaisBalanco['diferenca'] ?? 0), 2),
            'balanco_equilibrado' => (bool)($balanco['equilibrado'] ?? false),
            'volume_negocios' => $volumeNegocios,
            'rendimentos' => $rendimentos,
            'custos' => $custos,
            'despesas' => $despesas,
            'gastos_totais' => round($custos + $despesas, 2),
            'resultado_bruto' => $resultadoBruto,
            'gastos_operacionais' => $gastosOperacionais,
            'amortizacoes' => $amortizacoes,
            'rendimentos_financeiros' => $rendimentosFinanceiros,
            'gastos_financeiros' => $gastosFinanceiros,
            'resultado_financeiro' => $resultadoFinanceiro,
            'ebitda' => $ebitda,
            'ebit' => $ebit,
            'resultado_operacional' => $resultadoOperacional,
            'resultado_antes_impostos' => $resultadoAntesImpostos,
            'imposto_rendimento' => $impostoRendimento,
            'resultado_liquido' => $resultadoLiquido
        ];
    }

    // ==================================================
    // CALCULAR INDICADORES
    // ==================================================

    private function calcularIndicadores(
        array $estrutura,
        array $estruturaCorrente,
        float $disponibilidades,
        float $inventarios,
        float $clientes,
        float $fornecedores,
        ?array $fluxoCaixa
    ): array {

        $ativoTotal = $estrutura['ativo_total'];
        $ativoCorrente = $estruturaCorrente['ativo_corrente'];
        $ativoNaoCorrente = $estruturaCorrente['ativo_nao_corrente'];
        $realizavelLongoPrazo = (float)($estruturaCorrente['realizavel_longo_prazo'] ?? 0);

        $passivoTotal = $estrutura['passivo_total'];
        $passivoCorrente = $estruturaCorrente['passivo_corrente'];
        $passivoNaoCorrente = $estruturaCorrente['passivo_nao_corrente'];

        $capitalProprio = $estrutura['capital_proprio'];
        $volumeNegocios = $estrutura['volume_negocios'];
        $rendimentos = $estrutura['rendimentos'];
        $custos = $estrutura['custos'];
        $gastosTotais = $estrutura['gastos_totais'];
        $resultadoBruto = $estrutura['resultado_bruto'];
        $resultadoOperacional = $estrutura['resultado_operacional'];
        $resultadoLiquido = $estrutura['resultado_liquido'];
        $ebitda = $estrutura['ebitda'];

        $fundoManeio = round($ativoCorrente - $passivoCorrente, 2);
        $necessidadeFundoManeio = round(($inventarios + $clientes) - $fornecedores, 2);
        $tesourariaLiquida = round($fundoManeio - $necessidadeFundoManeio, 2);
        $capitalInvestido = round($ativoTotal - $passivoCorrente, 2);

        return [
            'liquidez_corrente' => $this->dividir($ativoCorrente, $passivoCorrente),
            'liquidez_reduzida' => $this->dividir($ativoCorrente - $inventarios, $passivoCorrente),
            'liquidez_imediata' => $this->dividir($disponibilidades, $passivoCorrente),
            'liquidez_geral' => $this->dividir($ativoCorrente + $realizavelLongoPrazo, $passivoCorrente + $passivoNaoCorrente),

            'autonomia_financeira' => $this->percentagem($capitalProprio, $ativoTotal),
            'endividamento' => $this->percentagem($passivoTotal, $ativoTotal),
            'solvabilidade' => $this->percentagem($capitalProprio, $passivoTotal),
            'cobertura_passivo' => $this->dividir($ativoTotal, $passivoTotal),
            'peso_ativo_corrente' => $this->percentagem($ativoCorrente, $ativoTotal),
            'peso_ativo_nao_corrente' => $this->percentagem($ativoNaoCorrente, $ativoTotal),
            'peso_passivo_corrente' => $this->percentagem($passivoCorrente, $passivoTotal),
            'peso_passivo_nao_corrente' => $this->percentagem($passivoNaoCorrente, $passivoTotal),
            'peso_capital_proprio' => $this->percentagem($capitalProprio, $ativoTotal),
            'grau_dependencia_financeira' => $this->percentagem($passivoTotal, $passivoTotal + $capitalProprio),

            'roa' => $this->percentagem($resultadoLiquido, $ativoTotal),
            'roe' => $this->percentagem($resultadoLiquido, $capitalProprio),
            'rentabilidade_vendas' => $this->percentagem($resultadoLiquido, $volumeNegocios),
            'rentabilidade_capital_investido' => $this->percentagem($resultadoOperacional, $capitalInvestido),

            'margem_bruta' => $this->percentagem($resultadoBruto, $volumeNegocios),
            'margem_operacional' => $this->percentagem($resultadoOperacional, $volumeNegocios),
            'margem_ebitda' => $this->percentagem($ebitda, $volumeNegocios),
            'margem_liquida' => $this->percentagem($resultadoLiquido, $volumeNegocios),
            'peso_custos_vendas' => $this->percentagem($custos, $volumeNegocios),
            'peso_gastos_totais' => $this->percentagem($gastosTotais, $rendimentos),

            'rotacao_ativo' => $this->dividir($volumeNegocios, $ativoTotal),
            'rotacao_capital_proprio' => $this->dividir($volumeNegocios, $capitalProprio),
            'cobertura_gastos' => $this->dividir($rendimentos, $gastosTotais),

            'fundo_maneio' => $fundoManeio,
            'necessidade_fundo_maneio' => $necessidadeFundoManeio,
            'tesouraria_liquida' => $tesourariaLiquida,
            'capital_investido' => $capitalInvestido,

            'fluxo_operacional' => $fluxoCaixa['resumo']['fluxo_operacional'] ?? null,
            'fluxo_investimento' => $fluxoCaixa['resumo']['fluxo_investimento'] ?? null,
            'fluxo_financiamento' => $fluxoCaixa['resumo']['fluxo_financiamento'] ?? null,
            'variacao_liquida_caixa' => $fluxoCaixa['resumo']['variacao_liquida'] ?? null,
            'saldo_final_caixa' => $fluxoCaixa['resumo']['saldo_final_contabilistico'] ?? $disponibilidades,
            'conversao_resultado_em_caixa' => $this->dividir((float)($fluxoCaixa['resumo']['fluxo_operacional'] ?? 0), $resultadoLiquido)
        ];
    }

    // ==================================================
    // ESTRUTURA CORRENTE / NÃO CORRENTE
    // ==================================================

    private function calcularEstruturaCorrente(
        int $empresaId,
        string $dataReferencia,
        ?int $filialId,
        ?int $planoContasId
    ): array {
        $statusId = $this->statusLancamentoId('Contabilizado');

        $sql = "
        SELECT
            conta.id,
            conta.codigo,
            conta.nome,
            conta.grupo,
            conta.classificacao_patrimonial,
            conta.realizavel_longo_prazo,
            COALESCE(SUM(CASE WHEN lancamento.id IS NOT NULL THEN item.debito ELSE 0 END), 0) AS debito,
            COALESCE(SUM(CASE WHEN lancamento.id IS NOT NULL THEN item.credito ELSE 0 END), 0) AS credito
        FROM contas_contabilisticas conta
        LEFT JOIN lancamento_contabilistico_itens item ON item.conta_contabil_id = conta.id
        LEFT JOIN lancamentos_contabilisticos lancamento ON lancamento.id = item.lancamento_id
           AND lancamento.empresa_id = conta.empresa_id
           And lancamento.status_id = :status_id
           AND lancamento.data_lancamento <= :data_referencia
    ";

        $params = [
            ':status_id' => $statusId,
            ':data_referencia' => $dataReferencia,
            ':empresa_id' => $empresaId
        ];

        if ($filialId !== null) {
            $sql .= " AND lancamento.filial_id = :filial_id";
            $params[':filial_id'] = $filialId;
        }

        $sql .= "
        WHERE conta.empresa_id = :empresa_id
          AND conta.tipo = 'ANALITICA'
          AND conta.aceita_lancamento = 1
          AND conta.grupo IN ('ATIVO', 'PASSIVO')
    ";

        if ($planoContasId !== null) {
            $sql .= " AND conta.plano_contas_id = :plano_contas_id";
            $params[':plano_contas_id'] = $planoContasId;
        }

        $sql .= " GROUP BY conta.id, conta.codigo, conta.nome, conta.grupo, conta.classificacao_patrimonial ORDER BY conta.codigo";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $ativoCorrente = 0.0;
        $ativoNaoCorrente = 0.0;
        $passivoCorrente = 0.0;
        $passivoNaoCorrente = 0.0;
        $realizavelLongoPrazo = 0.0;

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $conta) {
            $grupo = strtoupper(trim((string)$conta['grupo']));
            $debito = (float)$conta['debito'];
            $credito = (float)$conta['credito'];
            $valor = $grupo === 'ATIVO' ? $debito - $credito : $credito - $debito;

            $classificacao = strtoupper(trim((string)($conta['classificacao_patrimonial'] ?? '')));
            $realizavelLongoPrazoConta = (int)($conta['realizavel_longo_prazo'] ?? 0) === 1;

            if (!in_array($classificacao, ['CORRENTE', 'NAO_CORRENTE'], true)) {
                $classificacao = $this->classificarCorrente((string)$conta['codigo'], (string)$conta['nome'], $grupo);
            }

            if ($grupo === 'ATIVO' && $classificacao === 'CORRENTE') {
                $ativoCorrente += $valor;
            } elseif ($grupo === 'ATIVO' && $classificacao === 'NAO_CORRENTE') {
                $ativoNaoCorrente += $valor;
                if ($realizavelLongoPrazoConta) {
                    $realizavelLongoPrazo += $valor;
                }
            } elseif ($grupo === 'PASSIVO' && $classificacao === 'CORRENTE') {
                $passivoCorrente += $valor;
            } elseif ($grupo === 'PASSIVO' && $classificacao === 'NAO_CORRENTE') {
                $passivoNaoCorrente += $valor;
            }
        }

        return [
            'ativo_corrente' => round($ativoCorrente, 2),
            'ativo_nao_corrente' => round($ativoNaoCorrente, 2),
            'realizavel_longo_prazo' => round($realizavelLongoPrazo, 2),
            'passivo_corrente' => round($passivoCorrente, 2),
            'passivo_nao_corrente' => round($passivoNaoCorrente, 2),
            'metodo_classificacao' => 'CLASSIFICACAO_PATRIMONIAL_COM_FALLBACK'
        ];
    }

    private function classificarCorrente(string $codigo, string $nome, string $grupo): string
    {
        $texto = mb_strtolower(trim($codigo . ' ' . $nome));

        $naoCorrente = [
            'não corrente',
            'nao corrente',
            'longo prazo',
            'imobilizado',
            'ativo fixo',
            'activo fixo',
            'intangível',
            'intangivel',
            'edifício',
            'edificio',
            'equipamento',
            'viatura',
            'investimento financeiro',
            'empréstimo longo prazo',
            'emprestimo longo prazo'
        ];

        foreach ($naoCorrente as $termo) {
            if (str_contains($texto, $termo)) {
                return 'NAO_CORRENTE';
            }
        }

        $corrente = [
            'caixa',
            'banco',
            'cliente',
            'inventário',
            'inventario',
            'stock',
            'fornecedor',
            'imposto',
            'iva',
            'curto prazo',
            'disponibilidade'
        ];

        foreach ($corrente as $termo) {
            if (str_contains($texto, $termo)) {
                return 'CORRENTE';
            }
        }

        return 'NAO_CLASSIFICADO';
    }

    // ==================================================
    // DISPONIBILIDADES
    // ==================================================

    private function calcularDisponibilidades(
        int $empresaId,
        string $dataReferencia,
        ?int $filialId,
        ?int $planoContasId
    ): float {
        $statusId = $this->statusLancamentoId('Contabilizado');

        $sql = "
        SELECT COALESCE(SUM(item.debito - item.credito), 0) AS saldo
        FROM contas_contabilisticas conta
        INNER JOIN lancamento_contabilistico_itens item ON item.conta_contabil_id = conta.id
        INNER JOIN lancamentos_contabilisticos lancamento ON lancamento.id = item.lancamento_id AND lancamento.empresa_id = conta.empresa_id
        WHERE conta.empresa_id = :empresa_id
          AND conta.grupo = 'ATIVO'
          AND conta.tipo = 'ANALITICA'
          AND conta.aceita_lancamento = 1
          AND conta.classificacao_patrimonial = 'CORRENTE'
          AND lancamento.status_id = :status_id
          AND lancamento.data_lancamento <= :data_referencia
          AND (
                conta.conta_caixa = 1
                OR conta.conta_banco = 1
                OR UPPER(COALESCE(conta.integracao_codigo, '')) IN ('CAIXA', 'BANCOS')
          )
    ";

        $params = [
            ':empresa_id' => $empresaId,
            ':status_id' => $statusId,
            ':data_referencia' => $dataReferencia
        ];

        if ($filialId !== null) {
            $sql .= " AND lancamento.filial_id = :filial_id";
            $params[':filial_id'] = $filialId;
        }

        if ($planoContasId !== null) {
            $sql .= " AND conta.plano_contas_id = :plano_contas_id";
            $params[':plano_contas_id'] = $planoContasId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return round((float)($stmt->fetchColumn() ?: 0), 2);
    }

    // ==================================================
    // INVENTÁRIOS
    // ==================================================

    private function calcularInventarios(array $balanco): float
    {
        $total = 0.0;

        foreach ($balanco['ativos'] ?? [] as $conta) {
            $integracao = strtoupper(trim((string)($conta['integracao_codigo'] ?? '')));
            $nome = mb_strtolower((string)($conta['nome'] ?? ''));

            if (
                $integracao === 'STOCK'
                || str_contains($nome, 'stock')
                || str_contains($nome, 'inventário')
                || str_contains($nome, 'inventario')
                || str_contains($nome, 'mercadoria')
            ) {
                $total += (float)($conta['valor'] ?? 0);
            }
        }

        return round($total, 2);
    }

    // ==================================================
    // SOMAR POR INTEGRAÇÃO
    // ==================================================

    private function calcularPorIntegracao(array $contas, array $codigos): float
    {
        $codigos = array_map('strtoupper', $codigos);
        $total = 0.0;

        foreach ($contas as $conta) {
            $integracao = strtoupper(trim((string)($conta['integracao_codigo'] ?? '')));

            if (in_array($integracao, $codigos, true)) {
                $total += (float)($conta['valor'] ?? 0);
            }
        }

        return round($total, 2);
    }

    // ==================================================
    // FLUXO DE CAIXA
    // ==================================================

    private function gerarFluxoCaixa(
        int $empresaId,
        ?int $planoContasId,
        string $dataInicial,
        string $dataFinal,
        ?int $filialId,
        ?int $exercicioId
    ): ?array {
        if ($this->dfc === null || $planoContasId === null) {
            return null;
        }

        try {
            return $this->dfc->gerar($empresaId, $planoContasId, $dataInicial, $dataFinal, $filialId, $exercicioId);
        } catch (Throwable $e) {
            return [
                'disponivel' => false,
                'erro' => $e->getMessage(),
                'resumo' => [
                    'fluxo_operacional' => null,
                    'fluxo_investimento' => null,
                    'fluxo_financiamento' => null,
                    'variacao_liquida' => null,
                    'saldo_final_contabilistico' => null
                ]
            ];
        }
    }

    // ==================================================
    // COMPARATIVO
    // ==================================================

    private function gerarComparativo(
        int $empresaId,
        string $dataInicialAnterior,
        string $dataFinalAnterior,
        ?int $filialId,
        ?int $planoContasId,
        array $estruturaAtual,
        array $indicadoresAtuais
    ): array {
        $balancoAnterior = $this->balanco->gerar($empresaId, $dataFinalAnterior, $filialId, $planoContasId);
        $dreAnterior = $this->dre->gerar($empresaId, $dataInicialAnterior, $dataFinalAnterior, $filialId, $planoContasId);
        $estruturaAnterior = $this->montarEstruturaFinanceira($balancoAnterior, $dreAnterior);

        return [
            'periodo_anterior' => [
                'data_inicial' => $dataInicialAnterior,
                'data_final' => $dataFinalAnterior
            ],
            'valores' => [
                'ativo_total' => $estruturaAnterior['ativo_total'],
                'passivo_total' => $estruturaAnterior['passivo_total'],
                'capital_proprio' => $estruturaAnterior['capital_proprio'],
                'volume_negocios' => $estruturaAnterior['volume_negocios'],
                'resultado_bruto' => $estruturaAnterior['resultado_bruto'],
                'resultado_operacional' => $estruturaAnterior['resultado_operacional'],
                'resultado_liquido' => $estruturaAnterior['resultado_liquido'],
                'ebitda' => $estruturaAnterior['ebitda'],
                'ebit' => $estruturaAnterior['ebit'],
                'resultado_antes_impostos' => $estruturaAnterior['resultado_antes_impostos']
            ],
            'variacoes' => [
                'ativo_total' => $this->variacaoPercentual($estruturaAtual['ativo_total'], $estruturaAnterior['ativo_total']),
                'passivo_total' => $this->variacaoPercentual($estruturaAtual['passivo_total'], $estruturaAnterior['passivo_total']),
                'capital_proprio' => $this->variacaoPercentual($estruturaAtual['capital_proprio'], $estruturaAnterior['capital_proprio']),
                'volume_negocios' => $this->variacaoPercentual($estruturaAtual['volume_negocios'], $estruturaAnterior['volume_negocios']),
                'resultado_bruto' => $this->variacaoPercentual($estruturaAtual['resultado_bruto'], $estruturaAnterior['resultado_bruto']),
                'resultado_operacional' => $this->variacaoPercentual($estruturaAtual['resultado_operacional'], $estruturaAnterior['resultado_operacional']),
                'resultado_liquido' => $this->variacaoPercentual($estruturaAtual['resultado_liquido'], $estruturaAnterior['resultado_liquido']),
                'ebitda' => $this->variacaoPercentual($estruturaAtual['ebitda'], $estruturaAnterior['ebitda']),
                'ebit' => $this->variacaoPercentual($estruturaAtual['ebit'], $estruturaAnterior['ebit']),
                'resultado_antes_impostos' => $this->variacaoPercentual($estruturaAtual['resultado_antes_impostos'], $estruturaAnterior['resultado_antes_impostos'])
            ]
        ];
    }

    private function calcularPeriodoAnterior(string $dataInicial, string $dataFinal): array
    {
        $inicio = new DateTimeImmutable($dataInicial);
        $fim = new DateTimeImmutable($dataFinal);
        $dias = (int)$inicio->diff($fim)->format('%a') + 1;

        $fimAnterior = $inicio->modify('-1 day');
        $inicioAnterior = $fimAnterior->modify('-' . ($dias - 1) . ' days');

        return [
            'data_inicial' => $inicioAnterior->format('Y-m-d'),
            'data_final' => $fimAnterior->format('Y-m-d')
        ];
    }

    // ==================================================
    // ALERTAS
    // ==================================================

    private function gerarAlertas(array $estrutura, array $indicadores, ?array $fluxoCaixa): array
    {
        $alertas = [];

        $this->adicionarAlerta(
            $alertas,
            'liquidez_corrente',
            $indicadores['liquidez_corrente'],
            1.0,
            1.5,
            'Liquidez corrente',
            'A empresa pode enfrentar dificuldades para cumprir obrigações de curto prazo.',
            'A liquidez corrente encontra-se num nível aceitável.',
            'A empresa apresenta boa capacidade para cumprir obrigações de curto prazo.'
        );

        $autonomia = $indicadores['autonomia_financeira'];

        if ($autonomia !== null) {
            if ($autonomia < 20) {
                $alertas[] = [
                    'codigo' => 'AUTONOMIA_BAIXA',
                    'nivel' => 'CRITICO',
                    'titulo' => 'Autonomia financeira muito baixa',
                    'mensagem' => 'A empresa apresenta elevada dependência de capitais alheios.',
                    'valor' => $autonomia
                ];
            } elseif ($autonomia < 35) {
                $alertas[] = [
                    'codigo' => 'AUTONOMIA_ATENCAO',
                    'nivel' => 'ATENCAO',
                    'titulo' => 'Autonomia financeira reduzida',
                    'mensagem' => 'O capital próprio representa uma parcela limitada do financiamento dos ativos.',
                    'valor' => $autonomia
                ];
            } else {
                $alertas[] = [
                    'codigo' => 'AUTONOMIA_BOA',
                    'nivel' => 'POSITIVO',
                    'titulo' => 'Autonomia financeira adequada',
                    'mensagem' => 'A empresa apresenta uma participação saudável de capital próprio.',
                    'valor' => $autonomia
                ];
            }
        }

        if ($estrutura['resultado_liquido'] < 0) {
            $alertas[] = [
                'codigo' => 'PREJUIZO',
                'nivel' => 'CRITICO',
                'titulo' => 'Resultado líquido negativo',
                'mensagem' => 'A empresa apresentou prejuízo no período analisado.',
                'valor' => $estrutura['resultado_liquido']
            ];
        }

        if ($indicadores['fundo_maneio'] < 0) {
            $alertas[] = [
                'codigo' => 'FUNDO_MANEIO_NEGATIVO',
                'nivel' => 'CRITICO',
                'titulo' => 'Fundo de maneio negativo',
                'mensagem' => 'O passivo corrente é superior ao ativo corrente.',
                'valor' => $indicadores['fundo_maneio']
            ];
        }

        if (isset($fluxoCaixa['resumo']['fluxo_operacional']) && (float)$fluxoCaixa['resumo']['fluxo_operacional'] < 0) {
            $alertas[] = [
                'codigo' => 'FLUXO_OPERACIONAL_NEGATIVO',
                'nivel' => 'ATENCAO',
                'titulo' => 'Fluxo de caixa operacional negativo',
                'mensagem' => 'A atividade operacional consumiu disponibilidades no período.',
                'valor' => (float)$fluxoCaixa['resumo']['fluxo_operacional']
            ];
        }

        if (!$estrutura['balanco_equilibrado']) {
            $alertas[] = [
                'codigo' => 'BALANCO_DESEQUILIBRADO',
                'nivel' => 'CRITICO',
                'titulo' => 'Balanço patrimonial desequilibrado',
                'mensagem' => 'O total do ativo não coincide com passivo, capital próprio e resultado.',
                'valor' => $estrutura['diferenca_balanco']
            ];
        }

        return $alertas;
    }

    private function adicionarAlerta(
        array &$alertas,
        string $codigo,
        ?float $valor,
        float $limiteCritico,
        float $limitePositivo,
        string $titulo,
        string $mensagemCritica,
        string $mensagemAtencao,
        string $mensagemPositiva
    ): void {
        if ($valor === null) {
            return;
        }

        if ($valor < $limiteCritico) {
            $nivel = 'CRITICO';
            $mensagem = $mensagemCritica;
        } elseif ($valor < $limitePositivo) {
            $nivel = 'ATENCAO';
            $mensagem = $mensagemAtencao;
        } else {
            $nivel = 'POSITIVO';
            $mensagem = $mensagemPositiva;
        }

        $alertas[] = [
            'codigo' => strtoupper($codigo),
            'nivel' => $nivel,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'valor' => $valor
        ];
    }

    // ==================================================
    // SAÚDE FINANCEIRA
    // ==================================================

    private function calcularSaudeFinanceira(array $alertas, array $indicadores): array
    {
        $pontuacao = 100;

        foreach ($alertas as $alerta) {
            $nivel = strtoupper((string)$alerta['nivel']);
            if ($nivel === 'CRITICO') {
                $pontuacao -= 20;
            } elseif ($nivel === 'ATENCAO') {
                $pontuacao -= 8;
            }
        }

        $pontuacao = max(0, min(100, $pontuacao));

        if ($pontuacao >= 80) {
            $classificacao = 'EXCELENTE';
        } elseif ($pontuacao >= 65) {
            $classificacao = 'BOA';
        } elseif ($pontuacao >= 50) {
            $classificacao = 'MODERADA';
        } elseif ($pontuacao >= 30) {
            $classificacao = 'FRAGIL';
        } else {
            $classificacao = 'CRITICA';
        }

        return [
            'pontuacao' => $pontuacao,
            'classificacao' => $classificacao,
            'quantidade_alertas_criticos' => count(array_filter($alertas, static fn(array $alerta): bool => $alerta['nivel'] === 'CRITICO')),
            'quantidade_alertas_atencao' => count(array_filter($alertas, static fn(array $alerta): bool => $alerta['nivel'] === 'ATENCAO')),
            'quantidade_indicadores' => count($indicadores)
        ];
    }

    // ==================================================
    // EXERCÍCIOS
    // ==================================================

    private function listarExercicios(int $empresaId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                exercicio.id,
                exercicio.codigo,
                exercicio.descricao,
                exercicio.data_inicio,
                exercicio.data_fim,
                exercicio.status_id,
                status.nome AS status_nome
            FROM exercicios_contabilisticos exercicio
            INNER JOIN status ON status.id = exercicio.status_id AND status.tipo = 'exercicio_contabilistico'
            WHERE exercicio.empresa_id = :empresa_id
            ORDER BY exercicio.data_inicio DESC
        ");

        $stmt->execute([':empresa_id' => $empresaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==================================================
    // MATEMÁTICA
    // ==================================================

    private function dividir(float $numerador, float $denominador): ?float
    {
        if (abs($denominador) <= 0.000001) {
            return null;
        }

        return round($numerador / $denominador, 4);
    }

    private function percentagem(float $numerador, float $denominador): ?float
    {
        $resultado = $this->dividir($numerador, $denominador);
        return $resultado === null ? null : round($resultado * 100, 2);
    }

    private function variacaoPercentual(float $atual, float $anterior): ?float
    {
        if (abs($anterior) <= 0.000001) {
            return null;
        }

        return round((($atual - $anterior) / abs($anterior)) * 100, 2);
    }

    // ==================================================
    // STATUS
    // ==================================================

    private function statusLancamentoId(string $nome): int
    {
        $stmt = $this->pdo->prepare("
            SELECT id
            FROM status
            WHERE tipo = 'lancamento_contabilistico'
              AND LOWER(nome) = LOWER(:nome)
            LIMIT 1
        ");

        $stmt->execute([':nome' => $nome]);
        $id = (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new RuntimeException("Status '{$nome}' do lançamento contabilístico não encontrado.");
        }

        return $id;
    }

    // ==================================================
    // VALIDAÇÕES
    // ==================================================

    private function validarFiltros(
        int $empresaId,
        string $dataInicial,
        string $dataFinal,
        ?int $filialId,
        ?int $planoContasId,
        ?int $exercicioId
    ): void {
        $this->validarEmpresa($empresaId);
        $this->validarData($dataInicial, 'Data inicial');
        $this->validarData($dataFinal, 'Data final');

        if ($dataFinal < $dataInicial) {
            throw new RuntimeException('A data final não pode ser anterior à data inicial.');
        }

        if ($filialId !== null) {
            $this->validarFilial($empresaId, $filialId);
        }

        if ($planoContasId !== null) {
            $this->validarPlano($empresaId, $planoContasId);
        }

        if ($exercicioId !== null) {
            $exercicio = $this->validarExercicio($empresaId, $exercicioId);

            if ($dataInicial < $exercicio['data_inicio'] || $dataFinal > $exercicio['data_fim']) {
                throw new RuntimeException('O intervalo informado não pertence integralmente ao exercício selecionado.');
            }
        }
    }

    private function validarEmpresa(int $empresaId): void
    {
        if ($empresaId <= 0) {
            throw new RuntimeException('Empresa inválida para gerar os Indicadores Contabilísticos.');
        }
    }

    private function validarFilial(int $empresaId, int $filialId): void
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM filiais WHERE id = :filial_id AND empresa_id = :empresa_id LIMIT 1
        ");

        $stmt->execute([':filial_id' => $filialId, ':empresa_id' => $empresaId]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('A filial selecionada não pertence à empresa.');
        }
    }

    private function validarPlano(int $empresaId, int $planoContasId): void
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM planos_contas WHERE id = :plano_contas_id AND empresa_id = :empresa_id LIMIT 1
        ");

        $stmt->execute([':plano_contas_id' => $planoContasId, ':empresa_id' => $empresaId]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('O plano de contas selecionado não pertence à empresa.');
        }
    }

    private function validarExercicio(int $empresaId, int $exercicioId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, codigo, data_inicio, data_fim
            FROM exercicios_contabilisticos
            WHERE id = :exercicio_id AND empresa_id = :empresa_id
            LIMIT 1
        ");

        $stmt->execute([':exercicio_id' => $exercicioId, ':empresa_id' => $empresaId]);
        $exercicio = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$exercicio) {
            throw new RuntimeException('Exercício contabilístico não encontrado.');
        }

        return $exercicio;
    }

    private function validarData(string $data, string $campo): void
    {
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        $erros = DateTimeImmutable::getLastErrors();

        if (!$objeto || (is_array($erros) && ($erros['warning_count'] > 0 || $erros['error_count'] > 0))) {
            throw new RuntimeException($campo . ' inválida.');
        }
    }
}
