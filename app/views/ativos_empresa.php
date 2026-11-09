<?php

declare(strict_types=1);

Auth::iniciarSessao();

?>

<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- ==================================================
             CABEÇALHO
        ================================================== -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Ativos da Empresa
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="/erpplus/index.php">
                                Dashboard
                            </a>

                        </li>

                        <li class="breadcrumb-item">
                            Contabilidade
                        </li>

                        <li class="breadcrumb-item active">
                            Ativos da Empresa
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <!-- ==================================================
         RESUMO
        ================================================== -->

        <div class="row">

            <div class="col-md-6 col-lg-3">

                <div class="card">

                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <div>

                                <span class="text-muted">
                                    Total de Ativos
                                </span>

                                <h3
                                    class="mt-2 mb-0"
                                    id="resumoTotalAtivos">

                                    0

                                </h3>

                            </div>

                            <div class="align-self-center">

                                <i
                                    class="la la-cubes"
                                    style="font-size:36px;">
                                </i>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-md-6 col-lg-3">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Valor de Aquisição
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoValorAquisicao">

                            0,00 MZN

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-md-6 col-lg-3">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Depreciação / Amortização
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoDepreciacaoAcumulada">

                            0,00 MZN

                        </h3>

                    </div>

                </div>

            </div>


            <div class="col-md-6 col-lg-3">

                <div class="card">

                    <div class="card-body">

                        <span class="text-muted">
                            Valor Líquido
                        </span>

                        <h3
                            class="mt-2 mb-0"
                            id="resumoValorLiquido">

                            0,00 MZN

                        </h3>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==================================================
         ABAS
        ================================================== -->

        <div class="card">

            <div class="card-header">

                <ul
                    class="nav nav-tabs card-header-tabs"
                    id="abasAtivosEmpresa"
                    role="tablist">

                    <li class="nav-item">

                        <a
                            class="nav-link active"
                            id="abaAtivos-tab"
                            data-toggle="tab"
                            href="#abaAtivos"
                            role="tab">

                            <i class="la la-cubes mr-1"></i>

                            Ativos

                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            id="abaDepreciacoes-tab"
                            data-toggle="tab"
                            href="#abaDepreciacoes"
                            role="tab">

                            <i class="la la-line-chart mr-1"></i>

                            Depreciações /
                            Amortizações

                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            id="abaMovimentacoes-tab"
                            data-toggle="tab"
                            href="#abaMovimentacoes"
                            role="tab">

                            <i class="la la-exchange mr-1"></i>

                            Movimentações

                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            id="abaManutencoes-tab"
                            data-toggle="tab"
                            href="#abaManutencoes"
                            role="tab">

                            <i class="la la-wrench mr-1"></i>

                            Manutenções

                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="nav-link"
                            id="abaBaixas-tab"
                            data-toggle="tab"
                            href="#abaBaixas"
                            role="tab">

                            <i class="la la-sign-out mr-1"></i>

                            Baixas / Alienações

                        </a>

                    </li>

                </ul>

            </div>


            <div class="card-body">

                <div class="tab-content">

                    <!-- ======================================
                     ABA ATIVOS
                    ======================================= -->

                    <div
                        class="tab-pane fade show active"
                        id="abaAtivos"
                        role="tabpanel">


                        <!-- BOTÃO NOVO -->

                        <div
                            class="d-flex
                               justify-content-between
                               align-items-center
                               mb-3">

                            <div>

                                <h4 class="mb-0">
                                    Cadastro de Ativos
                                </h4>

                                <small class="text-muted">

                                    Gestão dos ativos tangíveis
                                    e intangíveis da empresa.

                                </small>

                            </div>


                            <button
                                type="button"
                                class="btn btn-primary"
                                id="btnNovoAtivo">

                                <i class="fa fa-plus mr-1"></i>

                                Novo Ativo

                            </button>

                        </div>


                        <!-- ==================================
                         FILTROS
                        =================================== -->

                        <div class="row">

                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Tipo
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroAtivoTipo">

                                        <option value="">
                                            Todos
                                        </option>

                                        <option value="TANGIVEL">
                                            Tangível
                                        </option>

                                        <option value="INTANGIVEL">
                                            Intangível
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Categoria
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroAtivoCategoria">

                                        <option value="">
                                            Todas
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Filial
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroAtivoFilial">

                                        <option value="">
                                            Todas
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Departamento
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroAtivoDepartamento">

                                        <option value="">
                                            Todos
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Situação
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroAtivoSituacao">

                                        <option value="">
                                            Todas
                                        </option>

                                        <option value="EM_USO">
                                            Em uso
                                        </option>

                                        <option value="ARMAZENADO">
                                            Armazenado
                                        </option>

                                        <option value="EM_MANUTENCAO">
                                            Em manutenção
                                        </option>

                                        <option value="CEDIDO">
                                            Cedido
                                        </option>

                                        <option value="VENDIDO">
                                            Vendido
                                        </option>

                                        <option value="ABATIDO">
                                            Abatido
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Pesquisa
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="filtroAtivoPesquisa"
                                        placeholder="Código, descrição...">

                                </div>

                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-3">

                                <div class="alert alert-light border mb-0">

                                    <small class="text-muted">
                                        Ativos elegíveis
                                    </small>

                                    <h4
                                        class="mb-0"
                                        id="depreciacaoResumoElegiveis">

                                        0

                                    </h4>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="alert alert-light border mb-0">

                                    <small class="text-muted">
                                        Pendentes no período
                                    </small>

                                    <h4
                                        class="mb-0"
                                        id="depreciacaoResumoPendentes">

                                        0

                                    </h4>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="alert alert-light border mb-0">

                                    <small class="text-muted">
                                        Processados
                                    </small>

                                    <h4
                                        class="mb-0"
                                        id="depreciacaoResumoProcessados">

                                        0

                                    </h4>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="alert alert-light border mb-0">

                                    <small class="text-muted">
                                        Valor previsto no período
                                    </small>

                                    <h4
                                        class="mb-0"
                                        id="depreciacaoResumoValor">

                                        0,00 MZN

                                    </h4>

                                </div>

                            </div>

                        </div>


                        <!-- ==================================
                         TABELA
                        =================================== -->

                        <div class="table-responsive">

                            <table
                                class="table table-striped
                                   table-hover
                                   table-bordered"
                                id="tabelaAtivosEmpresa"
                                style="width:100%;">

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
                                            Categoria
                                        </th>

                                        <th class="text-right">
                                            Aquisição
                                        </th>

                                        <th class="text-right">
                                            Dep./Amort.
                                        </th>

                                        <th class="text-right">
                                            Valor Líquido
                                        </th>

                                        <th>
                                            Departamento
                                        </th>

                                        <th>
                                            Localização
                                        </th>

                                        <th>
                                            Situação
                                        </th>

                                        <th class="text-center">
                                            Ações
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>
                                </tbody>

                            </table>

                        </div>

                    </div>


                    <!-- ======================================
                     DEPRECIAÇÕES
                    ======================================= -->

                    <div
                        class="tab-pane fade"
                        id="abaDepreciacoes"
                        role="tabpanel">

                        <div id="conteudoDepreciacoesAtivos">

                            <div
                                class="d-flex
                                justify-content-between
                                align-items-center
                                mb-3">

                                <div>

                                    <h4 class="mb-0">
                                        Depreciações e Amortizações
                                    </h4>

                                    <small class="text-muted">
                                        Processamento periódico dos ativos.
                                    </small>

                                </div>


                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    id="btnProcessarDepreciacoesSelecionadas">

                                    <i class="fa fa-calculator mr-1"></i>

                                    Processar Selecionados

                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary mr-2"
                                    id="btnConfiguracaoContabilAtivos">

                                    <i class="fa fa-cog mr-1"></i>

                                    Configuração Contabilística

                                </button>

                            </div>


                            <div class="row">

                                <div class="col-md-2">

                                    <div class="form-group">

                                        <label>
                                            Ano
                                        </label>

                                        <input
                                            type="number"
                                            class="form-control"
                                            id="depreciacaoAno"
                                            value="<?= date('Y') ?>">

                                    </div>

                                </div>


                                <div class="col-md-2">

                                    <div class="form-group">

                                        <label>
                                            Mês
                                        </label>

                                        <select
                                            class="form-control"
                                            id="depreciacaoMes">

                                            <?php

                                            $meses = [
                                                1 => 'Janeiro',
                                                2 => 'Fevereiro',
                                                3 => 'Março',
                                                4 => 'Abril',
                                                5 => 'Maio',
                                                6 => 'Junho',
                                                7 => 'Julho',
                                                8 => 'Agosto',
                                                9 => 'Setembro',
                                                10 => 'Outubro',
                                                11 => 'Novembro',
                                                12 => 'Dezembro'
                                            ];

                                            foreach (
                                                $meses as
                                                $numero => $nome
                                            ) {

                                                $selecionado =
                                                    $numero
                                                    === (int)date('n')
                                                    ? 'selected'
                                                    : '';

                                                echo sprintf(
                                                    '<option value="%d" %s>%s</option>',
                                                    $numero,
                                                    $selecionado,
                                                    htmlspecialchars(
                                                        $nome,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    )
                                                );
                                            }

                                            ?>

                                        </select>

                                    </div>

                                </div>


                                <div class="col-md-2">

                                    <div class="form-group">

                                        <label>
                                            Tipo
                                        </label>

                                        <select
                                            class="form-control"
                                            id="depreciacaoTipo">

                                            <option value="">
                                                Todos
                                            </option>

                                            <option value="TANGIVEL">
                                                Tangíveis
                                            </option>

                                            <option value="INTANGIVEL">
                                                Intangíveis
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <div class="col-md-4">

                                    <div class="form-group">

                                        <label>
                                            Pesquisa
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control"
                                            id="depreciacaoPesquisa"
                                            placeholder="Código, ativo ou categoria">

                                    </div>

                                </div>


                                <div class="col-md-2">

                                    <div class="form-group">

                                        <label>
                                            &nbsp;
                                        </label>

                                        <button
                                            type="button"
                                            class="btn btn-outline-primary btn-block"
                                            id="btnAtualizarDepreciacoes">

                                            <i class="fa fa-refresh mr-1"></i>

                                            Atualizar

                                        </button>

                                    </div>

                                </div>

                            </div>


                            <div class="table-responsive">

                                <table
                                    class="table
               table-bordered
               table-striped
               table-hover"
                                    id="tabelaDepreciacoesAtivos">

                                    <thead>

                                        <tr>

                                            <th class="text-center">

                                                <input
                                                    type="checkbox"
                                                    id="selecionarTodasDepreciacoes">

                                            </th>

                                            <th>
                                                Código
                                            </th>

                                            <th>
                                                Ativo
                                            </th>

                                            <th>
                                                Tipo
                                            </th>

                                            <th class="text-right">
                                                Aquisição
                                            </th>

                                            <th class="text-right">
                                                Residual
                                            </th>

                                            <th class="text-right">
                                                Mensal
                                            </th>

                                            <th class="text-right">
                                                Acumulado
                                            </th>

                                            <th class="text-right">
                                                Valor Líquido
                                            </th>

                                            <th>
                                                Estado do período
                                            </th>

                                            <th class="text-center">
                                                Ação
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody>
                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>


                    <!-- ======================================
                     MOVIMENTAÇÕES
                    ======================================= -->

                    <div
                        class="tab-pane fade"
                        id="abaMovimentacoes"
                        role="tabpanel">

                        <div
                            class="d-flex
               justify-content-between
               align-items-center
               mb-3">

                            <div>

                                <h4 class="mb-0">
                                    Movimentações de Ativos
                                </h4>

                                <small class="text-muted">
                                    Transferências, mudanças de localização,
                                    departamento e responsável.
                                </small>

                            </div>


                            <button
                                type="button"
                                class="btn btn-primary"
                                id="btnNovaMovimentacaoAtivo">

                                <i class="fa fa-plus mr-1"></i>

                                Nova Movimentação

                            </button>

                        </div>


                        <!-- ==================================================
         FILTROS
    ================================================== -->

                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Ativo
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroMovimentacaoAtivo">

                                        <option value="">
                                            Todos
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Tipo
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroMovimentacaoTipo">

                                        <option value="">
                                            Todos
                                        </option>

                                        <option value="ENTRADA">
                                            Entrada
                                        </option>

                                        <option value="TRANSFERENCIA">
                                            Transferência
                                        </option>

                                        <option value="ALTERACAO_RESPONSAVEL">
                                            Alteração de Responsável
                                        </option>

                                        <option value="ALTERACAO_LOCALIZACAO">
                                            Alteração de Localização
                                        </option>

                                        <option value="CEDENCIA">
                                            Cedência
                                        </option>

                                        <option value="DEVOLUCAO">
                                            Devolução
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Filial
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroMovimentacaoFilial">

                                        <option value="">
                                            Todas
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Departamento
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroMovimentacaoDepartamento">

                                        <option value="">
                                            Todos
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Pesquisa
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="filtroMovimentacaoPesquisa"
                                        placeholder="Código, ativo, motivo...">

                                </div>

                            </div>

                        </div>


                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Data inicial
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filtroMovimentacaoDataInicial">

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Data final
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filtroMovimentacaoDataFinal">

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        &nbsp;
                                    </label>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-block"
                                        id="btnAtualizarMovimentacoes">

                                        <i class="fa fa-refresh mr-1"></i>

                                        Atualizar

                                    </button>

                                </div>

                            </div>

                        </div>


                        <!-- ==================================================
         TABELA
    ================================================== -->

                        <div class="table-responsive">

                            <table
                                class="table
                   table-bordered
                   table-striped
                   table-hover"
                                id="tabelaMovimentacoesAtivos">

                                <thead>

                                    <tr>

                                        <th>
                                            Data
                                        </th>

                                        <th>
                                            Ativo
                                        </th>

                                        <th>
                                            Tipo
                                        </th>

                                        <th>
                                            Filial
                                        </th>

                                        <th>
                                            Departamento
                                        </th>

                                        <th>
                                            Localização
                                        </th>

                                        <th>
                                            Responsável
                                        </th>

                                        <th>
                                            Motivo
                                        </th>

                                        <th>
                                            Utilizador
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>
                                </tbody>

                            </table>

                        </div>

                    </div>


                    <!-- ======================================
                     MANUTENÇÕES
                    ======================================= -->

                    <div
                        class="tab-pane fade"
                        id="abaManutencoes"
                        role="tabpanel">

                        <div
                            class="d-flex
               justify-content-between
               align-items-center
               mb-3">

                            <div>

                                <h4 class="mb-0">
                                    Manutenções de Ativos
                                </h4>

                                <small class="text-muted">
                                    Controlo de manutenções preventivas,
                                    corretivas e inspeções.
                                </small>

                            </div>


                            <button
                                type="button"
                                class="btn btn-primary"
                                id="btnNovaManutencaoAtivo">

                                <i class="fa fa-plus mr-1"></i>

                                Nova Manutenção

                            </button>

                        </div>


                        <!-- ==================================================
         FILTROS
    ================================================== -->

                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Ativo
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroManutencaoAtivo">

                                        <option value="">
                                            Todos
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Tipo
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroManutencaoTipo">

                                        <option value="">
                                            Todos
                                        </option>

                                        <option value="PREVENTIVA">
                                            Preventiva
                                        </option>

                                        <option value="CORRETIVA">
                                            Corretiva
                                        </option>

                                        <option value="INSPECAO">
                                            Inspeção
                                        </option>

                                        <option value="OUTRA">
                                            Outra
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Estado
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroManutencaoEstado">

                                        <option value="">
                                            Todos
                                        </option>

                                        <option value="AGENDADA">
                                            Agendada
                                        </option>

                                        <option value="EM_ANDAMENTO">
                                            Em andamento
                                        </option>

                                        <option value="CONCLUIDA">
                                            Concluída
                                        </option>

                                        <option value="CANCELADA">
                                            Cancelada
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Data inicial
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filtroManutencaoDataInicial">

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Data final
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filtroManutencaoDataFinal">

                                </div>

                            </div>

                        </div>


                        <div class="row">

                            <div class="col-md-5">

                                <div class="form-group">

                                    <label>
                                        Pesquisa
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="filtroManutencaoPesquisa"
                                        placeholder="Ativo, técnico, fornecedor...">

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        &nbsp;
                                    </label>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-block"
                                        id="btnAtualizarManutencoes">

                                        <i class="fa fa-refresh mr-1"></i>

                                        Atualizar

                                    </button>

                                </div>

                            </div>

                        </div>


                        <!-- ==================================================
         TABELA
    ================================================== -->

                        <div class="table-responsive">

                            <table
                                class="table
                   table-bordered
                   table-striped
                   table-hover"
                                id="tabelaManutencoesAtivos">

                                <thead>

                                    <tr>

                                        <th>
                                            Ativo
                                        </th>

                                        <th>
                                            Tipo
                                        </th>

                                        <th>
                                            Prevista
                                        </th>

                                        <th>
                                            Início
                                        </th>

                                        <th>
                                            Realizada
                                        </th>

                                        <th>
                                            Fornecedor / Técnico
                                        </th>

                                        <th class="text-right">
                                            Custo
                                        </th>

                                        <th>
                                            Próxima
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
                                </tbody>

                            </table>

                        </div>

                    </div>

                    <!-- ======================================
                     BAIXAS
                     ======================================= -->

                    <div
                        class="tab-pane fade"
                        id="abaBaixas"
                        role="tabpanel">

                        <div
                            class="d-flex
                            justify-content-between
                            align-items-center
                            mb-3">

                            <div>

                                <h4 class="mb-0">
                                    Baixas e Alienações
                                </h4>

                                <small class="text-muted">
                                    Venda, abate, perda, roubo,
                                    doação e fim de vida útil dos ativos.
                                </small>

                            </div>


                            <div>

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary mr-2"
                                    id="btnConfiguracaoBaixas">

                                    <i class="fa fa-cog mr-1"></i>

                                    Configuração Contabilística

                                </button>


                                <button
                                    type="button"
                                    class="btn btn-danger"
                                    id="btnNovaBaixaAtivo">

                                    <i class="fa fa-sign-out mr-1"></i>

                                    Registar Baixa

                                </button>

                            </div>
                        </div>


                        <!-- FILTROS -->

                        <div class="row">

                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Tipo
                                    </label>

                                    <select
                                        class="form-control"
                                        id="filtroBaixaTipo">

                                        <option value="">
                                            Todos
                                        </option>

                                        <option value="VENDA">
                                            Venda
                                        </option>

                                        <option value="ABATE">
                                            Abate
                                        </option>

                                        <option value="PERDA">
                                            Perda
                                        </option>

                                        <option value="ROUBO">
                                            Roubo
                                        </option>

                                        <option value="DOACAO">
                                            Doação
                                        </option>

                                        <option value="FIM_VIDA_UTIL">
                                            Fim de Vida Útil
                                        </option>

                                        <option value="OUTRO">
                                            Outro
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Data inicial
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filtroBaixaDataInicial">

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Data final
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filtroBaixaDataFinal">

                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="form-group">

                                    <label>
                                        Pesquisa
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="filtroBaixaPesquisa"
                                        placeholder="Ativo, documento, motivo...">

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        &nbsp;
                                    </label>

                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-block"
                                        id="btnAtualizarBaixas">

                                        <i class="fa fa-refresh mr-1"></i>

                                        Atualizar

                                    </button>

                                </div>

                            </div>

                        </div>


                        <!-- TABELA -->

                        <div class="table-responsive">

                            <table
                                class="table
                   table-bordered
                   table-striped
                   table-hover"
                                id="tabelaBaixasAtivos">

                                <thead>

                                    <tr>

                                        <th>
                                            Data
                                        </th>

                                        <th>
                                            Ativo
                                        </th>

                                        <th>
                                            Tipo
                                        </th>

                                        <th class="text-right">
                                            Aquisição
                                        </th>

                                        <th class="text-right">
                                            Dep./Amort.
                                        </th>

                                        <th class="text-right">
                                            Valor Líquido
                                        </th>

                                        <th class="text-right">
                                            Alienação
                                        </th>

                                        <th class="text-right">
                                            Ganho / Perda
                                        </th>

                                        <th>
                                            Documento
                                        </th>

                                        <th>
                                            Utilizador
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


    <!-- ==================================================
     MODAL NOVO / EDITAR ATIVO
================================================== -->

    <div
        class="modal fade"
        id="modalAtivoEmpresa"
        tabindex="-1"
        role="dialog"
        aria-hidden="true">

        <div
            class="modal-dialog
               modal-xl
               modal-dialog-scrollable"
            role="document">

            <div class="modal-content">

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="tituloModalAtivo">

                        Novo Ativo

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


                <form id="formAtivoEmpresa">

                    <div class="modal-body">

                        <input
                            type="hidden"
                            id="ativoId"
                            name="id">


                        <!-- ==================================
                         IDENTIFICAÇÃO
                    =================================== -->

                        <h5 class="mb-3">
                            Identificação
                        </h5>

                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Código
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="ativoCodigo"
                                        name="codigo"
                                        readonly>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Tipo de Ativo
                                        <span class="text-danger">
                                            *
                                        </span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="ativoTipo"
                                        name="tipo"
                                        required>

                                        <option value="">
                                            Selecione
                                        </option>

                                        <option value="TANGIVEL">
                                            Tangível
                                        </option>

                                        <option value="INTANGIVEL">
                                            Intangível
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>
                                        Categoria Contabilística
                                        <span class="text-danger">
                                            *
                                        </span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="ativoContaContabil"
                                        name="conta_contabil_id"
                                        required>

                                        <option value="">
                                            Primeiro selecione
                                            o tipo de ativo
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>


                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>
                                        Descrição
                                        <span class="text-danger">
                                            *
                                        </span>
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="ativoDescricao"
                                        name="descricao"
                                        required>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Marca
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="ativoMarca"
                                        name="marca">

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Modelo
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="ativoModelo"
                                        name="modelo">

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="form-group">

                                    <label>
                                        Nº Série
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="ativoNumeroSerie"
                                        name="numero_serie">

                                </div>

                            </div>

                        </div>


                        <hr>


                        <!-- ==================================
                         VALORES E VIDA ÚTIL
                    =================================== -->

                        <h5 class="mb-3">
                            Aquisição e Vida Útil
                        </h5>

                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Data de Aquisição
                                        <span class="text-danger">
                                            *
                                        </span>
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="ativoDataAquisicao"
                                        name="data_aquisicao"
                                        required>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Entrada em Uso
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="ativoDataEntradaUso"
                                        name="data_entrada_uso">

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Valor de Aquisição
                                        <span class="text-danger">
                                            *
                                        </span>
                                    </label>

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="form-control"
                                        id="ativoValorAquisicao"
                                        name="valor_aquisicao"
                                        required>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Valor Residual
                                    </label>

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="form-control"
                                        id="ativoValorResidual"
                                        name="valor_residual"
                                        value="0">

                                </div>

                            </div>

                        </div>


                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Vida Útil
                                    </label>

                                    <div class="input-group">

                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            class="form-control"
                                            id="ativoVidaUtilAnos"
                                            name="vida_util_anos">

                                        <div class="input-group-append">

                                            <span
                                                class="input-group-text">

                                                anos

                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Método
                                    </label>

                                    <select
                                        class="form-control"
                                        id="ativoMetodoDepreciacao"
                                        name="metodo_depreciacao">

                                        <option value="LINEAR">
                                            Linear
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Taxa Anual (%)
                                    </label>

                                    <input
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.0001"
                                        class="form-control"
                                        id="ativoTaxaAnual"
                                        name="taxa_anual">

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label id="labelValorAnual">
                                        Depreciação Anual
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="ativoValorAnual"
                                        readonly>

                                </div>

                            </div>

                        </div>


                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label id="labelValorMensal">
                                        Depreciação Mensal
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="ativoValorMensal"
                                        readonly>

                                </div>

                            </div>

                        </div>


                        <hr>


                        <!-- ==================================
                         AFETAÇÃO
                    =================================== -->

                        <h5 class="mb-3">
                            Afetação e Localização
                        </h5>

                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Filial
                                    </label>

                                    <select
                                        class="form-control"
                                        id="ativoFilial"
                                        name="filial_id">

                                        <option value="">
                                            Selecione
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Departamento
                                    </label>

                                    <select
                                        class="form-control"
                                        id="ativoDepartamento"
                                        name="departamento_id">

                                        <option value="">
                                            Selecione
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Localização
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="ativoLocalizacao"
                                        name="localizacao">

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Responsável
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="ativoResponsavel"
                                        name="responsavel">

                                </div>

                            </div>

                        </div>


                        <hr>


                        <!-- ==================================
                         AQUISIÇÃO
                    =================================== -->

                        <h5 class="mb-3">
                            Dados da Aquisição
                        </h5>

                        <div class="row">

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label>
                                        Fornecedor
                                    </label>

                                    <select
                                        class="form-control"
                                        id="ativoFornecedor"
                                        name="fornecedor_id">

                                        <option value="">
                                            Selecione
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="form-group">

                                    <label>
                                        Documento
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="ativoNumeroDocumento"
                                        name="numero_documento"
                                        placeholder="Factura, recibo...">

                                </div>

                            </div>

                        </div>


                        <hr>


                        <!-- ==================================
                         ESTADO
                    =================================== -->

                        <h5 class="mb-3">
                            Estado do Ativo
                        </h5>

                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Estado Físico
                                    </label>

                                    <select
                                        class="form-control"
                                        id="ativoEstadoFisico"
                                        name="estado_fisico">

                                        <option value="NOVO">
                                            Novo
                                        </option>

                                        <option value="BOM">
                                            Bom
                                        </option>

                                        <option value="REGULAR">
                                            Regular
                                        </option>

                                        <option value="DANIFICADO">
                                            Danificado
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Situação
                                    </label>

                                    <select
                                        class="form-control"
                                        id="ativoSituacao"
                                        name="situacao">

                                        <option value="EM_USO">
                                            Em uso
                                        </option>

                                        <option value="ARMAZENADO">
                                            Armazenado
                                        </option>

                                        <option value="EM_MANUTENCAO">
                                            Em manutenção
                                        </option>

                                        <option value="CEDIDO">
                                            Cedido
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>
                                        Observações
                                    </label>

                                    <textarea
                                        class="form-control"
                                        id="ativoObservacoes"
                                        name="observacoes"
                                        rows="3">
                                </textarea>

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
                            id="btnGuardarAtivo">

                            <i class="fa fa-save mr-1"></i>

                            Guardar

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="modalConfiguracaoContabilAtivos"
        tabindex="-1"
        role="dialog">

        <div
            class="modal-dialog modal-lg"
            role="document">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Configuração Contabilística dos Ativos
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>


                <form id="formConfiguracaoContabilAtivo">

                    <div class="modal-body">

                        <div class="form-group">

                            <label>
                                Categoria do Ativo
                            </label>

                            <select
                                class="form-control"
                                id="configContaAtivo"
                                name="conta_ativo_id"
                                required>

                                <option value="">
                                    Selecione
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Conta de Depreciação /
                                Amortização Acumulada
                            </label>

                            <select
                                class="form-control"
                                id="configContaAcumulada"
                                name="conta_acumulada_id"
                                required>

                                <option value="">
                                    Selecione
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Conta de Gasto
                            </label>

                            <select
                                class="form-control"
                                id="configContaGasto"
                                name="conta_gasto_id"
                                required>

                                <option value="">
                                    Selecione
                                </option>

                            </select>

                        </div>


                        <hr>


                        <h6>
                            Configurações existentes
                        </h6>


                        <div class="table-responsive">

                            <table
                                class="table table-sm table-bordered"
                                id="tabelaConfiguracoesContabeisAtivos">

                                <thead>

                                    <tr>

                                        <th>
                                            Categoria
                                        </th>

                                        <th>
                                            Conta Acumulada
                                        </th>

                                        <th>
                                            Conta de Gasto
                                        </th>

                                        <th
                                            class="text-center"
                                            style="width:80px;">

                                            Ação

                                        </th>

                                    </tr>

                                </thead>

                                <tbody>
                                </tbody>

                            </table>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">

                            Fechar

                        </button>


                        <button
                            type="submit"
                            class="btn btn-primary">

                            <i class="fa fa-save mr-1"></i>

                            Guardar

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="modalMovimentacaoAtivo"
        tabindex="-1"
        role="dialog"
        aria-hidden="true">

        <div
            class="modal-dialog
               modal-xl
               modal-dialog-scrollable"
            role="document">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Nova Movimentação
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>


                <form id="formMovimentacaoAtivo">

                    <div class="modal-body">

                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>
                                        Ativo
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="movimentacaoAtivo"
                                        name="ativo_id"
                                        required>

                                        <option value="">
                                            Selecione
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Tipo de Movimentação
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="movimentacaoTipo"
                                        name="tipo_movimentacao"
                                        required>

                                        <option value="">
                                            Selecione
                                        </option>

                                        <option value="TRANSFERENCIA">
                                            Transferência
                                        </option>

                                        <option value="ALTERACAO_RESPONSAVEL">
                                            Alteração de Responsável
                                        </option>

                                        <option value="ALTERACAO_LOCALIZACAO">
                                            Alteração de Localização
                                        </option>

                                        <option value="CEDENCIA">
                                            Cedência
                                        </option>

                                        <option value="DEVOLUCAO">
                                            Devolução
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Data
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="movimentacaoData"
                                        name="data_movimentacao"
                                        required>

                                </div>

                            </div>

                        </div>


                        <hr>


                        <h5 class="mb-3">
                            Situação Atual
                        </h5>


                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Filial Atual
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="movimentacaoFilialAtual"
                                        readonly>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Departamento Atual
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="movimentacaoDepartamentoAtual"
                                        readonly>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Localização Atual
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="movimentacaoLocalizacaoAtual"
                                        readonly>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Responsável Atual
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="movimentacaoResponsavelAtual"
                                        readonly>

                                </div>

                            </div>

                        </div>


                        <hr>


                        <h5 class="mb-3">
                            Novo Destino / Afetação
                        </h5>


                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Nova Filial
                                    </label>

                                    <select
                                        class="form-control"
                                        id="movimentacaoFilialDestino"
                                        name="filial_destino_id">

                                        <option value="">
                                            Manter atual
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Novo Departamento
                                    </label>

                                    <select
                                        class="form-control"
                                        id="movimentacaoDepartamentoDestino"
                                        name="departamento_destino_id">

                                        <option value="">
                                            Manter atual
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Nova Localização
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="movimentacaoLocalizacaoDestino"
                                        name="localizacao_destino"
                                        placeholder="Deixe vazio para manter">

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Novo Responsável
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="movimentacaoResponsavelDestino"
                                        name="responsavel_destino"
                                        placeholder="Deixe vazio para manter">

                                </div>

                            </div>

                        </div>


                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>
                                        Motivo
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="movimentacaoMotivo"
                                        name="motivo">

                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>
                                        Observações
                                    </label>

                                    <textarea
                                        class="form-control"
                                        id="movimentacaoObservacoes"
                                        name="observacoes"
                                        rows="3">
                                </textarea>

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
                            id="btnGuardarMovimentacaoAtivo">

                            <i class="fa fa-save mr-1"></i>

                            Guardar Movimentação

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="modalManutencaoAtivo"
        tabindex="-1"
        role="dialog"
        aria-hidden="true">

        <div
            class="modal-dialog
               modal-xl
               modal-dialog-scrollable"
            role="document">

            <div class="modal-content">

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="tituloModalManutencaoAtivo">

                        Nova Manutenção

                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>


                <form id="formManutencaoAtivo">

                    <div class="modal-body">

                        <input
                            type="hidden"
                            id="manutencaoId"
                            name="id">


                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>
                                        Ativo
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="manutencaoAtivo"
                                        name="ativo_id"
                                        required>

                                        <option value="">
                                            Selecione
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Tipo
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="manutencaoTipo"
                                        name="tipo_manutencao"
                                        required>

                                        <option value="">
                                            Selecione
                                        </option>

                                        <option value="PREVENTIVA">
                                            Preventiva
                                        </option>

                                        <option value="CORRETIVA">
                                            Corretiva
                                        </option>

                                        <option value="INSPECAO">
                                            Inspeção
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Estado
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="manutencaoEstado"
                                        name="estado"
                                        required>

                                        <option value="AGENDADA">
                                            Agendada
                                        </option>

                                        <option value="EM_ANDAMENTO">
                                            Em andamento
                                        </option>

                                        <option value="CONCLUIDA">
                                            Concluída
                                        </option>

                                        <option value="CANCELADA">
                                            Cancelada
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>


                        <hr>


                        <h5 class="mb-3">
                            Datas
                        </h5>


                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Data Prevista
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="manutencaoDataPrevista"
                                        name="data_prevista"
                                        required>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Data de Início
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="manutencaoDataInicio"
                                        name="data_inicio">

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Data Realizada
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="manutencaoDataRealizada"
                                        name="data_realizada">

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Próxima Manutenção
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="manutencaoProxima"
                                        name="proxima_manutencao">

                                </div>

                            </div>

                        </div>


                        <hr>


                        <h5 class="mb-3">
                            Prestador e Custo
                        </h5>


                        <div class="row">

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label>
                                        Fornecedor
                                    </label>

                                    <select
                                        class="form-control"
                                        id="manutencaoFornecedor"
                                        name="fornecedor_id">

                                        <option value="">
                                            Selecione
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="form-group">

                                    <label>
                                        Técnico Responsável
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="manutencaoTecnico"
                                        name="tecnico_responsavel">

                                </div>

                            </div>


                            <div class="col-md-4">

                                <div class="form-group">

                                    <label>
                                        Custo
                                    </label>

                                    <div class="input-group">

                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="form-control"
                                            id="manutencaoCusto"
                                            name="custo"
                                            value="0">

                                        <div class="input-group-append">

                                            <span class="input-group-text">
                                                MZN
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <hr>


                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>
                                        Descrição do Serviço
                                    </label>

                                    <textarea
                                        class="form-control"
                                        id="manutencaoDescricao"
                                        name="descricao"
                                        rows="4"
                                        placeholder="Descreva o serviço a realizar..."></textarea>

                                </div>

                            </div>


                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>
                                        Observações
                                    </label>

                                    <textarea
                                        class="form-control"
                                        id="manutencaoObservacoes"
                                        name="observacoes"
                                        rows="4"></textarea>

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
                            id="btnGuardarManutencaoAtivo">

                            <i class="fa fa-save mr-1"></i>

                            Guardar

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="modalBaixaAtivo"
        tabindex="-1"
        role="dialog"
        aria-hidden="true">

        <div
            class="modal-dialog
               modal-xl
               modal-dialog-scrollable"
            role="document">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Registar Baixa / Alienação
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


                <form id="formBaixaAtivo">

                    <div class="modal-body">

                        <div class="row">

                            <div class="col-md-6">

                                <div class="form-group">

                                    <label>
                                        Ativo
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="baixaAtivo"
                                        name="ativo_id"
                                        required>

                                        <option value="">
                                            Selecione
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Tipo
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="baixaTipo"
                                        name="tipo"
                                        required>

                                        <option value="">
                                            Selecione
                                        </option>

                                        <option value="VENDA">
                                            Venda
                                        </option>

                                        <option value="ABATE">
                                            Abate
                                        </option>

                                        <option value="PERDA">
                                            Perda
                                        </option>

                                        <option value="ROUBO">
                                            Roubo
                                        </option>

                                        <option value="DOACAO">
                                            Doação
                                        </option>

                                        <option value="FIM_VIDA_UTIL">
                                            Fim de Vida Útil
                                        </option>

                                        <option value="OUTRO">
                                            Outro
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Data da Baixa
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        id="baixaData"
                                        name="data_baixa"
                                        required>

                                </div>

                            </div>

                        </div>


                        <hr>


                        <h5 class="mb-3">
                            Situação Contabilística
                        </h5>


                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Valor de Aquisição
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="baixaValorAquisicao"
                                        readonly>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Dep./Amort. Acumulada
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="baixaDepreciacaoAcumulada"
                                        readonly>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Valor Líquido
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="baixaValorLiquido"
                                        readonly>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-group">

                                    <label>
                                        Valor da Alienação
                                    </label>

                                    <div class="input-group">

                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="form-control"
                                            id="baixaValorAlienacao"
                                            name="valor_alienacao"
                                            disabled>

                                        <div class="input-group-append">

                                            <span class="input-group-text">
                                                MZN
                                            </span>

                                        </div>

                                    </div>

                                    <small
                                        class="text-muted"
                                        id="textoValorAlienacao">

                                        Disponível para venda.

                                    </small>

                                </div>

                            </div>

                        </div>

                        <div
                            class="row"
                            id="blocoRecebimentoBaixa"
                            style="display:none;">


                            <div class="col-md-4">

                                <div class="form-group">

                                    <label>
                                        Forma de Recebimento
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="baixaFormaRecebimento"
                                        name="forma_recebimento">

                                        <option value="">
                                            Selecione
                                        </option>

                                        <option value="CAIXA">
                                            Caixa
                                        </option>

                                        <option value="BANCO">
                                            Banco
                                        </option>

                                        <option value="CONTA_RECEBER">
                                            Conta a Receber
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="col-md-8">

                                <div class="form-group">

                                    <label>
                                        Conta de Recebimento
                                        <span class="text-danger">*</span>
                                    </label>

                                    <select
                                        class="form-control"
                                        id="baixaContaRecebimento"
                                        name="conta_recebimento_id">

                                        <option value="">
                                            Selecione primeiro a forma
                                            de recebimento
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>


                        <!-- PREVIEW GANHO / PERDA -->

                        <div
                            class="alert alert-light border"
                            id="resumoGanhoPerdaBaixa">

                            <div class="row align-items-center">

                                <div class="col-md-8">

                                    <strong>
                                        Resultado estimado da baixa
                                    </strong>

                                    <br>

                                    <small class="text-muted">
                                        Valor recebido menos valor líquido contabilístico.
                                    </small>

                                </div>


                                <div class="col-md-4 text-right">

                                    <h4
                                        class="mb-0"
                                        id="baixaGanhoPerda">

                                        0,00 MZN

                                    </h4>

                                    <small
                                        id="baixaGanhoPerdaTexto"
                                        class="text-muted">

                                        Selecione um ativo.

                                    </small>

                                </div>

                            </div>

                        </div>


                        <hr>


                        <div class="row">

                            <div class="col-md-4">

                                <div class="form-group">

                                    <label>
                                        Documento
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="baixaDocumento"
                                        name="documento"
                                        placeholder="Factura, auto, participação...">

                                </div>

                            </div>


                            <div class="col-md-8">

                                <div class="form-group">

                                    <label>
                                        Motivo
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="baixaMotivo"
                                        name="motivo"
                                        placeholder="Motivo da baixa/alienação">

                                </div>

                            </div>

                        </div>


                        <div class="form-group">

                            <label>
                                Observações
                            </label>

                            <textarea
                                class="form-control"
                                id="baixaObservacoes"
                                name="observacoes"
                                rows="3"></textarea>

                        </div>


                        <div class="alert alert-warning mb-0">

                            <i class="fa fa-exclamation-triangle mr-1"></i>

                            Esta operação representa uma saída
                            definitiva do ativo.

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
                            id="btnGuardarBaixaAtivo">

                            <i class="fa fa-sign-out mr-1"></i>

                            Confirmar Baixa

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="modalConfiguracaoBaixas"
        tabindex="-1"
        role="dialog"
        aria-hidden="true">

        <div
            class="modal-dialog modal-lg"
            role="document">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Configuração Contabilística das Baixas
                    </h5>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal">

                        <span>&times;</span>

                    </button>

                </div>


                <form id="formConfiguracaoBaixas">

                    <div class="modal-body">

                        <div class="form-group">

                            <label>
                                Conta de Ganho na Alienação
                            </label>

                            <select
                                class="form-control"
                                id="configBaixaContaGanho"
                                name="conta_ganho_alienacao_id"
                                required>

                                <option value="">
                                    Selecione
                                </option>

                            </select>

                            <small class="text-muted">
                                Ex.: 7.6.3.1 - Alienação
                            </small>

                        </div>


                        <div class="form-group">

                            <label>
                                Conta de Perda na Alienação
                            </label>

                            <select
                                class="form-control"
                                id="configBaixaContaPerda"
                                name="conta_perda_alienacao_id"
                                required>

                                <option value="">
                                    Selecione
                                </option>

                            </select>

                            <small class="text-muted">
                                Ex.: 6.8.3.1 - Alienação
                            </small>

                        </div>


                        <div class="form-group">

                            <label>
                                Conta de Abate
                            </label>

                            <select
                                class="form-control"
                                id="configBaixaContaAbate"
                                name="conta_abate_id"
                                required>

                                <option value="">
                                    Selecione
                                </option>

                            </select>

                            <small class="text-muted">
                                Ex.: 6.8.3.2 - Abates
                            </small>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-dismiss="modal">

                            Fechar

                        </button>


                        <button
                            type="submit"
                            class="btn btn-primary">

                            <i class="fa fa-save mr-1"></i>

                            Guardar

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <style>
        /* ==================================================
   MODAL BAIXA / ALIENAÇÃO
================================================== */

        #modalBaixaAtivo {
            overflow: hidden;
        }

        #modalBaixaAtivo .modal-dialog {
            height: calc(100vh - 30px);
            max-height: calc(100vh - 30px);
            margin: 15px auto;
        }

        #modalBaixaAtivo .modal-content {
            height: 100%;
            max-height: 100%;
            display: flex;
            flex-direction: column;
        }

        #modalBaixaAtivo .modal-header {
            flex: 0 0 auto;
        }

        #modalBaixaAtivo form {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }

        #modalBaixaAtivo .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
        }

        #modalBaixaAtivo .modal-footer {
            flex: 0 0 auto;
            background: #fff;
        }

        #abasAtivosEmpresa .nav-link {
            white-space: nowrap;
        }


        #tabelaAtivosEmpresa th,
        #tabelaAtivosEmpresa td {
            vertical-align: middle;
        }


        #modalAtivoEmpresa .modal-body {
            max-height: calc(100vh - 190px);

            overflow-y: auto;
        }


        #modalAtivoEmpresa h5 {
            font-weight: 600;
        }


        @media (max-width: 991px) {

            #abasAtivosEmpresa {
                overflow-x: auto;
                flex-wrap: nowrap;
            }
        }
    </style>