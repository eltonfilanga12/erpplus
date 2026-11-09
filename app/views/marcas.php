<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">Marcas</h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item">
                            Cadastros
                        </li>

                        <li class="breadcrumb-item active">
                            Marcas
                        </li>
                    </ul>
                </div>

                <!-- No cabeçalho da página (perto do botão Nova Marca) -->
                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="btnImportarMarca">
                        <i class="fa fa-file-excel-o"></i>
                        <span class="d-none d-sm-inline">
                            Importar Excel
                        </span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovaMarca">
                        <i class="fa fa-plus"></i>
                        <span class="d-none d-sm-inline">
                            Nova Marca
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <div class="row">
            <div class="col-md-12">

                <div class="table-responsive">
                    <table
                        id="marcasTable"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Status</th>
                                <th>Criado em</th>
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


<!-- Modal Marca -->
<div
    id="modalMarca"
    class="modal fade"
    tabindex="-1">
    <div class="modal-dialog modal-lg">

        <form
            id="formMarca"
            class="modal-content">
            <div class="modal-header">

                <h4
                    class="modal-title"
                    id="tituloModalMarca">
                    Nova Marca
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
                    id="marcaId">

                <div class="form-row">

                    <div class="form-group col-md-6">
                        <label for="marcaNome">
                            Nome *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="nome"
                            id="marcaNome"
                            maxlength="120"
                            required>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="marcaStatusId">
                            Status *
                        </label>

                        <select
                            class="form-control"
                            name="status_id"
                            id="marcaStatusId"
                            required>
                            <option value="">
                                A carregar estados...
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-12">
                        <label for="marcaDescricao">
                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            name="descricao"
                            id="marcaDescricao"
                            rows="4"
                            maxlength="500"></textarea>
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
                    id="btnGuardarMarca">
                    <i class="fa fa-save"></i>
                    Guardar
                </button>

            </div>
        </form>

    </div>
</div>


<!-- Modal Eliminar -->
<div
    id="modalEliminarMarca"
    class="modal fade"
    tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">

        <form
            id="formEliminarMarca"
            class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Eliminar Marca
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
                    name="id"
                    id="eliminarMarcaId">

                <p class="mb-0">
                    Pretende eliminar esta marca?
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


<!-- Modal Mensagem -->
<div
    id="modalMensagemMarca"
    class="modal fade"
    tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="tituloMensagemMarca">
                    Informação
                </h5>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">
                    &times;
                </button>
            </div>

            <div class="modal-body">
                <p
                    id="textoMensagemMarca"
                    class="mb-0"></p>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-primary"
                    data-dismiss="modal">
                    OK
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modal Importação Excel de Marcas -->
<div
    id="modalImportarMarca"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-file-excel-o"></i>
                    Importar Marcas por Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="marcaImportacaoErro" class="alert alert-danger d-none"></div>
                <div id="marcaImportacaoSucesso" class="alert alert-success d-none"></div>

                <!-- PASSO 1 -->
                <div id="marcaImportacaoPasso1">
                    <div class="alert alert-info">
                        <strong>Como funciona?</strong>
                        <ol class="mb-0 mt-2">
                            <li>Baixe o modelo Excel.</li>
                            <li>Preencha o nome e a descrição das marcas.</li>
                            <li>Selecione o ficheiro.</li>
                            <li>Clique em <strong>Validar Excel</strong>.</li>
                            <li>Confira os erros e registos válidos.</li>
                            <li>Confirme a importação.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-success" id="btnModeloExcelMarca">
                            <i class="fa fa-download"></i> Baixar modelo Excel
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="marcaFicheiroExcel">
                            Ficheiro Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control-file" id="marcaFicheiroExcel" accept=".xlsx,.xls">
                        <small class="form-text text-muted">Formatos permitidos: XLSX e XLS. Tamanho máximo: 10 MB.</small>
                    </div>

                    <button type="button" class="btn btn-primary" id="btnValidarExcelMarca">
                        <i class="fa fa-check"></i> Validar Excel
                    </button>
                </div>

                <!-- PASSO 2 -->
                <div id="marcaImportacaoPasso2" class="d-none">
                    <div id="marcaImportacaoErrosContainer" class="d-none mb-3">
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
                                <tbody id="marcaImportacaoErros"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="marcaImportacaoValidosContainer" class="mt-4">
                        <h5 class="text-success"><i class="fa fa-check-circle"></i> Marcas prontas para importar</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Nome</th>
                                        <th>Descrição</th>
                                    </tr>
                                </thead>
                                <tbody id="marcaImportacaoValidos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary d-none" id="btnVoltarImportacaoMarca">
                    <i class="fa fa-arrow-left"></i> Voltar
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success d-none" id="btnConfirmarImportacaoMarca">
                    <i class="fa fa-upload"></i> Importar Marcas Válidas
                </button>
            </div>
        </div>
    </div>
</div>