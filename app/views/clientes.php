<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Clientes
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Clientes
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="clienteBtnImportar">
                        <i class="fa fa-file-excel-o"></i>
                        <span class="d-none d-sm-inline">
                            Importar Excel
                        </span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="clienteBtnNovo">
                        <i class="fa fa-plus"></i>
                        <span class="d-none d-sm-inline">
                            Novo Cliente
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Card -->
        <div class="card">
            <div class="card-body">

                <div
                    id="clienteErroLista"
                    class="alert alert-danger d-none"></div>

                <div class="table-responsive">
                    <table
                        id="clienteTabela"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Tipo</th>
                                <th>NUIT</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th>Limite</th>
                                <th>Saldo</th>
                                <th class="text-right">
                                    Ações
                                </th>
                            </tr>
                        </thead>

                        <tbody id="clienteTabelaBody">

                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal Criar/Editar -->
<div
    id="clienteModal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="clienteModalTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-xl"
        role="document">
        <form
            id="clienteForm"
            class="modal-content"
            autocomplete="off">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="clienteModalTitulo">
                    Novo Cliente
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
                    id="clienteErroFormulario"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    id="clienteId"
                    name="id">

                <input
                    type="hidden"
                    name="acao"
                    value="salvar">

                <!-- Identificação -->
                <h5 class="mb-3">
                    <i class="fa fa-user"></i>
                    Identificação
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-4">
                        <label for="clienteStatusId">
                            Estado
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="clienteStatusId"
                            name="status_id"
                            required>
                            <option value="">
                                A carregar estados...
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="clienteTipo">
                            Tipo
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="clienteTipo"
                            name="tipo"
                            required>
                            <option value="PARTICULAR">
                                Particular
                            </option>

                            <option value="EMPRESA">
                                Empresa
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="clienteNuit">
                            NUIT
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="clienteNuit"
                            name="nuit"
                            maxlength="30">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="clienteNome">
                            Nome
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="clienteNome"
                            name="nome"
                            maxlength="150"
                            required>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="clienteNomeComercial">
                            Nome Comercial
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="clienteNomeComercial"
                            name="nome_comercial"
                            maxlength="150">
                    </div>

                </div>

                <hr>

                <!-- Contactos -->
                <h5 class="mb-3">
                    <i class="fa fa-phone"></i>
                    Contactos
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-4">
                        <label for="clienteTelefone">
                            Telefone
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="clienteTelefone"
                            name="telefone"
                            maxlength="30">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="clienteEmail">
                            Email
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="clienteEmail"
                            name="email"
                            maxlength="190">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="clienteContacto">
                            Pessoa de Contacto
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="clienteContacto"
                            name="contacto"
                            maxlength="150">
                    </div>

                </div>

                <hr>

                <!-- Localização -->
                <h5 class="mb-3">
                    <i class="fa fa-map-marker"></i>
                    Localização
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-12">
                        <label for="clienteEndereco">
                            Endereço
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="clienteEndereco"
                            name="endereco"
                            maxlength="255">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="clienteBairro">
                            Bairro
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="clienteBairro"
                            name="bairro"
                            maxlength="120">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="clienteCidade">
                            Cidade
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="clienteCidade"
                            name="cidade"
                            maxlength="100">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="clienteProvincia">
                            Província
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="clienteProvincia"
                            name="provincia"
                            maxlength="100">
                    </div>

                    <div class="form-group col-md-3">
                        <label for="clientePais">
                            País
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="clientePais"
                            name="pais"
                            maxlength="100"
                            value="Moçambique">
                    </div>

                </div>

                <hr>

                <!-- Crédito -->
                <h5 class="mb-3">
                    <i class="fa fa-credit-card"></i>
                    Crédito e observações
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-4">
                        <label for="clienteLimiteCredito">
                            Limite de Crédito
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="clienteLimiteCredito"
                            name="limite_credito"
                            min="0"
                            step="0.01"
                            value="0.00">
                    </div>

                    <div class="form-group col-md-8">
                        <label for="clienteObservacoes">
                            Observações
                        </label>

                        <textarea
                            class="form-control"
                            id="clienteObservacoes"
                            name="observacoes"
                            rows="3"></textarea>
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
                    id="clienteBtnGuardar">
                    <i class="fa fa-save"></i>
                    Guardar
                </button>

            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar -->
<div
    id="clienteModalEliminar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="clienteModalEliminarTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">
        <form
            id="clienteFormEliminar"
            class="modal-content">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="clienteModalEliminarTitulo">
                    Eliminar Cliente
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
                    id="clienteErroEliminar"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    id="clienteEliminarId"
                    name="id">

                <input
                    type="hidden"
                    name="acao"
                    value="eliminar">

                <p>
                    Pretende realmente eliminar o cliente
                    <strong id="clienteEliminarNome"></strong>?
                </p>

                <p class="text-danger mb-0">
                    A eliminação será impedida caso existam vendas, pagamentos
                    ou outros registos associados.
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
                    id="clienteBtnEliminar">
                    <i class="fa fa-trash"></i>
                    Eliminar
                </button>

            </div>
        </form>
    </div>
</div>

<!-- Modal Importação Excel de Clientes -->
<div
    id="clienteModalImportar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="clienteModalImportarTitulo"
    aria-hidden="true">

    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clienteModalImportarTitulo">
                    <i class="fa fa-file-excel-o"></i>
                    Importar Clientes por Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="clienteImportacaoErro" class="alert alert-danger d-none"></div>
                <div id="clienteImportacaoSucesso" class="alert alert-success d-none"></div>

                <!-- PASSO 1 -->
                <div id="clienteImportacaoPasso1">
                    <div class="alert alert-info">
                        <strong>Como funciona?</strong>
                        <ol class="mb-0 mt-2">
                            <li>Baixe o modelo Excel.</li>
                            <li>Preencha os dados dos clientes.</li>
                            <li>Selecione o ficheiro.</li>
                            <li>Clique em <strong>Validar Excel</strong>.</li>
                            <li>Confira os erros e registos válidos.</li>
                            <li>Confirme a importação.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-success" id="clienteBtnModeloExcel">
                            <i class="fa fa-download"></i> Baixar modelo Excel
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="clienteFicheiroExcel">
                            Ficheiro Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control-file" id="clienteFicheiroExcel" accept=".xlsx,.xls">
                        <small class="form-text text-muted">Formatos permitidos: XLSX e XLS. Tamanho máximo: 10 MB.</small>
                    </div>

                    <button type="button" class="btn btn-primary" id="clienteBtnValidarExcel">
                        <i class="fa fa-check"></i> Validar Excel
                    </button>
                </div>

                <!-- PASSO 2 -->
                <div id="clienteImportacaoPasso2" class="d-none">
                    <div id="clienteImportacaoResumo" class="mb-3"></div>

                    <div id="clienteImportacaoErrosContainer" class="d-none">
                        <h5 class="text-danger"><i class="fa fa-warning"></i> Linhas com erro</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Nome</th>
                                        <th>NUIT</th>
                                        <th>Erro</th>
                                    </tr>
                                </thead>
                                <tbody id="clienteImportacaoErros"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="clienteImportacaoValidosContainer" class="mt-4">
                        <h5 class="text-success"><i class="fa fa-check-circle"></i> Clientes prontos para importar</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Nome</th>
                                        <th>Tipo</th>
                                        <th>NUIT</th>
                                        <th>Telefone</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody id="clienteImportacaoValidos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary d-none" id="clienteBtnVoltarImportacao">
                    <i class="fa fa-arrow-left"></i> Voltar
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success d-none" id="clienteBtnConfirmarImportacao">
                    <i class="fa fa-upload"></i> Importar Clientes Válidos
                </button>
            </div>
        </div>
    </div>
</div>