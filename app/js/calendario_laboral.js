"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const CALENDARIO_LABORAL_URL =
        "/erpplus/app/ajax/calendario_laboral_ajax.php";

    let eventosCalendarioCache = [];

    let filiaisCalendarioCache = [];

    let tiposEventosCalendarioCache = [];

    let tabelaCalendarioLaboral = null;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaCalendarioLaboralExiste()) {
            return;
        }

        configurarEventosCalendarioLaboral();

        carregarDadosIniciaisCalendarioLaboral()
            .catch(function (erro) {

                console.error(
                    "Erro ao iniciar o calendário laboral:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaCalendarioLaboralExiste() {

        return (
            $("#calendarioLaboralTable").length > 0
            && $("#formCalendarioLaboral").length > 0
            && $("#modalCalendarioLaboral").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosCalendarioLaboral() {

        $("#btnNovoEventoLaboral").on(
            "click",
            function () {

                abrirModalNovoEventoLaboral();

            }
        );

        $("#formCalendarioLaboral").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarEventoLaboral()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao guardar evento laboral:",
                            erro
                        );

                    });

            }
        );

        $("#calendarioCodigo").on(
            "input",
            function () {

                const valor =
                    String(
                        $(this).val() || ""
                    )
                        .toUpperCase()
                        .replace(
                            /[^A-Z0-9._-]/g,
                            ""
                        );

                $(this).val(valor);

            }
        );

        $("#calendarioDiaInteiro").on(
            "change",
            function () {

                atualizarCamposHorarioEvento();

            }
        );

        $("#calendarioTipoEvento").on(
            "change",
            function () {

                atualizarCamposPorTipoEvento();

            }
        );

        $("#calendarioPermiteTrabalho").on(
            "change",
            function () {

                atualizarCampoCompensacao();

            }
        );

        $("#calendarioDataInicio").on(
            "change",
            function () {

                ajustarDataFimEvento();

            }
        );

        $("#filtroCalendarioDataInicio, #filtroCalendarioDataFim")
            .on(
                "change",
                function () {

                    validarPeriodoFiltroCalendario();

                }
            );

        $("#btnFiltrarCalendarioLaboral").on(
            "click",
            function () {

                carregarEventosFiltrados()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao filtrar calendário:",
                            erro
                        );

                    });

            }
        );

        $("#btnLimparFiltrosCalendarioLaboral").on(
            "click",
            function () {

                limparFiltrosCalendarioLaboral();

                carregarEventosFiltrados()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao limpar filtros:",
                            erro
                        );

                    });

            }
        );

        $("#calendarioLaboralTable").on(
            "click",
            ".btn-ver-evento-laboral",
            function () {

                const eventoId =
                    Number(
                        $(this).data("id") || 0
                    );

                mostrarDetalhesEventoLaboral(
                    eventoId
                );

            }
        );

        $("#calendarioLaboralTable").on(
            "click",
            ".btn-editar-evento-laboral",
            function () {

                const eventoId =
                    Number(
                        $(this).data("id") || 0
                    );

                abrirModalEditarEventoLaboral(
                    eventoId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao carregar evento laboral:",
                            erro
                        );

                    });

            }
        );

        $("#calendarioLaboralTable").on(
            "click",
            ".btn-eliminar-evento-laboral",
            function () {

                const eventoId =
                    Number(
                        $(this).data("id") || 0
                    );

                confirmarEliminarEventoLaboral(
                    eventoId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao eliminar evento laboral:",
                            erro
                        );

                    });

            }
        );

        $("#modalCalendarioLaboral").on(
            "hidden.bs.modal",
            function () {

                limparFormularioCalendarioLaboral();

            }
        );

    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisCalendarioLaboral() {

        mostrarCarregamentoCalendarioLaboral(true);

        try {

            const resposta =
                await requisicaoCalendarioLaboral(
                    CALENDARIO_LABORAL_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            eventosCalendarioCache =
                Array.isArray(
                    dados.eventos
                )
                    ? dados.eventos
                    : [];

            filiaisCalendarioCache =
                Array.isArray(
                    dados.filiais
                )
                    ? dados.filiais
                    : [];

            tiposEventosCalendarioCache =
                Array.isArray(
                    dados.tipos_eventos
                )
                    ? dados.tipos_eventos
                    : [];

            preencherFiliaisCalendario(
                filiaisCalendarioCache
            );

            preencherStatusCalendario(
                dados.status || []
            );

            preencherTiposEventosCalendario(
                tiposEventosCalendarioCache
            );

            preencherFiltrosCalendario(
                filiaisCalendarioCache,
                tiposEventosCalendarioCache,
                dados.status || []
            );

            definirPeriodoPadraoFiltroCalendario();

            renderizarTabelaCalendarioLaboral(
                eventosCalendarioCache
            );

            atualizarResumoCalendarioLaboral(
                eventosCalendarioCache
            );

        } catch (erro) {

            mostrarErroCalendarioLaboral(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoCalendarioLaboral(false);

        }

    }


    // ==================================================
    // FILTRAR
    // ==================================================

    async function carregarEventosFiltrados() {

        validarPeriodoFiltroCalendario();

        mostrarCarregamentoCalendarioLaboral(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "listar"
                });

            const dataInicio =
                String(
                    $("#filtroCalendarioDataInicio").val()
                    || ""
                ).trim();

            const dataFim =
                String(
                    $("#filtroCalendarioDataFim").val()
                    || ""
                ).trim();

            const filialId =
                String(
                    $("#filtroCalendarioFilialId").val()
                    || ""
                ).trim();

            const tipoEvento =
                String(
                    $("#filtroCalendarioTipoEvento").val()
                    || ""
                ).trim();

            const statusId =
                String(
                    $("#filtroCalendarioStatusId").val()
                    || ""
                ).trim();

            if (dataInicio !== "") {

                parametros.append(
                    "data_inicio",
                    dataInicio
                );

            }

            if (dataFim !== "") {

                parametros.append(
                    "data_fim",
                    dataFim
                );

            }

            if (filialId !== "") {

                parametros.append(
                    "filial_id",
                    filialId
                );

            }

            if (tipoEvento !== "") {

                parametros.append(
                    "tipo_evento",
                    tipoEvento
                );

            }

            if (statusId !== "") {

                parametros.append(
                    "status_id",
                    statusId
                );

            }

            const resposta =
                await requisicaoCalendarioLaboral(
                    CALENDARIO_LABORAL_URL
                    + "?"
                    + parametros.toString()
                );

            eventosCalendarioCache =
                Array.isArray(
                    resposta.data
                )
                    ? resposta.data
                    : [];

            renderizarTabelaCalendarioLaboral(
                eventosCalendarioCache
            );

            atualizarResumoCalendarioLaboral(
                eventosCalendarioCache
            );

        } catch (erro) {

            mostrarErroCalendarioLaboral(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoCalendarioLaboral(false);

        }

    }


    // ==================================================
    // RECARREGAR
    // ==================================================

    async function recarregarCalendarioLaboral() {

        mostrarCarregamentoCalendarioLaboral(true);

        try {

            const resposta =
                await requisicaoCalendarioLaboral(
                    CALENDARIO_LABORAL_URL
                    + "?acao=listar"
                );

            eventosCalendarioCache =
                Array.isArray(
                    resposta.data
                )
                    ? resposta.data
                    : [];

            renderizarTabelaCalendarioLaboral(
                eventosCalendarioCache
            );

            atualizarResumoCalendarioLaboral(
                eventosCalendarioCache
            );

        } catch (erro) {

            console.error(
                "Erro ao recarregar calendário laboral:",
                erro
            );

            mostrarErroCalendarioLaboral(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoCalendarioLaboral(false);

        }

    }


    // ==================================================
    // FILIAIS
    // ==================================================

    function preencherFiliaisCalendario(
        filiais
    ) {

        const select =
            $("#calendarioFilialId");

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
                    <option value="${Number(
                    filial.id
                )}">
                        ${escaparHtmlCalendario(
                    texto
                )}
                    </option>
                `);

            }
        );

    }


    // ==================================================
    // STATUS
    // ==================================================

    function preencherStatusCalendario(
        status
    ) {

        const select =
            $("#calendarioStatusId");

        select.empty();

        select.append(`
            <option value="">
                Selecione o estado
            </option>
        `);

        status.forEach(
            function (item) {

                select.append(`
                    <option value="${Number(
                    item.id
                )}">
                        ${escaparHtmlCalendario(
                    item.nome || "-"
                )}
                    </option>
                `);

            }
        );

        const statusAtivo =
            status.find(
                function (item) {

                    return [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        normalizarTextoCalendario(
                            item.nome || ""
                        )
                    );

                }
            );

        if (statusAtivo) {

            select.data(
                "status-ativo-id",
                Number(
                    statusAtivo.id
                )
            );

        }

    }


    // ==================================================
    // TIPOS DE EVENTOS
    // ==================================================

    function preencherTiposEventosCalendario(
        tipos
    ) {

        const select =
            $("#calendarioTipoEvento");

        select.empty();

        select.append(`
            <option value="">
                Selecione o tipo
            </option>
        `);

        tipos.forEach(
            function (tipo) {

                select.append(`
                    <option value="${escaparHtmlCalendario(
                    tipo.codigo || ""
                )}">
                        ${escaparHtmlCalendario(
                    tipo.nome || "-"
                )}
                    </option>
                `);

            }
        );

    }


    // ==================================================
    // FILTROS
    // ==================================================

    function preencherFiltrosCalendario(
        filiais,
        tipos,
        status
    ) {

        const filtroFilial =
            $("#filtroCalendarioFilialId");

        filtroFilial.empty();

        filtroFilial.append(`
            <option value="">
                Todas as filiais
            </option>
        `);

        filiais.forEach(
            function (filial) {

                filtroFilial.append(`
                    <option value="${Number(
                    filial.id
                )}">
                        ${escaparHtmlCalendario(
                    filial.nome || "-"
                )}
                    </option>
                `);

            }
        );

        const filtroTipo =
            $("#filtroCalendarioTipoEvento");

        filtroTipo.empty();

        filtroTipo.append(`
            <option value="">
                Todos os tipos
            </option>
        `);

        tipos.forEach(
            function (tipo) {

                filtroTipo.append(`
                    <option value="${escaparHtmlCalendario(
                    tipo.codigo || ""
                )}">
                        ${escaparHtmlCalendario(
                    tipo.nome || "-"
                )}
                    </option>
                `);

            }
        );

        const filtroStatus =
            $("#filtroCalendarioStatusId");

        filtroStatus.empty();

        filtroStatus.append(`
            <option value="">
                Todos os estados
            </option>
        `);

        status.forEach(
            function (item) {

                filtroStatus.append(`
                    <option value="${Number(
                    item.id
                )}">
                        ${escaparHtmlCalendario(
                    item.nome || "-"
                )}
                    </option>
                `);

            }
        );

    }


    function definirPeriodoPadraoFiltroCalendario() {

        const hoje =
            new Date();

        const ano =
            hoje.getFullYear();

        $("#filtroCalendarioDataInicio")
            .val(
                `${ano}-01-01`
            );

        $("#filtroCalendarioDataFim")
            .val(
                `${ano}-12-31`
            );

    }


    function limparFiltrosCalendarioLaboral() {

        definirPeriodoPadraoFiltroCalendario();

        $("#filtroCalendarioFilialId")
            .val("");

        $("#filtroCalendarioTipoEvento")
            .val("");

        $("#filtroCalendarioStatusId")
            .val("");

    }


    function validarPeriodoFiltroCalendario() {

        const dataInicio =
            String(
                $("#filtroCalendarioDataInicio").val()
                || ""
            );

        const dataFim =
            String(
                $("#filtroCalendarioDataFim").val()
                || ""
            );

        if (
            dataInicio !== ""
            && dataFim !== ""
            && dataFim < dataInicio
        ) {
            throw new Error(
                "A data final do filtro não pode ser anterior à data inicial."
            );
        }

    }


    // ==================================================
    // TABELA
    // ==================================================

    function renderizarTabelaCalendarioLaboral(
        eventos
    ) {

        destruirDataTableCalendarioLaboral();

        const tbody =
            $("#calendarioLaboralTable tbody");

        tbody.empty();

        if (!eventos.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="10"
                        class="text-center text-muted py-4">

                        Nenhum evento laboral encontrado.

                    </td>

                </tr>
            `);

            return;

        }

        eventos.forEach(
            function (evento) {

                const statusNome =
                    String(
                        evento.status_nome || "-"
                    );

                const statusNormalizado =
                    normalizarTextoCalendario(
                        statusNome
                    );

                let badgeStatus =
                    "badge-secondary";

                if (
                    [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        statusNormalizado
                    )
                ) {
                    badgeStatus =
                        "badge-success";
                }

                if (
                    statusNormalizado
                    === "cancelado"
                ) {
                    badgeStatus =
                        "badge-danger";
                }

                const tipoNome =
                    obterNomeTipoEvento(
                        evento.tipo_evento
                    );

                const periodo =
                    formatarPeriodoEvento(
                        evento
                    );

                const filial =
                    evento.filial_nome
                    || "Todas";

                tbody.append(`
                    <tr>

                        <td>
                            <strong>
                                ${escaparHtmlCalendario(
                    evento.codigo || "-"
                )}
                            </strong>
                        </td>

                        <td>

                            <div class="d-flex align-items-center">

                                <span
                                    class="mr-2"
                                    style="
                                        width:12px;
                                        height:12px;
                                        border-radius:50%;
                                        display:inline-block;
                                        background:${corSeguraEvento(
                    evento.cor
                )};
                                    ">
                                </span>

                                <div>

                                    ${escaparHtmlCalendario(
                    evento.titulo || "-"
                )}

                                    ${Number(
                    evento.recorrente_anual || 0
                ) === 1
                        ? `
                                                <span class="badge badge-info ml-1">
                                                    Anual
                                                </span>
                                            `
                        : ""
                    }

                                    ${evento.descricao
                        ? `
                                                <div class="text-muted small">
                                                    ${escaparHtmlCalendario(
                            evento.descricao
                        )}
                                                </div>
                                            `
                        : ""
                    }

                                </div>

                            </div>

                        </td>

                        <td>
                            ${escaparHtmlCalendario(
                        tipoNome
                    )}
                        </td>

                        <td>
                            ${escaparHtmlCalendario(
                        periodo
                    )}
                        </td>

                        <td>
                            ${escaparHtmlCalendario(
                        filial
                    )}
                        </td>

                        <td class="text-center">

                            ${Number(
                        evento.dia_util || 0
                    ) === 1
                        ? `
                                        <span class="badge badge-success">
                                            Sim
                                        </span>
                                    `
                        : `
                                        <span class="badge badge-secondary">
                                            Não
                                        </span>
                                    `
                    }

                        </td>

                        <td class="text-center">

                            ${Number(
                        evento.remunerado || 0
                    ) === 1
                        ? "Sim"
                        : "Não"
                    }

                        </td>

                        <td class="text-center">

                            ${Number(
                        evento.permite_trabalho || 0
                    ) === 1
                        ? `
                                        <span class="badge badge-warning">
                                            Permitido
                                        </span>
                                    `
                        : `
                                        <span class="badge badge-secondary">
                                            Não
                                        </span>
                                    `
                    }

                        </td>

                        <td>

                            <span class="badge ${badgeStatus}">
                                ${escaparHtmlCalendario(
                        statusNome
                    )}
                            </span>

                        </td>

                        <td class="text-right">

                            <div class="dropdown dropdown-action">

                                <a
                                    href="#"
                                    class="action-icon dropdown-toggle"
                                    data-toggle="dropdown"
                                    aria-expanded="false">

                                    <i class="material-icons">
                                        more_vert
                                    </i>

                                </a>

                                <div class="dropdown-menu dropdown-menu-right">

                                    <button
                                        type="button"
                                        class="dropdown-item btn-ver-evento-laboral"
                                        data-id="${Number(
                        evento.id
                    )}">

                                        <i class="fa fa-eye m-r-5"></i>

                                        Ver detalhes

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item btn-editar-evento-laboral"
                                        data-id="${Number(
                        evento.id
                    )}">

                                        <i class="fa fa-pencil m-r-5"></i>

                                        Editar

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item text-danger btn-eliminar-evento-laboral"
                                        data-id="${Number(
                        evento.id
                    )}">

                                        <i class="fa fa-trash-o m-r-5"></i>

                                        Eliminar

                                    </button>

                                </div>

                            </div>

                        </td>

                    </tr>
                `);

            }
        );

        iniciarDataTableCalendarioLaboral();

    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function iniciarDataTableCalendarioLaboral() {

        if (
            typeof $.fn.DataTable
            === "undefined"
        ) {
            return;
        }

        tabelaCalendarioLaboral =
            $("#calendarioLaboralTable")
                .DataTable({
                    pageLength:
                        10,

                    order: [
                        [
                            3,
                            "asc"
                        ]
                    ],

                    columnDefs: [
                        {
                            targets:
                                9,

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
                            "Nenhum evento laboral encontrado",

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


    function destruirDataTableCalendarioLaboral() {

        if (
            tabelaCalendarioLaboral
            && typeof tabelaCalendarioLaboral.destroy
            === "function"
        ) {

            tabelaCalendarioLaboral.destroy();

            tabelaCalendarioLaboral = null;

        }

        if (
            typeof $.fn.DataTable
            !== "undefined"
            && $.fn.DataTable.isDataTable(
                "#calendarioLaboralTable"
            )
        ) {

            $("#calendarioLaboralTable")
                .DataTable()
                .destroy();

        }

    }


    // ==================================================
    // RESUMO
    // ==================================================

    function atualizarResumoCalendarioLaboral(
        eventos
    ) {

        const total =
            eventos.length;

        const feriados =
            eventos.filter(
                function (evento) {

                    return [
                        "FERIADO_NACIONAL",
                        "FERIADO_LOCAL"
                    ].includes(
                        String(
                            evento.tipo_evento || ""
                        )
                    );

                }
            ).length;

        const diasNaoUteis =
            eventos.filter(
                function (evento) {

                    return Number(
                        evento.dia_util || 0
                    ) !== 1;

                }
            ).length;

        const recorrentes =
            eventos.filter(
                function (evento) {

                    return Number(
                        evento.recorrente_anual || 0
                    ) === 1;

                }
            ).length;

        definirTextoCalendario(
            "#resumoTotalEventosLaborais",
            total.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoCalendario(
            "#resumoFeriadosLaborais",
            feriados.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoCalendario(
            "#resumoDiasNaoUteis",
            diasNaoUteis.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoCalendario(
            "#resumoEventosRecorrentes",
            recorrentes.toLocaleString(
                "pt-PT"
            )
        );

    }


    // ==================================================
    // NOVO
    // ==================================================

    function abrirModalNovoEventoLaboral() {

        limparFormularioCalendarioLaboral();

        $("#modalCalendarioLaboralTitulo")
            .text(
                "Novo Evento Laboral"
            );

        $("#btnGuardarCalendarioLaboralTexto")
            .text(
                "Guardar Evento"
            );

        const hoje =
            obterDataAtualIso();

        $("#calendarioDataInicio")
            .val(hoje);

        $("#calendarioDataFim")
            .val(hoje);

        $("#calendarioDiaInteiro")
            .prop("checked", true);

        $("#calendarioDiaUtil")
            .prop("checked", false);

        $("#calendarioDescontaFerias")
            .prop("checked", false);

        $("#calendarioRemunerado")
            .prop("checked", true);

        $("#calendarioPermiteTrabalho")
            .prop("checked", false);

        $("#calendarioExigeCompensacao")
            .prop("checked", false);

        $("#calendarioRecorrenteAnual")
            .prop("checked", false);

        $("#calendarioAplicaTodosFuncionarios")
            .prop("checked", true);

        $("#calendarioCor")
            .val("#dc3545");

        $("#calendarioTipoEvento")
            .val(
                "FERIADO_NACIONAL"
            );

        const statusAtivoId =
            Number(
                $("#calendarioStatusId")
                    .data(
                        "status-ativo-id"
                    )
                || 0
            );

        if (statusAtivoId > 0) {

            $("#calendarioStatusId")
                .val(
                    String(
                        statusAtivoId
                    )
                );

        }

        atualizarCamposHorarioEvento();

        atualizarCamposPorTipoEvento();

        atualizarCampoCompensacao();

        $("#modalCalendarioLaboral")
            .modal("show");

        setTimeout(
            function () {

                $("#calendarioCodigo")
                    .trigger("focus");

            },
            300
        );

    }


    // ==================================================
    // EDITAR
    // ==================================================

    async function abrirModalEditarEventoLaboral(
        eventoId
    ) {

        if (eventoId <= 0) {

            mostrarErroCalendarioLaboral(
                "Evento laboral inválido."
            );

            return;

        }

        mostrarCarregamentoCalendarioLaboral(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "buscar",

                    id:
                        String(
                            eventoId
                        )
                });

            const resposta =
                await requisicaoCalendarioLaboral(
                    CALENDARIO_LABORAL_URL
                    + "?"
                    + parametros.toString()
                );

            const evento =
                resposta.data || {};

            limparFormularioCalendarioLaboral();

            $("#calendarioId")
                .val(
                    Number(
                        evento.id || 0
                    )
                );

            $("#calendarioCodigo")
                .val(
                    evento.codigo || ""
                );

            $("#calendarioTitulo")
                .val(
                    evento.titulo || ""
                );

            $("#calendarioDescricao")
                .val(
                    evento.descricao || ""
                );

            $("#calendarioTipoEvento")
                .val(
                    evento.tipo_evento || ""
                );

            $("#calendarioFilialId")
                .val(
                    evento.filial_id
                        ? String(
                            evento.filial_id
                        )
                        : ""
                );

            $("#calendarioDataInicio")
                .val(
                    evento.data_inicio || ""
                );

            $("#calendarioDataFim")
                .val(
                    evento.data_fim || ""
                );

            $("#calendarioDiaInteiro")
                .prop(
                    "checked",
                    Number(
                        evento.dia_inteiro || 0
                    ) === 1
                );

            $("#calendarioHoraInicio")
                .val(
                    formatarHoraInputCalendario(
                        evento.hora_inicio
                    )
                );

            $("#calendarioHoraFim")
                .val(
                    formatarHoraInputCalendario(
                        evento.hora_fim
                    )
                );

            $("#calendarioDiaUtil")
                .prop(
                    "checked",
                    Number(
                        evento.dia_util || 0
                    ) === 1
                );

            $("#calendarioDescontaFerias")
                .prop(
                    "checked",
                    Number(
                        evento.desconta_ferias || 0
                    ) === 1
                );

            $("#calendarioRemunerado")
                .prop(
                    "checked",
                    Number(
                        evento.remunerado || 0
                    ) === 1
                );

            $("#calendarioPermiteTrabalho")
                .prop(
                    "checked",
                    Number(
                        evento.permite_trabalho || 0
                    ) === 1
                );

            $("#calendarioExigeCompensacao")
                .prop(
                    "checked",
                    Number(
                        evento.exige_compensacao || 0
                    ) === 1
                );

            $("#calendarioRecorrenteAnual")
                .prop(
                    "checked",
                    Number(
                        evento.recorrente_anual || 0
                    ) === 1
                );

            $("#calendarioAplicaTodosFuncionarios")
                .prop(
                    "checked",
                    Number(
                        evento.aplica_todos_funcionarios || 0
                    ) === 1
                );

            $("#calendarioCor")
                .val(
                    evento.cor || "#dc3545"
                );

            $("#calendarioObservacoes")
                .val(
                    evento.observacoes || ""
                );

            $("#calendarioStatusId")
                .val(
                    evento.status_id
                        ? String(
                            evento.status_id
                        )
                        : ""
                );

            atualizarCamposHorarioEvento();

            atualizarCamposPorTipoEvento();

            atualizarCampoCompensacao();

            $("#modalCalendarioLaboralTitulo")
                .text(
                    "Editar Evento Laboral"
                );

            $("#btnGuardarCalendarioLaboralTexto")
                .text(
                    "Atualizar Evento"
                );

            $("#modalCalendarioLaboral")
                .modal("show");

        } catch (erro) {

            mostrarErroCalendarioLaboral(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoCalendarioLaboral(false);

        }

    }


    // ==================================================
    // GUARDAR
    // ==================================================

    async function guardarEventoLaboral() {

        const dados =
            obterDadosFormularioCalendarioLaboral();

        validarFormularioCalendarioLaboral(
            dados
        );

        bloquearModalCalendarioLaboral(true);

        try {

            const formData =
                new FormData();

            formData.append(
                "acao",
                dados.id > 0
                    ? "editar"
                    : "criar"
            );

            if (dados.id > 0) {

                formData.append(
                    "id",
                    String(
                        dados.id
                    )
                );

            }

            Object.entries(
                dados
            ).forEach(
                function ([campo, valor]) {

                    if (campo === "id") {
                        return;
                    }

                    if (typeof valor === "boolean") {

                        formData.append(
                            campo,
                            valor
                                ? "1"
                                : "0"
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

            const resposta =
                await requisicaoCalendarioLaboral(
                    CALENDARIO_LABORAL_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            $("#modalCalendarioLaboral")
                .modal("hide");

            await Swal.fire({
                icon:
                    "success",

                title:
                    dados.id > 0
                        ? "Evento atualizado"
                        : "Evento criado",

                text:
                    resposta.mensagem
                    || "Operação concluída com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await recarregarCalendarioLaboral();

        } catch (erro) {

            mostrarErroCalendarioLaboral(
                erro.message
            );

            throw erro;

        } finally {

            bloquearModalCalendarioLaboral(false);

        }

    }


    // ==================================================
    // DADOS DO FORMULÁRIO
    // ==================================================

    function obterDadosFormularioCalendarioLaboral() {

        return {
            id:
                Number(
                    $("#calendarioId").val()
                    || 0
                ),

            codigo:
                String(
                    $("#calendarioCodigo").val()
                    || ""
                )
                    .trim()
                    .toUpperCase(),

            titulo:
                String(
                    $("#calendarioTitulo").val()
                    || ""
                ).trim(),

            descricao:
                String(
                    $("#calendarioDescricao").val()
                    || ""
                ).trim(),

            tipo_evento:
                String(
                    $("#calendarioTipoEvento").val()
                    || ""
                ).trim(),

            filial_id:
                inteiroOpcionalCalendario(
                    $("#calendarioFilialId").val()
                ),

            data_inicio:
                String(
                    $("#calendarioDataInicio").val()
                    || ""
                ).trim(),

            data_fim:
                String(
                    $("#calendarioDataFim").val()
                    || ""
                ).trim(),

            dia_inteiro:
                $("#calendarioDiaInteiro")
                    .is(":checked"),

            hora_inicio:
                valorOpcionalCalendario(
                    $("#calendarioHoraInicio").val()
                ),

            hora_fim:
                valorOpcionalCalendario(
                    $("#calendarioHoraFim").val()
                ),

            dia_util:
                $("#calendarioDiaUtil")
                    .is(":checked"),

            desconta_ferias:
                $("#calendarioDescontaFerias")
                    .is(":checked"),

            remunerado:
                $("#calendarioRemunerado")
                    .is(":checked"),

            permite_trabalho:
                $("#calendarioPermiteTrabalho")
                    .is(":checked"),

            exige_compensacao:
                $("#calendarioExigeCompensacao")
                    .is(":checked"),

            recorrente_anual:
                $("#calendarioRecorrenteAnual")
                    .is(":checked"),

            aplica_todos_funcionarios:
                $("#calendarioAplicaTodosFuncionarios")
                    .is(":checked"),

            cor:
                String(
                    $("#calendarioCor").val()
                    || ""
                ).trim(),

            observacoes:
                String(
                    $("#calendarioObservacoes").val()
                    || ""
                ).trim(),

            status_id:
                Number(
                    $("#calendarioStatusId").val()
                    || 0
                )
        };

    }


    // ==================================================
    // VALIDAR FORMULÁRIO
    // ==================================================

    function validarFormularioCalendarioLaboral(
        dados
    ) {

        if (dados.codigo === "") {

            throw new Error(
                "Informe o código do evento."
            );

        }

        if (dados.codigo.length > 30) {

            throw new Error(
                "O código do evento não pode exceder 30 caracteres."
            );

        }

        if (
            !/^[A-Z0-9._-]+$/
                .test(
                    dados.codigo
                )
        ) {

            throw new Error(
                "O código contém caracteres inválidos."
            );

        }

        if (dados.titulo === "") {

            throw new Error(
                "Informe o título do evento."
            );

        }

        if (dados.titulo.length > 150) {

            throw new Error(
                "O título não pode exceder 150 caracteres."
            );

        }

        if (dados.tipo_evento === "") {

            throw new Error(
                "Selecione o tipo de evento."
            );

        }

        if (dados.data_inicio === "") {

            throw new Error(
                "Informe a data inicial."
            );

        }

        if (dados.data_fim === "") {

            throw new Error(
                "Informe a data final."
            );

        }

        if (
            dados.data_fim
            < dados.data_inicio
        ) {

            throw new Error(
                "A data final não pode ser anterior à data inicial."
            );

        }

        if (!dados.dia_inteiro) {

            if (
                dados.hora_inicio === null
                || dados.hora_fim === null
            ) {

                throw new Error(
                    "Informe a hora inicial e a hora final."
                );

            }

            if (
                dados.data_inicio
                === dados.data_fim
                && dados.hora_fim
                <= dados.hora_inicio
            ) {

                throw new Error(
                    "A hora final deve ser posterior à hora inicial."
                );

            }

        }

        if (
            dados.exige_compensacao
            && !dados.permite_trabalho
        ) {

            throw new Error(
                "A compensação só pode ser exigida quando o trabalho for permitido."
            );

        }

        if (
            dados.cor !== ""
            && !/^#[0-9A-Fa-f]{6}$/
                .test(
                    dados.cor
                )
        ) {

            throw new Error(
                "A cor deve estar no formato hexadecimal, por exemplo #dc3545."
            );

        }

        if (dados.status_id <= 0) {

            throw new Error(
                "Selecione o estado do evento."
            );

        }

    }


    // ==================================================
    // CAMPOS DINÂMICOS
    // ==================================================

    function atualizarCamposHorarioEvento() {

        const diaInteiro =
            $("#calendarioDiaInteiro")
                .is(":checked");

        $("#calendarioHoraInicio")
            .prop(
                "disabled",
                diaInteiro
            )
            .prop(
                "required",
                !diaInteiro
            );

        $("#calendarioHoraFim")
            .prop(
                "disabled",
                diaInteiro
            )
            .prop(
                "required",
                !diaInteiro
            );

        if (diaInteiro) {

            $("#calendarioHoraInicio")
                .val("");

            $("#calendarioHoraFim")
                .val("");

        }

    }


    function atualizarCamposPorTipoEvento() {

        const tipo =
            String(
                $("#calendarioTipoEvento").val()
                || ""
            );

        const feriadoNacional =
            tipo === "FERIADO_NACIONAL";

        const diaUtilEspecial =
            tipo === "DIA_UTIL_ESPECIAL";

        const naoUtil =
            [
                "FERIADO_NACIONAL",
                "FERIADO_LOCAL",
                "DIA_NAO_UTIL",
                "SUSPENSAO_ATIVIDADE"
            ].includes(
                tipo
            );

        if (feriadoNacional) {

            $("#calendarioFilialId")
                .val("")
                .prop(
                    "disabled",
                    true
                );

            $("#calendarioAplicaTodosFuncionarios")
                .prop(
                    "checked",
                    true
                );

        } else {

            $("#calendarioFilialId")
                .prop(
                    "disabled",
                    false
                );

        }

        if (diaUtilEspecial) {

            $("#calendarioDiaUtil")
                .prop(
                    "checked",
                    true
                );

        }

        if (naoUtil) {

            $("#calendarioDiaUtil")
                .prop(
                    "checked",
                    false
                );

        }

        const corSugerida =
            obterCorSugeridaTipoEvento(
                tipo
            );

        if (corSugerida) {

            $("#calendarioCor")
                .val(
                    corSugerida
                );

        }

    }


    function atualizarCampoCompensacao() {

        const permiteTrabalho =
            $("#calendarioPermiteTrabalho")
                .is(":checked");

        $("#calendarioExigeCompensacao")
            .prop(
                "disabled",
                !permiteTrabalho
            );

        if (!permiteTrabalho) {

            $("#calendarioExigeCompensacao")
                .prop(
                    "checked",
                    false
                );

        }

    }


    function ajustarDataFimEvento() {

        const dataInicio =
            String(
                $("#calendarioDataInicio").val()
                || ""
            );

        const dataFim =
            String(
                $("#calendarioDataFim").val()
                || ""
            );

        if (
            dataInicio !== ""
            && (
                dataFim === ""
                || dataFim < dataInicio
            )
        ) {

            $("#calendarioDataFim")
                .val(
                    dataInicio
                );

        }

    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    async function confirmarEliminarEventoLaboral(
        eventoId
    ) {

        const evento =
            eventosCalendarioCache.find(
                function (item) {

                    return Number(
                        item.id
                    ) === Number(
                        eventoId
                    );

                }
            );

        if (!evento) {

            mostrarErroCalendarioLaboral(
                "Evento laboral não encontrado."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Eliminar evento laboral?",

                html:
                    `
                        <p>
                            Pretende eliminar:
                        </p>

                        <strong>
                            ${escaparHtmlCalendario(
                        evento.codigo
                        + " - "
                        + evento.titulo
                    )}
                        </strong>

                        <p class="mt-3 mb-0">
                            Este evento deixará de ser considerado
                            nos cálculos do calendário laboral.
                        </p>
                    `,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, eliminar",

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

        mostrarCarregamentoCalendarioLaboral(true);

        try {

            const formData =
                new FormData();

            formData.append(
                "acao",
                "eliminar"
            );

            formData.append(
                "id",
                String(
                    eventoId
                )
            );

            const resposta =
                await requisicaoCalendarioLaboral(
                    CALENDARIO_LABORAL_URL,
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
                    "Evento eliminado",

                text:
                    resposta.mensagem
                    || "Evento eliminado com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await recarregarCalendarioLaboral();

        } catch (erro) {

            mostrarErroCalendarioLaboral(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoCalendarioLaboral(false);

        }

    }


    // ==================================================
    // DETALHES
    // ==================================================

    function mostrarDetalhesEventoLaboral(
        eventoId
    ) {

        const evento =
            eventosCalendarioCache.find(
                function (item) {

                    return Number(
                        item.id
                    ) === Number(
                        eventoId
                    );

                }
            );

        if (!evento) {

            mostrarErroCalendarioLaboral(
                "Evento laboral não encontrado."
            );

            return;

        }

        Swal.fire({
            icon:
                "info",

            title:
                escaparHtmlCalendario(
                    evento.titulo
                    || "Evento Laboral"
                ),

            width:
                800,

            html:
                `
                    <div class="text-left">

                        <div class="row">

                            ${campoDetalheCalendario(
                    "Código",
                    evento.codigo || "-"
                )}

                            ${campoDetalheCalendario(
                    "Tipo",
                    obterNomeTipoEvento(
                        evento.tipo_evento
                    )
                )}

                            ${campoDetalheCalendario(
                    "Período",
                    formatarPeriodoEvento(
                        evento
                    )
                )}

                            ${campoDetalheCalendario(
                    "Filial",
                    evento.filial_nome
                    || "Todas as filiais"
                )}

                            ${campoDetalheCalendario(
                    "Estado",
                    evento.status_nome || "-"
                )}

                            ${campoDetalheCalendario(
                    "Dia inteiro",
                    simNaoCalendario(
                        evento.dia_inteiro
                    )
                )}

                            ${campoDetalheCalendario(
                    "Dia útil",
                    simNaoCalendario(
                        evento.dia_util
                    )
                )}

                            ${campoDetalheCalendario(
                    "Remunerado",
                    simNaoCalendario(
                        evento.remunerado
                    )
                )}

                            ${campoDetalheCalendario(
                    "Permite trabalho",
                    simNaoCalendario(
                        evento.permite_trabalho
                    )
                )}

                            ${campoDetalheCalendario(
                    "Exige compensação",
                    simNaoCalendario(
                        evento.exige_compensacao
                    )
                )}

                            ${campoDetalheCalendario(
                    "Desconta férias",
                    simNaoCalendario(
                        evento.desconta_ferias
                    )
                )}

                            ${campoDetalheCalendario(
                    "Recorrente anualmente",
                    simNaoCalendario(
                        evento.recorrente_anual
                    )
                )}

                            ${campoDetalheCalendario(
                    "Todos os funcionários",
                    simNaoCalendario(
                        evento.aplica_todos_funcionarios
                    )
                )}

                            ${campoDetalheCalendario(
                    "Cor",
                    evento.cor || "-"
                )}

                        </div>

                        <hr>

                        <div class="mb-3">

                            <strong>Descrição:</strong>

                            <div class="mt-2">
                                ${formatarTextoLongoCalendario(
                    evento.descricao
                )}
                            </div>

                        </div>

                        <div>

                            <strong>Observações:</strong>

                            <div class="mt-2">
                                ${formatarTextoLongoCalendario(
                    evento.observacoes
                )}
                            </div>

                        </div>

                    </div>
                `,

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // LIMPAR FORMULÁRIO
    // ==================================================

    function limparFormularioCalendarioLaboral() {

        const formulario =
            $("#formCalendarioLaboral")[0];

        if (formulario) {
            formulario.reset();
        }

        $("#calendarioId")
            .val("");

        $("#calendarioHoraInicio")
            .prop(
                "disabled",
                true
            );

        $("#calendarioHoraFim")
            .prop(
                "disabled",
                true
            );

        $("#calendarioFilialId")
            .prop(
                "disabled",
                false
            );

        $("#calendarioExigeCompensacao")
            .prop(
                "disabled",
                true
            );

        $("#formCalendarioLaboral")
            .find(".is-invalid")
            .removeClass("is-invalid");

        $("#modalCalendarioLaboralTitulo")
            .text(
                "Novo Evento Laboral"
            );

        $("#btnGuardarCalendarioLaboralTexto")
            .text(
                "Guardar Evento"
            );

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoCalendarioLaboral(
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
                || json.message
                || "Não foi possível processar o calendário laboral."
            );

        }

        return json;

    }


    // ==================================================
    // ESTADO DA INTERFACE
    // ==================================================

    function mostrarCarregamentoCalendarioLaboral(
        mostrar
    ) {

        $("#calendarioLaboralCarregando")
            .toggle(
                Boolean(
                    mostrar
                )
            );

        $("#btnNovoEventoLaboral")
            .prop(
                "disabled",
                Boolean(
                    mostrar
                )
            );

        $("#btnFiltrarCalendarioLaboral")
            .prop(
                "disabled",
                Boolean(
                    mostrar
                )
            );

        $("#btnLimparFiltrosCalendarioLaboral")
            .prop(
                "disabled",
                Boolean(
                    mostrar
                )
            );

    }


    function bloquearModalCalendarioLaboral(
        bloquear
    ) {

        $("#formCalendarioLaboral")
            .find(
                "input, textarea, select, button"
            )
            .prop(
                "disabled",
                Boolean(
                    bloquear
                )
            );

        $("#btnGuardarCalendarioLaboralSpinner")
            .toggle(
                Boolean(
                    bloquear
                )
            );

    }


    // ==================================================
    // ERRO
    // ==================================================

    function mostrarErroCalendarioLaboral(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível processar",

            text:
                mensagem
                || "Ocorreu um erro no calendário laboral.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // HELPERS
    // ==================================================

    function obterNomeTipoEvento(
        codigo
    ) {

        const tipo =
            tiposEventosCalendarioCache.find(
                function (item) {

                    return String(
                        item.codigo || ""
                    ) === String(
                        codigo || ""
                    );

                }
            );

        if (tipo) {
            return tipo.nome;
        }

        return String(
            codigo || "-"
        )
            .replace(
                /_/g,
                " "
            );

    }


    function obterCorSugeridaTipoEvento(
        tipo
    ) {

        const cores = {
            FERIADO_NACIONAL:
                "#dc3545",

            FERIADO_LOCAL:
                "#fd7e14",

            TOLERANCIA_PONTO:
                "#ffc107",

            DIA_NAO_UTIL:
                "#6c757d",

            DIA_UTIL_ESPECIAL:
                "#28a745",

            EVENTO_INTERNO:
                "#17a2b8",

            SUSPENSAO_ATIVIDADE:
                "#343a40",

            OUTRO:
                "#6f42c1"
        };

        return cores[tipo]
            || "#007bff";

    }


    function formatarPeriodoEvento(
        evento
    ) {

        const dataInicio =
            formatarDataCalendario(
                evento.data_inicio
            );

        const dataFim =
            formatarDataCalendario(
                evento.data_fim
            );

        let periodo =
            dataInicio;

        if (
            evento.data_fim
            && evento.data_fim
            !== evento.data_inicio
        ) {

            periodo +=
                " a "
                + dataFim;

        }

        if (
            Number(
                evento.dia_inteiro || 0
            ) !== 1
        ) {

            periodo +=
                " | "
                + formatarHoraVisivelCalendario(
                    evento.hora_inicio
                )
                + " - "
                + formatarHoraVisivelCalendario(
                    evento.hora_fim
                );

        }

        return periodo;

    }


    function formatarDataCalendario(
        data
    ) {

        if (!data) {
            return "-";
        }

        const partes =
            String(
                data
            ).split("-");

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


    function formatarHoraVisivelCalendario(
        hora
    ) {

        if (!hora) {
            return "-";
        }

        return String(
            hora
        ).substring(
            0,
            5
        );

    }


    function formatarHoraInputCalendario(
        hora
    ) {

        if (!hora) {
            return "";
        }

        return String(
            hora
        ).substring(
            0,
            5
        );

    }


    function simNaoCalendario(
        valor
    ) {

        return Number(
            valor || 0
        ) === 1
            ? "Sim"
            : "Não";

    }


    function campoDetalheCalendario(
        titulo,
        valor
    ) {

        return `
            <div class="col-md-6 mb-3">

                <strong>
                    ${escaparHtmlCalendario(
            titulo
        )}:
                </strong>

                <br>

                ${escaparHtmlCalendario(
            valor
        )}

            </div>
        `;

    }


    function inteiroOpcionalCalendario(
        valor
    ) {

        const numero =
            Number(
                valor || 0
            );

        return Number.isInteger(
            numero
        )
            && numero > 0
            ? numero
            : null;

    }


    function valorOpcionalCalendario(
        valor
    ) {

        const texto =
            String(
                valor ?? ""
            ).trim();

        return texto === ""
            ? null
            : texto;

    }


    function obterDataAtualIso() {

        const hoje =
            new Date();

        const ano =
            hoje.getFullYear();

        const mes =
            String(
                hoje.getMonth() + 1
            ).padStart(
                2,
                "0"
            );

        const dia =
            String(
                hoje.getDate()
            ).padStart(
                2,
                "0"
            );

        return `${ano}-${mes}-${dia}`;

    }


    function corSeguraEvento(
        cor
    ) {

        const valor =
            String(
                cor || ""
            ).trim();

        if (
            /^#[0-9A-Fa-f]{6}$/
                .test(
                    valor
                )
        ) {
            return valor;
        }

        return "#007bff";

    }


    function formatarTextoLongoCalendario(
        valor
    ) {

        const texto =
            String(
                valor || ""
            ).trim();

        if (texto === "") {

            return `
                <span class="text-muted">
                    Não informado
                </span>
            `;

        }

        return escaparHtmlCalendario(
            texto
        ).replace(
            /\n/g,
            "<br>"
        );

    }


    function definirTextoCalendario(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function normalizarTextoCalendario(
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


    function escaparHtmlCalendario(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);