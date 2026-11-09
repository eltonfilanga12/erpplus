"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const CARGOS_URL =
        "/erpplus/app/ajax/cargos_ajax.php";

    let cargosCache =
        [];

    let tabelaCargos =
        null;

    let cargoImportacaoToken = '';
    let cargoImportacaoDados = null;
    let cargoImportacaoProcessando = false;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaCargosExiste()) {
            return;
        }

        configurarEventosCargos();

        carregarDadosIniciaisCargos()
            .catch(function (erro) {

                console.error(
                    "Erro ao iniciar a gestão de cargos:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaCargosExiste() {

        return (
            $("#cargosTable").length > 0
            && $("#formCargo").length > 0
            && $("#modalCargo").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosCargos() {

        $("#btnNovoCargo").on(
            "click",
            function () {

                abrirModalNovoCargo();

            }
        );

        $("#btnImportarCargo").on("click", abrirImportacaoCargo);
        $("#btnModeloExcelCargo").on("click", baixarModeloExcelCargo);
        $("#btnValidarExcelCargo").on("click", validarImportacaoCargo);
        $("#btnConfirmarImportacaoCargo").on("click", confirmarImportacaoCargo);
        $("#btnVoltarImportacaoCargo").on("click", voltarImportacaoCargo);
        $("#cargoFicheiroExcel").on("change", alterarFicheiroExcelCargo);

        $("#formCargo").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarCargo()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao guardar cargo:",
                            erro
                        );

                    });

            }
        );

        $("#cargoCodigo").on(
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

                $(this).val(
                    valor
                );

            }
        );

        $("#cargoSalarioMinimo, #cargoSalarioMaximo, #cargoCargaHorariaSemanal")
            .on(
                "input",
                function () {

                    normalizarCampoDecimalCargo(
                        $(this)
                    );

                }
            );

        $("#cargoDepartamentoId").on(
            "change",
            function () {

                sugerirCentroCustoDoDepartamento();

            }
        );

        $("#cargosTable").on(
            "click",
            ".btn-ver-cargo",
            function () {

                const cargoId =
                    Number(
                        $(this).data("id")
                        || 0
                    );

                mostrarDetalhesCargo(
                    cargoId
                );

            }
        );

        $("#cargosTable").on(
            "click",
            ".btn-editar-cargo",
            function () {

                const cargoId =
                    Number(
                        $(this).data("id")
                        || 0
                    );

                abrirModalEditarCargo(
                    cargoId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao carregar cargo:",
                            erro
                        );

                    });

            }
        );

        $("#cargosTable").on(
            "click",
            ".btn-eliminar-cargo",
            function () {

                const cargoId =
                    Number(
                        $(this).data("id")
                        || 0
                    );

                confirmarEliminarCargo(
                    cargoId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao eliminar cargo:",
                            erro
                        );

                    });

            }
        );

        $("#modalCargo").on(
            "hidden.bs.modal",
            function () {

                limparFormularioCargo();

            }
        );

    }


    // Adicionar as funções auxiliares de importação:
    function abrirImportacaoCargo() {
        cargoImportacaoToken = '';
        cargoImportacaoDados = null;
        cargoImportacaoProcessando = false;

        const ficheiro = document.getElementById('cargoFicheiroExcel');
        if (ficheiro) ficheiro.value = '';

        const label = document.querySelector('label[for="cargoFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';

        $("#cargoImportacaoPasso1").removeClass('d-none');
        $("#cargoImportacaoPasso2").addClass('d-none');
        $("#btnValidarExcelCargo").removeClass('d-none');
        $("#btnConfirmarImportacaoCargo").addClass('d-none');
        $("#btnVoltarImportacaoCargo").addClass('d-none');

        esconderErroImportacaoCargo();
        esconderSucessoImportacaoCargo();

        $('#modalImportarCargo').modal('show');
    }

    function alterarFicheiroExcelCargo(evento) {
        const ficheiro = evento.target.files?.[0];
        const label = document.querySelector('label[for="cargoFicheiroExcel"]');
        if (label) {
            label.textContent = ficheiro ? ficheiro.name : 'Selecione o ficheiro';
        }
    }

    function baixarModeloExcelCargo() {
        const url = CARGOS_URL + '?acao=baixar_modelo_excel&_=' + Date.now();
        window.location.href = url;
    }

    async function validarImportacaoCargo() {
        if (cargoImportacaoProcessando) return;

        esconderErroImportacaoCargo();
        esconderSucessoImportacaoCargo();

        const input = document.getElementById('cargoFicheiroExcel');
        const ficheiro = input?.files?.[0];

        if (!ficheiro) {
            mostrarErroImportacaoCargo('Selecione um ficheiro Excel antes de continuar.');
            return;
        }

        const extensao = ficheiro.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(extensao)) {
            mostrarErroImportacaoCargo('O ficheiro deve estar no formato XLSX ou XLS.');
            return;
        }

        const botao = $("#btnValidarExcelCargo");
        const htmlOriginal = botao.html();

        try {
            cargoImportacaoProcessando = true;
            botao.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> A validar...');

            const formData = new FormData();
            formData.append('acao', 'validar_importacao');
            formData.append('ficheiro', ficheiro);

            const resposta = await requisicaoCargos(CARGOS_URL, {
                method: 'POST',
                body: formData
            });

            cargoImportacaoDados = resposta.data || {};
            cargoImportacaoToken = String(cargoImportacaoDados.token || '');

            if (!cargoImportacaoToken) {
                throw new Error('O servidor não devolveu o token da importação.');
            }

            renderizarResultadoImportacaoCargo(cargoImportacaoDados);

            $("#cargoImportacaoPasso1").addClass('d-none');
            $("#cargoImportacaoPasso2").removeClass('d-none');
            $("#btnValidarExcelCargo").addClass('d-none');
            $("#btnVoltarImportacaoCargo").removeClass('d-none');

            const totalValidos = Number(cargoImportacaoDados.total_validos || 0);
            if (totalValidos > 0) {
                $("#btnConfirmarImportacaoCargo").removeClass('d-none');
            } else {
                $("#btnConfirmarImportacaoCargo").addClass('d-none');
            }
        } catch (erro) {
            mostrarErroImportacaoCargo(erro.message);
        } finally {
            cargoImportacaoProcessando = false;
            botao.prop('disabled', false).html(htmlOriginal);
        }
    }

    function renderizarResultadoImportacaoCargo(dados) {
        const validos = Array.isArray(dados.validos) ? dados.validos : [];
        const erros = Array.isArray(dados.erros) ? dados.erros : [];

        renderizarCargosValidosImportacao(validos);
        renderizarErrosImportacaoCargo(erros);

        $("#cargoImportacaoValidosContainer").toggleClass('d-none', validos.length === 0);
        $("#cargoImportacaoErrosContainer").toggleClass('d-none', erros.length === 0);
    }

    function renderizarCargosValidosImportacao(validos) {
        const tbody = $("#cargoImportacaoValidos");
        tbody.empty();

        if (validos.length === 0) {
            tbody.html(`<tr><td colspan="6" class="text-center text-muted">Nenhum cargo válido encontrado.</td></tr>`);
            return;
        }

        validos.forEach(item => {
            const preview = item.preview || {};
            tbody.append(`
            <tr>
                <td>${escaparHtmlCargo(item.linha ?? '')}</td>
                <td><strong>${escaparHtmlCargo(preview.codigo ?? '')}</strong></td>
                <td>${escaparHtmlCargo(preview.nome ?? '')}</td>
                <td>${escaparHtmlCargo(preview.departamento ?? '')}</td>
                <td>${escaparHtmlCargo(preview.nivel ?? 1)}</td>
                <td>${escaparHtmlCargo(preview.chefia ?? 'Não')}</td>
            </tr>
        `);
        });
    }

    function renderizarErrosImportacaoCargo(erros) {
        const tbody = $("#cargoImportacaoErros");
        tbody.empty();

        if (erros.length === 0) {
            tbody.html(`<tr><td colspan="3" class="text-center text-muted">Nenhum erro encontrado.</td></tr>`);
            return;
        }

        erros.forEach(item => {
            const dados = item.dados || {};
            let mensagens = item.erros || ['Erro de validação.'];
            if (!Array.isArray(mensagens)) mensagens = [mensagens];

            const listaErros = mensagens.map(m => `<div>${escaparHtmlCargo(m)}</div>`).join('');
            tbody.append(`
            <tr>
                <td>${escaparHtmlCargo(item.linha ?? '-')}</td>
                <td>${escaparHtmlCargo(dados.codigo ?? '')} - ${escaparHtmlCargo(dados.nome ?? '')}</td>
                <td class="text-danger">${listaErros}</td>
            </tr>
        `);
        });
    }

    async function confirmarImportacaoCargo() {
        if (cargoImportacaoProcessando || !cargoImportacaoToken) return;

        const totalValidos = Number(cargoImportacaoDados?.total_validos || 0);
        if (totalValidos <= 0) return;

        const confirmacao = await Swal.fire({
            icon: 'question',
            title: 'Confirmar importação?',
            html: `Serão registados <strong>${totalValidos}</strong> cargos.`,
            showCancelButton: true,
            confirmButtonText: 'Sim, importar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacao.isConfirmed) return;

        const botao = $("#btnConfirmarImportacaoCargo");
        const htmlOriginal = botao.html();

        try {
            cargoImportacaoProcessando = true;
            botao.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> A importar...');

            const formData = new FormData();
            formData.append('acao', 'confirmar_importacao');
            formData.append('token', cargoImportacaoToken);

            const resposta = await requisicaoCargos(CARGOS_URL, {
                method: 'POST',
                body: formData
            });

            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: resposta.mensagem || 'Importação concluída com sucesso.',
                confirmButtonText: 'OK'
            });

            cargoImportacaoToken = '';
            cargoImportacaoDados = null;

            botao.addClass('d-none');
            await recarregarCargos();

            setTimeout(() => {
                $('#modalImportarCargo').modal('hide');
            }, 1200);
        } catch (erro) {
            mostrarErroImportacaoCargo(erro.message);
        } finally {
            cargoImportacaoProcessando = false;
            botao.prop('disabled', false).html(htmlOriginal);
        }
    }

    function voltarImportacaoCargo() {
        cargoImportacaoToken = '';
        cargoImportacaoDados = null;

        $("#cargoImportacaoPasso2").addClass('d-none');
        $("#cargoImportacaoPasso1").removeClass('d-none');
        $("#btnConfirmarImportacaoCargo").addClass('d-none');
        $("#btnVoltarImportacaoCargo").addClass('d-none');
        $("#btnValidarExcelCargo").removeClass('d-none');

        const input = document.getElementById('cargoFicheiroExcel');
        if (input) input.value = '';

        const label = document.querySelector('label[for="cargoFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';

        esconderErroImportacaoCargo();
    }

    function mostrarErroImportacaoCargo(mensagem) {
        const el = $("#cargoImportacaoErro");
        el.text(mensagem).removeClass('d-none');
    }

    function esconderErroImportacaoCargo() {
        $("#cargoImportacaoErro").addClass('d-none');
    }

    function mostrarSucessoImportacaoCargo(mensagem) {
        const el = $("#cargoImportacaoSucesso");
        el.text(mensagem).removeClass('d-none');
    }

    function esconderSucessoImportacaoCargo() {
        $("#cargoImportacaoSucesso").addClass('d-none');
    }
    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisCargos() {

        mostrarCarregamentoCargos(
            true
        );

        try {

            const resposta =
                await requisicaoCargos(
                    CARGOS_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            cargosCache =
                Array.isArray(
                    dados.cargos
                )
                    ? dados.cargos
                    : [];

            preencherDepartamentosCargo(
                dados.departamentos || []
            );

            preencherCentrosCustoCargo(
                dados.centros_custo || []
            );

            preencherStatusCargo(
                dados.status || []
            );

            renderizarTabelaCargos(
                cargosCache
            );

            atualizarResumoCargos(
                cargosCache
            );

        } catch (erro) {

            mostrarErroCargo(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoCargos(
                false
            );

        }

    }


    // ==================================================
    // RECARREGAR
    // ==================================================

    async function recarregarCargos() {

        const resposta =
            await requisicaoCargos(
                CARGOS_URL
                + "?acao=listar"
            );

        cargosCache =
            Array.isArray(
                resposta.data
            )
                ? resposta.data
                : [];

        renderizarTabelaCargos(
            cargosCache
        );

        atualizarResumoCargos(
            cargosCache
        );

    }


    // ==================================================
    // DEPARTAMENTOS
    // ==================================================

    function preencherDepartamentosCargo(
        departamentos
    ) {

        const select =
            $("#cargoDepartamentoId");

        select.empty();

        select.append(`
            <option value="">
                Sem departamento específico
            </option>
        `);

        departamentos.forEach(
            function (departamento) {

                const texto =
                    [
                        departamento.codigo || "",
                        departamento.nome || ""
                    ]
                        .filter(Boolean)
                        .join(" - ");

                select.append(`
                    <option
                        value="${Number(
                    departamento.id
                )}"
                        data-centro-custo-id="${Number(
                    departamento.centro_custo_id
                    || 0
                )}">

                        ${escaparHtmlCargo(
                    texto
                )}

                    </option>
                `);

            }
        );

    }


    // ==================================================
    // CENTROS DE CUSTO
    // ==================================================

    function preencherCentrosCustoCargo(
        centros
    ) {

        const select =
            $("#cargoCentroCustoId");

        select.empty();

        select.append(`
            <option value="">
                Sem centro de custo
            </option>
        `);

        centros.forEach(
            function (centro) {

                const texto =
                    [
                        centro.codigo || "",
                        centro.nome || ""
                    ]
                        .filter(Boolean)
                        .join(" - ");

                select.append(`
                    <option value="${Number(
                    centro.id
                )}">

                        ${escaparHtmlCargo(
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

    function preencherStatusCargo(
        status
    ) {

        const select =
            $("#cargoStatusId");

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

                        ${escaparHtmlCargo(
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
                        normalizarTextoCargo(
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
    // SUGERIR CENTRO DE CUSTO
    // ==================================================

    function sugerirCentroCustoDoDepartamento() {

        const option =
            $("#cargoDepartamentoId")
                .find(
                    "option:selected"
                );

        const centroCustoId =
            Number(
                option.data(
                    "centro-custo-id"
                )
                || 0
            );

        if (
            centroCustoId > 0
            && $("#cargoCentroCustoId")
                .find(
                    `option[value="${centroCustoId}"]`
                )
                .length
        ) {
            $("#cargoCentroCustoId")
                .val(
                    String(
                        centroCustoId
                    )
                );
        }

    }


    // ==================================================
    // TABELA
    // ==================================================

    function renderizarTabelaCargos(
        cargos
    ) {

        destruirDataTableCargos();

        const tbody =
            $("#cargosTable tbody");

        tbody.empty();

        if (!cargos.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="9"
                        class="text-center text-muted py-4">

                        Nenhum cargo registado.

                    </td>

                </tr>
            `);

            return;

        }

        cargos.forEach(
            function (cargo) {

                const statusNome =
                    String(
                        cargo.status_nome
                        || "-"
                    );

                const ativo =
                    [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        normalizarTextoCargo(
                            statusNome
                        )
                    );

                const badgeStatus =
                    ativo
                        ? "badge-success"
                        : "badge-secondary";

                const salario =
                    formatarFaixaSalarialCargo(
                        cargo.salario_minimo,
                        cargo.salario_maximo
                    );

                tbody.append(`
                    <tr>

                        <td>
                            <strong>
                                ${escaparHtmlCargo(
                    cargo.codigo
                    || "-"
                )}
                            </strong>
                        </td>

                        <td>

                            ${escaparHtmlCargo(
                    cargo.nome
                    || "-"
                )}

                            ${Number(
                    cargo.cargo_chefia
                    || 0
                ) === 1
                        ? `
                                        <span class="badge badge-info ml-1">
                                            Chefia
                                        </span>
                                    `
                        : ""
                    }

                            ${cargo.descricao
                        ? `
                                        <div class="text-muted small">
                                            ${escaparHtmlCargo(
                            cargo.descricao
                        )}
                                        </div>
                                    `
                        : ""
                    }

                        </td>

                        <td>
                            ${escaparHtmlCargo(
                        cargo.departamento_nome
                        || "Não associado"
                    )}
                        </td>

                        <td class="text-center">
                            ${Number(
                        cargo.nivel_hierarquico
                        || 1
                    )}
                        </td>

                        <td>
                            ${escaparHtmlCargo(
                        salario
                    )}
                        </td>

                        <td class="text-center">

                            ${cargo.carga_horaria_semanal
                        !== null
                        && cargo.carga_horaria_semanal
                        !== ""
                        ? formatarNumeroCargo(
                            cargo
                                .carga_horaria_semanal
                        )
                        + " h"
                        : "-"
                    }

                        </td>

                        <td class="text-center">

                            ${Number(
                        cargo.permite_horas_extras
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

                        <td>

                            <span class="badge ${badgeStatus}">
                                ${escaparHtmlCargo(
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
                                        class="dropdown-item btn-ver-cargo"
                                        data-id="${Number(
                        cargo.id
                    )}">

                                        <i class="fa fa-eye m-r-5"></i>

                                        Ver detalhes

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item btn-editar-cargo"
                                        data-id="${Number(
                        cargo.id
                    )}">

                                        <i class="fa fa-pencil m-r-5"></i>

                                        Editar

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item text-danger btn-eliminar-cargo"
                                        data-id="${Number(
                        cargo.id
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

        iniciarDataTableCargos();

    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function iniciarDataTableCargos() {

        if (
            typeof $.fn.DataTable
            === "undefined"
        ) {
            return;
        }

        tabelaCargos =
            $("#cargosTable")
                .DataTable({
                    pageLength:
                        10,

                    order: [
                        [
                            3,
                            "asc"
                        ],
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
                            "Nenhum cargo encontrado",

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


    function destruirDataTableCargos() {

        if (
            tabelaCargos
            && typeof tabelaCargos.destroy
            === "function"
        ) {
            tabelaCargos.destroy();

            tabelaCargos =
                null;
        }

        if (
            typeof $.fn.DataTable
            !== "undefined"
            && $.fn.DataTable.isDataTable(
                "#cargosTable"
            )
        ) {
            $("#cargosTable")
                .DataTable()
                .destroy();
        }

    }


    // ==================================================
    // RESUMO
    // ==================================================

    function atualizarResumoCargos(
        cargos
    ) {

        const total =
            cargos.length;

        const ativos =
            cargos.filter(
                function (cargo) {

                    return [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        normalizarTextoCargo(
                            cargo.status_nome
                            || ""
                        )
                    );

                }
            ).length;

        const chefias =
            cargos.filter(
                function (cargo) {

                    return Number(
                        cargo.cargo_chefia
                        || 0
                    ) === 1;

                }
            ).length;

        const permitemHorasExtras =
            cargos.filter(
                function (cargo) {

                    return Number(
                        cargo.permite_horas_extras
                        || 0
                    ) === 1;

                }
            ).length;

        definirTextoCargo(
            "#resumoTotalCargos",
            total.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoCargo(
            "#resumoCargosAtivos",
            ativos.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoCargo(
            "#resumoCargosChefia",
            chefias.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoCargo(
            "#resumoCargosHorasExtras",
            permitemHorasExtras
                .toLocaleString(
                    "pt-PT"
                )
        );

    }


    // ==================================================
    // NOVO
    // ==================================================

    function abrirModalNovoCargo() {

        limparFormularioCargo();

        $("#modalCargoTitulo")
            .text(
                "Novo Cargo"
            );

        $("#btnGuardarCargoTexto")
            .text(
                "Guardar Cargo"
            );

        const statusAtivoId =
            Number(
                $("#cargoStatusId")
                    .data(
                        "status-ativo-id"
                    )
                || 0
            );

        if (statusAtivoId > 0) {

            $("#cargoStatusId")
                .val(
                    String(
                        statusAtivoId
                    )
                );

        }

        $("#cargoNivelHierarquico")
            .val("1");

        $("#cargoCargaHorariaSemanal")
            .val("40");

        $("#cargoPermiteHorasExtras")
            .prop(
                "checked",
                true
            );

        $("#cargoCargoChefia")
            .prop(
                "checked",
                false
            );

        $("#modalCargo")
            .modal("show");

        setTimeout(
            function () {

                $("#cargoCodigo")
                    .trigger(
                        "focus"
                    );

            },
            300
        );

    }


    // ==================================================
    // EDITAR
    // ==================================================

    async function abrirModalEditarCargo(
        cargoId
    ) {

        if (cargoId <= 0) {

            mostrarErroCargo(
                "Cargo inválido."
            );

            return;

        }

        mostrarCarregamentoCargos(
            true
        );

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "buscar",

                    id:
                        String(
                            cargoId
                        )
                });

            const resposta =
                await requisicaoCargos(
                    CARGOS_URL
                    + "?"
                    + parametros.toString()
                );

            const cargo =
                resposta.data || {};

            limparFormularioCargo();

            $("#cargoId")
                .val(
                    Number(
                        cargo.id || 0
                    )
                );

            $("#cargoCodigo")
                .val(
                    cargo.codigo || ""
                );

            $("#cargoNome")
                .val(
                    cargo.nome || ""
                );

            $("#cargoDepartamentoId")
                .val(
                    cargo.departamento_id
                        ? String(
                            cargo.departamento_id
                        )
                        : ""
                );

            $("#cargoCentroCustoId")
                .val(
                    cargo.centro_custo_id
                        ? String(
                            cargo.centro_custo_id
                        )
                        : ""
                );

            $("#cargoNivelHierarquico")
                .val(
                    Number(
                        cargo.nivel_hierarquico
                        || 1
                    )
                );

            $("#cargoSalarioMinimo")
                .val(
                    valorDecimalParaInputCargo(
                        cargo.salario_minimo
                    )
                );

            $("#cargoSalarioMaximo")
                .val(
                    valorDecimalParaInputCargo(
                        cargo.salario_maximo
                    )
                );

            $("#cargoCargaHorariaSemanal")
                .val(
                    valorDecimalParaInputCargo(
                        cargo
                            .carga_horaria_semanal
                    )
                );

            $("#cargoPermiteHorasExtras")
                .prop(
                    "checked",
                    Number(
                        cargo.permite_horas_extras
                        || 0
                    ) === 1
                );

            $("#cargoCargoChefia")
                .prop(
                    "checked",
                    Number(
                        cargo.cargo_chefia
                        || 0
                    ) === 1
                );

            $("#cargoStatusId")
                .val(
                    cargo.status_id
                        ? String(
                            cargo.status_id
                        )
                        : ""
                );

            $("#cargoDescricao")
                .val(
                    cargo.descricao || ""
                );

            $("#cargoResponsabilidades")
                .val(
                    cargo.responsabilidades
                    || ""
                );

            $("#cargoRequisitos")
                .val(
                    cargo.requisitos
                    || ""
                );

            $("#modalCargoTitulo")
                .text(
                    "Editar Cargo"
                );

            $("#btnGuardarCargoTexto")
                .text(
                    "Atualizar Cargo"
                );

            $("#modalCargo")
                .modal("show");

        } catch (erro) {

            mostrarErroCargo(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoCargos(
                false
            );

        }

    }


    // ==================================================
    // GUARDAR
    // ==================================================

    async function guardarCargo() {

        const dados =
            obterDadosFormularioCargo();

        validarFormularioCargo(
            dados
        );

        bloquearModalCargo(
            true
        );

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

            formData.append(
                "codigo",
                dados.codigo
            );

            formData.append(
                "nome",
                dados.nome
            );

            formData.append(
                "departamento_id",
                dados.departamento_id
                ?? ""
            );

            formData.append(
                "centro_custo_id",
                dados.centro_custo_id
                ?? ""
            );

            formData.append(
                "nivel_hierarquico",
                String(
                    dados.nivel_hierarquico
                )
            );

            formData.append(
                "salario_minimo",
                dados.salario_minimo
                ?? ""
            );

            formData.append(
                "salario_maximo",
                dados.salario_maximo
                ?? ""
            );

            formData.append(
                "carga_horaria_semanal",
                dados.carga_horaria_semanal
                ?? ""
            );

            formData.append(
                "permite_horas_extras",
                dados.permite_horas_extras
                    ? "1"
                    : "0"
            );

            formData.append(
                "cargo_chefia",
                dados.cargo_chefia
                    ? "1"
                    : "0"
            );

            formData.append(
                "status_id",
                String(
                    dados.status_id
                )
            );

            formData.append(
                "descricao",
                dados.descricao
            );

            formData.append(
                "responsabilidades",
                dados.responsabilidades
            );

            formData.append(
                "requisitos",
                dados.requisitos
            );

            const resposta =
                await requisicaoCargos(
                    CARGOS_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            $("#modalCargo")
                .modal(
                    "hide"
                );

            await Swal.fire({
                icon:
                    "success",

                title:
                    dados.id > 0
                        ? "Cargo atualizado"
                        : "Cargo criado",

                text:
                    resposta.mensagem
                    || "Operação concluída com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await recarregarCargos();

        } catch (erro) {

            mostrarErroCargo(
                erro.message
            );

            throw erro;

        } finally {

            bloquearModalCargo(
                false
            );

        }

    }


    // ==================================================
    // DADOS DO FORMULÁRIO
    // ==================================================

    function obterDadosFormularioCargo() {

        return {
            id:
                Number(
                    $("#cargoId")
                        .val()
                    || 0
                ),

            codigo:
                String(
                    $("#cargoCodigo")
                        .val()
                    || ""
                )
                    .trim()
                    .toUpperCase(),

            nome:
                String(
                    $("#cargoNome")
                        .val()
                    || ""
                ).trim(),

            departamento_id:
                inteiroOpcionalCargo(
                    $("#cargoDepartamentoId")
                        .val()
                ),

            centro_custo_id:
                inteiroOpcionalCargo(
                    $("#cargoCentroCustoId")
                        .val()
                ),

            nivel_hierarquico:
                Number(
                    $("#cargoNivelHierarquico")
                        .val()
                    || 1
                ),

            salario_minimo:
                decimalOpcionalCargo(
                    $("#cargoSalarioMinimo")
                        .val()
                ),

            salario_maximo:
                decimalOpcionalCargo(
                    $("#cargoSalarioMaximo")
                        .val()
                ),

            carga_horaria_semanal:
                decimalOpcionalCargo(
                    $("#cargoCargaHorariaSemanal")
                        .val()
                ),

            permite_horas_extras:
                $("#cargoPermiteHorasExtras")
                    .is(
                        ":checked"
                    ),

            cargo_chefia:
                $("#cargoCargoChefia")
                    .is(
                        ":checked"
                    ),

            status_id:
                Number(
                    $("#cargoStatusId")
                        .val()
                    || 0
                ),

            descricao:
                String(
                    $("#cargoDescricao")
                        .val()
                    || ""
                ).trim(),

            responsabilidades:
                String(
                    $("#cargoResponsabilidades")
                        .val()
                    || ""
                ).trim(),

            requisitos:
                String(
                    $("#cargoRequisitos")
                        .val()
                    || ""
                ).trim()
        };

    }


    // ==================================================
    // VALIDAR FORMULÁRIO
    // ==================================================

    function validarFormularioCargo(
        dados
    ) {

        if (dados.codigo === "") {

            throw new Error(
                "Informe o código do cargo."
            );

        }

        if (dados.codigo.length > 20) {

            throw new Error(
                "O código do cargo não pode exceder 20 caracteres."
            );

        }

        if (dados.nome === "") {

            throw new Error(
                "Informe o nome do cargo."
            );

        }

        if (dados.nome.length > 150) {

            throw new Error(
                "O nome do cargo não pode exceder 150 caracteres."
            );

        }

        if (
            !Number.isInteger(
                dados.nivel_hierarquico
            )
            || dados.nivel_hierarquico <= 0
            || dados.nivel_hierarquico > 255
        ) {

            throw new Error(
                "Informe um nível hierárquico válido."
            );

        }

        if (
            dados.salario_minimo !== null
            && dados.salario_minimo < 0
        ) {

            throw new Error(
                "O salário mínimo não pode ser negativo."
            );

        }

        if (
            dados.salario_maximo !== null
            && dados.salario_maximo < 0
        ) {

            throw new Error(
                "O salário máximo não pode ser negativo."
            );

        }

        if (
            dados.salario_minimo !== null
            && dados.salario_maximo !== null
            && dados.salario_maximo
            < dados.salario_minimo
        ) {

            throw new Error(
                "O salário máximo não pode ser inferior ao salário mínimo."
            );

        }

        if (
            dados.carga_horaria_semanal
            !== null
            && (
                dados.carga_horaria_semanal
                <= 0
                || dados.carga_horaria_semanal
                > 168
            )
        ) {

            throw new Error(
                "A carga horária semanal é inválida."
            );

        }

        if (dados.status_id <= 0) {

            throw new Error(
                "Selecione o estado do cargo."
            );

        }

    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    async function confirmarEliminarCargo(
        cargoId
    ) {

        const cargo =
            cargosCache.find(
                function (item) {

                    return Number(
                        item.id
                    ) === Number(
                        cargoId
                    );

                }
            );

        if (!cargo) {

            mostrarErroCargo(
                "Cargo não encontrado."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Eliminar cargo?",

                html:
                    `
                        <p>
                            Pretende eliminar o cargo:
                        </p>

                        <strong>
                            ${escaparHtmlCargo(
                        cargo.codigo
                        + " - "
                        + cargo.nome
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

        mostrarCarregamentoCargos(
            true
        );

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
                    cargoId
                )
            );

            const resposta =
                await requisicaoCargos(
                    CARGOS_URL,
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
                    "Cargo eliminado",

                text:
                    resposta.mensagem
                    || "Cargo eliminado com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await recarregarCargos();

        } catch (erro) {

            mostrarErroCargo(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoCargos(
                false
            );

        }

    }


    // ==================================================
    // DETALHES
    // ==================================================

    function mostrarDetalhesCargo(
        cargoId
    ) {

        const cargo =
            cargosCache.find(
                function (item) {

                    return Number(
                        item.id
                    ) === Number(
                        cargoId
                    );

                }
            );

        if (!cargo) {

            mostrarErroCargo(
                "Cargo não encontrado."
            );

            return;

        }

        Swal.fire({
            icon:
                "info",

            title:
                escaparHtmlCargo(
                    cargo.nome
                    || "Cargo"
                ),

            width:
                720,

            html:
                `
                    <div class="text-left">

                        <div class="row">

                            <div class="col-md-6 mb-2">
                                <strong>Código:</strong><br>
                                ${escaparHtmlCargo(
                    cargo.codigo
                    || "-"
                )}
                            </div>

                            <div class="col-md-6 mb-2">
                                <strong>Estado:</strong><br>
                                ${escaparHtmlCargo(
                    cargo.status_nome
                    || "-"
                )}
                            </div>

                            <div class="col-md-6 mb-2">
                                <strong>Departamento:</strong><br>
                                ${escaparHtmlCargo(
                    cargo.departamento_nome
                    || "Não associado"
                )}
                            </div>

                            <div class="col-md-6 mb-2">
                                <strong>Centro de custo:</strong><br>
                                ${escaparHtmlCargo(
                    cargo.centro_custo_nome
                    || "Não associado"
                )}
                            </div>

                            <div class="col-md-4 mb-2">
                                <strong>Nível hierárquico:</strong><br>
                                ${Number(
                    cargo.nivel_hierarquico
                    || 1
                )}
                            </div>

                            <div class="col-md-4 mb-2">
                                <strong>Cargo de chefia:</strong><br>
                                ${Number(
                    cargo.cargo_chefia
                    || 0
                ) === 1
                    ? "Sim"
                    : "Não"
                }
                            </div>

                            <div class="col-md-4 mb-2">
                                <strong>Horas extras:</strong><br>
                                ${Number(
                    cargo.permite_horas_extras
                    || 0
                ) === 1
                    ? "Permitidas"
                    : "Não permitidas"
                }
                            </div>

                            <div class="col-md-6 mb-2">
                                <strong>Faixa salarial:</strong><br>
                                ${escaparHtmlCargo(
                    formatarFaixaSalarialCargo(
                        cargo.salario_minimo,
                        cargo.salario_maximo
                    )
                )}
                            </div>

                            <div class="col-md-6 mb-2">
                                <strong>Carga horária semanal:</strong><br>
                                ${cargo.carga_horaria_semanal
                    !== null
                    && cargo.carga_horaria_semanal
                    !== ""
                    ? formatarNumeroCargo(
                        cargo
                            .carga_horaria_semanal
                    )
                    + " horas"
                    : "-"
                }
                            </div>

                        </div>

                        <hr>

                        <div class="mb-3">
                            <strong>Descrição:</strong>
                            <div class="mt-1">
                                ${formatarTextoLongoCargo(
                    cargo.descricao
                )}
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>Responsabilidades:</strong>
                            <div class="mt-1">
                                ${formatarTextoLongoCargo(
                    cargo.responsabilidades
                )}
                            </div>
                        </div>

                        <div>
                            <strong>Requisitos:</strong>
                            <div class="mt-1">
                                ${formatarTextoLongoCargo(
                    cargo.requisitos
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

    function limparFormularioCargo() {

        const formulario =
            $("#formCargo")[0];

        if (formulario) {
            formulario.reset();
        }

        $("#cargoId")
            .val("");

        $("#cargoNivelHierarquico")
            .val("1");

        $("#cargoCargaHorariaSemanal")
            .val("40");

        $("#cargoPermiteHorasExtras")
            .prop(
                "checked",
                true
            );

        $("#cargoCargoChefia")
            .prop(
                "checked",
                false
            );

        $("#formCargo")
            .find(
                ".is-invalid"
            )
            .removeClass(
                "is-invalid"
            );

        $("#modalCargoTitulo")
            .text(
                "Novo Cargo"
            );

        $("#btnGuardarCargoTexto")
            .text(
                "Guardar Cargo"
            );

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoCargos(
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
                || "Não foi possível processar o cargo."
            );
        }

        return json;

    }


    // ==================================================
    // CARREGAMENTO
    // ==================================================

    function mostrarCarregamentoCargos(
        mostrar
    ) {

        $("#cargosCarregando")
            .toggle(
                Boolean(
                    mostrar
                )
            );

        $("#btnNovoCargo")
            .prop(
                "disabled",
                Boolean(
                    mostrar
                )
            );

    }


    function bloquearModalCargo(
        bloquear
    ) {

        $("#formCargo")
            .find(
                "input, textarea, select, button"
            )
            .prop(
                "disabled",
                Boolean(
                    bloquear
                )
            );

        $("#btnGuardarCargoSpinner")
            .toggle(
                Boolean(
                    bloquear
                )
            );

    }


    // ==================================================
    // ERROS
    // ==================================================

    function mostrarErroCargo(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível processar",

            text:
                mensagem
                || "Ocorreu um erro na gestão de cargos.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // HELPERS
    // ==================================================

    function definirTextoCargo(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function inteiroOpcionalCargo(
        valor
    ) {

        const numero =
            Number(
                valor || 0
            );

        return numero > 0
            ? numero
            : null;

    }


    function decimalOpcionalCargo(
        valor
    ) {

        const texto =
            String(
                valor || ""
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
            Number(
                texto
            );

        if (
            !Number.isFinite(
                numero
            )
        ) {
            return null;
        }

        return Number(
            numero.toFixed(2)
        );

    }


    function normalizarCampoDecimalCargo(
        campo
    ) {

        let valor =
            String(
                campo.val() || ""
            );

        valor =
            valor.replace(
                /[^0-9,.-]/g,
                ""
            );

        const partes =
            valor.split(
                /[,.]/
            );

        if (partes.length > 2) {

            valor =
                partes.shift()
                + ","
                + partes.join("");

        }

        campo.val(
            valor
        );

    }


    function valorDecimalParaInputCargo(
        valor
    ) {

        if (
            valor === null
            || valor === ""
            || typeof valor
            === "undefined"
        ) {
            return "";
        }

        return Number(
            valor
        )
            .toFixed(2)
            .replace(
                ".",
                ","
            );

    }


    function formatarNumeroCargo(
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


    function formatarMoedaCargo(
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


    function formatarFaixaSalarialCargo(
        minimo,
        maximo
    ) {

        const possuiMinimo =
            minimo !== null
            && minimo !== ""
            && typeof minimo
            !== "undefined";

        const possuiMaximo =
            maximo !== null
            && maximo !== ""
            && typeof maximo
            !== "undefined";

        if (
            !possuiMinimo
            && !possuiMaximo
        ) {
            return "Não definida";
        }

        if (
            possuiMinimo
            && possuiMaximo
        ) {
            return (
                formatarMoedaCargo(
                    minimo
                )
                + " a "
                + formatarMoedaCargo(
                    maximo
                )
            );
        }

        if (possuiMinimo) {
            return (
                "A partir de "
                + formatarMoedaCargo(
                    minimo
                )
            );
        }

        return (
            "Até "
            + formatarMoedaCargo(
                maximo
            )
        );

    }


    function normalizarTextoCargo(
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


    function formatarTextoLongoCargo(
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

        return escaparHtmlCargo(
            texto
        ).replace(
            /\n/g,
            "<br>"
        );

    }


    function escaparHtmlCargo(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);