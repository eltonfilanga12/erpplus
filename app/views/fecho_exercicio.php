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
                        Fecho do Exercício
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
                            Fecho do Exercício
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <!-- =========================================
             AVISO
        ========================================== -->

        <div class="alert alert-warning">

            <div class="d-flex align-items-start">

                <i class="fa fa-exclamation-triangle mr-3 mt-1"></i>

                <div>

                    <strong>
                        Operação contabilística sensível
                    </strong>

                    <div class="mt-1">

                        O fecho do exercício encerra os períodos,
                        apura o lucro ou prejuízo e impede novos
                        lançamentos contabilísticos no exercício.

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             SELEÇÃO DO EXERCÍCIO
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Selecionar exercício
                </h4>

            </div>

            <div class="card-body">

                <form id="formFechoExercicio">

                    <div class="row align-items-end">

                        <div class="col-lg-8 col-md-7">

                            <div class="form-group mb-md-0">

                                <label for="fechoExercicioId">
                                    Exercício contabilístico
                                </label>

                                <select
                                    class="form-control"
                                    id="fechoExercicioId"
                                    name="exercicio_id"
                                    required>

                                    <option value="">
                                        Selecione o exercício
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-5">

                            <button
                                type="submit"
                                class="btn btn-primary btn-block"
                                id="btnDiagnosticarFecho"
                                disabled>

                                <i class="fa fa-search mr-1"></i>

                                Verificar exercício

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
            id="fechoExercicioCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status"
                aria-hidden="true">
            </span>

            A processar informações do exercício...

        </div>


        <!-- =========================================
             PAINEL DE DIAGNÓSTICO
        ========================================== -->

        <div
            id="painelDiagnosticoFecho"
            style="display:none;">


            <!-- =====================================
                 ESTADO GERAL
            ====================================== -->

            <div
                id="estadoGeralFecho"
                class="alert"
                style="display:none;">
            </div>


            <!-- =====================================
                 IDENTIFICAÇÃO DO EXERCÍCIO
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Dados do exercício
                    </h4>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-lg-3 col-md-6 mb-3">

                            <span class="text-muted">
                                Código
                            </span>

                            <h5
                                class="mt-1 mb-0"
                                id="fechoCodigoExercicio">

                                -

                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6 mb-3">

                            <span class="text-muted">
                                Descrição
                            </span>

                            <h5
                                class="mt-1 mb-0"
                                id="fechoDescricaoExercicio">

                                -

                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6 mb-3">

                            <span class="text-muted">
                                Período
                            </span>

                            <h5
                                class="mt-1 mb-0"
                                id="fechoPeriodoExercicio">

                                -

                            </h5>

                        </div>


                        <div class="col-lg-3 col-md-6 mb-3">

                            <span class="text-muted">
                                Estado
                            </span>

                            <h5
                                class="mt-1 mb-0"
                                id="fechoStatusExercicio">

                                -

                            </h5>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 RESULTADO DO EXERCÍCIO
            ====================================== -->

            <div class="row">

                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Rendimentos
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="fechoTotalRendimentos">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Gastos
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="fechoTotalGastos">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Resultado líquido
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="fechoResultadoLiquido">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Classificação
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="fechoTipoResultado">

                                -

                            </h4>

                        </div>

                    </div>

                </div>

            </div>


            <div
                id="estadoResultadoFecho"
                class="alert"
                style="display:none;">
            </div>


            <!-- =====================================
                 VALIDAÇÕES
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Validações do encerramento
                    </h4>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered mb-0">

                            <tbody>

                                <tr>

                                    <td>
                                        Lançamentos pendentes
                                    </td>

                                    <td
                                        class="text-right font-weight-bold"
                                        id="fechoLancamentosPendentes">

                                        0

                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Total de períodos
                                    </td>

                                    <td
                                        class="text-right font-weight-bold"
                                        id="fechoTotalPeriodos">

                                        0

                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Períodos abertos
                                    </td>

                                    <td
                                        class="text-right font-weight-bold"
                                        id="fechoPeriodosAbertos">

                                        0

                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Períodos fechados
                                    </td>

                                    <td
                                        class="text-right font-weight-bold"
                                        id="fechoPeriodosFechados">

                                        0

                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Conta Resultado do Exercício
                                    </td>

                                    <td class="text-right">

                                        <span
                                            class="badge badge-danger"
                                            id="fechoContaResultadoConfigurada">

                                            Não configurada

                                        </span>

                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Diário de Encerramento
                                    </td>

                                    <td class="text-right">

                                        <span
                                            class="badge badge-danger"
                                            id="fechoDiarioEncerramentoConfigurado">

                                            Não configurado

                                        </span>

                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Lançamento de encerramento
                                    </td>

                                    <td
                                        class="text-right"
                                        id="fechoLancamentoEncerramento">

                                        <span class="text-muted">
                                            Ainda não foi criado.
                                        </span>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 CONFIRMAÇÃO
            ====================================== -->

            <div
                class="alert alert-danger"
                id="fechoAvisoConfirmacao"
                style="display:none;">

                <div class="d-flex align-items-start">

                    <i class="fa fa-lock mr-3 mt-1"></i>

                    <div>

                        <strong>
                            O exercício está pronto para ser fechado.
                        </strong>

                        <div class="mt-1">

                            Depois do encerramento, os períodos serão
                            bloqueados e não será possível registar novos
                            lançamentos contabilísticos neste exercício.

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 BOTÃO DE FECHO
            ====================================== -->

            <div class="card">

                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <h4 class="mb-1">
                                Encerrar exercício contabilístico
                            </h4>

                            <p class="text-muted mb-md-0">

                                Confirme apenas depois de rever os
                                lançamentos, o Balancete, a Demonstração
                                de Resultados e o Balanço Patrimonial.

                            </p>

                        </div>


                        <div class="col-md-4 text-md-right mt-3 mt-md-0">

                            <button
                                type="button"
                                class="btn btn-danger"
                                id="btnFecharExercicio"
                                disabled>

                                <i class="fa fa-lock mr-1"></i>

                                Fechar Exercício

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>