<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Movimentos de Stock
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item">
                            Stock
                        </li>

                        <li class="breadcrumb-item active">
                            Movimentos de Stock
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="btnAtualizarMovimentosStock">
                        <i class="fa fa-refresh"></i>
                        Atualizar
                    </button>

                </div>

            </div>

        </div>


        <!-- Resumo -->
        <div class="row">

            <div class="col-md-3 col-sm-6">

                <div class="card">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <span class="text-muted">
                                    Entradas
                                </span>

                                <h3
                                    class="mb-0 mt-2"
                                    id="resumoEntradas">
                                    0
                                </h3>
                            </div>

                            <div class="text-success">
                                <i class="fa fa-sign-in fa-2x"></i>
                            </div>

                        </div>

                    </div>
                </div>

            </div>


            <div class="col-md-3 col-sm-6">

                <div class="card">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <span class="text-muted">
                                    Saídas
                                </span>

                                <h3
                                    class="mb-0 mt-2"
                                    id="resumoSaidas">
                                    0
                                </h3>
                            </div>

                            <div class="text-danger">
                                <i class="fa fa-sign-out fa-2x"></i>
                            </div>

                        </div>

                    </div>
                </div>

            </div>


            <div class="col-md-3 col-sm-6">

                <div class="card">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <span class="text-muted">
                                    Transferências
                                </span>

                                <h3
                                    class="mb-0 mt-2"
                                    id="resumoTransferencias">
                                    0
                                </h3>
                            </div>

                            <div class="text-info">
                                <i class="fa fa-exchange fa-2x"></i>
                            </div>

                        </div>

                    </div>
                </div>

            </div>


            <div class="col-md-3 col-sm-6">

                <div class="card">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <span class="text-muted">
                                    Ajustes
                                </span>

                                <h3
                                    class="mb-0 mt-2"
                                    id="resumoAjustes">
                                    0
                                </h3>
                            </div>

                            <div class="text-warning">
                                <i class="fa fa-wrench fa-2x"></i>
                            </div>

                        </div>

                    </div>
                </div>

            </div>

        </div>


        <!-- Filtros -->
        <div class="card">

            <div class="card-header">

                <h5 class="card-title mb-0">
                    <i class="fa fa-filter"></i>
                    Filtros
                </h5>

            </div>

            <div class="card-body">

                <div class="form-row">

                    <div class="form-group col-md-3">

                        <label for="filtroTipoMovimento">
                            Tipo
                        </label>

                        <select
                            class="form-control"
                            id="filtroTipoMovimento">
                            <option value="">
                                Todos
                            </option>

                            <option value="ENTRADA">
                                Entrada
                            </option>

                            <option value="SAIDA">
                                Saída
                            </option>

                            <option value="TRANSFERENCIA">
                                Transferência
                            </option>

                            <option value="AJUSTE">
                                Ajuste
                            </option>
                        </select>

                    </div>


                    <div class="form-group col-md-3">

                        <label for="filtroArmazemMovimento">
                            Armazém
                        </label>

                        <select
                            class="form-control"
                            id="filtroArmazemMovimento">
                            <option value="">
                                Todos
                            </option>
                        </select>

                    </div>


                    <div class="form-group col-md-3">

                        <label for="filtroDataInicioMovimento">
                            Data Inicial
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="filtroDataInicioMovimento">

                    </div>


                    <div class="form-group col-md-3">

                        <label for="filtroDataFimMovimento">
                            Data Final
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="filtroDataFimMovimento">

                    </div>

                </div>

            </div>

        </div>


        <!-- Tabela -->
        <div class="card">

            <div class="card-header">

                <h5 class="card-title mb-0">
                    Histórico de Movimentos
                </h5>

            </div>

            <div class="card-body">

                <div
                    class="alert alert-danger d-none"
                    id="erroMovimentosStock"></div>

                <div class="table-responsive">

                    <table
                        id="movimentosStockTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Origem</th>
                                <th>Destino</th>
                                <th>Itens</th>
                                <th>Quantidade Total</th>
                                <th>Valor Total</th>
                                <th>Utilizador</th>
                                <th>Status</th>
                                <th>Observações</th>
                                <th class="text-right">
                                    Ações
                                </th>
                            </tr>

                        </thead>

                        <tbody id="movimentosStockBody">

                           

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- Modal Detalhes -->
<div
    class="modal fade"
    id="modalDetalhesMovimentoStock"
    tabindex="-1">

    <div class="modal-dialog modal-xl">

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

                <div class="row mb-3">

                    <div class="col-md-3">
                        <strong>Movimento:</strong>
                        <div id="detalheMovimentoId">
                            -
                        </div>
                    </div>

                    <div class="col-md-3">
                        <strong>Tipo:</strong>
                        <div id="detalheMovimentoTipo">
                            -
                        </div>
                    </div>

                    <div class="col-md-3">
                        <strong>Data:</strong>
                        <div id="detalheMovimentoData">
                            -
                        </div>
                    </div>

                    <div class="col-md-3">
                        <strong>Utilizador:</strong>
                        <div id="detalheMovimentoUsuario">
                            -
                        </div>
                    </div>

                </div>


                <div class="row mb-3">

                    <div class="col-md-4">
                        <strong>Armazém Origem:</strong>
                        <div id="detalheMovimentoOrigem">
                            -
                        </div>
                    </div>

                    <div class="col-md-4">
                        <strong>Armazém Destino:</strong>
                        <div id="detalheMovimentoDestino">
                            -
                        </div>
                    </div>

                    <div class="col-md-4">
                        <strong>Status:</strong>
                        <div id="detalheMovimentoStatus">
                            -
                        </div>
                    </div>

                </div>


                <div class="form-group">
                    <strong>Observações:</strong>

                    <div
                        class="border rounded p-2 mt-1"
                        id="detalheMovimentoObservacoes">
                        -
                    </div>
                </div>


                <div class="table-responsive mt-4">

                    <table class="table table-bordered">

                        <thead>

                            <tr>
                                <th>Produto</th>
                                <th>SKU</th>
                                <th>Unidade</th>
                                <th>Quantidade</th>
                                <th>Custo Unitário</th>
                                <th>Subtotal</th>
                            </tr>

                        </thead>

                        <tbody id="detalheMovimentoItens">

                   

                        </tbody>

                    </table>

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