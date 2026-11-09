"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const ANALISE_GASTOS_URL =
        "/erpplus/app/ajax/analise_gastos_ajax.php";


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaAnaliseGastosExiste()) {
            return;
        }

        configurarEventosAnaliseGastos();

        definirDatasPadraoAnaliseGastos();

        carregarDadosIniciaisAnaliseGastos();

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaAnaliseGastosExiste() {

        return (
            $("#formFiltroAnaliseGastos").length > 0
            && $("#gastosDataInicial").length > 0
            && $("#gastosDataFinal").length > 0
            && $("#tabelaDetalhesGastos").length > 0
        );
    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosAnaliseGastos() {

        $("#formFiltroAnaliseGastos")
            .on(
                "submit",
                function (evento) {

                    evento.preventDefault();

                    gerarAnaliseGastos();
                }
            );


        $("#btnAtualizarAnaliseGastos")
            .on(
                "click",
                function () {

                    gerarAnaliseGastos();
                }
            );


        $("#btnLimparAnaliseGastos")
            .on(
                "click",
                function () {

                    limparFiltrosAnaliseGastos();
                }
            );
    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisAnaliseGastos() {

        mostrarCarregamentoAnaliseGastos(true);

        bloquearFiltrosAnaliseGastos(true);

        try {

            const resposta =
                await requisicaoAnaliseGastos(
                    ANALISE_GASTOS_URL
                    + "?acao=dados_iniciais"
                );


            const dados =
                resposta.data || {};


            preencherPlanosAnaliseGastos(
                dados.planos || []
            );


            preencherFiliaisAnaliseGastos(
                dados.filiais || [],
                dados.filial_sessao_id
                ?? null
            );


            preencherCentrosCustoAnaliseGastos(
                dados.centros_custo || []
            );


            await gerarAnaliseGastos();

        } catch (erro) {

            console.error(
                "Erro ao carregar dados iniciais da análise de gastos:",
                erro
            );

            mostrarErroAnaliseGastos(
                erro.message
            );

        } finally {

            mostrarCarregamentoAnaliseGastos(false);

            bloquearFiltrosAnaliseGastos(false);
        }
    }


    // ==================================================
    // GERAR ANÁLISE
    // ==================================================

    async function gerarAnaliseGastos() {

        if (!paginaAnaliseGastosExiste()) {
            return;
        }


        const dataInicial =
            $("#gastosDataInicial")
                .val();


        const dataFinal =
            $("#gastosDataFinal")
                .val();


        const filialId =
            $("#gastosFilialId")
                .val();


        const planoContasId =
            $("#gastosPlanoContasId")
                .val();


        const centroCustoId =
            $("#gastosCentroCustoId")
                .val();


        const comparar =
            $("#gastosCompararAnterior")
                .is(":checked")
                ? 1
                : 0;


        if (
            !dataInicial
            || !dataFinal
        ) {

            mostrarErroAnaliseGastos(
                "Informe a data inicial e a data final."
            );

            return;
        }


        mostrarCarregamentoAnaliseGastos(true);

        bloquearFiltrosAnaliseGastos(true);


        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "gerar",

                    data_inicial:
                        dataInicial,

                    data_final:
                        dataFinal,

                    filial_id:
                        filialId || "",

                    plano_contas_id:
                        planoContasId || "",

                    centro_custo_id:
                        centroCustoId || "",

                    comparar_periodo_anterior:
                        comparar
                });


            const resposta =
                await requisicaoAnaliseGastos(
                    ANALISE_GASTOS_URL
                    + "?"
                    + parametros.toString()
                );


            const dados =
                resposta.data || {};


            preencherResumoAnaliseGastos(
                dados.atual || {},
                dados.comparacao || {}
            );


            preencherTabelaDetalhesGastos(
                (
                    dados.atual
                    && dados.atual.detalhes
                )
                    ? dados.atual.detalhes
                    : []
            );


            atualizarComparacaoAnaliseGastos(
                dados.comparacao || {}
            );


        } catch (erro) {

            console.error(
                "Erro ao gerar análise de gastos:",
                erro
            );


            limparResultadoAnaliseGastos();


            mostrarErroAnaliseGastos(
                erro.message
            );

        } finally {

            mostrarCarregamentoAnaliseGastos(false);

            bloquearFiltrosAnaliseGastos(false);
        }
    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoAnaliseGastos(
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
                JSON.parse(
                    texto
                );

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
                || "Não foi possível gerar a análise de gastos."
            );
        }


        return json;
    }


    // ==================================================
    // PLANOS
    // ==================================================

    function preencherPlanosAnaliseGastos(
        planos
    ) {

        const select =
            $("#gastosPlanoContasId");


        select.empty();


        select.append(`
            <option value="">
                Todos os planos
            </option>
        `);


        planos.forEach(
            function (plano) {

                const codigo =
                    plano.codigo
                        ? plano.codigo
                        + " - "
                        : "";


                const selecionado =
                    Number(
                        plano.padrao || 0
                    ) === 1
                        ? "selected"
                        : "";


                select.append(`
                    <option
                        value="${Number(plano.id)}"
                        ${selecionado}>

                        ${escaparHtmlAnaliseGastos(
                    codigo
                    + (
                        plano.nome
                        || "Plano"
                    )
                )}

                    </option>
                `);
            }
        );
    }


    // ==================================================
    // FILIAIS
    // ==================================================

    function preencherFiliaisAnaliseGastos(
        filiais,
        filialSessaoId
    ) {

        const select =
            $("#gastosFilialId");


        select.empty();


        if (filialSessaoId) {

            select.append(`
                <option
                    value="${Number(
                filialSessaoId
            )}"
                    selected>

                    Filial da sessão

                </option>
            `);


            select.prop(
                "disabled",
                true
            );


            return;
        }


        select.append(`
            <option value="">
                Todas as filiais
            </option>
        `);


        filiais.forEach(
            function (filial) {

                const codigo =
                    filial.codigo
                        ? filial.codigo
                        + " - "
                        : "";


                select.append(`
                    <option value="${Number(filial.id)}">

                        ${escaparHtmlAnaliseGastos(
                    codigo
                    + (
                        filial.nome
                        || "Filial"
                    )
                )}

                    </option>
                `);
            }
        );
    }


    // ==================================================
    // CENTROS DE CUSTO
    // ==================================================

    function preencherCentrosCustoAnaliseGastos(
        centros
    ) {

        const select =
            $("#gastosCentroCustoId");


        select.empty();


        select.append(`
            <option value="">
                Todos os centros de custo
            </option>
        `);


        centros.forEach(
            function (centro) {

                const codigo =
                    centro.codigo
                        ? centro.codigo
                        + " - "
                        : "";


                select.append(`
                    <option value="${Number(centro.id)}">

                        ${escaparHtmlAnaliseGastos(
                    codigo
                    + (
                        centro.nome
                        || "Centro de custo"
                    )
                )}

                    </option>
                `);
            }
        );
    }


    // ==================================================
    // RESUMO
    // ==================================================

    function preencherResumoAnaliseGastos(
        atual,
        comparacao
    ) {

        definirTextoAnaliseGastos(
            "#gastosTotal",
            formatarMoedaAnaliseGastos(
                atual.total_gastos
                || 0
            )
        );


        definirTextoAnaliseGastos(
            "#gastosCustoMercadorias",
            formatarMoedaAnaliseGastos(
                atual.custo_mercadorias
                || 0
            )
        );


        definirTextoAnaliseGastos(
            "#gastosPessoal",
            formatarMoedaAnaliseGastos(
                atual.gastos_pessoal
                || 0
            )
        );


        definirTextoAnaliseGastos(
            "#gastosFornecimentos",
            formatarMoedaAnaliseGastos(
                atual.fornecimentos_servicos
                || 0
            )
        );


        definirTextoAnaliseGastos(
            "#gastosOutros",
            formatarMoedaAnaliseGastos(
                atual.outros_gastos
                || 0
            )
        );

        definirTextoAnaliseGastos(
            "#gastosAmortizacoes",
            formatarMoedaAnaliseGastos(
                atual.amortizacoes
                || 0
            )
        );


        definirTextoAnaliseGastos(
            "#gastosImpostosTaxas",
            formatarMoedaAnaliseGastos(
                atual.impostos_taxas
                || 0
            )
        );


        definirTextoAnaliseGastos(
            "#gastosFinanceiros",
            formatarMoedaAnaliseGastos(
                atual.gastos_financeiros
                || 0
            )
        );

        definirTextoAnaliseGastos(
            "#gastosOutrosOperacionais",
            formatarMoedaAnaliseGastos(
                atual.outros_operacionais
                || 0
            )
        );


        const maior =
            atual.maior_gasto
            || null;


        if (maior) {

            $("#gastosMaiorConta")
                .text(
                    (
                        maior.codigo
                            ? maior.codigo
                            + " - "
                            : ""
                    )
                    + (
                        maior.nome
                        || "-"
                    )
                );


            $("#gastosMaiorValor")
                .text(
                    formatarMoedaAnaliseGastos(
                        maior.valor
                        || 0
                    )
                );

        } else {

            $("#gastosMaiorConta")
                .text(
                    "Sem movimentos"
                );


            $("#gastosMaiorValor")
                .text(
                    formatarMoedaAnaliseGastos(
                        0
                    )
                );
        }


        const variacao =
            comparacao
                && comparacao.disponivel
                ? comparacao.variacao_percentual
                : null;


        if (
            variacao === null
            || variacao === undefined
        ) {

            $("#gastosVariacao")
                .text(
                    "—"
                );

        } else {

            const sinal =
                Number(variacao) > 0
                    ? "+"
                    : "";


            $("#gastosVariacao")
                .text(
                    sinal
                    + Number(
                        variacao
                    )
                        .toLocaleString(
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
    }


    // ==================================================
    // TABELA
    // ==================================================

    function preencherTabelaDetalhesGastos(
        detalhes
    ) {

        const tbody =
            $("#tabelaDetalhesGastos tbody");


        tbody.empty();


        if (!detalhes.length) {

            tbody.html(`
                <tr>
                    <td
                        colspan="6"
                        class="text-center text-muted py-4">

                        Nenhum gasto encontrado
                        para o período selecionado.

                    </td>
                </tr>
            `);

            return;
        }


        detalhes.forEach(
            function (linha) {

                tbody.append(`
                    <tr>

                        <td>
                            ${escaparHtmlAnaliseGastos(
                    linha.codigo
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlAnaliseGastos(
                    linha.nome
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlAnaliseGastos(
                    nomeCategoriaAnaliseGastos(
                        linha.categoria
                    )
                )}
                        </td>

                        <td class="text-center">
                            ${Number(
                    linha.movimentos
                    || 0
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaAnaliseGastos(
                    linha.valor
                    || 0
                )}
                        </td>

                        <td class="text-right">
                            ${calcularPercentualAnaliseGastos(
                    linha.valor
                )}
                        </td>

                    </tr>
                `);
            }
        );
    }


    // ==================================================
    // PERCENTUAL DO TOTAL
    // ==================================================

    function calcularPercentualAnaliseGastos(
        valor
    ) {

        const total =
            numeroMoedaAnaliseGastos(
                $("#gastosTotal").text()
            );


        if (
            Math.abs(total)
            <= 0.009
        ) {

            return "0,00%";
        }


        const percentual =
            (
                Number(
                    valor || 0
                )
                / total
            )
            * 100;


        return Number(
            percentual
        )
            .toLocaleString(
                "pt-PT",
                {
                    minimumFractionDigits:
                        2,

                    maximumFractionDigits:
                        2
                }
            )
            + "%";
    }


    // ==================================================
    // COMPARAÇÃO
    // ==================================================

    function atualizarComparacaoAnaliseGastos(
        comparacao
    ) {

        const bloco =
            $("#blocoComparacaoGastos");


        if (
            !comparacao
            || !comparacao.disponivel
        ) {

            bloco.hide();

            return;
        }


        bloco.show();


        definirTextoAnaliseGastos(
            "#gastosPeriodoAtual",
            formatarMoedaAnaliseGastos(
                comparacao.atual
                || 0
            )
        );


        definirTextoAnaliseGastos(
            "#gastosPeriodoAnterior",
            formatarMoedaAnaliseGastos(
                comparacao.anterior
                || 0
            )
        );


        definirTextoAnaliseGastos(
            "#gastosVariacaoValor",
            formatarMoedaAnaliseGastos(
                comparacao.variacao_valor
                || 0
            )
        );


        let percentual =
            "—";


        if (
            comparacao.variacao_percentual
            !== null
            && comparacao.variacao_percentual
            !== undefined
        ) {

            percentual =
                Number(
                    comparacao.variacao_percentual
                )
                    .toLocaleString(
                        "pt-PT",
                        {
                            minimumFractionDigits:
                                2,

                            maximumFractionDigits:
                                2
                        }
                    )
                + "%";
        }


        definirTextoAnaliseGastos(
            "#gastosVariacaoPercentual",
            percentual
        );
    }


    // ==================================================
    // NOMES DAS CATEGORIAS
    // ==================================================

    function nomeCategoriaAnaliseGastos(
        categoria
    ) {

        const nomes = {

            CUSTO_INVENTARIOS:
                "Custo dos inventários",

            PESSOAL:
                "Gastos com pessoal",

            FORNECIMENTOS_SERVICOS:
                "Fornecimentos e serviços",

            IMPARIDADES:
                "Perdas por imparidade",

            AMORTIZACOES:
                "Amortizações do período",

            PROVISOES:
                "Provisões do período",

            JUSTO_VALOR:
                "Perdas por redução do justo valor",

            IMPOSTOS_TAXAS:
                "Impostos e taxas",

            OUTROS_OPERACIONAIS:
                "Outros gastos operacionais",

            FINANCEIROS:
                "Gastos financeiros",

            OUTROS:
                "Outros gastos"
        };


        return nomes[
            categoria
        ]
            || "Outros gastos";
    }


    // ==================================================
    // DATAS PADRÃO
    // ==================================================

    function definirDatasPadraoAnaliseGastos() {

        const hoje =
            new Date();


        const primeiroDia =
            new Date(
                hoje.getFullYear(),
                hoje.getMonth(),
                1
            );


        if (
            !$("#gastosDataInicial")
                .val()
        ) {

            $("#gastosDataInicial")
                .val(
                    formatarDataInputAnaliseGastos(
                        primeiroDia
                    )
                );
        }


        if (
            !$("#gastosDataFinal")
                .val()
        ) {

            $("#gastosDataFinal")
                .val(
                    formatarDataInputAnaliseGastos(
                        hoje
                    )
                );
        }
    }


    function formatarDataInputAnaliseGastos(
        data
    ) {

        const ano =
            data.getFullYear();


        const mes =
            String(
                data.getMonth() + 1
            )
                .padStart(
                    2,
                    "0"
                );


        const dia =
            String(
                data.getDate()
            )
                .padStart(
                    2,
                    "0"
                );


        return `${ano}-${mes}-${dia}`;
    }


    // ==================================================
    // LIMPAR
    // ==================================================

    function limparFiltrosAnaliseGastos() {

        $("#gastosPlanoContasId")
            .val("");


        if (
            !$("#gastosFilialId")
                .prop(
                    "disabled"
                )
        ) {

            $("#gastosFilialId")
                .val("");
        }


        $("#gastosCentroCustoId")
            .val("");


        $("#gastosCompararAnterior")
            .prop(
                "checked",
                true
            );


        $("#gastosDataInicial")
            .val("");


        $("#gastosDataFinal")
            .val("");


        definirDatasPadraoAnaliseGastos();


        gerarAnaliseGastos();
    }


    // ==================================================
    // LIMPAR RESULTADO
    // ==================================================

    function limparResultadoAnaliseGastos() {

        preencherResumoAnaliseGastos(
            {},
            {}
        );


        preencherTabelaDetalhesGastos(
            []
        );


        $("#blocoComparacaoGastos")
            .hide();
    }


    // ==================================================
    // CARREGAMENTO
    // ==================================================

    function mostrarCarregamentoAnaliseGastos(
        mostrar
    ) {

        $("#gastosCarregando")
            .toggle(
                Boolean(
                    mostrar
                )
            );
    }


    function bloquearFiltrosAnaliseGastos(
        bloquear
    ) {

        $("#formFiltroAnaliseGastos")
            .find(
                "input, select, button"
            )
            .prop(
                "disabled",
                Boolean(
                    bloquear
                )
            );


        $("#btnAtualizarAnaliseGastos")
            .prop(
                "disabled",
                Boolean(
                    bloquear
                )
            );
    }


    // ==================================================
    // ERRO
    // ==================================================

    function mostrarErroAnaliseGastos(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível carregar",

            text:
                mensagem
                || "Ocorreu um erro ao gerar a análise de gastos.",

            confirmButtonText:
                "Fechar"
        });
    }


    // ==================================================
    // FORMATAÇÃO
    // ==================================================

    function formatarMoedaAnaliseGastos(
        valor
    ) {

        return Number(
            valor || 0
        )
            .toLocaleString(
                "pt-PT",
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


    function numeroMoedaAnaliseGastos(
        texto
    ) {

        if (!texto) {
            return 0;
        }


        let valor =
            String(
                texto
            )
                .replace(
                    /[^\d,.-]/g,
                    ""
                );


        valor =
            valor
                .replace(
                    /\./g,
                    ""
                )
                .replace(
                    ",",
                    "."
                );


        return Number(
            valor
        )
            || 0;
    }


    function definirTextoAnaliseGastos(
        seletor,
        valor
    ) {

        $(seletor)
            .text(
                valor
            );
    }


    function escaparHtmlAnaliseGastos(
        valor
    ) {

        return $("<div>")
            .text(
                valor
                ?? ""
            )
            .html();
    }

})(jQuery);