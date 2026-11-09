<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Empresas
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Empresas
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-primary"
                        id="empresaBtnNova"
                    >
                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Nova Empresa
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Card -->
        <div class="card">
            <div class="card-body">

                <div
                    id="empresaErroLista"
                    class="alert alert-danger d-none"
                ></div>

                <div class="table-responsive">
                    <table
                        id="empresaTabela"
                        class="table table-striped custom-table mb-0"
                    >
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Empresa</th>
                                <th>NUIT</th>
                                <th>Contacto</th>
                                <th>Localização</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Moeda</th>
                                <th class="text-right">
                                    Ações
                                </th>
                            </tr>
                        </thead>

                        <tbody id="empresaTabelaBody">
                        
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal Empresa -->
<div
    id="empresaModal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="empresaModalTitulo"
    aria-hidden="true"
>
    <div
        class="modal-dialog modal-lg"
        role="document"
    >
        <form
            id="empresaForm"
            class="modal-content"
            enctype="multipart/form-data"
            autocomplete="off"
        >
            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="empresaModalTitulo"
                >
                    Nova Empresa
                </h5>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Fechar"
                >
                    <span aria-hidden="true">
                        &times;
                    </span>
                </button>
            </div>

            <div class="modal-body">

                <div
                    id="empresaErroFormulario"
                    class="alert alert-danger d-none"
                ></div>

                <input
                    type="hidden"
                    id="empresaId"
                    name="id"
                >

                <input
                    type="hidden"
                    id="empresaLogoAtual"
                    name="logo_atual"
                >

                <input
                    type="hidden"
                    name="acao"
                    value="salvar"
                >

                <!-- Dados principais -->
                <h5 class="mb-3">
                    <i class="fa fa-building"></i>
                    Dados da Empresa
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-6">
                        <label for="empresaNome">
                            Nome da Empresa
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="nome"
                            id="empresaNome"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="form-group col-md-6">
                        <label for="empresaNomeFantasia">
                            Nome Fantasia
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="nome_fantasia"
                            id="empresaNomeFantasia"
                            maxlength="150"
                        >
                    </div>

                    <div class="form-group col-md-6">
                        <label for="empresaNuit">
                            NUIT
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="nuit"
                            id="empresaNuit"
                            maxlength="30"
                        >
                    </div>

                    <div class="form-group col-md-6">
                        <label for="empresaTipo">
                            Tipo de Empresa
                        </label>

                        <select
                            class="form-control"
                            name="tipo_empresa"
                            id="empresaTipo"
                        >
                            <option value="">
                                Selecione
                            </option>

                            <option value="Pequena">
                                Pequena
                            </option>

                            <option value="Média">
                                Média
                            </option>

                            <option value="Grande">
                                Grande
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="empresaRamoActividade">
                            Ramo de Actividade
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="ramo_actividade"
                            id="empresaRamoActividade"
                            maxlength="150"
                        >
                    </div>

                    <div class="form-group col-md-6">
                        <label for="empresaStatusId">
                            Estado
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            class="form-control"
                            name="status_id"
                            id="empresaStatusId"
                            required
                        >
                            <option value="">
                                A carregar estados...
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="empresaLogo">
                            Logo da Empresa
                        </label>

                        <input
                            type="file"
                            class="form-control"
                            name="logo"
                            id="empresaLogo"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                        <small class="form-text text-muted">
                            Formatos permitidos: JPG, JPEG, PNG e WEBP.
                        </small>
                    </div>

                    <div class="form-group col-md-6">
                        <label>
                            Pré-visualização
                        </label>

                        <div>
                            <img
                                id="empresaPreviewLogo"
                                src="/erpplus/assets/img/default-logo.png"
                                alt="Logo da empresa"
                                width="100"
                                height="100"
                                class="img-thumbnail"
                                style="object-fit: contain;"
                            >
                        </div>
                    </div>

                </div>

                <hr>

                <!-- Contactos -->
                <h5 class="mb-3">
                    <i class="fa fa-phone"></i>
                    Contactos
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-6">
                        <label for="empresaEmail">
                            Email
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            name="email"
                            id="empresaEmail"
                            maxlength="190"
                        >
                    </div>

                    <div class="form-group col-md-6">
                        <label for="empresaTelefone">
                            Telefone
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="telefone"
                            id="empresaTelefone"
                            maxlength="30"
                        >
                    </div>

                    <div class="form-group col-md-6">
                        <label for="empresaWebsite">
                            Website
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="website"
                            id="empresaWebsite"
                            maxlength="190"
                        >
                    </div>

                    <div class="form-group col-md-6">
                        <label for="empresaContactoPrincipal">
                            Contacto Principal
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="contacto_principal"
                            id="empresaContactoPrincipal"
                            maxlength="150"
                        >
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
                        <label for="empresaLogradouro">
                            Endereço
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="logradouro"
                            id="empresaLogradouro"
                            maxlength="255"
                        >
                    </div>

                    <div class="form-group col-md-4">
                        <label for="empresaProvincia">
                            Província
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="provincia"
                            id="empresaProvincia"
                            maxlength="100"
                        >
                    </div>

                    <div class="form-group col-md-4">
                        <label for="empresaCidade">
                            Cidade
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="cidade"
                            id="empresaCidade"
                            maxlength="100"
                        >
                    </div>

                    <div class="form-group col-md-4">
                        <label for="empresaCodigoPostal">
                            Código Postal
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="codigo_postal"
                            id="empresaCodigoPostal"
                            maxlength="20"
                        >
                    </div>

                    <div class="form-group col-md-6">
                        <label for="empresaPais">
                            País
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="pais"
                            id="empresaPais"
                            maxlength="100"
                            value="Moçambique"
                        >
                    </div>

                </div>

                <hr>

                <!-- Configuração -->
                <h5 class="mb-3">
                    <i class="fa fa-cogs"></i>
                    Configuração
                </h5>

                <div class="form-row">

                    <div class="form-group col-md-6">
                        <label for="empresaMoeda">
                            Moeda
                        </label>

                        <select
                            class="form-control"
                            name="moeda"
                            id="empresaMoeda"
                        >
                            <option value="MZN">
                                Metical (MZN)
                            </option>

                            <option value="USD">
                                Dólar (USD)
                            </option>

                            <option value="ZAR">
                                Rand (ZAR)
                            </option>

                            <option value="EUR">
                                Euro (EUR)
                            </option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="empresaFusoHorario">
                            Fuso Horário
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="fuso_horario"
                            id="empresaFusoHorario"
                            maxlength="50"
                            value="Africa/Maputo"
                        >
                    </div>

                    <div class="form-group col-md-12">
                        <label for="empresaSlogan">
                            Slogan
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="slogan"
                            id="empresaSlogan"
                            maxlength="255"
                        >
                    </div>

                    <div class="form-group col-md-12">
                        <label for="empresaDescricao">
                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            rows="3"
                            name="descricao"
                            id="empresaDescricao"
                        ></textarea>
                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-dismiss="modal"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="empresaBtnGuardar"
                >
                    <i class="fa fa-save"></i>
                    Guardar
                </button>

            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar -->
<div
    id="empresaModalEliminar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="empresaModalEliminarTitulo"
    aria-hidden="true"
>
    <div
        class="modal-dialog modal-dialog-centered"
        role="document"
    >
        <form
            id="empresaFormEliminar"
            class="modal-content"
        >
            <div class="modal-header">
                <h5
                    class="modal-title"
                    id="empresaModalEliminarTitulo"
                >
                    Eliminar Empresa
                </h5>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Fechar"
                >
                    <span aria-hidden="true">
                        &times;
                    </span>
                </button>
            </div>

            <div class="modal-body">

                <div
                    id="empresaErroEliminar"
                    class="alert alert-danger d-none"
                ></div>

                <input
                    type="hidden"
                    id="empresaEliminarId"
                    name="id"
                >

                <input
                    type="hidden"
                    name="acao"
                    value="eliminar"
                >

                <p>
                    Pretende realmente eliminar a empresa
                    <strong id="empresaEliminarNome"></strong>?
                </p>

                <p class="text-danger mb-0">
                    A eliminação pode ser impedida caso existam registos associados.
                </p>
            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-dismiss="modal"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn btn-danger"
                    id="empresaBtnEliminar"
                >
                    <i class="fa fa-trash"></i>
                    Eliminar
                </button>

            </div>
        </form>
    </div>
</div>