"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const FECHO_EXERCICIO_URL =
        "/erpplus/app/ajax/fecho_exercicio_ajax.php";


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaFechoExercicioExiste()) {
            return;
        }

        configurarEventosFechoExercicio();

        carregarDadosIniciaisFechoExercicio()
            .catch(function (erro) {

                console.error(
                    "Erro na inicialização do Fecho do Exercício:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaFechoExercicioExiste() {

        return (
            $("#formFechoExercicio").length > 0
            && $("#fechoExercicioId").length > 0
            && $("#btnDiagnosticarFecho").length > 0
            && $("#btnFecharExercicio").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosFechoExercicio() {

        $("#formFechoExercicio").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                diagnosticarExercicio()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao diagnosticar exercício:",
                            erro
                        );

                    });

            }
        );

        $("#btnDiagnosticarFecho").on(
            "click",
            function () {

                diagnosticarExercicio()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao diagnosticar exercício:",
                            erro
                        );

                    });

            }
        );

        $("#btnFecharExercicio").on(
            "click",
            function () {

                confirmarFechoExercicio()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao fechar exercício:",
                            erro
                        );

                    });

            }
        );

        $("#fechoExercicioId").on(
            "change",
            function () {

                limparDiagnosticoFechoExercicio();

                const exercicioId =
                    Number(
                        $(this).val() || 0
                    );

                $("#btnDiagnosticarFecho")
                    .prop(
                        "disabled",
                        exercicioId <= 0
                    );

            }
        );

    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisFechoExercicio() {

        mostrarCarregamentoFechoExercicio(true);

        bloquearFormularioFechoExercicio(true);

        try {

            const resposta =
                await requisicaoFechoExercicio(
                    FECHO_EXERCICIO_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            preencherExerciciosFechoExercicio(
                dados.exercicios || [],
                dados.exercicio_aberto_id
                ?? null
            );

            const exercicioSelecionado =
                Number(
                    $("#fechoExercicioId")
                        .val()
                    || 0
                );

            if (exercicioSelecionado > 0) {

                await diagnosticarExercicio();

            } else {

                limparDiagnosticoFechoExercicio();

            }

        } catch (erro) {

            console.error(
                "Erro ao carregar dados iniciais do Fecho do Exercício:",
                erro
            );

            mostrarErroFechoExercicio(
                erro.message
            );

        } finally {

            mostrarCarregamentoFechoExercicio(false);

            bloquearFormularioFechoExercicio(false);

        }

    }


    // ==================================================
    // PREENCHER EXERCÍCIOS
    // ==================================================

    function preencherExerciciosFechoExercicio(
        exercicios,
        exercicioAbertoId
    ) {

        const select =
            $("#fechoExercicioId");

        select.empty();

        select.append(`
            <option value="">
                Selecione o exercício
            </option>
        `);

        exercicios.forEach(
            function (exercicio) {

                const status =
                    String(
                        exercicio.status_nome
                        || ""
                    );

                const selecionado =
                    Number(exercicio.id)
                        === Number(
                            exercicioAbertoId
                        )
                        ? "selected"
                        : "";

                const texto =
                    [
                        exercicio.codigo
                        || "Exercício",

                        exercicio.descricao
                        || "",

                        status
                    ]
                        .filter(Boolean)
                        .join(" - ");

                select.append(`
                    <option
                        value="${Number(exercicio.id)}"
                        ${selecionado}>

                        ${escaparHtmlFechoExercicio(
                    texto
                )}

                    </option>
                `);

            }
        );

        $("#btnDiagnosticarFecho")
            .prop(
                "disabled",
                Number(
                    select.val() || 0
                ) <= 0
            );

    }


    // ==================================================
    // DIAGNOSTICAR
    // ==================================================

    async function diagnosticarExercicio() {

        const exercicioId =
            Number(
                $("#fechoExercicioId")
                    .val()
                || 0
            );

        if (exercicioId <= 0) {

            mostrarErroFechoExercicio(
                "Selecione um exercício contabilístico."
            );

            return;
        }

        mostrarCarregamentoFechoExercicio(true);

        bloquearFormularioFechoExercicio(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "diagnosticar",

                    exercicio_id:
                        String(exercicioId)
                });

            const resposta =
                await requisicaoFechoExercicio(
                    FECHO_EXERCICIO_URL
                    + "?"
                    + parametros.toString()
                );

            const dados =
                resposta.data || {};

            preencherDiagnosticoFechoExercicio(
                dados
            );

        } catch (erro) {

            limparDiagnosticoFechoExercicio();

            mostrarErroFechoExercicio(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoFechoExercicio(false);

            bloquearFormularioFechoExercicio(false);

        }

    }


    // ==================================================
    // PREENCHER DIAGNÓSTICO
    // ==================================================

    function preencherDiagnosticoFechoExercicio(
        dados
    ) {

        const exercicio =
            dados.exercicio || {};

        const resultado =
            dados.resultado || {};

        const periodos =
            dados.periodos || {};

        const configuracao =
            dados.configuracao || {};

        const lancamento =
            dados.lancamento_encerramento
            || null;

        definirTextoFechoExercicio(
            "#fechoCodigoExercicio",
            exercicio.codigo || "-"
        );

        definirTextoFechoExercicio(
            "#fechoDescricaoExercicio",
            exercicio.descricao || "-"
        );

        definirTextoFechoExercicio(
            "#fechoPeriodoExercicio",
            formatarPeriodoFechoExercicio(
                exercicio.data_inicio,
                exercicio.data_fim
            )
        );

        definirTextoFechoExercicio(
            "#fechoStatusExercicio",
            exercicio.status_nome || "-"
        );

        definirTextoFechoExercicio(
            "#fechoTotalRendimentos",
            formatarMoedaFechoExercicio(
                resultado.total_rendimentos
            )
        );

        definirTextoFechoExercicio(
            "#fechoTotalGastos",
            formatarMoedaFechoExercicio(
                resultado.total_gastos
            )
        );

        definirTextoFechoExercicio(
            "#fechoResultadoLiquido",
            formatarMoedaFechoExercicio(
                resultado.lucro_prejuizo
            )
        );

        definirTextoFechoExercicio(
            "#fechoTipoResultado",
            resultado.tipo_resultado || "-"
        );

        definirTextoFechoExercicio(
            "#fechoLancamentosPendentes",
            Number(
                dados.lancamentos_pendentes
                || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

        definirTextoFechoExercicio(
            "#fechoTotalPeriodos",
            Number(
                periodos.total || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

        definirTextoFechoExercicio(
            "#fechoPeriodosAbertos",
            Number(
                periodos.abertos || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

        definirTextoFechoExercicio(
            "#fechoPeriodosFechados",
            Number(
                periodos.fechados || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

        atualizarIndicadorConfiguracaoFecho(
            "#fechoContaResultadoConfigurada",
            Boolean(
                configuracao
                    .conta_resultado_configurada
            ),
            "Configurada",
            "Não configurada"
        );

        atualizarIndicadorConfiguracaoFecho(
            "#fechoDiarioEncerramentoConfigurado",
            Boolean(
                configuracao
                    .diario_encerramento_configurado
            ),
            "Configurado",
            "Não configurado"
        );

        preencherLancamentoEncerramentoFecho(
            lancamento
        );

        atualizarEstadoResultadoFecho(
            resultado
        );

        atualizarEstadoGeralFecho(
            dados
        );

        $("#painelDiagnosticoFecho")
            .show();

        $("#btnFecharExercicio")
            .prop(
                "disabled",
                !Boolean(
                    dados.pode_fechar
                )
            );

        $("#fechoAvisoConfirmacao")
            .toggle(
                Boolean(
                    dados.pode_fechar
                )
            );

    }


    // ==================================================
    // ESTADO DO RESULTADO
    // ==================================================

    function atualizarEstadoResultadoFecho(
        resultado
    ) {

        const elemento =
            $("#estadoResultadoFecho");

        elemento.removeClass(
            "alert-success alert-danger alert-warning"
        );

        const valor =
            Number(
                resultado.lucro_prejuizo
                || 0
            );

        if (valor > 0) {

            elemento
                .addClass(
                    "alert-success"
                )
                .html(`
                    <strong>Lucro do exercício.</strong>
                    O resultado apurado é de
                    ${formatarMoedaFechoExercicio(
                    valor
                )}.
                `);

        } else if (valor < 0) {

            elemento
                .addClass(
                    "alert-danger"
                )
                .html(`
                    <strong>Prejuízo do exercício.</strong>
                    O resultado apurado é de
                    ${formatarMoedaFechoExercicio(
                    Math.abs(valor)
                )}.
                `);

        } else {

            elemento
                .addClass(
                    "alert-warning"
                )
                .html(`
                    <strong>Resultado nulo.</strong>
                    Não existe lucro nem prejuízo apurado.
                `);

        }

        elemento.show();

    }


    // ==================================================
    // ESTADO GERAL
    // ==================================================

    function atualizarEstadoGeralFecho(
        dados
    ) {

        const elemento =
            $("#estadoGeralFecho");

        elemento.removeClass(
            "alert-success alert-danger alert-warning"
        );

        if (Boolean(dados.pode_fechar)) {

            elemento
                .addClass(
                    "alert-success"
                )
                .html(`
                    <strong>Exercício pronto para encerramento.</strong>
                    As validações obrigatórias foram concluídas.
                `);

            elemento.show();

            return;
        }

        const problemas = [];

        if (
            Number(
                dados.lancamentos_pendentes
                || 0
            ) > 0
        ) {
            problemas.push(
                "Existem lançamentos contabilísticos pendentes."
            );
        }

        if (
            !Boolean(
                dados.configuracao
                    ?.conta_resultado_configurada
            )
        ) {
            problemas.push(
                "A conta Resultado do Exercício não está configurada."
            );
        }

        if (
            !Boolean(
                dados.configuracao
                    ?.diario_encerramento_configurado
            )
        ) {
            problemas.push(
                "O Diário de Encerramento não está configurado."
            );
        }

        if (
            dados.lancamento_encerramento
        ) {
            problemas.push(
                "Já existe lançamento de encerramento para este exercício."
            );
        }

        const status =
            normalizarTextoFechoExercicio(
                dados.exercicio
                    ?.status_nome
                || ""
            );

        if (
            ![
                "aberto",
                "activo",
                "ativo"
            ].includes(status)
        ) {
            problemas.push(
                "O exercício não está aberto."
            );
        }

        elemento
            .addClass(
                problemas.length
                    ? "alert-danger"
                    : "alert-warning"
            )
            .html(`
                <strong>O exercício ainda não pode ser encerrado.</strong>

                ${problemas.length
                    ? `
                            <ul class="mb-0 mt-2">
                                ${problemas
                        .map(
                            function (
                                problema
                            ) {
                                return `
                                                    <li>
                                                        ${escaparHtmlFechoExercicio(
                                    problema
                                )}
                                                    </li>
                                                `;
                            }
                        )
                        .join("")
                    }
                            </ul>
                        `
                    : `
                            Reveja as configurações e os movimentos
                            contabilísticos do exercício.
                        `
                }
            `);

        elemento.show();

    }


    // ==================================================
    // CONFIGURAÇÕES
    // ==================================================

    function atualizarIndicadorConfiguracaoFecho(
        seletor,
        configurado,
        textoConfigurado,
        textoNaoConfigurado
    ) {

        const elemento =
            $(seletor);

        elemento.removeClass(
            "badge-success badge-danger"
        );

        elemento
            .addClass(
                configurado
                    ? "badge-success"
                    : "badge-danger"
            )
            .text(
                configurado
                    ? textoConfigurado
                    : textoNaoConfigurado
            );

    }


    // ==================================================
    // LANÇAMENTO DE ENCERRAMENTO
    // ==================================================

    function preencherLancamentoEncerramentoFecho(
        lancamento
    ) {

        const container =
            $("#fechoLancamentoEncerramento");

        if (!lancamento) {

            container.html(`
                <span class="text-muted">
                    Ainda não foi criado.
                </span>
            `);

            return;
        }

        container.html(`
            <div>

                <strong>
                    ${escaparHtmlFechoExercicio(
            lancamento.numero_documento
            || lancamento.numero
            || "-"
        )}
                </strong>

                <div class="text-muted">

                    ${formatarDataFechoExercicio(
            lancamento.data_lancamento
        )}

                    · Débito:
                    ${formatarMoedaFechoExercicio(
            lancamento.total_debito
        )}

                    · Crédito:
                    ${formatarMoedaFechoExercicio(
            lancamento.total_credito
        )}

                </div>

            </div>
        `);

    }


    // ==================================================
    // CONFIRMAR FECHO
    // ==================================================

    async function confirmarFechoExercicio() {

        const exercicioId =
            Number(
                $("#fechoExercicioId")
                    .val()
                || 0
            );

        if (exercicioId <= 0) {

            mostrarErroFechoExercicio(
                "Selecione um exercício contabilístico."
            );

            return;
        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Fechar exercício contabilístico?",

                html:
                    `
                        <p>
                            Esta operação irá:
                        </p>

                        <ul class="text-left">
                            <li>
                                criar o lançamento de encerramento;
                            </li>
                            <li>
                                zerar as contas de rendimentos e gastos;
                            </li>
                            <li>
                                fechar todos os períodos;
                            </li>
                            <li>
                                marcar o exercício como fechado.
                            </li>
                        </ul>

                        <p class="mb-0">
                            O processo não deve ser interrompido.
                        </p>
                    `,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, fechar exercício",

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

        mostrarCarregamentoFechoExercicio(true);

        bloquearFormularioFechoExercicio(true);

        try {

            const formData =
                new FormData();

            formData.append(
                "acao",
                "fechar"
            );

            formData.append(
                "exercicio_id",
                String(exercicioId)
            );

            const resposta =
                await requisicaoFechoExercicio(
                    FECHO_EXERCICIO_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            const dados =
                resposta.data || {};

            await Swal.fire({
                icon:
                    "success",

                title:
                    "Exercício fechado",

                html:
                    `
                        <p>
                            ${escaparHtmlFechoExercicio(
                        resposta.mensagem
                        || "O exercício foi fechado com sucesso."
                    )
                    }
                        </p>

                        <div class="text-left">

                            <strong>
                                Resultado:
                            </strong>

                            ${formatarMoedaFechoExercicio(
                        dados.resultado
                    )
                    }

                            <br>

                            <strong>
                                Tipo:
                            </strong>

                            ${escaparHtmlFechoExercicio(
                        dados.tipo_resultado
                        || "-"
                    )
                    }

                            <br>

                            <strong>
                                Lançamento:
                            </strong>

                            ${dados.lancamento_id
                        ? "#"
                        + Number(
                            dados.lancamento_id
                        )
                        : "Não necessário"
                    }

                        </div>
                    `,

                confirmButtonText:
                    "Concluir"
            });

            await carregarDadosIniciaisFechoExercicio();

        } catch (erro) {

            mostrarErroFechoExercicio(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoFechoExercicio(false);

            bloquearFormularioFechoExercicio(false);

        }

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoFechoExercicio(
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
                || "Não foi possível processar o Fecho do Exercício."
            );
        }

        return json;
    }


    // ==================================================
    // LIMPAR DIAGNÓSTICO
    // ==================================================

    function limparDiagnosticoFechoExercicio() {

        $("#painelDiagnosticoFecho")
            .hide();

        $("#estadoResultadoFecho")
            .hide();

        $("#estadoGeralFecho")
            .hide();

        $("#fechoAvisoConfirmacao")
            .hide();

        $("#btnFecharExercicio")
            .prop(
                "disabled",
                true
            );

        definirTextoFechoExercicio(
            "#fechoCodigoExercicio",
            "-"
        );

        definirTextoFechoExercicio(
            "#fechoDescricaoExercicio",
            "-"
        );

        definirTextoFechoExercicio(
            "#fechoPeriodoExercicio",
            "-"
        );

        definirTextoFechoExercicio(
            "#fechoStatusExercicio",
            "-"
        );

        definirTextoFechoExercicio(
            "#fechoTotalRendimentos",
            formatarMoedaFechoExercicio(0)
        );

        definirTextoFechoExercicio(
            "#fechoTotalGastos",
            formatarMoedaFechoExercicio(0)
        );

        definirTextoFechoExercicio(
            "#fechoResultadoLiquido",
            formatarMoedaFechoExercicio(0)
        );

        definirTextoFechoExercicio(
            "#fechoTipoResultado",
            "-"
        );

        definirTextoFechoExercicio(
            "#fechoLancamentosPendentes",
            "0"
        );

        definirTextoFechoExercicio(
            "#fechoTotalPeriodos",
            "0"
        );

        definirTextoFechoExercicio(
            "#fechoPeriodosAbertos",
            "0"
        );

        definirTextoFechoExercicio(
            "#fechoPeriodosFechados",
            "0"
        );

        $("#fechoLancamentoEncerramento")
            .html(`
                <span class="text-muted">
                    Ainda não foi criado.
                </span>
            `);

    }


    // ==================================================
    // CARREGAMENTO E BLOQUEIO
    // ==================================================

    function mostrarCarregamentoFechoExercicio(
        mostrar
    ) {

        $("#fechoExercicioCarregando")
            .toggle(
                Boolean(mostrar)
            );

    }


    function bloquearFormularioFechoExercicio(
        bloquear
    ) {

        $("#formFechoExercicio")
            .find(
                "input, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnFecharExercicio")
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        if (!bloquear) {

            const podeFechar =
                $("#estadoGeralFecho")
                    .hasClass(
                        "alert-success"
                    );

            $("#btnFecharExercicio")
                .prop(
                    "disabled",
                    !podeFechar
                );

            $("#btnDiagnosticarFecho")
                .prop(
                    "disabled",
                    Number(
                        $("#fechoExercicioId")
                            .val()
                        || 0
                    ) <= 0
                );

        }

    }


    // ==================================================
    // MENSAGENS
    // ==================================================

    function mostrarErroFechoExercicio(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível processar",

            text:
                mensagem
                || "Ocorreu um erro no Fecho do Exercício.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // FORMATAÇÃO
    // ==================================================

    function formatarMoedaFechoExercicio(
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


    function formatarDataFechoExercicio(
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
            return escaparHtmlFechoExercicio(
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


    function formatarPeriodoFechoExercicio(
        dataInicio,
        dataFim
    ) {

        return (
            formatarDataFechoExercicio(
                dataInicio
            )
            + " a "
            + formatarDataFechoExercicio(
                dataFim
            )
        );

    }


    function normalizarTextoFechoExercicio(
        texto
    ) {

        return String(
            texto || ""
        )
            .trim()
            .toLocaleLowerCase(
                "pt-PT"
            );

    }


    function definirTextoFechoExercicio(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function escaparHtmlFechoExercicio(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);