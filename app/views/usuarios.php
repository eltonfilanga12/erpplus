<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Utilizadores
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Utilizadores
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoUsuario">
                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Novo Utilizador
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Card -->
        <div class="card">
            <div class="card-body">

                <div
                    id="erroListaUsuarios"
                    class="alert alert-danger d-none"></div>

                <div class="table-responsive">
                    <table
                        id="usuariosTable"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Perfil</th>
                                <th>Estado</th>
                                <th>Criado em</th>
                                <th class="text-right">
                                    Ações
                                </th>
                            </tr>
                        </thead>

                        <tbody id="usuariosTableBody">

                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal Criar/Editar -->
<div
    id="modalUsuario"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalUsuarioTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-lg modal-dialog-centered"
        role="document">
        <form
            id="formUsuario"
            class="modal-content"
            autocomplete="off">
            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="modalUsuarioTitulo">
                    Novo Utilizador
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
                    id="erroFormularioUsuario"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    id="usuarioId"
                    name="id">

                <input
                    type="hidden"
                    name="acao"
                    value="guardar">

                <div class="form-row">

                    <div class="form-group col-md-6">
                        <label for="usuarioNome">
                            Nome completo
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="nome"
                            id="usuarioNome"
                            maxlength="180"
                            required>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="usuarioUsername">
                            Username
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="username"
                            id="usuarioUsername"
                            maxlength="80"
                            autocomplete="off"
                            required>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="usuarioEmail">
                            Email
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            name="email"
                            id="usuarioEmail"
                            maxlength="180">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="usuarioTelefone">
                            Telefone
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="telefone"
                            id="usuarioTelefone"
                            maxlength="30">
                    </div>

                    <div class="form-group col-md-6">
                        <label for="usuarioPerfilId">
                            Perfil
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            name="perfil_id"
                            id="usuarioPerfilId"
                            required>
                            <option value="">
                                A carregar perfis...
                            </option>
                        </select>
                    </div>

                    <div
                        class="form-group col-md-6 d-none"
                        id="grupoUsuarioEmpresa">

                        <label for="usuarioEmpresaId">
                            Empresa

                            <span class="text-danger">
                                *
                            </span>
                        </label>

                        <select
                            class="form-control"
                            name="empresa_id"
                            id="usuarioEmpresaId">

                            <option value="">
                                Selecione a empresa
                            </option>

                        </select>

                        <small class="form-text text-muted">
                            Disponível apenas para Super Administrador.
                        </small>

                    </div>

                    <div class="form-group col-md-6">
                        <label for="usuarioFilialId">
                            Filial
                        </label>

                        <select
                            class="form-control"
                            name="filial_id"
                            id="usuarioFilialId">
                            <option value="">
                                Todas / Sede
                            </option>
                        </select>

                        <small class="form-text text-muted">
                            Deixe vazio para permitir acesso geral à empresa.
                        </small>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="usuarioStatusId">
                            Estado
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            name="status_id"
                            id="usuarioStatusId"
                            required>
                            <option value="">
                                A carregar estados...
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="usuarioSenha">
                            Senha
                            <span
                                id="senhaObrigatoria"
                                class="text-danger">
                                *
                            </span>
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            name="senha"
                            id="usuarioSenha"
                            minlength="6"
                            autocomplete="new-password">

                        <small
                            id="ajudaSenha"
                            class="form-text text-muted">
                            Informe uma senha com pelo menos 6 caracteres.
                        </small>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="usuarioConfirmarSenha">
                            Confirmar senha
                            <span
                                id="confirmarSenhaObrigatoria"
                                class="text-danger">
                                *
                            </span>
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            name="confirmar_senha"
                            id="usuarioConfirmarSenha"
                            minlength="6"
                            autocomplete="new-password">
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
                    id="btnGuardarUsuario">
                    <i class="fa fa-save"></i>
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar -->
<div
    id="modalEliminarUsuario"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalEliminarUsuarioTitulo"
    aria-hidden="true">
    <div
        class="modal-dialog modal-dialog-centered"
        role="document">
        <form
            id="formEliminarUsuario"
            class="modal-content">
            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="modalEliminarUsuarioTitulo">
                    Eliminar Utilizador
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
                    id="erroEliminarUsuario"
                    class="alert alert-danger d-none"></div>

                <input
                    type="hidden"
                    id="eliminarUsuarioId"
                    name="id">

                <input
                    type="hidden"
                    name="acao"
                    value="eliminar">

                <p class="mb-0">
                    Pretende realmente eliminar o utilizador
                    <strong id="eliminarUsuarioNome"></strong>?
                </p>

                <p class="text-danger mt-2 mb-0">
                    Esta operação pode não ser reversível.
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
                    id="btnEliminarUsuario">
                    <i class="fa fa-trash"></i>
                    Eliminar
                </button>
            </div>
        </form>
    </div>
</div>