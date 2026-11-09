"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const RAZAO_URL =
        "/erpplus/app/ajax/razao_contabilistico_ajax.php";


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        /*
         * Evita executar este ficheiro
         * em páginas diferentes do Razão.
         */
        if (!paginaRazaoExiste()) {
            return;
        }

        configurarEventosRazao();

        definirPeriodoPadraoRazao();

        carregarDadosIniciaisRazao();

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaRazaoExiste() {

        return (
            $("#formFiltroRazao").length > 0
            && $("#razaoDataInicio").length > 0
            && $("#razaoDataFim").length > 0
            && $("#razaoContaContabilId").length > 0
            && $("#tabelaRazao").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosRazao() {

        $("#formFiltroRazao").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                gerarRazao();

            }
        );

        $("#btnAtualizarRazao").on(
            "click",
            function () {

                gerarRazao();

            }
        );

        $("#btnLimparRazao").on(
            "click",
            function () {

                limparFiltrosRazao();

            }
        );

        $("#razaoPlanoContasId").on(
            "change",
            function () {

                const planoContasId =
                    $(this).val();

                carregarContasRazao(
                    planoContasId
                );

            }
        );

    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisRazao() {

        mostrarCarregamentoRazao(true);

        bloquearFiltrosRazao(true);

        try {

            const resposta =
                await requisicaoRazao(
                    RAZAO_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            preencherPlanosRazao(
                dados.planos || [],
                dados.plano_padrao_id
                ?? null
            );

            preencherContasRazao(
                dados.contas || []
            );

            preencherFiliaisRazao(
                dados.filiais || [],
                dados.filial_sessao_id
                ?? null
            );

        } catch (erro) {

            console.error(
                "Erro ao carregar dados do Razão:",
                erro
            );

            mostrarErroRazao(
                erro.message
            );

        } finally {

            mostrarCarregamentoRazao(false);

            bloquearFiltrosRazao(false);

        }

    }


    // ==================================================
    // CARREGAR CONTAS POR PLANO
    // ==================================================

    async function carregarContasRazao(
        planoContasId
    ) {

        const select =
            $("#razaoContaContabilId");

        select.empty();

        select.append(`
            <option value="">
                A carregar contas...
            </option>
        `);

        select.prop(
            "disabled",
            true
        );

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "contas",

                    plano_contas_id:
                        planoContasId || ""
                });

            const resposta =
                await requisicaoRazao(
                    RAZAO_URL
                    + "?"
                    + parametros.toString()
                );

            preencherContasRazao(
                resposta.data || []
            );

        } catch (erro) {

            console.error(
                "Erro ao carregar contas:",
                erro
            );

            select.empty();

            select.append(`
                <option value="">
                    Não foi possível carregar
                </option>
            `);

            mostrarErroRazao(
                erro.message
            );

        } finally {

            select.prop(
                "disabled",
                false
            );

        }

    }


    // ==================================================
    // GERAR RAZÃO
    // ==================================================

    async function gerarRazao() {

        if (!paginaRazaoExiste()) {
            return;
        }

        const contaContabilId =
            $("#razaoContaContabilId")
                .val();

        const dataInicio =
            $("#razaoDataInicio")
                .val();

        const dataFim =
            $("#razaoDataFim")
                .val();

        const filialId =
            $("#razaoFilialId")
                .val();

        if (!contaContabilId) {

            mostrarErroRazao(
                "Selecione a conta contabilística."
            );

            return;
        }

        if (!dataInicio || !dataFim) {

            mostrarErroRazao(
                "Informe a data inicial e a data final."
            );

            return;
        }

        if (dataInicio > dataFim) {

            mostrarErroRazao(
                "A data inicial não pode ser posterior à data final."
            );

            return;
        }

        mostrarCarregamentoRazao(true);

        bloquearFiltrosRazao(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "gerar",

                    conta_contabil_id:
                        contaContabilId,

                    data_inicio:
                        dataInicio,

                    data_fim:
                        dataFim,

                    filial_id:
                        filialId || ""
                });

            const resposta =
                await requisicaoRazao(
                    RAZAO_URL
                    + "?"
                    + parametros.toString()
                );

            const dados =
                resposta.data || {};

            preencherCabecalhoRazao(
                dados
            );

            preencherTabelaRazao(
                dados.linhas || []
            );

            preencherTotaisRazao(
                dados
            );

        } catch (erro) {

            console.error(
                "Erro ao gerar Razão:",
                erro
            );

            limparResultadoRazao();

            mostrarErroRazao(
                erro.message
            );

        } finally {

            mostrarCarregamentoRazao(false);

            bloquearFiltrosRazao(false);

        }

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoRazao(
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
                || "Não foi possível gerar o Razão Geral."
            );
        }

        return json;
    }


    // ==================================================
    // PLANOS
    // ==================================================

    function preencherPlanosRazao(
        planos,
        planoPadraoId
    ) {

        const select =
            $("#razaoPlanoContasId");

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

                        ${escaparHtmlRazao(
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
    // CONTAS
    // ==================================================

    function preencherContasRazao(
        contas
    ) {

        const select =
            $("#razaoContaContabilId");

        select.empty();

        select.append(`
            <option value="">
                Selecione a conta
            </option>
        `);

        contas.forEach(
            function (conta) {

                const codigo =
                    conta.codigo
                        ? conta.codigo
                        + " - "
                        : "";

                select.append(`
                    <option value="${Number(conta.id)}">

                        ${escaparHtmlRazao(
                    codigo
                    + (
                        conta.nome
                        || "Conta"
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

    function preencherFiliaisRazao(
        filiais,
        filialSessaoId
    ) {

        const select =
            $("#razaoFilialId");

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

                        ${escaparHtmlRazao(
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
    // CABEÇALHO
    // ==================================================

    function preencherCabecalhoRazao(
        dados
    ) {

        const conta =
            dados.conta || {};

        definirTextoRazao(
            "#razaoContaCodigo",
            conta.codigo || "-"
        );

        definirTextoRazao(
            "#razaoContaNome",
            conta.nome || "-"
        );

        definirTextoRazao(
            "#razaoContaGrupo",
            conta.grupo || "-"
        );

        definirTextoRazao(
            "#razaoContaNatureza",
            conta.natureza || "-"
        );

        $("#cabecalhoContaRazao")
            .show();

    }


    // ==================================================
    // TABELA
    // ==================================================

    function preencherTabelaRazao(
        linhas
    ) {

        const tbody =
            $("#tabelaRazao tbody");

        tbody.empty();

        if (!linhas.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="10"
                        class="text-center text-muted py-4">

                        Nenhum movimento encontrado
                        para a conta e período selecionados.

                    </td>

                </tr>
            `);

            return;
        }

        linhas.forEach(
            function (linha) {

                const documento =
                    linha.numero_documento
                    || linha.referencia
                    || linha.numero
                    || "-";

                const entidade =
                    linha.cliente_nome
                    || linha.fornecedor_nome
                    || "-";

                const centroCusto =
                    linha.centro_custo_codigo
                        ? linha.centro_custo_codigo
                        + " - "
                        + (
                            linha.centro_custo_nome
                            || ""
                        )
                        : (
                            linha.centro_custo_nome
                            || "-"
                        );

                tbody.append(`
                    <tr>

                        <td>
                            ${formatarDataRazao(
                    linha.data_lancamento
                )}
                        </td>

                        <td>
                            ${escaparHtmlRazao(
                    linha.diario_codigo
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlRazao(
                    documento
                )}
                        </td>

                        <td>
                            ${escaparHtmlRazao(
                    linha.descricao
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlRazao(
                    linha.origem
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlRazao(
                    entidade
                )}
                        </td>

                        <td>
                            ${escaparHtmlRazao(
                    centroCusto
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaRazao(
                    linha.debito
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaRazao(
                    linha.credito
                )}
                        </td>

                        <td class="text-right font-weight-bold">
                            ${formatarSaldoRazao(
                    linha.saldo_devedor,
                    linha.saldo_credor
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

    function preencherTotaisRazao(
        dados
    ) {

        definirTextoRazao(
            "#razaoSaldoInicialDevedor",
            formatarMoedaRazao(
                dados.saldo_inicial_devedor
            )
        );

        definirTextoRazao(
            "#razaoSaldoInicialCredor",
            formatarMoedaRazao(
                dados.saldo_inicial_credor
            )
        );

        definirTextoRazao(
            "#razaoTotalDebitos",
            formatarMoedaRazao(
                dados.total_debitos
            )
        );

        definirTextoRazao(
            "#razaoTotalCreditos",
            formatarMoedaRazao(
                dados.total_creditos
            )
        );

        definirTextoRazao(
            "#razaoSaldoFinalDevedor",
            formatarMoedaRazao(
                dados.saldo_final_devedor
            )
        );

        definirTextoRazao(
            "#razaoSaldoFinalCredor",
            formatarMoedaRazao(
                dados.saldo_final_credor
            )
        );


        definirTextoRazao(
            "#razaoRodapeDebitos",
            formatarMoedaRazao(
                dados.total_debitos
            )
        );

        definirTextoRazao(
            "#razaoRodapeCreditos",
            formatarMoedaRazao(
                dados.total_creditos
            )
        );

        definirTextoRazao(
            "#razaoRodapeSaldo",
            formatarSaldoRazao(
                dados.saldo_final_devedor,
                dados.saldo_final_credor
            )
        );

    }


    // ==================================================
    // PERÍODO PADRÃO
    // ==================================================

    function definirPeriodoPadraoRazao() {

        const hoje =
            new Date();

        const primeiroDiaAno =
            new Date(
                hoje.getFullYear(),
                0,
                1
            );

        if (!$("#razaoDataInicio").val()) {

            $("#razaoDataInicio")
                .val(
                    formatarDataInputRazao(
                        primeiroDiaAno
                    )
                );

        }

        if (!$("#razaoDataFim").val()) {

            $("#razaoDataFim")
                .val(
                    formatarDataInputRazao(
                        hoje
                    )
                );

        }

    }


    function formatarDataInputRazao(
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

    function limparFiltrosRazao() {

        $("#razaoPlanoContasId")
            .val("");

        preencherContasRazao([]);

        if (
            !$("#razaoFilialId")
                .prop("disabled")
        ) {
            $("#razaoFilialId")
                .val("");
        }

        $("#razaoDataInicio")
            .val("");

        $("#razaoDataFim")
            .val("");

        definirPeriodoPadraoRazao();

        limparResultadoRazao();

    }


    function limparResultadoRazao() {

        $("#cabecalhoContaRazao")
            .hide();

        $("#tabelaRazao tbody")
            .html(`
                <tr>

                    <td
                        colspan="10"
                        class="text-center text-muted py-4">

                        Selecione uma conta
                        para gerar o Razão.

                    </td>

                </tr>
            `);

        preencherTotaisRazao({});

    }


    // ==================================================
    // CARREGAMENTO
    // ==================================================

    function mostrarCarregamentoRazao(
        mostrar
    ) {

        $("#razaoCarregando")
            .toggle(
                Boolean(mostrar)
            );

    }


    function bloquearFiltrosRazao(
        bloquear
    ) {

        $("#formFiltroRazao")
            .find(
                "input, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnAtualizarRazao")
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        if (!bloquear) {

            const filial =
                $("#razaoFilialId");

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

    function mostrarErroRazao(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível carregar",

            text:
                mensagem
                || "Ocorreu um erro ao gerar o Razão Geral.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // FORMATAÇÃO
    // ==================================================

    function formatarMoedaRazao(
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


    function formatarSaldoRazao(
        saldoDevedor,
        saldoCredor
    ) {

        const devedor =
            Number(
                saldoDevedor || 0
            );

        const credor =
            Number(
                saldoCredor || 0
            );

        if (devedor > 0) {
            return (
                formatarMoedaRazao(
                    devedor
                )
                + " D"
            );
        }

        if (credor > 0) {
            return (
                formatarMoedaRazao(
                    credor
                )
                + " C"
            );
        }

        return formatarMoedaRazao(0);
    }


    function formatarDataRazao(
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
            return escaparHtmlRazao(
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


    function definirTextoRazao(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function escaparHtmlRazao(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);