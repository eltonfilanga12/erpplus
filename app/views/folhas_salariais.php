<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Cabeçalho -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Folhas de Salário
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
                            Folhas de Salário
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-toggle="modal"
                        data-target="#modalFolhaSalarial">

                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Gerar Folha
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Resumo -->
        <div class="row">

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <span>Total de folhas</span>
                        <h3 id="resumoFolhasQuantidade">
                            0
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <span>Funcionários</span>
                        <h3 id="resumoFolhasFuncionarios">
                            0
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <span>Total líquido</span>
                        <h3 id="resumoFolhasLiquido">
                            0,00 MT
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <span>Saldo pendente</span>
                        <h3 id="resumoFolhasPendente">
                            0,00 MT
                        </h3>
                    </div>
                </div>
            </div>

        </div>

        <!-- Filtros -->
        <div class="card">
            <div class="card-body">

                <form id="formFiltrosFolhas">
                    <div class="row">

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Pesquisa</label>

                                <input
                                    type="text"
                                    id="filtroPesquisaFolha"
                                    class="form-control"
                                    placeholder="Código ou descrição">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Período</label>

                                <select
    id="filtroPeriodo"
    name="periodo_id"
    class="form-control"
>
    <option value="">
        Todos os períodos
    </option>
</select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Estado</label>

                                <select
                                    id="filtroEstadoFolha"
                                    class="form-control">

                                    <option value="">
                                        Todos
                                    </option>

                                    <option value="Gerada">
                                        Gerada
                                    </option>

                                    <option value="Fechada">
                                        Fechada
                                    </option>

                                    <option value="Cancelada">
                                        Cancelada
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>

                                <button
                                    type="submit"
                                    class="btn btn-primary btn-block">

                                    <i class="fa fa-search"></i>
                                    Pesquisar
                                </button>
                            </div>
                        </div>

                    </div>
                </form>

            </div>
        </div>

        <!-- Tabela -->
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="folhasSalariaisTable"
                        class="table table-striped
                            custom-table mb-0">

                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Período</th>
                                <th>Funcionários</th>
                                <th>Bruto</th>
                                <th>Descontos</th>
                                <th>Líquido</th>
                                <th>Pago</th>
                                <th>Pendente</th>
                                <th>Pagamento</th>
                                <th>Estado</th>
                                <th class="text-right">
                                    Ações
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

<!-- Modal gerar folha -->
<div
    id="modalFolhaSalarial"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <form id="formFolhaSalarial">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Gerar Folha de Salário
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="">

                    <div class="form-group">
                        <label>
                            Período salarial
                            <span class="text-danger">
                                *
                            </span>
                        </label>

                        <select
    id="periodoId"
    name="periodo_id"
    class="form-control"
    required
>
    <option value="">
        Selecione o período
    </option>
</select>
                    </div>

                    <div class="form-group">
                        <label>Observações</label>

                        <textarea
                            name="observacoes"
                            class="form-control"
                            rows="3"
                            maxlength="1000">
                        </textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">
                        <i class="fa fa-file-text"></i>
                        Gerar Folha
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- Modal detalhes -->
<div
    id="modalDetalhesFolha"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">
                <h5
                    id="detalheFolhaTitulo"
                    class="modal-title">
                    Detalhes da Folha
                </h5>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="table-responsive">
                    <table
                        id="detalhesFolhaTable"
                        class="table table-striped">

                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Funcionário</th>
                                <th>Cargo</th>
                                <th>Salário base</th>
                                <th>Outros proventos</th>
                                <th>Descontos</th>
                                <th>Líquido</th>
                            </tr>
                        </thead>

                        <tbody></tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    data-dismiss="modal">
                    Fechar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modal cancelar -->
<div
    id="modalCancelarFolha"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <form id="formCancelarFolha">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Cancelar Folha Salarial
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="id">

                    <div class="form-group">
                        <label>
                            Motivo
                            <span class="text-danger">
                                *
                            </span>
                        </label>

                        <textarea
                            name="motivo"
                            class="form-control"
                            rows="4"
                            maxlength="255"
                            required>
                        </textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">
                        Voltar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger">
                        <i class="fa fa-times"></i>
                        Cancelar Folha
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
