<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- Cabeçalho -->
        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Movimentos Bancários
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>

                        <li class="breadcrumb-item">

                            <a href="index.php?page=bancos">
                                Bancos
                            </a>

                        </li>

                        <li class="breadcrumb-item active">
                            Movimentos Bancários
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoMovimento">

                        <i class="fa fa-plus"></i>
                        Novo Movimento

                    </button>

                    <button
                        type="button"
                        class="btn btn-secondary"
                        id="btnNovaTransferencia">

                        <i class="fa fa-exchange-alt"></i>
                        Transferir

                    </button>

                    <button
                        type="button"
                        class="btn btn-info"
                        id="btnExtratoBancario">

                        <i class="fa fa-file-alt"></i>
                        Extrato Bancário

                    </button>

                </div>

            </div>

        </div>


        <!-- Filtros -->
        <div class="card">

            <div class="card-body">

                <div class="form-row">

                    <div class="form-group col-md-3">

                        <label for="filtro_banco_id">
                            Banco
                        </label>

                        <select
                            id="filtro_banco_id"
                            class="form-control">

                            <option value="">
                                Todos os bancos
                            </option>

                        </select>

                    </div>

                    <div class="form-group col-md-2">

                        <label for="filtro_tipo">
                            Tipo
                        </label>

                        <select
                            id="filtro_tipo"
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

                        </select>

                    </div>

                    <div class="form-group col-md-2">

                        <label for="filtro_categoria">
                            Categoria
                        </label>

                        <select
                            id="filtro_categoria"
                            class="form-control">

                            <option value="">
                                Todas
                            </option>

                            <option value="DEPOSITO">
                                Depósito
                            </option>

                            <option value="LEVANTAMENTO">
                                Levantamento
                            </option>

                            <option value="TRANSFERENCIA">
                                Transferência
                            </option>

                            <option value="PAGAMENTO">
                                Pagamento
                            </option>

                            <option value="RECEBIMENTO">
                                Recebimento
                            </option>

                            <option value="TARIFA">
                                Tarifa
                            </option>

                            <option value="JURO">
                                Juro
                            </option>

                            <option value="AJUSTE">
                                Ajuste
                            </option>

                            <option value="OUTRO">
                                Outro
                            </option>

                        </select>

                    </div>

                    <div class="form-group col-md-2">

                        <label for="filtro_data_inicio">
                            Data Inicial
                        </label>

                        <input
                            type="date"
                            id="filtro_data_inicio"
                            class="form-control">

                    </div>

                    <div class="form-group col-md-2">

                        <label for="filtro_data_fim">
                            Data Final
                        </label>

                        <input
                            type="date"
                            id="filtro_data_fim"
                            class="form-control">

                    </div>

                    <div class="form-group col-md-1">

                        <label>
                            &nbsp;
                        </label>

                        <button
                            type="button"
                            id="btnFiltrarMovimentos"
                            class="btn btn-primary btn-block">

                            <i class="fa fa-search"></i>

                        </button>

                    </div>

                </div>

            </div>

        </div>


        <!-- Tabela -->
        <div class="card">

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="movimentosBancariosTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Data</th>
                                <th>Banco</th>
                                <th>Tipo</th>
                                <th>Categoria</th>
                                <th>Descrição</th>
                                <th>Documento</th>
                                <th>Saldo Anterior</th>
                                <th>Valor</th>
                                <th>Saldo Posterior</th>
                                <th>Conciliado</th>
                                <th>Operador</th>
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


<!-- ==================================================
MODAL NOVO MOVIMENTO
================================================== -->

<div
    id="modalMovimentoBancario"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog modal-lg">

        <form
            id="formMovimentoBancario"
            class="modal-content">

            <div class="modal-header">

                <h4 class="modal-title">
                    Novo Movimento Bancário
                </h4>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">

                    &times;

                </button>

            </div>

            <div class="modal-body">

                <input
                    type="hidden"
                    id="movimento_bancario_id"
                    name="id">

                <div class="form-row">

                    <div class="form-group col-md-6">

                        <label for="banco_id">
                            Banco *
                        </label>

                        <select
                            class="form-control"
                            id="banco_id"
                            name="banco_id"
                            required>

                            <option value="">
                                Selecione o banco
                            </option>

                        </select>

                    </div>

                    <div class="form-group col-md-3">

                        <label for="tipo">
                            Tipo *
                        </label>

                        <select
                            class="form-control"
                            id="tipo"
                            name="tipo"
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

                        </select>

                    </div>

                    <div class="form-group col-md-3">

                        <label for="categoria">
                            Categoria *
                        </label>

                        <select
                            class="form-control"
                            id="categoria"
                            name="categoria"
                            required>

                            <option value="">
                                Selecione
                            </option>

                            <option value="DEPOSITO">
                                Depósito
                            </option>

                            <option value="LEVANTAMENTO">
                                Levantamento
                            </option>

                            <option value="PAGAMENTO">
                                Pagamento
                            </option>

                            <option value="RECEBIMENTO">
                                Recebimento
                            </option>

                            <option value="TARIFA">
                                Tarifa
                            </option>

                            <option value="JURO">
                                Juro
                            </option>

                            <option value="AJUSTE">
                                Ajuste
                            </option>

                            <option value="OUTRO">
                                Outro
                            </option>

                        </select>

                    </div>

                    <div class="form-group col-md-6">

                        <label for="descricao">
                            Descrição *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="descricao"
                            name="descricao"
                            maxlength="255"
                            required>

                    </div>

                    <div class="form-group col-md-3">

                        <label for="documento">
                            Documento
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="documento"
                            name="documento"
                            maxlength="100">

                    </div>

                    <div class="form-group col-md-3">

                        <label for="referencia">
                            Referência
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="referencia"
                            name="referencia"
                            maxlength="100">

                    </div>

                    <div class="form-group col-md-4">

                        <label for="valor">
                            Valor *
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="valor"
                            name="valor"
                            min="0.01"
                            step="0.01"
                            required>

                    </div>

                    <div class="form-group col-md-4">

                        <label for="data_movimento">
                            Data do Movimento *
                        </label>

                        <input
                            type="datetime-local"
                            class="form-control"
                            id="data_movimento"
                            name="data_movimento"
                            required>

                    </div>

                    <div class="form-group col-md-4">

                        <label for="origem">
                            Origem
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="origem"
                            name="origem"
                            value="MANUAL"
                            maxlength="50"
                            readonly>

                    </div>

                    <div class="form-group col-md-12">

                        <label for="observacoes">
                            Observações
                        </label>

                        <textarea
                            class="form-control"
                            id="observacoes"
                            name="observacoes"
                            rows="3"></textarea>

                    </div>

                </div>

            </div>

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
                    id="btnGuardarMovimento">

                    <i class="fa fa-save"></i>
                    Guardar Movimento

                </button>

            </div>

        </form>

    </div>

</div>


<!-- ==================================================
MODAL TRANSFERÊNCIA BANCÁRIA
================================================== -->

<div
    id="modalTransferenciaBancaria"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog modal-lg">

        <form
            id="formTransferenciaBancaria"
            class="modal-content">

            <div class="modal-header">

                <h4 class="modal-title">
                    Transferência Bancária
                </h4>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">

                    &times;

                </button>

            </div>

            <div class="modal-body">

                <div class="form-row">

                    <div class="form-group col-md-6">

                        <label for="banco_origem_id">
                            Banco de Origem *
                        </label>

                        <select
                            class="form-control"
                            id="banco_origem_id"
                            name="banco_origem_id"
                            required>

                            <option value="">
                                Selecione o banco de origem
                            </option>

                        </select>

                    </div>

                    <div class="form-group col-md-6">

                        <label for="banco_destino_id">
                            Banco de Destino *
                        </label>

                        <select
                            class="form-control"
                            id="banco_destino_id"
                            name="banco_destino_id"
                            required>

                            <option value="">
                                Selecione o banco de destino
                            </option>

                        </select>

                    </div>

                    <div class="form-group col-md-4">

                        <label for="valor_transferencia">
                            Valor *
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="valor_transferencia"
                            name="valor"
                            min="0.01"
                            step="0.01"
                            required>

                    </div>

                    <div class="form-group col-md-4">

                        <label for="taxa_transferencia">
                            Taxa
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="taxa_transferencia"
                            name="taxa"
                            min="0"
                            step="0.01"
                            value="0.00">

                    </div>

                    <div class="form-group col-md-4">

                        <label for="data_transferencia">
                            Data da Transferência *
                        </label>

                        <input
                            type="datetime-local"
                            class="form-control"
                            id="data_transferencia"
                            name="data_transferencia"
                            required>

                    </div>

                    <div class="form-group col-md-6">

                        <label for="documento_transferencia">
                            Documento
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="documento_transferencia"
                            name="documento"
                            maxlength="100">

                    </div>

                    <div class="form-group col-md-6">

                        <label for="referencia_transferencia">
                            Referência
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="referencia_transferencia"
                            name="referencia"
                            maxlength="100">

                    </div>

                    <div class="form-group col-md-12">

                        <label for="descricao_transferencia">
                            Descrição
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="descricao_transferencia"
                            name="descricao"
                            maxlength="255"
                            placeholder="Ex.: Transferência para reforço de saldo">

                    </div>

                    <div class="form-group col-md-12">

                        <label for="observacoes_transferencia">
                            Observações
                        </label>

                        <textarea
                            class="form-control"
                            id="observacoes_transferencia"
                            name="observacoes"
                            rows="3"></textarea>

                    </div>

                </div>

            </div>

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
                    id="btnGuardarTransferencia">

                    <i class="fa fa-exchange-alt"></i>
                    Transferir

                </button>

                <button
                    type="button"
                    class="btn btn-info"
                    id="btnExtratoBancario">

                    <i class="fa fa-file"></i>
                    Extrato Bancário

                </button>

            </div>

        </form>

    </div>

</div>


<!-- ==================================================
MODAL DETALHES DO MOVIMENTO
================================================== -->

<div
    id="modalDetalhesMovimento"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h4 class="modal-title">
                    Detalhes do Movimento
                </h4>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">

                    &times;

                </button>

            </div>

            <div class="modal-body">

                <div class="row">

                    <div class="col-md-6">

                        <p>
                            <strong>Banco:</strong>
                            <span id="detalhe_banco">-</span>
                        </p>

                        <p>
                            <strong>Tipo:</strong>
                            <span id="detalhe_tipo">-</span>
                        </p>

                        <p>
                            <strong>Categoria:</strong>
                            <span id="detalhe_categoria">-</span>
                        </p>

                        <p>
                            <strong>Data:</strong>
                            <span id="detalhe_data">-</span>
                        </p>

                    </div>

                    <div class="col-md-6">

                        <p>
                            <strong>Valor:</strong>
                            <span id="detalhe_valor">-</span>
                        </p>

                        <p>
                            <strong>Saldo anterior:</strong>
                            <span id="detalhe_saldo_anterior">-</span>
                        </p>

                        <p>
                            <strong>Saldo posterior:</strong>
                            <span id="detalhe_saldo_posterior">-</span>
                        </p>

                        <p>
                            <strong>Operador:</strong>
                            <span id="detalhe_operador">-</span>
                        </p>

                    </div>

                    <div class="col-md-12">

                        <hr>

                        <p>
                            <strong>Descrição:</strong>
                            <span id="detalhe_descricao">-</span>
                        </p>

                        <p>
                            <strong>Documento:</strong>
                            <span id="detalhe_documento">-</span>
                        </p>

                        <p>
                            <strong>Referência:</strong>
                            <span id="detalhe_referencia">-</span>
                        </p>

                        <p>
                            <strong>Observações:</strong>
                            <span id="detalhe_observacoes">-</span>
                        </p>

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-dismiss="modal">

                    Fechar

                </button>

            </div>

        </div>

    </div>

</div>

<!-- ==================================================
MODAL EXTRATO BANCÁRIO
================================================== -->

<div
    id="modalExtratoBancario"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h4 class="modal-title">
                    Extrato Bancário
                </h4>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">

                    &times;

                </button>

            </div>

            <div class="modal-body">

                <div class="form-row">

                    <div class="form-group col-md-4">

                        <label for="extrato_banco_id">
                            Banco *
                        </label>

                        <select
                            id="extrato_banco_id"
                            class="form-control">

                            <option value="">
                                Selecione o banco
                            </option>

                        </select>

                    </div>

                    <div class="form-group col-md-3">

                        <label for="extrato_data_inicio">
                            Data Inicial
                        </label>

                        <input
                            type="date"
                            id="extrato_data_inicio"
                            class="form-control">

                    </div>

                    <div class="form-group col-md-3">

                        <label for="extrato_data_fim">
                            Data Final
                        </label>

                        <input
                            type="date"
                            id="extrato_data_fim"
                            class="form-control">

                    </div>

                    <div class="form-group col-md-2">

                        <label>
                            &nbsp;
                        </label>

                        <button
                            type="button"
                            class="btn btn-primary btn-block"
                            id="btnCarregarExtratoBancario">

                            <i class="fa fa-search"></i>
                            Consultar

                        </button>

                    </div>

                </div>

                <div class="row mb-3">

                    <div class="col-md-4">

                        <div class="card">

                            <div class="card-body">

                                <small class="text-muted">
                                    Saldo Inicial do Período
                                </small>

                                <h4 id="extrato_saldo_inicial">
                                    0,00 MZN
                                </h4>

                            </div>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="card">

                            <div class="card-body">

                                <small class="text-muted">
                                    Total de Entradas
                                </small>

                                <h4 id="extrato_total_entradas">
                                    0,00 MZN
                                </h4>

                            </div>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="card">

                            <div class="card-body">

                                <small class="text-muted">
                                    Total de Saídas
                                </small>

                                <h4 id="extrato_total_saidas">
                                    0,00 MZN
                                </h4>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="table-responsive">

                    <table
                        id="extratoBancarioTable"
                        class="table table-striped table-bordered">

                        <thead>

                            <tr>

                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Categoria</th>
                                <th>Descrição</th>
                                <th>Documento</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Saldo</th>

                            </tr>

                        </thead>

                        <tbody></tbody>

                    </table>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-dismiss="modal">

                    Fechar

                </button>

            </div>

        </div>

    </div>

</div>