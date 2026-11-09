<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Fornecedores
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Fornecedores
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="fornecedorBtnImportar">
                        <i class="fa fa-file-excel-o"></i>
                        <span class="d-none d-sm-inline">
                            Importar Excel
                        </span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="fornecedorBtnNovo">
                        <i class="fa fa-plus"></i>
                        <span class="d-none d-sm-inline">
                            Novo Fornecedor
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Tabela -->
        <div class="card">
            <div class="card-body">

                <div
                    id="fornecedorErroLista"
                    class="alert alert-danger d-none"></div>

                <div class="table-responsive">
                    <table
                        id="fornecedorTabela"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fornecedor</th>
                                <th>NUIT</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Cidade</th>
                                <th>Contacto</th>

                                <th class="text-right">
                                    Saldo
                                </th>

                                <th>Estado</th>
                                <th>Criado em</th>

                                <th class="text-right">
                                    Ações
                                </th>
                            </tr>
                        </thead>

                        <tbody id="fornecedorTabelaBody">

                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal Criar/Editar -->
<div
    id="fornecedorModal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="fornecedorModalTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-lg"
        role="document">
        <form
            id="fornecedorForm"
            class="modal-content"
            autocomplete="off">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="fornecedorModalTitulo">
                    Novo Fornecedor
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
                    id="fornecedorErroFormulario"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    name="id"
                    id="fornecedorId">

                <input
                    type="hidden"
                    name="acao"
                    value="salvar">

                <h5 class="mb-3">
                    <i class="fa fa-truck"></i>
                    Identificação
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-6">
                        <label for="fornecedorNome">
                            Nome
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="fornecedorNome"
                            name="nome"
                            maxlength="150"
                            required>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="fornecedorNomeComercial">
                            Nome Comercial
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="fornecedorNomeComercial"
                            name="nome_comercial"
                            maxlength="150">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="fornecedorNuit">
                            NUIT
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="fornecedorNuit"
                            name="nuit"
                            maxlength="30">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="fornecedorStatusId">
                            Estado
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            id="fornecedorStatusId"
                            name="status_id"
                            required>
                            <option value="">
                                A carregar estados...
                            </option>
                        </select>
                    </div>

                </div>

                <hr>

                <h5 class="mb-3">
                    <i class="fa fa-phone"></i>
                    Contactos
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-4">
                        <label for="fornecedorTelefone">
                            Telefone
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="fornecedorTelefone"
                            name="telefone"
                            maxlength="30">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="fornecedorEmail">
                            Email
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="fornecedorEmail"
                            name="email"
                            maxlength="190">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="fornecedorContacto">
                            Pessoa de Contacto
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="fornecedorContacto"
                            name="contacto"
                            maxlength="150">
                    </div>

                </div>

                <hr>

                <h5 class="mb-3">
                    <i class="fa fa-map-marker"></i>
                    Localização
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-12">
                        <label for="fornecedorEndereco">
                            Endereço
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="fornecedorEndereco"
                            name="endereco"
                            maxlength="255">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="fornecedorCidade">
                            Cidade
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="fornecedorCidade"
                            name="cidade"
                            maxlength="100">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="fornecedorPais">
                            País
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="fornecedorPais"
                            name="pais"
                            maxlength="100"
                            value="Moçambique">
                    </div>

                    <div class="form-group col-md-12">
                        <label for="fornecedorObservacoes">
                            Observações
                        </label>

                        <textarea
                            class="form-control"
                            id="fornecedorObservacoes"
                            name="observacoes"
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
                    id="fornecedorBtnGuardar">
                    <i class="fa fa-save"></i>
                    Guardar
                </button>

            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar -->
<div
    id="fornecedorModalEliminar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="fornecedorModalEliminarTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">
        <form
            id="fornecedorFormEliminar"
            class="modal-content">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="fornecedorModalEliminarTitulo">
                    Eliminar Fornecedor
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
                    id="fornecedorErroEliminar"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    id="fornecedorEliminarId"
                    name="id">

                <input
                    type="hidden"
                    name="acao"
                    value="eliminar">

                <p>
                    Pretende realmente eliminar o fornecedor
                    <strong id="fornecedorEliminarNome"></strong>?
                </p>

                <p class="text-danger mb-0">
                    A eliminação será impedida caso existam compras,
                    pagamentos ou produtos associados.
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
                    id="fornecedorBtnEliminar">
                    <i class="fa fa-trash"></i>
                    Eliminar
                </button>

            </div>
        </form>
    </div>
</div>

<!-- ========================================================= -->
<!-- MODAL EXTRATO DO FORNECEDOR -->
<!-- ========================================================= -->

<div
    id="fornecedorModalExtrato"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="fornecedorExtratoTitulo"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <div>
                    <h5
                        class="modal-title"
                        id="fornecedorExtratoTitulo">
                        Extrato do Fornecedor
                    </h5>

                    <small
                        class="text-muted"
                        id="fornecedorExtratoIdentificacao">
                    </small>
                </div>

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
                    id="fornecedorExtratoId">


                <!-- FILTROS -->

                <div class="row mb-3">

                    <div class="col-md-4">

                        <label>
                            Data inicial
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="fornecedorExtratoDataInicial">

                    </div>


                    <div class="col-md-4">

                        <label>
                            Data final
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="fornecedorExtratoDataFinal">

                    </div>


                    <div
                        class="col-md-4
                               d-flex
                               align-items-end">

                        <button
                            type="button"
                            class="btn btn-primary btn-block"
                            id="fornecedorExtratoBtnPesquisar">

                            <i class="fa fa-search"></i>
                            Pesquisar

                        </button>

                    </div>

                </div>


                <div
                    id="fornecedorExtratoErro"
                    class="alert alert-danger d-none">
                </div>


                <!-- RESUMO -->

                <div class="row mb-3">

                    <div class="col-md-3">

                        <div class="card">
                            <div class="card-body">

                                <small class="text-muted">
                                    Saldo Anterior
                                </small>

                                <h5
                                    class="mb-0"
                                    id="fornecedorExtratoSaldoAnterior">
                                    0,00 MZN
                                </h5>

                            </div>
                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="card">
                            <div class="card-body">

                                <small class="text-muted">
                                    Débitos
                                </small>

                                <h5
                                    class="mb-0"
                                    id="fornecedorExtratoDebitos">
                                    0,00 MZN
                                </h5>

                            </div>
                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="card">
                            <div class="card-body">

                                <small class="text-muted">
                                    Créditos
                                </small>

                                <h5
                                    class="mb-0"
                                    id="fornecedorExtratoCreditos">
                                    0,00 MZN
                                </h5>

                            </div>
                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="card">
                            <div class="card-body">

                                <small class="text-muted">
                                    Saldo Atual
                                </small>

                                <h5
                                    class="mb-0"
                                    id="fornecedorExtratoSaldoAtual">
                                    0,00 MZN
                                </h5>

                            </div>
                        </div>

                    </div>

                </div>


                <!-- TABELA -->

                <div class="table-responsive">

                    <table
                        class="table table-striped table-sm"
                        id="fornecedorExtratoTabela">

                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Documento</th>
                                <th>Descrição</th>
                                <th>Conta</th>

                                <th class="text-right">
                                    Débito
                                </th>

                                <th class="text-right">
                                    Crédito
                                </th>

                                <th class="text-right">
                                    Saldo
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            id="fornecedorExtratoTabelaBody">
                        </tbody>

                    </table>

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-dismiss="modal">

                    Fechar

                </button>

            </div>

        </div>
    </div>
</div>
<!-- Modal Importação Excel de Fornecedores -->
<div
    id="fornecedorModalImportar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="fornecedorModalImportarTitulo"
    aria-hidden="true">

    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fornecedorModalImportarTitulo">
                    <i class="fa fa-file-excel-o"></i>
                    Importar Fornecedores por Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="fornecedorImportacaoErro" class="alert alert-danger d-none"></div>
                <div id="fornecedorImportacaoSucesso" class="alert alert-success d-none"></div>

                <!-- PASSO 1 -->
                <div id="fornecedorImportacaoPasso1">
                    <div class="alert alert-info">
                        <strong>Como funciona?</strong>
                        <ol class="mb-0 mt-2">
                            <li>Baixe o modelo Excel.</li>
                            <li>Preencha os dados dos fornecedores.</li>
                            <li>Selecione o ficheiro.</li>
                            <li>Clique em <strong>Validar Excel</strong>.</li>
                            <li>Confira os erros e registos válidos.</li>
                            <li>Confirme a importação.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-success" id="fornecedorBtnModeloExcel">
                            <i class="fa fa-download"></i> Baixar modelo Excel
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="fornecedorFicheiroExcel">
                            Ficheiro Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control-file" id="fornecedorFicheiroExcel" accept=".xlsx,.xls">
                        <small class="form-text text-muted">Formatos permitidos: XLSX e XLS. Tamanho máximo: 10 MB.</small>
                    </div>

                    <button type="button" class="btn btn-primary" id="fornecedorBtnValidarExcel">
                        <i class="fa fa-check"></i> Validar Excel
                    </button>
                </div>

                <!-- PASSO 2 -->
                <div id="fornecedorImportacaoPasso2" class="d-none">
                    <div id="fornecedorImportacaoResumo" class="mb-3"></div>

                    <div id="fornecedorImportacaoErrosContainer" class="d-none">
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
                                <tbody id="fornecedorImportacaoErros"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="fornecedorImportacaoValidosContainer" class="mt-4">
                        <h5 class="text-success"><i class="fa fa-check-circle"></i> Fornecedores prontos para importar</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Nome</th>
                                        <th>NUIT</th>
                                        <th>Telefone</th>
                                        <th>Email</th>
                                        <th>Cidade</th>
                                    </tr>
                                </thead>
                                <tbody id="fornecedorImportacaoValidos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary d-none" id="fornecedorBtnVoltarImportacao">
                    <i class="fa fa-arrow-left"></i> Voltar
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success d-none" id="fornecedorBtnConfirmarImportacao">
                    <i class="fa fa-upload"></i> Importar Fornecedores Válidos
                </button>
            </div>
        </div>
    </div>
</div>