"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const BALANCETE_URL =
        "/erpplus/app/ajax/balancete_ajax.php";


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        /*
         * Evita que este ficheiro execute
         * em páginas que não sejam o Balancete.
         */
        if (!paginaBalanceteExiste()) {
            return;
        }

        configurarEventosBalancete();

        definirPeriodoPadraoBalancete();

        carregarDadosIniciaisBalancete();

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaBalanceteExiste() {

        return (
            $("#formFiltroBalancete").length > 0
            && $("#balanceteDataInicio").length > 0
            && $("#balanceteDataFim").length > 0
            && $("#tabelaBalancete").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosBalancete() {

        $("#formFiltroBalancete").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                gerarBalancete();

            }
        );

        $("#btnAtualizarBalancete").on(
            "click",
            function () {

                gerarBalancete();

            }
        );

        $("#btnLimparBalancete").on(
            "click",
            function () {

                limparFiltrosBalancete();

            }
        );

    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisBalancete() {

        mostrarCarregamentoBalancete(true);

        bloquearFiltrosBalancete(true);

        try {

            const resposta =
                await requisicaoBalancete(
                    BALANCETE_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            preencherPlanosBalancete(
                dados.planos || []
            );

            preencherFiliaisBalancete(
                dados.filiais || [],
                dados.filial_sessao_id
                ?? null
            );

            await gerarBalancete();

        } catch (erro) {

            console.error(
                "Erro ao carregar dados do Balancete:",
                erro
            );

            mostrarErroBalancete(
                erro.message
            );

        } finally {

            mostrarCarregamentoBalancete(false);

            bloquearFiltrosBalancete(false);

        }

    }


    // ==================================================
    // GERAR BALANCETE
    // ==================================================

    async function gerarBalancete() {

        if (!paginaBalanceteExiste()) {
            return;
        }

        const dataInicio =
            $("#balanceteDataInicio").val();

        const dataFim =
            $("#balanceteDataFim").val();

        const filialId =
            $("#balanceteFilialId").val();

        const planoContasId =
            $("#balancetePlanoContasId").val();

        if (!dataInicio || !dataFim) {

            mostrarErroBalancete(
                "Informe a data inicial e a data final."
            );

            return;
        }

        if (dataInicio > dataFim) {

            mostrarErroBalancete(
                "A data inicial não pode ser posterior à data final."
            );

            return;
        }

        mostrarCarregamentoBalancete(true);

        bloquearFiltrosBalancete(true);

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
                await requisicaoBalancete(
                    BALANCETE_URL
                    + "?"
                    + parametros.toString()
                );

            const dados =
                resposta.data || {};

            preencherTabelaBalancete(
                dados.linhas || []
            );

            preencherTotaisBalancete(
                dados.totais || {}
            );

            atualizarEstadoBalancete(
                dados
            );

        } catch (erro) {

            console.error(
                "Erro ao gerar Balancete:",
                erro
            );

            limparResultadoBalancete();

            mostrarErroBalancete(
                erro.message
            );

        } finally {

            mostrarCarregamentoBalancete(false);

            bloquearFiltrosBalancete(false);

        }

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoBalancete(
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
                || "Não foi possível gerar o Balancete."
            );
        }

        return json;
    }


    // ==================================================
    // PLANOS
    // ==================================================

    function preencherPlanosBalancete(
        planos
    ) {

        const select =
            $("#balancetePlanoContasId");

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
                        ? plano.codigo + " - "
                        : "";

                const selecionado =
                    Number(plano.padrao || 0) === 1
                        ? "selected"
                        : "";

                select.append(`
                    <option
                        value="${Number(plano.id)}"
                        ${selecionado}>

                        ${escaparHtmlBalancete(
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

    function preencherFiliaisBalancete(
        filiais,
        filialSessaoId
    ) {

        const select =
            $("#balanceteFilialId");

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
                        ? filial.codigo + " - "
                        : "";

                select.append(`
                    <option value="${Number(filial.id)}">

                        ${escaparHtmlBalancete(
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
    // TABELA
    // ==================================================

    function preencherTabelaBalancete(
        linhas
    ) {

        const tbody =
            $("#tabelaBalancete tbody");

        tbody.empty();

        if (!linhas.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="9"
                        class="text-center text-muted py-4">

                        Nenhum movimento contabilístico encontrado
                        para o período selecionado.

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
                            ${escaparHtmlBalancete(
                    linha.codigo || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlBalancete(
                    linha.nome || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlBalancete(
                    linha.grupo || "-"
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaBalancete(
                    linha.saldo_inicial_devedor
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaBalancete(
                    linha.saldo_inicial_credor
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaBalancete(
                    linha.debitos
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaBalancete(
                    linha.creditos
                )}
                        </td>

                        <td class="text-right font-weight-bold">
                            ${formatarMoedaBalancete(
                    linha.saldo_final_devedor
                )}
                        </td>

                        <td class="text-right font-weight-bold">
                            ${formatarMoedaBalancete(
                    linha.saldo_final_credor
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

    function preencherTotaisBalancete(
        totais
    ) {

        definirTextoBalancete(
            "#totalSaldoInicialDevedor",
            formatarMoedaBalancete(
                totais.saldo_inicial_devedor
            )
        );

        definirTextoBalancete(
            "#totalSaldoInicialCredor",
            formatarMoedaBalancete(
                totais.saldo_inicial_credor
            )
        );

        definirTextoBalancete(
            "#totalDebitosBalancete",
            formatarMoedaBalancete(
                totais.debitos
            )
        );

        definirTextoBalancete(
            "#totalCreditosBalancete",
            formatarMoedaBalancete(
                totais.creditos
            )
        );

        definirTextoBalancete(
            "#totalSaldoFinalDevedor",
            formatarMoedaBalancete(
                totais.saldo_final_devedor
            )
        );

        definirTextoBalancete(
            "#totalSaldoFinalCredor",
            formatarMoedaBalancete(
                totais.saldo_final_credor
            )
        );

        definirTextoBalancete(
            "#resumoDebitosBalancete",
            formatarMoedaBalancete(
                totais.debitos
            )
        );

        definirTextoBalancete(
            "#resumoCreditosBalancete",
            formatarMoedaBalancete(
                totais.creditos
            )
        );

        definirTextoBalancete(
            "#resumoSaldoFinalDevedor",
            formatarMoedaBalancete(
                totais.saldo_final_devedor
            )
        );

        definirTextoBalancete(
            "#resumoSaldoFinalCredor",
            formatarMoedaBalancete(
                totais.saldo_final_credor
            )
        );

    }


    // ==================================================
    // ESTADO DO BALANCETE
    // ==================================================

    function atualizarEstadoBalancete(
        dados
    ) {

        const equilibrado =
            Boolean(
                dados.equilibrado
            );

        const diferenca =
            Number(
                dados.diferenca_movimentos
                || 0
            );

        const elemento =
            $("#estadoBalancete");

        elemento.removeClass(
            "alert-success alert-danger alert-warning"
        );

        if (equilibrado) {

            elemento
                .addClass(
                    "alert-success"
                )
                .html(`
                    <strong>Balancete equilibrado.</strong>
                    Os débitos e créditos do período são iguais.
                `);

        } else {

            elemento
                .addClass(
                    "alert-danger"
                )
                .html(`
                    <strong>Balancete desequilibrado.</strong>
                    Diferença de
                    ${formatarMoedaBalancete(
                    Math.abs(diferenca)
                )}.
                `);

        }

        elemento.show();

    }


    // ==================================================
    // PERÍODO PADRÃO
    // ==================================================

    function definirPeriodoPadraoBalancete() {

        const hoje =
            new Date();

        const primeiroDiaAno =
            new Date(
                hoje.getFullYear(),
                0,
                1
            );

        if (
            !$("#balanceteDataInicio")
                .val()
        ) {
            $("#balanceteDataInicio")
                .val(
                    formatarDataInputBalancete(
                        primeiroDiaAno
                    )
                );
        }

        if (
            !$("#balanceteDataFim")
                .val()
        ) {
            $("#balanceteDataFim")
                .val(
                    formatarDataInputBalancete(
                        hoje
                    )
                );
        }

    }


    function formatarDataInputBalancete(
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

    function limparFiltrosBalancete() {

        $("#balancetePlanoContasId")
            .val("");

        if (
            !$("#balanceteFilialId")
                .prop("disabled")
        ) {
            $("#balanceteFilialId")
                .val("");
        }

        $("#balanceteDataInicio")
            .val("");

        $("#balanceteDataFim")
            .val("");

        definirPeriodoPadraoBalancete();

        gerarBalancete();

    }


    function limparResultadoBalancete() {

        $("#tabelaBalancete tbody")
            .html(`
                <tr>

                    <td
                        colspan="9"
                        class="text-center text-muted py-4">

                        Não foi possível carregar o Balancete.

                    </td>

                </tr>
            `);

        preencherTotaisBalancete({});

        $("#estadoBalancete").hide();

    }


    // ==================================================
    // CARREGAMENTO
    // ==================================================

    function mostrarCarregamentoBalancete(
        mostrar
    ) {

        $("#balanceteCarregando")
            .toggle(
                Boolean(mostrar)
            );

    }


    function bloquearFiltrosBalancete(
        bloquear
    ) {

        $("#formFiltroBalancete")
            .find(
                "input, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        /*
         * Mantém a filial da sessão bloqueada
         * após terminar o carregamento.
         */
        if (!bloquear) {

            const selectFilial =
                $("#balanceteFilialId");

            if (
                selectFilial
                    .find("option")
                    .length === 1
                && selectFilial.val()
            ) {
                selectFilial.prop(
                    "disabled",
                    true
                );
            }

        }

    }


    // ==================================================
    // MENSAGENS
    // ==================================================

    function mostrarErroBalancete(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível carregar",

            text:
                mensagem
                || "Ocorreu um erro ao gerar o Balancete.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // FORMATAÇÃO
    // ==================================================

    function formatarMoedaBalancete(
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


    function definirTextoBalancete(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function escaparHtmlBalancete(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);