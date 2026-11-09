"use strict";


const SAIDA_STOCK_URL =
    "/erpplus/app/ajax/saida_stock_ajax.php";


let produtosSaidaStock = [];

let contadorLinhasSaida = 0;


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
                "formSaidaStock"
            );

        if (!form) {
            return;
        }

        configurarDataSaida();

        registrarEventosSaidaStock();

        await carregarArmazensSaida();

        adicionarLinhaSaida();
    }
);


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

function configurarDataSaida() {

    const input =
        document.getElementById(
            "saidaData"
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

function registrarEventosSaidaStock() {

    $("#saidaArmazemId")
        .on(
            "change",
            async function () {

                await carregarProdutosSaida();

                limparLinhasProdutosSaida();

                adicionarLinhaSaida();
            }
        );


    $("#btnAdicionarProdutoSaida")
        .on(
            "click",
            function () {

                if (
                    !$("#saidaArmazemId")
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


                adicionarLinhaSaida();
            }
        );


    $("#btnLimparSaidaStock")
        .on(
            "click",
            limparSaidaStock
        );


    $("#formSaidaStock")
        .on(
            "submit",
            guardarSaidaStock
        );


    $(document)
        .on(
            "click",
            ".btnRemoverLinhaSaida",
            function () {

                const linha =
                    $(this).closest(
                        "tr"
                    );

                const select =
                    linha.find(
                        ".produtoSaidaSelect"
                    );

                if (
                    select.length
                    &&
                    select.hasClass(
                        "select2-hidden-accessible"
                    )
                ) {
                    select.select2(
                        "destroy"
                    );
                }

                linha.remove();

                calcularTotalSaida();
            }
        );


    $(document)
        .on(
            "change",
            ".produtoSaidaSelect",
            function () {

                preencherProdutoSaida(
                    this
                );
            }
        );


    $(document)
        .on(
            "input",
            ".quantidadeSaida",
            calcularTotalSaida
        );
}


/*
|--------------------------------------------------------------------------
| ARMAZÉNS
|--------------------------------------------------------------------------
*/

async function carregarArmazensSaida() {

    try {

        const resposta =
            await requisicaoSaidaStock(
                "armazens"
            );


        const select =
            document.getElementById(
                "saidaArmazemId"
            );


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
                        String(armazem.id)
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
| PRODUTOS DO ARMAZÉM
|--------------------------------------------------------------------------
*/

async function carregarProdutosSaida() {

    const armazemId =
        $("#saidaArmazemId")
            .val();


    produtosSaidaStock = [];


    if (!armazemId) {
        return;
    }


    try {

        const resposta =
            await requisicaoSaidaStock(
                "produtos",
                {
                    armazem_id:
                        armazemId
                }
            );


        produtosSaidaStock =
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

function adicionarLinhaSaida() {

    const tbody =
        document.getElementById(
            "saidaProdutosBody"
        );

    if (!tbody) {
        return;
    }

    contadorLinhasSaida++;

    const tr =
        document.createElement(
            "tr"
        );

    tr.innerHTML = `
        <td>
            <select
                class="form-control produtoSaidaSelect"
                required
            >
                ${criarOpcoesProdutosSaida()}
            </select>
        </td>

        <td class="unidadeSaida text-center">
            -
        </td>

        <td class="saldoSaida text-center">
            0
        </td>

        <td>
            <input
                type="number"
                class="form-control quantidadeSaida"
                min="0.001"
                step="0.001"
                value="1"
                required
            >
        </td>

        <td>
            <input
                type="text"
                class="form-control custoSaida"
                value="0,00 MT"
                readonly
            >
        </td>

        <td class="subtotalSaida text-right">
            0,00 MT
        </td>

        <td class="text-center">
            <button
                type="button"
                class="btn btn-sm btn-danger btnRemoverLinhaSaida"
            >
                <i class="fa fa-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(
        tr
    );

    const selectProduto =
        tr.querySelector(
            ".produtoSaidaSelect"
        );

    if (selectProduto) {

        inicializarSelectProdutoSaida(
            selectProduto
        );
    }

    calcularTotalSaida();
}


function criarOpcoesProdutosSaida() {

    let html = `
        <option value="">
            Selecione o produto
        </option>
    `;


    produtosSaidaStock
        .forEach(
            produto => {

                html += `
                    <option value="${produto.id}">
                        ${escaparHtmlSaida(
                    produto.sku
                )}
                        -
                        ${escaparHtmlSaida(
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
| PRODUTO
|--------------------------------------------------------------------------
*/

function preencherProdutoSaida(
    select
) {

    const tr =
        select.closest("tr");


    const produto =
        produtosSaidaStock
            .find(
                item =>
                    Number(item.id)
                    === Number(
                        select.value
                    )
            );


    if (!produto) {

        tr.querySelector(
            ".unidadeSaida"
        ).textContent = "-";


        tr.querySelector(
            ".saldoSaida"
        ).textContent = "0";


        tr.querySelector(
            ".custoSaida"
        ).value = "0,00 MT";


        calcularTotalSaida();

        return;
    }


    tr.dataset.custo =
        Number(
            produto.preco_custo
            || 0
        );


    tr.dataset.saldo =
        Number(
            produto.stock_disponivel
            || 0
        );


    tr.querySelector(
        ".unidadeSaida"
    ).textContent =
        produto.unidade_codigo
        || "-";


    tr.querySelector(
        ".saldoSaida"
    ).textContent =
        formatarQuantidadeSaida(
            produto.stock_disponivel,
            produto.casas_decimais
        );


    tr.querySelector(
        ".custoSaida"
    ).value =
        formatarMoedaSaida(
            produto.preco_custo
        );


    calcularTotalSaida();
}


/*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/

function calcularTotalSaida() {

    let total = 0;


    document
        .querySelectorAll(
            "#saidaProdutosBody tr"
        )
        .forEach(
            tr => {

                const quantidade =
                    Number(
                        tr.querySelector(
                            ".quantidadeSaida"
                        )?.value
                        || 0
                    );


                const custo =
                    Number(
                        tr.dataset.custo
                        || 0
                    );


                const subtotal =
                    quantidade
                    * custo;


                total += subtotal;


                const td =
                    tr.querySelector(
                        ".subtotalSaida"
                    );


                if (td) {

                    td.textContent =
                        formatarMoedaSaida(
                            subtotal
                        );
                }
            }
        );


    $("#saidaValorTotal")
        .text(
            formatarMoedaSaida(
                total
            )
        );
}


/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/

async function guardarSaidaStock(
    evento
) {

    evento.preventDefault();


    const itens =
        recolherItensSaida();


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


    for (
        const item of itens
    ) {

        if (
            item.quantidade
            > item.stock_disponivel
        ) {

            Swal.fire({
                icon: "warning",
                title: "Stock insuficiente",
                text:
                    `A quantidade solicitada é superior ao saldo disponível.`
            });

            return;
        }
    }


    const formData =
        new FormData(
            evento.currentTarget
        );


    formData.append(
        "acao",
        "guardar"
    );


    formData.append(
        "itens",
        JSON.stringify(
            itens.map(
                item => ({
                    produto_id:
                        item.produto_id,

                    quantidade:
                        item.quantidade
                })
            )
        )
    );


    const botao =
        document.getElementById(
            "btnGuardarSaidaStock"
        );


    const htmlOriginal =
        botao.innerHTML;


    botao.disabled = true;


    botao.innerHTML = `
        <i class="fa fa-spinner fa-spin"></i>
        A registar...
    `;


    try {

        const resposta =
            await fetch(
                SAIDA_STOCK_URL,
                {
                    method: "POST",
                    body: formData
                }
            );


        const json =
            await lerJsonSaida(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
                || "Não foi possível registar a saída."
            );
        }


        await Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: json.mensagem
        });


        limparSaidaStock();


    } catch (erro) {

        Swal.fire({
            icon: "error",
            title: "Erro!",
            text: erro.message
        });


    } finally {

        botao.disabled = false;

        botao.innerHTML =
            htmlOriginal;
    }
}

/*
|--------------------------------------------------------------------------
| SELECT2 - PRODUTOS
|--------------------------------------------------------------------------
*/

function inicializarSelectProdutoSaida(
    select
) {
    const $select =
        $(select);

    if (
        $select.hasClass(
            "select2-hidden-accessible"
        )
    ) {
        return;
    }

    $select.select2({
        width: "100%",

        placeholder:
            "Pesquisar produto...",

        allowClear: true,

        language: {
            noResults:
                function () {
                    return "Nenhum produto encontrado";
                },

            searching:
                function () {
                    return "A pesquisar...";
                }
        }
    });
}


/*
|--------------------------------------------------------------------------
| ITENS
|--------------------------------------------------------------------------
*/

function recolherItensSaida() {

    const itens = [];


    document
        .querySelectorAll(
            "#saidaProdutosBody tr"
        )
        .forEach(
            tr => {

                const produtoId =
                    Number(
                        tr.querySelector(
                            ".produtoSaidaSelect"
                        )?.value
                        || 0
                    );


                const quantidade =
                    Number(
                        tr.querySelector(
                            ".quantidadeSaida"
                        )?.value
                        || 0
                    );


                if (produtoId > 0) {

                    itens.push({
                        produto_id:
                            produtoId,

                        quantidade:
                            quantidade,

                        stock_disponivel:
                            Number(
                                tr.dataset.saldo
                                || 0
                            )
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

function limparLinhasProdutosSaida() {

    $("#saidaProdutosBody")
        .find(
            ".produtoSaidaSelect"
        )
        .each(
            function () {

                const $select =
                    $(this);

                if (
                    $select.hasClass(
                        "select2-hidden-accessible"
                    )
                ) {
                    $select.select2(
                        "destroy"
                    );
                }
            }
        );

    document
        .getElementById(
            "saidaProdutosBody"
        )
        .innerHTML = "";

    contadorLinhasSaida = 0;

    calcularTotalSaida();
}

function limparSaidaStock() {

    document
        .getElementById(
            "formSaidaStock"
        )
        .reset();


    produtosSaidaStock = [];


    limparLinhasProdutosSaida();


    configurarDataSaida();


    adicionarLinhaSaida();
}


/*
|--------------------------------------------------------------------------
| HTTP
|--------------------------------------------------------------------------
*/

async function requisicaoSaidaStock(
    acao,
    parametros = {}
) {

    const url =
        new URL(
            SAIDA_STOCK_URL,
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
            url.toString()
        );


    const json =
        await lerJsonSaida(
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


async function lerJsonSaida(
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

function formatarQuantidadeSaida(
    valor,
    casasDecimais = 0
) {

    let numero =
        Number(
            valor
            || 0
        );


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


function formatarMoedaSaida(
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


function escaparHtmlSaida(
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