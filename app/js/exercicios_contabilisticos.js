"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const EXERCICIO_URL =
        "/erpplus/app/ajax/exercicio_contabilistico_ajax.php";

    let tabelaExercicios = null;

    let exerciciosContabilisticos = [];

    let statusExercicios = [];

    let statusPeriodos = [];

    let exercicioPeriodosAtual = null;

    let reabrirModalPeriodosAposStatus =
        false;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(
        async function () {

            configurarEventosExercicios();

            limparResumoExercicios();

            await Promise.all([
                carregarStatusExercicios(),
                carregarStatusPeriodos()
            ]);

            iniciarTabelaExercicios();

        }
    );


    // ==================================================
    // CONFIGURAR EVENTOS
    // ==================================================

    function configurarEventosExercicios() {

        // ----------------------------------------------
        // NOVO EXERCÍCIO
        // ----------------------------------------------

        $("#btnNovoExercicio").on(
            "click",
            abrirNovoExercicio
        );


        // ----------------------------------------------
        // FORMULÁRIO DO EXERCÍCIO
        // ----------------------------------------------

        $("#formExercicioContabilistico").on(
            "submit",
            guardarExercicio
        );


        // ----------------------------------------------
        // FILTROS
        // ----------------------------------------------

        $("#pesquisaExercicio").on(
            "input",
            aplicarFiltrosExercicios
        );

        $("#filtroStatusExercicio").on(
            "change",
            aplicarFiltrosExercicios
        );

        $("#btnLimparFiltrosExercicio").on(
            "click",
            limparFiltrosExercicios
        );


        // ----------------------------------------------
        // EDITAR EXERCÍCIO
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-editar-exercicio",
            function () {

                editarExercicio(
                    Number(
                        $(this).data("id")
                        || 0
                    )
                );

            }
        );


        // ----------------------------------------------
        // PERÍODOS
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-periodos-exercicio",
            function () {

                abrirPeriodosExercicio(
                    Number(
                        $(this).data("id")
                        || 0
                    )
                );

            }
        );


        // ----------------------------------------------
        // STATUS DO EXERCÍCIO
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-status-exercicio",
            function () {

                abrirStatusExercicio(
                    Number(
                        $(this).data("id")
                        || 0
                    ),
                    String(
                        $(this).data("status")
                        || ""
                    )
                );

            }
        );


        // ----------------------------------------------
        // ELIMINAR EXERCÍCIO
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-eliminar-exercicio",
            function () {

                abrirEliminarExercicio(
                    Number(
                        $(this).data("id")
                        || 0
                    )
                );

            }
        );


        // ----------------------------------------------
        // GERAR PERÍODOS
        // ----------------------------------------------

        $("#btnGerarPeriodos").on(
            "click",
            gerarPeriodos
        );


        // ----------------------------------------------
        // ATUALIZAR PERÍODOS
        // ----------------------------------------------

        $("#btnAtualizarPeriodos").on(
            "click",
            function () {

                if (
                    exercicioPeriodosAtual
                    && exercicioPeriodosAtual.id
                ) {
                    carregarPeriodos(
                        exercicioPeriodosAtual.id
                    );
                }

            }
        );


        // ----------------------------------------------
        // STATUS DO PERÍODO
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-status-periodo",
            function () {

                abrirStatusPeriodo(
                    Number(
                        $(this).data("id")
                        || 0
                    ),
                    String(
                        $(this).data("status")
                        || ""
                    )
                );

            }
        );


        // ----------------------------------------------
        // GUARDAR STATUS DO EXERCÍCIO
        // ----------------------------------------------

        $("#formStatusExercicio").on(
            "submit",
            guardarStatusExercicio
        );


        // ----------------------------------------------
        // GUARDAR STATUS DO PERÍODO
        // ----------------------------------------------

        $("#formStatusPeriodo").on(
            "submit",
            guardarStatusPeriodo
        );


        // ----------------------------------------------
        // ELIMINAR
        // ----------------------------------------------

        $("#formEliminarExercicio").on(
            "submit",
            eliminarExercicio
        );


        // ----------------------------------------------
        // ALERTAS DE STATUS
        // ----------------------------------------------

        $("#novoStatusExercicio").on(
            "change",
            function () {

                const status =
                    normalizarTextoExercicio(
                        $(this).val()
                    );

                $("#alertaEncerrarExercicio")
                    .toggle(
                        status === "encerrado"
                    );

            }
        );


        $("#novoStatusPeriodo").on(
            "change",
            function () {

                const status =
                    normalizarTextoExercicio(
                        $(this).val()
                    );

                $("#alertaFecharPeriodo")
                    .toggle(
                        status === "fechado"
                    );

            }
        );


        // ----------------------------------------------
        // AUTOPREENCHER DATAS
        // ----------------------------------------------

        $("#exercicioCodigo").on(
            "input",
            preencherDatasPeloCodigo
        );


        // ----------------------------------------------
        // LIMPAR MODAIS
        // ----------------------------------------------

        $("#modalExercicioContabilistico").on(
            "hidden.bs.modal",
            limparFormularioExercicio
        );

        $("#modalStatusExercicio").on(
            "hidden.bs.modal",
            function () {

                $("#formStatusExercicio")[0]
                    ?.reset();

                $("#statusExercicioId")
                    .val("");

                $("#alertaEncerrarExercicio")
                    .hide();

            }
        );


        $("#modalStatusPeriodo").on(
            "hidden.bs.modal",
            function () {

                $("#formStatusPeriodo")[0]
                    ?.reset();


                $("#statusPeriodoId")
                    .val("");


                $("#alertaFecharPeriodo")
                    .hide();


                /*
                |--------------------------------------------------------------------------
                | Voltar aos períodos
                |--------------------------------------------------------------------------
                */

                if (
                    reabrirModalPeriodosAposStatus
                    && exercicioPeriodosAtual
                    && exercicioPeriodosAtual.id
                ) {

                    reabrirModalPeriodosAposStatus =
                        false;


                    $("#modalPeriodosContabilisticos")
                        .modal("show");
                }

            }
        );

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoExercicio(
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
                "Resposta inválida:",
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

    async function carregarStatusExercicios() {

        try {

            const resposta =
                await requisicaoExercicio(
                    EXERCICIO_URL
                    + "?acao=status_exercicios"
                );

            statusExercicios =
                resposta.data || [];

            preencherStatusExercicios();

        } catch (erro) {

            mostrarErroExercicio(
                erro.message
            );

        }

    }


    function preencherStatusExercicios() {

        const selects = [
            $("#filtroStatusExercicio"),
            $("#novoStatusExercicio")
        ];

        selects.forEach(
            function (select) {

                const filtro =
                    select.attr("id")
                    === "filtroStatusExercicio";

                select.html(`
                    <option value="">
                        ${filtro
                        ? "Todos os status"
                        : "Seleccione"
                    }
                    </option>
                `);

                statusExercicios.forEach(
                    function (status) {

                        select.append(`
                            <option value="${escaparHtmlExercicio(
                            status.nome
                        )
                            }">
                                ${escaparHtmlExercicio(
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


    async function carregarStatusPeriodos() {

        try {

            const resposta =
                await requisicaoExercicio(
                    EXERCICIO_URL
                    + "?acao=status_periodos"
                );

            statusPeriodos =
                resposta.data || [];

            preencherStatusPeriodos();

        } catch (erro) {

            mostrarErroExercicio(
                erro.message
            );

        }

    }


    function preencherStatusPeriodos() {

        const select =
            $("#novoStatusPeriodo");

        select.html(`
            <option value="">
                Seleccione
            </option>
        `);

        statusPeriodos.forEach(
            function (status) {

                select.append(`
                    <option value="${escaparHtmlExercicio(
                    status.nome
                )
                    }">
                        ${escaparHtmlExercicio(
                        status.nome
                    )
                    }
                    </option>
                `);

            }
        );

    }


    // ==================================================
    // TABELA DOS EXERCÍCIOS
    // ==================================================

    function iniciarTabelaExercicios() {

        tabelaExercicios =
            $("#exerciciosContabilisticosTable")
                .DataTable({

                    ajax: {

                        url:
                            EXERCICIO_URL,

                        data:
                        {
                            acao:
                                "listar"
                        },

                        dataSrc:
                            function (resposta) {

                                exerciciosContabilisticos =
                                    resposta.data
                                    || [];

                                atualizarResumoExercicios();

                                return exerciciosContabilisticos;

                            },

                        error:
                            function (xhr) {

                                let mensagem =
                                    "Não foi possível carregar os exercícios.";

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

                                mostrarErroExercicio(
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
                                "descricao"
                        },

                        {
                            data:
                                "data_inicio",

                            render:
                                function (valor) {

                                    return formatarDataExercicio(
                                        valor
                                    );

                                }
                        },

                        {
                            data:
                                "data_fim",

                            render:
                                function (valor) {

                                    return formatarDataExercicio(
                                        valor
                                    );

                                }
                        },

                        {
                            data:
                                "quantidade_periodos",

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

                                    return criarBadgeStatus(
                                        valor
                                    );

                                }
                        },

                        {
                            data:
                                "encerrado_em",

                            render:
                                function (valor) {

                                    return valor
                                        ? formatarDataHoraExercicio(
                                            valor
                                        )
                                        : "-";

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
                                "text-right acoes-exercicio",

                            render:
                                function (
                                    dados,
                                    tipo,
                                    exercicio
                                ) {

                                    return criarBotoesExercicio(
                                        exercicio
                                    );

                                }
                        }

                    ],

                    order: [
                        [
                            3,
                            "desc"
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


    function criarBotoesExercicio(
        exercicio
    ) {

        const status =
            normalizarTextoExercicio(
                exercicio.status_nome
            );

        const encerrado =
            status === "encerrado";

        const editar =
            encerrado
                ? ""
                : `
                    <button
                        type="button"
                        class="btn btn-sm btn-info btn-editar-exercicio"
                        data-id="${exercicio.id}"
                        title="Editar">

                        <i class="fa fa-edit"></i>

                    </button>
                `;

        const eliminar =
            encerrado
                ? ""
                : `
                    <button
                        type="button"
                        class="btn btn-sm btn-danger btn-eliminar-exercicio"
                        data-id="${exercicio.id}"
                        title="Eliminar">

                        <i class="fa fa-trash"></i>

                    </button>
                `;

        return `
            ${editar}

            <button
                type="button"
                class="btn btn-sm btn-primary btn-periodos-exercicio"
                data-id="${exercicio.id}"
                title="Períodos">

                <i class="fa fa-calendar"></i>

            </button>

            <button
                type="button"
                class="btn btn-sm btn-warning btn-status-exercicio"
                data-id="${exercicio.id}"
                data-status="${escaparHtmlExercicio(
            exercicio.status_nome
        )
            }"
                title="Alterar status">

                <i class="fa fa-exchange"></i>

            </button>

            ${eliminar}
        `;

    }


    // ==================================================
    // FILTROS
    // ==================================================

    function aplicarFiltrosExercicios() {

        if (!tabelaExercicios) {
            return;
        }

        const pesquisa =
            String(
                $("#pesquisaExercicio").val()
                || ""
            );

        const status =
            String(
                $("#filtroStatusExercicio").val()
                || ""
            );

        tabelaExercicios
            .search(pesquisa);

        tabelaExercicios
            .column(6)
            .search(
                status
                    ? "^"
                    + escaparRegexExercicio(
                        status
                    )
                    + "$"
                    : "",
                true,
                false
            );

        tabelaExercicios.draw();

    }


    function limparFiltrosExercicios() {

        $("#pesquisaExercicio")
            .val("");

        $("#filtroStatusExercicio")
            .val("");

        if (tabelaExercicios) {

            tabelaExercicios
                .search("");

            tabelaExercicios
                .column(6)
                .search("");

            tabelaExercicios
                .draw();

        }

    }


    // ==================================================
    // NOVO EXERCÍCIO
    // ==================================================

    function abrirNovoExercicio() {

        limparFormularioExercicio();

        const anoAtual =
            new Date()
                .getFullYear();

        $("#exercicioCodigo")
            .val(
                String(anoAtual)
            );

        $("#exercicioDescricao")
            .val(
                `Exercício contabilístico de ${anoAtual}`
            );

        $("#exercicioDataInicio")
            .val(
                `${anoAtual}-01-01`
            );

        $("#exercicioDataFim")
            .val(
                `${anoAtual}-12-31`
            );

        $("#tituloModalExercicio")
            .text(
                "Novo Exercício Contabilístico"
            );

        $("#modalExercicioContabilistico")
            .modal("show");

    }


    function preencherDatasPeloCodigo() {

        if (
            $("#exercicioId").val()
        ) {
            return;
        }

        const codigo =
            String(
                $("#exercicioCodigo").val()
                || ""
            ).trim();

        if (
            !/^\d{4}$/.test(codigo)
        ) {
            return;
        }

        $("#exercicioDescricao")
            .val(
                `Exercício contabilístico de ${codigo}`
            );

        $("#exercicioDataInicio")
            .val(
                `${codigo}-01-01`
            );

        $("#exercicioDataFim")
            .val(
                `${codigo}-12-31`
            );

    }


    // ==================================================
    // EDITAR EXERCÍCIO
    // ==================================================

    async function editarExercicio(
        exercicioId
    ) {

        if (exercicioId <= 0) {
            return;
        }

        try {

            const resposta =
                await requisicaoExercicio(
                    EXERCICIO_URL
                    + "?acao=buscar_exercicio&id="
                    + encodeURIComponent(
                        exercicioId
                    )
                );

            const exercicio =
                resposta.data;

            limparFormularioExercicio();

            $("#exercicioId")
                .val(
                    exercicio.id
                );

            $("#exercicioCodigo")
                .val(
                    exercicio.codigo
                    || ""
                );

            $("#exercicioDescricao")
                .val(
                    exercicio.descricao
                    || ""
                );

            $("#exercicioDataInicio")
                .val(
                    exercicio.data_inicio
                    || ""
                );

            $("#exercicioDataFim")
                .val(
                    exercicio.data_fim
                    || ""
                );

            $("#tituloModalExercicio")
                .text(
                    "Editar Exercício Contabilístico"
                );

            $("#modalExercicioContabilistico")
                .modal("show");

        } catch (erro) {

            mostrarErroExercicio(
                erro.message
            );

        }

    }


    // ==================================================
    // GUARDAR EXERCÍCIO
    // ==================================================

    async function guardarExercicio(
        evento
    ) {

        evento.preventDefault();

        const formulario =
            evento.currentTarget;

        const dataInicio =
            $("#exercicioDataInicio").val();

        const dataFim =
            $("#exercicioDataFim").val();

        if (
            dataInicio
            && dataFim
            && dataFim < dataInicio
        ) {

            mostrarAvisoExercicio(
                "A data final não pode ser anterior à data inicial."
            );

            return;
        }

        const botao =
            $("#btnGuardarExercicio");

        bloquearBotaoExercicio(
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
                "guardar_exercicio"
            );

            const resposta =
                await requisicaoExercicio(
                    EXERCICIO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalExercicioContabilistico")
                .modal("hide");

            recarregarTabelaExercicios();

            toastExercicio(
                resposta.mensagem
                || "Exercício guardado com sucesso."
            );

        } catch (erro) {

            mostrarErroExercicio(
                erro.message
            );

        } finally {

            restaurarBotaoExercicio(
                botao,
                `
                    <i class="fa fa-save"></i>
                    Guardar Exercício
                `
            );

        }

    }


    // ==================================================
    // PERÍODOS
    // ==================================================

    async function abrirPeriodosExercicio(
        exercicioId
    ) {

        const exercicio =
            exerciciosContabilisticos.find(
                function (item) {

                    return Number(item.id)
                        === Number(exercicioId);

                }
            );

        if (!exercicio) {

            mostrarErroExercicio(
                "Exercício contabilístico não encontrado."
            );

            return;
        }

        exercicioPeriodosAtual =
            exercicio;

        $("#periodosExercicioId")
            .val(
                exercicio.id
            );

        $("#descricaoExercicioPeriodos")
            .text(
                exercicio.codigo
                + " — "
                + exercicio.descricao
            );

        $("#modalPeriodosContabilisticos")
            .modal("show");

        await carregarPeriodos(
            exercicio.id
        );

    }


    async function carregarPeriodos(
        exercicioId
    ) {

        mostrarCarregamentoPeriodos(
            true
        );

        try {

            const resposta =
                await requisicaoExercicio(
                    EXERCICIO_URL
                    + "?acao=listar_periodos&exercicio_id="
                    + encodeURIComponent(
                        exercicioId
                    )
                );

            renderizarPeriodos(
                resposta.data || []
            );

        } catch (erro) {

            mostrarErroExercicio(
                erro.message
            );

        } finally {

            mostrarCarregamentoPeriodos(
                false
            );

        }

    }


    function renderizarPeriodos(
        periodos
    ) {

        const corpo =
            $("#listaPeriodosContabilisticos");

        corpo.empty();

        if (
            !Array.isArray(periodos)
            || periodos.length === 0
        ) {

            $("#mensagemSemPeriodos")
                .show();

            $("#periodosContabilisticosTable")
                .hide();

            return;
        }

        $("#mensagemSemPeriodos")
            .hide();

        $("#periodosContabilisticosTable")
            .show();

        periodos.forEach(
            function (periodo) {

                corpo.append(`
                    <tr>

                        <td>
                            ${Number(
                    periodo.numero
                    || 0
                )}
                        </td>

                        <td>
                            ${escaparHtmlExercicio(
                    periodo.nome
                )
                    }
                        </td>

                        <td>
                            ${formatarDataExercicio(
                        periodo.data_inicio
                    )
                    }
                        </td>

                        <td>
                            ${formatarDataExercicio(
                        periodo.data_fim
                    )
                    }
                        </td>

                        <td>
                            ${criarBadgeStatus(
                        periodo.status_nome
                    )
                    }
                        </td>

                        <td>
                            ${periodo.fechado_em
                        ? formatarDataHoraExercicio(
                            periodo.fechado_em
                        )
                        : "-"
                    }
                        </td>

                        <td class="text-right">

                            <button
                                type="button"
                                class="btn btn-sm btn-warning btn-status-periodo"
                                data-id="${periodo.id}"
                                data-status="${escaparHtmlExercicio(
                        periodo.status_nome
                    )
                    }"
                                title="Alterar status">

                                <i class="fa fa-exchange"></i>

                            </button>

                        </td>

                    </tr>
                `);

            }
        );

    }


    // ==================================================
    // GERAR PERÍODOS
    // ==================================================

    async function gerarPeriodos() {

        const exercicioId =
            Number(
                $("#periodosExercicioId").val()
                || 0
            );

        if (exercicioId <= 0) {

            mostrarAvisoExercicio(
                "Exercício contabilístico inválido."
            );

            return;
        }

        const confirmacao =
            await Swal.fire({

                icon:
                    "question",

                title:
                    "Gerar períodos",

                text:
                    "Deseja gerar automaticamente os períodos deste exercício?",

                showCancelButton:
                    true,

                confirmButtonText:
                    "Gerar",

                cancelButtonText:
                    "Cancelar"

            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        const botao =
            $("#btnGerarPeriodos");

        bloquearBotaoExercicio(
            botao,
            "A gerar..."
        );

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                "gerar_periodos"
            );

            dados.append(
                "exercicio_id",
                String(exercicioId)
            );

            const resposta =
                await requisicaoExercicio(
                    EXERCICIO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            await carregarPeriodos(
                exercicioId
            );

            recarregarTabelaExercicios();

            toastExercicio(
                resposta.mensagem
            );

        } catch (erro) {

            mostrarErroExercicio(
                erro.message
            );

        } finally {

            restaurarBotaoExercicio(
                botao,
                `
                    <i class="fa fa-calendar-plus-o"></i>
                    Gerar Períodos
                `
            );

        }

    }


    // ==================================================
    // STATUS DO EXERCÍCIO
    // ==================================================

    function abrirStatusExercicio(
        exercicioId,
        statusAtual
    ) {

        $("#statusExercicioId")
            .val(
                exercicioId
            );

        $("#novoStatusExercicio")
            .val(
                statusAtual
            )
            .trigger("change");

        $("#modalStatusExercicio")
            .modal("show");

    }


    async function guardarStatusExercicio(
        evento
    ) {

        evento.preventDefault();

        const exercicioId =
            Number(
                $("#statusExercicioId").val()
                || 0
            );

        const status =
            String(
                $("#novoStatusExercicio").val()
                || ""
            ).trim();

        if (
            exercicioId <= 0
            || status === ""
        ) {

            mostrarAvisoExercicio(
                "Seleccione um status válido."
            );

            return;
        }

        if (
            normalizarTextoExercicio(status)
            === "encerrado"
        ) {

            const confirmacao =
                await Swal.fire({

                    icon:
                        "warning",

                    title:
                        "Encerrar exercício",

                    text:
                        "Depois de encerrado, o exercício não poderá ser reaberto diretamente. Deseja continuar?",

                    showCancelButton:
                        true,

                    confirmButtonText:
                        "Encerrar",

                    cancelButtonText:
                        "Cancelar"

                });

            if (!confirmacao.isConfirmed) {
                return;
            }

        }

        const botao =
            $("#btnGuardarStatusExercicio");

        bloquearBotaoExercicio(
            botao,
            "A atualizar..."
        );

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                "alterar_status_exercicio"
            );

            dados.append(
                "id",
                String(exercicioId)
            );

            dados.append(
                "status",
                status
            );

            const resposta =
                await requisicaoExercicio(
                    EXERCICIO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalStatusExercicio")
                .modal("hide");

            recarregarTabelaExercicios();

            toastExercicio(
                resposta.mensagem
            );

        } catch (erro) {

            mostrarErroExercicio(
                erro.message
            );

        } finally {

            restaurarBotaoExercicio(
                botao,
                `
                    <i class="fa fa-save"></i>
                    Atualizar Status
                `
            );

        }

    }


    // ==================================================
    // STATUS DO PERÍODO
    // ==================================================

    function abrirStatusPeriodo(
        periodoId,
        statusAtual
    ) {

        if (
            !periodoId
            || periodoId <= 0
        ) {
            mostrarAvisoExercicio(
                "Período contabilístico inválido."
            );

            return;
        }


        $("#statusPeriodoId")
            .val(
                periodoId
            );


        $("#novoStatusPeriodo")
            .val(
                statusAtual
            )
            .trigger("change");


        /*
        |--------------------------------------------------------------------------
        | O modal dos períodos já está aberto.
        |
        | Não abrimos outro modal por cima.
        | Primeiro fechamos o modal principal.
        |--------------------------------------------------------------------------
        */

        if (
            $("#modalPeriodosContabilisticos")
                .hasClass("show")
        ) {

            reabrirModalPeriodosAposStatus =
                true;


            $("#modalPeriodosContabilisticos")
                .one(
                    "hidden.bs.modal",
                    function () {

                        $("#modalStatusPeriodo")
                            .modal("show");

                    }
                );


            $("#modalPeriodosContabilisticos")
                .modal("hide");


            return;
        }


        reabrirModalPeriodosAposStatus =
            false;


        $("#modalStatusPeriodo")
            .modal("show");
    }


    async function guardarStatusPeriodo(
        evento
    ) {

        evento.preventDefault();

        const periodoId =
            Number(
                $("#statusPeriodoId").val()
                || 0
            );

        const status =
            String(
                $("#novoStatusPeriodo").val()
                || ""
            ).trim();

        if (
            periodoId <= 0
            || status === ""
        ) {

            mostrarAvisoExercicio(
                "Seleccione um status válido."
            );

            return;
        }

        if (
            normalizarTextoExercicio(status)
            === "fechado"
        ) {

            const confirmacao =
                await Swal.fire({

                    icon:
                        "warning",

                    title:
                        "Fechar período",

                    text:
                        "O período será validado antes do fechamento. Deseja continuar?",

                    showCancelButton:
                        true,

                    confirmButtonText:
                        "Fechar",

                    cancelButtonText:
                        "Cancelar"

                });

            if (!confirmacao.isConfirmed) {
                return;
            }

        }

        const botao =
            $("#btnGuardarStatusPeriodo");

        bloquearBotaoExercicio(
            botao,
            "A atualizar..."
        );

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                "alterar_status_periodo"
            );

            dados.append(
                "id",
                String(periodoId)
            );

            dados.append(
                "status",
                status
            );

            const resposta =
                await requisicaoExercicio(
                    EXERCICIO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalStatusPeriodo")
                .modal("hide");

            if (
                exercicioPeriodosAtual
                && exercicioPeriodosAtual.id
            ) {

                await carregarPeriodos(
                    exercicioPeriodosAtual.id
                );

            }

            recarregarTabelaExercicios();

            toastExercicio(
                resposta.mensagem
            );

        } catch (erro) {

            mostrarErroExercicio(
                erro.message
            );

        } finally {

            restaurarBotaoExercicio(
                botao,
                `
                    <i class="fa fa-save"></i>
                    Atualizar Status
                `
            );

        }

    }


    // ==================================================
    // ELIMINAR EXERCÍCIO
    // ==================================================

    function abrirEliminarExercicio(
        exercicioId
    ) {

        $("#eliminarExercicioId")
            .val(
                exercicioId
            );

        $("#modalEliminarExercicio")
            .modal("show");

    }


    async function eliminarExercicio(
        evento
    ) {

        evento.preventDefault();

        const exercicioId =
            Number(
                $("#eliminarExercicioId").val()
                || 0
            );

        if (exercicioId <= 0) {

            mostrarAvisoExercicio(
                "Exercício contabilístico inválido."
            );

            return;
        }

        const botao =
            $("#btnConfirmarEliminarExercicio");

        bloquearBotaoExercicio(
            botao,
            "A eliminar..."
        );

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                "eliminar_exercicio"
            );

            dados.append(
                "id",
                String(exercicioId)
            );

            const resposta =
                await requisicaoExercicio(
                    EXERCICIO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalEliminarExercicio")
                .modal("hide");

            recarregarTabelaExercicios();

            toastExercicio(
                resposta.mensagem
            );

        } catch (erro) {

            mostrarErroExercicio(
                erro.message
            );

        } finally {

            restaurarBotaoExercicio(
                botao,
                `
                    <i class="fa fa-trash"></i>
                    Eliminar
                `
            );

        }

    }


    // ==================================================
    // RESUMOS
    // ==================================================

    function atualizarResumoExercicios() {

        const total =
            exerciciosContabilisticos.length;

        const abertos =
            exerciciosContabilisticos.filter(
                function (exercicio) {

                    return normalizarTextoExercicio(
                        exercicio.status_nome
                    ) === "aberto";

                }
            ).length;

        const bloqueados =
            exerciciosContabilisticos.filter(
                function (exercicio) {

                    return normalizarTextoExercicio(
                        exercicio.status_nome
                    ) === "bloqueado";

                }
            ).length;

        const encerrados =
            exerciciosContabilisticos.filter(
                function (exercicio) {

                    return normalizarTextoExercicio(
                        exercicio.status_nome
                    ) === "encerrado";

                }
            ).length;

        $("#resumoTotalExercicios")
            .text(total);

        $("#resumoExerciciosAbertos")
            .text(abertos);

        $("#resumoExerciciosBloqueados")
            .text(bloqueados);

        $("#resumoExerciciosEncerrados")
            .text(encerrados);

    }


    function limparResumoExercicios() {

        $("#resumoTotalExercicios")
            .text("0");

        $("#resumoExerciciosAbertos")
            .text("0");

        $("#resumoExerciciosBloqueados")
            .text("0");

        $("#resumoExerciciosEncerrados")
            .text("0");

    }


    // ==================================================
    // BADGES
    // ==================================================

    function criarBadgeStatus(
        status
    ) {

        const normalizado =
            normalizarTextoExercicio(
                status
            );

        let classe =
            "status-desconhecido";

        if (normalizado === "aberto") {
            classe =
                "status-aberto";
        }

        if (normalizado === "bloqueado") {
            classe =
                "status-bloqueado";
        }

        if (normalizado === "encerrado") {
            classe =
                "status-encerrado";
        }

        if (normalizado === "fechado") {
            classe =
                "status-fechado";
        }

        return `
            <span
                class="badge-status-contabilistico ${classe}">

                ${escaparHtmlExercicio(
            status || "-"
        )
            }

            </span>
        `;

    }


    // ==================================================
    // FORMULÁRIO
    // ==================================================

    function limparFormularioExercicio() {

        const formulario =
            document.getElementById(
                "formExercicioContabilistico"
            );

        if (formulario) {
            formulario.reset();
        }

        $("#exercicioId")
            .val("");

        $("#tituloModalExercicio")
            .text(
                "Novo Exercício Contabilístico"
            );

    }


    // ==================================================
    // AUXILIARES
    // ==================================================

    function recarregarTabelaExercicios() {

        if (tabelaExercicios) {

            tabelaExercicios.ajax.reload(
                null,
                false
            );

        }

    }


    function mostrarCarregamentoPeriodos(
        mostrar
    ) {

        $("#carregandoPeriodos")
            .toggle(
                Boolean(mostrar)
            );

    }


    function formatarDataExercicio(
        valor
    ) {

        if (!valor) {
            return "-";
        }

        const partes =
            String(valor)
                .substring(0, 10)
                .split("-");

        if (partes.length !== 3) {
            return valor;
        }

        return (
            partes[2]
            + "/"
            + partes[1]
            + "/"
            + partes[0]
        );

    }


    function formatarDataHoraExercicio(
        valor
    ) {

        if (!valor) {
            return "-";
        }

        const texto =
            String(valor);

        const partes =
            texto.split(" ");

        const data =
            formatarDataExercicio(
                partes[0]
            );

        return partes[1]
            ? data + " " + partes[1]
            : data;

    }


    function normalizarTextoExercicio(
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


    function escaparHtmlExercicio(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }


    function escaparRegexExercicio(
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

    function mostrarErroExercicio(
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


    function mostrarAvisoExercicio(
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


    function toastExercicio(
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

    function bloquearBotaoExercicio(
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


    function restaurarBotaoExercicio(
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