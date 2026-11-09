"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const DEPARTAMENTOS_URL =
        "/erpplus/app/ajax/departamentos_ajax.php";

    let departamentosCache =
        [];

    let tabelaDepartamentos =
        null;

    let departamentoImportacaoToken = '';
    let departamentoImportacaoDados = null;
    let departamentoImportacaoProcessando = false;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaDepartamentosExiste()) {
            return;
        }

        configurarEventosDepartamentos();

        carregarDadosIniciaisDepartamentos()
            .catch(function (erro) {

                console.error(
                    "Erro ao iniciar a gestão de departamentos:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaDepartamentosExiste() {

        return (
            $("#departamentosTable").length > 0
            && $("#formDepartamento").length > 0
            && $("#modalDepartamento").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosDepartamentos() {

        $("#btnNovoDepartamento").on(
            "click",
            function () {

                abrirModalNovoDepartamento();

            }
        );

        $("#btnImportarDepartamento").on("click", abrirImportacaoDepartamento);
        $("#btnModeloExcelDepartamento").on("click", baixarModeloExcelDepartamento);
        $("#btnValidarExcelDepartamento").on("click", validarImportacaoDepartamento);
        $("#btnConfirmarImportacaoDepartamento").on("click", confirmarImportacaoDepartamento);
        $("#btnVoltarImportacaoDepartamento").on("click", voltarImportacaoDepartamento);
        $("#departamentoFicheiroExcel").on("change", alterarFicheiroExcelDepartamento);

        $("#formDepartamento").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarDepartamento()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao guardar departamento:",
                            erro
                        );

                    });

            }
        );

        $("#departamentoCodigo").on(
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

        $("#departamentosTable").on(
            "click",
            ".btn-editar-departamento",
            function () {

                const departamentoId =
                    Number(
                        $(this).data("id")
                        || 0
                    );

                abrirModalEditarDepartamento(
                    departamentoId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao carregar departamento:",
                            erro
                        );

                    });

            }
        );

        $("#departamentosTable").on(
            "click",
            ".btn-eliminar-departamento",
            function () {

                const departamentoId =
                    Number(
                        $(this).data("id")
                        || 0
                    );

                confirmarEliminarDepartamento(
                    departamentoId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao eliminar departamento:",
                            erro
                        );

                    });

            }
        );

        $("#departamentosTable").on(
            "click",
            ".btn-ver-departamento",
            function () {

                const departamentoId =
                    Number(
                        $(this).data("id")
                        || 0
                    );

                mostrarDetalhesDepartamento(
                    departamentoId
                );

            }
        );

        $("#modalDepartamento").on(
            "hidden.bs.modal",
            function () {

                limparFormularioDepartamento();

            }
        );

        $("#departamentoFilialId").on(
            "change",
            function () {

                atualizarDepartamentosPaiDisponiveis();

            }
        );

    }


    // Adicionar as funções auxiliares de importação:
    function abrirImportacaoDepartamento() {
        departamentoImportacaoToken = '';
        departamentoImportacaoDados = null;
        departamentoImportacaoProcessando = false;

        const ficheiro = document.getElementById('departamentoFicheiroExcel');
        if (ficheiro) ficheiro.value = '';

        const label = document.querySelector('label[for="departamentoFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';

        $("#departamentoImportacaoPasso1").removeClass('d-none');
        $("#departamentoImportacaoPasso2").addClass('d-none');
        $("#btnValidarExcelDepartamento").removeClass('d-none');
        $("#btnConfirmarImportacaoDepartamento").addClass('d-none');
        $("#btnVoltarImportacaoDepartamento").addClass('d-none');

        esconderErroImportacaoDepartamento();
        esconderSucessoImportacaoDepartamento();

        $('#modalImportarDepartamento').modal('show');
    }

    function alterarFicheiroExcelDepartamento(evento) {
        const ficheiro = evento.target.files?.[0];
        const label = document.querySelector('label[for="departamentoFicheiroExcel"]');
        if (label) {
            label.textContent = ficheiro ? ficheiro.name : 'Selecione o ficheiro';
        }
    }

    function baixarModeloExcelDepartamento() {
        const url = DEPARTAMENTOS_URL + '?acao=baixar_modelo_excel&_=' + Date.now();
        window.location.href = url;
    }

    async function validarImportacaoDepartamento() {
        if (departamentoImportacaoProcessando) return;

        esconderErroImportacaoDepartamento();
        esconderSucessoImportacaoDepartamento();

        const input = document.getElementById('departamentoFicheiroExcel');
        const ficheiro = input?.files?.[0];

        if (!ficheiro) {
            mostrarErroImportacaoDepartamento('Selecione um ficheiro Excel antes de continuar.');
            return;
        }

        const extensao = ficheiro.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(extensao)) {
            mostrarErroImportacaoDepartamento('O ficheiro deve estar no formato XLSX ou XLS.');
            return;
        }

        const botao = $("#btnValidarExcelDepartamento");
        const htmlOriginal = botao.html();

        try {
            departamentoImportacaoProcessando = true;
            botao.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> A validar...');

            const formData = new FormData();
            formData.append('acao', 'validar_importacao');
            formData.append('ficheiro', ficheiro);

            const resposta = await requisicaoDepartamentos(DEPARTAMENTOS_URL, {
                method: 'POST',
                body: formData
            });

            departamentoImportacaoDados = resposta.data || {};
            departamentoImportacaoToken = String(departamentoImportacaoDados.token || '');

            if (!departamentoImportacaoToken) {
                throw new Error('O servidor não devolveu o token da importação.');
            }

            renderizarResultadoImportacaoDepartamento(departamentoImportacaoDados);

            $("#departamentoImportacaoPasso1").addClass('d-none');
            $("#departamentoImportacaoPasso2").removeClass('d-none');
            $("#btnValidarExcelDepartamento").addClass('d-none');
            $("#btnVoltarImportacaoDepartamento").removeClass('d-none');

            const totalValidos = Number(departamentoImportacaoDados.total_validos || 0);
            if (totalValidos > 0) {
                $("#btnConfirmarImportacaoDepartamento").removeClass('d-none');
            } else {
                $("#btnConfirmarImportacaoDepartamento").addClass('d-none');
            }
        } catch (erro) {
            mostrarErroImportacaoDepartamento(erro.message);
        } finally {
            departamentoImportacaoProcessando = false;
            botao.prop('disabled', false).html(htmlOriginal);
        }
    }

    function renderizarResultadoImportacaoDepartamento(dados) {
        const validos = Array.isArray(dados.validos) ? dados.validos : [];
        const erros = Array.isArray(dados.erros) ? dados.erros : [];

        renderizarDepartamentosValidosImportacao(validos);
        renderizarErrosImportacaoDepartamento(erros);

        $("#departamentoImportacaoValidosContainer").toggleClass('d-none', validos.length === 0);
        $("#departamentoImportacaoErrosContainer").toggleClass('d-none', erros.length === 0);
    }

    function renderizarDepartamentosValidosImportacao(validos) {
        const tbody = $("#departamentoImportacaoValidos");
        tbody.empty();

        if (validos.length === 0) {
            tbody.html(`<tr><td colspan="6" class="text-center text-muted">Nenhum departamento válido encontrado.</td></tr>`);
            return;
        }

        validos.forEach(item => {
            const preview = item.preview || {};
            tbody.append(`
            <tr>
                <td>${escaparHtmlDepartamento(item.linha ?? '')}</td>
                <td><strong>${escaparHtmlDepartamento(preview.codigo ?? '')}</strong></td>
                <td>${escaparHtmlDepartamento(preview.nome ?? '')}</td>
                <td>${escaparHtmlDepartamento(preview.filial ?? '')}</td>
                <td>${escaparHtmlDepartamento(preview.departamento_pai ?? '')}</td>
                <td>${escaparHtmlDepartamento(preview.centro_custo ?? '')}</td>
            </tr>
        `);
        });
    }

    function renderizarErrosImportacaoDepartamento(erros) {
        const tbody = $("#departamentoImportacaoErros");
        tbody.empty();

        if (erros.length === 0) {
            tbody.html(`<tr><td colspan="3" class="text-center text-muted">Nenhum erro encontrado.</td></tr>`);
            return;
        }

        erros.forEach(item => {
            const dados = item.dados || {};
            let mensagens = item.erros || ['Erro de validação.'];
            if (!Array.isArray(mensagens)) mensagens = [mensagens];

            const listaErros = mensagens.map(m => `<div>${escaparHtmlDepartamento(m)}</div>`).join('');
            tbody.append(`
            <tr>
                <td>${escaparHtmlDepartamento(item.linha ?? '-')}</td>
                <td>${escaparHtmlDepartamento(dados.codigo ?? '')} - ${escaparHtmlDepartamento(dados.nome ?? '')}</td>
                <td class="text-danger">${listaErros}</td>
            </tr>
        `);
        });
    }

    async function confirmarImportacaoDepartamento() {
        if (departamentoImportacaoProcessando || !departamentoImportacaoToken) return;

        const totalValidos = Number(departamentoImportacaoDados?.total_validos || 0);
        if (totalValidos <= 0) return;

        const confirmacao = await Swal.fire({
            icon: 'question',
            title: 'Confirmar importação?',
            html: `Serão registados <strong>${totalValidos}</strong> departamentos.`,
            showCancelButton: true,
            confirmButtonText: 'Sim, importar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacao.isConfirmed) return;

        const botao = $("#btnConfirmarImportacaoDepartamento");
        const htmlOriginal = botao.html();

        try {
            departamentoImportacaoProcessando = true;
            botao.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> A importar...');

            const formData = new FormData();
            formData.append('acao', 'confirmar_importacao');
            formData.append('token', departamentoImportacaoToken);

            const resposta = await requisicaoDepartamentos(DEPARTAMENTOS_URL, {
                method: 'POST',
                body: formData
            });

            await Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: resposta.mensagem || 'Importação concluída com sucesso.',
                confirmButtonText: 'OK'
            });

            departamentoImportacaoToken = '';
            departamentoImportacaoDados = null;

            botao.addClass('d-none');
            await carregarDadosIniciaisDepartamentos();

            setTimeout(() => {
                $('#modalImportarDepartamento').modal('hide');
            }, 1200);
        } catch (erro) {
            mostrarErroImportacaoDepartamento(erro.message);
        } finally {
            departamentoImportacaoProcessando = false;
            botao.prop('disabled', false).html(htmlOriginal);
        }
    }

    function voltarImportacaoDepartamento() {
        departamentoImportacaoToken = '';
        departamentoImportacaoDados = null;

        $("#departamentoImportacaoPasso2").addClass('d-none');
        $("#departamentoImportacaoPasso1").removeClass('d-none');
        $("#btnConfirmarImportacaoDepartamento").addClass('d-none');
        $("#btnVoltarImportacaoDepartamento").addClass('d-none');
        $("#btnValidarExcelDepartamento").removeClass('d-none');

        const input = document.getElementById('departamentoFicheiroExcel');
        if (input) input.value = '';

        const label = document.querySelector('label[for="departamentoFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';

        esconderErroImportacaoDepartamento();
    }

    function mostrarErroImportacaoDepartamento(mensagem) {
        const el = $("#departamentoImportacaoErro");
        el.text(mensagem).removeClass('d-none');
    }

    function esconderErroImportacaoDepartamento() {
        $("#departamentoImportacaoErro").addClass('d-none');
    }

    function mostrarSucessoImportacaoDepartamento(mensagem) {
        const el = $("#departamentoImportacaoSucesso");
        el.text(mensagem).removeClass('d-none');
    }

    function esconderSucessoImportacaoDepartamento() {
        $("#departamentoImportacaoSucesso").addClass('d-none');
    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisDepartamentos() {

        mostrarCarregamentoDepartamentos(true);

        try {

            const resposta =
                await requisicaoDepartamentos(
                    DEPARTAMENTOS_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            departamentosCache =
                Array.isArray(
                    dados.departamentos
                )
                    ? dados.departamentos
                    : [];

            preencherFiliaisDepartamento(
                dados.filiais || []
            );

            preencherCentrosCustoDepartamento(
                dados.centros_custo || []
            );

            preencherStatusDepartamento(
                dados.status || []
            );

            preencherDepartamentosPai(
                departamentosCache
            );

            renderizarTabelaDepartamentos(
                departamentosCache
            );

            atualizarResumoDepartamentos(
                departamentosCache
            );

        } catch (erro) {

            mostrarErroDepartamento(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoDepartamentos(false);

        }

    }


    // ==================================================
    // LISTAR NOVAMENTE
    // ==================================================

    async function recarregarDepartamentos() {

        const resposta =
            await requisicaoDepartamentos(
                DEPARTAMENTOS_URL
                + "?acao=listar"
            );

        departamentosCache =
            Array.isArray(
                resposta.data
            )
                ? resposta.data
                : [];

        preencherDepartamentosPai(
            departamentosCache
        );

        renderizarTabelaDepartamentos(
            departamentosCache
        );

        atualizarResumoDepartamentos(
            departamentosCache
        );

    }


    // ==================================================
    // FILIAIS
    // ==================================================

    function preencherFiliaisDepartamento(
        filiais
    ) {

        const select =
            $("#departamentoFilialId");

        select.empty();

        select.append(`
            <option value="">
                Todas / Sem filial específica
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
                    <option value="${Number(filial.id)}">
                        ${escaparHtmlDepartamento(texto)}
                    </option>
                `);

            }
        );

    }


    // ==================================================
    // CENTROS DE CUSTO
    // ==================================================

    function preencherCentrosCustoDepartamento(
        centros
    ) {

        const select =
            $("#departamentoCentroCustoId");

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
                    <option value="${Number(centro.id)}">
                        ${escaparHtmlDepartamento(texto)}
                    </option>
                `);

            }
        );

    }


    // ==================================================
    // STATUS
    // ==================================================

    function preencherStatusDepartamento(
        status
    ) {

        const select =
            $("#departamentoStatusId");

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
                        ${escaparHtmlDepartamento(
                    item.nome || "-"
                )}
                    </option>
                `);

            }
        );

        const statusAtivo =
            status.find(
                function (item) {

                    const nome =
                        normalizarTextoDepartamento(
                            item.nome || ""
                        );

                    return [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(nome);

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
    // DEPARTAMENTOS PAI
    // ==================================================

    function preencherDepartamentosPai(
        departamentos,
        departamentoAtualId = null
    ) {

        const select =
            $("#departamentoPaiId");

        const valorAtual =
            String(
                select.val() || ""
            );

        select.empty();

        select.append(`
            <option value="">
                Departamento principal
            </option>
        `);

        departamentos.forEach(
            function (departamento) {

                const id =
                    Number(
                        departamento.id || 0
                    );

                if (
                    departamentoAtualId !== null
                    && id === Number(
                        departamentoAtualId
                    )
                ) {
                    return;
                }

                const prefixo =
                    departamento
                        .departamento_pai_id
                        ? "— "
                        : "";

                const texto =
                    prefixo
                    + [
                        departamento.codigo || "",
                        departamento.nome || ""
                    ]
                        .filter(Boolean)
                        .join(" - ");

                select.append(`
                    <option
                        value="${id}"
                        data-filial-id="${Number(
                    departamento.filial_id
                    || 0
                )}">

                        ${escaparHtmlDepartamento(texto)}

                    </option>
                `);

            }
        );

        if (
            valorAtual !== ""
            && select.find(
                `option[value="${valorAtual}"]`
            ).length
        ) {
            select.val(valorAtual);
        }

        atualizarDepartamentosPaiDisponiveis();

    }


    function atualizarDepartamentosPaiDisponiveis() {

        const filialId =
            Number(
                $("#departamentoFilialId")
                    .val()
                || 0
            );

        const select =
            $("#departamentoPaiId");

        select
            .find("option")
            .each(function () {

                const option =
                    $(this);

                const id =
                    Number(
                        option.val() || 0
                    );

                if (id <= 0) {

                    option.prop(
                        "disabled",
                        false
                    );

                    return;

                }

                const filialDepartamento =
                    Number(
                        option.data("filial-id")
                        || 0
                    );

                /*
                 * Um departamento sem filial pode ser pai
                 * de qualquer departamento.
                 *
                 * Um departamento com filial só pode ser pai
                 * de outro da mesma filial.
                 */
                const permitido =
                    filialDepartamento === 0
                    || filialId === 0
                    || filialDepartamento === filialId;

                option.prop(
                    "disabled",
                    !permitido
                );

            });

        const selecionado =
            select.find(
                "option:selected"
            );

        if (
            selecionado.length
            && selecionado.prop("disabled")
        ) {
            select.val("");
        }

    }


    // ==================================================
    // RENDERIZAR TABELA
    // ==================================================

    function renderizarTabelaDepartamentos(
        departamentos
    ) {

        destruirDataTableDepartamentos();

        const tbody =
            $("#departamentosTable tbody");

        tbody.empty();

        if (!departamentos.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="8"
                        class="text-center text-muted py-4">

                        Nenhum departamento registado.

                    </td>

                </tr>
            `);

            return;

        }

        departamentos.forEach(
            function (departamento) {

                const statusNome =
                    String(
                        departamento.status_nome
                        || "-"
                    );

                const statusNormalizado =
                    normalizarTextoDepartamento(
                        statusNome
                    );

                const statusAtivo =
                    [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        statusNormalizado
                    );

                const badgeStatus =
                    statusAtivo
                        ? "badge-success"
                        : "badge-secondary";

                const departamentoPai =
                    departamento
                        .departamento_pai_nome
                    || "Departamento principal";

                const filial =
                    departamento.filial_nome
                    || "Todas";

                const centroCusto =
                    departamento.centro_custo_nome
                    || "Não associado";

                tbody.append(`
                    <tr>

                        <td>
                            <strong>
                                ${escaparHtmlDepartamento(
                    departamento.codigo
                    || "-"
                )}
                            </strong>
                        </td>

                        <td>
                            ${escaparHtmlDepartamento(
                    departamento.nome
                    || "-"
                )}

                            ${departamento.descricao
                        ? `
                                        <div class="text-muted small">
                                            ${escaparHtmlDepartamento(
                            departamento.descricao
                        )}
                                        </div>
                                    `
                        : ""
                    }
                        </td>

                        <td>
                            ${escaparHtmlDepartamento(
                        departamentoPai
                    )}
                        </td>

                        <td>
                            ${escaparHtmlDepartamento(
                        filial
                    )}
                        </td>

                        <td>
                            ${escaparHtmlDepartamento(
                        centroCusto
                    )}
                        </td>

                        <td>
                            ${departamento.gestor_id
                        ? escaparHtmlDepartamento(
                            departamento.gestor_nome
                            || "Gestor associado"
                        )
                        : `
                                        <span class="text-muted">
                                            Não definido
                                        </span>
                                    `
                    }
                        </td>

                        <td>
                            <span class="badge ${badgeStatus}">
                                ${escaparHtmlDepartamento(
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
                                        class="dropdown-item btn-ver-departamento"
                                        data-id="${Number(
                        departamento.id
                    )}">

                                        <i class="fa fa-eye m-r-5"></i>

                                        Ver detalhes

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item btn-editar-departamento"
                                        data-id="${Number(
                        departamento.id
                    )}">

                                        <i class="fa fa-pencil m-r-5"></i>

                                        Editar

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item text-danger btn-eliminar-departamento"
                                        data-id="${Number(
                        departamento.id
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

        iniciarDataTableDepartamentos();

    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function iniciarDataTableDepartamentos() {

        if (
            typeof $.fn.DataTable
            === "undefined"
        ) {
            return;
        }

        tabelaDepartamentos =
            $("#departamentosTable")
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
                                7,

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
                            "Nenhum departamento encontrado",

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


    function destruirDataTableDepartamentos() {

        if (
            tabelaDepartamentos
            && typeof tabelaDepartamentos.destroy
            === "function"
        ) {
            tabelaDepartamentos.destroy();

            tabelaDepartamentos =
                null;
        }

        if (
            typeof $.fn.DataTable
            !== "undefined"
            && $.fn.DataTable.isDataTable(
                "#departamentosTable"
            )
        ) {
            $("#departamentosTable")
                .DataTable()
                .destroy();
        }

    }


    // ==================================================
    // RESUMO
    // ==================================================

    function atualizarResumoDepartamentos(
        departamentos
    ) {

        const total =
            departamentos.length;

        const ativos =
            departamentos.filter(
                function (departamento) {

                    const status =
                        normalizarTextoDepartamento(
                            departamento.status_nome
                            || ""
                        );

                    return [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(status);

                }
            ).length;

        const principais =
            departamentos.filter(
                function (departamento) {

                    return !departamento
                        .departamento_pai_id;

                }
            ).length;

        const subdepartamentos =
            total - principais;

        definirTextoDepartamento(
            "#resumoTotalDepartamentos",
            total.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoDepartamento(
            "#resumoDepartamentosAtivos",
            ativos.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoDepartamento(
            "#resumoDepartamentosPrincipais",
            principais.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoDepartamento(
            "#resumoSubdepartamentos",
            subdepartamentos.toLocaleString(
                "pt-PT"
            )
        );

    }


    // ==================================================
    // NOVO DEPARTAMENTO
    // ==================================================

    function abrirModalNovoDepartamento() {

        limparFormularioDepartamento();

        $("#modalDepartamentoTitulo")
            .text(
                "Novo Departamento"
            );

        $("#btnGuardarDepartamentoTexto")
            .text(
                "Guardar Departamento"
            );

        const statusAtivoId =
            Number(
                $("#departamentoStatusId")
                    .data(
                        "status-ativo-id"
                    )
                || 0
            );

        if (statusAtivoId > 0) {

            $("#departamentoStatusId")
                .val(
                    String(statusAtivoId)
                );

        }

        preencherDepartamentosPai(
            departamentosCache,
            null
        );

        $("#modalDepartamento")
            .modal("show");

        setTimeout(
            function () {

                $("#departamentoCodigo")
                    .trigger("focus");

            },
            300
        );

    }


    // ==================================================
    // EDITAR DEPARTAMENTO
    // ==================================================

    async function abrirModalEditarDepartamento(
        departamentoId
    ) {

        if (departamentoId <= 0) {

            mostrarErroDepartamento(
                "Departamento inválido."
            );

            return;
        }

        mostrarCarregamentoDepartamentos(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "buscar",

                    id:
                        String(
                            departamentoId
                        )
                });

            const resposta =
                await requisicaoDepartamentos(
                    DEPARTAMENTOS_URL
                    + "?"
                    + parametros.toString()
                );

            const departamento =
                resposta.data || {};

            limparFormularioDepartamento();

            preencherDepartamentosPai(
                departamentosCache,
                departamentoId
            );

            $("#departamentoId")
                .val(
                    Number(
                        departamento.id
                        || 0
                    )
                );

            $("#departamentoCodigo")
                .val(
                    departamento.codigo
                    || ""
                );

            $("#departamentoNome")
                .val(
                    departamento.nome
                    || ""
                );

            $("#departamentoDescricao")
                .val(
                    departamento.descricao
                    || ""
                );

            $("#departamentoFilialId")
                .val(
                    departamento.filial_id
                        ? String(
                            departamento.filial_id
                        )
                        : ""
                );

            atualizarDepartamentosPaiDisponiveis();

            $("#departamentoPaiId")
                .val(
                    departamento
                        .departamento_pai_id
                        ? String(
                            departamento
                                .departamento_pai_id
                        )
                        : ""
                );

            $("#departamentoCentroCustoId")
                .val(
                    departamento.centro_custo_id
                        ? String(
                            departamento
                                .centro_custo_id
                        )
                        : ""
                );

            $("#departamentoStatusId")
                .val(
                    departamento.status_id
                        ? String(
                            departamento.status_id
                        )
                        : ""
                );

            $("#modalDepartamentoTitulo")
                .text(
                    "Editar Departamento"
                );

            $("#btnGuardarDepartamentoTexto")
                .text(
                    "Atualizar Departamento"
                );

            $("#modalDepartamento")
                .modal("show");

        } catch (erro) {

            mostrarErroDepartamento(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoDepartamentos(false);

        }

    }


    // ==================================================
    // GUARDAR
    // ==================================================

    async function guardarDepartamento() {

        const dados =
            obterDadosFormularioDepartamento();

        validarFormularioDepartamento(
            dados
        );

        bloquearModalDepartamento(true);

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

            formData.append(
                "codigo",
                dados.codigo
            );

            formData.append(
                "nome",
                dados.nome
            );

            formData.append(
                "descricao",
                dados.descricao
            );

            formData.append(
                "filial_id",
                dados.filial_id
                ?? ""
            );

            formData.append(
                "departamento_pai_id",
                dados.departamento_pai_id
                ?? ""
            );

            formData.append(
                "centro_custo_id",
                dados.centro_custo_id
                ?? ""
            );

            formData.append(
                "status_id",
                String(
                    dados.status_id
                )
            );

            const resposta =
                await requisicaoDepartamentos(
                    DEPARTAMENTOS_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            $("#modalDepartamento")
                .modal("hide");

            await Swal.fire({
                icon:
                    "success",

                title:
                    dados.id > 0
                        ? "Departamento atualizado"
                        : "Departamento criado",

                text:
                    resposta.mensagem
                    || "Operação concluída com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await recarregarDepartamentos();

        } catch (erro) {

            mostrarErroDepartamento(
                erro.message
            );

            throw erro;

        } finally {

            bloquearModalDepartamento(false);

        }

    }


    // ==================================================
    // DADOS DO FORMULÁRIO
    // ==================================================

    function obterDadosFormularioDepartamento() {

        return {
            id:
                Number(
                    $("#departamentoId")
                        .val()
                    || 0
                ),

            codigo:
                String(
                    $("#departamentoCodigo")
                        .val()
                    || ""
                )
                    .trim()
                    .toUpperCase(),

            nome:
                String(
                    $("#departamentoNome")
                        .val()
                    || ""
                ).trim(),

            descricao:
                String(
                    $("#departamentoDescricao")
                        .val()
                    || ""
                ).trim(),

            filial_id:
                inteiroOpcionalDepartamento(
                    $("#departamentoFilialId")
                        .val()
                ),

            departamento_pai_id:
                inteiroOpcionalDepartamento(
                    $("#departamentoPaiId")
                        .val()
                ),

            centro_custo_id:
                inteiroOpcionalDepartamento(
                    $("#departamentoCentroCustoId")
                        .val()
                ),

            status_id:
                Number(
                    $("#departamentoStatusId")
                        .val()
                    || 0
                )
        };

    }


    // ==================================================
    // VALIDAR FORMULÁRIO
    // ==================================================

    function validarFormularioDepartamento(
        dados
    ) {

        if (dados.codigo === "") {

            throw new Error(
                "Informe o código do departamento."
            );

        }

        if (dados.codigo.length > 20) {

            throw new Error(
                "O código não pode exceder 20 caracteres."
            );

        }

        if (dados.nome === "") {

            throw new Error(
                "Informe o nome do departamento."
            );

        }

        if (dados.nome.length > 150) {

            throw new Error(
                "O nome não pode exceder 150 caracteres."
            );

        }

        if (dados.status_id <= 0) {

            throw new Error(
                "Selecione o estado do departamento."
            );

        }

        if (
            dados.id > 0
            && dados.departamento_pai_id
            === dados.id
        ) {

            throw new Error(
                "O departamento não pode ser superior de si próprio."
            );

        }

    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    async function confirmarEliminarDepartamento(
        departamentoId
    ) {

        const departamento =
            departamentosCache.find(
                function (item) {

                    return Number(item.id)
                        === Number(
                            departamentoId
                        );

                }
            );

        if (!departamento) {

            mostrarErroDepartamento(
                "Departamento não encontrado."
            );

            return;
        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Eliminar departamento?",

                html:
                    `
                        <p>
                            Pretende eliminar o departamento:
                        </p>

                        <strong>
                            ${escaparHtmlDepartamento(
                        departamento.codigo
                        + " - "
                        + departamento.nome
                    )}
                        </strong>

                        <p class="mt-3 mb-0">
                            A operação será bloqueada se o departamento
                            possuir subdepartamentos ou funcionários.
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

        mostrarCarregamentoDepartamentos(true);

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
                    departamentoId
                )
            );

            const resposta =
                await requisicaoDepartamentos(
                    DEPARTAMENTOS_URL,
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
                    "Departamento eliminado",

                text:
                    resposta.mensagem
                    || "Departamento eliminado com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await recarregarDepartamentos();

        } catch (erro) {

            mostrarErroDepartamento(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoDepartamentos(false);

        }

    }


    // ==================================================
    // DETALHES
    // ==================================================

    function mostrarDetalhesDepartamento(
        departamentoId
    ) {

        const departamento =
            departamentosCache.find(
                function (item) {

                    return Number(item.id)
                        === Number(
                            departamentoId
                        );

                }
            );

        if (!departamento) {

            mostrarErroDepartamento(
                "Departamento não encontrado."
            );

            return;
        }

        Swal.fire({
            icon:
                "info",

            title:
                escaparHtmlDepartamento(
                    departamento.nome
                    || "Departamento"
                ),

            html:
                `
                    <div class="text-left">

                        <div class="mb-2">
                            <strong>Código:</strong>
                            ${escaparHtmlDepartamento(
                    departamento.codigo
                    || "-"
                )}
                        </div>

                        <div class="mb-2">
                            <strong>Departamento superior:</strong>
                            ${escaparHtmlDepartamento(
                    departamento
                        .departamento_pai_nome
                    || "Departamento principal"
                )}
                        </div>

                        <div class="mb-2">
                            <strong>Filial:</strong>
                            ${escaparHtmlDepartamento(
                    departamento.filial_nome
                    || "Todas"
                )}
                        </div>

                        <div class="mb-2">
                            <strong>Centro de custo:</strong>
                            ${escaparHtmlDepartamento(
                    departamento
                        .centro_custo_nome
                    || "Não associado"
                )}
                        </div>

                        <div class="mb-2">
                            <strong>Gestor:</strong>
                            ${escaparHtmlDepartamento(
                    departamento.gestor_nome
                    || "Não definido"
                )}
                        </div>

                        <div class="mb-2">
                            <strong>Estado:</strong>
                            ${escaparHtmlDepartamento(
                    departamento.status_nome
                    || "-"
                )}
                        </div>

                        <div class="mb-2">
                            <strong>Descrição:</strong>
                            ${escaparHtmlDepartamento(
                    departamento.descricao
                    || "Sem descrição"
                )}
                        </div>

                        <div>
                            <strong>Criado em:</strong>
                            ${formatarDataHoraDepartamento(
                    departamento.criado_em
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

    function limparFormularioDepartamento() {

        const formulario =
            $("#formDepartamento")[0];

        if (formulario) {
            formulario.reset();
        }

        $("#departamentoId")
            .val("");

        $("#formDepartamento")
            .find(".is-invalid")
            .removeClass(
                "is-invalid"
            );

        $("#modalDepartamentoTitulo")
            .text(
                "Novo Departamento"
            );

        $("#btnGuardarDepartamentoTexto")
            .text(
                "Guardar Departamento"
            );

        preencherDepartamentosPai(
            departamentosCache,
            null
        );

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoDepartamentos(
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
                || "Não foi possível processar o departamento."
            );
        }

        return json;

    }


    // ==================================================
    // CARREGAMENTO
    // ==================================================

    function mostrarCarregamentoDepartamentos(
        mostrar
    ) {

        $("#departamentosCarregando")
            .toggle(
                Boolean(mostrar)
            );

        $("#btnNovoDepartamento")
            .prop(
                "disabled",
                Boolean(mostrar)
            );

    }


    function bloquearModalDepartamento(
        bloquear
    ) {

        $("#formDepartamento")
            .find(
                "input, textarea, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnGuardarDepartamentoSpinner")
            .toggle(
                Boolean(bloquear)
            );

    }


    // ==================================================
    // ERROS
    // ==================================================

    function mostrarErroDepartamento(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível processar",

            text:
                mensagem
                || "Ocorreu um erro na gestão de departamentos.",

            confirmButtonText:
                "Fechar"
        });

    }


    // ==================================================
    // HELPERS
    // ==================================================

    function definirTextoDepartamento(
        seletor,
        valor
    ) {

        $(seletor).text(
            valor
        );

    }


    function inteiroOpcionalDepartamento(
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


    function normalizarTextoDepartamento(
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


    function formatarDataHoraDepartamento(
        data
    ) {

        if (!data) {
            return "-";
        }

        const objeto =
            new Date(
                String(data)
                    .replace(
                        " ",
                        "T"
                    )
            );

        if (
            Number.isNaN(
                objeto.getTime()
            )
        ) {
            return escaparHtmlDepartamento(
                data
            );
        }

        return objeto.toLocaleString(
            "pt-PT",
            {
                day:
                    "2-digit",

                month:
                    "2-digit",

                year:
                    "numeric",

                hour:
                    "2-digit",

                minute:
                    "2-digit"
            }
        );

    }


    function escaparHtmlDepartamento(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);