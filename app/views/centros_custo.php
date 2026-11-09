<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- ==================================================
        CABEÇALHO
        ================================================== -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Centros de Custo
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>

                        <li class="breadcrumb-item">
                            Financeiro
                        </li>

                        <li class="breadcrumb-item active">
                            Centros de Custo
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoCentroCusto">

                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Novo Centro de Custo
                        </span>

                    </button>

                </div>

            </div>

        </div>


        <!-- ==================================================
        CARDS
        ================================================== -->

        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-primary">

                                <i class="fa fa-sitemap"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="totalCentrosCusto">
                                    0
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Total de Centros
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-success">

                                <i class="fa fa-check-circle"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="totalCentrosActivos">
                                    0
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Centros Activos
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-warning">

                                <i class="fa fa-wallet"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="orcamentoTotalCentros">
                                    0,00 MZN
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Orçamento Mensal
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-info">

                                <i class="fa fa-code-branch"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="totalCentrosFilhos">
                                    0
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Subcentros
                            </h6>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==================================================
        FILTROS
        ================================================== -->

        <div class="card">

            <div class="card-body">

                <div class="form-row">

                    <div class="form-group col-md-3">

                        <label for="filtroCentroTipo">
                            Tipo
                        </label>

                        <select
                            class="form-control"
                            id="filtroCentroTipo">

                            <option value="">
                                Todos os tipos
                            </option>

                            <option value="CUSTO">
                                Custo
                            </option>

                            <option value="RECEITA">
                                Receita
                            </option>

                            <option value="MISTO">
                                Misto
                            </option>

                        </select>

                    </div>


                    <div class="form-group col-md-3">

                        <label for="filtroCentroFilial">
                            Filial
                        </label>

                        <select
                            class="form-control"
                            id="filtroCentroFilial">

                            <option value="">
                                Todas as filiais
                            </option>

                        </select>

                    </div>


                    <div class="form-group col-md-3">

                        <label for="filtroCentroStatus">
                            Status
                        </label>

                        <select
                            class="form-control"
                            id="filtroCentroStatus">

                            <option value="">
                                Todos os status
                            </option>

                        </select>

                    </div>


                    <div class="form-group col-md-3">

                        <label>
                            &nbsp;
                        </label>

                        <div class="d-flex">

                            <button
                                type="button"
                                class="btn btn-light btn-block mr-2"
                                id="btnLimparFiltroCentro">

                                <i class="fa fa-eraser"></i>
                                Limpar

                            </button>

                            <button
                                type="button"
                                class="btn btn-primary btn-block"
                                id="btnFiltrarCentro">

                                <i class="fa fa-search"></i>
                                Consultar

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==================================================
        TABELA
        ================================================== -->

        <div class="card">

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="centrosCustoTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Código</th>
                                <th>Centro de Custo</th>
                                <th>Centro Pai</th>
                                <th>Tipo</th>
                                <th>Filial</th>
                                <th>Responsável</th>

                                <th class="text-right">
                                    Orçamento Mensal
                                </th>

                                <th>Status</th>

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


<!-- ==================================================
MODAL CADASTRO / EDIÇÃO
================================================== -->

<div
    id="modalCentroCusto"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog modal-lg">

        <form
            id="formCentroCusto"
            class="modal-content">

            <div class="modal-header">

                <h4
                    class="modal-title"
                    id="tituloModalCentroCusto">

                    Novo Centro de Custo

                </h4>

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
                    id="centroCustoId"
                    name="id">


                <div class="form-row">

                    <!-- Código -->
                    <div class="form-group col-md-4">

                        <label for="codigo">
                            Código *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="codigo"
                            name="codigo"
                            maxlength="30"
                            placeholder="Ex.: ADM"
                            required>

                        <small class="form-text text-muted">
                            Código único dentro da empresa.
                        </small>

                    </div>


                    <!-- Nome -->
                    <div class="form-group col-md-8">

                        <label for="nome">
                            Nome *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="nome"
                            name="nome"
                            maxlength="150"
                            placeholder="Ex.: Administração"
                            required>

                    </div>


                    <!-- Centro Pai -->
                    <div class="form-group col-md-6">

                        <label for="centro_pai_id">
                            Centro Pai
                        </label>

                        <select
                            class="form-control"
                            id="centro_pai_id"
                            name="centro_pai_id">

                            <option value="">
                                Nenhum — Centro Principal
                            </option>

                        </select>

                        <small class="form-text text-muted">
                            Use para criar subcentros.
                        </small>

                    </div>


                    <!-- Filial -->
                    <div class="form-group col-md-6">

                        <label for="filial_id">
                            Filial
                        </label>

                        <select
                            class="form-control"
                            id="filial_id"
                            name="filial_id">

                            <option value="">
                                Todas / Sede Principal
                            </option>

                        </select>

                    </div>


                    <!-- Tipo -->
                    <div class="form-group col-md-4">

                        <label for="tipo">
                            Tipo *
                        </label>

                        <select
                            class="form-control"
                            id="tipo"
                            name="tipo"
                            required>

                            <option value="CUSTO">
                                Custo
                            </option>

                            <option value="RECEITA">
                                Receita
                            </option>

                            <option value="MISTO">
                                Misto
                            </option>

                        </select>

                    </div>


                    <!-- Status -->
                    <div class="form-group col-md-4">

                        <label for="status_id">
                            Status *
                        </label>

                        <select
                            class="form-control"
                            id="status_id"
                            name="status_id"
                            required>

                            <option value="">
                                Selecione
                            </option>

                        </select>

                    </div>


                    <!-- Orçamento -->
                    <div class="form-group col-md-4">

                        <label for="orcamento_mensal">
                            Orçamento Mensal
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="orcamento_mensal"
                            name="orcamento_mensal"
                            min="0"
                            step="0.01"
                            value="0.00">

                    </div>


                    <!-- Responsável -->
                    <div class="form-group col-md-12">

                        <label for="responsavel">
                            Responsável
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="responsavel"
                            name="responsavel"
                            maxlength="150"
                            placeholder="Nome do responsável pelo centro">

                    </div>


                    <!-- Descrição -->
                    <div class="form-group col-md-12">

                        <label for="descricao">
                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            id="descricao"
                            name="descricao"
                            rows="3"
                            maxlength="255"
                            placeholder="Descreva a finalidade deste centro de custo"></textarea>

                    </div>

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
                    class="btn btn-primary"
                    id="btnGuardarCentroCusto">

                    <i class="fa fa-save"></i>
                    Guardar

                </button>

            </div>

        </form>

    </div>

</div>


<!-- ==================================================
MODAL DETALHES
================================================== -->

<div
    id="modalDetalhesCentroCusto"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h4 class="modal-title">
                    Detalhes do Centro de Custo
                </h4>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">

                    &times;

                </button>

            </div>


            <div class="modal-body">

                <div class="row">

                    <div class="col-md-6">

                        <p>
                            <strong>Código:</strong>
                            <span id="detalheCentroCodigo">-</span>
                        </p>

                        <p>
                            <strong>Nome:</strong>
                            <span id="detalheCentroNome">-</span>
                        </p>

                        <p>
                            <strong>Tipo:</strong>
                            <span id="detalheCentroTipo">-</span>
                        </p>

                        <p>
                            <strong>Status:</strong>
                            <span id="detalheCentroStatus">-</span>
                        </p>

                    </div>


                    <div class="col-md-6">

                        <p>
                            <strong>Centro Pai:</strong>
                            <span id="detalheCentroPai">-</span>
                        </p>

                        <p>
                            <strong>Filial:</strong>
                            <span id="detalheCentroFilial">-</span>
                        </p>

                        <p>
                            <strong>Responsável:</strong>
                            <span id="detalheCentroResponsavel">-</span>
                        </p>

                        <p>
                            <strong>Orçamento Mensal:</strong>
                            <span id="detalheCentroOrcamento">-</span>
                        </p>

                    </div>


                    <div class="col-md-12">

                        <hr>

                        <p>
                            <strong>Descrição:</strong>
                            <span id="detalheCentroDescricao">-</span>
                        </p>

                    </div>

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


<!-- ==================================================
MODAL ELIMINAR
================================================== -->

<div
    id="modalEliminarCentroCusto"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <form
            id="formEliminarCentroCusto"
            class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Eliminar Centro de Custo
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
                    id="eliminarCentroCustoId"
                    name="id">

                <div class="text-center">

                    <div class="mb-3">

                        <i
                            class="fa fa-exclamation-triangle text-danger"
                            style="font-size: 48px;"></i>

                    </div>

                    <h5>
                        Confirma a eliminação?
                    </h5>

                    <p class="text-muted mb-0">

                        O centro de custo só poderá ser eliminado
                        se não estiver associado a movimentos,
                        contas ou subcentros.

                    </p>

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
                    class="btn btn-danger"
                    id="btnConfirmarEliminarCentro">

                    <i class="fa fa-trash"></i>
                    Eliminar

                </button>

            </div>

        </form>

    </div>

</div>