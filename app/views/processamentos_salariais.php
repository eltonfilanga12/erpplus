<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Processamento Salarial
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>

                        <li class="breadcrumb-item active">
                            Processamento Salarial
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnAbrirProcessamento">

                        <i class="fa fa-calculator"></i>

                        <span class="d-none d-sm-inline">
                            Processar Período
                        </span>

                    </button>

                    <button
                        type="button"
                        class="btn btn-warning"
                        id="btnRecalcularPeriodo">

                        <i class="fa fa-refresh"></i>
                        Recalcular Período

                    </button>

                </div>

            </div>

        </div>


        <!-- Cards de resumo -->
        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span
                                class="dash-widget-icon bg-primary">

                                <i class="fa fa-users"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="resumoQuantidade">
                                    0
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Funcionários processados
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span
                                class="dash-widget-icon bg-info">

                                <i class="fa fa-money"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="resumoBruto">
                                    0,00 MZN
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Total bruto
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span
                                class="dash-widget-icon bg-danger">

                                <i class="fa fa-minus-circle"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="resumoDescontos">
                                    0,00 MZN
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Total de descontos
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span
                                class="dash-widget-icon bg-success">

                                <i class="fa fa-check-circle"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="resumoLiquido">
                                    0,00 MZN
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Total líquido
                            </h6>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Filtros -->
        <div class="card">

            <div class="card-body">

                <div class="row align-items-end">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label for="filtroPeriodo">
                                Período salarial
                            </label>

                            <select
                                id="filtroPeriodo"
                                class="form-control select2">

                                <option value="">
                                    Todos os períodos
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group">

                            <label for="filtroEstado">
                                Estado
                            </label>

                            <select
                                id="filtroEstado"
                                class="form-control">

                                <option value="">
                                    Todos
                                </option>

                                <option value="Pendente">
                                    Pendente
                                </option>

                                <option value="Calculado">
                                    Calculado
                                </option>

                                <option value="Aprovado">
                                    Aprovado
                                </option>

                                <option value="Pago">
                                    Pago
                                </option>

                                <option value="Cancelado">
                                    Cancelado
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group">

                            <label for="filtroPesquisa">
                                Pesquisar
                            </label>

                            <input
                                type="text"
                                id="filtroPesquisa"
                                class="form-control"
                                placeholder="Funcionário ou número">

                        </div>

                    </div>


                    <div class="col-md-2">

                        <div class="form-group">

                            <button
                                type="button"
                                class="btn btn-secondary btn-block"
                                id="btnLimparFiltros">

                                <i class="fa fa-refresh"></i>
                                Limpar

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Tabela -->
        <div class="card">

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="processamentosSalariaisTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Nº
                                </th>

                                <th>
                                    Funcionário
                                </th>

                                <th>
                                    Período
                                </th>

                                <th>
                                    Salário Base
                                </th>

                                <th>
                                    Proventos
                                </th>

                                <th>
                                    Descontos
                                </th>

                                <th>
                                    Bruto
                                </th>

                                <th>
                                    Líquido
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th class="text-center">
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


<!-- =====================================================
     MODAL PROCESSAR PERÍODO
====================================================== -->

<div
    class="modal fade"
    id="modalProcessarPeriodo"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-dialog-centered"
        role="document">

        <div class="modal-content">

            <form id="formProcessarPeriodo">

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa fa-calculator"></i>
                        Processar Período Salarial

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


                <div class="modal-body">

                    <div class="alert alert-info">

                        <i class="fa fa-info-circle"></i>

                        Selecione um período salarial aberto.
                        Todos os funcionários elegíveis serão
                        processados automaticamente.

                    </div>

                    <div class="form-group">

                        <label for="periodoProcessamento">

                            Período salarial

                            <span class="text-danger">
                                *
                            </span>

                        </label>

                        <select
                            id="periodoProcessamento"
                            name="periodo_id"
                            class="form-control select2"
                            required>

                            <option value="">
                                Selecione o período
                            </option>

                        </select>

                        <div
                            class="invalid-feedback"
                            id="erroPeriodoProcessamento">
                        </div>

                    </div>


                    <div
                        class="card bg-light"
                        id="detalhesPeriodo"
                        style="display: none;">

                        <div class="card-body">

                            <div class="row">

                                <div class="col-6">

                                    <small class="text-muted">
                                        Data de início
                                    </small>

                                    <div
                                        class="font-weight-bold"
                                        id="detalhePeriodoInicio">
                                        -
                                    </div>

                                </div>

                                <div class="col-6">

                                    <small class="text-muted">
                                        Data de fim
                                    </small>

                                    <div
                                        class="font-weight-bold"
                                        id="detalhePeriodoFim">
                                        -
                                    </div>

                                </div>

                            </div>


                            <hr>


                            <div class="row">

                                <div class="col-6">

                                    <small class="text-muted">
                                        Dias do calendário
                                    </small>

                                    <div
                                        class="font-weight-bold"
                                        id="detalheDiasCalendario">
                                        0
                                    </div>

                                </div>

                                <div class="col-6">

                                    <small class="text-muted">
                                        Dias úteis
                                    </small>

                                    <div
                                        class="font-weight-bold"
                                        id="detalheDiasUteis">
                                        0
                                    </div>

                                </div>

                            </div>

                        </div>

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
                        class="btn btn-primary"
                        id="btnConfirmarProcessamento">

                        <i class="fa fa-calculator"></i>

                        Processar

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL DETALHES DO PROCESSAMENTO
====================================================== -->

<div
    class="modal fade"
    id="modalDetalhesProcessamento"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl modal-dialog-scrollable"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="fa fa-file-text-o"></i>
                    Detalhes do Processamento

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


            <div class="modal-body">

                <input
                    type="hidden"
                    id="detalheProcessamentoId">


                <!-- Dados do funcionário -->
                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Número
                            </label>

                            <input
                                type="text"
                                id="detalheNumeroFuncionario"
                                class="form-control"
                                readonly>

                        </div>

                    </div>


                    <div class="col-md-8">

                        <div class="form-group">

                            <label>
                                Funcionário
                            </label>

                            <input
                                type="text"
                                id="detalheFuncionarioNome"
                                class="form-control"
                                readonly>

                        </div>

                    </div>

                </div>


                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Cargo
                            </label>

                            <input
                                type="text"
                                id="detalheCargo"
                                class="form-control"
                                readonly>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Departamento
                            </label>

                            <input
                                type="text"
                                id="detalheDepartamento"
                                class="form-control"
                                readonly>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Filial
                            </label>

                            <input
                                type="text"
                                id="detalheFilial"
                                class="form-control"
                                readonly>

                        </div>

                    </div>

                </div>


                <hr>


                <!-- Resumo individual -->
                <div class="row">

                    <div class="col-md-3">

                        <div class="card resumo-processamento-card">

                            <div class="card-body">

                                <small class="text-muted">
                                    Salário base
                                </small>

                                <h5 id="detalheSalarioBase">
                                    0,00 MZN
                                </h5>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="card resumo-processamento-card">

                            <div class="card-body">

                                <small class="text-muted">
                                    Total de proventos
                                </small>

                                <h5
                                    id="detalheTotalProventos"
                                    class="text-success">

                                    0,00 MZN

                                </h5>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="card resumo-processamento-card">

                            <div class="card-body">

                                <small class="text-muted">
                                    Total de descontos
                                </small>

                                <h5
                                    id="detalheTotalDescontos"
                                    class="text-danger">

                                    0,00 MZN

                                </h5>

                            </div>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="card resumo-processamento-card">

                            <div class="card-body">

                                <small class="text-muted">
                                    Salário líquido
                                </small>

                                <h5
                                    id="detalheSalarioLiquido"
                                    class="text-primary">

                                    0,00 MZN

                                </h5>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Itens -->
                <div class="table-responsive">

                    <table
                        class="table table-bordered"
                        id="tabelaItensProcessamento">

                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Rubrica
                                </th>

                                <th>
                                    Tipo
                                </th>

                                <th>
                                    Origem
                                </th>

                                <th class="text-right">
                                    Quantidade
                                </th>

                                <th class="text-right">
                                    Valor unitário
                                </th>

                                <th class="text-right">
                                    Percentagem
                                </th>

                                <th class="text-right">
                                    Total
                                </th>

                            </tr>

                        </thead>

                        <tbody id="tbodyItensProcessamento">

                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center text-muted">

                                    Nenhum item encontrado.

                                </td>

                            </tr>

                        </tbody>

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


<!-- =====================================================
     MODAL APROVAR
====================================================== -->

<div
    class="modal fade"
    id="modalAprovarProcessamento"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-dialog-centered"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="fa fa-check-circle"></i>
                    Aprovar Processamento

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


            <div class="modal-body">

                <input
                    type="hidden"
                    id="aprovarProcessamentoId">

                <p>
                    Tem a certeza de que deseja aprovar
                    este processamento salarial?
                </p>

                <div class="alert alert-warning mb-0">

                    <i class="fa fa-exclamation-triangle"></i>

                    Depois da aprovação, o processamento
                    ficará disponível para pagamento.

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
                    type="button"
                    class="btn btn-success"
                    id="btnConfirmarAprovacao">

                    <i class="fa fa-check"></i>
                    Aprovar

                </button>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL CANCELAR
====================================================== -->

<div
    class="modal fade"
    id="modalCancelarProcessamento"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-dialog-centered"
        role="document">

        <div class="modal-content">

            <form id="formCancelarProcessamento">

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa fa-ban"></i>
                        Cancelar Processamento

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


                <div class="modal-body">

                    <input
                        type="hidden"
                        id="cancelarProcessamentoId"
                        name="id">

                    <div class="form-group">

                        <label for="motivoCancelamento">

                            Motivo do cancelamento

                            <span class="text-danger">
                                *
                            </span>

                        </label>

                        <textarea
                            id="motivoCancelamento"
                            name="motivo"
                            class="form-control"
                            rows="4"
                            maxlength="1000"
                            required
                            placeholder="Informe o motivo do cancelamento"></textarea>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">

                        Fechar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger"
                        id="btnConfirmarCancelamento">

                        <i class="fa fa-ban"></i>
                        Cancelar processamento

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<style>
    #processamentosSalariaisTable th,
    #processamentosSalariaisTable td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .badge-processamento {
        display: inline-block;
        min-width: 85px;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
    }

    .processamento-pendente {
        background-color: #fff3cd;
        color: #856404;
    }

    .processamento-calculado {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .processamento-aprovado {
        background-color: #d4edda;
        color: #155724;
    }

    .processamento-pago {
        background-color: #cce5ff;
        color: #004085;
    }

    .processamento-cancelado {
        background-color: #f8d7da;
        color: #721c24;
    }

    .processamento-desconhecido {
        background-color: #e2e3e5;
        color: #383d41;
    }

    .resumo-processamento-card {
        min-height: 100px;
    }

    .acoes-processamento {
        white-space: nowrap;
    }

    #modalDetalhesProcessamento .modal-body {
        max-height: calc(100vh - 190px);
        overflow-y: auto;
    }

    #tabelaItensProcessamento th,
    #tabelaItensProcessamento td {
        vertical-align: middle;
    }
</style>