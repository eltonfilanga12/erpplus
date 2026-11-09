<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">Unidades</h3>

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
                            Unidades
                        </li>
                    </ul>
                </div>

                <!-- No cabeçalho da página (perto do botão Nova Unidade) -->
                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="btnImportarUnidade">
                        <i class="fa fa-file-excel-o"></i>
                        <span class="d-none d-sm-inline">
                            Importar Excel
                        </span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovaUnidade">
                        <i class="fa fa-plus"></i>
                        <span class="d-none d-sm-inline">
                            Nova Unidade
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <div class="row">
            <div class="col-md-12">

                <div class="table-responsive">

                    <table
                        id="unidadesTable"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Casas Decimais</th>
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


<!-- Modal Unidade -->
<div
    id="modalUnidade"
    class="modal fade"
    tabindex="-1">
    <div class="modal-dialog modal-lg">

        <form
            id="formUnidade"
            class="modal-content">

            <div class="modal-header">

                <h4
                    class="modal-title"
                    id="tituloModalUnidade">
                    Nova Unidade
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
                    id="unidadeId">

                <div class="form-row">

                    <div class="form-group col-md-4">
                        <label for="unidadeCodigo">
                            Código *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="codigo"
                            id="unidadeCodigo"
                            maxlength="20"
                            required>
                    </div>


                    <div class="form-group col-md-8">
                        <label for="unidadeNome">
                            Nome *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="nome"
                            id="unidadeNome"
                            maxlength="100"
                            required>
                    </div>


                    <div class="form-group col-md-6">
                        <label for="unidadeCasasDecimais">
                            Casas Decimais *
                        </label>

                        <select
                            class="form-control"
                            name="casas_decimais"
                            id="unidadeCasasDecimais"
                            required>
                            <option value="0">0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                        </select>
                    </div>


                    <div class="form-group col-md-6">
                        <label for="unidadeStatusId">
                            Status *
                        </label>

                        <select
                            class="form-control"
                            name="status_id"
                            id="unidadeStatusId"
                            required>
                            <option value="">
                                A carregar estados...
                            </option>
                        </select>
                    </div>


                    <div class="form-group col-md-12">
                        <label for="unidadeDescricao">
                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            name="descricao"
                            id="unidadeDescricao"
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
                    id="btnGuardarUnidade">
                    <i class="fa fa-save"></i>
                    Guardar
                </button>

            </div>

        </form>

    </div>
</div>


<!-- Modal Eliminar -->
<div
    id="modalEliminarUnidade"
    class="modal fade"
    tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">

        <form
            id="formEliminarUnidade"
            class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Eliminar Unidade
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
                    id="eliminarUnidadeId">

                <p class="mb-0">
                    Pretende eliminar esta unidade?
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
<!-- Modal Importação Excel de Unidades -->
<div
    id="modalImportarUnidade"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-file-excel-o"></i>
                    Importar Unidades por Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="unidadeImportacaoErro" class="alert alert-danger d-none"></div>
                <div id="unidadeImportacaoSucesso" class="alert alert-success d-none"></div>

                <!-- PASSO 1 -->
                <div id="unidadeImportacaoPasso1">
                    <div class="alert alert-info">
                        <strong>Como funciona?</strong>
                        <ol class="mb-0 mt-2">
                            <li>Baixe o modelo Excel.</li>
                            <li>Preencha o código, nome, casas decimais (0 a 6) e descrição.</li>
                            <li>Selecione o ficheiro.</li>
                            <li>Clique em <strong>Validar Excel</strong>.</li>
                            <li>Confira os erros e registos válidos.</li>
                            <li>Confirme a importação.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-success" id="btnModeloExcelUnidade">
                            <i class="fa fa-download"></i> Baixar modelo Excel
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="unidadeFicheiroExcel">
                            Ficheiro Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control-file" id="unidadeFicheiroExcel" accept=".xlsx,.xls">
                        <small class="form-text text-muted">Formatos permitidos: XLSX e XLS. Tamanho máximo: 10 MB.</small>
                    </div>

                    <button type="button" class="btn btn-primary" id="btnValidarExcelUnidade">
                        <i class="fa fa-check"></i> Validar Excel
                    </button>
                </div>

                <!-- PASSO 2 -->
                <div id="unidadeImportacaoPasso2" class="d-none">
                    <div id="unidadeImportacaoErrosContainer" class="d-none mb-3">
                        <h5 class="text-danger"><i class="fa fa-warning"></i> Linhas com erro</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Código / Nome</th>
                                        <th>Erro</th>
                                    </tr>
                                </thead>
                                <tbody id="unidadeImportacaoErros"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="unidadeImportacaoValidosContainer" class="mt-4">
                        <h5 class="text-success"><i class="fa fa-check-circle"></i> Unidades prontas para importar</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Código</th>
                                        <th>Nome</th>
                                        <th>Casas Decimais</th>
                                        <th>Descrição</th>
                                    </tr>
                                </thead>
                                <tbody id="unidadeImportacaoValidos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary d-none" id="btnVoltarImportacaoUnidade">
                    <i class="fa fa-arrow-left"></i> Voltar
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success d-none" id="btnConfirmarImportacaoUnidade">
                    <i class="fa fa-upload"></i> Importar Unidades Válidas
                </button>
            </div>
        </div>
    </div>
</div>