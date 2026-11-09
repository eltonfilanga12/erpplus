<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Armazéns
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Armazéns
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-primary"
                        id="armazemBtnNovo">
                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Novo Armazém
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <div class="card">
            <div class="card-body">

                <div
                    id="armazemErroLista"
                    class="alert alert-danger d-none"></div>

                <div class="table-responsive">
                    <table
                        id="armazemTabela"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Filial</th>
                                <th>Armazém</th>
                                <th>Código</th>
                                <th>Responsável</th>
                                <th>Telefone</th>
                                <th>Endereço</th>
                                <th>Estado</th>
                                <th>Criado em</th>
                                <th class="text-right">
                                    Ações
                                </th>
                            </tr>
                        </thead>

                        <tbody id="armazemTabelaBody">
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal Criar/Editar -->
<div
    id="armazemModal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="armazemModalTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-lg"
        role="document">
        <form
            id="armazemForm"
            class="modal-content"
            autocomplete="off">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="armazemModalTitulo">
                    Novo Armazém
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
                    id="armazemErroFormulario"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    name="id"
                    id="armazemId">

                <input
                    type="hidden"
                    name="acao"
                    value="salvar">

                <div class="form-row">

                    <div class="form-group col-md-6">
                        <label for="armazemFilialId">
                            Filial
                        </label>

                        <select
                            class="form-control"
                            name="filial_id"
                            id="armazemFilialId">
                            <option value="">
                                A carregar filiais...
                            </option>
                        </select>

                        <small class="form-text text-muted">
                            Pode deixar vazio para um armazém geral da empresa.
                        </small>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="armazemStatusId">
                            Estado
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            name="status_id"
                            id="armazemStatusId"
                            required>
                            <option value="">
                                A carregar estados...
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="armazemNome">
                            Nome do Armazém
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="nome"
                            id="armazemNome"
                            maxlength="150"
                            required>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="armazemCodigo">
                            Código
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="codigo"
                            id="armazemCodigo"
                            maxlength="30"
                            placeholder="Ex.: ARM-XAI-001">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="armazemResponsavel">
                            Responsável
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="responsavel"
                            id="armazemResponsavel"
                            maxlength="150">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="armazemTelefone">
                            Telefone
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="telefone"
                            id="armazemTelefone"
                            maxlength="30">
                    </div>

                    <div class="form-group col-md-12">
                        <label for="armazemEndereco">
                            Endereço
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="endereco"
                            id="armazemEndereco"
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
                    id="armazemBtnGuardar">
                    <i class="fa fa-save"></i>
                    Guardar
                </button>

            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar -->
<div
    id="armazemModalEliminar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="armazemModalEliminarTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">
        <form
            id="armazemFormEliminar"
            class="modal-content">
            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="armazemModalEliminarTitulo">
                    Eliminar Armazém
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
                    id="armazemErroEliminar"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    name="id"
                    id="armazemEliminarId">

                <input
                    type="hidden"
                    name="acao"
                    value="eliminar">

                <p>
                    Pretende realmente eliminar o armazém
                    <strong id="armazemEliminarNome"></strong>?
                </p>

                <p class="text-danger mb-0">
                    A eliminação será impedida caso existam movimentos ou outros registos associados.
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
                    id="armazemBtnEliminar">
                    <i class="fa fa-trash"></i>
                    Eliminar
                </button>

            </div>
        </form>
    </div>
</div>