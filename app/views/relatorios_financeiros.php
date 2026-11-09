<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Relatórios Financeiros
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item">
                            Relatórios
                        </li>

                        <li class="breadcrumb-item active">
                            Financeiros
                        </li>

                    </ul>
                </div>


                <div class="col-auto">

                    <div class="btn-group">

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            id="btnImprimirRelatorioFinanceiro">

                            <i class="fa fa-print"></i>

                            <span class="d-none d-sm-inline">
                                Imprimir
                            </span>

                        </button>


                        <button
                            type="button"
                            class="btn btn-outline-success"
                            id="btnExportarRelatorioFinanceiro">

                            <i class="fa fa-file-excel-o"></i>

                            <span class="d-none d-sm-inline">
                                Exportar
                            </span>

                        </button>

                    </div>

                </div>

            </div>
        </div>


        <!-- Abas -->
        <div class="card">
            <div class="card-body pb-0">

                <!-- Desktop -->
                <div class="d-none d-lg-block">

                    <ul
                        class="nav nav-tabs"
                        id="relatorioFinanceiroTabs"
                        role="tablist">

                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link active"
                                data-relatorio="FLUXO_CAIXA">

                                <i class="fa fa-exchange mr-1"></i>

                                Fluxo de Caixa

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="RECEITAS_DESPESAS">

                                <i class="fa fa-line-chart mr-1"></i>

                                Receitas e Despesas

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="CONTAS_RECEBER">

                                <i class="fa fa-arrow-circle-down mr-1"></i>

                                Contas a Receber

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="CONTAS_PAGAR">

                                <i class="fa fa-arrow-circle-up mr-1"></i>

                                Contas a Pagar

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="CENTROS_CUSTO">

                                <i class="fa fa-sitemap mr-1"></i>

                                Centros de Custo

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="MOVIMENTACAO_BANCARIA">

                                <i class="fa fa-bank mr-1"></i>

                                Movimentação Bancária

                            </a>

                        </li>

                    </ul>

                </div>


                <!-- Mobile -->
                <div class="d-lg-none mb-3">

                    <label
                        for="tipoRelatorioFinanceiroMobile">

                        Tipo de relatório

                    </label>


                    <select
                        id="tipoRelatorioFinanceiroMobile"
                        class="form-control">

                        <option value="FLUXO_CAIXA">
                            Fluxo de Caixa
                        </option>

                        <option value="RECEITAS_DESPESAS">
                            Receitas e Despesas
                        </option>

                        <option value="CONTAS_RECEBER">
                            Contas a Receber
                        </option>

                        <option value="CONTAS_PAGAR">
                            Contas a Pagar
                        </option>

                        <option value="CENTROS_CUSTO">
                            Centros de Custo
                        </option>

                        <option value="MOVIMENTACAO_BANCARIA">
                            Movimentação Bancária
                        </option>

                    </select>

                </div>

            </div>
        </div>


        <!-- Filtros -->
        <div class="card">
            <div class="card-body">

                <form id="formFiltrosRelatorioFinanceiro">

                    <input
                        type="hidden"
                        id="tipoRelatorioFinanceiro"
                        name="tipo_relatorio"
                        value="FLUXO_CAIXA">


                    <div class="row">


                        <!-- Data Inicial -->
                        <div class="col-md-3">

                            <div class="form-group">

                                <label
                                    for="relatorioFinanceiroDataInicio">

                                    Data inicial

                                </label>


                                <input
                                    type="date"
                                    class="form-control"
                                    id="relatorioFinanceiroDataInicio"
                                    name="data_inicio">

                            </div>

                        </div>


                        <!-- Data Final -->
                        <div class="col-md-3">

                            <div class="form-group">

                                <label
                                    for="relatorioFinanceiroDataFim">

                                    Data final

                                </label>


                                <input
                                    type="date"
                                    class="form-control"
                                    id="relatorioFinanceiroDataFim"
                                    name="data_fim">

                            </div>

                        </div>


                        <!-- Filial -->
                        <div class="col-md-3">

                            <div class="form-group">

                                <label
                                    for="relatorioFinanceiroFilial">

                                    Filial

                                </label>


                                <select
                                    class="form-control"
                                    id="relatorioFinanceiroFilial"
                                    name="filial_id">

                                    <option value="">
                                        Todas as filiais
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Estado -->
                        <div
                            class="col-md-3"
                            id="grupoRelatorioFinanceiroEstado">

                            <div class="form-group">

                                <label
                                    for="relatorioFinanceiroEstado">

                                    Estado

                                </label>


                                <select
                                    class="form-control"
                                    id="relatorioFinanceiroEstado"
                                    name="estado">

                                    <option value="">
                                        Todos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Banco -->
                        <div
                            class="col-md-3 d-none"
                            id="grupoRelatorioFinanceiroBanco">

                            <div class="form-group">

                                <label
                                    for="relatorioFinanceiroBanco">

                                    Conta bancária

                                </label>


                                <select
                                    class="form-control"
                                    id="relatorioFinanceiroBanco"
                                    name="banco_id">

                                    <option value="">
                                        Todas as contas
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Centro de Custo -->
                        <div
                            class="col-md-3 d-none"
                            id="grupoRelatorioFinanceiroCentroCusto">

                            <div class="form-group">

                                <label
                                    for="relatorioFinanceiroCentroCusto">

                                    Centro de custo

                                </label>


                                <select
                                    class="form-control"
                                    id="relatorioFinanceiroCentroCusto"
                                    name="centro_custo_id">

                                    <option value="">
                                        Todos os centros
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Tipo Movimento -->
                        <div
                            class="col-md-3 d-none"
                            id="grupoRelatorioFinanceiroTipoMovimento">

                            <div class="form-group">

                                <label
                                    for="relatorioFinanceiroTipoMovimento">

                                    Tipo de movimento

                                </label>


                                <select
                                    class="form-control"
                                    id="relatorioFinanceiroTipoMovimento"
                                    name="tipo_movimento">

                                    <option value="">
                                        Todos
                                    </option>

                                    <option value="ENTRADA">
                                        Entrada
                                    </option>

                                    <option value="SAIDA">
                                        Saída
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Pesquisa -->
                        <div class="col-md-6">

                            <div class="form-group mb-md-0">

                                <label
                                    for="relatorioFinanceiroPesquisa">

                                    Pesquisar

                                </label>


                                <input
                                    type="text"
                                    class="form-control"
                                    id="relatorioFinanceiroPesquisa"
                                    name="pesquisa"
                                    placeholder="Pesquisar no relatório...">

                            </div>

                        </div>


                        <!-- Botões -->
                        <div class="col-md-6">

                            <div class="form-group mb-0">

                                <label>&nbsp;</label>


                                <div class="d-flex">

                                    <button
                                        type="submit"
                                        class="btn btn-primary mr-2 flex-fill">

                                        <i class="fa fa-search"></i>

                                        Aplicar filtros

                                    </button>


                                    <button
                                        type="button"
                                        class="btn btn-light"
                                        id="btnLimparFiltrosRelatorioFinanceiro">

                                        <i class="fa fa-refresh"></i>

                                    </button>

                                </div>

                            </div>

                        </div>


                    </div>

                </form>

            </div>
        </div>


        <!-- Resumo -->
        <div
            class="row"
            id="relatorioFinanceiroResumo">


            <!-- Card 1 -->
            <div class="col-md-3">

                <div class="card">

                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="relatorioFinanceiroResumoTitulo1">

                            Entradas

                        </span>


                        <h3
                            id="relatorioFinanceiroResumoValor1">

                            0,00 MTn

                        </h3>

                    </div>

                </div>

            </div>


            <!-- Card 2 -->
            <div class="col-md-3">

                <div class="card">

                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="relatorioFinanceiroResumoTitulo2">

                            Saídas

                        </span>


                        <h3
                            id="relatorioFinanceiroResumoValor2">

                            0,00 MTn

                        </h3>

                    </div>

                </div>

            </div>


            <!-- Card 3 -->
            <div class="col-md-3">

                <div class="card">

                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="relatorioFinanceiroResumoTitulo3">

                            Saldo

                        </span>


                        <h3
                            id="relatorioFinanceiroResumoValor3">

                            0,00 MTn

                        </h3>

                    </div>

                </div>

            </div>


            <!-- Card 4 -->
            <div class="col-md-3">

                <div class="card">

                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="relatorioFinanceiroResumoTitulo4">

                            Movimentos

                        </span>


                        <h3
                            id="relatorioFinanceiroResumoValor4">

                            0

                        </h3>

                    </div>

                </div>

            </div>


        </div>


        <!-- Relatório -->
        <div class="card">

            <div class="card-header">

                <div class="row align-items-center">


                    <div class="col">

                        <h4
                            class="card-title mb-0"
                            id="relatorioFinanceiroTitulo">

                            Relatório de Fluxo de Caixa

                        </h4>

                    </div>


                    <div class="col-auto">

                        <span
                            class="badge badge-light"
                            id="relatorioFinanceiroQuantidade">

                            0 registos

                        </span>

                    </div>


                </div>

            </div>


            <div class="card-body">


                <!-- Erro -->
                <div
                    id="relatorioFinanceiroErro"
                    class="alert alert-danger d-none">
                </div>


                <!-- Tabela -->
                <div class="table-responsive">

                    <table
                        id="relatorioFinanceiroTable"
                        class="table table-striped custom-table mb-0">


                        <thead
                            id="relatorioFinanceiroTableHead">

                            <tr>

                                <th>
                                    A carregar...
                                </th>

                            </tr>

                        </thead>


                        <tbody
                            id="relatorioFinanceiroTableBody">

                           

                        </tbody>


                    </table>

                </div>


            </div>

        </div>


    </div>
</div>