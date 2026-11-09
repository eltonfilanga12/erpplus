<!-- Page Wrapper -->

<div class="page-wrapper">
    <div class="content container-fluid">

     
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        <i class="fa fa-file-text-o"></i>
                        Cotações
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item">
                            Vendas
                        </li>

                        <li class="breadcrumb-item active">
                            Cotações
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovaCotacao">

                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Nova Cotação
                        </span>
                    </button>
                </div>

            </div>
        </div>


        <!-- Card -->
        <div class="card">
            <div class="card-body">

                <!-- Mensagem de erro -->
                <div
                    id="cotacaoErroLista"
                    class="alert alert-danger d-none">
                </div>


                <!-- Tabela -->
                <div class="table-responsive">

                    <table
                        id="cotacoesTable"
                        class="table table-striped custom-table mb-0"
                        width="100%">

                        <thead>
                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Número
                                </th>

                                <th>
                                    Emissão
                                </th>

                                <th>
                                    Validade
                                </th>

                                <th>
                                    Cliente
                                </th>

                                <th>
                                    NUIT
                                </th>

                                <th class="text-right">
                                    Total
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th class="text-right">
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

<!-- ==========================================================
     MODAL CRIAR / EDITAR COTAÇÃO
=========================================================== -->

<div
    id="modalCotacao"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="tituloModalCotacao"
    aria-hidden="true">

    
    <div
        class="modal-dialog modal-xl"
        role="document">

        <form
            id="formCotacao"
            class="modal-content"
            autocomplete="off">


            <!-- Modal Header -->
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="tituloModalCotacao">

                    Nova Cotação

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


            <!-- Modal Body -->
            <div class="modal-body">

                <input
                    type="hidden"
                    id="cotacaoId"
                    name="id"
                    value="">


                <!-- ==================================================
                 DADOS DA COTAÇÃO
            =================================================== -->

                <h5 class="mb-3">

                    <i class="fa fa-file-text-o"></i>

                    Dados da Cotação

                </h5>


                <div class="form-row">

                    <div class="form-group col-md-3">

                        <label for="cotacaoNumero">
                            Número
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="cotacaoNumero"
                            name="numero"
                            maxlength="50"
                            required>

                    </div>


                    <div class="form-group col-md-3">

                        <label for="cotacaoDataEmissao">
                            Data de Emissão
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="cotacaoDataEmissao"
                            name="data_emissao"
                            required>

                    </div>


                    <div class="form-group col-md-3">

                        <label for="cotacaoValidade">
                            Validade
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="cotacaoValidade"
                            name="validade">

                    </div>


                    <div class="form-group col-md-3">

                        <label for="cotacaoStatusId">
                            Estado
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="cotacaoStatusId"
                            name="status_id"
                            required>

                            <option value="">
                                A carregar estados...
                            </option>

                        </select>

                    </div>

                </div>


                <hr>


                <!-- ==================================================
                 CLIENTE
            =================================================== -->

                <h5 class="mb-3">

                    <i class="fa fa-user"></i>

                    Cliente

                </h5>


                <input
                    type="hidden"
                    id="cotacaoClienteId"
                    name="cliente_id"
                    value="">


                <div class="form-row">

                    <!-- Nome -->
                    <div class="form-group col-md-6">

                        <label for="cotacaoClienteNome">

                            Nome do Cliente

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <div class="position-relative">

                            <input
                                type="text"
                                class="form-control"
                                id="cotacaoClienteNome"
                                name="cliente_nome"
                                maxlength="150"
                                placeholder="Digite o nome do cliente..."
                                required>


                            <div
                                id="listaSugestoesClientes"
                                class="list-group position-absolute w-100"
                                style="
                                z-index:1060;
                                display:none;
                                max-height:220px;
                                overflow-y:auto;
                            ">
                            </div>

                        </div>


                        <small class="form-text text-muted">

                            Selecione um cliente existente
                            ou digite um cliente externo.

                        </small>

                    </div>


                    <!-- NUIT -->
                    <div class="form-group col-md-3">

                        <label for="cotacaoClienteNuit">
                            NUIT
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="cotacaoClienteNuit"
                            name="cliente_nuit"
                            maxlength="30">

                    </div>


                    <!-- Telefone -->
                    <div class="form-group col-md-3">

                        <label for="cotacaoClienteTelefone">
                            Telefone
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="cotacaoClienteTelefone"
                            name="cliente_telefone"
                            maxlength="30">

                    </div>

                </div>


                <!-- Endereço -->
                <div class="form-row">

                    <div class="form-group col-md-12">

                        <label for="cotacaoClienteEndereco">
                            Endereço
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="cotacaoClienteEndereco"
                            name="cliente_endereco"
                            maxlength="255">

                    </div>

                </div>


                <hr>


                <!-- ==================================================
                 PRODUTOS
            =================================================== -->

                <h5 class="mb-3">

                    <i class="fa fa-cubes"></i>

                    Produtos / Serviços

                </h5>


                <div class="form-row">

                    <div class="form-group col-md-10">

                        <label for="cotacaoProdutoPesquisa">
                            Adicionar Produto
                        </label>


                        <div class="position-relative">

                            <input
                                type="text"
                                class="form-control"
                                id="cotacaoProdutoPesquisa"
                                placeholder="Pesquisar por nome, SKU ou código de barras...">


                            <div
                                id="listaSugestoesProdutos"
                                class="list-group position-absolute w-100"
                                style="
                                z-index:1055;
                                display:none;
                                max-height:250px;
                                overflow-y:auto;
                            ">
                            </div>

                        </div>

                    </div>


                    <div class="form-group col-md-2 d-flex align-items-end">

                        <button
                            type="button"
                            class="btn btn-success btn-block"
                            id="btnAdicionarItem">

                            <i class="fa fa-plus"></i>

                            <span class="d-none d-sm-inline">
                                Adicionar
                            </span>

                        </button>

                    </div>

                </div>


                <!-- ==================================================
                 TABELA DE ITENS
            =================================================== -->

                <div class="table-responsive">

                    <table
                        class="table table-bordered table-sm"
                        id="cotacaoItensTable">

                        <thead class="thead-light">

                            <tr>

                                <th
                                    class="text-center"
                                    style="width:45px;">
                                    #
                                </th>

                                <th>
                                    Produto
                                </th>

                                <th
                                    class="text-right"
                                    style="width:110px;">
                                    Quantidade
                                </th>

                                <th
                                    class="text-right"
                                    style="width:150px;">
                                    Preço Unit.
                                </th>

                                <th
                                    class="text-right"
                                    style="width:110px;">
                                    Desc. %
                                </th>

                                <th
                                    class="text-right"
                                    style="width:110px;">
                                    Imposto %
                                </th>

                                <th
                                    class="text-right"
                                    style="width:150px;">
                                    Total
                                </th>

                                <th style="width:55px;">Ação
                                </th>

                            </tr>

                        </thead>


                        <tbody id="cotacaoItensBody">
                        </tbody>

                    </table>

                </div>


                <!-- Sem itens -->
                <div
                    id="cotacaoItensVazio"
                    class="alert alert-light text-center">

                    <i class="fa fa-info-circle"></i>

                    Adicione pelo menos um produto à cotação.

                </div>


                <hr>


                <!-- ==================================================
                 TOTAIS
            =================================================== -->

                <div class="row">

                    <!-- Observações -->
                    <div class="col-md-7">

                        <div class="form-group">

                            <label for="cotacaoObservacoes">
                                Observações
                            </label>

                            <textarea
                                class="form-control"
                                id="cotacaoObservacoes"
                                name="observacoes"
                                rows="5"
                                placeholder="Observações da cotação..."></textarea>

                        </div>

                    </div>


                    <!-- Totais -->
                    <div class="col-md-5">

                        <div class="table-responsive">

                            <table
                                class="table table-sm table-bordered">

                                <tbody>

                                    <tr>

                                        <th>
                                            Subtotal
                                        </th>

                                        <td
                                            id="cotacaoSubtotal"
                                            class="text-right">
                                            0,00 MT
                                        </td>

                                    </tr>


                                    <tr>

                                        <th>

                                            Desconto

                                            <span
                                                id="cotacaoDescontoPercentual"
                                                class="text-muted">
                                                0,00%
                                            </span>

                                        </th>

                                        <td
                                            id="cotacaoDescontoValor"
                                            class="text-right">
                                            0,00 MT
                                        </td>

                                    </tr>


                                    <tr>

                                        <th>

                                            Imposto

                                            <span
                                                id="cotacaoImpostoPercentual"
                                                class="text-muted">
                                                0,00%
                                            </span>

                                        </th>

                                        <td
                                            id="cotacaoImpostoValor"
                                            class="text-right">
                                            0,00 MT
                                        </td>

                                    </tr>


                                    <tr class="bg-light">

                                        <th>
                                            TOTAL
                                        </th>

                                        <td
                                            id="cotacaoTotal"
                                            class="text-right font-weight-bold">
                                            0,00 MT
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Modal Footer -->
            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-dismiss="modal">

                    <i class="fa fa-times"></i>

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn btn-primary"
                    id="btnGuardarCotacao">

                    <i class="fa fa-save"></i>

                    Guardar Cotação

                </button>

            </div>

        </form>

    </div>
    

</div>

<!-- ==========================================================
     MODAL ELIMINAR
=========================================================== -->

<div
    id="modalEliminarCotacao"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="tituloModalEliminarCotacao"
    aria-hidden="true">

 
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">

        <form
            id="formEliminarCotacao"
            class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="tituloModalEliminarCotacao">

                    <i class="fa fa-trash"></i>

                    Eliminar Cotação

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


            <div class="modal-body">

                <input
                    type="hidden"
                    id="eliminarCotacaoId"
                    name="id"
                    value="">


                <div
                    id="cotacaoErroEliminar"
                    class="alert alert-danger d-none">
                </div>


                <div class="text-center">

                    <i
                        class="fa fa-exclamation-triangle text-warning"
                        style="font-size:48px;">
                    </i>


                    <p class="mt-3 mb-0">

                        Tem certeza de que deseja eliminar
                        esta cotação?

                    </p>


                    <small class="text-muted">

                        Esta operação não poderá ser desfeita.

                    </small>

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
                    class="btn btn-danger"
                    id="btnConfirmarEliminarCotacao">

                    <i class="fa fa-trash"></i>

                    Eliminar

                </button>

            </div>

        </form>

    </div>

</div>