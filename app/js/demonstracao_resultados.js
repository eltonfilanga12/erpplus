"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const DRE_URL =
        "/erpplus/app/ajax/demonstracao_resultados_ajax.php";


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        /*
         * Evita executar este ficheiro
         * fora da página da Demonstração de Resultados.
         */
        if (!paginaDreExiste()) {
            return;
        }

        configurarEventosDre();

        definirPeriodoPadraoDre();

        carregarDadosIniciaisDre();

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaDreExiste() {

        return (
            $("#formFiltroDre").length > 0
            && $("#dreDataInicio").length > 0
            && $("#dreDataFim").length > 0
            && $("#tabelaReceitasDre").length > 0
            && $("#tabelaCustosDre").length > 0
            && $("#tabelaDespesasDre").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosDre() {

        $("#formFiltroDre").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                gerarDre();

            }
        );

        $("#btnAtualizarDre").on(
            "click",
            function () {

                gerarDre();

            }
        );

        $("#btnLimparDre").on(
            "click",
            function () {

                limparFiltrosDre();

            }
        );

    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisDre() {

        mostrarCarregamentoDre(true);

        bloquearFiltrosDre(true);

        try {

            const resposta =
                await requisicaoDre(
                    DRE_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            preencherPlanosDre(
                dados.planos || [],
                dados.plano_padrao_id
                ?? null
            );

            preencherFiliaisDre(
                dados.filiais || [],
                dados.filial_sessao_id
                ?? null
            );

            await gerarDre();

        } catch (erro) {

            console.error(
                "Erro ao carregar dados da Demonstração de Resultados:",
                erro
            );

            mostrarErroDre(
                erro.message
            );

        } finally {

            mostrarCarregamentoDre(false);

            bloquearFiltrosDre(false);

        }

    }


    // ==================================================
    // GERAR DRE
    // ==================================================

    async function gerarDre() {

        if (!paginaDreExiste()) {
            return;
        }

        const dataInicio =
            $("#dreDataInicio")
                .val();

        const dataFim =
            $("#dreDataFim")
                .val();

        const filialId =
            $("#dreFilialId")
                .val();

        const planoContasId =
            $("#drePlanoContasId")
                .val();

        if (!dataInicio || !dataFim) {

            mostrarErroDre(
                "Informe a data inicial e a data final."
            );

            return;
        }

        if (dataInicio > dataFim) {

            mostrarErroDre(
                "A data inicial não pode ser posterior à data final."
            );

            return;
        }

        mostrarCarregamentoDre(true);

        bloquearFiltrosDre(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "gerar",

                    data_inicio:
                        dataInicio,

                    data_fim:
                        dataFim,

                    filial_id:
                        filialId || "",

                    plano_contas_id:
                        planoContasId || ""
                });

            const resposta =
                await requisicaoDre(
                    DRE_URL
                    + "?"
                    + parametros.toString()
                );

            const dados =
                resposta.data || {};

            preencherTabelaDre(
                "#tabelaReceitasDre",
                dados.receitas || [],
                "Nenhuma receita encontrada."
            );

            preencherTabelaDre(
                "#tabelaCustosDre",
                dados.custos || [],
                "Nenhum custo encontrado."
            );

            preencherTabelaDre(
                "#tabelaDespesasDre",
                dados.despesas || [],
                "Nenhuma despesa encontrada."
            );

            preencherTotaisDre(
                dados.totais || {}
            );

            atualizarResultadoDre(
                dados
            );

        } catch (erro) {

            console.error(
                "Erro ao gerar Demonstração de Resultados:",
                erro
            );

            limparResultadoDre();

            mostrarErroDre(
                erro.message
            );

        } finally {

            mostrarCarregamentoDre(false);

            bloquearFiltrosDre(false);

        }

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoDre(
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

        if (resposta.status === 401) {

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
                || "Não foi possível gerar a Demonstração de Resultados."
            );
        }

        return json;
    }


    // ==================================================
    // PLANOS
    // ==================================================

    function preencherPlanosDre(
        planos,
        planoPadraoId
    ) {

        const select =
            $("#drePlanoContasId");

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
                    Number(plano.id)
                        === Number(
                            planoPadraoId
                        )
                        ? "selected"
                        : "";

                select.append(`
                    <option
                        value="${Number(plano.id)}"
                        ${selecionado}>

                        ${escaparHtmlDre(
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

    function preencherFiliaisDre(
        filiais,
        filialSessaoId
    ) {

        const select =
            $("#dreFilialId");

        select.empty();

        if (filialSessaoId) {

            select.append(`
                <option
                    value="${Number(filialSessaoId)}"
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

                        ${escaparHtmlDre(
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
    // TABELAS
    // ==================================================

    function preencherTabelaDre(
        seletor,
        linhas,
        mensagemVazia
    ) {

        const tbody =
            $(seletor)
                .find("tbody");

        tbody.empty();

        if (!linhas.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="3"
                        class="text-center text-muted py-3">

                        ${escaparHtmlDre(
                mensagemVazia
            )}

                    </td>

                </tr>
            `);

            return;
        }

        linhas.forEach(
            function (linha) {

                tbody.append(`
                    <tr>

                        <td>
                            ${escaparHtmlDre(
                    linha.codigo || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlDre(
                    linha.nome || "-"
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaDre(
                    linha.valor
                )}
                        </td>

                    </tr>
                `);

            }
        );

    }


    // ==================================================
    // TOTAIS
    // ==================================================

    function preencherTotaisDre(
        totais
    ) {


        definirTextoDre(
            "#dreTotalReceitas",
            formatarMoedaDre(
                totais.receitas
            )
        );

        definirTextoDre(
            "#dreTotalCustos",
            formatarMoedaDre(
                totais.custos
            )
        );

        definirTextoDre(
            "#dreResultadoBruto",
            formatarMoedaDre(
                totais.resultado_bruto
            )
        );

        definirTextoDre(
            "#dreTotalDespesas",
            formatarMoedaDre(
                totais.despesas
            )
        );

        definirTextoDre(
            "#dreResultadoOperacional",
            formatarMoedaDre(
                totais.resultado_operacional
            )
        );

        definirTextoDre(
            "#dreResultadoLiquido",
            formatarMoedaDre(
                totais.resultado_liquido
            )
        );

        definirTextoDre(
            "#dreRodapeReceitas",
            formatarMoedaDre(
                totais.receitas
            )
        );

        definirTextoDre(
            "#dreRodapeCustos",
            formatarMoedaDre(
                totais.custos
            )
        );

        definirTextoDre(
            "#dreRodapeDespesas",
            formatarMoedaDre(
                totais.despesas
            )
        );

        definirTextoDre(
            "#dreResumoReceitas",
            formatarMoedaDre(
                totais.receitas
            )
        );

        definirTextoDre(
            "#dreResumoCustos",
            formatarMoedaDre(
                totais.custos
            )
        );

        definirTextoDre(
            "#dreResumoResultadoBruto",
            formatarMoedaDre(
                totais.resultado_bruto
            )
        );

        definirTextoDre(
            "#dreResumoDespesas",
            formatarMoedaDre(
                totais.despesas
            )
        );

        definirTextoDre(
            "#dreResumoResultadoOperacional",
            formatarMoedaDre(
                totais.resultado_operacional
            )
        );

        definirTextoDre(
            "#dreResumoResultadoLiquido",
            formatarMoedaDre(
                totais.resultado_liquido
            )
        );

    }


    // ==================================================
    // RESULTADO
    // ==================================================

    function atualizarResultadoDre(
        dados
    ) {

        const resultado =
            Number(
                dados.totais
                    ?.resultado_liquido
                || 0
            );

        const positivo =
            Boolean(
                dados.resultado_positivo
            );

        const elemento =
            $("#estadoResultadoDre");

        elemento.removeClass(
            "alert-success alert-danger alert-warning"
        );

        if (resultado > 0 && positivo) {

            elemento
                .addClass(
                    "alert-success"
                )
                .html(`
                    <strong>Resultado positivo.</strong>
                    A empresa registou lucro de
                    ${formatarMoedaDre(
                    resultado
                )}.
                `);

        } else if (resultado < 0) {

            elemento
                .addClass(
                    "alert-danger"
                )
                .html(`
                    <strong>Resultado negativo.</strong>
                    A empresa registou prejuízo de
                    ${formatarMoedaDre(
                    Math.abs(resultado)
                )}.
                `);

        } else {

            elemento
                .addClass(
                    "alert-warning"
                )
                .html(`
                    <strong>Resultado nulo.</strong>
                    As receitas são iguais aos custos e despesas.
                `);

        }

        elemento.show();

    }


    // ==================================================
    // PERÍODO PADRÃO
    // ==================================================

    function definirPeriodoPadraoDre() {

        const hoje =
            new Date();

        const primeiroDiaAno =
            new Date(
                hoje.getFullYear(),
                0,
                1
            );

        if (!$("#dreDataInicio").val()) {

            $("#dreDataInicio")
                .val(
                    formatarDataInputDre(
                        primeiroDiaAno
                    )
                );

        }

        if (!$("#dreDataFim").val()) {

            $("#dreDataFim")
                .val(
                    formatarDataInputDre(
                        hoje
                    )
                );

        }

    }


    function formatarDataInputDre(
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
    // LIMPAR
    // ==================================================

    function limparFiltrosDre() {

        $("#drePlanoContasId")
            .val("");

        if (
            !$("#dreFilialId")
                .prop("disabled")
        ) {
            $("#dreFilialId")
                .val("");
        }

        $("#dreDataInicio")
            .val("");

        $("#dreDataFim")
            .val("");

        definirPeriodoPadraoDre();

        gerarDre();

    }


    function limparResultadoDre() {

        preencherTabelaDre(
            "#tabelaReceitasDre",
            [],
            "Não foi possível carregar as receitas."
        );

        preencherTabelaDre(
            "#tabelaCustosDre",
            [],
            "Não foi possível carregar os custos."
        );

        preencherTabelaDre(
            "#tabelaDespesasDre",
            [],
            "Não foi possível carregar as despesas."
        );

        preencherTotaisDre({});

        $("#estadoResultadoDre")
            .hide();

    }


    // ==================================================
    // CARREGAMENTO
    // ==================================================

    function mostrarCarregamentoDre(
        mostrar
    ) {

        $("#dreCarregando")
            .toggle(
                Boolean(mostrar)
            );

    }


    function bloquearFiltrosDre(
        bloquear
    ) {

        $("#formFiltroDre")
            .find(
                "input, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnAtualizarDre")
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        if (!bloquear) {

            const filial =
                $("#dreFilialId");

            if (
                filial
                    .find("option")
                    .length === 1
                && filial.val()
            ) {
                filial.prop(
                    "disabled",
                    true
                );
            }

        }

    }


    // ==================================================
    // MENSAGENS
    // ==================================================

    function mostrarErroDre(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível carregar",

            text:
                mensagem
                || "Ocorreu um erro ao gerar a Demonstração de Resultados.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // FORMATAÇÃO
    // ==================================================

    function formatarMoedaDre(
        valor
    ) {

        return Number(
            valor || 0
        ).toLocaleString(
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


    function definirTextoDre(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function escaparHtmlDre(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);