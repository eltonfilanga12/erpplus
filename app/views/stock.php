<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Stock Atual
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Stock Atual
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="btnAtualizarStock">

                        <i class="fa fa-sync"></i>
                        Atualizar

                    </button>

                </div>

            </div>

        </div>


        <!-- RESUMO -->
        <div class="row">

            <div class="col-md-3 col-sm-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <span class="text-muted">
                                    Produtos em Stock
                                </span>

                                <h3
                                    class="mb-0 mt-2"
                                    id="stockResumoProdutos">

                                    0

                                </h3>

                            </div>

                            <!-- Produtos em Stock -->
                            <div class="text-success">
                                <i class="fa fa-cubes fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-md-3 col-sm-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <span class="text-muted">
                                    Stock Baixo
                                </span>

                                <h3
                                    class="mb-0 mt-2"
                                    id="stockResumoBaixo">

                                    0

                                </h3>

                            </div>
                            <!-- Stock Baixo -->
                            <div class="text-warning">
                                <i class="fa fa-exclamation-triangle fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-md-3 col-sm-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <span class="text-muted">
                                    Sem Stock
                                </span>

                                <h3
                                    class="mb-0 mt-2"
                                    id="stockResumoSemStock">

                                    0

                                </h3>

                            </div>

                            <!-- Sem Stock -->
                            <div class="text-danger">
                                <i class="fa fa-times-circle fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-md-3 col-sm-6">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <span class="text-muted">
                                    Valor do Stock
                                </span>

                                <h5
                                    class="mb-0 mt-2"
                                    id="stockResumoValor">

                                    0,00 MT

                                </h5>

                            </div>

                            <!-- Valor do Stock -->
                            <div class="text-primary">
                                <i class="fa fa-money fa-2x"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- FILTROS -->
        <div class="card">

            <div class="card-header">

                <h5 class="card-title mb-0">

                    <i class="fa fa-filter"></i>
                    Filtros

                </h5>

            </div>

            <div class="card-body">

                <div class="form-row">

                    <div class="form-group col-md-4">

                        <label for="stockFiltroArmazem">
                            Armazém
                        </label>

                        <select
                            class="form-control"
                            id="stockFiltroArmazem">

                            <option value="">
                                Todos os armazéns
                            </option>

                        </select>

                    </div>


                    <div class="form-group col-md-4">

                        <label for="stockFiltroCategoria">
                            Categoria
                        </label>

                        <select
                            class="form-control"
                            id="stockFiltroCategoria">

                            <option value="">
                                Todas as categorias
                            </option>

                        </select>

                    </div>


                    <div class="form-group col-md-4">

                        <label for="stockFiltroSituacao">
                            Situação
                        </label>

                        <select
                            class="form-control"
                            id="stockFiltroSituacao">

                            <option value="">
                                Todas
                            </option>

                            <option value="NORMAL">
                                Normal
                            </option>

                            <option value="STOCK_BAIXO">
                                Stock Baixo
                            </option>

                            <option value="SEM_STOCK">
                                Sem Stock
                            </option>

                        </select>

                    </div>

                </div>

            </div>

        </div>


        <!-- TABELA -->
        <div class="card">

            <div class="card-header">

                <h5 class="card-title mb-0">
                    Posição Atual do Stock
                </h5>

            </div>

            <div class="card-body">

                <div
                    id="stockErro"
                    class="alert alert-danger d-none">
                </div>

                <div class="table-responsive">

                    <table
                        id="stockTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>Produto</th>
                                <th>SKU</th>
                                <th>Categoria</th>
                                <th>Marca</th>
                                <th>Armazém</th>
                                <th>Unidade</th>
                                <th>Stock Atual</th>
                                <th>Stock Mínimo</th>
                                <th>Custo Médio</th>
                                <th>Valor Stock</th>
                                <th>Situação</th>
                                <th>Atualizado em</th>

                            </tr>

                        </thead>

                        <tbody id="stockTabelaBody">

                            

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>