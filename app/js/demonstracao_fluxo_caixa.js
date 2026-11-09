"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const DFC_URL =
        "/erpplus/app/ajax/demonstracao_fluxo_caixa_ajax.php";

    let graficoDFC = null;

    let dadosDFCAtual = null;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaDFCExiste()) {
            return;
        }

        configurarEventosDFC();

        carregarDadosIniciaisDFC()
            .catch(function (erro) {

                console.error(
                    "Erro ao iniciar a Demonstração de Fluxos de Caixa:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaDFCExiste() {

        return (
            $("#formDemonstracaoFluxoCaixa").length > 0
            && $("#dfcPlanoContasId").length > 0
            && $("#dfcDataInicial").length > 0
            && $("#dfcDataFinal").length > 0
            && $("#btnGerarDFC").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosDFC() {

        $("#formDemonstracaoFluxoCaixa").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                gerarDFC()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao gerar a DFC:",
                            erro
                        );

                    });

            }
        );

        $("#dfcExercicioId").on(
            "change",
            function () {

                aplicarDatasDoExercicioDFC();

                filtrarPeriodosPorExercicioDFC();

            }
        );

        $("#dfcPeriodoId").on(
            "change",
            function () {

                aplicarDatasDoPeriodoDFC();

            }
        );

        $("#btnLimparDFC").on(
            "click",
            function () {

                limparFiltrosDFC();

            }
        );

        $("#btnImprimirDFC").on(
            "click",
            function () {

                imprimirDFC();

            }
        );

        $("#btnExportarExcelDFC").on(
            "click",
            function () {

                exportarExcelDFC();

            }
        );

        $("#btnExportarPdfDFC").on(
            "click",
            function () {

                exportarPdfDFC();

            }
        );

    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisDFC() {

        mostrarCarregamentoDFC(true);

        bloquearFormularioDFC(true);

        try {

            const resposta =
                await requisicaoDFC(
                    DFC_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            preencherPlanosContasDFC(
                dados.planos_contas || []
            );

            preencherFiliaisDFC(
                dados.filiais || []
            );

            preencherExerciciosDFC(
                dados.exercicios || []
            );

            preencherPeriodosDFC(
                dados.periodos || []
            );

            preencherResumoClassificacaoDFC(
                dados.classificacao || {}
            );

            selecionarExercicioPadraoDFC(
                dados.exercicios || []
            );

            filtrarPeriodosPorExercicioDFC();

            aplicarDatasDoExercicioDFC();

        } catch (erro) {

            mostrarErroDFC(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoDFC(false);

            bloquearFormularioDFC(false);

        }

    }


    // ==================================================
    // PREENCHER PLANOS
    // ==================================================

    function preencherPlanosContasDFC(
        planos
    ) {

        const select =
            $("#dfcPlanoContasId");

        select.empty();

        select.append(`
            <option value="">
                Selecione o plano de contas
            </option>
        `);

        planos.forEach(
            function (plano) {

                const texto =
                    [
                        plano.codigo || "",
                        plano.nome || ""
                    ]
                        .filter(Boolean)
                        .join(" - ");

                select.append(`
                    <option value="${Number(plano.id)}">
                        ${escaparHtmlDFC(texto)}
                    </option>
                `);

            }
        );

        if (planos.length === 1) {
            select.val(
                String(planos[0].id)
            );
        }

    }


    // ==================================================
    // PREENCHER FILIAIS
    // ==================================================

    function preencherFiliaisDFC(
        filiais
    ) {

        const select =
            $("#dfcFilialId");

        select.empty();

        select.append(`
            <option value="">
                Todas as filiais
            </option>
        `);

        filiais.forEach(
            function (filial) {

                const texto =
                    [
                        filial.codigo || "",
                        filial.nome || ""
                    ]
                        .filter(Boolean)
                        .join(" - ");

                select.append(`
                    <option value="${Number(filial.id)}">
                        ${escaparHtmlDFC(texto)}
                    </option>
                `);

            }
        );

    }


    // ==================================================
    // PREENCHER EXERCÍCIOS
    // ==================================================

    function preencherExerciciosDFC(
        exercicios
    ) {

        const select =
            $("#dfcExercicioId");

        select.empty();

        select.append(`
            <option value="">
                Todos os exercícios
            </option>
        `);

        exercicios.forEach(
            function (exercicio) {

                select.append(`
                    <option
                        value="${Number(exercicio.id)}"
                        data-inicio="${escaparHtmlDFC(
                    exercicio.data_inicio || ""
                )}"
                        data-fim="${escaparHtmlDFC(
                    exercicio.data_fim || ""
                )}">

                        ${escaparHtmlDFC(
                    [
                        exercicio.codigo || "",
                        exercicio.descricao || "",
                        exercicio.status_nome || ""
                    ]
                        .filter(Boolean)
                        .join(" - ")
                )}

                    </option>
                `);

            }
        );

    }


    // ==================================================
    // PREENCHER PERÍODOS
    // ==================================================

    function preencherPeriodosDFC(
        periodos
    ) {

        const select =
            $("#dfcPeriodoId");

        select.empty();

        select.append(`
            <option value="">
                Todos os períodos
            </option>
        `);

        periodos.forEach(
            function (periodo) {

                select.append(`
                    <option
                        value="${Number(periodo.id)}"
                        data-exercicio-id="${Number(
                    periodo.exercicio_id
                )}"
                        data-inicio="${escaparHtmlDFC(
                    periodo.data_inicio || ""
                )}"
                        data-fim="${escaparHtmlDFC(
                    periodo.data_fim || ""
                )}">

                        ${escaparHtmlDFC(
                    [
                        periodo.numero
                            ? String(periodo.numero).padStart(2, "0")
                            : "",
                        periodo.nome || "",
                        periodo.status_nome || ""
                    ]
                        .filter(Boolean)
                        .join(" - ")
                )}

                    </option>
                `);

            }
        );

    }


    // ==================================================
    // EXERCÍCIO PADRÃO
    // ==================================================

    function selecionarExercicioPadraoDFC(
        exercicios
    ) {

        const hoje =
            new Date();

        const dataHoje =
            [
                hoje.getFullYear(),
                String(
                    hoje.getMonth() + 1
                ).padStart(2, "0"),
                String(
                    hoje.getDate()
                ).padStart(2, "0")
            ].join("-");

        const atual =
            exercicios.find(
                function (exercicio) {

                    return (
                        dataHoje >=
                        String(
                            exercicio.data_inicio
                            || ""
                        )

                        && dataHoje <=
                        String(
                            exercicio.data_fim
                            || ""
                        )
                    );

                }
            );

        if (atual) {

            $("#dfcExercicioId")
                .val(
                    String(atual.id)
                );

            return;

        }

        if (exercicios.length > 0) {

            $("#dfcExercicioId")
                .val(
                    String(exercicios[0].id)
                );

        }

    }


    // ==================================================
    // FILTRAR PERÍODOS
    // ==================================================

    function filtrarPeriodosPorExercicioDFC() {

        const exercicioId =
            Number(
                $("#dfcExercicioId")
                    .val()
                || 0
            );

        const select =
            $("#dfcPeriodoId");

        select.val("");

        select
            .find("option")
            .each(function () {

                const option =
                    $(this);

                const valor =
                    Number(
                        option.val() || 0
                    );

                if (valor === 0) {

                    option.show();

                    return;

                }

                const optionExercicioId =
                    Number(
                        option.data(
                            "exercicio-id"
                        )
                        || 0
                    );

                option.toggle(
                    exercicioId <= 0
                    || optionExercicioId ===
                    exercicioId
                );

            });

    }


    // ==================================================
    // DATAS DO EXERCÍCIO
    // ==================================================

    function aplicarDatasDoExercicioDFC() {

        const option =
            $("#dfcExercicioId")
                .find(
                    "option:selected"
                );

        const inicio =
            String(
                option.data("inicio")
                || ""
            );

        const fim =
            String(
                option.data("fim")
                || ""
            );

        if (
            inicio !== ""
            && fim !== ""
        ) {

            $("#dfcDataInicial")
                .val(inicio);

            $("#dfcDataFinal")
                .val(fim);

        }

    }


    // ==================================================
    // DATAS DO PERÍODO
    // ==================================================

    function aplicarDatasDoPeriodoDFC() {

        const option =
            $("#dfcPeriodoId")
                .find(
                    "option:selected"
                );

        const inicio =
            String(
                option.data("inicio")
                || ""
            );

        const fim =
            String(
                option.data("fim")
                || ""
            );

        if (
            inicio !== ""
            && fim !== ""
        ) {

            $("#dfcDataInicial")
                .val(inicio);

            $("#dfcDataFinal")
                .val(fim);

        }

    }


    // ==================================================
    // CLASSIFICAÇÃO DAS CONTAS
    // ==================================================

    function preencherResumoClassificacaoDFC(
        classificacao
    ) {

        definirTextoDFC(
            "#dfcClassificacaoOperacional",
            Number(
                classificacao.OPERACIONAL
                || 0
            )
        );

        definirTextoDFC(
            "#dfcClassificacaoInvestimento",
            Number(
                classificacao.INVESTIMENTO
                || 0
            )
        );

        definirTextoDFC(
            "#dfcClassificacaoFinanciamento",
            Number(
                classificacao.FINANCIAMENTO
                || 0
            )
        );

        definirTextoDFC(
            "#dfcClassificacaoNenhum",
            Number(
                classificacao.NENHUM
                || 0
            )
        );

    }


    // ==================================================
    // GERAR DFC
    // ==================================================

    async function gerarDFC() {

        const filtros =
            obterFiltrosDFC();

        validarFiltrosDFC(
            filtros
        );

        mostrarCarregamentoDFC(true);

        bloquearFormularioDFC(true);

        try {

            const formData =
                new FormData();

            formData.append(
                "acao",
                "gerar"
            );

            formData.append(
                "plano_contas_id",
                String(
                    filtros.plano_contas_id
                )
            );

            formData.append(
                "data_inicial",
                filtros.data_inicial
            );

            formData.append(
                "data_final",
                filtros.data_final
            );

            if (
                filtros.filial_id !== null
            ) {
                formData.append(
                    "filial_id",
                    String(
                        filtros.filial_id
                    )
                );
            }

            if (
                filtros.exercicio_id !== null
            ) {
                formData.append(
                    "exercicio_id",
                    String(
                        filtros.exercicio_id
                    )
                );
            }

            const resposta =
                await requisicaoDFC(
                    DFC_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            dadosDFCAtual =
                resposta.data || {};

            preencherResultadoDFC(
                dadosDFCAtual
            );

        } catch (erro) {

            limparResultadoDFC();

            mostrarErroDFC(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoDFC(false);

            bloquearFormularioDFC(false);

        }

    }


    // ==================================================
    // OBTER FILTROS
    // ==================================================

    function obterFiltrosDFC() {

        return {
            plano_contas_id:
                Number(
                    $("#dfcPlanoContasId")
                        .val()
                    || 0
                ),

            filial_id:
                valorInteiroOpcionalDFC(
                    $("#dfcFilialId")
                        .val()
                ),

            exercicio_id:
                valorInteiroOpcionalDFC(
                    $("#dfcExercicioId")
                        .val()
                ),

            data_inicial:
                String(
                    $("#dfcDataInicial")
                        .val()
                    || ""
                ).trim(),

            data_final:
                String(
                    $("#dfcDataFinal")
                        .val()
                    || ""
                ).trim()
        };

    }


    // ==================================================
    // VALIDAR FILTROS
    // ==================================================

    function validarFiltrosDFC(
        filtros
    ) {

        if (
            filtros.plano_contas_id <= 0
        ) {
            throw new Error(
                "Selecione o plano de contas."
            );
        }

        if (
            filtros.data_inicial === ""
            || filtros.data_final === ""
        ) {
            throw new Error(
                "Informe a data inicial e a data final."
            );
        }

        if (
            filtros.data_inicial >
            filtros.data_final
        ) {
            throw new Error(
                "A data inicial não pode ser posterior à data final."
            );
        }

    }


    // ==================================================
    // PREENCHER RESULTADO
    // ==================================================

    function preencherResultadoDFC(
        dados
    ) {

        const resumo =
            dados.resumo || {};

        const atividades =
            dados.atividades || {};

        const estatisticas =
            dados.estatisticas || {};

        preencherResumoDFC(
            resumo
        );

        preencherTabelaAtividadeDFC(
            "#tabelaDFCOperacional",
            atividades.operacional || {}
        );

        preencherTabelaAtividadeDFC(
            "#tabelaDFCInvestimento",
            atividades.investimento || {}
        );

        preencherTabelaAtividadeDFC(
            "#tabelaDFCFinanciamento",
            atividades.financiamento || {}
        );

        preencherMovimentosNaoClassificadosDFC(
            dados.movimentos_nao_classificados
            || []
        );

        preencherTransferenciasInternasDFC(
            dados.transferencias_internas
            || []
        );

        preencherEstatisticasDFC(
            estatisticas
        );

        preencherConferenciaDFC(
            resumo
        );

        criarGraficoDFC(
            resumo
        );

        preencherPeriodoRelatorioDFC(
            dados.filtros || {}
        );

        $("#painelResultadoDFC")
            .show();

        $("#btnImprimirDFC")
            .prop(
                "disabled",
                false
            );

        $("#btnExportarExcelDFC")
            .prop(
                "disabled",
                false
            );

        $("#btnExportarPdfDFC")
            .prop(
                "disabled",
                false
            );

    }


    // ==================================================
    // RESUMO
    // ==================================================

    function preencherResumoDFC(
        resumo
    ) {

        definirTextoDFC(
            "#dfcSaldoInicial",
            formatarMoedaDFC(
                resumo.saldo_inicial
            )
        );

        definirTextoDFC(
            "#dfcFluxoOperacional",
            formatarMoedaDFC(
                resumo.fluxo_operacional
            )
        );

        definirTextoDFC(
            "#dfcFluxoInvestimento",
            formatarMoedaDFC(
                resumo.fluxo_investimento
            )
        );

        definirTextoDFC(
            "#dfcFluxoFinanciamento",
            formatarMoedaDFC(
                resumo.fluxo_financiamento
            )
        );

        definirTextoDFC(
            "#dfcVariacaoLiquida",
            formatarMoedaDFC(
                resumo.variacao_liquida
            )
        );

        definirTextoDFC(
            "#dfcSaldoFinalCalculado",
            formatarMoedaDFC(
                resumo.saldo_final_calculado
            )
        );

        definirTextoDFC(
            "#dfcSaldoFinalContabilistico",
            formatarMoedaDFC(
                resumo.saldo_final_contabilistico
            )
        );

        definirTextoDFC(
            "#dfcDiferencaConferencia",
            formatarMoedaDFC(
                resumo.diferenca_conferencia
            )
        );

        aplicarClasseValorDFC(
            "#dfcFluxoOperacional",
            resumo.fluxo_operacional
        );

        aplicarClasseValorDFC(
            "#dfcFluxoInvestimento",
            resumo.fluxo_investimento
        );

        aplicarClasseValorDFC(
            "#dfcFluxoFinanciamento",
            resumo.fluxo_financiamento
        );

        aplicarClasseValorDFC(
            "#dfcVariacaoLiquida",
            resumo.variacao_liquida
        );

    }


    // ==================================================
    // TABELAS DAS ATIVIDADES
    // ==================================================

    function preencherTabelaAtividadeDFC(
        seletor,
        atividade
    ) {

        const tbody =
            $(seletor)
                .find("tbody");

        tbody.empty();

        const linhas =
            atividade.linhas || [];

        if (!linhas.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="4"
                        class="text-center text-muted py-3">

                        Nenhum fluxo encontrado nesta atividade.

                    </td>

                </tr>
            `);

        } else {

            linhas.forEach(
                function (linha) {

                    tbody.append(`
                        <tr>

                            <td>
                                ${escaparHtmlDFC(
                        linha.descricao
                        || "-"
                    )}
                            </td>

                            <td class="text-right">
                                ${formatarMoedaDFC(
                        linha.entradas
                    )}
                            </td>

                            <td class="text-right">
                                ${formatarMoedaDFC(
                        linha.saidas
                    )}
                            </td>

                            <td class="text-right font-weight-bold">
                                ${formatarMoedaDFC(
                        linha.fluxo_liquido
                    )}
                            </td>

                        </tr>
                    `);

                }
            );

        }

        tbody.append(`
            <tr class="font-weight-bold">

                <td>
                    Total
                </td>

                <td class="text-right">
                    ${formatarMoedaDFC(
            atividade.total_entradas
        )}
                </td>

                <td class="text-right">
                    ${formatarMoedaDFC(
            atividade.total_saidas
        )}
                </td>

                <td class="text-right">
                    ${formatarMoedaDFC(
            atividade.fluxo_liquido
        )}
                </td>

            </tr>
        `);

    }


    // ==================================================
    // NÃO CLASSIFICADOS
    // ==================================================

    function preencherMovimentosNaoClassificadosDFC(
        movimentos
    ) {

        const tbody =
            $("#tabelaDFCNaoClassificados tbody");

        tbody.empty();

        if (!movimentos.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="6"
                        class="text-center text-muted py-3">

                        Não existem movimentos monetários sem classificação.

                    </td>

                </tr>
            `);

            $("#painelDFCNaoClassificados")
                .hide();

            return;

        }

        movimentos.forEach(
            function (movimento) {

                const contas =
                    (
                        movimento
                            .contas_sem_classificacao
                        || []
                    )
                        .map(
                            function (conta) {

                                return [
                                    conta.codigo || "",
                                    conta.nome || ""
                                ]
                                    .filter(Boolean)
                                    .join(" - ");

                            }
                        )
                        .join(", ");

                tbody.append(`
                    <tr>

                        <td>
                            ${formatarDataDFC(
                    movimento.data
                )}
                        </td>

                        <td>
                            ${escaparHtmlDFC(
                    movimento.numero
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlDFC(
                    movimento.documento
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlDFC(
                    movimento.descricao
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlDFC(
                    contas || "-"
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaDFC(
                    movimento.valor
                )}
                        </td>

                    </tr>
                `);

            }
        );

        $("#painelDFCNaoClassificados")
            .show();

    }


    // ==================================================
    // TRANSFERÊNCIAS INTERNAS
    // ==================================================

    function preencherTransferenciasInternasDFC(
        transferencias
    ) {

        const tbody =
            $("#tabelaDFCTransferencias tbody");

        tbody.empty();

        if (!transferencias.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="5"
                        class="text-center text-muted py-3">

                        Não existem transferências internas no período.

                    </td>

                </tr>
            `);

            $("#painelDFCTransferencias")
                .hide();

            return;

        }

        transferencias.forEach(
            function (movimento) {

                tbody.append(`
                    <tr>

                        <td>
                            ${formatarDataDFC(
                    movimento.data
                )}
                        </td>

                        <td>
                            ${escaparHtmlDFC(
                    movimento.numero
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlDFC(
                    movimento.documento
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlDFC(
                    movimento.descricao
                    || "-"
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaDFC(
                    movimento.valor
                )}
                        </td>

                    </tr>
                `);

            }
        );

        $("#painelDFCTransferencias")
            .show();

    }


    // ==================================================
    // ESTATÍSTICAS
    // ==================================================

    function preencherEstatisticasDFC(
        estatisticas
    ) {

        definirTextoDFC(
            "#dfcQuantidadeLancamentos",
            Number(
                estatisticas
                    .quantidade_lancamentos
                || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

        definirTextoDFC(
            "#dfcQuantidadeClassificados",
            Number(
                estatisticas
                    .quantidade_movimentos_classificados
                || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

        definirTextoDFC(
            "#dfcQuantidadeNaoClassificados",
            Number(
                estatisticas
                    .quantidade_nao_classificados
                || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

        definirTextoDFC(
            "#dfcQuantidadeTransferencias",
            Number(
                estatisticas
                    .quantidade_transferencias_internas
                || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

    }


    // ==================================================
    // CONFERÊNCIA
    // ==================================================

    function preencherConferenciaDFC(
        resumo
    ) {

        const elemento =
            $("#estadoConferenciaDFC");

        elemento.removeClass(
            "alert-success alert-danger alert-warning"
        );

        if (Boolean(resumo.conferido)) {

            elemento
                .addClass(
                    "alert-success"
                )
                .html(`
                    <strong>Demonstração conferida.</strong>
                    O saldo final calculado coincide com o
                    saldo contabilístico de Caixa e Bancos.
                `);

        } else {

            elemento
                .addClass(
                    "alert-danger"
                )
                .html(`
                    <strong>Diferença encontrada.</strong>
                    Existe uma diferença de
                    ${formatarMoedaDFC(
                    resumo.diferenca_conferencia
                )}
                    entre o saldo calculado e o saldo contabilístico.
                    Verifique os movimentos sem classificação.
                `);

        }

        elemento.show();

    }


    // ==================================================
    // GRÁFICO
    // ==================================================

    function criarGraficoDFC(
        resumo
    ) {

        const canvas =
            document.getElementById(
                "graficoDemonstracaoFluxoCaixa"
            );

        if (
            !canvas
            || typeof Chart === "undefined"
        ) {
            return;
        }

        if (graficoDFC) {

            graficoDFC.destroy();

            graficoDFC = null;

        }

        graficoDFC =
            new Chart(
                canvas.getContext("2d"),
                {
                    type:
                        "bar",

                    data: {
                        labels: [
                            "Operacional",
                            "Investimento",
                            "Financiamento",
                            "Variação Líquida"
                        ],

                        datasets: [
                            {
                                label:
                                    "Fluxo líquido",

                                data: [
                                    Number(
                                        resumo.fluxo_operacional
                                        || 0
                                    ),

                                    Number(
                                        resumo.fluxo_investimento
                                        || 0
                                    ),

                                    Number(
                                        resumo.fluxo_financiamento
                                        || 0
                                    ),

                                    Number(
                                        resumo.variacao_liquida
                                        || 0
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
                                            contexto
                                        ) {
                                            return formatarMoedaDFC(
                                                contexto.raw
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
                                            return formatarNumeroDFC(
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
    // PERÍODO DO RELATÓRIO
    // ==================================================

    function preencherPeriodoRelatorioDFC(
        filtros
    ) {

        definirTextoDFC(
            "#dfcPeriodoRelatorio",
            formatarDataDFC(
                filtros.data_inicial
            )
            + " a "
            + formatarDataDFC(
                filtros.data_final
            )
        );

    }


    // ==================================================
    // LIMPAR FILTROS
    // ==================================================

    function limparFiltrosDFC() {

        $("#formDemonstracaoFluxoCaixa")[0]
            .reset();

        dadosDFCAtual =
            null;

        selecionarExercicioPadraoDFC(
            obterExerciciosDoSelectDFC()
        );

        filtrarPeriodosPorExercicioDFC();

        aplicarDatasDoExercicioDFC();

        limparResultadoDFC();

    }


    // ==================================================
    // LIMPAR RESULTADO
    // ==================================================

    function limparResultadoDFC() {

        $("#painelResultadoDFC")
            .hide();

        $("#estadoConferenciaDFC")
            .hide();

        $("#painelDFCNaoClassificados")
            .hide();

        $("#painelDFCTransferencias")
            .hide();

        $("#btnImprimirDFC")
            .prop(
                "disabled",
                true
            );

        $("#btnExportarExcelDFC")
            .prop(
                "disabled",
                true
            );

        $("#btnExportarPdfDFC")
            .prop(
                "disabled",
                true
            );

        if (graficoDFC) {

            graficoDFC.destroy();

            graficoDFC = null;

        }

    }


    // ==================================================
    // IMPRESSÃO
    // ==================================================

    function imprimirDFC() {

        if (!dadosDFCAtual) {

            mostrarErroDFC(
                "Gere a Demonstração de Fluxos de Caixa antes de imprimir."
            );

            return;

        }

        window.print();

    }


    // ==================================================
    // EXPORTAR EXCEL
    // ==================================================

    function exportarExcelDFC() {

        if (!dadosDFCAtual) {

            mostrarErroDFC(
                "Gere a Demonstração de Fluxos de Caixa antes de exportar."
            );

            return;

        }

        if (
            typeof XLSX === "undefined"
        ) {

            mostrarErroDFC(
                "A biblioteca de exportação Excel não está disponível."
            );

            return;

        }

        const linhas =
            montarLinhasExportacaoDFC(
                dadosDFCAtual
            );

        const worksheet =
            XLSX.utils.aoa_to_sheet(
                linhas
            );

        const workbook =
            XLSX.utils.book_new();

        XLSX.utils.book_append_sheet(
            workbook,
            worksheet,
            "DFC"
        );

        XLSX.writeFile(
            workbook,
            "demonstracao_fluxos_caixa.xlsx"
        );

    }


    // ==================================================
    // EXPORTAR PDF
    // ==================================================

    function exportarPdfDFC() {

        if (!dadosDFCAtual) {

            mostrarErroDFC(
                "Gere a Demonstração de Fluxos de Caixa antes de exportar."
            );

            return;

        }

        window.print();

    }


    // ==================================================
    // MONTAR EXPORTAÇÃO
    // ==================================================

    function montarLinhasExportacaoDFC(
        dados
    ) {

        const linhas = [
            [
                "Demonstração de Fluxos de Caixa"
            ],

            [
                "Período",
                dados.filtros.data_inicial
                + " a "
                + dados.filtros.data_final
            ],

            [],

            [
                "Descrição",
                "Entradas",
                "Saídas",
                "Fluxo Líquido"
            ]
        ];

        adicionarAtividadeExportacaoDFC(
            linhas,
            "Atividades Operacionais",
            dados.atividades.operacional
        );

        adicionarAtividadeExportacaoDFC(
            linhas,
            "Atividades de Investimento",
            dados.atividades.investimento
        );

        adicionarAtividadeExportacaoDFC(
            linhas,
            "Atividades de Financiamento",
            dados.atividades.financiamento
        );

        linhas.push(
            [],
            [
                "Saldo Inicial",
                dados.resumo.saldo_inicial
            ],
            [
                "Variação Líquida",
                dados.resumo.variacao_liquida
            ],
            [
                "Saldo Final Calculado",
                dados.resumo.saldo_final_calculado
            ],
            [
                "Saldo Final Contabilístico",
                dados.resumo.saldo_final_contabilistico
            ],
            [
                "Diferença",
                dados.resumo.diferenca_conferencia
            ]
        );

        return linhas;

    }


    function adicionarAtividadeExportacaoDFC(
        linhas,
        titulo,
        atividade
    ) {

        linhas.push(
            [],
            [titulo]
        );

        (
            atividade.linhas
            || []
        ).forEach(
            function (linha) {

                linhas.push([
                    linha.descricao,
                    linha.entradas,
                    linha.saidas,
                    linha.fluxo_liquido
                ]);

            }
        );

        linhas.push([
            "Total",
            atividade.total_entradas,
            atividade.total_saidas,
            atividade.fluxo_liquido
        ]);

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoDFC(
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
                || "Não foi possível gerar a Demonstração de Fluxos de Caixa."
            );
        }

        return json;

    }


    // ==================================================
    // BLOQUEIO
    // ==================================================

    function mostrarCarregamentoDFC(
        mostrar
    ) {

        $("#dfcCarregando")
            .toggle(
                Boolean(mostrar)
            );

    }


    function bloquearFormularioDFC(
        bloquear
    ) {

        $("#formDemonstracaoFluxoCaixa")
            .find(
                "input, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

    }


    // ==================================================
    // MENSAGENS
    // ==================================================

    function mostrarErroDFC(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível processar",

            text:
                mensagem
                || "Ocorreu um erro na Demonstração de Fluxos de Caixa.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // HELPERS
    // ==================================================

    function definirTextoDFC(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function aplicarClasseValorDFC(
        seletor,
        valor
    ) {

        const elemento =
            $(seletor);

        elemento.removeClass(
            "text-success text-danger text-muted"
        );

        const numero =
            Number(valor || 0);

        if (numero > 0) {

            elemento.addClass(
                "text-success"
            );

        } else if (numero < 0) {

            elemento.addClass(
                "text-danger"
            );

        } else {

            elemento.addClass(
                "text-muted"
            );

        }

    }


    function formatarMoedaDFC(
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


    function formatarNumeroDFC(
        valor
    ) {

        return Number(
            valor || 0
        ).toLocaleString(
            "pt-PT",
            {
                minimumFractionDigits:
                    0,

                maximumFractionDigits:
                    2
            }
        );

    }


    function formatarDataDFC(
        data
    ) {

        if (!data) {
            return "-";
        }

        const partes =
            String(data)
                .substring(0, 10)
                .split("-");

        if (partes.length !== 3) {
            return escaparHtmlDFC(
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


    function escaparHtmlDFC(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }


    function valorInteiroOpcionalDFC(
        valor
    ) {

        const numero =
            Number(valor || 0);

        return numero > 0
            ? numero
            : null;

    }


    function obterExerciciosDoSelectDFC() {

        const exercicios = [];

        $("#dfcExercicioId option")
            .each(function () {

                const option =
                    $(this);

                const id =
                    Number(
                        option.val() || 0
                    );

                if (id <= 0) {
                    return;
                }

                exercicios.push({
                    id:
                        id,

                    data_inicio:
                        String(
                            option.data("inicio")
                            || ""
                        ),

                    data_fim:
                        String(
                            option.data("fim")
                            || ""
                        )
                });

            });

        return exercicios;

    }

})(jQuery);