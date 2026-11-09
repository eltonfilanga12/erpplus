<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">

                <div class="col">
                    <h3 class="page-title">
                        Recibos de Salário
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
                            Recibos de Salário
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-toggle="modal"
                        data-target="#modalGerarRecibos"
                    >
                        <i class="fa fa-plus"></i>

                        <span class="d-none d-sm-inline">
                            Gerar Recibos
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Resumo -->
        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="dash-widget-header">
                            <span class="dash-widget-icon bg-primary">
                                <i class="fa fa-file-text"></i>
                            </span>

                            <div class="dash-count">
                                <h3 id="resumoRecibosTotal">
                                    0
                                </h3>
                            </div>
                        </div>

                        <div class="dash-widget-info">
                            <h6 class="text-muted">
                                Total de Recibos
                            </h6>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="dash-widget-header">
                            <span class="dash-widget-icon bg-info">
                                <i class="fa fa-check"></i>
                            </span>

                            <div class="dash-count">
                                <h3 id="resumoRecibosEmitidos">
                                    0
                                </h3>
                            </div>
                        </div>

                        <div class="dash-widget-info">
                            <h6 class="text-muted">
                                Emitidos
                            </h6>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="dash-widget-header">
                            <span class="dash-widget-icon bg-success">
                                <i class="fa fa-check-circle"></i>
                            </span>

                            <div class="dash-count">
                                <h3 id="resumoRecibosEntregues">
                                    0
                                </h3>
                            </div>
                        </div>

                        <div class="dash-widget-info">
                            <h6 class="text-muted">
                                Entregues
                            </h6>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="dash-widget-header">
                            <span class="dash-widget-icon bg-danger">
                                <i class="fa fa-times-circle"></i>
                            </span>

                            <div class="dash-count">
                                <h3 id="resumoRecibosCancelados">
                                    0
                                </h3>
                            </div>
                        </div>

                        <div class="dash-widget-info">
                            <h6 class="text-muted">
                                Cancelados
                            </h6>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <!-- Filtros -->
        <div class="card">
            <div class="card-body">

                <form id="formFiltrosRecibos">

                    <div class="row align-items-end">

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>
                                    Período Salarial
                                </label>

                                <select
                                    id="filtroPeriodoRecibo"
                                    class="form-control"
                                >
                                    <option value="">
                                        Todos os períodos
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>
                                    Folha Salarial
                                </label>

                                <select
                                    id="filtroFolhaRecibo"
                                    class="form-control"
                                >
                                    <option value="">
                                        Todas as folhas
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>
                                    Estado
                                </label>

                                <select
                                    id="filtroEstadoRecibo"
                                    class="form-control"
                                >
                                    <option value="">
                                        Todos os estados
                                    </option>

                                    <option value="Emitido">
                                        Emitido
                                    </option>

                                    <option value="Enviado">
                                        Enviado
                                    </option>

                                    <option value="Entregue">
                                        Entregue
                                    </option>

                                    <option value="Cancelado">
                                        Cancelado
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>
                                    Pesquisar
                                </label>

                                <input
                                    type="text"
                                    id="filtroPesquisaRecibo"
                                    class="form-control"
                                    placeholder="Código ou funcionário"
                                >
                            </div>
                        </div>

                        <div class="col-md-1">
                            <div class="form-group">
                                <button
                                    type="submit"
                                    class="btn btn-primary btn-block"
                                    title="Pesquisar"
                                >
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>

                    </div>

                </form>

            </div>
        </div>

        <!-- Tabela -->
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="recibosSalariosTable"
                        class="table table-striped custom-table mb-0"
                    >
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Funcionário</th>
                                <th>Período</th>
                                <th>Folha</th>
                                <th>Salário Bruto</th>
                                <th>Descontos</th>
                                <th>Salário Líquido</th>
                                <th>Estado</th>
                                <th>Emissão</th>
                                <th class="text-right">
                                    Ações
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                        </tbody>
                    </table>

                </div>

            </div>
        </div>

    </div>
</div>

<!-- Modal Gerar Recibos -->
<div
    id="modalGerarRecibos"
    class="modal custom-modal fade"
    role="dialog"
>
    <div
        class="modal-dialog modal-dialog-centered modal-lg"
        role="document"
    >
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Gerar Recibos de Salário
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

                <form id="formGerarRecibos">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $_SESSION['csrf_token'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <div class="row">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    Período Salarial
                                    <span class="text-danger">
                                        *
                                    </span>
                                </label>

                                <select
                                    id="periodoReciboId"
                                    name="periodo_id"
                                    class="form-control"
                                    required
                                >
                                    <option value="">
                                        Selecione o período
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>
                                    Folha Salarial
                                    <span class="text-danger">
                                        *
                                    </span>
                                </label>

                                <select
                                    id="folhaReciboId"
                                    name="folha_salarial_id"
                                    class="form-control"
                                    required
                                >
                                    <option value="">
                                        Selecione a folha
                                    </option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="form-group">
                        <label>
                            Tipo de geração
                        </label>

                        <div class="form-check mb-2">
                            <input
                                type="radio"
                                id="gerarTodosFuncionarios"
                                name="tipo_geracao"
                                value="todos"
                                class="form-check-input"
                                checked
                            >

                            <label
                                class="form-check-label"
                                for="gerarTodosFuncionarios"
                            >
                                Gerar recibos para todos os funcionários
                            </label>
                        </div>

                        <div class="form-check">
                            <input
                                type="radio"
                                id="gerarFuncionarioIndividual"
                                name="tipo_geracao"
                                value="individual"
                                class="form-check-input"
                            >

                            <label
                                class="form-check-label"
                                for="gerarFuncionarioIndividual"
                            >
                                Gerar recibo para um funcionário
                            </label>
                        </div>
                    </div>

                    <div
                        id="grupoFuncionarioRecibo"
                        class="form-group d-none"
                    >
                        <label>
                            Funcionário
                            <span class="text-danger">
                                *
                            </span>
                        </label>

                        <select
                            id="funcionarioReciboId"
                            name="processamento_id"
                            class="form-control"
                        >
                            <option value="">
                                Selecione o funcionário
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            Observações
                        </label>

                        <textarea
                            name="observacoes"
                            class="form-control"
                            rows="3"
                            placeholder="Observações opcionais"
                        ></textarea>
                    </div>

                    <div class="submit-section">
                        <button
                            type="submit"
                            class="btn btn-primary submit-btn"
                        >
                            <i class="fa fa-file-text"></i>
                            Gerar Recibos
                        </button>

                        <button
                            type="button"
                            class="btn btn-secondary submit-btn"
                            data-dismiss="modal"
                        >
                            Cancelar
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</div>

<!-- Modal Detalhes do Recibo -->
<div
    id="modalDetalhesRecibo"
    class="modal custom-modal fade"
    role="dialog"
>
    <div
        class="modal-dialog modal-dialog-centered modal-xl"
        role="document"
    >
        <div class="modal-content">

            <div class="modal-header">
                <h5
                    id="detalheReciboTitulo"
                    class="modal-title"
                >
                    Detalhes do Recibo
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

                <div class="row">

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">

                                <h5 class="card-title">
                                    Funcionário
                                </h5>

                                <p>
                                    <strong>Número:</strong>
                                    <span id="detalheNumeroFuncionario">
                                        -
                                    </span>
                                </p>

                                <p>
                                    <strong>Nome:</strong>
                                    <span id="detalheFuncionarioNome">
                                        -
                                    </span>
                                </p>

                                <p>
                                    <strong>Cargo:</strong>
                                    <span id="detalheCargoNome">
                                        -
                                    </span>
                                </p>

                                <p>
                                    <strong>Departamento:</strong>
                                    <span id="detalheDepartamentoNome">
                                        -
                                    </span>
                                </p>

                                <p>
                                    <strong>Filial:</strong>
                                    <span id="detalheFilialNome">
                                        -
                                    </span>
                                </p>

                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">

                                <h5 class="card-title">
                                    Recibo
                                </h5>

                                <p>
                                    <strong>Código:</strong>
                                    <span id="detalheReciboCodigo">
                                        -
                                    </span>
                                </p>

                                <p>
                                    <strong>Período:</strong>
                                    <span id="detalhePeriodoRecibo">
                                        -
                                    </span>
                                </p>

                                <p>
                                    <strong>Folha:</strong>
                                    <span id="detalheFolhaRecibo">
                                        -
                                    </span>
                                </p>

                                <p>
                                    <strong>Estado:</strong>
                                    <span id="detalheEstadoRecibo">
                                        -
                                    </span>
                                </p>

                                <p>
                                    <strong>Emitido em:</strong>
                                    <span id="detalheEmitidoEm">
                                        -
                                    </span>
                                </p>

                            </div>
                        </div>
                    </div>

                </div>

                <div class="row">

                    <!-- Proventos -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">

                                <h5 class="card-title">
                                    Proventos
                                </h5>

                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <td>Salário Base</td>
                                                <td
                                                    id="detalheSalarioBase"
                                                    class="text-right"
                                                >
                                                    0,00 MZN
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>Subsídios</td>
                                                <td
                                                    id="detalheTotalSubsidios"
                                                    class="text-right"
                                                >
                                                    0,00 MZN
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>Horas Extras</td>
                                                <td
                                                    id="detalheHorasExtras"
                                                    class="text-right"
                                                >
                                                    0,00 MZN
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>
                                                    Outros Proventos
                                                </td>

                                                <td
                                                    id="detalheOutrosProventos"
                                                    class="text-right"
                                                >
                                                    0,00 MZN
                                                </td>
                                            </tr>

                                            <tr class="font-weight-bold">
                                                <td>
                                                    Total de Proventos
                                                </td>

                                                <td
                                                    id="detalheTotalProventos"
                                                    class="text-right"
                                                >
                                                    0,00 MZN
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Descontos -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">

                                <h5 class="card-title">
                                    Descontos
                                </h5>

                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <td>Faltas</td>
                                                <td
                                                    id="detalheTotalFaltas"
                                                    class="text-right"
                                                >
                                                    0,00 MZN
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>Atrasos</td>
                                                <td
                                                    id="detalheTotalAtrasos"
                                                    class="text-right"
                                                >
                                                    0,00 MZN
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>INSS</td>
                                                <td
                                                    id="detalheTotalInss"
                                                    class="text-right"
                                                >
                                                    0,00 MZN
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>IRPS</td>
                                                <td
                                                    id="detalheTotalIrps"
                                                    class="text-right"
                                                >
                                                    0,00 MZN
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>
                                                    Outros Descontos
                                                </td>

                                                <td
                                                    id="detalheOutrosDescontos"
                                                    class="text-right"
                                                >
                                                    0,00 MZN
                                                </td>
                                            </tr>

                                            <tr class="font-weight-bold">
                                                <td>
                                                    Total de Descontos
                                                </td>

                                                <td
                                                    id="detalheTotalDescontos"
                                                    class="text-right"
                                                >
                                                    0,00 MZN
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

                <!-- Totais -->
                <div class="card">
                    <div class="card-body">

                        <div class="row text-center">

                            <div class="col-md-4">
                                <h6>Salário Bruto</h6>

                                <h4 id="detalheSalarioBruto">
                                    0,00 MZN
                                </h4>
                            </div>

                            <div class="col-md-4">
                                <h6>Total de Descontos</h6>

                                <h4
                                    id="detalheResumoDescontos"
                                    class="text-danger"
                                >
                                    0,00 MZN
                                </h4>
                            </div>

                            <div class="col-md-4">
                                <h6>Salário Líquido</h6>

                                <h4
                                    id="detalheSalarioLiquido"
                                    class="text-success"
                                >
                                    0,00 MZN
                                </h4>
                            </div>

                        </div>

                    </div>
                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    id="btnImprimirReciboDetalhe"
                    class="btn btn-primary"
                >
                    <i class="fa fa-print"></i>
                    Imprimir
                </button>

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-dismiss="modal"
                >
                    Fechar
                </button>

            </div>

        </div>
    </div>
</div>

<!-- Modal Cancelar Recibo -->
<div
    id="modalCancelarRecibo"
    class="modal custom-modal fade"
    role="dialog"
>
    <div
        class="modal-dialog modal-dialog-centered"
        role="document"
    >
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Cancelar Recibo
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

                <form id="formCancelarRecibo">

                    <input
                        type="hidden"
                        name="id"
                    >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $_SESSION['csrf_token'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <div class="form-group">
                        <label>
                            Motivo do cancelamento
                            <span class="text-danger">
                                *
                            </span>
                        </label>

                        <textarea
                            name="motivo"
                            class="form-control"
                            rows="4"
                            minlength="5"
                            required
                            placeholder="Informe o motivo do cancelamento"
                        ></textarea>
                    </div>

                    <div class="submit-section">
                        <button
                            type="submit"
                            class="btn btn-danger submit-btn"
                        >
                            <i class="fa fa-times"></i>
                            Cancelar Recibo
                        </button>

                        <button
                            type="button"
                            class="btn btn-secondary submit-btn"
                            data-dismiss="modal"
                        >
                            Voltar
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</div>