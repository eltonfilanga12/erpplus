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
                        Indicadores Financeiros
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="/erpplus/index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>

                        <li class="breadcrumb-item active">
                            Indicadores Financeiros
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="btnAtualizarIndicadores">

                        <i class="fa fa-refresh"></i>

                        Atualizar

                    </button>

                </div>

            </div>

        </div>


        <!-- =========================================
             FILTROS
        ========================================== -->

        <div class="card">

            <div class="card-body">

                <form id="formFiltroIndicadores">

                    <div class="row align-items-end">

                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="indicador_data_inicial">
                                    Data Inicial
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="indicador_data_inicial"
                                    name="data_inicial">

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="indicador_data_final">
                                    Data Final
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="indicador_data_final"
                                    name="data_final">

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="indicador_meses">
                                    Período do gráfico
                                </label>

                                <select
                                    class="form-control"
                                    id="indicador_meses"
                                    name="meses">

                                    <option value="6">
                                        Últimos 6 meses
                                    </option>

                                    <option value="12" selected>
                                        Últimos 12 meses
                                    </option>

                                    <option value="18">
                                        Últimos 18 meses
                                    </option>

                                    <option value="24">
                                        Últimos 24 meses
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="form-group">

                                <button
                                    type="submit"
                                    class="btn btn-primary btn-block"
                                    id="btnFiltrarIndicadores">

                                    <i class="fa fa-filter"></i>

                                    Aplicar Filtro

                                </button>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        <!-- =========================================
             INDICADOR DE CARREGAMENTO
        ========================================== -->

        <div
            id="indicadoresCarregando"
            class="alert alert-info text-center"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status">
            </span>

            A carregar os indicadores financeiros...

        </div>


        <!-- =========================================
             PRIMEIRA LINHA DE CARTÕES
        ========================================== -->

        <div class="row">

            <!-- SALDO EM CAIXA -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-money"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="indicadorSaldoCaixa">
                                0,00 MZN
                            </h3>

                            <span style="font-size:14px;">
                                Saldo em Caixa
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- SALDO BANCÁRIO -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-university"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="indicadorSaldoBancario">
                                0,00 MZN
                            </h3>

                            <span style="font-size:14px;">
                                Saldo Bancário
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- TOTAL A RECEBER -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-arrow-circle-down"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="indicadorTotalReceber">
                                0,00 MZN
                            </h3>

                            <span style="font-size:14px;">
                                Total a Receber
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- TOTAL A PAGAR -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-arrow-circle-up"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="indicadorTotalPagar">
                                0,00 MZN
                            </h3>

                            <span style="font-size:14px;">
                                Total a Pagar
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             SEGUNDA LINHA DE CARTÕES
        ========================================== -->

        <style>
            .dash-widget {
                height: 125px;
            }

            .dash-widget .card-body {
                height: 100%;
                display: flex;
                align-items: center;
            }

            .dash-widget-info {
                min-width: 0;
                flex: 1;
            }

            .dash-widget-info h3 {
                font-size: clamp(13px, 1.35vw, 20px);
                font-weight: 700;
                line-height: 1.2;

                white-space: nowrap;
                margin-bottom: 5px;
            }
        </style>

        <div class="row">

            <!-- RECEBIDO -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-plus-circle"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="indicadorRecebidoPeriodo">
                                0,00 MZN
                            </h3>

                            <span style="font-size:14px;">
                                Recebido no Período
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- PAGO -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-minus-circle"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="indicadorPagoPeriodo">
                                0,00 MZN
                            </h3>

                            <span style="font-size:14px;">
                                Pago no Período
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- RESULTADO -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-line-chart"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="indicadorResultadoPeriodo">
                                0,00 MZN
                            </h3>

                            <span style="font-size:14px;">
                                Resultado do Período
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- VALORES VENCIDOS -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-exclamation-triangle"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="indicadorTotalVencido">
                                0,00 MZN
                            </h3>

                            <span style="font-size:14px;">
                                Valores Vencidos
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             INDICADORES COMPLEMENTARES
        ========================================== -->
        <!-- =========================================
     PRAZOS MÉDIOS
========================================== -->

        <div class="row">

            <!-- PRAZO MÉDIO DE PAGAMENTO -->

            <div class="col-md-6">

                <div class="card">

                    <div class="card-body text-center">

                        <h5>
                            Prazo Médio de Pagamento
                        </h5>

                        <h2
                            id="indicadorPrazoMedioPagamento">

                            0,0 dias

                        </h2>

                        <small class="text-muted">

                            Tempo médio entre a emissão
                            e o pagamento aos fornecedores

                        </small>

                    </div>

                </div>

            </div>


            <!-- PRAZO MÉDIO DE RECEBIMENTO -->

            <div class="col-md-6">

                <div class="card">

                    <div class="card-body text-center">

                        <h5>
                            Prazo Médio de Recebimento
                        </h5>

                        <h2
                            id="indicadorPrazoMedioRecebimento">

                            0,0 dias

                        </h2>

                        <small class="text-muted">

                            Tempo médio entre a emissão
                            e o recebimento dos clientes

                        </small>

                    </div>

                </div>

            </div>

        </div>

        <div class="row">

            <div class="col-md-4">

                <div class="card">

                    <div class="card-body text-center">

                        <h5>
                            Saldo Disponível
                        </h5>

                        <h2 id="indicadorSaldoDisponivel">
                            0,00 MZN
                        </h2>

                        <small class="text-muted">
                            Caixa + Bancos
                        </small>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="card">

                    <div class="card-body text-center">

                        <h5>
                            Inadimplência
                        </h5>

                        <h2 id="indicadorInadimplencia">
                            0,00%
                        </h2>

                        <small class="text-muted">
                            Valores vencidos a receber
                        </small>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="card">

                    <div class="card-body text-center">

                        <h5>
                            Liquidez Imediata
                        </h5>

                        <h2 id="indicadorLiquidezImediata">
                            0,00
                        </h2>

                        <small class="text-muted">
                            Disponibilidade ÷ Contas a pagar
                        </small>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             GRÁFICOS
        ========================================== -->

        <div class="row">

            <!-- ENTRADAS X SAÍDAS -->

            <div class="col-xl-8 col-lg-12">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Entradas versus Saídas
                        </h4>

                    </div>

                    <div class="card-body">

                        <div
                            class="chart-container"
                            style="position:relative; min-height:360px;">

                            <canvas id="graficoFluxoMensal"></canvas>

                        </div>

                    </div>

                </div>

            </div>


            <!-- CONTAS VENCIDAS -->

            <div class="col-xl-4 col-lg-12">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Resumo de Vencidos
                        </h4>

                    </div>

                    <div class="card-body">

                        <div
                            class="chart-container"
                            style="position:relative; min-height:360px;">

                            <canvas id="graficoContasVencidas"></canvas>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             CENTROS DE CUSTO
        ========================================== -->

        <div class="row">

            <div class="col-12">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Receitas e Despesas por Centro de Custo
                        </h4>

                    </div>

                    <div class="card-body">

                        <div
                            class="chart-container"
                            style="position:relative; min-height:380px;">

                            <canvas id="graficoCentrosCusto"></canvas>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             ALERTAS E DÍVIDAS
        ========================================== -->

        <div class="row">

            <!-- CONTAS VENCIDAS -->

            <div class="col-xl-6 col-lg-12">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Contas Vencidas
                        </h4>

                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table
                                class="table table-striped table-hover"
                                id="tabelaContasVencidas">

                                <thead>

                                    <tr>

                                        <th>Tipo</th>

                                        <th>Entidade</th>

                                        <th>Documento</th>

                                        <th>Vencimento</th>

                                        <th>Dias</th>

                                        <th class="text-right">
                                            Saldo
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td
                                            colspan="6"
                                            class="text-center text-muted">

                                            Nenhuma conta vencida.

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            <!-- CLIENTES -->

            <div class="col-xl-6 col-lg-12">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Clientes com Maior Dívida
                        </h4>

                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table
                                class="table table-striped table-hover"
                                id="tabelaClientesDivida">

                                <thead>

                                    <tr>

                                        <th>Cliente</th>

                                        <th>Contas</th>

                                        <th class="text-right">
                                            Dívida Total
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td
                                            colspan="3"
                                            class="text-center text-muted">

                                            Nenhum cliente com dívida.

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             FORNECEDORES
        ========================================== -->

        <div class="row">

            <div class="col-12">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Fornecedores com Maiores Valores Pendentes
                        </h4>

                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table
                                class="table table-striped table-hover"
                                id="tabelaFornecedoresDivida">

                                <thead>

                                    <tr>

                                        <th>Fornecedor</th>

                                        <th>Quantidade de Contas</th>

                                        <th class="text-right">
                                            Valor Pendente
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td
                                            colspan="3"
                                            class="text-center text-muted">

                                            Nenhum fornecedor com valor pendente.

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>