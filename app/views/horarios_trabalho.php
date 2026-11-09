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
                        Horários de Trabalho
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
                            Horários de Trabalho
                        </li>

                    </ul>

                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="btnImportarHorarioTrabalho">
                        <i class="fa fa-file-excel-o mr-1"></i>
                        Importar Excel
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoHorarioTrabalho">
                        <i class="fa fa-plus mr-1"></i>
                        Novo Horário
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
                            Total de Horários
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoTotalHorarios">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Horários Activos
                        </span>

                        <h3
                            class="mt-2 mb-0 text-success"
                            id="resumoHorariosAtivos">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Horários Flexíveis
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoHorariosFlexiveis">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Horários Noturnos
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoHorariosNoturnos">

                            0

                        </h3>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             CARREGAMENTO
        ========================================== -->

        <div
            id="horariosTrabalhoCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status"
                aria-hidden="true">
            </span>

            A carregar horários de trabalho...

        </div>


        <!-- =========================================
             TABELA
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Horários Registados
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="horariosTrabalhoTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Horário
                                </th>

                                <th>
                                    Período
                                </th>

                                <th>
                                    Intervalo
                                </th>

                                <th>
                                    Dias
                                </th>

                                <th class="text-center">
                                    Carga Diária
                                </th>

                                <th class="text-center">
                                    Carga Semanal
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
     MODAL HORÁRIO DE TRABALHO
========================================== -->

<div
    class="modal fade"
    id="modalHorarioTrabalho"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalHorarioTrabalhoTitulo"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="modalHorarioTrabalhoTitulo">

                    Novo Horário de Trabalho

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


            <form id="formHorarioTrabalho">

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="horarioId"
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

                                <label for="horarioCodigo">

                                    Código

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="horarioCodigo"
                                    name="codigo"
                                    maxlength="20"
                                    autocomplete="off"
                                    placeholder="Ex.: NORMAL"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="horarioNome">

                                    Nome

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="horarioNome"
                                    name="nome"
                                    maxlength="120"
                                    autocomplete="off"
                                    placeholder="Ex.: Horário Normal"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="horarioStatusId">

                                    Estado

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control"
                                    id="horarioStatusId"
                                    name="status_id"
                                    required>

                                    <option value="">
                                        Selecione o estado
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-12">

                            <div class="form-group">

                                <label for="horarioDescricao">
                                    Descrição
                                </label>

                                <textarea
                                    class="form-control"
                                    id="horarioDescricao"
                                    name="descricao"
                                    rows="3"
                                    placeholder="Descrição do horário e respetiva jornada de trabalho"></textarea>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         PERÍODO DE TRABALHO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Período de Trabalho
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="horarioHoraEntrada">

                                    Hora de Entrada

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="time"
                                    class="form-control"
                                    id="horarioHoraEntrada"
                                    name="hora_entrada"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="horarioHoraSaida">

                                    Hora de Saída

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="time"
                                    class="form-control"
                                    id="horarioHoraSaida"
                                    name="hora_saida"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="horarioIntervaloInicio">
                                    Início do Intervalo
                                </label>

                                <input
                                    type="time"
                                    class="form-control"
                                    id="horarioIntervaloInicio"
                                    name="intervalo_inicio">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="horarioIntervaloFim">
                                    Fim do Intervalo
                                </label>

                                <input
                                    type="time"
                                    class="form-control"
                                    id="horarioIntervaloFim"
                                    name="intervalo_fim">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="horarioToleranciaEntrada">
                                    Tolerância de Entrada
                                </label>

                                <div class="input-group">

                                    <input
                                        type="number"
                                        class="form-control"
                                        id="horarioToleranciaEntrada"
                                        name="tolerancia_entrada_minutos"
                                        min="0"
                                        max="1440"
                                        value="0">

                                    <div class="input-group-append">

                                        <span class="input-group-text">
                                            min
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="horarioToleranciaSaida">
                                    Tolerância de Saída
                                </label>

                                <div class="input-group">

                                    <input
                                        type="number"
                                        class="form-control"
                                        id="horarioToleranciaSaida"
                                        name="tolerancia_saida_minutos"
                                        min="0"
                                        max="1440"
                                        value="0">

                                    <div class="input-group-append">

                                        <span class="input-group-text">
                                            min
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="card bg-light">

                                <div class="card-body text-center py-3">

                                    <span class="text-muted">
                                        Carga Diária
                                    </span>

                                    <h4
                                        class="mb-0 mt-1"
                                        id="horarioCargaDiariaCalculada">

                                        0 h

                                    </h4>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="card bg-light">

                                <div class="card-body text-center py-3">

                                    <span class="text-muted">
                                        Carga Semanal
                                    </span>

                                    <h4
                                        class="mb-0 mt-1"
                                        id="horarioCargaSemanalCalculada">

                                        0 h

                                    </h4>

                                </div>

                            </div>

                        </div>


                        <div class="col-12">

                            <div
                                class="alert alert-warning"
                                id="avisoHorarioNoturno"
                                style="display:none;">

                                <i class="fa fa-exclamation-triangle mr-1"></i>

                                Este horário atravessa a meia-noite.
                                Marque a opção de trabalho noturno.

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         DIAS DE TRABALHO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Dias de Trabalho
                            </h5>

                        </div>


                        <div class="col-lg col-md-4 col-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="horarioSegundaFeira"
                                        name="segunda_feira"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="horarioSegundaFeira">

                                        Segunda-feira

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg col-md-4 col-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="horarioTercaFeira"
                                        name="terca_feira"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="horarioTercaFeira">

                                        Terça-feira

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg col-md-4 col-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="horarioQuartaFeira"
                                        name="quarta_feira"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="horarioQuartaFeira">

                                        Quarta-feira

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg col-md-4 col-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="horarioQuintaFeira"
                                        name="quinta_feira"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="horarioQuintaFeira">

                                        Quinta-feira

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg col-md-4 col-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="horarioSextaFeira"
                                        name="sexta_feira"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="horarioSextaFeira">

                                        Sexta-feira

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg col-md-4 col-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="horarioSabado"
                                        name="sabado"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="horarioSabado">

                                        Sábado

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg col-md-4 col-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="horarioDomingo"
                                        name="domingo"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="horarioDomingo">

                                        Domingo

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         CARACTERÍSTICAS
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Características do Horário
                            </h5>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="horarioPermiteHorasExtras"
                                        name="permite_horas_extras"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="horarioPermiteHorasExtras">

                                        Permitir horas extras

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="horarioPermiteTrabalhoNoturno"
                                        name="permite_trabalho_noturno"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="horarioPermiteTrabalhoNoturno">

                                        Permitir trabalho noturno

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="horarioFlexivel"
                                        name="horario_flexivel"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="horarioFlexivel">

                                        Horário flexível

                                    </label>

                                </div>

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
                        id="btnGuardarHorarioTrabalho">

                        <span
                            id="btnGuardarHorarioTrabalhoSpinner"
                            class="spinner-border spinner-border-sm mr-2"
                            role="status"
                            aria-hidden="true"
                            style="display:none;">
                        </span>

                        <span id="btnGuardarHorarioTrabalhoTexto">

                            Guardar Horário

                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
<!-- Modal Importação Excel de Horários -->
<div
    class="modal fade"
    id="modalImportarHorarioTrabalho"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-file-excel-o mr-2"></i>
                    Importar Horários de Trabalho por Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="horarioImportacaoErro" class="alert alert-danger d-none"></div>

                <!-- PASSO 1 -->
                <div id="horarioImportacaoPasso1">
                    <div class="alert alert-info">
                        <strong>Instruções de Importação:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Baixe o modelo Excel oficial.</li>
                            <li>Preencha os códigos, nomes, horas de entrada, saída, intervalos e dias aplicáveis.</li>
                            <li>Carregue o ficheiro preenchido.</li>
                            <li>Clique em <strong>Validar Excel</strong>.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-success" id="btnModeloExcelHorario">
                            <i class="fa fa-download mr-1"></i> Baixar modelo Excel
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="horarioFicheiroExcel">
                            Ficheiro Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control-file" id="horarioFicheiroExcel" accept=".xlsx,.xls">
                        <small class="form-text text-muted">Formatos aceites: XLSX e XLS.</small>
                    </div>

                    <button type="button" class="btn btn-primary" id="btnValidarExcelHorario">
                        <i class="fa fa-check mr-1"></i> Validar Excel
                    </button>
                </div>

                <!-- PASSO 2 -->
                <div id="horarioImportacaoPasso2" class="d-none">
                    <div id="horarioImportacaoErrosContainer" class="d-none mb-3">
                        <h5 class="text-danger"><i class="fa fa-warning mr-1"></i> Linhas com Erros</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Identificação</th>
                                        <th>Erros detetados</th>
                                    </tr>
                                </thead>
                                <tbody id="horarioImportacaoErros"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="horarioImportacaoValidosContainer" class="mt-4">
                        <h5 class="text-success"><i class="fa fa-check-circle mr-1"></i> Horários Prontos para Importar</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Código</th>
                                        <th>Nome</th>
                                        <th>Período</th>
                                        <th>Carga Diária</th>
                                        <th>Carga Semanal</th>
                                    </tr>
                                </thead>
                                <tbody id="horarioImportacaoValidos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary d-none" id="btnVoltarImportacaoHorario">
                    <i class="fa fa-arrow-left mr-1"></i> Voltar
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success d-none" id="btnConfirmarImportacaoHorario">
                    <i class="fa fa-upload mr-1"></i> Confirmar Importação
                </button>
            </div>
        </div>
    </div>
</div>