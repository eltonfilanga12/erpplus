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
                        Plano de Contas
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
                            Plano de Contas
                        </li>

                    </ul>

                </div>


                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-outline-primary mr-2"
                        id="btnNovoPlano">

                        <i class="fa fa-plus"></i>

                        Novo Plano

                    </button>

                    <button
                        type="button"
                        class="btn btn-success mr-2"
                        id="btnImportarPgc"
                        disabled>
                        <i class="fa fa-download"></i>

                        Importar PGC-NIRF
                    </button>

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnNovaConta"
                        disabled>

                        <i class="fa fa-plus"></i>

                        Nova Conta

                    </button>


                </div>

            </div>

        </div>


        <!-- =========================================
             PLANO SELECIONADO E FILTROS
        ========================================== -->

        <div class="card">

            <div class="card-body">

                <div class="row align-items-end">

                    <div class="col-lg-4 col-md-6">

                        <div class="form-group">

                            <label for="planoSelecionado">
                                Plano de Contas
                            </label>

                            <select
                                class="form-control"
                                id="planoSelecionado">

                                <option value="">
                                    Seleccione o plano
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-lg-2 col-md-3">

                        <div class="form-group">

                            <button
                                type="button"
                                class="btn btn-info btn-block"
                                id="btnEditarPlano"
                                disabled>

                                <i class="fa fa-edit"></i>

                                Editar Plano

                            </button>

                        </div>

                    </div>

                    <div class="col-lg-3 col-md-3">

                        <div class="form-group">

                            <button
                                type="button"
                                class="btn btn-warning btn-block"
                                id="btnStatusPlano"
                                disabled>

                                <i class="fa fa-exchange"></i>

                                Alterar Status

                            </button>
                        </div>

                    </div>


                    <div class="col-lg-3 col-md-3">

                        <div class="form-group">

                            <button
                                type="button"
                                class="btn btn-danger btn-block"
                                id="btnEliminarPlano"
                                disabled>

                                <i class="fa fa-trash"></i>

                                Eliminar Plano

                            </button>

                        </div>

                    </div>








                </div>

                <div class="row">
                    <div class="col-md-6">

                        <div class="form-group">

                            <label for="filtroGrupoConta">
                                Grupo
                            </label>

                            <select
                                class="form-control"
                                id="filtroGrupoConta">

                                <option value="">
                                    Todos
                                </option>

                                <option value="ATIVO">
                                    Ativo
                                </option>

                                <option value="PASSIVO">
                                    Passivo
                                </option>

                                <option value="CAPITAL_PROPRIO">
                                    Capital Próprio
                                </option>

                                <option value="RENDIMENTO">
                                    Rendimento
                                </option>

                                <option value="GASTO">
                                    Gasto
                                </option>

                                <option value="RESULTADO">
                                    Resultado
                                </option>

                                <option value="EXTRAPATRIMONIAL">
                                    Extrapatrimonial
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="form-group">

                            <label for="filtroStatusConta">
                                Status
                            </label>

                            <select
                                class="form-control"
                                id="filtroStatusConta">

                                <option value="">
                                    Todos os status
                                </option>

                            </select>

                        </div>

                    </div>
                </div>

                <div class="row">

                    <div class="col-md-8">

                        <div class="form-group mb-0">

                            <label for="pesquisaConta">
                                Pesquisar conta
                            </label>

                            <input
                                type="search"
                                class="form-control"
                                id="pesquisaConta"
                                placeholder="Código ou nome da conta">

                        </div>

                    </div>


                    <div class="col-md-2">

                        <div class="form-group mb-0">

                            <label for="filtroTipoConta">
                                Tipo
                            </label>

                            <select
                                class="form-control"
                                id="filtroTipoConta">

                                <option value="">
                                    Todos
                                </option>

                                <option value="SINTETICA">
                                    Sintética
                                </option>

                                <option value="ANALITICA">
                                    Analítica
                                </option>

                            </select>

                        </div>

                    </div>


                    <div class="col-md-2">

                        <div class="form-group mb-0">

                            <label>&nbsp;</label>

                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-block"
                                id="btnLimparFiltrosConta">

                                <i class="fa fa-eraser"></i>

                                Limpar

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             RESUMO DO PLANO
        ========================================== -->

        <div class="row">

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoTotalContas">
                            0
                        </h3>

                        <span class="text-muted">
                            Total de Contas
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoContasSinteticas">
                            0
                        </h3>

                        <span class="text-muted">
                            Contas Sintéticas
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoContasAnaliticas">
                            0
                        </h3>

                        <span class="text-muted">
                            Contas Analíticas
                        </span>

                    </div>

                </div>

            </div>


            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body text-center">

                        <h3 id="resumoContasIndisponiveis">
                            0
                        </h3>

                        <span class="text-muted">
                            Contas Indisponíveis
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <!-- =========================================
             ÁRVORE DE CONTAS
        ========================================== -->

        <div class="card">

            <div class="card-header">

                <div class="row align-items-center">

                    <div class="col">

                        <h4 class="card-title mb-0">
                            Estrutura do Plano de Contas
                        </h4>

                    </div>


                    <div class="col-auto">

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            id="btnExpandirArvore">

                            <i class="fa fa-plus-square"></i>

                            Expandir

                        </button>


                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            id="btnRecolherArvore">

                            <i class="fa fa-minus-square"></i>

                            Recolher

                        </button>


                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            id="btnAtualizarArvore">

                            <i class="fa fa-refresh"></i>

                            Atualizar

                        </button>

                    </div>

                </div>

            </div>


            <div class="card-body">

                <div
                    id="planoContasCarregando"
                    class="text-center py-4"
                    style="display:none;">

                    <span
                        class="spinner-border spinner-border-sm mr-2">
                    </span>

                    A carregar contas contabilísticas...

                </div>


                <div
                    id="mensagemPlanoVazio"
                    class="alert alert-info text-center">

                    Seleccione um plano de contas para visualizar a estrutura.

                </div>


                <div
                    id="arvorePlanoContas"
                    class="plano-contas-arvore">

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL DO PLANO
====================================================== -->

<div
    class="modal fade"
    id="modalPlanoConta"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-lg"
        role="document">

        <div class="modal-content">

            <form id="formPlanoConta">

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="tituloModalPlano">

                        Novo Plano de Contas

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

                    <input
                        type="hidden"
                        id="planoId"
                        name="id">


                    <div class="row">

                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="planoCodigo">
                                    Código *
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="planoCodigo"
                                    name="codigo"
                                    maxlength="30"
                                    required>

                            </div>

                        </div>


                        <div class="col-md-5">

                            <div class="form-group">

                                <label for="planoNome">
                                    Nome *
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="planoNome"
                                    name="nome"
                                    maxlength="150"
                                    required>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="planoVersao">
                                    Versão
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="planoVersao"
                                    name="versao"
                                    maxlength="30">

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="planoDataInicio">
                                    Início da Vigência *
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="planoDataInicio"
                                    name="data_inicio_vigencia"
                                    required>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="planoDataFim">
                                    Fim da Vigência
                                </label>

                                <input
                                    type="date"
                                    class="form-control"
                                    id="planoDataFim"
                                    name="data_fim_vigencia">

                            </div>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="planoDescricao">
                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            id="planoDescricao"
                            name="descricao"
                            rows="3"></textarea>

                    </div>


                    <div class="row">

                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="planoStatusId">
                                    Status *
                                </label>

                                <select
                                    class="form-control"
                                    id="planoStatusId"
                                    name="status_id"
                                    required>

                                    <option value="">
                                        Seleccione o status
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Configuração
                                </label>

                                <div class="form-check mt-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="planoPadrao"
                                        name="padrao"
                                        value="1">

                                    <label
                                        class="form-check-label"
                                        for="planoPadrao">

                                        Definir como plano padrão

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnGuardarPlano">

                        <i class="fa fa-save"></i>

                        Guardar Plano

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL DA CONTA CONTABILÍSTICA
====================================================== -->

<div
    class="modal fade"
    id="modalContaContabil"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog modal-xl"
        role="document">

        <div class="modal-content">

            <form id="formContaContabil">

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="tituloModalConta">

                        Nova Conta Contabilística

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

                    <input
                        type="hidden"
                        id="contaId"
                        name="id">

                    <input
                        type="hidden"
                        id="contaPlanoId"
                        name="plano_contas_id">


                    <div class="row">

                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="contaPaiId">
                                    Conta Superior
                                </label>

                                <select
                                    class="form-control"
                                    id="contaPaiId"
                                    name="conta_pai_id">

                                    <option value="">
                                        Conta de nível principal
                                    </option>

                                </select>

                                <small class="form-text text-muted">
                                    Apenas contas sintéticas podem receber contas filhas.
                                </small>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="contaCodigo">
                                    Código *
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="contaCodigo"
                                    name="codigo"
                                    maxlength="50"
                                    required>

                            </div>

                        </div>


                        <div class="col-md-5">

                            <div class="form-group">

                                <label for="contaNome">
                                    Nome da Conta *
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="contaNome"
                                    name="nome"
                                    maxlength="180"
                                    required>

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="contaTipo">
                                    Tipo *
                                </label>

                                <select
                                    class="form-control"
                                    id="contaTipo"
                                    name="tipo"
                                    required>

                                    <option value="SINTETICA">
                                        Sintética
                                    </option>

                                    <option value="ANALITICA">
                                        Analítica
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="contaNatureza">
                                    Natureza *
                                </label>

                                <select
                                    class="form-control"
                                    id="contaNatureza"
                                    name="natureza"
                                    required>

                                    <option value="">
                                        Seleccione
                                    </option>

                                    <option value="DEVEDORA">
                                        Devedora
                                    </option>

                                    <option value="CREDORA">
                                        Credora
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="contaGrupo">
                                    Grupo Contabilístico *
                                </label>

                                <select
                                    class="form-control"
                                    id="contaGrupo"
                                    name="grupo"
                                    required>

                                    <option value="">
                                        Seleccione
                                    </option>

                                    <option value="ATIVO">
                                        Ativo
                                    </option>

                                    <option value="PASSIVO">
                                        Passivo
                                    </option>

                                    <option value="CAPITAL_PROPRIO">
                                        Capital Próprio
                                    </option>

                                    <option value="RENDIMENTO">
                                        Rendimento
                                    </option>

                                    <option value="GASTO">
                                        Gasto
                                    </option>

                                    <option value="RESULTADO">
                                        Resultado
                                    </option>

                                    <option value="EXTRAPATRIMONIAL">
                                        Extrapatrimonial
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-md-2">

                            <div class="form-group">

                                <label for="contaStatusId">
                                    Status *
                                </label>

                                <select
                                    class="form-control"
                                    id="contaStatusId"
                                    name="status_id"
                                    required>

                                    <option value="">
                                        Seleccione
                                    </option>

                                </select>
                            </div>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="contaDescricao">
                            Descrição
                        </label>

                        <textarea
                            class="form-control"
                            id="contaDescricao"
                            name="descricao"
                            rows="2"></textarea>

                    </div>


                    <hr>


                    <h5 class="mb-3">
                        Configurações da Conta
                    </h5>


                    <div class="row">

                        <div class="col-md-4">

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="contaAceitaLancamento"
                                    name="aceita_lancamento"
                                    value="1">

                                <label
                                    class="form-check-label"
                                    for="contaAceitaLancamento">

                                    Aceita lançamentos

                                </label>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="contaExigeCentroCusto"
                                    name="exige_centro_custo"
                                    value="1">

                                <label
                                    class="form-check-label"
                                    for="contaExigeCentroCusto">

                                    Exige centro de custo

                                </label>

                            </div>

                        </div>


                        <div class="col-md-4">

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="contaExigeEntidade"
                                    name="exige_entidade"
                                    value="1">

                                <label
                                    class="form-check-label"
                                    for="contaExigeEntidade">

                                    Exige cliente ou fornecedor

                                </label>

                            </div>

                        </div>

                    </div>


                    <hr>


                    <h5 class="mb-3">
                        Classificação para Integração
                    </h5>

                    <div class="row">

                        <div class="col-md-12">

                            <div class="form-group">

                                <label for="contaIntegracaoCodigo">
                                    Código de Integração
                                </label>

                                <select
                                    class="form-control"
                                    id="contaIntegracaoCodigo"
                                    name="integracao_codigo">

                                    <option value="">
                                        Sem integração automática
                                    </option>

                                    <option value="CAIXA">
                                        Caixa
                                    </option>

                                    <option value="BANCOS">
                                        Bancos
                                    </option>

                                    <option value="CLIENTES">
                                        Clientes
                                    </option>

                                    <option value="FORNECEDORES">
                                        Fornecedores
                                    </option>

                                    <option value="VENDAS">
                                        Vendas
                                    </option>

                                    <option value="COMPRAS">
                                        Compras
                                    </option>

                                    <option value="STOCK">
                                        Stock
                                    </option>

                                    <option value="CMV">
                                        Custo das Mercadorias Vendidas
                                    </option>

                                    <option value="IVA_LIQUIDADO">
                                        IVA Liquidado
                                    </option>

                                    <option value="IVA_DEDUTIVEL">
                                        IVA Dedutível
                                    </option>

                                </select>

                                <small class="form-text text-muted">
                                    Use apenas em contas analíticas destinadas às integrações automáticas.
                                </small>

                            </div>

                        </div>

                    </div>

                    <hr>

                    <h5 class="mb-3">
                        Demonstração de Fluxos de Caixa
                    </h5>

                    <div class="alert alert-light border">
                        Esta classificação é utilizada quando a conta
                        aparece como contrapartida de uma entrada ou saída
                        de Caixa/Banco.
                    </div>

                    <div class="row">

                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="contaFluxoCaixaCategoria">
                                    Categoria DFC
                                </label>

                                <select
                                    class="form-control"
                                    id="contaFluxoCaixaCategoria"
                                    name="fluxo_caixa_categoria">

                                    <option value="NENHUM">
                                        Sem classificação direta
                                    </option>

                                    <option value="OPERACIONAL">
                                        Atividade Operacional
                                    </option>

                                    <option value="INVESTIMENTO">
                                        Atividade de Investimento
                                    </option>

                                    <option value="FINANCIAMENTO">
                                        Atividade de Financiamento
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="col-md-8">

                            <div class="form-group">

                                <label for="contaFluxoCaixaDescricao">
                                    Descrição na DFC
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="contaFluxoCaixaDescricao"
                                    name="fluxo_caixa_descricao"
                                    maxlength="180"
                                    placeholder="Ex.: Realização de capital social">

                                <small class="form-text text-muted">
                                    Esta descrição será utilizada para agrupar
                                    os movimentos na Demonstração de Fluxos de Caixa.
                                </small>

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        <div class="col-md-4 col-lg-2">

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="contaCaixa"
                                    name="conta_caixa"
                                    value="1">

                                <label
                                    class="form-check-label"
                                    for="contaCaixa">

                                    Caixa

                                </label>

                            </div>

                        </div>


                        <div class="col-md-4 col-lg-2">

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="contaBanco"
                                    name="conta_banco"
                                    value="1">

                                <label
                                    class="form-check-label"
                                    for="contaBanco">

                                    Banco

                                </label>

                            </div>

                        </div>


                        <div class="col-md-4 col-lg-2">

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="contaCliente"
                                    name="conta_cliente"
                                    value="1">

                                <label
                                    class="form-check-label"
                                    for="contaCliente">

                                    Cliente

                                </label>

                            </div>

                        </div>


                        <div class="col-md-4 col-lg-2">

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="contaFornecedor"
                                    name="conta_fornecedor"
                                    value="1">

                                <label
                                    class="form-check-label"
                                    for="contaFornecedor">

                                    Fornecedor

                                </label>

                            </div>

                        </div>


                        <div class="col-md-4 col-lg-2">

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="contaImposto"
                                    name="conta_imposto"
                                    value="1">

                                <label
                                    class="form-check-label"
                                    for="contaImposto">

                                    Imposto

                                </label>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnGuardarConta">

                        <i class="fa fa-save"></i>

                        Guardar Conta

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- =====================================================
     MODAL DE STATUS DO PLANO
====================================================== -->

<div
    class="modal fade"
    id="modalStatusPlano"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">

            <form id="formStatusPlano">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Alterar Status do Plano
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>
                            &times;
                        </span>

                    </button>

                </div>

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="statusPlanoId"
                        name="id">

                    <div class="form-group">

                        <label for="novoStatusPlanoId">
                            Novo status
                        </label>

                        <select
                            class="form-control"
                            id="novoStatusPlanoId"
                            name="status_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnGuardarStatusPlano">

                        <i class="fa fa-save"></i>

                        Atualizar Status

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- =====================================================
     MODAL DE STATUS DA CONTA
====================================================== -->

<div
    class="modal fade"
    id="modalStatusConta"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">

            <form id="formStatusConta">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Alterar Status da Conta
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>
                            &times;
                        </span>

                    </button>

                </div>

                <div class="modal-body">

                    <input
                        type="hidden"
                        id="statusContaId"
                        name="id">

                    <div class="form-group">

                        <label for="novoStatusContaId">
                            Novo status
                        </label>

                        <select
                            class="form-control"
                            id="novoStatusContaId"
                            name="status_id"
                            required>

                            <option value="">
                                Seleccione
                            </option>

                        </select>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnGuardarStatusConta">

                        <i class="fa fa-save"></i>

                        Atualizar Status

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL DE ELIMINAÇÃO
====================================================== -->

<div
    class="modal fade"
    id="modalEliminarPlanoConta"
    tabindex="-1"
    role="dialog"
    aria-hidden="true">

    <div
        class="modal-dialog"
        role="document">

        <div class="modal-content">

            <form id="formEliminarPlanoConta">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Confirmar Eliminação
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>
                            &times;
                        </span>

                    </button>

                </div>


                <div class="modal-body">

                    <input
                        type="hidden"
                        id="eliminarTipo"
                        name="tipo">

                    <input
                        type="hidden"
                        id="eliminarId"
                        name="id">

                    <p id="mensagemEliminarPlanoConta">
                        Confirma a eliminação deste registo?
                    </p>

                    <div class="alert alert-warning mb-0">

                        <i class="fa fa-exclamation-triangle"></i>

                        Esta operação não poderá ser desfeita.

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger"
                        id="btnConfirmarEliminarPlanoConta">

                        <i class="fa fa-trash"></i>

                        Eliminar

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     ESTILO DA ÁRVORE
====================================================== -->

<style>
    .plano-contas-arvore {
        width: 100%;
    }

    .conta-arvore-item {
        border-bottom: 1px solid #eeeeee;
    }

    .conta-arvore-linha {
        display: flex;
        align-items: center;
        min-height: 52px;
        padding: 8px 10px;
        transition: background-color .2s ease;
    }

    .conta-arvore-linha:hover {
        background-color: #f8f9fa;
    }

    .conta-arvore-toggle {
        width: 28px;
        min-width: 28px;
        cursor: pointer;
        text-align: center;
    }

    .conta-arvore-identificacao {
        flex: 1;
        min-width: 0;
    }

    .conta-arvore-codigo {
        display: inline-block;
        min-width: 110px;
        font-weight: 700;
    }

    .conta-arvore-nome {
        font-weight: 500;
    }

    .conta-arvore-detalhes {
        margin-top: 3px;
        font-size: 12px;
        color: #6c757d;
    }

    .conta-arvore-acoes {
        white-space: nowrap;
    }

    .conta-arvore-filhas {
        display: block;
    }

    .conta-arvore-filhas.recolhida {
        display: none;
    }

    .conta-inativa {
        opacity: .60;
    }

    .badge-conta-sintetica {
        background-color: #6c757d;
        color: #ffffff;
    }

    .badge-conta-analitica {
        background-color: #17a2b8;
        color: #ffffff;
    }

    @media (max-width: 767px) {

        .conta-arvore-linha {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .conta-arvore-identificacao {
            width: calc(100% - 28px);
        }

        .conta-arvore-codigo {
            display: block;
            min-width: auto;
        }

        .conta-arvore-acoes {
            width: 100%;
            padding-left: 28px;
            padding-top: 8px;
        }

    }
</style>