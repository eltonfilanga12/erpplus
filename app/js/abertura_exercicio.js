"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const ABERTURA_EXERCICIO_URL =
        "/erpplus/app/ajax/abertura_exercicio_ajax.php";


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaAberturaExercicioExiste()) {
            return;
        }

        configurarEventosAberturaExercicio();

        carregarDadosIniciaisAberturaExercicio()
            .catch(function (erro) {

                console.error(
                    "Erro na inicialização da Abertura do Exercício:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaAberturaExercicioExiste() {

        return (
            $("#formAberturaExercicio").length > 0
            && $("#aberturaExercicioOrigemId").length > 0
            && $("#btnDiagnosticarAbertura").length > 0
            && $("#btnGerarAbertura").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosAberturaExercicio() {

        $("#formAberturaExercicio").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                diagnosticarAberturaExercicio()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao diagnosticar abertura:",
                            erro
                        );

                    });

            }
        );

        $("#btnDiagnosticarAbertura").on(
            "click",
            function () {

                diagnosticarAberturaExercicio()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao diagnosticar abertura:",
                            erro
                        );

                    });

            }
        );

        $("#btnGerarAbertura").on(
            "click",
            function () {

                confirmarGeracaoAbertura()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao gerar abertura:",
                            erro
                        );

                    });

            }
        );

        $("#aberturaExercicioOrigemId").on(
            "change",
            function () {

                limparDiagnosticoAbertura();

                const exercicioId =
                    Number(
                        $(this).val() || 0
                    );

                $("#btnDiagnosticarAbertura")
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

    async function carregarDadosIniciaisAberturaExercicio() {

        mostrarCarregamentoAbertura(true);

        bloquearFormularioAbertura(true);

        try {

            const resposta =
                await requisicaoAberturaExercicio(
                    ABERTURA_EXERCICIO_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            preencherExerciciosOrigem(
                dados.exercicios || [],
                dados.exercicio_encerrado_id
                ?? null
            );

            const exercicioSelecionado =
                Number(
                    $("#aberturaExercicioOrigemId")
                        .val()
                    || 0
                );

            if (exercicioSelecionado > 0) {

                await diagnosticarAberturaExercicio();

            } else {

                limparDiagnosticoAbertura();

            }

        } catch (erro) {

            console.error(
                "Erro ao carregar dados da Abertura do Exercício:",
                erro
            );

            mostrarErroAbertura(
                erro.message
            );

        } finally {

            mostrarCarregamentoAbertura(false);

            bloquearFormularioAbertura(false);

        }

    }


    // ==================================================
    // PREENCHER EXERCÍCIOS
    // ==================================================

    function preencherExerciciosOrigem(
        exercicios,
        exercicioEncerradoId
    ) {

        const select =
            $("#aberturaExercicioOrigemId");

        select.empty();

        select.append(`
            <option value="">
                Selecione o exercício encerrado
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
                            exercicioEncerradoId
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

                        ${escaparHtmlAbertura(
                    texto
                )}

                    </option>
                `);

            }
        );

        $("#btnDiagnosticarAbertura")
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

    async function diagnosticarAberturaExercicio() {

        const exercicioOrigemId =
            Number(
                $("#aberturaExercicioOrigemId")
                    .val()
                || 0
            );

        if (exercicioOrigemId <= 0) {

            mostrarErroAbertura(
                "Selecione o exercício encerrado."
            );

            return;
        }

        mostrarCarregamentoAbertura(true);

        bloquearFormularioAbertura(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "diagnosticar",

                    exercicio_origem_id:
                        String(
                            exercicioOrigemId
                        )
                });

            const resposta =
                await requisicaoAberturaExercicio(
                    ABERTURA_EXERCICIO_URL
                    + "?"
                    + parametros.toString()
                );

            preencherDiagnosticoAbertura(
                resposta.data || {}
            );

        } catch (erro) {

            limparDiagnosticoAbertura();

            mostrarErroAbertura(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoAbertura(false);

            bloquearFormularioAbertura(false);

        }

    }


    // ==================================================
    // PREENCHER DIAGNÓSTICO
    // ==================================================

    function preencherDiagnosticoAbertura(
        dados
    ) {

        const origem =
            dados.exercicio_origem || {};

        const destino =
            dados.exercicio_destino || {};

        const saldos =
            dados.saldos || {};

        const configuracao =
            dados.configuracao || {};

        definirTextoAbertura(
            "#aberturaCodigoOrigem",
            origem.codigo || "-"
        );

        definirTextoAbertura(
            "#aberturaDescricaoOrigem",
            origem.descricao || "-"
        );

        definirTextoAbertura(
            "#aberturaStatusOrigem",
            origem.status_nome || "-"
        );

        definirTextoAbertura(
            "#aberturaCodigoDestino",
            destino.codigo || "-"
        );

        definirTextoAbertura(
            "#aberturaDescricaoDestino",
            destino.descricao || "-"
        );

        definirTextoAbertura(
            "#aberturaStatusDestino",
            destino.status_nome || "-"
        );

        definirTextoAbertura(
            "#aberturaDataDestino",
            formatarDataAbertura(
                destino.data_inicio
            )
        );

        definirTextoAbertura(
            "#aberturaQuantidadeContas",
            Number(
                saldos.quantidade_contas || 0
            ).toLocaleString(
                "pt-PT"
            )
        );

        definirTextoAbertura(
            "#aberturaTotalDebito",
            formatarMoedaAbertura(
                saldos.total_debito
            )
        );

        definirTextoAbertura(
            "#aberturaTotalCredito",
            formatarMoedaAbertura(
                saldos.total_credito
            )
        );

        definirTextoAbertura(
            "#aberturaDiferenca",
            formatarMoedaAbertura(
                Math.abs(
                    Number(
                        saldos.diferenca || 0
                    )
                )
            )
        );

        definirTextoAbertura(
            "#aberturaResultadoTransportar",
            formatarMoedaAbertura(
                saldos.resultado_transportar
            )
        );

        atualizarIndicadorConfiguracaoAbertura(
            "#aberturaContaResultadosConfigurada",
            Boolean(
                configuracao
                    .conta_resultados_transitados
            ),
            "Configurada",
            "Não configurada"
        );

        atualizarIndicadorConfiguracaoAbertura(
            "#aberturaDiarioConfigurado",
            Boolean(
                configuracao.diario_abertura
            ),
            "Configurado",
            "Não configurado"
        );

        preencherAberturaExistente(
            dados.abertura_existente
            || null
        );

        preencherTabelaSaldosAbertura(
            saldos.contas || []
        );

        atualizarEstadoAbertura(
            dados
        );

        $("#painelDiagnosticoAbertura")
            .show();

        $("#btnGerarAbertura")
            .prop(
                "disabled",
                !Boolean(
                    dados.pode_abrir
                )
            );

        $("#avisoGerarAbertura")
            .toggle(
                Boolean(
                    dados.pode_abrir
                )
            );

    }


    // ==================================================
    // ESTADO GERAL
    // ==================================================

    function atualizarEstadoAbertura(
        dados
    ) {

        const elemento =
            $("#estadoGeralAbertura");

        elemento.removeClass(
            "alert-success alert-danger alert-warning"
        );

        if (Boolean(dados.pode_abrir)) {

            elemento
                .addClass(
                    "alert-success"
                )
                .html(`
                    <strong>Abertura pronta para ser gerada.</strong>
                    Os saldos patrimoniais estão equilibrados
                    e as configurações necessárias existem.
                `);

            elemento.show();

            return;
        }

        const problemas = [];

        const origemStatus =
            normalizarTextoAbertura(
                dados.exercicio_origem
                    ?.status_nome
                || ""
            );

        const destinoStatus =
            normalizarTextoAbertura(
                dados.exercicio_destino
                    ?.status_nome
                || ""
            );

        if (
            ![
                "encerrado",
                "fechado"
            ].includes(origemStatus)
        ) {
            problemas.push(
                "O exercício de origem ainda não está encerrado."
            );
        }

        if (
            ![
                "aberto",
                "activo",
                "ativo"
            ].includes(destinoStatus)
        ) {
            problemas.push(
                "O exercício de destino não está aberto."
            );
        }

        if (
            !Boolean(
                dados.configuracao
                    ?.conta_resultados_transitados
            )
        ) {
            problemas.push(
                "A conta Resultados Transitados não está configurada."
            );
        }

        if (
            !Boolean(
                dados.configuracao
                    ?.diario_abertura
            )
        ) {
            problemas.push(
                "O Diário de Abertura não está configurado."
            );
        }

        if (
            dados.abertura_existente
        ) {
            problemas.push(
                "Já existe lançamento de abertura no exercício de destino."
            );
        }

        if (
            Math.abs(
                Number(
                    dados.saldos
                        ?.diferenca
                    || 0
                )
            ) > 0.009
        ) {
            problemas.push(
                "Os saldos patrimoniais estão desequilibrados."
            );
        }

        elemento
            .addClass(
                problemas.length
                    ? "alert-danger"
                    : "alert-warning"
            )
            .html(`
                <strong>
                    A abertura ainda não pode ser gerada.
                </strong>

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
                                                        ${escaparHtmlAbertura(
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
                            Reveja os saldos e as configurações.
                        `
                }
            `);

        elemento.show();

    }


    // ==================================================
    // TABELA DE SALDOS
    // ==================================================

    function preencherTabelaSaldosAbertura(
        contas
    ) {

        const tbody =
            $("#tabelaSaldosAbertura tbody");

        tbody.empty();

        if (!contas.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="5"
                        class="text-center text-muted py-3">

                        Nenhum saldo patrimonial encontrado.

                    </td>

                </tr>
            `);

            return;
        }

        contas.forEach(
            function (conta) {

                const saldo =
                    Number(
                        conta.saldo || 0
                    );

                tbody.append(`
                    <tr>

                        <td>
                            ${escaparHtmlAbertura(
                    conta.codigo || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlAbertura(
                    conta.nome || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlAbertura(
                    conta.grupo || "-"
                )}
                        </td>

                        <td class="text-right">
                            ${saldo > 0
                        ? formatarMoedaAbertura(
                            saldo
                        )
                        : formatarMoedaAbertura(
                            0
                        )
                    }
                        </td>

                        <td class="text-right">
                            ${saldo < 0
                        ? formatarMoedaAbertura(
                            Math.abs(saldo)
                        )
                        : formatarMoedaAbertura(
                            0
                        )
                    }
                        </td>

                    </tr>
                `);

            }
        );

    }


    // ==================================================
    // INDICADORES
    // ==================================================

    function atualizarIndicadorConfiguracaoAbertura(
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
    // ABERTURA EXISTENTE
    // ==================================================

    function preencherAberturaExistente(
        abertura
    ) {

        const container =
            $("#aberturaLancamentoExistente");

        if (!abertura) {

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
                    ${escaparHtmlAbertura(
            abertura.numero_documento
            || abertura.numero
            || "-"
        )}
                </strong>

                <div class="text-muted">

                    ${formatarDataAbertura(
            abertura.data_lancamento
        )}

                    · Débito:
                    ${formatarMoedaAbertura(
            abertura.total_debito
        )}

                    · Crédito:
                    ${formatarMoedaAbertura(
            abertura.total_credito
        )}

                </div>

            </div>
        `);

    }


    // ==================================================
    // CONFIRMAR GERAÇÃO
    // ==================================================

    async function confirmarGeracaoAbertura() {

        const exercicioOrigemId =
            Number(
                $("#aberturaExercicioOrigemId")
                    .val()
                || 0
            );

        if (exercicioOrigemId <= 0) {

            mostrarErroAbertura(
                "Selecione o exercício encerrado."
            );

            return;
        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Gerar abertura do exercício?",

                html:
                    `
                        <p>
                            Esta operação irá transportar os saldos
                            patrimoniais para o exercício seguinte.
                        </p>

                        <ul class="text-left">
                            <li>
                                Ativos serão transportados;
                            </li>
                            <li>
                                Passivos serão transportados;
                            </li>
                            <li>
                                Capital próprio será transportado;
                            </li>
                            <li>
                                O resultado será transferido para
                                Resultados Transitados.
                            </li>
                        </ul>
                    `,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, gerar abertura",

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

        mostrarCarregamentoAbertura(true);

        bloquearFormularioAbertura(true);

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                "abrir"
            );

            dados.append(
                "exercicio_origem_id",
                String(
                    exercicioOrigemId
                )
            );

            const resposta =
                await requisicaoAberturaExercicio(
                    ABERTURA_EXERCICIO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            const resultado =
                resposta.data || {};

            await Swal.fire({
                icon:
                    "success",

                title:
                    "Abertura gerada",

                html:
                    `
                        <p>
                            ${escaparHtmlAbertura(
                        resposta.mensagem
                        || "A abertura foi gerada com sucesso."
                    )}
                        </p>

                        <div class="text-left">

                            <strong>
                                Exercício destino:
                            </strong>

                            ${escaparHtmlAbertura(
                        resultado.exercicio_destino_codigo
                        || "-"
                    )}

                            <br>

                            <strong>
                                Data de abertura:
                            </strong>

                            ${formatarDataAbertura(
                        resultado.data_abertura
                    )}

                            <br>

                            <strong>
                                Contas transportadas:
                            </strong>

                            ${Number(
                        resultado.quantidade_contas
                        || 0
                    ).toLocaleString(
                        "pt-PT"
                    )}

                            <br>

                            <strong>
                                Lançamento:
                            </strong>

                            ${resultado.lancamento_id
                        ? "#"
                        + Number(
                            resultado.lancamento_id
                        )
                        : "Não necessário"
                    }

                        </div>
                    `,

                confirmButtonText:
                    "Concluir"
            });

            await carregarDadosIniciaisAberturaExercicio();

        } catch (erro) {

            mostrarErroAbertura(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoAbertura(false);

            bloquearFormularioAbertura(false);

        }

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoAberturaExercicio(
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
                || "Não foi possível gerar a abertura do exercício."
            );
        }

        return json;
    }


    // ==================================================
    // LIMPAR
    // ==================================================

    function limparDiagnosticoAbertura() {

        $("#painelDiagnosticoAbertura")
            .hide();

        $("#estadoGeralAbertura")
            .hide();

        $("#avisoGerarAbertura")
            .hide();

        $("#btnGerarAbertura")
            .prop(
                "disabled",
                true
            );

        definirTextoAbertura(
            "#aberturaCodigoOrigem",
            "-"
        );

        definirTextoAbertura(
            "#aberturaDescricaoOrigem",
            "-"
        );

        definirTextoAbertura(
            "#aberturaStatusOrigem",
            "-"
        );

        definirTextoAbertura(
            "#aberturaCodigoDestino",
            "-"
        );

        definirTextoAbertura(
            "#aberturaDescricaoDestino",
            "-"
        );

        definirTextoAbertura(
            "#aberturaStatusDestino",
            "-"
        );

        definirTextoAbertura(
            "#aberturaDataDestino",
            "-"
        );

        definirTextoAbertura(
            "#aberturaQuantidadeContas",
            "0"
        );

        definirTextoAbertura(
            "#aberturaTotalDebito",
            formatarMoedaAbertura(0)
        );

        definirTextoAbertura(
            "#aberturaTotalCredito",
            formatarMoedaAbertura(0)
        );

        definirTextoAbertura(
            "#aberturaDiferenca",
            formatarMoedaAbertura(0)
        );

        definirTextoAbertura(
            "#aberturaResultadoTransportar",
            formatarMoedaAbertura(0)
        );

        preencherTabelaSaldosAbertura([]);

        $("#aberturaLancamentoExistente")
            .html(`
                <span class="text-muted">
                    Ainda não foi criado.
                </span>
            `);

    }


    // ==================================================
    // CARREGAMENTO E BLOQUEIO
    // ==================================================

    function mostrarCarregamentoAbertura(
        mostrar
    ) {

        $("#aberturaExercicioCarregando")
            .toggle(
                Boolean(mostrar)
            );

    }


    function bloquearFormularioAbertura(
        bloquear
    ) {

        $("#formAberturaExercicio")
            .find(
                "input, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnGerarAbertura")
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        if (!bloquear) {

            const podeAbrir =
                $("#estadoGeralAbertura")
                    .hasClass(
                        "alert-success"
                    );

            $("#btnGerarAbertura")
                .prop(
                    "disabled",
                    !podeAbrir
                );

            $("#btnDiagnosticarAbertura")
                .prop(
                    "disabled",
                    Number(
                        $("#aberturaExercicioOrigemId")
                            .val()
                        || 0
                    ) <= 0
                );

        }

    }


    // ==================================================
    // MENSAGENS
    // ==================================================

    function mostrarErroAbertura(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível processar",

            text:
                mensagem
                || "Ocorreu um erro na Abertura do Exercício.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // FORMATAÇÃO
    // ==================================================

    function formatarMoedaAbertura(
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


    function formatarDataAbertura(
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
            return escaparHtmlAbertura(
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


    function normalizarTextoAbertura(
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


    function definirTextoAbertura(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function escaparHtmlAbertura(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);