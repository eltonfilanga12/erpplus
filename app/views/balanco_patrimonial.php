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
                        Balanço Patrimonial
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
                            Balanço Patrimonial
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary"
                        id="btnAtualizarBalanco">

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
                    Filtros do Balanço
                </h4>

            </div>

            <div class="card-body">

                <form id="formFiltroBalanco">

                    <div class="row align-items-end">

                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="balancoDataReferencia">
                                    Data de referência
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="balancoDataReferencia"
                                    name="data_referencia"
                                    required>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="balancoPlanoContasId">
                                    Plano de Contas
                                </label>

                                <select
                                    class="form-control"
                                    id="balancoPlanoContasId"
                                    name="plano_contas_id">

                                    <option value="">
                                        Todos os planos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6">

                            <div class="form-group">

                                <label for="balancoFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="balancoFilialId"
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
                                id="btnLimparBalanco">

                                <i class="fa fa-eraser"></i>

                                Limpar

                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary">

                                <i class="fa fa-search"></i>

                                Gerar Balanço

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
            id="balancoCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2">
            </span>

            A gerar Balanço Patrimonial...

        </div>


        <!-- =========================================
             ESTADO
        ========================================== -->

        <div
            id="estadoBalanco"
            class="alert"
            style="display:none;">
        </div>


        <!-- =========================================
             RESUMO
        ========================================== -->

        <div class="row">

            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Total do ativo
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="balancoTotalAtivo">

                            0,00 MZN

                        </h5>

                    </div>

                </div>

            </div>


            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Total do passivo
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="balancoTotalPassivo">

                            0,00 MZN

                        </h5>

                    </div>

                </div>

            </div>


            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Capital Próprio Total
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="balancoTotalCapital">

                            0,00 MZN

                        </h5>

                    </div>

                </div>

            </div>


            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Resultados
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="balancoTotalResultado">

                            0,00 MZN

                        </h5>

                    </div>

                </div>

            </div>


            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Passivo + capital
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="balancoTotalPassivoCapital">

                            0,00 MZN

                        </h5>

                    </div>

                </div>

            </div>


            <div class="col-xl-2 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <span class="text-muted">
                            Diferença
                        </span>

                        <h5
                            class="mt-2 mb-0"
                            id="balancoDiferenca">

                            0,00 MZN

                        </h5>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             ATIVOS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Ativo
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped table-hover table-bordered"
                        id="tabelaAtivosBalanco">

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

                                    Nenhuma conta de ativo carregada.

                                </td>

                            </tr>

                        </tbody>

                        <tfoot>

                            <tr class="font-weight-bold">

                                <td colspan="2">
                                    Total do ativo
                                </td>

                                <td
                                    class="text-right"
                                    id="balancoRodapeAtivo">

                                    0,00 MZN

                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        </div>


        <!-- =========================================
             PASSIVOS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Passivo
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped table-hover table-bordered"
                        id="tabelaPassivosBalanco">

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



                        </tbody>

                        <tfoot>

                            <tr class="font-weight-bold">

                                <td colspan="2">
                                    Total do passivo
                                </td>

                                <td
                                    class="text-right"
                                    id="balancoRodapePassivo">

                                    0,00 MZN

                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        </div>


        <!-- =========================================
             CAPITAL PRÓPRIO
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Capital Próprio
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped table-hover table-bordered"
                        id="tabelaCapitalBalanco">

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



                        </tbody>

                        <tfoot>

                            <tr class="font-weight-bold">

                                <td colspan="2">
                                    Total do capital próprio
                                </td>

                                <td
                                    class="text-right"
                                    id="balancoRodapeCapital">

                                    0,00 MZN

                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        </div>


        <!-- =========================================
             RESULTADOS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Resultados
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table table-striped table-hover table-bordered"
                        id="tabelaResultadosBalanco">

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



                        </tbody>

                        <tfoot>

                            <tr class="font-weight-bold">

                                <td colspan="2">
                                    Total dos resultados
                                </td>

                                <td
                                    class="text-right"
                                    id="balancoRodapeResultado">

                                    0,00 MZN

                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        </div>


        <!-- =========================================
             RESUMO FINAL
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Resumo do Balanço
                </h4>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered mb-0">

                        <tbody>

                            <tr>

                                <td>
                                    Total do ativo
                                </td>

                                <td
                                    class="text-right"
                                    id="balancoResumoAtivo">

                                    0,00 MZN

                                </td>

                            </tr>

                            <tr>

                                <td>
                                    Total do passivo
                                </td>

                                <td
                                    class="text-right"
                                    id="balancoResumoPassivo">

                                    0,00 MZN

                                </td>

                            </tr>

                            <tr>

                                <td>
                                    Capital próprio contabilizado
                                </td>

                                <td
                                    class="text-right"
                                    id="balancoResumoCapital">

                                    0,00 MZN

                                </td>

                            </tr>

                            <tr>

                                <td>
                                    Total dos resultados
                                </td>

                                <td
                                    class="text-right"
                                    id="balancoResumoResultado">

                                    0,00 MZN

                                </td>

                            </tr>

                            <tr class="font-weight-bold">

                                <td>
                                    Passivo + Capital Próprio + Resultados
                                </td>

                                <td
                                    class="text-right"
                                    id="balancoResumoPassivoCapital">

                                    0,00 MZN

                                </td>

                            </tr>

                            <tr class="font-weight-bold">

                                <td>
                                    Diferença
                                </td>

                                <td
                                    class="text-right"
                                    id="balancoResumoDiferenca">

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