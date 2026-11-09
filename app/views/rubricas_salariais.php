<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Cabeçalho -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Rubricas Salariais</h3>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">Dashboard</a>
                        </li>

                        <li class="breadcrumb-item">
                            Recursos Humanos
                        </li>

                        <li class="breadcrumb-item active">
                            Rubricas Salariais
                        </li>
                    </ul>
                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovaRubrica">
                        <i class="fa fa-plus"></i>
                        <span class="d-none d-sm-inline">
                            Nova Rubrica
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Cards -->
        <div class="row">

            <div class="col-md-6 col-sm-6 col-lg-3">
                <div class="card dash-widget">
                    <div class="card-body">
                        <span class="dash-widget-icon">
                            <i class="fa fa-list"></i>
                        </span>

                        <div class="dash-widget-info">
                            <h3 id="resumoTotal">0</h3>
                            <span>Total de Rubricas</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-sm-6 col-lg-3">
                <div class="card dash-widget">
                    <div class="card-body">
                        <span class="dash-widget-icon">
                            <i class="fa fa-plus-circle"></i>
                        </span>

                        <div class="dash-widget-info">
                            <h3 id="resumoProventos">0</h3>
                            <span>Proventos</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-sm-6 col-lg-3">
                <div class="card dash-widget">
                    <div class="card-body">
                        <span class="dash-widget-icon">
                            <i class="fa fa-minus-circle"></i>
                        </span>

                        <div class="dash-widget-info">
                            <h3 id="resumoDescontos">0</h3>
                            <span>Descontos</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-sm-6 col-lg-3">
                <div class="card dash-widget">
                    <div class="card-body">
                        <span class="dash-widget-icon">
                            <i class="fa fa-check-circle"></i>
                        </span>

                        <div class="dash-widget-info">
                            <h3 id="resumoAtivas">0</h3>
                            <span>Rubricas Ativas</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Filtros -->
        <div class="card">
            <div class="card-body">

                <div class="row align-items-end">

                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filtroCodigo">Código</label>

                            <input
                                type="text"
                                id="filtroCodigo"
                                class="form-control"
                                placeholder="Código">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filtroNome">Nome</label>

                            <input
                                type="text"
                                id="filtroNome"
                                class="form-control"
                                placeholder="Nome da rubrica">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filtroTipo">Tipo</label>

                            <select
                                id="filtroTipo"
                                class="form-control">
                                <option value="">Todos</option>
                                <option value="PROVENTO">
                                    Provento
                                </option>

                                <option value="DESCONTO">
                                    Desconto
                                </option>

                                <option value="ENCARGO">
                                    Encargo da Empresa
                                </option>

                                <option value="INFORMATIVO">
                                    Informativo
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filtroCategoria">Categoria</label>

                            <select
                                id="filtroCategoria"
                                class="form-control">
                                <option value="">Todas</option>
                                <option value="SALARIO_BASE">Salário Base</option>
                                <option value="SUBSIDIO_ALIMENTACAO">Subsídio de Alimentação</option>
                                <option value="SUBSIDIO_TRANSPORTE">Subsídio de Transporte</option>
                                <option value="SUBSIDIO_COMUNICACAO">Subsídio de Comunicação</option>
                                <option value="SUBSIDIO_TURNO">Subsídio de Turno</option>
                                <option value="SUBSIDIO_RISCO">Subsídio de Risco</option>
                                <option value="SUBSIDIO_OUTRO">Outro Subsídio</option>
                                <option value="HORA_EXTRA">Hora Extra</option>
                                <option value="BONUS">Bónus</option>
                                <option value="PREMIO">Prémio</option>
                                <option value="COMISSAO">Comissão</option>
                                <option value="FALTA">Falta</option>
                                <option value="ATRASO">Atraso</option>
                                <option value="INSS">
                                    INSS - Trabalhador
                                </option>

                                <option value="INSS_PATRONAL">
                                    INSS - Entidade Empregadora
                                </option>

                                <option value="IRPS">
                                    IRPS
                                </option>
                                <option value="ADIANTAMENTO">Adiantamento</option>
                                <option value="EMPRESTIMO">Empréstimo</option>
                                <option value="PENSAO">Pensão</option>
                                <option value="OUTRO">Outro</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filtroEstado">Estado</label>

                            <select
                                id="filtroEstado"
                                class="form-control">
                                <option value="">Todos</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-12 text-right">
                        <button
                            type="button"
                            class="btn btn-primary"
                            id="btnFiltrar">
                            <i class="fa fa-search"></i>
                            Pesquisar
                        </button>

                        <button
                            type="button"
                            class="btn btn-secondary"
                            id="btnLimparFiltros">
                            <i class="fa fa-eraser"></i>
                            Limpar
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">
                    <table
                        id="rubricasTable"
                        class="table table-striped custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Tipo</th>
                                <th>Modo de Cálculo</th>
                                <th>Valor</th>
                                <th>Estado</th>
                                <th class="text-right">Acções</th>
                            </tr>
                        </thead>

                        <tbody></tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
</div>


<div
    class="modal fade"
    id="modalRubrica"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalRubricaLabel"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl modal-dialog-scrollable"
        role="document">

        <div class="modal-content">

            <form
                id="formRubrica"
                class="form-modal-scroll">

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="modalRubricaLabel">
                        Nova Rubrica Salarial
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

                    <input
                        type="hidden"
                        id="rubricaId"
                        name="id">

                    <input
                        type="hidden"
                        id="rubricaUuid"
                        name="uuid">

                    <!-- Dados gerais -->
                    <h5 class="mb-3">Dados Gerais</h5>

                    <div class="row">

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="codigo">
                                    Código
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="codigo"
                                    name="codigo"
                                    class="form-control"
                                    maxlength="30"
                                    required>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="nome">
                                    Nome
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    class="form-control"
                                    maxlength="120"
                                    required>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="tipo">
                                    Tipo
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    id="tipo"
                                    name="tipo"
                                    class="form-control"
                                    required>
                                    <option value="">Seleccione</option>
                                    <option value="PROVENTO">
                                        Provento
                                    </option>

                                    <option value="DESCONTO">
                                        Desconto
                                    </option>

                                    <option value="ENCARGO">
                                        Encargo da Empresa
                                    </option>

                                    <option value="INFORMATIVO">
                                        Informativo
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="ordem">Ordem</label>

                                <input
                                    type="number"
                                    id="ordem"
                                    name="ordem"
                                    class="form-control"
                                    min="0"
                                    max="65535"
                                    value="0">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="categoria">
                                    Categoria
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    id="categoria"
                                    name="categoria"
                                    class="form-control"
                                    required>
                                    <option value="">Seleccione</option>
                                    <option value="SALARIO_BASE">Salário Base</option>
                                    <option value="SUBSIDIO_ALIMENTACAO">Subsídio de Alimentação</option>
                                    <option value="SUBSIDIO_TRANSPORTE">Subsídio de Transporte</option>
                                    <option value="SUBSIDIO_COMUNICACAO">Subsídio de Comunicação</option>
                                    <option value="SUBSIDIO_TURNO">Subsídio de Turno</option>
                                    <option value="SUBSIDIO_RISCO">Subsídio de Risco</option>
                                    <option value="SUBSIDIO_OUTRO">Outro Subsídio</option>
                                    <option value="HORA_EXTRA">Hora Extra</option>
                                    <option value="BONUS">Bónus</option>
                                    <option value="PREMIO">Prémio</option>
                                    <option value="COMISSAO">Comissão</option>
                                    <option value="FALTA">Falta</option>
                                    <option value="ATRASO">Atraso</option>
                                    <option value="INSS">
                                        INSS - Trabalhador
                                    </option>

                                    <option value="INSS_PATRONAL">
                                        INSS - Entidade Empregadora
                                    </option>

                                    <option value="IRPS">
                                        IRPS
                                    </option>
                                    <option value="ADIANTAMENTO">Adiantamento</option>
                                    <option value="EMPRESTIMO">Empréstimo</option>
                                    <option value="PENSAO">Pensão</option>
                                    <option value="OUTRO">Outro</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="statusId">
                                    Estado
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    id="statusId"
                                    name="status_id"
                                    class="form-control"
                                    required>
                                    <option value="">Seleccione</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="descricao">Descrição</label>

                                <textarea
                                    id="descricao"
                                    name="descricao"
                                    class="form-control"
                                    rows="3"
                                    maxlength="500"></textarea>
                            </div>
                        </div>

                    </div>

                    <hr>

                    <!-- Configuração de cálculo -->
                    <h5 class="mb-3">Configuração de Cálculo</h5>

                    <div class="row">

                        <div class="col-md-4">
                            <div class="form-group">

                                <label for="modoCalculo">
                                    Modo de Cálculo
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    id="modoCalculo"
                                    name="modo_calculo"
                                    class="form-control"
                                    required>

                                    <option value="">Seleccione</option>
                                    <option value="VALOR_FIXO">Valor Fixo</option>
                                    <option value="PERCENTAGEM">Percentagem</option>
                                    <option value="QUANTIDADE_VALOR">
                                        Quantidade × Valor
                                    </option>
                                    <option value="FORMULA">Fórmula</option>
                                    <option value="MANUAL">Manual</option>

                                </select>

                                <small
                                    id="ajudaModoCalculo"
                                    class="form-text text-muted">
                                </small>

                            </div>
                        </div>

                        <div
                            class="col-md-4"
                            id="grupoBaseCalculo">

                            <div class="form-group">

                                <label for="baseCalculo">
                                    Base de Cálculo
                                </label>

                                <select
                                    id="baseCalculo"
                                    name="base_calculo"
                                    class="form-control">

                                    <option value="NENHUMA">Nenhuma</option>
                                    <option value="SALARIO_BASE">Salário Base</option>
                                    <option value="TOTAL_PROVENTOS">
                                        Total de Proventos
                                    </option>
                                    <option value="SALARIO_BRUTO">
                                        Salário Bruto
                                    </option>
                                    <option value="VALOR_HORA">
                                        Valor por Hora
                                    </option>
                                    <option value="VALOR_DIA">
                                        Valor por Dia
                                    </option>

                                </select>

                                <small
                                    id="ajudaBaseCalculo"
                                    class="form-text text-muted">
                                </small>

                            </div>
                        </div>

                        <div
                            class="col-md-4"
                            id="grupoValorPadrao">
                            <div class="form-group">
                                <label for="valorPadrao">
                                    Valor Padrão
                                </label>

                                <input
                                    type="number"
                                    id="valorPadrao"
                                    name="valor_padrao"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    value="0.00">
                            </div>
                        </div>

                        <div
                            class="col-md-4"
                            id="grupoPercentagem">
                            <div class="form-group">
                                <label for="percentagemPadrao">
                                    Percentagem
                                </label>

                                <div class="input-group">
                                    <input
                                        type="number"
                                        id="percentagemPadrao"
                                        name="percentagem_padrao"
                                        class="form-control"
                                        min="0"
                                        step="0.0001"
                                        value="0.0000">

                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="col-md-8"
                            id="grupoFormula">
                            <div class="form-group">
                                <label for="formulaCalculo">
                                    Fórmula de Cálculo
                                </label>

                                <input
                                    type="text"
                                    id="formulaCalculo"
                                    name="formula_calculo"
                                    class="form-control"
                                    maxlength="500"
                                    placeholder="Ex.: SALARIO_BASE * 0.03">

                                <small class="form-text text-muted">
                                    Utilize apenas variáveis e operadores autorizados pelo sistema.
                                </small>
                            </div>
                        </div>

                    </div>

                    <hr>

                    <!-- Comportamento -->
                    <h5 class="mb-3">Comportamento da Rubrica</h5>

                    <div class="row">

                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        id="tributavelInss"
                                        name="tributavel_inss"
                                        class="form-check-input"
                                        value="1">

                                    <label
                                        for="tributavelInss"
                                        class="form-check-label">
                                        Tributável para INSS
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        id="tributavelIrps"
                                        name="tributavel_irps"
                                        class="form-check-input"
                                        value="1">

                                    <label
                                        for="tributavelIrps"
                                        class="form-check-label">
                                        Tributável para IRPS
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        id="obrigatoria"
                                        name="obrigatoria"
                                        class="form-check-input"
                                        value="1">

                                    <label
                                        for="obrigatoria"
                                        class="form-check-label">
                                        Rubrica obrigatória
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        id="recorrente"
                                        name="recorrente"
                                        class="form-check-input"
                                        value="1">

                                    <label
                                        for="recorrente"
                                        class="form-check-label">
                                        Rubrica recorrente
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        id="permiteEdicao"
                                        name="permite_edicao"
                                        class="form-check-input"
                                        value="1"
                                        checked>

                                    <label
                                        for="permiteEdicao"
                                        class="form-check-label">
                                        Permite edição no processamento
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="fa fa-save"></i>
                        Guardar

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- Modal Visualizar -->
<div
    class="modal fade"
    id="modalVisualizarRubrica"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">
    <div
        class="modal-dialog modal-lg modal-dialog-scrollable"
        role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Detalhes da Rubrica Salarial
                </h5>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="row">

                    <div class="col-md-4">
                        <strong>Código</strong>
                        <p id="visualizarCodigo">-</p>
                    </div>

                    <div class="col-md-8">
                        <strong>Nome</strong>
                        <p id="visualizarNome">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Tipo</strong>
                        <p id="visualizarTipo">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Categoria</strong>
                        <p id="visualizarCategoria">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Estado</strong>
                        <p id="visualizarEstado">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Modo de Cálculo</strong>
                        <p id="visualizarModoCalculo">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Base de Cálculo</strong>
                        <p id="visualizarBaseCalculo">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Valor Padrão</strong>
                        <p id="visualizarValorPadrao">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Percentagem</strong>
                        <p id="visualizarPercentagem">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Ordem</strong>
                        <p id="visualizarOrdem">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Tributável para INSS</strong>
                        <p id="visualizarTributavelInss">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Tributável para IRPS</strong>
                        <p id="visualizarTributavelIrps">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Obrigatória</strong>
                        <p id="visualizarObrigatoria">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Recorrente</strong>
                        <p id="visualizarRecorrente">-</p>
                    </div>

                    <div class="col-md-4">
                        <strong>Permite edição</strong>
                        <p id="visualizarPermiteEdicao">-</p>
                    </div>

                    <div class="col-md-12">
                        <strong>Fórmula</strong>
                        <p id="visualizarFormula">-</p>
                    </div>

                    <div class="col-md-12">
                        <strong>Descrição</strong>
                        <p id="visualizarDescricao">-</p>
                    </div>

                </div>

            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    data-dismiss="modal">
                    Fechar
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    #modalRubrica .modal-dialog {
        max-height: calc(100vh - 30px);
        margin-top: 15px;
        margin-bottom: 15px;
    }

    #modalRubrica .modal-content {
        max-height: calc(100vh - 30px);
        overflow: hidden;
    }

    #modalRubrica .form-modal-scroll {
        display: flex;
        flex-direction: column;
        width: 100%;
        max-height: calc(100vh - 30px);
        overflow: hidden;
    }

    #modalRubrica .modal-header {
        flex-shrink: 0;
    }

    #modalRubrica .modal-body {
        flex: 1 1 auto;
        overflow-y: auto;
        overflow-x: hidden;
        min-height: 0;
    }

    #modalRubrica .modal-footer {
        flex-shrink: 0;
    }
</style>