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
                        Departamentos
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
                            Departamentos
                        </li>

                    </ul>

                </div>


                <!-- No cabeçalho da página (perto do botão Novo Departamento) -->
                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="btnImportarDepartamento">
                        <i class="fa fa-file-excel-o mr-1"></i>
                        Importar Excel
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoDepartamento">
                        <i class="fa fa-plus mr-1"></i>
                        Novo Departamento
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
                            Total de Departamentos
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoTotalDepartamentos">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Departamentos Activos
                        </span>

                        <h3
                            class="mt-2 mb-0 text-success"
                            id="resumoDepartamentosAtivos">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Departamentos Principais
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoDepartamentosPrincipais">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Subdepartamentos
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoSubdepartamentos">

                            0

                        </h3>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             CARREGAMENTO
        ========================================== -->

        <div
            id="departamentosCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status"
                aria-hidden="true">
            </span>

            A carregar departamentos...

        </div>


        <!-- =========================================
             TABELA
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Estrutura Organizacional
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="departamentosTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Departamento
                                </th>

                                <th>
                                    Departamento Superior
                                </th>

                                <th>
                                    Filial
                                </th>

                                <th>
                                    Centro de Custo
                                </th>

                                <th>
                                    Gestor
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
     MODAL DEPARTAMENTO
========================================== -->

<div
    class="modal fade"
    id="modalDepartamento"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalDepartamentoTitulo"
    aria-hidden="true">

    <div
        class="modal-dialog modal-lg"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="modalDepartamentoTitulo">

                    Novo Departamento

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


            <form id="formDepartamento">

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="departamentoId"
                        name="id">


                    <div class="row">

                        <!-- CÓDIGO -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="departamentoCodigo">

                                    Código

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="departamentoCodigo"
                                    name="codigo"
                                    maxlength="20"
                                    autocomplete="off"
                                    placeholder="Ex.: RH"
                                    required>

                                <small class="form-text text-muted">

                                    Utilize letras, números, ponto,
                                    hífen ou sublinhado.

                                </small>

                            </div>

                        </div>


                        <!-- NOME -->

                        <div class="col-lg-8 col-md-6">

                            <div class="form-group">

                                <label for="departamentoNome">

                                    Nome

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="departamentoNome"
                                    name="nome"
                                    maxlength="150"
                                    autocomplete="off"
                                    placeholder="Ex.: Recursos Humanos"
                                    required>

                            </div>

                        </div>


                        <!-- FILIAL -->

                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="departamentoFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="departamentoFilialId"
                                    name="filial_id">

                                    <option value="">
                                        Todas / Sem filial específica
                                    </option>

                                </select>

                                <small class="form-text text-muted">

                                    Deixe vazio para um departamento
                                    comum a toda a empresa.

                                </small>

                            </div>

                        </div>


                        <!-- DEPARTAMENTO PAI -->

                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="departamentoPaiId">
                                    Departamento Superior
                                </label>

                                <select
                                    class="form-control"
                                    id="departamentoPaiId"
                                    name="departamento_pai_id">

                                    <option value="">
                                        Departamento principal
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- CENTRO DE CUSTO -->

                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="departamentoCentroCustoId">
                                    Centro de Custo
                                </label>

                                <select
                                    class="form-control"
                                    id="departamentoCentroCustoId"
                                    name="centro_custo_id">

                                    <option value="">
                                        Sem centro de custo
                                    </option>

                                </select>

                                <small class="form-text text-muted">

                                    Permite analisar custos por departamento.

                                </small>

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="departamentoStatusId">

                                    Estado

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control"
                                    id="departamentoStatusId"
                                    name="status_id"
                                    required>

                                    <option value="">
                                        Selecione o estado
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- GESTOR -->

                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Gestor do Departamento
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="Disponível após a criação dos funcionários"
                                    readonly>

                                <small class="form-text text-muted">

                                    O gestor será selecionado entre os
                                    funcionários ativos da empresa.

                                </small>

                            </div>

                        </div>


                        <!-- DESCRIÇÃO -->

                        <div class="col-12">

                            <div class="form-group mb-0">

                                <label for="departamentoDescricao">
                                    Descrição
                                </label>

                                <textarea
                                    class="form-control"
                                    id="departamentoDescricao"
                                    name="descricao"
                                    rows="4"
                                    placeholder="Descreva as responsabilidades do departamento"></textarea>

                            </div>

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
                        id="btnGuardarDepartamento">

                        <span
                            id="btnGuardarDepartamentoSpinner"
                            class="spinner-border spinner-border-sm mr-2"
                            role="status"
                            aria-hidden="true"
                            style="display:none;">
                        </span>

                        <span id="btnGuardarDepartamentoTexto">

                            Guardar Departamento

                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- Modal Importação Excel de Departamentos -->
<div
    class="modal fade"
    id="modalImportarDepartamento"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-file-excel-o mr-2"></i>
                    Importar Departamentos por Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="departamentoImportacaoErro" class="alert alert-danger d-none"></div>
                <div id="departamentoImportacaoSucesso" class="alert alert-success d-none"></div>

                <!-- PASSO 1 -->
                <div id="departamentoImportacaoPasso1">
                    <div class="alert alert-info">
                        <strong>Como funciona?</strong>
                        <ol class="mb-0 mt-2">
                            <li>Baixe o modelo Excel.</li>
                            <li>Preencha o código, nome, filial, departamento pai e centro de custo.</li>
                            <li>Selecione o ficheiro.</li>
                            <li>Clique em <strong>Validar Excel</strong>.</li>
                            <li>Confira os erros e registos válidos.</li>
                            <li>Confirme a importação.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-success" id="btnModeloExcelDepartamento">
                            <i class="fa fa-download mr-1"></i> Baixar modelo Excel
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="departamentoFicheiroExcel">
                            Ficheiro Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control-file" id="departamentoFicheiroExcel" accept=".xlsx,.xls">
                        <small class="form-text text-muted">Formatos permitidos: XLSX e XLS.</small>
                    </div>

                    <button type="button" class="btn btn-primary" id="btnValidarExcelDepartamento">
                        <i class="fa fa-check mr-1"></i> Validar Excel
                    </button>
                </div>

                <!-- PASSO 2 -->
                <div id="departamentoImportacaoPasso2" class="d-none">
                    <div id="departamentoImportacaoErrosContainer" class="d-none mb-3">
                        <h5 class="text-danger"><i class="fa fa-warning mr-1"></i> Linhas com erro</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Código / Nome</th>
                                        <th>Erro</th>
                                    </tr>
                                </thead>
                                <tbody id="departamentoImportacaoErros"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="departamentoImportacaoValidosContainer" class="mt-4">
                        <h5 class="text-success"><i class="fa fa-check-circle mr-1"></i> Departamentos prontos para importar</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Código</th>
                                        <th>Nome</th>
                                        <th>Filial</th>
                                        <th>Departamento Superior</th>
                                        <th>Centro de Custo</th>
                                    </tr>
                                </thead>
                                <tbody id="departamentoImportacaoValidos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary d-none" id="btnVoltarImportacaoDepartamento">
                    <i class="fa fa-arrow-left mr-1"></i> Voltar
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success d-none" id="btnConfirmarImportacaoDepartamento">
                    <i class="fa fa-upload mr-1"></i> Importar Departamentos Válidos
                </button>
            </div>
        </div>
    </div>
</div>