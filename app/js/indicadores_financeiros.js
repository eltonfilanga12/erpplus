"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const INDICADOR_FINANCEIRO_URL =
        "/erpplus/app/ajax/indicador_financeiro_ajax.php";

    let graficoFluxoMensal = null;
    let graficoContasVencidas = null;
    let graficoCentrosCusto = null;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        /*
         * O ficheiro pode estar incluído no layout geral.
         * Só deve iniciar quando a página de indicadores
         * financeiros estiver realmente aberta.
         */
        if (!paginaIndicadoresExiste()) {
            return;
        }

        configurarEventosIndicadores();

        definirPeriodoPadrao();

        carregarPainelIndicadores();

    });

    // ==================================================
    // VERIFICAR PÁGINA DE INDICADORES
    // ==================================================

    function paginaIndicadoresExiste() {

        return (
            $("#formFiltroIndicadores").length > 0
            && $("#indicador_data_inicial").length > 0
            && $("#indicador_data_final").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosIndicadores() {

        $("#formFiltroIndicadores").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                carregarPainelIndicadores();

            }
        );


        $("#btnAtualizarIndicadores").on(
            "click",
            function () {

                carregarPainelIndicadores();

            }
        );

    }


    // ==================================================
    // CARREGAR PAINEL
    // ==================================================

    async function carregarPainelIndicadores() {

        if (!paginaIndicadoresExiste()) {
            return;
        }

        const dataInicial =
            $("#indicador_data_inicial").val();

        const dataFinal =
            $("#indicador_data_final").val();

        const meses =
            Number(
                $("#indicador_meses").val()
                || 12
            );

        if (!dataInicial || !dataFinal) {

            mostrarErroIndicadores(
                "Informe a data inicial e a data final."
            );

            return;
        }

        if (dataInicial > dataFinal) {

            mostrarErroIndicadores(
                "A data inicial não pode ser posterior à data final."
            );

            return;
        }

        mostrarCarregamentoIndicadores(
            true
        );

        bloquearFiltrosIndicadores(
            true
        );

        try {

            const parametros =
                new URLSearchParams({

                    acao:
                        "painel",

                    data_inicial:
                        dataInicial,

                    data_final:
                        dataFinal,

                    meses:
                        String(meses)

                });

            const resposta =
                await requisicaoIndicadores(
                    INDICADOR_FINANCEIRO_URL
                    + "?"
                    + parametros.toString()
                );

            const dados =
                resposta.data || {};

            preencherResumoIndicadores(
                dados.resumo || {}
            );

            criarGraficoFluxoMensal(
                dados.fluxo_mensal || []
            );

            criarGraficoContasVencidas(
                dados.resumo || {}
            );

            criarGraficoCentrosCusto(
                dados.centros_custo || []
            );

            preencherTabelaContasVencidas(
                dados.contas_vencidas || []
            );

            preencherTabelaClientesDivida(
                dados.clientes_maior_divida || []
            );

            preencherTabelaFornecedoresDivida(
                dados.fornecedores_maior_divida || []
            );

        } catch (erro) {

            console.error(
                "Erro ao carregar indicadores:",
                erro
            );

            mostrarErroIndicadores(
                erro.message
            );

        } finally {

            mostrarCarregamentoIndicadores(
                false
            );

            bloquearFiltrosIndicadores(
                false
            );

        }

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoIndicadores(
        url,
        opcoes = {}
    ) {

        const resposta =
            await fetch(
                url,
                {
                    credentials:
                        "same-origin",

                    ...opcoes
                }
            );

        const texto =
            await resposta.text();

        let json;

        try {

            json =
                JSON.parse(texto);

        } catch (erro) {

            console.error(
                "Resposta recebida:",
                texto
            );

            throw new Error(
                "O servidor não retornou uma resposta JSON válida."
            );

        }

        if (
            resposta.status === 401
        ) {

            await Swal.fire({

                icon:
                    "warning",

                title:
                    "Sessão terminada",

                text:
                    json.mensagem
                    || "Faça login novamente.",

                confirmButtonText:
                    "Ir para o login"

            });

            window.location.href =
                "/erpplus/index.php?page=login";

            throw new Error(
                "Sessão expirada."
            );

        }

        if (
            !resposta.ok
            || json.success === false
        ) {

            throw new Error(
                json.mensagem
                || json.message
                || "Não foi possível carregar os indicadores."
            );

        }

        return json;
    }


    // ==================================================
    // RESUMO
    // ==================================================

    function preencherResumoIndicadores(
        resumo
    ) {

        definirTexto(
            "#indicadorSaldoCaixa",
            formatarMoedaIndicador(
                resumo.saldo_caixa
            )
        );

        definirTexto(
            "#indicadorSaldoBancario",
            formatarMoedaIndicador(
                resumo.saldo_bancario
            )
        );

        definirTexto(
            "#indicadorSaldoDisponivel",
            formatarMoedaIndicador(
                resumo.saldo_disponivel
            )
        );

        definirTexto(
            "#indicadorTotalReceber",
            formatarMoedaIndicador(
                resumo.total_receber
            )
        );

        definirTexto(
            "#indicadorTotalPagar",
            formatarMoedaIndicador(
                resumo.total_pagar
            )
        );

        definirTexto(
            "#indicadorRecebidoPeriodo",
            formatarMoedaIndicador(
                resumo.recebido_periodo
            )
        );

        definirTexto(
            "#indicadorPagoPeriodo",
            formatarMoedaIndicador(
                resumo.pago_periodo
            )
        );

        definirTexto(
            "#indicadorResultadoPeriodo",
            formatarMoedaIndicador(
                resumo.resultado_periodo
            )
        );

        definirTexto(
            "#indicadorTotalVencido",
            formatarMoedaIndicador(
                resumo.total_vencido
            )
        );

        definirTexto(
            "#indicadorInadimplencia",
            formatarPercentagemIndicador(
                resumo.inadimplencia
            )
        );

        definirTexto(
            "#indicadorLiquidezImediata",
            formatarNumeroIndicador(
                resumo.liquidez_imediata,
                2
            )
        );

        // ==================================================
        // PRAZO MÉDIO DE PAGAMENTO
        // ==================================================

        definirTexto(
            "#indicadorPrazoMedioPagamento",

            formatarNumeroIndicador(
                resumo.prazo_medio_pagamento,
                1
            )
            + " dias"
        );


        // ==================================================
        // PRAZO MÉDIO DE RECEBIMENTO
        // ==================================================

        definirTexto(
            "#indicadorPrazoMedioRecebimento",

            formatarNumeroIndicador(
                resumo.prazo_medio_recebimento,
                1
            )
            + " dias"
        );

        aplicarClasseResultado(
            "#indicadorResultadoPeriodo",
            Number(
                resumo.resultado_periodo
                || 0
            )
        );

        aplicarClasseResultado(
            "#indicadorSaldoDisponivel",
            Number(
                resumo.saldo_disponivel
                || 0
            )
        );

        aplicarClasseLiquidez(
            "#indicadorLiquidezImediata",
            Number(
                resumo.liquidez_imediata
                || 0
            )
        );

        aplicarClasseInadimplencia(
            "#indicadorInadimplencia",
            Number(
                resumo.inadimplencia
                || 0
            )
        );

    }


    // ==================================================
    // GRÁFICO FLUXO MENSAL
    // ==================================================

    function criarGraficoFluxoMensal(
        dados
    ) {

        const canvas =
            document.getElementById(
                "graficoFluxoMensal"
            );

        if (!canvas) {
            return;
        }

        const labels =
            dados.map(
                function (item) {

                    return item.label
                        || item.periodo
                        || "-";

                }
            );

        const entradas =
            dados.map(
                function (item) {

                    return Number(
                        item.entradas
                        || 0
                    );

                }
            );

        const saidas =
            dados.map(
                function (item) {

                    return Number(
                        item.saidas
                        || 0
                    );

                }
            );

        if (graficoFluxoMensal) {

            graficoFluxoMensal.destroy();

        }

        graficoFluxoMensal =
            new Chart(
                canvas,
                {
                    type:
                        "bar",

                    data: {

                        labels:
                            labels,

                        datasets: [

                            {
                                label:
                                    "Entradas",

                                data:
                                    entradas,

                                borderWidth:
                                    1
                            },

                            {
                                label:
                                    "Saídas",

                                data:
                                    saidas,

                                borderWidth:
                                    1
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

                                position:
                                    "top"

                            },

                            tooltip: {

                                callbacks: {

                                    label:
                                        function (
                                            contexto
                                        ) {

                                            return (
                                                contexto.dataset.label
                                                + ": "
                                                + formatarMoedaIndicador(
                                                    contexto.raw
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


    // ==================================================
    // GRÁFICO CONTAS VENCIDAS
    // ==================================================

    function criarGraficoContasVencidas(
        resumo
    ) {

        const canvas =
            document.getElementById(
                "graficoContasVencidas"
            );

        if (!canvas) {
            return;
        }

        const vencidoReceber =
            Number(
                resumo.vencido_receber
                || 0
            );

        const vencidoPagar =
            Number(
                resumo.vencido_pagar
                || 0
            );

        if (graficoContasVencidas) {

            graficoContasVencidas.destroy();

        }

        graficoContasVencidas =
            new Chart(
                canvas,
                {
                    type:
                        "doughnut",

                    data: {

                        labels: [
                            "A receber vencido",
                            "A pagar vencido"
                        ],

                        datasets: [
                            {
                                data: [
                                    vencidoReceber,
                                    vencidoPagar
                                ],

                                borderWidth:
                                    1
                            }
                        ]

                    },

                    options: {

                        responsive:
                            true,

                        maintainAspectRatio:
                            false,

                        cutout:
                            "65%",

                        plugins: {

                            legend: {

                                position:
                                    "bottom"

                            },

                            tooltip: {

                                callbacks: {

                                    label:
                                        function (
                                            contexto
                                        ) {

                                            return (
                                                contexto.label
                                                + ": "
                                                + formatarMoedaIndicador(
                                                    contexto.raw
                                                )
                                            );

                                        }

                                }

                            }

                        }

                    }

                }
            );

    }


    // ==================================================
    // GRÁFICO CENTROS DE CUSTO
    // ==================================================

    function criarGraficoCentrosCusto(
        dados
    ) {

        const canvas =
            document.getElementById(
                "graficoCentrosCusto"
            );

        if (!canvas) {
            return;
        }

        const dadosComMovimento =
            dados.filter(
                function (item) {

                    return (
                        Number(
                            item.receitas || 0
                        ) !== 0
                        ||
                        Number(
                            item.despesas || 0
                        ) !== 0
                    );

                }
            );

        const labels =
            dadosComMovimento.map(
                function (item) {

                    const codigo =
                        item.codigo
                            ? item.codigo
                            + " - "
                            : "";

                    return codigo
                        + (
                            item.nome
                            || "Centro"
                        );

                }
            );

        const receitas =
            dadosComMovimento.map(
                function (item) {

                    return Number(
                        item.receitas
                        || 0
                    );

                }
            );

        const despesas =
            dadosComMovimento.map(
                function (item) {

                    return Number(
                        item.despesas
                        || 0
                    );

                }
            );

        if (graficoCentrosCusto) {

            graficoCentrosCusto.destroy();

        }

        graficoCentrosCusto =
            new Chart(
                canvas,
                {
                    type:
                        "bar",

                    data: {

                        labels:
                            labels,

                        datasets: [

                            {
                                label:
                                    "Receitas",

                                data:
                                    receitas,

                                borderWidth:
                                    1
                            },

                            {
                                label:
                                    "Despesas",

                                data:
                                    despesas,

                                borderWidth:
                                    1
                            }

                        ]

                    },

                    options: {

                        responsive:
                            true,

                        maintainAspectRatio:
                            false,

                        indexAxis:
                            labels.length > 8
                                ? "y"
                                : "x",

                        interaction: {

                            mode:
                                "index",

                            intersect:
                                false

                        },

                        plugins: {

                            legend: {

                                position:
                                    "top"

                            },

                            tooltip: {

                                callbacks: {

                                    label:
                                        function (
                                            contexto
                                        ) {

                                            return (
                                                contexto.dataset.label
                                                + ": "
                                                + formatarMoedaIndicador(
                                                    contexto.raw
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

                                            if (
                                                labels.length > 8
                                            ) {
                                                return valor;
                                            }

                                            return formatarMoedaCurta(
                                                valor
                                            );

                                        }

                                }

                            },

                            x: {

                                beginAtZero:
                                    true,

                                ticks: {

                                    callback:
                                        function (
                                            valor
                                        ) {

                                            if (
                                                labels.length > 8
                                            ) {
                                                return formatarMoedaCurta(
                                                    valor
                                                );
                                            }

                                            return this.getLabelForValue(
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


    // ==================================================
    // TABELA DE CONTAS VENCIDAS
    // ==================================================

    function preencherTabelaContasVencidas(
        dados
    ) {

        const tbody =
            $("#tabelaContasVencidas tbody");

        tbody.empty();

        if (!dados.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="6"
                        class="text-center text-muted">

                        Nenhuma conta vencida.

                    </td>

                </tr>
            `);

            return;
        }

        dados.forEach(
            function (conta) {

                const tipo =
                    String(
                        conta.tipo || ""
                    ).toUpperCase();

                const classe =
                    tipo === "RECEBER"
                        ? "success"
                        : "danger";

                const textoTipo =
                    tipo === "RECEBER"
                        ? "A Receber"
                        : "A Pagar";

                tbody.append(`
                    <tr>

                        <td>

                            <span class="badge badge-${classe}">

                                ${textoTipo}

                            </span>

                        </td>

                        <td>
                            ${escaparHtmlIndicador(
                    conta.entidade
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlIndicador(
                    conta.numero_documento
                    || "-"
                )}
                        </td>

                        <td>
                            ${formatarDataIndicador(
                    conta.data_vencimento
                )}
                        </td>

                        <td>
                            ${formatarNumeroIndicador(
                    conta.dias_atraso,
                    0
                )}
                        </td>

                        <td class="text-right font-weight-bold">
                            ${formatarMoedaIndicador(
                    conta.saldo
                )}
                        </td>

                    </tr>
                `);

            }
        );

    }


    // ==================================================
    // CLIENTES COM DÍVIDA
    // ==================================================

    function preencherTabelaClientesDivida(
        dados
    ) {

        const tbody =
            $("#tabelaClientesDivida tbody");

        tbody.empty();

        if (!dados.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="3"
                        class="text-center text-muted">

                        Nenhum cliente com dívida.

                    </td>

                </tr>
            `);

            return;
        }

        dados.forEach(
            function (cliente) {

                tbody.append(`
                    <tr>

                        <td>
                            ${escaparHtmlIndicador(
                    cliente.nome
                    || "-"
                )}
                        </td>

                        <td>
                            ${formatarNumeroIndicador(
                    cliente.quantidade_contas,
                    0
                )}
                        </td>

                        <td class="text-right font-weight-bold">
                            ${formatarMoedaIndicador(
                    cliente.total_divida
                )}
                        </td>

                    </tr>
                `);

            }
        );

    }


    // ==================================================
    // FORNECEDORES COM DÍVIDA
    // ==================================================

    function preencherTabelaFornecedoresDivida(
        dados
    ) {

        const tbody =
            $("#tabelaFornecedoresDivida tbody");

        tbody.empty();

        if (!dados.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="3"
                        class="text-center text-muted">

                        Nenhum fornecedor com valor pendente.

                    </td>

                </tr>
            `);

            return;
        }

        dados.forEach(
            function (fornecedor) {

                tbody.append(`
                    <tr>

                        <td>
                            ${escaparHtmlIndicador(
                    fornecedor.nome
                    || "-"
                )}
                        </td>

                        <td>
                            ${formatarNumeroIndicador(
                    fornecedor.quantidade_contas,
                    0
                )}
                        </td>

                        <td class="text-right font-weight-bold">
                            ${formatarMoedaIndicador(
                    fornecedor.total_divida
                )}
                        </td>

                    </tr>
                `);

            }
        );

    }


    // ==================================================
    // PERÍODO PADRÃO
    // ==================================================

    function definirPeriodoPadrao() {

        const hoje =
            new Date();

        const primeiroDia =
            new Date(
                hoje.getFullYear(),
                hoje.getMonth(),
                1
            );

        if (
            !$("#indicador_data_inicial")
                .val()
        ) {

            $("#indicador_data_inicial")
                .val(
                    formatarDataInput(
                        primeiroDia
                    )
                );

        }

        if (
            !$("#indicador_data_final")
                .val()
        ) {

            $("#indicador_data_final")
                .val(
                    formatarDataInput(
                        hoje
                    )
                );

        }

    }


    function formatarDataInput(
        data
    ) {

        const ano =
            data.getFullYear();

        const mes =
            String(
                data.getMonth() + 1
            ).padStart(
                2,
                "0"
            );

        const dia =
            String(
                data.getDate()
            ).padStart(
                2,
                "0"
            );

        return `${ano}-${mes}-${dia}`;
    }


    // ==================================================
    // FORMATAÇÃO
    // ==================================================

    function formatarMoedaIndicador(
        valor
    ) {

        return Number(
            valor || 0
        ).toLocaleString(
            "pt-MZ",
            {
                style:
                    "currency",

                currency:
                    "MZN",

                minimumFractionDigits:
                    2,

                maximumFractionDigits:
                    2
            }
        );

    }


    function formatarMoedaCurta(
        valor
    ) {

        const numero =
            Number(
                valor || 0
            );

        if (
            Math.abs(numero)
            >= 1000000
        ) {

            return (
                (
                    numero
                    / 1000000
                ).toFixed(1)
                + " M"
            );

        }

        if (
            Math.abs(numero)
            >= 1000
        ) {

            return (
                (
                    numero
                    / 1000
                ).toFixed(1)
                + " mil"
            );

        }

        return numero.toFixed(0);
    }


    function formatarPercentagemIndicador(
        valor
    ) {

        return (
            Number(
                valor || 0
            ).toLocaleString(
                "pt-PT",
                {
                    minimumFractionDigits:
                        2,

                    maximumFractionDigits:
                        2
                }
            )
            + "%"
        );

    }


    function formatarNumeroIndicador(
        valor,
        casas = 2
    ) {

        return Number(
            valor || 0
        ).toLocaleString(
            "pt-PT",
            {
                minimumFractionDigits:
                    casas,

                maximumFractionDigits:
                    casas
            }
        );

    }


    function formatarDataIndicador(
        data
    ) {

        if (!data) {
            return "-";
        }

        const partes =
            String(data)
                .substring(
                    0,
                    10
                )
                .split("-");

        if (partes.length !== 3) {
            return escaparHtmlIndicador(
                data
            );
        }

        return (
            partes[2]
            + "/"
            + partes[1]
            + "/"
            + partes[0]
        );

    }


    // ==================================================
    // CLASSES VISUAIS
    // ==================================================

    function aplicarClasseResultado(
        seletor,
        valor
    ) {

        const elemento =
            $(seletor);

        elemento.removeClass(
            "text-success text-danger text-warning"
        );

        if (valor > 0) {

            elemento.addClass(
                "text-success"
            );

        } else if (valor < 0) {

            elemento.addClass(
                "text-danger"
            );

        } else {

            elemento.addClass(
                "text-warning"
            );

        }

    }


    function aplicarClasseLiquidez(
        seletor,
        valor
    ) {

        const elemento =
            $(seletor);

        elemento.removeClass(
            "text-success text-danger text-warning"
        );

        if (valor >= 1) {

            elemento.addClass(
                "text-success"
            );

        } else if (valor >= 0.5) {

            elemento.addClass(
                "text-warning"
            );

        } else {

            elemento.addClass(
                "text-danger"
            );

        }

    }


    function aplicarClasseInadimplencia(
        seletor,
        valor
    ) {

        const elemento =
            $(seletor);

        elemento.removeClass(
            "text-success text-danger text-warning"
        );

        if (valor <= 5) {

            elemento.addClass(
                "text-success"
            );

        } else if (valor <= 15) {

            elemento.addClass(
                "text-warning"
            );

        } else {

            elemento.addClass(
                "text-danger"
            );

        }

    }


    // ==================================================
    // CARREGAMENTO E BOTÕES
    // ==================================================

    function mostrarCarregamentoIndicadores(
        mostrar
    ) {

        $("#indicadoresCarregando")
            .toggle(
                Boolean(mostrar)
            );

    }


    function bloquearFiltrosIndicadores(
        bloquear
    ) {

        $("#formFiltroIndicadores")
            .find(
                "input, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnAtualizarIndicadores")
            .prop(
                "disabled",
                Boolean(bloquear)
            );

    }


    // ==================================================
    // MENSAGENS
    // ==================================================

    function mostrarErroIndicadores(
        mensagem
    ) {

        return Swal.fire({

            icon:
                "error",

            title:
                "Não foi possível carregar",

            text:
                mensagem
                || "Ocorreu um erro ao carregar os indicadores financeiros.",

            confirmButtonText:
                "Fechar"

        });

    }


    // ==================================================
    // UTILITÁRIOS
    // ==================================================

    function definirTexto(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function escaparHtmlIndicador(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);