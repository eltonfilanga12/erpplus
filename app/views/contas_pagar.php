<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Contas a Pagar
                    </h3>


                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>


                        <li class="breadcrumb-item active">
                            Contas a Pagar
                        </li>


                    </ul>

                </div>



                <div class="col-auto">


                    <button
                        class="btn btn-primary"
                        data-toggle="modal"
                        data-target="#modalContaPagar">


                        <i class="fa fa-plus"></i>

                        Nova Conta


                    </button>


                </div>


            </div>

        </div>







        <!-- TABELA -->


        <div class="table-responsive">


            <table
                id="contasPagarTable"
                class="table table-striped custom-table mb-0">


                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Fornecedor</th>
                        <th>Documento</th>
                        <th>Descrição</th>
                        <th>Centro de Custo</th>
                        <th>Emissão</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Pago</th>
                        <th>Saldo</th>
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









<!-- =========================
MODAL CONTA
========================= -->


<div
    class="modal fade"
    id="modalContaPagar">


    <div class="modal-dialog modal-lg">


        <form
            id="formContaPagar"
            class="modal-content">



            <div class="modal-header">


                <h4 class="modal-title">

                    Conta a Pagar

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
                    name="id"
                    id="contaPagarId">





                <div class="form-row">



                    <div class="form-group col-md-6">


                        <label>

                            Fornecedor *

                        </label>


                        <select
                            class="form-control"
                            id="fornecedor_id"
                            name="fornecedor_id"
                            required>


                            <option value="">

                                Seleccione

                            </option>


                        </select>


                    </div>






                    <div class="form-group col-md-6">


                        <label>

                            Forma Pagamento

                        </label>


                        <select
                            class="form-control"
                            id="forma_pagamento_id"
                            name="forma_pagamento_id">


                            <option value="">

                                Seleccione

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
                            Área, departamento ou projecto responsável pela despesa.
                        </small>

                    </div>


                    <div class="form-group col-md-6">

                        <label for="filial_id_conta">
                            Filial
                        </label>

                        <select
                            class="form-control"
                            id="filial_id_conta"
                            name="filial_id">

                            <option value="">
                                Sede Principal / Sem filial
                            </option>

                        </select>

                    </div>

                </div>


                <div class="form-row">



                    <div class="form-group col-md-4">


                        <label>

                            Documento

                        </label>


                        <input
                            class="form-control"
                            id="numero_documento"
                            name="numero_documento">


                    </div>






                    <div class="form-group col-md-4">


                        <label>

                            Referência

                        </label>


                        <input
                            class="form-control"
                            id="referencia"
                            name="referencia">


                    </div>






                    <div class="form-group col-md-4">


                        <label>

                            Descrição *

                        </label>


                        <input
                            class="form-control"
                            id="descricao"
                            name="descricao"
                            required>


                    </div>


                </div>


                <div class="form-row">

                    <div class="form-group col-md-3">

                        <label for="valor">
                            Valor *
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="form-control"
                            id="valor"
                            name="valor"
                            required>

                    </div>


                    <div class="form-group col-md-3">

                        <label for="desconto">
                            Desconto
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            value="0.00"
                            class="form-control"
                            id="desconto"
                            name="desconto">

                    </div>


                    <div class="form-group col-md-3">

                        <label for="juros">
                            Juros
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            value="0.00"
                            class="form-control"
                            id="juros"
                            name="juros">

                    </div>


                    <div class="form-group col-md-3">

                        <label for="multa">
                            Multa
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            value="0.00"
                            class="form-control"
                            id="multa"
                            name="multa">

                    </div>

                </div>







                <div class="form-row">



                    <div class="form-group col-md-6">


                        <label>

                            Data Emissão

                        </label>


                        <input
                            type="date"
                            class="form-control"
                            id="data_emissao"
                            name="data_emissao">


                    </div>





                    <div class="form-group col-md-6">


                        <label>

                            Data Vencimento

                        </label>


                        <input
                            type="date"
                            class="form-control"
                            id="data_vencimento"
                            name="data_vencimento">


                    </div>



                </div>







                <div class="form-group">


                    <label>

                        Observações

                    </label>


                    <textarea
                        class="form-control"
                        id="observacoes"
                        name="observacoes"></textarea>


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
                    class="btn btn-primary">

                    <i class="fa fa-save"></i>

                    Guardar

                </button>



            </div>



        </form>


    </div>

</div>



<!-- =========================
MODAL PAGAMENTO
========================= -->

<div
    class="modal fade"
    id="modalPagamento"
    tabindex="-1">

    <div class="modal-dialog modal-lg">

        <form
            id="formPagamento"
            class="modal-content">

            <div class="modal-header">

                <h4 class="modal-title">
                    Registar Pagamento
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
                    id="pagamento_conta_id"
                    name="id">

                <input
                    type="hidden"
                    id="pagamento_centro_custo_id"
                    name="centro_custo_id">

                <input
                    type="hidden"
                    id="pagamento_filial_id"
                    name="filial_id">


                <!-- Informações da conta -->

                <div class="alert alert-light border">

                    <div class="row">

                        <div class="col-md-6">

                            <strong>Fornecedor:</strong>

                            <span id="pagamento_fornecedor">
                                -
                            </span>

                        </div>

                        <div class="col-md-3">

                            <strong>Saldo:</strong>

                            <span
                                id="pagamento_saldo"
                                class="text-danger">

                                0,00 MZN

                            </span>

                        </div>

                        <div class="col-md-3">

                            <strong>Centro:</strong>

                            <span id="pagamento_centro_nome">
                                -
                            </span>

                        </div>

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group col-md-4">

                        <label for="valor_pago">
                            Valor do Pagamento *
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            id="valor_pago"
                            name="valor"
                            class="form-control"
                            required>

                    </div>


                    <div class="form-group col-md-4">

                        <label for="pagamento_data">
                            Data do Pagamento *
                        </label>

                        <input
                            type="date"
                            id="pagamento_data"
                            name="data_pagamento"
                            class="form-control"
                            required>

                    </div>


                    <div class="form-group col-md-4">

                        <label for="pagamento_forma_id">
                            Forma de Pagamento *
                        </label>

                        <select
                            id="pagamento_forma_id"
                            name="forma_pagamento_id"
                            class="form-control"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group col-md-6">

                        <label for="pagamento_origem">
                            Origem do Dinheiro *
                        </label>

                        <select
                            id="pagamento_origem"
                            name="origem_pagamento"
                            class="form-control"
                            required>

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


                    <div class="form-group col-md-6">

                        <label for="pagamento_caixa_id">
                            Caixa
                        </label>

                        <select
                            id="pagamento_caixa_id"
                            name="caixa_id"
                            class="form-control">

                            <option value="">
                                Seleccione o caixa
                            </option>

                        </select>

                    </div>


                    <div
                        class="form-group col-md-6"
                        id="grupoPagamentoBanco"
                        style="display: none;">

                        <label for="pagamento_banco_id">
                            Banco
                        </label>

                        <select
                            id="pagamento_banco_id"
                            name="banco_id"
                            class="form-control">

                            <option value="">
                                Seleccione o banco
                            </option>

                        </select>

                    </div>

                    <div class="form-group col-md-6">
                        <label for="pagamento_comprovativo">Comprovativo de Pagamento (Opcional)</label>
                        <input
                            type="file"
                            class="form-field-input"
                            id="pagamento_comprovativo"
                            name="comprovativo"
                            accept=".pdf,.png,.jpg,.jpeg">
                        <small class="form-text text-muted">Ficheiros permitidos: PDF, PNG, JPG, JPEG (Máx. 5MB).</small>
                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group col-md-6">

                        <label for="pagamento_documento">
                            Número do Documento
                        </label>

                        <input
                            type="text"
                            id="pagamento_documento"
                            name="numero_documento"
                            class="form-control"
                            maxlength="100">

                    </div>


                    <div class="form-group col-md-6">

                        <label for="pagamento_referencia">
                            Referência
                        </label>

                        <input
                            type="text"
                            id="pagamento_referencia"
                            name="referencia"
                            class="form-control"
                            maxlength="100">

                    </div>

                </div>


                <div class="form-group">

                    <label for="pagamento_observacoes">
                        Observações
                    </label>

                    <textarea
                        id="pagamento_observacoes"
                        name="observacoes"
                        class="form-control"
                        rows="3"></textarea>

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
                    class="btn btn-success"
                    id="btnConfirmarPagamento"
                    type="submit">

                    <i class="fa fa-check"></i>
                    Confirmar Pagamento

                </button>

            </div>

        </form>

    </div>

</div>


<!-- Modal Histórico de Pagamentos -->
<div class="modal fade" id="modalHistoricoPagamentos" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-history mr-1"></i> Histórico de Pagamentos — <span id="historico_conta_id_titulo"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Resumo da Conta -->
                <div class="row mb-3 bg-light py-2 rounded">
                    <div class="col-md-4">
                        <strong>Fornecedor:</strong> <span id="hist_fornecedor">-</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Valor Total:</strong> <span id="hist_valor_total">-</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Saldo Pendente:</strong> <span id="hist_saldo" class="text-danger font-weight-bold">-</span>
                    </div>
                </div>

                <!-- Tabela de Baixas dentro do Modal -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="tabelaHistoricoPagamentos" style="width:100%">
                        <thead class="thead-light">
                            <tr>
                                <th>Data de Pag</th>
                                <th>Forma de Pagamento</th>
                                <th>Doc. / Ref.</th>
                                <th>Observações</th>
                                <th class="text-center">Comprovativo</th>
                                <th class="text-right">Valor Pago</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyHistoricoPagamentos">
                            <!-- Preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- =========================
MODAL ELIMINAR
========================= -->


<div
    class="modal fade"
    id="modalEliminarPagar">


    <div class="modal-dialog modal-dialog-centered">


        <form
            id="formEliminarPagar"
            class="modal-content">



            <div class="modal-body">


                <input
                    type="hidden"
                    id="eliminarPagarId">



                <h5>

                    Eliminar esta conta?

                </h5>



            </div>





            <div class="modal-footer">


                <button
                    class="btn btn-danger"
                    type="submit">


                    Eliminar


                </button>


            </div>



        </form>


    </div>


</div>