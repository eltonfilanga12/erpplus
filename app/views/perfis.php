<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Perfis
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Perfis
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-primary"
                        id="perfilBtnNovo">
                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Novo Perfil
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Card -->
        <div class="card">
            <div class="card-body">

                <div
                    id="perfilErroLista"
                    class="alert alert-danger d-none"></div>

                <div class="table-responsive">
                    <table
                        id="perfilTabela"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Estado</th>
                                <th>Criado em</th>
                                <th class="text-right">
                                    Ações
                                </th>
                            </tr>
                        </thead>

                        <tbody id="perfilTabelaBody">
                           
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal Criar/Editar -->
<div
    id="perfilModal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="perfilModalTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">
        <form
            id="perfilForm"
            class="modal-content"
            autocomplete="off">
            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="perfilModalTitulo">
                    Novo Perfil
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
                    id="perfilErroFormulario"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    id="perfilId"
                    name="id">

                <input
                    type="hidden"
                    name="acao"
                    value="guardar">

                <div class="form-group">
                    <label for="perfilNome">
                        Nome
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="perfilNome"
                        name="nome"
                        maxlength="120"
                        required>
                </div>

                <div class="form-group">
                    <label for="perfilDescricao">
                        Descrição
                    </label>

                    <textarea
                        class="form-control"
                        id="perfilDescricao"
                        name="descricao"
                        rows="4"
                        maxlength="500"></textarea>
                </div>

                <div class="form-group">
                    <label for="perfilStatusId">
                        Estado
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        class="form-control"
                        id="perfilStatusId"
                        name="status_id"
                        required>
                        <option value="">
                            A carregar estados...
                        </option>
                    </select>
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
                    id="perfilBtnGuardar">
                    <i class="fa fa-save"></i>
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar -->
<div
    id="perfilModalEliminar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="perfilModalEliminarTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">
        <form
            id="perfilFormEliminar"
            class="modal-content">
            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="perfilModalEliminarTitulo">
                    Eliminar Perfil
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
                    id="perfilErroEliminar"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    id="perfilEliminarId"
                    name="id">

                <input
                    type="hidden"
                    name="acao"
                    value="eliminar">

                <p>
                    Pretende realmente eliminar o perfil
                    <strong id="perfilEliminarNome"></strong>?
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
                    id="perfilBtnEliminar">
                    <i class="fa fa-trash"></i>
                    Eliminar
                </button>
            </div>
        </form>
    </div>
</div>