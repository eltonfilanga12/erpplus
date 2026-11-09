<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- ==================================================
        CABEÇALHO
        ================================================== -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Fluxo de Caixa
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>

                        <li class="breadcrumb-item">
                            Financeiro
                        </li>

                        <li class="breadcrumb-item active">
                            Fluxo de Caixa
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-light"
                        id="btnAtualizarFluxo">

                        <i class="fa fa-sync"></i>
                        Atualizar

                    </button>

                    <button
                        type="button"
                        class="btn btn-success"
                        id="btnExportarFluxo">

                        <i class="fa fa-file-excel"></i>
                        Exportar

                    </button>

                </div>

            </div>

        </div>


        <!-- ==================================================
        FILTROS
        ================================================== -->

        <div class="card">

            <div class="card-body">

                <div class="form-row">

                    <!-- Período -->
                    <div class="form-group col-md-2">

                        <label for="fluxo_data_inicio">
                            Data Inicial
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="fluxo_data_inicio">

                    </div>

                    <div class="form-group col-md-2">

                        <label for="fluxo_data_fim">
                            Data Final
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="fluxo_data_fim">

                    </div>

                    <!-- Tipo -->
                    <div class="form-group col-md-2">

                        <label for="fluxo_tipo">
                            Tipo de Fluxo
                        </label>

                        <select
                            class="form-control"
                            id="fluxo_tipo">

                            <option value="TODOS">
                                Previsto e Realizado
                            </option>

                            <option value="PREVISTO">
                                Apenas Previsto
                            </option>

                            <option value="REALIZADO">
                                Apenas Realizado
                            </option>

                        </select>

                    </div>

                    <!-- Natureza -->
                    <div class="form-group col-md-2">

                        <label for="fluxo_natureza">
                            Natureza
                        </label>

                        <select
                            class="form-control"
                            id="fluxo_natureza">

                            <option value="">
                                Entradas e Saídas
                            </option>

                            <option value="ENTRADA">
                                Apenas Entradas
                            </option>

                            <option value="SAIDA">
                                Apenas Saídas
                            </option>

                        </select>

                    </div>

                    <!-- Origem -->
                    <div class="form-group col-md-2">

                        <label for="fluxo_origem">
                            Origem
                        </label>

                        <select
                            class="form-control"
                            id="fluxo_origem">

                            <option value="">
                                Todas as origens
                            </option>

                            <option value="CONTA_RECEBER">
                                Contas a Receber
                            </option>

                            <option value="CONTA_PAGAR">
                                Contas a Pagar
                            </option>

                            <option value="CAIXA">
                                Caixa
                            </option>

                            <option value="BANCO">
                                Banco
                            </option>

                        </select>

                    </div>

                    <!-- Filial -->
                    <div class="form-group col-md-2">

                        <label for="fluxo_filial_id">
                            Filial
                        </label>

                        <select
                            class="form-control"
                            id="fluxo_filial_id">

                            <option value="">
                                Todas as filiais
                            </option>

                        </select>

                    </div>

                    <div class="form-group col-md-12 mb-0">

                        <div class="text-right">

                            <button
                                type="button"
                                class="btn btn-light"
                                id="btnLimparFiltrosFluxo">

                                <i class="fa fa-eraser"></i>
                                Limpar

                            </button>

                            <button
                                type="button"
                                class="btn btn-primary"
                                id="btnFiltrarFluxo">

                                <i class="fa fa-search"></i>
                                Consultar

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==================================================
        SALDOS DISPONÍVEIS
        ================================================== -->

        <div class="row">

            <!-- Saldo em Caixa -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-primary">

                                <i class="fa fa-cash-register"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="saldo_total_caixas">
                                    0,00 MZN
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Saldo em Caixas
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Saldo Bancário -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-info">

                                <i class="fa fa-university"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="saldo_total_bancos">
                                    0,00 MZN
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Saldo Bancário
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Saldo Disponível -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-success">

                                <i class="fa fa-wallet"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="saldo_total_disponivel">
                                    0,00 MZN
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Saldo Total Disponível
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Saldo Previsto Final -->
            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-warning">

                                <i class="fa fa-chart-line"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="saldo_previsto_final">
                                    0,00 MZN
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Saldo Previsto Final
                            </h6>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==================================================
        RESUMO PREVISTO
        ================================================== -->

        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Entradas Previstas
                        </h6>

                        <h3
                            id="total_entradas_previstas"
                            class="text-success">

                            0,00 MZN

                        </h3>

                        <small class="text-muted">
                            Contas a receber pendentes
                        </small>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Saídas Previstas
                        </h6>

                        <h3
                            id="total_saidas_previstas"
                            class="text-danger">

                            0,00 MZN

                        </h3>

                        <small class="text-muted">
                            Contas a pagar pendentes
                        </small>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Resultado Previsto
                        </h6>

                        <h3 id="resultado_previsto">
                            0,00 MZN
                        </h3>

                        <small class="text-muted">
                            Entradas menos saídas previstas
                        </small>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Contas Vencidas
                        </h6>

                        <h3
                            id="total_contas_vencidas"
                            class="text-danger">

                            0,00 MZN

                        </h3>

                        <small class="text-muted">
                            Valores vencidos e não liquidados
                        </small>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==================================================
        RESUMO REALIZADO
        ================================================== -->

        <div class="row">

            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Entradas Realizadas
                        </h6>

                        <h3
                            id="total_entradas_realizadas"
                            class="text-success">

                            0,00 MZN

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Saídas Realizadas
                        </h6>

                        <h3
                            id="total_saidas_realizadas"
                            class="text-danger">

                            0,00 MZN

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <h6 class="text-muted">
                            Resultado Realizado
                        </h6>

                        <h3 id="resultado_realizado">
                            0,00 MZN
                        </h3>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==================================================
        GRÁFICO
        ================================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Evolução do Fluxo de Caixa
                </h4>

            </div>

            <div class="card-body">

                <div style="height: 380px;">

                    <canvas id="graficoFluxoCaixaDetalhado"></canvas>

                </div>

            </div>

        </div>


        <!-- ==================================================
        ABAS
        ================================================== -->

        <div class="card">

            <div class="card-header">

                <ul
                    class="nav nav-tabs card-header-tabs"
                    role="tablist">

                    <li class="nav-item">

                        <a
                            class="nav-link active"
                            data-toggle="tab"
                            href="#tabFluxoConsolidado">

                            Fluxo Consolidado

                        </a>

                    </li>

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            data-toggle="tab"
                            href="#tabFluxoPrevisto">

                            Previsto

                        </a>

                    </li>

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            data-toggle="tab"
                            href="#tabFluxoRealizado">

                            Realizado

                        </a>

                    </li>

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            data-toggle="tab"
                            href="#tabDisponibilidades">

                            Disponibilidades

                        </a>

                    </li>

                </ul>

            </div>


            <div class="card-body">

                <div class="tab-content">


                    <!-- ==================================================
                    FLUXO CONSOLIDADO
                    ================================================== -->

                    <div
                        class="tab-pane fade show active"
                        id="tabFluxoConsolidado">

                        <div class="table-responsive">

                            <table
                                id="fluxoCaixaTable"
                                class="table table-striped custom-table mb-0">

                                <thead>

                                    <tr>

                                        <th>Data</th>
                                        <th>Situação</th>
                                        <th>Origem</th>
                                        <th>Documento</th>
                                        <th>Descrição</th>
                                        <th>Entrada</th>
                                        <th>Saída</th>
                                        <th>Saldo Projetado</th>
                                        <th>Filial</th>

                                    </tr>

                                </thead>

                                <tbody></tbody>

                            </table>

                        </div>

                    </div>


                    <!-- ==================================================
                    FLUXO PREVISTO
                    ================================================== -->

                    <div
                        class="tab-pane fade"
                        id="tabFluxoPrevisto">

                        <div class="table-responsive">

                            <table
                                id="fluxoPrevistoTable"
                                class="table table-striped custom-table mb-0">

                                <thead>

                                    <tr>

                                        <th>Vencimento</th>
                                        <th>Natureza</th>
                                        <th>Origem</th>
                                        <th>Entidade</th>
                                        <th>Documento</th>
                                        <th>Descrição</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th>Filial</th>

                                    </tr>

                                </thead>

                                <tbody></tbody>

                            </table>

                        </div>

                    </div>


                    <!-- ==================================================
                    FLUXO REALIZADO
                    ================================================== -->

                    <div
                        class="tab-pane fade"
                        id="tabFluxoRealizado">

                        <div class="table-responsive">

                            <table
                                id="fluxoRealizadoTable"
                                class="table table-striped custom-table mb-0">

                                <thead>

                                    <tr>

                                        <th>Data</th>
                                        <th>Natureza</th>
                                        <th>Origem</th>
                                        <th>Conta/Caixa</th>
                                        <th>Documento</th>
                                        <th>Descrição</th>
                                        <th>Valor</th>
                                        <th>Operador</th>
                                        <th>Filial</th>

                                    </tr>

                                </thead>

                                <tbody></tbody>

                            </table>

                        </div>

                    </div>


                    <!-- ==================================================
                    DISPONIBILIDADES
                    ================================================== -->

                    <div
                        class="tab-pane fade"
                        id="tabDisponibilidades">

                        <div class="row">


                            <!-- Caixas -->
                            <div class="col-md-6">

                                <div class="card">

                                    <div class="card-header">

                                        <h4 class="card-title mb-0">
                                            Saldos dos Caixas
                                        </h4>

                                    </div>

                                    <div class="card-body">

                                        <div class="table-responsive">

                                            <table
                                                id="saldosCaixasTable"
                                                class="table table-striped">

                                                <thead>

                                                    <tr>

                                                        <th>Caixa</th>
                                                        <th>Filial</th>
                                                        <th>Status</th>
                                                        <th class="text-right">
                                                            Saldo
                                                        </th>

                                                    </tr>

                                                </thead>

                                                <tbody></tbody>

                                            </table>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- Bancos -->
                            <div class="col-md-6">

                                <div class="card">

                                    <div class="card-header">

                                        <h4 class="card-title mb-0">
                                            Saldos Bancários
                                        </h4>

                                    </div>

                                    <div class="card-body">

                                        <div class="table-responsive">

                                            <table
                                                id="saldosBancosTable"
                                                class="table table-striped">

                                                <thead>

                                                    <tr>

                                                        <th>Banco</th>
                                                        <th>Conta</th>
                                                        <th>Status</th>
                                                        <th class="text-right">
                                                            Saldo
                                                        </th>

                                                    </tr>

                                                </thead>

                                                <tbody></tbody>

                                            </table>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- ==================================================
MODAL DETALHES DO FLUXO
================================================== -->

<div
    id="modalDetalhesFluxo"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h4 class="modal-title">
                    Detalhes do Movimento
                </h4>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">

                    &times;

                </button>

            </div>

            <div class="modal-body">

                <div class="row">

                    <div class="col-md-6">

                        <p>
                            <strong>Data:</strong>
                            <span id="detalhe_fluxo_data">-</span>
                        </p>

                        <p>
                            <strong>Situação:</strong>
                            <span id="detalhe_fluxo_situacao">-</span>
                        </p>

                        <p>
                            <strong>Natureza:</strong>
                            <span id="detalhe_fluxo_natureza">-</span>
                        </p>

                        <p>
                            <strong>Origem:</strong>
                            <span id="detalhe_fluxo_origem">-</span>
                        </p>

                    </div>

                    <div class="col-md-6">

                        <p>
                            <strong>Documento:</strong>
                            <span id="detalhe_fluxo_documento">-</span>
                        </p>

                        <p>
                            <strong>Entidade:</strong>
                            <span id="detalhe_fluxo_entidade">-</span>
                        </p>

                        <p>
                            <strong>Valor:</strong>
                            <span id="detalhe_fluxo_valor">-</span>
                        </p>

                        <p>
                            <strong>Filial:</strong>
                            <span id="detalhe_fluxo_filial">-</span>
                        </p>

                    </div>

                    <div class="col-md-12">

                        <hr>

                        <p>
                            <strong>Descrição:</strong>
                            <span id="detalhe_fluxo_descricao">-</span>
                        </p>

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-dismiss="modal">

                    Fechar

                </button>

            </div>

        </div>

    </div>

</div>