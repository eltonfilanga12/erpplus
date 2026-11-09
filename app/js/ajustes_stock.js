"use strict";


const AJUSTE_STOCK_URL =
    "/erpplus/app/ajax/ajuste_stock_ajax.php";


let produtosAjusteStock = [];

let contadorLinhasAjuste = 0;


/*
|--------------------------------------------------------------------------
| INICIALIZAÇÃO
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    async function () {

        const form =
            document.getElementById(
                "formAjusteStock"
            );


        if (!form) {
            return;
        }


        configurarDataAjuste();

        registrarEventosAjuste();


        await carregarArmazensAjuste();


        adicionarLinhaAjuste();
    }
);


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

function configurarDataAjuste() {

    const input =
        document.getElementById(
            "ajusteData"
        );


    if (!input) {
        return;
    }


    const agora =
        new Date();


    const local =
        new Date(
            agora.getTime()
            - agora.getTimezoneOffset()
            * 60000
        );


    input.value =
        local
            .toISOString()
            .slice(0, 16);
}


/*
|--------------------------------------------------------------------------
| EVENTOS
|--------------------------------------------------------------------------
*/

function registrarEventosAjuste() {

    $("#ajusteArmazemId")
        .on(
            "change",
            async function () {

                await carregarProdutosAjuste();

                limparLinhasAjuste();

                adicionarLinhaAjuste();
            }
        );


    $("#btnAdicionarProdutoAjuste")
        .on(
            "click",
            function () {

                if (
                    !$("#ajusteArmazemId")
                        .val()
                ) {

                    Swal.fire({
                        icon: "warning",
                        title: "Atenção",
                        text:
                            "Selecione primeiro o armazém."
                    });

                    return;
                }


                adicionarLinhaAjuste();
            }
        );


    $("#btnLimparAjuste")
        .on(
            "click",
            limparAjuste
        );


    $("#formAjusteStock")
        .on(
            "submit",
            guardarAjuste
        );


    $(document)
        .on(
            "click",
            ".btnRemoverLinhaAjuste",
            function () {

                $(this)
                    .closest("tr")
                    .remove();


                calcularAjustes();
            }
        );


    $(document)
        .on(
            "change",
            ".produtoAjusteSelect",
            function () {

                preencherProdutoAjuste(
                    this
                );
            }
        );


    $(document)
        .on(
            "input",
            ".stockFisicoAjuste",
            calcularAjustes
        );
}


/*
|--------------------------------------------------------------------------
| ARMAZÉNS
|--------------------------------------------------------------------------
*/

async function carregarArmazensAjuste() {

    try {

        const resposta =
            await requisicaoAjuste(
                "armazens"
            );


        const select =
            document.getElementById(
                "ajusteArmazemId"
            );


        if (!select) {
            return;
        }


        select.replaceChildren(
            new Option(
                "Selecione o armazém",
                ""
            )
        );


        (
            resposta.dados
            || []
        ).forEach(
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

        Swal.fire({
            icon: "error",
            title: "Erro!",
            text: erro.message
        });
    }
}


/*
|--------------------------------------------------------------------------
| PRODUTOS
|--------------------------------------------------------------------------
*/

async function carregarProdutosAjuste() {

    produtosAjusteStock = [];


    const armazemId =
        $("#ajusteArmazemId")
            .val();


    if (!armazemId) {
        return;
    }


    try {

        const resposta =
            await requisicaoAjuste(
                "produtos",
                {
                    armazem_id:
                        armazemId
                }
            );


        produtosAjusteStock =
            Array.isArray(
                resposta.dados
            )
                ? resposta.dados
                : [];


    } catch (erro) {

        Swal.fire({
            icon: "error",
            title: "Erro!",
            text: erro.message
        });
    }
}


/*
|--------------------------------------------------------------------------
| LINHA
|--------------------------------------------------------------------------
*/

function adicionarLinhaAjuste() {

    const tbody =
        document.getElementById(
            "ajusteProdutosBody"
        );


    if (!tbody) {
        return;
    }


    contadorLinhasAjuste++;


    const tr =
        document.createElement(
            "tr"
        );


    tr.innerHTML = `

        <td>

            <select
                class="form-control produtoAjusteSelect"
                required
            >
                ${criarOpcoesProdutosAjuste()}
            </select>

        </td>


        <td class="unidadeAjuste text-center">
            -
        </td>


        <td class="stockSistemaAjuste text-center">
            0
        </td>


        <td>

            <input
                type="number"
                class="form-control stockFisicoAjuste"
                value="0"
                min="0"
                step="0.001"
                required
            >

        </td>


        <td class="diferencaAjuste text-center">
            0
        </td>


        <td>

            <input
                type="text"
                class="form-control custoAjuste"
                value="0,00 MT"
                readonly
            >

        </td>


        <td class="impactoAjuste text-right">
            0,00 MT
        </td>


        <td class="text-center">

            <button
                type="button"
                class="btn btn-sm btn-danger btnRemoverLinhaAjuste"
            >
                <i class="fa fa-trash"></i>
            </button>

        </td>
    `;


    tbody.appendChild(
        tr
    );


    calcularAjustes();
}


function criarOpcoesProdutosAjuste() {

    let html = `
        <option value="">
            Selecione o produto
        </option>
    `;


    produtosAjusteStock
        .forEach(
            produto => {

                html += `
                    <option
                        value="${produto.id}"
                    >
                        ${escaparHtmlAjuste(
                    produto.sku
                )}
                        -
                        ${escaparHtmlAjuste(
                    produto.nome
                )}
                    </option>
                `;
            }
        );


    return html;
}


/*
|--------------------------------------------------------------------------
| PRODUTO SELECIONADO
|--------------------------------------------------------------------------
*/

function preencherProdutoAjuste(
    select
) {

    const tr =
        select.closest("tr");


    const produto =
        produtosAjusteStock
            .find(
                p =>
                    Number(p.id)
                    === Number(
                        select.value
                    )
            );


    if (!produto) {

        tr.dataset.stockSistema =
            "0";

        tr.dataset.custo =
            "0";


        tr.querySelector(
            ".unidadeAjuste"
        ).textContent =
            "-";


        tr.querySelector(
            ".stockSistemaAjuste"
        ).textContent =
            "0";


        tr.querySelector(
            ".stockFisicoAjuste"
        ).value =
            "0";


        tr.querySelector(
            ".custoAjuste"
        ).value =
            "0,00 MT";


        calcularAjustes();

        return;
    }


    const stockSistema =
        Number(
            produto.stock_sistema
            || 0
        );


    const custo =
        Number(
            produto.preco_custo
            || 0
        );


    tr.dataset.stockSistema =
        String(
            stockSistema
        );


    tr.dataset.custo =
        String(
            custo
        );


    tr.querySelector(
        ".unidadeAjuste"
    ).textContent =
        produto.unidade_codigo
        || "-";


    tr.querySelector(
        ".stockSistemaAjuste"
    ).textContent =
        formatarQuantidadeAjuste(
            stockSistema,
            produto.casas_decimais
        );


    /*
     * Por padrão colocamos o stock atual.
     * Assim o utilizador apenas altera se encontrou diferença.
     */

    tr.querySelector(
        ".stockFisicoAjuste"
    ).value =
        stockSistema;


    tr.querySelector(
        ".custoAjuste"
    ).value =
        formatarMoedaAjuste(
            custo
        );


    calcularAjustes();
}


/*
|--------------------------------------------------------------------------
| CALCULAR
|--------------------------------------------------------------------------
*/

function calcularAjustes() {

    let impactoTotal = 0;


    document
        .querySelectorAll(
            "#ajusteProdutosBody tr"
        )
        .forEach(
            tr => {

                const sistema =
                    Number(
                        tr.dataset.stockSistema
                        || 0
                    );


                const fisico =
                    Number(
                        tr.querySelector(
                            ".stockFisicoAjuste"
                        )?.value
                        || 0
                    );


                const custo =
                    Number(
                        tr.dataset.custo
                        || 0
                    );


                const diferenca =
                    fisico - sistema;


                const impacto =
                    diferenca * custo;


                impactoTotal +=
                    impacto;


                const tdDiferenca =
                    tr.querySelector(
                        ".diferencaAjuste"
                    );


                if (tdDiferenca) {

                    tdDiferenca.textContent =
                        formatarDiferencaAjuste(
                            diferenca
                        );


                    tdDiferenca.classList.remove(
                        "text-success",
                        "text-danger",
                        "text-muted"
                    );


                    if (diferenca > 0) {

                        tdDiferenca.classList.add(
                            "text-success"
                        );

                    } else if (
                        diferenca < 0
                    ) {

                        tdDiferenca.classList.add(
                            "text-danger"
                        );

                    } else {

                        tdDiferenca.classList.add(
                            "text-muted"
                        );
                    }
                }


                const tdImpacto =
                    tr.querySelector(
                        ".impactoAjuste"
                    );


                if (tdImpacto) {

                    tdImpacto.textContent =
                        formatarMoedaAjuste(
                            impacto
                        );
                }
            }
        );


    $("#ajusteImpactoTotal")
        .text(
            formatarMoedaAjuste(
                impactoTotal
            )
        );
}


/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/

async function guardarAjuste(
    evento
) {

    evento.preventDefault();


    const itens =
        recolherItensAjuste();


    if (
        itens.length === 0
    ) {

        Swal.fire({
            icon: "warning",
            title: "Atenção",
            text:
                "Adicione pelo menos um produto."
        });

        return;
    }


    const temDiferenca =
        itens.some(
            item =>
                Math.abs(
                    item.stock_fisico
                    - item.stock_sistema
                ) > 0.000001
        );


    if (!temDiferenca) {

        Swal.fire({
            icon: "info",
            title: "Sem diferenças",
            text:
                "O stock físico é igual ao stock do sistema."
        });

        return;
    }


    const confirmacao =
        await Swal.fire({
            icon: "warning",
            title: "Confirmar ajuste?",
            text:
                "O saldo do stock será alterado para a quantidade física informada.",
            showCancelButton: true,
            confirmButtonText:
                "Sim, ajustar",
            cancelButtonText:
                "Cancelar"
        });


    if (
        !confirmacao.isConfirmed
    ) {
        return;
    }


    const dados =
        new FormData(
            evento.currentTarget
        );


    dados.append(
        "acao",
        "guardar"
    );


    dados.append(
        "itens",
        JSON.stringify(
            itens.map(
                item => ({
                    produto_id:
                        item.produto_id,

                    stock_fisico:
                        item.stock_fisico
                })
            )
        )
    );


    const botao =
        document.getElementById(
            "btnGuardarAjuste"
        );


    const htmlOriginal =
        botao.innerHTML;


    botao.disabled =
        true;


    botao.innerHTML = `
        <i class="fa fa-spinner fa-spin"></i>
        A ajustar...
    `;


    try {

        const resposta =
            await fetch(
                AJUSTE_STOCK_URL,
                {
                    method: "POST",
                    body: dados
                }
            );


        const json =
            await lerJsonAjuste(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
                || "Não foi possível registar o ajuste."
            );
        }


        await Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: json.mensagem
        });


        limparAjuste();


    } catch (erro) {

        Swal.fire({
            icon: "error",
            title: "Erro!",
            text: erro.message
        });


    } finally {

        botao.disabled =
            false;


        botao.innerHTML =
            htmlOriginal;
    }
}


/*
|--------------------------------------------------------------------------
| RECOLHER
|--------------------------------------------------------------------------
*/

function recolherItensAjuste() {

    const itens = [];


    document
        .querySelectorAll(
            "#ajusteProdutosBody tr"
        )
        .forEach(
            tr => {

                const produtoId =
                    Number(
                        tr.querySelector(
                            ".produtoAjusteSelect"
                        )?.value
                        || 0
                    );


                const stockFisico =
                    Number(
                        tr.querySelector(
                            ".stockFisicoAjuste"
                        )?.value
                        || 0
                    );


                if (produtoId > 0) {

                    itens.push({
                        produto_id:
                            produtoId,

                        stock_sistema:
                            Number(
                                tr.dataset.stockSistema
                                || 0
                            ),

                        stock_fisico:
                            stockFisico
                    });
                }
            }
        );


    return itens;
}


/*
|--------------------------------------------------------------------------
| LIMPAR
|--------------------------------------------------------------------------
*/

function limparLinhasAjuste() {

    const tbody =
        document.getElementById(
            "ajusteProdutosBody"
        );


    if (tbody) {

        tbody.innerHTML =
            "";
    }


    contadorLinhasAjuste =
        0;


    calcularAjustes();
}


function limparAjuste() {

    const form =
        document.getElementById(
            "formAjusteStock"
        );


    if (!form) {
        return;
    }


    form.reset();


    produtosAjusteStock =
        [];


    limparLinhasAjuste();


    configurarDataAjuste();


    adicionarLinhaAjuste();
}


/*
|--------------------------------------------------------------------------
| HTTP
|--------------------------------------------------------------------------
*/

async function requisicaoAjuste(
    acao,
    parametros = {}
) {

    const url =
        new URL(
            AJUSTE_STOCK_URL,
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


    const json =
        await lerJsonAjuste(
            resposta
        );


    if (
        !resposta.ok
        || !json.sucesso
    ) {

        throw new Error(
            json.mensagem
            || "Erro na operação."
        );
    }


    return json;
}


async function lerJsonAjuste(
    resposta
) {

    const texto =
        await resposta.text();


    try {

        return JSON.parse(
            texto
        );


    } catch {

        console.error(
            "Resposta do servidor:",
            texto
        );


        throw new Error(
            "O servidor devolveu uma resposta inválida."
        );
    }
}


/*
|--------------------------------------------------------------------------
| FORMATAÇÃO
|--------------------------------------------------------------------------
*/

function formatarQuantidadeAjuste(
    valor,
    casasDecimais = 0
) {

    let casas =
        Number(
            casasDecimais
            || 0
        );


    if (
        !Number.isFinite(casas)
        || casas < 0
    ) {

        casas = 0;
    }


    return Number(
        valor
        || 0
    ).toLocaleString(
        "pt-PT",
        {
            minimumFractionDigits: 0,
            maximumFractionDigits:
                Math.min(casas, 6),
            useGrouping: false
        }
    );
}


function formatarDiferencaAjuste(
    valor
) {

    const numero =
        Number(
            valor
            || 0
        );


    if (numero > 0) {

        return "+"
            + formatarQuantidadeAjuste(
                numero,
                3
            );
    }


    return formatarQuantidadeAjuste(
        numero,
        3
    );
}


function formatarMoedaAjuste(
    valor
) {

    return Number(
        valor
        || 0
    ).toLocaleString(
        "pt-MZ",
        {
            style: "currency",
            currency: "MZN"
        }
    );
}


function escaparHtmlAjuste(
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