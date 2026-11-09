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
                        Demonstração de Fluxos de Caixa
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
                            Demonstração de Fluxos de Caixa
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <!-- =========================================
             INFORMAÇÃO
        ========================================== -->

        <div class="alert alert-info">

            <div class="d-flex align-items-start">

                <i class="fa fa-info-circle mr-3 mt-1"></i>

                <div>

                    <strong>
                        Demonstração contabilística
                    </strong>

                    <div class="mt-1">

                        Este relatório apresenta os fluxos monetários
                        classificados em atividades operacionais,
                        de investimento e de financiamento.

                        Transferências internas entre Caixa e Bancos
                        não são consideradas entradas ou saídas da empresa.

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             FILTROS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Filtros do relatório
                </h4>

            </div>

            <div class="card-body">

                <form id="formDemonstracaoFluxoCaixa">

                    <div class="row">

                        <!-- PLANO DE CONTAS -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="dfcPlanoContasId">
                                    Plano de Contas
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    class="form-control"
                                    id="dfcPlanoContasId"
                                    name="plano_contas_id"
                                    required>

                                    <option value="">
                                        Selecione o plano de contas
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- EXERCÍCIO -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="dfcExercicioId">
                                    Exercício contabilístico
                                </label>

                                <select
                                    class="form-control"
                                    id="dfcExercicioId"
                                    name="exercicio_id">

                                    <option value="">
                                        Todos os exercícios
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- PERÍODO -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="dfcPeriodoId">
                                    Período contabilístico
                                </label>

                                <select
                                    class="form-control"
                                    id="dfcPeriodoId"
                                    name="periodo_id">

                                    <option value="">
                                        Todos os períodos
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- FILIAL -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="dfcFilialId">
                                    Filial
                                </label>

                                <select
                                    class="form-control"
                                    id="dfcFilialId"
                                    name="filial_id">

                                    <option value="">
                                        Todas as filiais
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- DATA INICIAL -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="dfcDataInicial">
                                    Data inicial
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="dfcDataInicial"
                                    name="data_inicial"
                                    required>

                            </div>

                        </div>


                        <!-- DATA FINAL -->

                        <div class="col-xl-4 col-lg-6 col-md-6">

                            <div class="form-group">

                                <label for="dfcDataFinal">
                                    Data final
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="dfcDataFinal"
                                    name="data_final"
                                    required>

                            </div>

                        </div>

                    </div>


                    <!-- BOTÕES -->

                    <div class="row">

                        <div class="col-12 text-right">

                            <button
                                type="button"
                                class="btn btn-light mr-2"
                                id="btnLimparDFC">

                                <i class="fa fa-eraser mr-1"></i>

                                Limpar

                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                                id="btnGerarDFC">

                                <i class="fa fa-search mr-1"></i>

                                Gerar Demonstração

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        <!-- =========================================
             CLASSIFICAÇÃO DAS CONTAS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Classificação das contas
                </h4>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-xl-3 col-sm-6 col-12">

                        <div class="border rounded p-3 text-center mb-3">

                            <span class="text-muted">
                                Operacionais
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcClassificacaoOperacional">

                                0

                            </h4>

                        </div>

                    </div>


                    <div class="col-xl-3 col-sm-6 col-12">

                        <div class="border rounded p-3 text-center mb-3">

                            <span class="text-muted">
                                Investimento
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcClassificacaoInvestimento">

                                0

                            </h4>

                        </div>

                    </div>


                    <div class="col-xl-3 col-sm-6 col-12">

                        <div class="border rounded p-3 text-center mb-3">

                            <span class="text-muted">
                                Financiamento
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcClassificacaoFinanciamento">

                                0

                            </h4>

                        </div>

                    </div>


                    <div class="col-xl-3 col-sm-6 col-12">

                        <div class="border rounded p-3 text-center mb-3">

                            <span class="text-muted">
                                Sem classificação direta
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcClassificacaoNenhum">

                                0

                            </h4>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             CARREGAMENTO
        ========================================== -->

        <div
            id="dfcCarregando"
            class="text-center py-4"
            style="display:none;">

            <span
                class="spinner-border spinner-border-sm mr-2"
                role="status"
                aria-hidden="true">
            </span>

            A gerar a Demonstração de Fluxos de Caixa...

        </div>


        <!-- =========================================
             RESULTADO
        ========================================== -->

        <div
            id="painelResultadoDFC"
            style="display:none;">

            <!-- CABEÇALHO DO RELATÓRIO -->

            <div class="card">

                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <h4 class="mb-1">
                                Demonstração de Fluxos de Caixa
                            </h4>

                            <div class="text-muted">

                                Período:

                                <strong id="dfcPeriodoRelatorio">
                                    -
                                </strong>

                            </div>

                        </div>


                        <div class="col-md-4 text-md-right mt-3 mt-md-0">

                            <button
                                type="button"
                                class="btn btn-light mr-1"
                                id="btnImprimirDFC"
                                disabled>

                                <i class="fa fa-print"></i>

                            </button>

                            <button
                                type="button"
                                class="btn btn-success mr-1"
                                id="btnExportarExcelDFC"
                                disabled>

                                <i class="fa fa-file-excel-o mr-1"></i>

                                Excel

                            </button>

                            <button
                                type="button"
                                class="btn btn-danger"
                                id="btnExportarPdfDFC"
                                disabled>

                                <i class="fa fa-file-pdf-o mr-1"></i>

                                PDF

                            </button>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 RESUMO PRINCIPAL
            ====================================== -->

            <div class="row">

                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Saldo inicial
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcSaldoInicial">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Fluxo operacional
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcFluxoOperacional">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Fluxo de investimento
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcFluxoInvestimento">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Fluxo de financiamento
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcFluxoFinanciamento">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>

            </div>


            <div class="row">

                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Variação líquida
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcVariacaoLiquida">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Saldo final calculado
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcSaldoFinalCalculado">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Saldo final contabilístico
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcSaldoFinalContabilistico">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>


                <div class="col-xl-3 col-sm-6 col-12">

                    <div class="card">

                        <div class="card-body text-center">

                            <span class="text-muted">
                                Diferença de conferência
                            </span>

                            <h4
                                class="mt-2 mb-0"
                                id="dfcDiferencaConferencia">

                                0,00 MZN

                            </h4>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 CONFERÊNCIA
            ====================================== -->

            <div
                id="estadoConferenciaDFC"
                class="alert"
                style="display:none;">
            </div>


            <!-- =====================================
                 GRÁFICO
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Fluxos líquidos por atividade
                    </h4>

                </div>

                <div class="card-body">

                    <div style="height:320px;">

                        <canvas id="graficoDemonstracaoFluxoCaixa">
                        </canvas>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 ATIVIDADES OPERACIONAIS
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Fluxos das Atividades Operacionais
                    </h4>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table
                            class="table table-bordered table-striped"
                            id="tabelaDFCOperacional">

                            <thead>

                                <tr>

                                    <th>
                                        Descrição
                                    </th>

                                    <th class="text-right">
                                        Entradas
                                    </th>

                                    <th class="text-right">
                                        Saídas
                                    </th>

                                    <th class="text-right">
                                        Fluxo líquido
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td
                                        colspan="4"
                                        class="text-center text-muted">

                                        Nenhum dado carregado.

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 ATIVIDADES DE INVESTIMENTO
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Fluxos das Atividades de Investimento
                    </h4>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table
                            class="table table-bordered table-striped"
                            id="tabelaDFCInvestimento">

                            <thead>

                                <tr>

                                    <th>
                                        Descrição
                                    </th>

                                    <th class="text-right">
                                        Entradas
                                    </th>

                                    <th class="text-right">
                                        Saídas
                                    </th>

                                    <th class="text-right">
                                        Fluxo líquido
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td
                                        colspan="4"
                                        class="text-center text-muted">

                                        Nenhum dado carregado.

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 ATIVIDADES DE FINANCIAMENTO
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Fluxos das Atividades de Financiamento
                    </h4>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table
                            class="table table-bordered table-striped"
                            id="tabelaDFCFinanciamento">

                            <thead>

                                <tr>

                                    <th>
                                        Descrição
                                    </th>

                                    <th class="text-right">
                                        Entradas
                                    </th>

                                    <th class="text-right">
                                        Saídas
                                    </th>

                                    <th class="text-right">
                                        Fluxo líquido
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td
                                        colspan="4"
                                        class="text-center text-muted">

                                        Nenhum dado carregado.

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 ESTATÍSTICAS
            ====================================== -->

            <div class="card">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Estatísticas do processamento
                    </h4>

                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-xl-3 col-sm-6 col-12">

                            <div class="border rounded p-3 text-center mb-3">

                                <span class="text-muted">
                                    Lançamentos analisados
                                </span>

                                <h4
                                    class="mt-2 mb-0"
                                    id="dfcQuantidadeLancamentos">

                                    0

                                </h4>

                            </div>

                        </div>


                        <div class="col-xl-3 col-sm-6 col-12">

                            <div class="border rounded p-3 text-center mb-3">

                                <span class="text-muted">
                                    Movimentos classificados
                                </span>

                                <h4
                                    class="mt-2 mb-0"
                                    id="dfcQuantidadeClassificados">

                                    0

                                </h4>

                            </div>

                        </div>


                        <div class="col-xl-3 col-sm-6 col-12">

                            <div class="border rounded p-3 text-center mb-3">

                                <span class="text-muted">
                                    Não classificados
                                </span>

                                <h4
                                    class="mt-2 mb-0"
                                    id="dfcQuantidadeNaoClassificados">

                                    0

                                </h4>

                            </div>

                        </div>


                        <div class="col-xl-3 col-sm-6 col-12">

                            <div class="border rounded p-3 text-center mb-3">

                                <span class="text-muted">
                                    Transferências internas
                                </span>

                                <h4
                                    class="mt-2 mb-0"
                                    id="dfcQuantidadeTransferencias">

                                    0

                                </h4>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 NÃO CLASSIFICADOS
            ====================================== -->

            <div
                class="card"
                id="painelDFCNaoClassificados"
                style="display:none;">

                <div class="card-header">

                    <h4 class="card-title mb-0 text-danger">

                        <i class="fa fa-exclamation-triangle mr-1"></i>

                        Movimentos monetários não classificados

                    </h4>

                </div>

                <div class="card-body">

                    <div class="alert alert-warning">

                        Estes movimentos possuem contrapartidas
                        sem classificação para a Demonstração de
                        Fluxos de Caixa.

                        Classifique as respetivas contas contabilísticas.

                    </div>

                    <div class="table-responsive">

                        <table
                            class="table table-bordered table-striped"
                            id="tabelaDFCNaoClassificados">

                            <thead>

                                <tr>

                                    <th>
                                        Data
                                    </th>

                                    <th>
                                        Lançamento
                                    </th>

                                    <th>
                                        Documento
                                    </th>

                                    <th>
                                        Descrição
                                    </th>

                                    <th>
                                        Contas
                                    </th>

                                    <th class="text-right">
                                        Valor
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
                 TRANSFERÊNCIAS INTERNAS
            ====================================== -->

            <div
                class="card"
                id="painelDFCTransferencias"
                style="display:none;">

                <div class="card-header">

                    <h4 class="card-title mb-0">
                        Transferências internas excluídas
                    </h4>

                </div>

                <div class="card-body">

                    <div class="alert alert-light border">

                        Estes movimentos representam transferências
                        entre Caixa e Bancos e, por isso, não alteram
                        o total das disponibilidades da empresa.

                    </div>

                    <div class="table-responsive">

                        <table
                            class="table table-bordered table-striped"
                            id="tabelaDFCTransferencias">

                            <thead>

                                <tr>

                                    <th>
                                        Data
                                    </th>

                                    <th>
                                        Lançamento
                                    </th>

                                    <th>
                                        Documento
                                    </th>

                                    <th>
                                        Descrição
                                    </th>

                                    <th class="text-right">
                                        Valor
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

</div>