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
                        Razão Geral
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
                            Razão Geral
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="btnAtualizarRazao">

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
                    Filtros do Razão Geral
                </h4>

            </div>

            <div class="card-body">

                <form id="formFiltroRazao">

                    <div class="row align-items-end">

                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="razaoDataInicio">
                                    Data inicial
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="razaoDataInicio"
                                    name="data_inicio"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="razaoDataFim">
                                    Data final
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="razaoDataFim"
                                    name="data_fim"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="razaoPlanoContasId">
                                    Plano de Contas
                                </label>

                                <select
                                    class="form-control"
                                    id="razaoPlanoContasId"
                                    name="plano_contas_id">

                                    <option value="">
                                        Todos os planos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-3 col-md-6">

                            <div class="form-group">

                                <label for="razaoFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="razaoFilialId"
                                    name="filial_id">

                                    <option value="">
                                        Todas as filiais
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-lg-8 col-md-8">

                            <div class="form-group">

                                <label for="razaoContaContabilId">
                                    Conta contabilística
                                </label>

                                <select
                                    class="form-control"
                                    id="razaoContaContabilId"
                                    name="conta_contabil_id"
                                    required>

                                    <option value="">
                                        Selecione a conta
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-4 text-right">

                            <div class="form-group">

                                <label>&nbsp;</label>

                                <div>

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary mr-2"
                                        id="btnLimparRazao">

                                        <i class="fa fa-eraser"></i>

                                        Limpar

                                    </button>

                                    <button
                                        type="submit"
                                        class="btn btn-primary">

                                        <i class="fa fa-search"></i>

                                        Gerar Razão

                                    </button>

                                </div>

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
            id="razaoCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2">
            </span>

            A gerar Razão geral...

        </div>


        <!-- =========================================
             CABEÇALHO DA CONTA
        ========================================== -->

        <div
            class="card"
            id="cabecalhoContaRazao"
            style="display:none;">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">

                        <small class="text-muted">
                            Código
                        </small>

                        <h5
                            class="mb-0"
                            id="razaoContaCodigo">

                            -

                        </h5>

                    </div>


                    <div class="col-md-4">

                        <small class="text-muted">
                            Conta
                        </small>

                        <h5
                            class="mb-0"
                            id="razaoContaNome">

                            -

                        </h5>

                    </div>


                    <div class="col-md-3">

                        <small class="text-muted">
                            Grupo
                        </small>

                        <h5
                            class="mb-0"
                            id="razaoContaGrupo">

                            -

                        </h5>

                    </div>


                    <div class="col-md-2">

                        <small class="text-muted">
                            Natureza
                        </small>

                        <h5
                            class="mb-0"
                            id="razaoContaNatureza">

                            -

                        </h5>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             RESUMO
        ========================================== -->

        <div class="row">

            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Saldo inicial devedor
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="razaoSaldoInicialDevedor">

                            0,00 MZN

                        </h5>

                    </div>

                </div>

            </div>


            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Saldo inicial credor
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="razaoSaldoInicialCredor">

                            0,00 MZN

                        </h5>

                    </div>

                </div>

            </div>


            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Total de débitos
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="razaoTotalDebitos">

                            0,00 MZN

                        </h5>

                    </div>

                </div>

            </div>


            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Total de créditos
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="razaoTotalCreditos">

                            0,00 MZN

                        </h5>

                    </div>

                </div>

            </div>


            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Saldo final devedor
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="razaoSaldoFinalDevedor">

                            0,00 MZN

                        </h5>

                    </div>

                </div>

            </div>


            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Saldo final credor
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="razaoSaldoFinalCredor">

                            0,00 MZN

                        </h5>

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
                            Movimentos da Conta
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
                        id="tabelaRazao">

                        <thead>

                            <tr>

                                <th>
                                    Data
                                </th>

                                <th>
                                    Diário
                                </th>

                                <th>
                                    Documento
                                </th>

                                <th>
                                    Descrição
                                </th>

                                <th>
                                    Origem
                                </th>

                                <th>
                                    Entidade
                                </th>

                                <th>
                                    Centro de custo
                                </th>

                                <th class="text-right">
                                    Débito
                                </th>

                                <th class="text-right">
                                    Crédito
                                </th>

                                <th class="text-right">
                                    Saldo
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            

                        </tbody>

                        <tfoot>

                            <tr class="font-weight-bold">

                                <td colspan="7">
                                    Totais do período
                                </td>

                                <td
                                    class="text-right"
                                    id="razaoRodapeDebitos">

                                    0,00 MZN

                                </td>

                                <td
                                    class="text-right"
                                    id="razaoRodapeCreditos">

                                    0,00 MZN

                                </td>

                                <td
                                    class="text-right"
                                    id="razaoRodapeSaldo">

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