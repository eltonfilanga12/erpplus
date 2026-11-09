<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Permissões
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item active">
                            Permissões
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        id="permissaoBtnGuardar"
                        class="btn btn-primary"
                        disabled>
                        <i class="fa fa-save"></i>

                        <span class="d-none d-sm-inline">
                            Guardar Permissões
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <div class="card">
            <div class="card-body">

                <div
                    id="permissaoMensagem"
                    class="alert d-none"></div>

                <div class="row">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="permissaoPerfilId">
                                Perfil
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                id="permissaoPerfilId"
                                class="form-control">
                                <option value="">
                                    A carregar perfis...
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div
                            class="custom-control custom-checkbox mt-4 pt-2">
                            <input
                                type="checkbox"
                                class="custom-control-input"
                                id="permissaoMarcarTodas">

                            <label
                                class="custom-control-label"
                                for="permissaoMarcarTodas">
                                Marcar ou desmarcar todas
                            </label>
                        </div>
                    </div>

                </div>

                <div class="table-responsive mt-3">
                    <table
                        id="permissaoTabela"
                        class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Módulo</th>

                                <th class="text-center">
                                    Visualizar
                                </th>

                                <th class="text-center">
                                    Criar
                                </th>

                                <th class="text-center">
                                    Editar
                                </th>

                                <th class="text-center">
                                    Eliminar
                                </th>

                                <th class="text-center">
                                    Exportar
                                </th>
                            </tr>
                        </thead>

                        <tbody id="permissaoTabelaBody">
                            
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>