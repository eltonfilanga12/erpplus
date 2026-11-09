<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Filiais
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Filiais
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-primary"
                        id="filialBtnNova">
                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Nova Filial
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Card -->
        <div class="card">
            <div class="card-body">

                <div
                    id="filialErroLista"
                    class="alert alert-danger d-none"></div>

                <div class="table-responsive">
                    <table
                        id="filialTabela"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Filial</th>
                                <th>Código</th>
                                <th>Contacto</th>
                                <th>Localização</th>
                                <th>Gerente</th>
                                <th>Estado</th>
                                <th>Criado em</th>
                                <th class="text-right">
                                    Ações
                                </th>
                            </tr>
                        </thead>

                        <tbody id="filialTabelaBody">
                         
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal Criar/Editar -->
<div
    id="filialModal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="filialModalTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-lg"
        role="document">
        <form
            id="filialForm"
            class="modal-content"
            autocomplete="off">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="filialModalTitulo">
                    Nova Filial
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
                    id="filialErroFormulario"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    name="id"
                    id="filialId">

                <input
                    type="hidden"
                    name="acao"
                    value="salvar">

                <!-- Dados principais -->
                <h5 class="mb-3">
                    <i class="fa fa-building"></i>
                    Dados da Filial
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-6">
                        <label for="filialNome">
                            Nome da Filial
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="nome"
                            id="filialNome"
                            maxlength="150"
                            required>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="filialCodigo">
                            Código
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="codigo"
                            id="filialCodigo"
                            maxlength="30"
                            placeholder="Ex.: EFL-XAI-001">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="filialStatusId">
                            Estado
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            name="status_id"
                            id="filialStatusId"
                            required>
                            <option value="">
                                A carregar estados...
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="filialGerente">
                            Gerente
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="gerente"
                            id="filialGerente"
                            maxlength="150">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="filialEmail">
                            Email
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            name="email"
                            id="filialEmail"
                            maxlength="190">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="filialTelefone">
                            Telefone
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="telefone"
                            id="filialTelefone"
                            maxlength="30">
                    </div>

                </div>

                <hr>

                <!-- Localização -->
                <h5 class="mb-3">
                    <i class="fa fa-map-marker"></i>
                    Localização
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-4">
                        <label for="filialProvincia">
                            Província
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="provincia"
                            id="filialProvincia"
                            maxlength="100">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="filialCidade">
                            Cidade
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="cidade"
                            id="filialCidade"
                            maxlength="100">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="filialBairro">
                            Bairro
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="bairro"
                            id="filialBairro"
                            maxlength="100">
                    </div>

                    <div class="form-group col-md-12">
                        <label for="filialEndereco">
                            Endereço
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="endereco"
                            id="filialEndereco"
                            maxlength="255">
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
                    id="filialBtnGuardar">
                    <i class="fa fa-save"></i>
                    Guardar
                </button>

            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar -->
<div
    id="filialModalEliminar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="filialModalEliminarTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">
        <form
            id="filialFormEliminar"
            class="modal-content">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="filialModalEliminarTitulo">
                    Eliminar Filial
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
                    id="filialErroEliminar"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    name="id"
                    id="filialEliminarId">

                <input
                    type="hidden"
                    name="acao"
                    value="eliminar">

                <p>
                    Pretende realmente eliminar a filial
                    <strong id="filialEliminarNome"></strong>?
                </p>

                <p class="text-danger mb-0">
                    A eliminação será impedida caso existam registos relacionados.
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
                    id="filialBtnEliminar">
                    <i class="fa fa-trash"></i>
                    Eliminar
                </button>

            </div>
        </form>
    </div>
</div>