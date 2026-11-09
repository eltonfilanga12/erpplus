"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const DIARIO_URL =
        "/erpplus/app/ajax/diario_contabilistico_ajax.php";

    let tabelaDiarios = null;

    let diariosContabilisticos = [];

    let statusDiarios = [];


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(
        async function () {

            configurarEventosDiario();

            limparResumoDiarios();

            await carregarStatusDiarios();

            iniciarTabelaDiarios();

        }
    );


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosDiario() {

        // ----------------------------------------------
        // NOVO DIÁRIO
        // ----------------------------------------------

        $("#btnNovoDiario").on(
            "click",
            abrirNovoDiario
        );


        // ----------------------------------------------
        // GUARDAR DIÁRIO
        // ----------------------------------------------

        $("#formDiarioContabilistico").on(
            "submit",
            guardarDiario
        );


        // ----------------------------------------------
        // FILTROS
        // ----------------------------------------------

        $("#pesquisaDiario").on(
            "input",
            aplicarFiltrosDiarios
        );

        $("#filtroTipoDiario").on(
            "change",
            aplicarFiltrosDiarios
        );

        $("#filtroStatusDiario").on(
            "change",
            aplicarFiltrosDiarios
        );

        $("#btnLimparFiltrosDiario").on(
            "click",
            limparFiltrosDiario
        );


        // ----------------------------------------------
        // EDITAR
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-editar-diario",
            function () {

                editarDiario(
                    Number(
                        $(this).data("id")
                        || 0
                    )
                );

            }
        );


        // ----------------------------------------------
        // ALTERAR STATUS
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-status-diario",
            function () {

                abrirStatusDiario(
                    Number(
                        $(this).data("id")
                        || 0
                    ),

                    Number(
                        $(this).data("status-id")
                        || 0
                    )
                );

            }
        );


        $("#formStatusDiario").on(
            "submit",
            guardarStatusDiario
        );


        // ----------------------------------------------
        // ELIMINAR
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-eliminar-diario",
            function () {

                abrirEliminarDiario(
                    Number(
                        $(this).data("id")
                        || 0
                    )
                );

            }
        );


        $("#formEliminarDiario").on(
            "submit",
            eliminarDiario
        );


        // ----------------------------------------------
        // TIPO DO DIÁRIO
        // ----------------------------------------------

        $("#diarioTipo").on(
            "change",
            atualizarInformacaoTipoDiario
        );


        // ----------------------------------------------
        // STATUS BLOQUEADO
        // ----------------------------------------------

        $("#novoStatusDiarioId").on(
            "change",
            function () {

                const status =
                    encontrarStatusDiario(
                        Number(
                            $(this).val()
                            || 0
                        )
                    );

                const bloqueado =
                    status
                    && normalizarTextoDiario(
                        status.nome
                    ) === "bloqueado";

                $("#alertaBloquearDiario")
                    .toggle(
                        Boolean(bloqueado)
                    );

            }
        );


        // ----------------------------------------------
        // LIMPAR MODAIS
        // ----------------------------------------------

        $("#modalDiarioContabilistico").on(
            "hidden.bs.modal",
            limparFormularioDiario
        );


        $("#modalStatusDiario").on(
            "hidden.bs.modal",
            function () {

                const formulario =
                    document.getElementById(
                        "formStatusDiario"
                    );

                if (formulario) {
                    formulario.reset();
                }

                $("#statusDiarioId")
                    .val("");

                $("#alertaBloquearDiario")
                    .hide();

            }
        );


        $("#modalEliminarDiario").on(
            "hidden.bs.modal",
            function () {

                $("#eliminarDiarioId")
                    .val("");

            }
        );

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoDiario(
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
                "Resposta não JSON:",
                texto
            );

            throw new Error(
                "O servidor não retornou uma resposta JSON válida."
            );

        }

        if (
            resposta.status === 401
        ) {

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
                || "Não foi possível concluir a operação."
            );

        }

        return json;
    }


    // ==================================================
    // STATUS
    // ==================================================

    async function carregarStatusDiarios() {

        try {

            const resposta =
                await requisicaoDiario(
                    DIARIO_URL
                    + "?acao=status_diarios"
                );

            statusDiarios =
                resposta.data || [];

            preencherSelectsStatusDiario();

        } catch (erro) {

            mostrarErroDiario(
                erro.message
            );

        }

    }


    function preencherSelectsStatusDiario() {

        const selects = [
            $("#diarioStatusId"),
            $("#novoStatusDiarioId"),
            $("#filtroStatusDiario")
        ];

        selects.forEach(
            function (select) {

                const filtro =
                    select.attr("id")
                    === "filtroStatusDiario";

                select.html(`
                    <option value="">
                        ${filtro
                        ? "Todos os status"
                        : "Seleccione o status"
                    }
                    </option>
                `);

                statusDiarios.forEach(
                    function (status) {

                        select.append(`
                            <option value="${status.id}">
                                ${escaparHtmlDiario(
                            status.nome
                        )
                            }
                            </option>
                        `);

                    }
                );

            }
        );

    }


    function encontrarStatusDiario(
        statusId
    ) {

        return statusDiarios.find(
            function (status) {

                return Number(status.id)
                    === Number(statusId);

            }
        ) || null;
    }


    function encontrarStatusPorNome(
        nome
    ) {

        const nomeNormalizado =
            normalizarTextoDiario(
                nome
            );

        return statusDiarios.find(
            function (status) {

                return normalizarTextoDiario(
                    status.nome
                ) === nomeNormalizado;

            }
        ) || null;
    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function iniciarTabelaDiarios() {

        tabelaDiarios =
            $("#diariosContabilisticosTable")
                .DataTable({

                    ajax: {

                        url:
                            DIARIO_URL,

                        data: {
                            acao:
                                "listar"
                        },

                        dataSrc:
                            function (resposta) {

                                diariosContabilisticos =
                                    resposta.data
                                    || [];

                                atualizarResumoDiarios();

                                return diariosContabilisticos;

                            },

                        error:
                            function (xhr) {

                                let mensagem =
                                    "Não foi possível carregar os diários contabilísticos.";

                                try {

                                    const resposta =
                                        JSON.parse(
                                            xhr.responseText
                                        );

                                    mensagem =
                                        resposta.mensagem
                                        || mensagem;

                                } catch (erro) {

                                    console.error(
                                        xhr.responseText
                                    );

                                }

                                mostrarErroDiario(
                                    mensagem
                                );

                            }

                    },


                    columns: [

                        {
                            data: null,

                            render: function (
                                data,
                                type,
                                row,
                                meta
                            ) {

                                const numero =
                                    meta.row
                                    + meta.settings._iDisplayStart
                                    + 1;

                                return String(
                                    numero
                                ).padStart(
                                    4,
                                    "0"
                                );
                            }
                        },

                        {
                            data:
                                "codigo"
                        },

                        {
                            data:
                                "nome"
                        },

                        {
                            data:
                                "tipo",

                            render:
                                function (valor) {

                                    return formatarTipoDiario(
                                        valor
                                    );

                                }
                        },

                        {
                            data:
                                "permite_manual",

                            render:
                                function (valor) {

                                    return criarBadgeSimNao(
                                        Number(valor) === 1
                                    );

                                }
                        },

                        {
                            data:
                                "numeracao_automatica",

                            render:
                                function (valor) {

                                    return criarBadgeSimNao(
                                        Number(valor) === 1
                                    );

                                }
                        },

                        {
                            data:
                                "ultimo_numero",

                            render:
                                function (valor) {

                                    return Number(
                                        valor || 0
                                    );

                                }
                        },

                        {
                            data:
                                "quantidade_lancamentos",

                            render:
                                function (valor) {

                                    return Number(
                                        valor || 0
                                    );

                                }
                        },

                        {
                            data:
                                "status_nome",

                            render:
                                function (valor) {

                                    return criarBadgeStatusDiario(
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
                                "text-right acoes-diario",

                            render:
                                function (
                                    dados,
                                    tipo,
                                    diario
                                ) {

                                    return criarBotoesDiario(
                                        diario
                                    );

                                }
                        }

                    ],


                    order: [
                        [
                            1,
                            "asc"
                        ]
                    ],


                    pageLength:
                        10,


                    lengthMenu: [
                        [
                            10,
                            25,
                            50,
                            100
                        ],
                        [
                            10,
                            25,
                            50,
                            100
                        ]
                    ],


                    responsive:
                        true,


                    language: {
                        url:
                            "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
                    }

                });

    }


    // ==================================================
    // BOTÕES DA TABELA
    // ==================================================

    function criarBotoesDiario(
        diario
    ) {

        const status =
            normalizarTextoDiario(
                diario.status_nome
            );

        const bloqueado =
            status === "bloqueado";

        const possuiLancamentos =
            Number(
                diario.quantidade_lancamentos
                || 0
            ) > 0;

        const botaoEditar =
            bloqueado
                ? ""
                : `
                    <button
                        type="button"
                        class="btn btn-sm btn-info btn-editar-diario"
                        data-id="${diario.id}"
                        title="Editar diário">

                        <i class="fa fa-edit"></i>

                    </button>
                `;

        const botaoEliminar =
            possuiLancamentos
                ? ""
                : `
                    <button
                        type="button"
                        class="btn btn-sm btn-danger btn-eliminar-diario"
                        data-id="${diario.id}"
                        title="Eliminar diário">

                        <i class="fa fa-trash"></i>

                    </button>
                `;

        return `
            ${botaoEditar}

            <button
                type="button"
                class="btn btn-sm btn-warning btn-status-diario"
                data-id="${diario.id}"
                data-status-id="${diario.status_id}"
                title="Alterar status">

                <i class="fa fa-exchange"></i>

            </button>

            ${botaoEliminar}
        `;

    }


    // ==================================================
    // FILTROS
    // ==================================================

    function aplicarFiltrosDiarios() {

        if (!tabelaDiarios) {
            return;
        }

        const pesquisa =
            String(
                $("#pesquisaDiario").val()
                || ""
            );

        const tipo =
            String(
                $("#filtroTipoDiario").val()
                || ""
            );

        const statusId =
            Number(
                $("#filtroStatusDiario").val()
                || 0
            );

        const status =
            statusId > 0
                ? encontrarStatusDiario(
                    statusId
                )
                : null;

        tabelaDiarios
            .search(
                pesquisa
            );

        tabelaDiarios
            .column(3)
            .search(
                tipo
                    ? "^"
                    + escaparRegexDiario(
                        formatarTipoDiario(tipo)
                    )
                    + "$"
                    : "",
                true,
                false
            );

        tabelaDiarios
            .column(8)
            .search(
                status
                    ? escaparRegexDiario(
                        status.nome
                    )
                    : "",
                true,
                false
            );

        tabelaDiarios.draw();

    }


    function limparFiltrosDiario() {

        $("#pesquisaDiario")
            .val("");

        $("#filtroTipoDiario")
            .val("");

        $("#filtroStatusDiario")
            .val("");

        if (tabelaDiarios) {

            tabelaDiarios
                .search("");

            tabelaDiarios
                .column(3)
                .search("");

            tabelaDiarios
                .column(8)
                .search("");

            tabelaDiarios.draw();

        }

    }


    // ==================================================
    // NOVO DIÁRIO
    // ==================================================

    function abrirNovoDiario() {

        limparFormularioDiario();

        const statusActivo =
            encontrarStatusPorNome(
                "Activo"
            );

        $("#diarioStatusId")
            .val(
                statusActivo
                    ? String(statusActivo.id)
                    : ""
            );

        $("#diarioNumeracaoAutomatica")
            .prop(
                "checked",
                true
            );

        $("#tituloModalDiario")
            .text(
                "Novo Diário Contabilístico"
            );

        atualizarInformacaoTipoDiario();

        $("#modalDiarioContabilistico")
            .modal("show");

    }


    // ==================================================
    // EDITAR DIÁRIO
    // ==================================================

    async function editarDiario(
        diarioId
    ) {

        if (diarioId <= 0) {
            return;
        }

        try {

            const resposta =
                await requisicaoDiario(
                    DIARIO_URL
                    + "?acao=buscar_diario&id="
                    + encodeURIComponent(
                        diarioId
                    )
                );

            const diario =
                resposta.data;

            limparFormularioDiario();

            $("#diarioId")
                .val(
                    diario.id
                );

            $("#diarioCodigo")
                .val(
                    diario.codigo
                    || ""
                );

            $("#diarioNome")
                .val(
                    diario.nome
                    || ""
                );

            $("#diarioTipo")
                .val(
                    diario.tipo
                    || ""
                );

            $("#diarioStatusId")
                .val(
                    String(
                        diario.status_id
                        || ""
                    )
                );

            $("#diarioDescricao")
                .val(
                    diario.descricao
                    || ""
                );

            $("#diarioPermiteManual")
                .prop(
                    "checked",
                    Number(
                        diario.permite_manual
                        || 0
                    ) === 1
                );

            $("#diarioNumeracaoAutomatica")
                .prop(
                    "checked",
                    Number(
                        diario.numeracao_automatica
                        || 0
                    ) === 1
                );

            $("#tituloModalDiario")
                .text(
                    "Editar Diário Contabilístico"
                );

            atualizarInformacaoTipoDiario();

            $("#modalDiarioContabilistico")
                .modal("show");

        } catch (erro) {

            mostrarErroDiario(
                erro.message
            );

        }

    }


    // ==================================================
    // GUARDAR DIÁRIO
    // ==================================================

    async function guardarDiario(
        evento
    ) {

        evento.preventDefault();

        const formulario =
            evento.currentTarget;

        const botao =
            $("#btnGuardarDiario");

        bloquearBotaoDiario(
            botao,
            "A guardar..."
        );

        try {

            const dados =
                new FormData(
                    formulario
                );

            dados.append(
                "acao",
                "guardar_diario"
            );

            preencherCheckboxFormDataDiario(
                dados,
                "permite_manual",
                "#diarioPermiteManual"
            );

            preencherCheckboxFormDataDiario(
                dados,
                "numeracao_automatica",
                "#diarioNumeracaoAutomatica"
            );

            dados.set(
                "status_id",
                String(
                    $("#diarioStatusId").val()
                    || ""
                )
            );

            const resposta =
                await requisicaoDiario(
                    DIARIO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalDiarioContabilistico")
                .modal("hide");

            recarregarTabelaDiarios();

            toastDiario(
                resposta.mensagem
                || "Diário guardado com sucesso."
            );

        } catch (erro) {

            mostrarErroDiario(
                erro.message
            );

        } finally {

            restaurarBotaoDiario(
                botao,
                `
                    <i class="fa fa-save"></i>
                    Guardar Diário
                `
            );

        }

    }


    // ==================================================
    // ALTERAR STATUS
    // ==================================================

    function abrirStatusDiario(
        diarioId,
        statusId
    ) {

        $("#statusDiarioId")
            .val(
                diarioId
            );

        $("#novoStatusDiarioId")
            .val(
                String(
                    statusId
                    || ""
                )
            )
            .trigger(
                "change"
            );

        $("#modalStatusDiario")
            .modal("show");

    }


    async function guardarStatusDiario(
        evento
    ) {

        evento.preventDefault();

        const diarioId =
            Number(
                $("#statusDiarioId").val()
                || 0
            );

        const statusId =
            Number(
                $("#novoStatusDiarioId").val()
                || 0
            );

        if (
            diarioId <= 0
            || statusId <= 0
        ) {

            mostrarAvisoDiario(
                "Seleccione um status válido."
            );

            return;
        }

        const status =
            encontrarStatusDiario(
                statusId
            );

        if (
            status
            && normalizarTextoDiario(
                status.nome
            ) === "bloqueado"
        ) {

            const confirmacao =
                await Swal.fire({

                    icon:
                        "warning",

                    title:
                        "Bloquear diário",

                    text:
                        "O diário ficará indisponível para edição e novos lançamentos. Deseja continuar?",

                    showCancelButton:
                        true,

                    confirmButtonText:
                        "Bloquear",

                    cancelButtonText:
                        "Cancelar"

                });

            if (!confirmacao.isConfirmed) {
                return;
            }

        }

        const botao =
            $("#btnGuardarStatusDiario");

        bloquearBotaoDiario(
            botao,
            "A atualizar..."
        );

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                "alterar_status_diario"
            );

            dados.append(
                "id",
                String(diarioId)
            );

            dados.append(
                "status_id",
                String(statusId)
            );

            const resposta =
                await requisicaoDiario(
                    DIARIO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalStatusDiario")
                .modal("hide");

            recarregarTabelaDiarios();

            toastDiario(
                resposta.mensagem
                || "Status atualizado."
            );

        } catch (erro) {

            mostrarErroDiario(
                erro.message
            );

        } finally {

            restaurarBotaoDiario(
                botao,
                `
                    <i class="fa fa-save"></i>
                    Atualizar Status
                `
            );

        }

    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    function abrirEliminarDiario(
        diarioId
    ) {

        $("#eliminarDiarioId")
            .val(
                diarioId
            );

        $("#modalEliminarDiario")
            .modal("show");

    }


    async function eliminarDiario(
        evento
    ) {

        evento.preventDefault();

        const diarioId =
            Number(
                $("#eliminarDiarioId").val()
                || 0
            );

        if (diarioId <= 0) {

            mostrarAvisoDiario(
                "Diário contabilístico inválido."
            );

            return;
        }

        const botao =
            $("#btnConfirmarEliminarDiario");

        bloquearBotaoDiario(
            botao,
            "A eliminar..."
        );

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                "eliminar_diario"
            );

            dados.append(
                "id",
                String(diarioId)
            );

            const resposta =
                await requisicaoDiario(
                    DIARIO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalEliminarDiario")
                .modal("hide");

            recarregarTabelaDiarios();

            toastDiario(
                resposta.mensagem
                || "Diário eliminado."
            );

        } catch (erro) {

            mostrarErroDiario(
                erro.message
            );

        } finally {

            restaurarBotaoDiario(
                botao,
                `
                    <i class="fa fa-trash"></i>
                    Eliminar
                `
            );

        }

    }


    // ==================================================
    // INFORMAÇÃO DO TIPO
    // ==================================================

    function atualizarInformacaoTipoDiario() {

        const tipo =
            String(
                $("#diarioTipo").val()
                || ""
            ).toUpperCase();

        const informacoes = {

            GERAL:
                "Utilizado para lançamentos contabilísticos manuais e operações diversas.",

            VENDAS:
                "Recebe os lançamentos automáticos gerados pelas vendas confirmadas.",

            COMPRAS:
                "Recebe os lançamentos automáticos gerados pelas compras confirmadas.",

            CAIXA:
                "Regista entradas e saídas financeiras realizadas em caixa.",

            BANCOS:
                "Regista movimentos de contas bancárias e conciliações.",

            STOCK:
                "Regista movimentos e ajustes contabilísticos relacionados com o stock.",

            SALARIOS:
                "Regista salários, remunerações, descontos e encargos sociais.",

            IMPOSTOS:
                "Regista apuramento, liquidação e pagamento de impostos.",

            ABERTURA:
                "Utilizado para os saldos iniciais e abertura do exercício contabilístico.",

            ENCERRAMENTO:
                "Utilizado para apuramentos e encerramento do exercício.",

            OUTROS:
                "Utilizado para operações que não se enquadram nos outros tipos."

        };

        const mensagem =
            informacoes[tipo]
            || "Seleccione um tipo para visualizar a finalidade recomendada do diário.";

        $("#informacaoTipoDiario")
            .html(`
                <i class="fa fa-info-circle"></i>
                ${escaparHtmlDiario(mensagem)}
            `);

    }


    // ==================================================
    // RESUMO
    // ==================================================

    function atualizarResumoDiarios() {

        const total =
            diariosContabilisticos.length;

        const activos =
            diariosContabilisticos.filter(
                function (diario) {

                    return normalizarTextoDiario(
                        diario.status_nome
                    ) === "activo";

                }
            ).length;

        const manuais =
            diariosContabilisticos.filter(
                function (diario) {

                    return Number(
                        diario.permite_manual
                        || 0
                    ) === 1;

                }
            ).length;

        const lancamentos =
            diariosContabilisticos.reduce(
                function (
                    totalAtual,
                    diario
                ) {

                    return totalAtual
                        + Number(
                            diario.quantidade_lancamentos
                            || 0
                        );

                },
                0
            );

        $("#resumoTotalDiarios")
            .text(total);

        $("#resumoDiariosActivos")
            .text(activos);

        $("#resumoDiariosManuais")
            .text(manuais);

        $("#resumoLancamentosDiarios")
            .text(lancamentos);

    }


    function limparResumoDiarios() {

        $("#resumoTotalDiarios")
            .text("0");

        $("#resumoDiariosActivos")
            .text("0");

        $("#resumoDiariosManuais")
            .text("0");

        $("#resumoLancamentosDiarios")
            .text("0");

    }


    // ==================================================
    // BADGES
    // ==================================================

    function criarBadgeStatusDiario(
        status
    ) {

        const normalizado =
            normalizarTextoDiario(
                status
            );

        let classe =
            "diario-status-desconhecido";

        if (normalizado === "activo") {
            classe =
                "diario-status-activo";
        }

        if (normalizado === "inactivo") {
            classe =
                "diario-status-inativo";
        }

        if (normalizado === "bloqueado") {
            classe =
                "diario-status-bloqueado";
        }

        return `
            <span
                class="badge-status-diario ${classe}">

                ${escaparHtmlDiario(
            status || "-"
        )
            }

            </span>
        `;

    }


    function criarBadgeSimNao(
        valor
    ) {

        if (valor) {

            return `
                <span class="badge badge-success">
                    Sim
                </span>
            `;

        }

        return `
            <span class="badge badge-secondary">
                Não
            </span>
        `;

    }


    // ==================================================
    // FORMATAÇÃO DO TIPO
    // ==================================================

    function formatarTipoDiario(
        tipo
    ) {

        const tipos = {

            GERAL:
                "Geral",

            VENDAS:
                "Vendas",

            COMPRAS:
                "Compras",

            CAIXA:
                "Caixa",

            BANCOS:
                "Bancos",

            STOCK:
                "Stock",

            SALARIOS:
                "Salários",

            IMPOSTOS:
                "Impostos",

            ABERTURA:
                "Abertura",

            ENCERRAMENTO:
                "Encerramento",

            OUTROS:
                "Outros"

        };

        return tipos[
            String(
                tipo || ""
            ).toUpperCase()
        ] || tipo || "-";

    }


    // ==================================================
    // LIMPAR FORMULÁRIO
    // ==================================================

    function limparFormularioDiario() {

        const formulario =
            document.getElementById(
                "formDiarioContabilistico"
            );

        if (formulario) {
            formulario.reset();
        }

        $("#diarioId")
            .val("");

        const statusActivo =
            encontrarStatusPorNome(
                "Activo"
            );

        $("#diarioStatusId")
            .val(
                statusActivo
                    ? String(statusActivo.id)
                    : ""
            );

        $("#diarioNumeracaoAutomatica")
            .prop(
                "checked",
                true
            );

        $("#tituloModalDiario")
            .text(
                "Novo Diário Contabilístico"
            );

        atualizarInformacaoTipoDiario();

    }


    // ==================================================
    // AUXILIARES
    // ==================================================

    function recarregarTabelaDiarios() {

        if (tabelaDiarios) {

            tabelaDiarios.ajax.reload(
                null,
                false
            );

        }

    }


    function preencherCheckboxFormDataDiario(
        formData,
        nome,
        seletor
    ) {

        formData.set(
            nome,
            $(seletor).is(":checked")
                ? "1"
                : "0"
        );

    }


    function normalizarTextoDiario(
        valor
    ) {

        return String(
            valor || ""
        )
            .normalize("NFD")
            .replace(
                /[\u0300-\u036f]/g,
                ""
            )
            .toLowerCase()
            .trim();

    }


    function escaparHtmlDiario(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }


    function escaparRegexDiario(
        valor
    ) {

        return String(valor)
            .replace(
                /[.*+?^${}()|[\]\\]/g,
                "\\$&"
            );

    }


    // ==================================================
    // MENSAGENS
    // ==================================================

    function mostrarErroDiario(
        mensagem
    ) {

        return Swal.fire({

            icon:
                "error",

            title:
                "Não foi possível concluir",

            text:
                mensagem
                || "Ocorreu um erro inesperado.",

            confirmButtonText:
                "Fechar"

        });

    }


    function mostrarAvisoDiario(
        mensagem
    ) {

        return Swal.fire({

            icon:
                "warning",

            title:
                "Atenção",

            text:
                mensagem,

            confirmButtonText:
                "Entendi"

        });

    }


    function toastDiario(
        mensagem
    ) {

        return Swal.fire({

            toast:
                true,

            position:
                "top-end",

            icon:
                "success",

            title:
                mensagem,

            showConfirmButton:
                false,

            timer:
                2500,

            timerProgressBar:
                true

        });

    }


    // ==================================================
    // BOTÕES
    // ==================================================

    function bloquearBotaoDiario(
        botao,
        texto
    ) {

        botao
            .prop(
                "disabled",
                true
            )
            .html(`
                <span
                    class="spinner-border spinner-border-sm mr-1">
                </span>

                ${texto}
            `);

    }


    function restaurarBotaoDiario(
        botao,
        html
    ) {

        botao
            .prop(
                "disabled",
                false
            )
            .html(html);

    }

})(jQuery);