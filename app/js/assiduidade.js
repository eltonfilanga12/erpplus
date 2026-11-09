"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const ASSIDUIDADE_URL =
        "/erpplus/app/ajax/assiduidade_ajax.php";

    let funcionariosCache = [];
    let filiaisCache = [];

    let tiposRegistoCache = [];
    let origensRegistoCache = [];
    let ocorrenciasCache = [];
    let tiposFaltaCache = [];
    let tiposHoraExtraCache = [];

    let statusRegistoCache = [];
    let statusAssiduidadeCache = [];
    let statusFaltaCache = [];
    let statusHoraExtraCache = [];

    let registosPontoCache = [];
    let assiduidadeCache = [];
    let faltasCache = [];
    let horasExtrasCache = [];

    let tabelaRegistosPonto = null;
    let tabelaAssiduidade = null;
    let tabelaFaltas = null;
    let tabelaHorasExtras = null;
    const registosPontoSelecionados = new Set();
    const assiduidadesSelecionadas = new Set();


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaAssiduidadeExiste()) {
            return;
        }

        configurarEventosAssiduidade();

        definirPeriodoInicial();

        carregarDadosIniciaisAssiduidade()
            .catch(function (erro) {

                console.error(
                    "Erro ao iniciar o módulo de assiduidade:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaAssiduidadeExiste() {

        return (
            $("#assiduidadeTable").length > 0
            || $("#registosPontoTable").length > 0
            || $("#faltasTable").length > 0
            || $("#horasExtrasTable").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosAssiduidade() {

        // ----------------------------------------------
        // FILTROS
        // ----------------------------------------------

        $("#btnFiltrarAssiduidade").on(
            "click",
            function () {

                carregarTodasListas()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao filtrar assiduidade:",
                            erro
                        );

                    });

            }
        );

        $("#btnLimparFiltrosAssiduidade").on(
            "click",
            function () {

                limparFiltrosAssiduidade();

                carregarTodasListas()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao limpar filtros:",
                            erro
                        );

                    });

            }
        );

        $("#registoIndividualModo").on(
            "change",
            atualizarModoRegistoIndividual
        );

        $("#filtroAssiduidadePesquisa").on(
            "keydown",
            function (evento) {

                if (evento.key !== "Enter") {
                    return;
                }

                evento.preventDefault();

                carregarTodasListas()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao pesquisar assiduidade:",
                            erro
                        );

                    });

            }
        );

        $(
            "#filtroAssiduidadeDataInicio, "
            + "#filtroAssiduidadeDataFim"
        ).on(
            "change",
            function () {

                validarPeriodoFiltro();

            }
        );


        // ----------------------------------------------
        // NOVO REGISTO DE PONTO
        // ----------------------------------------------

        $("#btnNovoRegistoPonto").on(
            "click",
            function () {

                abrirModalNovoRegistoPonto();

            }
        );

        $("#formRegistoPonto").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarRegistoPonto()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao guardar registo de ponto:",
                            erro
                        );

                    });

            }
        );

        $("#registoPontoFuncionarioId").on(
            "change",
            function () {

                preencherFilialDoFuncionario();

            }
        );

        $("#registoPontoData, #registoPontoHora").on(
            "change",
            function () {

                validarDataHoraRegisto();

            }
        );

        $("#modalRegistoPonto").on(
            "hidden.bs.modal",
            function () {

                limparFormularioRegistoPonto();

            }
        );


        // ----------------------------------------------
        // TABELA DE REGISTOS DE PONTO
        // ----------------------------------------------

        $("#registosPontoTable").on(
            "click",
            ".btn-ver-registo-ponto",
            function () {

                mostrarDetalhesRegistoPonto(
                    Number(
                        $(this).data("id") || 0
                    )
                );

            }
        );

        $("#registosPontoTable").on(
            "click",
            ".btn-editar-registo-ponto",
            function () {

                abrirModalEditarRegistoPonto(
                    Number(
                        $(this).data("id") || 0
                    )
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao editar registo de ponto:",
                            erro
                        );

                    });

            }
        );

        $("#registosPontoTable").on(
            "click",
            ".btn-validar-registo-ponto",
            function () {

                confirmarValidarRegistoPonto(
                    Number(
                        $(this).data("id") || 0
                    )
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao validar registo:",
                            erro
                        );

                    });

            }
        );

        $("#registosPontoTable").on(
            "click",
            ".btn-anular-registo-ponto",
            function () {

                confirmarAnularRegistoPonto(
                    Number(
                        $(this).data("id") || 0
                    )
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao anular registo:",
                            erro
                        );

                    });

            }
        );


        // ----------------------------------------------
        // ASSIDUIDADE DIÁRIA
        // ----------------------------------------------

        $("#assiduidadeTable").on(
            "click",
            ".btn-ver-assiduidade",
            function () {

                mostrarDetalhesAssiduidade(
                    Number(
                        $(this).data("id") || 0
                    )
                );

            }
        );

        $("#assiduidadeTable").on(
            "click",
            ".btn-apurar-assiduidade",
            function () {

                const funcionarioId =
                    Number(
                        $(this).data(
                            "funcionario-id"
                        ) || 0
                    );

                const data =
                    String(
                        $(this).data("data") || ""
                    );

                confirmarApurarDia(
                    funcionarioId,
                    data
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao apurar assiduidade:",
                            erro
                        );

                    });

            }
        );

        $("#assiduidadeTable").on(
            "click",
            ".btn-aprovar-assiduidade",
            function () {

                confirmarAprovarAssiduidade(
                    Number(
                        $(this).data("id") || 0
                    )
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao aprovar assiduidade:",
                            erro
                        );

                    });

            }
        );

        // ----------------------------------------------
        // SELEÇÃO MÚLTIPLA DA ASSIDUIDADE
        // ----------------------------------------------

        $("#assiduidadeTable").on(
            "change",
            ".checkbox-assiduidade",
            function () {

                const assiduidadeId =
                    Number(
                        $(this).data("id") || 0
                    );

                if (assiduidadeId <= 0) {
                    return;
                }

                if ($(this).is(":checked")) {

                    assiduidadesSelecionadas.add(
                        assiduidadeId
                    );

                } else {

                    assiduidadesSelecionadas.delete(
                        assiduidadeId
                    );

                }

                atualizarSelecaoAssiduidades();

            }
        );


        // Selecionar todos os registos visíveis
        $("#selecionarTodasAssiduidades").on(
            "change",
            function (evento) {

                evento.stopPropagation();

                const selecionar =
                    this.checked;

                let linhasPaginaAtual;

                if (tabelaAssiduidade) {

                    linhasPaginaAtual =
                        $(
                            tabelaAssiduidade
                                .rows({
                                    page:
                                        "current"
                                })
                                .nodes()
                        );

                } else {

                    linhasPaginaAtual =
                        $("#assiduidadeTable tbody tr");

                }

                linhasPaginaAtual
                    .find(".checkbox-assiduidade")
                    .each(
                        function () {

                            const assiduidadeId =
                                Number(
                                    $(this).data("id") || 0
                                );

                            if (assiduidadeId <= 0) {
                                return;
                            }

                            $(this).prop(
                                "checked",
                                selecionar
                            );

                            if (selecionar) {

                                assiduidadesSelecionadas.add(
                                    assiduidadeId
                                );

                            } else {

                                assiduidadesSelecionadas.delete(
                                    assiduidadeId
                                );

                            }

                        }
                    );

                atualizarSelecaoAssiduidades();

            }
        );


        // Botão Aprovar selecionados
        $("#btnAprovarAssiduidadesSelecionadas").on(
            "click",
            function () {

                confirmarAprovarAssiduidadesSelecionadas()
                    .catch(
                        function (erro) {

                            console.error(
                                "Erro ao aprovar assiduidades selecionadas:",
                                erro
                            );

                        }
                    );

            }
        );

        // ----------------------------------------------
        // FALTAS
        // ----------------------------------------------

        $("#faltasTable").on(
            "click",
            ".btn-ver-falta",
            function () {

                mostrarDetalhesFalta(
                    Number(
                        $(this).data("id") || 0
                    )
                );

            }
        );

        $("#faltasTable").on(
            "click",
            ".btn-justificar-falta",
            function () {

                abrirModalJustificarFalta(
                    Number(
                        $(this).data("id") || 0
                    )
                );

            }
        );

        $("#faltasTable").on(
            "click",
            ".btn-falta-injustificada",
            function () {

                confirmarFaltaInjustificada(
                    Number(
                        $(this).data("id") || 0
                    )
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao marcar falta injustificada:",
                            erro
                        );

                    });

            }
        );

        $("#formJustificarFalta").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarJustificacaoFalta()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao justificar falta:",
                            erro
                        );

                    });

            }
        );

        $("#modalJustificarFalta").on(
            "hidden.bs.modal",
            function () {

                limparFormularioJustificarFalta();

            }
        );


        // ----------------------------------------------
        // HORAS EXTRAS
        // ----------------------------------------------

        $("#horasExtrasTable").on(
            "click",
            ".btn-ver-hora-extra",
            function () {

                mostrarDetalhesHoraExtra(
                    Number(
                        $(this).data("id") || 0
                    )
                );

            }
        );

        $("#horasExtrasTable").on(
            "click",
            ".btn-aprovar-hora-extra",
            function () {

                abrirModalAprovarHoraExtra(
                    Number(
                        $(this).data("id") || 0
                    )
                );

            }
        );

        $("#horasExtrasTable").on(
            "click",
            ".btn-rejeitar-hora-extra",
            function () {

                confirmarRejeitarHoraExtra(
                    Number(
                        $(this).data("id") || 0
                    )
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao rejeitar horas extras:",
                            erro
                        );

                    });

            }
        );

        $("#formAprovarHoraExtra").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarAprovacaoHoraExtra()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao aprovar horas extras:",
                            erro
                        );

                    });

            }
        );

        $(
            "#horaExtraMinutosAprovados, "
            + "#horaExtraPercentualAcrescimo, "
            + "#horaExtraValorHora"
        ).on(
            "input",
            function () {

                calcularValorTotalHoraExtra();

            }
        );

        $("#modalAprovarHoraExtra").on(
            "hidden.bs.modal",
            function () {

                limparFormularioAprovarHoraExtra();

            }
        );

        // ----------------------------------------------
        // ASSIDUIDADE EM LOTE
        // ----------------------------------------------

        $("#btnMarcarAssiduidadeLote").on(
            "click",
            abrirModalAssiduidadeLote
        );


        $("#assiduidadeLoteFilial").on(
            "change",
            montarFuncionariosAssiduidadeLote
        );


        $("#pesquisaFuncionarioLote").on(
            "input",
            filtrarFuncionariosAssiduidadeLote
        );


        $("#selecionarTodosLote").on(
            "change",
            function () {

                const marcado =
                    $(this).is(":checked");

                $(".linha-funcionario-lote:visible")
                    .find(".check-funcionario-lote")
                    .prop(
                        "checked",
                        marcado
                    );

                atualizarResumoAssiduidadeLote();
            }
        );


        $(document).on(
            "change",
            ".check-funcionario-lote, .situacao-assiduidade-lote",
            function () {

                const linha =
                    $(this).closest("tr");

                atualizarLinhaSituacaoLote(
                    linha
                );

                atualizarResumoAssiduidadeLote();
            }
        );


        $("#btnAplicarHorarioLote").on(
            "click",
            aplicarHorarioPadraoLote
        );


        $("#btnMarcarPresentesLote").on(
            "click",
            function () {

                linhasSelecionadasLote()
                    .each(
                        function () {

                            const linha =
                                $(this);

                            linha.find(
                                ".situacao-assiduidade-lote"
                            ).val("PRESENTE");

                            atualizarLinhaSituacaoLote(
                                linha
                            );
                        }
                    );

                atualizarResumoAssiduidadeLote();
            }
        );


        $("#btnMarcarFaltasLote").on(
            "click",
            function () {

                linhasSelecionadasLote()
                    .each(
                        function () {

                            const linha =
                                $(this);

                            linha.find(
                                ".situacao-assiduidade-lote"
                            ).val("FALTA");

                            limparHorasLinhaLote(
                                linha
                            );

                            atualizarLinhaSituacaoLote(
                                linha
                            );
                        }
                    );

                atualizarResumoAssiduidadeLote();
            }
        );


        $("#btnLimparHorasLote").on(
            "click",
            function () {

                linhasSelecionadasLote()
                    .each(
                        function () {

                            limparHorasLinhaLote(
                                $(this)
                            );
                        }
                    );
            }
        );


        $("#btnGuardarAssiduidadeLote").on(
            "click",
            function () {

                guardarAssiduidadeLote()
                    .catch(
                        function (erro) {

                            console.error(
                                "Erro ao guardar assiduidade em lote:",
                                erro
                            );
                        }
                    );
            }
        );


        $("#modalAssiduidadeLote").on(
            "hidden.bs.modal",
            limparModalAssiduidadeLote
        );


        // ----------------------------------------------
        // EXPORTAR
        // ----------------------------------------------

        $("#btnExportarAssiduidade").on(
            "click",
            function () {

                exportarTabelaAssiduidade();

            }
        );


        // Selecionar um registo
        $("#registosPontoTable").on(
            "change",
            ".checkbox-registo-ponto",
            function () {

                const registoId = Number(
                    $(this).data("id") || 0
                );

                if ($(this).is(":checked")) {

                    registosPontoSelecionados.add(
                        registoId
                    );

                } else {

                    registosPontoSelecionados.delete(
                        registoId
                    );

                }

                atualizarSelecaoRegistosPonto();

            }
        );

        // Impedir que o clique na checkbox ordene a tabela
        $("#selecionarTodosRegistosPonto, #selecionarTodasAssiduidades")
            .on(
                "click",
                function (evento) {

                    evento.stopPropagation();

                }
            );


        // Selecionar todos os registos visíveis
        $("#selecionarTodosRegistosPonto").on(
            "change",
            function (evento) {

                evento.stopPropagation();

                const selecionar =
                    this.checked;

                let linhasPaginaAtual;

                if (tabelaRegistosPonto) {

                    linhasPaginaAtual =
                        $(
                            tabelaRegistosPonto
                                .rows({
                                    page:
                                        "current"
                                })
                                .nodes()
                        );

                } else {

                    linhasPaginaAtual =
                        $("#registosPontoTable tbody tr");

                }

                linhasPaginaAtual
                    .find(".checkbox-registo-ponto")
                    .each(
                        function () {

                            const registoId =
                                Number(
                                    $(this).data("id") || 0
                                );

                            if (registoId <= 0) {
                                return;
                            }

                            $(this).prop(
                                "checked",
                                selecionar
                            );

                            if (selecionar) {

                                registosPontoSelecionados.add(
                                    registoId
                                );

                            } else {

                                registosPontoSelecionados.delete(
                                    registoId
                                );

                            }

                        }
                    );

                atualizarSelecaoRegistosPonto();

            }
        );

        // Validar os registos selecionados
        $("#btnValidarRegistosPontoSelecionados").on(
            "click",
            function () {

                confirmarValidarRegistosPontoSelecionados()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao validar registos selecionados:",
                            erro
                        );

                    });

            }
        );

    }

    function atualizarModoRegistoIndividual() {

        const modo =
            String(
                $("#registoIndividualModo")
                    .val()
                || "PONTO"
            ).toUpperCase();

        const falta =
            modo === "FALTA";


        $("#registoPontoHora")
            .prop(
                "disabled",
                falta
            )
            .prop(
                "required",
                !falta
            );


        $("#registoPontoTipo")
            .prop(
                "disabled",
                falta
            )
            .prop(
                "required",
                !falta
            );


        $("#registoPontoOrigem")
            .prop(
                "disabled",
                falta
            )
            .prop(
                "required",
                !falta
            );


        $(
            "#registoPontoDispositivo, "
            + "#registoPontoIdentificadorDispositivo, "
            + "#registoPontoLatitude, "
            + "#registoPontoLongitude, "
            + "#registoPontoEnderecoLocalizacao"
        ).prop(
            "disabled",
            falta
        );


        if (falta) {

            $("#registoPontoHora")
                .val("");

            $("#registoPontoTipo")
                .val("");
        }
    }

    // ==================================================
    // PERÍODO INICIAL
    // ==================================================

    function definirPeriodoInicial() {

        const hoje =
            new Date();

        const primeiroDia =
            new Date(
                hoje.getFullYear(),
                hoje.getMonth(),
                1
            );

        const ultimoDia =
            new Date(
                hoje.getFullYear(),
                hoje.getMonth() + 1,
                0
            );

        if (
            $("#filtroAssiduidadeDataInicio")
                .val() === ""
        ) {

            $("#filtroAssiduidadeDataInicio")
                .val(
                    formatarDataIsoObjeto(
                        primeiroDia
                    )
                );

        }

        if (
            $("#filtroAssiduidadeDataFim")
                .val() === ""
        ) {

            $("#filtroAssiduidadeDataFim")
                .val(
                    formatarDataIsoObjeto(
                        ultimoDia
                    )
                );

        }

    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisAssiduidade() {

        validarPeriodoFiltro();

        mostrarCarregamentoAssiduidade(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "dados_iniciais"
                });

            adicionarParametro(
                parametros,
                "data_inicio",
                $("#filtroAssiduidadeDataInicio")
                    .val()
            );

            adicionarParametro(
                parametros,
                "data_fim",
                $("#filtroAssiduidadeDataFim")
                    .val()
            );

            const resposta =
                await requisicaoAssiduidade(
                    ASSIDUIDADE_URL
                    + "?"
                    + parametros.toString()
                );

            const dados =
                resposta.data || {};

            funcionariosCache =
                arraySeguro(
                    dados.funcionarios
                );

            filiaisCache =
                arraySeguro(
                    dados.filiais
                );

            tiposRegistoCache =
                arraySeguro(
                    dados.tipos_registo
                );

            origensRegistoCache =
                arraySeguro(
                    dados.origens_registo
                );

            ocorrenciasCache =
                arraySeguro(
                    dados.ocorrencias
                );

            tiposFaltaCache =
                arraySeguro(
                    dados.tipos_falta
                );

            tiposHoraExtraCache =
                arraySeguro(
                    dados.tipos_hora_extra
                );

            statusRegistoCache =
                arraySeguro(
                    dados.status_registo
                );

            statusAssiduidadeCache =
                arraySeguro(
                    dados.status_assiduidade
                );

            statusFaltaCache =
                arraySeguro(
                    dados.status_falta
                );

            statusHoraExtraCache =
                arraySeguro(
                    dados.status_hora_extra
                );

            registosPontoCache =
                arraySeguro(
                    dados.registos_ponto
                );

            assiduidadeCache =
                arraySeguro(
                    dados.assiduidade
                );

            faltasCache =
                arraySeguro(
                    dados.faltas
                );

            horasExtrasCache =
                arraySeguro(
                    dados.horas_extras
                );

            preencherSelectsAssiduidade();

            atualizarResumoAssiduidade(
                dados.resumo || {}
            );

            renderizarTabelaRegistosPonto(
                registosPontoCache
            );

            renderizarTabelaAssiduidade(
                assiduidadeCache
            );

            renderizarTabelaFaltas(
                faltasCache
            );

            renderizarTabelaHorasExtras(
                horasExtrasCache
            );

        } catch (erro) {

            mostrarErroAssiduidade(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoAssiduidade(false);

        }

    }


    // ==================================================
    // ABRIR MODAL DE ASSIDUIDADE EM LOTE
    // ==================================================

    function abrirModalAssiduidadeLote() {

        limparModalAssiduidadeLote();

        $("#assiduidadeLoteData")
            .val(
                dataAtualIso()
            );

        preencherSelectCodigo(
            "#assiduidadeLoteOrigem",
            origensRegistoCache,
            "Selecione a origem"
        );

        $("#assiduidadeLoteOrigem")
            .val("MANUAL");


        $("#assiduidadeLoteFilial")
            .html(`
            <option value="">
                Todas as filiais
            </option>
        `);

        filiaisCache.forEach(
            function (filial) {

                $("#assiduidadeLoteFilial")
                    .append(
                        $("<option>", {
                            value:
                                filial.id,

                            text:
                                filial.nome
                                || filial.codigo
                                || (
                                    "Filial #"
                                    + filial.id
                                )
                        })
                    );
            }
        );

        montarFuncionariosAssiduidadeLote();

        $("#modalAssiduidadeLote")
            .modal("show");
    }

    // ==================================================
    // MONTAR FUNCIONÁRIOS
    // ==================================================

    function montarFuncionariosAssiduidadeLote() {

        const filialId =
            Number(
                $("#assiduidadeLoteFilial")
                    .val()
                || 0
            );

        let funcionarios =
            Array.isArray(
                funcionariosCache
            )
                ? funcionariosCache
                : [];


        if (filialId > 0) {

            funcionarios =
                funcionarios.filter(
                    function (funcionario) {

                        return Number(
                            funcionario.filial_id
                            || 0
                        ) === filialId;
                    }
                );
        }


        let html = "";


        funcionarios.forEach(
            function (funcionario) {

                const funcionarioId =
                    Number(
                        funcionario.id
                        || 0
                    );

                if (funcionarioId <= 0) {
                    return;
                }


                const filial =
                    filiaisCache.find(
                        function (item) {

                            return Number(
                                item.id
                                || 0
                            ) === Number(
                                funcionario.filial_id
                                || 0
                            );
                        }
                    );


                const filialNome =
                    filial
                        ? (
                            filial.nome
                            || filial.codigo
                            || "-"
                        )
                        : "-";


                html += `
                <tr
                    class="linha-funcionario-lote"
                    data-funcionario-id="${funcionarioId}"
                    data-filial-id="${Number(
                    funcionario.filial_id || 0
                )}">

                    <td class="text-center">

                        <input
                            type="checkbox"
                            class="check-funcionario-lote">

                    </td>


                    <td>
                        ${escaparHtml(
                    funcionario.numero_funcionario
                    || "-"
                )}
                    </td>


                    <td>
                        ${escaparHtml(
                    funcionario.nome_completo
                    || "-"
                )}
                    </td>


                    <td>
                        ${escaparHtml(
                    filialNome
                )}
                    </td>


                    <td>

                        <input
                            type="time"
                            class="form-control form-control-sm lote-entrada">

                    </td>


                    <td>

                        <input
                            type="time"
                            class="form-control form-control-sm lote-inicio-intervalo">

                    </td>


                    <td>

                        <input
                            type="time"
                            class="form-control form-control-sm lote-fim-intervalo">

                    </td>


                    <td>

                        <input
                            type="time"
                            class="form-control form-control-sm lote-saida">

                    </td>


                    <td>

                        <select
                            class="form-control form-control-sm situacao-assiduidade-lote">

                            <option value="PRESENTE">
                                Presente
                            </option>

                            <option value="FALTA">
                                Falta
                            </option>

                        </select>

                    </td>


                    <td>

                        <input
                            type="text"
                            class="form-control form-control-sm lote-observacao"
                            maxlength="500">

                    </td>

                </tr>
            `;
            }
        );


        if (html === "") {

            html = `
            <tr>
                <td
                    colspan="10"
                    class="text-center text-muted">

                    Nenhum funcionário encontrado.

                </td>
            </tr>
        `;
        }


        $("#listaFuncionariosLote")
            .html(html);


        atualizarResumoAssiduidadeLote();
    }

    function linhasSelecionadasLote() {

        return $(".linha-funcionario-lote")
            .filter(
                function () {

                    return $(this)
                        .find(
                            ".check-funcionario-lote"
                        )
                        .is(":checked");
                }
            );
    }


    function aplicarHorarioPadraoLote() {

        const entrada =
            $("#loteEntradaPadrao")
                .val();

        const inicioIntervalo =
            $("#loteInicioIntervaloPadrao")
                .val();

        const fimIntervalo =
            $("#loteFimIntervaloPadrao")
                .val();

        const saida =
            $("#loteSaidaPadrao")
                .val();


        linhasSelecionadasLote()
            .each(
                function () {

                    const linha =
                        $(this);

                    linha.find(
                        ".situacao-assiduidade-lote"
                    )
                        .val(
                            "PRESENTE"
                        );

                    linha.find(
                        ".lote-entrada"
                    ).val(
                        entrada
                    );

                    linha.find(
                        ".lote-inicio-intervalo"
                    ).val(
                        inicioIntervalo
                    );

                    linha.find(
                        ".lote-fim-intervalo"
                    ).val(
                        fimIntervalo
                    );

                    linha.find(
                        ".lote-saida"
                    ).val(
                        saida
                    );

                    atualizarLinhaSituacaoLote(
                        linha
                    );
                }
            );


        atualizarResumoAssiduidadeLote();
    }

    function limparHorasLinhaLote(
        linha
    ) {

        linha.find(
            ".lote-entrada, "
            + ".lote-inicio-intervalo, "
            + ".lote-fim-intervalo, "
            + ".lote-saida"
        ).val("");
    }


    function atualizarLinhaSituacaoLote(
        linha
    ) {

        const situacao =
            String(
                linha.find(
                    ".situacao-assiduidade-lote"
                ).val()
                || ""
            ).toUpperCase();


        const camposHorario =
            linha.find(
                ".lote-entrada, "
                + ".lote-inicio-intervalo, "
                + ".lote-fim-intervalo, "
                + ".lote-saida"
            );


        if (situacao === "FALTA") {

            camposHorario
                .val("")
                .prop(
                    "disabled",
                    true
                );

        } else {

            camposHorario
                .prop(
                    "disabled",
                    false
                );
        }
    }

    function filtrarFuncionariosAssiduidadeLote() {

        const pesquisa =
            String(
                $("#pesquisaFuncionarioLote")
                    .val()
                || ""
            )
                .trim()
                .toLowerCase();


        $(".linha-funcionario-lote")
            .each(
                function () {

                    const linha =
                        $(this);

                    const texto =
                        linha.text()
                            .toLowerCase();

                    linha.toggle(
                        pesquisa === ""
                        || texto.includes(
                            pesquisa
                        )
                    );
                }
            );
    }


    function atualizarResumoAssiduidadeLote() {

        const linhas =
            $(".linha-funcionario-lote");

        let selecionados = 0;
        let presentes = 0;
        let faltas = 0;


        linhas.each(
            function () {

                const linha =
                    $(this);

                if (
                    !linha.find(
                        ".check-funcionario-lote"
                    ).is(":checked")
                ) {
                    return;
                }

                selecionados++;

                const situacao =
                    String(
                        linha.find(
                            ".situacao-assiduidade-lote"
                        ).val()
                        || ""
                    ).toUpperCase();

                if (
                    situacao === "PRESENTE"
                ) {
                    presentes++;
                }

                if (
                    situacao === "FALTA"
                ) {
                    faltas++;
                }
            }
        );


        $("#resumoAssiduidadeLote")
            .html(
                "<strong>"
                + selecionados
                + "</strong> selecionados"
                + " &nbsp; | &nbsp; "
                + "<strong>"
                + presentes
                + "</strong> presentes"
                + " &nbsp; | &nbsp; "
                + "<strong>"
                + faltas
                + "</strong> faltas"
            );
    }

    function obterDadosAssiduidadeLote() {

        const data =
            String(
                $("#assiduidadeLoteData")
                    .val()
                || ""
            ).trim();


        const origem =
            String(
                $("#assiduidadeLoteOrigem")
                    .val()
                || ""
            )
                .trim()
                .toUpperCase();


        if (data === "") {
            throw new Error(
                "Informe a data da assiduidade."
            );
        }


        if (origem === "") {
            throw new Error(
                "Selecione a origem dos registos."
            );
        }


        const funcionarios = [];


        linhasSelecionadasLote()
            .each(
                function () {

                    const linha =
                        $(this);

                    const funcionarioId =
                        Number(
                            linha.data(
                                "funcionario-id"
                            )
                            || 0
                        );


                    const filialId =
                        Number(
                            linha.data(
                                "filial-id"
                            )
                            || 0
                        );


                    const situacao =
                        String(
                            linha.find(
                                ".situacao-assiduidade-lote"
                            ).val()
                            || ""
                        )
                            .trim()
                            .toUpperCase();


                    const nome =
                        linha.children(
                            "td"
                        )
                            .eq(2)
                            .text()
                            .trim();


                    const entrada =
                        linha.find(
                            ".lote-entrada"
                        ).val();


                    const inicioIntervalo =
                        linha.find(
                            ".lote-inicio-intervalo"
                        ).val();


                    const fimIntervalo =
                        linha.find(
                            ".lote-fim-intervalo"
                        ).val();


                    const saida =
                        linha.find(
                            ".lote-saida"
                        ).val();


                    if (
                        situacao === "PRESENTE"
                    ) {

                        if (!entrada) {
                            throw new Error(
                                nome
                                + ": informe a hora de entrada."
                            );
                        }

                        if (!inicioIntervalo) {
                            throw new Error(
                                nome
                                + ": informe a hora de início do almoço."
                            );
                        }

                        if (!fimIntervalo) {
                            throw new Error(
                                nome
                                + ": informe a hora de regresso do almoço."
                            );
                        }

                        if (!saida) {
                            throw new Error(
                                nome
                                + ": informe a hora de saída."
                            );
                        }


                        if (
                            !(
                                entrada
                                < inicioIntervalo
                                && inicioIntervalo
                                < fimIntervalo
                                && fimIntervalo
                                < saida
                            )
                        ) {

                            throw new Error(
                                nome
                                + ": a sequência das horas é inválida."
                            );
                        }
                    }


                    funcionarios.push({
                        funcionario_id:
                            funcionarioId,

                        filial_id:
                            filialId > 0
                                ? filialId
                                : null,

                        situacao:
                            situacao,

                        entrada:
                            entrada || null,

                        inicio_intervalo:
                            inicioIntervalo
                            || null,

                        fim_intervalo:
                            fimIntervalo
                            || null,

                        saida:
                            saida || null,

                        observacoes:
                            String(
                                linha.find(
                                    ".lote-observacao"
                                ).val()
                                || ""
                            ).trim()
                    });
                }
            );


        if (
            funcionarios.length === 0
        ) {
            throw new Error(
                "Selecione pelo menos um funcionário."
            );
        }


        return {
            acao:
                "guardar_assiduidade_lote",

            data:
                data,

            origem:
                origem,

            dispositivo:
                String(
                    $("#assiduidadeLoteDispositivo")
                        .val()
                    || ""
                ).trim(),

            identificador_dispositivo:
                String(
                    $("#assiduidadeLoteIdentificador")
                        .val()
                    || ""
                ).trim(),

            observacoes:
                String(
                    $("#assiduidadeLoteObservacao")
                        .val()
                    || ""
                ).trim(),

            funcionarios:
                funcionarios
        };
    }

    async function guardarAssiduidadeLote() {

        const botao =
            $("#btnGuardarAssiduidadeLote");


        try {

            /*
             * IMPORTANTE:
             * validação dentro do TRY.
             */
            const dados =
                obterDadosAssiduidadeLote();


            botao
                .prop(
                    "disabled",
                    true
                )
                .html(
                    '<i class="fa fa-spinner fa-spin"></i> '
                    + "A guardar..."
                );


            $("#alertaAssiduidadeLote")
                .hide()
                .text("");


            const resposta =
                await requisicaoAssiduidade(
                    ASSIDUIDADE_URL
                    + "?acao=guardar_assiduidade_lote",
                    {
                        method:
                            "POST",

                        headers: {
                            "Content-Type":
                                "application/json"
                        },

                        body:
                            JSON.stringify(
                                dados
                            )
                    }
                );


            $("#modalAssiduidadeLote")
                .modal("hide");


            await Swal.fire({

                icon:
                    "success",

                title:
                    "Assiduidade registada",

                text:
                    resposta.mensagem
                    || "Assiduidade registada com sucesso.",

                confirmButtonText:
                    "Concluir"

            });


            await carregarTodasListas();


        } catch (erro) {

            console.error(
                "Erro ao guardar assiduidade:",
                erro
            );

            $("#alertaAssiduidadeLote")
                .hide()
                .text("");

            await mostrarErroAssiduidade(
                erro.message
                || "Não foi possível guardar a assiduidade."
            );

        } finally {

            botao
                .prop(
                    "disabled",
                    false
                )
                .html(
                    '<i class="fa fa-save"></i> '
                    + "Guardar Assiduidade"
                );
        }
    }

    function limparModalAssiduidadeLote() {

        $("#assiduidadeLoteData")
            .val("");

        $("#assiduidadeLoteFilial")
            .val("");

        $("#assiduidadeLoteDispositivo")
            .val("");

        $("#assiduidadeLoteIdentificador")
            .val("");

        $("#assiduidadeLoteObservacao")
            .val("");

        $("#loteEntradaPadrao")
            .val("");

        $("#loteInicioIntervaloPadrao")
            .val("");

        $("#loteFimIntervaloPadrao")
            .val("");

        $("#loteSaidaPadrao")
            .val("");

        $("#pesquisaFuncionarioLote")
            .val("");

        $("#selecionarTodosLote")
            .prop(
                "checked",
                false
            );

        $("#alertaAssiduidadeLote")
            .hide()
            .text("");

        $("#listaFuncionariosLote")
            .empty();
    }

    // ==================================================
    // CARREGAR TODAS AS LISTAS
    // ==================================================

    async function carregarTodasListas() {

        validarPeriodoFiltro();

        mostrarCarregamentoAssiduidade(true);

        try {

            await Promise.all([
                carregarResumo(),
                carregarRegistosPonto(),
                carregarAssiduidade(),
                carregarFaltas(),
                carregarHorasExtras()
            ]);

        } catch (erro) {

            mostrarErroAssiduidade(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoAssiduidade(false);

        }

    }


    // ==================================================
    // RESUMO
    // ==================================================

    async function carregarResumo() {

        const parametros =
            new URLSearchParams({
                acao:
                    "resumo",

                data_inicio:
                    String(
                        $("#filtroAssiduidadeDataInicio")
                            .val() || ""
                    ),

                data_fim:
                    String(
                        $("#filtroAssiduidadeDataFim")
                            .val() || ""
                    )
            });

        const resposta =
            await requisicaoAssiduidade(
                ASSIDUIDADE_URL
                + "?"
                + parametros.toString()
            );

        atualizarResumoAssiduidade(
            resposta.data || {}
        );

    }


    function atualizarResumoAssiduidade(
        resumo
    ) {

        definirTexto(
            "#resumoTotalFuncionariosAssiduidade",
            numeroInteiro(
                resumo.total_funcionarios
            )
        );

        definirTexto(
            "#resumoFuncionariosRegistados",
            numeroInteiro(
                resumo.funcionarios_registados
            )
        );

        definirTexto(
            "#resumoPresencasAssiduidade",
            numeroInteiro(
                resumo.presencas
            )
        );

        definirTexto(
            "#resumoFaltasAssiduidade",
            numeroInteiro(
                resumo.faltas
            )
        );

        definirTexto(
            "#resumoAtrasosAssiduidade",
            numeroInteiro(
                resumo.atrasos
            )
        );

        definirTexto(
            "#resumoSaidasAntecipadas",
            numeroInteiro(
                resumo.saidas_antecipadas
            )
        );

        definirTexto(
            "#resumoHorasTrabalhadas",
            formatarMinutosLongos(
                resumo.minutos_trabalhados
            )
        );

        definirTexto(
            "#resumoHorasExtrasAssiduidade",
            formatarMinutosLongos(
                resumo.minutos_extra
            )
        );

        definirTexto(
            "#resumoTaxaAssiduidade",
            formatarPercentagem(
                resumo.taxa_assiduidade
            )
        );

    }


    // ==================================================
    // PREENCHER SELECTS
    // ==================================================

    function preencherSelectsAssiduidade() {

        preencherSelectId(
            "#filtroAssiduidadeFuncionarioId",
            funcionariosCache,
            "Todos os funcionários",
            function (item) {

                return (
                    (item.numero_funcionario || "-")
                    + " - "
                    + (item.nome_completo || "-")
                );

            }
        );

        preencherSelectId(
            "#filtroAssiduidadeFilialId",
            filiaisCache,
            "Todas as filiais",
            function (item) {

                return item.nome || "-";

            }
        );

        preencherSelectCodigo(
            "#filtroAssiduidadeOcorrencia",
            ocorrenciasCache,
            "Todas as ocorrências"
        );

        preencherSelectId(
            "#filtroAssiduidadeStatusId",
            statusAssiduidadeCache,
            "Todos os estados",
            function (item) {

                return item.nome || "-";

            }
        );

        preencherSelectCodigo(
            "#filtroRegistoPontoTipo",
            tiposRegistoCache,
            "Todos os tipos"
        );

        preencherSelectCodigo(
            "#filtroRegistoPontoOrigem",
            origensRegistoCache,
            "Todas as origens"
        );

        preencherSelectId(
            "#registoPontoFuncionarioId",
            funcionariosCache,
            "Selecione o funcionário",
            function (item) {

                return (
                    (item.numero_funcionario || "-")
                    + " - "
                    + (item.nome_completo || "-")
                );

            }
        );

        preencherSelectId(
            "#registoPontoFilialId",
            filiaisCache,
            "Sem filial definida",
            function (item) {

                return [
                    item.codigo || "",
                    item.nome || ""
                ]
                    .filter(Boolean)
                    .join(" - ");

            }
        );

        preencherSelectCodigo(
            "#registoPontoTipo",
            tiposRegistoCache,
            "Selecione o tipo"
        );

        preencherSelectCodigo(
            "#registoPontoOrigem",
            origensRegistoCache,
            "Selecione a origem"
        );

        preencherSelectId(
            "#registoPontoStatusId",
            statusRegistoCache,
            "Selecione o estado",
            function (item) {

                return item.nome || "-";

            }
        );

        const statusPendente =
            statusRegistoCache.find(
                function (item) {

                    return normalizarTexto(
                        item.nome
                    ) === "pendente";

                }
            );

        if (statusPendente) {

            $("#registoPontoStatusId")
                .data(
                    "status-pendente-id",
                    Number(
                        statusPendente.id
                    )
                );

        }

        preencherSelectCodigo(
            "#justificarFaltaTipo",
            tiposFaltaCache,
            "Selecione o tipo de falta"
        );

    }


    // ==================================================
    // LISTAR REGISTOS DE PONTO
    // ==================================================

    async function carregarRegistosPonto() {

        const parametros =
            criarParametrosFiltros(
                "listar_registos_ponto"
            );

        adicionarParametro(
            parametros,
            "tipo_registo",
            $("#filtroRegistoPontoTipo")
                .val()
        );

        adicionarParametro(
            parametros,
            "origem",
            $("#filtroRegistoPontoOrigem")
                .val()
        );

        const resposta =
            await requisicaoAssiduidade(
                ASSIDUIDADE_URL
                + "?"
                + parametros.toString()
            );

        registosPontoCache =
            arraySeguro(
                resposta.data
            );

        renderizarTabelaRegistosPonto(
            registosPontoCache
        );

    }


    function renderizarTabelaRegistosPonto(
        registos
    ) {

        destruirDataTable(
            "#registosPontoTable",
            tabelaRegistosPonto
        );

        tabelaRegistosPonto = null;

        const tbody =
            $("#registosPontoTable tbody");

        tbody.empty();

        if (!registos.length) {

            tbody.html(`
                <tr>
                    <td
                        colspan="10"
                        class="text-center text-muted py-4">

                        Nenhum registo de ponto encontrado.

                    </td>
                </tr>
            `);

            return;

        }

        registos.forEach(
            function (registo) {

                const estado =
                    String(
                        registo.status_nome || "-"
                    );

                tbody.append(`
    <tr>

        <td class="text-center">

            ${podeValidarRegisto(registo)
                        ? `
                    <input
                        type="checkbox"
                        class="checkbox-registo-ponto"
                        data-id="${Number(registo.id)}"
                        ${registosPontoSelecionados.has(
                            Number(registo.id)
                        )
                            ? "checked"
                            : ""
                        }>
                `
                        : ""
                    }

        </td>

                        <td>
                            ${formatarData(
                        registo.data_registo
                    )}
                        </td>

                        <td>
                            ${formatarHora(
                        registo.hora_registo
                    )}
                        </td>

                        <td>
                            <strong>
                                ${escaparHtml(
                        registo.funcionario_nome || "-"
                    )}
                            </strong>

                            <div class="text-muted small">
                                ${escaparHtml(
                        registo.numero_funcionario || "-"
                    )}
                            </div>
                        </td>

                        <td>
                            ${escaparHtml(
                        formatarCodigo(
                            registo.tipo_registo
                        )
                    )}
                        </td>

                        <td>
                            ${escaparHtml(
                        formatarCodigo(
                            registo.origem
                        )
                    )}
                        </td>

                        <td>
                            ${escaparHtml(
                        registo.filial_nome || "-"
                    )}
                        </td>

                        <td>
                            ${Number(
                        registo.corrigido || 0
                    ) === 1
                        ? `
                                        <span class="badge badge-warning">
                                            Corrigido
                                        </span>
                                    `
                        : `
                                        <span class="badge badge-light">
                                            Original
                                        </span>
                                    `
                    }
                        </td>

                        <td>
                            <span class="badge ${classeStatusRegisto(
                        estado
                    )}">
                                ${escaparHtml(estado)}
                            </span>
                        </td>

                        <td class="text-right">

                            <div class="dropdown dropdown-action">

                                <a
                                    href="#"
                                    class="action-icon dropdown-toggle"
                                    data-toggle="dropdown">

                                    <i class="material-icons">
                                        more_vert
                                    </i>

                                </a>

                                <div class="dropdown-menu dropdown-menu-right">

                                    <button
                                        type="button"
                                        class="dropdown-item btn-ver-registo-ponto"
                                        data-id="${Number(
                        registo.id
                    )}">

                                        <i class="fa fa-eye m-r-5"></i>
                                        Ver detalhes

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item btn-editar-registo-ponto"
                                        data-id="${Number(
                        registo.id
                    )}">

                                        <i class="fa fa-pencil m-r-5"></i>
                                        Editar

                                    </button>

                                    ${podeValidarRegisto(
                        registo
                    )
                        ? `
                                                <button
                                                    type="button"
                                                    class="dropdown-item btn-validar-registo-ponto"
                                                    data-id="${Number(
                            registo.id
                        )}">

                                                    <i class="fa fa-check m-r-5"></i>
                                                    Validar

                                                </button>
                                            `
                        : ""
                    }

                                    ${podeAnularRegisto(
                        registo
                    )
                        ? `
                                                <button
                                                    type="button"
                                                    class="dropdown-item text-danger btn-anular-registo-ponto"
                                                    data-id="${Number(
                            registo.id
                        )}">

                                                    <i class="fa fa-ban m-r-5"></i>
                                                    Anular

                                                </button>
                                            `
                        : ""
                    }

                                </div>

                            </div>

                        </td>

                    </tr>
                `);

            }
        );

        tabelaRegistosPonto =
            iniciarDataTable(
                "#registosPontoTable",
                9,
                [
                    [
                        1,
                        "desc"
                    ],
                    [
                        2,
                        "desc"
                    ]
                ]
            );

        $("#registosPontoTable")
            .off("draw.dt.selecao")
            .on(
                "draw.dt.selecao",
                atualizarSelecaoRegistosPonto
            );

        atualizarSelecaoRegistosPonto();

    }

    function atualizarSelecaoRegistosPonto() {

        const caixasVisiveis =
            $("#registosPontoTable tbody .checkbox-registo-ponto:visible");

        const marcadasVisiveis =
            caixasVisiveis
                .filter(":checked")
                .length;

        $("#selecionarTodosRegistosPonto")
            .prop(
                "checked",
                caixasVisiveis.length > 0
                && marcadasVisiveis === caixasVisiveis.length
            )
            .prop(
                "indeterminate",
                marcadasVisiveis > 0
                && marcadasVisiveis < caixasVisiveis.length
            );

        $("#totalRegistosPontoSelecionados")
            .text(
                registosPontoSelecionados.size
            );

        $("#btnValidarRegistosPontoSelecionados")
            .prop(
                "disabled",
                registosPontoSelecionados.size === 0
            );

    }


    // ==================================================
    // NOVO REGISTO DE PONTO
    // ==================================================

    function abrirModalNovoRegistoPonto() {

        limparFormularioRegistoPonto();

        $("#modalRegistoPontoTitulo")
            .text(
                "Novo Registo de Ponto"
            );

        $("#btnGuardarRegistoPontoTexto")
            .text(
                "Guardar Registo"
            );

        $("#registoPontoData")
            .val(
                dataAtualIso()
            );

        $("#registoPontoHora")
            .val(
                horaAtual()
            );

        $("#registoPontoOrigem")
            .val("MANUAL");

        const statusPendenteId =
            Number(
                $("#registoPontoStatusId")
                    .data(
                        "status-pendente-id"
                    )
                || 0
            );

        if (statusPendenteId > 0) {

            $("#registoPontoStatusId")
                .val(
                    String(
                        statusPendenteId
                    )
                );

        }

        $("#modalRegistoPonto")
            .modal("show");

        setTimeout(
            function () {

                $("#registoPontoFuncionarioId")
                    .trigger("focus");

            },
            300
        );

    }


    // ==================================================
    // EDITAR REGISTO
    // ==================================================

    async function abrirModalEditarRegistoPonto(
        registoId
    ) {

        if (registoId <= 0) {

            mostrarErroAssiduidade(
                "Registo de ponto inválido."
            );

            return;

        }

        mostrarCarregamentoAssiduidade(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "buscar_registo_ponto",

                    id:
                        String(registoId)
                });

            const resposta =
                await requisicaoAssiduidade(
                    ASSIDUIDADE_URL
                    + "?"
                    + parametros.toString()
                );

            const registo =
                resposta.data || {};

            limparFormularioRegistoPonto();

            preencherFormularioRegistoPonto(
                registo
            );

            $("#modalRegistoPontoTitulo")
                .text(
                    "Editar Registo de Ponto"
                );

            $("#btnGuardarRegistoPontoTexto")
                .text(
                    "Atualizar Registo"
                );

            $("#modalRegistoPonto")
                .modal("show");

        } catch (erro) {

            mostrarErroAssiduidade(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoAssiduidade(false);

        }

    }


    function preencherFormularioRegistoPonto(
        registo
    ) {

        $("#registoPontoId")
            .val(
                registo.id || ""
            );

        $("#registoPontoFuncionarioId")
            .val(
                registo.funcionario_id
                    ? String(
                        registo.funcionario_id
                    )
                    : ""
            );

        $("#registoPontoFilialId")
            .val(
                registo.filial_id
                    ? String(
                        registo.filial_id
                    )
                    : ""
            );

        $("#registoPontoData")
            .val(
                registo.data_registo || ""
            );

        $("#registoPontoHora")
            .val(
                formatarHoraInput(
                    registo.hora_registo
                )
            );

        $("#registoPontoTipo")
            .val(
                registo.tipo_registo || ""
            );

        $("#registoPontoOrigem")
            .val(
                registo.origem || "MANUAL"
            );

        $("#registoPontoDispositivo")
            .val(
                registo.dispositivo || ""
            );

        $("#registoPontoIdentificadorDispositivo")
            .val(
                registo.identificador_dispositivo
                || ""
            );

        $("#registoPontoLatitude")
            .val(
                registo.latitude ?? ""
            );

        $("#registoPontoLongitude")
            .val(
                registo.longitude ?? ""
            );

        $("#registoPontoEnderecoLocalizacao")
            .val(
                registo.endereco_localizacao
                || ""
            );

        $("#registoPontoObservacoes")
            .val(
                registo.observacoes || ""
            );

        $("#registoPontoJustificacao")
            .val(
                registo.justificacao || ""
            );

        $("#registoPontoStatusId")
            .val(
                registo.status_id
                    ? String(
                        registo.status_id
                    )
                    : ""
            );

        $("#registoPontoRegistoOriginalId")
            .val(
                registo.registo_original_id
                || ""
            );

        $("#registoPontoCorrigido")
            .prop(
                "checked",
                Number(
                    registo.corrigido || 0
                ) === 1
            );

    }


    // ==================================================
    // GUARDAR REGISTO DE PONTO
    // ==================================================

    async function guardarRegistoPonto() {

        const modo =
            String(
                $("#registoIndividualModo")
                    .val()
                || "PONTO"
            ).toUpperCase();


        if (modo === "FALTA") {

            await guardarFaltaIndividual();

            return;
        }

        const dados =
            obterDadosFormularioRegistoPonto();

        validarFormularioRegistoPonto(
            dados
        );

        bloquearModalRegistoPonto(true);

        try {

            const formData =
                criarFormData(
                    dados
                );

            formData.append(
                "acao",
                dados.id > 0
                    ? "editar_registo_ponto"
                    : "criar_registo_ponto"
            );

            const resposta =
                await requisicaoAssiduidade(
                    ASSIDUIDADE_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            $("#modalRegistoPonto")
                .modal("hide");

            await Swal.fire({
                icon:
                    "success",

                title:
                    dados.id > 0
                        ? "Registo atualizado"
                        : "Registo criado",

                text:
                    resposta.mensagem
                    || "Operação concluída com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await carregarTodasListas();

        } catch (erro) {

            mostrarErroAssiduidade(
                erro.message
            );

            throw erro;

        } finally {

            bloquearModalRegistoPonto(false);

        }

    }

    async function guardarFaltaIndividual() {

        const funcionarioId =
            Number(
                $("#registoPontoFuncionarioId")
                    .val()
                || 0
            );

        const data =
            textoCampo(
                "#registoPontoData"
            );

        const observacoes =
            textoCampo(
                "#registoPontoObservacoes"
            );


        if (funcionarioId <= 0) {
            throw new Error(
                "Selecione o funcionário."
            );
        }


        if (data === "") {
            throw new Error(
                "Informe a data da falta."
            );
        }


        bloquearModalRegistoPonto(
            true
        );


        try {

            const formData =
                new FormData();

            formData.append(
                "acao",
                "registar_falta"
            );

            formData.append(
                "funcionario_id",
                String(funcionarioId)
            );

            formData.append(
                "data",
                data
            );

            formData.append(
                "observacoes",
                observacoes
            );


            const resposta =
                await requisicaoAssiduidade(
                    ASSIDUIDADE_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );


            $("#modalRegistoPonto")
                .modal("hide");


            await Swal.fire({

                icon:
                    "success",

                title:
                    "Falta registada",

                text:
                    resposta.mensagem
                    || "Falta registada com sucesso.",

                confirmButtonText:
                    "Concluir"
            });


            await carregarTodasListas();


        } finally {

            bloquearModalRegistoPonto(
                false
            );
        }
    }


    function obterDadosFormularioRegistoPonto() {

        return {
            id:
                Number(
                    $("#registoPontoId")
                        .val() || 0
                ),

            funcionario_id:
                Number(
                    $("#registoPontoFuncionarioId")
                        .val() || 0
                ),

            filial_id:
                inteiroOpcional(
                    $("#registoPontoFilialId")
                        .val()
                ),

            data_registo:
                textoCampo(
                    "#registoPontoData"
                ),

            hora_registo:
                textoCampo(
                    "#registoPontoHora"
                ),

            tipo_registo:
                textoCampo(
                    "#registoPontoTipo"
                ),

            origem:
                textoCampo(
                    "#registoPontoOrigem"
                ),

            dispositivo:
                textoCampo(
                    "#registoPontoDispositivo"
                ),

            identificador_dispositivo:
                textoCampo(
                    "#registoPontoIdentificadorDispositivo"
                ),

            latitude:
                decimalOpcional(
                    $("#registoPontoLatitude")
                        .val()
                ),

            longitude:
                decimalOpcional(
                    $("#registoPontoLongitude")
                        .val()
                ),

            endereco_localizacao:
                textoCampo(
                    "#registoPontoEnderecoLocalizacao"
                ),

            observacoes:
                textoCampo(
                    "#registoPontoObservacoes"
                ),

            justificacao:
                textoCampo(
                    "#registoPontoJustificacao"
                ),

            registo_original_id:
                inteiroOpcional(
                    $("#registoPontoRegistoOriginalId")
                        .val()
                ),

            corrigido:
                $("#registoPontoCorrigido")
                    .is(":checked")
                    ? 1
                    : 0
        };

    }


    function validarFormularioRegistoPonto(
        dados
    ) {

        if (dados.funcionario_id <= 0) {
            throw new Error(
                "Selecione o funcionário."
            );
        }

        if (dados.data_registo === "") {
            throw new Error(
                "Informe a data do registo."
            );
        }

        if (dados.hora_registo === "") {
            throw new Error(
                "Informe a hora do registo."
            );
        }

        if (dados.tipo_registo === "") {
            throw new Error(
                "Selecione o tipo de registo."
            );
        }

        if (dados.origem === "") {
            throw new Error(
                "Selecione a origem do registo."
            );
        }


        if (
            dados.latitude !== null
            && (
                dados.latitude < -90
                || dados.latitude > 90
            )
        ) {
            throw new Error(
                "A latitude deve estar entre -90 e 90."
            );
        }

        if (
            dados.longitude !== null
            && (
                dados.longitude < -180
                || dados.longitude > 180
            )
        ) {
            throw new Error(
                "A longitude deve estar entre -180 e 180."
            );
        }

        validarDataHoraRegisto();

    }


    function preencherFilialDoFuncionario() {

        const funcionarioId =
            Number(
                $("#registoPontoFuncionarioId")
                    .val() || 0
            );

        const funcionario =
            funcionariosCache.find(
                function (item) {

                    return Number(item.id)
                        === funcionarioId;

                }
            );

        if (!funcionario) {
            return;
        }

        $("#registoPontoFilialId")
            .val(
                funcionario.filial_id
                    ? String(
                        funcionario.filial_id
                    )
                    : ""
            );

    }


    // ==================================================
    // VALIDAR REGISTO
    // ==================================================

    async function confirmarValidarRegistoPonto(
        registoId
    ) {

        const registo =
            encontrarPorId(
                registosPontoCache,
                registoId
            );

        if (!registo) {

            mostrarErroAssiduidade(
                "Registo de ponto não encontrado."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "question",

                title:
                    "Validar registo de ponto?",

                html:
                    `
                        <strong>
                            ${escaparHtml(
                        registo.funcionario_nome
                        || "-"
                    )}
                        </strong>

                        <div class="mt-2">
                            ${formatarData(
                        registo.data_registo
                    )}
                            às
                            ${formatarHora(
                        registo.hora_registo
                    )}
                        </div>
                    `,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, validar",

                cancelButtonText:
                    "Cancelar",

                reverseButtons:
                    true
            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        await executarAcaoPost(
            "validar_registo_ponto",
            {
                id:
                    registoId
            },
            "Registo validado",
            "O registo de ponto foi validado com sucesso."
        );

    }

    async function confirmarValidarRegistosPontoSelecionados() {

        const ids =
            Array.from(
                registosPontoSelecionados
            );

        if (!ids.length) {

            mostrarErroAssiduidade(
                "Selecione pelo menos um registo pendente."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({

                icon:
                    "question",

                title:
                    "Validar registos selecionados?",

                text:
                    `${ids.length} registo(s) serão validados.`,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, validar todos",

                cancelButtonText:
                    "Cancelar",

                reverseButtons:
                    true

            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        await executarAcaoPost(
            "validar_registos_ponto_lote",
            {
                ids:
                    ids
            },
            "Registos validados",
            `${ids.length} registo(s) foram validados com sucesso.`
        );

        registosPontoSelecionados.clear();

        $("#registosPontoTable .checkbox-registo-ponto")
            .prop(
                "checked",
                false
            );

        atualizarSelecaoRegistosPonto();

    }


    // ==================================================
    // ANULAR REGISTO
    // ==================================================

    async function confirmarAnularRegistoPonto(
        registoId
    ) {

        const registo =
            encontrarPorId(
                registosPontoCache,
                registoId
            );

        if (!registo) {

            mostrarErroAssiduidade(
                "Registo de ponto não encontrado."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Anular registo de ponto?",

                input:
                    "textarea",

                inputLabel:
                    "Justificação",

                inputPlaceholder:
                    "Informe o motivo da anulação",

                inputAttributes: {
                    maxlength:
                        1000
                },

                showCancelButton:
                    true,

                confirmButtonText:
                    "Anular registo",

                cancelButtonText:
                    "Cancelar",

                confirmButtonColor:
                    "#d33",

                reverseButtons:
                    true,

                inputValidator:
                    function (valor) {

                        if (
                            !String(
                                valor || ""
                            ).trim()
                        ) {
                            return "Informe a justificação.";
                        }

                        return undefined;

                    }
            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        await executarAcaoPost(
            "anular_registo_ponto",
            {
                id:
                    registoId,

                justificacao:
                    String(
                        confirmacao.value || ""
                    ).trim()
            },
            "Registo anulado",
            "O registo foi anulado e a assiduidade foi recalculada."
        );

    }


    // ==================================================
    // DETALHES DO REGISTO
    // ==================================================

    function mostrarDetalhesRegistoPonto(
        registoId
    ) {

        const registo =
            encontrarPorId(
                registosPontoCache,
                registoId
            );

        if (!registo) {

            mostrarErroAssiduidade(
                "Registo de ponto não encontrado."
            );

            return;

        }

        Swal.fire({
            icon:
                "info",

            title:
                "Registo de Ponto",

            width:
                850,

            html:
                `
                    <div class="text-left">

                        <div class="row">

                            ${campoDetalhe(
                    "Funcionário",
                    registo.funcionario_nome || "-"
                )}

                            ${campoDetalhe(
                    "Número",
                    registo.numero_funcionario || "-"
                )}

                            ${campoDetalhe(
                    "Data",
                    formatarData(
                        registo.data_registo
                    )
                )}

                            ${campoDetalhe(
                    "Hora",
                    formatarHora(
                        registo.hora_registo
                    )
                )}

                            ${campoDetalhe(
                    "Tipo",
                    formatarCodigo(
                        registo.tipo_registo
                    )
                )}

                            ${campoDetalhe(
                    "Origem",
                    formatarCodigo(
                        registo.origem
                    )
                )}

                            ${campoDetalhe(
                    "Filial",
                    registo.filial_nome || "-"
                )}

                            ${campoDetalhe(
                    "Estado",
                    registo.status_nome || "-"
                )}

                            ${campoDetalhe(
                    "Dispositivo",
                    registo.dispositivo || "-"
                )}

                            ${campoDetalhe(
                    "Identificador",
                    registo.identificador_dispositivo
                    || "-"
                )}

                            ${campoDetalhe(
                    "Latitude",
                    registo.latitude ?? "-"
                )}

                            ${campoDetalhe(
                    "Longitude",
                    registo.longitude ?? "-"
                )}

                            ${campoDetalhe(
                    "Validado por",
                    registo.validado_por_nome || "-"
                )}

                            ${campoDetalhe(
                    "Validado em",
                    formatarDataHora(
                        registo.validado_em
                    )
                )}

                        </div>

                        <hr>

                        <strong>Localização:</strong>

                        <div class="mt-2 mb-3">
                            ${textoLongo(
                    registo.endereco_localizacao
                )}
                        </div>

                        <strong>Observações:</strong>

                        <div class="mt-2 mb-3">
                            ${textoLongo(
                    registo.observacoes
                )}
                        </div>

                        <strong>Justificação:</strong>

                        <div class="mt-2">
                            ${textoLongo(
                    registo.justificacao
                )}
                        </div>

                    </div>
                `,

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // LISTAR ASSIDUIDADE
    // ==================================================

    async function carregarAssiduidade() {

        const parametros =
            criarParametrosFiltros(
                "listar_assiduidade"
            );

        adicionarParametro(
            parametros,
            "ocorrencia",
            $("#filtroAssiduidadeOcorrencia")
                .val()
        );

        const resposta =
            await requisicaoAssiduidade(
                ASSIDUIDADE_URL
                + "?"
                + parametros.toString()
            );

        assiduidadeCache =
            arraySeguro(
                resposta.data
            );

        renderizarTabelaAssiduidade(
            assiduidadeCache
        );

    }


    function renderizarTabelaAssiduidade(
        registos
    ) {

        destruirDataTable(
            "#assiduidadeTable",
            tabelaAssiduidade
        );

        tabelaAssiduidade = null;

        const tbody =
            $("#assiduidadeTable tbody");

        tbody.empty();

        if (!registos.length) {

            tbody.html(`
                <tr>
                    <td
                        colspan="13"
                        class="text-center text-muted py-4">

                        Nenhum registo de assiduidade encontrado.

                    </td>
                </tr>
            `);

            return;

        }

        registos.forEach(
            function (registo) {

                const estado =
                    String(
                        registo.status_nome || "-"
                    );

                tbody.append(`
    <tr>

        <td class="text-center">

            ${podeAprovarAssiduidade(registo)
                        ? `
                    <input
                        type="checkbox"
                        class="checkbox-assiduidade"
                        data-id="${Number(registo.id)}"
                        ${assiduidadesSelecionadas.has(
                            Number(registo.id)
                        )
                            ? "checked"
                            : ""
                        }>
                `
                        : ""
                    }

        </td>

                        <td>
                            ${formatarData(
                        registo.data_assiduidade
                    )}
                        </td>

                        <td>
                            <strong>
                                ${escaparHtml(
                        registo.funcionario_nome || "-"
                    )}
                            </strong>

                            <div class="text-muted small">
                                ${escaparHtml(
                        registo.numero_funcionario || "-"
                    )}
                            </div>
                        </td>

                        <td>
                            ${escaparHtml(
                        registo.filial_nome || "-"
                    )}
                        </td>

                        <td>
                            ${formatarHora(
                        registo.hora_real_entrada
                    )}
                        </td>

                        <td>
                            ${formatarHora(
                        registo.hora_real_saida
                    )}
                        </td>

                        <td>
                            ${formatarMinutosLongos(
                        registo.minutos_trabalhados
                    )}
                        </td>

                        <td>
                            ${formatarMinutosLongos(
                        registo.minutos_atraso
                    )}
                        </td>

                        <td>
                            ${formatarMinutosLongos(
                        registo.minutos_extra
                    )}
                        </td>

                        <td>
                            ${formatarMinutosLongos(
                        registo.minutos_falta
                    )}
                        </td>

                        <td>
                            <span class="badge ${classeOcorrencia(
                        registo.ocorrencia
                    )}">
                                ${escaparHtml(
                        formatarCodigo(
                            registo.ocorrencia
                        )
                    )}
                            </span>
                        </td>

                        <td>
                            <span class="badge ${classeStatusAssiduidade(
                        estado
                    )}">
                                ${escaparHtml(estado)}
                            </span>
                        </td>

                        <td class="text-right">

                            <div class="dropdown dropdown-action">

                                <a
                                    href="#"
                                    class="action-icon dropdown-toggle"
                                    data-toggle="dropdown">

                                    <i class="material-icons">
                                        more_vert
                                    </i>

                                </a>

                                <div class="dropdown-menu dropdown-menu-right">

                                    <button
                                        type="button"
                                        class="dropdown-item btn-ver-assiduidade"
                                        data-id="${Number(
                        registo.id
                    )}">

                                        <i class="fa fa-eye m-r-5"></i>
                                        Ver detalhes

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item btn-apurar-assiduidade"
                                        data-funcionario-id="${Number(
                        registo.funcionario_id
                    )}"
                                        data-data="${escaparHtml(
                        registo.data_assiduidade
                    )}">

                                        <i class="fa fa-refresh m-r-5"></i>
                                        Reapurar

                                    </button>

                                    ${podeAprovarAssiduidade(
                        registo
                    )
                        ? `
                                                <button
                                                    type="button"
                                                    class="dropdown-item btn-aprovar-assiduidade"
                                                    data-id="${Number(
                            registo.id
                        )}">

                                                    <i class="fa fa-check m-r-5"></i>
                                                    Aprovar

                                                </button>
                                            `
                        : ""
                    }

                                </div>

                            </div>

                        </td>

                    </tr>
                `);

            }
        );

        tabelaAssiduidade =
            iniciarDataTable(
                "#assiduidadeTable",
                12,
                [
                    [
                        1,
                        "desc"
                    ],
                    [
                        2,
                        "asc"
                    ]
                ]
            );

        $("#assiduidadeTable")
            .off(
                "draw.dt.selecaoAssiduidade"
            )
            .on(
                "draw.dt.selecaoAssiduidade",
                atualizarSelecaoAssiduidades
            );

        atualizarSelecaoAssiduidades();

    }

    function atualizarSelecaoAssiduidades() {

        const caixasVisiveis =
            $("#assiduidadeTable tbody .checkbox-assiduidade:visible");

        const marcadasVisiveis =
            caixasVisiveis
                .filter(":checked")
                .length;

        $("#selecionarTodasAssiduidades")
            .prop(
                "checked",
                caixasVisiveis.length > 0
                && marcadasVisiveis
                === caixasVisiveis.length
            )
            .prop(
                "indeterminate",
                marcadasVisiveis > 0
                && marcadasVisiveis
                < caixasVisiveis.length
            );

        $("#totalAssiduidadesSelecionadas")
            .text(
                assiduidadesSelecionadas.size
            );

        $("#btnAprovarAssiduidadesSelecionadas")
            .prop(
                "disabled",
                assiduidadesSelecionadas.size === 0
            );

    }


    // ==================================================
    // APURAR DIA
    // ==================================================

    async function confirmarApurarDia(
        funcionarioId,
        data
    ) {

        if (
            funcionarioId <= 0
            || !data
        ) {

            mostrarErroAssiduidade(
                "Funcionário ou data inválidos."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "question",

                title:
                    "Reapurar assiduidade?",

                text:
                    "Os cálculos de presença, atraso, falta e horas extras serão atualizados.",

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, reapurar",

                cancelButtonText:
                    "Cancelar",

                reverseButtons:
                    true
            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        await executarAcaoPost(
            "apurar_dia",
            {
                funcionario_id:
                    funcionarioId,

                data:
                    data
            },
            "Assiduidade apurada",
            "O dia foi reapurado com sucesso."
        );

    }


    // ==================================================
    // APROVAR ASSIDUIDADE
    // ==================================================

    async function confirmarAprovarAssiduidade(
        assiduidadeId
    ) {

        const registo =
            encontrarPorId(
                assiduidadeCache,
                assiduidadeId
            );

        if (!registo) {

            mostrarErroAssiduidade(
                "Assiduidade diária não encontrada."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "question",

                title:
                    "Aprovar assiduidade?",

                html:
                    `
                        <strong>
                            ${escaparHtml(
                        registo.funcionario_nome
                        || "-"
                    )}
                        </strong>

                        <div class="mt-2">
                            ${formatarData(
                        registo.data_assiduidade
                    )}
                        </div>
                    `,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, aprovar",

                cancelButtonText:
                    "Cancelar",

                reverseButtons:
                    true
            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        await executarAcaoPost(
            "aprovar_assiduidade",
            {
                id:
                    assiduidadeId
            },
            "Assiduidade aprovada",
            "A assiduidade diária foi aprovada."
        );

    }

    async function confirmarAprovarAssiduidadesSelecionadas() {

        const ids =
            Array.from(
                assiduidadesSelecionadas
            );

        if (!ids.length) {

            mostrarErroAssiduidade(
                "Selecione pelo menos uma assiduidade para aprovar."
            );

            return;

        }

        if (ids.length > 500) {

            mostrarErroAssiduidade(
                "Selecione no máximo 500 registos de cada vez."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({

                icon:
                    "question",

                title:
                    "Aprovar assiduidades selecionadas?",

                text:
                    `${ids.length} registo(s) serão aprovados.`,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, aprovar todos",

                cancelButtonText:
                    "Cancelar",

                reverseButtons:
                    true

            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        await executarAcaoPost(
            "aprovar_assiduidades_lote",
            {
                ids:
                    ids
            },
            "Assiduidades aprovadas",
            `${ids.length} registo(s) foram aprovados com sucesso.`
        );

        assiduidadesSelecionadas.clear();

        $("#assiduidadeTable .checkbox-assiduidade")
            .prop(
                "checked",
                false
            );

        atualizarSelecaoAssiduidades();

    }

    // ==================================================
    // DETALHES DA ASSIDUIDADE
    // ==================================================

    function mostrarDetalhesAssiduidade(
        assiduidadeId
    ) {

        const registo =
            encontrarPorId(
                assiduidadeCache,
                assiduidadeId
            );

        if (!registo) {

            mostrarErroAssiduidade(
                "Assiduidade diária não encontrada."
            );

            return;

        }

        Swal.fire({
            icon:
                "info",

            title:
                "Assiduidade Diária",

            width:
                950,

            html:
                `
                    <div class="text-left">

                        <div class="row">

                            ${campoDetalhe(
                    "Funcionário",
                    registo.funcionario_nome || "-"
                )}

                            ${campoDetalhe(
                    "Número",
                    registo.numero_funcionario || "-"
                )}

                            ${campoDetalhe(
                    "Data",
                    formatarData(
                        registo.data_assiduidade
                    )
                )}

                            ${campoDetalhe(
                    "Filial",
                    registo.filial_nome || "-"
                )}

                            ${campoDetalhe(
                    "Horário",
                    registo.horario_nome || "-"
                )}

                            ${campoDetalhe(
                    "Entrada prevista",
                    formatarHora(
                        registo.hora_prevista_entrada
                    )
                )}

                            ${campoDetalhe(
                    "Saída prevista",
                    formatarHora(
                        registo.hora_prevista_saida
                    )
                )}

                            ${campoDetalhe(
                    "Entrada real",
                    formatarHora(
                        registo.hora_real_entrada
                    )
                )}

                            ${campoDetalhe(
                    "Saída real",
                    formatarHora(
                        registo.hora_real_saida
                    )
                )}

                            ${campoDetalhe(
                    "Intervalo",
                    formatarIntervalo(
                        registo.inicio_intervalo,
                        registo.fim_intervalo
                    )
                )}

                            ${campoDetalhe(
                    "Horas previstas",
                    formatarMinutosLongos(
                        registo.minutos_previstos
                    )
                )}

                            ${campoDetalhe(
                    "Horas trabalhadas",
                    formatarMinutosLongos(
                        registo.minutos_trabalhados
                    )
                )}

                            ${campoDetalhe(
                    "Atraso",
                    formatarMinutosLongos(
                        registo.minutos_atraso
                    )
                )}

                            ${campoDetalhe(
                    "Saída antecipada",
                    formatarMinutosLongos(
                        registo.minutos_saida_antecipada
                    )
                )}

                            ${campoDetalhe(
                    "Horas extras",
                    formatarMinutosLongos(
                        registo.minutos_extra
                    )
                )}

                            ${campoDetalhe(
                    "Tempo em falta",
                    formatarMinutosLongos(
                        registo.minutos_falta
                    )
                )}

                            ${campoDetalhe(
                    "Ocorrência",
                    formatarCodigo(
                        registo.ocorrencia
                    )
                )}

                            ${campoDetalhe(
                    "Estado",
                    registo.status_nome || "-"
                )}

                            ${campoDetalhe(
                    "Aprovado por",
                    registo.aprovado_por_nome || "-"
                )}

                            ${campoDetalhe(
                    "Aprovado em",
                    formatarDataHora(
                        registo.aprovado_em
                    )
                )}

                        </div>

                        <hr>

                        <strong>Observações:</strong>

                        <div class="mt-2">
                            ${textoLongo(
                    registo.observacoes
                )}
                        </div>

                    </div>
                `,

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // LISTAR FALTAS
    // ==================================================

    async function carregarFaltas() {

        const parametros =
            criarParametrosFiltros(
                "listar_faltas"
            );

        adicionarParametro(
            parametros,
            "status_id",
            $("#filtroFaltaStatusId")
                .val()
        );

        const resposta =
            await requisicaoAssiduidade(
                ASSIDUIDADE_URL
                + "?"
                + parametros.toString()
            );

        faltasCache =
            arraySeguro(
                resposta.data
            );

        renderizarTabelaFaltas(
            faltasCache
        );

    }


    function renderizarTabelaFaltas(
        faltas
    ) {

        destruirDataTable(
            "#faltasTable",
            tabelaFaltas
        );

        tabelaFaltas = null;

        const tbody =
            $("#faltasTable tbody");

        tbody.empty();

        if (!faltas.length) {

            tbody.html(`
                <tr>
                    <td
                        colspan="10"
                        class="text-center text-muted py-4">

                        Nenhuma falta encontrada.

                    </td>
                </tr>
            `);

            return;

        }

        faltas.forEach(
            function (falta) {

                const estado =
                    String(
                        falta.status_nome || "-"
                    );

                tbody.append(`
                    <tr>

                        <td>
                            ${formatarData(
                    falta.data_inicio
                )}
                        </td>

                        <td>
                            <strong>
                                ${escaparHtml(
                    falta.funcionario_nome || "-"
                )}
                            </strong>

                            <div class="text-muted small">
                                ${escaparHtml(
                    falta.numero_funcionario || "-"
                )}
                            </div>
                        </td>

                        <td>
                            ${escaparHtml(
                    formatarCodigo(
                        falta.tipo_falta
                    )
                )}
                        </td>

                        <td>
                            ${formatarMinutosLongos(
                    falta.minutos_falta
                )}
                        </td>

                        <td>
                            ${formatarNumeroDecimal(
                    falta.quantidade_dias
                )}
                        </td>

                        <td>
                            ${simNao(
                    falta.remunerada
                )}
                        </td>

                        <td>
                            ${simNao(
                    falta.desconta_salario
                )}
                        </td>

                        <td>
                            ${escaparHtml(
                    formatarCodigo(
                        falta.origem
                    )
                )}
                        </td>

                        <td>
                            <span class="badge ${classeStatusFalta(
                    estado
                )}">
                                ${escaparHtml(estado)}
                            </span>
                        </td>

                        <td class="text-right">

                            <div class="dropdown dropdown-action">

                                <a
                                    href="#"
                                    class="action-icon dropdown-toggle"
                                    data-toggle="dropdown">

                                    <i class="material-icons">
                                        more_vert
                                    </i>

                                </a>

                                <div class="dropdown-menu dropdown-menu-right">

                                    <button
                                        type="button"
                                        class="dropdown-item btn-ver-falta"
                                        data-id="${Number(
                    falta.id
                )}">

                                        <i class="fa fa-eye m-r-5"></i>
                                        Ver detalhes

                                    </button>

                                    ${podeAnalisarFalta(
                    falta
                )
                        ? `
                                                <button
                                                    type="button"
                                                    class="dropdown-item btn-justificar-falta"
                                                    data-id="${Number(
                            falta.id
                        )}">

                                                    <i class="fa fa-file-text-o m-r-5"></i>
                                                    Justificar

                                                </button>

                                                <button
                                                    type="button"
                                                    class="dropdown-item text-danger btn-falta-injustificada"
                                                    data-id="${Number(
                            falta.id
                        )}">

                                                    <i class="fa fa-times m-r-5"></i>
                                                    Marcar injustificada

                                                </button>
                                            `
                        : ""
                    }

                                </div>

                            </div>

                        </td>

                    </tr>
                `);

            }
        );

        tabelaFaltas =
            iniciarDataTable(
                "#faltasTable",
                9,
                [
                    [
                        0,
                        "desc"
                    ],
                    [
                        1,
                        "asc"
                    ]
                ]
            );

    }


    // ==================================================
    // ABRIR MODAL DE JUSTIFICAÇÃO DE FALTA
    // ==================================================

    function abrirModalJustificarFalta(
        faltaId
    ) {

        const falta =
            encontrarPorId(
                faltasCache,
                faltaId
            );

        if (!falta) {

            mostrarErroAssiduidade(
                "Falta não encontrada."
            );

            return;
        }

        limparFormularioJustificarFalta();

        $("#justificarFaltaId")
            .val(
                falta.id || ""
            );

        $("#justificarFaltaFuncionario")
            .val(
                falta.funcionario_nome || ""
            );

        /*
         * O input type="date" só aceita YYYY-MM-DD.
         * data_inicio vem do banco nesse formato ou
         * no formato YYYY-MM-DD HH:mm:ss.
         */
        const dataFalta =
            String(
                falta.data_inicio || ""
            )
                .trim()
                .substring(
                    0,
                    10
                );

        $("#justificarFaltaData")
            .val(
                dataFalta
            );

        /*
         * Preencher o tipo de falta existente.
         */
        const tipoFalta =
            String(
                falta.tipo_falta
                || ""
            ).trim();

        $("#justificarFaltaTipo")
            .val(
                tipoFalta
            );

        /*
         * Caso o valor tenha diferença de
         * maiúsculas ou minúsculas.
         */
        if (
            tipoFalta
            && !$("#justificarFaltaTipo").val()
        ) {

            $("#justificarFaltaTipo option")
                .each(function () {

                    const valorOpcao =
                        String(
                            $(this).val() || ""
                        ).trim();

                    if (
                        normalizarTexto(valorOpcao)
                        === normalizarTexto(tipoFalta)
                    ) {

                        $("#justificarFaltaTipo")
                            .val(
                                valorOpcao
                            );

                        return false;
                    }

                    return true;
                });
        }

        $("#justificarFaltaJustificacao")
            .val(
                falta.justificacao || ""
            );

        /*
         * Não é permitido preencher um input file
         * com um caminho já guardado.
         */
        $("#justificarFaltaDocumento")
            .val("");

        function mostrarDocumentoJustificacaoAtual(

            caminhoDocumento

        ) {



            const container =

                $("#justificarFaltaDocumentoAtual");



            container.empty();



            const caminho =

                String(

                    caminhoDocumento || ""

                ).trim();



            if (!caminho) {



                container.html(`

            <small class="text-muted">

                Nenhum documento anexado.

            </small>

        `);



                return;

            }



            /*

             * Caso o banco guarde:

             * uploads/faltas/documento.pdf

             *

             * O sistema acrescenta /erpplus/

             * apenas quando o caminho não é URL absoluta.

             */

            let urlDocumento = caminho;



            if (

                !caminho.startsWith("http://")

                && !caminho.startsWith("https://")

                && !caminho.startsWith("/")

            ) {



                urlDocumento =

                    "/erpplus/"

                    + caminho.replace(

                        /^\/+/,

                        ""

                    );

            }



            container.html(`

        <div class="alert alert-light border mb-2">



            <div class="d-flex align-items-center justify-content-between">



                <div>

                    <i class="fa fa-paperclip mr-1"></i>

                    Documento já anexado

                </div>



                <a

                    href="${escaparHtml(urlDocumento)}"

                    target="_blank"

                    rel="noopener noreferrer"

                    class="btn btn-sm btn-info">



                    <i class="fa fa-eye"></i>

                    Ver documento



                </a>



            </div>



            <small class="text-muted d-block mt-2">

                Se selecionar outro ficheiro, o documento atual será substituído.

            </small>



        </div>

    `);

        }

        $("#justificarFaltaRemunerada")
            .prop(
                "checked",
                Number(
                    falta.remunerada || 0
                ) === 1
            );

        $("#justificarFaltaDescontaSalario")
            .prop(
                "checked",
                Number(
                    falta.desconta_salario || 0
                ) === 1
            );

        $("#justificarFaltaDias")
            .val(
                falta.dias
                ?? falta.quantidade_dias
                ?? ""
            );

        $("#justificarFaltaObservacoes")
            .val(
                falta.observacoes || ""
            );

        $("#modalJustificarFalta")
            .modal("show");
    }


    async function guardarJustificacaoFalta() {

        const faltaId =
            Number(
                $("#justificarFaltaId")
                    .val() || 0
            );

        const tipoFalta =
            textoCampo(
                "#justificarFaltaTipo"
            );

        const justificacao =
            textoCampo(
                "#justificarFaltaJustificacao"
            );

        const observacoes =
            textoCampo(
                "#justificarFaltaObservacoes"
            );

        const dias =
            decimalNaoNegativo(
                $("#justificarFaltaDias")
                    .val()
            );

        const remunerada =
            $("#justificarFaltaRemunerada")
                .is(":checked")
                ? 1
                : 0;

        const descontaSalario =
            $("#justificarFaltaDescontaSalario")
                .is(":checked")
                ? 1
                : 0;

        const inputDocumento =
            document.getElementById(
                "justificarFaltaDocumento"
            );

        const documento =
            inputDocumento
                && inputDocumento.files
                && inputDocumento.files.length > 0
                ? inputDocumento.files[0]
                : null;

        if (faltaId <= 0) {

            throw new Error(
                "Falta inválida."
            );
        }

        if (!tipoFalta) {

            throw new Error(
                "Selecione o tipo de falta."
            );
        }

        if (!justificacao) {

            throw new Error(
                "Informe a justificação da falta."
            );
        }

        if (documento) {

            validarDocumentoJustificacao(
                documento
            );
        }

        bloquearModalJustificarFalta(true);

        try {

            const formData =
                new FormData();

            formData.append(
                "acao",
                "justificar_falta"
            );

            formData.append(
                "id",
                String(faltaId)
            );

            formData.append(
                "tipo_falta",
                tipoFalta
            );

            formData.append(
                "justificacao",
                justificacao
            );

            formData.append(
                "dias",
                String(dias)
            );

            formData.append(
                "remunerada",
                String(remunerada)
            );

            formData.append(
                "desconta_salario",
                String(descontaSalario)
            );

            formData.append(
                "observacoes",
                observacoes
            );

            if (documento) {

                formData.append(
                    "documento",
                    documento,
                    documento.name
                );
            }

            const resposta =
                await requisicaoAssiduidade(
                    ASSIDUIDADE_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            $("#modalJustificarFalta")
                .modal("hide");

            await Swal.fire({
                icon:
                    "success",

                title:
                    "Falta justificada",

                text:
                    resposta.mensagem
                    || "A falta foi justificada com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await carregarTodasListas();

        } catch (erro) {

            mostrarErroAssiduidade(
                erro.message
            );

            throw erro;

        } finally {

            bloquearModalJustificarFalta(false);
        }
    }

    function validarDocumentoJustificacao(
        documento
    ) {

        const extensoesPermitidas = [
            "pdf",
            "jpg",
            "jpeg",
            "png"
        ];

        const tiposPermitidos = [
            "application/pdf",
            "image/jpeg",
            "image/png"
        ];

        const tamanhoMaximo =
            5 * 1024 * 1024;

        const nome =
            String(
                documento.name || ""
            );

        const extensao =
            nome.includes(".")
                ? nome
                    .split(".")
                    .pop()
                    .toLowerCase()
                : "";

        if (
            !extensoesPermitidas.includes(
                extensao
            )
        ) {

            throw new Error(
                "O documento deve ser PDF, JPG, JPEG ou PNG."
            );
        }

        if (
            documento.type
            && !tiposPermitidos.includes(
                documento.type
            )
        ) {

            throw new Error(
                "O tipo do documento selecionado não é permitido."
            );
        }

        if (
            documento.size > tamanhoMaximo
        ) {

            throw new Error(
                "O documento não pode ultrapassar 5 MB."
            );
        }
    }


    // ==================================================
    // MARCAR FALTA INJUSTIFICADA
    // ==================================================

    async function confirmarFaltaInjustificada(
        faltaId
    ) {

        const falta =
            encontrarPorId(
                faltasCache,
                faltaId
            );

        if (!falta) {

            mostrarErroAssiduidade(
                "Falta não encontrada."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Marcar como injustificada?",

                html:
                    `
                        <strong>
                            ${escaparHtml(
                        falta.funcionario_nome
                        || "-"
                    )}
                        </strong>

                        <div class="mt-2">
                            ${formatarData(
                        falta.data_inicio
                    )}
                        </div>
                    `,

                input:
                    "textarea",

                inputLabel:
                    "Observações",

                inputPlaceholder:
                    "Informe uma observação opcional",

                showCancelButton:
                    true,

                confirmButtonText:
                    "Marcar injustificada",

                cancelButtonText:
                    "Cancelar",

                confirmButtonColor:
                    "#d33",

                reverseButtons:
                    true
            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        await executarAcaoPost(
            "marcar_falta_injustificada",
            {
                id:
                    faltaId,

                observacoes:
                    String(
                        confirmacao.value || ""
                    ).trim()
            },
            "Falta injustificada",
            "A falta foi marcada como injustificada."
        );

    }


    // ==================================================
    // DETALHES DA FALTA
    // ==================================================

    function mostrarDetalhesFalta(
        faltaId
    ) {

        const falta =
            encontrarPorId(
                faltasCache,
                faltaId
            );

        if (!falta) {

            mostrarErroAssiduidade(
                "Falta não encontrada."
            );

            return;

        }

        Swal.fire({
            icon:
                "info",

            title:
                "Detalhes da Falta",

            width:
                900,

            html:
                `
                    <div class="text-left">

                        <div class="row">

                            ${campoDetalhe(
                    "Funcionário",
                    falta.funcionario_nome || "-"
                )}

                            ${campoDetalhe(
                    "Número",
                    falta.numero_funcionario || "-"
                )}

                            ${campoDetalhe(
                    "Data inicial",
                    formatarData(
                        falta.data_inicio
                    )
                )}

                            ${campoDetalhe(
                    "Data final",
                    formatarData(
                        falta.data_fim
                    )
                )}

                            ${campoDetalhe(
                    "Tipo",
                    formatarCodigo(
                        falta.tipo_falta
                    )
                )}

                            ${campoDetalhe(
                    "Origem",
                    formatarCodigo(
                        falta.origem
                    )
                )}

                            ${campoDetalhe(
                    "Quantidade de dias",
                    formatarNumeroDecimal(
                        falta.quantidade_dias
                    )
                )}

                            ${campoDetalhe(
                    "Tempo em falta",
                    formatarMinutosLongos(
                        falta.minutos_falta
                    )
                )}

                            ${campoDetalhe(
                    "Remunerada",
                    simNao(
                        falta.remunerada
                    )
                )}

                            ${campoDetalhe(
                    "Desconta salário",
                    simNao(
                        falta.desconta_salario
                    )
                )}

                            ${campoDetalhe(
                    "Desconta férias",
                    simNao(
                        falta.desconta_ferias
                    )
                )}

                            ${campoDetalhe(
                    "Estado",
                    falta.status_nome || "-"
                )}

                            ${campoDetalhe(
                    "Analisado por",
                    falta.analisado_por_nome || "-"
                )}

                            ${campoDetalhe(
                    "Analisado em",
                    formatarDataHora(
                        falta.analisado_em
                    )
                )}

                        </div>

                        <hr>

                        <strong>Motivo:</strong>

                        <div class="mt-2 mb-3">
                            ${textoLongo(
                    falta.motivo
                )}
                        </div>

                        <strong>Justificação:</strong>

                        <div class="mt-2 mb-3">
                            ${textoLongo(
                    falta.justificacao
                )}
                        </div>

                        <strong>Documento:</strong>

                        <div class="mt-2 mb-3">
                            ${textoLongo(
                    falta.documento_justificacao
                )}
                        </div>

                        <strong>Observações:</strong>

                        <div class="mt-2">
                            ${textoLongo(
                    falta.observacoes
                )}
                        </div>

                    </div>
                `,

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // LISTAR HORAS EXTRAS
    // ==================================================

    async function carregarHorasExtras() {

        const parametros =
            criarParametrosFiltros(
                "listar_horas_extras"
            );

        adicionarParametro(
            parametros,
            "status_id",
            $("#filtroHoraExtraStatusId")
                .val()
        );

        const resposta =
            await requisicaoAssiduidade(
                ASSIDUIDADE_URL
                + "?"
                + parametros.toString()
            );

        horasExtrasCache =
            arraySeguro(
                resposta.data
            );

        renderizarTabelaHorasExtras(
            horasExtrasCache
        );

    }


    function renderizarTabelaHorasExtras(
        horasExtras
    ) {

        destruirDataTable(
            "#horasExtrasTable",
            tabelaHorasExtras
        );

        tabelaHorasExtras = null;

        const tbody =
            $("#horasExtrasTable tbody");

        tbody.empty();

        if (!horasExtras.length) {

            tbody.html(`
                <tr>
                    <td
                        colspan="10"
                        class="text-center text-muted py-4">

                        Nenhum registo de horas extras encontrado.

                    </td>
                </tr>
            `);

            return;

        }

        horasExtras.forEach(
            function (horaExtra) {

                const estado =
                    String(
                        horaExtra.status_nome || "-"
                    );

                tbody.append(`
                    <tr>

                        <td>
                            ${formatarData(
                    horaExtra.data_hora_extra
                )}
                        </td>

                        <td>
                            <strong>
                                ${escaparHtml(
                    horaExtra.funcionario_nome || "-"
                )}
                            </strong>

                            <div class="text-muted small">
                                ${escaparHtml(
                    horaExtra.numero_funcionario || "-"
                )}
                            </div>
                        </td>

                        <td>
                            ${escaparHtml(
                    formatarCodigo(
                        horaExtra.tipo_hora_extra
                    )
                )}
                        </td>

                        <td>
                            ${formatarMinutosLongos(
                    horaExtra.minutos_apurados
                )}
                        </td>

                        <td>
                            ${formatarMinutosLongos(
                    horaExtra.minutos_aprovados
                )}
                        </td>

                        <td>
                            ${formatarPercentagem(
                    horaExtra.percentual_acrescimo
                )}
                        </td>

                        <td>
                            ${formatarMoeda(
                    horaExtra.valor_hora,
                    "MZN"
                )}
                        </td>

                        <td>
                            ${formatarMoeda(
                    horaExtra.valor_total,
                    "MZN"
                )}
                        </td>

                        <td>
                            <span class="badge ${classeStatusHoraExtra(
                    estado
                )}">
                                ${escaparHtml(estado)}
                            </span>
                        </td>

                        <td class="text-right">

                            <div class="dropdown dropdown-action">

                                <a
                                    href="#"
                                    class="action-icon dropdown-toggle"
                                    data-toggle="dropdown">

                                    <i class="material-icons">
                                        more_vert
                                    </i>

                                </a>

                                <div class="dropdown-menu dropdown-menu-right">

                                    <button
                                        type="button"
                                        class="dropdown-item btn-ver-hora-extra"
                                        data-id="${Number(
                    horaExtra.id
                )}">

                                        <i class="fa fa-eye m-r-5"></i>
                                        Ver detalhes

                                    </button>

                                    ${podeAnalisarHoraExtra(
                    horaExtra
                )
                        ? `
                                                <button
                                                    type="button"
                                                    class="dropdown-item btn-aprovar-hora-extra"
                                                    data-id="${Number(
                            horaExtra.id
                        )}">

                                                    <i class="fa fa-check m-r-5"></i>
                                                    Aprovar

                                                </button>

                                                <button
                                                    type="button"
                                                    class="dropdown-item text-danger btn-rejeitar-hora-extra"
                                                    data-id="${Number(
                            horaExtra.id
                        )}">

                                                    <i class="fa fa-times m-r-5"></i>
                                                    Rejeitar

                                                </button>
                                            `
                        : ""
                    }

                                </div>

                            </div>

                        </td>

                    </tr>
                `);

            }
        );

        tabelaHorasExtras =
            iniciarDataTable(
                "#horasExtrasTable",
                9,
                [
                    [
                        0,
                        "desc"
                    ],
                    [
                        1,
                        "asc"
                    ]
                ]
            );

    }


    // ==================================================
    // APROVAR HORA EXTRA
    // ==================================================

    function abrirModalAprovarHoraExtra(
        horaExtraId
    ) {

        const horaExtra =
            encontrarPorId(
                horasExtrasCache,
                horaExtraId
            );

        if (!horaExtra) {

            mostrarErroAssiduidade(
                "Registo de horas extras não encontrado."
            );

            return;

        }

        limparFormularioAprovarHoraExtra();

        $("#horaExtraId")
            .val(
                horaExtra.id
            );

        $("#horaExtraFuncionario")
            .val(
                horaExtra.funcionario_nome || ""
            );

        $("#horaExtraData")
            .val(
                String(
                    horaExtra.data_hora_extra || ""
                ).substring(0, 10)
            );

        $("#aprovarHoraExtraTipo")
            .val(
                formatarCodigo(
                    horaExtra.tipo_hora_extra || ""
                )
            );

        $("#horaExtraMinutosApurados")
            .val(
                Number(
                    horaExtra.minutos_apurados || 0
                )
            );

        $("#horaExtraMinutosAprovados")
            .val(
                Number(
                    horaExtra.minutos_aprovados
                    || horaExtra.minutos_apurados
                    || 0
                )
            );

        $("#horaExtraPercentualAcrescimo")
            .val(
                Number(
                    horaExtra.percentual_acrescimo
                    || 0
                )
            );

        $("#horaExtraValorHora")
            .val(
                Number(
                    horaExtra.valor_hora || 0
                ).toFixed(2)
            );

        $("#horaExtraObservacoes")
            .val(
                horaExtra.observacoes || ""
            );

        calcularValorTotalHoraExtra();

        $("#modalAprovarHoraExtra")
            .modal("show");

    }


    async function guardarAprovacaoHoraExtra() {

        const dados = {
            id:
                Number(
                    $("#horaExtraId")
                        .val() || 0
                ),

            minutos_aprovados:
                inteiroNaoNegativo(
                    $("#horaExtraMinutosAprovados")
                        .val()
                ),

            observacoes:
                textoCampo(
                    "#horaExtraObservacoes"
                )
        };

        if (dados.id <= 0) {
            throw new Error(
                "Registo de horas extras inválido."
            );
        }

        if (dados.minutos_aprovados <= 0) {
            throw new Error(
                "Informe os minutos aprovados."
            );
        }

        bloquearModalAprovarHoraExtra(true);

        try {

            const formData =
                criarFormData(
                    dados
                );

            formData.append(
                "acao",
                "aprovar_hora_extra"
            );

            const resposta =
                await requisicaoAssiduidade(
                    ASSIDUIDADE_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            $("#modalAprovarHoraExtra")
                .modal("hide");

            await Swal.fire({
                icon:
                    "success",

                title:
                    "Horas extras aprovadas",

                text:
                    resposta.mensagem
                    || "As horas extras foram aprovadas.",

                confirmButtonText:
                    "Concluir"
            });

            await carregarTodasListas();

        } catch (erro) {

            mostrarErroAssiduidade(
                erro.message
            );

            throw erro;

        } finally {

            bloquearModalAprovarHoraExtra(false);

        }

    }


    function calcularValorTotalHoraExtra() {

        const minutos =
            inteiroNaoNegativo(
                $("#horaExtraMinutosAprovados")
                    .val()
            );

        const percentual =
            decimalNaoNegativo(
                $("#horaExtraPercentualAcrescimo")
                    .val()
            );

        const valorHora =
            decimalNaoNegativo(
                $("#horaExtraValorHora")
                    .val()
            );

        const total =
            (
                minutos / 60
            )
            * valorHora
            * (
                1
                + (
                    percentual / 100
                )
            );

        $("#horaExtraValorTotal")
            .val(
                total.toFixed(2)
            );

    }


    // ==================================================
    // REJEITAR HORA EXTRA
    // ==================================================

    async function confirmarRejeitarHoraExtra(
        horaExtraId
    ) {

        const horaExtra =
            encontrarPorId(
                horasExtrasCache,
                horaExtraId
            );

        if (!horaExtra) {

            mostrarErroAssiduidade(
                "Registo de horas extras não encontrado."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Rejeitar horas extras?",

                html:
                    `
                        <strong>
                            ${escaparHtml(
                        horaExtra.funcionario_nome
                        || "-"
                    )}
                        </strong>

                        <div class="mt-2">
                            ${formatarData(
                        horaExtra.data_hora_extra
                    )}
                        </div>
                    `,

                input:
                    "textarea",

                inputLabel:
                    "Motivo da rejeição",

                inputPlaceholder:
                    "Informe o motivo",

                showCancelButton:
                    true,

                confirmButtonText:
                    "Rejeitar",

                cancelButtonText:
                    "Cancelar",

                confirmButtonColor:
                    "#d33",

                reverseButtons:
                    true,

                inputValidator:
                    function (valor) {

                        if (
                            !String(
                                valor || ""
                            ).trim()
                        ) {
                            return "Informe o motivo da rejeição.";
                        }

                        return undefined;

                    }
            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        await executarAcaoPost(
            "rejeitar_hora_extra",
            {
                id:
                    horaExtraId,

                observacoes:
                    String(
                        confirmacao.value || ""
                    ).trim()
            },
            "Horas extras rejeitadas",
            "O registo de horas extras foi rejeitado."
        );

    }


    // ==================================================
    // DETALHES DA HORA EXTRA
    // ==================================================

    function mostrarDetalhesHoraExtra(
        horaExtraId
    ) {

        const horaExtra =
            encontrarPorId(
                horasExtrasCache,
                horaExtraId
            );

        if (!horaExtra) {

            mostrarErroAssiduidade(
                "Registo de horas extras não encontrado."
            );

            return;

        }

        Swal.fire({
            icon:
                "info",

            title:
                "Horas Extras",

            width:
                900,

            html:
                `
                    <div class="text-left">

                        <div class="row">

                            ${campoDetalhe(
                    "Funcionário",
                    horaExtra.funcionario_nome || "-"
                )}

                            ${campoDetalhe(
                    "Número",
                    horaExtra.numero_funcionario || "-"
                )}

                            ${campoDetalhe(
                    "Data",
                    formatarData(
                        horaExtra.data_hora_extra
                    )
                )}

                            ${campoDetalhe(
                    "Tipo",
                    formatarCodigo(
                        horaExtra.tipo_hora_extra
                    )
                )}

                            ${campoDetalhe(
                    "Origem",
                    formatarCodigo(
                        horaExtra.origem
                    )
                )}

                            ${campoDetalhe(
                    "Minutos solicitados",
                    horaExtra.minutos_solicitados || 0
                )}

                            ${campoDetalhe(
                    "Tempo apurado",
                    formatarMinutosLongos(
                        horaExtra.minutos_apurados
                    )
                )}

                            ${campoDetalhe(
                    "Tempo aprovado",
                    formatarMinutosLongos(
                        horaExtra.minutos_aprovados
                    )
                )}

                            ${campoDetalhe(
                    "Acréscimo",
                    formatarPercentagem(
                        horaExtra.percentual_acrescimo
                    )
                )}

                            ${campoDetalhe(
                    "Valor por hora",
                    formatarMoeda(
                        horaExtra.valor_hora,
                        "MZN"
                    )
                )}

                            ${campoDetalhe(
                    "Valor total",
                    formatarMoeda(
                        horaExtra.valor_total,
                        "MZN"
                    )
                )}

                            ${campoDetalhe(
                    "Estado",
                    horaExtra.status_nome || "-"
                )}

                            ${campoDetalhe(
                    "Aprovado por",
                    horaExtra.aprovado_por_nome || "-"
                )}

                            ${campoDetalhe(
                    "Aprovado em",
                    formatarDataHora(
                        horaExtra.aprovado_em
                    )
                )}

                        </div>

                        <hr>

                        <strong>Motivo:</strong>

                        <div class="mt-2 mb-3">
                            ${textoLongo(
                    horaExtra.motivo
                )}
                        </div>

                        <strong>Observações:</strong>

                        <div class="mt-2">
                            ${textoLongo(
                    horaExtra.observacoes
                )}
                        </div>

                    </div>
                `,

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // EXECUTAR AÇÃO POST
    // ==================================================

    async function executarAcaoPost(
        acao,
        dados,
        tituloSucesso,
        mensagemSucesso
    ) {

        mostrarCarregamentoAssiduidade(true);

        try {

            const formData =
                criarFormData(
                    dados
                );

            formData.append(
                "acao",
                acao
            );

            const resposta =
                await requisicaoAssiduidade(
                    ASSIDUIDADE_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            await Swal.fire({
                icon:
                    "success",

                title:
                    tituloSucesso,

                text:
                    resposta.mensagem
                    || mensagemSucesso,

                confirmButtonText:
                    "Concluir"
            });

            await carregarTodasListas();

        } catch (erro) {

            mostrarErroAssiduidade(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoAssiduidade(false);

        }

    }


    // ==================================================
    // FILTROS
    // ==================================================

    function criarParametrosFiltros(
        acao
    ) {

        const parametros =
            new URLSearchParams({
                acao:
                    acao
            });

        adicionarParametro(
            parametros,
            "funcionario_id",
            $("#filtroAssiduidadeFuncionarioId")
                .val()
        );

        adicionarParametro(
            parametros,
            "filial_id",
            $("#filtroAssiduidadeFilialId")
                .val()
        );

        adicionarParametro(
            parametros,
            "status_id",
            $("#filtroAssiduidadeStatusId")
                .val()
        );

        adicionarParametro(
            parametros,
            "data_inicio",
            $("#filtroAssiduidadeDataInicio")
                .val()
        );

        adicionarParametro(
            parametros,
            "data_fim",
            $("#filtroAssiduidadeDataFim")
                .val()
        );

        adicionarParametro(
            parametros,
            "pesquisa",
            $("#filtroAssiduidadePesquisa")
                .val()
        );

        return parametros;

    }


    function limparFiltrosAssiduidade() {

        $("#filtroAssiduidadePesquisa")
            .val("");

        $("#filtroAssiduidadeFuncionarioId")
            .val("");

        $("#filtroAssiduidadeFilialId")
            .val("");

        $("#filtroAssiduidadeStatusId")
            .val("");

        $("#filtroAssiduidadeOcorrencia")
            .val("");

        $("#filtroRegistoPontoTipo")
            .val("");

        $("#filtroRegistoPontoOrigem")
            .val("");

        $("#filtroFaltaStatusId")
            .val("");

        $("#filtroHoraExtraStatusId")
            .val("");

        definirPeriodoInicial();

    }


    function validarPeriodoFiltro() {

        const inicio =
            textoCampo(
                "#filtroAssiduidadeDataInicio"
            );

        const fim =
            textoCampo(
                "#filtroAssiduidadeDataFim"
            );

        if (!inicio) {
            throw new Error(
                "Informe a data inicial."
            );
        }

        if (!fim) {
            throw new Error(
                "Informe a data final."
            );
        }

        if (fim < inicio) {
            throw new Error(
                "A data final não pode ser anterior à data inicial."
            );
        }

    }


    // ==================================================
    // EXPORTAR CSV
    // ==================================================

    function exportarTabelaAssiduidade() {

        if (!assiduidadeCache.length) {

            mostrarErroAssiduidade(
                "Não existem dados de assiduidade para exportar."
            );

            return;

        }

        const linhas = [
            [
                "Data",
                "Número",
                "Funcionário",
                "Filial",
                "Entrada",
                "Saída",
                "Minutos trabalhados",
                "Minutos de atraso",
                "Minutos extras",
                "Minutos em falta",
                "Ocorrência",
                "Estado"
            ]
        ];

        assiduidadeCache.forEach(
            function (item) {

                linhas.push([
                    item.data_assiduidade || "",
                    item.numero_funcionario || "",
                    item.funcionario_nome || "",
                    item.filial_nome || "",
                    item.hora_real_entrada || "",
                    item.hora_real_saida || "",
                    item.minutos_trabalhados || 0,
                    item.minutos_atraso || 0,
                    item.minutos_extra || 0,
                    item.minutos_falta || 0,
                    item.ocorrencia || "",
                    item.status_nome || ""
                ]);

            }
        );

        const csv =
            linhas
                .map(
                    function (linha) {

                        return linha
                            .map(
                                escaparCampoCsv
                            )
                            .join(";");

                    }
                )
                .join("\n");

        const blob =
            new Blob(
                [
                    "\uFEFF",
                    csv
                ],
                {
                    type:
                        "text/csv;charset=utf-8;"
                }
            );

        const url =
            URL.createObjectURL(
                blob
            );

        const link =
            document.createElement(
                "a"
            );

        link.href =
            url;

        link.download =
            "relatorio_assiduidade_"
            + dataAtualIso()
            + ".csv";

        document.body.appendChild(
            link
        );

        link.click();

        document.body.removeChild(
            link
        );

        URL.revokeObjectURL(
            url
        );

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoAssiduidade(
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
                || json.detalhe
                || "Não foi possível processar a assiduidade."
            );

        }

        return json;

    }


    // ==================================================
    // ESTADO DA INTERFACE
    // ==================================================

    function mostrarCarregamentoAssiduidade(
        mostrar
    ) {

        $("#assiduidadeCarregando")
            .toggle(
                Boolean(mostrar)
            );

        $(
            "#btnNovoRegistoPonto, "
            + "#btnFiltrarAssiduidade, "
            + "#btnLimparFiltrosAssiduidade, "
            + "#btnExportarAssiduidade"
        )
            .prop(
                "disabled",
                Boolean(mostrar)
            );

    }


    function bloquearModalRegistoPonto(
        bloquear
    ) {

        $("#formRegistoPonto")
            .find(
                "input, select, textarea, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnGuardarRegistoPontoSpinner")
            .toggle(
                Boolean(bloquear)
            );

    }


    function bloquearModalJustificarFalta(
        bloquear
    ) {

        $("#formJustificarFalta")
            .find(
                "input, select, textarea, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnGuardarJustificacaoFaltaSpinner")
            .toggle(
                Boolean(bloquear)
            );

    }


    function bloquearModalAprovarHoraExtra(
        bloquear
    ) {

        $("#formAprovarHoraExtra")
            .find(
                "input, select, textarea, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnGuardarHoraExtraSpinner")
            .toggle(
                Boolean(bloquear)
            );

    }


    function mostrarErroAssiduidade(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível processar",

            text:
                mensagem
                || "Ocorreu um erro na gestão de assiduidade.",

            confirmButtonText:
                "Fechar"
        });
    }


    // ==================================================
    // LIMPAR FORMULÁRIOS
    // ==================================================

    function limparFormularioRegistoPonto() {

        const formulario =
            $("#formRegistoPonto")[0];

        if (formulario) {
            formulario.reset();
        }

        $("#registoPontoId")
            .val("");

        $("#registoPontoRegistoOriginalId")
            .val("");

        $("#registoPontoCorrigido")
            .prop(
                "checked",
                false
            );

        $("#modalRegistoPontoTitulo")
            .text(
                "Novo Registo de Ponto"
            );

        $("#btnGuardarRegistoPontoTexto")
            .text(
                "Guardar Registo"
            );

    }


    function limparFormularioJustificarFalta() {

        const formulario =
            $("#formJustificarFalta")[0];

        if (formulario) {

            formulario.reset();
        }

        $("#justificarFaltaId")
            .val("");

        $("#justificarFaltaDocumento")
            .val("");

        $("#justificarFaltaDocumentoAtual")
            .empty();

        $("#alertaJustificarFalta")
            .hide()
            .text("");
    }

    function limparFormularioAprovarHoraExtra() {

        const formulario =
            $("#formAprovarHoraExtra")[0];

        if (formulario) {
            formulario.reset();
        }

        $("#horaExtraId")
            .val("");

        $("#horaExtraValorTotal")
            .val("0.00");

    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function iniciarDataTable(
        seletor,
        colunaAcoes,
        ordenacao
    ) {

        if (
            typeof $.fn.DataTable
            === "undefined"
        ) {
            return null;
        }

        return $(seletor)
            .DataTable({
                pageLength:
                    10,

                order:
                    ordenacao,

                columnDefs: [
                    {
                        targets:
                            colunaAcoes,

                        orderable:
                            false,

                        searchable:
                            false
                    }
                ],

                language: {
                    search:
                        "Pesquisar:",

                    lengthMenu:
                        "Mostrar _MENU_ registos",

                    info:
                        "A mostrar _START_ a _END_ de _TOTAL_ registos",

                    infoEmpty:
                        "Nenhum registo disponível",

                    zeroRecords:
                        "Nenhum registo encontrado",

                    paginate: {
                        first:
                            "Primeiro",

                        last:
                            "Último",

                        next:
                            "Seguinte",

                        previous:
                            "Anterior"
                    }
                }
            });

    }


    function destruirDataTable(
        seletor,
        instancia
    ) {

        if (
            instancia
            && typeof instancia.destroy
            === "function"
        ) {

            instancia.destroy();

        }

        if (
            typeof $.fn.DataTable
            !== "undefined"
            && $.fn.DataTable.isDataTable(
                seletor
            )
        ) {

            $(seletor)
                .DataTable()
                .destroy();

        }

    }


    // ==================================================
    // PERMISSÕES DE AÇÕES
    // ==================================================

    function podeValidarRegisto(
        registo
    ) {

        return normalizarTexto(
            registo.status_nome
        ) === "pendente";

    }


    function podeAnularRegisto(
        registo
    ) {

        return normalizarTexto(
            registo.status_nome
        ) !== "anulado";

    }


    function podeAprovarAssiduidade(
        registo
    ) {

        return [
            "aberta",
            "apurada"
        ].includes(
            normalizarTexto(
                registo.status_nome
            )
        );

    }


    function podeAnalisarFalta(
        falta
    ) {

        return normalizarTexto(
            falta.status_nome
        ) === "pendente";

    }


    function podeAnalisarHoraExtra(
        horaExtra
    ) {

        return normalizarTexto(
            horaExtra.status_nome
        ) === "pendente";

    }


    // ==================================================
    // CLASSES DE ESTADO
    // ==================================================

    function classeStatusRegisto(
        estado
    ) {

        const texto =
            normalizarTexto(
                estado
            );

        if (texto === "validado") {
            return "badge-success";
        }

        if (texto === "pendente") {
            return "badge-warning";
        }

        if (texto === "anulado") {
            return "badge-danger";
        }

        return "badge-secondary";

    }


    function classeStatusAssiduidade(
        estado
    ) {

        const texto =
            normalizarTexto(
                estado
            );

        if (texto === "aprovada") {
            return "badge-success";
        }

        if (texto === "apurada") {
            return "badge-info";
        }

        if (texto === "aberta") {
            return "badge-warning";
        }

        if (texto === "fechada") {
            return "badge-secondary";
        }

        return "badge-light";

    }


    function classeStatusFalta(
        estado
    ) {

        const texto =
            normalizarTexto(
                estado
            );

        if (texto === "justificada") {
            return "badge-success";
        }

        if (texto === "pendente") {
            return "badge-warning";
        }

        if (
            texto === "injustificada"
            || texto === "rejeitada"
        ) {
            return "badge-danger";
        }

        if (texto === "cancelada") {
            return "badge-secondary";
        }

        return "badge-light";

    }


    function classeStatusHoraExtra(
        estado
    ) {

        const texto =
            normalizarTexto(
                estado
            );

        if (texto === "aprovada") {
            return "badge-success";
        }

        if (texto === "pendente") {
            return "badge-warning";
        }

        if (
            texto === "rejeitada"
            || texto === "cancelada"
        ) {
            return "badge-danger";
        }

        if (texto === "processada") {
            return "badge-info";
        }

        return "badge-light";

    }


    function classeOcorrencia(
        ocorrencia
    ) {

        const texto =
            String(
                ocorrencia || ""
            ).toUpperCase();

        if (texto === "NORMAL") {
            return "badge-success";
        }

        if (
            texto === "ATRASO"
            || texto === "SAIDA_ANTECIPADA"
            || texto === "FALTA_PARCIAL"
            || texto === "INCOMPLETO"
        ) {
            return "badge-warning";
        }

        if (texto === "FALTA_TOTAL") {
            return "badge-danger";
        }

        if (
            texto === "FERIAS"
            || texto === "LICENCA"
            || texto === "FERIADO"
            || texto === "FOLGA"
        ) {
            return "badge-info";
        }

        return "badge-secondary";

    }


    // ==================================================
    // VALIDAÇÃO DE DATA E HORA
    // ==================================================

    function validarDataHoraRegisto() {

        const data =
            textoCampo(
                "#registoPontoData"
            );

        const hora =
            textoCampo(
                "#registoPontoHora"
            );

        if (
            !data
            || !hora
        ) {
            return;
        }

        const dataHora =
            new Date(
                data
                + "T"
                + hora
            );

        if (
            Number.isNaN(
                dataHora.getTime()
            )
        ) {
            throw new Error(
                "A data ou hora do registo é inválida."
            );
        }

    }


    // ==================================================
    // HELPERS DE SELECT
    // ==================================================

    function preencherSelectId(
        seletor,
        itens,
        textoVazio,
        obterTexto
    ) {

        const select =
            $(seletor);

        if (!select.length) {
            return;
        }

        select.empty();

        select.append(`
            <option value="">
                ${escaparHtml(
            textoVazio
        )}
            </option>
        `);

        itens.forEach(
            function (item) {

                select.append(`
                    <option value="${Number(
                    item.id
                )}">
                        ${escaparHtml(
                    obterTexto(item)
                )}
                    </option>
                `);

            }
        );

    }


    function preencherSelectCodigo(
        seletor,
        itens,
        textoVazio
    ) {

        const select =
            $(seletor);

        if (!select.length) {
            return;
        }

        select.empty();

        select.append(`
            <option value="">
                ${escaparHtml(
            textoVazio
        )}
            </option>
        `);

        itens.forEach(
            function (item) {

                select.append(`
                    <option value="${escaparHtml(
                    item.codigo || ""
                )}">
                        ${escaparHtml(
                    item.nome || "-"
                )}
                    </option>
                `);

            }
        );

    }


    // ==================================================
    // HELPERS GERAIS
    // ==================================================

    function criarFormData(
        dados
    ) {

        const formData =
            new FormData();

        Object.entries(
            dados
        ).forEach(
            function ([campo, valor]) {

                if (Array.isArray(valor)) {

                    valor.forEach(
                        function (item) {

                            formData.append(
                                campo + "[]",
                                String(item)
                            );

                        }
                    );

                    return;

                }

                formData.append(
                    campo,
                    valor === null
                        ? ""
                        : String(valor)
                );

            }
        );

        return formData;

    }


    function adicionarParametro(
        parametros,
        nome,
        valor
    ) {

        const texto =
            String(
                valor ?? ""
            ).trim();

        if (texto !== "") {

            parametros.append(
                nome,
                texto
            );

        }

    }


    function encontrarPorId(
        itens,
        id
    ) {

        return itens.find(
            function (item) {

                return Number(item.id)
                    === Number(id);

            }
        );

    }


    function arraySeguro(
        valor
    ) {

        return Array.isArray(valor)
            ? valor
            : [];

    }


    function textoCampo(
        seletor
    ) {

        return String(
            $(seletor).val()
            || ""
        ).trim();

    }


    function inteiroOpcional(
        valor
    ) {

        const numero =
            Number(
                valor || 0
            );

        return Number.isInteger(numero)
            && numero > 0
            ? numero
            : null;

    }


    function inteiroNaoNegativo(
        valor
    ) {

        const numero =
            Number(
                valor || 0
            );

        return Number.isInteger(numero)
            && numero >= 0
            ? numero
            : 0;

    }


    function decimalOpcional(
        valor
    ) {

        const texto =
            String(
                valor ?? ""
            )
                .trim()
                .replace(
                    ",",
                    "."
                );

        if (texto === "") {
            return null;
        }

        const numero =
            Number(texto);

        return Number.isFinite(numero)
            ? numero
            : null;

    }


    function decimalNaoNegativo(
        valor
    ) {

        const numero =
            Number(
                String(
                    valor ?? "0"
                ).replace(
                    ",",
                    "."
                )
            );

        return Number.isFinite(numero)
            && numero >= 0
            ? numero
            : 0;

    }


    function numeroInteiro(
        valor
    ) {

        return Number(
            valor || 0
        ).toLocaleString(
            "pt-PT",
            {
                maximumFractionDigits:
                    0
            }
        );

    }


    function formatarNumeroDecimal(
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


    function formatarPercentagem(
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
        )
            + "%";

    }


    function formatarMoeda(
        valor,
        moeda
    ) {

        return Number(
            valor || 0
        ).toLocaleString(
            "pt-PT",
            {
                style:
                    "currency",

                currency:
                    moeda || "MZN"
            }
        );

    }


    function formatarMinutosLongos(
        minutos
    ) {

        const total =
            Math.max(
                0,
                Number(
                    minutos || 0
                )
            );

        const horas =
            Math.floor(
                total / 60
            );

        const restantes =
            Math.floor(
                total % 60
            );

        if (
            horas <= 0
            && restantes <= 0
        ) {
            return "0 min";
        }

        if (horas <= 0) {
            return restantes + " min";
        }

        if (restantes <= 0) {
            return horas + " h";
        }

        return (
            horas
            + " h "
            + restantes
            + " min"
        );

    }


    function formatarData(
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
            return String(data);
        }

        return (
            partes[2]
            + "/"
            + partes[1]
            + "/"
            + partes[0]
        );

    }


    function formatarHora(
        hora
    ) {

        if (!hora) {
            return "-";
        }

        return String(hora)
            .substring(0, 5);

    }


    function formatarHoraInput(
        hora
    ) {

        if (!hora) {
            return "";
        }

        return String(hora)
            .substring(0, 5);

    }


    function formatarDataHora(
        dataHora
    ) {

        if (!dataHora) {
            return "-";
        }

        const texto =
            String(dataHora);

        const partes =
            texto.split(" ");

        if (partes.length < 2) {
            return texto;
        }

        return (
            formatarData(
                partes[0]
            )
            + " "
            + formatarHora(
                partes[1]
            )
        );

    }


    function formatarIntervalo(
        inicio,
        fim
    ) {

        if (
            !inicio
            && !fim
        ) {
            return "-";
        }

        return (
            formatarHora(inicio)
            + " - "
            + formatarHora(fim)
        );

    }


    function formatarCodigo(
        codigo
    ) {

        const texto =
            String(
                codigo || "-"
            )
                .replace(
                    /_/g,
                    " "
                )
                .toLocaleLowerCase(
                    "pt-PT"
                );

        return texto.replace(
            /\b\p{L}/gu,
            function (letra) {

                return letra.toLocaleUpperCase(
                    "pt-PT"
                );

            }
        );

    }


    function dataAtualIso() {

        return formatarDataIsoObjeto(
            new Date()
        );

    }


    function horaAtual() {

        const agora =
            new Date();

        const horas =
            String(
                agora.getHours()
            ).padStart(
                2,
                "0"
            );

        const minutos =
            String(
                agora.getMinutes()
            ).padStart(
                2,
                "0"
            );

        return (
            horas
            + ":"
            + minutos
        );

    }


    function formatarDataIsoObjeto(
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

        return (
            ano
            + "-"
            + mes
            + "-"
            + dia
        );

    }


    function simNao(
        valor
    ) {

        return Number(
            valor || 0
        ) === 1
            ? "Sim"
            : "Não";

    }


    function campoDetalhe(
        titulo,
        valor
    ) {

        return `
            <div class="col-md-6 mb-3">

                <strong>
                    ${escaparHtml(
            titulo
        )}:
                </strong>

                <br>

                ${escaparHtml(
            valor
        )}

            </div>
        `;

    }


    function textoLongo(
        valor
    ) {

        const texto =
            String(
                valor || ""
            ).trim();

        if (!texto) {

            return `
                <span class="text-muted">
                    Não informado
                </span>
            `;

        }

        return escaparHtml(
            texto
        ).replace(
            /\n/g,
            "<br>"
        );

    }


    function normalizarTexto(
        valor
    ) {

        return String(
            valor || ""
        )
            .trim()
            .toLocaleLowerCase(
                "pt-PT"
            );

    }


    function definirTexto(
        seletor,
        valor
    ) {

        if ($(seletor).length) {
            $(seletor).text(valor);
        }

    }


    function escaparHtml(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }


    function escaparCampoCsv(
        valor
    ) {

        const texto =
            String(
                valor ?? ""
            ).replace(
                /"/g,
                '""'
            );

        return (
            '"'
            + texto
            + '"'
        );

    }

})(jQuery);