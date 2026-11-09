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
                        Lançamentos Contabilísticos
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
                            Lançamentos Contabilísticos
                        </li>

                    </ul>

                </div>


                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoLancamento">

                        <i class="fa fa-plus"></i>

                        Novo Lançamento

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

                        <h3 id="resumoTotalLancamentos">
                            0
                        </h3>

                        <span class="text-muted">
                            Total de Lançamentos
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoLancamentosRascunho">
                            0
                        </h3>

                        <span class="text-muted">
                            Em Rascunho
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoLancamentosContabilizados">
                            0
                        </h3>

                        <span class="text-muted">
                            Contabilizados
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoValorContabilizado">
                            0,00 MZN
                        </h3>

                        <span class="text-muted">
                            Valor Contabilizado
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             FILTROS
        ================================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Filtros
                </h4>

            </div>


            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label for="filtroExercicioLancamento">
                                Exercício
                            </label>

                            <select
                                class="form-control"
                                id="filtroExercicioLancamento">

                                <option value="">
                                    Todos
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group">

                            <label for="filtroPeriodoLancamento">
                                Período
                            </label>

                            <select
                                class="form-control"
                                id="filtroPeriodoLancamento">

                                <option value="">
                                    Todos
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group">

                            <label for="filtroDiarioLancamento">
                                Diário
                            </label>

                            <select
                                class="form-control"
                                id="filtroDiarioLancamento">

                                <option value="">
                                    Todos
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group">

                            <label for="filtroStatusLancamento">
                                Status
                            </label>

                            <select
                                class="form-control"
                                id="filtroStatusLancamento">

                                <option value="">
                                    Todos
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group mb-md-0">

                            <label for="filtroOrigemLancamento">
                                Origem
                            </label>

                            <select
                                class="form-control"
                                id="filtroOrigemLancamento">

                                <option value="">
                                    Todas
                                </option>

                                <option value="MANUAL">
                                    Manual
                                </option>

                                <option value="VENDA">
                                    Venda
                                </option>

                                <option value="COMPRA">
                                    Compra
                                </option>

                                <option value="RECEBIMENTO">
                                    Recebimento
                                </option>

                                <option value="PAGAMENTO">
                                    Pagamento
                                </option>

                                <option value="CAIXA">
                                    Caixa
                                </option>

                                <option value="BANCO">
                                    Banco
                                </option>

                                <option value="STOCK">
                                    Stock
                                </option>

                                <option value="FOLHA_SALARIAL">
                                    Folha Salarial
                                </option>

                                <option value="PAGAMENTO_SALARIAL">
                                    Pagamento Salarial
                                </option>

                                <option value="IMPOSTO">
                                    Imposto
                                </option>

                                <option value="ABERTURA">
                                    Abertura
                                </option>

                                <option value="ENCERRAMENTO">
                                    Encerramento
                                </option>

                                <option value="ESTORNO">
                                    Estorno
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group mb-md-0">

                            <label for="filtroDataInicioLancamento">
                                Data Inicial
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                id="filtroDataInicioLancamento">

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group mb-md-0">

                            <label for="filtroDataFimLancamento">
                                Data Final
                            </label>

                            <input
                                type="date"
                                class="form-control"
                                id="filtroDataFimLancamento">

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group mb-0">

                            <label>&nbsp;</label>

                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-block"
                                id="btnLimparFiltrosLancamento">

                                <i class="fa fa-eraser"></i>

                                Limpar Filtros

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
                    Lista de Lançamentos
                </h4>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped custom-table"
                        id="lancamentosContabilisticosTable">

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Data</th>

                                <th>Número</th>

                                <th>Diário</th>

                                <th>Documento</th>

                                <th>Descrição</th>

                                <th>Origem</th>

                                <th>Débito</th>

                                <th>Crédito</th>

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
     MODAL DO LANÇAMENTO
====================================================== -->

<div
    class="modal fade"
    id="modalLancamentoContabilistico"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">

            <form id="formLancamentoContabilistico">

                <div class="modal-header">

                    <div>

                        <h5
                            class="modal-title"
                            id="tituloModalLancamento">

                            Novo Lançamento Contabilístico

                        </h5>

                        <small
                            class="text-muted"
                            id="numeroLancamentoInformacao">

                            O número será gerado ao guardar.

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
                        id="lancamentoId"
                        name="id">


                    <!-- =====================================
                         CONTEXTO CONTABILÍSTICO
                    ====================================== -->

                    <div class="row">

                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="lancamentoExercicioId">
                                    Exercício *
                                </label>

                                <select
                                    class="form-control"
                                    id="lancamentoExercicioId"
                                    name="exercicio_id"
                                    required>

                                    <option value="">
                                        Seleccione
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="lancamentoPeriodoId">
                                    Período *
                                </label>

                                <select
                                    class="form-control"
                                    id="lancamentoPeriodoId"
                                    name="periodo_id"
                                    required>

                                    <option value="">
                                        Seleccione
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="lancamentoDiarioId">
                                    Diário *
                                </label>

                                <select
                                    class="form-control"
                                    id="lancamentoDiarioId"
                                    name="diario_id"
                                    required>

                                    <option value="">
                                        Seleccione
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="lancamentoData">
                                    Data do Lançamento *
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="lancamentoData"
                                    name="data_lancamento"
                                    required>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="lancamentoDataDocumento">
                                    Data do Documento
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="lancamentoDataDocumento"
                                    name="data_documento">

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="lancamentoNumeroDocumento">
                                    Número do Documento
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="lancamentoNumeroDocumento"
                                    name="numero_documento"
                                    maxlength="100"
                                    placeholder="Ex.: FT 001/2026">

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="lancamentoReferencia">
                                    Referência
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="lancamentoReferencia"
                                    name="referencia"
                                    maxlength="120"
                                    placeholder="Referência externa">

                            </div>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="lancamentoDescricao">
                            Descrição *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="lancamentoDescricao"
                            name="descricao"
                            maxlength="255"
                            placeholder="Descrição do lançamento contabilístico"
                            required>

                    </div>


                    <hr>


                    <!-- =====================================
                         ADICIONAR ITEM
                    ====================================== -->

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h5 class="mb-0">
                            Itens do Lançamento
                        </h5>

                        <span class="text-muted">
                            Partidas dobradas
                        </span>

                    </div>


                    <div class="card bg-light">

                        <div class="card-body">

                            <div class="row">

                                <div class="col-md-6">

                                    <div class="form-group">

                                        <label for="itemContaContabilId">
                                            Conta Contabilística *
                                        </label>

                                        <select
                                            class="form-control"
                                            id="itemContaContabilId">

                                            <option value="">
                                                Seleccione a conta
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <div class="col-md-3">

                                    <div class="form-group">

                                        <label for="itemCentroCustoId">
                                            Centro de Custo
                                        </label>

                                        <select
                                            class="form-control"
                                            id="itemCentroCustoId">

                                            <option value="">
                                                Nenhum
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <div class="col-md-3">

                                    <div class="form-group">

                                        <label for="itemTipoEntidade">
                                            Tipo de Entidade
                                        </label>

                                        <select
                                            class="form-control"
                                            id="itemTipoEntidade">

                                            <option value="">
                                                Nenhuma
                                            </option>

                                            <option value="CLIENTE">
                                                Cliente
                                            </option>

                                            <option value="FORNECEDOR">
                                                Fornecedor
                                            </option>

                                        </select>

                                    </div>

                                </div>

                            </div>


                            <div class="row">

                                <div
                                    class="col-md-4"
                                    id="grupoItemCliente"
                                    style="display:none;">

                                    <div class="form-group">

                                        <label for="itemClienteId">
                                            Cliente
                                        </label>

                                        <select
                                            class="form-control"
                                            id="itemClienteId">

                                            <option value="">
                                                Seleccione
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <div
                                    class="col-md-4"
                                    id="grupoItemFornecedor"
                                    style="display:none;">

                                    <div class="form-group">

                                        <label for="itemFornecedorId">
                                            Fornecedor
                                        </label>

                                        <select
                                            class="form-control"
                                            id="itemFornecedorId">

                                            <option value="">
                                                Seleccione
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="itemDebito">
                                            Débito
                                        </label>

                                        <input
                                            type="number"
                                            class="form-control text-right"
                                            id="itemDebito"
                                            min="0"
                                            step="0.01"
                                            value="0.00">

                                    </div>

                                </div>


                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="itemCredito">
                                            Crédito
                                        </label>

                                        <input
                                            type="number"
                                            class="form-control text-right"
                                            id="itemCredito"
                                            min="0"
                                            step="0.01"
                                            value="0.00">

                                    </div>

                                </div>

                            </div>


                            <div class="row align-items-end">

                                <div class="col-md-9">

                                    <div class="form-group mb-md-0">

                                        <label for="itemDescricao">
                                            Descrição do Item
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control"
                                            id="itemDescricao"
                                            maxlength="255"
                                            placeholder="Descrição específica do item">

                                    </div>

                                </div>


                                <div class="col-md-3">

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-block"
                                        id="btnAdicionarItemLancamento">

                                        <i class="fa fa-plus"></i>

                                        Adicionar Item

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- =====================================
                         TABELA DOS ITENS
                    ====================================== -->

                    <div class="table-responsive">

                        <table
                            class="table table-bordered"
                            id="itensLancamentoTable">

                            <thead>

                                <tr>

                                    <th style="width:60px;">
                                        Ordem
                                    </th>

                                    <th>
                                        Conta
                                    </th>

                                    <th>
                                        Descrição
                                    </th>

                                    <th>
                                        Centro de Custo
                                    </th>

                                    <th>
                                        Entidade
                                    </th>

                                    <th class="text-right">
                                        Débito
                                    </th>

                                    <th class="text-right">
                                        Crédito
                                    </th>

                                    <th
                                        class="text-center"
                                        style="width:90px;">

                                        Ações

                                    </th>

                                </tr>

                            </thead>

                            <tbody id="listaItensLancamento">

                                <tr id="linhaSemItensLancamento">

                                    <td
                                        colspan="8"
                                        class="text-center text-muted py-4">

                                        Nenhum item adicionado.

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>


                    <!-- =====================================
                         TOTAIS
                    ====================================== -->

                    <div class="row justify-content-end">

                        <div class="col-md-6 col-lg-5">

                            <table class="table table-sm">

                                <tbody>

                                    <tr>

                                        <th>
                                            Total de Débito
                                        </th>

                                        <td
                                            class="text-right font-weight-bold"
                                            id="totalDebitoLancamento">

                                            0,00 MZN

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Total de Crédito
                                        </th>

                                        <td
                                            class="text-right font-weight-bold"
                                            id="totalCreditoLancamento">

                                            0,00 MZN

                                        </td>

                                    </tr>


                                    <tr>

                                        <th>
                                            Diferença
                                        </th>

                                        <td
                                            class="text-right font-weight-bold"
                                            id="diferencaLancamento">

                                            0,00 MZN

                                        </td>

                                    </tr>

                                </tbody>

                            </table>


                            <div
                                class="alert alert-danger"
                                id="alertaLancamentoDesbalanceado"
                                style="display:none;">

                                <i class="fa fa-exclamation-triangle"></i>

                                O total de débito deve ser igual ao total de
                                crédito.

                            </div>


                            <div
                                class="alert alert-success"
                                id="alertaLancamentoBalanceado"
                                style="display:none;">

                                <i class="fa fa-check-circle"></i>

                                O lançamento está balanceado.

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
                        id="btnGuardarLancamento">

                        <i class="fa fa-save"></i>

                        Guardar Rascunho

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL DE DETALHES
====================================================== -->

<div
    class="modal fade"
    id="modalDetalhesLancamento"
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
                        Detalhes do Lançamento
                    </h5>

                    <small
                        class="text-muted"
                        id="detalhesLancamentoNumero">

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

                <div class="row">

                    <div class="col-md-3">

                        <strong>Data</strong>

                        <p id="detalhesLancamentoData">
                            -
                        </p>

                    </div>


                    <div class="col-md-3">

                        <strong>Diário</strong>

                        <p id="detalhesLancamentoDiario">
                            -
                        </p>

                    </div>


                    <div class="col-md-3">

                        <strong>Período</strong>

                        <p id="detalhesLancamentoPeriodo">
                            -
                        </p>

                    </div>


                    <div class="col-md-3">

                        <strong>Status</strong>

                        <p id="detalhesLancamentoStatus">
                            -
                        </p>

                    </div>

                </div>


                <div class="row">

                    <div class="col-md-4">

                        <strong>Número do Documento</strong>

                        <p id="detalhesLancamentoDocumento">
                            -
                        </p>

                    </div>


                    <div class="col-md-4">

                        <strong>Referência</strong>

                        <p id="detalhesLancamentoReferencia">
                            -
                        </p>

                    </div>


                    <div class="col-md-4">

                        <strong>Origem</strong>

                        <p id="detalhesLancamentoOrigem">
                            -
                        </p>

                    </div>

                </div>


                <div class="form-group">

                    <strong>Descrição</strong>

                    <p id="detalhesLancamentoDescricao">
                        -
                    </p>

                </div>


                <div class="table-responsive">

                    <table class="table table-bordered">

                        <thead>

                            <tr>

                                <th>Conta</th>

                                <th>Descrição</th>

                                <th>Centro de Custo</th>

                                <th>Entidade</th>

                                <th class="text-right">
                                    Débito
                                </th>

                                <th class="text-right">
                                    Crédito
                                </th>

                            </tr>

                        </thead>

                        <tbody id="detalhesItensLancamento">
                        </tbody>

                        <tfoot>

                            <tr>

                                <th colspan="4">
                                    Totais
                                </th>

                                <th
                                    class="text-right"
                                    id="detalhesTotalDebito">

                                    0,00 MZN

                                </th>

                                <th
                                    class="text-right"
                                    id="detalhesTotalCredito">

                                    0,00 MZN

                                </th>

                            </tr>

                        </tfoot>

                    </table>

                </div>


                <div
                    class="alert alert-danger mb-0"
                    id="detalhesMotivoAnulacao"
                    style="display:none;">

                    <strong>Motivo da Anulação:</strong>

                    <span id="textoMotivoAnulacao"></span>

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
     MODAL DE CONTABILIZAÇÃO
====================================================== -->

<div
    class="modal fade"
    id="modalContabilizarLancamento"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">

            <form id="formContabilizarLancamento">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Contabilizar Lançamento
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
                        id="contabilizarLancamentoId"
                        name="id">


                    <div class="alert alert-warning mb-0">

                        <i class="fa fa-exclamation-triangle"></i>

                        Depois de contabilizado, o lançamento não poderá ser
                        editado nem eliminado.

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
                        class="btn btn-success"
                        id="btnConfirmarContabilizacao">

                        <i class="fa fa-check"></i>

                        Contabilizar

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL DE ANULAÇÃO
====================================================== -->

<div
    class="modal fade"
    id="modalAnularLancamento"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">

            <form id="formAnularLancamento">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Estornar Lançamento
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
                        id="anularLancamentoId"
                        name="id">

                    <div class="form-group">

                        <label for="dataEstornoLancamento">
                            Data do Estorno *
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="dataEstornoLancamento"
                            name="data_estorno"
                            required>

                        <small class="form-text text-muted">
                            A data deve pertencer a um período contabilístico aberto.
                        </small>

                    </div>


                    <div class="form-group">

                        <label for="motivoAnulacaoLancamento">
                            Motivo da Anulação *
                        </label>

                        <textarea
                            class="form-control"
                            id="motivoAnulacaoLancamento"
                            name="motivo"
                            rows="4"
                            maxlength="255"
                            required></textarea>

                    </div>


                    <div class="alert alert-danger mb-0">

                        <i class="fa fa-exclamation-triangle"></i>

                        A anulação manterá o lançamento no histórico
                        contabilístico.

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
                        id="btnConfirmarAnulacao">

                        <i class="fa fa-undo"></i>

                        Criar Estorno

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
    id="modalEliminarLancamento"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">

            <form id="formEliminarLancamento">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Eliminar Lançamento
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
                        id="eliminarLancamentoId"
                        name="id">


                    <p>
                        Confirma a eliminação deste lançamento?
                    </p>


                    <div class="alert alert-danger mb-0">

                        <i class="fa fa-exclamation-triangle"></i>

                        Apenas lançamentos manuais em rascunho podem ser
                        eliminados.

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
                        id="btnConfirmarEliminarLancamento">

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
    .badge-status-lancamento {
        display: inline-block;
        min-width: 95px;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
    }

    .status-lancamento-rascunho {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .status-lancamento-contabilizado {
        background-color: #d4edda;
        color: #155724;
    }

    .status-lancamento-anulado {
        background-color: #f8d7da;
        color: #721c24;
    }

    .status-lancamento-desconhecido {
        background-color: #e2e3e5;
        color: #383d41;
    }

    .acoes-lancamento {
        white-space: nowrap;
    }

    #itensLancamentoTable td,
    #itensLancamentoTable th,
    #lancamentosContabilisticosTable td,
    #lancamentosContabilisticosTable th {
        vertical-align: middle;
    }

    .valor-lancamento-positivo {
        color: #28a745;
    }

    .valor-lancamento-negativo {
        color: #dc3545;
    }

    .item-lancamento-arrastavel {
        cursor: move;
    }
</style>