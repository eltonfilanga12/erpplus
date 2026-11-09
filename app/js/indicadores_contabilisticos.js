"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const INDICADORES_URL =
        "/erpplus/app/ajax/indicadores_contabilisticos_ajax.php";

    let dadosIndicadoresAtuais =
        null;

    let graficoEstruturaFinanceira =
        null;

    let graficoResultados =
        null;

    let graficoComparativo =
        null;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaIndicadoresExiste()) {
            return;
        }

        configurarEventosIndicadores();

        carregarDadosIniciaisIndicadores()
            .catch(function (erro) {

                console.error(
                    "Erro ao iniciar os Indicadores Contabilísticos:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaIndicadoresExiste() {

        return (
            $("#formIndicadoresContabilisticos").length > 0
            && $("#indicadoresDataInicial").length > 0
            && $("#indicadoresDataFinal").length > 0
            && $("#btnGerarIndicadores").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosIndicadores() {

        $("#formIndicadoresContabilisticos").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                gerarIndicadores()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao gerar Indicadores Contabilísticos:",
                            erro
                        );

                    });

            }
        );

        $("#indicadoresExercicioId").on(
            "change",
            function () {

                aplicarDatasExercicioIndicadores();

            }
        );

        $("#btnLimparIndicadores").on(
            "click",
            function () {

                limparFiltrosIndicadores();

            }
        );

        $("#btnImprimirIndicadores").on(
            "click",
            function () {

                imprimirIndicadores();

            }
        );

        $("#btnExportarExcelIndicadores").on(
            "click",
            function () {

                exportarExcelIndicadores();

            }
        );

        $("#btnExportarPdfIndicadores").on(
            "click",
            function () {

                exportarPdfIndicadores();

            }
        );

    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisIndicadores() {

        mostrarCarregamentoIndicadores(true);

        bloquearFormularioIndicadores(true);

        try {

            const resposta =
                await requisicaoIndicadores(
                    INDICADORES_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            preencherPlanosIndicadores(
                dados.planos_contas || []
            );

            preencherFiliaisIndicadores(
                dados.filiais || []
            );

            preencherExerciciosIndicadores(
                dados.exercicios || []
            );

            selecionarExercicioPadraoIndicadores(
                dados.exercicios || []
            );

            aplicarDatasExercicioIndicadores();

        } catch (erro) {

            mostrarErroIndicadores(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoIndicadores(false);

            bloquearFormularioIndicadores(false);

        }

    }


    // ==================================================
    // PREENCHER PLANOS
    // ==================================================

    function preencherPlanosIndicadores(
        planos
    ) {

        const select =
            $("#indicadoresPlanoContasId");

        select.empty();

        select.append(`
            <option value="">
                Todos os planos de contas
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
                        ${escaparHtmlIndicadores(texto)}
                    </option>
                `);

            }
        );

        const planoPadrao =
            planos.find(
                function (plano) {

                    return Number(
                        plano.padrao || 0
                    ) === 1;

                }
            );

        if (planoPadrao) {

            select.val(
                String(planoPadrao.id)
            );

        } else if (
            planos.length === 1
        ) {

            select.val(
                String(planos[0].id)
            );

        }

    }


    // ==================================================
    // PREENCHER FILIAIS
    // ==================================================

    function preencherFiliaisIndicadores(
        filiais
    ) {

        const select =
            $("#indicadoresFilialId");

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
                        ${escaparHtmlIndicadores(texto)}
                    </option>
                `);

            }
        );

    }


    // ==================================================
    // PREENCHER EXERCÍCIOS
    // ==================================================

    function preencherExerciciosIndicadores(
        exercicios
    ) {

        const select =
            $("#indicadoresExercicioId");

        select.empty();

        select.append(`
            <option value="">
                Selecione o exercício
            </option>
        `);

        exercicios.forEach(
            function (exercicio) {

                const texto =
                    [
                        exercicio.codigo || "",
                        exercicio.descricao || "",
                        exercicio.status_nome || ""
                    ]
                        .filter(Boolean)
                        .join(" - ");

                select.append(`
                    <option
                        value="${Number(exercicio.id)}"
                        data-inicio="${escaparHtmlIndicadores(
                    exercicio.data_inicio || ""
                )}"
                        data-fim="${escaparHtmlIndicadores(
                    exercicio.data_fim || ""
                )}">

                        ${escaparHtmlIndicadores(texto)}

                    </option>
                `);

            }
        );

    }


    // ==================================================
    // EXERCÍCIO PADRÃO
    // ==================================================

    function selecionarExercicioPadraoIndicadores(
        exercicios
    ) {

        const hoje =
            new Date();

        const dataAtual =
            [
                hoje.getFullYear(),
                String(
                    hoje.getMonth() + 1
                ).padStart(2, "0"),
                String(
                    hoje.getDate()
                ).padStart(2, "0")
            ].join("-");

        const exercicioAtual =
            exercicios.find(
                function (exercicio) {

                    return (
                        dataAtual >=
                        String(
                            exercicio.data_inicio
                            || ""
                        )

                        && dataAtual <=
                        String(
                            exercicio.data_fim
                            || ""
                        )
                    );

                }
            );

        if (exercicioAtual) {

            $("#indicadoresExercicioId")
                .val(
                    String(
                        exercicioAtual.id
                    )
                );

            return;

        }

        if (exercicios.length > 0) {

            $("#indicadoresExercicioId")
                .val(
                    String(
                        exercicios[0].id
                    )
                );

        }

    }


    // ==================================================
    // DATAS DO EXERCÍCIO
    // ==================================================

    function aplicarDatasExercicioIndicadores() {

        const option =
            $("#indicadoresExercicioId")
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

            $("#indicadoresDataInicial")
                .val(inicio);

            $("#indicadoresDataFinal")
                .val(fim);

        }

    }


    // ==================================================
    // GERAR INDICADORES
    // ==================================================

    async function gerarIndicadores() {

        const filtros =
            obterFiltrosIndicadores();

        validarFiltrosIndicadores(
            filtros
        );

        mostrarCarregamentoIndicadores(true);

        bloquearFormularioIndicadores(true);

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                "gerar"
            );

            dados.append(
                "data_inicial",
                filtros.data_inicial
            );

            dados.append(
                "data_final",
                filtros.data_final
            );

            if (
                filtros.filial_id !== null
            ) {
                dados.append(
                    "filial_id",
                    String(
                        filtros.filial_id
                    )
                );
            }

            if (
                filtros.plano_contas_id !== null
            ) {
                dados.append(
                    "plano_contas_id",
                    String(
                        filtros.plano_contas_id
                    )
                );
            }

            if (
                filtros.exercicio_id !== null
            ) {
                dados.append(
                    "exercicio_id",
                    String(
                        filtros.exercicio_id
                    )
                );
            }

            dados.append(
                "comparar_periodo_anterior",
                filtros.comparar_periodo_anterior
                    ? "1"
                    : "0"
            );

            const resposta =
                await requisicaoIndicadores(
                    INDICADORES_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            dadosIndicadoresAtuais =
                resposta.data || {};

            preencherIndicadores(
                dadosIndicadoresAtuais
            );

        } catch (erro) {

            limparResultadoIndicadores();

            mostrarErroIndicadores(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoIndicadores(false);

            bloquearFormularioIndicadores(false);

        }

    }


    // ==================================================
    // OBTER FILTROS
    // ==================================================

    function obterFiltrosIndicadores() {

        return {
            data_inicial:
                String(
                    $("#indicadoresDataInicial")
                        .val()
                    || ""
                ).trim(),

            data_final:
                String(
                    $("#indicadoresDataFinal")
                        .val()
                    || ""
                ).trim(),

            filial_id:
                inteiroOpcionalIndicadores(
                    $("#indicadoresFilialId")
                        .val()
                ),

            plano_contas_id:
                inteiroOpcionalIndicadores(
                    $("#indicadoresPlanoContasId")
                        .val()
                ),

            exercicio_id:
                inteiroOpcionalIndicadores(
                    $("#indicadoresExercicioId")
                        .val()
                ),

            comparar_periodo_anterior:
                $("#indicadoresCompararPeriodoAnterior")
                    .is(":checked")
        };

    }


    // ==================================================
    // VALIDAR FILTROS
    // ==================================================

    function validarFiltrosIndicadores(
        filtros
    ) {

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

    function preencherIndicadores(
        dados
    ) {

        preencherPeriodoIndicadores(
            dados.filtros || {}
        );

        preencherSaudeFinanceira(
            dados.saude_financeira || {}
        );

        preencherResumoBalanco(
            dados.balanco || {}
        );

        preencherResumoDre(
            dados.dre || {}
        );

        preencherSaldosRelevantes(
            dados.saldos_relevantes || {}
        );

        preencherRaciosIndicadores(
            dados.indicadores || {}
        );


        preencherComposicaoPatrimonial(
            dados.indicadores || {}
        );

        preencherFluxosIndicadores(
            dados.indicadores || {},
            dados.fluxo_caixa || null
        );

        preencherComparativoIndicadores(
            dados.comparativo || null,
            dados.balanco || {},
            dados.dre || {}
        );

        preencherAlertasIndicadores(
            dados.alertas || []
        );

        preencherEstadoBalanco(
            dados.balanco || {}
        );

        criarGraficoEstruturaFinanceiraIndicadores(
            dados.balanco || {}
        );

        criarGraficoResultadosIndicadores(
            dados.dre || {}
        );

        criarGraficoComparativoIndicadores(
            dados.comparativo || null,
            dados.dre || {}
        );

        $("#painelResultadoIndicadores")
            .show();

        $("#btnImprimirIndicadores")
            .prop(
                "disabled",
                false
            );

        $("#btnExportarExcelIndicadores")
            .prop(
                "disabled",
                false
            );

        $("#btnExportarPdfIndicadores")
            .prop(
                "disabled",
                false
            );

    }


    // ==================================================
    // PERÍODO
    // ==================================================

    function preencherPeriodoIndicadores(
        filtros
    ) {

        definirTextoIndicadores(
            "#indicadoresPeriodoRelatorio",
            formatarDataIndicadores(
                filtros.data_inicial
            )
            + " a "
            + formatarDataIndicadores(
                filtros.data_final
            )
        );

    }


    // ==================================================
    // SAÚDE FINANCEIRA
    // ==================================================

    function preencherSaudeFinanceira(
        saude
    ) {

        definirTextoIndicadores(
            "#indicadoresPontuacaoSaude",
            Number(
                saude.pontuacao || 0
            ).toLocaleString(
                "pt-PT"
            )
            + "/100"
        );

        definirTextoIndicadores(
            "#indicadoresClassificacaoSaude",
            saude.classificacao || "-"
        );

        definirTextoIndicadores(
            "#indicadoresAlertasCriticos",
            Number(
                saude.quantidade_alertas_criticos
                || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

        definirTextoIndicadores(
            "#indicadoresAlertasAtencao",
            Number(
                saude.quantidade_alertas_atencao
                || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

        const barra =
            $("#indicadoresBarraSaude");

        const pontuacao =
            Math.max(
                0,
                Math.min(
                    100,
                    Number(
                        saude.pontuacao || 0
                    )
                )
            );

        barra
            .css(
                "width",
                pontuacao + "%"
            )
            .attr(
                "aria-valuenow",
                pontuacao
            );

        barra.removeClass(
            "bg-success bg-info bg-warning bg-danger"
        );

        if (pontuacao >= 80) {

            barra.addClass(
                "bg-success"
            );

        } else if (pontuacao >= 65) {

            barra.addClass(
                "bg-info"
            );

        } else if (pontuacao >= 50) {

            barra.addClass(
                "bg-warning"
            );

        } else {

            barra.addClass(
                "bg-danger"
            );

        }

    }


    // ==================================================
    // BALANÇO
    // ==================================================

    function preencherResumoBalanco(
        balanco
    ) {

        definirTextoIndicadores(
            "#indicadoresAtivoTotal",
            formatarMoedaIndicadores(
                balanco.ativo_total
            )
        );

        definirTextoIndicadores(
            "#indicadoresAtivoCorrente",
            formatarMoedaIndicadores(
                balanco.ativo_corrente
            )
        );

        definirTextoIndicadores(
            "#indicadoresAtivoNaoCorrente",
            formatarMoedaIndicadores(
                balanco.ativo_nao_corrente
            )
        );

        definirTextoIndicadores(
            "#indicadoresPassivoTotal",
            formatarMoedaIndicadores(
                balanco.passivo_total
            )
        );

        definirTextoIndicadores(
            "#indicadoresPassivoCorrente",
            formatarMoedaIndicadores(
                balanco.passivo_corrente
            )
        );

        definirTextoIndicadores(
            "#indicadoresPassivoNaoCorrente",
            formatarMoedaIndicadores(
                balanco.passivo_nao_corrente
            )
        );

        definirTextoIndicadores(
            "#indicadoresCapitalProprio",
            formatarMoedaIndicadores(
                balanco.capital_proprio
            )
        );

        definirTextoIndicadores(
            "#indicadoresResultadoBalanco",
            formatarMoedaIndicadores(
                balanco.resultado_balanco
            )
        );

        aplicarClasseValorIndicadores(
            "#indicadoresCapitalProprio",
            balanco.capital_proprio
        );

        aplicarClasseValorIndicadores(
            "#indicadoresResultadoBalanco",
            balanco.resultado_balanco
        );

    }


    // ==================================================
    // DRE
    // ==================================================

    function preencherResumoDre(
        dre
    ) {

        definirTextoIndicadores(
            "#indicadoresVolumeNegocios",
            formatarMoedaIndicadores(
                dre.volume_negocios
            )
        );


        definirTextoIndicadores(
            "#indicadoresRendimentos",
            formatarMoedaIndicadores(
                dre.rendimentos
            )
        );


        definirTextoIndicadores(
            "#indicadoresCustos",
            formatarMoedaIndicadores(
                dre.custos
            )
        );


        definirTextoIndicadores(
            "#indicadoresGastosOperacionais",
            formatarMoedaIndicadores(
                dre.gastos_operacionais
            )
        );


        definirTextoIndicadores(
            "#indicadoresAmortizacoes",
            formatarMoedaIndicadores(
                dre.amortizacoes
            )
        );


        definirTextoIndicadores(
            "#indicadoresRendimentosFinanceiros",
            formatarMoedaIndicadores(
                dre.rendimentos_financeiros
            )
        );


        definirTextoIndicadores(
            "#indicadoresGastosFinanceiros",
            formatarMoedaIndicadores(
                dre.gastos_financeiros
            )
        );


        definirTextoIndicadores(
            "#indicadoresResultadoFinanceiro",
            formatarMoedaIndicadores(
                dre.resultado_financeiro
            )
        );


        definirTextoIndicadores(
            "#indicadoresResultadoBruto",
            formatarMoedaIndicadores(
                dre.resultado_bruto
            )
        );


        definirTextoIndicadores(
            "#indicadoresEbitda",
            formatarMoedaIndicadores(
                dre.ebitda
            )
        );


        definirTextoIndicadores(
            "#indicadoresEbit",
            formatarMoedaIndicadores(
                dre.ebit
            )
        );


        definirTextoIndicadores(
            "#indicadoresResultadoOperacional",
            formatarMoedaIndicadores(
                dre.resultado_operacional
            )
        );


        definirTextoIndicadores(
            "#indicadoresResultadoAntesImpostos",
            formatarMoedaIndicadores(
                dre.resultado_antes_impostos
            )
        );


        definirTextoIndicadores(
            "#indicadoresImpostoRendimento",
            formatarMoedaIndicadores(
                dre.imposto_rendimento
            )
        );


        definirTextoIndicadores(
            "#indicadoresResultadoLiquido",
            formatarMoedaIndicadores(
                dre.resultado_liquido
            )
        );


        /*
        |--------------------------------------------------------------------------
        | CORES DOS RESULTADOS
        |--------------------------------------------------------------------------
        */

        aplicarClasseValorIndicadores(
            "#indicadoresResultadoFinanceiro",
            dre.resultado_financeiro
        );


        aplicarClasseValorIndicadores(
            "#indicadoresResultadoBruto",
            dre.resultado_bruto
        );


        aplicarClasseValorIndicadores(
            "#indicadoresEbitda",
            dre.ebitda
        );


        aplicarClasseValorIndicadores(
            "#indicadoresEbit",
            dre.ebit
        );


        aplicarClasseValorIndicadores(
            "#indicadoresResultadoOperacional",
            dre.resultado_operacional
        );


        aplicarClasseValorIndicadores(
            "#indicadoresResultadoAntesImpostos",
            dre.resultado_antes_impostos
        );


        aplicarClasseValorIndicadores(
            "#indicadoresResultadoLiquido",
            dre.resultado_liquido
        );
    }

    // ==================================================
    // SALDOS RELEVANTES
    // ==================================================

    function preencherSaldosRelevantes(
        saldos
    ) {

        definirTextoIndicadores(
            "#indicadoresDisponibilidades",
            formatarMoedaIndicadores(
                saldos.disponibilidades
            )
        );

        definirTextoIndicadores(
            "#indicadoresInventarios",
            formatarMoedaIndicadores(
                saldos.inventarios
            )
        );

        definirTextoIndicadores(
            "#indicadoresClientes",
            formatarMoedaIndicadores(
                saldos.clientes
            )
        );

        definirTextoIndicadores(
            "#indicadoresFornecedores",
            formatarMoedaIndicadores(
                saldos.fornecedores
            )
        );

        definirTextoIndicadores(
            "#indicadoresFundoManeio",
            formatarMoedaIndicadores(
                saldos.fundo_maneio
            )
        );

        definirTextoIndicadores(
            "#indicadoresCapitalInvestido",
            formatarMoedaIndicadores(
                saldos.capital_investido
            )
        );

        aplicarClasseValorIndicadores(
            "#indicadoresFundoManeio",
            saldos.fundo_maneio
        );

    }


    // ==================================================
    // RÁCIOS
    // ==================================================

    function preencherRaciosIndicadores(
        indicadores
    ) {

        // LIQUIDEZ

        definirTextoIndicadores(
            "#indicadoresLiquidezCorrente",
            formatarRacioIndicadores(
                indicadores.liquidez_corrente
            )
        );

        definirTextoIndicadores(
            "#indicadoresLiquidezReduzida",
            formatarRacioIndicadores(
                indicadores.liquidez_reduzida
            )
        );

        definirTextoIndicadores(
            "#indicadoresLiquidezImediata",
            formatarRacioIndicadores(
                indicadores.liquidez_imediata
            )
        );

        definirTextoIndicadores(
            "#indicadoresLiquidezGeral",

            formatarRacioIndicadores(
                indicadores.liquidez_geral
            )
        );

        // ESTRUTURA

        definirTextoIndicadores(
            "#indicadoresAutonomiaFinanceira",
            formatarPercentagemIndicadores(
                indicadores.autonomia_financeira
            )
        );

        definirTextoIndicadores(
            "#indicadoresEndividamento",
            formatarPercentagemIndicadores(
                indicadores.endividamento
            )
        );

        definirTextoIndicadores(
            "#indicadoresSolvabilidade",
            formatarPercentagemIndicadores(
                indicadores.solvabilidade
            )
        );

        definirTextoIndicadores(
            "#indicadoresCoberturaPassivo",
            formatarRacioIndicadores(
                indicadores.cobertura_passivo
            )
        );

        definirTextoIndicadores(
            "#indicadoresDependenciaFinanceira",
            formatarPercentagemIndicadores(
                indicadores.grau_dependencia_financeira
            )
        );

        // RENTABILIDADE

        definirTextoIndicadores(
            "#indicadoresRoa",
            formatarPercentagemIndicadores(
                indicadores.roa
            )
        );

        definirTextoIndicadores(
            "#indicadoresRoe",
            formatarPercentagemIndicadores(
                indicadores.roe
            )
        );

        definirTextoIndicadores(
            "#indicadoresRentabilidadeVendas",
            formatarPercentagemIndicadores(
                indicadores.rentabilidade_vendas
            )
        );

        definirTextoIndicadores(
            "#indicadoresRentabilidadeCapitalInvestido",
            formatarPercentagemIndicadores(
                indicadores
                    .rentabilidade_capital_investido
            )
        );

        // MARGENS

        definirTextoIndicadores(
            "#indicadoresMargemBruta",
            formatarPercentagemIndicadores(
                indicadores.margem_bruta
            )
        );

        definirTextoIndicadores(
            "#indicadoresMargemOperacional",
            formatarPercentagemIndicadores(
                indicadores.margem_operacional
            )
        );

        definirTextoIndicadores(
            "#indicadoresMargemEbitda",
            formatarPercentagemIndicadores(
                indicadores.margem_ebitda
            )
        );

        definirTextoIndicadores(
            "#indicadoresMargemLiquida",
            formatarPercentagemIndicadores(
                indicadores.margem_liquida
            )
        );

        definirTextoIndicadores(
            "#indicadoresPesoCustos",
            formatarPercentagemIndicadores(
                indicadores.peso_custos_vendas
            )
        );

        definirTextoIndicadores(
            "#indicadoresPesoGastos",
            formatarPercentagemIndicadores(
                indicadores.peso_gastos_totais
            )
        );

        // EFICIÊNCIA

        definirTextoIndicadores(
            "#indicadoresRotacaoAtivo",
            formatarRacioIndicadores(
                indicadores.rotacao_ativo
            )
        );

        definirTextoIndicadores(
            "#indicadoresRotacaoCapitalProprio",
            formatarRacioIndicadores(
                indicadores.rotacao_capital_proprio
            )
        );

        definirTextoIndicadores(
            "#indicadoresCoberturaGastos",
            formatarRacioIndicadores(
                indicadores.cobertura_gastos
            )
        );

        // FUNDO DE MANEIO

        definirTextoIndicadores(
            "#indicadoresNecessidadeFundoManeio",
            formatarMoedaIndicadores(
                indicadores.necessidade_fundo_maneio
            )
        );

        definirTextoIndicadores(
            "#indicadoresTesourariaLiquida",
            formatarMoedaIndicadores(
                indicadores.tesouraria_liquida
            )
        );

        aplicarClasseValorIndicadores(
            "#indicadoresTesourariaLiquida",
            indicadores.tesouraria_liquida
        );

    }

    // ==================================================
    // COMPOSIÇÃO PATRIMONIAL
    // ==================================================

    function preencherComposicaoPatrimonial(
        indicadores
    ) {

        definirTextoIndicadores(
            "#indicadoresPesoAtivoCorrente",
            formatarPercentagemIndicadores(
                indicadores.peso_ativo_corrente
            )
        );


        definirTextoIndicadores(
            "#indicadoresPesoAtivoNaoCorrente",
            formatarPercentagemIndicadores(
                indicadores.peso_ativo_nao_corrente
            )
        );


        definirTextoIndicadores(
            "#indicadoresPesoPassivoCorrente",
            formatarPercentagemIndicadores(
                indicadores.peso_passivo_corrente
            )
        );


        definirTextoIndicadores(
            "#indicadoresPesoPassivoNaoCorrente",
            formatarPercentagemIndicadores(
                indicadores.peso_passivo_nao_corrente
            )
        );


        definirTextoIndicadores(
            "#indicadoresPesoCapitalProprio",
            formatarPercentagemIndicadores(
                indicadores.peso_capital_proprio
            )
        );
    }


    // ==================================================
    // FLUXOS DE CAIXA
    // ==================================================

    function preencherFluxosIndicadores(
        indicadores,
        fluxoCaixa
    ) {

        definirTextoIndicadores(
            "#indicadoresFluxoOperacional",
            formatarMoedaOuIndisponivel(
                indicadores.fluxo_operacional
            )
        );

        definirTextoIndicadores(
            "#indicadoresFluxoInvestimento",
            formatarMoedaOuIndisponivel(
                indicadores.fluxo_investimento
            )
        );

        definirTextoIndicadores(
            "#indicadoresFluxoFinanciamento",
            formatarMoedaOuIndisponivel(
                indicadores.fluxo_financiamento
            )
        );

        definirTextoIndicadores(
            "#indicadoresVariacaoLiquidaCaixa",
            formatarMoedaOuIndisponivel(
                indicadores.variacao_liquida_caixa
            )
        );

        definirTextoIndicadores(
            "#indicadoresSaldoFinalCaixa",
            formatarMoedaOuIndisponivel(
                indicadores.saldo_final_caixa
            )
        );

        definirTextoIndicadores(
            "#indicadoresConversaoResultadoCaixa",
            formatarRacioIndicadores(
                indicadores
                    .conversao_resultado_em_caixa
            )
        );

        const aviso =
            $("#indicadoresAvisoFluxoCaixa");

        if (
            fluxoCaixa
            && fluxoCaixa.disponivel === false
        ) {

            aviso
                .removeClass(
                    "alert-success alert-info"
                )
                .addClass(
                    "alert-warning"
                )
                .text(
                    fluxoCaixa.erro
                    || "Os indicadores de fluxo de caixa não estão disponíveis."
                )
                .show();

        } else {

            aviso.hide();

        }

    }


    // ==================================================
    // COMPARATIVO
    // ==================================================

    function preencherComparativoIndicadores(
        comparativo,
        balancoAtual,
        dreAtual
    ) {

        const painel =
            $("#painelComparativoIndicadores");


        if (!comparativo) {

            painel.hide();

            return;
        }


        const periodo =
            comparativo.periodo_anterior
            || {};


        definirTextoIndicadores(
            "#indicadoresPeriodoAnterior",

            formatarDataIndicadores(
                periodo.data_inicial
            )
            + " a "
            + formatarDataIndicadores(
                periodo.data_final
            )
        );


        const anterior =
            comparativo.valores
            || {};


        const variacoes =
            comparativo.variacoes
            || {};


        preencherLinhaComparativo(
            "#comparativoAtivoTotal",

            balancoAtual.ativo_total,

            anterior.ativo_total,

            variacoes.ativo_total
        );


        preencherLinhaComparativo(
            "#comparativoPassivoTotal",

            balancoAtual.passivo_total,

            anterior.passivo_total,

            variacoes.passivo_total
        );


        preencherLinhaComparativo(
            "#comparativoCapitalProprio",

            balancoAtual.capital_proprio,

            anterior.capital_proprio,

            variacoes.capital_proprio
        );


        preencherLinhaComparativo(
            "#comparativoVolumeNegocios",

            dreAtual.volume_negocios,

            anterior.volume_negocios,

            variacoes.volume_negocios
        );


        preencherLinhaComparativo(
            "#comparativoResultadoBruto",

            dreAtual.resultado_bruto,

            anterior.resultado_bruto,

            variacoes.resultado_bruto
        );


        preencherLinhaComparativo(
            "#comparativoEbitda",

            dreAtual.ebitda,

            anterior.ebitda,

            variacoes.ebitda
        );


        preencherLinhaComparativo(
            "#comparativoEbit",

            dreAtual.ebit,

            anterior.ebit,

            variacoes.ebit
        );


        preencherLinhaComparativo(
            "#comparativoResultadoOperacional",

            dreAtual.resultado_operacional,

            anterior.resultado_operacional,

            variacoes.resultado_operacional
        );


        preencherLinhaComparativo(
            "#comparativoResultadoAntesImpostos",

            dreAtual.resultado_antes_impostos,

            anterior.resultado_antes_impostos,

            variacoes.resultado_antes_impostos
        );


        preencherLinhaComparativo(
            "#comparativoResultadoLiquido",

            dreAtual.resultado_liquido,

            anterior.resultado_liquido,

            variacoes.resultado_liquido
        );


        painel.show();
    }


    function preencherLinhaComparativo(
        seletor,
        valorAtual,
        valorAnterior,
        variacao
    ) {

        const linha =
            $(seletor);


        /*
        |--------------------------------------------------------------------------
        | PERÍODO ATUAL
        |--------------------------------------------------------------------------
        */

        linha
            .find(
                "[data-campo='atual']"
            )
            .text(
                formatarMoedaIndicadores(
                    valorAtual
                )
            );


        /*
        |--------------------------------------------------------------------------
        | PERÍODO ANTERIOR
        |--------------------------------------------------------------------------
        */

        linha
            .find(
                "[data-campo='anterior']"
            )
            .text(
                formatarMoedaIndicadores(
                    valorAnterior
                )
            );


        /*
        |--------------------------------------------------------------------------
        | VARIAÇÃO
        |--------------------------------------------------------------------------
        */

        const elementoVariacao =
            linha.find(
                "[data-campo='variacao']"
            );


        elementoVariacao
            .removeClass(
                "text-success text-danger text-muted"
            )
            .text(
                formatarPercentagemIndicadores(
                    variacao
                )
            );


        if (
            variacao === null
            || typeof variacao ===
            "undefined"
        ) {

            elementoVariacao
                .addClass(
                    "text-muted"
                );

        } else if (
            Number(variacao) > 0
        ) {

            elementoVariacao
                .addClass(
                    "text-success"
                );

        } else if (
            Number(variacao) < 0
        ) {

            elementoVariacao
                .addClass(
                    "text-danger"
                );

        } else {

            elementoVariacao
                .addClass(
                    "text-muted"
                );
        }
    }


    // ==================================================
    // ALERTAS
    // ==================================================

    function preencherAlertasIndicadores(
        alertas
    ) {

        const container =
            $("#listaAlertasIndicadores");

        container.empty();

        if (!alertas.length) {

            container.html(`
                <div class="alert alert-success mb-0">

                    <strong>
                        Sem alertas relevantes.
                    </strong>

                    Não foram identificadas situações críticas
                    nos indicadores analisados.

                </div>
            `);

            return;

        }

        alertas.forEach(
            function (alerta) {

                const nivel =
                    String(
                        alerta.nivel || ""
                    ).toUpperCase();

                let classe =
                    "alert-info";

                let icone =
                    "fa-info-circle";

                if (nivel === "CRITICO") {

                    classe =
                        "alert-danger";

                    icone =
                        "fa-exclamation-circle";

                } else if (
                    nivel === "ATENCAO"
                ) {

                    classe =
                        "alert-warning";

                    icone =
                        "fa-exclamation-triangle";

                } else if (
                    nivel === "POSITIVO"
                ) {

                    classe =
                        "alert-success";

                    icone =
                        "fa-check-circle";

                }

                container.append(`
                    <div class="alert ${classe}">

                        <div class="d-flex align-items-start">

                            <i class="fa ${icone} mr-3 mt-1"></i>

                            <div>

                                <strong>
                                    ${escaparHtmlIndicadores(
                    alerta.titulo || "-"
                )}
                                </strong>

                                <div class="mt-1">

                                    ${escaparHtmlIndicadores(
                    alerta.mensagem || ""
                )}

                                </div>

                            </div>

                        </div>

                    </div>
                `);

            }
        );

    }


    // ==================================================
    // ESTADO DO BALANÇO
    // ==================================================

    function preencherEstadoBalanco(
        balanco
    ) {

        const elemento =
            $("#estadoBalancoIndicadores");

        elemento.removeClass(
            "alert-success alert-danger"
        );

        if (Boolean(balanco.equilibrado)) {

            elemento
                .addClass(
                    "alert-success"
                )
                .html(`
                    <strong>Balanço equilibrado.</strong>
                    O total do ativo coincide com passivo,
                    capital próprio e resultado.
                `);

        } else {

            elemento
                .addClass(
                    "alert-danger"
                )
                .html(`
                    <strong>Balanço desequilibrado.</strong>
                    Diferença:
                    ${formatarMoedaIndicadores(
                    balanco.diferenca
                )}.
                `);

        }

        elemento.show();

    }


    // ==================================================
    // GRÁFICO DA ESTRUTURA FINANCEIRA
    // ==================================================

    function criarGraficoEstruturaFinanceiraIndicadores(
        balanco
    ) {

        const canvas =
            document.getElementById(
                "graficoEstruturaFinanceiraIndicadores"
            );

        if (
            !canvas
            || typeof Chart === "undefined"
        ) {
            return;
        }

        if (graficoEstruturaFinanceira) {

            graficoEstruturaFinanceira.destroy();

            graficoEstruturaFinanceira =
                null;

        }

        graficoEstruturaFinanceira =
            new Chart(
                canvas.getContext("2d"),
                {
                    type:
                        "bar",

                    data: {
                        labels: [
                            "Ativo",
                            "Passivo",
                            "Capital Próprio",
                            "Resultado"
                        ],

                        datasets: [
                            {
                                label:
                                    "Valor contabilístico",

                                data: [
                                    Number(
                                        balanco.ativo_total
                                        || 0
                                    ),

                                    Number(
                                        balanco.passivo_total
                                        || 0
                                    ),

                                    Number(
                                        balanco.capital_proprio
                                        || 0
                                    ),

                                    Number(
                                        balanco.resultado_balanco
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
                                            return formatarMoedaIndicadores(
                                                contexto.raw
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
    // GRÁFICO DOS RESULTADOS
    // ==================================================

    function criarGraficoResultadosIndicadores(
        dre
    ) {

        const canvas =
            document.getElementById(
                "graficoResultadosIndicadores"
            );


        if (
            !canvas
            || typeof Chart === "undefined"
        ) {
            return;
        }


        if (graficoResultados) {

            graficoResultados.destroy();

            graficoResultados =
                null;
        }


        graficoResultados =
            new Chart(
                canvas.getContext("2d"),
                {
                    type:
                        "bar",

                    data: {

                        labels: [
                            "Volume de Negócios",
                            "Resultado Bruto",
                            "EBITDA",
                            "EBIT",
                            "Resultado Antes de Impostos",
                            "Resultado Líquido"
                        ],

                        datasets: [
                            {
                                label:
                                    "Resultado",

                                data: [

                                    Number(
                                        dre.volume_negocios
                                        || 0
                                    ),

                                    Number(
                                        dre.resultado_bruto
                                        || 0
                                    ),

                                    Number(
                                        dre.ebitda
                                        || 0
                                    ),

                                    Number(
                                        dre.ebit
                                        || 0
                                    ),

                                    Number(
                                        dre.resultado_antes_impostos
                                        || 0
                                    ),

                                    Number(
                                        dre.resultado_liquido
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

                                            return formatarMoedaIndicadores(
                                                contexto.raw
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
    // GRÁFICO COMPARATIVO
    // ==================================================

    function criarGraficoComparativoIndicadores(
        comparativo,
        dreAtual
    ) {

        const canvas =
            document.getElementById(
                "graficoComparativoIndicadores"
            );


        if (
            !canvas
            || typeof Chart === "undefined"
        ) {
            return;
        }


        if (graficoComparativo) {

            graficoComparativo.destroy();

            graficoComparativo =
                null;
        }


        if (!comparativo) {
            return;
        }


        const anterior =
            comparativo.valores
            || {};


        graficoComparativo =
            new Chart(
                canvas.getContext("2d"),
                {
                    type:
                        "bar",

                    data: {

                        labels: [
                            "Volume de Negócios",
                            "Resultado Bruto",
                            "EBITDA",
                            "EBIT",
                            "Resultado Antes de Impostos",
                            "Resultado Líquido"
                        ],

                        datasets: [

                            {
                                label:
                                    "Período atual",

                                data: [

                                    Number(
                                        dreAtual.volume_negocios
                                        || 0
                                    ),

                                    Number(
                                        dreAtual.resultado_bruto
                                        || 0
                                    ),

                                    Number(
                                        dreAtual.ebitda
                                        || 0
                                    ),

                                    Number(
                                        dreAtual.ebit
                                        || 0
                                    ),

                                    Number(
                                        dreAtual.resultado_antes_impostos
                                        || 0
                                    ),

                                    Number(
                                        dreAtual.resultado_liquido
                                        || 0
                                    )
                                ]
                            },

                            {
                                label:
                                    "Período anterior",

                                data: [

                                    Number(
                                        anterior.volume_negocios
                                        || 0
                                    ),

                                    Number(
                                        anterior.resultado_bruto
                                        || 0
                                    ),

                                    Number(
                                        anterior.ebitda
                                        || 0
                                    ),

                                    Number(
                                        anterior.ebit
                                        || 0
                                    ),

                                    Number(
                                        anterior.resultado_antes_impostos
                                        || 0
                                    ),

                                    Number(
                                        anterior.resultado_liquido
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

                            tooltip: {

                                callbacks: {

                                    label:
                                        function (
                                            contexto
                                        ) {

                                            return (
                                                contexto.dataset.label
                                                + ": "
                                                + formatarMoedaIndicadores(
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
    // LIMPAR FILTROS
    // ==================================================

    function limparFiltrosIndicadores() {

        $("#formIndicadoresContabilisticos")[0]
            .reset();

        dadosIndicadoresAtuais =
            null;

        selecionarPrimeiroExercicioIndicadores();

        aplicarDatasExercicioIndicadores();

        limparResultadoIndicadores();

    }


    function selecionarPrimeiroExercicioIndicadores() {

        const primeiraOpcao =
            $("#indicadoresExercicioId option")
                .filter(
                    function () {

                        return Number(
                            $(this).val() || 0
                        ) > 0;

                    }
                )
                .first();

        if (primeiraOpcao.length) {

            $("#indicadoresExercicioId")
                .val(
                    primeiraOpcao.val()
                );

        }

    }


    // ==================================================
    // LIMPAR RESULTADO
    // ==================================================

    function limparResultadoIndicadores() {

        $("#painelResultadoIndicadores")
            .hide();

        $("#painelComparativoIndicadores")
            .hide();

        $("#estadoBalancoIndicadores")
            .hide();

        $("#indicadoresAvisoFluxoCaixa")
            .hide();

        $("#btnImprimirIndicadores")
            .prop(
                "disabled",
                true
            );

        $("#btnExportarExcelIndicadores")
            .prop(
                "disabled",
                true
            );

        $("#btnExportarPdfIndicadores")
            .prop(
                "disabled",
                true
            );

        destruirGraficosIndicadores();

    }


    // ==================================================
    // IMPRESSÃO
    // ==================================================

    function imprimirIndicadores() {

        if (!dadosIndicadoresAtuais) {

            mostrarErroIndicadores(
                "Gere os Indicadores Contabilísticos antes de imprimir."
            );

            return;

        }

        window.print();

    }


    // ==================================================
    // EXPORTAR EXCEL
    // ==================================================

    function exportarExcelIndicadores() {

        if (!dadosIndicadoresAtuais) {

            mostrarErroIndicadores(
                "Gere os Indicadores Contabilísticos antes de exportar."
            );

            return;

        }

        if (typeof XLSX === "undefined") {

            mostrarErroIndicadores(
                "A biblioteca de exportação Excel não está disponível."
            );

            return;

        }

        const linhas =
            montarLinhasExcelIndicadores(
                dadosIndicadoresAtuais
            );

        const folha =
            XLSX.utils.aoa_to_sheet(
                linhas
            );

        const livro =
            XLSX.utils.book_new();

        XLSX.utils.book_append_sheet(
            livro,
            folha,
            "Indicadores"
        );

        XLSX.writeFile(
            livro,
            "indicadores_contabilisticos.xlsx"
        );

    }


    // ==================================================
    // EXPORTAR PDF
    // ==================================================

    function exportarPdfIndicadores() {

        if (!dadosIndicadoresAtuais) {

            mostrarErroIndicadores(
                "Gere os Indicadores Contabilísticos antes de exportar."
            );

            return;

        }

        window.print();

    }


    // ==================================================
    // LINHAS PARA EXCEL
    // ==================================================

    function montarLinhasExcelIndicadores(
        dados
    ) {

        const balanco =
            dados.balanco || {};

        const dre =
            dados.dre || {};

        const indicadores =
            dados.indicadores || {};

        const linhas = [
            [
                "Indicadores Contabilísticos"
            ],

            [
                "Período",
                dados.filtros.data_inicial
                + " a "
                + dados.filtros.data_final
            ],

            [],

            [
                "BALANÇO PATRIMONIAL"
            ],

            [
                "Ativo Total",
                balanco.ativo_total
            ],

            [
                "Ativo Corrente",
                balanco.ativo_corrente
            ],

            [
                "Ativo Não Corrente",
                balanco.ativo_nao_corrente
            ],

            [
                "Passivo Total",
                balanco.passivo_total
            ],

            [
                "Passivo Corrente",
                balanco.passivo_corrente
            ],

            [
                "Passivo Não Corrente",
                balanco.passivo_nao_corrente
            ],

            [
                "Capital Próprio",
                balanco.capital_proprio
            ],

            [],

            [
                "DEMONSTRAÇÃO DE RESULTADOS"
            ],

            [
                "Volume de Negócios",
                dre.volume_negocios
            ],

            [
                "Rendimentos Totais",
                dre.rendimentos
            ],

            [
                "Custos das Vendas",
                dre.custos
            ],

            [
                "Gastos Operacionais",
                dre.gastos_operacionais
            ],

            [
                "Amortizações",
                dre.amortizacoes
            ],

            [
                "Rendimentos Financeiros",
                dre.rendimentos_financeiros
            ],

            [
                "Gastos Financeiros",
                dre.gastos_financeiros
            ],

            [
                "Resultado Financeiro",
                dre.resultado_financeiro
            ],

            [
                "Resultado Bruto",
                dre.resultado_bruto
            ],

            [
                "EBITDA",
                dre.ebitda
            ],

            [
                "EBIT",
                dre.ebit
            ],

            [
                "Resultado Operacional",
                dre.resultado_operacional
            ],

            [
                "Resultado Antes de Impostos",
                dre.resultado_antes_impostos
            ],

            [
                "Imposto sobre o Rendimento",
                dre.imposto_rendimento
            ],

            [
                "Resultado Líquido",
                dre.resultado_liquido
            ],

            [],

            [
                "INDICADORES"
            ]
        ];

        Object.entries(
            indicadores
        ).forEach(
            function (entrada) {

                linhas.push([
                    entrada[0],
                    entrada[1]
                ]);

            }
        );

        return linhas;

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
                || "Não foi possível gerar os Indicadores Contabilísticos."
            );
        }

        return json;

    }


    // ==================================================
    // CARREGAMENTO E BLOQUEIO
    // ==================================================

    function mostrarCarregamentoIndicadores(
        mostrar
    ) {

        $("#indicadoresCarregando")
            .toggle(
                Boolean(mostrar)
            );

    }


    function bloquearFormularioIndicadores(
        bloquear
    ) {

        $("#formIndicadoresContabilisticos")
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

    function mostrarErroIndicadores(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível processar",

            text:
                mensagem
                || "Ocorreu um erro ao gerar os Indicadores Contabilísticos.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // HELPERS DE FORMATAÇÃO
    // ==================================================

    function definirTextoIndicadores(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function formatarMoedaIndicadores(
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


    function formatarMoedaOuIndisponivel(
        valor
    ) {

        if (
            valor === null
            || typeof valor === "undefined"
        ) {
            return "Indisponível";
        }

        return formatarMoedaIndicadores(
            valor
        );

    }


    function formatarPercentagemIndicadores(
        valor
    ) {

        if (
            valor === null
            || typeof valor === "undefined"
        ) {
            return "N/D";
        }

        return Number(valor)
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


    function formatarRacioIndicadores(
        valor
    ) {

        if (
            valor === null
            || typeof valor === "undefined"
        ) {
            return "N/D";
        }

        return Number(valor)
            .toLocaleString(
                "pt-PT",
                {
                    minimumFractionDigits:
                        2,

                    maximumFractionDigits:
                        4
                }
            );

    }


    function formatarDataIndicadores(
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
            return escaparHtmlIndicadores(
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


    function aplicarClasseValorIndicadores(
        seletor,
        valor
    ) {

        const elemento =
            $(seletor);

        elemento.removeClass(
            "text-success text-danger text-muted"
        );

        const numero =
            Number(
                valor || 0
            );

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


    function inteiroOpcionalIndicadores(
        valor
    ) {

        const numero =
            Number(
                valor || 0
            );

        return numero > 0
            ? numero
            : null;

    }


    function escaparHtmlIndicadores(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }


    // ==================================================
    // DESTRUIR GRÁFICOS
    // ==================================================

    function destruirGraficosIndicadores() {

        if (graficoEstruturaFinanceira) {

            graficoEstruturaFinanceira.destroy();

            graficoEstruturaFinanceira =
                null;

        }

        if (graficoResultados) {

            graficoResultados.destroy();

            graficoResultados =
                null;

        }

        if (graficoComparativo) {

            graficoComparativo.destroy();

            graficoComparativo =
                null;

        }

    }

})(jQuery);