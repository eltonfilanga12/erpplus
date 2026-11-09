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
                        Balancete
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
                            Balancete
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="btnAtualizarBalancete">

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
                    Filtros do Balancete
                </h4>

            </div>

            <div class="card-body">

                <form id="formFiltroBalancete">

                    <div class="row align-items-end">

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="balanceteDataInicio">
                                    Data inicial
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="balanceteDataInicio"
                                    name="data_inicio"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="balanceteDataFim">
                                    Data final
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="balanceteDataFim"
                                    name="data_fim"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="balancetePlanoContasId">
                                    Plano de Contas
                                </label>

                                <select
                                    class="form-control"
                                    id="balancetePlanoContasId"
                                    name="plano_contas_id">

                                    <option value="">
                                        Todos os planos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="balanceteFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="balanceteFilialId"
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
                                id="btnLimparBalancete">

                                <i class="fa fa-eraser"></i>

                                Limpar

                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary">

                                <i class="fa fa-search"></i>

                                Gerar Balancete

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
            id="balanceteCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2">
            </span>

            A gerar Balancete...

        </div>


        <!-- =========================================
             ESTADO
        ========================================== -->

        <div
            id="estadoBalancete"
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
                            Débitos do período
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="resumoDebitosBalancete">
                            0,00 MZN
                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Créditos do período
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="resumoCreditosBalancete">
                            0,00 MZN
                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Saldo final devedor
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="resumoSaldoFinalDevedor">
                            0,00 MZN
                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Saldo final credor
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="resumoSaldoFinalCredor">
                            0,00 MZN
                        </h4>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             TABELA
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <div class="row align-items-center">

                    <div class="col">

                        <h4 class="card-title mb-0">
                            Balancete de Verificação
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

                <div class="table-responsive">

                    <table
                        class="table table-striped table-hover table-bordered"
                        id="tabelaBalancete">

                        <thead>

                            <tr>

                                <th rowspan="2">
                                    Código
                                </th>

                                <th rowspan="2">
                                    Conta
                                </th>

                                <th rowspan="2">
                                    Grupo
                                </th>

                                <th
                                    colspan="2"
                                    class="text-center">

                                    Saldo Inicial

                                </th>

                                <th
                                    colspan="2"
                                    class="text-center">

                                    Movimento do Período

                                </th>

                                <th
                                    colspan="2"
                                    class="text-center">

                                    Saldo Final

                                </th>

                            </tr>

                            <tr>

                                <th class="text-right">
                                    Devedor
                                </th>

                                <th class="text-right">
                                    Credor
                                </th>

                                <th class="text-right">
                                    Débitos
                                </th>

                                <th class="text-right">
                                    Créditos
                                </th>

                                <th class="text-right">
                                    Devedor
                                </th>

                                <th class="text-right">
                                    Credor
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                           

                        </tbody>

                        <tfoot>

                            <tr class="font-weight-bold">

                                <td colspan="3">
                                    Totais
                                </td>

                                <td
                                    class="text-right"
                                    id="totalSaldoInicialDevedor">

                                    0,00 MZN

                                </td>

                                <td
                                    class="text-right"
                                    id="totalSaldoInicialCredor">

                                    0,00 MZN

                                </td>

                                <td
                                    class="text-right"
                                    id="totalDebitosBalancete">

                                    0,00 MZN

                                </td>

                                <td
                                    class="text-right"
                                    id="totalCreditosBalancete">

                                    0,00 MZN

                                </td>

                                <td
                                    class="text-right"
                                    id="totalSaldoFinalDevedor">

                                    0,00 MZN

                                </td>

                                <td
                                    class="text-right"
                                    id="totalSaldoFinalCredor">

                                    0,00 MZN

                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>