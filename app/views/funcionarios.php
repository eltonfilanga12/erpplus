<?php

declare(strict_types=1);

Auth::requireLogin();

?>

<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- =========================================
             CABEÇALHO
        ========================================== -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Funcionários
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
                            Funcionários
                        </li>

                    </ul>

                </div>

                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="funcionarioBtnImportar">
                        <i class="fa fa-file-excel-o"></i>
                        <span class="d-none d-sm-inline">
                            Importar Excel
                        </span>
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoFuncionario">

                        <i class="fa fa-plus mr-1"></i>

                        Novo Funcionário

                    </button>

                </div>

            </div>

        </div>


        <!-- =========================================
             RESUMO
        ========================================== -->

        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Total de Funcionários
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoTotalFuncionarios">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Funcionários Activos
                        </span>

                        <h3
                            class="mt-2 mb-0 text-success"
                            id="resumoFuncionariosAtivos">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Funcionários Suspensos
                        </span>

                        <h3
                            class="mt-2 mb-0 text-warning"
                            id="resumoFuncionariosSuspensos">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Funcionários Desligados
                        </span>

                        <h3
                            class="mt-2 mb-0 text-danger"
                            id="resumoFuncionariosDesligados">

                            0

                        </h3>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             FILTROS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Filtros
                </h4>

            </div>

            <div class="card-body">

                <div class="row align-items-end">

                    <div class="col-xl-3 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroFuncionarioPesquisa">
                                Pesquisa
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="filtroFuncionarioPesquisa"
                                placeholder="Nome, número, NUIT, BI ou telefone">

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroFuncionarioFilialId">
                                Filial
                            </label>

                            <select
                                class="form-control"
                                id="filtroFuncionarioFilialId">

                                <option value="">
                                    Todas as filiais
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroFuncionarioDepartamentoId">
                                Departamento
                            </label>

                            <select
                                class="form-control"
                                id="filtroFuncionarioDepartamentoId">

                                <option value="">
                                    Todos os departamentos
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroFuncionarioCargoId">
                                Cargo
                            </label>

                            <select
                                class="form-control"
                                id="filtroFuncionarioCargoId">

                                <option value="">
                                    Todos os cargos
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-1 col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="filtroFuncionarioStatusId">
                                Estado
                            </label>

                            <select
                                class="form-control"
                                id="filtroFuncionarioStatusId">

                                <option value="">
                                    Todos
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-xl-2 col-lg-4 col-md-6">

                        <div class="form-group">

                            <div class="btn-group w-100">

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    id="btnFiltrarFuncionarios">

                                    <i class="fa fa-search mr-1"></i>

                                    Filtrar

                                </button>

                                <button
                                    type="button"
                                    class="btn btn-light"
                                    id="btnLimparFiltrosFuncionarios"
                                    title="Limpar filtros">

                                    <i class="fa fa-eraser"></i>

                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             CARREGAMENTO
        ========================================== -->

        <div
            id="funcionariosCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status"
                aria-hidden="true">
            </span>

            A carregar funcionários...

        </div>


        <!-- =========================================
             TABELA
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Funcionários Registados
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="funcionariosTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Número
                                </th>

                                <th>
                                    Funcionário
                                </th>

                                <th>
                                    Filial
                                </th>

                                <th>
                                    Departamento
                                </th>

                                <th>
                                    Cargo
                                </th>

                                <th>
                                    Vínculo
                                </th>

                                <th>
                                    Admissão
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th class="text-right">
                                    Acções
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


<!-- =========================================
     MODAL FUNCIONÁRIO
========================================== -->

<div
    class="modal fade"
    id="modalFuncionario"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalFuncionarioTitulo"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="modalFuncionarioTitulo">

                    Novo Funcionário

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


            <form id="formFuncionario">

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="funcionarioId"
                        name="id">


                    <!-- =================================
                         IDENTIFICAÇÃO PROFISSIONAL
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Identificação Profissional
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioNumero">

                                    Número do Funcionário

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioNumero"
                                    name="numero_funcionario"
                                    maxlength="30"
                                    autocomplete="off"
                                    placeholder="Ex.: FUN-0001"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioStatusId">

                                    Estado

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control"
                                    id="funcionarioStatusId"
                                    name="status_id"
                                    required>

                                    <option value="">
                                        Selecione o estado
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioTipoVinculo">

                                    Tipo de Vínculo

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control"
                                    id="funcionarioTipoVinculo"
                                    name="tipo_vinculo"
                                    required>

                                    <option value="">
                                        Selecione o vínculo
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDataAdmissao">

                                    Data de Admissão

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="funcionarioDataAdmissao"
                                    name="data_admissao"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="funcionarioFilialId"
                                    name="filial_id">

                                    <option value="">
                                        Sem filial definida
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDepartamentoId">
                                    Departamento
                                </label>

                                <select
                                    class="form-control"
                                    id="funcionarioDepartamentoId"
                                    name="departamento_id">

                                    <option value="">
                                        Sem departamento
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioCargoId">
                                    Cargo
                                </label>

                                <select
                                    class="form-control"
                                    id="funcionarioCargoId"
                                    name="cargo_id">

                                    <option value="">
                                        Sem cargo definido
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioHorarioId">
                                    Horário de Trabalho
                                </label>

                                <select
                                    class="form-control"
                                    id="funcionarioHorarioId"
                                    name="horario_trabalho_id">

                                    <option value="">
                                        Sem horário definido
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         DADOS PESSOAIS
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Dados Pessoais
                            </h5>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioPrimeiroNome">

                                    Primeiro Nome

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioPrimeiroNome"
                                    name="primeiro_nome"
                                    maxlength="80"
                                    autocomplete="off"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioApelido">

                                    Apelido

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioApelido"
                                    name="apelido"
                                    maxlength="80"
                                    autocomplete="off"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioNomeCompleto">
                                    Nome Completo
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioNomeCompleto"
                                    readonly>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioSexo">
                                    Sexo
                                </label>

                                <select
                                    class="form-control"
                                    id="funcionarioSexo"
                                    name="sexo">

                                    <option value="">
                                        Selecione
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDataNascimento">
                                    Data de Nascimento
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="funcionarioDataNascimento"
                                    name="data_nascimento">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioEstadoCivil">
                                    Estado Civil
                                </label>

                                <select
                                    class="form-control"
                                    id="funcionarioEstadoCivil"
                                    name="estado_civil">

                                    <option value="">
                                        Selecione
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioNacionalidade">
                                    Nacionalidade
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioNacionalidade"
                                    name="nacionalidade"
                                    maxlength="80"
                                    value="Moçambicana">

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioNaturalidade">
                                    Naturalidade
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioNaturalidade"
                                    name="naturalidade"
                                    maxlength="120">

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioFotografiaArquivo">
                                    Fotografia
                                </label>

                                <input
                                    type="file"
                                    class="form-control"
                                    id="funcionarioFotografiaArquivo"
                                    name="fotografia_arquivo"
                                    accept="image/jpeg,image/png,image/webp">

                                <small class="form-text text-muted">
                                    JPG, PNG ou WEBP.
                                </small>

                                <div
                                    id="funcionarioFotografiaPreviewContainer"
                                    class="mt-2"
                                    style="display:none;">

                                    <img
                                        id="funcionarioFotografiaPreview"
                                        src=""
                                        alt="Fotografia do funcionário"
                                        class="img-thumbnail"
                                        style="
                                        width:120px;
                                        height:120px;
                                        object-fit:cover;
                                    ">

                                </div>

                                <input
                                    type="hidden"
                                    id="funcionarioFotografiaAtual"
                                    name="fotografia">

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         DOCUMENTAÇÃO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Documentação
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioNuit">
                                    NUIT
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioNuit"
                                    name="nuit"
                                    maxlength="20">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioNumeroBi">
                                    Número do BI
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioNumeroBi"
                                    name="numero_bi"
                                    maxlength="40">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDataEmissaoBi">
                                    Emissão do BI
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="funcionarioDataEmissaoBi"
                                    name="data_emissao_bi">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDataValidadeBi">
                                    Validade do BI
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="funcionarioDataValidadeBi"
                                    name="data_validade_bi">

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioEntidadeEmissoraBi">
                                    Entidade Emissora
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioEntidadeEmissoraBi"
                                    name="entidade_emissora_bi"
                                    maxlength="120">

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioNumeroInss">
                                    Número de INSS
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioNumeroInss"
                                    name="numero_inss"
                                    maxlength="40">

                            </div>

                        </div>

                        <div class="col-12 mt-3">
                            <h6 class="mb-3">
                                Dados Fiscais
                            </h6>
                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioNumeroDependentes">
                                    Número de Dependentes
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="funcionarioNumeroDependentes"
                                    name="numero_dependentes"
                                    min="0"
                                    step="1"
                                    value="0">

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioResidenteFiscal">
                                    Residente Fiscal
                                </label>

                                <select
                                    class="form-control"
                                    id="funcionarioResidenteFiscal"
                                    name="residente_fiscal">

                                    <option value="1">
                                        Sim
                                    </option>

                                    <option value="0">
                                        Não
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>



                    <div
                        class="col-12"
                        id="funcionarioDocumentosExistentesContainer"
                        style="display:none;">

                        <div class="card border mt-3">

                            <div class="card-header py-2">

                                <strong>
                                    Documentos já guardados
                                </strong>

                            </div>

                            <div
                                class="card-body p-2"
                                id="funcionarioDocumentosExistentes">
                            </div>

                        </div>

                    </div>

                    <hr>

                    <div class="row">

                        <div class="col-12 mt-3">

                            <h6 class="mb-3">
                                Ficheiros do Dossier do Funcionário
                            </h6>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDocumentoBi">
                                    Cópia do BI
                                </label>

                                <input
                                    type="file"
                                    class="form-control"
                                    id="funcionarioDocumentoBi"
                                    name="documento_bi"
                                    accept=".pdf,.jpg,.jpeg,.png">

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDocumentoPassaporte">
                                    Cópia do Passaporte
                                </label>

                                <input
                                    type="file"
                                    class="form-control"
                                    id="funcionarioDocumentoPassaporte"
                                    name="documento_passaporte"
                                    accept=".pdf,.jpg,.jpeg,.png">

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDocumentoNuit">
                                    Cópia do NUIT
                                </label>

                                <input
                                    type="file"
                                    class="form-control"
                                    id="funcionarioDocumentoNuit"
                                    name="documento_nuit"
                                    accept=".pdf,.jpg,.jpeg,.png">

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDocumentoInss">
                                    Comprovativo de Inscrição no INSS
                                </label>

                                <input
                                    type="file"
                                    class="form-control"
                                    id="funcionarioDocumentoInss"
                                    name="documento_inss"
                                    accept=".pdf,.jpg,.jpeg,.png">

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDocumentoRegistoCriminal">
                                    Certificado de Registo Criminal
                                </label>

                                <input
                                    type="file"
                                    class="form-control"
                                    id="funcionarioDocumentoRegistoCriminal"
                                    name="documento_registo_criminal"
                                    accept=".pdf,.jpg,.jpeg,.png">

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDocumentoHabilitacoes">
                                    Certificado de Habilitações
                                </label>

                                <input
                                    type="file"
                                    class="form-control"
                                    id="funcionarioDocumentoHabilitacoes"
                                    name="documento_habilitacoes"
                                    accept=".pdf,.jpg,.jpeg,.png">

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDocumentoCv">
                                    Curriculum Vitae (CV)
                                </label>

                                <input
                                    type="file"
                                    class="form-control"
                                    id="funcionarioDocumentoCv"
                                    name="documento_cv"
                                    accept=".pdf,.doc,.docx">

                            </div>

                        </div>


                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDocumentoSanidade">
                                    Atestado de Sanidade Mental e Física
                                </label>

                                <input
                                    type="file"
                                    class="form-control"
                                    id="funcionarioDocumentoSanidade"
                                    name="documento_atestado_sanidade"
                                    accept=".pdf,.jpg,.jpeg,.png">

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         CONTACTOS
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Contactos
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioTelefonePrincipal">
                                    Telefone Principal
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioTelefonePrincipal"
                                    name="telefone_principal"
                                    maxlength="30">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioTelefoneAlternativo">
                                    Telefone Alternativo
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioTelefoneAlternativo"
                                    name="telefone_alternativo"
                                    maxlength="30">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioEmailPessoal">
                                    E-mail Pessoal
                                </label>

                                <input
                                    type="email"
                                    class="form-control"
                                    id="funcionarioEmailPessoal"
                                    name="email_pessoal"
                                    maxlength="150">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioEmailProfissional">
                                    E-mail Profissional
                                </label>

                                <input
                                    type="email"
                                    class="form-control"
                                    id="funcionarioEmailProfissional"
                                    name="email_profissional"
                                    maxlength="150">

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         MORADA
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Morada
                            </h5>

                        </div>


                        <div class="col-12">

                            <div class="form-group">

                                <label for="funcionarioEndereco">
                                    Endereço
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioEndereco"
                                    name="endereco"
                                    maxlength="255">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioProvincia">
                                    Província
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioProvincia"
                                    name="provincia"
                                    maxlength="100">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDistrito">
                                    Distrito
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioDistrito"
                                    name="distrito"
                                    maxlength="100">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioCidade">
                                    Cidade
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioCidade"
                                    name="cidade"
                                    maxlength="100">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioBairro">
                                    Bairro
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioBairro"
                                    name="bairro"
                                    maxlength="100">

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         CONTACTO DE EMERGÊNCIA
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Contacto de Emergência
                            </h5>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioContactoEmergenciaNome">
                                    Nome
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioContactoEmergenciaNome"
                                    name="contacto_emergencia_nome"
                                    maxlength="150">

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioContactoEmergenciaParentesco">
                                    Parentesco
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioContactoEmergenciaParentesco"
                                    name="contacto_emergencia_parentesco"
                                    maxlength="80">

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioContactoEmergenciaTelefone">
                                    Telefone
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioContactoEmergenciaTelefone"
                                    name="contacto_emergencia_telefone"
                                    maxlength="30">

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         REMUNERAÇÃO E BANCO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Remuneração e Dados Bancários
                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioSalarioBase">
                                    Salário Base
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="funcionarioSalarioBase"
                                    name="salario_base"
                                    min="0"
                                    step="0.01"
                                    value="0.00">

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioMoeda">
                                    Moeda
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioMoeda"
                                    name="moeda"
                                    maxlength="3"
                                    value="MZN">

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioBancoNome">
                                    Banco
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioBancoNome"
                                    name="banco_nome"
                                    maxlength="150">

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioNumeroContaBancaria">
                                    Número da Conta
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioNumeroContaBancaria"
                                    name="numero_conta_bancaria"
                                    maxlength="50">

                            </div>

                        </div>


                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioNib">
                                    NIB
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="funcionarioNib"
                                    name="nib"
                                    maxlength="50">

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         DESLIGAMENTO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Desligamento
                            </h5>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioDataDesligamento">
                                    Data de Desligamento
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="funcionarioDataDesligamento"
                                    name="data_desligamento"
                                    disabled>

                                <small class="form-text text-muted">
                                    Disponível quando o estado for Desligado.
                                </small>

                            </div>

                        </div>


                        <div class="col-lg-8 col-md-6">

                            <div class="form-group">

                                <label for="funcionarioMotivoDesligamento">
                                    Motivo do Desligamento
                                </label>

                                <textarea
                                    class="form-control"
                                    id="funcionarioMotivoDesligamento"
                                    name="motivo_desligamento"
                                    rows="3"
                                    disabled></textarea>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         OBSERVAÇÕES
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <div class="form-group mb-0">

                                <label for="funcionarioObservacoes">
                                    Observações
                                </label>

                                <textarea
                                    class="form-control"
                                    id="funcionarioObservacoes"
                                    name="observacoes"
                                    rows="4"
                                    placeholder="Informações adicionais sobre o funcionário"></textarea>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =====================================
                     RODAPÉ
                ====================================== -->

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
                        id="btnGuardarFuncionario">

                        <span
                            id="btnGuardarFuncionarioSpinner"
                            class="spinner-border spinner-border-sm mr-2"
                            role="status"
                            aria-hidden="true"
                            style="display:none;">
                        </span>

                        <span id="btnGuardarFuncionarioTexto">
                            Guardar Funcionário
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- Modal de Importação Excel -->
<div
    id="funcionarioModalImportar"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="funcionarioModalImportarTitulo"
    aria-hidden="true">

    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="funcionarioModalImportarTitulo">
                    <i class="fa fa-file-excel-o"></i>
                    Importar Funcionários por Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="funcionarioImportacaoErro" class="alert alert-danger d-none"></div>
                <div id="funcionarioImportacaoSucesso" class="alert alert-success d-none"></div>

                <!-- PASSO 1: Upload e Instruções -->
                <div id="funcionarioImportacaoPasso1">
                    <div class="alert alert-info">
                        <strong>Como funciona?</strong>
                        <ol class="mb-0 mt-2">
                            <li>Baixe o modelo Excel.</li>
                            <li>Preencha os dados dos funcionários.</li>
                            <li>Seleccione o ficheiro preenchido.</li>
                            <li>Clique em <strong>Validar Excel</strong>.</li>
                            <li>Confira os erros (se houver) e os funcionários válidos.</li>
                            <li>Confirme a importação final.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-success" id="funcionarioBtnModeloExcel">
                            <i class="fa fa-download"></i> Baixar modelo Excel
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="funcionarioFicheiroExcel">
                            Ficheiro Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control-file" id="funcionarioFicheiroExcel" accept=".xlsx,.xls">
                        <small class="form-text text-muted">
                            Formatos permitidos: XLSX e XLS. Tamanho máximo: 10 MB.
                        </small>
                    </div>

                    <button type="button" class="btn btn-primary" id="funcionarioBtnValidarExcel">
                        <i class="fa fa-check"></i> Validar Excel
                    </button>
                </div>

                <!-- PASSO 2: Pré-visualização de Válidos e Erros -->
                <div id="funcionarioImportacaoPasso2" class="d-none">
                    <div id="funcionarioImportacaoResumo" class="mb-3"></div>

                    <div id="funcionarioImportacaoErrosContainer" class="d-none">
                        <h5 class="text-danger">
                            <i class="fa fa-warning"></i> Linhas com erro
                        </h5>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th>Linha</th>
                                        <th>Nº Funcionário</th>
                                        <th>Nome</th>
                                        <th>Erro</th>
                                    </tr>
                                </thead>
                                <tbody id="funcionarioImportacaoErros"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="funcionarioImportacaoValidosContainer" class="mt-4">
                        <h5 class="text-success">
                            <i class="fa fa-check-circle"></i> Funcionários prontos para importar (Verifique todos os dados)
                        </h5>
                        <div class="table-responsive" style="max-height: 450px; overflow-y: auto; overflow-x: auto;">
                            <table class="table table-sm table-striped table-bordered text-nowrap">
                                <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr>
                                        <th>Linha</th>
                                        <th>Nº Funcionário</th>
                                        <th>Nome Completo</th>
                                        <th>Sexo</th>
                                        <th>Filial</th>
                                        <th>Departamento</th>
                                        <th>Cargo</th>
                                        <th>NUIT</th>
                                        <th>Nº BI</th>
                                        <th>Telefone</th>
                                        <th>E-mail</th>
                                        <th>Admissão</th>
                                        <th>Salário Base</th>
                                        <th>Vínculo</th>
                                    </tr>
                                </thead>
                                <tbody id="funcionarioImportacaoValidos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary d-none" id="funcionarioBtnVoltarImportacao">
                    <i class="fa fa-arrow-left"></i> Voltar
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-success d-none" id="funcionarioBtnConfirmarImportacao">
                    <i class="fa fa-upload"></i> Importar Funcionários Válidos
                </button>
            </div>

        </div>
    </div>
</div>