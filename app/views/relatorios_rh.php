<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content container-fluid">

        <!-- Page Header -->
        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Relatórios de RH
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item">
                            Relatórios
                        </li>

                        <li class="breadcrumb-item active">
                            Recursos Humanos
                        </li>

                    </ul>

                </div>


                <div class="col-auto">

                    <div class="btn-group">

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            id="btnImprimirRelatorioRH">

                            <i class="fa fa-print"></i>

                            <span class="d-none d-sm-inline">
                                Imprimir
                            </span>

                        </button>


                        <button
                            type="button"
                            class="btn btn-outline-success"
                            id="btnExportarRelatorioRH">

                            <i class="fa fa-file-excel-o"></i>

                            <span class="d-none d-sm-inline">
                                Exportar
                            </span>

                        </button>

                    </div>

                </div>

            </div>

        </div>


        <!-- Abas -->
        <div class="card">

            <div class="card-body pb-0">

                <!-- Desktop -->
                <div class="d-none d-lg-block">

                    <ul
                        class="nav nav-tabs"
                        id="relatorioRHTabs">

                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link active"
                                data-relatorio="ASSIDUIDADE">

                                <i class="fa fa-clock-o mr-1"></i>

                                Assiduidade

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="FALTAS">

                                <i class="fa fa-calendar-times-o mr-1"></i>

                                Faltas

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="HORAS_EXTRAS">

                                <i class="fa fa-hourglass-half mr-1"></i>

                                Horas Extras

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="FOLHA_SALARIAL">

                                <i class="fa fa-money mr-1"></i>

                                Folha Salarial

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="ENCARGOS">

                                <i class="fa fa-calculator mr-1"></i>

                                Encargos

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="FUNCIONARIOS">

                                <i class="fa fa-users mr-1"></i>

                                Funcionários

                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                href="#"
                                class="nav-link"
                                data-relatorio="PERFIL_FUNCIONARIO">

                                <i class="fa fa-user mr-1"></i>

                                Perfil do Funcionário

                            </a>

                        </li>

                    </ul>

                </div>


                <!-- Mobile -->
                <div class="d-lg-none mb-3">

                    <label
                        for="tipoRelatorioRHMobile">

                        Tipo de relatório

                    </label>


                    <select
                        class="form-control"
                        id="tipoRelatorioRHMobile">

                        <option value="ASSIDUIDADE">
                            Assiduidade
                        </option>

                        <option value="FALTAS">
                            Faltas
                        </option>

                        <option value="HORAS_EXTRAS">
                            Horas Extras
                        </option>

                        <option value="FOLHA_SALARIAL">
                            Folha Salarial
                        </option>

                        <option value="ENCARGOS">
                            Encargos
                        </option>

                        <option value="FUNCIONARIOS">
                            Funcionários
                        </option>

                        <option value="PERFIL_FUNCIONARIO">
                            Perfil do Funcionário
                        </option>

                    </select>

                </div>

            </div>

        </div>


        <!-- Filtros -->
        <div class="card">

            <div class="card-body">

                <form id="formFiltrosRelatorioRH">

                    <input
                        type="hidden"
                        id="tipoRelatorioRH"
                        value="ASSIDUIDADE">


                    <div class="row">


                        <!-- Data inicial -->
                        <div
                            class="col-md-3"
                            id="grupoRHDataInicio">

                            <div class="form-group">

                                <label for="rhDataInicio">
                                    Data inicial
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="rhDataInicio">

                            </div>

                        </div>


                        <!-- Data final -->
                        <div
                            class="col-md-3"
                            id="grupoRHDataFim">

                            <div class="form-group">

                                <label for="rhDataFim">
                                    Data final
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="rhDataFim">

                            </div>

                        </div>


                        <!-- Período salarial -->
                        <div
                            class="col-md-3 d-none"
                            id="grupoRHPeriodo">

                            <div class="form-group">

                                <label for="rhPeriodo">
                                    Período salarial
                                </label>

                                <select
                                    class="form-control"
                                    id="rhPeriodo">

                                    <option value="">
                                        Todos os períodos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Filial -->
                        <div
                            class="col-md-3"
                            id="grupoRHFilial">

                            <div class="form-group">

                                <label for="rhFilial">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="rhFilial">

                                    <option value="">
                                        Todas as filiais
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Departamento -->
                        <div
                            class="col-md-3"
                            id="grupoRHDepartamento">

                            <div class="form-group">

                                <label for="rhDepartamento">
                                    Departamento
                                </label>

                                <select
                                    class="form-control"
                                    id="rhDepartamento">

                                    <option value="">
                                        Todos os departamentos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Cargo -->
                        <div
                            class="col-md-3"
                            id="grupoRHCargo">

                            <div class="form-group">

                                <label for="rhCargo">
                                    Cargo
                                </label>

                                <select
                                    class="form-control"
                                    id="rhCargo">

                                    <option value="">
                                        Todos os cargos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Funcionário -->
                        <div
                            class="col-md-4"
                            id="grupoRHFuncionario">

                            <div class="form-group">

                                <label for="rhFuncionario">
                                    Funcionário
                                </label>

                                <select
                                    class="form-control"
                                    id="rhFuncionario">

                                    <option value="">
                                        Todos os funcionários
                                    </option>

                                </select>

                                <small
                                    class="form-text text-muted d-none"
                                    id="avisoRHFuncionarioObrigatorio">

                                    No Perfil do Funcionário é obrigatório selecionar um funcionário.

                                </small>

                            </div>

                        </div>


                        <!-- Ocorrência -->
                        <div
                            class="col-md-3 d-none"
                            id="grupoRHOcorrencia">

                            <div class="form-group">

                                <label for="rhOcorrencia">
                                    Ocorrência
                                </label>

                                <select
                                    class="form-control"
                                    id="rhOcorrencia">

                                    <option value="">
                                        Todas as ocorrências
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Tipo de falta -->
                        <div
                            class="col-md-3 d-none"
                            id="grupoRHTipoFalta">

                            <div class="form-group">

                                <label for="rhTipoFalta">
                                    Tipo de falta
                                </label>

                                <select
                                    class="form-control"
                                    id="rhTipoFalta">

                                    <option value="">
                                        Todos os tipos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Tipo de hora extra -->
                        <div
                            class="col-md-3 d-none"
                            id="grupoRHTipoHoraExtra">

                            <div class="form-group">

                                <label for="rhTipoHoraExtra">
                                    Tipo de hora extra
                                </label>

                                <select
                                    class="form-control"
                                    id="rhTipoHoraExtra">

                                    <option value="">
                                        Todos os tipos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Origem -->
                        <div
                            class="col-md-3 d-none"
                            id="grupoRHOrigem">

                            <div class="form-group">

                                <label for="rhOrigem">
                                    Origem
                                </label>

                                <select
                                    class="form-control"
                                    id="rhOrigem">

                                    <option value="">
                                        Todas as origens
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Sexo -->
                        <div
                            class="col-md-3 d-none"
                            id="grupoRHSexo">

                            <div class="form-group">

                                <label for="rhSexo">
                                    Sexo
                                </label>

                                <select
                                    class="form-control"
                                    id="rhSexo">

                                    <option value="">
                                        Todos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Tipo vínculo -->
                        <div
                            class="col-md-3 d-none"
                            id="grupoRHTipoVinculo">

                            <div class="form-group">

                                <label for="rhTipoVinculo">
                                    Tipo de vínculo
                                </label>

                                <select
                                    class="form-control"
                                    id="rhTipoVinculo">

                                    <option value="">
                                        Todos os vínculos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- Pesquisa -->
                        <div
                            class="col-md-4"
                            id="grupoRHPesquisa">

                            <div class="form-group">

                                <label for="rhPesquisa">
                                    Pesquisar
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="rhPesquisa"
                                    placeholder="Nome, número do funcionário, NUIT...">

                            </div>

                        </div>


                        <!-- Botões -->
                        <div class="col-md-4">

                            <div class="form-group mb-0">

                                <label>&nbsp;</label>

                                <div class="d-flex">

                                    <button
                                        type="submit"
                                        class="btn btn-primary mr-2 flex-fill">

                                        <i class="fa fa-search"></i>

                                        Aplicar filtros

                                    </button>


                                    <button
                                        type="button"
                                        class="btn btn-light"
                                        id="btnLimparFiltrosRH">

                                        <i class="fa fa-refresh"></i>

                                    </button>

                                </div>

                            </div>

                        </div>


                    </div>

                </form>

            </div>

        </div>


        <!-- Resumo -->
        <div
            class="row"
            id="relatorioRHResumo">


            <div class="col-md-3">

                <div class="card">

                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="rhResumoTitulo1">

                            Presenças

                        </span>

                        <h3 id="rhResumoValor1">
                            0
                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="card">

                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="rhResumoTitulo2">

                            Faltas

                        </span>

                        <h3 id="rhResumoValor2">
                            0
                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="card">

                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="rhResumoTitulo3">

                            Atrasos

                        </span>

                        <h3 id="rhResumoValor3">
                            0
                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-md-3">

                <div class="card">

                    <div class="card-body">

                        <span
                            class="text-muted"
                            id="rhResumoTitulo4">

                            Horas trabalhadas

                        </span>

                        <h3 id="rhResumoValor4">
                            0h
                        </h3>

                    </div>

                </div>

            </div>


        </div>


        <!-- Perfil do funcionário -->
        <div
            id="rhPerfilContainer"
            class="d-none">


            <!-- Identificação -->
            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Dados do Funcionário
                    </h4>

                </div>


                <div class="card-body">

                    <div class="row">


                        <div class="col-md-3">

                            <small class="text-muted">
                                Número
                            </small>

                            <h5
                                class="mb-3"
                                id="rhPerfilNumero">

                                -

                            </h5>

                        </div>


                        <div class="col-md-5">

                            <small class="text-muted">
                                Nome
                            </small>

                            <h5
                                class="mb-3"
                                id="rhPerfilNome">

                                -

                            </h5>

                        </div>


                        <div class="col-md-2">

                            <small class="text-muted">
                                Estado
                            </small>

                            <h5
                                class="mb-3"
                                id="rhPerfilEstado">

                                -

                            </h5>

                        </div>


                        <div class="col-md-2">

                            <small class="text-muted">
                                Vínculo
                            </small>

                            <h5
                                class="mb-3"
                                id="rhPerfilVinculo">

                                -

                            </h5>

                        </div>


                        <div class="col-md-3">

                            <small class="text-muted">
                                Filial
                            </small>

                            <h6 id="rhPerfilFilial">
                                -
                            </h6>

                        </div>


                        <div class="col-md-3">

                            <small class="text-muted">
                                Departamento
                            </small>

                            <h6 id="rhPerfilDepartamento">
                                -
                            </h6>

                        </div>


                        <div class="col-md-3">

                            <small class="text-muted">
                                Cargo
                            </small>

                            <h6 id="rhPerfilCargo">
                                -
                            </h6>

                        </div>


                        <div class="col-md-3">

                            <small class="text-muted">
                                Data de admissão
                            </small>

                            <h6 id="rhPerfilAdmissao">
                                -
                            </h6>

                        </div>


                    </div>

                </div>

            </div>


            <!-- Contrato e salário -->
            <div class="row">


                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Contrato Atual
                            </h4>

                        </div>


                        <div class="card-body">

                            <div class="row">


                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Número
                                    </small>

                                    <p id="rhPerfilContratoNumero">
                                        -
                                    </p>

                                </div>


                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Estado
                                    </small>

                                    <p id="rhPerfilContratoEstado">
                                        -
                                    </p>

                                </div>


                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Início
                                    </small>

                                    <p id="rhPerfilContratoInicio">
                                        -
                                    </p>

                                </div>


                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Fim
                                    </small>

                                    <p id="rhPerfilContratoFim">
                                        -
                                    </p>

                                </div>


                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Horas semanais
                                    </small>

                                    <p id="rhPerfilContratoHoras">
                                        -
                                    </p>

                                </div>


                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Local de trabalho
                                    </small>

                                    <p id="rhPerfilContratoLocal">
                                        -
                                    </p>

                                </div>


                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Remuneração Atual
                            </h4>

                        </div>


                        <div class="card-body">

                            <div class="row">


                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Salário base
                                    </small>

                                    <h4 id="rhPerfilSalarioBase">
                                        -
                                    </h4>

                                </div>


                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Periodicidade
                                    </small>

                                    <p id="rhPerfilSalarioPeriodicidade">
                                        -
                                    </p>

                                </div>


                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Horas mensais
                                    </small>

                                    <p id="rhPerfilSalarioHoras">
                                        -
                                    </p>

                                </div>


                                <div class="col-md-6">

                                    <small class="text-muted">
                                        Dias mensais
                                    </small>

                                    <p id="rhPerfilSalarioDias">
                                        -
                                    </p>

                                </div>


                            </div>

                        </div>

                    </div>

                </div>


            </div>


            <!-- Resumo individual -->
            <div class="row">


                <div class="col-md-4">

                    <div class="card">

                        <div class="card-body">

                            <span class="text-muted">
                                Presenças
                            </span>

                            <h3 id="rhPerfilPresencas">
                                0
                            </h3>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="card">

                        <div class="card-body">

                            <span class="text-muted">
                                Faltas
                            </span>

                            <h3 id="rhPerfilFaltas">
                                0
                            </h3>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="card">

                        <div class="card-body">

                            <span class="text-muted">
                                Horas extras aprovadas
                            </span>

                            <h3 id="rhPerfilHorasExtras">
                                0h
                            </h3>

                        </div>

                    </div>

                </div>


            </div>

        </div>


        <!-- Resultado geral -->
        <div
            class="card"
            id="rhResultadoContainer">

            <div class="card-header">

                <div class="row align-items-center">

                    <div class="col">

                        <h4
                            class="card-title mb-0"
                            id="rhRelatorioTitulo">

                            Relatório de Assiduidade

                        </h4>

                    </div>


                    <div class="col-auto">

                        <span
                            class="badge badge-light"
                            id="rhRelatorioQuantidade">

                            0 registos

                        </span>

                    </div>

                </div>

            </div>


            <div class="card-body">


                <!-- Erro -->
                <div
                    id="rhRelatorioErro"
                    class="alert alert-danger d-none">
                </div>


                <!-- Tabela -->
                <div
                    class="table-responsive"
                    id="rhTabelaContainer">

                    <table
                        class="table table-striped custom-table mb-0"
                        id="rhRelatorioTable">

                        <thead id="rhRelatorioTableHead">

                            <tr>

                                <th>
                                    A carregar...
                                </th>

                            </tr>

                        </thead>


                        <tbody id="rhRelatorioTableBody">


                        </tbody>

                    </table>

                </div>


            </div>

        </div>


    </div>
</div>