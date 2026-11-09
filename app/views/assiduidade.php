<!-- ==========================================
     PAGE WRAPPER
=========================================== -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- ===========================
             PAGE HEADER
        ============================ -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">

                        Assiduidade

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

                            Assiduidade

                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        id="btnMarcarAssiduidadeLote"
                        type="button"
                        class="btn btn-success mr-2">

                        <i class="fa fa-users"></i>

                        Marcar Assiduidade

                    </button>

                    <button
                        id="btnNovoRegistoPonto"
                        type="button"
                        class="btn btn-primary">

                        <i class="fa fa-plus"></i>

                        Registo Individual

                    </button>

                </div>

            </div>

        </div>

        <!-- ===========================
            RESUMO
        ============================ -->

        <div class="row">

            <div class="col-lg-2 col-md-4">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-users"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoTotalFuncionariosAssiduidade">

                                0

                            </h3>

                            <span>

                                Funcionários

                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-lg-2 col-md-4">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-check-circle"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoPresencasAssiduidade">

                                0

                            </h3>

                            <span>

                                Presentes

                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-lg-2 col-md-4">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-times-circle"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoFaltasAssiduidade">

                                0

                            </h3>

                            <span>

                                Faltas

                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-lg-2 col-md-4">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-clock-o"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoAtrasosAssiduidade">

                                0

                            </h3>

                            <span>

                                Atrasos

                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-lg-2 col-md-4">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-hourglass-half"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoHorasExtrasAssiduidade">

                                0

                            </h3>

                            <span>

                                Horas Extras

                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-lg-2 col-md-4">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-line-chart"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoTaxaAssiduidade">

                                0%

                            </h3>

                            <span>

                                Taxa

                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- ===========================
              FILTROS
        ============================ -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title">

                    Filtros

                </h4>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-2">

                        <label>

                            Data Inicial

                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="filtroAssiduidadeDataInicio">

                    </div>

                    <div class="col-md-2">

                        <label>

                            Data Final

                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="filtroAssiduidadeDataFim">

                    </div>

                    <div class="col-md-3">

                        <label>

                            Funcionário

                        </label>

                        <select
                            id="filtroAssiduidadeFuncionarioId"
                            class="form-control">

                        </select>

                    </div>

                    <div class="col-md-2">

                        <label>

                            Filial

                        </label>

                        <select
                            id="filtroAssiduidadeFilialId"
                            class="form-control">

                        </select>

                    </div>

                    <div class="col-md-3">

                        <label>

                            Estado

                        </label>

                        <select
                            id="filtroAssiduidadeStatusId"
                            class="form-control">

                        </select>

                    </div>

                </div>

                <div class="row mt-3">

                    <div class="col-md-6">

                        <input
                            id="filtroAssiduidadePesquisa"
                            class="form-control"
                            placeholder="Pesquisar...">

                    </div>

                    <div class="col-md-6 text-right">

                        <button
                            id="btnFiltrarAssiduidade"
                            class="btn btn-success">

                            <i class="fa fa-search"></i>

                            Filtrar

                        </button>

                        <button
                            id="btnLimparFiltrosAssiduidade"
                            class="btn btn-secondary">

                            <i class="fa fa-refresh"></i>

                            Limpar

                        </button>

                        <button
                            id="btnExportarAssiduidade"
                            class="btn btn-info">

                            <i class="fa fa-file-excel-o"></i>

                            Exportar

                        </button>

                    </div>

                </div>

            </div>

        </div>

        <!-- ===========================
               ABAS
        ============================ -->

        <div class="card">

            <div class="card-body">

                <ul class="nav nav-tabs nav-tabs-solid">

                    <li class="nav-item">

                        <a
                            class="nav-link active"
                            data-toggle="tab"
                            href="#tabRegistos">

                            Registos de Ponto

                        </a>

                    </li>

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            data-toggle="tab"
                            href="#tabAssiduidade">

                            Assiduidade

                        </a>

                    </li>

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            data-toggle="tab"
                            href="#tabFaltas">

                            Faltas

                        </a>

                    </li>

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            data-toggle="tab"
                            href="#tabHorasExtras">

                            Horas Extras

                        </a>

                    </li>

                </ul>

                <div class="tab-content">

                    <!-- ==================================================
                        ABA: REGISTOS DE PONTO
                    ================================================== -->
                    <div
                        id="tabRegistos"
                        class="tab-pane show active">

                        <div class="row mt-4">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label for="filtroRegistoPontoTipo">
                                        Tipo de registo
                                    </label>

                                    <select
                                        id="filtroRegistoPontoTipo"
                                        class="form-control">

                                        <option value="">
                                            Todos os tipos
                                        </option>

                                    </select>

                                </div>

                            </div>

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label for="filtroRegistoPontoOrigem">
                                        Origem
                                    </label>

                                    <select
                                        id="filtroRegistoPontoOrigem"
                                        class="form-control">

                                        <option value="">
                                            Todas as origens
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">

                            <div class="text-muted">
                                <span id="totalRegistosPontoSelecionados">0</span>
                                registo(s) selecionado(s)
                            </div>

                            <button
                                id="btnValidarRegistosPontoSelecionados"
                                type="button"
                                class="btn btn-success"
                                disabled>

                                <i class="fa fa-check-square-o"></i>
                                Validar selecionados

                            </button>

                        </div>

                        <div class="table-responsive">

                            <table
                                id="registosPontoTable"
                                class="table table-striped custom-table mb-0">

                                <thead>

                                    <tr>

                                        <th class="text-center" style="width: 42px;">
                                            <input
                                                id="selecionarTodosRegistosPonto"
                                                type="checkbox"
                                                title="Selecionar todos os registos visíveis">
                                        </th>

                                        <th>
                                            Data
                                        </th>

                                        <th>
                                            Hora
                                        </th>

                                        <th>
                                            Funcionário
                                        </th>

                                        <th>
                                            Tipo
                                        </th>

                                        <th>
                                            Origem
                                        </th>

                                        <th>
                                            Filial
                                        </th>

                                        <th>
                                            Registo
                                        </th>

                                        <th>
                                            Estado
                                        </th>

                                        <th class="text-right">
                                            Ações
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>



                                </tbody>

                            </table>

                        </div>

                    </div>


                    <!-- ==================================================
                            ABA: ASSIDUIDADE DIÁRIA
                        ================================================== -->
                    <div
                        id="tabAssiduidade"
                        class="tab-pane fade">

                        <div class="row mt-4">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label for="filtroAssiduidadeOcorrencia">
                                        Ocorrência
                                    </label>

                                    <select
                                        id="filtroAssiduidadeOcorrencia"
                                        class="form-control">

                                        <option value="">
                                            Todas as ocorrências
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>

                        <!-- ==========================================
     APROVAÇÃO MÚLTIPLA DA ASSIDUIDADE
=========================================== -->

                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">

                            <div class="text-muted">

                                <span id="totalAssiduidadesSelecionadas">
                                    0
                                </span>

                                registo(s) selecionado(s)

                            </div>

                            <button
                                id="btnAprovarAssiduidadesSelecionadas"
                                type="button"
                                class="btn btn-success"
                                disabled>

                                <i class="fa fa-check-square-o"></i>

                                Aprovar selecionados

                            </button>

                        </div>


                        <!-- ==========================================
                            TABELA DE ASSIDUIDADE
                        =========================================== -->

                        <div class="table-responsive">

                            <table
                                id="assiduidadeTable"
                                class="table table-striped custom-table mb-0">

                                <thead>

                                    <tr>

                                        <th
                                            class="text-center"
                                            style="width: 42px;">

                                            <input
                                                id="selecionarTodasAssiduidades"
                                                type="checkbox"
                                                title="Selecionar todos os registos visíveis">

                                        </th>

                                        <th>
                                            Data
                                        </th>

                                        <th>
                                            Funcionário
                                        </th>

                                        <th>
                                            Filial
                                        </th>

                                        <th>
                                            Entrada
                                        </th>

                                        <th>
                                            Saída
                                        </th>

                                        <th>
                                            Trabalhado
                                        </th>

                                        <th>
                                            Atraso
                                        </th>

                                        <th>
                                            Horas extras
                                        </th>

                                        <th>
                                            Tempo em falta
                                        </th>

                                        <th>
                                            Ocorrência
                                        </th>

                                        <th>
                                            Estado
                                        </th>

                                        <th class="text-right">
                                            Ações
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>



                                </tbody>

                            </table>

                        </div>

                    </div>


                    <!-- ==================================================
                        ABA: FALTAS
                    ================================================== -->
                    <div
                        id="tabFaltas"
                        class="tab-pane fade">

                        <div class="row mt-4">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label for="filtroFaltaStatusId">
                                        Estado da falta
                                    </label>

                                    <select
                                        id="filtroFaltaStatusId"
                                        class="form-control">

                                        <option value="">
                                            Todos os estados
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>

                        <div class="table-responsive">

                            <table
                                id="faltasTable"
                                class="table table-striped custom-table mb-0">

                                <thead>

                                    <tr>

                                        <th>
                                            Data
                                        </th>

                                        <th>
                                            Funcionário
                                        </th>

                                        <th>
                                            Tipo
                                        </th>

                                        <th>
                                            Tempo
                                        </th>

                                        <th>
                                            Dias
                                        </th>

                                        <th>
                                            Remunerada
                                        </th>

                                        <th>
                                            Desconta salário
                                        </th>

                                        <th>
                                            Origem
                                        </th>

                                        <th>
                                            Estado
                                        </th>

                                        <th class="text-right">
                                            Ações
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>



                                </tbody>

                            </table>

                        </div>

                    </div>


                    <!-- ==================================================
                        ABA: HORAS EXTRAS
                    ================================================== -->
                    <div
                        id="tabHorasExtras"
                        class="tab-pane fade">

                        <div class="row mt-4">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label for="filtroHoraExtraStatusId">
                                        Estado das horas extras
                                    </label>

                                    <select
                                        id="filtroHoraExtraStatusId"
                                        class="form-control">

                                        <option value="">
                                            Todos os estados
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>

                        <div class="table-responsive">

                            <table
                                id="horasExtrasTable"
                                class="table table-striped custom-table mb-0">

                                <thead>

                                    <tr>

                                        <th>
                                            Data
                                        </th>

                                        <th>
                                            Funcionário
                                        </th>

                                        <th>
                                            Tipo
                                        </th>

                                        <th>
                                            Apurado
                                        </th>

                                        <th>
                                            Aprovado
                                        </th>

                                        <th>
                                            Acréscimo
                                        </th>

                                        <th>
                                            Valor/hora
                                        </th>

                                        <th>
                                            Valor total
                                        </th>

                                        <th>
                                            Estado
                                        </th>

                                        <th class="text-right">
                                            Ações
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>



                                </tbody>

                            </table>

                        </div>

                    </div>
                </div>
                <!-- /.tab-content -->

            </div>
            <!-- /.card-body -->

        </div>
        <!-- /.card -->

    </div>

    <!-- ==================================================
     MODAL: REGISTO DE PONTO
================================================== -->
    <div
        id="modalRegistoPonto"
        class="modal custom-modal fade"
        tabindex="-1"
        role="dialog"
        aria-hidden="true">

        <div
            class="modal-dialog modal-dialog-centered modal-lg"
            role="document">

            <div class="modal-content">

                <div class="modal-header">

                    <h5
                        id="tituloModalRegistoPonto"
                        class="modal-title">

                        Novo Registo de Ponto

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

                <form id="formRegistoPonto">

                    <div class="modal-body">

                        <input
                            type="hidden"
                            id="registoPontoId"
                            name="id">

                        <div class="row">

                            <div class="col-md-12">

                                <div class="form-group">

                                    <label>
                                        Tipo de lançamento *
                                    </label>

                                    <select
                                        id="registoIndividualModo"
                                        class="form-control">

                                        <option value="PONTO">
                                            Registo de ponto
                                        </option>

                                        <option value="FALTA">
                                            Falta — não compareceu
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label for="registoPontoFuncionarioId">
                                        Funcionário
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        id="registoPontoFuncionarioId"
                                        name="funcionario_id"
                                        class="form-control"
                                        required>

                                        <option value="">
                                            Selecione o funcionário
                                        </option>

                                    </select>

                                    <div class="invalid-feedback">
                                        Selecione o funcionário.
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label for="registoPontoFilialId">
                                        Filial
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        id="registoPontoFilialId"
                                        name="filial_id"
                                        class="form-control"
                                        required>

                                        <option value="">
                                            Selecione a filial
                                        </option>

                                    </select>

                                    <div class="invalid-feedback">
                                        Selecione a filial.
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="registoPontoData">
                                        Data
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="date"
                                        id="registoPontoData"
                                        name="data"
                                        class="form-control"
                                        required>

                                    <div class="invalid-feedback">
                                        Informe a data.
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="registoPontoHora">
                                        Hora
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="time"
                                        id="registoPontoHora"
                                        name="hora"
                                        class="form-control"
                                        step="1"
                                        required>

                                    <div class="invalid-feedback">
                                        Informe a hora.
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="registoPontoTipo">
                                        Tipo
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        id="registoPontoTipo"
                                        name="tipo"
                                        class="form-control"
                                        required>

                                        <option value="">
                                            Selecione o tipo
                                        </option>

                                    </select>

                                    <div class="invalid-feedback">
                                        Selecione o tipo de registo.
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="registoPontoOrigem">
                                        Origem
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        id="registoPontoOrigem"
                                        name="origem"
                                        class="form-control"
                                        required>

                                        <option value="">
                                            Selecione a origem
                                        </option>

                                    </select>

                                    <div class="invalid-feedback">
                                        Selecione a origem.
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="registoPontoDispositivo">
                                        Dispositivo
                                    </label>

                                    <input
                                        type="text"
                                        id="registoPontoDispositivo"
                                        name="dispositivo"
                                        class="form-control"
                                        maxlength="150"
                                        placeholder="Ex.: Terminal RH-01">

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="registoPontoReferencia">
                                        Referência externa
                                    </label>

                                    <input
                                        type="text"
                                        id="registoPontoReferencia"
                                        name="referencia_externa"
                                        class="form-control"
                                        maxlength="150"
                                        placeholder="Código ou identificador">

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label for="registoPontoLatitude">
                                        Latitude
                                    </label>

                                    <input
                                        type="number"
                                        id="registoPontoLatitude"
                                        name="latitude"
                                        class="form-control"
                                        step="0.0000001"
                                        placeholder="-25.0000000">

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label for="registoPontoLongitude">
                                        Longitude
                                    </label>

                                    <input
                                        type="number"
                                        id="registoPontoLongitude"
                                        name="longitude"
                                        class="form-control"
                                        step="0.0000001"
                                        placeholder="32.0000000">

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-12">

                                <div class="form-group">

                                    <label for="registoPontoObservacoes">
                                        Observações
                                    </label>

                                    <textarea
                                        id="registoPontoObservacoes"
                                        name="observacoes"
                                        class="form-control"
                                        rows="3"
                                        maxlength="500"
                                        placeholder="Observações adicionais sobre o registo"></textarea>

                                </div>

                            </div>

                        </div>

                        <div
                            id="alertaRegistoPonto"
                            class="alert alert-danger"
                            style="display: none;"
                            role="alert">
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">

                            <i class="fa fa-times"></i>

                            Cancelar

                        </button>

                        <button
                            type="submit"
                            id="btnGuardarRegistoPonto"
                            class="btn btn-primary">

                            <i class="fa fa-save"></i>

                            Guardar

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <!-- ==================================================
     MODAL: MARCAR ASSIDUIDADE EM LOTE
================================================== -->
    <div
        id="modalAssiduidadeLote"
        class="modal custom-modal fade"
        tabindex="-1"
        role="dialog"
        aria-hidden="true">

        <div
            class="modal-dialog modal-dialog-centered modal-xl"
            role="document">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa fa-users mr-2"></i>
                        Marcar Assiduidade

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

                    <!-- =========================
                     DADOS GERAIS
                ========================== -->

                    <div class="card">

                        <div class="card-body">

                            <div class="row">

                                <div class="col-md-3">

                                    <div class="form-group">

                                        <label>
                                            Data *
                                        </label>

                                        <input
                                            type="date"
                                            id="assiduidadeLoteData"
                                            class="form-control">

                                    </div>

                                </div>


                                <div class="col-md-3">

                                    <div class="form-group">

                                        <label>
                                            Filial
                                        </label>

                                        <select
                                            id="assiduidadeLoteFilial"
                                            class="form-control">

                                            <option value="">
                                                Todas as filiais
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <div class="col-md-3">

                                    <div class="form-group">

                                        <label>
                                            Origem *
                                        </label>

                                        <select
                                            id="assiduidadeLoteOrigem"
                                            class="form-control">

                                        </select>

                                    </div>

                                </div>


                                <div class="col-md-3">

                                    <div class="form-group">

                                        <label>
                                            Dispositivo
                                        </label>

                                        <input
                                            type="text"
                                            id="assiduidadeLoteDispositivo"
                                            class="form-control"
                                            maxlength="150"
                                            placeholder="Ex.: Terminal RH-01">

                                    </div>

                                </div>

                            </div>


                            <div class="row">

                                <div class="col-md-3">

                                    <div class="form-group">

                                        <label>
                                            Identificador
                                        </label>

                                        <input
                                            type="text"
                                            id="assiduidadeLoteIdentificador"
                                            class="form-control"
                                            maxlength="150">

                                    </div>

                                </div>


                                <div class="col-md-9">

                                    <div class="form-group">

                                        <label>
                                            Observação geral
                                        </label>

                                        <input
                                            type="text"
                                            id="assiduidadeLoteObservacao"
                                            class="form-control"
                                            maxlength="500">

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- =========================
                     PREENCHIMENTO RÁPIDO
                ========================== -->

                    <div class="card">

                        <div class="card-header">

                            <h5 class="card-title mb-0">
                                Preenchimento rápido
                            </h5>

                        </div>

                        <div class="card-body">

                            <div class="row align-items-end">

                                <div class="col-md-2">

                                    <div class="form-group">

                                        <label>
                                            Entrada
                                        </label>

                                        <input
                                            type="time"
                                            id="loteEntradaPadrao"
                                            class="form-control">

                                    </div>

                                </div>


                                <div class="col-md-2">

                                    <div class="form-group">

                                        <label>
                                            Início almoço
                                        </label>

                                        <input
                                            type="time"
                                            id="loteInicioIntervaloPadrao"
                                            class="form-control">

                                    </div>

                                </div>


                                <div class="col-md-2">

                                    <div class="form-group">

                                        <label>
                                            Fim almoço
                                        </label>

                                        <input
                                            type="time"
                                            id="loteFimIntervaloPadrao"
                                            class="form-control">

                                    </div>

                                </div>


                                <div class="col-md-2">

                                    <div class="form-group">

                                        <label>
                                            Saída
                                        </label>

                                        <input
                                            type="time"
                                            id="loteSaidaPadrao"
                                            class="form-control">

                                    </div>

                                </div>


                                <div class="col-md-4">

                                    <div class="form-group">

                                        <button
                                            type="button"
                                            id="btnAplicarHorarioLote"
                                            class="btn btn-info btn-block">

                                            <i class="fa fa-clock-o"></i>

                                            Aplicar aos selecionados

                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- =========================
                     AÇÕES
                ========================== -->

                    <div class="row mb-3">

                        <div class="col-md-5">

                            <input
                                type="text"
                                id="pesquisaFuncionarioLote"
                                class="form-control"
                                placeholder="Pesquisar funcionário...">

                        </div>


                        <div class="col-md-7 text-right">

                            <button
                                type="button"
                                id="btnMarcarPresentesLote"
                                class="btn btn-success">

                                <i class="fa fa-check"></i>
                                Marcar presentes

                            </button>

                            <button
                                type="button"
                                id="btnMarcarFaltasLote"
                                class="btn btn-danger">

                                <i class="fa fa-times"></i>
                                Marcar falta

                            </button>

                            <button
                                type="button"
                                id="btnLimparHorasLote"
                                class="btn btn-secondary">

                                Limpar horários

                            </button>

                        </div>

                    </div>


                    <!-- =========================
                     FUNCIONÁRIOS
                ========================== -->

                    <div class="table-responsive">

                        <table
                            class="table table-bordered table-hover"
                            id="tabelaAssiduidadeLote">

                            <thead>

                                <tr>

                                    <th width="40">
                                        <input
                                            type="checkbox"
                                            id="selecionarTodosLote">
                                    </th>

                                    <th>
                                        Nº
                                    </th>

                                    <th style="min-width: 200px;">
                                        Funcionário
                                    </th>

                                    <th>
                                        Filial
                                    </th>

                                    <th width="120">
                                        Entrada
                                    </th>

                                    <th width="120">
                                        Início almoço
                                    </th>

                                    <th width="120">
                                        Fim almoço
                                    </th>

                                    <th width="120">
                                        Saída
                                    </th>

                                    <th width="140">
                                        Situação
                                    </th>

                                    <th style="min-width: 180px;">
                                        Observação
                                    </th>

                                </tr>

                            </thead>

                            <tbody id="listaFuncionariosLote">

                            </tbody>

                        </table>

                    </div>


                    <div
                        id="resumoAssiduidadeLote"
                        class="alert alert-light border mt-3">

                        0 funcionários

                    </div>


                    <div
                        id="alertaAssiduidadeLote"
                        class="alert alert-danger"
                        style="display:none;">

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
                        id="btnGuardarAssiduidadeLote"
                        class="btn btn-primary">

                        <i class="fa fa-save"></i>

                        Guardar Assiduidade

                    </button>

                </div>

            </div>

        </div>

    </div>


    <!-- ==================================================
     MODAL: JUSTIFICAR FALTA
================================================== -->
    <div
        id="modalJustificarFalta"
        class="modal custom-modal fade"
        tabindex="-1"
        role="dialog"
        aria-hidden="true">

        <div
            class="modal-dialog modal-dialog-centered modal-lg"
            role="document">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Justificar Falta
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

                <form
                    id="formJustificarFalta"
                    enctype="multipart/form-data">

                    <div class="modal-body">

                        <input
                            type="hidden"
                            id="justificarFaltaId"
                            name="id">

                        <div class="row">

                            <div class="col-md-8">

                                <div class="form-group">

                                    <label for="justificarFaltaFuncionario">
                                        Funcionário
                                    </label>

                                    <input
                                        type="text"
                                        id="justificarFaltaFuncionario"
                                        class="form-control"
                                        readonly>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="justificarFaltaData">
                                        Data
                                    </label>

                                    <input
                                        type="date"
                                        id="justificarFaltaData"
                                        class="form-control"
                                        readonly>

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label for="justificarFaltaTipo">
                                        Tipo de falta
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        id="justificarFaltaTipo"
                                        name="tipo_falta"
                                        class="form-control"
                                        required>

                                        <option value="">
                                            Selecione o tipo
                                        </option>

                                    </select>

                                    <div class="invalid-feedback">
                                        Selecione o tipo de falta.
                                    </div>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label for="justificarFaltaDocumento">
                                        Documento de justificação
                                    </label>

                                    <div
                                        id="justificarFaltaDocumentoAtual"
                                        class="mb-2">
                                    </div>

                                    <input
                                        type="file"
                                        id="justificarFaltaDocumento"
                                        name="documento"
                                        class="form-control"
                                        accept=".pdf,.jpg,.jpeg,.png">

                                    <small class="form-text text-muted">
                                        Formatos permitidos: PDF, JPG, JPEG e PNG. Máximo: 5 MB.
                                    </small>

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-12">

                                <div class="form-group">

                                    <label for="justificarFaltaJustificacao">
                                        Justificação
                                        <span class="text-danger">*</span>
                                    </label>

                                    <textarea
                                        id="justificarFaltaJustificacao"
                                        name="justificacao"
                                        class="form-control"
                                        rows="4"
                                        maxlength="1000"
                                        required
                                        placeholder="Descreva o motivo da falta"></textarea>

                                    <div class="invalid-feedback">
                                        Informe a justificação.
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label>
                                        Falta remunerada
                                    </label>

                                    <div class="custom-control custom-switch mt-2">

                                        <input
                                            type="checkbox"
                                            id="justificarFaltaRemunerada"
                                            name="remunerada"
                                            value="1"
                                            class="custom-control-input">

                                        <label
                                            class="custom-control-label"
                                            for="justificarFaltaRemunerada">

                                            Sim

                                        </label>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label>
                                        Desconta no salário
                                    </label>

                                    <div class="custom-control custom-switch mt-2">

                                        <input
                                            type="checkbox"
                                            id="justificarFaltaDescontaSalario"
                                            name="desconta_salario"
                                            value="1"
                                            class="custom-control-input">

                                        <label
                                            class="custom-control-label"
                                            for="justificarFaltaDescontaSalario">

                                            Sim

                                        </label>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="justificarFaltaDias">
                                        Dias considerados
                                    </label>

                                    <input
                                        type="number"
                                        id="justificarFaltaDias"
                                        name="dias"
                                        class="form-control"
                                        min="0"
                                        step="0.5"
                                        placeholder="1">

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-12">

                                <div class="form-group">

                                    <label for="justificarFaltaObservacoes">
                                        Observações
                                    </label>

                                    <textarea
                                        id="justificarFaltaObservacoes"
                                        name="observacoes"
                                        class="form-control"
                                        rows="3"
                                        maxlength="500"
                                        placeholder="Observações adicionais"></textarea>

                                </div>

                            </div>

                        </div>

                        <div
                            id="alertaJustificarFalta"
                            class="alert alert-danger"
                            style="display: none;"
                            role="alert">
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">

                            <i class="fa fa-times"></i>

                            Cancelar

                        </button>

                        <button
                            type="submit"
                            id="btnGuardarJustificacaoFalta"
                            class="btn btn-primary">

                            <i class="fa fa-save"></i>

                            Guardar justificação

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <!-- ==================================================
     MODAL: APROVAR HORA EXTRA
================================================== -->
    <div
        id="modalAprovarHoraExtra"
        class="modal custom-modal fade"
        tabindex="-1"
        role="dialog"
        aria-hidden="true">

        <div
            class="modal-dialog modal-dialog-centered modal-lg"
            role="document">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Aprovar Hora Extra
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

                <form id="formAprovarHoraExtra">

                    <div class="modal-body">

                        <input
                            type="hidden"
                            id="horaExtraId"
                            name="id">

                        <div class="row">

                            <div class="col-md-8">

                                <div class="form-group">

                                    <label for="aprovarHoraExtraFuncionario">
                                        Funcionário
                                    </label>

                                    <input
                                        type="text"
                                        id="horaExtraFuncionario"
                                        class="form-control"
                                        readonly>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="aprovarHoraExtraData">
                                        Data
                                    </label>

                                    <input
                                        type="date"
                                        id="horaExtraData"
                                        class="form-control"
                                        readonly>

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="aprovarHoraExtraTipo">
                                        Tipo
                                    </label>

                                    <input
                                        type="text"
                                        id="aprovarHoraExtraTipo"
                                        class="form-control"
                                        readonly>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="aprovarHoraExtraHorasApuradas">
                                        Horas apuradas
                                    </label>

                                    <input
                                        type="number"
                                        id="horaExtraMinutosApurados"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        readonly>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="aprovarHoraExtraHorasAprovadas">
                                        Horas aprovadas
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        id="horaExtraMinutosAprovados"
                                        name="horas_aprovadas"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        required>

                                    <div class="invalid-feedback">
                                        Informe as horas aprovadas.
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="aprovarHoraExtraPercentagem">
                                        Percentagem de acréscimo
                                    </label>

                                    <div class="input-group">

                                        <input
                                            type="number"
                                            id="horaExtraPercentualAcrescimo"
                                            name="percentagem"
                                            class="form-control"
                                            min="0"
                                            step="0.01"
                                            placeholder="0" readonly>

                                        <div class="input-group-append">

                                            <span class="input-group-text">
                                                %
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="aprovarHoraExtraValorHora">
                                        Valor por hora
                                    </label>

                                    <input
                                        type="number"
                                        id="horaExtraValorHora"
                                        name="valor_hora"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        placeholder="0,00" readonly>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label for="aprovarHoraExtraValorTotal">
                                        Valor total
                                    </label>

                                    <input
                                        type="number"
                                        id="horaExtraValorTotal"
                                        name="valor_total"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        readonly>

                                </div>

                            </div>

                        </div>

                        <div class="row">

                            <div class="col-md-12">

                                <div class="form-group">

                                    <label for="aprovarHoraExtraObservacoes">
                                        Observações
                                    </label>

                                    <textarea
                                        id="horaExtraObservacoes"
                                        name="observacoes"
                                        class="form-control"
                                        rows="3"
                                        maxlength="500"
                                        placeholder="Observações sobre a aprovação"></textarea>

                                </div>

                            </div>

                        </div>

                        <div
                            id="alertaAprovarHoraExtra"
                            class="alert alert-danger"
                            style="display: none;"
                            role="alert">
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">

                            <i class="fa fa-times"></i>

                            Cancelar

                        </button>

                        <button
                            type="submit"
                            id="btnConfirmarAprovacaoHoraExtra"
                            class="btn btn-success">

                            <i class="fa fa-check"></i>

                            Aprovar

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>