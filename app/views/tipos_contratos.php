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
                        Tipos de Contrato
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
                            Tipos de Contrato
                        </li>

                    </ul>

                </div>

                <!-- No cabeçalho da página (perto do botão Novo Tipo de Contrato) -->
                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="btnImportarTipoContrato">
                        <i class="fa fa-file-excel-o mr-1"></i>
                        Importar Excel
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoTipoContrato">
                        <i class="fa fa-plus mr-1"></i>
                        Novo Tipo de Contrato
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
                            Total de Tipos
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoTotalTiposContratos">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Tipos Activos
                        </span>

                        <h3
                            class="mt-2 mb-0 text-success"
                            id="resumoTiposContratosAtivos">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Duração Determinada
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoContratosDeterminados">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Sujeitos a INSS
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoContratosSujeitosInss">

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
            id="tiposContratosCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status"
                aria-hidden="true">
            </span>

            A carregar tipos de contrato...

        </div>


        <!-- =========================================
             TABELA
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Tipos de Contrato Registados
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="tiposContratosTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Tipo de Contrato
                                </th>

                                <th>
                                    Duração
                                </th>

                                <th class="text-center">
                                    Renovação
                                </th>

                                <th class="text-center">
                                    Período Experimental
                                </th>

                                <th class="text-center">
                                    Férias
                                </th>

                                <th class="text-center">
                                    INSS
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
     MODAL TIPO DE CONTRATO
========================================== -->

<div
    class="modal fade"
    id="modalTipoContrato"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalTipoContratoTitulo"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="modalTipoContratoTitulo">

                    Novo Tipo de Contrato

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


            <form id="formTipoContrato">

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="tipoContratoId"
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


                        <!-- CÓDIGO -->

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="tipoContratoCodigo">

                                    Código

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="tipoContratoCodigo"
                                    name="codigo"
                                    maxlength="20"
                                    autocomplete="off"
                                    placeholder="Ex.: DETERMINADO"
                                    required>

                                <small class="form-text text-muted">

                                    Utilize letras, números, ponto,
                                    hífen ou sublinhado.

                                </small>

                            </div>

                        </div>


                        <!-- NOME -->

                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="tipoContratoNome">

                                    Nome

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="tipoContratoNome"
                                    name="nome"
                                    maxlength="120"
                                    autocomplete="off"
                                    placeholder="Ex.: Contrato por Tempo Determinado"
                                    required>

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="tipoContratoStatusId">

                                    Estado

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control"
                                    id="tipoContratoStatusId"
                                    name="status_id"
                                    required>

                                    <option value="">
                                        Selecione o estado
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- DESCRIÇÃO -->

                        <div class="col-12">

                            <div class="form-group">

                                <label for="tipoContratoDescricao">
                                    Descrição
                                </label>

                                <textarea
                                    class="form-control"
                                    id="tipoContratoDescricao"
                                    name="descricao"
                                    rows="3"
                                    placeholder="Descreva as principais características deste tipo de contrato"></textarea>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         DURAÇÃO E RENOVAÇÃO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Duração e Renovação
                            </h5>

                        </div>


                        <!-- DURAÇÃO DETERMINADA -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label class="d-block">
                                    Tipo de Duração
                                </label>

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="tipoContratoDuracaoDeterminada"
                                        name="duracao_determinada"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="tipoContratoDuracaoDeterminada">

                                        Contrato com duração determinada

                                    </label>

                                </div>

                                <small class="form-text text-muted">

                                    Quando marcado, a duração em meses
                                    torna-se obrigatória.

                                </small>

                            </div>

                        </div>


                        <!-- DURAÇÃO EM MESES -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="tipoContratoDuracaoMeses">
                                    Duração
                                </label>

                                <div class="input-group">

                                    <input
                                        type="number"
                                        class="form-control"
                                        id="tipoContratoDuracaoMeses"
                                        name="duracao_meses"
                                        min="1"
                                        max="1200"
                                        disabled
                                        placeholder="12">

                                    <div class="input-group-append">

                                        <span class="input-group-text">
                                            meses
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- EXIGE DATA DE FIM -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label class="d-block">
                                    Data de Término
                                </label>

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="tipoContratoExigeDataFim"
                                        name="exige_data_fim"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="tipoContratoExigeDataFim">

                                        Exigir data final no contrato

                                    </label>

                                </div>

                            </div>

                        </div>


                        <!-- RENOVAÇÃO AUTOMÁTICA -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label class="d-block">
                                    Renovação
                                </label>

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="tipoContratoRenovacaoAutomatica"
                                        name="renovacao_automatica"
                                        value="1"
                                        disabled>

                                    <label
                                        class="form-check-label"
                                        for="tipoContratoRenovacaoAutomatica">

                                        Permitir renovação automática

                                    </label>

                                </div>

                            </div>

                        </div>


                        <!-- MÁXIMO DE RENOVAÇÕES -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="tipoContratoNumeroMaximoRenovacoes">
                                    Número Máximo de Renovações
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="tipoContratoNumeroMaximoRenovacoes"
                                    name="numero_maximo_renovacoes"
                                    min="0"
                                    max="255"
                                    disabled
                                    placeholder="2">

                            </div>

                        </div>


                        <!-- PERÍODO EXPERIMENTAL -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="tipoContratoPeriodoExperimentalDias">
                                    Período Experimental
                                </label>

                                <div class="input-group">

                                    <input
                                        type="number"
                                        class="form-control"
                                        id="tipoContratoPeriodoExperimentalDias"
                                        name="periodo_experimental_dias"
                                        min="0"
                                        max="3650"
                                        placeholder="90">

                                    <div class="input-group-append">

                                        <span class="input-group-text">
                                            dias
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- AVISO PRÉVIO -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="tipoContratoAvisoPrevioDias">
                                    Aviso Prévio
                                </label>

                                <div class="input-group">

                                    <input
                                        type="number"
                                        class="form-control"
                                        id="tipoContratoAvisoPrevioDias"
                                        name="aviso_previo_dias"
                                        min="0"
                                        max="3650"
                                        placeholder="30">

                                    <div class="input-group-append">

                                        <span class="input-group-text">
                                            dias
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         CONDIÇÕES DE TRABALHO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Condições de Trabalho
                            </h5>

                        </div>


                        <!-- TEMPO PARCIAL -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="tipoContratoPermiteTempoParcial"
                                        name="permite_tempo_parcial"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="tipoContratoPermiteTempoParcial">

                                        Permitir tempo parcial

                                    </label>

                                </div>

                            </div>

                        </div>


                        <!-- HORAS EXTRAS -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="tipoContratoPermiteHorasExtras"
                                        name="permite_horas_extras"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="tipoContratoPermiteHorasExtras">

                                        Permitir horas extras

                                    </label>

                                </div>

                            </div>

                        </div>


                        <!-- FÉRIAS -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="tipoContratoGeraFerias"
                                        name="gera_ferias"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="tipoContratoGeraFerias">

                                        Gerar direito a férias

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         SUBSÍDIOS
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Subsídios
                            </h5>

                        </div>


                        <!-- SUBSÍDIO DE FÉRIAS -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="tipoContratoGeraSubsidioFerias"
                                        name="gera_subsidio_ferias"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="tipoContratoGeraSubsidioFerias">

                                        Gerar subsídio de férias

                                    </label>

                                </div>

                            </div>

                        </div>


                        <!-- SUBSÍDIO DE NATAL -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="tipoContratoGeraSubsidioNatal"
                                        name="gera_subsidio_natal"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="tipoContratoGeraSubsidioNatal">

                                        Gerar subsídio de Natal

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         OBRIGAÇÕES FISCAIS E SOCIAIS
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Obrigações Fiscais e Sociais
                            </h5>

                        </div>


                        <!-- INSS -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="tipoContratoSujeitoInss"
                                        name="sujeito_inss"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="tipoContratoSujeitoInss">

                                        Sujeito a contribuição para INSS

                                    </label>

                                </div>

                            </div>

                        </div>


                        <!-- IRPS -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <div class="form-check">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="tipoContratoSujeitoIrps"
                                        name="sujeito_irps"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="tipoContratoSujeitoIrps">

                                        Sujeito a retenção de IRPS

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =====================================
                     RODAPÉ DO MODAL
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
                        id="btnGuardarTipoContrato">

                        <span
                            id="btnGuardarTipoContratoSpinner"
                            class="spinner-border spinner-border-sm mr-2"
                            role="status"
                            aria-hidden="true"
                            style="display:none;">
                        </span>

                        <span id="btnGuardarTipoContratoTexto">

                            Guardar Tipo de Contrato

                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- Modal Importação Excel de Tipos de Contrato -->
<div
    class="modal fade"
    id="modalImportarTipoContrato"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-file-excel-o mr-2"></i>
                    Importar Tipos de Contrato por Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="tipoContratoImportacaoErro" class="alert alert-danger d-none"></div>
                <div id="tipoContratoImportacaoSucesso" class="alert alert-success d-none"></div>

                <!-- PASSO 1 -->
                <div id="tipoContratoImportacaoPasso1">
                    <div class="alert alert-info">
                        <strong>Como funciona?</strong>
                        <ol class="mb-0 mt-2">
                            <li>Baixe o modelo Excel.</li>
                            <li>Preencha o código, nome, duração, subsídios e regras associadas.</li>
                            <li>Selecione o ficheiro.</li>
                            <li>Clique em <strong>Validar Excel</strong>.</li>
                            <li>Confira os erros e registos válidos.</li>
                            <li>Confirme a importação.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-success" id="btnModeloExcelTipoContrato">
                            <i class="fa fa-download mr-1"></i> Baixar modelo Excel
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="tipoContratoFicheiroExcel">
                            Ficheiro Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control-file" id="tipoContratoFicheiroExcel" accept=".xlsx,.xls">
                        <small class="form-text text-muted">Formatos permitidos: XLSX e XLS.</small>
                    </div>

                    <button type="button" class="btn btn-primary" id="btnValidarExcelTipoContrato">
                        <i class="fa fa-check mr-1"></i> Validar Excel
                    </button>
                </div>

                <!-- PASSO 2 -->
                <div id="tipoContratoImportacaoPasso2" class="d-none">
                    <div id="tipoContratoImportacaoErrosContainer" class="d-none mb-3">
                        <h5 class="text-danger"><i class="fa fa-warning mr-1"></i> Linhas com erro</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Código / Nome</th>
                                        <th>Erro</th>
                                    </tr>
                                </thead>
                                <tbody id="tipoContratoImportacaoErros"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="tipoContratoImportacaoValidosContainer" class="mt-4">
                        <h5 class="text-success"><i class="fa fa-check-circle mr-1"></i> Tipos de contrato prontos para importar</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Código</th>
                                        <th>Nome</th>
                                        <th>Duração</th>
                                        <th>Renovação</th>
                                        <th>INSS</th>
                                    </tr>
                                </thead>
                                <tbody id="tipoContratoImportacaoValidos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary d-none" id="btnVoltarImportacaoTipoContrato">
                    <i class="fa fa-arrow-left mr-1"></i> Voltar
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success d-none" id="btnConfirmarImportacaoTipoContrato">
                    <i class="fa fa-upload mr-1"></i> Importar Tipos Válidos
                </button>
            </div>
        </div>
    </div>
</div>