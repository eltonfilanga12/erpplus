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
                        Demonstração de Resultados
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
                            Demonstração de Resultados
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="btnAtualizarDre">

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
                    Filtros da Demonstração
                </h4>

            </div>

            <div class="card-body">

                <form id="formFiltroDre">

                    <div class="row align-items-end">

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="dreDataInicio">
                                    Data inicial
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="dreDataInicio"
                                    name="data_inicio"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="dreDataFim">
                                    Data final
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="dreDataFim"
                                    name="data_fim"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="drePlanoContasId">
                                    Plano de Contas
                                </label>

                                <select
                                    class="form-control"
                                    id="drePlanoContasId"
                                    name="plano_contas_id">

                                    <option value="">
                                        Todos os planos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="dreFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="dreFilialId"
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
                                id="btnLimparDre">

                                <i class="fa fa-eraser"></i>

                                Limpar

                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary">

                                <i class="fa fa-search"></i>

                                Gerar Demonstração

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
            id="dreCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2">
            </span>

            A gerar Demonstração de Resultados...

        </div>


        <!-- =========================================
             ESTADO DO RESULTADO
        ========================================== -->

        <div
            id="estadoResultadoDre"
            class="alert"
            style="display:none;">
        </div>


        <!-- =========================================
             RESUMO
        ========================================== -->

        <div class="row">

            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Total de receitas
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="dreTotalReceitas">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Total de custos
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="dreTotalCustos">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Resultado bruto
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="dreResultadoBruto">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Total de despesas
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="dreTotalDespesas">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Resultado operacional
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="dreResultadoOperacional">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Resultado líquido
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="dreResultadoLiquido">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             RECEITAS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Receitas
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped table-hover table-bordered"
                        id="tabelaReceitasDre">

                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Conta
                                </th>

                                <th class="text-right">
                                    Valor
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td
                                    colspan="3"
                                    class="text-center text-muted py-3">

                                    Nenhuma receita carregada.

                                </td>

                            </tr>

                        </tbody>

                        <tfoot>

                            <tr class="font-weight-bold">

                                <td colspan="2">
                                    Total de receitas
                                </td>

                                <td
                                    class="text-right"
                                    id="dreRodapeReceitas">

                                    0,00 MZN

                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        </div>


        <!-- =========================================
             CUSTOS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Custos
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped table-hover table-bordered"
                        id="tabelaCustosDre">

                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Conta
                                </th>

                                <th class="text-right">
                                    Valor
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td
                                    colspan="3"
                                    class="text-center text-muted py-3">

                                    Nenhum custo carregado.

                                </td>

                            </tr>

                        </tbody>

                        <tfoot>

                            <tr class="font-weight-bold">

                                <td colspan="2">
                                    Total de custos
                                </td>

                                <td
                                    class="text-right"
                                    id="dreRodapeCustos">

                                    0,00 MZN

                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        </div>


        <!-- =========================================
             DESPESAS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Despesas
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped table-hover table-bordered"
                        id="tabelaDespesasDre">

                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Conta
                                </th>

                                <th class="text-right">
                                    Valor
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td
                                    colspan="3"
                                    class="text-center text-muted py-3">

                                    Nenhuma despesa carregada.

                                </td>

                            </tr>

                        </tbody>

                        <tfoot>

                            <tr class="font-weight-bold">

                                <td colspan="2">
                                    Total de despesas
                                </td>

                                <td
                                    class="text-right"
                                    id="dreRodapeDespesas">

                                    0,00 MZN

                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        </div>


        <!-- =========================================
             RESULTADO FINAL
        ========================================== -->

        <div class="card">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered mb-0">

                        <tbody>

                            <tr>

                                <td>
                                    Total de receitas
                                </td>

                                <td
                                    class="text-right"
                                    id="dreResumoReceitas">

                                    0,00 MZN

                                </td>

                            </tr>

                            <tr>

                                <td>
                                    (-) Total de custos
                                </td>

                                <td
                                    class="text-right"
                                    id="dreResumoCustos">

                                    0,00 MZN

                                </td>

                            </tr>

                            <tr class="font-weight-bold">

                                <td>
                                    Resultado bruto
                                </td>

                                <td
                                    class="text-right"
                                    id="dreResumoResultadoBruto">

                                    0,00 MZN

                                </td>

                            </tr>

                            <tr>

                                <td>
                                    (-) Total de despesas
                                </td>

                                <td
                                    class="text-right"
                                    id="dreResumoDespesas">

                                    0,00 MZN

                                </td>

                            </tr>

                            <tr class="font-weight-bold">

                                <td>
                                    Resultado operacional
                                </td>

                                <td
                                    class="text-right"
                                    id="dreResumoResultadoOperacional">

                                    0,00 MZN

                                </td>

                            </tr>

                            <tr class="font-weight-bold">

                                <td>
                                    Resultado líquido do período
                                </td>

                                <td
                                    class="text-right"
                                    id="dreResumoResultadoLiquido">

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