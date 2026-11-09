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
                        Cargos
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
                            Cargos
                        </li>

                    </ul>

                </div>


                <!-- No cabeçalho da página (perto do botão Novo Cargo) -->
                <div class="col-auto">
                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="btnImportarCargo">
                        <i class="fa fa-file-excel-o mr-1"></i>
                        Importar Excel
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoCargo">
                        <i class="fa fa-plus mr-1"></i>
                        Novo Cargo
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
                            Total de Cargos
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoTotalCargos">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Cargos Activos
                        </span>

                        <h3
                            class="mt-2 mb-0 text-success"
                            id="resumoCargosAtivos">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Cargos de Chefia
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoCargosChefia">

                            0

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Permitem Horas Extras
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoCargosHorasExtras">

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
            id="cargosCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status"
                aria-hidden="true">
            </span>

            A carregar cargos...

        </div>


        <!-- =========================================
             TABELA
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Estrutura de Cargos
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="cargosTable"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Cargo
                                </th>

                                <th>
                                    Departamento
                                </th>

                                <th class="text-center">
                                    Nível
                                </th>

                                <th>
                                    Faixa Salarial
                                </th>

                                <th class="text-center">
                                    Carga Semanal
                                </th>

                                <th class="text-center">
                                    Horas Extras
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
     MODAL CARGO
========================================== -->

<div
    class="modal fade"
    id="modalCargo"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modalCargoTitulo"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="modalCargoTitulo">

                    Novo Cargo

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


            <form id="formCargo">

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="cargoId"
                        name="id">


                    <!-- =================================
                         IDENTIFICAÇÃO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Identificação do Cargo
                            </h5>

                        </div>


                        <!-- CÓDIGO -->

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="cargoCodigo">

                                    Código

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="cargoCodigo"
                                    name="codigo"
                                    maxlength="20"
                                    autocomplete="off"
                                    placeholder="Ex.: TEC-RH"
                                    required>

                                <small class="form-text text-muted">

                                    Utilize letras, números, ponto,
                                    hífen ou sublinhado.

                                </small>

                            </div>

                        </div>


                        <!-- NOME -->

                        <div class="col-lg-5 col-md-6">

                            <div class="form-group">

                                <label for="cargoNome">

                                    Nome

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="cargoNome"
                                    name="nome"
                                    maxlength="150"
                                    autocomplete="off"
                                    placeholder="Ex.: Técnico de Recursos Humanos"
                                    required>

                            </div>

                        </div>


                        <!-- NÍVEL HIERÁRQUICO -->

                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="cargoNivelHierarquico">

                                    Nível

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="cargoNivelHierarquico"
                                    name="nivel_hierarquico"
                                    min="1"
                                    max="255"
                                    value="1"
                                    required>

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="col-lg-2 col-md-6">

                            <div class="form-group">

                                <label for="cargoStatusId">

                                    Estado

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select
                                    class="form-control"
                                    id="cargoStatusId"
                                    name="status_id"
                                    required>

                                    <option value="">
                                        Selecione
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- DEPARTAMENTO -->

                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="cargoDepartamentoId">
                                    Departamento
                                </label>

                                <select
                                    class="form-control"
                                    id="cargoDepartamentoId"
                                    name="departamento_id">

                                    <option value="">
                                        Sem departamento específico
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- CENTRO DE CUSTO -->

                        <div class="col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="cargoCentroCustoId">
                                    Centro de Custo
                                </label>

                                <select
                                    class="form-control"
                                    id="cargoCentroCustoId"
                                    name="centro_custo_id">

                                    <option value="">
                                        Sem centro de custo
                                    </option>

                                </select>

                                <small class="form-text text-muted">

                                    Pode ser sugerido automaticamente
                                    pelo departamento selecionado.

                                </small>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         REMUNERAÇÃO E HORÁRIO
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Remuneração e Horário
                            </h5>

                        </div>


                        <!-- SALÁRIO MÍNIMO -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="cargoSalarioMinimo">
                                    Salário Mínimo
                                </label>

                                <div class="input-group">

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="cargoSalarioMinimo"
                                        name="salario_minimo"
                                        inputmode="decimal"
                                        autocomplete="off"
                                        placeholder="0,00">

                                    <div class="input-group-append">

                                        <span class="input-group-text">
                                            MZN
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- SALÁRIO MÁXIMO -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="cargoSalarioMaximo">
                                    Salário Máximo
                                </label>

                                <div class="input-group">

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="cargoSalarioMaximo"
                                        name="salario_maximo"
                                        inputmode="decimal"
                                        autocomplete="off"
                                        placeholder="0,00">

                                    <div class="input-group-append">

                                        <span class="input-group-text">
                                            MZN
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- CARGA HORÁRIA -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="cargoCargaHorariaSemanal">
                                    Carga Horária Semanal
                                </label>

                                <div class="input-group">

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="cargoCargaHorariaSemanal"
                                        name="carga_horaria_semanal"
                                        inputmode="decimal"
                                        autocomplete="off"
                                        value="40"
                                        placeholder="40">

                                    <div class="input-group-append">

                                        <span class="input-group-text">
                                            horas
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- CHEFIA -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label class="d-block">
                                    Classificação
                                </label>

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="cargoCargoChefia"
                                        name="cargo_chefia"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="cargoCargoChefia">

                                        Este é um cargo de chefia

                                    </label>

                                </div>

                            </div>

                        </div>


                        <!-- HORAS EXTRAS -->

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label class="d-block">
                                    Horas Extraordinárias
                                </label>

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="cargoPermiteHorasExtras"
                                        name="permite_horas_extras"
                                        value="1"
                                        checked>

                                    <label
                                        class="form-check-label"
                                        for="cargoPermiteHorasExtras">

                                        Permitir horas extras

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <!-- =================================
                         DESCRIÇÃO FUNCIONAL
                    ================================== -->

                    <div class="row">

                        <div class="col-12">

                            <h5 class="mb-3">
                                Descrição Funcional
                            </h5>

                        </div>


                        <!-- DESCRIÇÃO -->

                        <div class="col-12">

                            <div class="form-group">

                                <label for="cargoDescricao">
                                    Descrição
                                </label>

                                <textarea
                                    class="form-control"
                                    id="cargoDescricao"
                                    name="descricao"
                                    rows="3"
                                    placeholder="Resumo da função e objetivo principal do cargo"></textarea>

                            </div>

                        </div>


                        <!-- RESPONSABILIDADES -->

                        <div class="col-lg-6">

                            <div class="form-group mb-lg-0">

                                <label for="cargoResponsabilidades">
                                    Responsabilidades
                                </label>

                                <textarea
                                    class="form-control"
                                    id="cargoResponsabilidades"
                                    name="responsabilidades"
                                    rows="6"
                                    placeholder="Principais tarefas, deveres e responsabilidades"></textarea>

                            </div>

                        </div>


                        <!-- REQUISITOS -->

                        <div class="col-lg-6">

                            <div class="form-group mb-0">

                                <label for="cargoRequisitos">
                                    Requisitos
                                </label>

                                <textarea
                                    class="form-control"
                                    id="cargoRequisitos"
                                    name="requisitos"
                                    rows="6"
                                    placeholder="Formação, experiência, competências e outros requisitos"></textarea>

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
                        id="btnGuardarCargo">

                        <span
                            id="btnGuardarCargoSpinner"
                            class="spinner-border spinner-border-sm mr-2"
                            role="status"
                            aria-hidden="true"
                            style="display:none;">
                        </span>

                        <span id="btnGuardarCargoTexto">
                            Guardar Cargo
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
<!-- Modal Importação Excel de Cargos -->
<div
    class="modal fade"
    id="modalImportarCargo"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-file-excel-o mr-2"></i>
                    Importar Cargos por Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="cargoImportacaoErro" class="alert alert-danger d-none"></div>
                <div id="cargoImportacaoSucesso" class="alert alert-success d-none"></div>

                <!-- PASSO 1 -->
                <div id="cargoImportacaoPasso1">
                    <div class="alert alert-info">
                        <strong>Como funciona?</strong>
                        <ol class="mb-0 mt-2">
                            <li>Baixe o modelo Excel.</li>
                            <li>Preencha o código, nome, departamento, nível, salários e demais dados.</li>
                            <li>Selecione o ficheiro.</li>
                            <li>Clique em <strong>Validar Excel</strong>.</li>
                            <li>Confira os erros e registos válidos.</li>
                            <li>Confirme a importação.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-success" id="btnModeloExcelCargo">
                            <i class="fa fa-download mr-1"></i> Baixar modelo Excel
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="cargoFicheiroExcel">
                            Ficheiro Excel <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control-file" id="cargoFicheiroExcel" accept=".xlsx,.xls">
                        <small class="form-text text-muted">Formatos permitidos: XLSX e XLS.</small>
                    </div>

                    <button type="button" class="btn btn-primary" id="btnValidarExcelCargo">
                        <i class="fa fa-check mr-1"></i> Validar Excel
                    </button>
                </div>

                <!-- PASSO 2 -->
                <div id="cargoImportacaoPasso2" class="d-none">
                    <div id="cargoImportacaoErrosContainer" class="d-none mb-3">
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
                                <tbody id="cargoImportacaoErros"></tbody>
                            </table>
                        </div>
                    </div>

                    <div id="cargoImportacaoValidosContainer" class="mt-4">
                        <h5 class="text-success"><i class="fa fa-check-circle mr-1"></i> Cargos prontos para importar</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Linha</th>
                                        <th>Código</th>
                                        <th>Nome</th>
                                        <th>Departamento</th>
                                        <th>Nível</th>
                                        <th>Chefia</th>
                                    </tr>
                                </thead>
                                <tbody id="cargoImportacaoValidos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary d-none" id="btnVoltarImportacaoCargo">
                    <i class="fa fa-arrow-left mr-1"></i> Voltar
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success d-none" id="btnConfirmarImportacaoCargo">
                    <i class="fa fa-upload mr-1"></i> Importar Cargos Válidos
                </button>
            </div>
        </div>
    </div>
</div>