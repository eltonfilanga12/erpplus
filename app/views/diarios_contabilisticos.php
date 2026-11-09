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
                        Diários Contabilísticos
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
                            Diários Contabilísticos
                        </li>

                    </ul>

                </div>


                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoDiario">

                        <i class="fa fa-plus"></i>

                        Novo Diário

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

                        <h3 id="resumoTotalDiarios">
                            0
                        </h3>

                        <span class="text-muted">
                            Total de Diários
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoDiariosActivos">
                            0
                        </h3>

                        <span class="text-muted">
                            Diários Activos
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoDiariosManuais">
                            0
                        </h3>

                        <span class="text-muted">
                            Permitem Lançamento Manual
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoLancamentosDiarios">
                            0
                        </h3>

                        <span class="text-muted">
                            Total de Lançamentos
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

                    <div class="col-md-4">

                        <div class="form-group mb-md-0">

                            <label for="pesquisaDiario">
                                Pesquisar
                            </label>

                            <input
                                type="search"
                                class="form-control"
                                id="pesquisaDiario"
                                placeholder="Código ou nome do diário">

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group mb-md-0">

                            <label for="filtroTipoDiario">
                                Tipo
                            </label>

                            <select
                                class="form-control"
                                id="filtroTipoDiario">

                                <option value="">
                                    Todos os tipos
                                </option>

                                <option value="GERAL">
                                    Geral
                                </option>

                                <option value="VENDAS">
                                    Vendas
                                </option>

                                <option value="COMPRAS">
                                    Compras
                                </option>

                                <option value="CAIXA">
                                    Caixa
                                </option>

                                <option value="BANCOS">
                                    Bancos
                                </option>

                                <option value="STOCK">
                                    Stock
                                </option>

                                <option value="SALARIOS">
                                    Salários
                                </option>

                                <option value="IMPOSTOS">
                                    Impostos
                                </option>

                                <option value="ABERTURA">
                                    Abertura
                                </option>

                                <option value="ENCERRAMENTO">
                                    Encerramento
                                </option>

                                <option value="OUTROS">
                                    Outros
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group mb-md-0">

                            <label for="filtroStatusDiario">
                                Status
                            </label>

                            <select
                                class="form-control"
                                id="filtroStatusDiario">

                                <option value="">
                                    Todos os status
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-2">

                        <div class="form-group mb-0">

                            <label>&nbsp;</label>

                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-block"
                                id="btnLimparFiltrosDiario">

                                <i class="fa fa-eraser"></i>

                                Limpar

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
                    Lista de Diários Contabilísticos
                </h4>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped custom-table"
                        id="diariosContabilisticosTable">

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Código</th>

                                <th>Nome</th>

                                <th>Tipo</th>

                                <th>Manual</th>

                                <th>Numeração</th>

                                <th>Último Número</th>

                                <th>Lançamentos</th>

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


<!-- =====================================================
     MODAL DO DIÁRIO
====================================================== -->

<div
    class="modal fade"
    id="modalDiarioContabilistico"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-lg"
        role="document">

        <div class="modal-content">

            <form id="formDiarioContabilistico">

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="tituloModalDiario">

                        Novo Diário Contabilístico

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
                        id="diarioId"
                        name="id">


                    <div class="row">

                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="diarioCodigo">
                                    Código *
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="diarioCodigo"
                                    name="codigo"
                                    maxlength="20"
                                    placeholder="Ex.: GER"
                                    required>

                                <small class="form-text text-muted">
                                    O código deve ser único dentro da empresa.
                                </small>

                            </div>

                        </div>


                        <div class="col-md-8">

                            <div class="form-group">

                                <label for="diarioNome">
                                    Nome *
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="diarioNome"
                                    name="nome"
                                    maxlength="100"
                                    placeholder="Ex.: Diário Geral"
                                    required>

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="diarioTipo">
                                    Tipo *
                                </label>

                                <select
                                    class="form-control"
                                    id="diarioTipo"
                                    name="tipo"
                                    required>

                                    <option value="">
                                        Seleccione
                                    </option>

                                    <option value="GERAL">
                                        Geral
                                    </option>

                                    <option value="VENDAS">
                                        Vendas
                                    </option>

                                    <option value="COMPRAS">
                                        Compras
                                    </option>

                                    <option value="CAIXA">
                                        Caixa
                                    </option>

                                    <option value="BANCOS">
                                        Bancos
                                    </option>

                                    <option value="STOCK">
                                        Stock
                                    </option>

                                    <option value="SALARIOS">
                                        Salários
                                    </option>

                                    <option value="IMPOSTOS">
                                        Impostos
                                    </option>

                                    <option value="ABERTURA">
                                        Abertura
                                    </option>

                                    <option value="ENCERRAMENTO">
                                        Encerramento
                                    </option>

                                    <option value="OUTROS">
                                        Outros
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="diarioStatusId">
                                    Status *
                                </label>

                                <select
                                    class="form-control"
                                    id="diarioStatusId"
                                    name="status_id"
                                    required>

                                    <option value="">
                                        Seleccione o status
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="diarioDescricao">
                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            id="diarioDescricao"
                            name="descricao"
                            rows="3"
                            maxlength="255"
                            placeholder="Descrição ou finalidade do diário"></textarea>

                    </div>


                    <hr>


                    <h5 class="mb-3">
                        Configurações
                    </h5>


                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="diarioPermiteManual"
                                    name="permite_manual"
                                    value="1">

                                <label
                                    class="form-check-label"
                                    for="diarioPermiteManual">

                                    Permitir lançamentos manuais

                                </label>

                                <small class="form-text text-muted">
                                    Autoriza o utilizador a criar lançamentos
                                    diretamente neste diário.
                                </small>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="diarioNumeracaoAutomatica"
                                    name="numeracao_automatica"
                                    value="1">

                                <label
                                    class="form-check-label"
                                    for="diarioNumeracaoAutomatica">

                                    Utilizar numeração automática

                                </label>

                                <small class="form-text text-muted">
                                    O número será gerado ao guardar cada
                                    lançamento contabilístico.
                                </small>

                            </div>

                        </div>

                    </div>


                    <div
                        class="alert alert-info mb-0"
                        id="informacaoTipoDiario">

                        <i class="fa fa-info-circle"></i>

                        Seleccione um tipo para visualizar a finalidade
                        recomendada do diário.

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
                        id="btnGuardarDiario">

                        <i class="fa fa-save"></i>

                        Guardar Diário

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL DE ALTERAÇÃO DE STATUS
====================================================== -->

<div
    class="modal fade"
    id="modalStatusDiario"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">

            <form id="formStatusDiario">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Alterar Status do Diário
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
                        id="statusDiarioId"
                        name="id">


                    <div class="form-group">

                        <label for="novoStatusDiarioId">
                            Novo status *
                        </label>

                        <select
                            class="form-control"
                            id="novoStatusDiarioId"
                            name="status_id"
                            required>

                            <option value="">
                                Seleccione o status
                            </option>

                        </select>

                    </div>


                    <div
                        class="alert alert-warning mb-0"
                        id="alertaBloquearDiario"
                        style="display:none;">

                        <i class="fa fa-exclamation-triangle"></i>

                        Um diário bloqueado não poderá ser editado nem
                        utilizado para novos lançamentos contabilísticos.

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
                        id="btnGuardarStatusDiario">

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
    id="modalEliminarDiario"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">

            <form id="formEliminarDiario">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Eliminar Diário Contabilístico
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
                        id="eliminarDiarioId"
                        name="id">


                    <p>
                        Confirma a eliminação deste diário contabilístico?
                    </p>


                    <div class="alert alert-danger mb-0">

                        <i class="fa fa-exclamation-triangle"></i>

                        Um diário que já possua lançamentos contabilísticos
                        não poderá ser eliminado.

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
                        id="btnConfirmarEliminarDiario">

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
    .badge-status-diario {
        display: inline-block;
        min-width: 80px;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
    }

    .diario-status-activo {
        background-color: #d4edda;
        color: #155724;
    }

    .diario-status-inativo {
        background-color: #e2e3e5;
        color: #383d41;
    }

    .diario-status-bloqueado {
        background-color: #fff3cd;
        color: #856404;
    }

    .diario-status-desconhecido {
        background-color: #f8f9fa;
        color: #6c757d;
    }

    .acoes-diario {
        white-space: nowrap;
    }

    #diariosContabilisticosTable td,
    #diariosContabilisticosTable th {
        vertical-align: middle;
    }
</style>

