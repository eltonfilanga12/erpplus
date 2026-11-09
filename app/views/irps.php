<!-- Page Wrapper -->
<div class="page-wrapper">

    <div class="content container-fluid">

        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="page-header">

            <div class="row align-items-center">

                <div class="col">

                    <h3 class="page-title">
                        Tabela Nacional de IRPS
                    </h3>

                    <ul class="breadcrumb">

                        <li class="breadcrumb-item">

                            <a href="index.php?page=dashboard">
                                Dashboard
                            </a>

                        </li>

                        <li class="breadcrumb-item active">
                            Tabela IRPS
                        </li>

                    </ul>

                </div>


                <div class="col-auto">

                    <button
                        type="button"
                        class="btn btn-primary"
                        id="btnPesquisarIrps">

                        <i class="fa fa-search"></i>

                        <span class="d-none d-sm-inline">
                            Consultar
                        </span>

                    </button>

                </div>

            </div>

        </div>


        <!-- =====================================================
             CARDS DE RESUMO
        ====================================================== -->

        <div class="row">


            <!-- ANO -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span
                                class="dash-widget-icon bg-primary">

                                <i class="fa fa-calendar"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="cardAnoIrps">
                                    -
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Ano consultado
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ESCALÕES -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span
                                class="dash-widget-icon bg-info">

                                <i class="fa fa-list-ol"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="cardEscaloesIrps">
                                    0
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Escalões
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <!-- MOEDA -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span
                                class="dash-widget-icon bg-warning">

                                <i class="fa fa-money"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="cardMoedaIrps">
                                    -
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Moeda
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ESTADO -->

            <div class="col-xl-3 col-sm-6 col-12">

                <div class="card">

                    <div class="card-body">

                        <div class="dash-widget-header">

                            <span
                                class="dash-widget-icon bg-success">

                                <i class="fa fa-check-circle"></i>

                            </span>

                            <div class="dash-count">

                                <h3 id="cardEstadoIrps">
                                    -
                                </h3>

                            </div>

                        </div>

                        <div class="dash-widget-info">

                            <h6 class="text-muted">
                                Estado da tabela
                            </h6>

                        </div>

                    </div>

                </div>

            </div>


        </div>


        <!-- =====================================================
             CONSULTA POR ANO
        ====================================================== -->

        <div class="card">

            <div class="card-body">

                <div class="row align-items-end">


                    <div class="col-md-4">

                        <div class="form-group">

                            <label for="filtroAnoIrps">
                                Ano fiscal
                            </label>

                            <input
                                type="number"
                                id="filtroAnoIrps"
                                class="form-control"
                                min="2014"
                                step="1"
                                placeholder="Ex.: 2026">

                        </div>

                    </div>


                    <div class="col-md-3">

                        <div class="form-group">

                            <button
                                type="button"
                                class="btn btn-primary btn-block"
                                id="btnConsultarAnoIrps">

                                <i class="fa fa-search"></i>
                                Consultar tabela

                            </button>

                        </div>

                    </div>


                    <div class="col-md-5">

                        <div class="form-group">

                            <div class="alert alert-info mb-0 py-2">

                                <i class="fa fa-info-circle"></i>

                                A tabela de IRPS é nacional e
                                partilhada por todas as empresas.

                            </div>

                        </div>

                    </div>


                </div>

            </div>

        </div>


        <!-- =====================================================
             TABELA VIGENTE
        ====================================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">

                    <i class="fa fa-file-text-o"></i>
                    Tabela Vigente

                </h4>

            </div>


            <div class="card-body">


                <div class="row">


                    <!-- Nome -->

                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Nome da tabela
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="irpsNomeTabela"
                                value="-"
                                readonly>

                        </div>

                    </div>


                    <!-- Vigência -->

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Vigência
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="irpsVigencia"
                                value="-"
                                readonly>

                        </div>

                    </div>


                    <!-- Moeda -->

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Moeda
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="irpsMoeda"
                                value="-"
                                readonly>

                        </div>

                    </div>


                </div>


                <div class="row">


                    <!-- Descrição -->

                    <div class="col-md-9">

                        <div class="form-group">

                            <label>
                                Descrição
                            </label>

                            <textarea
                                class="form-control"
                                id="irpsDescricao"
                                rows="2"
                                readonly>-</textarea>

                        </div>

                    </div>


                    <!-- Estado -->

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>
                                Estado
                            </label>

                            <div
                                class="form-control d-flex align-items-center"
                                id="irpsEstado">

                                -

                            </div>

                        </div>

                    </div>


                </div>


            </div>

        </div>


        <!-- =====================================================
             ESCALÕES
        ====================================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">

                    <i class="fa fa-table"></i>
                    Escalões de Retenção do IRPS

                </h4>

            </div>


            <div class="card-body">


                <div class="table-responsive">

                    <table
                        id="tabelaEscaloesIrps"
                        class="table table-striped custom-table mb-0">

                        <thead class="thead-irps">

                            <tr>

                                <th class="faixa-salarial-irps">
                                    Faixa salarial
                                </th>

                                <th
                                    colspan="5"
                                    class="text-center grupo-dependentes-irps">

                                    Valor de IRPS no limite inferior por número de dependentes

                                </th>

                                <th class="text-center coeficiente-irps">
                                    Coeficiente
                                </th>

                            </tr>


                            <tr class="linha-dependentes-irps">

                                <th></th>

                                <th class="text-center">
                                    0
                                </th>

                                <th class="text-center">
                                    1
                                </th>

                                <th class="text-center">
                                    2
                                </th>

                                <th class="text-center">
                                    3
                                </th>

                                <th class="text-center">
                                    4 ou mais
                                </th>

                                <th></th>

                            </tr>

                        </thead>


                        <tbody id="tbodyEscaloesIrps">

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


                <!-- Legenda -->

                <div class="alert alert-light border mt-4 mb-0">

                    <div class="mb-2">

                        <strong>
                            <i class="fa fa-info-circle"></i>
                            Legenda
                        </strong>

                    </div>


                    <div>

                        <strong>—</strong>

                        significa que não há imposto a reter
                        e não se aplica o coeficiente.

                    </div>


                    <div class="mt-1">

                        <strong>0,00</strong>

                        significa que apenas se aplica
                        o coeficiente.

                    </div>

                </div>


            </div>

        </div>


        <!-- =====================================================
             HISTÓRICO
        ====================================================== -->

        <div class="card">

            <div class="card-header">

                <h4 class="card-title mb-0">

                    <i class="fa fa-history"></i>
                    Histórico das Tabelas de IRPS

                </h4>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table
                        id="tabelaHistoricoIrps"
                        class="table table-striped custom-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Nome
                                </th>

                                <th>
                                    Ano inicial
                                </th>

                                <th>
                                    Ano final
                                </th>

                                <th>
                                    Moeda
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th class="text-center">
                                    Ações
                                </th>

                            </tr>

                        </thead>


                        <tbody id="tbodyHistoricoIrps">

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

        </div>


    </div>

</div>

<style>
    /* =====================================================
       TABELA IRPS
    ====================================================== */

    #tabelaEscaloesIrps th,
    #tabelaEscaloesIrps td {
        vertical-align: middle;
        white-space: nowrap;
    }


    #tabelaHistoricoIrps th,
    #tabelaHistoricoIrps td {
        vertical-align: middle;
    }


    #tabelaEscaloesIrps thead tr:first-child th {
        vertical-align: middle;
    }


    /* =====================================================
       VALORES
    ====================================================== */

    .valor-irps {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }


    /* =====================================================
       ESTADO
    ====================================================== */

    .badge-irps {
        display: inline-block;
        min-width: 75px;
        padding: 6px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
    }


    .irps-activo {
        background-color: #d4edda;
        color: #155724;
    }


    .irps-inactivo {
        background-color: #e2e3e5;
        color: #383d41;
    }


    /* =====================================================
       RESPONSIVO
    ====================================================== */

    @media (max-width: 767.98px) {

        #tabelaEscaloesIrps {
            min-width: 900px;
        }

    }

    #tabelaEscaloesIrps {
        width: 100%;
        border-collapse: collapse;
    }


    /* Todas as células do cabeçalho */
    #tabelaEscaloesIrps thead th {
        vertical-align: middle !important;
        font-weight: 600;
        white-space: nowrap;
    }


    /* Primeira linha */
    #tabelaEscaloesIrps .thead-irps>tr:first-child>th {
        padding-top: 12px;
        padding-bottom: 12px;
    }


    /* Faixa salarial */
    #tabelaEscaloesIrps .faixa-salarial-irps {
        width: 32%;
        text-align: left;
        vertical-align: middle !important;
    }


    /* Grupo dos dependentes */
    #tabelaEscaloesIrps .grupo-dependentes-irps {
        width: 55%;
        text-align: center !important;
        vertical-align: middle !important;
    }


    /* Coeficiente */
    #tabelaEscaloesIrps .coeficiente-irps {
        width: 13%;
        text-align: center !important;
        vertical-align: middle !important;
    }


    /* Segunda linha */
    #tabelaEscaloesIrps .linha-dependentes-irps th {
        text-align: center !important;
        vertical-align: middle !important;
        padding-top: 8px;
        padding-bottom: 8px;
    }


    /* Corpo */
    #tabelaEscaloesIrps tbody td {
        vertical-align: middle !important;
    }


    /* Valores dos dependentes */
    #tabelaEscaloesIrps tbody td:not(:first-child) {
        text-align: center;
    }


    /* Primeira coluna */
    #tabelaEscaloesIrps tbody td:first-child {
        text-align: left;
    }
</style>