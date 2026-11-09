<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Relatórios Contabilísticos
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
                            Contabilísticos
                        </li>

                    </ul>
                </div>


                <div class="col-auto">

                    <div class="btn-group">

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            id="btnImprimirRelatorioContabilistico">

                            <i class="fa fa-print"></i>

                            <span class="d-none d-sm-inline">
                                Imprimir
                            </span>

                        </button>


                        <button
                            type="button"
                            class="btn btn-outline-success"
                            id="btnExportarRelatorioContabilistico">

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
                        id="relatorioContabilisticoTabs">

                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link active"
                                data-relatorio="DIARIO">

                                <i class="fa fa-book mr-1"></i>

                                Diário

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="RAZAO">

                                <i class="fa fa-list-alt mr-1"></i>

                                Razão

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="BALANCETE">

                                <i class="fa fa-balance-scale mr-1"></i>

                                Balancete

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="DRE">

                                <i class="fa fa-line-chart mr-1"></i>

                                DRE

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="BALANCO_PATRIMONIAL">

                                <i class="fa fa-university mr-1"></i>

                                Balanço Patrimonial

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="IVA">

                                <i class="fa fa-percent mr-1"></i>

                                IVA

                            </a>

                        </li>

                    </ul>

                </div>


                <!-- Mobile -->
                <div class="d-lg-none mb-3">

                    <label
                        for="tipoRelatorioContabilisticoMobile">

                        Tipo de relatório

                    </label>


                    <select
                        id="tipoRelatorioContabilisticoMobile"
                        class="form-control">

                        <option value="DIARIO">
                            Diário
                        </option>

                        <option value="RAZAO">
                            Razão
                        </option>

                        <option value="BALANCETE">
                            Balancete
                        </option>

                        <option value="DRE">
                            DRE
                        </option>

                        <option value="BALANCO_PATRIMONIAL">
                            Balanço Patrimonial
                        </option>

                        <option value="IVA">
                            IVA
                        </option>

                    </select>

                </div>

            </div>
        </div>


        <!-- Filtros -->
        <div class="card">
            <div class="card-body">

                <form id="formFiltrosRelatorioContabilistico">

                    <input
                        type="hidden"
                        id="tipoRelatorioContabilistico"
                        value="DIARIO">


                    <div class="row">


                        <!-- Data inicial -->
                        <div
                            class="col-md-3"
                            id="grupoRelatorioContabilisticoDataInicio">

                            <div class="form-group">

                                <label
                                    for="relatorioContabilisticoDataInicio">

                                    Data inicial

                                </label>


                                <input
                                    type="date"
                                    class="form-control"
                                    id="relatorioContabilisticoDataInicio">

                            </div>

                        </div>


                        <!-- Data final -->
                        <div
                            class="col-md-3"
                            id="grupoRelatorioContabilisticoDataFim">

                            <div class="form-group">

                                <label
                                    for="relatorioContabilisticoDataFim">

                                    Data final

                                </label>


                                <input
                                    type="date"
                                    class="form-control"
                                    id="relatorioContabilisticoDataFim">

                            </div>

                        </div>


                        <!-- Período -->
                        <div
                            class="col-md-3"
                            id="grupoRelatorioContabilisticoPeriodo">

                            <div class="form-group">

                                <label
                                    for="relatorioContabilisticoPeriodo">

                                    Período contabilístico

                                </label>


                                <select
                                    class="form-control"
                                    id="relatorioContabilisticoPeriodo">

                                    <option value="">
                                        Todos os períodos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Diário -->
                        <div
                            class="col-md-3"
                            id="grupoRelatorioContabilisticoDiario">

                            <div class="form-group">

                                <label
                                    for="relatorioContabilisticoDiario">

                                    Diário

                                </label>


                                <select
                                    class="form-control"
                                    id="relatorioContabilisticoDiario">

                                    <option value="">
                                        Todos os diários
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Conta -->
                        <div
                            class="col-md-4"
                            id="grupoRelatorioContabilisticoConta">

                            <div class="form-group">

                                <label
                                    for="relatorioContabilisticoConta">

                                    Conta contabilística

                                </label>


                                <select
                                    class="form-control"
                                    id="relatorioContabilisticoConta">

                                    <option value="">
                                        Todas as contas
                                    </option>

                                </select>

                                <small
                                    class="form-text text-muted d-none"
                                    id="relatorioContabilisticoContaObrigatoria">

                                    No Razão é obrigatório selecionar uma conta.

                                </small>

                            </div>

                        </div>


                        <!-- Filial -->
                        <div
                            class="col-md-3"
                            id="grupoRelatorioContabilisticoFilial">

                            <div class="form-group">

                                <label
                                    for="relatorioContabilisticoFilial">

                                    Filial

                                </label>


                                <select
                                    class="form-control"
                                    id="relatorioContabilisticoFilial">

                                    <option value="">
                                        Todas as filiais
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Pesquisa -->
                        <div
                            class="col-md-5"
                            id="grupoRelatorioContabilisticoPesquisa">

                            <div class="form-group">

                                <label
                                    for="relatorioContabilisticoPesquisa">

                                    Pesquisar

                                </label>


                                <input
                                    type="text"
                                    class="form-control"
                                    id="relatorioContabilisticoPesquisa"
                                    placeholder="Documento, referência, descrição, conta...">

                            </div>

                        </div>


                        <!-- Botões -->
                        <div class="col-md-4">

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
                                        id="btnLimparFiltrosRelatorioContabilistico">

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
            id="relatorioContabilisticoResumo">


            <div class="col-md-3">

                <div class="card">
                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="relatorioContabilisticoResumoTitulo1">

                            Lançamentos

                        </span>

                        <h3
                            id="relatorioContabilisticoResumoValor1">

                            0

                        </h3>

                    </div>
                </div>

            </div>


            <div class="col-md-3">

                <div class="card">
                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="relatorioContabilisticoResumoTitulo2">

                            Débitos

                        </span>

                        <h3
                            id="relatorioContabilisticoResumoValor2">

                            0,00 MTn

                        </h3>

                    </div>
                </div>

            </div>


            <div class="col-md-3">

                <div class="card">
                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="relatorioContabilisticoResumoTitulo3">

                            Créditos

                        </span>

                        <h3
                            id="relatorioContabilisticoResumoValor3">

                            0,00 MTn

                        </h3>

                    </div>
                </div>

            </div>


            <div class="col-md-3">

                <div class="card">
                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="relatorioContabilisticoResumoTitulo4">

                            Diferença

                        </span>

                        <h3
                            id="relatorioContabilisticoResumoValor4">

                            0,00 MTn

                        </h3>

                    </div>
                </div>

            </div>


        </div>


        <!-- Área especial do Razão -->
        <div
            id="relatorioContabilisticoRazaoConta"
            class="card d-none">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">

                        <small class="text-muted">
                            Código
                        </small>

                        <h5
                            class="mb-0"
                            id="relatorioContabilisticoRazaoCodigo">

                            -

                        </h5>

                    </div>


                    <div class="col-md-5">

                        <small class="text-muted">
                            Conta
                        </small>

                        <h5
                            class="mb-0"
                            id="relatorioContabilisticoRazaoNome">

                            -

                        </h5>

                    </div>


                    <div class="col-md-2">

                        <small class="text-muted">
                            Natureza
                        </small>

                        <h5
                            class="mb-0"
                            id="relatorioContabilisticoRazaoNatureza">

                            -

                        </h5>

                    </div>


                    <div class="col-md-2">

                        <small class="text-muted">
                            Grupo
                        </small>

                        <h5
                            class="mb-0"
                            id="relatorioContabilisticoRazaoGrupo">

                            -

                        </h5>

                    </div>

                </div>

            </div>

        </div>


        <!-- Resultado -->
        <div class="card">

            <div class="card-header">

                <div class="row align-items-center">

                    <div class="col">

                        <h4
                            class="card-title mb-0"
                            id="relatorioContabilisticoTitulo">

                            Relatório Diário

                        </h4>

                    </div>


                    <div class="col-auto">

                        <span
                            class="badge badge-light"
                            id="relatorioContabilisticoQuantidade">

                            0 registos

                        </span>

                    </div>

                </div>

            </div>


            <div class="card-body">


                <!-- Erro -->
                <div
                    id="relatorioContabilisticoErro"
                    class="alert alert-danger d-none">
                </div>


                <!-- Tabela comum -->
                <div
                    class="table-responsive"
                    id="relatorioContabilisticoTabelaContainer">

                    <table
                        id="relatorioContabilisticoTable"
                        class="table table-striped custom-table mb-0">

                        <thead
                            id="relatorioContabilisticoTableHead">

                            <tr>
                                <th>
                                    A carregar...
                                </th>
                            </tr>

                        </thead>


                        <tbody
                            id="relatorioContabilisticoTableBody">

                           

                        </tbody>

                    </table>

                </div>


                <!-- DRE / Balanço -->
                <div
                    id="relatorioContabilisticoEstruturaContainer"
                    class="d-none">

                    <div
                        id="relatorioContabilisticoEstrutura">
                    </div>

                </div>


            </div>

        </div>


    </div>
</div>