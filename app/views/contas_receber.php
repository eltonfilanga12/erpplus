<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">


        <!-- Page Header -->
        <div class="page-header">

            <div class="row align-items-center">


                <div class="col">


                    <h3 class="page-title">
                        Contas a Receber
                    </h3>


                    <ul class="breadcrumb">


                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>


                        <li class="breadcrumb-item active">

                            Contas a Receber

                        </li>


                    </ul>


                </div>



                <div class="col-auto">


                    <button
                        class="btn btn-primary"
                        data-toggle="modal"
                        data-target="#modalContaReceber">


                        <i class="fa fa-plus"></i>

                        Nova Conta


                    </button>


                </div>


            </div>

        </div>





        <!-- Tabela -->

        <div class="table-responsive">


            <table
                id="contasReceberTable"
                class="table table-striped custom-table mb-0">


                <thead>


                    <tr>


                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Documento</th>
                                <th>Cliente</th>
                                <th>Centro de Custo</th>
                                <th>Emissão</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Recebido</th>
                                <th>Saldo</th>
                                <th>Status</th>
                                <th class="text-right">
                                    Ações
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

<!-- Modal Nova / Editar Conta -->

<div
    class="modal fade"
    id="modalContaReceber">


    <div class="modal-dialog modal-lg">


        <form
            id="formContaReceber"
            class="modal-content">



            <div class="modal-header">


                <h4 class="modal-title">

                    Conta a Receber

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
                    id="contaReceberId"
                    name="id">

                <div class="form-row">

                    <div class="form-group col-md-6">

                        <label for="cliente_id">
                            Cliente *
                        </label>

                        <select
                            class="form-control"
                            id="cliente_id"
                            name="cliente_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>


                    <div class="form-group col-md-6">

                        <label for="filial_id_receber">
                            Filial
                        </label>

                        <select
                            class="form-control"
                            id="filial_id_receber"
                            name="filial_id">

                            <option value="">
                                Sede Principal / Sem filial
                            </option>

                        </select>

                    </div>

                </div>








                <div class="form-row">



                    <!-- DOCUMENTO -->

                    <div class="form-group col-md-6">


                        <label>

                            Número Documento

                        </label>


                        <input

                            type="text"

                            class="form-control"

                            id="numero_documento"

                            name="numero_documento">


                    </div>






                    <!-- FORMA PAGAMENTO -->

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

                    <div class="form-group col-md-12">

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
                            Área, departamento ou projecto que gerou esta receita.
                        </small>

                    </div>

                </div>



                <div class="form-row">



                    <!-- EMISSÃO -->

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






                    <!-- VENCIMENTO -->

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
                            class="form-control"
                            id="desconto"
                            name="desconto"
                            value="0.00">

                    </div>


                    <div class="form-group col-md-3">

                        <label for="juros">
                            Juros
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            id="juros"
                            name="juros"
                            value="0.00">

                    </div>


                    <div class="form-group col-md-3">

                        <label for="multa">
                            Multa
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            id="multa"
                            name="multa"
                            value="0.00">

                    </div>

                </div>

                <!-- OBSERVAÇÕES -->


                <div class="form-group">


                    <label>

                        Observações

                    </label>


                    <textarea

                        class="form-control"

                        id="observacoes"

                        name="observacoes"

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

                    type="submit"

                    class="btn btn-primary">


                    <i class="fa fa-save"></i>


                    Guardar


                </button>



            </div>





        </form>


    </div>


</div>
<!-- ===================================== -->
<!-- MODAL ELIMINAR CONTA -->
<!-- ===================================== -->


<div
    class="modal fade"
    id="modalEliminarConta">


    <div class="modal-dialog modal-dialog-centered">


        <form
            id="formEliminarConta"
            class="modal-content">



            <div class="modal-header">


                <h5 class="modal-title">

                    Eliminar Conta

                </h5>


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
                    id="eliminarContaReceberId"
                    name="id">

                <p>

                    Tem certeza que deseja eliminar esta conta?

                </p>



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

                    class="btn btn-danger">


                    <i class="fa fa-trash"></i>


                    Eliminar


                </button>



            </div>



        </form>


    </div>


</div>





<!-- =====================================
MODAL HISTÓRICO DE RECEBIMENTOS
===================================== -->
<div class="modal fade" id="modalHistoricoRecebimentos" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-history"></i> Histórico de Recebimentos</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tabelaHistoricoRecebimentos">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Forma de Pagamento</th>
                                <th>Valor</th>
                                <th>Utilizador</th>
                                <th>Observação</th>
                                <th>Anexo</th>
                                <th class="text-center">Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Preenchido via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>



<!-- =====================================
MODAL RECEBER PAGAMENTO
===================================== -->

<div
    class="modal fade"
    id="modalReceber"
    tabindex="-1">

    <div class="modal-dialog modal-lg">

        <form
            id="formReceber"
            class="modal-content">

            <div class="modal-header">

                <h4 class="modal-title">
                    Registar Recebimento
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
                    id="receberContaId"
                    name="id">

                <input
                    type="hidden"
                    id="receberCentroCustoId"
                    name="centro_custo_id">

                <input
                    type="hidden"
                    id="receberFilialId"
                    name="filial_id">


                <!-- Resumo da conta -->

                <div class="alert alert-light border">

                    <div class="row">

                        <div class="col-md-4">

                            <strong>Cliente:</strong>

                            <span id="receberClienteNome">
                                -
                            </span>

                        </div>

                        <div class="col-md-4">

                            <strong>Saldo pendente:</strong>

                            <span
                                id="receberSaldoConta"
                                class="text-success">

                                0,00 MZN

                            </span>

                        </div>

                        <div class="col-md-4">

                            <strong>Centro:</strong>

                            <span id="receberCentroNome">
                                -
                            </span>

                        </div>

                    </div>

                </div>

                <!-- ===================================== -->
                <!-- PLANO DE PARCELAS -->
                <!-- ===================================== -->

                <div
                    id="grupoReceberParcelas"
                    class="mb-3"
                    style="display:none;">

                    <h5 class="mb-2">
                        Plano de Pagamento
                    </h5>

                    <div class="table-responsive">

                        <table
                            class="table table-bordered table-sm mb-0">

                            <thead>

                                <tr>
                                    <th>Parcela</th>
                                    <th>Vencimento</th>
                                    <th class="text-right">
                                        Valor
                                    </th>
                                    <th class="text-right">
                                        Recebido
                                    </th>
                                    <th class="text-right">
                                        Saldo
                                    </th>
                                    <th>Status</th>
                                    <th style="width:90px;">
                                        Ação
                                    </th>
                                </tr>

                            </thead>

                            <tbody
                                id="receberParcelasBody">

                                <tr>
                                    <td
                                        colspan="7"
                                        class="text-center text-muted">

                                        Nenhuma parcela.

                                    </td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>


                <input
                    type="hidden"
                    id="receberParcelaId"
                    name="parcela_id">


                <div class="form-row">

                    <div class="form-group col-md-4">

                        <label for="valor_recebido">
                            Valor Recebido *
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="form-control"
                            id="valor_recebido"
                            name="valor"
                            required>

                    </div>


                    <div class="form-group col-md-4">

                        <label for="data_recebimento">
                            Data do Recebimento *
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="data_recebimento"
                            name="data_recebimento"
                            required>

                    </div>


                    <div class="form-group col-md-4">

                        <label for="receber_forma_pagamento_id">
                            Forma de Pagamento *
                        </label>

                        <select
                            class="form-control"
                            id="receber_forma_pagamento_id"
                            name="forma_pagamento_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group col-md-6">

                        <label for="destino_recebimento">
                            Destino do Dinheiro *
                        </label>

                        <select
                            class="form-control"
                            id="destino_recebimento"
                            name="destino_recebimento"
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


                    <div
                        class="form-group col-md-6"
                        id="grupoReceberCaixa"
                        style="display: none;">

                        <label for="receber_caixa_id">
                            Caixa *
                        </label>

                        <select
                            class="form-control"
                            id="receber_caixa_id"
                            name="caixa_id">

                            <option value="">
                                Seleccione o caixa
                            </option>

                        </select>

                    </div>


                    <div
                        class="form-group col-md-6"
                        id="grupoReceberBanco"
                        style="display: none;">

                        <label for="receber_banco_id">
                            Banco *
                        </label>

                        <select
                            class="form-control"
                            id="receber_banco_id"
                            name="banco_id">

                            <option value="">
                                Seleccione o banco
                            </option>

                        </select>

                    </div>

                    <div class="form-group" id="grupoComprovativoBanco">
                        <label for="comprovativo_banco">
                            <i class="fa fa-paperclip"></i> Comprovativo (PDF / Imagem)
                        </label>
                        <input type="file"
                            class="form-control-file"
                            id="comprovativo_banco"
                            name="comprovativo"
                            accept="application/pdf, image/jpeg, image/png, image/webp">
                        <small class="form-text text-muted">Formatos permitidos: PDF, JPG, PNG, WEBP (Máx. 5MB).</small>
                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group col-md-6">

                        <label for="receber_referencia">
                            Referência
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="receber_referencia"
                            name="referencia"
                            maxlength="120">

                    </div>


                    <div class="form-group col-md-6">

                        <label for="receber_documento">
                            Número do Documento
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="receber_documento"
                            name="numero_documento"
                            maxlength="100">

                    </div>

                </div>


                <div class="form-group">

                    <label for="receber_observacoes">
                        Observações
                    </label>

                    <textarea
                        class="form-control"
                        id="receber_observacoes"
                        name="observacoes"
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
                    type="submit"
                    class="btn btn-success"
                    id="btnConfirmarRecebimento">

                    <i class="fa fa-money"></i>
                    Confirmar Recebimento

                </button>

            </div>

        </form>

    </div>

</div>