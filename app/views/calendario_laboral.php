<?php

declare(strict_types=1);

Auth::requireLogin();

?>

<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- =========================================
             CABEÇALHO
        ========================================== -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Calendário Laboral
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
                            Calendário Laboral
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoEventoLaboral">

                        <i class="fa fa-plus mr-1"></i>

                        Novo Evento

                    </button>

                </div>

            </div>

        </div>


        <!-- =========================================
             RESUMO
        ========================================== -->

        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Total de Eventos
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoTotalEventosLaborais">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Feriados
                        </span>

                        <h3
                            class="mt-2 mb-0 text-danger"
                            id="resumoFeriadosLaborais">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Dias Não Úteis
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoDiasNaoUteis">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Eventos Recorrentes
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoEventosRecorrentes">

                            0

                        </h3>

                    </div>

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

                <div class="row align-items-end">

                    <div class="col-xl-2 col-lg-3 col-md-6">

                        <div class="form-group">

                            <label for="filtroCalendarioDataInicio">
                                Data Inicial
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                id="filtroCalendarioDataInicio">

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-3 col-md-6">

                        <div class="form-group">

                            <label for="filtroCalendarioDataFim">
                                Data Final
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                id="filtroCalendarioDataFim">

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-3 col-md-6">

                        <div class="form-group">

                            <label for="filtroCalendarioFilialId">
                                Filial
                            </label>

                            <select
                                class="form-control"
                                id="filtroCalendarioFilialId">

                                <option value="">
                                    Todas as filiais
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-3 col-md-6">

                        <div class="form-group">

                            <label for="filtroCalendarioTipoEvento">
                                Tipo
                            </label>

                            <select
                                class="form-control"
                                id="filtroCalendarioTipoEvento">

                                <option value="">
                                    Todos os tipos
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-3 col-md-6">

                        <div class="form-group">

                            <label for="filtroCalendarioStatusId">
                                Estado
                            </label>

                            <select
                                class="form-control"
                                id="filtroCalendarioStatusId">

                                <option value="">
                                    Todos os estados
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-3 col-md-6">

                        <div class="form-group">

                            <div class="btn-group w-100">

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    id="btnFiltrarCalendarioLaboral">

                                    <i class="fa fa-search mr-1"></i>

                                    Filtrar

                                </button>

                                <button
                                    type="button"
                                    class="btn btn-light"
                                    id="btnLimparFiltrosCalendarioLaboral"
                                    title="Limpar filtros">

                                    <i class="fa fa-eraser"></i>

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             CARREGAMENTO
        ========================================== -->

        <div
            id="calendarioLaboralCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status"
                aria-hidden="true">
            </span>

            A carregar calendário laboral...

        </div>


        <!-- =========================================
             TABELA
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Eventos Laborais
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="calendarioLaboralTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Evento
                                </th>

                                <th>
                                    Tipo
                                </th>

                                <th>
                                    Período
                                </th>

                                <th>
                                    Filial
                                </th>

                                <th class="text-center">
                                    Dia Útil
                                </th>

                                <th class="text-center">
                                    Remunerado
                                </th>

                                <th class="text-center">
                                    Trabalho
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th class="text-right">
                                    Acções
                                </th>

                            </tr>

                        </thead>

                        <tbody>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =========================================
     MODAL EVENTO LABORAL
========================================== -->

<div
    class="modal fade"
    id="modalCalendarioLaboral"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalCalendarioLaboralTitulo"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="modalCalendarioLaboralTitulo">

                    Novo Evento Laboral

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


            <form id="formCalendarioLaboral">

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="calendarioId"
                        name="id">


                    <!-- =================================
                         IDENTIFICAÇÃO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Identificação
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="calendarioCodigo">

                                    Código

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="calendarioCodigo"
                                    name="codigo"
                                    maxlength="30"
                                    autocomplete="off"
                                    placeholder="Ex.: FER-NATAL-2026"
                                    required>

                                <small class="form-text text-muted">
                                    Utilize letras, números, ponto, hífen ou sublinhado.
                                </small>

                            </div>

                        </div>


                        <div class="col-lg-5 col-md-6">

                            <div class="form-group">

                                <label for="calendarioTitulo">

                                    Título

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="calendarioTitulo"
                                    name="titulo"
                                    maxlength="150"
                                    autocomplete="off"
                                    placeholder="Ex.: Dia da Família"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="calendarioTipoEvento">

                                    Tipo

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control"
                                    id="calendarioTipoEvento"
                                    name="tipo_evento"
                                    required>

                                    <option value="">
                                        Selecione
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="calendarioStatusId">

                                    Estado

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control"
                                    id="calendarioStatusId"
                                    name="status_id"
                                    required>

                                    <option value="">
                                        Selecione
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="calendarioFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="calendarioFilialId"
                                    name="filial_id">

                                    <option value="">
                                        Todas as filiais
                                    </option>

                                </select>

                                <small class="form-text text-muted">
                                    Deixe vazio para aplicar o evento a toda a empresa.
                                </small>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="calendarioCor">
                                    Cor do Evento
                                </label>

                                <input
                                    type="color"
                                    class="form-control"
                                    id="calendarioCor"
                                    name="cor"
                                    value="#dc3545"
                                    style="height:38px;">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label class="d-block">
                                    Aplicação
                                </label>

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="calendarioAplicaTodosFuncionarios"
                                        name="aplica_todos_funcionarios"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="calendarioAplicaTodosFuncionarios">

                                        Aplicar a todos os funcionários

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-12">

                            <div class="form-group">

                                <label for="calendarioDescricao">
                                    Descrição
                                </label>

                                <textarea
                                    class="form-control"
                                    id="calendarioDescricao"
                                    name="descricao"
                                    rows="3"
                                    placeholder="Descrição do evento laboral"></textarea>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         PERÍODO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Período do Evento
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="calendarioDataInicio">

                                    Data Inicial

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="calendarioDataInicio"
                                    name="data_inicio"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="calendarioDataFim">

                                    Data Final

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="calendarioDataFim"
                                    name="data_fim"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label class="d-block">
                                    Duração
                                </label>

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="calendarioDiaInteiro"
                                        name="dia_inteiro"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="calendarioDiaInteiro">

                                        Evento de dia inteiro

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="calendarioHoraInicio">
                                    Hora Inicial
                                </label>

                                <input
                                    type="time"
                                    class="form-control"
                                    id="calendarioHoraInicio"
                                    name="hora_inicio"
                                    disabled>

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="calendarioHoraFim">
                                    Hora Final
                                </label>

                                <input
                                    type="time"
                                    class="form-control"
                                    id="calendarioHoraFim"
                                    name="hora_fim"
                                    disabled>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         REGRAS LABORAIS
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Regras Laborais
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="calendarioDiaUtil"
                                        name="dia_util"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="calendarioDiaUtil">

                                        Considerar como dia útil

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="calendarioRemunerado"
                                        name="remunerado"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="calendarioRemunerado">

                                        Evento remunerado

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="calendarioDescontaFerias"
                                        name="desconta_ferias"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="calendarioDescontaFerias">

                                        Descontar das férias

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="calendarioRecorrenteAnual"
                                        name="recorrente_anual"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="calendarioRecorrenteAnual">

                                        Repetir anualmente

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="calendarioPermiteTrabalho"
                                        name="permite_trabalho"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="calendarioPermiteTrabalho">

                                        Permitir trabalho neste evento

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="calendarioExigeCompensacao"
                                        name="exige_compensacao"
                                        value="1"
                                        disabled>

                                    <label
                                        class="form-check-label"
                                        for="calendarioExigeCompensacao">

                                        Exigir compensação

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         OBSERVAÇÕES
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <div class="form-group mb-0">

                                <label for="calendarioObservacoes">
                                    Observações
                                </label>

                                <textarea
                                    class="form-control"
                                    id="calendarioObservacoes"
                                    name="observacoes"
                                    rows="4"
                                    placeholder="Informações adicionais sobre o evento"></textarea>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =====================================
                     RODAPÉ
                ====================================== -->

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
                        id="btnGuardarCalendarioLaboral">

                        <span
                            id="btnGuardarCalendarioLaboralSpinner"
                            class="spinner-border spinner-border-sm mr-2"
                            role="status"
                            aria-hidden="true"
                            style="display:none;">
                        </span>

                        <span id="btnGuardarCalendarioLaboralTexto">
                            Guardar Evento
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>