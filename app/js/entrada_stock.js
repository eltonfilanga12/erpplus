"use strict";


const ENTRADA_STOCK_URL =
    "/erpplus/app/ajax/entrada_stock_ajax.php";


let produtosEntradaStock = [];

let contadorLinhasEntrada = 0;


/*
|--------------------------------------------------------------------------
| INICIALIZAR
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    async function () {

        const form =
            document.getElementById(
                "formEntradaStock"
            );

        if (!form) {
            return;
        }

        garantirCampoOrigemContabilistica();

        configurarDataEntrada();

        registrarEventosEntradaStock();

        await Promise.all([
            carregarArmazensEntrada(),
            carregarProdutosEntrada()
        ]);

        adicionarLinhaEntrada();
    }
);


/*
|--------------------------------------------------------------------------
| ORIGEM CONTABILÍSTICA
|--------------------------------------------------------------------------
*/

function garantirCampoOrigemContabilistica() {

    if (
        document.getElementById(
            "entradaOrigemContabilistica"
        )
    ) {
        return;
    }

    const observacoes =
        document.getElementById(
            "entradaObservacoes"
        );

    if (!observacoes) {
        return;
    }

    const grupoObservacoes =
        observacoes.closest(
            ".form-group"
        );

    if (!grupoObservacoes) {
        return;
    }

    const grupo =
        document.createElement(
            "div"
        );

    grupo.className =
        "form-group col-md-12";

    grupo.innerHTML = `
        <label for="entradaOrigemContabilistica">
            Origem contabilística
            <span class="text-danger">*</span>
        </label>

        <select
            class="form-control"
            id="entradaOrigemContabilistica"
            name="origem_contabilistica"
            required>

            <option value="">
                Selecione a origem
            </option>

            <option value="STOCK_INICIAL">
                Stock inicial — Resultados transitados
            </option>

            <option value="CAPITAL_SOCIOS">
                Entrada dos sócios — Capital
            </option>

            <option value="AJUSTE_POSITIVO">
                Ajuste positivo de inventário
            </option>

        </select>

        <small class="form-text text-muted">
            Compras devem ser registadas no módulo de Compras.
        </small>
    `;

    grupoObservacoes.parentNode
        .insertBefore(
            grupo,
            grupoObservacoes
        );
}


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

function configurarDataEntrada() {

    const input =
        document.getElementById(
            "entradaData"
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
            .slice(
                0,
                16
            );
}


/*
|--------------------------------------------------------------------------
| EVENTOS
|--------------------------------------------------------------------------
*/

function registrarEventosEntradaStock() {

    $("#btnAdicionarProdutoEntrada")
        .on(
            "click",
            adicionarLinhaEntrada
        );


    $("#btnLimparEntradaStock")
        .on(
            "click",
            function () {

                limparEntradaStock();
            }
        );


    $("#formEntradaStock")
        .on(
            "submit",
            guardarEntradaStock
        );


    $(document)
        .on(
            "click",
            ".btnRemoverLinhaEntrada",
            function () {

                const linha =
                    $(this).closest(
                        "tr"
                    );

                const select =
                    linha.find(
                        ".produtoEntradaSelect"
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

                calcularTotalEntrada();
            }
        );


    $(document)
        .on(
            "change",
            ".produtoEntradaSelect",
            function () {

                preencherDadosProdutoEntrada(
                    this
                );
            }
        );


    $(document)
        .on(
            "input",
            ".quantidadeEntrada, .custoEntrada",
            calcularTotalEntrada
        );
}


/*
|--------------------------------------------------------------------------
| ARMAZÉNS
|--------------------------------------------------------------------------
*/

async function carregarArmazensEntrada() {

    const select =
        document.getElementById(
            "entradaArmazemId"
        );

    if (!select) {
        return;
    }

    try {

        const resposta =
            await requisicaoEntradaStock(
                "armazens"
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

        console.error(
            "Erro ao carregar armazéns da entrada:",
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| PRODUTOS
|--------------------------------------------------------------------------
*/

async function carregarProdutosEntrada() {

    const resposta =
        await requisicaoEntradaStock(
            "produtos"
        );


    produtosEntradaStock =
        Array.isArray(
            resposta.dados
        )
            ? resposta.dados
            : [];
}




/*
|--------------------------------------------------------------------------
| LINHA
|--------------------------------------------------------------------------
*/

function adicionarLinhaEntrada() {

    contadorLinhasEntrada++;


    const tbody =
        document.getElementById(
            "entradaProdutosBody"
        );


    const tr =
        document.createElement(
            "tr"
        );


    tr.dataset.linha =
        contadorLinhasEntrada;


    tr.innerHTML = `

        <td>

            <select
                class="form-control produtoEntradaSelect"
                required
            >
                ${criarOpcoesProdutosEntrada()}
            </select>

        </td>


        <td class="unidadeEntrada text-center">
            -
        </td>


        <td>

            <input
                type="number"
                class="form-control quantidadeEntrada"
                value="1"
                min="0.001"
                step="0.001"
                required
            >

        </td>


        <td>

            <input
                type="number"
                class="form-control custoEntrada"
                value="0"
                min="0"
                step="0.01"
                required
            >

        </td>


        <td class="subtotalEntrada text-right">
            0,00 MT
        </td>


        <td class="text-center">

            <button
                type="button"
                class="btn btn-sm btn-danger btnRemoverLinhaEntrada"
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
            ".produtoEntradaSelect"
        );

    if (selectProduto) {

        inicializarSelectProdutoEntrada(
            selectProduto
        );
    }

    calcularTotalEntrada();
}


function criarOpcoesProdutosEntrada() {

    let html = `
        <option value="">
            Selecione o produto
        </option>
    `;


    produtosEntradaStock
        .forEach(
            produto => {

                html += `
                    <option
                        value="${produto.id}"
                    >
                        ${escaparHtmlEntrada(
                    produto.sku
                )}
                        -
                        ${escaparHtmlEntrada(
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

function preencherDadosProdutoEntrada(
    select
) {

    const tr =
        select.closest(
            "tr"
        );


    const produto =
        produtosEntradaStock
            .find(
                p =>
                    Number(p.id)
                    === Number(
                        select.value
                    )
            );


    const unidade =
        tr.querySelector(
            ".unidadeEntrada"
        );


    const custo =
        tr.querySelector(
            ".custoEntrada"
        );


    if (!produto) {

        unidade.textContent =
            "-";


        custo.value =
            "0";


        calcularTotalEntrada();

        return;
    }


    unidade.textContent =
        produto.unidade_codigo
        || "-";


    custo.value =
        Number(
            produto.preco_custo
            || 0
        ).toFixed(2);


    calcularTotalEntrada();
}


/*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/

function calcularTotalEntrada() {

    let total = 0;


    document
        .querySelectorAll(
            "#entradaProdutosBody tr"
        )
        .forEach(
            tr => {

                const quantidade =
                    Number(
                        tr.querySelector(
                            ".quantidadeEntrada"
                        )?.value
                        || 0
                    );


                const custo =
                    Number(
                        tr.querySelector(
                            ".custoEntrada"
                        )?.value
                        || 0
                    );


                const subtotal =
                    quantidade
                    * custo;


                total +=
                    subtotal;


                const coluna =
                    tr.querySelector(
                        ".subtotalEntrada"
                    );


                if (coluna) {

                    coluna.textContent =
                        formatarMoedaEntrada(
                            subtotal
                        );
                }
            }
        );


    $("#entradaValorTotal")
        .text(
            formatarMoedaEntrada(
                total
            )
        );
}


/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/

async function guardarEntradaStock(
    evento
) {

    evento.preventDefault();


    const itens =
        recolherItensEntrada();


    if (itens.length === 0) {

        Swal.fire({
            icon: "warning",
            title: "Atenção",
            text:
                "Adicione pelo menos um produto."
        });

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
            itens
        )
    );


    const botao =
        document.getElementById(
            "btnGuardarEntradaStock"
        );


    const htmlOriginal =
        botao.innerHTML;


    botao.disabled =
        true;


    botao.innerHTML = `
        <i class="fa fa-spinner fa-spin"></i>
        A registar...
    `;


    try {

        const resposta =
            await fetch(
                ENTRADA_STOCK_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await lerJsonEntrada(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
                || "Não foi possível registar a entrada."
            );
        }


        await Swal.fire({
            icon:
                "success",

            title:
                "Sucesso!",

            text:
                json.mensagem,

            confirmButtonText:
                "OK"
        });


        limparEntradaStock();


    } catch (erro) {

        Swal.fire({
            icon:
                "error",

            title:
                "Erro!",

            text:
                erro.message
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
| SELECT2 - PRODUTOS
|--------------------------------------------------------------------------
*/

function inicializarSelectProdutoEntrada(
    select
) {
    const $select =
        $(select);

    /*
     * Evita inicializar duas vezes.
     */
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
| RECOLHER ITENS
|--------------------------------------------------------------------------
*/

function recolherItensEntrada() {

    const itens = [];


    document
        .querySelectorAll(
            "#entradaProdutosBody tr"
        )
        .forEach(
            tr => {

                const produtoId =
                    Number(
                        tr.querySelector(
                            ".produtoEntradaSelect"
                        )?.value
                        || 0
                    );


                const quantidade =
                    Number(
                        tr.querySelector(
                            ".quantidadeEntrada"
                        )?.value
                        || 0
                    );


                const custo =
                    Number(
                        tr.querySelector(
                            ".custoEntrada"
                        )?.value
                        || 0
                    );


                if (produtoId > 0) {

                    itens.push({
                        produto_id:
                            produtoId,

                        quantidade:
                            quantidade,

                        custo_unitario:
                            custo
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

function limparEntradaStock() {

    $("#entradaProdutosBody")
        .find(
            ".produtoEntradaSelect"
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
            "formEntradaStock"
        )
        .reset();


    document
        .getElementById(
            "entradaProdutosBody"
        )
        .innerHTML = "";


    contadorLinhasEntrada = 0;


    configurarDataEntrada();


    adicionarLinhaEntrada();


    calcularTotalEntrada();
}


/*
|--------------------------------------------------------------------------
| HTTP
|--------------------------------------------------------------------------
*/

async function requisicaoEntradaStock(
    acao
) {

    const url =
        ENTRADA_STOCK_URL
        + "?acao="
        + encodeURIComponent(
            acao
        );


    const resposta =
        await fetch(
            url
        );


    const json =
        await lerJsonEntrada(
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


async function lerJsonEntrada(
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

function formatarMoedaEntrada(
    valor
) {

    return Number(
        valor
        || 0
    ).toLocaleString(
        "pt-MZ",
        {
            style:
                "currency",

            currency:
                "MZN"
        }
    );
}


function escaparHtmlEntrada(
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
