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
                        Despesas
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">
                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>
                        </li>

                        <li class="breadcrumb-item">
                            Financeiro
                        </li>

                        <li class="breadcrumb-item active">
                            Despesas
                        </li>

                    </ul>

                </div>

                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovaDespesa">

                        <i class="fa fa-plus mr-1"></i>
                        Nova Despesa

                    </button>

                </div>

            </div>

        </div>


        <!-- ==================================================
             RESUMO
        ================================================== -->

        <div class="row">

            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <span class="text-muted">
                            Total Registado
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="resumoTotalDespesas">
                            0,00 MZN
                        </h4>
                    </div>
                </div>
            </div>


            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <span class="text-muted">
                            Rascunhos
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="resumoRascunhos">
                            0
                        </h4>
                    </div>
                </div>
            </div>


            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <span class="text-muted">
                            Contabilizadas
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="resumoContabilizadas">
                            0
                        </h4>
                    </div>
                </div>
            </div>


            <div class="col-lg-3 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <span class="text-muted">
                            Canceladas
                        </span>

                        <h4
                            class="mt-2 mb-0"
                            id="resumoCanceladas">
                            0
                        </h4>
                    </div>
                </div>
            </div>

        </div>


        <!-- ==================================================
             TABELA
        ================================================== -->

        <div class="card">
            <div class="card-body">

                <div class="table-responsive">

                    <table
                        class="table"
                        id="tabelaDespesas">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Documento</th>
                                <th>Descrição</th>
                                <th>Fornecedor</th>
                                <th>Conta</th>
                                <th>Liquidação</th>
                                <th>Valor</th>
                                <th>Estado</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody></tbody>

                    </table>

                </div>

            </div>
        </div>

    </div>
</div>


<!-- ==================================================
     MODAL DESPESA
================================================== -->

<div
    class="modal fade"
    id="modalDespesa"
    tabindex="-1"
    aria-hidden="true">
    <div
        class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">

                <h5
                    class="modal-title"
                    id="tituloModalDespesa">
                    Nova Despesa
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


            <div class="modal-body">

                <form id="formDespesa">

                    <input
                        type="hidden"
                        id="despesaId"
                        name="id">


                    <!-- ======================================
                         DADOS PRINCIPAIS
                    ======================================= -->

                    <div class="row">

                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="form-group">
                                <label>
                                    Data da Despesa *
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="despesaData"
                                    name="data_despesa"
                                    required>
                            </div>
                        </div>


                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="form-group">
                                <label>
                                    Nº Documento
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="despesaDocumento"
                                    name="numero_documento"
                                    maxlength="80">
                            </div>
                        </div>


                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="form-group">
                                <label>
                                    Referência
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="despesaReferencia"
                                    name="referencia"
                                    maxlength="100">
                            </div>
                        </div>


                        <div class="col-12">
                            <div class="form-group">
                                <label>
                                    Descrição *
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="despesaDescricao"
                                    name="descricao"
                                    maxlength="255"
                                    required>
                            </div>
                        </div>

                    </div>


                    <hr>


                    <!-- ======================================
                         CLASSIFICAÇÃO
                    ======================================= -->

                    <div class="row">

                        <div class="col-lg-6 col-12">
                            <div class="form-group">
                                <label>
                                    Conta de Gasto *
                                </label>

                                <select
                                    class="form-control"
                                    id="despesaContaGasto"
                                    name="conta_gasto_id"
                                    required>
                                    <option value="">
                                        Selecione a conta
                                    </option>
                                </select>
                            </div>
                        </div>


                        <div class="col-lg-6 col-12">
                            <div class="form-group">
                                <label>
                                    Centro de Custo
                                </label>

                                <select
                                    class="form-control"
                                    id="despesaCentroCusto"
                                    name="centro_custo_id">
                                    <option value="">
                                        Sem centro de custo
                                    </option>
                                </select>

                                <small
                                    class="text-muted"
                                    id="avisoCentroCustoDespesa"></small>
                            </div>
                        </div>


                        <div class="col-lg-6 col-12">
                            <div class="form-group">
                                <label>
                                    Fornecedor
                                </label>

                                <select
                                    class="form-control"
                                    id="despesaFornecedor"
                                    name="fornecedor_id">
                                    <option value="">
                                        Sem fornecedor
                                    </option>
                                </select>
                            </div>
                        </div>

                    </div>


                    <hr>


                    <!-- ======================================
                         VALORES
                    ======================================= -->

                    <div class="row">

                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="form-group">
                                <label>
                                    Valor Base *
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="despesaValorBase"
                                    name="valor_base"
                                    min="0.01"
                                    step="0.01"
                                    required>
                            </div>
                        </div>


                        <div class="col-lg-4 col-md-6 col-12">

                            <div class="form-group">

                                <label for="despesaImposto">
                                    Imposto (%)
                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    id="despesaImposto"
                                    name="imposto_percentual"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value="0.00">

                                <small
                                    id="despesaImpostoValorCalculado"
                                    class="form-text text-muted">

                                    Valor do imposto: 0,00 MTn

                                </small>

                            </div>

                        </div>


                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="form-group">
                                <label>
                                    Total
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="despesaValorTotal"
                                    value="0,00 MZN"
                                    readonly>
                            </div>
                        </div>

                    </div>


                    <hr>


                    <!-- ======================================
                         LIQUIDAÇÃO
                    ======================================= -->

                    <div class="row">

                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="form-group">
                                <label>
                                    Liquidação *
                                </label>

                                <select
                                    class="form-control"
                                    id="despesaTipoLiquidacao"
                                    name="tipo_liquidacao"
                                    required>
                                    <option value="">
                                        Selecione
                                    </option>

                                    <option value="CAIXA">
                                        Caixa
                                    </option>

                                    <option value="BANCO">
                                        Banco
                                    </option>

                                    <option value="CONTA_PAGAR">
                                        Conta a Pagar
                                    </option>
                                </select>
                            </div>
                        </div>


                        <div
                            class="col-lg-4 col-md-6 col-12 d-none"
                            id="grupoCaixaDespesa">
                            <div class="form-group">
                                <label>
                                    Caixa *
                                </label>

                                <select
                                    class="form-control"
                                    id="despesaCaixa"
                                    name="caixa_id">
                                    <option value="">
                                        Selecione o caixa
                                    </option>
                                </select>
                            </div>
                        </div>


                        <div
                            class="col-lg-4 col-md-6 col-12 d-none"
                            id="grupoBancoDespesa">
                            <div class="form-group">
                                <label>
                                    Banco *
                                </label>

                                <select
                                    class="form-control"
                                    id="despesaBanco"
                                    name="banco_id">
                                    <option value="">
                                        Selecione o banco
                                    </option>
                                </select>
                            </div>
                        </div>


                        <div
                            class="col-lg-4 col-md-6 col-12 d-none"
                            id="grupoVencimentoDespesa">
                            <div class="form-group">
                                <label>
                                    Data de Vencimento *
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="despesaVencimento"
                                    name="data_vencimento">
                            </div>
                        </div>

                    </div>


                    <div class="row">

                        <div class="col-12">
                            <div class="form-group">
                                <label>
                                    Observações
                                </label>

                                <textarea
                                    class="form-control"
                                    id="despesaObservacoes"
                                    name="observacoes"
                                    rows="3"></textarea>
                            </div>
                        </div>

                    </div>

                </form>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-dismiss="modal">

                    Cancelar

                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="btnGuardarDespesa">
                    Guardar Rascunho
                </button>

            </div>

        </div>
    </div>
</div>