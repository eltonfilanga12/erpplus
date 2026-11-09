<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">


        <!-- HEADER -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Caixas
                    </h3>


                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>


                        <li class="breadcrumb-item active">
                            Caixas
                        </li>

                    </ul>

                </div>



                <div class="col-auto">

                    <button
                        class="btn btn-primary"
                        data-toggle="modal"
                        data-target="#modalCaixa">


                        <i class="fa fa-plus"></i>
                        Novo Caixa


                    </button>

                    <button
                        class="btn btn-secondary mr-2"
                        onclick="abrirTransferencia()">

                        <i class="fa fa-exchange-alt"></i>
                        Transferir

                    </button>


                </div>


            </div>

        </div>





        <!-- TABELA -->


        <div class="table-responsive">


            <table
                id="caixasTable"
                class="table table-striped custom-table">


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Nome</th>

                        <th>Código</th>

                        <th>Filial</th>

                        <th>Saldo Inicial</th>

                        <th>Saldo Actual</th>

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






<!-- ==========================
MODAL CADASTRO CAIXA
========================== -->


<div
    class="modal fade"
    id="modalCaixa">


    <div class="modal-dialog modal-lg">


        <form
            id="formCaixa"
            class="modal-content">


            <div class="modal-header">


                <h4 id="tituloModalCaixa">
                    Novo Caixa
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
                    id="caixa_id"
                    name="id">



                <!-- FILIAL -->

                <div class="form-group">

                    <label>
                        Filial
                    </label>


                    <select
                        class="form-control"
                        id="caixaFilialId"
                        name="filial_id">


                        <option value="">
                            Todas / Sem filial
                        </option>


                    </select>


                </div>







                <div class="form-row">



                    <div class="form-group col-md-6">


                        <label>

                            Nome do Caixa *

                        </label>


                        <input
                            class="form-control"
                            name="nome"
                            id="nome"
                            required>


                    </div>






                    <div class="form-group col-md-6">


                        <label>

                            Código

                        </label>


                        <input
                            class="form-control"
                            name="codigo"
                            id="codigo">


                    </div>



                </div>

                <div class="form-row">



                    <div class="form-group col-md-6">


                        <label>

                            Localização

                        </label>


                        <input
                            class="form-control"
                            name="localizacao"
                            id="localizacao">


                    </div>






                    <div class="form-group col-md-6">


                        <label>

                            Descrição

                        </label>


                        <input
                            class="form-control"
                            name="descricao"
                            id="descricao">

                    </div>



                </div>

















                <div class="form-row">


                    <div class="form-group col-md-6">


                        <label>

                            Saldo Inicial

                        </label>


                        <input
                            type="number"
                            step="0.01"
                            class="form-control"
                            name="saldo_inicial"
                            id="saldo_inicial"
                            value="0">


                    </div>

                    <div
                        class="form-group col-md-6"
                        id="campoStatusCaixa">


                        <label>
                            Status
                        </label>


                        <select
                            class="form-control"
                            id="status_caixa"
                            name="status_id">


                            <option value="">
                                Seleccione
                            </option>


                        </select>


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
                    class="btn btn-primary">

                    <i class="fa fa-save"></i>

                    Guardar

                </button>


            </div>



        </form>


    </div>


</div>









<!-- ==========================
ABERTURA CAIXA
========================== -->


<div
    class="modal fade"
    id="modalAbertura">


    <div class="modal-dialog">


        <form
            id="formAbertura"
            class="modal-content">



            <div class="modal-header">

                <h4>

                    Abrir Caixa

                </h4>


            </div>




            <div class="modal-body">



                <input
                    type="hidden"
                    id="abertura_caixa_id"
                    name="caixa_id">



                <div class="form-group">


                    <label>

                        Valor Inicial

                    </label>


                    <input
                        type="number"
                        step="0.01"
                        class="form-control"
                        id="valor_inicial"
                        name="valor_inicial"
                        required>


                </div>






                <div class="form-group">


                    <label>

                        Observações

                    </label>


                    <textarea
                        class="form-control"
                        id="observacoes_abertura"
                        name="observacoes">

</textarea>


                </div>




            </div>





            <div class="modal-footer">


                <button
                    class="btn btn-success"
                    type="submit">

                    Abrir Caixa

                </button>


            </div>




        </form>


    </div>


</div>









<!-- ==========================
MOVIMENTO CAIXA
========================== -->


<div
    class="modal fade"
    id="modalMovimento">


    <div class="modal-dialog">


        <form
            id="formMovimento"
            class="modal-content">


            <div class="modal-header">

                <h4>
                    Movimento Caixa
                </h4>

            </div>




            <div class="modal-body">


                <input
                    type="hidden"
                    id="mov_caixa_id"
                    name="caixa_id">





                <div class="form-group">

                    <label>

                        Tipo

                    </label>


                    <select
                        class="form-control"
                        id="tipo"
                        name="tipo">


                        <option value="ENTRADA">
                            Entrada
                        </option>


                        <option value="SAIDA">
                            Saída
                        </option>


                        <option value="SANGRIA">
                            Sangria
                        </option>


                        <option value="SUPRIMENTO">
                            Suprimento
                        </option>


                    </select>


                </div>







                <div class="form-group">


                    <label>

                        Descrição

                    </label>


                    <input
                        class="form-control"
                        id="descricao_movimento"
                        name="descricao">


                </div>







                <div class="form-group">


                    <label>

                        Valor

                    </label>


                    <input
                        type="number"
                        step="0.01"
                        class="form-control"
                        id="valor_movimento"
                        name="valor">


                </div>





            </div>






            <div class="modal-footer">


                <button
                    class="btn btn-primary"
                    type="submit">


                    Guardar Movimento


                </button>


            </div>


        </form>


    </div>


</div>









<!-- ==========================
FECHAMENTO CAIXA
========================== -->


<div
    class="modal fade"
    id="modalFechamento">


    <div class="modal-dialog">


        <form
            id="formFechamento"
            class="modal-content">



            <div class="modal-header">

                <h4>
                    Fechar Caixa
                </h4>


            </div>




            <div class="modal-body">

                <input
                    type="hidden"
                    id="fechar_caixa_id"
                    name="caixa_id">


                <div class="form-group">


                    <label>

                        Saldo Informado

                    </label>


                    <input
                        type="number"
                        step="0.01"
                        class="form-control"
                        id="saldo_informado"
                        name="saldo_informado">


                </div>







                <div class="form-group">


                    <label>

                        Observações

                    </label>


                    <textarea
                        class="form-control"
                        id="observacao_fechamento"
                        name="observacoes"
                        rows="3"></textarea>


                </div>



            </div>





            <div class="modal-footer">


                <button
                    class="btn btn-danger"
                    type="submit">


                    Fechar Caixa


                </button>


            </div>


        </form>


    </div>


</div>

<!-- ==========================
TRANSFERÊNCIA ENTRE CAIXAS
========================== -->
<div class="modal fade" id="modalTransferencia">
    <div class="modal-dialog">

        <form id="formTransferencia" class="modal-content">

            <div class="modal-header">
                <h4>Transferir entre Caixas</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">

                <div class="form-group">
                    <label>Caixa Origem</label>
                    <select class="form-control" name="caixa_origem_id" id="caixa_origem_id" required></select>
                </div>

                <div class="form-group">
                    <label>Caixa Destino</label>
                    <select class="form-control" name="caixa_destino_id" id="caixa_destino_id" required></select>
                </div>

                <div class="form-group">
                    <label>Valor</label>
                    <input type="number" step="0.01"
                        class="form-control"
                        name="valor" required>
                </div>

                <div class="form-group">
                    <label>Descrição</label>
                    <input class="form-control"
                        name="descricao"
                        placeholder="Ex: Reforço do caixa da loja">
                </div>

            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-exchange-alt"></i>
                    Transferir
                </button>
            </div>

        </form>

    </div>
</div>

<div class="modal fade" id="modalExtrato">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h4>Extrato do Caixa</h4>

                <button class="close" data-dismiss="modal">
                    &times;
                </button>

            </div>

            <div class="modal-body">

                <input type="hidden" id="extrato_caixa_id">

                <div class="row">

                    <div class="col-md-3">

                        <label>Data Inicial</label>

                        <input
                            type="date"
                            id="data_inicio"
                            class="form-control">

                    </div>

                    <div class="col-md-3">

                        <label>Data Final</label>

                        <input
                            type="date"
                            id="data_fim"
                            class="form-control">

                    </div>

                    <div class="col-md-3">

                        <label>Tipo</label>

                        <select
                            id="filtro_tipo"
                            class="form-control">

                            <option value="">Todos</option>

                            <option>ENTRADA</option>

                            <option>SAIDA</option>

                            <option>SANGRIA</option>

                            <option>SUPRIMENTO</option>

                        </select>

                    </div>

                    <div class="col-md-3">

                        <label>&nbsp;</label>

                        <button
                            class="btn btn-primary btn-block"
                            onclick="carregarExtrato()">

                            Pesquisar

                        </button>

                    </div>

                </div>

                <hr>

                <table
                    id="extratoTable"
                    class="table table-bordered table-striped">

                    <thead>

                        <tr>

                            <th>Data</th>

                            <th>Tipo</th>

                            <th>Origem</th>

                            <th>Descrição</th>

                            <th>Operador</th>

                            <th>Valor</th>

                        </tr>

                    </thead>

                    <tbody></tbody>

                </table>

            </div>

        </div>

    </div>

</div>