"use strict";


/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

$(document).ready(function () {

    let chartVendas = null;
    let chartReceitasDespesas = null;
    let chartFluxoCaixa = null;
    let chartTopProdutos = null;


    /*
    |--------------------------------------------------------------------------
    | Inicialização
    |--------------------------------------------------------------------------
    */

    carregarDashboard();


    /*
    |--------------------------------------------------------------------------
    | Alterar período
    |--------------------------------------------------------------------------
    */

    $("#dashboardPeriodo").on(
        "change",
        function () {

            carregarDashboard();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Carregar Dashboard
    |--------------------------------------------------------------------------
    */

    function carregarDashboard() {

        const periodo =
            $("#dashboardPeriodo").val()
            || "mes";


        bloquearDashboard();


        $.ajax({

            url:
                "/erpplus/app/ajax/dashboard_ajax.php",

            type:
                "POST",

            dataType:
                "json",

            contentType:
                "application/json; charset=utf-8",

            data:
                JSON.stringify({

                    acao:
                        "carregar",

                    periodo:
                        periodo

                }),


            success:
                function (response) {

                    if (
                        !response
                        ||
                        response.success !== true
                    ) {

                        mostrarErro(
                            response?.message
                            || "Não foi possível carregar o Dashboard."
                        );

                        limparDashboard();

                        return;
                    }


                    const dados =
                        response.data
                        || {};


                    preencherCards(
                        dados.cards
                        || {}
                    );


                    preencherContabilidade(
                        dados.contabilidade
                        || {}
                    );


                    preencherIndicadores(
                        dados
                    );


                    preencherGraficos(
                        dados.graficos
                        || {}
                    );

                },


            error:
                function (xhr) {

                    let mensagem =
                        "Erro ao carregar o Dashboard.";


                    if (
                        xhr.responseJSON
                        &&
                        xhr.responseJSON.message
                    ) {

                        mensagem =
                            xhr.responseJSON.message;
                    }


                    console.error(
                        "Dashboard:",
                        xhr.responseText
                    );


                    mostrarErro(
                        mensagem
                    );


                    limparDashboard();

                },


            complete:
                function () {

                    desbloquearDashboard();

                }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Cards principais
    |--------------------------------------------------------------------------
    */

    function preencherCards(
        cards
    ) {

        $("#dashVendas").text(
            formatarMoeda(
                cards.vendas
            )
        );


        $("#dashReceitas").text(
            formatarMoeda(
                cards.receitas
            )
        );


        $("#dashDespesas").text(
            formatarMoeda(
                cards.despesas
            )
        );


        $("#dashLucro").text(
            formatarMoeda(
                cards.lucro
            )
        );


        $("#dashStock").text(
            formatarMoeda(
                cards.stock
            )
        );


        $("#dashClientes").text(
            formatarNumero(
                cards.clientes
            )
        );


        $("#dashReceber").text(
            formatarMoeda(
                cards.contas_receber
            )
        );


        $("#dashPagar").text(
            formatarMoeda(
                cards.contas_pagar
            )
        );


        /*
         * Caso depois adicionemos
         * o card de funcionários
         * na View.
         */

        if (
            $("#dashFuncionarios").length
        ) {

            $("#dashFuncionarios").text(
                formatarNumero(
                    cards.funcionarios
                )
            );
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Contabilidade
    |--------------------------------------------------------------------------
    */

    function preencherContabilidade(
        dados
    ) {

        $("#dashAtivo").text(
            formatarMoeda(
                dados.ativo
            )
        );


        $("#dashPassivo").text(
            formatarMoeda(
                dados.passivo
            )
        );


        $("#dashCapital").text(
            formatarMoeda(
                dados.capital_proprio
            )
        );


        $("#dashResultado").text(
            formatarMoeda(
                dados.resultado_liquido
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Indicadores financeiros
    |--------------------------------------------------------------------------
    */

    function preencherIndicadores(
        dados
    ) {

        const contabilidade =
            dados.contabilidade
            || {};


        const resumos =
            dados.resumos
            || {};


        const ativo =
            numero(
                contabilidade.ativo
            );


        const passivo =
            numero(
                contabilidade.passivo
            );


        const capital =
            numero(
                contabilidade.capital_proprio
            );


        const resultado =
            numero(
                contabilidade.resultado_liquido
            );


        const vendas =
            numero(
                dados.cards?.vendas
            );


        /*
        |--------------------------------------------------------------------------
        | Liquidez
        |--------------------------------------------------------------------------
        |
        | Por enquanto:
        | ativo / passivo.
        |
        | Depois podemos tornar este indicador tecnicamente mais preciso usando
        | somente ativo corrente / passivo corrente.
        |
        */

        const liquidez =
            passivo !== 0
                ? ativo / passivo
                : 0;


        /*
        |--------------------------------------------------------------------------
        | Endividamento
        |--------------------------------------------------------------------------
        */

        const endividamento =
            ativo !== 0
                ? (
                    passivo
                    / ativo
                ) * 100
                : 0;


        /*
        |--------------------------------------------------------------------------
        | Rentabilidade
        |--------------------------------------------------------------------------
        */

        const rentabilidade =
            vendas !== 0
                ? (
                    resultado
                    / vendas
                ) * 100
                : 0;


        $("#dashLiquidez").text(
            formatarDecimal(
                liquidez
            )
        );


        $("#dashEndividamento").text(
            formatarPercentagem(
                endividamento
            )
        );


        $("#dashRentabilidade").text(
            formatarPercentagem(
                rentabilidade
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Rotação de Stock
        |--------------------------------------------------------------------------
        |
        | Ainda não devemos inventar este indicador.
        | Para cálculo contabilístico correto precisamos do custo das mercadorias
        | vendidas e stock médio do período.
        |
        */

        $("#dashRotacaoStock").text(
            "-"
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Gráficos
    |--------------------------------------------------------------------------
    */

    function preencherGraficos(
        graficos
    ) {

        renderizarGraficoVendas(
            graficos.vendas
            || []
        );


        renderizarGraficoReceitasDespesas(
            graficos.receitas_despesas
            || {}
        );


        renderizarGraficoFluxoCaixa(
            graficos.fluxo_caixa
            || []
        );


        renderizarGraficoTopProdutos(
            graficos.top_produtos
            || []
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Gráfico de Vendas
    |--------------------------------------------------------------------------
    */

    function renderizarGraficoVendas(
        dados
    ) {

        destruirGraficoPorCanvas(
            "graficoVendas"
        );

        chartVendas = null;

        const canvas =
            document.getElementById(
                "graficoVendas"
            );

        if (!canvas) {
            return;
        }

        const categorias =
            dados.map(
                item =>
                    formatarPeriodo(
                        item.periodo
                    )
            );

        const valores =
            dados.map(
                item =>
                    numero(
                        item.valor
                    )
            );


        chartVendas =
            new Chart(
                canvas,
                {

                    type:
                        "line",

                    data: {

                        labels:
                            categorias,

                        datasets: [
                            {
                                label:
                                    "Vendas",

                                data:
                                    valores,

                                tension:
                                    0.4,

                                fill:
                                    false
                            }
                        ]

                    },


                    options: {

                        responsive:
                            true,

                        maintainAspectRatio:
                            false,


                        interaction: {

                            mode:
                                "index",

                            intersect:
                                false
                        },


                        plugins: {

                            legend: {
                                display:
                                    true
                            },

                            tooltip: {

                                callbacks: {

                                    label:
                                        function (
                                            context
                                        ) {

                                            return (
                                                context.dataset.label
                                                + ": "
                                                + formatarMoeda(
                                                    context.raw
                                                )
                                            );

                                        }

                                }

                            }

                        },


                        scales: {

                            y: {

                                beginAtZero:
                                    true,

                                ticks: {

                                    callback:
                                        function (
                                            valor
                                        ) {

                                            return formatarMoedaCurta(
                                                valor
                                            );

                                        }

                                }

                            }

                        }

                    }

                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Receitas vs Despesas
    |--------------------------------------------------------------------------
    */

    function renderizarGraficoReceitasDespesas(
        dados
    ) {

        destruirGraficoPorCanvas(
            "graficoReceitasDespesas"
        );

        chartReceitasDespesas = null;

        const canvas =
            document.getElementById(
                "graficoReceitasDespesas"
            );

        if (!canvas) {
            return;
        }


        chartReceitasDespesas =
            new Chart(
                canvas,
                {

                    type:
                        "bar",

                    data: {

                        labels: [
                            "Receitas",
                            "Despesas"
                        ],

                        datasets: [
                            {
                                label:
                                    "Valor",

                                data: [

                                    numero(
                                        dados.receitas
                                    ),

                                    numero(
                                        dados.despesas
                                    )

                                ]
                            }
                        ]

                    },


                    options: {

                        responsive:
                            true,

                        maintainAspectRatio:
                            false,


                        plugins: {

                            legend: {
                                display:
                                    false
                            },

                            tooltip: {

                                callbacks: {

                                    label:
                                        function (
                                            context
                                        ) {

                                            return formatarMoeda(
                                                context.raw
                                            );

                                        }

                                }

                            }

                        },


                        scales: {

                            y: {

                                beginAtZero:
                                    true,

                                ticks: {

                                    callback:
                                        function (
                                            valor
                                        ) {

                                            return formatarMoedaCurta(
                                                valor
                                            );

                                        }

                                }

                            }

                        }

                    }

                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Fluxo de Caixa
    |--------------------------------------------------------------------------
    */

    function renderizarGraficoFluxoCaixa(dados) {

        if (chartFluxoCaixa) {

            chartFluxoCaixa.destroy();

            chartFluxoCaixa = null;
        }

        destruirGraficoPorCanvas(
            "graficoFluxoCaixa"
        );

        const canvas =
            document.getElementById(
                "graficoFluxoCaixa"
            );

        if (!canvas) {
            return;
        }


        const categorias =
            dados.map(
                item =>
                    formatarDataGrafico(
                        item.data
                        || item.periodo
                    )
            );


        const entradas =
            dados.map(
                item =>
                    numero(
                        item.entradas
                        ?? item.receitas
                        ?? item.total_entradas
                        ?? 0
                    )
            );


        const saidas =
            dados.map(
                item =>
                    numero(
                        item.saidas
                        ?? item.despesas
                        ?? item.total_saidas
                        ?? 0
                    )
            );


        chartFluxoCaixa =
            new Chart(
                canvas,
                {

                    type: "line",

                    data: {

                        labels:
                            categorias,

                        datasets: [
                            {
                                label:
                                    "Entradas",

                                data:
                                    entradas,

                                tension:
                                    0.4
                            },

                            {
                                label:
                                    "Saídas",

                                data:
                                    saidas,

                                tension:
                                    0.4
                            }
                        ]

                    },


                    options: {

                        responsive:
                            true,

                        maintainAspectRatio:
                            false,

                        interaction: {

                            mode:
                                "index",

                            intersect:
                                false
                        },

                        plugins: {

                            tooltip: {

                                callbacks: {

                                    label:
                                        function (context) {

                                            return (
                                                context.dataset.label
                                                + ": "
                                                + formatarMoeda(
                                                    context.raw
                                                )
                                            );
                                        }
                                }
                            }
                        },

                        scales: {

                            y: {

                                beginAtZero:
                                    true,

                                ticks: {

                                    callback:
                                        function (valor) {

                                            return formatarMoedaCurta(
                                                valor
                                            );
                                        }
                                }
                            }
                        }
                    }
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Top Produtos
    |--------------------------------------------------------------------------
    */

    function renderizarGraficoTopProdutos(
        dados
    ) {

        destruirGraficoPorCanvas(
            "graficoTopProdutos"
        );

        chartTopProdutos = null;

        const canvas =
            document.getElementById(
                "graficoTopProdutos"
            );

        if (!canvas) {
            return;
        }


        const nomes =
            dados.map(
                item =>
                    item.produto
                    || item.nome
                    || item.designacao
                    || "-"
            );


        const valores =
            dados.map(
                item =>
                    numero(
                        item.valor_vendido
                        ?? item.total
                        ?? 0
                    )
            );


        chartTopProdutos =
            new Chart(
                canvas,
                {

                    type:
                        "bar",

                    data: {

                        labels:
                            nomes,

                        datasets: [
                            {
                                label:
                                    "Valor vendido",

                                data:
                                    valores
                            }
                        ]

                    },


                    options: {

                        indexAxis:
                            "y",

                        responsive:
                            true,

                        maintainAspectRatio:
                            false,


                        plugins: {

                            legend: {
                                display:
                                    false
                            },

                            tooltip: {

                                callbacks: {

                                    label:
                                        function (
                                            context
                                        ) {

                                            return formatarMoeda(
                                                context.raw
                                            );

                                        }

                                }

                            }

                        },


                        scales: {

                            x: {

                                beginAtZero:
                                    true,

                                ticks: {

                                    callback:
                                        function (
                                            valor
                                        ) {

                                            return formatarMoedaCurta(
                                                valor
                                            );

                                        }

                                }

                            }

                        }

                    }

                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Destruir gráfico
    |--------------------------------------------------------------------------
    */

    function destruirGraficoPorCanvas(canvasId) {

        const canvas = document.getElementById(canvasId);

        if (!canvas) {
            return;
        }

        // Chart.js 3/4
        if (
            typeof Chart !== "undefined"
            && typeof Chart.getChart === "function"
        ) {

            const grafico = Chart.getChart(canvasId);

            if (grafico) {
                grafico.destroy();
            }
        }

        // Segurança extra
        if (
            typeof Chart !== "undefined"
            && Chart.instances
        ) {

            Object.keys(Chart.instances).forEach(function (id) {

                const grafico = Chart.instances[id];

                if (
                    grafico
                    && grafico.canvas
                    && grafico.canvas.id === canvasId
                ) {
                    grafico.destroy();
                }

            });
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Limpar Dashboard
    |--------------------------------------------------------------------------
    */

    function limparDashboard() {

        preencherCards(
            {}
        );


        preencherContabilidade(
            {}
        );


        $("#dashLiquidez").text(
            "0,00"
        );

        $("#dashEndividamento").text(
            "0,00%"
        );

        $("#dashRentabilidade").text(
            "0,00%"
        );

        $("#dashRotacaoStock").text(
            "-"
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Bloquear
    |--------------------------------------------------------------------------
    */

    function bloquearDashboard() {

        $("#dashboardPeriodo")
            .prop(
                "disabled",
                true
            );

    }


    /*
    |--------------------------------------------------------------------------
    | Desbloquear
    |--------------------------------------------------------------------------
    */

    function desbloquearDashboard() {

        $("#dashboardPeriodo")
            .prop(
                "disabled",
                false
            );

    }


    /*
    |--------------------------------------------------------------------------
    | Mostrar erro
    |--------------------------------------------------------------------------
    */

    function mostrarErro(
        mensagem
    ) {

        console.error(
            mensagem
        );


        /*
         * Compatibilidade com SweetAlert2.
         */

        if (
            typeof Swal !==
            "undefined"
        ) {

            Swal.fire({

                icon:
                    "error",

                title:
                    "Erro",

                text:
                    mensagem

            });

            return;
        }


        alert(
            mensagem
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Converter para número
    |--------------------------------------------------------------------------
    */

    function numero(
        valor
    ) {

        const numero =
            Number(
                valor
            );


        return Number.isFinite(
            numero
        )
            ? numero
            : 0;

    }


    /*
    |--------------------------------------------------------------------------
    | Formatar moeda
    |--------------------------------------------------------------------------
    */

    function formatarMoeda(
        valor
    ) {

        return new Intl.NumberFormat(
            "pt-MZ",
            {
                minimumFractionDigits:
                    2,

                maximumFractionDigits:
                    2
            }
        ).format(
            numero(
                valor
            )
        )
            + " MTn";

    }


    /*
    |--------------------------------------------------------------------------
    | Moeda curta para gráficos
    |--------------------------------------------------------------------------
    */

    function formatarMoedaCurta(
        valor
    ) {

        valor =
            numero(
                valor
            );


        if (
            Math.abs(
                valor
            ) >= 1000000
        ) {

            return (
                valor
                / 1000000
            ).toFixed(
                1
            )
                + "M";

        }


        if (
            Math.abs(
                valor
            ) >= 1000
        ) {

            return (
                valor
                / 1000
            ).toFixed(
                1
            )
                + "K";

        }


        return valor.toFixed(
            0
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Formatar número
    |--------------------------------------------------------------------------
    */

    function formatarNumero(
        valor
    ) {

        return new Intl.NumberFormat(
            "pt-MZ",
            {
                maximumFractionDigits:
                    0
            }
        ).format(
            numero(
                valor
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Decimal
    |--------------------------------------------------------------------------
    */

    function formatarDecimal(
        valor
    ) {

        return new Intl.NumberFormat(
            "pt-MZ",
            {
                minimumFractionDigits:
                    2,

                maximumFractionDigits:
                    2
            }
        ).format(
            numero(
                valor
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Percentagem
    |--------------------------------------------------------------------------
    */

    function formatarPercentagem(
        valor
    ) {

        return formatarDecimal(
            valor
        )
            + "%";

    }


    /*
    |--------------------------------------------------------------------------
    | Formatar período YYYY-MM
    |--------------------------------------------------------------------------
    */

    function formatarPeriodo(
        periodo
    ) {

        if (!periodo) {
            return "-";
        }


        const partes =
            String(
                periodo
            ).split(
                "-"
            );


        if (
            partes.length !== 2
        ) {

            return periodo;
        }


        const meses = [
            "Jan",
            "Fev",
            "Mar",
            "Abr",
            "Mai",
            "Jun",
            "Jul",
            "Ago",
            "Set",
            "Out",
            "Nov",
            "Dez"
        ];


        const mes =
            Number(
                partes[1]
            );


        if (
            mes < 1
            ||
            mes > 12
        ) {

            return periodo;
        }


        return meses[
            mes - 1
        ]
            + "/"
            + partes[0];

    }


    /*
    |--------------------------------------------------------------------------
    | Formatar data para gráfico
    |--------------------------------------------------------------------------
    */

    function formatarDataGrafico(
        data
    ) {

        if (!data) {
            return "-";
        }


        const valor =
            String(
                data
            );


        const partes =
            valor.split(
                "-"
            );


        if (
            partes.length === 3
        ) {

            return partes[2]
                + "/"
                + partes[1];

        }


        if (
            partes.length === 2
        ) {

            return formatarPeriodo(
                valor
            );

        }


        return valor;

    }

});