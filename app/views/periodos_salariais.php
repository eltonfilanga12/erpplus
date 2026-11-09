<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($_SESSION['csrf_token'])
) {
    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );
}

$csrfToken = $_SESSION['csrf_token'];

?>

<meta
    name="csrf-token"
    content="<?= htmlspecialchars(
        $csrfToken,
        ENT_QUOTES,
        'UTF-8'
    ); ?>"
>

<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- =====================================================
        CABEÇALHO
        ====================================================== -->
        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Períodos Salariais
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
                            Períodos Salariais
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoPeriodo"
                    >

                        <i class="fa fa-plus mr-1"></i>

                        <span class="d-none d-sm-inline">
                            Novo Período
                        </span>

                    </button>

                </div>

            </div>

        </div>

        <!-- =====================================================
        CARTÕES DE RESUMO
        ====================================================== -->
        <div class="row">

            <!-- Total -->
            <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span class="dash-widget-icon">

                            <i class="fa fa-calendar"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoTotal">
                                0
                            </h3>

                            <span>
                                Total
                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Abertos -->
            <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span
                            class="dash-widget-icon"
                            style="
                                background-color: #e7f3ff;
                                color: #0d6efd;
                            "
                        >

                            <i class="fa fa-folder-open"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoAbertos">
                                0
                            </h3>

                            <span>
                                Abertos
                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Em processamento -->
            <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span
                            class="dash-widget-icon"
                            style="
                                background-color: #fff3cd;
                                color: #856404;
                            "
                        >

                            <i class="fa fa-cogs"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoProcessamento">
                                0
                            </h3>

                            <span>
                                Em processamento
                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Processados -->
            <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span
                            class="dash-widget-icon"
                            style="
                                background-color: #d1ecf1;
                                color: #0c5460;
                            "
                        >

                            <i class="fa fa-check-circle"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoProcessados">
                                0
                            </h3>

                            <span>
                                Processados
                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Fechados -->
            <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span
                            class="dash-widget-icon"
                            style="
                                background-color: #d4edda;
                                color: #155724;
                            "
                        >

                            <i class="fa fa-lock"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoFechados">
                                0
                            </h3>

                            <span>
                                Fechados
                            </span>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Cancelados -->
            <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">

                <div class="card dash-widget">

                    <div class="card-body">

                        <span
                            class="dash-widget-icon"
                            style="
                                background-color: #f8d7da;
                                color: #721c24;
                            "
                        >

                            <i class="fa fa-ban"></i>

                        </span>

                        <div class="dash-widget-info">

                            <h3 id="resumoCancelados">
                                0
                            </h3>

                            <span>
                                Cancelados
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- =====================================================
        FILTROS
        ====================================================== -->
        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">

                    <i class="fa fa-filter mr-1"></i>

                    Filtros

                </h4>

            </div>

            <div class="card-body">

                <div class="row align-items-end">

                    <!-- Ano -->
                    <div class="col-xl-3 col-lg-3 col-md-6">

                        <div class="form-group">

                            <label for="filtroAno">
                                Ano
                            </label>

                            <select
                                class="form-control select2"
                                id="filtroAno"
                                name="filtro_ano"
                            >

                                <option value="">
                                    Todos os anos
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- Mês -->
                    <div class="col-xl-3 col-lg-3 col-md-6">

                        <div class="form-group">

                            <label for="filtroMes">
                                Mês
                            </label>

                            <select
                                class="form-control select2"
                                id="filtroMes"
                                name="filtro_mes"
                            >

                                <option value="">
                                    Todos os meses
                                </option>

                                <option value="1">
                                    Janeiro
                                </option>

                                <option value="2">
                                    Fevereiro
                                </option>

                                <option value="3">
                                    Março
                                </option>

                                <option value="4">
                                    Abril
                                </option>

                                <option value="5">
                                    Maio
                                </option>

                                <option value="6">
                                    Junho
                                </option>

                                <option value="7">
                                    Julho
                                </option>

                                <option value="8">
                                    Agosto
                                </option>

                                <option value="9">
                                    Setembro
                                </option>

                                <option value="10">
                                    Outubro
                                </option>

                                <option value="11">
                                    Novembro
                                </option>

                                <option value="12">
                                    Dezembro
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- Estado -->
                    <div class="col-xl-3 col-lg-3 col-md-6">

                        <div class="form-group">

                            <label for="filtroEstado">
                                Estado
                            </label>

                            <select
                                class="form-control select2"
                                id="filtroEstado"
                                name="filtro_estado"
                            >

                                <option value="">
                                    Todos os estados
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- Botões -->
                    <div class="col-xl-3 col-lg-3 col-md-6">

                        <div class="form-group">

                            <button
                                type="button"
                                class="btn btn-primary mr-2"
                                id="btnFiltrarPeriodos"
                            >

                                <i class="fa fa-search mr-1"></i>

                                Filtrar

                            </button>

                            <button
                                type="button"
                                class="btn btn-secondary"
                                id="btnLimparFiltros"
                            >

                                <i class="fa fa-eraser mr-1"></i>

                                Limpar

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- =====================================================
        TABELA
        ====================================================== -->
        <div class="card">

            <div class="card-header">

                <div class="row align-items-center">

                    <div class="col">

                        <h4 class="card-title mb-0">

                            <i class="fa fa-list mr-1"></i>

                            Lista de Períodos Salariais

                        </h4>

                    </div>

                    <div class="col-auto">

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            onclick="PeriodoSalarial.carregar();"
                        >

                            <i class="fa fa-refresh mr-1"></i>

                            Atualizar

                        </button>

                    </div>

                </div>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="periodosSalariaisTable"
                        class="
                            table
                            table-striped
                            table-hover
                            custom-table
                            mb-0
                        "
                    >

                        <thead>

                            <tr>

                                <th style="width: 50px;">
                                    #
                                </th>

                                <th>
                                    Código
                                </th>

                                <th class="text-center">
                                    Ano
                                </th>

                                <th class="text-center">
                                    Mês
                                </th>

                                <th>
                                    Período
                                </th>

                                <th class="text-center">
                                    Pagamento previsto
                                </th>

                                <th class="text-center">
                                    Dias úteis
                                </th>

                                <th class="text-center">
                                    Estado
                                </th>

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

<!-- =========================================================
MODAL: NOVO / EDITAR PERÍODO
========================================================== -->
<div
    class="modal fade"
    id="modalPeriodoSalarial"
    tabindex="-1"
    role="dialog"
    aria-labelledby="tituloModalPeriodo"
    aria-hidden="true"
>

    <div
        class="modal-dialog modal-xl modal-dialog-centered"
        role="document"
    >

        <div class="modal-content">

            <form
                id="formPeriodoSalarial"
                autocomplete="off"
                novalidate
            >

                <input
                    type="hidden"
                    id="periodo_id"
                    name="id"
                    value=""
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

                <!-- Cabeçalho -->
                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="tituloModalPeriodo"
                    >

                        Novo Período Salarial

                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Fechar"
                    >

                        <span aria-hidden="true">
                            &times;
                        </span>

                    </button>

                </div>

                <!-- Corpo -->
                <div class="modal-body">

                    <div class="row">

                        <!-- Código -->
                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="codigo">

                                    Código

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="codigo"
                                    name="codigo"
                                    maxlength="30"
                                    placeholder="Ex.: PS-2026-07"
                                    required
                                >

                                <small class="form-text text-muted">

                                    Utilize letras, números, ponto,
                                    hífen, barra ou sublinhado.

                                </small>

                                <div class="invalid-feedback"></div>

                            </div>

                        </div>

                        <!-- Ano -->
                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="ano">

                                    Ano

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="ano"
                                    name="ano"
                                    min="2000"
                                    max="2100"
                                    required
                                >

                                <div class="invalid-feedback"></div>

                            </div>

                        </div>

                        <!-- Mês -->
                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="mes">

                                    Mês

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control select2"
                                    id="mes"
                                    name="mes"
                                    required
                                >

                                    <option value="">
                                        Selecione o mês
                                    </option>

                                    <option value="1">
                                        Janeiro
                                    </option>

                                    <option value="2">
                                        Fevereiro
                                    </option>

                                    <option value="3">
                                        Março
                                    </option>

                                    <option value="4">
                                        Abril
                                    </option>

                                    <option value="5">
                                        Maio
                                    </option>

                                    <option value="6">
                                        Junho
                                    </option>

                                    <option value="7">
                                        Julho
                                    </option>

                                    <option value="8">
                                        Agosto
                                    </option>

                                    <option value="9">
                                        Setembro
                                    </option>

                                    <option value="10">
                                        Outubro
                                    </option>

                                    <option value="11">
                                        Novembro
                                    </option>

                                    <option value="12">
                                        Dezembro
                                    </option>

                                </select>

                                <div class="invalid-feedback"></div>

                            </div>

                        </div>

                        <!-- Dias úteis -->
                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="dias_uteis">
                                    Dias úteis
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="dias_uteis"
                                    name="dias_uteis"
                                    min="0"
                                    max="31"
                                    placeholder="Ex.: 22"
                                >

                                <div class="invalid-feedback"></div>

                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <!-- Descrição -->
                        <div class="col-12">

                            <div class="form-group">

                                <label for="descricao">
                                    Descrição
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="descricao"
                                    name="descricao"
                                    maxlength="150"
                                    placeholder="
                                        Ex.: Processamento salarial de Julho de 2026
                                    "
                                >

                                <div class="invalid-feedback"></div>

                            </div>

                        </div>

                    </div>

                    <hr>

                    <h5 class="mb-3">

                        <i class="fa fa-calendar mr-1"></i>

                        Datas do período

                    </h5>

                    <div class="row">

                        <!-- Data início -->
                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="data_inicio">

                                    Data de início

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="data_inicio"
                                    name="data_inicio"
                                    required
                                >

                                <div class="invalid-feedback"></div>

                            </div>

                        </div>

                        <!-- Data fim -->
                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="data_fim">

                                    Data de fim

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="data_fim"
                                    name="data_fim"
                                    required
                                >

                                <div class="invalid-feedback"></div>

                            </div>

                        </div>

                        <!-- Limite assiduidade -->
                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="data_limite_assiduidade">
                                    Limite da assiduidade
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="data_limite_assiduidade"
                                    name="data_limite_assiduidade"
                                >

                                <div class="invalid-feedback"></div>

                            </div>

                        </div>

                        <!-- Pagamento previsto -->
                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="data_prevista_pagamento">
                                    Pagamento previsto
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="data_prevista_pagamento"
                                    name="data_prevista_pagamento"
                                >

                                <div class="invalid-feedback"></div>

                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <!-- Observações -->
                        <div class="col-12">

                            <div class="form-group">

                                <label for="observacoes">
                                    Observações
                                </label>

                                <textarea
                                    class="form-control"
                                    id="observacoes"
                                    name="observacoes"
                                    rows="4"
                                    maxlength="1000"
                                    placeholder="
                                        Introduza alguma observação relevante...
                                    "
                                ></textarea>

                                <div class="invalid-feedback"></div>

                            </div>

                        </div>

                    </div>

                    <div class="alert alert-info mb-0">

                        <i class="fa fa-info-circle mr-1"></i>

                        O período salarial define o intervalo utilizado
                        para o processamento dos salários, assiduidade,
                        descontos, subsídios e pagamentos.

                    </div>

                </div>

                <!-- Rodapé -->
                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal"
                    >

                        <i class="fa fa-times mr-1"></i>

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnGuardarPeriodo"
                    >

                        <i class="fa fa-save mr-1"></i>

                        Guardar

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- =========================================================
MODAL: CANCELAR PERÍODO
========================================================== -->
<div
    class="modal fade"
    id="modalCancelarPeriodo"
    tabindex="-1"
    role="dialog"
    aria-labelledby="tituloModalCancelarPeriodo"
    aria-hidden="true"
>

    <div
        class="modal-dialog modal-dialog-centered"
        role="document"
    >

        <div class="modal-content">

            <form
                id="formCancelarPeriodo"
                autocomplete="off"
                novalidate
            >

                <input
                    type="hidden"
                    id="cancelar_periodo_id"
                    name="id"
                    value=""
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

                <!-- Cabeçalho -->
                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="tituloModalCancelarPeriodo"
                    >

                        <i class="fa fa-ban text-danger mr-1"></i>

                        Cancelar Período Salarial

                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Fechar"
                    >

                        <span aria-hidden="true">
                            &times;
                        </span>

                    </button>

                </div>

                <!-- Corpo -->
                <div class="modal-body">

                    <div class="alert alert-warning">

                        <i class="fa fa-exclamation-triangle mr-1"></i>

                        Esta operação irá cancelar o período salarial.
                        Informe o motivo do cancelamento.

                    </div>

                    <div class="form-group">

                        <label for="motivo_cancelamento">

                            Motivo do cancelamento

                            <span class="text-danger">
                                *
                            </span>

                        </label>

                        <textarea
                            class="form-control"
                            id="motivo_cancelamento"
                            name="motivo_cancelamento"
                            rows="5"
                            minlength="5"
                            maxlength="500"
                            placeholder="
                                Descreva o motivo do cancelamento...
                            "
                            required
                        ></textarea>

                        <div class="invalid-feedback"></div>

                    </div>

                </div>

                <!-- Rodapé -->
                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal"
                    >

                        <i class="fa fa-times mr-1"></i>

                        Voltar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger"
                        id="btnConfirmarCancelamento"
                    >

                        <i class="fa fa-ban mr-1"></i>

                        Confirmar cancelamento

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- =========================================================
ESTILOS DO MÓDULO
========================================================== -->
<style>

    .dash-widget {
        min-height: 115px;
    }

    .dash-widget-info h3 {
        font-size: 24px;
        margin-bottom: 3px;
    }

    .dash-widget-info span {
        font-size: 13px;
    }

    .dash-widget-icon {
        align-items: center;
        border-radius: 50%;
        display: inline-flex;
        font-size: 20px;
        height: 50px;
        justify-content: center;
        width: 50px;
    }

    #periodosSalariaisTable td {
        vertical-align: middle;
    }

    #periodosSalariaisTable .badge {
        font-size: 12px;
        font-weight: 500;
        padding: 6px 10px;
    }

    #periodosSalariaisTable .dropdown-menu {
        min-width: 210px;
    }

    #periodosSalariaisTable .dropdown-item {
        cursor: pointer;
        font-size: 14px;
        padding: 9px 15px;
    }

    #periodosSalariaisTable .dropdown-item i {
        min-width: 18px;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container--default
    .select2-selection--single {
        border: 1px solid #d7dce1;
        border-radius: 4px;
        height: 42px;
    }

    .select2-container--default
    .select2-selection--single
    .select2-selection__rendered {
        line-height: 40px;
        padding-left: 12px;
    }

    .select2-container--default
    .select2-selection--single
    .select2-selection__arrow {
        height: 40px;
    }

    .modal .form-group label {
        font-weight: 500;
    }

    .modal textarea {
        resize: vertical;
    }

    @media (max-width: 767px) {

        .modal-dialog.modal-xl {
            margin: 10px;
        }

        .card-body {
            padding: 15px;
        }

        #periodosSalariaisTable {
            min-width: 1000px;
        }

    }

</style>
