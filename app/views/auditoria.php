<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">


        <!-- =========================================
             PAGE HEADER
        ========================================== -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Auditoria
                    </h3>


                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>


                        <li class="breadcrumb-item active">
                            Auditoria
                        </li>

                    </ul>

                </div>


                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="auditoriaBtnAtualizar">

                        <i class="fa fa-refresh"></i>

                        <span class="d-none d-sm-inline">
                            Atualizar
                        </span>

                    </button>

                </div>

            </div>

        </div>


        <!-- =========================================
             FILTROS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Filtros
                </h4>

            </div>


            <div class="card-body">

                <form
                    id="auditoriaFormFiltros"
                    autocomplete="off">

                    <div class="row">


                        <!-- Data inicial -->

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="auditoriaDataInicial">
                                    Data inicial
                                </label>


                                <input
                                    type="date"
                                    class="form-control"
                                    id="auditoriaDataInicial"
                                    name="data_inicial">

                            </div>

                        </div>


                        <!-- Data final -->

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="auditoriaDataFinal">
                                    Data final
                                </label>


                                <input
                                    type="date"
                                    class="form-control"
                                    id="auditoriaDataFinal"
                                    name="data_final">

                            </div>

                        </div>


                        <!-- Utilizador -->

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="auditoriaUsuarioId">
                                    Utilizador
                                </label>


                                <select
                                    class="form-control"
                                    id="auditoriaUsuarioId"
                                    name="usuario_id">

                                    <option value="">
                                        Todos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Operação -->

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="auditoriaOperacao">
                                    Operação
                                </label>


                                <select
                                    class="form-control"
                                    id="auditoriaOperacao"
                                    name="operacao">

                                    <option value="">
                                        Todas
                                    </option>

                                    <option value="INSERT">
                                        Criação
                                    </option>

                                    <option value="UPDATE">
                                        Alteração
                                    </option>

                                    <option value="DELETE">
                                        Eliminação
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <div class="text-right">

                        <button
                            type="button"
                            class="btn btn-light"
                            id="auditoriaBtnLimpar">

                            <i class="fa fa-eraser"></i>

                            Limpar

                        </button>


                        <button
                            type="submit"
                            class="btn btn-primary"
                            id="auditoriaBtnFiltrar">

                            <i class="fa fa-filter"></i>

                            Filtrar

                        </button>

                    </div>

                </form>

            </div>

        </div>


        <!-- =========================================
             LISTA
        ========================================== -->

        <div class="card">

            <div class="card-body">


                <div
                    id="auditoriaErroLista"
                    class="alert alert-danger d-none">
                </div>


                <div class="table-responsive">

                    <table
                        id="auditoriaTabela"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Data/Hora
                                </th>

                                <th>
                                    Utilizador
                                </th>

                                <th>
                                    Operação
                                </th>

                                <th class="text-center">
                                    Alteração
                                </th>

                            </tr>

                        </thead>


                        <tbody id="auditoriaTabelaBody">

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =============================================
     MODAL DETALHES DA AUDITORIA
============================================== -->

<div
    id="auditoriaModalDetalhes"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="auditoriaModalTitulo"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">


            <!-- Header -->

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="auditoriaModalTitulo">

                    Detalhes da Auditoria

                </h5>


                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Fechar">

                    <span aria-hidden="true">
                        &times;
                    </span>

                </button>

            </div>


            <!-- Body -->

            <div class="modal-body">


                <div
                    id="auditoriaErroDetalhes"
                    class="alert alert-danger d-none">
                </div>


                <!-- Informações -->

                <div class="row mb-3">

                    <div class="col-md-4">

                        <small class="text-muted">
                            Utilizador
                        </small>

                        <div>
                            <strong id="auditoriaDetalheUsuario">
                                -
                            </strong>
                        </div>

                    </div>


                    <div class="col-md-4">

                        <small class="text-muted">
                            Operação
                        </small>

                        <div id="auditoriaDetalheOperacao">
                            -
                        </div>

                    </div>


                    <div class="col-md-4">

                        <small class="text-muted">
                            Data/Hora
                        </small>

                        <div id="auditoriaDetalheData">
                            -
                        </div>

                    </div>

                </div>


                <hr>


                <!-- Título dos dados -->

                <h5
                    class="mb-3"
                    id="auditoriaDetalheTitulo">

                    Alterações realizadas

                </h5>


                <!-- Tabela -->

                <div class="table-responsive">

                    <table
                        class="table table-bordered table-hover mb-0">

                        <thead
                            id="auditoriaDetalheTabelaCabecalho">

                        </thead>


                        <tbody
                            id="auditoriaDetalheTabelaBody">

                        </tbody>

                    </table>

                </div>


                <div
                    id="auditoriaSemAlteracoes"
                    class="alert alert-info d-none mt-3 mb-0">

                    Não foram identificadas alterações nos dados armazenados.

                </div>

            </div>


            <!-- Footer -->

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