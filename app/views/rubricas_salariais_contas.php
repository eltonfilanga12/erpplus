<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Contas das Rubricas Salariais
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item">
                            Recursos Humanos
                        </li>

                        <li class="breadcrumb-item active">
                            Contas das Rubricas
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-primary"
                        id="rubricaContaBtnGuardarTodos">
                        <i class="fa fa-save"></i>

                        <span class="d-none d-sm-inline">
                            Guardar todos
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <div
            id="rubricaContaErroGeral"
            class="alert alert-danger d-none"></div>

        <div
            id="rubricaContaSucessoGeral"
            class="alert alert-success d-none"></div>

        <!-- Resumo -->
        <div class="row">

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">
                            Total de rubricas
                        </h6>

                        <h3 id="rubricaContaTotal">
                            0
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">
                            Configuradas
                        </h6>

                        <h3
                            id="rubricaContaConfiguradas"
                            class="text-success">
                            0
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">
                            Não configuradas
                        </h6>

                        <h3
                            id="rubricaContaNaoConfiguradas"
                            class="text-danger">
                            0
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted">
                            Inactivas
                        </h6>

                        <h3
                            id="rubricaContaInactivas"
                            class="text-secondary">
                            0
                        </h3>
                    </div>
                </div>
            </div>

        </div>

        <!-- Filtros -->
        <div class="card">
            <div class="card-body">

                <div class="form-row">

                    <div class="form-group col-md-4 mb-0">
                        <label for="rubricaContaPesquisa">
                            Pesquisar
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="rubricaContaPesquisa"
                            placeholder="Código, nome ou categoria...">
                    </div>

                    <div class="form-group col-md-3 mb-0">
                        <label for="rubricaContaFiltroTipo">
                            Tipo
                        </label>

                        <select
                            class="form-control"
                            id="rubricaContaFiltroTipo">
                            <option value="">
                                Todos
                            </option>

                            <option value="PROVENTO">
                                Provento
                            </option>

                            <option value="DESCONTO">
                                Desconto
                            </option>

                            <option value="INFORMATIVO">
                                Informativo
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-3 mb-0">
                        <label for="rubricaContaFiltroConfiguracao">
                            Configuração
                        </label>

                        <select
                            class="form-control"
                            id="rubricaContaFiltroConfiguracao">
                            <option value="">
                                Todas
                            </option>

                            <option value="CONFIGURADA">
                                Configuradas
                            </option>

                            <option value="NAO_CONFIGURADA">
                                Não configuradas
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-2 mb-0">
                        <label>&nbsp;</label>

                        <button
                            type="button"
                            class="btn btn-light btn-block"
                            id="rubricaContaBtnRecarregar">
                            <i class="fa fa-refresh"></i>
                            Recarregar
                        </button>
                    </div>

                </div>

            </div>
        </div>

        <!-- Tabela -->
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">
                    <table
                        id="rubricaContaTabela"
                        class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 230px;">
                                    Rubrica
                                </th>

                                <th style="min-width: 130px;">
                                    Tipo
                                </th>

                                <th style="min-width: 260px;">
                                    Conta de débito
                                </th>

                                <th style="min-width: 260px;">
                                    Conta de crédito
                                </th>

                                <th class="text-center">
                                    Salários a pagar
                                </th>

                                <th class="text-center">
                                    Centro de custo
                                </th>

                                <th style="min-width: 140px;">
                                    Estado
                                </th>

                                <th class="text-right">
                                    Acções
                                </th>
                            </tr>
                        </thead>

                        <tbody id="rubricaContaTabelaBody">
                            
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>