"use strict";

const CENTRO_CUSTO_URL =
    "/erpplus/app/ajax/centro_custo_ajax.php";

const CENTRO_CUSTO_FILIAL_URL =
    "/erpplus/app/ajax/filial_ajax.php";

let tabelaCentrosCusto = null;


// ==================================================
// INICIALIZAÇÃO
// ==================================================

$(document).ready(function () {

    inicializarTabelaCentrosCusto();

    carregarStatusCentroCusto();

    carregarFiliaisCentroCusto();

    carregarCentrosSelect();

    carregarCentrosCusto();

    configurarEventosCentroCusto();

});


// ==================================================
// CONFIGURAR EVENTOS
// ==================================================

function configurarEventosCentroCusto() {

    $("#btnNovoCentroCusto").on(
        "click",
        abrirNovoCentroCusto
    );


    $("#btnFiltrarCentro").on(
        "click",
        carregarCentrosCusto
    );


    $("#btnLimparFiltroCentro").on(
        "click",
        limparFiltrosCentroCusto
    );


    $("#formCentroCusto").on(
        "submit",
        guardarCentroCusto
    );


    $("#formEliminarCentroCusto").on(
        "submit",
        eliminarCentroCusto
    );


    $("#modalCentroCusto").on(
        "hidden.bs.modal",
        function () {

            limparFormularioCentroCusto();

        }
    );


    $(document).on(
        "click",
        ".btn-ver-centro",
        function () {

            const id =
                Number(
                    $(this).data("id")
                );

            verCentroCusto(id);

        }
    );


    $(document).on(
        "click",
        ".btn-editar-centro",
        function () {

            const id =
                Number(
                    $(this).data("id")
                );

            editarCentroCusto(id);

        }
    );


    $(document).on(
        "click",
        ".btn-eliminar-centro",
        function () {

            const id =
                Number(
                    $(this).data("id")
                );

            abrirModalEliminarCentroCusto(
                id
            );

        }
    );

}


// ==================================================
// REQUISIÇÃO PADRÃO
// ==================================================

async function requisicaoCentroCusto(
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
        !resposta.ok
        || json.success === false
    ) {
        throw new Error(
            json.mensagem
            || json.message
            || "Não foi possível concluir a operação."
        );
    }

    return json;
}


// ==================================================
// INICIALIZAR DATATABLE
// ==================================================

function inicializarTabelaCentrosCusto() {

    tabelaCentrosCusto =
        $("#centrosCustoTable")
            .DataTable({

                data: [],

                columns: [

                    {
                        data: null,
                        orderable: false,
                        searchable: false,

                        render: function (
                            data,
                            type,
                            row,
                            meta
                        ) {
                            return (
                                meta.row
                                + meta.settings._iDisplayStart
                                + 1
                            );
                        }
                    },

                    {
                        data:
                            "codigo",

                        render: function (
                            valor
                        ) {

                            return `
                                <strong>
                                    ${escaparHtmlCentro(valor || "-")}
                                </strong>
                            `;

                        }
                    },

                    {
                        data:
                            "nome",

                        render: function (
                            valor,
                            tipo,
                            row
                        ) {

                            const prefixo =
                                row.centro_pai_id
                                    ? '<span class="text-muted mr-1">↳</span>'
                                    : "";

                            return `
                                ${prefixo}
                                ${escaparHtmlCentro(valor || "-")}
                            `;

                        }
                    },

                    {
                        data:
                            "centro_pai",

                        defaultContent:
                            "Centro Principal"
                    },

                    {
                        data:
                            "tipo",

                        render:
                            renderTipoCentroCusto
                    },

                    {
                        data:
                            "filial",

                        defaultContent:
                            "Todas / Sede Principal"
                    },

                    {
                        data:
                            "responsavel",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "orcamento_mensal",

                        className:
                            "text-right",

                        render:
                            formatarMoedaCentroCusto
                    },

                    {
                        data:
                            "status",

                        render:
                            renderStatusCentroCusto
                    },

                    {
                        data:
                            "id",

                        className:
                            "text-right",

                        orderable:
                            false,

                        searchable:
                            false,

                        render: function (
                            id
                        ) {

                            return `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-secondary btn-ver-centro"
                                    data-id="${id}"
                                    title="Ver detalhes">

                                    <i class="fa fa-eye"></i>

                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-info btn-editar-centro"
                                    data-id="${id}"
                                    title="Editar">

                                    <i class="fa fa-pencil"></i>

                                </button>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger btn-eliminar-centro"
                                    data-id="${id}"
                                    title="Eliminar">

                                    <i class="fa fa-trash"></i>

                                </button>
                            `;

                        }
                    }

                ],

                order: [
                    [1, "asc"]
                ],

                pageLength:
                    10,

                lengthMenu: [
                    [5, 10, 25, 50, 100],
                    [5, 10, 25, 50, 100]
                ],

                responsive:
                    true,

                autoWidth:
                    false,

                dom:
                    "<'row'<'col-sm-12 col-md-8 d-flex align-items-center'Bl><'col-sm-12 col-md-4'f>>"
                    + "<'row'<'col-sm-12'tr>>"
                    + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",

                buttons: [

                    {
                        extend:
                            "excelHtml5",

                        text:
                            '<i class="fa fa-file-excel"></i> Excel',

                        title:
                            "Centros de Custo",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4, 5, 6, 7, 8]
                        }
                    },

                    {
                        extend:
                            "pdfHtml5",

                        text:
                            '<i class="fa fa-file-pdf"></i> PDF',

                        title:
                            "Centros de Custo",

                        orientation:
                            "landscape",

                        pageSize:
                            "A4",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4, 5, 6, 7, 8]
                        }
                    },

                    {
                        extend:
                            "print",

                        text:
                            '<i class="fa fa-print"></i> Imprimir',

                        title:
                            "Centros de Custo",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4, 5, 6, 7, 8]
                        }
                    }

                ],

                language: {
                    url:
                        "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
                }

            });

}


// ==================================================
// CARREGAR LISTAGEM E RESUMO
// ==================================================

async function carregarCentrosCusto() {

    const botao =
        $("#btnFiltrarCentro");

    bloquearBotaoCentro(
        botao,
        "A consultar..."
    );

    try {

        const filtros =
            obterFiltrosCentroCusto();

        const query =
            new URLSearchParams({
                acao:
                    "listar",

                ...filtros
            });

        const queryResumo =
            new URLSearchParams({
                acao:
                    "resumo",

                ...filtros
            });

        const resultados =
            await Promise.all([

                requisicaoCentroCusto(
                    CENTRO_CUSTO_URL
                    + "?"
                    + query.toString()
                ),

                requisicaoCentroCusto(
                    CENTRO_CUSTO_URL
                    + "?"
                    + queryResumo.toString()
                )

            ]);

        const lista =
            resultados[0].data || [];

        const resumo =
            resultados[1].data || {};

        atualizarTabelaCentrosCusto(
            lista
        );

        preencherResumoCentroCusto(
            resumo
        );

    } catch (erro) {

        mensagemErroCentroCusto(
            erro.message
        );

    } finally {

        restaurarBotaoCentro(
            botao,
            `
                <i class="fa fa-search"></i>
                Consultar
            `
        );

    }
}


// ==================================================
// ATUALIZAR DATATABLE
// ==================================================

function atualizarTabelaCentrosCusto(
    dados
) {
    if (!tabelaCentrosCusto) {
        return;
    }

    tabelaCentrosCusto
        .clear()
        .rows
        .add(
            Array.isArray(dados)
                ? dados
                : []
        )
        .draw();
}


// ==================================================
// PREENCHER CARDS
// ==================================================

function preencherResumoCentroCusto(
    resumo
) {
    $("#totalCentrosCusto")
        .text(
            Number(
                resumo.total_centros || 0
            )
        );

    $("#totalCentrosActivos")
        .text(
            Number(
                resumo.total_activos || 0
            )
        );

    $("#orcamentoTotalCentros")
        .text(
            formatarMoedaCentroCusto(
                resumo.orcamento_total || 0
            )
        );

    $("#totalCentrosFilhos")
        .text(
            Number(
                resumo.total_subcentros || 0
            )
        );
}


// ==================================================
// ABRIR NOVO
// ==================================================

async function abrirNovoCentroCusto() {

    limparFormularioCentroCusto();

    $("#tituloModalCentroCusto")
        .text(
            "Novo Centro de Custo"
        );

    await carregarCentrosSelect();

    selecionarStatusActivoCentro();

    $("#modalCentroCusto")
        .modal("show");
}


// ==================================================
// GUARDAR
// ==================================================

async function guardarCentroCusto(
    evento
) {
    evento.preventDefault();

    const formulario =
        evento.currentTarget;

    const botao =
        $("#btnGuardarCentroCusto");

    bloquearBotaoCentro(
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
            "guardar"
        );

        const resposta =
            await requisicaoCentroCusto(
                CENTRO_CUSTO_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );

        $("#modalCentroCusto")
            .modal("hide");

        await carregarCentrosCusto();

        await carregarCentrosSelect();

        toastCentroCusto(
            resposta.mensagem
            || "Centro de custo guardado com sucesso."
        );

    } catch (erro) {

        mensagemErroCentroCusto(
            erro.message
        );

    } finally {

        restaurarBotaoCentro(
            botao,
            `
                <i class="fa fa-save"></i>
                Guardar
            `
        );

    }
}


// ==================================================
// EDITAR
// ==================================================

async function editarCentroCusto(
    id
) {
    try {

        const resposta =
            await requisicaoCentroCusto(
                CENTRO_CUSTO_URL
                + "?acao=buscar&id="
                + encodeURIComponent(id)
            );

        const centro =
            resposta.data;

        if (!centro) {
            throw new Error(
                "Centro de custo não encontrado."
            );
        }

        limparFormularioCentroCusto();

        await carregarCentrosSelect(id);

        $("#centroCustoId")
            .val(
                centro.id
            );

        $("#codigo")
            .val(
                centro.codigo || ""
            );

        $("#nome")
            .val(
                centro.nome || ""
            );

        $("#centro_pai_id")
            .val(
                centro.centro_pai_id || ""
            );

        $("#filial_id")
            .val(
                centro.filial_id || ""
            );

        $("#tipo")
            .val(
                centro.tipo || "CUSTO"
            );

        $("#status_id")
            .val(
                centro.status_id || ""
            );

        $("#orcamento_mensal")
            .val(
                Number(
                    centro.orcamento_mensal || 0
                ).toFixed(2)
            );

        $("#responsavel")
            .val(
                centro.responsavel || ""
            );

        $("#descricao")
            .val(
                centro.descricao || ""
            );

        $("#tituloModalCentroCusto")
            .text(
                "Editar Centro de Custo"
            );

        $("#modalCentroCusto")
            .modal("show");

    } catch (erro) {

        mensagemErroCentroCusto(
            erro.message
        );

    }
}


// ==================================================
// VER DETALHES
// ==================================================

async function verCentroCusto(
    id
) {
    try {

        const resposta =
            await requisicaoCentroCusto(
                CENTRO_CUSTO_URL
                + "?acao=buscar&id="
                + encodeURIComponent(id)
            );

        const centro =
            resposta.data;

        $("#detalheCentroCodigo")
            .text(
                centro.codigo || "-"
            );

        $("#detalheCentroNome")
            .text(
                centro.nome || "-"
            );

        $("#detalheCentroTipo")
            .html(
                renderTipoCentroCusto(
                    centro.tipo
                )
            );

        $("#detalheCentroStatus")
            .html(
                renderStatusCentroCusto(
                    centro.status
                )
            );

        $("#detalheCentroPai")
            .text(
                centro.centro_pai
                || "Centro Principal"
            );

        $("#detalheCentroFilial")
            .text(
                centro.filial
                || "Todas / Sede Principal"
            );

        $("#detalheCentroResponsavel")
            .text(
                centro.responsavel || "-"
            );

        $("#detalheCentroOrcamento")
            .text(
                formatarMoedaCentroCusto(
                    centro.orcamento_mensal || 0
                )
            );

        $("#detalheCentroDescricao")
            .text(
                centro.descricao || "-"
            );

        $("#modalDetalhesCentroCusto")
            .modal("show");

    } catch (erro) {

        mensagemErroCentroCusto(
            erro.message
        );

    }
}


// ==================================================
// ELIMINAR
// ==================================================

function abrirModalEliminarCentroCusto(
    id
) {
    $("#eliminarCentroCustoId")
        .val(id);

    $("#modalEliminarCentroCusto")
        .modal("show");
}


async function eliminarCentroCusto(
    evento
) {
    evento.preventDefault();

    const botao =
        $("#btnConfirmarEliminarCentro");

    bloquearBotaoCentro(
        botao,
        "A eliminar..."
    );

    try {

        const dados =
            new FormData(
                evento.currentTarget
            );

        dados.append(
            "acao",
            "eliminar"
        );

        const resposta =
            await requisicaoCentroCusto(
                CENTRO_CUSTO_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );

        $("#modalEliminarCentroCusto")
            .modal("hide");

        await carregarCentrosCusto();

        await carregarCentrosSelect();

        toastCentroCusto(
            resposta.mensagem
            || "Centro de custo eliminado com sucesso."
        );

    } catch (erro) {

        mensagemErroCentroCusto(
            erro.message
        );

    } finally {

        restaurarBotaoCentro(
            botao,
            `
                <i class="fa fa-trash"></i>
                Eliminar
            `
        );

    }
}


// ==================================================
// CARREGAR CENTROS PARA SELECT
// ==================================================

async function carregarCentrosSelect(
    excluirId = null
) {
    try {

        let url =
            CENTRO_CUSTO_URL
            + "?acao=listar_select";

        if (
            excluirId !== null
            && Number(excluirId) > 0
        ) {
            url +=
                "&excluir_id="
                + encodeURIComponent(
                    excluirId
                );
        }

        const resposta =
            await requisicaoCentroCusto(
                url
            );

        const select =
            $("#centro_pai_id");

        const valorAtual =
            select.val();

        select.html(`
            <option value="">
                Nenhum — Centro Principal
            </option>
        `);

        (resposta.data || [])
            .forEach(
                function (centro) {

                    select.append(`
                        <option value="${centro.id}">
                            ${escaparHtmlCentro(
                        centro.texto
                        || (
                            centro.codigo
                            + " - "
                            + centro.nome
                        )
                    )}
                        </option>
                    `);

                }
            );

        if (
            valorAtual
            && select.find(
                `option[value="${valorAtual}"]`
            ).length
        ) {
            select.val(
                valorAtual
            );
        }

    } catch (erro) {

        console.error(
            "Erro ao carregar centros:",
            erro
        );

    }
}


// ==================================================
// CARREGAR STATUS
// ==================================================

async function carregarStatusCentroCusto() {

    try {

        const resposta =
            await requisicaoCentroCusto(
                CENTRO_CUSTO_URL
                + "?acao=listar_status"
            );

        const status =
            resposta.data || [];

        const selectFormulario =
            $("#status_id");

        const selectFiltro =
            $("#filtroCentroStatus");

        selectFormulario.html(`
            <option value="">
                Selecione
            </option>
        `);

        selectFiltro.html(`
            <option value="">
                Todos os status
            </option>
        `);

        status.forEach(
            function (item) {

                const opcao = `
                    <option value="${item.id}">
                        ${escaparHtmlCentro(
                    item.nome
                )}
                    </option>
                `;

                selectFormulario.append(
                    opcao
                );

                selectFiltro.append(
                    opcao
                );

            }
        );

        selecionarStatusActivoCentro();

    } catch (erro) {

        mensagemErroCentroCusto(
            erro.message
        );

    }
}


function selecionarStatusActivoCentro() {

    const select =
        $("#status_id");

    const opcaoActivo =
        select
            .find("option")
            .filter(function () {

                const texto =
                    String(
                        $(this).text()
                    )
                        .trim()
                        .toLowerCase();

                return (
                    texto === "activo"
                    || texto === "ativo"
                );

            })
            .first();

    if (opcaoActivo.length) {

        select.val(
            opcaoActivo.val()
        );

    }
}


// ==================================================
// CARREGAR FILIAIS
// ==================================================

// Substitua a função carregarFiliaisCentroCusto no seu JS por esta:
async function carregarFiliaisCentroCusto() {
    try {
        const resposta = await fetch(
            CENTRO_CUSTO_FILIAL_URL + "?acao=listar",
            { credentials: "same-origin" }
        );

        const texto = await resposta.text();
        let json;

        try {
            json = JSON.parse(texto);
        } catch {
            console.warn("Resposta inválida das filiais:", texto);
            return;
        }

        // Verifica se a requisição foi bem-sucedida (aceita 'sucesso' ou 'success')
        const operacaoSucesso = json.sucesso ?? json.success ?? false;
        if (!operacaoSucesso && !Array.isArray(json)) {
            console.warn("Erro ao buscar filiais:", json.mensagem || json.message);
            return;
        }

        // Extrai a lista independentemente do nome da chave ('dados', 'data' ou array direto)
        const filiais = Array.isArray(json)
            ? json
            : (json.dados || json.data || []);

        const selectFormulario = $("#filial_id");
        const selectFiltro = $("#filtroCentroFilial");

        selectFormulario.html(`
            <option value="">
                Todas / Sede Principal
            </option>
        `);

        selectFiltro.html(`
            <option value="">
                Todas as filiais
            </option>
        `);

        filiais.forEach(function (filial) {
            const nome = escaparHtmlCentro(filial.nome || "Filial");
            const opcao = `<option value="${filial.id}">${nome}</option>`;

            selectFormulario.append(opcao);
            selectFiltro.append(opcao);
        });

    } catch (erro) {
        console.warn("Não foi possível carregar filiais:", erro);
    }
}


// ==================================================
// FILTROS
// ==================================================

function obterFiltrosCentroCusto() {

    return {

        tipo:
            $("#filtroCentroTipo")
                .val()
            || "",

        filial_id:
            $("#filtroCentroFilial")
                .val()
            || "",

        status_id:
            $("#filtroCentroStatus")
                .val()
            || ""

    };
}


function limparFiltrosCentroCusto() {

    $("#filtroCentroTipo")
        .val("");

    $("#filtroCentroFilial")
        .val("");

    $("#filtroCentroStatus")
        .val("");

    carregarCentrosCusto();
}


// ==================================================
// LIMPAR FORMULÁRIO
// ==================================================

function limparFormularioCentroCusto() {

    const formulario =
        document.getElementById(
            "formCentroCusto"
        );

    if (formulario) {
        formulario.reset();
    }

    $("#centroCustoId")
        .val("");

    $("#centro_pai_id")
        .val("");

    $("#filial_id")
        .val("");

    $("#tipo")
        .val("CUSTO");

    $("#orcamento_mensal")
        .val("0.00");

    $("#tituloModalCentroCusto")
        .text(
            "Novo Centro de Custo"
        );

    selecionarStatusActivoCentro();
}


// ==================================================
// BADGE DE TIPO
// ==================================================

function renderTipoCentroCusto(
    tipo
) {
    const valor =
        String(
            tipo || ""
        ).toUpperCase();

    const configuracao = {

        CUSTO: {
            classe:
                "danger",

            texto:
                "Custo"
        },

        RECEITA: {
            classe:
                "success",

            texto:
                "Receita"
        },

        MISTO: {
            classe:
                "info",

            texto:
                "Misto"
        }

    };

    const item =
        configuracao[valor]
        || {
            classe:
                "secondary",

            texto:
                valor || "-"
        };

    return `
        <span class="badge badge-${item.classe}">
            ${item.texto}
        </span>
    `;
}


// ==================================================
// BADGE DE STATUS
// ==================================================

function renderStatusCentroCusto(
    status
) {
    const texto =
        String(
            status || ""
        );

    const normalizado =
        texto
            .trim()
            .toLowerCase();

    let classe =
        "secondary";

    if (
        normalizado === "activo"
        || normalizado === "ativo"
    ) {

        classe =
            "success";

    } else if (
        normalizado === "inactivo"
        || normalizado === "inativo"
    ) {

        classe =
            "warning";

    } else if (
        normalizado === "encerrado"
    ) {

        classe =
            "danger";

    }

    return `
        <span class="badge badge-${classe}">
            ${escaparHtmlCentro(texto || "-")}
        </span>
    `;
}


// ==================================================
// FORMATAÇÃO DE MOEDA
// ==================================================

function formatarMoedaCentroCusto(
    valor
) {
    return Number(
        valor || 0
    ).toLocaleString(
        "pt-MZ",
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


// ==================================================
// MENSAGENS
// ==================================================

function mensagemErroCentroCusto(
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


function toastCentroCusto(
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

function bloquearBotaoCentro(
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


function restaurarBotaoCentro(
    botao,
    html
) {
    botao
        .prop(
            "disabled",
            false
        )
        .html(
            html
        );
}


// ==================================================
// ESCAPAR HTML
// ==================================================

function escaparHtmlCentro(
    valor
) {
    return $("<div>")
        .text(
            valor ?? ""
        )
        .html();
}