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
                        Contratos de Trabalho
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
                            Contratos de Trabalho
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoContratoTrabalho">

                        <i class="fa fa-plus mr-1"></i>

                        Novo Contrato

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
                            Total de Contratos
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoTotalContratos">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Contratos Activos
                        </span>

                        <h3
                            class="mt-2 mb-0 text-success"
                            id="resumoContratosAtivos">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Por Renovar
                        </span>

                        <h3
                            class="mt-2 mb-0 text-warning"
                            id="resumoContratosPorRenovar">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Contratos Expirados
                        </span>

                        <h3
                            class="mt-2 mb-0 text-danger"
                            id="resumoContratosExpirados">

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

                    <div class="col-xl-3 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroContratoPesquisa">
                                Pesquisa
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="filtroContratoPesquisa"
                                placeholder="Número, referência ou funcionário">

                        </div>

                    </div>


                    <div class="col-xl-3 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroContratoFuncionarioId">
                                Funcionário
                            </label>

                            <select
                                class="form-control"
                                id="filtroContratoFuncionarioId">

                                <option value="">
                                    Todos os funcionários
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroContratoTipoId">
                                Tipo
                            </label>

                            <select
                                class="form-control"
                                id="filtroContratoTipoId">

                                <option value="">
                                    Todos os tipos
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroContratoFilialId">
                                Filial
                            </label>

                            <select
                                class="form-control"
                                id="filtroContratoFilialId">

                                <option value="">
                                    Todas as filiais
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroContratoDepartamentoId">
                                Departamento
                            </label>

                            <select
                                class="form-control"
                                id="filtroContratoDepartamentoId">

                                <option value="">
                                    Todos os departamentos
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroContratoStatusId">
                                Estado
                            </label>

                            <select
                                class="form-control"
                                id="filtroContratoStatusId">

                                <option value="">
                                    Todos os estados
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroContratoDataInicio">
                                Início do Período
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                id="filtroContratoDataInicio">

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroContratoDataFim">
                                Fim do Período
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                id="filtroContratoDataFim">

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-4 col-md-6">

                        <div class="form-group">

                            <div class="btn-group w-100">

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    id="btnFiltrarContratosTrabalho">

                                    <i class="fa fa-search mr-1"></i>

                                    Filtrar

                                </button>

                                <button
                                    type="button"
                                    class="btn btn-light"
                                    id="btnLimparFiltrosContratosTrabalho"
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
            id="contratosTrabalhoCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status"
                aria-hidden="true">
            </span>

            A carregar contratos de trabalho...

        </div>


        <!-- =========================================
             TABELA
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Contratos Registados
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="contratosTrabalhoTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Contrato
                                </th>

                                <th>
                                    Funcionário
                                </th>

                                <th>
                                    Tipo
                                </th>

                                <th>
                                    Cargo
                                </th>

                                <th>
                                    Início
                                </th>

                                <th>
                                    Fim
                                </th>

                                <th>
                                    Salário Base
                                </th>

                                <th class="text-center">
                                    Renovações
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
     MODAL CONTRATO
========================================== -->

<div
    class="modal fade"
    id="modalContratoTrabalho"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalContratoTrabalhoTitulo"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="modalContratoTrabalhoTitulo">

                    Novo Contrato de Trabalho

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


            <form id="formContratoTrabalho">

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="contratoId"
                        name="id">


                    <!-- =================================
                         IDENTIFICAÇÃO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Identificação do Contrato
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoNumero">

                                    Número do Contrato

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="contratoNumero"
                                    name="numero_contrato"
                                    maxlength="40"
                                    autocomplete="off"
                                    placeholder="Ex.: CONT-2026-0001"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoReferenciaExterna">
                                    Referência Externa
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="contratoReferenciaExterna"
                                    name="referencia_externa"
                                    maxlength="80"
                                    placeholder="Referência opcional">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoStatusId">

                                    Estado

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control"
                                    id="contratoStatusId"
                                    name="status_id"
                                    required>

                                    <option value="">
                                        Selecione o estado
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoDocumento">
                                    Documento do Contrato
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="contratoDocumento"
                                    name="documento_contrato"
                                    maxlength="255"
                                    placeholder="/uploads/contratos/contrato.pdf">

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         FUNCIONÁRIO E VÍNCULO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Funcionário e Vínculo
                            </h5>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="contratoFuncionarioId">

                                    Funcionário

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control"
                                    id="contratoFuncionarioId"
                                    name="funcionario_id"
                                    required>

                                    <option value="">
                                        Selecione o funcionário
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="contratoTipoContratoId">

                                    Tipo de Contrato

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control"
                                    id="contratoTipoContratoId"
                                    name="tipo_contrato_id"
                                    required>

                                    <option value="">
                                        Selecione o tipo de contrato
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="contratoFilialId"
                                    name="filial_id">

                                    <option value="">
                                        Sem filial definida
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoDepartamentoId">
                                    Departamento
                                </label>

                                <select
                                    class="form-control"
                                    id="contratoDepartamentoId"
                                    name="departamento_id">

                                    <option value="">
                                        Sem departamento
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoCargoId">
                                    Cargo
                                </label>

                                <select
                                    class="form-control"
                                    id="contratoCargoId"
                                    name="cargo_id">

                                    <option value="">
                                        Sem cargo definido
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoHorarioId">
                                    Horário de Trabalho
                                </label>

                                <select
                                    class="form-control"
                                    id="contratoHorarioId"
                                    name="horario_trabalho_id">

                                    <option value="">
                                        Sem horário definido
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         PERÍODO DO CONTRATO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Período do Contrato
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoDataAssinatura">
                                    Data de Assinatura
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="contratoDataAssinatura"
                                    name="data_assinatura">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoDataInicio">

                                    Data de Início

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="contratoDataInicio"
                                    name="data_inicio"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoDataFim">
                                    Data de Fim
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="contratoDataFim"
                                    name="data_fim">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoAvisoPrevioDias">
                                    Aviso Prévio
                                </label>

                                <div class="input-group">

                                    <input
                                        type="number"
                                        class="form-control"
                                        id="contratoAvisoPrevioDias"
                                        name="aviso_previo_dias"
                                        min="0"
                                        max="3650"
                                        value="0">

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
                         PERÍODO EXPERIMENTAL
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Período Experimental
                            </h5>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="contratoPeriodoExperimentalInicio">
                                    Data de Início
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="contratoPeriodoExperimentalInicio"
                                    name="periodo_experimental_inicio">

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="contratoPeriodoExperimentalFim">
                                    Data de Fim
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="contratoPeriodoExperimentalFim"
                                    name="periodo_experimental_fim">

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         RENOVAÇÃO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Renovação
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label class="d-block">
                                    Renovação Automática
                                </label>

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="contratoRenovacaoAutomatica"
                                        name="renovacao_automatica"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="contratoRenovacaoAutomatica">

                                        Renovar automaticamente

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoNumeroRenovacoes">
                                    Renovações Realizadas
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="contratoNumeroRenovacoes"
                                    name="numero_renovacoes"
                                    min="0"
                                    value="0">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoNumeroMaximoRenovacoes">
                                    Máximo de Renovações
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="contratoNumeroMaximoRenovacoes"
                                    name="numero_maximo_renovacoes"
                                    min="0"
                                    disabled>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoDataProximaRenovacao">
                                    Próxima Renovação
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="contratoDataProximaRenovacao"
                                    name="data_proxima_renovacao"
                                    disabled>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         REMUNERAÇÃO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Remuneração e Carga Horária
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoSalarioBase">
                                    Salário Base
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="contratoSalarioBase"
                                    name="salario_base"
                                    min="0"
                                    step="0.01"
                                    value="0.00">

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="contratoMoeda">
                                    Moeda
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="contratoMoeda"
                                    name="moeda"
                                    maxlength="3"
                                    value="MZN">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoPeriodicidadeSalario">
                                    Periodicidade
                                </label>

                                <select
                                    class="form-control"
                                    id="contratoPeriodicidadeSalario"
                                    name="periodicidade_salario">

                                    <option value="">
                                        Selecione
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="contratoHorasSemanais">
                                    Horas Semanais
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="contratoHorasSemanais"
                                    name="horas_semanais"
                                    min="0"
                                    max="168"
                                    step="0.01">

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="contratoLocalTrabalho">
                                    Local de Trabalho
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="contratoLocalTrabalho"
                                    name="local_trabalho"
                                    maxlength="255">

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         FUNÇÕES E CONDIÇÕES
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Funções e Condições Contratuais
                            </h5>

                        </div>


                        <div class="col-lg-6">

                            <div class="form-group">

                                <label for="contratoDescricaoFuncoes">
                                    Descrição das Funções
                                </label>

                                <textarea
                                    class="form-control"
                                    id="contratoDescricaoFuncoes"
                                    name="descricao_funcoes"
                                    rows="4"></textarea>

                            </div>

                        </div>


                        <div class="col-lg-6">

                            <div class="form-group">

                                <label for="contratoBeneficios">
                                    Benefícios
                                </label>

                                <textarea
                                    class="form-control"
                                    id="contratoBeneficios"
                                    name="beneficios"
                                    rows="4"></textarea>

                            </div>

                        </div>


                        <div class="col-lg-6">

                            <div class="form-group">

                                <label for="contratoClausulasEspeciais">
                                    Cláusulas Especiais
                                </label>

                                <textarea
                                    class="form-control"
                                    id="contratoClausulasEspeciais"
                                    name="clausulas_especiais"
                                    rows="4"></textarea>

                            </div>

                        </div>


                        <div class="col-lg-6">

                            <div class="form-group">

                                <label for="contratoObservacoes">
                                    Observações
                                </label>

                                <textarea
                                    class="form-control"
                                    id="contratoObservacoes"
                                    name="observacoes"
                                    rows="4"></textarea>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         SUSPENSÃO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Suspensão
                            </h5>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="contratoDataSuspensao">
                                    Data de Suspensão
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="contratoDataSuspensao"
                                    name="data_suspensao"
                                    disabled>

                            </div>

                        </div>


                        <div class="col-lg-8 col-md-6">

                            <div class="form-group">

                                <label for="contratoMotivoSuspensao">
                                    Motivo da Suspensão
                                </label>

                                <textarea
                                    class="form-control"
                                    id="contratoMotivoSuspensao"
                                    name="motivo_suspensao"
                                    rows="3"
                                    disabled></textarea>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         RESCISÃO OU TÉRMINO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Rescisão ou Término
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoDataRescisao">
                                    Data
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="contratoDataRescisao"
                                    name="data_rescisao"
                                    disabled>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="contratoIniciativaRescisao">
                                    Iniciativa
                                </label>

                                <select
                                    class="form-control"
                                    id="contratoIniciativaRescisao"
                                    name="iniciativa_rescisao"
                                    disabled>

                                    <option value="">
                                        Selecione
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-12">

                            <div class="form-group">

                                <label for="contratoMotivoRescisao">
                                    Motivo
                                </label>

                                <textarea
                                    class="form-control"
                                    id="contratoMotivoRescisao"
                                    name="motivo_rescisao"
                                    rows="3"
                                    disabled></textarea>

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
                        id="btnGuardarContratoTrabalho">

                        <span
                            id="btnGuardarContratoTrabalhoSpinner"
                            class="spinner-border spinner-border-sm mr-2"
                            role="status"
                            aria-hidden="true"
                            style="display:none;">
                        </span>

                        <span id="btnGuardarContratoTrabalhoTexto">
                            Guardar Contrato
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>