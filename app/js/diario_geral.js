"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const DIARIO_GERAL_URL =
        "/erpplus/app/ajax/diario_geral_ajax.php";


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        /*
         * Evita executar este ficheiro
         * fora da página do Diário Geral.
         */
        if (!paginaDiarioGeralExiste()) {
            return;
        }

        configurarEventosDiarioGeral();

        definirPeriodoPadraoDiarioGeral();

        carregarDadosIniciaisDiarioGeral();

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaDiarioGeralExiste() {

        return (
            $("#formFiltroDiarioGeral").length > 0
            && $("#diarioGeralDataInicio").length > 0
            && $("#diarioGeralDataFim").length > 0
            && $("#listaLancamentosDiarioGeral").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosDiarioGeral() {

        $("#formFiltroDiarioGeral").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                gerarDiarioGeral();

            }
        );

        $("#btnAtualizarDiarioGeral").on(
            "click",
            function () {

                gerarDiarioGeral();

            }
        );

        $("#btnLimparDiarioGeral").on(
            "click",
            function () {

                limparFiltrosDiarioGeral();

            }
        );

    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisDiarioGeral() {

        mostrarCarregamentoDiarioGeral(true);

        bloquearFiltrosDiarioGeral(true);

        try {

            const resposta =
                await requisicaoDiarioGeral(
                    DIARIO_GERAL_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            preencherDiariosDiarioGeral(
                dados.diarios || []
            );

            preencherFiliaisDiarioGeral(
                dados.filiais || [],
                dados.filial_sessao_id
                ?? null
            );

            await gerarDiarioGeral();

        } catch (erro) {

            console.error(
                "Erro ao carregar dados do Diário Geral:",
                erro
            );

            mostrarErroDiarioGeral(
                erro.message
            );

        } finally {

            mostrarCarregamentoDiarioGeral(false);

            bloquearFiltrosDiarioGeral(false);

        }

    }


    // ==================================================
    // GERAR DIÁRIO GERAL
    // ==================================================

    async function gerarDiarioGeral() {

        if (!paginaDiarioGeralExiste()) {
            return;
        }

        const dataInicio =
            $("#diarioGeralDataInicio")
                .val();

        const dataFim =
            $("#diarioGeralDataFim")
                .val();

        const filialId =
            $("#diarioGeralFilialId")
                .val();

        const diarioId =
            $("#diarioGeralDiarioId")
                .val();

        if (!dataInicio || !dataFim) {

            mostrarErroDiarioGeral(
                "Informe a data inicial e a data final."
            );

            return;
        }

        if (dataInicio > dataFim) {

            mostrarErroDiarioGeral(
                "A data inicial não pode ser posterior à data final."
            );

            return;
        }

        mostrarCarregamentoDiarioGeral(true);

        bloquearFiltrosDiarioGeral(true);

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

                    diario_id:
                        diarioId || ""
                });

            const resposta =
                await requisicaoDiarioGeral(
                    DIARIO_GERAL_URL
                    + "?"
                    + parametros.toString()
                );

            const dados =
                resposta.data || {};

            preencherLancamentosDiarioGeral(
                dados.lancamentos || []
            );

            preencherResumoDiarioGeral(
                dados
            );

            atualizarEstadoDiarioGeral(
                dados
            );

        } catch (erro) {

            console.error(
                "Erro ao gerar Diário Geral:",
                erro
            );

            limparResultadoDiarioGeral();

            mostrarErroDiarioGeral(
                erro.message
            );

        } finally {

            mostrarCarregamentoDiarioGeral(false);

            bloquearFiltrosDiarioGeral(false);

        }

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoDiarioGeral(
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
                || "Não foi possível gerar o Diário Geral."
            );
        }

        return json;
    }


    // ==================================================
    // DIÁRIOS
    // ==================================================

    function preencherDiariosDiarioGeral(
        diarios
    ) {

        const select =
            $("#diarioGeralDiarioId");

        select.empty();

        select.append(`
            <option value="">
                Todos os diários
            </option>
        `);

        diarios.forEach(
            function (diario) {

                const codigo =
                    diario.codigo
                        ? diario.codigo
                        + " - "
                        : "";

                select.append(`
                    <option value="${Number(diario.id)}">

                        ${escaparHtmlDiarioGeral(
                    codigo
                    + (
                        diario.nome
                        || "Diário"
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

    function preencherFiliaisDiarioGeral(
        filiais,
        filialSessaoId
    ) {

        const select =
            $("#diarioGeralFilialId");

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

                        ${escaparHtmlDiarioGeral(
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
    // LANÇAMENTOS
    // ==================================================

    function preencherLancamentosDiarioGeral(
        lancamentos
    ) {

        const container =
            $("#listaLancamentosDiarioGeral");

        container.empty();

        if (!lancamentos.length) {

            container.html(`
                <div class="text-center text-muted py-5">

                    Nenhum lançamento contabilístico encontrado
                    para o período selecionado.

                </div>
            `);

            return;
        }

        lancamentos.forEach(
            function (lancamento) {

                const diario =
                    lancamento.diario || {};

                const filial =
                    lancamento.filial || null;

                const documento =
                    lancamento.numero_documento
                    || lancamento.referencia
                    || lancamento.numero
                    || "-";

                const filialTexto =
                    filial
                        ? (
                            (
                                filial.codigo
                                    ? filial.codigo + " - "
                                    : ""
                            )
                            + (
                                filial.nome
                                || "Filial"
                            )
                        )
                        : "Geral";

                const itensHtml =
                    montarItensLancamentoDiarioGeral(
                        lancamento.itens || []
                    );

                container.append(`
                    <div class="card mb-3">

                        <div class="card-header">

                            <div class="row align-items-center">

                                <div class="col-md-8">

                                    <strong>
                                        ${escaparHtmlDiarioGeral(
                    diario.codigo || "-"
                )}
                                        /
                                        ${escaparHtmlDiarioGeral(
                    String(
                        lancamento.numero || "-"
                    )
                )}
                                    </strong>

                                    <span class="ml-2 text-muted">

                                        ${formatarDataDiarioGeral(
                    lancamento.data_lancamento
                )}

                                    </span>

                                </div>

                                <div class="col-md-4 text-md-right">

                                    <span class="badge badge-light">

                                        ${escaparHtmlDiarioGeral(
                    lancamento.origem || "MANUAL"
                )}

                                    </span>

                                </div>

                            </div>

                        </div>

                        <div class="card-body">

                            <div class="row mb-3">

                                <div class="col-md-3">

                                    <small class="text-muted">
                                        Documento
                                    </small>

                                    <div>
                                        ${escaparHtmlDiarioGeral(
                    documento
                )}
                                    </div>

                                </div>

                                <div class="col-md-3">

                                    <small class="text-muted">
                                        Diário
                                    </small>

                                    <div>
                                        ${escaparHtmlDiarioGeral(
                    diario.nome || "-"
                )}
                                    </div>

                                </div>

                                <div class="col-md-3">

                                    <small class="text-muted">
                                        Filial
                                    </small>

                                    <div>
                                        ${escaparHtmlDiarioGeral(
                    filialTexto
                )}
                                    </div>

                                </div>

                                <div class="col-md-3">

                                    <small class="text-muted">
                                        Contabilizado por
                                    </small>

                                    <div>
                                        ${escaparHtmlDiarioGeral(
                    lancamento
                        .contabilizado_por_nome
                    || "-"
                )}
                                    </div>

                                </div>

                            </div>

                            <div class="mb-3">

                                <small class="text-muted">
                                    Descrição
                                </small>

                                <div>
                                    ${escaparHtmlDiarioGeral(
                    lancamento.descricao
                    || "-"
                )}
                                </div>

                            </div>

                            <div class="table-responsive">

                                <table
                                    class="table table-sm table-bordered mb-0">

                                    <thead>

                                        <tr>

                                            <th>
                                                Conta
                                            </th>

                                            <th>
                                                Descrição
                                            </th>

                                            <th>
                                                Entidade
                                            </th>

                                            <th>
                                                Centro de custo
                                            </th>

                                            <th class="text-right">
                                                Débito
                                            </th>

                                            <th class="text-right">
                                                Crédito
                                            </th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        ${itensHtml}

                                    </tbody>

                                    <tfoot>

                                        <tr class="font-weight-bold">

                                            <td colspan="4">
                                                Total do lançamento
                                            </td>

                                            <td class="text-right">

                                                ${formatarMoedaDiarioGeral(
                    lancamento.total_debito
                )}

                                            </td>

                                            <td class="text-right">

                                                ${formatarMoedaDiarioGeral(
                    lancamento.total_credito
                )}

                                            </td>

                                        </tr>

                                    </tfoot>

                                </table>

                            </div>

                        </div>

                    </div>
                `);

            }
        );

    }


    function montarItensLancamentoDiarioGeral(
        itens
    ) {

        if (!itens.length) {

            return `
                <tr>

                    <td
                        colspan="6"
                        class="text-center text-muted">

                        Sem itens contabilísticos.

                    </td>

                </tr>
            `;

        }

        return itens.map(
            function (item) {

                const entidade =
                    item.cliente_nome
                    || item.fornecedor_nome
                    || "-";

                const centroCusto =
                    item.centro_custo_codigo
                        ? (
                            item.centro_custo_codigo
                            + " - "
                            + (
                                item.centro_custo_nome
                                || ""
                            )
                        )
                        : (
                            item.centro_custo_nome
                            || "-"
                        );

                const conta =
                    (
                        item.conta_codigo
                            ? item.conta_codigo + " - "
                            : ""
                    )
                    + (
                        item.conta_nome
                        || "Conta"
                    );

                return `
                    <tr>

                        <td>
                            ${escaparHtmlDiarioGeral(
                    conta
                )}
                        </td>

                        <td>
                            ${escaparHtmlDiarioGeral(
                    item.descricao || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlDiarioGeral(
                    entidade
                )}
                        </td>

                        <td>
                            ${escaparHtmlDiarioGeral(
                    centroCusto
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaDiarioGeral(
                    item.debito
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaDiarioGeral(
                    item.credito
                )}
                        </td>

                    </tr>
                `;

            }
        ).join("");

    }


    // ==================================================
    // RESUMO
    // ==================================================

    function preencherResumoDiarioGeral(
        dados
    ) {

        definirTextoDiarioGeral(
            "#diarioGeralQuantidadeLancamentos",
            Number(
                dados.quantidade_lancamentos
                || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

        definirTextoDiarioGeral(
            "#diarioGeralTotalDebitos",
            formatarMoedaDiarioGeral(
                dados.total_debitos
            )
        );

        definirTextoDiarioGeral(
            "#diarioGeralTotalCreditos",
            formatarMoedaDiarioGeral(
                dados.total_creditos
            )
        );

        definirTextoDiarioGeral(
            "#diarioGeralDiferenca",
            formatarMoedaDiarioGeral(
                Math.abs(
                    Number(
                        dados.diferenca
                        || 0
                    )
                )
            )
        );

    }


    // ==================================================
    // ESTADO
    // ==================================================

    function atualizarEstadoDiarioGeral(
        dados
    ) {

        const elemento =
            $("#estadoDiarioGeral");

        elemento.removeClass(
            "alert-success alert-danger"
        );

        if (Boolean(dados.equilibrado)) {

            elemento
                .addClass(
                    "alert-success"
                )
                .html(`
                    <strong>Diário equilibrado.</strong>
                    Os débitos e créditos do período são iguais.
                `);

        } else {

            elemento
                .addClass(
                    "alert-danger"
                )
                .html(`
                    <strong>Diário desequilibrado.</strong>
                    Diferença de
                    ${formatarMoedaDiarioGeral(
                    Math.abs(
                        Number(
                            dados.diferenca
                            || 0
                        )
                    )
                )}.
                `);

        }

        elemento.show();

    }


    // ==================================================
    // PERÍODO PADRÃO
    // ==================================================

    function definirPeriodoPadraoDiarioGeral() {

        const hoje =
            new Date();

        const primeiroDiaMes =
            new Date(
                hoje.getFullYear(),
                hoje.getMonth(),
                1
            );

        if (
            !$("#diarioGeralDataInicio")
                .val()
        ) {
            $("#diarioGeralDataInicio")
                .val(
                    formatarDataInputDiarioGeral(
                        primeiroDiaMes
                    )
                );
        }

        if (
            !$("#diarioGeralDataFim")
                .val()
        ) {
            $("#diarioGeralDataFim")
                .val(
                    formatarDataInputDiarioGeral(
                        hoje
                    )
                );
        }

    }


    function formatarDataInputDiarioGeral(
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

    function limparFiltrosDiarioGeral() {

        $("#diarioGeralDiarioId")
            .val("");

        if (
            !$("#diarioGeralFilialId")
                .prop("disabled")
        ) {
            $("#diarioGeralFilialId")
                .val("");
        }

        $("#diarioGeralDataInicio")
            .val("");

        $("#diarioGeralDataFim")
            .val("");

        definirPeriodoPadraoDiarioGeral();

        gerarDiarioGeral();

    }


    function limparResultadoDiarioGeral() {

        $("#listaLancamentosDiarioGeral")
            .html(`
                <div class="text-center text-muted py-5">

                    Não foi possível carregar o Diário Geral.

                </div>
            `);

        preencherResumoDiarioGeral({});

        $("#estadoDiarioGeral")
            .hide();

    }


    // ==================================================
    // CARREGAMENTO
    // ==================================================

    function mostrarCarregamentoDiarioGeral(
        mostrar
    ) {

        $("#diarioGeralCarregando")
            .toggle(
                Boolean(mostrar)
            );

    }


    function bloquearFiltrosDiarioGeral(
        bloquear
    ) {

        $("#formFiltroDiarioGeral")
            .find(
                "input, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnAtualizarDiarioGeral")
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        if (!bloquear) {

            const filial =
                $("#diarioGeralFilialId");

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

    function mostrarErroDiarioGeral(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível carregar",

            text:
                mensagem
                || "Ocorreu um erro ao gerar o Diário Geral.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // FORMATAÇÃO
    // ==================================================

    function formatarMoedaDiarioGeral(
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


    function formatarDataDiarioGeral(
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
            return escaparHtmlDiarioGeral(
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


    function definirTextoDiarioGeral(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function escaparHtmlDiarioGeral(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);