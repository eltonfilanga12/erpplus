"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const TIPOS_CONTRATOS_URL =
        "/erpplus/app/ajax/tipos_contratos_ajax.php";

    let tiposContratosCache =
        [];

    let tabelaTiposContratos =
        null;
    let tipoContratoImportacaoToken = '';
    let tipoContratoImportacaoDados = null;
    let tipoContratoImportacaoProcessando = false;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaTiposContratosExiste()) {
            return;
        }

        configurarEventosTiposContratos();

        carregarDadosIniciaisTiposContratos()
            .catch(function (erro) {

                console.error(
                    "Erro ao iniciar a gestão dos tipos de contrato:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaTiposContratosExiste() {

        return (
            $("#tiposContratosTable").length > 0
            && $("#formTipoContrato").length > 0
            && $("#modalTipoContrato").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosTiposContratos() {

        $("#btnNovoTipoContrato").on(
            "click",
            function () {

                abrirModalNovoTipoContrato();

            }
        );

        $("#btnImportarTipoContrato").on("click", abrirImportacaoTipoContrato);
        $("#btnModeloExcelTipoContrato").on("click", baixarModeloExcelTipoContrato);
        $("#btnValidarExcelTipoContrato").on("click", validarImportacaoTipoContrato);
        $("#btnConfirmarImportacaoTipoContrato").on("click", confirmarImportacaoTipoContrato);
        $("#btnVoltarImportacaoTipoContrato").on("click", voltarImportacaoTipoContrato);
        $("#tipoContratoFicheiroExcel").on("change", alterarFicheiroExcelTipoContrato);

        $("#formTipoContrato").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarTipoContrato()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao guardar tipo de contrato:",
                            erro
                        );

                    });

            }
        );

        $("#tipoContratoCodigo").on(
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

        $("#tipoContratoDuracaoDeterminada").on(
            "change",
            function () {

                atualizarCamposDuracaoTipoContrato();

            }
        );

        $("#tipoContratoRenovacaoAutomatica").on(
            "change",
            function () {

                atualizarCamposRenovacaoTipoContrato();

            }
        );

        $("#tiposContratosTable").on(
            "click",
            ".btn-ver-tipo-contrato",
            function () {

                const tipoContratoId =
                    Number(
                        $(this).data("id")
                        || 0
                    );

                mostrarDetalhesTipoContrato(
                    tipoContratoId
                );

            }
        );

        $("#tiposContratosTable").on(
            "click",
            ".btn-editar-tipo-contrato",
            function () {

                const tipoContratoId =
                    Number(
                        $(this).data("id")
                        || 0
                    );

                abrirModalEditarTipoContrato(
                    tipoContratoId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao carregar tipo de contrato:",
                            erro
                        );

                    });

            }
        );

        $("#tiposContratosTable").on(
            "click",
            ".btn-eliminar-tipo-contrato",
            function () {

                const tipoContratoId =
                    Number(
                        $(this).data("id")
                        || 0
                    );

                confirmarEliminarTipoContrato(
                    tipoContratoId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao eliminar tipo de contrato:",
                            erro
                        );

                    });

            }
        );

        $("#modalTipoContrato").on(
            "hidden.bs.modal",
            function () {

                limparFormularioTipoContrato();

            }
        );

    }


    // Adicionar as funções auxiliares de importação:
    function abrirImportacaoTipoContrato() {
        tipoContratoImportacaoToken = '';
        tipoContratoImportacaoDados = null;
        tipoContratoImportacaoProcessando = false;

        const ficheiro = document.getElementById('tipoContratoFicheiroExcel');
        if (ficheiro) ficheiro.value = '';

        const label = document.querySelector('label[for="tipoContratoFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';

        $("#tipoContratoImportacaoPasso1").removeClass('d-none');
        $("#tipoContratoImportacaoPasso2").addClass('d-none');
        $("#btnValidarExcelTipoContrato").removeClass('d-none');
        $("#btnConfirmarImportacaoTipoContrato").addClass('d-none');
        $("#btnVoltarImportacaoTipoContrato").addClass('d-none');

        esconderErroImportacaoTipoContrato();
        esconderSucessoImportacaoTipoContrato();

        $('#modalImportarTipoContrato').modal('show');
    }

    function alterarFicheiroExcelTipoContrato(evento) {
        const ficheiro = evento.target.files?.[0];
        const label = document.querySelector('label[for="tipoContratoFicheiroExcel"]');
        if (label) {
            label.textContent = ficheiro ? ficheiro.name : 'Selecione o ficheiro';
        }
    }

    function baixarModeloExcelTipoContrato() {
        const url = TIPOS_CONTRATOS_URL + '?acao=baixar_modelo_excel&_=' + Date.now();
        window.location.href = url;
    }

    async function validarImportacaoTipoContrato() {
        if (tipoContratoImportacaoProcessando) return;

        esconderErroImportacaoTipoContrato();
        esconderSucessoImportacaoTipoContrato();

        const input = document.getElementById('tipoContratoFicheiroExcel');
        const ficheiro = input?.files?.[0];

        if (!ficheiro) {
            mostrarErroImportacaoTipoContrato('Selecione um ficheiro Excel antes de continuar.');
            return;
        }

        const extensao = ficheiro.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(extensao)) {
            mostrarErroImportacaoTipoContrato('O ficheiro deve estar no formato XLSX ou XLS.');
            return;
        }

        const botao = $("#btnValidarExcelTipoContrato");
        const htmlOriginal = botao.html();

        try {
            tipoContratoImportacaoProcessando = true;
            botao.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> A validar...');

            const formData = new FormData();
            formData.append('acao', 'validar_importacao');
            formData.append('ficheiro', ficheiro);

            const resposta = await requisicaoTiposContratos(TIPOS_CONTRATOS_URL, {
                method: 'POST',
                body: formData
            });

            tipoContratoImportacaoDados = resposta.data || {};
            tipoContratoImportacaoToken = String(tipoContratoImportacaoDados.token || '');

            if (!tipoContratoImportacaoToken) {
                throw new Error('O servidor não devolveu o token da importação.');
            }

            renderizarResultadoImportacaoTipoContrato(tipoContratoImportacaoDados);

            $("#tipoContratoImportacaoPasso1").addClass('d-none');
            $("#tipoContratoImportacaoPasso2").removeClass('d-none');
            $("#btnValidarExcelTipoContrato").addClass('d-none');
            $("#btnVoltarImportacaoTipoContrato").removeClass('d-none');

            const totalValidos = Number(tipoContratoImportacaoDados.total_validos || 0);
            if (totalValidos > 0) {
                $("#btnConfirmarImportacaoTipoContrato").removeClass('d-none');
            } else {
                $("#btnConfirmarImportacaoTipoContrato").addClass('d-none');
            }
        } catch (erro) {
            mostrarErroImportacaoTipoContrato(erro.message);
        } finally {
            tipoContratoImportacaoProcessando = false;
            botao.prop('disabled', false).html(htmlOriginal);
        }
    }

    function renderizarResultadoImportacaoTipoContrato(dados) {
        const validos = Array.isArray(dados.validos) ? dados.validos : [];
        const erros = Array.isArray(dados.erros) ? dados.erros : [];

        renderizarTiposValidosImportacao(validos);
        renderizarErrosImportacaoTipoContrato(erros);

        $("#tipoContratoImportacaoValidosContainer").toggleClass('d-none', validos.length === 0);
        $("#tipoContratoImportacaoErrosContainer").toggleClass('d-none', erros.length === 0);
    }

    function renderizarTiposValidosImportacao(validos) {
        const tbody = $("#tipoContratoImportacaoValidos");
        tbody.empty();

        if (validos.length === 0) {
            tbody.html(`<tr><td colspan="6" class="text-center text-muted">Nenhum tipo de contrato válido encontrado.</td></tr>`);
            return;
        }

        validos.forEach(item => {
            const preview = item.preview || {};
            tbody.append(`
            <tr>
                <td>${escaparHtmlTipoContrato(item.linha ?? '')}</td>
                <td><strong>${escaparHtmlTipoContrato(preview.codigo ?? '')}</strong></td>
                <td>${escaparHtmlTipoContrato(preview.nome ?? '')}</td>
                <td>${escaparHtmlTipoContrato(preview.duracao ?? '')}</td>
                <td>${escaparHtmlTipoContrato(preview.renovacao ?? 'Não')}</td>
                <td>${escaparHtmlTipoContrato(preview.inss ?? 'Sim')}</td>
            </tr>
        `);
        });
    }

    function renderizarErrosImportacaoTipoContrato(erros) {
        const tbody = $("#tipoContratoImportacaoErros");
        tbody.empty();

        if (erros.length === 0) {
            tbody.html(`<tr><td colspan="3" class="text-center text-muted">Nenhum erro encontrado.</td></tr>`);
            return;
        }

        erros.forEach(item => {
            const dados = item.dados || {};
            let mensagens = item.erros || ['Erro de validação.'];
            if (!Array.isArray(mensagens)) mensagens = [mensagens];

            const listaErros = mensagens.map(m => `<div>${escaparHtmlTipoContrato(m)}</div>`).join('');
            tbody.append(`
            <tr>
                <td>${escaparHtmlTipoContrato(item.linha ?? '-')}</td>
                <td>${escaparHtmlTipoContrato(dados.codigo ?? '')} - ${escaparHtmlTipoContrato(dados.nome ?? '')}</td>
                <td class="text-danger">${listaErros}</td>
            </tr>
        `);
        });
    }

    async function confirmarImportacaoTipoContrato() {
        if (tipoContratoImportacaoProcessando || !tipoContratoImportacaoToken) return;

        const totalValidos = Number(tipoContratoImportacaoDados?.total_validos || 0);
        if (totalValidos <= 0) return;

        const confirmacao = await Swal.fire({
            icon: 'question',
            title: 'Confirmar importação?',
            html: `Serão registados <strong>${totalValidos}</strong> tipos de contrato.`,
            showCancelButton: true,
            confirmButtonText: 'Sim, importar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacao.isConfirmed) return;

        const botao = $("#btnConfirmarImportacaoTipoContrato");
        const htmlOriginal = botao.html();

        try {
            tipoContratoImportacaoProcessando = true;
            botao.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> A importar...');

            const formData = new FormData();
            formData.append('acao', 'confirmar_importacao');
            formData.append('token', tipoContratoImportacaoToken);

            const resposta = await requisicaoTiposContratos(TIPOS_CONTRATOS_URL, {
                method: 'POST',
                body: formData
            });

            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: resposta.mensagem || 'Importação concluída com sucesso.',
                confirmButtonText: 'OK'
            });

            tipoContratoImportacaoToken = '';
            tipoContratoImportacaoDados = null;

            botao.addClass('d-none');
            await carregarDadosIniciaisTiposContratos();

            setTimeout(() => {
                $('#modalImportarTipoContrato').modal('hide');
            }, 1200);
        } catch (erro) {
            mostrarErroImportacaoTipoContrato(erro.message);
        } finally {
            tipoContratoImportacaoProcessando = false;
            botao.prop('disabled', false).html(htmlOriginal);
        }
    }

    function voltarImportacaoTipoContrato() {
        tipoContratoImportacaoToken = '';
        tipoContratoImportacaoDados = null;

        $("#tipoContratoImportacaoPasso2").addClass('d-none');
        $("#tipoContratoImportacaoPasso1").removeClass('d-none');
        $("#btnConfirmarImportacaoTipoContrato").addClass('d-none');
        $("#btnVoltarImportacaoTipoContrato").addClass('d-none');
        $("#btnValidarExcelTipoContrato").removeClass('d-none');

        const input = document.getElementById('tipoContratoFicheiroExcel');
        if (input) input.value = '';

        const label = document.querySelector('label[for="tipoContratoFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';

        esconderErroImportacaoTipoContrato();
    }

    function mostrarErroImportacaoTipoContrato(mensagem) {
        const el = $("#tipoContratoImportacaoErro");
        el.text(mensagem).removeClass('d-none');
    }

    function esconderErroImportacaoTipoContrato() {
        $("#tipoContratoImportacaoErro").addClass('d-none');
    }

    function mostrarSucessoImportacaoTipoContrato(mensagem) {
        const el = $("#tipoContratoImportacaoSucesso");
        el.text(mensagem).removeClass('d-none');
    }

    function esconderSucessoImportacaoTipoContrato() {
        $("#tipoContratoImportacaoSucesso").addClass('d-none');
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisTiposContratos() {

        mostrarCarregamentoTiposContratos(true);

        try {

            const resposta =
                await requisicaoTiposContratos(
                    TIPOS_CONTRATOS_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            tiposContratosCache =
                Array.isArray(
                    dados.tipos_contratos
                )
                    ? dados.tipos_contratos
                    : [];

            preencherStatusTipoContrato(
                dados.status || []
            );

            renderizarTabelaTiposContratos(
                tiposContratosCache
            );

            atualizarResumoTiposContratos(
                tiposContratosCache
            );

        } catch (erro) {

            mostrarErroTipoContrato(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoTiposContratos(false);

        }

    }


    // ==================================================
    // RECARREGAR
    // ==================================================

    async function recarregarTiposContratos() {

        const resposta =
            await requisicaoTiposContratos(
                TIPOS_CONTRATOS_URL
                + "?acao=listar"
            );

        tiposContratosCache =
            Array.isArray(
                resposta.data
            )
                ? resposta.data
                : [];

        renderizarTabelaTiposContratos(
            tiposContratosCache
        );

        atualizarResumoTiposContratos(
            tiposContratosCache
        );

    }


    // ==================================================
    // STATUS
    // ==================================================

    function preencherStatusTipoContrato(
        status
    ) {

        const select =
            $("#tipoContratoStatusId");

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
                        ${escaparHtmlTipoContrato(
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
                        normalizarTextoTipoContrato(
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
    // CAMPOS DE DURAÇÃO
    // ==================================================

    function atualizarCamposDuracaoTipoContrato() {

        const determinada =
            $("#tipoContratoDuracaoDeterminada")
                .is(":checked");

        $("#tipoContratoDuracaoMeses")
            .prop(
                "disabled",
                !determinada
            )
            .prop(
                "required",
                determinada
            );

        $("#tipoContratoRenovacaoAutomatica")
            .prop(
                "disabled",
                !determinada
            );

        $("#tipoContratoExigeDataFim")
            .prop(
                "checked",
                determinada
                    ? true
                    : $("#tipoContratoExigeDataFim")
                        .is(":checked")
            );

        if (!determinada) {

            $("#tipoContratoDuracaoMeses")
                .val("");

            $("#tipoContratoRenovacaoAutomatica")
                .prop("checked", false);

            $("#tipoContratoNumeroMaximoRenovacoes")
                .val("")
                .prop("disabled", true);

        }

        atualizarCamposRenovacaoTipoContrato();

    }


    // ==================================================
    // CAMPOS DE RENOVAÇÃO
    // ==================================================

    function atualizarCamposRenovacaoTipoContrato() {

        const determinada =
            $("#tipoContratoDuracaoDeterminada")
                .is(":checked");

        const renovacao =
            $("#tipoContratoRenovacaoAutomatica")
                .is(":checked");

        const habilitar =
            determinada && renovacao;

        $("#tipoContratoNumeroMaximoRenovacoes")
            .prop(
                "disabled",
                !habilitar
            )
            .prop(
                "required",
                habilitar
            );

        if (!habilitar) {

            $("#tipoContratoNumeroMaximoRenovacoes")
                .val("");

        }

    }


    // ==================================================
    // TABELA
    // ==================================================

    function renderizarTabelaTiposContratos(
        tipos
    ) {

        destruirDataTableTiposContratos();

        const tbody =
            $("#tiposContratosTable tbody");

        tbody.empty();

        if (!tipos.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="9"
                        class="text-center text-muted py-4">

                        Nenhum tipo de contrato registado.

                    </td>

                </tr>
            `);

            return;

        }

        tipos.forEach(
            function (tipo) {

                const statusNome =
                    String(
                        tipo.status_nome
                        || "-"
                    );

                const ativo =
                    [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        normalizarTextoTipoContrato(
                            statusNome
                        )
                    );

                const badgeStatus =
                    ativo
                        ? "badge-success"
                        : "badge-secondary";

                const duracao =
                    formatarDuracaoTipoContrato(
                        tipo
                    );

                const renovacao =
                    Number(
                        tipo.renovacao_automatica
                        || 0
                    ) === 1;

                tbody.append(`
                    <tr>

                        <td>
                            <strong>
                                ${escaparHtmlTipoContrato(
                    tipo.codigo || "-"
                )}
                            </strong>
                        </td>

                        <td>

                            ${escaparHtmlTipoContrato(
                    tipo.nome || "-"
                )}

                            ${tipo.descricao
                        ? `
                                        <div class="text-muted small">
                                            ${escaparHtmlTipoContrato(
                            tipo.descricao
                        )}
                                        </div>
                                    `
                        : ""
                    }

                        </td>

                        <td>
                            ${escaparHtmlTipoContrato(
                        duracao
                    )}
                        </td>

                        <td class="text-center">

                            ${renovacao
                        ? `
                                        <span class="badge badge-info">
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
                            ${tipo.periodo_experimental_dias
                        !== null
                        && tipo.periodo_experimental_dias
                        !== ""
                        ? Number(
                            tipo.periodo_experimental_dias
                        )
                        + " dias"
                        : "-"
                    }
                        </td>

                        <td class="text-center">

                            ${Number(
                        tipo.gera_ferias
                        || 0
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
                        tipo.sujeito_inss
                        || 0
                    ) === 1
                        ? "Sim"
                        : "Não"
                    }

                        </td>

                        <td>

                            <span class="badge ${badgeStatus}">
                                ${escaparHtmlTipoContrato(
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
                                        class="dropdown-item btn-ver-tipo-contrato"
                                        data-id="${Number(
                        tipo.id
                    )}">

                                        <i class="fa fa-eye m-r-5"></i>

                                        Ver detalhes

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item btn-editar-tipo-contrato"
                                        data-id="${Number(
                        tipo.id
                    )}">

                                        <i class="fa fa-pencil m-r-5"></i>

                                        Editar

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item text-danger btn-eliminar-tipo-contrato"
                                        data-id="${Number(
                        tipo.id
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

        iniciarDataTableTiposContratos();

    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function iniciarDataTableTiposContratos() {

        if (
            typeof $.fn.DataTable
            === "undefined"
        ) {
            return;
        }

        tabelaTiposContratos =
            $("#tiposContratosTable")
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
                            "Nenhum tipo de contrato encontrado",

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


    function destruirDataTableTiposContratos() {

        if (
            tabelaTiposContratos
            && typeof tabelaTiposContratos.destroy
            === "function"
        ) {
            tabelaTiposContratos.destroy();

            tabelaTiposContratos =
                null;
        }

        if (
            typeof $.fn.DataTable
            !== "undefined"
            && $.fn.DataTable.isDataTable(
                "#tiposContratosTable"
            )
        ) {
            $("#tiposContratosTable")
                .DataTable()
                .destroy();
        }

    }


    // ==================================================
    // RESUMO
    // ==================================================

    function atualizarResumoTiposContratos(
        tipos
    ) {

        const total =
            tipos.length;

        const ativos =
            tipos.filter(
                function (tipo) {

                    return [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        normalizarTextoTipoContrato(
                            tipo.status_nome
                            || ""
                        )
                    );

                }
            ).length;

        const determinados =
            tipos.filter(
                function (tipo) {

                    return Number(
                        tipo.duracao_determinada
                        || 0
                    ) === 1;

                }
            ).length;

        const sujeitosInss =
            tipos.filter(
                function (tipo) {

                    return Number(
                        tipo.sujeito_inss
                        || 0
                    ) === 1;

                }
            ).length;

        definirTextoTipoContrato(
            "#resumoTotalTiposContratos",
            total.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoTipoContrato(
            "#resumoTiposContratosAtivos",
            ativos.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoTipoContrato(
            "#resumoContratosDeterminados",
            determinados.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoTipoContrato(
            "#resumoContratosSujeitosInss",
            sujeitosInss.toLocaleString(
                "pt-PT"
            )
        );

    }


    // ==================================================
    // NOVO
    // ==================================================

    function abrirModalNovoTipoContrato() {

        limparFormularioTipoContrato();

        $("#modalTipoContratoTitulo")
            .text(
                "Novo Tipo de Contrato"
            );

        $("#btnGuardarTipoContratoTexto")
            .text(
                "Guardar Tipo de Contrato"
            );

        const statusAtivoId =
            Number(
                $("#tipoContratoStatusId")
                    .data(
                        "status-ativo-id"
                    )
                || 0
            );

        if (statusAtivoId > 0) {

            $("#tipoContratoStatusId")
                .val(
                    String(statusAtivoId)
                );

        }

        $("#tipoContratoPermiteTempoParcial")
            .prop("checked", true);

        $("#tipoContratoPermiteHorasExtras")
            .prop("checked", true);

        $("#tipoContratoGeraFerias")
            .prop("checked", true);

        $("#tipoContratoGeraSubsidioFerias")
            .prop("checked", true);

        $("#tipoContratoGeraSubsidioNatal")
            .prop("checked", true);

        $("#tipoContratoSujeitoInss")
            .prop("checked", true);

        $("#tipoContratoSujeitoIrps")
            .prop("checked", true);

        atualizarCamposDuracaoTipoContrato();

        $("#modalTipoContrato")
            .modal("show");

        setTimeout(
            function () {

                $("#tipoContratoCodigo")
                    .trigger("focus");

            },
            300
        );

    }


    // ==================================================
    // EDITAR
    // ==================================================

    async function abrirModalEditarTipoContrato(
        tipoContratoId
    ) {

        if (tipoContratoId <= 0) {

            mostrarErroTipoContrato(
                "Tipo de contrato inválido."
            );

            return;

        }

        mostrarCarregamentoTiposContratos(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "buscar",

                    id:
                        String(
                            tipoContratoId
                        )
                });

            const resposta =
                await requisicaoTiposContratos(
                    TIPOS_CONTRATOS_URL
                    + "?"
                    + parametros.toString()
                );

            const tipo =
                resposta.data || {};

            limparFormularioTipoContrato();

            $("#tipoContratoId")
                .val(
                    Number(
                        tipo.id || 0
                    )
                );

            $("#tipoContratoCodigo")
                .val(
                    tipo.codigo || ""
                );

            $("#tipoContratoNome")
                .val(
                    tipo.nome || ""
                );

            $("#tipoContratoDescricao")
                .val(
                    tipo.descricao || ""
                );

            $("#tipoContratoDuracaoDeterminada")
                .prop(
                    "checked",
                    Number(
                        tipo.duracao_determinada
                        || 0
                    ) === 1
                );

            $("#tipoContratoDuracaoMeses")
                .val(
                    tipo.duracao_meses
                    ?? ""
                );

            $("#tipoContratoPeriodoExperimentalDias")
                .val(
                    tipo.periodo_experimental_dias
                    ?? ""
                );

            $("#tipoContratoRenovacaoAutomatica")
                .prop(
                    "checked",
                    Number(
                        tipo.renovacao_automatica
                        || 0
                    ) === 1
                );

            $("#tipoContratoNumeroMaximoRenovacoes")
                .val(
                    tipo.numero_maximo_renovacoes
                    ?? ""
                );

            $("#tipoContratoExigeDataFim")
                .prop(
                    "checked",
                    Number(
                        tipo.exige_data_fim
                        || 0
                    ) === 1
                );

            $("#tipoContratoPermiteTempoParcial")
                .prop(
                    "checked",
                    Number(
                        tipo.permite_tempo_parcial
                        || 0
                    ) === 1
                );

            $("#tipoContratoPermiteHorasExtras")
                .prop(
                    "checked",
                    Number(
                        tipo.permite_horas_extras
                        || 0
                    ) === 1
                );

            $("#tipoContratoGeraFerias")
                .prop(
                    "checked",
                    Number(
                        tipo.gera_ferias
                        || 0
                    ) === 1
                );

            $("#tipoContratoGeraSubsidioFerias")
                .prop(
                    "checked",
                    Number(
                        tipo.gera_subsidio_ferias
                        || 0
                    ) === 1
                );

            $("#tipoContratoGeraSubsidioNatal")
                .prop(
                    "checked",
                    Number(
                        tipo.gera_subsidio_natal
                        || 0
                    ) === 1
                );

            $("#tipoContratoSujeitoInss")
                .prop(
                    "checked",
                    Number(
                        tipo.sujeito_inss
                        || 0
                    ) === 1
                );

            $("#tipoContratoSujeitoIrps")
                .prop(
                    "checked",
                    Number(
                        tipo.sujeito_irps
                        || 0
                    ) === 1
                );

            $("#tipoContratoAvisoPrevioDias")
                .val(
                    tipo.aviso_previo_dias
                    ?? ""
                );

            $("#tipoContratoStatusId")
                .val(
                    tipo.status_id
                        ? String(
                            tipo.status_id
                        )
                        : ""
                );

            atualizarCamposDuracaoTipoContrato();

            atualizarCamposRenovacaoTipoContrato();

            $("#modalTipoContratoTitulo")
                .text(
                    "Editar Tipo de Contrato"
                );

            $("#btnGuardarTipoContratoTexto")
                .text(
                    "Atualizar Tipo de Contrato"
                );

            $("#modalTipoContrato")
                .modal("show");

        } catch (erro) {

            mostrarErroTipoContrato(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoTiposContratos(false);

        }

    }


    // ==================================================
    // GUARDAR
    // ==================================================

    async function guardarTipoContrato() {

        const dados =
            obterDadosFormularioTipoContrato();

        validarFormularioTipoContrato(
            dados
        );

        bloquearModalTipoContrato(true);

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
                await requisicaoTiposContratos(
                    TIPOS_CONTRATOS_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            $("#modalTipoContrato")
                .modal("hide");

            await Swal.fire({
                icon:
                    "success",

                title:
                    dados.id > 0
                        ? "Tipo de contrato atualizado"
                        : "Tipo de contrato criado",

                text:
                    resposta.mensagem
                    || "Operação concluída com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await recarregarTiposContratos();

        } catch (erro) {

            mostrarErroTipoContrato(
                erro.message
            );

            throw erro;

        } finally {

            bloquearModalTipoContrato(false);

        }

    }


    // ==================================================
    // DADOS DO FORMULÁRIO
    // ==================================================

    function obterDadosFormularioTipoContrato() {

        return {
            id:
                Number(
                    $("#tipoContratoId")
                        .val()
                    || 0
                ),

            codigo:
                String(
                    $("#tipoContratoCodigo")
                        .val()
                    || ""
                )
                    .trim()
                    .toUpperCase(),

            nome:
                String(
                    $("#tipoContratoNome")
                        .val()
                    || ""
                ).trim(),

            descricao:
                String(
                    $("#tipoContratoDescricao")
                        .val()
                    || ""
                ).trim(),

            duracao_determinada:
                $("#tipoContratoDuracaoDeterminada")
                    .is(":checked"),

            duracao_meses:
                inteiroOpcionalTipoContrato(
                    $("#tipoContratoDuracaoMeses")
                        .val()
                ),

            periodo_experimental_dias:
                inteiroOpcionalPermitindoZeroTipoContrato(
                    $("#tipoContratoPeriodoExperimentalDias")
                        .val()
                ),

            renovacao_automatica:
                $("#tipoContratoRenovacaoAutomatica")
                    .is(":checked"),

            numero_maximo_renovacoes:
                inteiroOpcionalPermitindoZeroTipoContrato(
                    $("#tipoContratoNumeroMaximoRenovacoes")
                        .val()
                ),

            exige_data_fim:
                $("#tipoContratoExigeDataFim")
                    .is(":checked"),

            permite_tempo_parcial:
                $("#tipoContratoPermiteTempoParcial")
                    .is(":checked"),

            permite_horas_extras:
                $("#tipoContratoPermiteHorasExtras")
                    .is(":checked"),

            gera_ferias:
                $("#tipoContratoGeraFerias")
                    .is(":checked"),

            gera_subsidio_ferias:
                $("#tipoContratoGeraSubsidioFerias")
                    .is(":checked"),

            gera_subsidio_natal:
                $("#tipoContratoGeraSubsidioNatal")
                    .is(":checked"),

            sujeito_inss:
                $("#tipoContratoSujeitoInss")
                    .is(":checked"),

            sujeito_irps:
                $("#tipoContratoSujeitoIrps")
                    .is(":checked"),

            aviso_previo_dias:
                inteiroOpcionalPermitindoZeroTipoContrato(
                    $("#tipoContratoAvisoPrevioDias")
                        .val()
                ),

            status_id:
                Number(
                    $("#tipoContratoStatusId")
                        .val()
                    || 0
                )
        };

    }


    // ==================================================
    // VALIDAR FORMULÁRIO
    // ==================================================

    function validarFormularioTipoContrato(
        dados
    ) {

        if (dados.codigo === "") {

            throw new Error(
                "Informe o código do tipo de contrato."
            );

        }

        if (dados.codigo.length > 20) {

            throw new Error(
                "O código não pode exceder 20 caracteres."
            );

        }

        if (dados.nome === "") {

            throw new Error(
                "Informe o nome do tipo de contrato."
            );

        }

        if (dados.nome.length > 120) {

            throw new Error(
                "O nome não pode exceder 120 caracteres."
            );

        }

        if (
            dados.duracao_determinada
            && (
                dados.duracao_meses === null
                || dados.duracao_meses <= 0
            )
        ) {

            throw new Error(
                "Informe a duração do contrato em meses."
            );

        }

        if (
            dados.renovacao_automatica
            && !dados.duracao_determinada
        ) {

            throw new Error(
                "A renovação automática só pode ser aplicada a contratos de duração determinada."
            );

        }

        if (
            dados.renovacao_automatica
            && dados.numero_maximo_renovacoes === null
        ) {

            throw new Error(
                "Informe o número máximo de renovações."
            );

        }

        if (
            dados.periodo_experimental_dias !== null
            && dados.periodo_experimental_dias > 3650
        ) {

            throw new Error(
                "O período experimental é inválido."
            );

        }

        if (
            dados.aviso_previo_dias !== null
            && dados.aviso_previo_dias > 3650
        ) {

            throw new Error(
                "O aviso prévio informado é inválido."
            );

        }

        if (dados.status_id <= 0) {

            throw new Error(
                "Selecione o estado do tipo de contrato."
            );

        }

    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    async function confirmarEliminarTipoContrato(
        tipoContratoId
    ) {

        const tipo =
            tiposContratosCache.find(
                function (item) {

                    return Number(item.id)
                        === Number(
                            tipoContratoId
                        );

                }
            );

        if (!tipo) {

            mostrarErroTipoContrato(
                "Tipo de contrato não encontrado."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Eliminar tipo de contrato?",

                html:
                    `
                        <p>
                            Pretende eliminar:
                        </p>

                        <strong>
                            ${escaparHtmlTipoContrato(
                        tipo.codigo
                        + " - "
                        + tipo.nome
                    )}
                        </strong>

                        <p class="mt-3 mb-0">
                            A eliminação será bloqueada quando
                            existirem contratos de trabalho associados.
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

        mostrarCarregamentoTiposContratos(true);

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
                    tipoContratoId
                )
            );

            const resposta =
                await requisicaoTiposContratos(
                    TIPOS_CONTRATOS_URL,
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
                    "Tipo de contrato eliminado",

                text:
                    resposta.mensagem
                    || "Tipo de contrato eliminado com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await recarregarTiposContratos();

        } catch (erro) {

            mostrarErroTipoContrato(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoTiposContratos(false);

        }

    }


    // ==================================================
    // DETALHES
    // ==================================================

    function mostrarDetalhesTipoContrato(
        tipoContratoId
    ) {

        const tipo =
            tiposContratosCache.find(
                function (item) {

                    return Number(item.id)
                        === Number(
                            tipoContratoId
                        );

                }
            );

        if (!tipo) {

            mostrarErroTipoContrato(
                "Tipo de contrato não encontrado."
            );

            return;

        }

        Swal.fire({
            icon:
                "info",

            title:
                escaparHtmlTipoContrato(
                    tipo.nome || "Tipo de Contrato"
                ),

            width:
                760,

            html:
                `
                    <div class="text-left">

                        <div class="row">

                            ${campoDetalheTipoContrato(
                    "Código",
                    tipo.codigo || "-"
                )}

                            ${campoDetalheTipoContrato(
                    "Estado",
                    tipo.status_nome || "-"
                )}

                            ${campoDetalheTipoContrato(
                    "Duração",
                    formatarDuracaoTipoContrato(
                        tipo
                    )
                )}

                            ${campoDetalheTipoContrato(
                    "Período experimental",
                    tipo.periodo_experimental_dias
                        !== null
                        && tipo.periodo_experimental_dias !== ""
                        ? tipo.periodo_experimental_dias
                        + " dias"
                        : "-"
                )}

                            ${campoDetalheTipoContrato(
                    "Renovação automática",
                    simNaoTipoContrato(
                        tipo.renovacao_automatica
                    )
                )}

                            ${campoDetalheTipoContrato(
                    "Máximo de renovações",
                    tipo.numero_maximo_renovacoes
                    ?? "-"
                )}

                            ${campoDetalheTipoContrato(
                    "Exige data de fim",
                    simNaoTipoContrato(
                        tipo.exige_data_fim
                    )
                )}

                            ${campoDetalheTipoContrato(
                    "Tempo parcial",
                    simNaoTipoContrato(
                        tipo.permite_tempo_parcial
                    )
                )}

                            ${campoDetalheTipoContrato(
                    "Horas extras",
                    simNaoTipoContrato(
                        tipo.permite_horas_extras
                    )
                )}

                            ${campoDetalheTipoContrato(
                    "Gera férias",
                    simNaoTipoContrato(
                        tipo.gera_ferias
                    )
                )}

                            ${campoDetalheTipoContrato(
                    "Subsídio de férias",
                    simNaoTipoContrato(
                        tipo.gera_subsidio_ferias
                    )
                )}

                            ${campoDetalheTipoContrato(
                    "Subsídio de Natal",
                    simNaoTipoContrato(
                        tipo.gera_subsidio_natal
                    )
                )}

                            ${campoDetalheTipoContrato(
                    "Sujeito a INSS",
                    simNaoTipoContrato(
                        tipo.sujeito_inss
                    )
                )}

                            ${campoDetalheTipoContrato(
                    "Sujeito a IRPS",
                    simNaoTipoContrato(
                        tipo.sujeito_irps
                    )
                )}

                            ${campoDetalheTipoContrato(
                    "Aviso prévio",
                    tipo.aviso_previo_dias
                        !== null
                        && tipo.aviso_previo_dias !== ""
                        ? tipo.aviso_previo_dias
                        + " dias"
                        : "-"
                )}

                        </div>

                        <hr>

                        <strong>Descrição:</strong>

                        <div class="mt-2">
                            ${formatarTextoLongoTipoContrato(
                    tipo.descricao
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

    function limparFormularioTipoContrato() {

        const formulario =
            $("#formTipoContrato")[0];

        if (formulario) {
            formulario.reset();
        }

        $("#tipoContratoId")
            .val("");

        $("#tipoContratoDuracaoMeses")
            .prop("disabled", true);

        $("#tipoContratoRenovacaoAutomatica")
            .prop("disabled", true);

        $("#tipoContratoNumeroMaximoRenovacoes")
            .prop("disabled", true);

        $("#formTipoContrato")
            .find(".is-invalid")
            .removeClass("is-invalid");

        $("#modalTipoContratoTitulo")
            .text(
                "Novo Tipo de Contrato"
            );

        $("#btnGuardarTipoContratoTexto")
            .text(
                "Guardar Tipo de Contrato"
            );

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoTiposContratos(
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
                || "Não foi possível processar o tipo de contrato."
            );
        }

        return json;

    }


    // ==================================================
    // ESTADO DA INTERFACE
    // ==================================================

    function mostrarCarregamentoTiposContratos(
        mostrar
    ) {

        $("#tiposContratosCarregando")
            .toggle(
                Boolean(mostrar)
            );

        $("#btnNovoTipoContrato")
            .prop(
                "disabled",
                Boolean(mostrar)
            );

    }


    function bloquearModalTipoContrato(
        bloquear
    ) {

        $("#formTipoContrato")
            .find(
                "input, textarea, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnGuardarTipoContratoSpinner")
            .toggle(
                Boolean(bloquear)
            );

    }


    // ==================================================
    // ERROS
    // ==================================================

    function mostrarErroTipoContrato(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível processar",

            text:
                mensagem
                || "Ocorreu um erro na gestão dos tipos de contrato.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // HELPERS
    // ==================================================

    function formatarDuracaoTipoContrato(
        tipo
    ) {

        if (
            Number(
                tipo.duracao_determinada
                || 0
            ) !== 1
        ) {
            return "Indeterminada";
        }

        const meses =
            Number(
                tipo.duracao_meses
                || 0
            );

        return meses > 0
            ? meses + " meses"
            : "Determinada";

    }


    function simNaoTipoContrato(
        valor
    ) {

        return Number(
            valor || 0
        ) === 1
            ? "Sim"
            : "Não";

    }


    function campoDetalheTipoContrato(
        titulo,
        valor
    ) {

        return `
            <div class="col-md-6 mb-3">

                <strong>
                    ${escaparHtmlTipoContrato(
            titulo
        )}:
                </strong>

                <br>

                ${escaparHtmlTipoContrato(
            valor
        )}

            </div>
        `;

    }


    function inteiroOpcionalTipoContrato(
        valor
    ) {

        const texto =
            String(
                valor ?? ""
            ).trim();

        if (texto === "") {
            return null;
        }

        const numero =
            Number(texto);

        return Number.isInteger(numero)
            && numero > 0
            ? numero
            : null;

    }


    function inteiroOpcionalPermitindoZeroTipoContrato(
        valor
    ) {

        const texto =
            String(
                valor ?? ""
            ).trim();

        if (texto === "") {
            return null;
        }

        const numero =
            Number(texto);

        return Number.isInteger(numero)
            && numero >= 0
            ? numero
            : null;

    }


    function definirTextoTipoContrato(
        seletor,
        valor
    ) {

        $(seletor).text(valor);

    }


    function normalizarTextoTipoContrato(
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


    function formatarTextoLongoTipoContrato(
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

        return escaparHtmlTipoContrato(
            texto
        ).replace(
            /\n/g,
            "<br>"
        );

    }


    function escaparHtmlTipoContrato(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);