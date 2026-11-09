<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Logs
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Logs
                        </li>

                    </ul>
                </div>


                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="logBtnAtualizar">

                        <i class="fa fa-refresh"></i>

                        <span class="d-none d-sm-inline">
                            Atualizar
                        </span>

                    </button>

                </div>

            </div>
        </div>


        <!-- Filtros -->
        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Filtros
                </h4>

            </div>


            <div class="card-body">

                <form
                    id="logFormFiltros"
                    autocomplete="off">

                    <div class="row">


                        <div class="col-lg-2 col-md-4">

                            <div class="form-group">

                                <label for="logDataInicial">
                                    Data inicial
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="logDataInicial"
                                    name="data_inicial">

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-4">

                            <div class="form-group">

                                <label for="logDataFinal">
                                    Data final
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="logDataFinal"
                                    name="data_final">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-4">

                            <div class="form-group">

                                <label for="logUsuarioId">
                                    Utilizador
                                </label>

                                <select
                                    class="form-control"
                                    id="logUsuarioId"
                                    name="usuario_id">

                                    <option value="">
                                        Todos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-4">

                            <div class="form-group">

                                <label for="logAcao">
                                    Ação
                                </label>

                                <select
                                    class="form-control"
                                    id="logAcao"
                                    name="acao">

                                    <option value="">
                                        Todas
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-8">

                            <div class="form-group">

                                <label for="logPesquisa">
                                    Pesquisa
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="logPesquisa"
                                    name="pesquisa"
                                    maxlength="200"
                                    placeholder="Utilizador, ação, descrição ou IP">

                            </div>

                        </div>

                    </div>


                    <div class="text-right">

                        <button
                            type="button"
                            class="btn btn-light"
                            id="logBtnLimpar">

                            <i class="fa fa-eraser"></i>
                            Limpar

                        </button>


                        <button
                            type="submit"
                            class="btn btn-primary"
                            id="logBtnFiltrar">

                            <i class="fa fa-filter"></i>
                            Filtrar

                        </button>

                    </div>

                </form>

            </div>

        </div>


        <!-- Lista -->
        <div class="card">

            <div class="card-body">

                <div
                    id="logErroLista"
                    class="alert alert-danger d-none">
                </div>


                <div class="table-responsive">

                    <table
                        id="logTabela"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>
                                <th>Data/Hora</th>
                                <th>Utilizador</th>
                                <th>Ação</th>
                                <th>Descrição</th>
                                <th>IP</th>
                            </tr>

                        </thead>


                        <tbody id="logTabelaBody">

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>
</div>