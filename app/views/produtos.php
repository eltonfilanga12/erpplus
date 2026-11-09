<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Produtos
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Produtos
                        </li>
                    </ul>
                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="produtoBtnImportar">
                        <i class="fa fa-file-excel-o"></i>

                        <span class="d-none d-sm-inline">
                            Importar Excel
                        </span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="produtoBtnNovo">
                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Novo Produto
                        </span>
                    </button>

                </div>

            </div>
        </div>

        <!-- Tabela -->
        <div class="card">
            <div class="card-body">

                <div
                    id="produtoErroLista"
                    class="alert alert-danger d-none"></div>

                <div class="table-responsive">
                    <table
                        id="produtoTabela"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagem</th>
                                <th>SKU</th>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Marca</th>
                                <th>Unidade</th>
                                <th>Armazém</th>
                                <th>Preço de venda</th>
                                <th>Stock mínimo</th>
                                <th>Estado</th>
                                <th>Criado em</th>
                                <th class="text-right">
                                    Acções
                                </th>
                            </tr>
                        </thead>

                        <tbody id="produtoTabelaBody">

                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal Produto -->
<div
    id="produtoModal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="produtoModalTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-xl"
        role="document">
        <form
            id="produtoForm"
            class="modal-content"
            enctype="multipart/form-data"
            autocomplete="off">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="produtoModalTitulo">
                    Novo Produto
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

                <div
                    id="produtoErroFormulario"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    id="produtoId"
                    name="id">

                <input
                    type="hidden"
                    id="produtoImagemActual"
                    name="imagem_actual">

                <input
                    type="hidden"
                    name="acao"
                    value="salvar">

                <h5 class="mb-3">
                    <i class="fa fa-cube"></i>
                    Identificação
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-4">
                        <label for="produtoCategoriaId">
                            Categoria
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="produtoCategoriaId"
                            name="categoria_id"
                            required>
                            <option value="">
                                A carregar categorias...
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">

                        <label for="produtoMarcaId">
                            Marca
                        </label>

                        <select
                            class="form-control"
                            id="produtoMarcaId"
                            name="marca_id">
                            <option value="">
                                A carregar marcas...
                            </option>
                        </select>

                    </div>

                    <div class="form-group col-md-4">
                        <label for="produtoArmazemId">
                            Armazém padrão
                        </label>

                        <select
                            class="form-control"
                            id="produtoArmazemId"
                            name="armazem_padrao_id">
                            <option value="">
                                A carregar armazéns...
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="produtoStatusId">
                            Estado
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="produtoStatusId"
                            name="status_id"
                            required>
                            <option value="">
                                A carregar estados...
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">

                        <label for="produtoSku">
                            SKU
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="produtoSku"
                            name="sku"
                            maxlength="50"
                            readonly
                            required>

                        <small
                            class="form-text text-muted"
                            id="produtoSkuAjuda">

                            Gerado automaticamente a partir
                            do nome do produto.

                        </small>

                    </div>

                    <div class="form-group col-md-8">
                        <label for="produtoNome">
                            Nome do produto
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="produtoNome"
                            name="nome"
                            maxlength="150"
                            required>
                    </div>

                    <div class="form-group col-md-4">

                        <label for="produtoUnidadeId">
                            Unidade
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="produtoUnidadeId"
                            name="unidade_id"
                            required>
                            <option value="">
                                A carregar unidades...
                            </option>
                        </select>

                    </div>

                    <div class="form-group col-md-4">

                        <label for="produtoCodigoBarras">
                            Código de barras
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="produtoCodigoBarras"
                            name="codigo_barras"
                            maxlength="50">

                        <div
                            id="produtoBarcodeContainer"
                            class="border rounded mt-2 p-2 text-center d-none">

                            <svg id="produtoBarcodePreview"></svg>

                        </div>

                        <small class="form-text text-muted">

                            Se o produto não possuir código do fabricante,
                            será utilizado o SKU automaticamente.

                        </small>

                    </div>

                    <div class="form-group col-md-4">
                        <label for="produtoImagem">
                            Imagem principal
                        </label>

                        <input
                            type="file"
                            class="form-control"
                            id="produtoImagem"
                            name="imagem_principal"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                        <small class="form-text text-muted">
                            Formatos permitidos: JPG, PNG e WEBP. Máximo: 5 MB.
                        </small>
                        <div class="border rounded p-2 text-center">
                            <img
                                id="produtoPreview"
                                src="/erpplus/public/assets/img/default-product.png"
                                alt="Pré-visualização do produto"
                                class="img-fluid img-thumbnail"
                                style="width: 160px; height: 160px; object-fit: cover;">

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger mt-2 d-none"
                                id="produtoBtnRemoverImagem">
                                <i class="fa fa-times"></i>
                                Remover imagem
                            </button>

                            <input
                                type="hidden"
                                id="produtoRemoverImagem"
                                name="remover_imagem"
                                value="0">
                        </div>
                    </div>

                </div>

                <hr>

                <h5 class="mb-3">
                    <i class="fa fa-money"></i>
                    Preços e stock
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-4">
                        <label for="produtoPrecoCusto">
                            Preço de custo
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="produtoPrecoCusto"
                            name="preco_custo"
                            min="0"
                            step="0.01"
                            value="0.00">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="produtoPrecoVenda">
                            Preço de venda
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="produtoPrecoVenda"
                            name="preco_venda"
                            min="0"
                            step="0.01"
                            value="0.00">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="produtoEstoqueMinimo">
                            Stock mínimo
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="produtoEstoqueMinimo"
                            name="estoque_minimo"
                            min="0"
                            step="0.001"
                            value="0.000">
                    </div>

                </div>

                <hr>

                <div class="form-row">

                    <div class="form-group col-md-8">
                        <label for="produtoDescricao">
                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            id="produtoDescricao"
                            name="descricao"
                            rows="5"></textarea>
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
                    id="produtoBtnGuardar">
                    <i class="fa fa-save"></i>
                    Guardar
                </button>

            </div>
        </form>
    </div>
</div>

<!-- Modal Importação Excel -->
<div
    id="produtoModalImportar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="produtoModalImportarTitulo"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="produtoModalImportarTitulo">

                    <i class="fa fa-file-excel-o"></i>
                    Importar Produtos por Excel

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

                <div
                    id="produtoImportacaoErro"
                    class="alert alert-danger d-none">
                </div>


                <div
                    id="produtoImportacaoSucesso"
                    class="alert alert-success d-none">
                </div>


                <!-- PASSO 1 -->
                <div
                    id="produtoImportacaoPasso1">

                    <div class="alert alert-info">

                        <strong>
                            Como funciona?
                        </strong>

                        <ol class="mb-0 mt-2">

                            <li>
                                Baixe o modelo Excel.
                            </li>

                            <li>
                                Preencha os produtos.
                            </li>

                            <li>
                                Seleccione o ficheiro.
                            </li>

                            <li>
                                Clique em
                                <strong>
                                    Validar Excel
                                </strong>.
                            </li>

                            <li>
                                Confira os erros e produtos válidos.
                            </li>

                            <li>
                                Confirme a importação.
                            </li>

                        </ol>

                    </div>


                    <div class="mb-3">

                        <button
                            type="button"
                            class="btn btn-outline-success"
                            id="produtoBtnModeloExcel">

                            <i class="fa fa-download"></i>

                            Baixar modelo Excel

                        </button>

                    </div>


                    <div class="form-group">

                        <label for="produtoFicheiroExcel">

                            Ficheiro Excel
                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="file"
                            class="form-control-file"
                            id="produtoFicheiroExcel"
                            accept=".xlsx,.xls">

                        <small
                            class="form-text text-muted">

                            Formatos permitidos:
                            XLSX e XLS.
                            Tamanho máximo: 10 MB.

                        </small>

                    </div>


                    <button
                        type="button"
                        class="btn btn-primary"
                        id="produtoBtnValidarExcel">

                        <i class="fa fa-check"></i>

                        Validar Excel

                    </button>

                </div>


                <!-- PASSO 2 -->
                <div
                    id="produtoImportacaoPasso2"
                    class="d-none">

                    <div
                        id="produtoImportacaoResumo"
                        class="mb-3">
                    </div>


                    <div
                        id="produtoImportacaoErrosContainer"
                        class="d-none">

                        <h5 class="text-danger">

                            <i class="fa fa-warning"></i>

                            Linhas com erro

                        </h5>

                        <div class="table-responsive">

                            <table
                                class="table table-sm table-bordered">

                                <thead>

                                    <tr>
                                        <th>Linha</th>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Erro</th>
                                    </tr>

                                </thead>

                                <tbody
                                    id="produtoImportacaoErros">
                                </tbody>

                            </table>

                        </div>

                    </div>


                    <div
                        id="produtoImportacaoValidosContainer"
                        class="mt-4">

                        <h5 class="text-success">

                            <i class="fa fa-check-circle"></i>

                            Produtos prontos para importar

                        </h5>

                        <div class="table-responsive">

                            <table
                                class="table table-sm table-striped table-bordered">

                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>SKU</th>
                                        <th>Produto</th>
                                        <th>Categoria</th>
                                        <th>Marca</th>
                                        <th>Unidade</th>
                                        <th>Preço Venda</th>
                                    </tr>
                                </thead>

                                <tbody id="produtoImportacaoValidos"></tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary d-none"
                    id="produtoBtnVoltarImportacao">

                    <i class="fa fa-arrow-left"></i>

                    Voltar

                </button>

                <button
                    type="button"
                    class="btn btn-light"
                    data-dismiss="modal">

                    Cancelar

                </button>

                <button
                    type="button"
                    class="btn btn-success d-none"
                    id="produtoBtnConfirmarImportacao">

                    <i class="fa fa-upload"></i>

                    Importar Produtos Válidos

                </button>

            </div>

        </div>

    </div>

</div>

<!-- Modal Eliminar -->
<div
    id="produtoModalEliminar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="produtoModalEliminarTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">
        <form
            id="produtoFormEliminar"
            class="modal-content">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="produtoModalEliminarTitulo">
                    Eliminar Produto
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

                <div
                    id="produtoErroEliminar"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    id="produtoEliminarId"
                    name="id">

                <input
                    type="hidden"
                    name="acao"
                    value="eliminar">

                <p>
                    Pretende realmente eliminar o produto
                    <strong id="produtoEliminarNome"></strong>?
                </p>

                <p class="text-danger mb-0">
                    A eliminação será impedida caso o produto possua stock,
                    vendas, compras ou outros movimentos associados.
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
                    class="btn btn-danger"
                    id="produtoBtnEliminar">
                    <i class="fa fa-trash"></i>
                    Eliminar
                </button>

            </div>
        </form>
    </div>
</div>