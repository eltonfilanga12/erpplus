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
                        Diário Geral
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>

                        <li class="breadcrumb-item">
                            Contabilidade
                        </li>

                        <li class="breadcrumb-item active">
                            Diário Geral
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="btnAtualizarDiarioGeral">

                        <i class="fa fa-refresh"></i>

                        Atualizar

                    </button>

                </div>

            </div>

        </div>


        <!-- =========================================
             FILTROS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Filtros do Diário Geral
                </h4>

            </div>

            <div class="card-body">

                <form id="formFiltroDiarioGeral">

                    <div class="row align-items-end">

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="diarioGeralDataInicio">
                                    Data inicial
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="diarioGeralDataInicio"
                                    name="data_inicio"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="diarioGeralDataFim">
                                    Data final
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="diarioGeralDataFim"
                                    name="data_fim"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="diarioGeralDiarioId">
                                    Diário
                                </label>

                                <select
                                    class="form-control"
                                    id="diarioGeralDiarioId"
                                    name="diario_id">

                                    <option value="">
                                        Todos os diários
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="diarioGeralFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="diarioGeralFilialId"
                                    name="filial_id">

                                    <option value="">
                                        Todas as filiais
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-12 text-right">

                            <button
                                type="button"
                                class="btn btn-outline-secondary mr-2"
                                id="btnLimparDiarioGeral">

                                <i class="fa fa-eraser"></i>

                                Limpar

                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary">

                                <i class="fa fa-search"></i>

                                Gerar Diário

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        <!-- =========================================
             CARREGAMENTO
        ========================================== -->

        <div
            id="diarioGeralCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2">
            </span>

            A gerar Diário Geral...

        </div>


        <!-- =========================================
             ESTADO
        ========================================== -->

        <div
            id="estadoDiarioGeral"
            class="alert"
            style="display:none;">
        </div>


        <!-- =========================================
             RESUMO
        ========================================== -->

        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Lançamentos
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="diarioGeralQuantidadeLancamentos">

                            0

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Total de débitos
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="diarioGeralTotalDebitos">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Total de créditos
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="diarioGeralTotalCreditos">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Diferença
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="diarioGeralDiferenca">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             LISTA DE LANÇAMENTOS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <div class="row align-items-center">

                    <div class="col">

                        <h4 class="card-title mb-0">
                            Lançamentos Contabilísticos
                        </h4>

                    </div>

                    <div class="col-auto">

                        <span class="text-muted">
                            Valores em MZN
                        </span>

                    </div>

                </div>

            </div>

            <div class="card-body">

                <div
                    id="listaLancamentosDiarioGeral">

                    <div class="text-center text-muted py-5">

                        Selecione o período para gerar o Diário Geral.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>