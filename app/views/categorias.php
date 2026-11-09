<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Categorias
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Categorias
                        </li>
                    </ul>
                </div>

                <!-- No cabeçalho da página (perto do botão Nova Categoria) -->
                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="categoriaBtnImportar">
                        <i class="fa fa-file-excel-o"></i>
                        <span class="d-none d-sm-inline">
                            Importar Excel
                        </span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="categoriaBtnNova">
                        <i class="fa fa-plus"></i>
                        <span class="d-none d-sm-inline">
                            Nova Categoria
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Tabela -->
        <div class="card">
            <div class="card-body">

                <div
                    id="categoriaErroLista"
                    class="alert alert-danger d-none"></div>

                <div class="table-responsive">
                    <table
                        id="categoriaTabela"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Categoria</th>
                                <th>Categoria Pai</th>
                                <th>Descrição</th>
                                <th>Estado</th>
                                <th>Criado em</th>
                                <th class="text-right">
                                    Acções
                                </th>
                            </tr>
                        </thead>

                        <tbody id="categoriaTabelaBody">

                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal Categoria -->
<div
    id="categoriaModal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="categoriaModalTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-lg"
        role="document">
        <form
            id="categoriaForm"
            class="modal-content"
            autocomplete="off">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="categoriaModalTitulo">
                    Nova Categoria
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
                    id="categoriaErroFormulario"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    id="categoriaId"
                    name="id">

                <input
                    type="hidden"
                    name="acao"
                    value="salvar">

                <div class="form-row">

                    <div class="form-group col-md-6">
                        <label for="categoriaNome">
                            Nome
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="categoriaNome"
                            name="nome"
                            maxlength="120"
                            required>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="categoriaStatusId">
                            Estado
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="categoriaStatusId"
                            name="status_id"
                            required>
                            <option value="">
                                A carregar estados...
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-12">
                        <label for="categoriaPaiId">
                            Categoria Pai
                        </label>

                        <select
                            class="form-control"
                            id="categoriaPaiId"
                            name="categoria_pai_id">
                            <option value="">
                                A carregar categorias...
                            </option>
                        </select>

                        <small class="form-text text-muted">
                            Deixe vazio para criar uma categoria principal.
                        </small>
                    </div>

                    <div class="form-group col-md-12">
                        <label for="categoriaDescricao">
                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            id="categoriaDescricao"
                            name="descricao"
                            rows="4"></textarea>
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
                    id="categoriaBtnGuardar">
                    <i class="fa fa-save"></i>
                    Guardar
                </button>

            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar -->
<div
    id="categoriaModalEliminar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="categoriaModalEliminarTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">
        <form
            id="categoriaFormEliminar"
            class="modal-content">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="categoriaModalEliminarTitulo">
                    Eliminar Categoria
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
                    id="categoriaErroEliminar"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    id="categoriaEliminarId"
                    name="id">

                <input
                    type="hidden"
                    name="acao"
                    value="eliminar">

                <p>
                    Pretende realmente eliminar a categoria
                    <strong id="categoriaEliminarNome"></strong>?
                </p>

                <p class="text-danger mb-0">
                    A eliminação será impedida caso existam subcategorias,
                    produtos ou outros registos associados.
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
                    id="categoriaBtnEliminar">
                    <i class="fa fa-trash"></i>
                    Eliminar
                </button>

            </div>
        </form>
    </div>
</div>
<!-- Modal Importação Excel de Categorias -->
<div
    id="categoriaModalImportar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="categoriaModalImportarTitulo"
    aria-hidden="true">

    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoriaModalImportarTitulo">
                    <i class="fa fa-file-excel-o"></i>
                    Importar Categorias por Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="categoriaImportacaoErro" class="alert alert-danger d-none"></div>
                <div id="categoriaImportacaoSucesso" class="alert alert-success d-none"></div>

                <!-- PASSO 1 -->
                <div id="categoriaImportacaoPasso1">
                    <div class="alert alert-info">
                        <strong>Como funciona?</strong>
                        <ol class="mb-0 mt-2">
                            <li>Baixe o modelo Excel.</li>
                            <li>Preencha as categorias e categorias pai (opcional).</li>
                            <li>Selecione o ficheiro.</li>
                            <li>Clique em <strong>Validar Excel</strong>.</li>
                            <li>Confira os erros e registos válidos.</li>
                            <li>Confirme a importação.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-success" id="categoriaBtnModeloExcel">
                            <i class="fa fa-download"></i> Baixar modelo Excel
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="categoriaFicheiroExcel">
                            Ficheiro Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control-file" id="categoriaFicheiroExcel" accept=".xlsx,.xls">
                        <small class="form-text text-muted">Formatos permitidos: XLSX e XLS. Tamanho máximo: 10 MB.</small>
                    </div>

                    <button type="button" class="btn btn-primary" id="categoriaBtnValidarExcel">
                        <i class="fa fa-check"></i> Validar Excel
                    </button>
                </div>

                <!-- PASSO 2 -->
                <div id="categoriaImportacaoPasso2" class="d-none">
                    <div id="categoriaImportacaoResumo" class="mb-3"></div>

                    <div id="categoriaImportacaoErrosContainer" class="d-none">
                        <h5 class="text-danger"><i class="fa fa-warning"></i> Linhas com erro</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Nome</th>
                                        <th>Erro</th>
                                    </tr>
                                </thead>
                                <tbody id="categoriaImportacaoErros"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="categoriaImportacaoValidosContainer" class="mt-4">
                        <h5 class="text-success"><i class="fa fa-check-circle"></i> Categorias prontas para importar</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Nome</th>
                                        <th>Categoria Pai</th>
                                        <th>Descrição</th>
                                    </tr>
                                </thead>
                                <tbody id="categoriaImportacaoValidos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary d-none" id="categoriaBtnVoltarImportacao">
                    <i class="fa fa-arrow-left"></i> Voltar
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success d-none" id="categoriaBtnConfirmarImportacao">
                    <i class="fa fa-upload"></i> Importar Categorias Válidas
                </button>
            </div>
        </div>
    </div>
</div>