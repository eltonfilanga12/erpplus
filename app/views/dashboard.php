<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Dashboard Executivo
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item active">
                            Dashboard
                        </li>
                    </ul>
                </div>

                <div class="col-auto">

                    <select
                        id="dashboardPeriodo"
                        class="form-control">
                        <option value="mes">
                            Este mês
                        </option>

                        <option value="trimestre">
                            Este trimestre
                        </option>

                        <option value="ano">
                            Este ano
                        </option>
                    </select>

                </div>

            </div>
        </div>


        <!-- ===================================================== -->
        <!-- INDICADORES PRINCIPAIS -->
        <!-- ===================================================== -->

        <div class="row">

            <!-- Vendas -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-primary">
                                <i class="fa fa-shopping-cart"></i>
                            </span>

                            <div class="dash-count">
                                <h3 id="dashVendas">
                                    0,00 MTn
                                </h3>
                            </div>

                        </div>

                        <div class="dash-widget-info">
                            <h6 class="text-muted">
                                Total de Vendas
                            </h6>
                        </div>

                    </div>

                </div>

            </div>


            <!-- Receitas -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-success">
                                <i class="fa fa-arrow-up"></i>
                            </span>

                            <div class="dash-count">
                                <h3 id="dashReceitas">
                                    0,00 MTn
                                </h3>
                            </div>

                        </div>

                        <div class="dash-widget-info">
                            <h6 class="text-muted">
                                Receitas
                            </h6>
                        </div>

                    </div>

                </div>

            </div>


            <!-- Despesas -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-danger">
                                <i class="fa fa-arrow-down"></i>
                            </span>

                            <div class="dash-count">
                                <h3 id="dashDespesas">
                                    0,00 MTn
                                </h3>
                            </div>

                        </div>

                        <div class="dash-widget-info">
                            <h6 class="text-muted">
                                Despesas
                            </h6>
                        </div>

                    </div>

                </div>

            </div>


            <!-- Lucro -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-warning">
                                <i class="fa fa-line-chart"></i>
                            </span>

                            <div class="dash-count">
                                <h3 id="dashLucro">
                                    0,00 MTn
                                </h3>
                            </div>

                        </div>

                        <div class="dash-widget-info">
                            <h6 class="text-muted">
                                Resultado Líquido
                            </h6>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- SEGUNDA LINHA -->
        <!-- ===================================================== -->

        <div class="row">

            <!-- Stock -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Valor do Stock
                        </h6>

                        <h3 id="dashStock">
                            0,00 MTn
                        </h3>

                    </div>

                </div>

            </div>


            <!-- Clientes -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Clientes
                        </h6>

                        <h3 id="dashClientes">
                            0
                        </h3>

                    </div>

                </div>

            </div>


            <!-- Contas receber -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Contas a Receber
                        </h6>

                        <h3 id="dashReceber">
                            0,00 MTn
                        </h3>

                    </div>

                </div>

            </div>


            <!-- Contas pagar -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Contas a Pagar
                        </h6>

                        <h3 id="dashPagar">
                            0,00 MTn
                        </h3>

                    </div>

                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- GRÁFICOS -->
        <!-- ===================================================== -->

        <div class="row">

            <!-- Vendas -->
            <div class="col-md-7">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Vendas por Período
                        </h4>

                    </div>

                    <div class="card-body">

                        <div style="height: 320px;">
                            <canvas id="graficoVendas"></canvas>
                        </div>

                    </div>

                </div>

            </div>


            <!-- Receitas x Despesas -->
            <div class="col-md-5">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Receitas vs Despesas
                        </h4>

                    </div>

                    <div class="card-body">

                        <div style="height: 320px;">
                            <canvas id="graficoReceitasDespesas"></canvas>
                        </div>
                    </div>

                </div>

            </div>

        </div>


        <div class="row">

            <!-- Fluxo caixa -->
            <div class="col-md-7">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Fluxo de Caixa
                        </h4>

                    </div>

                    <div class="card-body">

                        <div style="height: 320px;">
                            <canvas id="graficoFluxoCaixa"></canvas>
                        </div>

                    </div>

                </div>

            </div>


            <!-- Top Produtos -->
            <div class="col-md-5">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Top Produtos
                        </h4>

                    </div>

                    <div class="card-body">

                        <div style="height: 320px;">
                            <canvas id="graficoTopProdutos"></canvas>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- INDICADORES CONTABILÍSTICOS -->
        <!-- ===================================================== -->

        <div class="row">

            <div class="col-12">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Indicadores Contabilísticos
                        </h4>

                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-3">

                                <small class="text-muted">
                                    Ativo
                                </small>

                                <h4 id="dashAtivo">
                                    0,00 MTn
                                </h4>

                            </div>


                            <div class="col-md-3">

                                <small class="text-muted">
                                    Passivo
                                </small>

                                <h4 id="dashPassivo">
                                    0,00 MTn
                                </h4>

                            </div>


                            <div class="col-md-3">

                                <small class="text-muted">
                                    Capital Próprio
                                </small>

                                <h4 id="dashCapital">
                                    0,00 MTn
                                </h4>

                            </div>


                            <div class="col-md-3">

                                <small class="text-muted">
                                    Resultado Líquido
                                </small>

                                <h4 id="dashResultado">
                                    0,00 MTn
                                </h4>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- INDICADORES FINANCEIROS -->
        <!-- ===================================================== -->

        <div class="row">

            <div class="col-12">

                <div class="card">

                    <div class="card-header">

                        <h4 class="card-title mb-0">
                            Indicadores Financeiros
                        </h4>

                    </div>

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-3">
                                <small>
                                    Liquidez
                                </small>

                                <h4 id="dashLiquidez">
                                    0,00
                                </h4>
                            </div>


                            <div class="col-md-3">
                                <small>
                                    Endividamento
                                </small>

                                <h4 id="dashEndividamento">
                                    0,00%
                                </h4>
                            </div>


                            <div class="col-md-3">
                                <small>
                                    Rentabilidade
                                </small>

                                <h4 id="dashRentabilidade">
                                    0,00%
                                </h4>
                            </div>


                            <div class="col-md-3">
                                <small>
                                    Rotação de Stock
                                </small>

                                <h4 id="dashRotacaoStock">
                                    0,00
                                </h4>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>