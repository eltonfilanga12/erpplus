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
                        Abertura do Exercício
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
                            Abertura do Exercício
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <!-- =========================================
             AVISO
        ========================================== -->

        <div class="alert alert-info">

            <div class="d-flex align-items-start">

                <i class="fa fa-info-circle mr-3 mt-1"></i>

                <div>

                    <strong>
                        Transporte de saldos patrimoniais
                    </strong>

                    <div class="mt-1">

                        A abertura do exercício transporta os saldos
                        das contas patrimoniais do exercício encerrado
                        para o exercício seguinte.

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
                    Selecionar exercício encerrado
                </h4>

            </div>

            <div class="card-body">

                <form id="formAberturaExercicio">

                    <div class="row align-items-end">

                        <div class="col-lg-8 col-md-7">

                            <div class="form-group mb-md-0">

                                <label for="aberturaExercicioOrigemId">
                                    Exercício de origem
                                </label>

                                <select
                                    class="form-control"
                                    id="aberturaExercicioOrigemId"
                                    name="exercicio_origem_id"
                                    required>

                                    <option value="">
                                        Selecione o exercício encerrado
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-5">

                            <button
                                type="submit"
                                class="btn btn-primary btn-block"
                                id="btnDiagnosticarAbertura"
                                disabled>

                                <i class="fa fa-search mr-1"></i>

                                Verificar abertura

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
            id="aberturaExercicioCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status"
                aria-hidden="true">
            </span>

            A processar os saldos de abertura...

        </div>


        <!-- =========================================
             PAINEL DE DIAGNÓSTICO
        ========================================== -->

        <div
            id="painelDiagnosticoAbertura"
            style="display:none;">


            <!-- =====================================
                 ESTADO GERAL
            ====================================== -->

            <div
                id="estadoGeralAbertura"
                class="alert"
                style="display:none;">
            </div>


            <!-- =====================================
                 EXERCÍCIOS
            ====================================== -->

            <div class="row">

                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Exercício de origem
                            </h4>

                        </div>

                        <div class="card-body">

                            <div class="row">

                                <div class="col-md-4 mb-3">

                                    <span class="text-muted">
                                        Código
                                    </span>

                                    <h5
                                        class="mt-1 mb-0"
                                        id="aberturaCodigoOrigem">

                                        -

                                    </h5>

                                </div>


                                <div class="col-md-5 mb-3">

                                    <span class="text-muted">
                                        Descrição
                                    </span>

                                    <h5
                                        class="mt-1 mb-0"
                                        id="aberturaDescricaoOrigem">

                                        -

                                    </h5>

                                </div>


                                <div class="col-md-3 mb-3">

                                    <span class="text-muted">
                                        Estado
                                    </span>

                                    <h5
                                        class="mt-1 mb-0"
                                        id="aberturaStatusOrigem">

                                        -

                                    </h5>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-lg-6">

                    <div class="card">

                        <div class="card-header">

                            <h4 class="card-title mb-0">
                                Exercício de destino
                            </h4>

                        </div>

                        <div class="card-body">

                            <div class="row">

                                <div class="col-md-3 mb-3">

                                    <span class="text-muted">
                                        Código
                                    </span>

                                    <h5
                                        class="mt-1 mb-0"
                                        id="aberturaCodigoDestino">

                                        -

                                    </h5>

                                </div>


                                <div class="col-md-4 mb-3">

                                    <span class="text-muted">
                                        Descrição
                                    </span>

                                    <h5
                                        class="mt-1 mb-0"
                                        id="aberturaDescricaoDestino">

                                        -

                                    </h5>

                                </div>


                                <div class="col-md-3 mb-3">

                                    <span class="text-muted">
                                        Estado
                                    </span>

                                    <h5
                                        class="mt-1 mb-0"
                                        id="aberturaStatusDestino">

                                        -

                                    </h5>

                                </div>


                                <div class="col-md-2 mb-3">

                                    <span class="text-muted">
                                        Início
                                    </span>

                                    <h5
                                        class="mt-1 mb-0"
                                        id="aberturaDataDestino">

                                        -

                                    </h5>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 RESUMO DOS SALDOS
            ====================================== -->

            <div class="row">

                <div class="col-xl-2 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Contas
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="aberturaQuantidadeContas">

                                0

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-2 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Total a débito
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="aberturaTotalDebito">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-2 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Total a crédito
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="aberturaTotalCredito">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-2 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Diferença
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="aberturaDiferenca">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-4 col-sm-12 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Resultado a transportar
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="aberturaResultadoTransportar">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 CONFIGURAÇÕES
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Configurações da abertura
                    </h4>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered mb-0">

                            <tbody>

                                <tr>

                                    <td>
                                        Conta Resultados Transitados
                                    </td>

                                    <td class="text-right">

                                        <span
                                            class="badge badge-danger"
                                            id="aberturaContaResultadosConfigurada">

                                            Não configurada

                                        </span>

                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Diário de Abertura
                                    </td>

                                    <td class="text-right">

                                        <span
                                            class="badge badge-danger"
                                            id="aberturaDiarioConfigurado">

                                            Não configurado

                                        </span>

                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Lançamento de abertura
                                    </td>

                                    <td
                                        class="text-right"
                                        id="aberturaLancamentoExistente">

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
                 TABELA DE SALDOS
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Saldos patrimoniais a transportar
                    </h4>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table
                            class="table table-striped table-hover table-bordered"
                            id="tabelaSaldosAbertura">

                            <thead>

                                <tr>

                                    <th>
                                        Código
                                    </th>

                                    <th>
                                        Conta
                                    </th>

                                    <th>
                                        Grupo
                                    </th>

                                    <th class="text-right">
                                        Débito
                                    </th>

                                    <th class="text-right">
                                        Crédito
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                              

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 AVISO DE GERAÇÃO
            ====================================== -->

            <div
                class="alert alert-warning"
                id="avisoGerarAbertura"
                style="display:none;">

                <div class="d-flex align-items-start">

                    <i class="fa fa-exchange mr-3 mt-1"></i>

                    <div>

                        <strong>
                            A abertura está pronta para ser gerada.
                        </strong>

                        <div class="mt-1">

                            Os saldos patrimoniais serão registados
                            no primeiro período do exercício seguinte.

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 BOTÃO
            ====================================== -->

            <div class="card">

                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <h4 class="mb-1">
                                Gerar lançamento de abertura
                            </h4>

                            <p class="text-muted mb-md-0">

                                O lançamento transportará os saldos
                                das contas de ativo, passivo e capital
                                próprio para o novo exercício.

                            </p>

                        </div>


                        <div class="col-md-4 text-md-right mt-3 mt-md-0">

                            <button
                                type="button"
                                class="btn btn-warning"
                                id="btnGerarAbertura"
                                disabled>

                                <i class="fa fa-exchange mr-1"></i>

                                Gerar Abertura

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>