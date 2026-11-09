<?php

declare(strict_types=1);

Auth::requireLogin();

?>

<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- =================================================
             CABEÇALHO
        ================================================== -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Exercícios Contabilísticos
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>

                        <li class="breadcrumb-item">
                            Contabilidade
                        </li>

                        <li class="breadcrumb-item active">
                            Exercícios Contabilísticos
                        </li>

                    </ul>

                </div>


                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoExercicio">

                        <i class="fa fa-plus"></i>

                        Novo Exercício

                    </button>

                </div>

            </div>

        </div>


        <!-- =================================================
             RESUMO
        ================================================== -->

        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoTotalExercicios">
                            0
                        </h3>

                        <span class="text-muted">
                            Total de Exercícios
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoExerciciosAbertos">
                            0
                        </h3>

                        <span class="text-muted">
                            Exercícios Abertos
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoExerciciosBloqueados">
                            0
                        </h3>

                        <span class="text-muted">
                            Exercícios Bloqueados
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoExerciciosEncerrados">
                            0
                        </h3>

                        <span class="text-muted">
                            Exercícios Encerrados
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             FILTROS
        ================================================== -->

        <div class="card">

            <div class="card-body">

                <div class="row align-items-end">

                    <div class="col-md-5">

                        <div class="form-group mb-md-0">

                            <label for="pesquisaExercicio">
                                Pesquisar
                            </label>

                            <input
                                type="search"
                                class="form-control"
                                id="pesquisaExercicio"
                                placeholder="Código ou descrição">

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="form-group mb-md-0">

                            <label for="filtroStatusExercicio">
                                Status
                            </label>

                            <select
                                class="form-control"
                                id="filtroStatusExercicio">

                                <option value="">
                                    Todos os status
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group mb-0">

                            <label>&nbsp;</label>

                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-block"
                                id="btnLimparFiltrosExercicio">

                                <i class="fa fa-eraser"></i>

                                Limpar filtros

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             TABELA
        ================================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Lista de Exercícios
                </h4>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped custom-table"
                        id="exerciciosContabilisticosTable">

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Código</th>

                                <th>Descrição</th>

                                <th>Início</th>

                                <th>Fim</th>

                                <th>Períodos</th>

                                <th>Status</th>

                                <th>Encerrado em</th>

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


<!-- =====================================================
     MODAL DO EXERCÍCIO
====================================================== -->

<div
    class="modal fade"
    id="modalExercicioContabilistico"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-lg"
        role="document">

        <div class="modal-content">

            <form id="formExercicioContabilistico">

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="tituloModalExercicio">

                        Novo Exercício Contabilístico

                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>
                            &times;
                        </span>

                    </button>

                </div>


                <div class="modal-body">

                    <input
                        type="hidden"
                        id="exercicioId"
                        name="id">


                    <div class="row">

                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="exercicioCodigo">
                                    Código *
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="exercicioCodigo"
                                    name="codigo"
                                    maxlength="20"
                                    placeholder="Ex.: 2026"
                                    required>

                            </div>

                        </div>


                        <div class="col-md-8">

                            <div class="form-group">

                                <label for="exercicioDescricao">
                                    Descrição *
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="exercicioDescricao"
                                    name="descricao"
                                    maxlength="150"
                                    placeholder="Exercício contabilístico de 2026"
                                    required>

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="exercicioDataInicio">
                                    Data de Início *
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="exercicioDataInicio"
                                    name="data_inicio"
                                    required>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="exercicioDataFim">
                                    Data de Fim *
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="exercicioDataFim"
                                    name="data_fim"
                                    required>

                            </div>

                        </div>

                    </div>


                    <div class="alert alert-info mb-0">

                        <i class="fa fa-info-circle"></i>

                        O novo exercício será criado automaticamente com o
                        status <strong>Aberto</strong>. Depois poderá gerar
                        os períodos mensais.

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
                        id="btnGuardarExercicio">

                        <i class="fa fa-save"></i>

                        Guardar Exercício

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL DOS PERÍODOS
====================================================== -->

<div
    class="modal fade"
    id="modalPeriodosContabilisticos"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Períodos Contabilísticos
                    </h5>

                    <small
                        class="text-muted"
                        id="descricaoExercicioPeriodos">

                    </small>

                </div>


                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">

                    <span>
                        &times;
                    </span>

                </button>

            </div>


            <div class="modal-body">

                <input
                    type="hidden"
                    id="periodosExercicioId">


                <div class="row mb-3">

                    <div class="col">

                        <button
                            type="button"
                            class="btn btn-primary"
                            id="btnGerarPeriodos">

                            <i class="fa fa-calendar-plus-o"></i>

                            Gerar Períodos

                        </button>

                    </div>


                    <div class="col-auto">

                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            id="btnAtualizarPeriodos">

                            <i class="fa fa-refresh"></i>

                            Atualizar

                        </button>

                    </div>

                </div>


                <div
                    class="text-center py-4"
                    id="carregandoPeriodos"
                    style="display:none;">

                    <span
                        class="spinner-border spinner-border-sm mr-2">
                    </span>

                    A carregar períodos...

                </div>


                <div
                    class="alert alert-info text-center"
                    id="mensagemSemPeriodos"
                    style="display:none;">

                    Este exercício ainda não possui períodos contabilísticos.

                </div>


                <div class="table-responsive">

                    <table
                        class="table table-striped custom-table"
                        id="periodosContabilisticosTable">

                        <thead>

                            <tr>

                                <th>N.º</th>

                                <th>Período</th>

                                <th>Data de Início</th>

                                <th>Data de Fim</th>

                                <th>Status</th>

                                <th>Fechado em</th>

                                <th class="text-right">
                                    Ações
                                </th>

                            </tr>

                        </thead>

                        <tbody id="listaPeriodosContabilisticos">
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
     MODAL DE STATUS DO EXERCÍCIO
====================================================== -->

<div
    class="modal fade"
    id="modalStatusExercicio"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">

            <form id="formStatusExercicio">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Alterar Status do Exercício
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>
                            &times;
                        </span>

                    </button>

                </div>


                <div class="modal-body">

                    <input
                        type="hidden"
                        id="statusExercicioId"
                        name="id">


                    <div class="form-group">

                        <label for="novoStatusExercicio">
                            Novo status *
                        </label>

                        <select
                            class="form-control"
                            id="novoStatusExercicio"
                            name="status"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>


                    <div
                        class="alert alert-warning mb-0"
                        id="alertaEncerrarExercicio"
                        style="display:none;">

                        <i class="fa fa-exclamation-triangle"></i>

                        Para encerrar o exercício, todos os períodos devem
                        estar fechados.

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
                        id="btnGuardarStatusExercicio">

                        <i class="fa fa-save"></i>

                        Atualizar Status

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL DE STATUS DO PERÍODO
====================================================== -->

<div
    class="modal fade"
    id="modalStatusPeriodo"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">

            <form id="formStatusPeriodo">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Alterar Status do Período
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>
                            &times;
                        </span>

                    </button>

                </div>


                <div class="modal-body">

                    <input
                        type="hidden"
                        id="statusPeriodoId"
                        name="id">


                    <div class="form-group">

                        <label for="novoStatusPeriodo">
                            Novo status *
                        </label>

                        <select
                            class="form-control"
                            id="novoStatusPeriodo"
                            name="status"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>


                    <div
                        class="alert alert-warning mb-0"
                        id="alertaFecharPeriodo"
                        style="display:none;">

                        <i class="fa fa-exclamation-triangle"></i>

                        O período só poderá ser fechado se não existirem
                        lançamentos em rascunho ou lançamentos não
                        balanceados.

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
                        id="btnGuardarStatusPeriodo">

                        <i class="fa fa-save"></i>

                        Atualizar Status

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL DE ELIMINAÇÃO
====================================================== -->

<div
    class="modal fade"
    id="modalEliminarExercicio"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">

            <form id="formEliminarExercicio">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Eliminar Exercício
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>
                            &times;
                        </span>

                    </button>

                </div>


                <div class="modal-body">

                    <input
                        type="hidden"
                        id="eliminarExercicioId"
                        name="id">


                    <p>
                        Confirma a eliminação deste exercício contabilístico?
                    </p>


                    <div class="alert alert-danger mb-0">

                        <i class="fa fa-exclamation-triangle"></i>

                        O exercício só poderá ser eliminado se não possuir
                        lançamentos contabilísticos.

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
                        class="btn btn-danger"
                        id="btnConfirmarEliminarExercicio">

                        <i class="fa fa-trash"></i>

                        Eliminar

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     ESTILOS
====================================================== -->

<style>
    .badge-status-contabilistico {
        display: inline-block;
        min-width: 85px;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
    }

    .status-aberto {
        background: #d4edda;
        color: #155724;
    }

    .status-bloqueado {
        background: #fff3cd;
        color: #856404;
    }

    .status-encerrado,
    .status-fechado {
        background: #e2e3e5;
        color: #383d41;
    }

    .status-desconhecido {
        background: #f8f9fa;
        color: #6c757d;
    }

    .acoes-exercicio {
        white-space: nowrap;
    }

    #periodosContabilisticosTable td,
    #periodosContabilisticosTable th {
        vertical-align: middle;
    }
</style>


