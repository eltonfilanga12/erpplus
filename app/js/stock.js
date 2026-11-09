"use strict";


const STOCK_URL =
    "/erpplus/app/ajax/stock_ajax.php";


let tabelaStock = null;


/*
|--------------------------------------------------------------------------
| INICIALIZAÇÃO
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    async function () {

        registrarEventosStock();

        await Promise.all([
            carregarArmazensStock(),
            carregarCategoriasStock(),
            carregarResumoStock()
        ]);

        await carregarStock();
    }
);


/*
|--------------------------------------------------------------------------
| EVENTOS
|--------------------------------------------------------------------------
*/

function registrarEventosStock() {

    $("#stockFiltroArmazem")
        .on(
            "change",
            carregarStock
        );


    $("#stockFiltroCategoria")
        .on(
            "change",
            carregarStock
        );


    $("#stockFiltroSituacao")
        .on(
            "change",
            carregarStock
        );


    $("#btnAtualizarStock")
        .on(
            "click",
            async function () {

                const botao = this;

                const htmlOriginal =
                    botao.innerHTML;


                botao.disabled =
                    true;


                botao.innerHTML = `
                    <i class="fa fa-spinner fa-spin"></i>
                    Atualizando...
                `;


                try {

                    await Promise.all([
                        carregarResumoStock(),
                        carregarStock()
                    ]);

                } finally {

                    botao.disabled =
                        false;

                    botao.innerHTML =
                        htmlOriginal;
                }
            }
        );
}


/*
|--------------------------------------------------------------------------
| CARREGAR STOCK
|--------------------------------------------------------------------------
*/

async function carregarStock() {

    esconderErroStock();


    const tbody =
        document.getElementById(
            "stockTabelaBody"
        );


    if (tbody) {

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="12"
                    class="text-center"
                >
                    <i class="fa fa-spinner fa-spin"></i>
                    A carregar stock...
                </td>
            </tr>
        `;
    }


    try {

        const parametros = {

            armazem_id:
                $("#stockFiltroArmazem")
                    .val(),

            categoria_id:
                $("#stockFiltroCategoria")
                    .val(),

            situacao:
                $("#stockFiltroSituacao")
                    .val()
        };


        const resposta =
            await requisicaoStock(
                "listar",
                parametros
            );


        const dados =
            Array.isArray(
                resposta.dados
            )
                ? resposta.dados
                : [];


        renderizarTabelaStock(
            dados
        );


    } catch (erro) {

        mostrarErroStock(
            erro.message
        );


        if (tbody) {

            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="12"
                        class="text-center text-danger"
                    >
                        Não foi possível carregar o stock.
                    </td>
                </tr>
            `;
        }
    }
}


/*
|--------------------------------------------------------------------------
| RENDER TABELA
|--------------------------------------------------------------------------
*/

function renderizarTabelaStock(
    dados
) {

    destruirDataTableStock();


    const tbody =
        document.getElementById(
            "stockTabelaBody"
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
                    Nenhum registo de stock encontrado.
                </td>
            </tr>
        `;

        return;
    }


    tbody.innerHTML =
        dados
            .map(
                criarLinhaStock
            )
            .join("");


    inicializarDataTableStock();
}


/*
|--------------------------------------------------------------------------
| CRIAR LINHA
|--------------------------------------------------------------------------
*/

function criarLinhaStock(
    stock
) {

    const quantidade =
        formatarQuantidadeStock(
            stock.quantidade,
            stock.unidade_codigo,
            stock.unidade_casas_decimais
        );


    const minimo =
        formatarQuantidadeStock(
            stock.estoque_minimo,
            stock.unidade_codigo,
            stock.unidade_casas_decimais
        );


    return `

        <tr>

            <td>
                <strong>
                    ${escaparHtmlStock(
        stock.produto_nome
    )}
                </strong>
            </td>


            <td>
                ${escaparHtmlStock(
        stock.sku
        || "-"
    )}
            </td>


            <td>
                ${escaparHtmlStock(
        stock.categoria_nome
        || "-"
    )}
            </td>


            <td>
                ${escaparHtmlStock(
        stock.marca_nome
        || "-"
    )}
            </td>


            <td>
                ${escaparHtmlStock(
        stock.armazem_nome
        || "-"
    )}
            </td>


            <td>

                ${stock.unidade_codigo
            ? `
                            <strong>
                                ${escaparHtmlStock(
                stock.unidade_codigo
            )}
                            </strong>

                            <div class="small text-muted">
                                ${escaparHtmlStock(
                stock.unidade_nome
                || ""
            )}
                            </div>
                        `
            : "-"
        }

            </td>


            <td>
                <strong>
                    ${quantidade}
                </strong>
            </td>


            <td>
                ${minimo}
            </td>


            <td>
                ${formatarMoedaStock(
            stock.custo_medio
        )}
            </td>


            <td>
                <strong>
                    ${formatarMoedaStock(
            stock.valor_stock
        )}
                </strong>
            </td>


            <td>
                ${criarBadgeSituacaoStock(
            stock.situacao_stock
        )}
            </td>


            <td>
                ${formatarDataStock(
            stock.atualizado_em
        )}
            </td>

        </tr>
    `;
}


/*
|--------------------------------------------------------------------------
| RESUMO
|--------------------------------------------------------------------------
*/

async function carregarResumoStock() {

    try {

        const resposta =
            await requisicaoStock(
                "resumo"
            );


        const resumo =
            resposta.dados
            || {};


        atualizarTextoStock(
            "stockResumoProdutos",
            resumo.produtos_em_stock
            || 0
        );


        atualizarTextoStock(
            "stockResumoBaixo",
            resumo.stock_baixo
            || 0
        );


        atualizarTextoStock(
            "stockResumoSemStock",
            resumo.sem_stock
            || 0
        );


        atualizarTextoStock(
            "stockResumoValor",
            formatarMoedaStock(
                resumo.valor_stock
                || 0
            )
        );


    } catch (erro) {

        console.error(
            "Erro ao carregar resumo:",
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| ARMAZÉNS
|--------------------------------------------------------------------------
*/

async function carregarArmazensStock() {

    const select =
        document.getElementById(
            "stockFiltroArmazem"
        );


    if (!select) {
        return;
    }


    try {

        const resposta =
            await requisicaoStock(
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
                "Todos os armazéns",
                ""
            )
        );


        dados.forEach(
            armazem => {

                select.add(
                    new Option(
                        armazem.nome,
                        String(
                            armazem.id
                        )
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
| CATEGORIAS
|--------------------------------------------------------------------------
*/

async function carregarCategoriasStock() {

    const select =
        document.getElementById(
            "stockFiltroCategoria"
        );


    if (!select) {
        return;
    }


    try {

        const resposta =
            await requisicaoStock(
                "categorias"
            );


        const dados =
            Array.isArray(
                resposta.dados
            )
                ? resposta.dados
                : [];


        select.replaceChildren(
            new Option(
                "Todas as categorias",
                ""
            )
        );


        dados.forEach(
            categoria => {

                select.add(
                    new Option(
                        categoria.nome,
                        String(
                            categoria.id
                        )
                    )
                );
            }
        );


    } catch (erro) {

        console.error(
            "Erro ao carregar categorias:",
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| REQUISIÇÃO
|--------------------------------------------------------------------------
*/

async function requisicaoStock(
    acao,
    parametros = {}
) {

    const url =
        new URL(
            STOCK_URL,
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
                method: "GET",

                headers: {
                    "Accept":
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

    } catch (erro) {

        console.error(
            "Resposta inválida do Stock:",
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

function inicializarDataTableStock() {

    if (
        typeof $.fn.DataTable
        === "undefined"
    ) {
        return;
    }


    tabelaStock =
        $("#stockTable")
            .DataTable({

                pageLength: 10,

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


                order: [
                    [
                        0,
                        "asc"
                    ]
                ],


                responsive: true,


                language: {

                    url:
                        "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
                },


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
                            "Stock Atual"
                    },


                    {
                        extend:
                            "pdfHtml5",

                        text:
                            '<i class="fa fa-file-pdf"></i> PDF',

                        title:
                            "Stock Atual",

                        orientation:
                            "landscape",

                        pageSize:
                            "A4"
                    },


                    {
                        extend:
                            "print",

                        text:
                            '<i class="fa fa-print"></i> Imprimir',

                        title:
                            "Stock Atual"
                    }

                ]
            });
}


/*
|--------------------------------------------------------------------------
| DESTRUIR DATATABLE
|--------------------------------------------------------------------------
*/

function destruirDataTableStock() {

    if (
        $.fn.DataTable
        && $.fn.DataTable
            .isDataTable(
                "#stockTable"
            )
    ) {

        $("#stockTable")
            .DataTable()
            .destroy();


        tabelaStock =
            null;
    }
}


/*
|--------------------------------------------------------------------------
| BADGE
|--------------------------------------------------------------------------
*/

function criarBadgeSituacaoStock(
    situacao
) {

    switch (
    String(
        situacao
        || ""
    ).toUpperCase()
    ) {


        case "SEM_STOCK":

            return `
                <span class="badge badge-danger">
                    Sem Stock
                </span>
            `;


        case "STOCK_BAIXO":

            return `
                <span class="badge badge-warning">
                    Stock Baixo
                </span>
            `;


        default:

            return `
                <span class="badge badge-success">
                    Normal
                </span>
            `;
    }
}


/*
|--------------------------------------------------------------------------
| QUANTIDADE
|--------------------------------------------------------------------------
*/

function formatarQuantidadeStock(
    valor,
    unidade,
    casasDecimais = 0
) {

    let numero =
        parseFloat(
            valor
        );


    if (
        !Number.isFinite(
            numero
        )
    ) {
        numero = 0;
    }


    let casas =
        parseInt(
            casasDecimais,
            10
        );


    if (
        !Number.isFinite(
            casas
        )
        || casas < 0
    ) {
        casas = 0;
    }


    if (casas > 6) {
        casas = 6;
    }


    const quantidade =
        numero.toLocaleString(
            "pt-PT",
            {
                minimumFractionDigits:
                    0,

                maximumFractionDigits:
                    casas,

                useGrouping:
                    false
            }
        );


    return (
        quantidade
        +
        (
            unidade
                ? " "
                + escaparHtmlStock(
                    unidade
                )
                : ""
        )
    );
}


/*
|--------------------------------------------------------------------------
| MOEDA
|--------------------------------------------------------------------------
*/

function formatarMoedaStock(
    valor
) {

    const numero =
        Number(
            valor
            || 0
        );


    return numero.toLocaleString(
        "pt-MZ",
        {
            style:
                "currency",

            currency:
                "MZN"
        }
    );
}


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

function formatarDataStock(
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
        return escaparHtmlStock(
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

function mostrarErroStock(
    mensagem
) {

    const elemento =
        document.getElementById(
            "stockErro"
        );


    if (!elemento) {
        return;
    }


    elemento.textContent =
        mensagem
        || "Ocorreu um erro.";


    elemento.classList.remove(
        "d-none"
    );
}


function esconderErroStock() {

    const elemento =
        document.getElementById(
            "stockErro"
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


/*
|--------------------------------------------------------------------------
| UTILITÁRIOS
|--------------------------------------------------------------------------
*/

function atualizarTextoStock(
    id,
    texto
) {

    const elemento =
        document.getElementById(
            id
        );


    if (elemento) {

        elemento.textContent =
            texto;
    }
}


function escaparHtmlStock(
    valor
) {

    return String(
        valor
        ?? ""
    )
        .replaceAll(
            "&",
            "&amp;"
        )
        .replaceAll(
            "<",
            "&lt;"
        )
        .replaceAll(
            ">",
            "&gt;"
        )
        .replaceAll(
            '"',
            "&quot;"
        )
        .replaceAll(
            "'",
            "&#039;"
        );
}