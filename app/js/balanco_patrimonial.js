"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const BALANCO_URL =
        "/erpplus/app/ajax/balanco_patrimonial_ajax.php";


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        /*
         * Evita executar este ficheiro
         * fora da página do Balanço Patrimonial.
         */
        if (!paginaBalancoExiste()) {
            return;
        }

        configurarEventosBalanco();

        definirDataPadraoBalanco();

        carregarDadosIniciaisBalanco();

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaBalancoExiste() {

        return (
            $("#formFiltroBalanco").length > 0
            && $("#balancoDataReferencia").length > 0
            && $("#tabelaAtivosBalanco").length > 0
            && $("#tabelaPassivosBalanco").length > 0
            && $("#tabelaCapitalBalanco").length > 0
            && $("#tabelaResultadosBalanco").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosBalanco() {

        $("#formFiltroBalanco").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                gerarBalanco();

            }
        );

        $("#btnAtualizarBalanco").on(
            "click",
            function () {

                gerarBalanco();

            }
        );

        $("#btnLimparBalanco").on(
            "click",
            function () {

                limparFiltrosBalanco();

            }
        );

    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisBalanco() {

        mostrarCarregamentoBalanco(true);

        bloquearFiltrosBalanco(true);

        try {

            const resposta =
                await requisicaoBalanco(
                    BALANCO_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            preencherPlanosBalanco(
                dados.planos || [],
                dados.plano_padrao_id
                ?? null
            );

            preencherFiliaisBalanco(
                dados.filiais || [],
                dados.filial_sessao_id
                ?? null
            );

            await gerarBalanco();

        } catch (erro) {

            console.error(
                "Erro ao carregar dados do Balanço Patrimonial:",
                erro
            );

            mostrarErroBalanco(
                erro.message
            );

        } finally {

            mostrarCarregamentoBalanco(false);

            bloquearFiltrosBalanco(false);

        }

    }


    // ==================================================
    // GERAR BALANÇO
    // ==================================================

    async function gerarBalanco() {

        if (!paginaBalancoExiste()) {
            return;
        }

        const dataReferencia =
            $("#balancoDataReferencia")
                .val();

        const filialId =
            $("#balancoFilialId")
                .val();

        const planoContasId =
            $("#balancoPlanoContasId")
                .val();

        if (!dataReferencia) {

            mostrarErroBalanco(
                "Informe a data de referência."
            );

            return;
        }

        mostrarCarregamentoBalanco(true);

        bloquearFiltrosBalanco(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "gerar",

                    data_referencia:
                        dataReferencia,

                    filial_id:
                        filialId || "",

                    plano_contas_id:
                        planoContasId || ""
                });

            const resposta =
                await requisicaoBalanco(
                    BALANCO_URL
                    + "?"
                    + parametros.toString()
                );

            const dados =
                resposta.data || {};

            preencherTabelaBalanco(
                "#tabelaAtivosBalanco",
                dados.ativos || [],
                "Nenhuma conta de ativo encontrada."
            );

            preencherTabelaBalanco(
                "#tabelaPassivosBalanco",
                dados.passivos || [],
                "Nenhuma conta de passivo encontrada."
            );

            preencherTabelaBalanco(
                "#tabelaCapitalBalanco",
                dados.capital_proprio || [],
                "Nenhuma conta de capital próprio encontrada."
            );

            preencherTabelaBalanco(
                "#tabelaResultadosBalanco",
                dados.resultados || [],
                "Nenhuma conta de resultado encontrada."
            );

            preencherTotaisBalanco(
                dados.totais || {}
            );

            atualizarEstadoBalanco(
                dados
            );

        } catch (erro) {

            console.error(
                "Erro ao gerar Balanço Patrimonial:",
                erro
            );

            limparResultadoBalanco();

            mostrarErroBalanco(
                erro.message
            );

        } finally {

            mostrarCarregamentoBalanco(false);

            bloquearFiltrosBalanco(false);

        }

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoBalanco(
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
                || "Não foi possível gerar o Balanço Patrimonial."
            );
        }

        return json;
    }


    // ==================================================
    // PLANOS
    // ==================================================

    function preencherPlanosBalanco(
        planos,
        planoPadraoId
    ) {

        const select =
            $("#balancoPlanoContasId");

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

                        ${escaparHtmlBalanco(
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

    function preencherFiliaisBalanco(
        filiais,
        filialSessaoId
    ) {

        const select =
            $("#balancoFilialId");

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

                        ${escaparHtmlBalanco(
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

    function preencherTabelaBalanco(
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

                        ${escaparHtmlBalanco(
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
                            ${escaparHtmlBalanco(
                    linha.codigo || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlBalanco(
                    linha.nome || "-"
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaBalanco(
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

    function preencherTotaisBalanco(
        totais
    ) {

        const totalAtivo =
            formatarMoedaBalanco(
                totais.ativo
            );

        const totalPassivo =
            formatarMoedaBalanco(
                totais.passivo
            );

        const totalCapitalBase =
            formatarMoedaBalanco(
                totais.capital_proprio_base
                ?? totais.capital_proprio
                ?? 0
            );

        const totalCapital =
            formatarMoedaBalanco(
                totais.capital_proprio_total
                ?? 0
            );

        const totalResultado =
            formatarMoedaBalanco(
                totais.resultado
            );

        const totalPassivoCapital =
            formatarMoedaBalanco(
                totais.passivo_capital_resultado
            );

        const diferenca =
            formatarMoedaBalanco(
                Math.abs(
                    Number(
                        totais.diferenca || 0
                    )
                )
            );

        definirTextoBalanco(
            "#balancoTotalAtivo",
            totalAtivo
        );

        definirTextoBalanco(
            "#balancoTotalPassivo",
            totalPassivo
        );

        definirTextoBalanco(
            "#balancoTotalCapital",
            totalCapital
        );

        definirTextoBalanco(
            "#balancoTotalResultado",
            totalResultado
        );

        definirTextoBalanco(
            "#balancoTotalPassivoCapital",
            totalPassivoCapital
        );

        definirTextoBalanco(
            "#balancoDiferenca",
            diferenca
        );

        /*
         * Rodapés das tabelas.
         */
        definirTextoBalanco(
            "#balancoRodapeAtivo",
            totalAtivo
        );

        definirTextoBalanco(
            "#balancoRodapePassivo",
            totalPassivo
        );

        definirTextoBalanco(
            "#balancoRodapeCapital",
            totalCapitalBase
        );

        definirTextoBalanco(
            "#balancoRodapeResultado",
            totalResultado
        );

        /*
         * Resumo final.
         */
        definirTextoBalanco(
            "#balancoResumoAtivo",
            totalAtivo
        );

        definirTextoBalanco(
            "#balancoResumoPassivo",
            totalPassivo
        );

        definirTextoBalanco(
            "#balancoResumoCapital",
            totalCapitalBase
        );

        definirTextoBalanco(
            "#balancoResumoResultado",
            totalResultado
        );

        definirTextoBalanco(
            "#balancoResumoPassivoCapital",
            totalPassivoCapital
        );

        definirTextoBalanco(
            "#balancoResumoDiferenca",
            diferenca
        );

    }


    // ==================================================
    // ESTADO
    // ==================================================

    function atualizarEstadoBalanco(
        dados
    ) {

        const elemento =
            $("#estadoBalanco");

        elemento.removeClass(
            "alert-success alert-danger alert-warning"
        );

        const totais =
            dados.totais || {};

        const totalAtivo =
            Number(
                totais.ativo || 0
            );

        const totalPassivoCapital =
            Number(
                totais.passivo_capital_resultado
                || 0
            );

        const diferenca =
            Number(
                totais.diferenca || 0
            );

        const semMovimentos =
            totalAtivo === 0
            && totalPassivoCapital === 0;

        if (semMovimentos) {

            elemento
                .addClass(
                    "alert-warning"
                )
                .html(`
                    <strong>Sem movimentos patrimoniais.</strong>
                    Não foram encontrados saldos patrimoniais
                    até à data selecionada.
                `);

            elemento.show();

            return;
        }

        if (Boolean(dados.equilibrado)) {

            elemento
                .addClass(
                    "alert-success"
                )
                .html(`
                    <strong>Balanço equilibrado.</strong>
                    O total do ativo é igual ao total do passivo,
                    capital próprio e resultados.
                `);

        } else {

            elemento
                .addClass(
                    "alert-danger"
                )
                .html(`
                    <strong>Balanço desequilibrado.</strong>
                    Existe uma diferença de
                    ${formatarMoedaBalanco(
                    Math.abs(diferenca)
                )}.
                `);

        }

        elemento.show();

    }


    // ==================================================
    // DATA PADRÃO
    // ==================================================

    function definirDataPadraoBalanco() {

        const hoje =
            new Date();

        if (
            !$("#balancoDataReferencia")
                .val()
        ) {
            $("#balancoDataReferencia")
                .val(
                    formatarDataInputBalanco(
                        hoje
                    )
                );
        }

    }


    function formatarDataInputBalanco(
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

    function limparFiltrosBalanco() {

        $("#balancoPlanoContasId")
            .val("");

        if (
            !$("#balancoFilialId")
                .prop("disabled")
        ) {
            $("#balancoFilialId")
                .val("");
        }

        $("#balancoDataReferencia")
            .val("");

        definirDataPadraoBalanco();

        gerarBalanco();

    }


    function limparResultadoBalanco() {

        preencherTabelaBalanco(
            "#tabelaAtivosBalanco",
            [],
            "Não foi possível carregar os ativos."
        );

        preencherTabelaBalanco(
            "#tabelaPassivosBalanco",
            [],
            "Não foi possível carregar os passivos."
        );

        preencherTabelaBalanco(
            "#tabelaCapitalBalanco",
            [],
            "Não foi possível carregar o capital próprio."
        );

        preencherTabelaBalanco(
            "#tabelaResultadosBalanco",
            [],
            "Não foi possível carregar os resultados."
        );

        preencherTotaisBalanco({});

        $("#estadoBalanco")
            .hide();

    }


    // ==================================================
    // CARREGAMENTO
    // ==================================================

    function mostrarCarregamentoBalanco(
        mostrar
    ) {

        $("#balancoCarregando")
            .toggle(
                Boolean(mostrar)
            );

    }


    function bloquearFiltrosBalanco(
        bloquear
    ) {

        $("#formFiltroBalanco")
            .find(
                "input, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnAtualizarBalanco")
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        if (!bloquear) {

            const filial =
                $("#balancoFilialId");

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

    function mostrarErroBalanco(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível carregar",

            text:
                mensagem
                || "Ocorreu um erro ao gerar o Balanço Patrimonial.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // FORMATAÇÃO
    // ==================================================

    function formatarMoedaBalanco(
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


    function definirTextoBalanco(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function escaparHtmlBalanco(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);