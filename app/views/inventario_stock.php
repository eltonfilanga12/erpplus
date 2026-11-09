<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Inventário de Stock
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
                            Inventário
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoInventario">
                        <i class="fa fa-plus"></i>
                        Novo Inventário
                    </button>

                </div>

            </div>

        </div>


        <!-- Cards -->
        <div class="row">

            <div class="col-md-4">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <span class="text-muted">
                                    Inventários Abertos
                                </span>

                                <h3 id="resumoInventariosAbertos">
                                    0
                                </h3>

                            </div>

                            <div class="text-warning">

                                <i class="fa fa-folder-open fa-2x"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <span class="text-muted">
                                    Finalizados
                                </span>

                                <h3 id="resumoInventariosFinalizados">
                                    0
                                </h3>

                            </div>

                            <div class="text-success">

                                <i class="fa fa-check-circle fa-2x"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <span class="text-muted">
                                    Cancelados
                                </span>

                                <h3 id="resumoInventariosCancelados">
                                    0
                                </h3>

                            </div>

                            <div class="text-danger">

                                <i class="fa fa-times-circle fa-2x"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Tabela -->
        <div class="card">

            <div class="card-header">

                <h5 class="card-title mb-0">
                    Histórico de Inventários
                </h5>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped custom-table mb-0"
                        id="inventariosTable">

                        <thead>

                            <tr>
                                <th>Código</th>
                                <th>Armazém</th>
                                <th>Abertura</th>
                                <th>Produtos</th>
                                <th>Contados</th>
                                <th>Diferenças</th>
                                <th>Estado</th>
                                <th>Utilizador</th>
                                <th class="text-right">
                                    Ações
                                </th>
                            </tr>

                        </thead>

                        <tbody id="inventariosBody"></tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- MODAL NOVO INVENTÁRIO -->
<div
    class="modal fade"
    id="modalNovoInventario"
    tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <form id="formNovoInventario">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Novo Inventário
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">
                        &times;
                    </button>

                </div>


                <div class="modal-body">

                    <div class="form-group">

                        <label>
                            Armazém
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="inventarioArmazemId"
                            name="armazem_id"
                            required>
                            <option value="">
                                Selecione
                            </option>
                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Data de abertura
                        </label>

                        <input
                            type="datetime-local"
                            class="form-control"
                            id="inventarioDataAbertura"
                            name="data_abertura"
                            required>

                    </div>


                    <div class="form-group">

                        <label>
                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            name="descricao"
                            rows="3"
                            placeholder="Ex.: Inventário mensal..."></textarea>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-dismiss="modal">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">
                        <i class="fa fa-folder-open"></i>
                        Abrir Inventário
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- MODAL CONTAGEM -->
<div
    class="modal fade"
    id="modalContagemInventario"
    tabindex="-1">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Contagem de Inventário
                </h5>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">
                    &times;
                </button>

            </div>


            <div class="modal-body">

                <input
                    type="hidden"
                    id="inventarioContagemId">


                <div class="row mb-3">

                    <div class="col-md-4">

                        <strong>Código:</strong>

                        <div id="contagemCodigo">
                            -
                        </div>

                    </div>

                    <div class="col-md-4">

                        <strong>Armazém:</strong>

                        <div id="contagemArmazem">
                            -
                        </div>

                    </div>

                    <div class="col-md-4">

                        <strong>Estado:</strong>

                        <div id="contagemStatus">
                            -
                        </div>

                    </div>

                </div>


                <div class="table-responsive">

                    <table class="table table-bordered">

                        <thead>

                            <tr>
                                <th>Produto</th>
                                <th>SKU</th>
                                <th>Unidade</th>
                                <th>Stock Sistema</th>
                                <th>Stock Físico</th>
                                <th>Diferença</th>
                                <th>Observação</th>
                            </tr>

                        </thead>

                        <tbody id="contagemInventarioBody"></tbody>

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

                <button
                    type="button"
                    class="btn btn-info"
                    id="btnGuardarContagem">
                    <i class="fa fa-save"></i>
                    Guardar Contagem
                </button>

                <button
                    type="button"
                    class="btn btn-success"
                    id="btnFinalizarInventario">
                    <i class="fa fa-check"></i>
                    Finalizar Inventário
                </button>

                <button
                    type="button"
                    class="btn btn-danger"
                    id="btnCancelarInventario">
                    <i class="fa fa-times"></i>
                    Cancelar Inventário
                </button>

            </div>

        </div>

    </div>

</div>