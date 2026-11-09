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
                        Saldos de Abertura
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
                            Saldos de Abertura
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovoSaldoAbertura">

                        <i class="fa fa-plus mr-1"></i>
                        Novo Saldo de Abertura

                    </button>

                </div>

            </div>

        </div>


        <!-- =========================================
             INFORMAÇÃO
        ========================================== -->

        <div class="alert alert-info">

            <strong>
                Saldos de Abertura
            </strong>

            <br>

            Utilize esta área para registar a posição
            contabilística inicial de uma empresa que já
            possuía caixa, bancos, inventários, clientes,
            fornecedores, ativos, capital ou outros saldos
            antes de começar a utilizar o ERPPlus.

        </div>


        <!-- =========================================
             LISTA DE ABERTURAS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">
                    Aberturas Registadas
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
                                    ID
                                </th>

                                <th>
                                    Data
                                </th>

                                <th>
                                    Exercício
                                </th>

                                <th>
                                    Plano
                                </th>

                                <th>
                                    Filial
                                </th>

                                <th class="text-right">
                                    Débito
                                </th>

                                <th class="text-right">
                                    Crédito
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th class="text-center">
                                    Ações
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td
                                    colspan="9"
                                    class="text-center text-muted py-4">

                                    A carregar...

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- ==================================================
     MODAL SALDO DE ABERTURA
================================================== -->

<div
    class="modal fade"
    id="modalSaldoAbertura"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl modal-dialog-scrollable"
        role="document">

        <div class="modal-content">

            <!-- ======================================
                 HEADER
            ======================================= -->

            <div class="modal-header">

                <h5 class="modal-title">
                    Saldo de Abertura
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


            <!-- ======================================
                 BODY
            ======================================= -->

            <div class="modal-body">

                <input
                    type="hidden"
                    id="saldoAberturaId">


                <!-- ==================================
                     CABEÇALHO
                =================================== -->

                <div class="card">

                    <div class="card-header">

                        <h5 class="card-title mb-0">
                            Dados da Abertura
                        </h5>

                    </div>

                    <div class="card-body">

                        <div class="row">


                            <!-- EXERCÍCIO -->

                            <div class="col-lg-3 col-md-6">

                                <div class="form-group">

                                    <label for="saldoAberturaExercicioId">
                                        Exercício contabilístico
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="saldoAberturaExercicioId">

                                        <option value="">
                                            Selecione
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <!-- DATA -->

                            <div class="col-lg-3 col-md-6">

                                <div class="form-group">

                                    <label for="saldoAberturaData">
                                        Data de abertura
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="saldoAberturaData">

                                </div>

                            </div>


                            <!-- PLANO -->

                            <div class="col-lg-3 col-md-6">

                                <div class="form-group">

                                    <label for="saldoAberturaPlanoContasId">
                                        Plano de Contas
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="saldoAberturaPlanoContasId">

                                        <option value="">
                                            Selecione
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <!-- FILIAL -->

                            <div class="col-lg-3 col-md-6">

                                <div class="form-group">

                                    <label for="saldoAberturaFilialId">
                                        Filial
                                    </label>

                                    <select
                                        class="form-control"
                                        id="saldoAberturaFilialId">

                                        <option value="">
                                            Geral
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>


                        <div class="row">

                            <div class="col-12">

                                <div class="form-group">

                                    <label for="saldoAberturaDescricao">
                                        Descrição
                                    </label>

                                    <textarea
                                        class="form-control"
                                        id="saldoAberturaDescricao"
                                        rows="2"
                                        placeholder="Ex.: Saldos iniciais da empresa na entrada em funcionamento do ERPPlus"></textarea>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ==================================
                     AÇÕES DOS ITENS
                =================================== -->

                <div class="row mb-3">

                    <div class="col-md-6">

                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            id="btnAdicionarContaAbertura">

                            <i class="fa fa-plus mr-1"></i>
                            Adicionar Outro Saldo

                        </button>

                    </div>

                    <div class="col-md-6 text-right">

                        <button
                            type="button"
                            class="btn btn-outline-info"
                            id="btnImportarSaldosAbertura">

                            <i class="fa fa-download mr-1"></i>
                            Importar Saldos

                        </button>

                    </div>

                </div>


                <!-- ==================================
                     ITENS
                =================================== -->

                <div class="card">

                    <div class="alert alert-light border mb-3">

                        <strong>
                            Como preencher:
                        </strong>

                        <div class="mt-2">

                            <span class="mr-3">
                                <strong>Ativos:</strong>
                                normalmente a Débito
                            </span>

                            <span class="mr-3">
                                <strong>Passivos:</strong>
                                normalmente a Crédito
                            </span>

                            <span>
                                <strong>Capital próprio:</strong>
                                normalmente a Crédito
                            </span>

                        </div>

                    </div>

                    <div class="card-header">

                        <h5 class="card-title mb-0">
                            Contas da Abertura
                        </h5>

                    </div>

                    <div class="card-body p-0">

                        <div class="alert alert-light border mb-3">

                            <strong>
                                Outros saldos da abertura
                            </strong>

                            <div class="mt-1 text-muted">

                                Utilize esta área para valores que não vêm
                                das importações automáticas, como capital,
                                reservas, resultados transitados, empréstimos,
                                impostos e outros ativos ou passivos.

                            </div>

                        </div>

                        <div class="table-responsive">

                            <table
                                class="table table-bordered mb-0"
                                id="tabelaItensAbertura">

                                <thead>

                                    <tr>

                                        <th style="min-width:260px;">
                                            Conta Contabilística
                                        </th>

                                        <th style="min-width:220px;">
                                            Descrição
                                        </th>

                                        <th
                                            class="text-right"
                                            style="min-width:150px;">

                                            Débito

                                        </th>

                                        <th
                                            class="text-right"
                                            style="min-width:150px;">

                                            Crédito

                                        </th>

                                        <th class="text-center">
                                            Origem
                                        </th>

                                        <th class="text-center">
                                            Referência
                                        </th>

                                        <th
                                            class="text-center"
                                            style="width:70px;">

                                            Ação

                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td
                                            colspan="7"
                                            class="text-center text-muted py-4">

                                            Nenhum item adicionado.

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <!-- ==================================
                     TOTAIS
                =================================== -->

                <div class="row">

                    <div class="col-lg-3 col-md-6">

                        <div class="card">

                            <div class="card-body">

                                <span class="text-muted">
                                    Total Débito
                                </span>

                                <h4
                                    class="mb-0 mt-2"
                                    id="saldoAberturaTotalDebito">

                                    0,00 MZN

                                </h4>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="card">

                            <div class="card-body">

                                <span class="text-muted">
                                    Total Crédito
                                </span>

                                <h4
                                    class="mb-0 mt-2"
                                    id="saldoAberturaTotalCredito">

                                    0,00 MZN

                                </h4>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="card">

                            <div class="card-body">

                                <span class="text-muted">
                                    Diferença
                                </span>

                                <h4
                                    class="mb-0 mt-2"
                                    id="saldoAberturaDiferenca">

                                    0,00 MZN

                                </h4>

                            </div>

                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6">

                        <div class="card">

                            <div class="card-body">

                                <span class="text-muted">
                                    Estado
                                </span>

                                <h6
                                    class="mb-0 mt-2"
                                    id="saldoAberturaEstadoEquilibrio">

                                    Abertura não equilibrada

                                </h6>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ==================================
                     AVISO CONTABILÍSTICO
                =================================== -->

                <div class="alert alert-warning mb-0">

                    <strong>
                        Atenção:
                    </strong>

                    o saldo de abertura só pode ser confirmado
                    quando o total dos débitos for igual ao total
                    dos créditos. A confirmação irá gerar um
                    lançamento contabilístico no Diário de Abertura.

                </div>

            </div>


            <!-- ======================================
                 FOOTER
            ======================================= -->

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-dismiss="modal">

                    Fechar

                </button>


                <button
                    type="button"
                    class="btn btn-outline-primary"
                    id="btnGuardarSaldoAbertura">

                    <i class="fa fa-save mr-1"></i>
                    Guardar Rascunho

                </button>


                <button
                    type="button"
                    class="btn btn-success"
                    id="btnConfirmarSaldoAbertura"
                    disabled>

                    <i class="fa fa-check mr-1"></i>
                    Confirmar Abertura

                </button>

            </div>

        </div>

    </div>

</div>


<!-- ==================================================
     MODAL ENTRADAS DE STOCK
================================================== -->

<!-- ==================================================
     MODAL IMPORTAR SALDOS
================================================== -->

<div
    class="modal fade"
    id="modalImportarSaldosAbertura"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl modal-dialog-scrollable"
        role="document">

        <div class="modal-content">

            <!-- HEADER -->

            <div class="modal-header">

                <h5 class="modal-title">
                    Importar Saldos para Abertura
                </h5>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">

                    <span aria-hidden="true">
                        &times;
                    </span>

                </button>

            </div>


            <!-- BODY -->

            <div class="modal-body">

                <div class="alert alert-info">

                    Selecione os saldos existentes antes
                    do início da utilização do ERPPlus.

                    A importação apenas cria itens no
                    documento de abertura e não gera
                    novos movimentos operacionais.

                </div>


                <!-- ABAS -->

                <ul
                    class="nav nav-tabs"
                    id="abasImportarSaldosAbertura"
                    role="tablist">

                    <li class="nav-item">

                        <a
                            class="nav-link active"
                            id="abaStockAbertura"
                            data-toggle="tab"
                            href="#conteudoStockAbertura"
                            role="tab">

                            <i class="fa fa-cubes mr-1"></i>
                            Stock

                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            id="abaCaixaAbertura"
                            data-toggle="tab"
                            href="#conteudoCaixaAbertura"
                            role="tab">

                            <i class="fa fa-money-bill mr-1"></i>
                            Caixa

                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            id="abaBancoAbertura"
                            data-toggle="tab"
                            href="#conteudoBancoAbertura"
                            role="tab">

                            <i class="fa fa-university mr-1"></i>
                            Bancos

                        </a>

                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            data-toggle="tab"
                            href="#conteudoClientesAbertura">

                            <i class="fa fa-users mr-1"></i>
                            Clientes

                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            data-toggle="tab"
                            href="#conteudoFornecedoresAbertura">

                            <i class="fa fa-truck mr-1"></i>
                            Fornecedores

                        </a>
                    </li>

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            id="abaAtivosAbertura"
                            data-toggle="tab"
                            href="#conteudoAtivosAbertura"
                            role="tab">

                            <i class="fa fa-laptop mr-1"></i>
                            Ativos

                        </a>

                    </li>

                </ul>


                <!-- CONTEÚDO DAS ABAS -->

                <div class="tab-content pt-3">


                    <!-- ==================================
                         STOCK
                    =================================== -->

                    <div
                        class="tab-pane fade show active"
                        id="conteudoStockAbertura"
                        role="tabpanel">

                        <div class="table-responsive">

                            <table
                                class="table table-bordered table-striped"
                                id="tabelaEntradasStockAbertura">

                                <thead>

                                    <tr>

                                        <th>
                                            Movimento
                                        </th>

                                        <th>
                                            Data
                                        </th>

                                        <th>
                                            Armazém
                                        </th>

                                        <th class="text-center">
                                            Produtos
                                        </th>

                                        <th class="text-center">
                                            Quantidade
                                        </th>

                                        <th class="text-right">
                                            Valor
                                        </th>

                                        <th class="text-center">
                                            Ação
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td
                                            colspan="7"
                                            class="text-center text-muted">

                                            A carregar...

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    <!-- ==================================
                         CAIXA
                    =================================== -->

                    <div
                        class="tab-pane fade"
                        id="conteudoCaixaAbertura"
                        role="tabpanel">

                        <div class="table-responsive">

                            <table
                                class="table table-bordered table-striped"
                                id="tabelaCaixasAbertura">

                                <thead>

                                    <tr>

                                        <th>
                                            Código
                                        </th>

                                        <th>
                                            Caixa
                                        </th>

                                        <th class="text-right">
                                            Saldo Inicial
                                        </th>

                                        <th class="text-right">
                                            Saldo na Abertura
                                        </th>

                                        <th class="text-center">
                                            Ação
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td
                                            colspan="5"
                                            class="text-center text-muted">

                                            A carregar...

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    <!-- ==================================
                         BANCOS
                    =================================== -->

                    <div
                        class="tab-pane fade"
                        id="conteudoBancoAbertura"
                        role="tabpanel">

                        <div class="table-responsive">

                            <table
                                class="table table-bordered table-striped"
                                id="tabelaBancosAbertura">

                                <thead>

                                    <tr>

                                        <th>
                                            Banco
                                        </th>

                                        <th>
                                            Conta
                                        </th>

                                        <th>
                                            Tipo
                                        </th>

                                        <th>
                                            Moeda
                                        </th>

                                        <th class="text-right">
                                            Saldo na Abertura
                                        </th>

                                        <th class="text-center">
                                            Ação
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td
                                            colspan="6"
                                            class="text-center text-muted">

                                            A carregar...

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                    <div
                        class="tab-pane fade"
                        id="conteudoClientesAbertura">

                        <div class="table-responsive">

                            <table
                                class="table table-bordered table-striped"
                                id="tabelaClientesAbertura">

                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>NUIT</th>
                                        <th class="text-center">Documentos</th>
                                        <th class="text-right">Saldo a receber</th>
                                        <th class="text-center">Ação</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td
                                            colspan="5"
                                            class="text-center text-muted">
                                            A carregar...
                                        </td>
                                    </tr>
                                </tbody>

                            </table>

                        </div>

                    </div>


                    <div
                        class="tab-pane fade"
                        id="conteudoFornecedoresAbertura">

                        <div class="table-responsive">

                            <table
                                class="table table-bordered table-striped"
                                id="tabelaFornecedoresAbertura">

                                <thead>
                                    <tr>
                                        <th>Fornecedor</th>
                                        <th>NUIT</th>
                                        <th class="text-center">Documentos</th>
                                        <th class="text-right">Saldo a pagar</th>
                                        <th class="text-center">Ação</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td
                                            colspan="5"
                                            class="text-center text-muted">
                                            A carregar...
                                        </td>
                                    </tr>
                                </tbody>

                            </table>

                        </div>

                    </div>

                    <!-- ==================================
     ATIVOS
=================================== -->

                    <div
                        class="tab-pane fade"
                        id="conteudoAtivosAbertura"
                        role="tabpanel">

                        <div class="table-responsive">

                            <table
                                class="table table-bordered table-striped"
                                id="tabelaAtivosAbertura">

                                <thead>

                                    <tr>

                                        <th>
                                            Código
                                        </th>

                                        <th>
                                            Ativo
                                        </th>

                                        <th>
                                            Tipo
                                        </th>

                                        <th>
                                            Conta
                                        </th>

                                        <th class="text-right">
                                            Aquisição
                                        </th>

                                        <th class="text-right">
                                            Deprec./Amort. acumulada
                                        </th>

                                        <th class="text-right">
                                            Valor líquido
                                        </th>

                                        <th class="text-center">
                                            Ação
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td
                                            colspan="8"
                                            class="text-center text-muted py-4">

                                            A carregar...

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            <!-- FOOTER -->

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-dismiss="modal">

                    Fechar

                </button>

            </div>

        </div>

    </div>

</div>