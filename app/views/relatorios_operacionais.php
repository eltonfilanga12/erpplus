<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Relatórios Operacionais
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
                            Operacionais
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <div class="btn-group">

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            id="btnImprimirRelatorioOperacional">

                            <i class="fa fa-print"></i>

                            <span class="d-none d-sm-inline">
                                Imprimir
                            </span>
                        </button>

                        <button
                            type="button"
                            class="btn btn-outline-success"
                            id="btnExportarRelatorioOperacional">

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

                <div class="d-none d-md-block">

                    <ul
                        class="nav nav-tabs"
                        id="relatorioOperacionalTabs"
                        role="tablist">

                        <li class="nav-item">
                            <a
                                class="nav-link active"
                                href="#"
                                data-relatorio="VENDAS">

                                <i class="fa fa-shopping-cart mr-1"></i>
                                Vendas
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                class="nav-link"
                                href="#"
                                data-relatorio="COMPRAS">

                                <i class="fa fa-truck mr-1"></i>
                                Compras
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                class="nav-link"
                                href="#"
                                data-relatorio="STOCK">

                                <i class="fa fa-cubes mr-1"></i>
                                Stock
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                class="nav-link"
                                href="#"
                                data-relatorio="CLIENTES">

                                <i class="fa fa-users mr-1"></i>
                                Clientes
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                class="nav-link"
                                href="#"
                                data-relatorio="FORNECEDORES">

                                <i class="fa fa-industry mr-1"></i>
                                Fornecedores
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                class="nav-link"
                                href="#"
                                data-relatorio="PRODUTOS">

                                <i class="fa fa-tags mr-1"></i>
                                Produtos
                            </a>
                        </li>

                    </ul>

                </div>


                <!-- Select Mobile -->
                <div class="d-md-none mb-3">

                    <label for="tipoRelatorioOperacionalMobile">
                        Tipo de relatório
                    </label>

                    <select
                        id="tipoRelatorioOperacionalMobile"
                        class="form-control">

                        <option value="VENDAS">
                            Vendas
                        </option>

                        <option value="COMPRAS">
                            Compras
                        </option>

                        <option value="STOCK">
                            Stock
                        </option>

                        <option value="CLIENTES">
                            Clientes
                        </option>

                        <option value="FORNECEDORES">
                            Fornecedores
                        </option>

                        <option value="PRODUTOS">
                            Produtos
                        </option>

                    </select>

                </div>

            </div>
        </div>


        <!-- Filtros -->
        <div class="card">

            <div class="card-body">

                <form id="formFiltrosRelatorioOperacional">

                    <input
                        type="hidden"
                        id="tipoRelatorioOperacional"
                        name="tipo_relatorio"
                        value="VENDAS">

                    <div class="row">

                        <div class="col-md-3">
                            <div class="form-group">

                                <label for="relatorioOperacionalDataInicio">
                                    Data inicial
                                </label>

                                <input
                                    type="date"
                                    id="relatorioOperacionalDataInicio"
                                    name="data_inicio"
                                    class="form-control">

                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="form-group">

                                <label for="relatorioOperacionalDataFim">
                                    Data final
                                </label>

                                <input
                                    type="date"
                                    id="relatorioOperacionalDataFim"
                                    name="data_fim"
                                    class="form-control">

                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="form-group">

                                <label for="relatorioOperacionalFilial">
                                    Filial
                                </label>

                                <select
                                    id="relatorioOperacionalFilial"
                                    name="filial_id"
                                    class="form-control">

                                    <option value="">
                                        Todas as filiais
                                    </option>

                                </select>

                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="form-group">

                                <label
                                    for="relatorioOperacionalEstado"
                                    id="relatorioOperacionalEstadoLabel">
                                    Estado
                                </label>

                                <select
                                    id="relatorioOperacionalEstado"
                                    name="estado"
                                    class="form-control">

                                    <option value="">
                                        Todos
                                    </option>

                                </select>

                            </div>
                        </div>


                        <div class="col-md-8">
                            <div class="form-group mb-md-0">

                                <label for="relatorioOperacionalPesquisa">
                                    Pesquisar
                                </label>

                                <input
                                    type="text"
                                    id="relatorioOperacionalPesquisa"
                                    name="pesquisa"
                                    class="form-control"
                                    placeholder="Pesquisar no relatório...">

                            </div>
                        </div>


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
                                        id="btnLimparFiltrosRelatorioOperacional">

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
            id="relatorioOperacionalResumo">

            <div class="col-md-3">

                <div class="card">

                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="relatorioOperacionalResumoTitulo1">
                            Total
                        </span>

                        <h3
                            id="relatorioOperacionalResumoValor1">
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
                            id="relatorioOperacionalResumoTitulo2">
                            Valor
                        </span>

                        <h3
                            id="relatorioOperacionalResumoValor2">
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
                            id="relatorioOperacionalResumoTitulo3">
                            Indicador
                        </span>

                        <h3
                            id="relatorioOperacionalResumoValor3">
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
                            id="relatorioOperacionalResumoTitulo4">
                            Resultado
                        </span>

                        <h3
                            id="relatorioOperacionalResumoValor4">
                            0
                        </h3>

                    </div>

                </div>

            </div>

        </div>


        <!-- Tabela -->
        <div class="card">

            <div class="card-header">

                <div class="row align-items-center">

                    <div class="col">

                        <h4
                            class="card-title mb-0"
                            id="relatorioOperacionalTitulo">

                            Relatório de Vendas

                        </h4>

                    </div>

                    <div class="col-auto">

                        <span
                            class="badge badge-light"
                            id="relatorioOperacionalQuantidade">

                            0 registos

                        </span>

                    </div>

                </div>

            </div>


            <div class="card-body">

                <div
                    id="relatorioOperacionalErro"
                    class="alert alert-danger d-none">
                </div>


                <div class="table-responsive">

                    <table
                        id="relatorioOperacionalTable"
                        class="table table-striped custom-table mb-0">

                        <thead id="relatorioOperacionalTableHead">

                            <tr>
                                <th>
                                    A carregar...
                                </th>
                            </tr>

                        </thead>


                        <tbody id="relatorioOperacionalTableBody">

                        

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>
</div>