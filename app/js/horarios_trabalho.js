"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const HORARIOS_TRABALHO_URL =
        "/erpplus/app/ajax/horarios_trabalho_ajax.php";

    let horariosCache = [];

    let tabelaHorarios = null;
    let horarioImportacaoToken = '';
    let horarioImportacaoDados = null;
    let horarioImportacaoProcessando = false;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaHorariosExiste()) {
            return;
        }

        configurarEventosHorarios();

        carregarDadosIniciaisHorarios()
            .catch(function (erro) {

                console.error(
                    "Erro ao iniciar a gestão de horários:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaHorariosExiste() {

        return (
            $("#horariosTrabalhoTable").length > 0
            && $("#formHorarioTrabalho").length > 0
            && $("#modalHorarioTrabalho").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosHorarios() {

        $("#btnNovoHorarioTrabalho").on(
            "click",
            function () {

                abrirModalNovoHorario();

            }
        );

        $("#btnImportarHorarioTrabalho").on("click", abrirImportacaoHorario);
        $("#btnModeloExcelHorario").on("click", baixarModeloExcelHorario);
        $("#btnValidarExcelHorario").on("click", validarImportacaoHorario);
        $("#btnConfirmarImportacaoHorario").on("click", confirmarImportacaoHorario);
        $("#btnVoltarImportacaoHorario").on("click", voltarImportacaoHorario);
        $("#horarioFicheiroExcel").on("change", alterarFicheiroExcelHorario);

        $("#formHorarioTrabalho").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarHorario()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao guardar horário:",
                            erro
                        );

                    });

            }
        );

        $("#horarioCodigo").on(
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

        $(
            "#horarioHoraEntrada, "
            + "#horarioHoraSaida, "
            + "#horarioIntervaloInicio, "
            + "#horarioIntervaloFim"
        ).on(
            "change input",
            function () {

                atualizarCargaHorariaPrevista();

            }
        );

        $(
            "#horarioSegundaFeira, "
            + "#horarioTercaFeira, "
            + "#horarioQuartaFeira, "
            + "#horarioQuintaFeira, "
            + "#horarioSextaFeira, "
            + "#horarioSabado, "
            + "#horarioDomingo"
        ).on(
            "change",
            function () {

                atualizarCargaHorariaPrevista();

            }
        );

        $("#horarioPermiteTrabalhoNoturno").on(
            "change",
            function () {

                verificarHorarioNoturno();

            }
        );

        $("#horariosTrabalhoTable").on(
            "click",
            ".btn-ver-horario",
            function () {

                const horarioId =
                    Number(
                        $(this).data("id") || 0
                    );

                mostrarDetalhesHorario(
                    horarioId
                );

            }
        );

        $("#horariosTrabalhoTable").on(
            "click",
            ".btn-editar-horario",
            function () {

                const horarioId =
                    Number(
                        $(this).data("id") || 0
                    );

                abrirModalEditarHorario(
                    horarioId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao carregar horário:",
                            erro
                        );

                    });

            }
        );

        $("#horariosTrabalhoTable").on(
            "click",
            ".btn-eliminar-horario",
            function () {

                const horarioId =
                    Number(
                        $(this).data("id") || 0
                    );

                confirmarEliminarHorario(
                    horarioId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao eliminar horário:",
                            erro
                        );

                    });

            }
        );

        $("#modalHorarioTrabalho").on(
            "hidden.bs.modal",
            function () {

                limparFormularioHorario();

            }
        );

    }


    // Funções de suporte à importação:
    function abrirImportacaoHorario() {
        horarioImportacaoToken = '';
        horarioImportacaoDados = null;
        horarioImportacaoProcessando = false;

        const input = document.getElementById('horarioFicheiroExcel');
        if (input) input.value = '';

        const label = document.querySelector('label[for="horarioFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';

        $("#horarioImportacaoPasso1").removeClass('d-none');
        $("#horarioImportacaoPasso2").addClass('d-none');
        $("#btnValidarExcelHorario").removeClass('d-none');
        $("#btnConfirmarImportacaoHorario").addClass('d-none');
        $("#btnVoltarImportacaoHorario").addClass('d-none');

        esconderErroImportacaoHorario();
        $('#modalImportarHorarioTrabalho').modal('show');
    }

    function alterarFicheiroExcelHorario(evento) {
        const ficheiro = evento.target.files?.[0];
        const label = document.querySelector('label[for="horarioFicheiroExcel"]');
        if (label) {
            label.textContent = ficheiro ? ficheiro.name : 'Selecione o ficheiro';
        }
    }

    function baixarModeloExcelHorario() {
        window.location.href = HORARIOS_TRABALHO_URL + '?acao=baixar_modelo_excel&_=' + Date.now();
    }

    async function validarImportacaoHorario() {
        if (horarioImportacaoProcessando) return;
        esconderErroImportacaoHorario();

        const input = document.getElementById('horarioFicheiroExcel');
        const ficheiro = input?.files?.[0];

        if (!ficheiro) {
            mostrarErroImportacaoHorario('Selecione um ficheiro Excel antes de continuar.');
            return;
        }

        const extensao = ficheiro.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(extensao)) {
            mostrarErroImportacaoHorario('O ficheiro deve ser do tipo XLSX ou XLS.');
            return;
        }

        const botao = $("#btnValidarExcelHorario");
        const htmlOriginal = botao.html();

        try {
            horarioImportacaoProcessando = true;
            botao.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> A validar...');

            const formData = new FormData();
            formData.append('acao', 'validar_importacao');
            formData.append('ficheiro', ficheiro);

            const resposta = await requisicaoHorarios(HORARIOS_TRABALHO_URL, {
                method: 'POST',
                body: formData
            });

            horarioImportacaoDados = resposta.data || {};
            horarioImportacaoToken = String(horarioImportacaoDados.token || '');

            if (!horarioImportacaoToken) {
                throw new Error('Token de importação não gerado.');
            }

            renderizarResultadoImportacaoHorario(horarioImportacaoDados);

            $("#horarioImportacaoPasso1").addClass('d-none');
            $("#horarioImportacaoPasso2").removeClass('d-none');
            $("#btnValidarExcelHorario").addClass('d-none');
            $("#btnVoltarImportacaoHorario").removeClass('d-none');

            if (Number(horarioImportacaoDados.total_validos || 0) > 0) {
                $("#btnConfirmarImportacaoHorario").removeClass('d-none');
            } else {
                $("#btnConfirmarImportacaoHorario").addClass('d-none');
            }
        } catch (erro) {
            mostrarErroImportacaoHorario(erro.message);
        } finally {
            horarioImportacaoProcessando = false;
            botao.prop('disabled', false).html(htmlOriginal);
        }
    }

    function renderizarResultadoImportacaoHorario(dados) {
        const validos = Array.isArray(dados.validos) ? dados.validos : [];
        const erros = Array.isArray(dados.erros) ? dados.erros : [];

        const tbodyValidos = $("#horarioImportacaoValidos").empty();
        if (validos.length === 0) {
            tbodyValidos.html(`<tr><td colspan="6" class="text-center text-muted">Nenhum horário válido.</td></tr>`);
        } else {
            validos.forEach(item => {
                const p = item.preview || {};
                tbodyValidos.append(`
                <tr>
                    <td>${escaparHtmlHorario(item.linha)}</td>
                    <td><strong>${escaparHtmlHorario(p.codigo)}</strong></td>
                    <td>${escaparHtmlHorario(p.nome)}</td>
                    <td>${escaparHtmlHorario(p.periodo)}</td>
                    <td>${escaparHtmlHorario(p.carga_diaria)}</td>
                    <td>${escaparHtmlHorario(p.carga_semanal)}</td>
                </tr>
            `);
            });
        }

        const tbodyErros = $("#horarioImportacaoErros").empty();
        if (erros.length === 0) {
            $("#horarioImportacaoErrosContainer").addClass('d-none');
        } else {
            $("#horarioImportacaoErrosContainer").removeClass('d-none');
            erros.forEach(item => {
                const d = item.dados || {};
                const msgs = (item.erros || []).map(m => `<div>${escaparHtmlHorario(m)}</div>`).join('');
                tbodyErros.append(`
                <tr>
                    <td>${escaparHtmlHorario(item.linha)}</td>
                    <td>${escaparHtmlHorario(d.codigo)} - ${escaparHtmlHorario(d.nome)}</td>
                    <td class="text-danger">${msgs}</td>
                </tr>
            `);
            });
        }
    }

    async function confirmarImportacaoHorario() {
        if (horarioImportacaoProcessando || !horarioImportacaoToken) return;

        const total = Number(horarioImportacaoDados?.total_validos || 0);
        if (total <= 0) return;

        const confirmacao = await Swal.fire({
            icon: 'question',
            title: 'Confirmar importação?',
            html: `Deseja registar <strong>${total}</strong> horários de trabalho?`,
            showCancelButton: true,
            confirmButtonText: 'Sim, importar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacao.isConfirmed) return;

        const botao = $("#btnConfirmarImportacaoHorario");
        const htmlOriginal = botao.html();

        try {
            horarioImportacaoProcessando = true;
            botao.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> A importar...');

            const formData = new FormData();
            formData.append('acao', 'confirmar_importacao');
            formData.append('token', horarioImportacaoToken);

            const resposta = await requisicaoHorarios(HORARIOS_TRABALHO_URL, {
                method: 'POST',
                body: formData
            });

            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: resposta.mensagem || 'Horários importados com sucesso.',
                confirmButtonText: 'OK'
            });

            horarioImportacaoToken = '';
            horarioImportacaoDados = null;
            botao.addClass('d-none');

            await carregarDadosIniciaisHorarios();
            setTimeout(() => { $('#modalImportarHorarioTrabalho').modal('hide'); }, 1200);
        } catch (erro) {
            mostrarErroHorario(erro.message);
        } finally {
            horarioImportacaoProcessando = false;
            botao.prop('disabled', false).html(htmlOriginal);
        }
    }

    function voltarImportacaoHorario() {
        horarioImportacaoToken = '';
        horarioImportacaoDados = null;

        $("#horarioImportacaoPasso2").addClass('d-none');
        $("#horarioImportacaoPasso1").removeClass('d-none');
        $("#btnConfirmarImportacaoHorario").addClass('d-none');
        $("#btnVoltarImportacaoHorario").addClass('d-none');
        $("#btnValidarExcelHorario").removeClass('d-none');

        const input = document.getElementById('horarioFicheiroExcel');
        if (input) input.value = '';
        const label = document.querySelector('label[for="horarioFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';
        esconderErroImportacaoHorario();
    }

    function mostrarErroImportacaoHorario(msg) {
        $("#horarioImportacaoErro").text(msg).removeClass('d-none');
    }

    function esconderErroImportacaoHorario() {
        $("#horarioImportacaoErro").addClass('d-none');
    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisHorarios() {

        mostrarCarregamentoHorarios(true);

        try {

            const resposta =
                await requisicaoHorarios(
                    HORARIOS_TRABALHO_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            horariosCache =
                Array.isArray(
                    dados.horarios
                )
                    ? dados.horarios
                    : [];

            preencherStatusHorario(
                dados.status || []
            );

            renderizarTabelaHorarios(
                horariosCache
            );

            atualizarResumoHorarios(
                horariosCache
            );

        } catch (erro) {

            mostrarErroHorario(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoHorarios(false);

        }

    }


    // ==================================================
    // RECARREGAR
    // ==================================================

    async function recarregarHorarios() {

        const resposta =
            await requisicaoHorarios(
                HORARIOS_TRABALHO_URL
                + "?acao=listar"
            );

        horariosCache =
            Array.isArray(
                resposta.data
            )
                ? resposta.data
                : [];

        renderizarTabelaHorarios(
            horariosCache
        );

        atualizarResumoHorarios(
            horariosCache
        );

    }


    // ==================================================
    // STATUS
    // ==================================================

    function preencherStatusHorario(
        status
    ) {

        const select =
            $("#horarioStatusId");

        select.empty();

        select.append(`
            <option value="">
                Selecione o estado
            </option>
        `);

        status.forEach(
            function (item) {

                select.append(`
                    <option value="${Number(item.id)}">
                        ${escaparHtmlHorario(
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
                        normalizarTextoHorario(
                            item.nome || ""
                        )
                    );

                }
            );

        if (statusAtivo) {

            select.data(
                "status-ativo-id",
                Number(statusAtivo.id)
            );

        }

    }


    // ==================================================
    // TABELA
    // ==================================================

    function renderizarTabelaHorarios(
        horarios
    ) {

        destruirDataTableHorarios();

        const tbody =
            $("#horariosTrabalhoTable tbody");

        tbody.empty();

        if (!horarios.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="9"
                        class="text-center text-muted py-4">

                        Nenhum horário de trabalho registado.

                    </td>

                </tr>
            `);

            return;

        }

        horarios.forEach(
            function (horario) {

                const statusNome =
                    String(
                        horario.status_nome || "-"
                    );

                const ativo =
                    [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        normalizarTextoHorario(
                            statusNome
                        )
                    );

                const badgeStatus =
                    ativo
                        ? "badge-success"
                        : "badge-secondary";

                const periodo =
                    formatarPeriodoHorario(
                        horario.hora_entrada,
                        horario.hora_saida
                    );

                const dias =
                    formatarDiasTrabalho(
                        horario
                    );

                tbody.append(`
                    <tr>

                        <td>
                            <strong>
                                ${escaparHtmlHorario(
                    horario.codigo || "-"
                )}
                            </strong>
                        </td>

                        <td>

                            ${escaparHtmlHorario(
                    horario.nome || "-"
                )}

                            ${Number(
                    horario.horario_flexivel || 0
                ) === 1
                        ? `
                                        <span class="badge badge-info ml-1">
                                            Flexível
                                        </span>
                                    `
                        : ""
                    }

                            ${Number(
                        horario.permite_trabalho_noturno || 0
                    ) === 1
                        ? `
                                        <span class="badge badge-dark ml-1">
                                            Noturno
                                        </span>
                                    `
                        : ""
                    }

                            ${horario.descricao
                        ? `
                                        <div class="text-muted small">
                                            ${escaparHtmlHorario(
                            horario.descricao
                        )}
                                        </div>
                                    `
                        : ""
                    }

                        </td>

                        <td>
                            ${escaparHtmlHorario(
                        periodo
                    )}
                        </td>

                        <td>
                            ${escaparHtmlHorario(
                        formatarIntervaloHorario(
                            horario.intervalo_inicio,
                            horario.intervalo_fim
                        )
                    )}
                        </td>

                        <td>
                            ${escaparHtmlHorario(
                        dias
                    )}
                        </td>

                        <td class="text-center">

                            ${formatarNumeroHorario(
                        horario.carga_horaria_diaria
                    )} h

                        </td>

                        <td class="text-center">

                            ${formatarNumeroHorario(
                        horario.carga_horaria_semanal
                    )} h

                        </td>

                        <td>

                            <span class="badge ${badgeStatus}">
                                ${escaparHtmlHorario(
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
                                        class="dropdown-item btn-ver-horario"
                                        data-id="${Number(
                        horario.id
                    )}">

                                        <i class="fa fa-eye m-r-5"></i>

                                        Ver detalhes

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item btn-editar-horario"
                                        data-id="${Number(
                        horario.id
                    )}">

                                        <i class="fa fa-pencil m-r-5"></i>

                                        Editar

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item text-danger btn-eliminar-horario"
                                        data-id="${Number(
                        horario.id
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

        iniciarDataTableHorarios();

    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function iniciarDataTableHorarios() {

        if (
            typeof $.fn.DataTable
            === "undefined"
        ) {
            return;
        }

        tabelaHorarios =
            $("#horariosTrabalhoTable")
                .DataTable({
                    pageLength:
                        10,

                    order: [
                        [
                            1,
                            "asc"
                        ]
                    ],

                    columnDefs: [
                        {
                            targets:
                                8,

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
                            "Nenhum horário encontrado",

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


    function destruirDataTableHorarios() {

        if (
            tabelaHorarios
            && typeof tabelaHorarios.destroy
            === "function"
        ) {

            tabelaHorarios.destroy();

            tabelaHorarios = null;

        }

        if (
            typeof $.fn.DataTable
            !== "undefined"
            && $.fn.DataTable.isDataTable(
                "#horariosTrabalhoTable"
            )
        ) {

            $("#horariosTrabalhoTable")
                .DataTable()
                .destroy();

        }

    }


    // ==================================================
    // RESUMO
    // ==================================================

    function atualizarResumoHorarios(
        horarios
    ) {

        const total =
            horarios.length;

        const ativos =
            horarios.filter(
                function (horario) {

                    return [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        normalizarTextoHorario(
                            horario.status_nome || ""
                        )
                    );

                }
            ).length;

        const flexiveis =
            horarios.filter(
                function (horario) {

                    return Number(
                        horario.horario_flexivel || 0
                    ) === 1;

                }
            ).length;

        const noturnos =
            horarios.filter(
                function (horario) {

                    return Number(
                        horario.permite_trabalho_noturno || 0
                    ) === 1;

                }
            ).length;

        definirTextoHorario(
            "#resumoTotalHorarios",
            total.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoHorario(
            "#resumoHorariosAtivos",
            ativos.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoHorario(
            "#resumoHorariosFlexiveis",
            flexiveis.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoHorario(
            "#resumoHorariosNoturnos",
            noturnos.toLocaleString(
                "pt-PT"
            )
        );

    }


    // ==================================================
    // NOVO
    // ==================================================

    function abrirModalNovoHorario() {

        limparFormularioHorario();

        $("#modalHorarioTrabalhoTitulo")
            .text(
                "Novo Horário de Trabalho"
            );

        $("#btnGuardarHorarioTrabalhoTexto")
            .text(
                "Guardar Horário"
            );

        const statusAtivoId =
            Number(
                $("#horarioStatusId")
                    .data(
                        "status-ativo-id"
                    )
                || 0
            );

        if (statusAtivoId > 0) {

            $("#horarioStatusId")
                .val(
                    String(statusAtivoId)
                );

        }

        $("#horarioHoraEntrada")
            .val("08:00");

        $("#horarioHoraSaida")
            .val("17:00");

        $("#horarioIntervaloInicio")
            .val("12:00");

        $("#horarioIntervaloFim")
            .val("13:00");

        $("#horarioToleranciaEntrada")
            .val("10");

        $("#horarioToleranciaSaida")
            .val("10");

        marcarDiasUteisPadrao();

        $("#horarioPermiteHorasExtras")
            .prop("checked", true);

        $("#horarioPermiteTrabalhoNoturno")
            .prop("checked", false);

        $("#horarioFlexivel")
            .prop("checked", false);

        atualizarCargaHorariaPrevista();

        $("#modalHorarioTrabalho")
            .modal("show");

        setTimeout(
            function () {

                $("#horarioCodigo")
                    .trigger("focus");

            },
            300
        );

    }


    // ==================================================
    // EDITAR
    // ==================================================

    async function abrirModalEditarHorario(
        horarioId
    ) {

        if (horarioId <= 0) {

            mostrarErroHorario(
                "Horário de trabalho inválido."
            );

            return;

        }

        mostrarCarregamentoHorarios(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "buscar",

                    id:
                        String(horarioId)
                });

            const resposta =
                await requisicaoHorarios(
                    HORARIOS_TRABALHO_URL
                    + "?"
                    + parametros.toString()
                );

            const horario =
                resposta.data || {};

            limparFormularioHorario();

            $("#horarioId")
                .val(
                    Number(
                        horario.id || 0
                    )
                );

            $("#horarioCodigo")
                .val(
                    horario.codigo || ""
                );

            $("#horarioNome")
                .val(
                    horario.nome || ""
                );

            $("#horarioDescricao")
                .val(
                    horario.descricao || ""
                );

            $("#horarioHoraEntrada")
                .val(
                    formatarHoraInput(
                        horario.hora_entrada
                    )
                );

            $("#horarioHoraSaida")
                .val(
                    formatarHoraInput(
                        horario.hora_saida
                    )
                );

            $("#horarioIntervaloInicio")
                .val(
                    formatarHoraInput(
                        horario.intervalo_inicio
                    )
                );

            $("#horarioIntervaloFim")
                .val(
                    formatarHoraInput(
                        horario.intervalo_fim
                    )
                );

            $("#horarioToleranciaEntrada")
                .val(
                    Number(
                        horario.tolerancia_entrada_minutos || 0
                    )
                );

            $("#horarioToleranciaSaida")
                .val(
                    Number(
                        horario.tolerancia_saida_minutos || 0
                    )
                );

            $("#horarioSegundaFeira")
                .prop(
                    "checked",
                    Number(
                        horario.segunda_feira || 0
                    ) === 1
                );

            $("#horarioTercaFeira")
                .prop(
                    "checked",
                    Number(
                        horario.terca_feira || 0
                    ) === 1
                );

            $("#horarioQuartaFeira")
                .prop(
                    "checked",
                    Number(
                        horario.quarta_feira || 0
                    ) === 1
                );

            $("#horarioQuintaFeira")
                .prop(
                    "checked",
                    Number(
                        horario.quinta_feira || 0
                    ) === 1
                );

            $("#horarioSextaFeira")
                .prop(
                    "checked",
                    Number(
                        horario.sexta_feira || 0
                    ) === 1
                );

            $("#horarioSabado")
                .prop(
                    "checked",
                    Number(
                        horario.sabado || 0
                    ) === 1
                );

            $("#horarioDomingo")
                .prop(
                    "checked",
                    Number(
                        horario.domingo || 0
                    ) === 1
                );

            $("#horarioPermiteHorasExtras")
                .prop(
                    "checked",
                    Number(
                        horario.permite_horas_extras || 0
                    ) === 1
                );

            $("#horarioPermiteTrabalhoNoturno")
                .prop(
                    "checked",
                    Number(
                        horario.permite_trabalho_noturno || 0
                    ) === 1
                );

            $("#horarioFlexivel")
                .prop(
                    "checked",
                    Number(
                        horario.horario_flexivel || 0
                    ) === 1
                );

            $("#horarioStatusId")
                .val(
                    horario.status_id
                        ? String(
                            horario.status_id
                        )
                        : ""
                );

            atualizarCargaHorariaPrevista();

            $("#modalHorarioTrabalhoTitulo")
                .text(
                    "Editar Horário de Trabalho"
                );

            $("#btnGuardarHorarioTrabalhoTexto")
                .text(
                    "Atualizar Horário"
                );

            $("#modalHorarioTrabalho")
                .modal("show");

        } catch (erro) {

            mostrarErroHorario(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoHorarios(false);

        }

    }


    // ==================================================
    // GUARDAR
    // ==================================================

    async function guardarHorario() {

        const dados =
            obterDadosFormularioHorario();

        validarFormularioHorario(
            dados
        );

        bloquearModalHorario(true);

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
                    String(dados.id)
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
                await requisicaoHorarios(
                    HORARIOS_TRABALHO_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            $("#modalHorarioTrabalho")
                .modal("hide");

            await Swal.fire({
                icon:
                    "success",

                title:
                    dados.id > 0
                        ? "Horário atualizado"
                        : "Horário criado",

                text:
                    resposta.mensagem
                    || "Operação concluída com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await recarregarHorarios();

        } catch (erro) {

            mostrarErroHorario(
                erro.message
            );

            throw erro;

        } finally {

            bloquearModalHorario(false);

        }

    }


    // ==================================================
    // DADOS DO FORMULÁRIO
    // ==================================================

    function obterDadosFormularioHorario() {

        return {
            id:
                Number(
                    $("#horarioId").val() || 0
                ),

            codigo:
                String(
                    $("#horarioCodigo").val() || ""
                )
                    .trim()
                    .toUpperCase(),

            nome:
                String(
                    $("#horarioNome").val() || ""
                ).trim(),

            descricao:
                String(
                    $("#horarioDescricao").val() || ""
                ).trim(),

            hora_entrada:
                String(
                    $("#horarioHoraEntrada").val() || ""
                ).trim(),

            hora_saida:
                String(
                    $("#horarioHoraSaida").val() || ""
                ).trim(),

            intervalo_inicio:
                valorOpcionalHorario(
                    $("#horarioIntervaloInicio").val()
                ),

            intervalo_fim:
                valorOpcionalHorario(
                    $("#horarioIntervaloFim").val()
                ),

            tolerancia_entrada_minutos:
                inteiroNaoNegativoHorario(
                    $("#horarioToleranciaEntrada").val()
                ),

            tolerancia_saida_minutos:
                inteiroNaoNegativoHorario(
                    $("#horarioToleranciaSaida").val()
                ),

            segunda_feira:
                $("#horarioSegundaFeira")
                    .is(":checked"),

            terca_feira:
                $("#horarioTercaFeira")
                    .is(":checked"),

            quarta_feira:
                $("#horarioQuartaFeira")
                    .is(":checked"),

            quinta_feira:
                $("#horarioQuintaFeira")
                    .is(":checked"),

            sexta_feira:
                $("#horarioSextaFeira")
                    .is(":checked"),

            sabado:
                $("#horarioSabado")
                    .is(":checked"),

            domingo:
                $("#horarioDomingo")
                    .is(":checked"),

            permite_horas_extras:
                $("#horarioPermiteHorasExtras")
                    .is(":checked"),

            permite_trabalho_noturno:
                $("#horarioPermiteTrabalhoNoturno")
                    .is(":checked"),

            horario_flexivel:
                $("#horarioFlexivel")
                    .is(":checked"),

            status_id:
                Number(
                    $("#horarioStatusId").val() || 0
                )
        };

    }


    // ==================================================
    // VALIDAR FORMULÁRIO
    // ==================================================

    function validarFormularioHorario(
        dados
    ) {

        if (dados.codigo === "") {

            throw new Error(
                "Informe o código do horário."
            );

        }

        if (dados.codigo.length > 20) {

            throw new Error(
                "O código não pode exceder 20 caracteres."
            );

        }

        if (dados.nome === "") {

            throw new Error(
                "Informe o nome do horário."
            );

        }

        if (dados.nome.length > 120) {

            throw new Error(
                "O nome não pode exceder 120 caracteres."
            );

        }

        if (dados.hora_entrada === "") {

            throw new Error(
                "Informe a hora de entrada."
            );

        }

        if (dados.hora_saida === "") {

            throw new Error(
                "Informe a hora de saída."
            );

        }

        if (
            dados.hora_entrada
            === dados.hora_saida
        ) {

            throw new Error(
                "A hora de entrada não pode ser igual à hora de saída."
            );

        }

        if (
            (
                dados.intervalo_inicio === null
                && dados.intervalo_fim !== null
            )
            || (
                dados.intervalo_inicio !== null
                && dados.intervalo_fim === null
            )
        ) {

            throw new Error(
                "Informe o início e o fim do intervalo."
            );

        }

        const totalDias =
            [
                dados.segunda_feira,
                dados.terca_feira,
                dados.quarta_feira,
                dados.quinta_feira,
                dados.sexta_feira,
                dados.sabado,
                dados.domingo
            ]
                .filter(Boolean)
                .length;

        if (totalDias <= 0) {

            throw new Error(
                "Selecione pelo menos um dia de trabalho."
            );

        }

        const atravessaMeiaNoite =
            horaParaMinutosHorario(
                dados.hora_saida
            )
            <=
            horaParaMinutosHorario(
                dados.hora_entrada
            );

        if (
            atravessaMeiaNoite
            && !dados.permite_trabalho_noturno
        ) {

            throw new Error(
                "Horários que atravessam a meia-noite devem permitir trabalho noturno."
            );

        }

        if (
            dados.tolerancia_entrada_minutos > 1440
            || dados.tolerancia_saida_minutos > 1440
        ) {

            throw new Error(
                "A tolerância não pode exceder 1440 minutos."
            );

        }

        if (dados.status_id <= 0) {

            throw new Error(
                "Selecione o estado do horário."
            );

        }

    }


    // ==================================================
    // CÁLCULO PREVISTO
    // ==================================================

    function atualizarCargaHorariaPrevista() {

        const entrada =
            String(
                $("#horarioHoraEntrada").val() || ""
            );

        const saida =
            String(
                $("#horarioHoraSaida").val() || ""
            );

        const intervaloInicio =
            String(
                $("#horarioIntervaloInicio").val() || ""
            );

        const intervaloFim =
            String(
                $("#horarioIntervaloFim").val() || ""
            );

        if (
            entrada === ""
            || saida === ""
        ) {

            definirTextoHorario(
                "#horarioCargaDiariaCalculada",
                "0 h"
            );

            definirTextoHorario(
                "#horarioCargaSemanalCalculada",
                "0 h"
            );

            return;

        }

        try {

            const cargaDiaria =
                calcularCargaDiariaHorario(
                    entrada,
                    saida,
                    intervaloInicio || null,
                    intervaloFim || null
                );

            const dias =
                contarDiasSelecionadosHorario();

            const cargaSemanal =
                cargaDiaria * dias;

            definirTextoHorario(
                "#horarioCargaDiariaCalculada",
                formatarNumeroHorario(
                    cargaDiaria
                )
                + " h"
            );

            definirTextoHorario(
                "#horarioCargaSemanalCalculada",
                formatarNumeroHorario(
                    cargaSemanal
                )
                + " h"
            );

            verificarHorarioNoturno();

        } catch (erro) {

            definirTextoHorario(
                "#horarioCargaDiariaCalculada",
                "-"
            );

            definirTextoHorario(
                "#horarioCargaSemanalCalculada",
                "-"
            );

        }

    }


    function calcularCargaDiariaHorario(
        entrada,
        saida,
        intervaloInicio,
        intervaloFim
    ) {

        let minutosEntrada =
            horaParaMinutosHorario(
                entrada
            );

        let minutosSaida =
            horaParaMinutosHorario(
                saida
            );

        if (
            minutosSaida <= minutosEntrada
        ) {
            minutosSaida += 1440;
        }

        let minutosIntervalo = 0;

        if (
            intervaloInicio
            && intervaloFim
        ) {

            let inicio =
                horaParaMinutosHorario(
                    intervaloInicio
                );

            let fim =
                horaParaMinutosHorario(
                    intervaloFim
                );

            if (inicio < minutosEntrada) {
                inicio += 1440;
            }

            if (fim <= inicio) {
                fim += 1440;
            }

            if (
                inicio < minutosEntrada
                || fim > minutosSaida
            ) {
                throw new Error(
                    "O intervalo está fora da jornada."
                );
            }

            minutosIntervalo =
                fim - inicio;

        }

        const minutosLiquidos =
            minutosSaida
            - minutosEntrada
            - minutosIntervalo;

        if (minutosLiquidos <= 0) {

            throw new Error(
                "Carga horária inválida."
            );

        }

        return Number(
            (
                minutosLiquidos / 60
            ).toFixed(2)
        );

    }


    function verificarHorarioNoturno() {

        const entrada =
            String(
                $("#horarioHoraEntrada").val() || ""
            );

        const saida =
            String(
                $("#horarioHoraSaida").val() || ""
            );

        if (
            entrada === ""
            || saida === ""
        ) {
            return;
        }

        const atravessaMeiaNoite =
            horaParaMinutosHorario(saida)
            <=
            horaParaMinutosHorario(entrada);

        $("#avisoHorarioNoturno")
            .toggle(
                atravessaMeiaNoite
                && !$("#horarioPermiteTrabalhoNoturno")
                    .is(":checked")
            );

    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    async function confirmarEliminarHorario(
        horarioId
    ) {

        const horario =
            horariosCache.find(
                function (item) {

                    return Number(item.id)
                        === Number(horarioId);

                }
            );

        if (!horario) {

            mostrarErroHorario(
                "Horário de trabalho não encontrado."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Eliminar horário?",

                html:
                    `
                        <p>
                            Pretende eliminar:
                        </p>

                        <strong>
                            ${escaparHtmlHorario(
                        horario.codigo
                        + " - "
                        + horario.nome
                    )}
                        </strong>

                        <p class="mt-3 mb-0">
                            A operação será bloqueada quando
                            existirem funcionários associados.
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

        mostrarCarregamentoHorarios(true);

        try {

            const formData =
                new FormData();

            formData.append(
                "acao",
                "eliminar"
            );

            formData.append(
                "id",
                String(horarioId)
            );

            const resposta =
                await requisicaoHorarios(
                    HORARIOS_TRABALHO_URL,
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
                    "Horário eliminado",

                text:
                    resposta.mensagem
                    || "Horário eliminado com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await recarregarHorarios();

        } catch (erro) {

            mostrarErroHorario(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoHorarios(false);

        }

    }


    // ==================================================
    // DETALHES
    // ==================================================

    function mostrarDetalhesHorario(
        horarioId
    ) {

        const horario =
            horariosCache.find(
                function (item) {

                    return Number(item.id)
                        === Number(horarioId);

                }
            );

        if (!horario) {

            mostrarErroHorario(
                "Horário de trabalho não encontrado."
            );

            return;

        }

        Swal.fire({
            icon:
                "info",

            title:
                escaparHtmlHorario(
                    horario.nome
                    || "Horário de Trabalho"
                ),

            width:
                760,

            html:
                `
                    <div class="text-left">

                        <div class="row">

                            ${campoDetalheHorario(
                    "Código",
                    horario.codigo || "-"
                )}

                            ${campoDetalheHorario(
                    "Estado",
                    horario.status_nome || "-"
                )}

                            ${campoDetalheHorario(
                    "Período",
                    formatarPeriodoHorario(
                        horario.hora_entrada,
                        horario.hora_saida
                    )
                )}

                            ${campoDetalheHorario(
                    "Intervalo",
                    formatarIntervaloHorario(
                        horario.intervalo_inicio,
                        horario.intervalo_fim
                    )
                )}

                            ${campoDetalheHorario(
                    "Carga diária",
                    formatarNumeroHorario(
                        horario.carga_horaria_diaria
                    ) + " horas"
                )}

                            ${campoDetalheHorario(
                    "Carga semanal",
                    formatarNumeroHorario(
                        horario.carga_horaria_semanal
                    ) + " horas"
                )}

                            ${campoDetalheHorario(
                    "Dias de trabalho",
                    formatarDiasTrabalho(
                        horario
                    )
                )}

                            ${campoDetalheHorario(
                    "Tolerância de entrada",
                    Number(
                        horario.tolerancia_entrada_minutos || 0
                    ) + " minutos"
                )}

                            ${campoDetalheHorario(
                    "Tolerância de saída",
                    Number(
                        horario.tolerancia_saida_minutos || 0
                    ) + " minutos"
                )}

                            ${campoDetalheHorario(
                    "Horas extras",
                    simNaoHorario(
                        horario.permite_horas_extras
                    )
                )}

                            ${campoDetalheHorario(
                    "Trabalho noturno",
                    simNaoHorario(
                        horario.permite_trabalho_noturno
                    )
                )}

                            ${campoDetalheHorario(
                    "Horário flexível",
                    simNaoHorario(
                        horario.horario_flexivel
                    )
                )}

                        </div>

                        <hr>

                        <strong>Descrição:</strong>

                        <div class="mt-2">
                            ${formatarTextoLongoHorario(
                    horario.descricao
                )}
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

    function limparFormularioHorario() {

        const formulario =
            $("#formHorarioTrabalho")[0];

        if (formulario) {
            formulario.reset();
        }

        $("#horarioId")
            .val("");

        marcarDiasUteisPadrao();

        $("#horarioPermiteHorasExtras")
            .prop("checked", true);

        $("#horarioPermiteTrabalhoNoturno")
            .prop("checked", false);

        $("#horarioFlexivel")
            .prop("checked", false);

        $("#horarioCargaDiariaCalculada")
            .text("0 h");

        $("#horarioCargaSemanalCalculada")
            .text("0 h");

        $("#avisoHorarioNoturno")
            .hide();

        $("#formHorarioTrabalho")
            .find(".is-invalid")
            .removeClass("is-invalid");

        $("#modalHorarioTrabalhoTitulo")
            .text(
                "Novo Horário de Trabalho"
            );

        $("#btnGuardarHorarioTrabalhoTexto")
            .text(
                "Guardar Horário"
            );

    }


    function marcarDiasUteisPadrao() {

        $("#horarioSegundaFeira")
            .prop("checked", true);

        $("#horarioTercaFeira")
            .prop("checked", true);

        $("#horarioQuartaFeira")
            .prop("checked", true);

        $("#horarioQuintaFeira")
            .prop("checked", true);

        $("#horarioSextaFeira")
            .prop("checked", true);

        $("#horarioSabado")
            .prop("checked", false);

        $("#horarioDomingo")
            .prop("checked", false);

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoHorarios(
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
                || "Não foi possível processar o horário."
            );

        }

        return json;

    }


    // ==================================================
    // ESTADO DA INTERFACE
    // ==================================================

    function mostrarCarregamentoHorarios(
        mostrar
    ) {

        $("#horariosTrabalhoCarregando")
            .toggle(
                Boolean(mostrar)
            );

        $("#btnNovoHorarioTrabalho")
            .prop(
                "disabled",
                Boolean(mostrar)
            );

    }


    function bloquearModalHorario(
        bloquear
    ) {

        $("#formHorarioTrabalho")
            .find(
                "input, textarea, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnGuardarHorarioTrabalhoSpinner")
            .toggle(
                Boolean(bloquear)
            );

    }


    // ==================================================
    // ERRO
    // ==================================================

    function mostrarErroHorario(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível processar",

            text:
                mensagem
                || "Ocorreu um erro na gestão de horários.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // HELPERS
    // ==================================================

    function contarDiasSelecionadosHorario() {

        return [
            "#horarioSegundaFeira",
            "#horarioTercaFeira",
            "#horarioQuartaFeira",
            "#horarioQuintaFeira",
            "#horarioSextaFeira",
            "#horarioSabado",
            "#horarioDomingo"
        ]
            .filter(
                function (seletor) {

                    return $(seletor)
                        .is(":checked");

                }
            )
            .length;

    }


    function formatarDiasTrabalho(
        horario
    ) {

        const dias = [];

        if (Number(horario.segunda_feira || 0) === 1) {
            dias.push("Seg");
        }

        if (Number(horario.terca_feira || 0) === 1) {
            dias.push("Ter");
        }

        if (Number(horario.quarta_feira || 0) === 1) {
            dias.push("Qua");
        }

        if (Number(horario.quinta_feira || 0) === 1) {
            dias.push("Qui");
        }

        if (Number(horario.sexta_feira || 0) === 1) {
            dias.push("Sex");
        }

        if (Number(horario.sabado || 0) === 1) {
            dias.push("Sáb");
        }

        if (Number(horario.domingo || 0) === 1) {
            dias.push("Dom");
        }

        return dias.length
            ? dias.join(", ")
            : "-";

    }


    function formatarPeriodoHorario(
        entrada,
        saida
    ) {

        return (
            formatarHoraVisivel(entrada)
            + " - "
            + formatarHoraVisivel(saida)
        );

    }


    function formatarIntervaloHorario(
        inicio,
        fim
    ) {

        if (!inicio || !fim) {
            return "Sem intervalo";
        }

        return (
            formatarHoraVisivel(inicio)
            + " - "
            + formatarHoraVisivel(fim)
        );

    }


    function formatarHoraVisivel(
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


    function horaParaMinutosHorario(
        hora
    ) {

        const partes =
            String(hora)
                .split(":");

        const horas =
            Number(
                partes[0] || 0
            );

        const minutos =
            Number(
                partes[1] || 0
            );

        return (
            horas * 60
            + minutos
        );

    }


    function valorOpcionalHorario(
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


    function inteiroNaoNegativoHorario(
        valor
    ) {

        const numero =
            Number(
                valor || 0
            );

        if (
            !Number.isInteger(numero)
            || numero < 0
        ) {
            return 0;
        }

        return numero;

    }


    function formatarNumeroHorario(
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


    function simNaoHorario(
        valor
    ) {

        return Number(
            valor || 0
        ) === 1
            ? "Sim"
            : "Não";

    }


    function campoDetalheHorario(
        titulo,
        valor
    ) {

        return `
            <div class="col-md-6 mb-3">

                <strong>
                    ${escaparHtmlHorario(
            titulo
        )}:
                </strong>

                <br>

                ${escaparHtmlHorario(
            valor
        )}

            </div>
        `;

    }


    function formatarTextoLongoHorario(
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

        return escaparHtmlHorario(
            texto
        ).replace(
            /\n/g,
            "<br>"
        );

    }


    function definirTextoHorario(
        seletor,
        valor
    ) {

        $(seletor).text(valor);

    }


    function normalizarTextoHorario(
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


    function escaparHtmlHorario(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);