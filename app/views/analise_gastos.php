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
                        Análise de Gastos
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
                            Análise de Gastos
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="btnAtualizarAnaliseGastos">

                        <i class="fa fa-refresh mr-1"></i>
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
                    Filtros da Análise
                </h4>

            </div>

            <div class="card-body">

                <form id="formFiltroAnaliseGastos">

                    <div class="row">

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="gastosDataInicial">
                                    Data inicial
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="gastosDataInicial"
                                    name="data_inicial"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="gastosDataFinal">
                                    Data final
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="gastosDataFinal"
                                    name="data_final"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="gastosPlanoContasId">
                                    Plano de Contas
                                </label>

                                <select
                                    class="form-control"
                                    id="gastosPlanoContasId"
                                    name="plano_contas_id">

                                    <option value="">
                                        Todos os planos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="gastosFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="gastosFilialId"
                                    name="filial_id">

                                    <option value="">
                                        Todas as filiais
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <div class="row align-items-end">

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="gastosCentroCustoId">
                                    Centro de Custo
                                </label>

                                <select
                                    class="form-control"
                                    id="gastosCentroCustoId"
                                    name="centro_custo_id">

                                    <option value="">
                                        Todos os centros de custo
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <div class="custom-control custom-checkbox mt-4">

                                    <input
                                        type="checkbox"
                                        class="custom-control-input"
                                        id="gastosCompararAnterior"
                                        name="comparar_periodo_anterior"
                                        checked>

                                    <label
                                        class="custom-control-label"
                                        for="gastosCompararAnterior">

                                        Comparar com período anterior

                                    </label>

                                </div>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-12 text-right">

                            <div class="form-group">

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary mr-2"
                                    id="btnLimparAnaliseGastos">

                                    <i class="fa fa-eraser mr-1"></i>
                                    Limpar

                                </button>

                                <button
                                    type="submit"
                                    class="btn btn-primary">

                                    <i class="fa fa-search mr-1"></i>
                                    Gerar Análise

                                </button>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        <!-- =========================================
             CARREGAMENTO
        ========================================== -->

        <div
            id="gastosCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2">
            </span>

            A gerar análise de gastos...

        </div>


        <!-- =========================================
             RESUMO PRINCIPAL
        ========================================== -->

        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Total de Gastos
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="gastosTotal">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Custo dos Inventários
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="gastosCustoMercadorias">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Gastos com Pessoal
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="gastosPessoal">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Fornecimentos e Serviços
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="gastosFornecimentos">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             RESUMO SECUNDÁRIO
        ========================================== -->

        <div class="row">

            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Outros Gastos Não Classificados
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="gastosOutros">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Maior Gasto
                        </span>

                        <h6
                            class="mt-2 mb-1"
                            id="gastosMaiorConta">

                            Sem movimentos

                        </h6>

                        <h4
                            class="mb-0"
                            id="gastosMaiorValor">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-4 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Variação vs. Período Anterior
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="gastosVariacao">

                            —

                        </h4>

                    </div>

                </div>

            </div>

        </div>

        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Amortizações
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="gastosAmortizacoes">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Impostos e Taxas
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="gastosImpostosTaxas">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Gastos Financeiros
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="gastosFinanceiros">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Outros Gastos Operacionais
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="gastosOutrosOperacionais">

                            0,00 MZN

                        </h4>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             COMPARAÇÃO
        ========================================== -->

        <div
            class="card"
            id="blocoComparacaoGastos"
            style="display:none;">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Comparação com Período Anterior
                </h4>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-lg-3 col-md-6">

                        <div class="border rounded p-3">

                            <span class="text-muted">
                                Período Atual
                            </span>

                            <h5
                                class="mt-2 mb-0"
                                id="gastosPeriodoAtual">

                                0,00 MZN

                            </h5>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="border rounded p-3">

                            <span class="text-muted">
                                Período Anterior
                            </span>

                            <h5
                                class="mt-2 mb-0"
                                id="gastosPeriodoAnterior">

                                0,00 MZN

                            </h5>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="border rounded p-3">

                            <span class="text-muted">
                                Variação em Valor
                            </span>

                            <h5
                                class="mt-2 mb-0"
                                id="gastosVariacaoValor">

                                0,00 MZN

                            </h5>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="border rounded p-3">

                            <span class="text-muted">
                                Variação Percentual
                            </span>

                            <h5
                                class="mt-2 mb-0"
                                id="gastosVariacaoPercentual">

                                —

                            </h5>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             DETALHAMENTO
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Gastos por Conta
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped table-hover table-bordered"
                        id="tabelaDetalhesGastos">

                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Conta
                                </th>

                                <th>
                                    Categoria
                                </th>

                                <th class="text-center">
                                    Movimentos
                                </th>

                                <th class="text-right">
                                    Valor
                                </th>

                                <th class="text-right">
                                    % do Total
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td
                                    colspan="6"
                                    class="text-center text-muted py-4">

                                    Nenhum gasto carregado.

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>