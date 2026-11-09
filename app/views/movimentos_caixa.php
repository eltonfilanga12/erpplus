<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- =========================================================
        PAGE HEADER
        ========================================================== -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Movimentos de Caixa
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item">
                            Tesouraria
                        </li>

                        <li class="breadcrumb-item active">
                            Movimentos de Caixa
                        </li>

                    </ul>

                </div>


                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-white mr-2"
                        id="btnTransferenciaCaixa">
                        <i class="fa fa-exchange-alt"></i>
                        Transferir
                    </button>


                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoMovimentoCaixa">
                        <i class="fa fa-plus"></i>
                        Novo Movimento
                    </button>

                </div>

            </div>

        </div>


        <!-- =========================================================
        CARDS
        ========================================================== -->

        <div class="row">

            <!-- Entradas -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-success-light">

                                <i class="fa fa-arrow-down"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="resumoEntradas">
                                    0,00 MTn
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Entradas
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Saídas -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-danger-light">

                                <i class="fa fa-arrow-up"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="resumoSaidas">
                                    0,00 MTn
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Saídas
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Saldo dos movimentos -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-info-light">

                                <i class="fa fa-balance-scale"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="resumoSaldoMovimentos">
                                    0,00 MTn
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Saldo dos Movimentos
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Registos -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span class="dash-widget-icon bg-warning-light">

                                <i class="fa fa-list"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="resumoTotalMovimentos">
                                    0
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Movimentos
                            </h6>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================================
        FILTROS
        ========================================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Filtros
                </h4>

            </div>


            <div class="card-body">

                <form id="formFiltrosMovimentosCaixa">

                    <div class="row">


                        <!-- Caixa -->

                        <div class="col-md-3">

                            <div class="form-group">

                                <label>
                                    Caixa
                                </label>

                                <select
                                    id="filtroCaixa"
                                    class="form-control">

                                    <option value="">
                                        Todos os caixas
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Tipo -->

                        <div class="col-md-2">

                            <div class="form-group">

                                <label>
                                    Tipo
                                </label>

                                <select
                                    id="filtroTipo"
                                    class="form-control">

                                    <option value="">
                                        Todos
                                    </option>

                                    <option value="ENTRADA">
                                        Entrada
                                    </option>

                                    <option value="SAIDA">
                                        Saída
                                    </option>

                                    <option value="SUPRIMENTO">
                                        Suprimento
                                    </option>

                                    <option value="SANGRIA">
                                        Sangria
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Origem -->

                        <div class="col-md-3">

                            <div class="form-group">

                                <label>
                                    Origem
                                </label>

                                <select
                                    id="filtroOrigem"
                                    class="form-control">

                                    <option value="">
                                        Todas
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

                                    <option value="TRANSFERENCIA">
                                        Transferência
                                    </option>

                                    <option value="MANUAL">
                                        Manual
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Data inicial -->

                        <div class="col-md-2">

                            <div class="form-group">

                                <label>
                                    Data Inicial
                                </label>

                                <input
                                    type="date"
                                    id="filtroDataInicio"
                                    class="form-control">

                            </div>

                        </div>


                        <!-- Data final -->

                        <div class="col-md-2">

                            <div class="form-group">

                                <label>
                                    Data Final
                                </label>

                                <input
                                    type="date"
                                    id="filtroDataFim"
                                    class="form-control">

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-12 text-right">

                            <button
                                type="button"
                                class="btn btn-white mr-2"
                                id="btnLimparFiltrosCaixa">
                                <i class="fa fa-eraser"></i>
                                Limpar
                            </button>


                            <button
                                type="submit"
                                class="btn btn-primary">
                                <i class="fa fa-search"></i>
                                Pesquisar
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        <!-- =========================================================
        TABELA
        ========================================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Lista de Movimentos
                </h4>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="movimentosCaixaTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Data
                                </th>

                                <th>
                                    Caixa
                                </th>

                                <th>
                                    Tipo
                                </th>

                                <th>
                                    Origem
                                </th>

                                <th>
                                    Descrição
                                </th>

                                <th>
                                    Operador
                                </th>

                                <th class="text-right">
                                    Valor
                                </th>

                                <th class="text-center">
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

    </div>

</div>


<!-- ===============================================================
MODAL NOVO MOVIMENTO
================================================================ -->

<div
    class="modal fade"
    id="modalMovimentoCaixa"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-lg"
        role="document">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">
                    Novo Movimento de Caixa
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


            <form id="formMovimentoCaixa">


                <div class="modal-body">

                    <div class="row">


                        <!-- Caixa -->

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Caixa
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="caixa_id"
                                    id="movimentoCaixaId"
                                    class="form-control"
                                    required>

                                    <option value="">
                                        Selecione
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Saldo -->

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Saldo Atual
                                </label>

                                <input
                                    type="text"
                                    id="movimentoSaldoAtual"
                                    class="form-control"
                                    value="0,00 MTn"
                                    readonly>

                            </div>

                        </div>


                        <!-- Tipo -->

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Tipo
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="tipo"
                                    id="movimentoTipo"
                                    class="form-control"
                                    required>

                                    <option value="">
                                        Selecione
                                    </option>

                                    <option value="ENTRADA">
                                        Entrada
                                    </option>

                                    <option value="SAIDA">
                                        Saída
                                    </option>

                                    <option value="SUPRIMENTO">
                                        Suprimento
                                    </option>

                                    <option value="SANGRIA">
                                        Sangria
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Valor -->

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Valor
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="valor"
                                    id="movimentoValor"
                                    class="form-control"
                                    min="0.01"
                                    step="0.01"
                                    required>

                            </div>

                        </div>


                        <!-- Data -->

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Data/Hora
                                </label>

                                <input
                                    type="datetime-local"
                                    name="data_movimento"
                                    id="movimentoData"
                                    class="form-control">

                            </div>

                        </div>


                        <!-- Centro de custo -->

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Centro de Custo
                                </label>

                                <select
                                    name="centro_custo_id"
                                    id="movimentoCentroCusto"
                                    class="form-control">

                                    <option value="">
                                        Nenhum
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Descrição -->

                        <div class="col-md-12">

                            <div class="form-group">

                                <label>
                                    Descrição
                                    <span class="text-danger">*</span>
                                </label>

                                <textarea
                                    name="descricao"
                                    id="movimentoDescricao"
                                    class="form-control"
                                    rows="3"
                                    maxlength="500"
                                    required></textarea>

                            </div>

                        </div>


                        <input
                            type="hidden"
                            name="origem"
                            value="MANUAL">

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-white"
                        data-dismiss="modal">
                        Cancelar
                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnGuardarMovimentoCaixa">

                        <i class="fa fa-save"></i>

                        Guardar Movimento

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- ===============================================================
MODAL TRANSFERÊNCIA
================================================================ -->

<div
    class="modal fade"
    id="modalTransferenciaCaixa"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">
                    Transferência entre Caixas
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


            <form id="formTransferenciaCaixa">


                <div class="modal-body">


                    <!-- Origem -->

                    <div class="form-group">

                        <label>
                            Caixa de Origem
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="caixa_origem_id"
                            id="transferenciaCaixaOrigem"
                            class="form-control"
                            required>

                            <option value="">
                                Selecione
                            </option>

                        </select>

                    </div>


                    <!-- Saldo -->

                    <div class="form-group">

                        <label>
                            Saldo Disponível
                        </label>

                        <input
                            type="text"
                            id="transferenciaSaldoOrigem"
                            class="form-control"
                            value="0,00 MTn"
                            readonly>

                    </div>


                    <!-- Destino -->

                    <div class="form-group">

                        <label>
                            Caixa de Destino
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="caixa_destino_id"
                            id="transferenciaCaixaDestino"
                            class="form-control"
                            required>

                            <option value="">
                                Selecione
                            </option>

                        </select>

                    </div>


                    <!-- Valor -->

                    <div class="form-group">

                        <label>
                            Valor
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="number"
                            name="valor"
                            id="transferenciaValor"
                            class="form-control"
                            min="0.01"
                            step="0.01"
                            required>

                    </div>


                    <!-- Data -->

                    <div class="form-group">

                        <label>
                            Data/Hora
                        </label>

                        <input
                            type="datetime-local"
                            name="data_movimento"
                            id="transferenciaData"
                            class="form-control">

                    </div>


                    <!-- Descrição -->

                    <div class="form-group">

                        <label>
                            Descrição
                        </label>

                        <textarea
                            name="descricao"
                            id="transferenciaDescricao"
                            class="form-control"
                            rows="3"
                            maxlength="500"
                            placeholder="Ex.: Transferência para reforço do Caixa 2"></textarea>

                    </div>


                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-white"
                        data-dismiss="modal">
                        Cancelar
                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnConfirmarTransferenciaCaixa">

                        <i class="fa fa-exchange-alt"></i>

                        Transferir

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- ===============================================================
MODAL DETALHES
================================================================ -->

<div
    class="modal fade"
    id="modalDetalhesMovimentoCaixa"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-lg"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Detalhes do Movimento
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

                <div class="row">

                    <div class="col-md-4">
                        <small class="text-muted">
                            Caixa
                        </small>

                        <h6 id="detalheCaixa">
                            -
                        </h6>
                    </div>


                    <div class="col-md-4">
                        <small class="text-muted">
                            Data
                        </small>

                        <h6 id="detalheData">
                            -
                        </h6>
                    </div>


                    <div class="col-md-4">
                        <small class="text-muted">
                            Operador
                        </small>

                        <h6 id="detalheOperador">
                            -
                        </h6>
                    </div>


                    <div class="col-md-4 mt-3">
                        <small class="text-muted">
                            Tipo
                        </small>

                        <h6 id="detalheTipo">
                            -
                        </h6>
                    </div>


                    <div class="col-md-4 mt-3">
                        <small class="text-muted">
                            Origem
                        </small>

                        <h6 id="detalheOrigem">
                            -
                        </h6>
                    </div>


                    <div class="col-md-4 mt-3">
                        <small class="text-muted">
                            Valor
                        </small>

                        <h6 id="detalheValor">
                            0,00 MTn
                        </h6>
                    </div>


                    <div class="col-md-4 mt-3">
                        <small class="text-muted">
                            Filial
                        </small>

                        <h6 id="detalheFilial">
                            -
                        </h6>
                    </div>


                    <div class="col-md-4 mt-3">
                        <small class="text-muted">
                            Centro de Custo
                        </small>

                        <h6 id="detalheCentroCusto">
                            -
                        </h6>
                    </div>


                    <div class="col-md-4 mt-3">
                        <small class="text-muted">
                            Documento
                        </small>

                        <h6 id="detalheDocumento">
                            -
                        </h6>
                    </div>


                    <div class="col-md-12 mt-3">

                        <small class="text-muted">
                            Descrição
                        </small>

                        <p
                            id="detalheDescricao"
                            class="mb-0">
                            -
                        </p>

                    </div>

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-white"
                    data-dismiss="modal">
                    Fechar
                </button>

            </div>

        </div>

    </div>

</div>