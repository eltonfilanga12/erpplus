<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">

            <div class="row">

                <div class="col">

                    <h3 class="page-title">
                        Perfil do Utilizador
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="dashboard.php">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Perfil
                        </li>

                    </ul>

                </div>

            </div>

        </div>
        <!-- /Page Header -->


        <!-- Mensagens -->
        <div
            id="perfilErro"
            class="alert alert-danger d-none">
        </div>

        <div
            id="perfilSucesso"
            class="alert alert-success d-none">
        </div>


        <!-- Loading -->
        <div
            id="perfilLoading"
            class="text-center py-3 d-none">

            <div
                class="spinner-border text-primary"
                role="status">

                <span class="sr-only">
                    A carregar...
                </span>

            </div>

            <div class="mt-2 text-muted">
                A carregar perfil...
            </div>

        </div>


        <div class="row">

            <div class="col-md-12">

                <!-- Profile Header -->
                <div class="profile-header">

                    <div class="row align-items-center">

                        <!-- Avatar -->
                        <div class="col-auto profile-image">

                            <div
                                class="position-relative"
                                style="
                                    width: 120px;
                                    height: 120px;
                                ">

                                <img
                                    class="rounded-circle"
                                    id="perfilAvatar"
                                    alt="Fotografia do utilizador"
                                    src="/erpplus/public/assets/img/user.jpg"
                                    style="
                                        width: 120px;
                                        height: 120px;
                                        object-fit: cover;
                                    ">

                            </div>

                        </div>


                        <!-- Informações -->
                        <div
                            class="col ml-md-n2 profile-user-info">

                            <h4
                                class="user-name mb-0"
                                id="perfilNome">
                                -
                            </h4>

                            <h6
                                class="text-muted"
                                id="perfilFuncao">
                                -
                            </h6>

                            <div class="user-Location">

                                <i class="fa fa-building"></i>

                                <span id="perfilEmpresa">
                                    -
                                </span>

                                <span class="mx-1">
                                    •
                                </span>

                                <span id="perfilFilial">
                                    -
                                </span>

                            </div>

                            <div class="user-Location mt-1">

                                <i class="fa fa-map-marker"></i>

                                <span id="perfilLocalizacao">
                                    -
                                </span>

                            </div>

                            <div class="about-text mt-2">

                                Último acesso:

                                <strong
                                    id="perfilUltimoLogin">
                                    -
                                </strong>

                            </div>

                        </div>


                        <!-- Botões -->
                        <div class="col-auto profile-btn">

                            <label
                                for="inputAvatarPerfil"
                                class="btn btn-outline-primary mb-1">

                                <i class="fa fa-camera mr-1"></i>

                                Alterar foto

                            </label>

                            <input
                                type="file"
                                id="inputAvatarPerfil"
                                accept=".jpg,.jpeg,.png,.webp"
                                class="d-none">

                            <button
                                type="button"
                                class="btn btn-outline-danger mb-1"
                                id="btnRemoverAvatar">

                                <i class="fa fa-trash mr-1"></i>

                                Remover foto

                            </button>

                            <a
                                href="#edit_personal_details"
                                class="btn btn-primary mb-1"
                                data-toggle="modal">

                                <i class="fa fa-edit mr-1"></i>

                                Editar Perfil

                            </a>

                        </div>

                    </div>

                </div>
                <!-- /Profile Header -->


                <!-- Profile Menu -->
                <div class="profile-menu">

                    <ul
                        class="nav nav-tabs nav-tabs-solid">

                        <li class="nav-item">

                            <a
                                class="nav-link active"
                                data-toggle="tab"
                                href="#per_details_tab">

                                Dados Pessoais

                            </a>

                        </li>

                        <li class="nav-item">

                            <a
                                class="nav-link"
                                data-toggle="tab"
                                href="#password_tab">

                                Segurança

                            </a>

                        </li>

                    </ul>

                </div>
                <!-- /Profile Menu -->


                <div class="tab-content profile-tab-cont">


                    <!-- ================================================= -->
                    <!-- DADOS PESSOAIS -->
                    <!-- ================================================= -->

                    <div
                        class="tab-pane fade show active"
                        id="per_details_tab">

                        <div class="row">

                            <!-- Dados -->
                            <div class="col-lg-9">

                                <div class="card">

                                    <div class="card-body">

                                        <h5
                                            class="card-title d-flex
                                                   justify-content-between">

                                            <span>
                                                Dados do Utilizador
                                            </span>

                                            <a
                                                class="edit-link"
                                                data-toggle="modal"
                                                href="#edit_personal_details">

                                                <i
                                                    class="fa fa-edit mr-1">
                                                </i>

                                                Editar

                                            </a>

                                        </h5>


                                        <!-- Nome -->
                                        <div class="row">

                                            <p
                                                class="col-sm-3
                                                       text-muted
                                                       text-sm-right
                                                       mb-0
                                                       mb-sm-3">

                                                Nome

                                            </p>

                                            <p
                                                class="col-sm-9"
                                                id="perfilDetalheNome">
                                                -
                                            </p>

                                        </div>


                                        <!-- Username -->
                                        <div class="row">

                                            <p
                                                class="col-sm-3
                                                       text-muted
                                                       text-sm-right
                                                       mb-0
                                                       mb-sm-3">

                                                Utilizador

                                            </p>

                                            <p
                                                class="col-sm-9"
                                                id="perfilDetalheUsername">
                                                -
                                            </p>

                                        </div>


                                        <!-- Email -->
                                        <div class="row">

                                            <p
                                                class="col-sm-3
                                                       text-muted
                                                       text-sm-right
                                                       mb-0
                                                       mb-sm-3">

                                                Email

                                            </p>

                                            <p
                                                class="col-sm-9"
                                                id="perfilDetalheEmail">
                                                -
                                            </p>

                                        </div>


                                        <!-- Telefone -->
                                        <div class="row">

                                            <p
                                                class="col-sm-3
                                                       text-muted
                                                       text-sm-right
                                                       mb-0
                                                       mb-sm-3">

                                                Telefone

                                            </p>

                                            <p
                                                class="col-sm-9"
                                                id="perfilDetalheTelefone">
                                                -
                                            </p>

                                        </div>


                                        <!-- Perfil -->
                                        <div class="row">

                                            <p
                                                class="col-sm-3
                                                       text-muted
                                                       text-sm-right
                                                       mb-0
                                                       mb-sm-3">

                                                Perfil

                                            </p>

                                            <p
                                                class="col-sm-9"
                                                id="perfilDetalhePerfil">
                                                -
                                            </p>

                                        </div>


                                        <!-- Empresa -->
                                        <div class="row">

                                            <p
                                                class="col-sm-3
                                                       text-muted
                                                       text-sm-right
                                                       mb-0
                                                       mb-sm-3">

                                                Empresa

                                            </p>

                                            <p
                                                class="col-sm-9"
                                                id="perfilDetalheEmpresa">
                                                -
                                            </p>

                                        </div>


                                        <!-- Filial -->
                                        <div class="row">

                                            <p
                                                class="col-sm-3
                                                       text-muted
                                                       text-sm-right
                                                       mb-0
                                                       mb-sm-3">

                                                Filial

                                            </p>

                                            <p
                                                class="col-sm-9"
                                                id="perfilDetalheFilial">
                                                -
                                            </p>

                                        </div>


                                        <!-- Estado -->
                                        <div class="row">

                                            <p
                                                class="col-sm-3
                                                       text-muted
                                                       text-sm-right
                                                       mb-0
                                                       mb-sm-3">

                                                Estado

                                            </p>

                                            <p
                                                class="col-sm-9"
                                                id="perfilDetalheStatus">
                                                -
                                            </p>

                                        </div>


                                        <!-- Criado em -->
                                        <div class="row">

                                            <p
                                                class="col-sm-3
                                                       text-muted
                                                       text-sm-right
                                                       mb-0
                                                       mb-sm-3">

                                                Conta criada em

                                            </p>

                                            <p
                                                class="col-sm-9"
                                                id="perfilDetalheCriadoEm">
                                                -
                                            </p>

                                        </div>


                                        <!-- Último Login -->
                                        <div class="row">

                                            <p
                                                class="col-sm-3
                                                       text-muted
                                                       text-sm-right
                                                       mb-0">

                                                Último acesso

                                            </p>

                                            <p
                                                class="col-sm-9 mb-0"
                                                id="perfilDetalheUltimoLogin">
                                                -
                                            </p>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- Lateral -->
                            <div class="col-lg-3">

                                <!-- Estado da Conta -->
                                <div class="card">

                                    <div class="card-body">

                                        <h5
                                            class="card-title">

                                            Estado da Conta

                                        </h5>

                                        <button
                                            class="btn btn-info"
                                            id="perfilStatus"
                                            type="button"
                                            disabled>

                                            -

                                        </button>

                                    </div>

                                </div>


                                <!-- Informação -->
                                <div class="card">

                                    <div class="card-body">

                                        <h5 class="card-title">
                                            Conta
                                        </h5>

                                        <p class="mb-2">

                                            <small class="text-muted">
                                                Empresa
                                            </small>

                                            <br>

                                            <strong id="perfilEmpresa">
                                                -
                                            </strong>

                                        </p>

                                        <p class="mb-2">

                                            <small class="text-muted">
                                                Filial
                                            </small>

                                            <br>

                                            <strong id="perfilFilial">
                                                -
                                            </strong>

                                        </p>

                                        <p class="mb-0">

                                            <small class="text-muted">
                                                Último acesso
                                            </small>

                                            <br>

                                            <strong
                                                id="perfilUltimoLogin">
                                                -
                                            </strong>

                                        </p>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- SEGURANÇA -->
                    <!-- ================================================= -->

                    <div
                        id="password_tab"
                        class="tab-pane fade">

                        <div class="card">

                            <div class="card-body">

                                <h5 class="card-title">
                                    Alterar Senha
                                </h5>


                                <div
                                    id="senhaErro"
                                    class="alert alert-danger d-none">
                                </div>

                                <div
                                    id="senhaSucesso"
                                    class="alert alert-success d-none">
                                </div>


                                <div class="row">

                                    <div class="col-md-10 col-lg-6">

                                        <form
                                            id="formAlterarSenha"
                                            autocomplete="off">

                                            <!-- Senha atual -->
                                            <div class="form-group">

                                                <label>
                                                    Senha Atual
                                                    <span class="text-danger">
                                                        *
                                                    </span>
                                                </label>

                                                <input
                                                    type="password"
                                                    class="form-control"
                                                    name="senha_atual"
                                                    autocomplete="current-password"
                                                    required>

                                            </div>


                                            <!-- Nova senha -->
                                            <div class="form-group">

                                                <label>
                                                    Nova Senha
                                                    <span class="text-danger">
                                                        *
                                                    </span>
                                                </label>

                                                <input
                                                    type="password"
                                                    class="form-control"
                                                    name="nova_senha"
                                                    autocomplete="new-password"
                                                    minlength="8"
                                                    required>

                                                <small class="form-text text-muted">
                                                    Utilize pelo menos 8 caracteres.
                                                </small>

                                            </div>


                                            <!-- Confirmar -->
                                            <div class="form-group">

                                                <label>
                                                    Confirmar Nova Senha
                                                    <span class="text-danger">
                                                        *
                                                    </span>
                                                </label>

                                                <input
                                                    type="password"
                                                    class="form-control"
                                                    name="confirmar_senha"
                                                    autocomplete="new-password"
                                                    minlength="8"
                                                    required>

                                            </div>


                                            <button
                                                class="btn btn-primary"
                                                type="submit">

                                                <i class="fa fa-key mr-1"></i>

                                                Alterar Senha

                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                </div>

            </div>

        </div>

    </div>

</div>
<!-- /Page Wrapper -->


<!-- ============================================================= -->
<!-- MODAL EDITAR PERFIL -->
<!-- ============================================================= -->

<div
    class="modal fade"
    id="edit_personal_details"
    aria-hidden="true"
    role="dialog">

    <div
        class="modal-dialog modal-dialog-centered"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Editar Perfil
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

                <form
                    id="formEditarPerfil"
                    autocomplete="off">

                    <div class="row form-row">


                        <!-- Nome -->
                        <div class="col-12">

                            <div class="form-group">

                                <label>
                                    Nome Completo
                                    <span class="text-danger">
                                        *
                                    </span>
                                </label>

                                <input
                                    type="text"
                                    id="editarPerfilNome"
                                    name="nome"
                                    class="form-control"
                                    maxlength="150"
                                    required>

                            </div>

                        </div>


                        <!-- Username -->
                        <div class="col-12 col-sm-6">

                            <div class="form-group">

                                <label>
                                    Utilizador
                                    <span class="text-danger">
                                        *
                                    </span>
                                </label>

                                <input
                                    type="text"
                                    id="editarPerfilUsername"
                                    name="username"
                                    class="form-control"
                                    maxlength="100"
                                    required>

                            </div>

                        </div>


                        <!-- Email -->
                        <div class="col-12 col-sm-6">

                            <div class="form-group">

                                <label>
                                    Email
                                    <span class="text-danger">
                                        *
                                    </span>
                                </label>

                                <input
                                    type="email"
                                    id="editarPerfilEmail"
                                    name="email"
                                    class="form-control"
                                    maxlength="190"
                                    required>

                            </div>

                        </div>


                        <!-- Telefone -->
                        <div class="col-12">

                            <div class="form-group">

                                <label>
                                    Telefone
                                </label>

                                <input
                                    type="text"
                                    id="editarPerfilTelefone"
                                    name="telefone"
                                    class="form-control"
                                    maxlength="30">

                            </div>

                        </div>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary btn-block">

                        <i class="fa fa-save mr-1"></i>

                        Guardar Alterações

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>
<!-- /Modal Editar Perfil -->