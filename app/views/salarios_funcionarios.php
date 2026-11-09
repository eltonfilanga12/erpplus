<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">Salários dos Funcionários</h3>

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
                            Salários dos Funcionários
                        </li>
                    </ul>
                </div>

                <div class="col-auto">

                    <button
                        id="btnNovoSalario"
                        class="btn btn-primary"
                        data-toggle="modal"
                        data-target="#modalSalarioFuncionario">

                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Novo Salário
                        </span>

                    </button>

                </div>

            </div>
        </div>
        <!-- /Page Header -->


        <!-- Tabela -->
        <div class="row">

            <div class="col-md-12">

                <div class="card">

                    <div class="card-body">

                        <div class="table-responsive">

                            <table
                                id="salariosFuncionariosTable"
                                class="table table-striped custom-table mb-0">

                                <thead>

                                    <tr>

                                        <th width="50">
                                            #
                                        </th>

                                        <th>
                                            Funcionário
                                        </th>

                                        <th>
                                            Contrato
                                        </th>

                                        <th class="text-right">
                                            Salário Base
                                        </th>

                                        <th class="text-right">
                                            Salário Bruto
                                        </th>

                                        <th class="text-right">
                                            Salário Líquido
                                        </th>

                                        <th>
                                            Início
                                        </th>

                                        <th>
                                            Fim
                                        </th>

                                        <th>
                                            Estado
                                        </th>

                                        <th
                                            width="140"
                                            class="text-center">

                                            Ações

                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <!-- DataTables -->

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>
        <!-- /Tabela -->

        <!-- Modal Salário -->
        <!-- Modal Salário -->
        <div
            class="modal fade"
            id="modalSalarioFuncionario"
            tabindex="-1"
            role="dialog"
            aria-labelledby="tituloModalSalario"
            aria-hidden="true">

            <div
                class="modal-dialog modal-xl modal-dialog-scrollable"
                role="document">

                <form
                    id="formSalarioFuncionario"
                    class="modal-content"
                    autocomplete="off">

                    <!-- Cabeçalho -->
                    <div class="modal-header">

                        <h4
                            class="modal-title"
                            id="tituloModalSalario">

                            Novo Salário

                        </h4>

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

                    <input
                        type="hidden"
                        id="id"
                        name="id">

                    <!-- Corpo com scroll -->
                    <div class="modal-body">

                        <!-- Navegação -->
                        <ul
                            class="nav nav-tabs"
                            id="salarioTabs"
                            role="tablist">

                            <li class="nav-item">

                                <a
                                    class="nav-link active"
                                    id="dados-tab"
                                    data-toggle="tab"
                                    href="#dadosGerais"
                                    role="tab">

                                    <i class="fa fa-user mr-1"></i>

                                    Dados Gerais

                                </a>

                            </li>

                            <li class="nav-item">

                                <a
                                    class="nav-link"
                                    id="rubricas-tab"
                                    data-toggle="tab"
                                    href="#rubricas"
                                    role="tab">

                                    <i class="fa fa-list mr-1"></i>

                                    Rubricas

                                </a>

                            </li>

                            <li class="nav-item">

                                <a
                                    class="nav-link"
                                    id="resumo-tab"
                                    data-toggle="tab"
                                    href="#resumo"
                                    role="tab">

                                    <i class="fa fa-calculator mr-1"></i>

                                    Resumo

                                </a>

                            </li>

                            <li class="nav-item">

                                <a
                                    class="nav-link"
                                    id="historico-tab"
                                    data-toggle="tab"
                                    href="#historico"
                                    role="tab">

                                    <i class="fa fa-history mr-1"></i>

                                    Histórico

                                </a>

                            </li>

                        </ul>

                        <!-- Conteúdo -->
                        <div class="tab-content pt-4">

                            <!-- Dados Gerais -->
                            <div
                                class="tab-pane fade show active"
                                id="dadosGerais"
                                role="tabpanel">

                                <div class="row">

                                    <!-- Funcionário -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>
                                                Funcionário
                                                <span class="text-danger">*</span>
                                            </label>

                                            <select
                                                id="funcionario_id"
                                                name="funcionario_id"
                                                class="form-control">

                                                <option value="">
                                                    Selecione...
                                                </option>

                                            </select>
                                        </div>
                                    </div>

                                    <!-- Contrato -->
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Contrato</label>

                                            <select
                                                id="contrato_id"
                                                name="contrato_id"
                                                class="form-control">

                                                <option value="">
                                                    Selecione...
                                                </option>

                                            </select>
                                        </div>
                                    </div>

                                    <!-- Moeda -->
                                    <div class="col-md-3">
                                        <div class="form-group">

                                            <label>Moeda</label>

                                            <input
                                                type="text"
                                                id="moeda"
                                                name="moeda"
                                                class="form-control"
                                                maxlength="3"
                                                value="MZN">

                                        </div>
                                    </div>

                                    <!-- Salário Base -->
                                    <div class="col-md-3">

                                        <div class="form-group">

                                            <label>
                                                Salário Base
                                                <span class="text-danger">*</span>
                                            </label>

                                            <input
                                                type="number"
                                                id="salario_base"
                                                name="salario_base"
                                                class="form-control"
                                                step="0.01"
                                                min="0">

                                        </div>

                                    </div>

                                    <!-- Periodicidade -->
                                    <div class="col-md-3">

                                        <div class="form-group">

                                            <label>Periodicidade</label>

                                            <select
                                                id="periodicidade"
                                                name="periodicidade"
                                                class="form-control">

                                                <option value="MENSAL">
                                                    Mensal
                                                </option>

                                                <option value="QUINZENAL">
                                                    Quinzenal
                                                </option>

                                                <option value="SEMANAL">
                                                    Semanal
                                                </option>

                                                <option value="DIARIO">
                                                    Diário
                                                </option>

                                                <option value="HORARIO">
                                                    Horário
                                                </option>

                                            </select>

                                        </div>

                                    </div>

                                    <!-- Horas -->
                                    <div class="col-md-3">

                                        <div class="form-group">

                                            <label>Horas Mensais</label>

                                            <input
                                                type="number"
                                                id="horas_mensais"
                                                name="horas_mensais"
                                                class="form-control"
                                                min="0">

                                        </div>

                                    </div>

                                    <!-- Dias -->
                                    <div class="col-md-3">

                                        <div class="form-group">

                                            <label>Dias Mensais</label>

                                            <input
                                                type="number"
                                                id="dias_mensais"
                                                name="dias_mensais"
                                                class="form-control"
                                                min="0">

                                        </div>

                                    </div>

                                    <!-- Data Início -->
                                    <div class="col-md-3">

                                        <div class="form-group">

                                            <label>
                                                Data Início
                                                <span class="text-danger">*</span>
                                            </label>

                                            <input
                                                type="date"
                                                id="data_inicio"
                                                name="data_inicio"
                                                class="form-control">

                                        </div>

                                    </div>

                                    <!-- Data Fim -->
                                    <div class="col-md-3">

                                        <div class="form-group">

                                            <label>Data Fim</label>

                                            <input
                                                type="date"
                                                id="data_fim"
                                                name="data_fim"
                                                class="form-control">

                                        </div>

                                    </div>

                                    <!-- Motivo -->
                                    <div class="col-md-3">

                                        <div class="form-group">

                                            <label>Motivo da Alteração</label>

                                            <input
                                                type="text"
                                                id="motivo_alteracao"
                                                name="motivo_alteracao"
                                                class="form-control"
                                                maxlength="255">

                                        </div>

                                    </div>

                                    <!-- Observações -->
                                    <div class="col-md-12">

                                        <div class="form-group">

                                            <label>Observações</label>

                                            <textarea
                                                id="observacoes"
                                                name="observacoes"
                                                rows="4"
                                                class="form-control"></textarea>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <!-- Rubricas -->
                            <div
                                class="tab-pane fade"
                                id="rubricas"
                                role="tabpanel">

                                <div class="row">

                                    <div class="col-md-12">

                                        <div class="d-flex justify-content-between align-items-center mb-3">

                                            <h5 class="mb-0">
                                                Rubricas Salariais
                                            </h5>

                                            <button
                                                type="button"
                                                id="btnAdicionarRubrica"
                                                class="btn btn-primary btn-sm">

                                                <i class="fa fa-plus"></i>

                                                Adicionar Rubrica

                                            </button>

                                        </div>

                                        <div class="table-responsive">

                                            <table
                                                class="table table-bordered table-hover"
                                                id="tabelaRubricas">

                                                <thead class="thead-light">

                                                    <tr>

                                                        <th width="25%">
                                                            Rubrica
                                                        </th>

                                                        <th width="12%" class="text-right">
                                                            Valor
                                                        </th>

                                                        <th width="10%" class="text-right">
                                                            %
                                                        </th>

                                                        <th width="10%" class="text-right">
                                                            Quantidade
                                                        </th>

                                                        <th width="10%">
                                                            Início
                                                        </th>

                                                        <th width="10%">
                                                            Fim
                                                        </th>

                                                        <th>
                                                            Observações
                                                        </th>

                                                        <th
                                                            width="60"
                                                            class="text-center">

                                                            Ações

                                                        </th>

                                                    </tr>

                                                </thead>

                                                <tbody id="tbodyRubricas">

                                                    <tr id="linhaSemRubricas">

                                                        <td
                                                            colspan="8"
                                                            class="text-center text-muted">

                                                            Nenhuma rubrica adicionada.

                                                        </td>

                                                    </tr>

                                                </tbody>

                                            </table>

                                            <table class="d-none">

                                                <tbody>

                                                    <tr id="templateRubrica">

                                                        <td>

                                                            <select
                                                                class="form-control rubrica_id">

                                                                <option value="">
                                                                    Selecione...
                                                                </option>

                                                            </select>

                                                        </td>

                                                        <td>

                                                            <input
                                                                type="number"
                                                                class="form-control text-right valor"
                                                                min="0"
                                                                step="0.01">

                                                        </td>

                                                        <td>

                                                            <input
                                                                type="number"
                                                                class="form-control text-right percentagem"
                                                                min="0"
                                                                step="0.01">

                                                        </td>

                                                        <td>

                                                            <input
                                                                type="number"
                                                                class="form-control text-right quantidade"
                                                                min="0"
                                                                step="0.01">

                                                        </td>

                                                        <td>

                                                            <input
                                                                type="date"
                                                                class="form-control data_inicio">

                                                        </td>

                                                        <td>

                                                            <input
                                                                type="date"
                                                                class="form-control data_fim">

                                                        </td>

                                                        <td>

                                                            <input
                                                                type="text"
                                                                class="form-control observacoes"
                                                                maxlength="255">

                                                        </td>

                                                        <td class="text-center">

                                                            <button
                                                                type="button"
                                                                class="btn btn-danger btn-sm removerRubrica">

                                                                <i class="fa fa-trash"></i>

                                                            </button>

                                                        </td>

                                                    </tr>

                                                </tbody>

                                            </table>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <!-- Resumo -->
                            <div
                                class="tab-pane fade"
                                id="resumo"
                                role="tabpanel">

                                <div class="row">

                                    <!-- Salário Base -->
                                    <div class="col-md-4">

                                        <div class="card">

                                            <div class="card-body text-center">

                                                <h6 class="text-muted">
                                                    Salário Base
                                                </h6>

                                                <h3
                                                    id="resumoSalarioBase"
                                                    class="mb-0">

                                                    0,00 MZN

                                                </h3>

                                            </div>

                                        </div>

                                    </div>

                                    <!-- Proventos -->
                                    <div class="col-md-4">

                                        <div class="card">

                                            <div class="card-body text-center">

                                                <h6 class="text-muted">
                                                    Total de Proventos
                                                </h6>

                                                <h3
                                                    id="resumoTotalProventos"
                                                    class="mb-0">

                                                    0,00 MZN

                                                </h3>

                                            </div>

                                        </div>

                                    </div>

                                    <!-- Descontos -->
                                    <div class="col-md-4">

                                        <div class="card">

                                            <div class="card-body text-center">

                                                <h6 class="text-muted">
                                                    Total de Descontos
                                                </h6>

                                                <h3
                                                    id="resumoTotalDescontos"
                                                    class="mb-0">

                                                    0,00 MZN

                                                </h3>

                                            </div>

                                        </div>

                                    </div>

                                    <!-- Salário Bruto -->
                                    <div class="col-md-6">

                                        <div class="card">

                                            <div class="card-body text-center">

                                                <h6 class="text-muted">
                                                    Salário Bruto
                                                </h6>

                                                <h2
                                                    id="resumoSalarioBruto"
                                                    class="mb-0">

                                                    0,00 MZN

                                                </h2>

                                            </div>

                                        </div>

                                    </div>

                                    <!-- Salário Líquido -->
                                    <div class="col-md-6">

                                        <div class="card">

                                            <div class="card-body text-center">

                                                <h6 class="text-muted">
                                                    Salário Líquido
                                                </h6>

                                                <h2
                                                    id="resumoSalarioLiquido"
                                                    class="mb-0">

                                                    0,00 MZN

                                                </h2>

                                            </div>

                                        </div>

                                    </div>

                                    <!-- Detalhes -->
                                    <div class="col-md-12">

                                        <div class="card">

                                            <div class="card-header">

                                                <h5 class="card-title mb-0">
                                                    Detalhes das Rubricas
                                                </h5>

                                            </div>

                                            <div class="card-body">

                                                <div class="table-responsive">

                                                    <table
                                                        id="tabelaResumoRubricas"
                                                        class="table table-bordered mb-0">

                                                        <thead class="thead-light">

                                                            <tr>

                                                                <th>
                                                                    Rubrica
                                                                </th>

                                                                <th>
                                                                    Tipo
                                                                </th>

                                                                <th class="text-right">
                                                                    Valor
                                                                </th>

                                                            </tr>

                                                        </thead>

                                                        <tbody id="tbodyResumoRubricas">

                                                            <tr id="linhaResumoSemRubricas">

                                                                <td
                                                                    colspan="3"
                                                                    class="text-center text-muted">

                                                                    Nenhuma rubrica adicionada.

                                                                </td>

                                                            </tr>

                                                        </tbody>

                                                    </table>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <!-- Histórico -->
                            <div
                                class="tab-pane fade"
                                id="historico"
                                role="tabpanel">

                                <div class="alert alert-info mb-0">

                                    <i class="fa fa-info-circle"></i>

                                    O histórico das alterações salariais será
                                    disponibilizado numa versão futura.

                                </div>

                            </div>

                        </div>

                    </div>
                    <!-- /modal-body -->

                    <!-- Rodapé -->
                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">

                            <i class="fa fa-times"></i>
                            Fechar

                        </button>

                        <button
                            type="submit"
                            id="btnGuardarSalario"
                            class="btn btn-primary">

                            <i class="fa fa-save"></i>
                            Guardar

                        </button>

                    </div>

                </form>

            </div>

        </div>
        <!-- /Modal Salário -->
    </div>

</div>

<style>
    /* Impede que o modal ultrapasse a altura da janela */
    #modalSalarioFuncionario .modal-dialog {
        max-height: calc(100vh - 30px);
        margin-top: 15px;
        margin-bottom: 15px;
    }

    /* O formulário passa a funcionar como modal-content */
    #modalSalarioFuncionario .modal-content {
        max-height: calc(100vh - 30px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Cabeçalho fixo */
    #modalSalarioFuncionario .modal-header {
        flex: 0 0 auto;
    }

    /* Área central com scroll */
    #modalSalarioFuncionario .modal-body {
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: hidden;
        min-height: 0;
        padding: 20px;
    }

    /* Rodapé fixo */
    #modalSalarioFuncionario .modal-footer {
        flex: 0 0 auto;
    }

    /* Evita que as abas saiam para fora em ecrãs pequenos */
    #modalSalarioFuncionario .nav-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
    }

    #modalSalarioFuncionario .nav-tabs .nav-item {
        flex: 0 0 auto;
    }

    /* Tabela das rubricas */
    #modalSalarioFuncionario #tabelaRubricas {
        min-width: 1100px;
    }

    /* Mantém os inputs utilizáveis dentro da tabela */
    #modalSalarioFuncionario #tabelaRubricas .form-control {
        min-width: 100px;
    }

    #modalSalarioFuncionario #tabelaRubricas .rubrica_id {
        min-width: 200px;
    }

    /* Ajustes para telemóveis */
    @media (max-width: 767.98px) {
        #modalSalarioFuncionario .modal-dialog {
            max-height: calc(100vh - 16px);
            margin: 8px;
        }

        #modalSalarioFuncionario .modal-content {
            max-height: calc(100vh - 16px);
        }

        #modalSalarioFuncionario .modal-body {
            padding: 15px;
        }

        #modalSalarioFuncionario .modal-footer {
            flex-wrap: nowrap;
        }

        #modalSalarioFuncionario .modal-footer .btn {
            flex: 1;
        }
    }
</style>