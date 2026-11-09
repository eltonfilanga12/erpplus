<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Bancos
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>

                        <li class="breadcrumb-item active">
                            Bancos
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoBanco">

                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Novo Banco
                        </span>

                    </button>

                </div>

            </div>

        </div>

        <!-- Tabela -->
        <div class="table-responsive">

            <table
                id="bancosTable"
                class="table table-striped custom-table mb-0">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Banco</th>
                        <th>Agência</th>
                        <th>Conta</th>
                        <th>Titular</th>
                        <th>Tipo</th>
                        <th>Moeda</th>
                        <th>Saldo Atual</th>
                        <th>Status</th>
                        <th class="text-right">Ações</th>

                    </tr>

                </thead>

                <tbody></tbody>

            </table>

        </div>

    </div>

</div>


<!-- Modal Banco -->
<div
    id="modalBanco"
    class="modal fade"
    tabindex="-1">

    <div class="modal-dialog modal-xl">

        <form
            id="formBanco"
            class="modal-content">

            <div class="modal-header">

                <h4
                    class="modal-title"
                    id="tituloModalBanco">

                    Novo Banco

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
                    id="banco_id"
                    name="id">

                <div class="form-row">

                    <!-- Filial -->
                    <div class="form-group col-md-6">

                        <label for="filial_id">
                            Filial
                        </label>

                        <select
                            class="form-control"
                            id="banco_filial_id"
                            name="filial_id">

                            <option value="">
                                Sem filial
                            </option>

                        </select>

                    </div>

                    <!-- Status -->
                    <div class="form-group col-md-6">

                        <label for="banco_status_id">
                            Status *
                        </label>

                        <select
                            class="form-control"
                            id="banco_status_id"
                            name="status_id"
                            required>

                            <option value="">
                                Selecione
                            </option>

                        </select>

                    </div>

                    <!-- Nome -->
                    <div class="form-group col-md-6">

                        <label for="nome_banco">
                            Nome do Banco *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="nome_banco"
                            name="nome_banco"
                            maxlength="150"
                            required>

                    </div>

                    <!-- Código -->
                    <div class="form-group col-md-6">

                        <label for="codigo_banco">
                            Código do Banco
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="codigo_banco"
                            name="codigo_banco"
                            maxlength="20">

                    </div>

                    <!-- Agência -->
                    <div class="form-group col-md-4">

                        <label for="agencia">
                            Agência
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="agencia"
                            name="agencia"
                            maxlength="30">

                    </div>

                    <!-- Dígito Agência -->
                    <div class="form-group col-md-2">

                        <label for="digito_agencia">
                            Dígito
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="digito_agencia"
                            name="digito_agencia"
                            maxlength="5">

                    </div>

                    <!-- Conta -->
                    <div class="form-group col-md-4">

                        <label for="conta">
                            Número da Conta *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="conta"
                            name="conta"
                            maxlength="50"
                            required>

                    </div>

                    <!-- Dígito Conta -->
                    <div class="form-group col-md-2">

                        <label for="digito_conta">
                            Dígito
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="digito_conta"
                            name="digito_conta"
                            maxlength="5">

                    </div>

                    <!-- IBAN -->
                    <div class="form-group col-md-6">

                        <label for="iban">
                            IBAN
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="iban"
                            name="iban"
                            maxlength="50">

                    </div>

                    <!-- SWIFT -->
                    <div class="form-group col-md-6">

                        <label for="swift">
                            SWIFT/BIC
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="swift"
                            name="swift"
                            maxlength="30">

                    </div>

                    <!-- Titular -->
                    <div class="form-group col-md-6">

                        <label for="titular">
                            Titular da Conta *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="titular"
                            name="titular"
                            maxlength="150"
                            required>

                    </div>

                    <!-- Documento -->
                    <div class="form-group col-md-6">

                        <label for="documento_titular">
                            Documento do Titular
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="documento_titular"
                            name="documento_titular"
                            maxlength="30">

                    </div>

                    <!-- Tipo -->
                    <div class="form-group col-md-4">

                        <label for="tipo_conta">
                            Tipo de Conta *
                        </label>

                        <select
                            class="form-control"
                            id="tipo_conta"
                            name="tipo_conta"
                            required>

                            <option value="Corrente">
                                Corrente
                            </option>

                            <option value="Poupança">
                                Poupança
                            </option>

                            <option value="Salário">
                                Salário
                            </option>

                            <option value="Empresarial">
                                Empresarial
                            </option>

                        </select>

                    </div>

                    <!-- Moeda -->
                    <div class="form-group col-md-2">

                        <label for="moeda">
                            Moeda *
                        </label>

                        <input
                            type="text"
                            class="form-control text-uppercase"
                            id="moeda"
                            name="moeda"
                            value="MZN"
                            maxlength="3"
                            required>

                    </div>

                    <!-- Saldo Inicial -->
                    <div class="form-group col-md-6">

                        <label for="saldo_inicial">
                            Saldo Inicial
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="saldo_inicial"
                            name="saldo_inicial"
                            min="0"
                            step="0.01"
                            value="0.00">

                        <small class="form-text text-muted">
                            Valor existente na conta no momento do cadastro.
                        </small>

                    </div>

                    <!-- Observações -->
                    <div class="form-group col-md-12">

                        <label for="observacoes">
                            Observações
                        </label>

                        <textarea
                            class="form-control"
                            id="observacoes"
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
                    id="btnGuardarBanco">

                    <i class="fa fa-save"></i>
                    Guardar

                </button>

            </div>

        </form>

    </div>

</div>