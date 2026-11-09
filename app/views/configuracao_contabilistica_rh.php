<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Configuração Contabilística do RH
                    </h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item">
                            Recursos Humanos
                        </li>

                        <li class="breadcrumb-item active">
                            Configuração Contabilística
                        </li>
                    </ul>
                </div>

            </div>
        </div>

        <div
            id="configRhErroGeral"
            class="alert alert-danger d-none"></div>

        <div
            id="configRhSucessoGeral"
            class="alert alert-success d-none"></div>

        <form
            id="configRhForm"
            autocomplete="off">
            <input
                type="hidden"
                id="configRhId"
                name="id">

            <input
                type="hidden"
                name="acao"
                value="guardar">

            <div class="row">

                <!-- Configuração principal -->
                <div class="col-lg-8">

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">
                                <i class="fa fa-book"></i>
                                Contas e diário contabilístico
                            </h4>
                        </div>

                        <div class="card-body">

                            <div class="form-row">

                                <div class="form-group col-md-6">
                                    <label for="configRhDiarioId">
                                        Diário contabilístico
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="configRhDiarioId"
                                        name="diario_contabilistico_id"
                                        required>
                                        <option value="">
                                            A carregar diários...
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="configRhStatusId">
                                        Estado
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="configRhStatusId"
                                        name="status_id"
                                        required>
                                        <option value="">
                                            A carregar estados...
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="configRhContaSalariosPagarId">
                                        Conta de salários a pagar
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control config-rh-conta"
                                        id="configRhContaSalariosPagarId"
                                        name="conta_salarios_pagar_id"
                                        required>
                                        <option value="">
                                            A carregar contas...
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="configRhContaSalarioBaseId">
                                        Conta de salário base
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control config-rh-conta"
                                        id="configRhContaSalarioBaseId"
                                        name="conta_salario_base_id"
                                        required>

                                        <option value="">
                                            A carregar contas...
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="configRhContaInssId">
                                        Conta de INSS a pagar
                                    </label>

                                    <select
                                        class="form-control config-rh-conta"
                                        id="configRhContaInssId"
                                        name="conta_inss_pagar_id">
                                        <option value="">
                                            A carregar contas...
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="configRhContaIrpsId">
                                        Conta de IRPS a pagar
                                    </label>

                                    <select
                                        class="form-control config-rh-conta"
                                        id="configRhContaIrpsId"
                                        name="conta_irps_pagar_id">
                                        <option value="">
                                            A carregar contas...
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="configRhContaOutrosDescontosId">
                                        Conta de outros descontos
                                    </label>

                                    <select
                                        class="form-control config-rh-conta"
                                        id="configRhContaOutrosDescontosId"
                                        name="conta_outros_descontos_id">
                                        <option value="">
                                            A carregar contas...
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="configRhContaEncargosId">
                                        Conta de encargos patronais
                                    </label>

                                    <select
                                        class="form-control config-rh-conta"
                                        id="configRhContaEncargosId"
                                        name="conta_encargos_patronais_id">
                                        <option value="">
                                            A carregar contas...
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="configRhContaBancoId">
                                        Conta bancária padrão
                                    </label>

                                    <select
                                        class="form-control config-rh-conta"
                                        id="configRhContaBancoId"
                                        name="conta_banco_padrao_id">
                                        <option value="">
                                            A carregar contas...
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="configRhContaCaixaId">
                                        Conta de caixa padrão
                                    </label>

                                    <select
                                        class="form-control config-rh-conta"
                                        id="configRhContaCaixaId"
                                        name="conta_caixa_padrao_id">
                                        <option value="">
                                            A carregar contas...
                                        </option>
                                    </select>
                                </div>

                            </div>

                        </div>
                    </div>

                </div>

                <!-- Opções -->
                <div class="col-lg-4">

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">
                                <i class="fa fa-cogs"></i>
                                Opções de integração
                            </h4>
                        </div>

                        <div class="card-body">

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input
                                        type="checkbox"
                                        class="custom-control-input"
                                        id="configRhAgruparFilial"
                                        name="agrupar_por_filial"
                                        value="1">

                                    <label
                                        class="custom-control-label"
                                        for="configRhAgruparFilial">
                                        Agrupar por filial
                                    </label>
                                </div>

                                <small class="form-text text-muted">
                                    Cria linhas separadas para cada filial.
                                </small>
                            </div>

                            <hr>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input
                                        type="checkbox"
                                        class="custom-control-input"
                                        id="configRhAgruparCentroCusto"
                                        name="agrupar_por_centro_custo"
                                        value="1">

                                    <label
                                        class="custom-control-label"
                                        for="configRhAgruparCentroCusto">
                                        Agrupar por centro de custo
                                    </label>
                                </div>

                                <small class="form-text text-muted">
                                    Distribui os gastos salariais pelos centros de custo.
                                </small>
                            </div>

                            <hr>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input
                                        type="checkbox"
                                        class="custom-control-input"
                                        id="configRhContabilizarRubrica"
                                        name="contabilizar_por_rubrica"
                                        value="1"
                                        checked>

                                    <label
                                        class="custom-control-label"
                                        for="configRhContabilizarRubrica">
                                        Contabilizar por rubrica
                                    </label>
                                </div>

                                <small class="form-text text-muted">
                                    Utiliza o mapeamento contabilístico individual de cada rubrica.
                                </small>
                            </div>

                            <hr>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input
                                        type="checkbox"
                                        class="custom-control-input"
                                        id="configRhGerarAutomaticamente"
                                        name="gerar_lancamento_automaticamente"
                                        value="1">

                                    <label
                                        class="custom-control-label"
                                        for="configRhGerarAutomaticamente">
                                        Gerar lançamento automaticamente
                                    </label>
                                </div>

                                <small class="form-text text-muted">
                                    O lançamento será gerado quando a folha for aprovada.
                                </small>
                            </div>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">

                            <button
                                type="submit"
                                class="btn btn-primary btn-block"
                                id="configRhBtnGuardar">
                                <i class="fa fa-save"></i>
                                Guardar configuração
                            </button>

                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-block"
                                id="configRhBtnRecarregar">
                                <i class="fa fa-refresh"></i>
                                Recarregar
                            </button>

                        </div>
                    </div>

                </div>

            </div>
        </form>

    </div>
</div>