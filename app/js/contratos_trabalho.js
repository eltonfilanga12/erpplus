"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const CONTRATOS_TRABALHO_URL =
        "/erpplus/app/ajax/contratos_trabalho_ajax.php";

    let contratosCache = [];
    let funcionariosCache = [];
    let tiposContratosCache = [];
    let filiaisCache = [];
    let departamentosCache = [];
    let cargosCache = [];
    let horariosCache = [];
    let statusContratosCache = [];
    let periodicidadesCache = [];
    let iniciativasRescisaoCache = [];

    let tabelaContratos = null;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaContratosExiste()) {
            return;
        }

        configurarEventosContratos();

        carregarDadosIniciaisContratos()
            .catch(function (erro) {

                console.error(
                    "Erro ao iniciar contratos de trabalho:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaContratosExiste() {

        return (
            $("#contratosTrabalhoTable").length > 0
            && $("#formContratoTrabalho").length > 0
            && $("#modalContratoTrabalho").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosContratos() {

        $("#btnNovoContratoTrabalho").on(
            "click",
            function () {

                abrirModalNovoContrato();

            }
        );

        $("#formContratoTrabalho").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarContrato()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao guardar contrato:",
                            erro
                        );

                    });

            }
        );

        $("#contratoNumero").on(
            "input",
            function () {

                const valor =
                    String(
                        $(this).val() || ""
                    )
                        .toUpperCase()
                        .replace(
                            /[^A-Z0-9._/-]/g,
                            ""
                        );

                $(this).val(valor);

            }
        );

        $("#contratoFuncionarioId").on(
            "change",
            function () {

                preencherDadosFuncionarioContrato();

            }
        );

        $("#contratoTipoContratoId").on(
            "change",
            function () {

                aplicarConfiguracaoTipoContrato();

            }
        );

        $("#contratoDepartamentoId").on(
            "change",
            function () {

                filtrarCargosContrato();

            }
        );

        $("#contratoHorarioId").on(
            "change",
            function () {

                preencherHorasSemanaisContrato();

            }
        );

        $("#contratoFilialId").on(
            "change",
            function () {

                preencherLocalTrabalhoContrato();

            }
        );

        $("#contratoDataInicio").on(
            "change",
            function () {

                aplicarDatasAutomaticasContrato();

            }
        );

        $("#contratoDataFim").on(
            "change",
            function () {

                atualizarProximaRenovacaoContrato();
                validarPeriodoContrato();

            }
        );

        $(
            "#contratoPeriodoExperimentalInicio, "
            + "#contratoPeriodoExperimentalFim"
        ).on(
            "change",
            function () {

                validarPeriodoExperimentalContrato();

            }
        );

        $("#contratoRenovacaoAutomatica").on(
            "change",
            function () {

                atualizarCamposRenovacaoContrato();

            }
        );

        $("#contratoStatusId").on(
            "change",
            function () {

                atualizarCamposEstadoContrato();

            }
        );

        $("#btnFiltrarContratosTrabalho").on(
            "click",
            function () {

                carregarContratosFiltrados()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao filtrar contratos:",
                            erro
                        );

                    });

            }
        );

        $("#btnLimparFiltrosContratosTrabalho").on(
            "click",
            function () {

                limparFiltrosContratos();

                carregarContratosFiltrados()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao limpar filtros:",
                            erro
                        );

                    });

            }
        );

        $("#filtroContratoPesquisa").on(
            "keydown",
            function (evento) {

                if (evento.key === "Enter") {

                    evento.preventDefault();

                    carregarContratosFiltrados()
                        .catch(function (erro) {

                            console.error(
                                "Erro ao pesquisar contratos:",
                                erro
                            );

                        });

                }

            }
        );

        $("#contratosTrabalhoTable").on(
            "click",
            ".btn-ver-contrato",
            function () {

                const contratoId =
                    Number(
                        $(this).data("id") || 0
                    );

                mostrarDetalhesContrato(
                    contratoId
                );

            }
        );

        $("#contratosTrabalhoTable").on(
            "click",
            ".btn-editar-contrato",
            function () {

                const contratoId =
                    Number(
                        $(this).data("id") || 0
                    );

                abrirModalEditarContrato(
                    contratoId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao carregar contrato:",
                            erro
                        );

                    });

            }
        );

        $("#contratosTrabalhoTable").on(
            "click",
            ".btn-eliminar-contrato",
            function () {

                const contratoId =
                    Number(
                        $(this).data("id") || 0
                    );

                confirmarEliminarContrato(
                    contratoId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao eliminar contrato:",
                            erro
                        );

                    });

            }
        );

        $("#modalContratoTrabalho").on(
            "hidden.bs.modal",
            function () {

                limparFormularioContrato();

            }
        );

    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisContratos() {

        mostrarCarregamentoContratos(true);

        try {

            const resposta =
                await requisicaoContratos(
                    CONTRATOS_TRABALHO_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            contratosCache =
                arraySeguro(dados.contratos);

            funcionariosCache =
                arraySeguro(dados.funcionarios);

            tiposContratosCache =
                arraySeguro(dados.tipos_contratos);

            filiaisCache =
                arraySeguro(dados.filiais);

            departamentosCache =
                arraySeguro(dados.departamentos);

            cargosCache =
                arraySeguro(dados.cargos);

            horariosCache =
                arraySeguro(dados.horarios);

            statusContratosCache =
                arraySeguro(dados.status);

            periodicidadesCache =
                arraySeguro(dados.periodicidades);

            iniciativasRescisaoCache =
                arraySeguro(dados.iniciativas_rescisao);

            preencherSelectsContrato();

            preencherFiltrosContrato();

            renderizarTabelaContratos(
                contratosCache
            );

            atualizarResumoContratos(
                contratosCache
            );

        } catch (erro) {

            mostrarErroContrato(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoContratos(false);

        }

    }


    // ==================================================
    // PREENCHER SELECTS
    // ==================================================

    function preencherSelectsContrato() {

        preencherSelectId(
            "#contratoFuncionarioId",
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
            "#contratoTipoContratoId",
            tiposContratosCache,
            "Selecione o tipo de contrato",
            function (item) {

                return (
                    (item.codigo || "-")
                    + " - "
                    + (item.nome || "-")
                );

            }
        );

        preencherSelectId(
            "#contratoFilialId",
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

        preencherSelectId(
            "#contratoDepartamentoId",
            departamentosCache,
            "Sem departamento",
            function (item) {

                return [
                    item.codigo || "",
                    item.nome || ""
                ]
                    .filter(Boolean)
                    .join(" - ");

            }
        );

        preencherCargosContrato(
            cargosCache
        );

        preencherSelectId(
            "#contratoHorarioId",
            horariosCache,
            "Sem horário definido",
            function (item) {

                const periodo =
                    item.hora_entrada
                        && item.hora_saida
                        ? " | "
                        + formatarHora(item.hora_entrada)
                        + " - "
                        + formatarHora(item.hora_saida)
                        : "";

                return (
                    (item.nome || "-")
                    + periodo
                );

            }
        );

        preencherSelectId(
            "#contratoStatusId",
            statusContratosCache,
            "Selecione o estado",
            function (item) {

                return item.nome || "-";

            }
        );

        preencherSelectCodigo(
            "#contratoPeriodicidadeSalario",
            periodicidadesCache,
            "Selecione a periodicidade"
        );

        preencherSelectCodigo(
            "#contratoIniciativaRescisao",
            iniciativasRescisaoCache,
            "Selecione a iniciativa"
        );

        const rascunho =
            statusContratosCache.find(
                function (item) {

                    return normalizarTexto(
                        item.nome
                    ) === "rascunho";

                }
            );

        if (rascunho) {

            $("#contratoStatusId")
                .data(
                    "status-rascunho-id",
                    Number(rascunho.id)
                );

        }

    }


    function preencherFiltrosContrato() {

        preencherSelectId(
            "#filtroContratoFuncionarioId",
            funcionariosCache,
            "Todos os funcionários",
            function (item) {

                return item.nome_completo || "-";

            }
        );

        preencherSelectId(
            "#filtroContratoTipoId",
            tiposContratosCache,
            "Todos os tipos",
            function (item) {

                return item.nome || "-";

            }
        );

        preencherSelectId(
            "#filtroContratoFilialId",
            filiaisCache,
            "Todas as filiais",
            function (item) {

                return item.nome || "-";

            }
        );

        preencherSelectId(
            "#filtroContratoDepartamentoId",
            departamentosCache,
            "Todos os departamentos",
            function (item) {

                return item.nome || "-";

            }
        );

        preencherSelectId(
            "#filtroContratoStatusId",
            statusContratosCache,
            "Todos os estados",
            function (item) {

                return item.nome || "-";

            }
        );

    }


    // ==================================================
    // FILTRAR
    // ==================================================

    async function carregarContratosFiltrados() {

        validarPeriodoFiltroContrato();

        mostrarCarregamentoContratos(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "listar"
                });

            adicionarParametro(
                parametros,
                "funcionario_id",
                $("#filtroContratoFuncionarioId").val()
            );

            adicionarParametro(
                parametros,
                "tipo_contrato_id",
                $("#filtroContratoTipoId").val()
            );

            adicionarParametro(
                parametros,
                "filial_id",
                $("#filtroContratoFilialId").val()
            );

            adicionarParametro(
                parametros,
                "departamento_id",
                $("#filtroContratoDepartamentoId").val()
            );

            adicionarParametro(
                parametros,
                "status_id",
                $("#filtroContratoStatusId").val()
            );

            adicionarParametro(
                parametros,
                "data_inicio",
                $("#filtroContratoDataInicio").val()
            );

            adicionarParametro(
                parametros,
                "data_fim",
                $("#filtroContratoDataFim").val()
            );

            adicionarParametro(
                parametros,
                "pesquisa",
                $("#filtroContratoPesquisa").val()
            );

            const resposta =
                await requisicaoContratos(
                    CONTRATOS_TRABALHO_URL
                    + "?"
                    + parametros.toString()
                );

            contratosCache =
                arraySeguro(
                    resposta.data
                );

            renderizarTabelaContratos(
                contratosCache
            );

            atualizarResumoContratos(
                contratosCache
            );

        } catch (erro) {

            mostrarErroContrato(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoContratos(false);

        }

    }


    // ==================================================
    // TABELA
    // ==================================================

    function renderizarTabelaContratos(
        contratos
    ) {

        destruirDataTableContratos();

        const tbody =
            $("#contratosTrabalhoTable tbody");

        tbody.empty();

        if (!contratos.length) {

            tbody.html(`
                <tr>
                    <td
                        colspan="10"
                        class="text-center text-muted py-4">

                        Nenhum contrato de trabalho encontrado.

                    </td>
                </tr>
            `);

            return;

        }

        contratos.forEach(
            function (contrato) {

                const statusNome =
                    String(
                        contrato.status_nome || "-"
                    );

                const badge =
                    classeStatusContrato(
                        statusNome
                    );

                tbody.append(`
                    <tr>

                        <td>
                            <strong>
                                ${escaparHtml(
                    contrato.numero_contrato || "-"
                )}
                            </strong>

                            ${contrato.referencia_externa
                        ? `
                                        <div class="text-muted small">
                                            ${escaparHtml(
                            contrato.referencia_externa
                        )}
                                        </div>
                                    `
                        : ""
                    }
                        </td>

                        <td>
                            <strong>
                                ${escaparHtml(
                        contrato.funcionario_nome || "-"
                    )}
                            </strong>

                            <div class="text-muted small">
                                ${escaparHtml(
                        contrato.numero_funcionario || "-"
                    )}
                            </div>
                        </td>

                        <td>
                            ${escaparHtml(
                        contrato.tipo_contrato_nome || "-"
                    )}
                        </td>

                        <td>
                            ${escaparHtml(
                        contrato.cargo_nome || "-"
                    )}

                            <div class="text-muted small">
                                ${escaparHtml(
                        contrato.departamento_nome || "-"
                    )}
                            </div>
                        </td>

                        <td>
                            ${formatarData(
                        contrato.data_inicio
                    )}
                        </td>

                        <td>
                            ${contrato.data_fim
                        ? formatarData(
                            contrato.data_fim
                        )
                        : `
                                        <span class="text-muted">
                                            Sem termo
                                        </span>
                                    `
                    }
                        </td>

                        <td>
                            ${formatarMoeda(
                        contrato.salario_base,
                        contrato.moeda || "MZN"
                    )}
                        </td>

                        <td class="text-center">
                            ${Number(
                        contrato.numero_renovacoes || 0
                    )}
                        </td>

                        <td>
                            <span class="badge ${badge}">
                                ${escaparHtml(
                        statusNome
                    )}
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
                                        class="dropdown-item btn-ver-contrato"
                                        data-id="${Number(
                        contrato.id
                    )}">

                                        <i class="fa fa-eye m-r-5"></i>
                                        Ver detalhes

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item btn-editar-contrato"
                                        data-id="${Number(
                        contrato.id
                    )}">

                                        <i class="fa fa-pencil m-r-5"></i>
                                        Editar

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item text-danger btn-eliminar-contrato"
                                        data-id="${Number(
                        contrato.id
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

        iniciarDataTableContratos();

    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function iniciarDataTableContratos() {

        if (
            typeof $.fn.DataTable
            === "undefined"
        ) {
            return;
        }

        tabelaContratos =
            $("#contratosTrabalhoTable")
                .DataTable({
                    pageLength:
                        10,

                    order: [
                        [
                            4,
                            "desc"
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
                            "Nenhum contrato encontrado",

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


    function destruirDataTableContratos() {

        if (
            tabelaContratos
            && typeof tabelaContratos.destroy
            === "function"
        ) {

            tabelaContratos.destroy();
            tabelaContratos = null;

        }

        if (
            typeof $.fn.DataTable
            !== "undefined"
            && $.fn.DataTable.isDataTable(
                "#contratosTrabalhoTable"
            )
        ) {

            $("#contratosTrabalhoTable")
                .DataTable()
                .destroy();

        }

    }


    // ==================================================
    // RESUMO
    // ==================================================

    function atualizarResumoContratos(
        contratos
    ) {

        const total =
            contratos.length;

        const ativos =
            contratos.filter(
                function (item) {

                    return [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        normalizarTexto(
                            item.status_nome
                        )
                    );

                }
            ).length;

        const expirados =
            contratos.filter(
                function (item) {

                    return item.data_fim
                        && item.data_fim
                        < dataAtualIso()
                        && ![
                            "terminado",
                            "rescindido",
                            "cancelado"
                        ].includes(
                            normalizarTexto(
                                item.status_nome
                            )
                        );

                }
            ).length;

        const porRenovar =
            contratos.filter(
                function (item) {

                    if (
                        !item.data_proxima_renovacao
                    ) {
                        return false;
                    }

                    const limite =
                        adicionarDiasIso(
                            dataAtualIso(),
                            30
                        );

                    return (
                        item.data_proxima_renovacao
                        >= dataAtualIso()
                        && item.data_proxima_renovacao
                        <= limite
                    );

                }
            ).length;

        $("#resumoTotalContratos")
            .text(total);

        $("#resumoContratosAtivos")
            .text(ativos);

        $("#resumoContratosPorRenovar")
            .text(porRenovar);

        $("#resumoContratosExpirados")
            .text(expirados);

    }


    // ==================================================
    // NOVO
    // ==================================================

    function abrirModalNovoContrato() {

        limparFormularioContrato();

        $("#modalContratoTrabalhoTitulo")
            .text(
                "Novo Contrato de Trabalho"
            );

        $("#btnGuardarContratoTrabalhoTexto")
            .text(
                "Guardar Contrato"
            );

        const statusRascunhoId =
            Number(
                $("#contratoStatusId")
                    .data(
                        "status-rascunho-id"
                    )
                || 0
            );

        if (statusRascunhoId > 0) {

            $("#contratoStatusId")
                .val(
                    String(statusRascunhoId)
                );

        }

        $("#contratoDataInicio")
            .val(
                dataAtualIso()
            );

        $("#contratoDataAssinatura")
            .val(
                dataAtualIso()
            );

        $("#contratoPeriodicidadeSalario")
            .val("MENSAL");

        $("#contratoMoeda")
            .val("MZN");

        $("#contratoRenovacaoAutomatica")
            .prop("checked", false);

        atualizarCamposRenovacaoContrato();
        atualizarCamposEstadoContrato();

        $("#modalContratoTrabalho")
            .modal("show");

        setTimeout(
            function () {

                $("#contratoNumero")
                    .trigger("focus");

            },
            300
        );

    }


    // ==================================================
    // EDITAR
    // ==================================================

    async function abrirModalEditarContrato(
        contratoId
    ) {

        mostrarCarregamentoContratos(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "buscar",

                    id:
                        String(contratoId)
                });

            const resposta =
                await requisicaoContratos(
                    CONTRATOS_TRABALHO_URL
                    + "?"
                    + parametros.toString()
                );

            const contrato =
                resposta.data || {};

            limparFormularioContrato();

            preencherFormularioContrato(
                contrato
            );

            $("#modalContratoTrabalhoTitulo")
                .text(
                    "Editar Contrato de Trabalho"
                );

            $("#btnGuardarContratoTrabalhoTexto")
                .text(
                    "Atualizar Contrato"
                );

            $("#modalContratoTrabalho")
                .modal("show");

        } catch (erro) {

            mostrarErroContrato(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoContratos(false);

        }

    }


    function preencherFormularioContrato(
        contrato
    ) {

        $("#contratoId")
            .val(contrato.id || "");

        $("#contratoNumero")
            .val(
                contrato.numero_contrato || ""
            );

        $("#contratoReferenciaExterna")
            .val(
                contrato.referencia_externa || ""
            );

        $("#contratoFuncionarioId")
            .val(
                contrato.funcionario_id
                    ? String(contrato.funcionario_id)
                    : ""
            );

        $("#contratoTipoContratoId")
            .val(
                contrato.tipo_contrato_id
                    ? String(contrato.tipo_contrato_id)
                    : ""
            );

        $("#contratoFilialId")
            .val(
                contrato.filial_id
                    ? String(contrato.filial_id)
                    : ""
            );

        $("#contratoDepartamentoId")
            .val(
                contrato.departamento_id
                    ? String(contrato.departamento_id)
                    : ""
            );

        filtrarCargosContrato(
            contrato.cargo_id || null
        );

        $("#contratoHorarioId")
            .val(
                contrato.horario_trabalho_id
                    ? String(
                        contrato.horario_trabalho_id
                    )
                    : ""
            );

        $("#contratoDataAssinatura")
            .val(
                contrato.data_assinatura || ""
            );

        $("#contratoDataInicio")
            .val(
                contrato.data_inicio || ""
            );

        $("#contratoDataFim")
            .val(
                contrato.data_fim || ""
            );

        $("#contratoPeriodoExperimentalInicio")
            .val(
                contrato.periodo_experimental_inicio
                || ""
            );

        $("#contratoPeriodoExperimentalFim")
            .val(
                contrato.periodo_experimental_fim
                || ""
            );

        $("#contratoAvisoPrevioDias")
            .val(
                Number(
                    contrato.aviso_previo_dias || 0
                )
            );

        $("#contratoRenovacaoAutomatica")
            .prop(
                "checked",
                Number(
                    contrato.renovacao_automatica || 0
                ) === 1
            );

        $("#contratoNumeroRenovacoes")
            .val(
                Number(
                    contrato.numero_renovacoes || 0
                )
            );

        $("#contratoNumeroMaximoRenovacoes")
            .val(
                contrato.numero_maximo_renovacoes
                ?? ""
            );

        $("#contratoDataProximaRenovacao")
            .val(
                contrato.data_proxima_renovacao || ""
            );

        $("#contratoSalarioBase")
            .val(
                contrato.salario_base
                ?? "0.00"
            );

        $("#contratoMoeda")
            .val(
                contrato.moeda || "MZN"
            );

        $("#contratoPeriodicidadeSalario")
            .val(
                contrato.periodicidade_salario
                || "MENSAL"
            );

        $("#contratoHorasSemanais")
            .val(
                contrato.horas_semanais
                ?? ""
            );

        $("#contratoLocalTrabalho")
            .val(
                contrato.local_trabalho || ""
            );

        $("#contratoDescricaoFuncoes")
            .val(
                contrato.descricao_funcoes || ""
            );

        $("#contratoClausulasEspeciais")
            .val(
                contrato.clausulas_especiais || ""
            );

        $("#contratoBeneficios")
            .val(
                contrato.beneficios || ""
            );

        $("#contratoObservacoes")
            .val(
                contrato.observacoes || ""
            );

        $("#contratoDocumento")
            .val(
                contrato.documento_contrato || ""
            );

        $("#contratoStatusId")
            .val(
                contrato.status_id
                    ? String(contrato.status_id)
                    : ""
            );

        $("#contratoDataSuspensao")
            .val(
                contrato.data_suspensao || ""
            );

        $("#contratoMotivoSuspensao")
            .val(
                contrato.motivo_suspensao || ""
            );

        $("#contratoDataRescisao")
            .val(
                contrato.data_rescisao || ""
            );

        $("#contratoMotivoRescisao")
            .val(
                contrato.motivo_rescisao || ""
            );

        $("#contratoIniciativaRescisao")
            .val(
                contrato.iniciativa_rescisao || ""
            );

        atualizarCamposRenovacaoContrato();
        atualizarCamposEstadoContrato();

    }


    // ==================================================
    // GUARDAR
    // ==================================================

    async function guardarContrato() {

        const dados =
            obterDadosFormularioContrato();

        validarFormularioContrato(
            dados
        );

        bloquearModalContrato(true);

        try {

            const formData =
                new FormData();

            formData.append(
                "acao",
                dados.id > 0
                    ? "editar"
                    : "criar"
            );

            Object.entries(
                dados
            ).forEach(
                function ([campo, valor]) {

                    formData.append(
                        campo,
                        valor === null
                            ? ""
                            : String(valor)
                    );

                }
            );

            const resposta =
                await requisicaoContratos(
                    CONTRATOS_TRABALHO_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            $("#modalContratoTrabalho")
                .modal("hide");

            await Swal.fire({
                icon:
                    "success",

                title:
                    dados.id > 0
                        ? "Contrato atualizado"
                        : "Contrato criado",

                text:
                    resposta.mensagem
                    || "Operação concluída com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await carregarContratosFiltrados();

        } catch (erro) {

            mostrarErroContrato(
                erro.message
            );

            throw erro;

        } finally {

            bloquearModalContrato(false);

        }

    }


    // ==================================================
    // DADOS DO FORMULÁRIO
    // ==================================================

    function obterDadosFormularioContrato() {

        return {
            id:
                Number(
                    $("#contratoId").val() || 0
                ),

            numero_contrato:
                textoCampo(
                    "#contratoNumero"
                ).toUpperCase(),

            referencia_externa:
                textoCampo(
                    "#contratoReferenciaExterna"
                ),

            funcionario_id:
                Number(
                    $("#contratoFuncionarioId").val()
                    || 0
                ),

            tipo_contrato_id:
                Number(
                    $("#contratoTipoContratoId").val()
                    || 0
                ),

            filial_id:
                inteiroOpcional(
                    $("#contratoFilialId").val()
                ),

            departamento_id:
                inteiroOpcional(
                    $("#contratoDepartamentoId").val()
                ),

            cargo_id:
                inteiroOpcional(
                    $("#contratoCargoId").val()
                ),

            horario_trabalho_id:
                inteiroOpcional(
                    $("#contratoHorarioId").val()
                ),

            data_assinatura:
                valorOpcional(
                    $("#contratoDataAssinatura").val()
                ),

            data_inicio:
                textoCampo(
                    "#contratoDataInicio"
                ),

            data_fim:
                valorOpcional(
                    $("#contratoDataFim").val()
                ),

            periodo_experimental_inicio:
                valorOpcional(
                    $("#contratoPeriodoExperimentalInicio").val()
                ),

            periodo_experimental_fim:
                valorOpcional(
                    $("#contratoPeriodoExperimentalFim").val()
                ),

            aviso_previo_dias:
                inteiroNaoNegativo(
                    $("#contratoAvisoPrevioDias").val()
                ),

            renovacao_automatica:
                $("#contratoRenovacaoAutomatica")
                    .is(":checked")
                    ? 1
                    : 0,

            numero_renovacoes:
                inteiroNaoNegativo(
                    $("#contratoNumeroRenovacoes").val()
                ),

            numero_maximo_renovacoes:
                inteiroOpcionalNaoNegativo(
                    $("#contratoNumeroMaximoRenovacoes").val()
                ),

            data_proxima_renovacao:
                valorOpcional(
                    $("#contratoDataProximaRenovacao").val()
                ),

            salario_base:
                decimalCampo(
                    $("#contratoSalarioBase").val()
                ),

            moeda:
                textoCampo(
                    "#contratoMoeda"
                ).toUpperCase(),

            periodicidade_salario:
                textoCampo(
                    "#contratoPeriodicidadeSalario"
                ),

            horas_semanais:
                decimalOpcionalCampo(
                    $("#contratoHorasSemanais").val()
                ),

            local_trabalho:
                textoCampo(
                    "#contratoLocalTrabalho"
                ),

            descricao_funcoes:
                textoCampo(
                    "#contratoDescricaoFuncoes"
                ),

            clausulas_especiais:
                textoCampo(
                    "#contratoClausulasEspeciais"
                ),

            beneficios:
                textoCampo(
                    "#contratoBeneficios"
                ),

            observacoes:
                textoCampo(
                    "#contratoObservacoes"
                ),

            documento_contrato:
                textoCampo(
                    "#contratoDocumento"
                ),

            status_id:
                Number(
                    $("#contratoStatusId").val()
                    || 0
                ),

            data_suspensao:
                valorOpcional(
                    $("#contratoDataSuspensao").val()
                ),

            motivo_suspensao:
                textoCampo(
                    "#contratoMotivoSuspensao"
                ),

            data_rescisao:
                valorOpcional(
                    $("#contratoDataRescisao").val()
                ),

            motivo_rescisao:
                textoCampo(
                    "#contratoMotivoRescisao"
                ),

            iniciativa_rescisao:
                valorOpcional(
                    $("#contratoIniciativaRescisao").val()
                )
        };

    }


    // ==================================================
    // VALIDAÇÃO
    // ==================================================

    function validarFormularioContrato(
        dados
    ) {

        if (dados.numero_contrato === "") {
            throw new Error(
                "Informe o número do contrato."
            );
        }

        if (dados.funcionario_id <= 0) {
            throw new Error(
                "Selecione o funcionário."
            );
        }

        if (dados.tipo_contrato_id <= 0) {
            throw new Error(
                "Selecione o tipo de contrato."
            );
        }

        if (dados.data_inicio === "") {
            throw new Error(
                "Informe a data de início."
            );
        }

        if (
            dados.data_fim
            && dados.data_fim < dados.data_inicio
        ) {
            throw new Error(
                "A data final não pode ser anterior à data inicial."
            );
        }

        if (
            dados.periodo_experimental_inicio
            && dados.periodo_experimental_fim
            && dados.periodo_experimental_fim
            < dados.periodo_experimental_inicio
        ) {
            throw new Error(
                "O fim do período experimental não pode ser anterior ao início."
            );
        }

        if (
            dados.numero_maximo_renovacoes !== null
            && dados.numero_renovacoes
            > dados.numero_maximo_renovacoes
        ) {
            throw new Error(
                "O número de renovações excede o limite."
            );
        }

        if (
            dados.renovacao_automatica === 1
            && !dados.data_proxima_renovacao
            && !dados.data_fim
        ) {
            throw new Error(
                "Informe a próxima renovação ou a data final."
            );
        }

        if (Number(dados.salario_base) < 0) {
            throw new Error(
                "O salário base não pode ser negativo."
            );
        }

        if (
            !/^[A-Z]{3}$/.test(
                dados.moeda
            )
        ) {
            throw new Error(
                "A moeda deve possuir três letras."
            );
        }

        if (dados.status_id <= 0) {
            throw new Error(
                "Selecione o estado do contrato."
            );
        }

        const estado =
            estadoContratoSelecionado();

        if (estado === "suspenso") {

            if (!dados.data_suspensao) {
                throw new Error(
                    "Informe a data de suspensão."
                );
            }

            if (!dados.motivo_suspensao) {
                throw new Error(
                    "Informe o motivo da suspensão."
                );
            }

        }

        if (
            [
                "terminado",
                "rescindido"
            ].includes(estado)
        ) {

            if (!dados.data_rescisao) {
                throw new Error(
                    "Informe a data de término ou rescisão."
                );
            }

            if (!dados.motivo_rescisao) {
                throw new Error(
                    "Informe o motivo da rescisão ou término."
                );
            }

            if (!dados.iniciativa_rescisao) {
                throw new Error(
                    "Selecione a iniciativa da rescisão."
                );
            }

        }

    }


    // ==================================================
    // FUNCIONÁRIO
    // ==================================================

    function preencherDadosFuncionarioContrato() {

        const funcionarioId =
            Number(
                $("#contratoFuncionarioId").val()
                || 0
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

        $("#contratoFilialId")
            .val(
                funcionario.filial_id
                    ? String(funcionario.filial_id)
                    : ""
            );

        $("#contratoDepartamentoId")
            .val(
                funcionario.departamento_id
                    ? String(
                        funcionario.departamento_id
                    )
                    : ""
            );

        filtrarCargosContrato(
            funcionario.cargo_id || null
        );

        $("#contratoHorarioId")
            .val(
                funcionario.horario_trabalho_id
                    ? String(
                        funcionario.horario_trabalho_id
                    )
                    : ""
            );

        $("#contratoSalarioBase")
            .val(
                funcionario.salario_base
                ?? "0.00"
            );

        $("#contratoMoeda")
            .val(
                funcionario.moeda || "MZN"
            );

        preencherHorasSemanaisContrato();
        preencherLocalTrabalhoContrato();

    }


    // ==================================================
    // TIPO DE CONTRATO
    // ==================================================

    function aplicarConfiguracaoTipoContrato() {

        const tipoId =
            Number(
                $("#contratoTipoContratoId").val()
                || 0
            );

        const tipo =
            tiposContratosCache.find(
                function (item) {

                    return Number(item.id)
                        === tipoId;

                }
            );

        if (!tipo) {
            return;
        }

        $("#contratoAvisoPrevioDias")
            .val(
                Number(
                    tipo.aviso_previo_dias || 0
                )
            );

        $("#contratoNumeroMaximoRenovacoes")
            .val(
                tipo.numero_maximo_renovacoes
                ?? ""
            );

        $("#contratoRenovacaoAutomatica")
            .prop(
                "checked",
                Number(
                    tipo.renovacao_automatica || 0
                ) === 1
            );

        aplicarDatasAutomaticasContrato();
        atualizarCamposRenovacaoContrato();

    }


    function aplicarDatasAutomaticasContrato() {

        const inicio =
            textoCampo(
                "#contratoDataInicio"
            );

        const tipoId =
            Number(
                $("#contratoTipoContratoId").val()
                || 0
            );

        const tipo =
            tiposContratosCache.find(
                function (item) {

                    return Number(item.id)
                        === tipoId;

                }
            );

        if (!inicio || !tipo) {
            return;
        }

        const duracaoMeses =
            Number(
                tipo.duracao_meses || 0
            );

        if (
            Number(
                tipo.duracao_determinada || 0
            ) === 1
            && duracaoMeses > 0
            && !$("#contratoDataFim").val()
        ) {

            $("#contratoDataFim")
                .val(
                    adicionarMesesIso(
                        inicio,
                        duracaoMeses
                    )
                );

        }

        const diasExperiencia =
            Number(
                tipo.periodo_experimental_dias || 0
            );

        if (diasExperiencia > 0) {

            if (
                !$("#contratoPeriodoExperimentalInicio")
                    .val()
            ) {

                $("#contratoPeriodoExperimentalInicio")
                    .val(inicio);

            }

            if (
                !$("#contratoPeriodoExperimentalFim")
                    .val()
            ) {

                $("#contratoPeriodoExperimentalFim")
                    .val(
                        adicionarDiasIso(
                            inicio,
                            diasExperiencia
                        )
                    );

            }

        }

        atualizarProximaRenovacaoContrato();

    }


    // ==================================================
    // CARGOS
    // ==================================================

    function filtrarCargosContrato(
        cargoSelecionado = null
    ) {

        const departamentoId =
            Number(
                $("#contratoDepartamentoId").val()
                || 0
            );

        const cargos =
            departamentoId > 0
                ? cargosCache.filter(
                    function (cargo) {

                        return Number(
                            cargo.departamento_id || 0
                        ) === departamentoId;

                    }
                )
                : cargosCache;

        preencherCargosContrato(
            cargos
        );

        if (
            cargoSelecionado !== null
            && Number(cargoSelecionado) > 0
        ) {

            $("#contratoCargoId")
                .val(
                    String(cargoSelecionado)
                );

        }

    }


    function preencherCargosContrato(
        cargos
    ) {

        preencherSelectId(
            "#contratoCargoId",
            cargos,
            "Sem cargo definido",
            function (item) {

                return [
                    item.codigo || "",
                    item.nome || ""
                ]
                    .filter(Boolean)
                    .join(" - ");

            }
        );

    }


    // ==================================================
    // HORÁRIO E LOCAL
    // ==================================================

    function preencherHorasSemanaisContrato() {

        const horarioId =
            Number(
                $("#contratoHorarioId").val()
                || 0
            );

        const horario =
            horariosCache.find(
                function (item) {

                    return Number(item.id)
                        === horarioId;

                }
            );

        if (!horario) {
            return;
        }

        $("#contratoHorasSemanais")
            .val(
                horario.carga_horaria_semanal
                ?? ""
            );

    }


    function preencherLocalTrabalhoContrato() {

        const filialId =
            Number(
                $("#contratoFilialId").val()
                || 0
            );

        const filial =
            filiaisCache.find(
                function (item) {

                    return Number(item.id)
                        === filialId;

                }
            );

        if (
            filial
            && !$("#contratoLocalTrabalho").val()
        ) {

            $("#contratoLocalTrabalho")
                .val(
                    filial.endereco
                    || filial.nome
                    || ""
                );

        }

    }


    // ==================================================
    // RENOVAÇÃO
    // ==================================================

    function atualizarCamposRenovacaoContrato() {

        const renovacao =
            $("#contratoRenovacaoAutomatica")
                .is(":checked");

        $("#contratoNumeroMaximoRenovacoes")
            .prop(
                "disabled",
                !renovacao
            );

        $("#contratoDataProximaRenovacao")
            .prop(
                "disabled",
                !renovacao
            );

        if (!renovacao) {

            $("#contratoDataProximaRenovacao")
                .val("");

        } else {

            atualizarProximaRenovacaoContrato();

        }

    }


    function atualizarProximaRenovacaoContrato() {

        if (
            !$("#contratoRenovacaoAutomatica")
                .is(":checked")
        ) {
            return;
        }

        const dataFim =
            textoCampo(
                "#contratoDataFim"
            );

        if (
            dataFim
            && !$("#contratoDataProximaRenovacao")
                .val()
        ) {

            $("#contratoDataProximaRenovacao")
                .val(dataFim);

        }

    }


    // ==================================================
    // ESTADO
    // ==================================================

    function atualizarCamposEstadoContrato() {

        const estado =
            estadoContratoSelecionado();

        const suspenso =
            estado === "suspenso";

        const encerrado =
            [
                "terminado",
                "rescindido"
            ].includes(
                estado
            );

        $("#contratoDataSuspensao")
            .prop(
                "disabled",
                !suspenso
            );

        $("#contratoMotivoSuspensao")
            .prop(
                "disabled",
                !suspenso
            );

        $("#contratoDataRescisao")
            .prop(
                "disabled",
                !encerrado
            );

        $("#contratoMotivoRescisao")
            .prop(
                "disabled",
                !encerrado
            );

        $("#contratoIniciativaRescisao")
            .prop(
                "disabled",
                !encerrado
            );

        if (!suspenso) {

            $("#contratoDataSuspensao")
                .val("");

            $("#contratoMotivoSuspensao")
                .val("");

        }

        if (!encerrado) {

            $("#contratoDataRescisao")
                .val("");

            $("#contratoMotivoRescisao")
                .val("");

            $("#contratoIniciativaRescisao")
                .val("");

        }

    }


    function estadoContratoSelecionado() {

        return normalizarTexto(
            $("#contratoStatusId option:selected")
                .text()
        );

    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    async function confirmarEliminarContrato(
        contratoId
    ) {

        const contrato =
            contratosCache.find(
                function (item) {

                    return Number(item.id)
                        === Number(contratoId);

                }
            );

        if (!contrato) {

            mostrarErroContrato(
                "Contrato não encontrado."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Eliminar contrato?",

                html:
                    `
                        <p>
                            Pretende eliminar:
                        </p>

                        <strong>
                            ${escaparHtml(
                        contrato.numero_contrato
                    )}
                        </strong>

                        <div class="mt-2">
                            ${escaparHtml(
                        contrato.funcionario_nome
                        || "-"
                    )}
                        </div>

                        <p class="mt-3 mb-0">
                            Apenas contratos em rascunho ou
                            cancelados podem ser eliminados.
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

        mostrarCarregamentoContratos(true);

        try {

            const formData =
                new FormData();

            formData.append(
                "acao",
                "eliminar"
            );

            formData.append(
                "id",
                String(contratoId)
            );

            const resposta =
                await requisicaoContratos(
                    CONTRATOS_TRABALHO_URL,
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
                    "Contrato eliminado",

                text:
                    resposta.mensagem
                    || "Contrato eliminado com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await carregarContratosFiltrados();

        } catch (erro) {

            mostrarErroContrato(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoContratos(false);

        }

    }


    // ==================================================
    // DETALHES
    // ==================================================

    function mostrarDetalhesContrato(
        contratoId
    ) {

        const contrato =
            contratosCache.find(
                function (item) {

                    return Number(item.id)
                        === Number(contratoId);

                }
            );

        if (!contrato) {

            mostrarErroContrato(
                "Contrato não encontrado."
            );

            return;

        }

        Swal.fire({
            icon:
                "info",

            title:
                escaparHtml(
                    contrato.numero_contrato
                    || "Contrato de Trabalho"
                ),

            width:
                950,

            html:
                `
                    <div class="text-left">

                        <div class="row">

                            ${campoDetalhe(
                    "Funcionário",
                    contrato.funcionario_nome || "-"
                )}

                            ${campoDetalhe(
                    "Tipo de contrato",
                    contrato.tipo_contrato_nome || "-"
                )}

                            ${campoDetalhe(
                    "Estado",
                    contrato.status_nome || "-"
                )}

                            ${campoDetalhe(
                    "Filial",
                    contrato.filial_nome || "-"
                )}

                            ${campoDetalhe(
                    "Departamento",
                    contrato.departamento_nome || "-"
                )}

                            ${campoDetalhe(
                    "Cargo",
                    contrato.cargo_nome || "-"
                )}

                            ${campoDetalhe(
                    "Data de início",
                    formatarData(
                        contrato.data_inicio
                    )
                )}

                            ${campoDetalhe(
                    "Data de fim",
                    contrato.data_fim
                        ? formatarData(
                            contrato.data_fim
                        )
                        : "Sem termo"
                )}

                            ${campoDetalhe(
                    "Salário base",
                    formatarMoeda(
                        contrato.salario_base,
                        contrato.moeda || "MZN"
                    )
                )}

                            ${campoDetalhe(
                    "Periodicidade",
                    formatarCodigo(
                        contrato.periodicidade_salario
                    )
                )}

                            ${campoDetalhe(
                    "Horas semanais",
                    contrato.horas_semanais
                        ? contrato.horas_semanais
                        + " horas"
                        : "-"
                )}

                            ${campoDetalhe(
                    "Renovação automática",
                    simNao(
                        contrato.renovacao_automatica
                    )
                )}

                            ${campoDetalhe(
                    "Renovações",
                    contrato.numero_renovacoes || 0
                )}

                            ${campoDetalhe(
                    "Próxima renovação",
                    contrato.data_proxima_renovacao
                        ? formatarData(
                            contrato.data_proxima_renovacao
                        )
                        : "-"
                )}

                        </div>

                        <hr>

                        <strong>Descrição das funções:</strong>

                        <div class="mt-2 mb-3">
                            ${textoLongo(
                    contrato.descricao_funcoes
                )}
                        </div>

                        <strong>Benefícios:</strong>

                        <div class="mt-2 mb-3">
                            ${textoLongo(
                    contrato.beneficios
                )}
                        </div>

                        <strong>Cláusulas especiais:</strong>

                        <div class="mt-2 mb-3">
                            ${textoLongo(
                    contrato.clausulas_especiais
                )}
                        </div>

                        <strong>Observações:</strong>

                        <div class="mt-2">
                            ${textoLongo(
                    contrato.observacoes
                )}
                        </div>

                    </div>
                `,

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // LIMPAR
    // ==================================================

    function limparFormularioContrato() {

        const formulario =
            $("#formContratoTrabalho")[0];

        if (formulario) {
            formulario.reset();
        }

        $("#contratoId")
            .val("");

        preencherCargosContrato(
            cargosCache
        );

        $("#contratoDataSuspensao")
            .prop("disabled", true);

        $("#contratoMotivoSuspensao")
            .prop("disabled", true);

        $("#contratoDataRescisao")
            .prop("disabled", true);

        $("#contratoMotivoRescisao")
            .prop("disabled", true);

        $("#contratoIniciativaRescisao")
            .prop("disabled", true);

        $("#contratoNumeroMaximoRenovacoes")
            .prop("disabled", true);

        $("#contratoDataProximaRenovacao")
            .prop("disabled", true);

        $("#formContratoTrabalho")
            .find(".is-invalid")
            .removeClass("is-invalid");

    }


    function limparFiltrosContratos() {

        $("#filtroContratoPesquisa")
            .val("");

        $("#filtroContratoFuncionarioId")
            .val("");

        $("#filtroContratoTipoId")
            .val("");

        $("#filtroContratoFilialId")
            .val("");

        $("#filtroContratoDepartamentoId")
            .val("");

        $("#filtroContratoStatusId")
            .val("");

        $("#filtroContratoDataInicio")
            .val("");

        $("#filtroContratoDataFim")
            .val("");

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoContratos(
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
                || json.detalhe
                || "Não foi possível processar o contrato."
            );

        }

        return json;

    }


    // ==================================================
    // INTERFACE
    // ==================================================

    function mostrarCarregamentoContratos(
        mostrar
    ) {

        $("#contratosTrabalhoCarregando")
            .toggle(Boolean(mostrar));

        $("#btnNovoContratoTrabalho")
            .prop(
                "disabled",
                Boolean(mostrar)
            );

        $("#btnFiltrarContratosTrabalho")
            .prop(
                "disabled",
                Boolean(mostrar)
            );

    }


    function bloquearModalContrato(
        bloquear
    ) {

        $("#formContratoTrabalho")
            .find(
                "input, textarea, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnGuardarContratoTrabalhoSpinner")
            .toggle(Boolean(bloquear));

    }


    function mostrarErroContrato(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível processar",

            text:
                mensagem
                || "Ocorreu um erro na gestão de contratos.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // VALIDAÇÕES DE DATAS
    // ==================================================

    function validarPeriodoContrato() {

        const inicio =
            textoCampo(
                "#contratoDataInicio"
            );

        const fim =
            textoCampo(
                "#contratoDataFim"
            );

        if (
            inicio
            && fim
            && fim < inicio
        ) {
            throw new Error(
                "A data final não pode ser anterior à inicial."
            );
        }

    }


    function validarPeriodoExperimentalContrato() {

        const inicio =
            textoCampo(
                "#contratoPeriodoExperimentalInicio"
            );

        const fim =
            textoCampo(
                "#contratoPeriodoExperimentalFim"
            );

        if (
            inicio
            && fim
            && fim < inicio
        ) {
            throw new Error(
                "O fim do período experimental não pode ser anterior ao início."
            );
        }

    }


    function validarPeriodoFiltroContrato() {

        const inicio =
            textoCampo(
                "#filtroContratoDataInicio"
            );

        const fim =
            textoCampo(
                "#filtroContratoDataFim"
            );

        if (
            inicio
            && fim
            && fim < inicio
        ) {
            throw new Error(
                "A data final do filtro não pode ser anterior à inicial."
            );
        }

    }


    // ==================================================
    // HELPERS
    // ==================================================

    function preencherSelectId(
        seletor,
        itens,
        textoVazio,
        obterTexto
    ) {

        const select =
            $(seletor);

        select.empty();

        select.append(`
            <option value="">
                ${escaparHtml(textoVazio)}
            </option>
        `);

        itens.forEach(
            function (item) {

                select.append(`
                    <option value="${Number(item.id)}">
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

        select.empty();

        select.append(`
            <option value="">
                ${escaparHtml(textoVazio)}
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
            $(seletor).val() || ""
        ).trim();

    }


    function valorOpcional(
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


    function inteiroOpcional(
        valor
    ) {

        const numero =
            Number(valor || 0);

        return Number.isInteger(numero)
            && numero > 0
            ? numero
            : null;

    }


    function inteiroNaoNegativo(
        valor
    ) {

        const numero =
            Number(valor || 0);

        return Number.isInteger(numero)
            && numero >= 0
            ? numero
            : 0;

    }


    function inteiroOpcionalNaoNegativo(
        valor
    ) {

        const texto =
            String(
                valor ?? ""
            ).trim();

        if (texto === "") {
            return null;
        }

        return inteiroNaoNegativo(
            texto
        );

    }


    function decimalCampo(
        valor
    ) {

        const numero =
            Number(
                String(
                    valor ?? "0"
                ).replace(",", ".")
            );

        return Number.isFinite(numero)
            && numero >= 0
            ? numero.toFixed(2)
            : "0.00";

    }


    function decimalOpcionalCampo(
        valor
    ) {

        const texto =
            String(
                valor ?? ""
            ).trim();

        return texto === ""
            ? null
            : decimalCampo(texto);

    }


    function formatarData(
        data
    ) {

        if (!data) {
            return "-";
        }

        const partes =
            String(data).split("-");

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

        return hora
            ? String(hora).substring(0, 5)
            : "-";

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


    function formatarCodigo(
        codigo
    ) {

        return String(
            codigo || "-"
        ).replace(
            /_/g,
            " "
        );

    }


    function classeStatusContrato(
        status
    ) {

        const texto =
            normalizarTexto(status);

        if (
            [
                "activo",
                "ativo",
                "activa",
                "ativa"
            ].includes(texto)
        ) {
            return "badge-success";
        }

        if (texto === "rascunho") {
            return "badge-secondary";
        }

        if (texto === "suspenso") {
            return "badge-warning";
        }

        if (
            [
                "terminado",
                "rescindido",
                "cancelado"
            ].includes(texto)
        ) {
            return "badge-danger";
        }

        return "badge-info";

    }


    function campoDetalhe(
        titulo,
        valor
    ) {

        return `
            <div class="col-md-6 mb-3">

                <strong>
                    ${escaparHtml(titulo)}:
                </strong>

                <br>

                ${escaparHtml(valor)}

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

        return escaparHtml(texto)
            .replace(
                /\n/g,
                "<br>"
            );

    }


    function simNao(
        valor
    ) {

        return Number(valor || 0) === 1
            ? "Sim"
            : "Não";

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


    function dataAtualIso() {

        const data =
            new Date();

        return formatarDataIsoObjeto(
            data
        );

    }


    function adicionarDiasIso(
        dataIso,
        dias
    ) {

        const data =
            criarDataIsoLocal(
                dataIso
            );

        data.setDate(
            data.getDate() + Number(dias)
        );

        return formatarDataIsoObjeto(
            data
        );

    }


    function adicionarMesesIso(
        dataIso,
        meses
    ) {

        const data =
            criarDataIsoLocal(
                dataIso
            );

        data.setMonth(
            data.getMonth() + Number(meses)
        );

        return formatarDataIsoObjeto(
            data
        );

    }


    function criarDataIsoLocal(
        dataIso
    ) {

        const partes =
            String(dataIso)
                .split("-")
                .map(Number);

        return new Date(
            partes[0],
            partes[1] - 1,
            partes[2]
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
            ).padStart(2, "0");

        const dia =
            String(
                data.getDate()
            ).padStart(2, "0");

        return `${ano}-${mes}-${dia}`;

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

})(jQuery);