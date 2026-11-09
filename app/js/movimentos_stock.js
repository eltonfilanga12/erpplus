"use strict";


const MOVIMENTO_STOCK_URL =
    "/erpplus/app/ajax/movimento_stock_ajax.php";


let tabelaMovimentosStock =
    null;


/*
|--------------------------------------------------------------------------
| INICIALIZAÇÃO
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    async function () {

        registrarEventosMovimentosStock();

        await Promise.all([
            carregarArmazensMovimentosStock(),
            carregarResumoMovimentosStock()
        ]);

        await carregarMovimentosStock();
        await abrirMovimentoDaUrl();
    }
);

async function abrirMovimentoDaUrl() {

    const parametros =
        new URLSearchParams(
            window.location.search
        );

    const movimentoId =
        Number(
            parametros.get(
                "movimento_id"
            )
        );

    if (
        !movimentoId
        || movimentoId <= 0
    ) {
        return;
    }

    try {

        await carregarDetalhesMovimentoStock(
            movimentoId
        );

    } catch (erro) {

        console.error(
            "Erro ao abrir movimento da URL:",
            erro
        );

        Swal.fire({
            icon: "error",
            title: "Movimento não encontrado",
            text:
                erro.message
                || "Não foi possível abrir o movimento solicitado."
        });
    }
}

/*
|--------------------------------------------------------------------------
| EVENTOS
|--------------------------------------------------------------------------
*/

function registrarEventosMovimentosStock() {

    $("#filtroTipoMovimento")
        .on(
            "change",
            carregarMovimentosStock
        );


    $("#filtroArmazemMovimento")
        .on(
            "change",
            carregarMovimentosStock
        );


    $("#filtroDataInicioMovimento")
        .on(
            "change",
            carregarMovimentosStock
        );


    $("#filtroDataFimMovimento")
        .on(
            "change",
            carregarMovimentosStock
        );


    $("#btnAtualizarMovimentosStock")
        .on(
            "click",
            async function () {

                const botao = this;

                const htmlOriginal =
                    botao.innerHTML;


                botao.disabled = true;

                botao.innerHTML = `
                    <i class="fa fa-spinner fa-spin"></i>
                    Atualizando...
                `;


                try {

                    await Promise.all([
                        carregarResumoMovimentosStock(),
                        carregarMovimentosStock()
                    ]);

                } finally {

                    botao.disabled = false;

                    botao.innerHTML =
                        htmlOriginal;
                }
            }
        );


    $(document)
        .on(
            "click",
            ".btnDetalhesMovimentoStock",
            function () {

                carregarDetalhesMovimentoStock(
                    Number(
                        $(this).data("id")
                    )
                );
            }
        );
}


/*
|--------------------------------------------------------------------------
| LISTAR
|--------------------------------------------------------------------------
*/

async function carregarMovimentosStock() {

    esconderErroMovimentosStock();


    const tbody =
        document.getElementById(
            "movimentosStockBody"
        );


    if (tbody) {

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="12"
                    class="text-center"
                >
                    <i class="fa fa-spinner fa-spin"></i>
                    A carregar movimentos...
                </td>
            </tr>
        `;
    }


    try {

        const parametros = {

            tipo:
                $("#filtroTipoMovimento")
                    .val(),

            armazem_id:
                $("#filtroArmazemMovimento")
                    .val(),

            data_inicio:
                $("#filtroDataInicioMovimento")
                    .val(),

            data_fim:
                $("#filtroDataFimMovimento")
                    .val()
        };


        const resposta =
            await requisicaoMovimentoStock(
                "listar",
                parametros
            );


        const dados =
            Array.isArray(
                resposta.dados
            )
                ? resposta.dados
                : [];


        renderizarMovimentosStock(
            dados
        );


    } catch (erro) {

        mostrarErroMovimentosStock(
            erro.message
        );
    }
}


/*
|--------------------------------------------------------------------------
| RENDER
|--------------------------------------------------------------------------
*/

function renderizarMovimentosStock(
    dados
) {

    destruirDataTableMovimentosStock();


    const tbody =
        document.getElementById(
            "movimentosStockBody"
        );


    if (!tbody) {
        return;
    }


    if (
        !Array.isArray(dados)
        || dados.length === 0
    ) {

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="12"
                    class="text-center text-muted"
                >
                    Nenhum movimento encontrado.
                </td>
            </tr>
        `;

        return;
    }


    tbody.innerHTML =
        dados
            .map(
                criarLinhaMovimentoStock
            )
            .join("");


    inicializarDataTableMovimentosStock();
}


function criarLinhaMovimentoStock(
    movimento,
    numero
) {

    return `
        <tr>

            <td>
                ${numero}
            </td>

            <td>
                ${formatarDataHoraMovimentoStock(
        movimento.data_movimento
    )}
            </td>

            <td>
                ${badgeTipoMovimentoStock(
        movimento.tipo
    )}
            </td>

            <td>
                ${escaparHtmlMovimentoStock(
        movimento.armazem_origem_nome
        || "-"
    )}
            </td>

            <td>
                ${escaparHtmlMovimentoStock(
        movimento.armazem_destino_nome
        || "-"
    )}
            </td>

            <td>
                ${Number(
        movimento.total_itens
        || 0
    )}
            </td>

            <td>
                ${formatarQuantidadeMovimentoStock(
        movimento.quantidade_total
    )}
            </td>

            <td>
                ${formatarMoedaMovimentoStock(
        movimento.valor_total
    )}
            </td>

            <td>
                ${escaparHtmlMovimentoStock(
        movimento.usuario_nome
        || "-"
    )}
            </td>

            <td>
                ${badgeStatusMovimentoStock(
        movimento.status_nome
    )}
            </td>

            <td>
                ${escaparHtmlMovimentoStock(
        movimento.observacoes
        || "-"
    )}
            </td>

            <td class="text-right">

                <button
                    type="button"
                    class="btn btn-sm btn-info btnDetalhesMovimentoStock"
                    data-id="${movimento.id}"
                    title="Detalhes"
                >
                    <i class="fa fa-eye"></i>
                </button>

            </td>

        </tr>
    `;
}


/*
|--------------------------------------------------------------------------
| DETALHES
|--------------------------------------------------------------------------
*/

async function carregarDetalhesMovimentoStock(
    id
) {

    try {

        const resposta =
            await requisicaoMovimentoStock(
                "buscar",
                {
                    id: id
                }
            );


        const movimento =
            resposta.dados
                ?.movimento;


        const itens =
            Array.isArray(
                resposta.dados
                    ?.itens
            )
                ? resposta.dados.itens
                : [];


        if (!movimento) {

            throw new Error(
                "Movimento não encontrado."
            );
        }


        $("#detalheMovimentoId")
            .text(
                "#" + movimento.id
            );


        $("#detalheMovimentoTipo")
            .html(
                badgeTipoMovimentoStock(
                    movimento.tipo
                )
            );


        $("#detalheMovimentoData")
            .text(
                formatarDataHoraMovimentoStock(
                    movimento.data_movimento
                )
            );


        $("#detalheMovimentoUsuario")
            .text(
                movimento.usuario_nome
                || "-"
            );


        $("#detalheMovimentoOrigem")
            .text(
                movimento.armazem_origem_nome
                || "-"
            );


        $("#detalheMovimentoDestino")
            .text(
                movimento.armazem_destino_nome
                || "-"
            );


        $("#detalheMovimentoStatus")
            .html(
                badgeStatusMovimentoStock(
                    movimento.status_nome
                )
            );


        $("#detalheMovimentoObservacoes")
            .text(
                movimento.observacoes
                || "-"
            );


        renderizarItensMovimentoStock(
            itens
        );


        $("#modalDetalhesMovimentoStock")
            .modal("show");


    } catch (erro) {

        Swal.fire({
            icon: "error",
            title: "Erro!",
            text:
                erro.message
                || "Erro ao carregar movimento."
        });
    }
}


/*
|--------------------------------------------------------------------------
| ITENS
|--------------------------------------------------------------------------
*/

function renderizarItensMovimentoStock(
    itens
) {

    const tbody =
        document.getElementById(
            "detalheMovimentoItens"
        );


    if (!tbody) {
        return;
    }


    if (
        !Array.isArray(itens)
        || itens.length === 0
    ) {

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="6"
                    class="text-center text-muted"
                >
                    Nenhum item encontrado.
                </td>
            </tr>
        `;

        return;
    }


    tbody.innerHTML =
        itens.map(
            item => `

                <tr>

                    <td>
                        ${escaparHtmlMovimentoStock(
                item.produto_nome
                || "-"
            )}
                    </td>

                    <td>
                        ${escaparHtmlMovimentoStock(
                item.sku
                || "-"
            )}
                    </td>

                    <td>
                        ${item.unidade_codigo
                    ? escaparHtmlMovimentoStock(
                        item.unidade_codigo
                    )
                    : "-"
                }
                    </td>

                    <td>
                        ${formatarQuantidadeMovimentoStock(
                    item.quantidade,
                    item.casas_decimais
                )}
                    </td>

                    <td>
                        ${formatarMoedaMovimentoStock(
                    item.custo_unitario
                )}
                    </td>

                    <td>
                        ${formatarMoedaMovimentoStock(
                    item.subtotal
                )}
                    </td>

                </tr>
            `
        ).join("");
}


/*
|--------------------------------------------------------------------------
| RESUMO
|--------------------------------------------------------------------------
*/

async function carregarResumoMovimentosStock() {

    try {

        const resposta =
            await requisicaoMovimentoStock(
                "resumo"
            );


        const dados =
            resposta.dados
            || {};


        atualizarTextoMovimentoStock(
            "resumoEntradas",
            dados.entradas || 0
        );


        atualizarTextoMovimentoStock(
            "resumoSaidas",
            dados.saidas || 0
        );


        atualizarTextoMovimentoStock(
            "resumoTransferencias",
            dados.transferencias || 0
        );


        atualizarTextoMovimentoStock(
            "resumoAjustes",
            dados.ajustes || 0
        );


    } catch (erro) {

        console.error(
            "Erro no resumo:",
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| ARMAZÉNS
|--------------------------------------------------------------------------
*/

async function carregarArmazensMovimentosStock() {

    const select =
        document.getElementById(
            "filtroArmazemMovimento"
        );


    if (!select) {
        return;
    }


    try {

        const resposta =
            await requisicaoMovimentoStock(
                "armazens"
            );


        const dados =
            Array.isArray(
                resposta.dados
            )
                ? resposta.dados
                : [];


        select.replaceChildren(
            new Option(
                "Todos",
                ""
            )
        );


        dados.forEach(
            armazem => {

                select.add(
                    new Option(
                        armazem.nome,
                        String(armazem.id)
                    )
                );
            }
        );


    } catch (erro) {

        console.error(
            "Erro ao carregar armazéns:",
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| HTTP
|--------------------------------------------------------------------------
*/

async function requisicaoMovimentoStock(
    acao,
    parametros = {}
) {

    const url =
        new URL(
            MOVIMENTO_STOCK_URL,
            window.location.origin
        );


    url.searchParams.set(
        "acao",
        acao
    );


    Object.entries(
        parametros
    ).forEach(
        ([chave, valor]) => {

            if (
                valor !== null
                && valor !== undefined
                && valor !== ""
            ) {

                url.searchParams.set(
                    chave,
                    valor
                );
            }
        }
    );


    const resposta =
        await fetch(
            url.toString(),
            {
                headers: {
                    Accept:
                        "application/json"
                }
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

    } catch {

        console.error(
            "Resposta inválida:",
            texto
        );


        throw new Error(
            "O servidor devolveu uma resposta inválida."
        );
    }


    if (
        !resposta.ok
        || !json.sucesso
    ) {

        throw new Error(
            json.mensagem
            || "Não foi possível concluir a operação."
        );
    }


    return json;
}


/*
|--------------------------------------------------------------------------
| DATATABLE
|--------------------------------------------------------------------------
*/

function inicializarDataTableMovimentosStock() {

    if (
        typeof $.fn.DataTable
        === "undefined"
    ) {
        return;
    }


    tabelaMovimentosStock =
        $("#movimentosStockTable")
            .DataTable({

                pageLength: 10,

                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],

                order: [
                    [1, "desc"]
                ],

                responsive: true,

                rowCallback: function (
                    row,
                    data,
                    displayIndex,
                    displayIndexFull
                ) {

                    const numero =
                        displayIndexFull + 1;

                    $("td:eq(0)", row).html(
                        String(numero).padStart(4, "0")
                    );
                },

                language: {
                    url:
                        "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
                },

                columnDefs: [
                    {
                        targets: [11],
                        orderable: false,
                        searchable: false
                    }
                ],

                dom:
                    "<'row'<'col-md-8 d-flex align-items-center'Bl><'col-md-4'f>>"
                    +
                    "<'row'<'col-md-12'tr>>"
                    +
                    "<'row'<'col-md-5'i><'col-md-7'p>>",

                buttons: [

                    {
                        extend:
                            "excelHtml5",

                        text:
                            '<i class="fa fa-file-excel"></i> Excel',

                        title:
                            "Movimentos de Stock",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                        }
                    },

                    {
                        extend:
                            "pdfHtml5",

                        text:
                            '<i class="fa fa-file-pdf"></i> PDF',

                        title:
                            "Movimentos de Stock",

                        orientation:
                            "landscape",

                        pageSize:
                            "A4",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                        }
                    },

                    {
                        extend:
                            "print",

                        text:
                            '<i class="fa fa-print"></i> Imprimir',

                        title:
                            "Movimentos de Stock",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                        }
                    }

                ]
            });
}


function destruirDataTableMovimentosStock() {

    if (
        $.fn.DataTable
        && $.fn.DataTable
            .isDataTable(
                "#movimentosStockTable"
            )
    ) {

        $("#movimentosStockTable")
            .DataTable()
            .destroy();


        tabelaMovimentosStock =
            null;
    }
}


/*
|--------------------------------------------------------------------------
| BADGES
|--------------------------------------------------------------------------
*/

function badgeTipoMovimentoStock(
    tipo
) {

    switch (
    String(
        tipo
        || ""
    ).toUpperCase()
    ) {

        case "ENTRADA":

            return `
                <span class="badge badge-success">
                    Entrada
                </span>
            `;


        case "SAIDA":

            return `
                <span class="badge badge-danger">
                    Saída
                </span>
            `;


        case "TRANSFERENCIA":

            return `
                <span class="badge badge-info">
                    Transferência
                </span>
            `;


        case "AJUSTE":

            return `
                <span class="badge badge-warning">
                    Ajuste
                </span>
            `;


        default:

            return `
                <span class="badge badge-secondary">
                    ${escaparHtmlMovimentoStock(tipo)}
                </span>
            `;
    }
}


function badgeStatusMovimentoStock(
    status
) {

    const texto =
        String(
            status
            || "-"
        );


    const normalizado =
        texto
            .trim()
            .toLowerCase();


    const ativo =
        [
            "ativo",
            "activo",
            "concluido",
            "concluído",
            "processado",
            "aprovado"
        ].includes(
            normalizado
        );


    return `
        <span
            class="badge ${ativo
            ? "badge-success"
            : "badge-secondary"
        }"
        >
            ${escaparHtmlMovimentoStock(texto)}
        </span>
    `;
}


/*
|--------------------------------------------------------------------------
| FORMATAÇÃO
|--------------------------------------------------------------------------
*/

function formatarQuantidadeMovimentoStock(
    valor,
    casasDecimais = 3
) {

    let numero =
        Number(valor || 0);


    let casas =
        Number(
            casasDecimais
            ?? 3
        );


    if (
        !Number.isFinite(casas)
        || casas < 0
    ) {
        casas = 3;
    }


    return numero.toLocaleString(
        "pt-PT",
        {
            minimumFractionDigits: 0,
            maximumFractionDigits:
                Math.min(casas, 6),
            useGrouping: false
        }
    );
}


function formatarMoedaMovimentoStock(
    valor
) {

    return Number(
        valor || 0
    ).toLocaleString(
        "pt-MZ",
        {
            style: "currency",
            currency: "MZN"
        }
    );
}


function formatarDataHoraMovimentoStock(
    valor
) {

    if (!valor) {
        return "-";
    }


    const data =
        new Date(
            String(valor)
                .replace(
                    " ",
                    "T"
                )
        );


    if (
        Number.isNaN(
            data.getTime()
        )
    ) {

        return escaparHtmlMovimentoStock(
            valor
        );
    }


    return data.toLocaleString(
        "pt-MZ"
    );
}


/*
|--------------------------------------------------------------------------
| ERRO
|--------------------------------------------------------------------------
*/

function mostrarErroMovimentosStock(
    mensagem
) {

    const elemento =
        document.getElementById(
            "erroMovimentosStock"
        );


    if (!elemento) {
        return;
    }


    elemento.textContent =
        mensagem;


    elemento.classList.remove(
        "d-none"
    );
}


function esconderErroMovimentosStock() {

    const elemento =
        document.getElementById(
            "erroMovimentosStock"
        );


    if (!elemento) {
        return;
    }


    elemento.textContent =
        "";


    elemento.classList.add(
        "d-none"
    );
}


function atualizarTextoMovimentoStock(
    id,
    valor
) {

    const elemento =
        document.getElementById(
            id
        );


    if (elemento) {

        elemento.textContent =
            valor;
    }
}


function escaparHtmlMovimentoStock(
    valor
) {

    return String(
        valor
        ?? ""
    )
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}