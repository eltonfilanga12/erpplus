<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">


        <!-- Page Header -->

        <div class="page-header">

            <div class="row align-items-center">


                <div class="col">

                    <h3 class="page-title">
                        Compras
                    </h3>


                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>


                        <li class="breadcrumb-item active">
                            Compras
                        </li>

                    </ul>

                </div>



                <div class="col-auto">


                    <button
                        class="btn btn-primary"
                        data-toggle="modal"
                        data-target="#modalCompra">


                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Nova Compra
                        </span>


                    </button>


                </div>


            </div>

        </div>





        <!-- Tabela -->


        <div class="table-responsive">


            <table
                id="comprasTable"
                class="table table-striped custom-table mb-0">


                <thead>

                    <thead>

                        <tr>

                            <th>ID</th>
                            <th>Fornecedor</th>
                            <th>Centro de Custo</th>
                            <th>Documento</th>
                            <th>Data</th>
                            <th>Vencimento</th>
                            <th>Armazém</th>
                            <th>Total</th>
                            <th>Pagamento</th>
                            <th>Status</th>
                            <th>Criado em</th>

                            <th class="text-right">
                                Acções
                            </th>

                        </tr>

                    </thead>

                </thead>


                <tbody>


                </tbody>


            </table>


        </div>



    </div>

</div>






<!-- Modal Compra -->


<div
    id="modalCompra"
    class="modal fade">


    <div class="modal-dialog modal-xl">


        <form
            id="formCompra"
            class="modal-content">



            <div class="modal-header">


                <h4 class="modal-title">

                    Nova Compra

                </h4>


                <button
                    class="close"
                    data-dismiss="modal">

                    &times;

                </button>


            </div>






            <div class="modal-body">


                <input
                    type="hidden"
                    id="compraId"
                    name="id">



                <div class="form-row">

                    <div class="form-group col-md-4">

                        <label for="fornecedor_id">
                            Fornecedor *
                        </label>

                        <select
                            class="form-control"
                            id="fornecedor_id_compra"
                            name="fornecedor_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>


                    <div class="form-group col-md-4">

                        <label for="armazem_id">
                            Armazém *
                        </label>

                        <select
                            class="form-control"
                            id="armazem_id"
                            name="armazem_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>


                    <div class="form-group col-md-4">

                        <label for="filial_id_compra">
                            Filial
                        </label>

                        <select
                            class="form-control"
                            id="filial_id_compra"
                            name="filial_id">

                            <option value="">
                                Sede Principal / Sem filial
                            </option>

                        </select>

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group col-md-6">

                        <label for="centro_custo_id">
                            Centro de Custo *
                        </label>

                        <select
                            class="form-control"
                            id="centro_custo_id"
                            name="centro_custo_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                        <small class="form-text text-muted">
                            Área ou departamento responsável pelo custo da compra.
                        </small>

                    </div>


                    <div class="form-group col-md-3">

                        <label for="forma_pagamento_id">
                            Forma de Pagamento *
                        </label>

                        <select
                            class="form-control"
                            id="forma_pagamento_id"
                            name="forma_pagamento_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>

                    <div class="form-group col-md-3">

                        <label for="status_id">
                            Status *
                        </label>

                        <select
                            class="form-control"
                            id="compra_status_id"
                            name="status_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>


                </div>

                <div class="form-row">

                    <div class="form-group col-md-4">

                        <label for="condicao_pagamento">
                            Condição de Pagamento *
                        </label>

                        <select
                            class="form-control"
                            id="condicao_pagamento"
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


                    <div class="form-group col-md-4">

                        <label for="data_compra">
                            Data da Compra *
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="data_compra"
                            name="data_compra"
                            required>

                    </div>


                    <div
                        class="form-group col-md-4"
                        id="grupoDataVencimento">

                        <label for="data_vencimento">
                            Data de Vencimento *
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="data_vencimento"
                            name="data_vencimento"
                            required>

                    </div>

                    <!-- ========================================= -->
                    <!-- PAGAMENTO À VISTA -->
                    <!-- ========================================= -->

                    <div
                        class="form-group col-md-4"
                        id="grupoOrigemPagamentoCompra"
                        style="display:none;">
                        <label for="origem_pagamento">
                            Pagar por
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="origem_pagamento"
                            name="origem_pagamento">
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


                    <!-- CAIXA -->

                    <div
                        class="form-group col-md-4"
                        id="grupoCaixaPagamentoCompra"
                        style="display:none;">
                        <label for="caixa_id_compra">
                            Caixa
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="caixa_id_compra"
                            name="caixa_id">
                            <option value="">
                                Seleccione o caixa
                            </option>
                        </select>
                    </div>


                    <!-- BANCO -->

                    <div
                        class="form-group col-md-4"
                        id="grupoBancoPagamentoCompra"
                        style="display:none;">
                        <label for="banco_id_compra">
                            Conta Bancária
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="banco_id_compra"
                            name="banco_id">
                            <option value="">
                                Seleccione a conta bancária
                            </option>
                        </select>
                    </div>

                </div>


                <hr>


                <button
                    type="button"
                    class="btn btn-success btn-sm"
                    id="adicionarProduto">


                    <i class="fa fa-plus"></i>

                    Adicionar Produto

                </button>

                <div class="form-row">


                    <div class="form-group col-md-6">

                        <label>
                            Produto
                        </label>


                        <div
                            id="produtoSelectContainer"
                            class="produto-select-container">

                            <select
                                class="form-control"
                                id="produto_id"
                                style="width: 100%;">

                                <option value="">
                                    Seleccione o produto
                                </option>

                            </select>

                        </div>


                    </div>




                    <div class="form-group col-md-3">

                        <label>
                            Quantidade
                        </label>


                        <input
                            type="number"
                            class="form-control"
                            id="quantidade"
                            value="1"
                            min="1">


                    </div>

                    <div class="form-group col-md-3">

                        <label for="preco_unitario_item">
                            Preço Unitário
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            id="preco_unitario_item">

                    </div>



                </div>


                <hr>
                <h5>

                    Produtos

                </h5>




                <table
                    class="table table-bordered"
                    id="tabelaItens">

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


                    <tbody id="listaItens">

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
                            id="subtotal"
                            name="subtotal">



                        <label>
                            Desconto
                        </label>


                        <input
                            class="form-control"
                            id="desconto"
                            name="desconto"
                            value="0">



                        <div class="form-group mt-3">

                            <div class="custom-control custom-switch">

                                <input
                                    type="checkbox"
                                    class="custom-control-input"
                                    id="compra_imposto_habilitado"
                                    name="imposto_habilitado"
                                    value="1"
                                    checked>

                                <label
                                    class="custom-control-label"
                                    for="compra_imposto_habilitado">

                                    Aplicar imposto (16%)

                                </label>

                            </div>

                            <small class="form-text text-muted">
                                O imposto é calculado automaticamente.
                                Desative apenas quando a compra não estiver sujeita a imposto.
                            </small>

                        </div>


                        <label>
                            Taxa de Imposto (%)
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="compra_taxa_imposto"
                            name="taxa_imposto"
                            value="16.00"
                            step="0.01"
                            readonly>


                        <label>
                            Imposto
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="imposto"
                            name="imposto"
                            value="0.00"
                            step="0.01"
                            readonly>

                        <label>
                            Total
                        </label>


                        <input
                            readonly
                            class="form-control"
                            id="total"
                            name="total">



                    </div>


                </div>






                <div class="form-group mt-3">


                    <label>
                        Observações
                    </label>


                    <textarea
                        class="form-control"
                        name="observacoes"
                        id="observacoes"></textarea>


                </div>





            </div>







            <div class="modal-footer">


                <button
                    class="btn btn-light"
                    data-dismiss="modal">

                    Cancelar

                </button>



                <button
                    class="btn btn-primary"
                    type="submit">


                    <i class="fa fa-save"></i>

                    Guardar


                </button>



            </div>




        </form>


    </div>


</div>








<!-- Modal Eliminar -->


<div
    id="modalEliminarCompra"
    class="modal fade">


    <div
        class="modal-dialog modal-dialog-centered">



        <form
            id="formEliminarCompra"
            class="modal-content">



            <div class="modal-header">


                <h5>
                    Eliminar Compra
                </h5>


            </div>




            <div class="modal-body">


                <input
                    type="hidden"
                    id="eliminarCompraId"
                    name="id">



                <p>
                    Pretende eliminar esta compra?
                </p>



            </div>






            <div class="modal-footer">


                <button
                    class="btn btn-light"
                    data-dismiss="modal">

                    Cancelar

                </button>




                <button
                    class="btn btn-danger"
                    type="submit">


                    <i class="fa fa-trash"></i>

                    Eliminar


                </button>



            </div>




        </form>


    </div>


</div>
<style>
    /* =====================================================
   SELECT2 - PRODUTO DA COMPRA
===================================================== */

    .produto-select-container {
        position: relative;
    }


    /* Select principal */

    #produtoSelectContainer .select2-container {
        width: 100% !important;
    }


    /* =====================================================
   DROPDOWN
===================================================== */

    #produtoSelectContainer .select2-container--open:not(.select2) {
        position: absolute !important;

        top: 100% !important;
        left: 0 !important;

        width: 100% !important;

        margin-top: 2px;

        z-index: 1065;
    }


    /*
 * Não permitir que o dropdown fique acima.
 */

    #produtoSelectContainer .select2-dropdown--above,
    #produtoSelectContainer .select2-dropdown--below {

        top: auto !important;
        bottom: auto !important;
    }


    /* Dropdown */

    #produtoSelectContainer .select2-dropdown {

        position: relative !important;

        width: 100% !important;

        border-radius: 4px;

        box-sizing: border-box;
    }


    /* =====================================================
   PESQUISA
===================================================== */

    .select2-produto-compra .select2-search--dropdown {

        padding: 8px;
    }


    .select2-produto-compra .select2-search__field {

        width: 100% !important;

        height: 36px;

        padding: 6px 10px;
    }


    /* =====================================================
   PRODUTOS
===================================================== */

    .select2-produto-compra .select2-results__option {

        min-height: 42px;

        padding: 9px 12px;
    }


    /*
 * Máximo de aproximadamente
 * 4 produtos visíveis.
 */

    .select2-produto-compra .select2-results__options {

        max-height: 168px !important;

        overflow-y: auto !important;

        overflow-x: hidden !important;
    }
</style>