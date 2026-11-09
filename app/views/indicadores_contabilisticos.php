<?php

declare(strict_types=1);

Auth::requireLogin();

?>

<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- =========================================
             CABEÇALHO
        ========================================== -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Indicadores Contabilísticos
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>

                        <li class="breadcrumb-item">
                            Contabilidade
                        </li>

                        <li class="breadcrumb-item active">
                            Indicadores Contabilísticos
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <!-- =========================================
             INFORMAÇÃO
        ========================================== -->

        <div class="alert alert-info">

            <div class="d-flex align-items-start">

                <i class="fa fa-info-circle mr-3 mt-1"></i>

                <div>

                    <strong>
                        Análise contabilística e financeira
                    </strong>

                    <div class="mt-1">

                        Os indicadores apresentados são calculados
                        a partir do Balanço Patrimonial,
                        Demonstração de Resultados e
                        Demonstração de Fluxos de Caixa,
                        conforme os filtros selecionados.

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             FILTROS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Filtros da análise
                </h4>

            </div>

            <div class="card-body">

                <form id="formIndicadoresContabilisticos">

                    <div class="row">

                        <!-- PLANO DE CONTAS -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="indicadoresPlanoContasId">
                                    Plano de Contas
                                </label>

                                <select
                                    class="form-control"
                                    id="indicadoresPlanoContasId"
                                    name="plano_contas_id">

                                    <option value="">
                                        Todos os planos de contas
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- EXERCÍCIO -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="indicadoresExercicioId">
                                    Exercício contabilístico
                                </label>

                                <select
                                    class="form-control"
                                    id="indicadoresExercicioId"
                                    name="exercicio_id">

                                    <option value="">
                                        Selecione o exercício
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- FILIAL -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="indicadoresFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="indicadoresFilialId"
                                    name="filial_id">

                                    <option value="">
                                        Todas as filiais
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- DATA INICIAL -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="indicadoresDataInicial">
                                    Data inicial
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="indicadoresDataInicial"
                                    name="data_inicial"
                                    required>

                            </div>

                        </div>


                        <!-- DATA FINAL -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="indicadoresDataFinal">
                                    Data final
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="indicadoresDataFinal"
                                    name="data_final"
                                    required>

                            </div>

                        </div>


                        <!-- COMPARAÇÃO -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label class="d-block">
                                    Comparação
                                </label>

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="indicadoresCompararPeriodoAnterior"
                                        name="comparar_periodo_anterior"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="indicadoresCompararPeriodoAnterior">

                                        Comparar com período anterior

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- BOTÕES -->

                    <div class="row">

                        <div class="col-12 text-right">

                            <button
                                type="button"
                                class="btn btn-light mr-2"
                                id="btnLimparIndicadores">

                                <i class="fa fa-eraser mr-1"></i>

                                Limpar

                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                                id="btnGerarIndicadores">

                                <i class="fa fa-line-chart mr-1"></i>

                                Gerar Indicadores

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        <!-- =========================================
             CARREGAMENTO
        ========================================== -->

        <div
            id="indicadoresCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status"
                aria-hidden="true">
            </span>

            A calcular os indicadores contabilísticos...

        </div>


        <!-- =========================================
             RESULTADO
        ========================================== -->

        <div
            id="painelResultadoIndicadores"
            style="display:none;">


            <!-- =====================================
                 CABEÇALHO DO RELATÓRIO
            ====================================== -->

            <div class="card">

                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <h4 class="mb-1">
                                Painel de Indicadores Contabilísticos
                            </h4>

                            <div class="text-muted">

                                Período:

                                <strong id="indicadoresPeriodoRelatorio">
                                    -
                                </strong>

                            </div>

                        </div>


                        <div class="col-md-4 text-md-right mt-3 mt-md-0">

                            <button
                                type="button"
                                class="btn btn-light mr-1"
                                id="btnImprimirIndicadores"
                                disabled>

                                <i class="fa fa-print"></i>

                            </button>

                            <button
                                type="button"
                                class="btn btn-success mr-1"
                                id="btnExportarExcelIndicadores"
                                disabled>

                                <i class="fa fa-file-excel-o mr-1"></i>

                                Excel

                            </button>

                            <button
                                type="button"
                                class="btn btn-danger"
                                id="btnExportarPdfIndicadores"
                                disabled>

                                <i class="fa fa-file-pdf-o mr-1"></i>

                                PDF

                            </button>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 SAÚDE FINANCEIRA
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Saúde financeira
                    </h4>

                </div>

                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-lg-3 col-md-6 mb-3">

                            <span class="text-muted">
                                Pontuação
                            </span>

                            <h3
                                class="mt-2 mb-0"
                                id="indicadoresPontuacaoSaude">

                                0/100

                            </h3>

                        </div>


                        <div class="col-lg-3 col-md-6 mb-3">

                            <span class="text-muted">
                                Classificação
                            </span>

                            <h3
                                class="mt-2 mb-0"
                                id="indicadoresClassificacaoSaude">

                                -

                            </h3>

                        </div>


                        <div class="col-lg-3 col-md-6 mb-3">

                            <span class="text-muted">
                                Alertas críticos
                            </span>

                            <h3
                                class="mt-2 mb-0 text-danger"
                                id="indicadoresAlertasCriticos">

                                0

                            </h3>

                        </div>


                        <div class="col-lg-3 col-md-6 mb-3">

                            <span class="text-muted">
                                Alertas de atenção
                            </span>

                            <h3
                                class="mt-2 mb-0 text-warning"
                                id="indicadoresAlertasAtencao">

                                0

                            </h3>

                        </div>

                    </div>


                    <div class="progress mt-2">

                        <div
                            class="progress-bar"
                            id="indicadoresBarraSaude"
                            role="progressbar"
                            style="width:0%;"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-valuenow="0">
                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 ESTADO DO BALANÇO
            ====================================== -->

            <div
                id="estadoBalancoIndicadores"
                class="alert"
                style="display:none;">
            </div>


            <!-- =====================================
                 BALANÇO PATRIMONIAL
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Estrutura patrimonial
                    </h4>

                </div>

                <div class="card-body">

                    <div class="row">

                        <?php

                        $cardsBalanco = [
                            [
                                'titulo' => 'Ativo Total',
                                'id' => 'indicadoresAtivoTotal'
                            ],
                            [
                                'titulo' => 'Ativo Corrente',
                                'id' => 'indicadoresAtivoCorrente'
                            ],
                            [
                                'titulo' => 'Ativo Não Corrente',
                                'id' => 'indicadoresAtivoNaoCorrente'
                            ],
                            [
                                'titulo' => 'Passivo Total',
                                'id' => 'indicadoresPassivoTotal'
                            ],
                            [
                                'titulo' => 'Passivo Corrente',
                                'id' => 'indicadoresPassivoCorrente'
                            ],
                            [
                                'titulo' => 'Passivo Não Corrente',
                                'id' => 'indicadoresPassivoNaoCorrente'
                            ],
                            [
                                'titulo' => 'Capital Próprio',
                                'id' => 'indicadoresCapitalProprio'
                            ],
                            [
                                'titulo' => 'Resultado do Balanço',
                                'id' => 'indicadoresResultadoBalanco'
                            ]
                        ];

                        foreach ($cardsBalanco as $card) {
                        ?>

                            <div class="col-xl-3 col-sm-6 col-12">

                                <div class="border rounded p-3 text-center mb-3">

                                    <span class="text-muted">
                                        <?= htmlspecialchars(
                                            $card['titulo'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <h4
                                        class="mt-2 mb-0"
                                        id="<?= htmlspecialchars(
                                                $card['id'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                        0,00 MZN

                                    </h4>

                                </div>

                            </div>

                        <?php
                        }

                        ?>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 DEMONSTRAÇÃO DE RESULTADOS
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Demonstração de Resultados
                    </h4>

                </div>

                <div class="card-body">

                    <div class="row">

                        <?php

                        $cardsDre = [

                            [
                                'titulo' =>
                                'Volume de Negócios',

                                'id' =>
                                'indicadoresVolumeNegocios'
                            ],

                            [
                                'titulo' =>
                                'Rendimentos Totais',

                                'id' =>
                                'indicadoresRendimentos'
                            ],

                            [
                                'titulo' =>
                                'Custos das Vendas',

                                'id' =>
                                'indicadoresCustos'
                            ],

                            [
                                'titulo' =>
                                'Gastos Operacionais',

                                'id' =>
                                'indicadoresGastosOperacionais'
                            ],

                            [
                                'titulo' =>
                                'Amortizações',

                                'id' =>
                                'indicadoresAmortizacoes'
                            ],

                            [
                                'titulo' =>
                                'Rendimentos Financeiros',

                                'id' =>
                                'indicadoresRendimentosFinanceiros'
                            ],

                            [
                                'titulo' =>
                                'Gastos Financeiros',

                                'id' =>
                                'indicadoresGastosFinanceiros'
                            ],

                            [
                                'titulo' =>
                                'Resultado Financeiro',

                                'id' =>
                                'indicadoresResultadoFinanceiro'
                            ],

                            [
                                'titulo' =>
                                'Resultado Bruto',

                                'id' =>
                                'indicadoresResultadoBruto'
                            ],

                            [
                                'titulo' =>
                                'EBITDA',

                                'id' =>
                                'indicadoresEbitda'
                            ],

                            [
                                'titulo' =>
                                'EBIT',

                                'id' =>
                                'indicadoresEbit'
                            ],

                            [
                                'titulo' =>
                                'Resultado Operacional',

                                'id' =>
                                'indicadoresResultadoOperacional'
                            ],

                            [
                                'titulo' =>
                                'Resultado Antes de Impostos',

                                'id' =>
                                'indicadoresResultadoAntesImpostos'
                            ],

                            [
                                'titulo' =>
                                'Imposto sobre o Rendimento',

                                'id' =>
                                'indicadoresImpostoRendimento'
                            ],

                            [
                                'titulo' =>
                                'Resultado Líquido',

                                'id' =>
                                'indicadoresResultadoLiquido'
                            ]
                        ];

                        foreach ($cardsDre as $card) {
                        ?>

                            <div class="col-xl-3 col-sm-6 col-12">

                                <div class="border rounded p-3 text-center mb-3">

                                    <span class="text-muted">
                                        <?= htmlspecialchars(
                                            $card['titulo'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <h4
                                        class="mt-2 mb-0"
                                        id="<?= htmlspecialchars(
                                                $card['id'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                        0,00 MZN

                                    </h4>

                                </div>

                            </div>

                        <?php
                        }

                        ?>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 SALDOS RELEVANTES
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Saldos relevantes
                    </h4>

                </div>

                <div class="card-body">

                    <div class="row">

                        <?php

                        $cardsSaldos = [
                            [
                                'titulo' => 'Disponibilidades',
                                'id' => 'indicadoresDisponibilidades'
                            ],
                            [
                                'titulo' => 'Inventários',
                                'id' => 'indicadoresInventarios'
                            ],
                            [
                                'titulo' => 'Clientes',
                                'id' => 'indicadoresClientes'
                            ],
                            [
                                'titulo' => 'Fornecedores',
                                'id' => 'indicadoresFornecedores'
                            ],
                            [
                                'titulo' => 'Fundo de Maneio',
                                'id' => 'indicadoresFundoManeio'
                            ],
                            [
                                'titulo' => 'Capital Investido',
                                'id' => 'indicadoresCapitalInvestido'
                            ]
                        ];

                        foreach ($cardsSaldos as $card) {
                        ?>

                            <div class="col-xl-4 col-sm-6 col-12">

                                <div class="border rounded p-3 text-center mb-3">

                                    <span class="text-muted">
                                        <?= htmlspecialchars(
                                            $card['titulo'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <h4
                                        class="mt-2 mb-0"
                                        id="<?= htmlspecialchars(
                                                $card['id'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                        0,00 MZN

                                    </h4>

                                </div>

                            </div>

                        <?php
                        }

                        ?>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 RÁCIOS
            ====================================== -->

            <div class="row">

                <!-- LIQUIDEZ -->

                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Liquidez
                            </h4>

                        </div>

                        <div class="card-body">

                            <table class="table table-bordered mb-0">

                                <tbody>

                                    <tr>
                                        <td>Liquidez Corrente</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresLiquidezCorrente">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Liquidez Reduzida</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresLiquidezReduzida">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Liquidez Imediata</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresLiquidezImediata">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            Liquidez Geral

                                            <i
                                                class="fa fa-info-circle text-muted ml-1"
                                                title="(Ativo Corrente + Realizável a Longo Prazo) / (Passivo Corrente + Passivo Não Corrente)">
                                            </i>
                                        </td>

                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresLiquidezGeral">

                                            N/D

                                        </td>


                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <!-- ESTRUTURA FINANCEIRA -->

                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Estrutura financeira
                            </h4>

                        </div>

                        <div class="card-body">

                            <table class="table table-bordered mb-0">

                                <tbody>

                                    <tr>
                                        <td>Autonomia Financeira</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresAutonomiaFinanceira">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Endividamento</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresEndividamento">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Solvabilidade</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresSolvabilidade">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Cobertura do Passivo</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresCoberturaPassivo">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Dependência Financeira</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresDependenciaFinanceira">
                                            N/D
                                        </td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            <div class="row">

                <!-- RENTABILIDADE -->

                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Rentabilidade
                            </h4>

                        </div>

                        <div class="card-body">

                            <table class="table table-bordered mb-0">

                                <tbody>

                                    <tr>
                                        <td>ROA</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresRoa">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>ROE</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresRoe">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Rentabilidade das Vendas</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresRentabilidadeVendas">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Rentabilidade do Capital Investido</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresRentabilidadeCapitalInvestido">
                                            N/D
                                        </td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <!-- MARGENS -->

                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Margens e custos
                            </h4>

                        </div>

                        <div class="card-body">

                            <table class="table table-bordered mb-0">

                                <tbody>

                                    <tr>
                                        <td>Margem Bruta</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresMargemBruta">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Margem Operacional</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresMargemOperacional">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Margem EBITDA</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresMargemEbitda">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Margem Líquida</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresMargemLiquida">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Peso dos Custos</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresPesoCustos">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Peso dos Gastos Totais</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresPesoGastos">
                                            N/D
                                        </td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            <div class="row">

                <!-- EFICIÊNCIA -->

                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Eficiência
                            </h4>

                        </div>

                        <div class="card-body">

                            <table class="table table-bordered mb-0">

                                <tbody>

                                    <tr>
                                        <td>Rotação do Ativo</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresRotacaoAtivo">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Rotação do Capital Próprio</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresRotacaoCapitalProprio">
                                            N/D
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Cobertura dos Gastos</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresCoberturaGastos">
                                            N/D
                                        </td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <!-- FUNDO DE MANEIO -->

                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Fundo de maneio e tesouraria
                            </h4>

                        </div>

                        <div class="card-body">

                            <table class="table table-bordered mb-0">

                                <tbody>

                                    <tr>
                                        <td>
                                            Necessidade de Fundo de Maneio
                                            <small class="text-muted d-block">
                                                Cálculo simplificado
                                            </small>
                                        </td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresNecessidadeFundoManeio">
                                            0,00 MZN
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Tesouraria Líquida</td>
                                        <td
                                            class="text-right font-weight-bold"
                                            id="indicadoresTesourariaLiquida">
                                            0,00 MZN
                                        </td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 FLUXOS DE CAIXA
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Indicadores de Fluxos de Caixa
                    </h4>

                </div>

                <div class="card-body">

                    <div
                        id="indicadoresAvisoFluxoCaixa"
                        class="alert alert-warning"
                        style="display:none;">
                    </div>

                    <div class="row">

                        <?php

                        $cardsFluxos = [
                            [
                                'titulo' => 'Fluxo Operacional',
                                'id' => 'indicadoresFluxoOperacional'
                            ],
                            [
                                'titulo' => 'Fluxo de Investimento',
                                'id' => 'indicadoresFluxoInvestimento'
                            ],
                            [
                                'titulo' => 'Fluxo de Financiamento',
                                'id' => 'indicadoresFluxoFinanciamento'
                            ],
                            [
                                'titulo' => 'Variação Líquida de Caixa',
                                'id' => 'indicadoresVariacaoLiquidaCaixa'
                            ],
                            [
                                'titulo' => 'Saldo Final de Caixa',
                                'id' => 'indicadoresSaldoFinalCaixa'
                            ],
                            [
                                'titulo' => 'Conversão do Resultado em Caixa',
                                'id' => 'indicadoresConversaoResultadoCaixa'
                            ]
                        ];

                        foreach ($cardsFluxos as $card) {
                        ?>

                            <div class="col-xl-4 col-sm-6 col-12">

                                <div class="border rounded p-3 text-center mb-3">

                                    <span class="text-muted">
                                        <?= htmlspecialchars(
                                            $card['titulo'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>

                                    <h4
                                        class="mt-2 mb-0"
                                        id="<?= htmlspecialchars(
                                                $card['id'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                        Indisponível

                                    </h4>

                                </div>

                            </div>

                        <?php
                        }

                        ?>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 GRÁFICOS
            ====================================== -->

            <div class="row">

                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Estrutura financeira
                            </h4>

                        </div>

                        <div class="card-body">

                            <div style="height:320px;">

                                <canvas id="graficoEstruturaFinanceiraIndicadores">
                                </canvas>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Resultados do período
                            </h4>

                        </div>

                        <div class="card-body">

                            <div style="height:320px;">

                                <canvas id="graficoResultadosIndicadores">
                                </canvas>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 COMPARATIVO
            ====================================== -->

            <div
                class="card"
                id="painelComparativoIndicadores"
                style="display:none;">

                <div class="card-header">

                    <h4 class="card-title mb-1">
                        Comparação com o período anterior
                    </h4>

                    <span class="text-muted">

                        Período anterior:

                        <strong id="indicadoresPeriodoAnterior">
                            -
                        </strong>

                    </span>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped">

                            <thead>

                                <tr>

                                    <th>
                                        Indicador
                                    </th>

                                    <th class="text-right">
                                        Período atual
                                    </th>

                                    <th class="text-right">
                                        Período anterior
                                    </th>

                                    <th class="text-right">
                                        Variação
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php

                                $linhasComparativo = [

                                    [
                                        'id' =>
                                        'comparativoAtivoTotal',

                                        'nome' =>
                                        'Ativo Total'
                                    ],

                                    [
                                        'id' =>
                                        'comparativoPassivoTotal',

                                        'nome' =>
                                        'Passivo Total'
                                    ],

                                    [
                                        'id' =>
                                        'comparativoCapitalProprio',

                                        'nome' =>
                                        'Capital Próprio'
                                    ],

                                    [
                                        'id' =>
                                        'comparativoVolumeNegocios',

                                        'nome' =>
                                        'Volume de Negócios'
                                    ],

                                    [
                                        'id' =>
                                        'comparativoResultadoBruto',

                                        'nome' =>
                                        'Resultado Bruto'
                                    ],

                                    [
                                        'id' =>
                                        'comparativoEbitda',

                                        'nome' =>
                                        'EBITDA'
                                    ],

                                    [
                                        'id' =>
                                        'comparativoEbit',

                                        'nome' =>
                                        'EBIT'
                                    ],

                                    [
                                        'id' =>
                                        'comparativoResultadoOperacional',

                                        'nome' =>
                                        'Resultado Operacional'
                                    ],

                                    [
                                        'id' =>
                                        'comparativoResultadoAntesImpostos',

                                        'nome' =>
                                        'Resultado Antes de Impostos'
                                    ],

                                    [
                                        'id' =>
                                        'comparativoResultadoLiquido',

                                        'nome' =>
                                        'Resultado Líquido'
                                    ]
                                ];

                                foreach ($linhasComparativo as $linha) {
                                ?>

                                    <tr id="<?= htmlspecialchars(
                                                $linha['id'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                        <td>
                                            <?= htmlspecialchars(
                                                $linha['nome'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td
                                            class="text-right font-weight-bold"
                                            data-campo="atual">

                                            0,00 MZN

                                        </td>

                                        <td
                                            class="text-right"
                                            data-campo="anterior">

                                            0,00 MZN

                                        </td>

                                        <td
                                            class="text-right font-weight-bold"
                                            data-campo="variacao">

                                            N/D

                                        </td>

                                    </tr>

                                <?php
                                }

                                ?>

                            </tbody>

                        </table>

                    </div>


                    <div class="mt-4" style="height:340px;">

                        <canvas id="graficoComparativoIndicadores">
                        </canvas>

                    </div>

                </div>

            </div>

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Composição Patrimonial
                    </h4>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-4 mb-3">

                            <span class="text-muted">
                                Ativo Corrente
                            </span>

                            <h4 id="indicadoresPesoAtivoCorrente">
                                N/D
                            </h4>

                        </div>


                        <div class="col-md-4 mb-3">

                            <span class="text-muted">
                                Ativo Não Corrente
                            </span>

                            <h4 id="indicadoresPesoAtivoNaoCorrente">
                                N/D
                            </h4>

                        </div>


                        <div class="col-md-4 mb-3">

                            <span class="text-muted">
                                Capital Próprio
                            </span>

                            <h4 id="indicadoresPesoCapitalProprio">
                                N/D
                            </h4>

                        </div>


                        <div class="col-md-6 mb-3">

                            <span class="text-muted">
                                Passivo Corrente
                            </span>

                            <h4 id="indicadoresPesoPassivoCorrente">
                                N/D
                            </h4>

                        </div>


                        <div class="col-md-6 mb-3">

                            <span class="text-muted">
                                Passivo Não Corrente
                            </span>

                            <h4 id="indicadoresPesoPassivoNaoCorrente">
                                N/D
                            </h4>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 ALERTAS
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Alertas e recomendações
                    </h4>

                </div>

                <div class="card-body">

                    <div id="listaAlertasIndicadores">

                        <div class="alert alert-light border mb-0">

                            Gere os indicadores para apresentar
                            a análise financeira.

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>