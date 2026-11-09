"use strict";


const ATIVOS_EMPRESA_URL =
    "/erpplus/app/ajax/ativos_empresa_ajax.php";


let dadosIniciaisAtivos = {

    categorias_tangiveis: [],

    categorias_intangiveis: [],

    filiais: [],

    departamentos: [],

    fornecedores: [],

    contas_contabeis: []
};


let tabelaAtivosEmpresa =
    null;

let ativosDisponiveisMovimentacao = [];


// ==================================================
// INICIALIZAÇÃO
// ==================================================

$(document).ready(
    function () {

        inicializarEventosAtivos();

        carregarDadosIniciaisAtivos();

        inicializarTabelaAtivos();
    }
);


// ==================================================
// EVENTOS
// ==================================================

function inicializarEventosAtivos() {

    // ==============================================
    // NOVO ATIVO
    // ==============================================

    $("#btnNovoAtivo")
        .on(
            "click",
            function () {

                abrirNovoAtivo();
            }
        );

    $("#btnConfiguracaoContabilAtivos")
        .on(
            "click",
            function () {

                abrirConfiguracaoContabilAtivos();
            }
        );


    $("#formConfiguracaoContabilAtivo")
        .on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarConfiguracaoContabilAtivo();
            }
        );

    // ==============================================
    // TIPO DO ATIVO
    // ==============================================

    $("#ativoTipo")
        .on(
            "change",
            function () {

                atualizarCategoriasAtivo();

                atualizarRotulosDepreciacao();

                calcularDepreciacaoAtivo();
            }
        );


    // ==============================================
    // DEPRECIAÇÕES / AMORTIZAÇÕES
    // ==============================================

    $("#abaDepreciacoes-tab")
        .on(
            "shown.bs.tab",
            function () {

                carregarDepreciacoesAtivos();
            }
        );


    $("#btnAtualizarDepreciacoes")
        .on(
            "click",
            function () {

                carregarDepreciacoesAtivos();
            }
        );


    $(
        "#depreciacaoAno,"
        + "#depreciacaoMes,"
        + "#depreciacaoTipo"
    )
        .on(
            "change",
            function () {

                carregarDepreciacoesAtivos();
            }
        );


    $("#depreciacaoPesquisa")
        .on(
            "keyup",
            debounceAtivos(
                function () {

                    carregarDepreciacoesAtivos();
                },
                400
            )
        );


    $("#selecionarTodasDepreciacoes")
        .on(
            "change",
            function () {

                const marcado =
                    $(this).is(
                        ":checked"
                    );


                $(
                    ".checkDepreciacaoAtivo:not(:disabled)"
                )
                    .prop(
                        "checked",
                        marcado
                    );


                atualizarContadorDepreciacoesSelecionadas();
            }
        );


    $(document)
        .on(
            "change",
            ".checkDepreciacaoAtivo",
            function () {

                atualizarEstadoSelecionarTodasDepreciacoes();

                atualizarContadorDepreciacoesSelecionadas();
            }
        );


    $("#btnProcessarDepreciacoesSelecionadas")
        .on(
            "click",
            function () {

                processarDepreciacoesSelecionadas();
            }
        );

    $("#btnConfiguracaoBaixas")
        .on(
            "click",
            function () {

                abrirConfiguracaoBaixas();
            }
        );


    $("#formConfiguracaoBaixas")
        .on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarConfiguracaoBaixas();
            }
        );


    $("#baixaFormaRecebimento")
        .on(
            "change",
            function () {

                carregarContasRecebimentoBaixa();
            }
        );

    // ==============================================
    // BAIXAS / ALIENAÇÕES
    // ==============================================

    $("#abaBaixas-tab")
        .on(
            "shown.bs.tab",
            async function () {

                await carregarAtivosBaixa();

                carregarBaixasAtivos();
            }
        );


    $("#btnNovaBaixaAtivo")
        .on(
            "click",
            async function () {

                await abrirNovaBaixaAtivo();
            }
        );


    $("#baixaAtivo")
        .on(
            "change",
            function () {

                carregarDadosAtivoBaixa();
            }
        );


    $("#baixaTipo")
        .on(
            "change",
            function () {

                ajustarTipoBaixa();

                calcularGanhoPerdaBaixa();
            }
        );


    $("#baixaValorAlienacao")
        .on(
            "input",
            function () {

                calcularGanhoPerdaBaixa();
            }
        );


    $("#formBaixaAtivo")
        .on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarBaixaAtivo();
            }
        );


    $(
        "#filtroBaixaTipo,"
        + "#filtroBaixaDataInicial,"
        + "#filtroBaixaDataFinal"
    )
        .on(
            "change",
            function () {

                carregarBaixasAtivos();
            }
        );


    $("#filtroBaixaPesquisa")
        .on(
            "keyup",
            debounceAtivos(
                function () {

                    carregarBaixasAtivos();
                },
                400
            )
        );


    $("#btnAtualizarBaixas")
        .on(
            "click",
            function () {

                carregarBaixasAtivos();
            }
        );

    // ==============================================
    // MANUTENÇÕES
    // ==============================================

    $("#abaManutencoes-tab")
        .on(
            "shown.bs.tab",
            async function () {

                await carregarAtivosManutencao();

                carregarManutencoesAtivos();
            }
        );


    $("#btnNovaManutencaoAtivo")
        .on(
            "click",
            async function () {

                await abrirNovaManutencaoAtivo();
            }
        );


    $("#formManutencaoAtivo")
        .on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarManutencaoAtivo();
            }
        );


    $(
        "#filtroManutencaoAtivo,"
        + "#filtroManutencaoTipo,"
        + "#filtroManutencaoEstado,"
        + "#filtroManutencaoDataInicial,"
        + "#filtroManutencaoDataFinal"
    )
        .on(
            "change",
            function () {

                carregarManutencoesAtivos();
            }
        );


    $("#filtroManutencaoPesquisa")
        .on(
            "keyup",
            debounceAtivos(
                function () {

                    carregarManutencoesAtivos();
                },
                400
            )
        );


    $("#btnAtualizarManutencoes")
        .on(
            "click",
            function () {

                carregarManutencoesAtivos();
            }
        );


    $("#manutencaoEstado")
        .on(
            "change",
            function () {

                ajustarDatasManutencao();
            }
        );

    // ==============================================
    // MOVIMENTAÇÕES
    // ==============================================

    $("#abaMovimentacoes-tab")
        .on(
            "shown.bs.tab",
            async function () {

                await carregarAtivosMovimentacao();

                carregarMovimentacoesAtivos();
            }
        );


    $("#btnNovaMovimentacaoAtivo")
        .on(
            "click",
            async function () {

                await abrirNovaMovimentacaoAtivo();
            }
        );


    $("#movimentacaoAtivo")
        .on(
            "change",
            function () {

                preencherOrigemMovimentacao();
            }
        );


    $("#formMovimentacaoAtivo")
        .on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarMovimentacaoAtivo();
            }
        );


    $(
        "#filtroMovimentacaoAtivo,"
        + "#filtroMovimentacaoTipo,"
        + "#filtroMovimentacaoFilial,"
        + "#filtroMovimentacaoDepartamento,"
        + "#filtroMovimentacaoDataInicial,"
        + "#filtroMovimentacaoDataFinal"
    )
        .on(
            "change",
            function () {

                carregarMovimentacoesAtivos();
            }
        );


    $("#filtroMovimentacaoPesquisa")
        .on(
            "keyup",
            debounceAtivos(
                function () {

                    carregarMovimentacoesAtivos();
                },
                400
            )
        );


    $("#btnAtualizarMovimentacoes")
        .on(
            "click",
            function () {

                carregarMovimentacoesAtivos();
            }
        );
    // ==============================================
    // CÁLCULOS
    // ==============================================

    $(
        "#ativoValorAquisicao,"
        + "#ativoValorResidual,"
        + "#ativoVidaUtilAnos,"
        + "#ativoTaxaAnual"
    )
        .on(
            "input change",
            function () {

                calcularDepreciacaoAtivo(
                    this.id
                );
            }
        );


    // ==============================================
    // GUARDAR
    // ==============================================

    $("#formAtivoEmpresa")
        .on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarAtivo();
            }
        );


    // ==============================================
    // FILTROS
    // ==============================================

    $(
        "#filtroAtivoTipo,"
        + "#filtroAtivoCategoria,"
        + "#filtroAtivoFilial,"
        + "#filtroAtivoDepartamento,"
        + "#filtroAtivoSituacao"
    )
        .on(
            "change",
            function () {

                recarregarTabelaAtivos();
            }
        );


    $("#filtroAtivoPesquisa")
        .on(
            "keyup",
            debounceAtivos(
                function () {

                    recarregarTabelaAtivos();
                },
                400
            )
        );


    $("#filtroAtivoTipo")
        .on(
            "change",
            function () {

                atualizarCategoriasFiltro();
            }
        );
}

async function abrirConfiguracaoBaixas() {

    preencherContasConfiguracaoBaixas();


    try {

        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?acao=configuracao_baixas"
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível carregar a configuração."
            );
        }


        const config =
            json.data || {};


        $("#configBaixaContaGanho")
            .val(
                config.conta_ganho_alienacao_id
                || ""
            );


        $("#configBaixaContaPerda")
            .val(
                config.conta_perda_alienacao_id
                || ""
            );


        $("#configBaixaContaAbate")
            .val(
                config.conta_abate_id
                || ""
            );


        $("#modalConfiguracaoBaixas")
            .modal(
                "show"
            );

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}

function preencherContasConfiguracaoBaixas() {

    const contas =
        dadosIniciaisAtivos
            .contas_contabeis
        || [];


    $("#configBaixaContaGanho")
        .html(
            montarOpcoesContabeisAgrupadas(
                contas.filter(
                    conta =>
                        String(
                            conta.classe
                        ) === "7"
                ),
                "Selecione a conta de ganho"
            )
        );


    $("#configBaixaContaPerda")
        .html(
            montarOpcoesContabeisAgrupadas(
                contas.filter(
                    conta =>
                        String(
                            conta.classe
                        ) === "6"
                ),
                "Selecione a conta de perda"
            )
        );


    $("#configBaixaContaAbate")
        .html(
            montarOpcoesContabeisAgrupadas(
                contas.filter(
                    conta =>
                        String(
                            conta.classe
                        ) === "6"
                ),
                "Selecione a conta de abate"
            )
        );
}

async function guardarConfiguracaoBaixas() {

    try {

        const form =
            document.getElementById(
                "formConfiguracaoBaixas"
            );


        const dados =
            new FormData(
                form
            );


        dados.set(
            "acao",
            "guardar_configuracao_baixas"
        );


        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível guardar a configuração."
            );
        }


        $("#modalConfiguracaoBaixas")
            .modal(
                "hide"
            );


        mostrarSucessoAtivos(
            json.mensagem
        );

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}

async function abrirConfiguracaoContabilAtivos() {

    preencherContasConfiguracaoAtivos();

    await carregarConfiguracoesContabeisAtivos();

    $("#modalConfiguracaoContabilAtivos")
        .modal(
            "show"
        );
}

function preencherContasConfiguracaoAtivos() {

    // ==============================================
    // JUNTAR CATEGORIAS
    // ==============================================

    const categorias = [
        ...(
            dadosIniciaisAtivos
                .categorias_tangiveis
            || []
        ),

        ...(
            dadosIniciaisAtivos
                .categorias_intangiveis
            || []
        )
    ];


    // ==============================================
    // CONTA / CATEGORIA DO ATIVO
    // ==============================================

    $("#configContaAtivo")
        .html(
            montarOpcoesContabeisAgrupadas(
                categorias,
                "Selecione"
            )
        );


    // ==============================================
    // TODAS AS CONTAS ANALÍTICAS
    // ==============================================

    const htmlContas =
        montarOpcoesContabeisAgrupadas(
            dadosIniciaisAtivos
                .contas_contabeis
            || [],
            "Selecione"
        );


    // ==============================================
    // DEPRECIAÇÃO / AMORTIZAÇÃO ACUMULADA
    // ==============================================

    $("#configContaAcumulada")
        .html(
            htmlContas
        );


    // ==============================================
    // CONTA DE GASTO
    // ==============================================

    $("#configContaGasto")
        .html(
            htmlContas
        );
}

// ==================================================
// CARREGAR ATIVOS PARA MANUTENÇÃO
// ==================================================

async function carregarAtivosManutencao() {

    /*
     * Já temos uma rota que devolve ativos
     * válidos para movimentação.
     *
     * Como ela exclui VENDIDO e ABATIDO,
     * podemos reutilizá-la.
     */

    if (
        !ativosDisponiveisMovimentacao.length
    ) {

        await carregarAtivosMovimentacao();
    }


    const opcoesModal = [
        '<option value="">Selecione</option>'
    ];


    const opcoesFiltro = [
        '<option value="">Todos</option>'
    ];


    ativosDisponiveisMovimentacao
        .forEach(
            function (ativo) {

                const texto =
                    (
                        ativo.codigo
                        || ""
                    )
                    + " - "
                    + (
                        ativo.descricao
                        || ""
                    );


                opcoesModal.push(`
                    <option value="${ativo.id}">
                        ${escaparHtmlAtivos(
                    texto
                )}
                    </option>
                `);


                opcoesFiltro.push(`
                    <option value="${ativo.id}">
                        ${escaparHtmlAtivos(
                    texto
                )}
                    </option>
                `);
            }
        );


    $("#manutencaoAtivo")
        .html(
            opcoesModal.join("")
        );


    const filtroAtual =
        $("#filtroManutencaoAtivo")
            .val();


    $("#filtroManutencaoAtivo")
        .html(
            opcoesFiltro.join("")
        );


    if (filtroAtual) {

        $("#filtroManutencaoAtivo")
            .val(
                filtroAtual
            );
    }


    preencherFornecedoresManutencao();
}

function preencherFornecedoresManutencao() {

    const opcoes = [
        '<option value="">Selecione</option>'
    ];


    (
        dadosIniciaisAtivos.fornecedores
        || []
    )
        .forEach(
            function (item) {

                opcoes.push(`
                    <option value="${item.id}">
                        ${escaparHtmlAtivos(
                    item.nome
                )}
                    </option>
                `);
            }
        );


    $("#manutencaoFornecedor")
        .html(
            opcoes.join("")
        );
}

async function abrirNovaManutencaoAtivo() {

    await carregarAtivosManutencao();


    const form =
        document.getElementById(
            "formManutencaoAtivo"
        );


    if (form) {

        form.reset();
    }


    $("#manutencaoId")
        .val("");


    $("#tituloModalManutencaoAtivo")
        .text(
            "Nova Manutenção"
        );


    $("#manutencaoEstado")
        .val(
            "AGENDADA"
        );


    $("#manutencaoCusto")
        .val(
            "0"
        );


    const hoje =
        new Date()
            .toISOString()
            .slice(
                0,
                10
            );


    $("#manutencaoDataPrevista")
        .val(
            hoje
        );


    $("#modalManutencaoAtivo")
        .modal(
            "show"
        );
}

async function carregarManutencoesAtivos() {

    const params =
        new URLSearchParams({

            acao:
                "listar_manutencoes",

            ativo_id:
                $("#filtroManutencaoAtivo")
                    .val()
                || "",

            tipo_manutencao:
                $("#filtroManutencaoTipo")
                    .val()
                || "",

            estado:
                $("#filtroManutencaoEstado")
                    .val()
                || "",

            data_inicial:
                $("#filtroManutencaoDataInicial")
                    .val()
                || "",

            data_final:
                $("#filtroManutencaoDataFinal")
                    .val()
                || "",

            pesquisa:
                $("#filtroManutencaoPesquisa")
                    .val()
                || ""
        });


    const tbody =
        $("#tabelaManutencoesAtivos tbody");


    tbody.html(`
        <tr>

            <td
                colspan="10"
                class="text-center py-4">

                <i class="fa fa-spinner fa-spin mr-1"></i>

                A carregar...

            </td>

        </tr>
    `);


    try {

        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?"
                + params.toString()
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível carregar as manutenções."
            );
        }


        renderizarManutencoesAtivos(
            Array.isArray(json.data)
                ? json.data
                : []
        );

    } catch (erro) {

        tbody.html(`
            <tr>

                <td
                    colspan="10"
                    class="text-center text-danger py-4">

                    ${escaparHtmlAtivos(
            erro.message
        )}

                </td>

            </tr>
        `);


        mostrarErroAtivos(
            erro.message
        );
    }
}

function renderizarManutencoesAtivos(
    dados
) {

    const tbody =
        $("#tabelaManutencoesAtivos tbody");


    if (!dados.length) {

        tbody.html(`
            <tr>

                <td
                    colspan="10"
                    class="text-center text-muted py-4">

                    Nenhuma manutenção encontrada.

                </td>

            </tr>
        `);

        return;
    }


    const linhas =
        dados.map(
            function (item) {

                let prestador =
                    "-";


                if (
                    item.fornecedor_nome
                ) {

                    prestador =
                        escaparHtmlAtivos(
                            item.fornecedor_nome
                        );
                }


                if (
                    item.tecnico_responsavel
                ) {

                    if (
                        prestador !== "-"
                    ) {

                        prestador +=
                            "<br>";
                    }


                    prestador += `
                        <small class="text-muted">
                            ${escaparHtmlAtivos(
                        item.tecnico_responsavel
                    )}
                        </small>
                    `;
                }


                return `
                    <tr>

                        <td>

                            <strong>
                                ${escaparHtmlAtivos(
                    item.ativo_codigo
                    || ""
                )}
                            </strong>

                            <br>

                            <small class="text-muted">
                                ${escaparHtmlAtivos(
                    item.ativo_descricao
                    || ""
                )}
                            </small>

                        </td>


                        <td>

                            ${badgeTipoManutencaoAtivo(
                    item.tipo_manutencao
                )}

                        </td>


                        <td>
                            ${formatarDataAtivo(
                    item.data_prevista
                )}
                        </td>


                        <td>
                            ${formatarDataAtivo(
                    item.data_inicio
                )}
                        </td>


                        <td>
                            ${formatarDataAtivo(
                    item.data_realizada
                )}
                        </td>


                        <td>
                            ${prestador}
                        </td>


                        <td class="text-right">

                            ${formatarMoedaAtivos(
                    item.custo
                )}

                        </td>


                        <td>
                            ${formatarDataAtivo(
                    item.proxima_manutencao
                )}
                        </td>


                        <td>

                            ${badgeEstadoManutencaoAtivo(
                    item.estado
                )}

                        </td>


                        <td class="text-center">

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                onclick="editarManutencaoAtivo(${item.id})"
                                title="Editar">

                                <i class="fa fa-pencil"></i>

                            </button>

                        </td>

                    </tr>
                `;
            }
        );


    tbody.html(
        linhas.join("")
    );
}

async function editarManutencaoAtivo(
    id
) {

    try {

        await carregarAtivosManutencao();


        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?acao=buscar_manutencao&id="
                + encodeURIComponent(
                    id
                )
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Manutenção não encontrada."
            );
        }


        const item =
            json.data;


        $("#manutencaoId")
            .val(
                item.id
                || ""
            );


        $("#manutencaoAtivo")
            .val(
                item.ativo_id
                || ""
            );


        $("#manutencaoTipo")
            .val(
                item.tipo_manutencao
                || ""
            );


        $("#manutencaoEstado")
            .val(
                item.estado
                || "AGENDADA"
            );


        $("#manutencaoDataPrevista")
            .val(
                item.data_prevista
                || ""
            );


        $("#manutencaoDataInicio")
            .val(
                item.data_inicio
                || ""
            );


        $("#manutencaoDataRealizada")
            .val(
                item.data_realizada
                || ""
            );


        $("#manutencaoProxima")
            .val(
                item.proxima_manutencao
                || ""
            );


        $("#manutencaoFornecedor")
            .val(
                item.fornecedor_id
                || ""
            );


        $("#manutencaoTecnico")
            .val(
                item.tecnico_responsavel
                || ""
            );


        $("#manutencaoCusto")
            .val(
                item.custo
                || 0
            );


        $("#manutencaoDescricao")
            .val(
                item.descricao
                || ""
            );


        $("#manutencaoObservacoes")
            .val(
                item.observacoes
                || ""
            );


        $("#tituloModalManutencaoAtivo")
            .text(
                "Editar Manutenção"
            );


        $("#modalManutencaoAtivo")
            .modal(
                "show"
            );

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}

async function guardarManutencaoAtivo() {

    const botao =
        $("#btnGuardarManutencaoAtivo");


    const htmlOriginal =
        botao.html();


    botao
        .prop(
            "disabled",
            true
        )
        .html(`
            <i class="fa fa-spinner fa-spin mr-1"></i>

            A guardar...
        `);


    try {

        const form =
            document.getElementById(
                "formManutencaoAtivo"
            );


        const dados =
            new FormData(
                form
            );


        dados.set(
            "acao",
            "guardar_manutencao"
        );


        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível guardar a manutenção."
            );
        }


        $("#modalManutencaoAtivo")
            .modal(
                "hide"
            );


        mostrarSucessoAtivos(
            json.mensagem
            || "Manutenção guardada com sucesso."
        );


        await carregarManutencoesAtivos();

        await carregarAtivosMovimentacao();

        recarregarTabelaAtivos();

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );

    } finally {

        botao
            .prop(
                "disabled",
                false
            )
            .html(
                htmlOriginal
            );
    }
}

function badgeTipoManutencaoAtivo(
    tipo
) {

    const tipos = {

        PREVENTIVA:
            "Preventiva",

        CORRETIVA:
            "Corretiva",

        INSPECAO:
            "Inspeção",

        OUTRA:
            "Outra"
    };


    return `
        <span class="badge badge-info">

            ${escaparHtmlAtivos(
        tipos[tipo]
        || tipo
        || "-"
    )}

        </span>
    `;
}


function badgeEstadoManutencaoAtivo(
    estado
) {

    const estados = {

        AGENDADA: {
            texto:
                "Agendada",

            classe:
                "badge-secondary"
        },

        EM_ANDAMENTO: {
            texto:
                "Em andamento",

            classe:
                "badge-warning"
        },

        CONCLUIDA: {
            texto:
                "Concluída",

            classe:
                "badge-success"
        },

        CANCELADA: {
            texto:
                "Cancelada",

            classe:
                "badge-danger"
        }
    };


    const config =
        estados[estado]
        || {
            texto:
                estado || "-",

            classe:
                "badge-light"
        };


    return `
        <span class="badge ${config.classe}">

            ${escaparHtmlAtivos(
        config.texto
    )}

        </span>
    `;
}

function ajustarDatasManutencao() {

    const estado =
        $("#manutencaoEstado")
            .val();


    const hoje =
        new Date()
            .toISOString()
            .slice(
                0,
                10
            );


    if (
        estado === "EM_ANDAMENTO"
        && !$("#manutencaoDataInicio").val()
    ) {

        $("#manutencaoDataInicio")
            .val(
                hoje
            );
    }


    if (
        estado === "CONCLUIDA"
        && !$("#manutencaoDataRealizada").val()
    ) {

        if (
            !$("#manutencaoDataInicio").val()
        ) {

            $("#manutencaoDataInicio")
                .val(
                    hoje
                );
        }


        $("#manutencaoDataRealizada")
            .val(
                hoje
            );
    }
}

// ==================================================
// CARREGAR ATIVOS DISPONÍVEIS
// ==================================================

async function carregarAtivosMovimentacao() {

    try {

        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?acao=ativos_movimentacao"
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível carregar os ativos."
            );
        }


        ativosDisponiveisMovimentacao =
            Array.isArray(json.data)
                ? json.data
                : [];


        preencherSelectAtivosMovimentacao();

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}


// ==================================================
// PREENCHER SELECT DE ATIVOS
// ==================================================

function preencherSelectAtivosMovimentacao() {

    const opcoesModal = [
        '<option value="">Selecione</option>'
    ];


    const opcoesFiltro = [
        '<option value="">Todos</option>'
    ];


    ativosDisponiveisMovimentacao
        .forEach(
            function (ativo) {

                const texto =
                    (
                        ativo.codigo
                        || ""
                    )
                    + " - "
                    + (
                        ativo.descricao
                        || ""
                    );


                opcoesModal.push(`
                    <option value="${ativo.id}">
                        ${escaparHtmlAtivos(texto)}
                    </option>
                `);


                opcoesFiltro.push(`
                    <option value="${ativo.id}">
                        ${escaparHtmlAtivos(texto)}
                    </option>
                `);
            }
        );


    $("#movimentacaoAtivo")
        .html(
            opcoesModal.join("")
        );


    const atual =
        $("#filtroMovimentacaoAtivo")
            .val();


    $("#filtroMovimentacaoAtivo")
        .html(
            opcoesFiltro.join("")
        );


    if (atual) {

        $("#filtroMovimentacaoAtivo")
            .val(atual);
    }
}

function preencherFiltrosMovimentacoes() {

    const filiais = [
        '<option value="">Todas</option>'
    ];


    (
        dadosIniciaisAtivos.filiais
        || []
    )
        .forEach(
            function (item) {

                filiais.push(`
                    <option value="${item.id}">
                        ${escaparHtmlAtivos(
                    item.nome
                )}
                    </option>
                `);
            }
        );


    $("#filtroMovimentacaoFilial")
        .html(
            filiais.join("")
        );


    const departamentos = [
        '<option value="">Todos</option>'
    ];


    (
        dadosIniciaisAtivos.departamentos
        || []
    )
        .forEach(
            function (item) {

                departamentos.push(`
                    <option value="${item.id}">
                        ${escaparHtmlAtivos(
                    item.nome
                )}
                    </option>
                `);
            }
        );


    $("#filtroMovimentacaoDepartamento")
        .html(
            departamentos.join("")
        );
}

async function guardarConfiguracaoContabilAtivo() {

    try {

        const form =
            document.getElementById(
                "formConfiguracaoContabilAtivo"
            );


        const dados =
            new FormData(
                form
            );


        dados.set(
            "acao",
            "guardar_configuracao_contabil"
        );


        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível guardar a configuração."
            );
        }


        mostrarSucessoAtivos(
            json.mensagem
        );


        form.reset();


        await carregarConfiguracoesContabeisAtivos();

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}

async function carregarConfiguracoesContabeisAtivos() {

    try {

        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?acao=configuracoes_contabeis"
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
            );
        }


        const dados =
            json.data || [];


        const linhas =
            dados.map(
                function (item) {

                    return `
                        <tr>

                            <td>
                                <strong>
                                    ${escaparHtmlAtivos(
                        item.conta_ativo_codigo
                        || ""
                    )}
                                </strong>

                                <br>

                                ${escaparHtmlAtivos(
                        item.conta_ativo_nome
                        || ""
                    )}
                            </td>


                            <td>

                                ${escaparHtmlAtivos(
                        (
                            item.conta_acumulada_codigo
                            || ""
                        )
                        + " - "
                        + (
                            item.conta_acumulada_nome
                            || ""
                        )
                    )}

                            </td>


                            <td>

                                ${escaparHtmlAtivos(
                        (
                            item.conta_gasto_codigo
                            || ""
                        )
                        + " - "
                        + (
                            item.conta_gasto_nome
                            || ""
                        )
                    )}

                            </td>


                            <td class="text-center">

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    onclick="editarConfiguracaoContabilAtivo(
                                        ${item.conta_ativo_id},
                                        ${item.conta_acumulada_id},
                                        ${item.conta_gasto_id}
                                    )">

                                    <i class="fa fa-pencil"></i>

                                </button>

                            </td>

                        </tr>
                    `;
                }
            );


        $("#tabelaConfiguracoesContabeisAtivos tbody")
            .html(
                linhas.length
                    ? linhas.join("")
                    : `
                        <tr>
                            <td
                                colspan="4"
                                class="text-center text-muted">

                                Nenhuma configuração encontrada.

                            </td>
                        </tr>
                    `
            );

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}

function editarConfiguracaoContabilAtivo(
    contaAtivoId,
    contaAcumuladaId,
    contaGastoId
) {

    $("#configContaAtivo")
        .val(
            contaAtivoId
        );


    $("#configContaAcumulada")
        .val(
            contaAcumuladaId
        );


    $("#configContaGasto")
        .val(
            contaGastoId
        );
}

// ==================================================
// CARREGAR DEPRECIAÇÕES / AMORTIZAÇÕES
// ==================================================

async function carregarDepreciacoesAtivos() {

    const ano =
        Number(
            $("#depreciacaoAno").val()
            || new Date().getFullYear()
        );


    const mes =
        Number(
            $("#depreciacaoMes").val()
            || (
                new Date().getMonth()
                + 1
            )
        );


    const tipo =
        $("#depreciacaoTipo")
            .val()
        || "";


    const pesquisa =
        $("#depreciacaoPesquisa")
            .val()
        || "";


    const params =
        new URLSearchParams({

            acao:
                "listar_depreciacoes",

            ano:
                String(ano),

            mes:
                String(mes),

            tipo:
                tipo,

            pesquisa:
                pesquisa
        });


    const tbody =
        $("#tabelaDepreciacoesAtivos tbody");


    tbody.html(`
        <tr>

            <td
                colspan="11"
                class="text-center py-4">

                <i class="fa fa-spinner fa-spin mr-1"></i>

                A carregar...

            </td>

        </tr>
    `);


    try {

        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?"
                + params.toString()
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível carregar as depreciações."
            );
        }


        const ativos =
            Array.isArray(
                json.data
            )
                ? json.data
                : [];


        renderizarTabelaDepreciacoes(
            ativos
        );


        atualizarResumoDepreciacoes(
            ativos
        );


        $("#selecionarTodasDepreciacoes")
            .prop(
                "checked",
                false
            );


        atualizarContadorDepreciacoesSelecionadas();

    } catch (erro) {

        tbody.html(`
            <tr>

                <td
                    colspan="11"
                    class="text-center text-danger py-4">

                    ${escaparHtmlAtivos(
            erro.message
        )}

                </td>

            </tr>
        `);


        mostrarErroAtivos(
            erro.message
        );
    }
}

function carregarContasRecebimentoBaixa() {

    const forma =
        $("#baixaFormaRecebimento")
            .val();


    const contas =
        dadosIniciaisAtivos.contas_contabeis
        || [];


    let prefixos = [];


    if (forma === "CAIXA") {

        prefixos = [
            "1.1"
        ];

    } else if (
        forma === "BANCO"
    ) {

        prefixos = [
            "1.2"
        ];

    } else if (
        forma === "CONTA_RECEBER"
    ) {

        prefixos = [
            "4.1",
            "4.5"
        ];
    }


    const opcoes = [
        '<option value="">Selecione</option>'
    ];


    contas
        .filter(
            function (conta) {

                return prefixos.some(
                    function (prefixo) {

                        return String(
                            conta.codigo
                            || ""
                        )
                            .startsWith(
                                prefixo
                            );
                    }
                );
            }
        )
        .forEach(
            function (conta) {

                opcoes.push(`
                    <option value="${conta.id}">
                        ${escaparHtmlAtivos(
                    conta.codigo
                    + " - "
                    + conta.nome
                )}
                    </option>
                `);
            }
        );


    $("#baixaContaRecebimento")
        .html(
            opcoes.join("")
        );
}

// ==================================================
// RENDERIZAR TABELA DE DEPRECIAÇÕES
// ==================================================

function renderizarTabelaDepreciacoes(
    ativos
) {

    const tbody =
        $("#tabelaDepreciacoesAtivos tbody");


    if (!ativos.length) {

        tbody.html(`
            <tr>

                <td
                    colspan="11"
                    class="text-center text-muted py-4">

                    Nenhum ativo encontrado
                    para o período selecionado.

                </td>

            </tr>
        `);

        return;
    }


    const linhas =
        ativos.map(
            function (ativo) {

                const processado =
                    ativo.periodo_processado
                    === true
                    || Number(
                        ativo.periodo_processado
                    ) === 1;


                const saldoDepreciavel =
                    Number(
                        ativo.saldo_depreciavel
                        || 0
                    );


                const podeProcessar =
                    !processado
                    && saldoDepreciavel > 0;


                const tipo =
                    ativo.tipo_processamento
                        === "AMORTIZACAO"
                        ? "Amortização"
                        : "Depreciação";


                let estadoPeriodo = "";
                let botao = "";

                if (processado) {

                    const depreciacaoId =
                        Number(
                            ativo.depreciacao_periodo_id
                            || 0
                        );

                    estadoPeriodo = `
        <span class="badge badge-success">
            Processado
        </span>
    `;

                    botao = `
        <button
            type="button"
            class="btn btn-sm btn-outline-danger"
            onclick="estornarDepreciacaoAtivo(${depreciacaoId})"
            title="Estornar">

            <i class="fa fa-undo"></i>

        </button>
    `;

                } else if (saldoDepreciavel > 0) {

                    estadoPeriodo = `
        <span class="badge badge-warning">
            Pendente
        </span>
    `;

                    botao = `
        <button
            type="button"
            class="btn btn-sm btn-primary"
            onclick="processarDepreciacaoAtivo(${ativo.id})"
            title="Processar">

            <i class="fa fa-calculator"></i>

        </button>
    `;

                } else {

                    estadoPeriodo = `
        <span class="badge badge-secondary">
            Totalmente depreciado
        </span>
    `;

                    botao = `
        <button
            type="button"
            class="btn btn-sm btn-secondary"
            disabled>

            <i class="fa fa-check"></i>

        </button>
    `;
                }


                const checkbox =
                    podeProcessar
                        ? `
            <input
                type="checkbox"
                class="checkDepreciacaoAtivo"
                value="${ativo.id}">
        `
                        : `
            <input
                type="checkbox"
                disabled>
        `;

                return `
                    <tr>

                        <td class="text-center">
                            ${checkbox}
                        </td>


                        <td>

                            <strong>
                                ${escaparHtmlAtivos(
                    ativo.codigo
                    || ""
                )}
                            </strong>

                        </td>


                        <td>

                            <strong>
                                ${escaparHtmlAtivos(
                    ativo.descricao
                    || ""
                )}
                            </strong>

                            <br>

                            <small class="text-muted">

                                ${escaparHtmlAtivos(
                    ativo.conta_codigo
                    || ""
                )}

                                -

                                ${escaparHtmlAtivos(
                    ativo.categoria_nome
                    || ""
                )}

                            </small>

                        </td>


                        <td>

                            ${ativo.tipo === "INTANGIVEL"
                        ? `
                                        <span class="badge badge-info">
                                            ${tipo}
                                        </span>
                                    `
                        : `
                                        <span class="badge badge-primary">
                                            ${tipo}
                                        </span>
                                    `
                    }

                        </td>


                        <td class="text-right">

                            ${formatarMoedaAtivos(
                        ativo.valor_aquisicao
                    )}

                        </td>


                        <td class="text-right">

                            ${formatarMoedaAtivos(
                        ativo.valor_residual
                    )}

                        </td>


                        <td class="text-right font-weight-bold">

                            ${formatarMoedaAtivos(
                        ativo.valor_periodo
                    )}

                        </td>


                        <td class="text-right">

                            ${formatarMoedaAtivos(
                        ativo.valor_acumulado
                    )}

                        </td>


                        <td class="text-right font-weight-bold">

                            ${formatarMoedaAtivos(
                        ativo.valor_liquido
                    )}

                        </td>


                        <td>
                            ${estadoPeriodo}
                        </td>


                        <td class="text-center">
                            ${botao}
                        </td>

                    </tr>
                `;
            }
        );


    tbody.html(
        linhas.join("")
    );
}

async function estornarDepreciacaoAtivo(
    depreciacaoId
) {

    if (
        !depreciacaoId
        || depreciacaoId <= 0
    ) {

        mostrarErroAtivos(
            "Processamento inválido."
        );

        return;
    }


    let motivo =
        "";

    let dataEstorno =
        new Date()
            .toISOString()
            .slice(
                0,
                10
            );


    if (
        typeof Swal
        !== "undefined"
    ) {

        const resultado =
            await Swal.fire({

                icon:
                    "warning",

                title:
                    "Estornar processamento?",

                html: `
                    <div class="text-left">

                        <div class="form-group">

                            <label>
                                Data do estorno
                            </label>

                            <input
                                type="date"
                                id="swalDataEstornoAtivo"
                                class="form-control"
                                value="${dataEstorno}">

                        </div>

                        <div class="form-group">

                            <label>
                                Motivo
                            </label>

                            <textarea
                                id="swalMotivoEstornoAtivo"
                                class="form-control"
                                rows="3"
                                placeholder="Informe o motivo do estorno">
                            </textarea>

                        </div>

                    </div>
                `,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, estornar",

                cancelButtonText:
                    "Cancelar",

                preConfirm:
                    function () {

                        const data =
                            document
                                .getElementById(
                                    "swalDataEstornoAtivo"
                                )
                                .value;


                        const texto =
                            document
                                .getElementById(
                                    "swalMotivoEstornoAtivo"
                                )
                                .value
                                .trim();


                        if (!data) {

                            Swal.showValidationMessage(
                                "Informe a data do estorno."
                            );

                            return false;
                        }


                        if (!texto) {

                            Swal.showValidationMessage(
                                "Informe o motivo do estorno."
                            );

                            return false;
                        }


                        return {
                            data:
                                data,

                            motivo:
                                texto
                        };
                    }
            });


        if (!resultado.isConfirmed) {
            return;
        }


        dataEstorno =
            resultado.value.data;

        motivo =
            resultado.value.motivo;

    } else {

        motivo =
            prompt(
                "Informe o motivo do estorno:"
            )
            || "";


        if (!motivo.trim()) {
            return;
        }
    }


    try {

        const dados =
            new FormData();


        dados.set(
            "acao",
            "estornar_depreciacao"
        );


        dados.set(
            "depreciacao_id",
            String(
                depreciacaoId
            )
        );


        dados.set(
            "data_estorno",
            dataEstorno
        );


        dados.set(
            "motivo",
            motivo
        );


        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível realizar o estorno."
            );
        }


        mostrarSucessoAtivos(
            json.mensagem
            || "Estorno realizado com sucesso."
        );


        await carregarDepreciacoesAtivos();

        recarregarTabelaAtivos();

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}

// ==================================================
// PROCESSAR UM ATIVO
// ==================================================

async function processarDepreciacaoAtivo(
    ativoId
) {

    const ano =
        Number(
            $("#depreciacaoAno").val()
        );


    const mes =
        Number(
            $("#depreciacaoMes").val()
        );


    if (
        !ano
        || mes < 1
        || mes > 12
    ) {

        mostrarErroAtivos(
            "Selecione um período válido."
        );

        return;
    }


    const confirmado =
        await confirmarProcessamentoAtivos(
            "Processar este ativo?",
            "A depreciação/amortização será registada para o período selecionado."
        );


    if (!confirmado) {
        return;
    }


    try {

        const dados =
            new FormData();


        dados.set(
            "acao",
            "processar_depreciacao"
        );


        dados.set(
            "ativo_id",
            String(ativoId)
        );


        dados.set(
            "ano",
            String(ano)
        );


        dados.set(
            "mes",
            String(mes)
        );


        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível processar o ativo."
            );
        }


        mostrarSucessoAtivos(
            json.mensagem
            || "Processamento realizado com sucesso."
        );


        await carregarDepreciacoesAtivos();


        /*
         * Atualiza também os valores da aba Ativos.
         */
        recarregarTabelaAtivos();

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}

async function carregarAtivosBaixa() {

    if (
        !ativosDisponiveisMovimentacao.length
    ) {

        await carregarAtivosMovimentacao();
    }


    const opcoes = [
        '<option value="">Selecione</option>'
    ];


    ativosDisponiveisMovimentacao
        .forEach(
            function (ativo) {

                const texto =
                    (
                        ativo.codigo
                        || ""
                    )
                    + " - "
                    + (
                        ativo.descricao
                        || ""
                    );


                opcoes.push(`
                    <option value="${ativo.id}">
                        ${escaparHtmlAtivos(
                    texto
                )}
                    </option>
                `);
            }
        );


    $("#baixaAtivo")
        .html(
            opcoes.join("")
        );
}

async function abrirNovaBaixaAtivo() {

    await carregarAtivosBaixa();


    const form =
        document.getElementById(
            "formBaixaAtivo"
        );


    if (form) {
        form.reset();
    }


    const hoje =
        new Date()
            .toISOString()
            .slice(
                0,
                10
            );


    $("#baixaData")
        .val(
            hoje
        );


    $("#baixaValorAquisicao")
        .val(
            formatarMoedaAtivos(0)
        );


    $("#baixaDepreciacaoAcumulada")
        .val(
            formatarMoedaAtivos(0)
        );


    $("#baixaValorLiquido")
        .val(
            formatarMoedaAtivos(0)
        );


    $("#baixaValorLiquido")
        .data(
            "valor",
            0
        );


    $("#baixaValorAlienacao")
        .val("")
        .prop(
            "disabled",
            true
        );


    $("#baixaGanhoPerda")
        .text(
            formatarMoedaAtivos(0)
        );


    $("#baixaGanhoPerdaTexto")
        .text(
            "Selecione um ativo."
        );


    $("#modalBaixaAtivo")
        .modal(
            "show"
        );
}

async function carregarDadosAtivoBaixa() {

    const ativoId =
        Number(
            $("#baixaAtivo")
                .val()
            || 0
        );


    if (!ativoId) {

        $("#baixaValorAquisicao")
            .val(
                formatarMoedaAtivos(0)
            );


        $("#baixaDepreciacaoAcumulada")
            .val(
                formatarMoedaAtivos(0)
            );


        $("#baixaValorLiquido")
            .val(
                formatarMoedaAtivos(0)
            )
            .data(
                "valor",
                0
            );


        calcularGanhoPerdaBaixa();

        return;
    }


    try {

        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?acao=dados_ativo_baixa&ativo_id="
                + encodeURIComponent(
                    ativoId
                )
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível obter os dados do ativo."
            );
        }


        const ativo =
            json.data || {};


        $("#baixaValorAquisicao")
            .val(
                formatarMoedaAtivos(
                    ativo.valor_aquisicao
                )
            );


        $("#baixaDepreciacaoAcumulada")
            .val(
                formatarMoedaAtivos(
                    ativo.depreciacao_acumulada
                )
            );


        $("#baixaValorLiquido")
            .val(
                formatarMoedaAtivos(
                    ativo.valor_liquido
                )
            )
            .data(
                "valor",
                Number(
                    ativo.valor_liquido
                    || 0
                )
            );


        calcularGanhoPerdaBaixa();

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}

function ajustarTipoBaixa() {

    const tipo =
        $("#baixaTipo")
            .val();


    const venda =
        tipo === "VENDA";


    $("#baixaValorAlienacao")
        .prop(
            "disabled",
            !venda
        );


    $("#blocoRecebimentoBaixa")
        .toggle(
            venda
        );


    $("#baixaFormaRecebimento")
        .prop(
            "required",
            venda
        );


    $("#baixaContaRecebimento")
        .prop(
            "required",
            venda
        );


    if (!venda) {

        $("#baixaValorAlienacao")
            .val("");

        $("#baixaFormaRecebimento")
            .val("");

        $("#baixaContaRecebimento")
            .html(`
                <option value="">
                    Selecione primeiro a forma
                    de recebimento
                </option>
            `);

    }


    calcularGanhoPerdaBaixa();
}

function calcularGanhoPerdaBaixa() {

    const valorLiquido =
        Number(
            $("#baixaValorLiquido")
                .data(
                    "valor"
                )
            || 0
        );


    const valorAlienacao =
        Number(
            $("#baixaValorAlienacao")
                .val()
            || 0
        );


    const resultado =
        valorAlienacao
        - valorLiquido;


    $("#baixaGanhoPerda")
        .text(
            formatarMoedaAtivos(
                resultado
            )
        );


    if (resultado > 0) {

        $("#baixaGanhoPerdaTexto")
            .text(
                "Ganho na alienação"
            )
            .removeClass(
                "text-danger text-muted"
            )
            .addClass(
                "text-success"
            );

    } else if (
        resultado < 0
    ) {

        $("#baixaGanhoPerdaTexto")
            .text(
                "Perda na baixa/alienação"
            )
            .removeClass(
                "text-success text-muted"
            )
            .addClass(
                "text-danger"
            );

    } else {

        $("#baixaGanhoPerdaTexto")
            .text(
                "Sem ganho nem perda"
            )
            .removeClass(
                "text-success text-danger"
            )
            .addClass(
                "text-muted"
            );
    }
}

async function guardarBaixaAtivo() {

    const ativoId =
        Number(
            $("#baixaAtivo").val()
            || 0
        );


    if (!ativoId) {

        mostrarErroAtivos(
            "Selecione o ativo."
        );

        return;
    }


    const confirmado =
        await confirmarProcessamentoAtivos(
            "Confirmar baixa do ativo?",
            "Esta operação marcará o ativo como vendido ou abatido."
        );


    if (!confirmado) {
        return;
    }


    const botao =
        $("#btnGuardarBaixaAtivo");


    const htmlOriginal =
        botao.html();


    botao
        .prop(
            "disabled",
            true
        )
        .html(`
            <i class="fa fa-spinner fa-spin mr-1"></i>
            A processar...
        `);


    try {

        const form =
            document.getElementById(
                "formBaixaAtivo"
            );


        const dados =
            new FormData(
                form
            );


        dados.set(
            "acao",
            "criar_baixa"
        );


        /*
         * Input disabled não entra no FormData.
         * Para tipos sem alienação, simplesmente
         * não enviamos o campo.
         */

        if (
            $("#baixaTipo").val()
            === "VENDA"
        ) {

            dados.set(
                "valor_alienacao",
                $("#baixaValorAlienacao")
                    .val()
                || "0"
            );
        }


        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível registar a baixa."
            );
        }


        $("#modalBaixaAtivo")
            .modal(
                "hide"
            );


        mostrarSucessoAtivos(
            json.mensagem
            || "Baixa registada com sucesso."
        );


        /*
         * Atualizar tudo porque o ativo
         * deixa de estar disponível.
         */

        ativosDisponiveisMovimentacao =
            [];


        await carregarAtivosMovimentacao();

        await carregarBaixasAtivos();

        recarregarTabelaAtivos();

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );

    } finally {

        botao
            .prop(
                "disabled",
                false
            )
            .html(
                htmlOriginal
            );
    }
}

async function carregarBaixasAtivos() {

    const params =
        new URLSearchParams({

            acao:
                "listar_baixas",

            tipo:
                $("#filtroBaixaTipo")
                    .val()
                || "",

            data_inicial:
                $("#filtroBaixaDataInicial")
                    .val()
                || "",

            data_final:
                $("#filtroBaixaDataFinal")
                    .val()
                || "",

            pesquisa:
                $("#filtroBaixaPesquisa")
                    .val()
                || ""
        });


    const tbody =
        $("#tabelaBaixasAtivos tbody");


    tbody.html(`
        <tr>
            <td
                colspan="10"
                class="text-center py-4">

                <i class="fa fa-spinner fa-spin mr-1"></i>

                A carregar...

            </td>
        </tr>
    `);


    try {

        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?"
                + params.toString()
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível carregar as baixas."
            );
        }


        renderizarBaixasAtivos(
            Array.isArray(json.data)
                ? json.data
                : []
        );

    } catch (erro) {

        tbody.html(`
            <tr>
                <td
                    colspan="10"
                    class="text-center text-danger py-4">

                    ${escaparHtmlAtivos(
            erro.message
        )}

                </td>
            </tr>
        `);


        mostrarErroAtivos(
            erro.message
        );
    }
}

function renderizarBaixasAtivos(
    dados
) {

    const tbody =
        $("#tabelaBaixasAtivos tbody");


    if (!dados.length) {

        tbody.html(`
            <tr>
                <td
                    colspan="10"
                    class="text-center text-muted py-4">

                    Nenhuma baixa/alienação encontrada.

                </td>
            </tr>
        `);

        return;
    }


    const linhas =
        dados.map(
            function (item) {

                return `
                    <tr>

                        <td>
                            ${formatarDataAtivo(
                    item.data_baixa
                )}
                        </td>


                        <td>

                            <strong>
                                ${escaparHtmlAtivos(
                    item.ativo_codigo
                    || ""
                )}
                            </strong>

                            <br>

                            <small class="text-muted">
                                ${escaparHtmlAtivos(
                    item.ativo_descricao
                    || ""
                )}
                            </small>

                        </td>


                        <td>
                            ${badgeTipoBaixaAtivo(
                    item.tipo
                )}
                        </td>


                        <td class="text-right">

                            ${formatarMoedaAtivos(
                    item.valor_aquisicao
                )}

                        </td>


                        <td class="text-right">

                            ${formatarMoedaAtivos(
                    item.depreciacao_acumulada
                )}

                        </td>


                        <td class="text-right">

                            ${formatarMoedaAtivos(
                    item.valor_liquido
                )}

                        </td>


                        <td class="text-right">

                            ${item.valor_alienacao !== null
                        && item.valor_alienacao !== ""
                        ? formatarMoedaAtivos(
                            item.valor_alienacao
                        )
                        : "-"
                    }

                        </td>


                        <td class="text-right">

                            ${formatarGanhoPerdaBaixa(
                        item.ganho_perda
                    )}

                        </td>


                        <td>
                            ${escaparHtmlAtivos(
                        item.documento
                        || "-"
                    )}
                        </td>


                        <td>
                            ${escaparHtmlAtivos(
                        item.usuario_nome
                        || "-"
                    )}
                        </td>

                    </tr>
                `;
            }
        );


    tbody.html(
        linhas.join("")
    );
}

function badgeTipoBaixaAtivo(
    tipo
) {

    const tipos = {

        VENDA:
            "Venda",

        ABATE:
            "Abate",

        PERDA:
            "Perda",

        ROUBO:
            "Roubo",

        DOACAO:
            "Doação",

        FIM_VIDA_UTIL:
            "Fim de Vida Útil",

        OUTRO:
            "Outro"
    };


    let classe =
        "badge-secondary";


    if (tipo === "VENDA") {

        classe =
            "badge-success";

    } else if (
        tipo === "ROUBO"
        || tipo === "PERDA"
    ) {

        classe =
            "badge-danger";

    } else if (
        tipo === "DOACAO"
    ) {

        classe =
            "badge-info";
    }


    return `
        <span class="badge ${classe}">

            ${escaparHtmlAtivos(
        tipos[tipo]
        || tipo
        || "-"
    )}

        </span>
    `;
}


function formatarGanhoPerdaBaixa(
    valor
) {

    const numero =
        Number(
            valor
            || 0
        );


    if (numero > 0) {

        return `
            <span class="text-success font-weight-bold">

                +${formatarMoedaAtivos(
            numero
        )}

            </span>
        `;
    }


    if (numero < 0) {

        return `
            <span class="text-danger font-weight-bold">

                ${formatarMoedaAtivos(
            numero
        )}

            </span>
        `;
    }


    return `
        <span class="text-muted">
            ${formatarMoedaAtivos(0)}
        </span>
    `;
}

// ==================================================
// PROCESSAR ATIVOS SELECIONADOS
// ==================================================

async function processarDepreciacoesSelecionadas() {

    const ativos =
        [];


    $(".checkDepreciacaoAtivo:checked")
        .each(
            function () {

                const id =
                    Number(
                        $(this).val()
                    );


                if (id > 0) {

                    ativos.push(
                        id
                    );
                }
            }
        );


    if (!ativos.length) {

        mostrarErroAtivos(
            "Selecione pelo menos um ativo."
        );

        return;
    }


    const ano =
        Number(
            $("#depreciacaoAno").val()
        );


    const mes =
        Number(
            $("#depreciacaoMes").val()
        );


    if (
        !ano
        || mes < 1
        || mes > 12
    ) {

        mostrarErroAtivos(
            "Selecione um período válido."
        );

        return;
    }


    const confirmado =
        await confirmarProcessamentoAtivos(
            "Processar ativos selecionados?",

            ativos.length
            + (
                ativos.length === 1
                    ? " ativo será processado."
                    : " ativos serão processados."
            )
        );


    if (!confirmado) {
        return;
    }


    const botao =
        $("#btnProcessarDepreciacoesSelecionadas");


    const htmlOriginal =
        botao.html();


    botao
        .prop(
            "disabled",
            true
        )
        .html(
            `
            <i class="fa fa-spinner fa-spin mr-1"></i>
            A processar...
            `
        );


    try {

        const dados =
            new FormData();


        dados.set(
            "acao",
            "processar_depreciacoes_lote"
        );


        dados.set(
            "ano",
            String(ano)
        );


        dados.set(
            "mes",
            String(mes)
        );


        /*
         * O Controller já está preparado
         * para receber JSON neste campo.
         */

        dados.set(
            "ativos",
            JSON.stringify(
                ativos
            )
        );


        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível processar os ativos."
            );
        }


        const resultado =
            json.data || {};


        await mostrarResultadoProcessamentoLote(
            resultado
        );


        await carregarDepreciacoesAtivos();


        recarregarTabelaAtivos();

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );

    } finally {

        botao
            .prop(
                "disabled",
                false
            )
            .html(
                htmlOriginal
            );
    }
}

// ==================================================
// RESULTADO DO PROCESSAMENTO EM LOTE
// ==================================================

async function mostrarResultadoProcessamentoLote(
    resultado
) {

    const processados =
        Number(
            resultado.total_processados
            || 0
        );


    const ignorados =
        Number(
            resultado.total_ignorados
            || 0
        );


    const erros =
        Number(
            resultado.total_erros
            || 0
        );


    let texto =
        "Processados: "
        + processados
        + "\n"
        + "Ignorados: "
        + ignorados
        + "\n"
        + "Erros: "
        + erros;


    if (
        typeof Swal
        !== "undefined"
    ) {

        let html = `
            <div class="text-left">

                <p>
                    <strong>Processados:</strong>
                    ${processados}
                </p>

                <p>
                    <strong>Ignorados:</strong>
                    ${ignorados}
                </p>

                <p>
                    <strong>Erros:</strong>
                    ${erros}
                </p>
        `;


        const listaIgnorados =
            Array.isArray(
                resultado.ignorados
            )
                ? resultado.ignorados
                : [];


        if (listaIgnorados.length) {

            html += `
                <hr>

                <strong>
                    Ignorados
                </strong>

                <ul class="mt-2">
            `;


            listaIgnorados
                .forEach(
                    function (item) {

                        html += `
                            <li>
                                Ativo #${escaparHtmlAtivos(
                            item.ativo_id
                        )}:
                                ${escaparHtmlAtivos(
                            item.mensagem
                            || ""
                        )}
                            </li>
                        `;
                    }
                );


            html += `
                </ul>
            `;
        }


        const listaErros =
            Array.isArray(
                resultado.erros
            )
                ? resultado.erros
                : [];


        if (listaErros.length) {

            html += `
                <hr>

                <strong class="text-danger">
                    Erros
                </strong>

                <ul class="mt-2">
            `;


            listaErros
                .forEach(
                    function (item) {

                        html += `
                            <li>
                                Ativo #${escaparHtmlAtivos(
                            item.ativo_id
                        )}:
                                ${escaparHtmlAtivos(
                            item.mensagem
                            || ""
                        )}
                            </li>
                        `;
                    }
                );


            html += `
                </ul>
            `;
        }


        html += `
            </div>
        `;


        await Swal.fire({

            icon:
                erros > 0
                    ? "warning"
                    : "success",

            title:
                "Processamento concluído",

            html:
                html
        });


        return;
    }


    alert(
        texto
    );
}

// ==================================================
// CONTROLO DA SELEÇÃO
// ==================================================

function atualizarEstadoSelecionarTodasDepreciacoes() {

    const disponiveis =
        $(
            ".checkDepreciacaoAtivo:not(:disabled)"
        );


    const selecionados =
        $(
            ".checkDepreciacaoAtivo:not(:disabled):checked"
        );


    $("#selecionarTodasDepreciacoes")
        .prop(
            "checked",
            disponiveis.length > 0
            && disponiveis.length
            === selecionados.length
        );
}


function atualizarContadorDepreciacoesSelecionadas() {

    const quantidade =
        $(
            ".checkDepreciacaoAtivo:checked"
        ).length;


    const botao =
        $("#btnProcessarDepreciacoesSelecionadas");


    botao
        .prop(
            "disabled",
            quantidade === 0
        );


    if (quantidade > 0) {

        botao.html(
            `
            <i class="fa fa-calculator mr-1"></i>

            Processar Selecionados
            (${quantidade})
            `
        );

    } else {

        botao.html(
            `
            <i class="fa fa-calculator mr-1"></i>

            Processar Selecionados
            `
        );
    }
}

// ==================================================
// CONFIRMAÇÃO
// ==================================================

async function confirmarProcessamentoAtivos(
    titulo,
    mensagem
) {

    if (
        typeof Swal
        !== "undefined"
    ) {

        const resultado =
            await Swal.fire({

                icon:
                    "question",

                title:
                    titulo,

                text:
                    mensagem,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, processar",

                cancelButtonText:
                    "Cancelar"
            });


        return resultado.isConfirmed;
    }


    return window.confirm(
        mensagem
    );
}

// ==================================================
// RESUMO DA ABA
// ==================================================

function atualizarResumoDepreciacoes(
    ativos
) {

    let pendentes =
        0;

    let processados =
        0;

    let valorPrevisto =
        0;


    ativos.forEach(
        function (ativo) {

            const processado =
                ativo.periodo_processado
                === true
                || Number(
                    ativo.periodo_processado
                ) === 1;


            if (processado) {

                processados++;

            } else if (
                Number(
                    ativo.saldo_depreciavel
                    || 0
                ) > 0
            ) {

                pendentes++;

                valorPrevisto +=
                    Number(
                        ativo.valor_periodo
                        || 0
                    );
            }
        }
    );


    $("#depreciacaoResumoElegiveis")
        .text(
            ativos.length
        );


    $("#depreciacaoResumoPendentes")
        .text(
            pendentes
        );


    $("#depreciacaoResumoProcessados")
        .text(
            processados
        );


    $("#depreciacaoResumoValor")
        .text(
            formatarMoedaAtivos(
                valorPrevisto
            )
        );
}


// ==================================================
// DADOS INICIAIS
// ==================================================

async function carregarDadosIniciaisAtivos() {

    try {

        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?acao=dados_iniciais"
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível carregar os dados iniciais."
            );
        }


        dadosIniciaisAtivos =
            json.data || {};


        preencherFiliaisAtivos();

        preencherDepartamentosAtivos();

        preencherFornecedoresAtivos();

        preencherFiltrosMovimentacoes();

        atualizarCategoriasFiltro();

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}

async function abrirNovaMovimentacaoAtivo() {

    if (
        !ativosDisponiveisMovimentacao.length
    ) {

        await carregarAtivosMovimentacao();
    }


    const form =
        document.getElementById(
            "formMovimentacaoAtivo"
        );


    if (form) {
        form.reset();
    }


    $("#movimentacaoFilialAtual")
        .val("");

    $("#movimentacaoDepartamentoAtual")
        .val("");

    $("#movimentacaoLocalizacaoAtual")
        .val("");

    $("#movimentacaoResponsavelAtual")
        .val("");


    const hoje =
        new Date()
            .toISOString()
            .slice(
                0,
                10
            );


    $("#movimentacaoData")
        .val(
            hoje
        );


    preencherDestinoMovimentacao();


    $("#modalMovimentacaoAtivo")
        .modal(
            "show"
        );
}

function preencherDestinoMovimentacao() {

    const filiais = [
        '<option value="">Manter atual</option>'
    ];


    (
        dadosIniciaisAtivos.filiais
        || []
    )
        .forEach(
            function (item) {

                filiais.push(`
                    <option value="${item.id}">
                        ${escaparHtmlAtivos(
                    item.nome
                )}
                    </option>
                `);
            }
        );


    $("#movimentacaoFilialDestino")
        .html(
            filiais.join("")
        );


    const departamentos = [
        '<option value="">Manter atual</option>'
    ];


    (
        dadosIniciaisAtivos.departamentos
        || []
    )
        .forEach(
            function (item) {

                departamentos.push(`
                    <option value="${item.id}">
                        ${escaparHtmlAtivos(
                    item.nome
                )}
                    </option>
                `);
            }
        );


    $("#movimentacaoDepartamentoDestino")
        .html(
            departamentos.join("")
        );
}

function preencherOrigemMovimentacao() {

    const ativoId =
        Number(
            $("#movimentacaoAtivo")
                .val()
            || 0
        );


    const ativo =
        ativosDisponiveisMovimentacao
            .find(
                function (item) {

                    return Number(
                        item.id
                    ) === ativoId;
                }
            );


    if (!ativo) {

        $("#movimentacaoFilialAtual")
            .val("");

        $("#movimentacaoDepartamentoAtual")
            .val("");

        $("#movimentacaoLocalizacaoAtual")
            .val("");

        $("#movimentacaoResponsavelAtual")
            .val("");

        return;
    }


    $("#movimentacaoFilialAtual")
        .val(
            ativo.filial_nome
            || "-"
        );


    $("#movimentacaoDepartamentoAtual")
        .val(
            ativo.departamento_nome
            || "-"
        );


    $("#movimentacaoLocalizacaoAtual")
        .val(
            ativo.localizacao
            || "-"
        );


    $("#movimentacaoResponsavelAtual")
        .val(
            ativo.responsavel
            || "-"
        );
}

async function guardarMovimentacaoAtivo() {

    const botao =
        $("#btnGuardarMovimentacaoAtivo");


    const htmlOriginal =
        botao.html();


    botao
        .prop(
            "disabled",
            true
        )
        .html(`
            <i class="fa fa-spinner fa-spin mr-1"></i>
            A guardar...
        `);


    try {

        const form =
            document.getElementById(
                "formMovimentacaoAtivo"
            );


        const dados =
            new FormData(
                form
            );


        dados.set(
            "acao",
            "criar_movimentacao"
        );


        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível guardar a movimentação."
            );
        }


        $("#modalMovimentacaoAtivo")
            .modal(
                "hide"
            );


        mostrarSucessoAtivos(
            json.mensagem
            || "Movimentação registada com sucesso."
        );


        await carregarAtivosMovimentacao();

        await carregarMovimentacoesAtivos();

        recarregarTabelaAtivos();

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );

    } finally {

        botao
            .prop(
                "disabled",
                false
            )
            .html(
                htmlOriginal
            );
    }
}

async function carregarMovimentacoesAtivos() {

    const params =
        new URLSearchParams({

            acao:
                "listar_movimentacoes",

            ativo_id:
                $("#filtroMovimentacaoAtivo")
                    .val()
                || "",

            tipo_movimentacao:
                $("#filtroMovimentacaoTipo")
                    .val()
                || "",

            filial_id:
                $("#filtroMovimentacaoFilial")
                    .val()
                || "",

            departamento_id:
                $("#filtroMovimentacaoDepartamento")
                    .val()
                || "",

            data_inicial:
                $("#filtroMovimentacaoDataInicial")
                    .val()
                || "",

            data_final:
                $("#filtroMovimentacaoDataFinal")
                    .val()
                || "",

            pesquisa:
                $("#filtroMovimentacaoPesquisa")
                    .val()
                || ""
        });


    const tbody =
        $("#tabelaMovimentacoesAtivos tbody");


    tbody.html(`
        <tr>
            <td
                colspan="9"
                class="text-center py-4">

                <i class="fa fa-spinner fa-spin mr-1"></i>

                A carregar...

            </td>
        </tr>
    `);


    try {

        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?"
                + params.toString()
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível carregar as movimentações."
            );
        }


        const dados =
            Array.isArray(json.data)
                ? json.data
                : [];


        renderizarMovimentacoesAtivos(
            dados
        );

    } catch (erro) {

        tbody.html(`
            <tr>
                <td
                    colspan="9"
                    class="text-center text-danger py-4">

                    ${escaparHtmlAtivos(
            erro.message
        )}

                </td>
            </tr>
        `);
    }
}

function renderizarMovimentacoesAtivos(
    dados
) {

    const tbody =
        $("#tabelaMovimentacoesAtivos tbody");


    if (!dados.length) {

        tbody.html(`
            <tr>

                <td
                    colspan="9"
                    class="text-center text-muted py-4">

                    Nenhuma movimentação encontrada.

                </td>

            </tr>
        `);

        return;
    }


    const linhas =
        dados.map(
            function (item) {

                const filial =
                    montarOrigemDestinoAtivo(
                        item.filial_origem_nome,
                        item.filial_destino_nome
                    );


                const departamento =
                    montarOrigemDestinoAtivo(
                        item.departamento_origem_nome,
                        item.departamento_destino_nome
                    );


                const localizacao =
                    montarOrigemDestinoAtivo(
                        item.localizacao_origem,
                        item.localizacao_destino
                    );


                const responsavel =
                    montarOrigemDestinoAtivo(
                        item.responsavel_origem,
                        item.responsavel_destino
                    );


                return `
                    <tr>

                        <td>
                            ${formatarDataAtivo(
                    item.data_movimentacao
                )}
                        </td>


                        <td>

                            <strong>
                                ${escaparHtmlAtivos(
                    item.ativo_codigo
                    || ""
                )}
                            </strong>

                            <br>

                            <small>
                                ${escaparHtmlAtivos(
                    item.ativo_descricao
                    || ""
                )}
                            </small>

                        </td>


                        <td>
                            ${badgeTipoMovimentacaoAtivo(
                    item.tipo_movimentacao
                )}
                        </td>


                        <td>
                            ${filial}
                        </td>


                        <td>
                            ${departamento}
                        </td>


                        <td>
                            ${localizacao}
                        </td>


                        <td>
                            ${responsavel}
                        </td>


                        <td>
                            ${escaparHtmlAtivos(
                    item.motivo
                    || "-"
                )}
                        </td>


                        <td>
                            ${escaparHtmlAtivos(
                    item.usuario_nome
                    || "-"
                )}
                        </td>

                    </tr>
                `;
            }
        );


    tbody.html(
        linhas.join("")
    );
}

function montarOrigemDestinoAtivo(
    origem,
    destino
) {

    const textoOrigem =
        origem
        || "-";


    const textoDestino =
        destino
        || "-";


    if (
        textoOrigem
        === textoDestino
    ) {

        return escaparHtmlAtivos(
            textoDestino
        );
    }


    return `
        <small class="text-muted">
            ${escaparHtmlAtivos(
        textoOrigem
    )}
        </small>

        <i class="fa fa-long-arrow-right mx-1"></i>

        <strong>
            ${escaparHtmlAtivos(
        textoDestino
    )}
        </strong>
    `;
}


function badgeTipoMovimentacaoAtivo(
    tipo
) {

    const tipos = {

        ENTRADA:
            "Entrada",

        TRANSFERENCIA:
            "Transferência",

        ALTERACAO_RESPONSAVEL:
            "Responsável",

        ALTERACAO_LOCALIZACAO:
            "Localização",

        CEDENCIA:
            "Cedência",

        DEVOLUCAO:
            "Devolução"
    };


    return `
        <span class="badge badge-info">

            ${escaparHtmlAtivos(
        tipos[tipo]
        || tipo
        || "-"
    )}

        </span>
    `;
}


function formatarDataAtivo(
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
        return data;
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
// FILIAIS
// ==================================================

function preencherFiliaisAtivos() {

    const opcoes =
        [
            '<option value="">Selecione</option>'
        ];


    (
        dadosIniciaisAtivos.filiais
        || []
    )
        .forEach(
            function (filial) {

                opcoes.push(
                    `
                    <option value="${filial.id}">
                        ${escaparHtmlAtivos(
                        filial.codigo
                            ? filial.codigo
                            + " - "
                            + filial.nome
                            : filial.nome
                    )}
                    </option>
                    `
                );
            }
        );


    $("#ativoFilial")
        .html(
            opcoes.join("")
        );


    const filtro =
        [
            '<option value="">Todas</option>'
        ];


    (
        dadosIniciaisAtivos.filiais
        || []
    )
        .forEach(
            function (filial) {

                filtro.push(
                    `
                    <option value="${filial.id}">
                        ${escaparHtmlAtivos(
                        filial.nome
                    )}
                    </option>
                    `
                );
            }
        );


    $("#filtroAtivoFilial")
        .html(
            filtro.join("")
        );
}


// ==================================================
// DEPARTAMENTOS
// ==================================================

function preencherDepartamentosAtivos() {

    const opcoes =
        [
            '<option value="">Selecione</option>'
        ];


    (
        dadosIniciaisAtivos.departamentos
        || []
    )
        .forEach(
            function (departamento) {

                opcoes.push(
                    `
                    <option value="${departamento.id}">
                        ${escaparHtmlAtivos(
                        departamento.nome
                    )}
                    </option>
                    `
                );
            }
        );


    $("#ativoDepartamento")
        .html(
            opcoes.join("")
        );


    const filtro =
        [
            '<option value="">Todos</option>'
        ];


    (
        dadosIniciaisAtivos.departamentos
        || []
    )
        .forEach(
            function (departamento) {

                filtro.push(
                    `
                    <option value="${departamento.id}">
                        ${escaparHtmlAtivos(
                        departamento.nome
                    )}
                    </option>
                    `
                );
            }
        );


    $("#filtroAtivoDepartamento")
        .html(
            filtro.join("")
        );
}


// ==================================================
// FORNECEDORES
// ==================================================

function preencherFornecedoresAtivos() {

    const opcoes =
        [
            '<option value="">Selecione</option>'
        ];


    (
        dadosIniciaisAtivos.fornecedores
        || []
    )
        .forEach(
            function (fornecedor) {

                opcoes.push(
                    `
                    <option value="${fornecedor.id}">
                        ${escaparHtmlAtivos(
                        fornecedor.nome
                    )}
                    </option>
                    `
                );
            }
        );


    $("#ativoFornecedor")
        .html(
            opcoes.join("")
        );
}


// ==================================================
// CATEGORIAS DO MODAL
// ==================================================

function atualizarCategoriasAtivo(
    valorSelecionado = ""
) {

    const tipo =
        String(
            $("#ativoTipo").val()
            || ""
        )
            .toUpperCase();


    let categorias =
        [];


    if (tipo === "TANGIVEL") {

        categorias =
            dadosIniciaisAtivos
                .categorias_tangiveis
            || [];

    } else if (
        tipo === "INTANGIVEL"
    ) {

        categorias =
            dadosIniciaisAtivos
                .categorias_intangiveis
            || [];
    }


    if (!tipo) {

        $("#ativoContaContabil")
            .html(`
            <option value="">
                Primeiro selecione o tipo de ativo
            </option>
        `);

        return;
    }


    $("#ativoContaContabil")
        .html(
            montarOpcoesContabeisAgrupadas(
                categorias,
                "Selecione a categoria",
                valorSelecionado
            )
        );
}


// ==================================================
// CATEGORIAS DO FILTRO
// ==================================================

function atualizarCategoriasFiltro() {

    const tipo =
        String(
            $("#filtroAtivoTipo").val()
            || ""
        )
            .toUpperCase();


    let categorias =
        [];


    if (tipo === "TANGIVEL") {

        categorias =
            dadosIniciaisAtivos
                .categorias_tangiveis
            || [];

    } else if (
        tipo === "INTANGIVEL"
    ) {

        categorias =
            dadosIniciaisAtivos
                .categorias_intangiveis
            || [];

    } else {

        categorias = [
            ...(
                dadosIniciaisAtivos
                    .categorias_tangiveis
                || []
            ),

            ...(
                dadosIniciaisAtivos
                    .categorias_intangiveis
                || []
            )
        ];
    }


    const atual =
        $("#filtroAtivoCategoria")
            .val();


    const opcoes =
        [
            '<option value="">Todas</option>'
        ];


    categorias.forEach(
        function (categoria) {

            opcoes.push(
                `
                <option value="${categoria.id}">
                    ${escaparHtmlAtivos(
                    categoria.codigo
                    + " - "
                    + categoria.nome
                )}
                </option>
                `
            );
        }
    );


    $("#filtroAtivoCategoria")
        .html(
            opcoes.join("")
        );


    if (
        atual
        && $(
            "#filtroAtivoCategoria "
            + `option[value="${atual}"]`
        ).length
    ) {

        $("#filtroAtivoCategoria")
            .val(
                atual
            );
    }
}


// ==================================================
// TABELA
// ==================================================

function inicializarTabelaAtivos() {

    if (
        !$.fn.DataTable
        || !document.getElementById(
            "tabelaAtivosEmpresa"
        )
    ) {

        carregarTabelaAtivosManual();

        return;
    }


    tabelaAtivosEmpresa =
        $("#tabelaAtivosEmpresa")
            .DataTable({

                processing:
                    true,

                serverSide:
                    false,

                searching:
                    false,

                ordering:
                    true,

                responsive:
                    false,

                autoWidth:
                    false,

                pageLength:
                    10,

                language: {
                    processing: "A processar...",
                    search: "Pesquisar:",
                    lengthMenu: "Mostrar _MENU_ registos",
                    info: "A mostrar _START_ a _END_ de _TOTAL_ registos",
                    infoEmpty: "A mostrar 0 a 0 de 0 registos",
                    infoFiltered: "(filtrado de _MAX_ registos no total)",
                    loadingRecords: "A carregar...",
                    zeroRecords: "Nenhum registo encontrado",
                    emptyTable: "Nenhum dado disponível na tabela",

                    paginate: {
                        first: "Primeiro",
                        previous: "Anterior",
                        next: "Seguinte",
                        last: "Último"
                    },

                    aria: {
                        sortAscending:
                            ": ativar para ordenar a coluna de forma crescente",

                        sortDescending:
                            ": ativar para ordenar a coluna de forma decrescente"
                    }
                },

                ajax: {

                    url:
                        ATIVOS_EMPRESA_URL,

                    type:
                        "GET",

                    data:
                        function (dados) {

                            dados.acao =
                                "listar";

                            dados.tipo =
                                $(
                                    "#filtroAtivoTipo"
                                ).val();

                            dados.conta_contabil_id =
                                $(
                                    "#filtroAtivoCategoria"
                                ).val();

                            dados.filial_id =
                                $(
                                    "#filtroAtivoFilial"
                                ).val();

                            dados.departamento_id =
                                $(
                                    "#filtroAtivoDepartamento"
                                ).val();

                            dados.situacao =
                                $(
                                    "#filtroAtivoSituacao"
                                ).val();

                            dados.pesquisa =
                                $(
                                    "#filtroAtivoPesquisa"
                                ).val();
                        },

                    dataSrc:
                        function (json) {

                            if (!json.success) {

                                mostrarErroAtivos(
                                    json.mensagem
                                    || "Não foi possível carregar os ativos."
                                );

                                return [];
                            }


                            atualizarResumoAtivos(
                                json.data
                                || []
                            );


                            return json.data
                                || [];
                        }
                },

                columns: [

                    {
                        data:
                            "codigo"
                    },

                    {
                        data:
                            null,

                        render:
                            function (
                                data,
                                type,
                                row
                            ) {

                                let html =
                                    `
                                    <strong>
                                        ${escaparHtmlAtivos(
                                        row.descricao
                                        || ""
                                    )}
                                    </strong>
                                    `;


                                const complemento =
                                    [
                                        row.marca,
                                        row.modelo,
                                        row.numero_serie
                                    ]
                                        .filter(
                                            Boolean
                                        )
                                        .join(
                                            " · "
                                        );


                                if (
                                    complemento
                                ) {

                                    html +=
                                        `
                                        <br>
                                        <small class="text-muted">
                                            ${escaparHtmlAtivos(
                                            complemento
                                        )}
                                        </small>
                                        `;
                                }


                                return html;
                            }
                    },

                    {
                        data:
                            "tipo",

                        render:
                            function (valor) {

                                if (
                                    valor
                                    === "TANGIVEL"
                                ) {

                                    return `
                                        <span class="badge badge-primary">
                                            Tangível
                                        </span>
                                    `;
                                }


                                return `
                                    <span class="badge badge-info">
                                        Intangível
                                    </span>
                                `;
                            }
                    },

                    {
                        data:
                            null,

                        render:
                            function (
                                data,
                                type,
                                row
                            ) {

                                return `
                                    <small class="text-muted">
                                        ${escaparHtmlAtivos(
                                    row.conta_codigo
                                    || ""
                                )}
                                    </small>
                                    <br>
                                    ${escaparHtmlAtivos(
                                    row.categoria_nome
                                    || ""
                                )}
                                `;
                            }
                    },

                    {
                        data:
                            "valor_aquisicao",

                        className:
                            "text-right",

                        render:
                            function (valor) {

                                return formatarMoedaAtivos(
                                    valor
                                );
                            }
                    },

                    {
                        data:
                            "depreciacao_acumulada",

                        className:
                            "text-right",

                        render:
                            function (valor) {

                                return formatarMoedaAtivos(
                                    valor
                                );
                            }
                    },

                    {
                        data:
                            "valor_liquido",

                        className:
                            "text-right font-weight-bold",

                        render:
                            function (valor) {

                                return formatarMoedaAtivos(
                                    valor
                                );
                            }
                    },

                    {
                        data:
                            "departamento_nome",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "localizacao",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "situacao",

                        render:
                            function (valor) {

                                return badgeSituacaoAtivo(
                                    valor
                                );
                            }
                    },

                    {
                        data:
                            null,

                        orderable:
                            false,

                        searchable:
                            false,

                        className:
                            "text-center",

                        render:
                            function (
                                data,
                                type,
                                row
                            ) {

                                return `
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary mr-1"
                                        onclick="editarAtivoEmpresa(${row.id})"
                                        title="Editar">

                                        <i class="fa fa-pencil"></i>

                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        onclick="verAtivoEmpresa(${row.id})"
                                        title="Visualizar">

                                        <i class="fa fa-eye"></i>

                                    </button>
                                `;
                            }
                    }
                ]
            });
}


// ==================================================
// TABELA SEM DATATABLE
// ==================================================

async function carregarTabelaAtivosManual() {

    try {

        const params =
            new URLSearchParams({

                acao:
                    "listar",

                tipo:
                    $("#filtroAtivoTipo").val()
                    || "",

                conta_contabil_id:
                    $("#filtroAtivoCategoria").val()
                    || "",

                filial_id:
                    $("#filtroAtivoFilial").val()
                    || "",

                departamento_id:
                    $("#filtroAtivoDepartamento").val()
                    || "",

                situacao:
                    $("#filtroAtivoSituacao").val()
                    || "",

                pesquisa:
                    $("#filtroAtivoPesquisa").val()
                    || ""
            });


        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?"
                + params.toString()
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
            );
        }


        const dados =
            json.data || [];


        atualizarResumoAtivos(
            dados
        );


        const linhas =
            dados.map(
                function (row) {

                    return `
                        <tr>

                            <td>
                                ${escaparHtmlAtivos(
                        row.codigo
                    )}
                            </td>

                            <td>
                                ${escaparHtmlAtivos(
                        row.descricao
                    )}
                            </td>

                            <td>
                                ${escaparHtmlAtivos(
                        row.tipo
                    )}
                            </td>

                            <td>
                                ${escaparHtmlAtivos(
                        row.categoria_nome
                        || ""
                    )}
                            </td>

                            <td class="text-right">
                                ${formatarMoedaAtivos(
                        row.valor_aquisicao
                    )}
                            </td>

                            <td class="text-right">
                                ${formatarMoedaAtivos(
                        row.depreciacao_acumulada
                    )}
                            </td>

                            <td class="text-right">
                                ${formatarMoedaAtivos(
                        row.valor_liquido
                    )}
                            </td>

                            <td>
                                ${escaparHtmlAtivos(
                        row.departamento_nome
                        || "-"
                    )}
                            </td>

                            <td>
                                ${escaparHtmlAtivos(
                        row.localizacao
                        || "-"
                    )}
                            </td>

                            <td>
                                ${badgeSituacaoAtivo(
                        row.situacao
                    )}
                            </td>

                            <td class="text-center">

                                <button
                                    class="btn btn-sm btn-outline-primary"
                                    onclick="editarAtivoEmpresa(${row.id})">

                                    <i class="fa fa-pencil"></i>

                                </button>

                            </td>

                        </tr>
                    `;
                }
            );


        $("#tabelaAtivosEmpresa tbody")
            .html(
                linhas.join("")
            );

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}


// ==================================================
// RECARREGAR
// ==================================================

function recarregarTabelaAtivos() {

    if (
        tabelaAtivosEmpresa
    ) {

        tabelaAtivosEmpresa
            .ajax
            .reload(
                null,
                false
            );

        return;
    }


    carregarTabelaAtivosManual();
}


// ==================================================
// RESUMO
// ==================================================

function atualizarResumoAtivos(
    dados
) {

    let valorAquisicao =
        0;

    let depreciacao =
        0;

    let valorLiquido =
        0;


    dados.forEach(
        function (ativo) {

            valorAquisicao +=
                Number(
                    ativo.valor_aquisicao
                    || 0
                );

            depreciacao +=
                Number(
                    ativo.depreciacao_acumulada
                    || 0
                );

            valorLiquido +=
                Number(
                    ativo.valor_liquido
                    || 0
                );
        }
    );


    $("#resumoTotalAtivos")
        .text(
            dados.length
        );


    $("#resumoValorAquisicao")
        .text(
            formatarMoedaAtivos(
                valorAquisicao
            )
        );


    $("#resumoDepreciacaoAcumulada")
        .text(
            formatarMoedaAtivos(
                depreciacao
            )
        );


    $("#resumoValorLiquido")
        .text(
            formatarMoedaAtivos(
                valorLiquido
            )
        );
}


// ==================================================
// NOVO ATIVO
// ==================================================

async function abrirNovoAtivo() {

    limparFormularioAtivo();


    $("#tituloModalAtivo")
        .text(
            "Novo Ativo"
        );


    $("#btnGuardarAtivo")
        .prop(
            "disabled",
            false
        );


    try {

        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?acao=proximo_codigo"
            );


        const json =
            await resposta.json();


        if (
            json.success
            && json.data
        ) {

            $("#ativoCodigo")
                .val(
                    json.data.codigo
                    || ""
                );
        }

    } catch (erro) {

        console.error(
            erro
        );
    }


    $("#modalAtivoEmpresa")
        .modal(
            "show"
        );
}


// ==================================================
// EDITAR
// ==================================================

async function editarAtivoEmpresa(
    ativoId
) {

    try {

        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL
                + "?acao=buscar&id="
                + encodeURIComponent(
                    ativoId
                )
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Ativo não encontrado."
            );
        }


        const ativo =
            json.data;


        limparFormularioAtivo();


        $("#ativoId")
            .val(
                ativo.id
            );


        $("#ativoCodigo")
            .val(
                ativo.codigo
                || ""
            );


        $("#ativoTipo")
            .val(
                ativo.tipo
                || ""
            );


        atualizarCategoriasAtivo(
            ativo.conta_contabil_id
        );


        $("#ativoDescricao")
            .val(
                ativo.descricao
                || ""
            );


        $("#ativoMarca")
            .val(
                ativo.marca
                || ""
            );


        $("#ativoModelo")
            .val(
                ativo.modelo
                || ""
            );


        $("#ativoNumeroSerie")
            .val(
                ativo.numero_serie
                || ""
            );


        $("#ativoDataAquisicao")
            .val(
                ativo.data_aquisicao
                || ""
            );


        $("#ativoDataEntradaUso")
            .val(
                ativo.data_entrada_uso
                || ""
            );


        $("#ativoValorAquisicao")
            .val(
                ativo.valor_aquisicao
                || ""
            );


        $("#ativoValorResidual")
            .val(
                ativo.valor_residual
                || 0
            );


        if (
            ativo.vida_util_meses
        ) {

            $("#ativoVidaUtilAnos")
                .val(
                    Number(
                        ativo.vida_util_meses
                    ) / 12
                );
        }


        $("#ativoMetodoDepreciacao")
            .val(
                ativo.metodo_depreciacao
                || "LINEAR"
            );


        $("#ativoTaxaAnual")
            .val(
                ativo.taxa_anual
                || ""
            );


        $("#ativoFilial")
            .val(
                ativo.filial_id
                || ""
            );


        $("#ativoDepartamento")
            .val(
                ativo.departamento_id
                || ""
            );


        $("#ativoLocalizacao")
            .val(
                ativo.localizacao
                || ""
            );


        $("#ativoResponsavel")
            .val(
                ativo.responsavel
                || ""
            );


        $("#ativoFornecedor")
            .val(
                ativo.fornecedor_id
                || ""
            );


        $("#ativoNumeroDocumento")
            .val(
                ativo.numero_documento
                || ""
            );


        $("#ativoEstadoFisico")
            .val(
                ativo.estado_fisico
                || "NOVO"
            );


        $("#ativoSituacao")
            .val(
                ativo.situacao
                || "EM_USO"
            );


        $("#ativoObservacoes")
            .val(
                ativo.observacoes
                || ""
            );


        atualizarRotulosDepreciacao();

        calcularDepreciacaoAtivo();


        $("#tituloModalAtivo")
            .text(
                "Editar Ativo"
            );


        $("#modalAtivoEmpresa")
            .modal(
                "show"
            );

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );
    }
}


// ==================================================
// VISUALIZAR
// ==================================================

function verAtivoEmpresa(
    ativoId
) {

    editarAtivoEmpresa(
        ativoId
    );
}


// ==================================================
// GUARDAR
// ==================================================

async function guardarAtivo() {

    const botao =
        $("#btnGuardarAtivo");


    botao
        .prop(
            "disabled",
            true
        );


    try {

        const ativoId =
            $("#ativoId").val();


        const dados =
            new FormData(
                document.getElementById(
                    "formAtivoEmpresa"
                )
            );


        dados.set(
            "acao",
            ativoId
                ? "editar"
                : "criar"
        );


        if (ativoId) {

            dados.set(
                "id",
                ativoId
            );
        }


        const resposta =
            await fetch(
                ATIVOS_EMPRESA_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await resposta.json();


        if (!json.success) {

            throw new Error(
                json.mensagem
                || "Não foi possível guardar o ativo."
            );
        }


        $("#modalAtivoEmpresa")
            .modal(
                "hide"
            );


        mostrarSucessoAtivos(
            json.mensagem
            || "Ativo guardado com sucesso."
        );


        recarregarTabelaAtivos();

    } catch (erro) {

        mostrarErroAtivos(
            erro.message
        );

    } finally {

        botao
            .prop(
                "disabled",
                false
            );
    }
}


// ==================================================
// CÁLCULO AUTOMÁTICO
// ==================================================

let ultimoCampoCalculoAtivo =
    null;


function calcularDepreciacaoAtivo(
    campoAlterado = null
) {

    if (campoAlterado) {

        ultimoCampoCalculoAtivo =
            campoAlterado;
    }


    const valorAquisicao =
        Number(
            $("#ativoValorAquisicao")
                .val()
            || 0
        );


    const valorResidual =
        Number(
            $("#ativoValorResidual")
                .val()
            || 0
        );


    const vidaUtilAnos =
        Number(
            $("#ativoVidaUtilAnos")
                .val()
            || 0
        );


    let taxa =
        Number(
            $("#ativoTaxaAnual")
                .val()
            || 0
        );


    const base =
        Math.max(
            0,
            valorAquisicao
            - valorResidual
        );


    let anual =
        0;


    /*
    |--------------------------------------------------------------------------
    | PRIORIDADE
    |--------------------------------------------------------------------------
    |
    | Se o utilizador alterou a taxa,
    | calculamos pela taxa.
    |
    | Caso contrário, se houver vida útil,
    | calculamos pela vida útil e preenchemos
    | a taxa automaticamente.
    |
    */

    if (
        ultimoCampoCalculoAtivo
        === "ativoTaxaAnual"
        && taxa > 0
    ) {

        anual =
            base
            * (
                taxa / 100
            );


        if (
            anual > 0
        ) {

            const vidaCalculada =
                base / anual;


            if (
                Number.isFinite(
                    vidaCalculada
                )
            ) {

                $("#ativoVidaUtilAnos")
                    .val(
                        arredondarAtivo(
                            vidaCalculada,
                            2
                        )
                    );
            }
        }

    } else if (
        vidaUtilAnos > 0
    ) {

        anual =
            base
            / vidaUtilAnos;


        taxa =
            base > 0
                ? (
                    anual
                    / base
                ) * 100
                : 0;


        $("#ativoTaxaAnual")
            .val(
                taxa > 0
                    ? arredondarAtivo(
                        taxa,
                        4
                    )
                    : ""
            );

    } else if (
        taxa > 0
    ) {

        anual =
            base
            * (
                taxa / 100
            );
    }


    /*
    |--------------------------------------------------------------------------
    | O VALOR ANUAL NÃO PODE ULTRAPASSAR
    | A BASE DEPRECIÁVEL
    |--------------------------------------------------------------------------
    */

    anual =
        Math.min(
            Math.max(
                anual,
                0
            ),
            base
        );


    const mensal =
        anual > 0
            ? anual / 12
            : 0;


    $("#ativoValorAnual")
        .val(
            formatarMoedaAtivos(
                anual
            )
        );


    $("#ativoValorMensal")
        .val(
            formatarMoedaAtivos(
                mensal
            )
        );
}


// ==================================================
// RÓTULOS
// ==================================================

function atualizarRotulosDepreciacao() {

    const tipo =
        $("#ativoTipo")
            .val();


    if (
        tipo === "INTANGIVEL"
    ) {

        $("#labelValorAnual")
            .text(
                "Amortização Anual"
            );


        $("#labelValorMensal")
            .text(
                "Amortização Mensal"
            );

    } else {

        $("#labelValorAnual")
            .text(
                "Depreciação Anual"
            );


        $("#labelValorMensal")
            .text(
                "Depreciação Mensal"
            );
    }
}

function montarOpcoesContabeisAgrupadas(
    contas,
    placeholder = "Selecione",
    valorSelecionado = ""
) {

    const grupos = {};


    (contas || [])
        .forEach(
            function (conta) {

                const classeCodigo =
                    String(
                        conta.classe_codigo
                        || conta.classe
                        || ""
                    );


                const classeNome =
                    String(
                        conta.classe_nome
                        || conta.grupo
                        || "Contas contabilísticas"
                    );


                const chave =
                    classeCodigo
                    || "OUTRAS";


                if (!grupos[chave]) {

                    grupos[chave] = {
                        codigo:
                            classeCodigo,

                        nome:
                            classeNome,

                        contas:
                            []
                    };
                }


                grupos[chave]
                    .contas
                    .push(
                        conta
                    );
            }
        );


    const html = [
        `<option value="">${escaparHtmlAtivos(
            placeholder
        )}</option>`
    ];


    Object.values(
        grupos
    )
        .forEach(
            function (grupo) {

                let titulo =
                    "CONTAS CONTABILÍSTICAS";


                if (grupo.codigo) {

                    titulo =
                        "CLASSE "
                        + grupo.codigo
                        + " — "
                        + grupo.nome;
                }


                html.push(`
                    <optgroup
                        label="${escaparHtmlAtivos(
                    titulo
                )}">
                `);


                grupo.contas
                    .forEach(
                        function (conta) {

                            const selecionado =
                                String(conta.id)
                                    === String(
                                        valorSelecionado
                                    )
                                    ? "selected"
                                    : "";


                            let texto =
                                conta.codigo
                                + " — "
                                + conta.nome;


                            /*
                             * Mostra também a conta
                             * imediatamente superior.
                             */
                            if (
                                conta.conta_pai_codigo
                                && conta.conta_pai_nome
                            ) {

                                texto +=
                                    "   ["
                                    + conta.conta_pai_codigo
                                    + " "
                                    + conta.conta_pai_nome
                                    + "]";
                            }


                            html.push(`
                                <option
                                    value="${Number(
                                conta.id
                            )}"
                                    ${selecionado}>

                                    ${escaparHtmlAtivos(
                                texto
                            )}

                                </option>
                            `);
                        }
                    );


                html.push(
                    "</optgroup>"
                );
            }
        );


    return html.join("");
}


// ==================================================
// LIMPAR FORMULÁRIO
// ==================================================

function limparFormularioAtivo() {

    const form =
        document.getElementById(
            "formAtivoEmpresa"
        );


    if (form) {

        form.reset();
    }


    $("#ativoId")
        .val("");


    $("#ativoCodigo")
        .val("");


    $("#ativoTipo")
        .val("");


    $("#ativoContaContabil")
        .html(
            `
            <option value="">
                Primeiro selecione o tipo de ativo
            </option>
            `
        );


    $("#ativoValorResidual")
        .val(
            "0"
        );


    $("#ativoMetodoDepreciacao")
        .val(
            "LINEAR"
        );


    $("#ativoEstadoFisico")
        .val(
            "NOVO"
        );


    $("#ativoSituacao")
        .val(
            "EM_USO"
        );


    $("#ativoValorAnual")
        .val(
            formatarMoedaAtivos(
                0
            )
        );


    $("#ativoValorMensal")
        .val(
            formatarMoedaAtivos(
                0
            )
        );


    $("#ativoObservacoes")
        .val("");


    ultimoCampoCalculoAtivo =
        null;


    atualizarRotulosDepreciacao();
}


// ==================================================
// BADGE SITUAÇÃO
// ==================================================

function badgeSituacaoAtivo(
    situacao
) {

    const configuracoes = {

        EM_USO: {
            texto:
                "Em uso",

            classe:
                "badge-success"
        },

        ARMAZENADO: {
            texto:
                "Armazenado",

            classe:
                "badge-secondary"
        },

        EM_MANUTENCAO: {
            texto:
                "Em manutenção",

            classe:
                "badge-warning"
        },

        CEDIDO: {
            texto:
                "Cedido",

            classe:
                "badge-info"
        },

        VENDIDO: {
            texto:
                "Vendido",

            classe:
                "badge-dark"
        },

        ABATIDO: {
            texto:
                "Abatido",

            classe:
                "badge-danger"
        }
    };


    const config =
        configuracoes[
        situacao
        ]
        || {
            texto:
                situacao || "-",

            classe:
                "badge-light"
        };


    return `
        <span class="badge ${config.classe}">
            ${escaparHtmlAtivos(
        config.texto
    )}
        </span>
    `;
}


// ==================================================
// MOEDA
// ==================================================

function formatarMoedaAtivos(
    valor
) {

    const numero =
        Number(
            valor
            || 0
        );


    return numero
        .toLocaleString(
            "pt-PT",
            {
                minimumFractionDigits:
                    2,

                maximumFractionDigits:
                    2
            }
        )
        + " MZN";
}


// ==================================================
// ALERTAS
// ==================================================

function mostrarSucessoAtivos(
    mensagem
) {

    if (
        typeof Swal
        !== "undefined"
    ) {

        Swal.fire({
            icon:
                "success",

            title:
                "Sucesso",

            text:
                mensagem,

            timer:
                2200,

            showConfirmButton:
                false
        });

        return;
    }


    alert(
        mensagem
    );
}


function mostrarErroAtivos(
    mensagem
) {

    if (
        typeof Swal
        !== "undefined"
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


// ==================================================
// ESCAPAR HTML
// ==================================================

function escaparHtmlAtivos(
    valor
) {

    return String(
        valor
        ?? ""
    )
        .replace(
            /&/g,
            "&amp;"
        )
        .replace(
            /</g,
            "&lt;"
        )
        .replace(
            />/g,
            "&gt;"
        )
        .replace(
            /"/g,
            "&quot;"
        )
        .replace(
            /'/g,
            "&#039;"
        );
}


// ==================================================
// DEBOUNCE
// ==================================================

function debounceAtivos(
    funcao,
    atraso
) {

    let temporizador;


    return function (
        ...args
    ) {

        clearTimeout(
            temporizador
        );


        temporizador =
            setTimeout(
                () =>
                    funcao.apply(
                        this,
                        args
                    ),
                atraso
            );
    };
}


// ==================================================
// ARREDONDAR
// ==================================================

function arredondarAtivo(
    valor,
    casas = 2
) {

    const fator =
        Math.pow(
            10,
            casas
        );


    return Math.round(
        (
            Number(valor)
            + Number.EPSILON
        )
        * fator
    ) / fator;
}