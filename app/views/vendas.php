<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">


        <!-- Page Header -->

        <div class="page-header">

            <div class="row align-items-center">


                <div class="col">


                    <h3 class="page-title">
                        Vendas
                    </h3>


                    <ul class="breadcrumb">


                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">

                                Dashboard

                            </a>

                        </li>


                        <li class="breadcrumb-item active">

                            Vendas

                        </li>


                    </ul>


                </div>





                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="btnVendaRapida">

                        <i class="fa fa-bolt"></i>

                        Venda Rápida

                    </button>
                    <button
                        class="btn btn-primary"
                        data-toggle="modal"
                        data-target="#modalVenda">


                        <i class="fa fa-plus"></i>

                        Nova Venda


                    </button>


                </div>


            </div>

        </div>









        <!-- Tabela -->

        <div class="table-responsive">


            <table
                id="vendasTable"
                class="table table-striped custom-table mb-0">


                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Centro de Receita</th>
                        <th>Armazém</th>
                        <th>Documento</th>
                        <th>Data</th>
                        <th>Vencimento</th>
                        <th>Total</th>
                        <th>Pagamento</th>
                        <th>Status</th>
                        <th>Ações</th>

                    </tr>

                </thead>


                <tbody>


                </tbody>


            </table>


        </div>



    </div>

</div>


<!-- MODAL VENDA -->


<div
    class="modal fade"
    id="modalVenda">


    <div class="modal-dialog modal-xl">


        <form
            id="formVenda"
            class="modal-content">



            <div class="modal-header">


                <h4 class="modal-title">

                    Nova Venda

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
                    id="vendaId"
                    name="id">

                <div class="form-row">

                    <div class="form-group col-md-4">

                        <label for="venda_cliente_id">
                            Cliente *
                        </label>

                        <select
                            class="form-control"
                            id="venda_cliente_id"
                            name="cliente_id"
                            required>

                            <option value="">
                                Seleccione o cliente
                            </option>

                        </select>

                    </div>


                    <div class="form-group col-md-4">

                        <label for="venda_armazem_id">
                            Armazém *
                        </label>

                        <select
                            class="form-control"
                            id="venda_armazem_id"
                            name="armazem_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>


                    <div class="form-group col-md-4">

                        <label for="venda_filial_id">
                            Filial
                        </label>

                        <select
                            class="form-control"
                            id="venda_filial_id"
                            name="filial_id">

                            <option value="">
                                Sede Principal / Sem filial
                            </option>

                        </select>

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group col-md-4">

                        <label for="venda_centro_custo_id">
                            Centro de Receita *
                        </label>

                        <select
                            class="form-control"
                            id="venda_centro_custo_id"
                            name="centro_custo_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                        <small class="form-text text-muted">
                            Área, projecto ou departamento que gerou a receita.
                        </small>

                    </div>


                    <div class="form-group col-md-4">

                        <label for="venda_forma_pagamento_id">
                            Forma de Pagamento *
                        </label>

                        <select
                            class="form-control"
                            id="venda_forma_pagamento_id"
                            name="forma_pagamento_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>

                    <div class="form-group col-md-4">

                        <label for="venda_condicao_pagamento">
                            Condição de Pagamento *
                        </label>

                        <select
                            class="form-control"
                            id="venda_condicao_pagamento"
                            name="condicao_pagamento"
                            required>

                            <option value="APRAZO">
                                A prazo
                            </option>

                            <option value="AVISTA">
                                À vista
                            </option>

                        </select>

                    </div>

                </div>

                <div class="form-row">




                    <div class="form-group col-md-4">

                        <label for="venda_data">
                            Data da Venda *
                        </label>

                        <input
                            type="datetime-local"
                            class="form-control"
                            id="venda_data"
                            name="data_venda"
                            required>

                    </div>


                    <div
                        class="form-group col-md-4"
                        id="grupoVendaVencimento">

                        <label for="venda_data_vencimento">
                            Data de Vencimento *
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="venda_data_vencimento"
                            name="data_vencimento"
                            required>

                    </div>

                    <div
                        class="form-group col-md-4"
                        id="grupoVendaParcelas"
                        style="display:none;">

                        <label for="venda_numero_parcelas">
                            Número de Parcelas *
                        </label>

                        <select
                            class="form-control"
                            id="venda_numero_parcelas"
                            name="numero_parcelas">

                            <option value="1">
                                1 parcela
                            </option>

                            <option value="2">
                                2 parcelas
                            </option>

                            <option value="3">
                                3 parcelas
                            </option>

                        </select>

                    </div>

                    <div
                        class="form-group col-md-4"
                        id="grupoVendaTipoParcelamento"
                        style="display:none;">

                        <label for="venda_tipo_parcelamento">
                            Distribuição das Parcelas
                        </label>

                        <select
                            class="form-control"
                            id="venda_tipo_parcelamento"
                            name="tipo_parcelamento">

                            <option value="AUTOMATICO" selected>
                                Automática
                            </option>

                            <option value="PERSONALIZADO">
                                Personalizada
                            </option>

                        </select>

                        <small class="form-text text-muted">
                            Na opção personalizada pode definir
                            valores diferentes para cada parcela.
                        </small>

                    </div>

                    <div
                        class="col-12"
                        id="grupoVendaPlanoParcelas"
                        style="display:none;">

                        <div class="card border">

                            <div class="card-header py-2">

                                <strong>
                                    Plano de Pagamento
                                </strong>

                            </div>


                            <div class="card-body">

                                <div class="row">

                                    <!-- PARCELA 1 -->

                                    <div
                                        class="col-md-4"
                                        id="grupoVendaParcela1">

                                        <div class="form-group">

                                            <label>
                                                1ª Parcela
                                            </label>

                                            <input
                                                type="number"
                                                class="form-control"
                                                id="venda_parcela_1_valor"
                                                name="parcela_1_valor"
                                                step="0.01"
                                                readonly>

                                        </div>

                                        <div class="form-group">

                                            <label>
                                                Vencimento
                                            </label>

                                            <input
                                                type="date"
                                                class="form-control"
                                                id="venda_parcela_1_data"
                                                name="parcela_1_data">

                                        </div>

                                    </div>


                                    <!-- PARCELA 2 -->

                                    <div
                                        class="col-md-4"
                                        id="grupoVendaParcela2"
                                        style="display:none;">

                                        <div class="form-group">

                                            <label>
                                                2ª Parcela
                                            </label>

                                            <input
                                                type="number"
                                                class="form-control"
                                                id="venda_parcela_2_valor"
                                                name="parcela_2_valor"
                                                step="0.01"
                                                readonly>

                                        </div>

                                        <div class="form-group">

                                            <label>
                                                Vencimento
                                            </label>

                                            <input
                                                type="date"
                                                class="form-control"
                                                id="venda_parcela_2_data"
                                                name="parcela_2_data">

                                        </div>

                                    </div>


                                    <!-- PARCELA 3 -->

                                    <div
                                        class="col-md-4"
                                        id="grupoVendaParcela3"
                                        style="display:none;">

                                        <div class="form-group">

                                            <label>
                                                3ª Parcela
                                            </label>

                                            <input
                                                type="number"
                                                class="form-control"
                                                id="venda_parcela_3_valor"
                                                name="parcela_3_valor"
                                                step="0.01"
                                                readonly>

                                        </div>

                                        <div class="form-group">

                                            <label>
                                                Vencimento
                                            </label>

                                            <input
                                                type="date"
                                                class="form-control"
                                                id="venda_parcela_3_data"
                                                name="parcela_3_data">

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div
                        class="form-group col-md-4"
                        id="grupoVendaDestino">

                        <label for="venda_destino_recebimento">
                            Destino do Recebimento *
                        </label>

                        <select
                            class="form-control"
                            id="venda_destino_recebimento"
                            name="destino_recebimento">

                            <option value="">
                                Seleccione
                            </option>

                            <option value="CAIXA">
                                Caixa
                            </option>

                            <option value="BANCO">
                                Banco
                            </option>

                        </select>

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group col-md-3">

                        <label>Nº Factura</label>

                        <input
                            type="text"
                            class="form-control"
                            id="venda_numero_fatura"
                            name="numero_fatura"
                            readonly
                            required>

                    </div>


                    <div class="form-group col-md-3">


                        <label>

                            Tipo Documento

                        </label>

                        <select
                            class="form-control"
                            name="tipo_documento"
                            id="venda_tipo_documento">


                            <option value="FACTURA">
                                Factura
                            </option>


                            <option value="FACTURA_RECIBO">
                                Factura Recibo
                            </option>


                            <option value="RECIBO">
                                Recibo
                            </option>


                            <option value="PROFORMA">
                                Proforma
                            </option>


                            <option value="COTACAO">
                                Cotação
                            </option>


                        </select>


                    </div>




                    <div class="form-group col-md-3">

                        <label>Nº Documento</label>

                        <input
                            type="text"
                            class="form-control"
                            name="numero_documento"
                            id="venda_numero_documento">

                    </div>

                    <div class="form-group col-md-3">


                        <label>

                            Origem

                        </label>

                        <select
                            class="form-control"
                            name="origem"
                            id="venda_origem">

                            <option>
                                BALCAO
                            </option>


                            <option>
                                ONLINE
                            </option>


                            <option>
                                APP
                            </option>


                        </select>


                    </div>

                    <div class="form-group col-md-4">

                        <label for="venda_status_id">
                            Status *
                        </label>

                        <select
                            class="form-control"
                            name="status_id"
                            id="venda_status_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>

                    <div
                        class="form-group col-md-4"
                        id="grupoVendaCaixa"
                        style="display:none;">

                        <label for="venda_caixa_id">
                            Caixa *
                        </label>

                        <select
                            class="form-control"
                            id="venda_caixa_id"
                            name="caixa_id">

                            <option value="">
                                Seleccione o caixa
                            </option>

                        </select>

                    </div>


                    <div
                        class="form-group col-md-4"
                        id="grupoVendaBanco"
                        style="display:none;">

                        <label for="venda_banco_id">
                            Conta Bancária *
                        </label>

                        <select
                            class="form-control"
                            id="venda_banco_id"
                            name="banco_id">

                            <option value="">
                                Seleccione a conta bancária
                            </option>

                        </select>

                    </div>


                </div>









                <hr>
                <div class="row mb-3">

                    <div class="col-md-5">

                        <label for="venda_produto_id">
                            Produto
                        </label>

                        <div
                            id="vendaProdutoSelectContainer"
                            class="produto-select-container">

                            <select
                                class="form-control"
                                id="venda_produto_id"
                                style="width: 100%;">

                                <option value="">
                                    Seleccione o produto
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-2">

                        <label for="venda_quantidade">
                            Quantidade
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="venda_quantidade"
                            value="1"
                            min="1"
                            step="0.001">

                    </div>


                    <div class="col-md-3">

                        <label for="venda_preco_unitario">
                            Preço Unitário
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="venda_preco_unitario"
                            min="0"
                            step="0.01">

                    </div>


                    <div class="col-md-2">

                        <label>&nbsp;</label>

                        <button
                            type="button"
                            class="btn btn-success btn-block"
                            id="vendaAdicionarProduto">

                            <i class="fa fa-plus"></i>
                            Adicionar

                        </button>

                    </div>

                </div>

                <h5>

                    Produtos

                </h5>



                <table
                    class="table table-bordered">

                    <thead>


                        <tr>


                            <th>
                                Produto
                            </th>


                            <th width="120">
                                Quantidade
                            </th>


                            <th width="150">
                                Preço
                            </th>


                            <th width="120">
                                Subtotal
                            </th>


                            <th width="50">
                                #
                            </th>


                        </tr>


                    </thead>

                    <tbody id="venda_lista_itens">

                    </tbody>


                </table>

                <hr>


                <div class="row">


                    <div class="col-md-4 ml-auto">


                        <label>
                            Subtotal
                        </label>


                        <input
                            readonly
                            class="form-control"
                            id="venda_subtotal"
                            name="subtotal"
                            value="0.00">

                        <label>
                            Desconto
                        </label>


                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            id="venda_desconto"
                            name="desconto"
                            value="0.00">

                        <div class="form-group mt-3">

                            <div class="custom-control custom-switch">

                                <input
                                    type="checkbox"
                                    class="custom-control-input"
                                    id="venda_imposto_habilitado"
                                    name="imposto_habilitado"
                                    value="1"
                                    checked>

                                <label
                                    class="custom-control-label"
                                    for="venda_imposto_habilitado">

                                    Aplicar imposto (16%)

                                </label>

                            </div>

                            <small class="form-text text-muted">
                                O imposto é aplicado automaticamente.
                                Desative apenas quando a venda não estiver sujeita ao imposto.
                            </small>

                        </div>


                        <label for="venda_imposto">
                            Imposto (16%)
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            id="venda_imposto"
                            name="imposto"
                            value="0.00"
                            readonly>


                        <input
                            type="hidden"
                            id="venda_taxa_imposto"
                            name="taxa_imposto"
                            value="16.00">

                        <label>
                            Total
                        </label>


                        <input
                            readonly
                            class="form-control"
                            id="venda_total"
                            name="total"
                            value="0.00">

                    </div>


                </div>


                <div class="form-group mt-3">


                    <label>

                        Observações

                    </label>

                    <textarea
                        class="form-control"
                        name="observacoes"
                        id="venda_observacoes"></textarea>
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
                    class="btn btn-primary"
                    type="submit">


                    <i class="fa fa-save"></i>

                    Guardar Venda


                </button>



            </div>




        </form>


    </div>


</div>

<!-- ===================================================== -->
<!-- MODAL VENDA RÁPIDA -->
<!-- ===================================================== -->

<div
    class="modal fade"
    id="modalVendaRapida"
    tabindex="-1">

    <div
        class="modal-dialog modal-xl">

        <form
            id="formVendaRapida"
            class="modal-content">


            <div class="modal-header">

                <div>

                    <h4 class="modal-title mb-0">

                        <i class="fa fa-bolt text-warning"></i>

                        Venda Rápida

                    </h4>

                    <small class="text-muted">
                        Venda de balcão com preenchimento simplificado
                    </small>

                </div>


                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">

                    &times;

                </button>

            </div>


            <div class="modal-body">


                <!-- ========================================== -->
                <!-- DADOS BÁSICOS -->
                <!-- ========================================== -->

                <div class="row">

                    <div class="form-group col-md-4">

                        <label>
                            Cliente
                        </label>

                        <select
                            class="form-control"
                            id="vendaRapidaCliente">

                            <option value="">
                                Consumidor Final
                            </option>

                        </select>

                        <small class="form-text text-muted">
                            Por padrão será usado Consumidor Final.
                        </small>

                    </div>


                    <div class="form-group col-md-4">

                        <label>
                            Armazém
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="vendaRapidaArmazem"
                            required>

                            <option value="">
                                Seleccione o armazém
                            </option>

                        </select>

                    </div>


                    <div class="form-group col-md-4">

                        <label>
                            Forma de Pagamento
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="vendaRapidaFormaPagamento"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>

                </div>


                <!-- ========================================== -->
                <!-- RECEBIMENTO -->
                <!-- ========================================== -->

                <div class="row">

                    <div class="form-group col-md-4">

                        <label>
                            Receber em
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="vendaRapidaDestino"
                            required>

                            <option value="CAIXA">
                                Caixa
                            </option>

                            <option value="BANCO">
                                Banco / Conta
                            </option>

                        </select>

                    </div>


                    <div
                        class="form-group col-md-4"
                        id="grupoVendaRapidaCaixa">

                        <label>
                            Caixa
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="vendaRapidaCaixa">

                            <option value="">
                                Seleccione o caixa
                            </option>

                        </select>

                    </div>


                    <div
                        class="form-group col-md-4"
                        id="grupoVendaRapidaBanco"
                        style="display:none;">

                        <label>
                            Conta Bancária
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="vendaRapidaBanco">

                            <option value="">
                                Seleccione a conta
                            </option>

                        </select>

                    </div>

                </div>


                <hr>


                <!-- ========================================== -->
                <!-- ADICIONAR PRODUTO -->
                <!-- ========================================== -->

                <div class="row align-items-end">

                    <div class="form-group col-md-5">

                        <label>
                            Produto
                        </label>

                        <div
                            id="vendaRapidaProdutoSelectContainer"
                            class="produto-select-container">

                            <select
                                class="form-control"
                                id="vendaRapidaProduto"
                                style="width: 100%;">

                                <option value="">
                                    Seleccione o produto
                                </option>

                            </select>

                        </div>

                        </select>

                    </div>


                    <div class="form-group col-md-2">

                        <label>
                            Quantidade
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="vendaRapidaQuantidade"
                            value="1"
                            min="0.001"
                            step="0.001">

                    </div>


                    <div class="form-group col-md-3">

                        <label>
                            Preço
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="vendaRapidaPreco"
                            min="0"
                            step="0.01">

                    </div>


                    <div class="form-group col-md-2">

                        <button
                            type="button"
                            class="btn btn-success btn-block"
                            id="btnAdicionarProdutoVendaRapida">

                            <i class="fa fa-plus"></i>

                            Adicionar

                        </button>

                    </div>

                </div>


                <!-- ========================================== -->
                <!-- ITENS -->
                <!-- ========================================== -->

                <div class="table-responsive">

                    <table
                        class="table table-bordered table-hover">

                        <thead>

                            <tr>

                                <th>
                                    Produto
                                </th>

                                <th width="120">
                                    Qtd.
                                </th>

                                <th width="150">
                                    Preço
                                </th>

                                <th width="150">
                                    Subtotal
                                </th>

                                <th width="60"></th>

                            </tr>

                        </thead>


                        <tbody
                            id="vendaRapidaListaItens">

                        </tbody>

                    </table>

                </div>

                <!-- ========================================== -->
                <!-- TOTAIS / IMPOSTO -->
                <!-- ========================================== -->

                <div class="row">

                    <div class="col-md-5 ml-auto">

                        <div class="card bg-light">

                            <div class="card-body">

                                <!-- IVA -->
                                <div class="form-group mb-3">

                                    <div class="custom-control custom-switch">

                                        <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="vendaRapidaImpostoHabilitado"
                                            checked>

                                        <label
                                            class="custom-control-label"
                                            for="vendaRapidaImpostoHabilitado">

                                            Aplicar imposto (16%)

                                        </label>

                                    </div>

                                    <small class="form-text text-muted">
                                        O imposto é aplicado automaticamente.
                                        Desative apenas quando a venda não estiver
                                        sujeita a imposto.
                                    </small>

                                </div>


                                <!-- SUBTOTAL -->
                                <div
                                    class="d-flex justify-content-between mb-2">

                                    <span>
                                        Subtotal
                                    </span>

                                    <strong
                                        id="vendaRapidaSubtotalTexto">

                                        0,00 MT

                                    </strong>

                                </div>


                                <!-- IMPOSTO -->
                                <div
                                    class="d-flex justify-content-between mb-2">

                                    <span>
                                        IVA (16%)
                                    </span>

                                    <strong
                                        id="vendaRapidaImpostoTexto">

                                        0,00 MT

                                    </strong>

                                </div>


                                <hr>


                                <!-- TOTAL -->
                                <div
                                    class="d-flex justify-content-between align-items-center">

                                    <strong>
                                        TOTAL DA VENDA
                                    </strong>

                                    <h3
                                        class="mb-0"
                                        id="vendaRapidaTotalTexto">

                                        0,00 MT

                                    </h3>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Campos automáticos -->
                <input
                    type="hidden"
                    id="vendaRapidaCentroReceita">

                <input
                    type="hidden"
                    id="vendaRapidaStatus">

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
                    class="btn btn-success"
                    id="btnFinalizarVendaRapida">

                    <i class="fa fa-check"></i>

                    Finalizar Venda

                </button>

            </div>

        </form>

    </div>

</div>
<style>
    /* =====================================================
   SELECT2 - PRODUTOS DAS VENDAS
===================================================== */

    .produto-select-container {
        position: relative;
    }


    /* Select principal */

    .produto-select-container .select2-container {
        width: 100% !important;
    }


    /* =====================================================
   DROPDOWN
===================================================== */

    .produto-select-container .select2-container--open:not(.select2) {

        position: absolute !important;

        top: 100% !important;

        left: 0 !important;

        width: 100% !important;

        margin-top: 2px;

        z-index: 1070;
    }


    /*
 * Dropdown sempre abaixo.
 */

    .produto-select-container .select2-dropdown {

        position: relative !important;

        width: 100% !important;

        box-sizing: border-box;
    }


    /* =====================================================
   PESQUISA
===================================================== */

    .select2-produto-venda .select2-search--dropdown {

        padding: 8px;
    }


    .select2-produto-venda .select2-search__field {

        width: 100% !important;

        height: 36px;

        padding: 6px 10px;
    }


    /* =====================================================
   PRODUTOS
===================================================== */

    .select2-produto-venda .select2-results__option {

        min-height: 42px;

        padding: 9px 12px;
    }


    /*
 * Aproximadamente quatro produtos.
 */

    .select2-produto-venda .select2-results__options {

        max-height: 168px !important;

        overflow-y: auto !important;

        overflow-x: hidden !important;
    }
</style>