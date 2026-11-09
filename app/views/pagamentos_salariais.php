<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Pagamentos Salariais
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
                            Pagamentos Salariais
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoPagamento">
                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Pagamento Individual
                        </span>
                    </button>
                    <button
                        type="button"
                        class="btn btn-success"
                        id="btnPagamentoLote">

                        <i class="fa fa-users"></i>

                        <span class="d-none d-sm-inline">
                            Pagamento em Lote
                        </span>

                    </button>
                </div>

            </div>
        </div>

        <!-- Resumo -->
        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="dash-widget-header">
                            <span
                                class="dash-widget-icon bg-1">
                                <i class="fa fa-money"></i>
                            </span>

                            <div class="dash-count">
                                <div class="dash-title">
                                    Total Pago
                                </div>

                                <div class="dash-counts">
                                    <p id="resumoValorPago">
                                        0,00 MZN
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="dash-widget-header">
                            <span
                                class="dash-widget-icon bg-2">
                                <i class="fa fa-clock-o"></i>
                            </span>

                            <div class="dash-count">
                                <div class="dash-title">
                                    Valor Pendente
                                </div>

                                <div class="dash-counts">
                                    <p id="resumoValorPendente">
                                        0,00 MZN
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="dash-widget-header">
                            <span
                                class="dash-widget-icon bg-3">
                                <i class="fa fa-check-circle"></i>
                            </span>

                            <div class="dash-count">
                                <div class="dash-title">
                                    Pagamentos Confirmados
                                </div>

                                <div class="dash-counts">
                                    <p id="resumoTotalPagos">
                                        0
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="dash-widget-header">
                            <span
                                class="dash-widget-icon bg-4">
                                <i class="fa fa-times-circle"></i>
                            </span>

                            <div class="dash-count">
                                <div class="dash-title">
                                    Cancelados
                                </div>

                                <div class="dash-counts">
                                    <p id="resumoTotalCancelados">
                                        0
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <!-- Filtros -->
        <div class="card">
            <div class="card-body">

                <div class="row align-items-end">

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="form-group">
                            <label for="filtroPeriodo">
                                Período Salarial
                            </label>

                            <select
                                id="filtroPeriodo"
                                class="form-control">
                                <option value="">
                                    Todos os períodos
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="form-group">
                            <label for="filtroStatus">
                                Estado
                            </label>

                            <select
                                id="filtroStatus"
                                class="form-control">
                                <option value="">
                                    Todos os estados
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="form-group">
                            <label for="filtroMetodoPagamento">
                                Método de pagamento
                            </label>

                            <select
                                id="filtroFormaPagamento"
                                class="form-control">
                                <option value="">
                                    Todas as formas
                                </option>

                                <option value="TRANSFERENCIA_BANCARIA">
                                    Transferência bancária
                                </option>

                                <option value="NUMERARIO">
                                    Numerário
                                </option>

                                <option value="CHEQUE">
                                    Cheque
                                </option>

                                <option value="MPESA">
                                    M-Pesa
                                </option>

                                <option value="EMOLA">
                                    e-Mola
                                </option>

                                <option value="MKESH">
                                    mKesh
                                </option>

                                <option value="CARTEIRA_DIGITAL">
                                    Carteira digital
                                </option>

                                <option value="OUTRO">
                                    Outro
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="form-group">
                            <label for="filtroPesquisa">
                                Pesquisar
                            </label>

                            <input
                                type="search"
                                id="filtroPesquisa"
                                class="form-control"
                                placeholder="Código, funcionário ou referência">
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="text-right">

                            <button
                                type="button"
                                class="btn btn-light"
                                id="btnLimparFiltros">
                                <i class="fa fa-eraser"></i>
                                Limpar
                            </button>

                            <button
                                type="button"
                                class="btn btn-primary"
                                id="btnFiltrarPagamentos">
                                <i class="fa fa-search"></i>
                                Filtrar
                            </button>

                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- Tabela -->
        <div class="card">
            <div class="card-body">
                <div class="row mb-3">

                    <div class="col-md-6">

                        <button
                            type="button"
                            class="btn btn-success"
                            id="btnConfirmarSelecionados"
                            disabled>

                            <i class="fa fa-check"></i>

                            Confirmar Selecionados

                        </button>

                    </div>


                    <div class="col-md-6 text-right">

                        <strong>
                            Selecionados:
                        </strong>

                        <span id="quantidadePagamentosSelecionados">
                            0
                        </span>

                        <span class="mx-2">
                            |
                        </span>

                        <strong>
                            Total:
                        </strong>

                        <span id="totalPagamentosSelecionados">
                            0,00 MZN
                        </span>

                    </div>

                </div>
                <div class="table-responsive">
                    <table
                        id="pagamentosSalariaisTable"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>

                                <th style="width: 35px;">
                                    <input
                                        type="checkbox"
                                        id="checkTodosPagamentosPendentes">
                                </th>

                                <th>Código</th>
                                <th>Funcionário</th>
                                <th>Período</th>
                                <th>Recibo</th>
                                <th>Método</th>
                                <th>Valor</th>
                                <th>Data</th>
                                <th>Estado</th>

                                <th class="text-right">
                                    Ações
                                </th>

                            </tr>
                        </thead>

                        <tbody id="pagamentosSalariaisBody">

                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal Novo/Editar Pagamento -->
<div
    class="modal fade"
    id="modalPagamentoSalarial"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalPagamentoSalarialTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-lg modal-dialog-centered"
        role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="modalPagamentoSalarialTitulo">
                    Novo Pagamento Salarial
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

            <form id="formPagamentoSalarial">

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="pagamentoId"
                        name="id">

                    <input
                        type="hidden"
                        id="acaoPagamento"
                        name="acao"
                        value="criar">

                    <div class="row">

                        <div
                            class="col-md-12"
                            id="grupoReciboSalarial">
                            <div class="form-group">
                                <label for="reciboSalarioId">
                                    Recibo salarial
                                    <span class="text-danger">
                                        *
                                    </span>
                                </label>

                                <select
                                    id="reciboSalarioId"
                                    name="recibo_id"
                                    class="form-control"
                                    required>
                                    <option value="">
                                        Selecione um recibo
                                    </option>
                                </select>

                                <small class="form-text text-muted">
                                    Apenas recibos ainda não pagos serão apresentados.
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="metodoPagamento">
                                    Método de pagamento
                                    <span class="text-danger">
                                        *
                                    </span>
                                </label>

                                <select
                                    id="formaPagamento"
                                    name="forma_pagamento"
                                    class="form-control"
                                    required>
                                    <option value="">
                                        Selecione
                                    </option>

                                    <option value="TRANSFERENCIA_BANCARIA">
                                        Transferência bancária
                                    </option>

                                    <option value="NUMERARIO">
                                        Numerário
                                    </option>

                                    <option value="CHEQUE">
                                        Cheque
                                    </option>

                                    <option value="MPESA">
                                        M-Pesa
                                    </option>

                                    <option value="EMOLA">
                                        e-Mola
                                    </option>

                                    <option value="MKESH">
                                        mKesh
                                    </option>

                                    <option value="CARTEIRA_DIGITAL">
                                        Carteira digital
                                    </option>

                                    <option value="OUTRO">
                                        Outro
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Conta de Pagamento
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    id="conta_contabil_id"
                                    name="conta_contabil_id"
                                    class="form-control">

                                    <option value="">
                                        Selecione...
                                    </option>

                                </select>

                                <small class="form-text text-muted">
                                    Conta de Caixa ou Banco utilizada no pagamento.
                                </small>

                            </div>

                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="valorPago">
                                    Valor a pagar
                                    <span class="text-danger">
                                        *
                                    </span>
                                </label>

                                <div class="input-group">
                                    <input
                                        type="number"
                                        id="valorPago"
                                        name="valor_pago"
                                        class="form-control"
                                        min="0.01"
                                        step="0.01"
                                        required>

                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            MZN
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dataPagamento">
                                    Data do pagamento
                                </label>

                                <input
                                    type="date"
                                    id="dataPagamento"
                                    name="data_pagamento"
                                    class="form-control">
                            </div>
                        </div>

                        <div
                            class="col-md-6"
                            id="grupoBanco">
                            <div class="form-group">
                                <label for="bancoId">
                                    Banco
                                </label>
                                <input
                                    type="text"
                                    id="banco"
                                    name="banco"
                                    class="form-control"
                                    maxlength="120"
                                    placeholder="Banco do funcionário"
                                    readonly>
                            </div>
                        </div>

                        <div
                            class="col-md-6"
                            id="grupoNumeroConta">
                            <div class="form-group">
                                <label for="numeroContaDestino">
                                    Número da conta
                                </label>

                                <input
                                    type="text"
                                    id="numeroContaDestino"
                                    name="conta_bancaria"
                                    class="form-control"
                                    maxlength="80"
                                    placeholder="Número da conta do funcionário"
                                    readonly>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="processamentoId">
                                    Processamento salarial
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    id="processamentoId"
                                    name="processamento_id"
                                    class="form-control"
                                    required>
                                    <option value="">
                                        Selecione o processamento
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="valorProcessamento">
                                    Valor processado
                                </label>

                                <input
                                    type="number"
                                    id="valorProcessamento"
                                    name="valor_processamento"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    required>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="referenciaPagamento">
                                    Referência do pagamento
                                </label>

                                <input
                                    type="text"
                                    id="referenciaPagamento"
                                    name="referencia"
                                    class="form-control"
                                    maxlength="120"
                                    placeholder="Referência bancária, cheque ou transação">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="observacoesPagamento">
                                    Observações
                                </label>

                                <textarea
                                    id="observacoesPagamento"
                                    name="observacoes"
                                    class="form-control"
                                    rows="3"
                                    maxlength="1000"
                                    placeholder="Observações adicionais"></textarea>
                            </div>
                        </div>

                    </div>

                    <!-- Informações do recibo selecionado -->
                    <div
                        id="detalhesReciboPagamento"
                        class="border rounded p-3 d-none">
                        <div class="row">

                            <div class="col-md-4">
                                <small class="text-muted">
                                    Funcionário
                                </small>

                                <div
                                    id="detalheFuncionario"
                                    class="font-weight-bold">
                                    -
                                </div>
                            </div>

                            <div class="col-md-4">
                                <small class="text-muted">
                                    Salário bruto
                                </small>

                                <div
                                    id="detalheSalarioBruto"
                                    class="font-weight-bold">
                                    0,00 MZN
                                </div>
                            </div>

                            <div class="col-md-4">
                                <small class="text-muted">
                                    Descontos
                                </small>

                                <div
                                    id="detalheDescontos"
                                    class="font-weight-bold">
                                    0,00 MZN
                                </div>
                            </div>

                            <div class="col-md-4 mt-3">
                                <small class="text-muted">
                                    Salário líquido
                                </small>

                                <div
                                    id="detalheSalarioLiquido"
                                    class="font-weight-bold text-success">
                                    0,00 MZN
                                </div>
                            </div>

                            <div class="col-md-4 mt-3">
                                <small class="text-muted">
                                    Período
                                </small>

                                <div
                                    id="detalhePeriodo"
                                    class="font-weight-bold">
                                    -
                                </div>
                            </div>

                            <div class="col-md-4 mt-3">
                                <small class="text-muted">
                                    Número do recibo
                                </small>

                                <div
                                    id="detalheNumeroRecibo"
                                    class="font-weight-bold">
                                    -
                                </div>
                            </div>

                        </div>
                    </div>

                    <div
                        id="erroPagamentoSalarial"
                        class="alert alert-danger mt-3 d-none"
                        role="alert"></div>

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
                        id="btnSalvarPagamento">
                        <i class="fa fa-save"></i>
                        Guardar
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<!-- =====================================================
     CONFIRMAR PAGAMENTOS EM LOTE
====================================================== -->

<div
    class="modal fade"
    id="modalConfirmarPagamentosLote"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-dialog-centered"
        role="document">

        <div class="modal-content">

            <form id="formConfirmarPagamentosLote">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Confirmar Pagamentos Selecionados
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>


                <div class="modal-body">

                    <div class="alert alert-warning">

                        <i class="fa fa-exclamation-triangle"></i>

                        Serão confirmados

                        <strong
                            id="quantidadeConfirmacaoLote">
                            0
                        </strong>

                        pagamento(s).

                        Depois da confirmação serão
                        contabilizados e marcados como pagos.

                    </div>


                    <div class="form-group">

                        <label>
                            Data do pagamento
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="date"
                            id="dataConfirmacaoLote"
                            class="form-control"
                            required>

                    </div>


                    <div class="form-group">

                        <label>
                            Referência
                        </label>

                        <input
                            type="text"
                            id="referenciaConfirmacaoLote"
                            class="form-control"
                            maxlength="150"
                            placeholder="Referência geral do lote">

                    </div>


                    <div
                        class="alert alert-danger d-none"
                        id="erroConfirmacaoLote">
                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">

                        Voltar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-success"
                        id="btnExecutarConfirmacaoLote">

                        <i class="fa fa-check"></i>
                        Confirmar Selecionados

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- Modal Confirmar Pagamento -->
<div
    class="modal fade"
    id="modalConfirmarPagamento"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalConfirmarPagamentoTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="modalConfirmarPagamentoTitulo">
                    Confirmar Pagamento
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

            <form id="formConfirmarPagamento">

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="confirmarPagamentoId"
                        name="id">

                    <input
                        type="hidden"
                        name="acao"
                        value="confirmar">

                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>

                        Ao confirmar, o pagamento será marcado como
                        <strong>Pago</strong>.
                    </div>

                    <div class="form-group">
                        <label for="confirmarDataPagamento">
                            Data do pagamento
                            <span class="text-danger">
                                *
                            </span>
                        </label>

                        <input
                            type="date"
                            id="confirmarDataPagamento"
                            name="data_pagamento"
                            class="form-control"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="confirmarReferenciaPagamento">
                            Referência do pagamento
                        </label>

                        <input
                            type="text"
                            id="confirmarReferenciaPagamento"
                            name="referencia_pagamento"
                            class="form-control"
                            maxlength="120"
                            placeholder="Referência da transação">
                    </div>

                    <div
                        id="erroConfirmarPagamento"
                        class="alert alert-danger d-none"
                        role="alert"></div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">
                        Voltar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-success"
                        id="btnConfirmarPagamento">
                        <i class="fa fa-check"></i>
                        Confirmar pagamento
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<!-- =====================================================
     MODAL PAGAMENTO EM LOTE
====================================================== -->

<div
    class="modal fade"
    id="modalPagamentoLote"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl modal-dialog-centered"
        role="document">

        <div class="modal-content">

            <form id="formPagamentoLote">

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="fa fa-users"></i>
                        Pagamento Salarial em Lote

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

                    <div
                        class="alert alert-danger d-none"
                        id="erroPagamentoLote">
                    </div>


                    <!-- CONFIGURAÇÃO -->

                    <div class="card bg-light">

                        <div class="card-body">

                            <div class="row">


                                <!-- Período -->

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="periodoPagamentoLote">

                                            Período salarial

                                            <span class="text-danger">
                                                *
                                            </span>

                                        </label>

                                        <select
                                            id="periodoPagamentoLote"
                                            class="form-control"
                                            required>

                                            <option value="">
                                                Selecione o período
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <!-- Banco -->

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="filtroBancoLote">
                                            Banco do funcionário
                                        </label>

                                        <select
                                            id="filtroBancoLote"
                                            class="form-control">

                                            <option value="">
                                                Todos os bancos
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <!-- Pesquisa -->

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="pesquisaFuncionarioLote">
                                            Pesquisar funcionário
                                        </label>

                                        <input
                                            type="search"
                                            id="pesquisaFuncionarioLote"
                                            class="form-control"
                                            placeholder="Nome ou número">

                                    </div>

                                </div>


                            </div>


                            <div class="row">


                                <!-- Método -->

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="formaPagamentoLote">

                                            Forma de pagamento

                                            <span class="text-danger">
                                                *
                                            </span>

                                        </label>

                                        <select
                                            id="formaPagamentoLote"
                                            name="forma_pagamento"
                                            class="form-control"
                                            required>

                                            <option value="TRANSFERENCIA_BANCARIA">
                                                Transferência bancária
                                            </option>

                                            <option value="NUMERARIO">
                                                Numerário
                                            </option>

                                            <option value="CHEQUE">
                                                Cheque
                                            </option>

                                            <option value="MPESA">
                                                M-Pesa
                                            </option>

                                            <option value="EMOLA">
                                                e-Mola
                                            </option>

                                            <option value="MKESH">
                                                mKesh
                                            </option>

                                            <option value="CARTEIRA_DIGITAL">
                                                Carteira digital
                                            </option>

                                            <option value="OUTRO">
                                                Outro
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <!-- Conta da empresa -->

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="contaContabilLote">

                                            Conta de pagamento

                                            <span class="text-danger">
                                                *
                                            </span>

                                        </label>

                                        <select
                                            id="contaContabilLote"
                                            name="conta_contabil_id"
                                            class="form-control"
                                            required>

                                            <option value="">
                                                Selecione
                                            </option>

                                        </select>

                                        <small class="form-text text-muted">
                                            Conta bancária ou caixa da empresa.
                                        </small>

                                    </div>

                                </div>


                                <!-- Data -->

                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label for="dataPagamentoLote">
                                            Data do pagamento
                                        </label>

                                        <input
                                            type="date"
                                            id="dataPagamentoLote"
                                            name="data_pagamento"
                                            class="form-control">

                                    </div>

                                </div>


                            </div>

                        </div>

                    </div>


                    <!-- AÇÕES DE SELEÇÃO -->

                    <div class="row mb-3">

                        <div class="col-md-6">

                            <button
                                type="button"
                                class="btn btn-sm btn-success"
                                id="btnSelecionarTodosLote">

                                <i class="fa fa-check-square-o"></i>
                                Selecionar visíveis

                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-light"
                                id="btnLimparSelecaoLote">

                                <i class="fa fa-square-o"></i>
                                Limpar seleção

                            </button>

                        </div>


                        <div class="col-md-6 text-right">

                            <strong>
                                Selecionados:
                            </strong>

                            <span id="quantidadeSelecionadosLote">
                                0
                            </span>

                            <span class="mx-2">
                                |
                            </span>

                            <strong>
                                Total:
                            </strong>

                            <span id="totalSelecionadoLote">
                                0,00 MZN
                            </span>

                        </div>

                    </div>


                    <!-- TABELA -->

                    <div class="table-responsive">

                        <table
                            class="table table-striped custom-table mb-0"
                            id="tabelaPagamentoLote">

                            <thead>

                                <tr>

                                    <th style="width: 35px;">

                                        <input
                                            type="checkbox"
                                            id="checkTodosLote">

                                    </th>

                                    <th>
                                        Funcionário
                                    </th>

                                    <th>
                                        Banco
                                    </th>

                                    <th>
                                        Conta
                                    </th>

                                    <th>
                                        NIB
                                    </th>

                                    <th class="text-right">
                                        Valor
                                    </th>

                                    <th>
                                        Situação
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="tbodyPagamentoLote">

                                <tr>

                                    <td
                                        colspan="7"
                                        class="text-center text-muted">

                                        Selecione um período salarial.

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>


                    <div class="mt-3">

                        <label for="observacoesPagamentoLote">
                            Observações
                        </label>

                        <textarea
                            id="observacoesPagamentoLote"
                            class="form-control"
                            rows="2"
                            maxlength="1000"></textarea>

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
                        id="btnCriarPagamentoLote">

                        <i class="fa fa-save"></i>
                        Criar Pagamentos

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- Modal Cancelar Pagamento -->
<div
    class="modal fade"
    id="modalCancelarPagamento"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalCancelarPagamentoTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="modalCancelarPagamentoTitulo">
                    Cancelar Pagamento
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

            <form id="formCancelarPagamento">

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="cancelarPagamentoId"
                        name="id">

                    <input
                        type="hidden"
                        name="acao"
                        value="cancelar">

                    <div class="alert alert-danger">
                        <i class="fa fa-warning"></i>

                        O pagamento será cancelado, mas permanecerá
                        guardado no histórico.
                    </div>

                    <div class="form-group">
                        <label for="motivoCancelamentoPagamento">
                            Motivo do cancelamento
                            <span class="text-danger">
                                *
                            </span>
                        </label>

                        <textarea
                            id="motivoCancelamentoPagamento"
                            name="motivo_cancelamento"
                            class="form-control"
                            rows="4"
                            minlength="5"
                            maxlength="500"
                            required
                            placeholder="Informe o motivo do cancelamento"></textarea>
                    </div>

                    <div
                        id="erroCancelarPagamento"
                        class="alert alert-danger d-none"
                        role="alert"></div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">
                        Voltar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger"
                        id="btnCancelarPagamento">
                        <i class="fa fa-times"></i>
                        Cancelar pagamento
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<style>
    /* =====================================================
   PAGAMENTO EM LOTE - SCROLL DO MODAL
===================================================== */

    #modalPagamentoLote .modal-dialog {
        max-height: calc(100vh - 30px);
        margin-top: 15px;
        margin-bottom: 15px;
    }


    #modalPagamentoLote .modal-content {
        max-height: calc(100vh - 30px);
        display: flex;
        flex-direction: column;
    }


    #modalPagamentoLote #formPagamentoLote {
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 30px);
        overflow: hidden;
    }


    #modalPagamentoLote .modal-header {
        flex-shrink: 0;
    }


    #modalPagamentoLote .modal-body {
        flex: 1 1 auto;
        overflow-y: auto !important;
        overflow-x: hidden;
        min-height: 0;
    }


    #modalPagamentoLote .modal-footer {
        flex-shrink: 0;
    }
</style>