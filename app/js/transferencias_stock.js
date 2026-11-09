"use strict";


const TRANSFERENCIA_STOCK_URL =
    "/erpplus/app/ajax/transferencia_stock_ajax.php";


let produtosTransferenciaStock = [];

let contadorLinhasTransferencia = 0;


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
                "formTransferenciaStock"
            );


        if (!form) {
            return;
        }


        configurarDataTransferencia();

        registrarEventosTransferencia();


        await carregarArmazensTransferencia();


        adicionarLinhaTransferencia();
    }
);


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

function configurarDataTransferencia() {

    const input =
        document.getElementById(
            "transferenciaData"
        );


    if (!input) {
        return;
    }


    const agora =
        new Date();


    const local =
        new Date(
            agora.getTime()
            -
            agora.getTimezoneOffset()
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

function registrarEventosTransferencia() {

    $("#transferenciaArmazemOrigem")
        .on(
            "change",
            async function () {

                atualizarDestinosTransferencia();


                await carregarProdutosTransferencia();


                limparLinhasTransferencia();


                adicionarLinhaTransferencia();
            }
        );


    $("#transferenciaArmazemDestino")
        .on(
            "change",
            function () {

                validarArmazensTransferencia();
            }
        );


    $("#btnAdicionarProdutoTransferencia")
        .on(
            "click",
            function () {

                if (
                    !$("#transferenciaArmazemOrigem")
                        .val()
                ) {

                    Swal.fire({
                        icon: "warning",
                        title: "Atenção",
                        text:
                            "Selecione primeiro o armazém de origem."
                    });

                    return;
                }


                adicionarLinhaTransferencia();
            }
        );


    $("#btnLimparTransferencia")
        .on(
            "click",
            limparTransferencia
        );


    $("#formTransferenciaStock")
        .on(
            "submit",
            guardarTransferencia
        );


    $(document)
        .on(
            "click",
            ".btnRemoverLinhaTransferencia",
            function () {

                $(this)
                    .closest("tr")
                    .remove();


                calcularTotalTransferencia();
            }
        );


    $(document)
        .on(
            "change",
            ".produtoTransferenciaSelect",
            function () {

                preencherProdutoTransferencia(
                    this
                );
            }
        );


    $(document)
        .on(
            "input",
            ".quantidadeTransferencia",
            calcularTotalTransferencia
        );
}


/*
|--------------------------------------------------------------------------
| ARMAZÉNS
|--------------------------------------------------------------------------
*/

async function carregarArmazensTransferencia() {

    try {

        const resposta =
            await requisicaoTransferencia(
                "armazens"
            );


        const origem =
            document.getElementById(
                "transferenciaArmazemOrigem"
            );


        const destino =
            document.getElementById(
                "transferenciaArmazemDestino"
            );


        if (
            !origem
            || !destino
        ) {
            return;
        }


        origem.replaceChildren(
            new Option(
                "Selecione",
                ""
            )
        );


        destino.replaceChildren(
            new Option(
                "Selecione",
                ""
            )
        );


        (
            resposta.dados
            || []
        ).forEach(
            armazem => {

                origem.add(
                    new Option(
                        armazem.nome,
                        String(armazem.id)
                    )
                );


                destino.add(
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
| NÃO PERMITIR MESMO ARMAZÉM
|--------------------------------------------------------------------------
*/

function atualizarDestinosTransferencia() {

    const origem =
        $("#transferenciaArmazemOrigem")
            .val();


    const destino =
        document.getElementById(
            "transferenciaArmazemDestino"
        );


    if (!destino) {
        return;
    }


    Array.from(
        destino.options
    ).forEach(
        option => {

            option.disabled =
                origem !== ""
                && option.value
                === origem;
        }
    );


    if (
        destino.value
        === origem
    ) {

        destino.value = "";
    }
}


function validarArmazensTransferencia() {

    const origem =
        $("#transferenciaArmazemOrigem")
            .val();


    const destino =
        $("#transferenciaArmazemDestino")
            .val();


    if (
        origem
        && destino
        && origem === destino
    ) {

        $("#transferenciaArmazemDestino")
            .val("");


        Swal.fire({
            icon: "warning",
            title: "Atenção",
            text:
                "O armazém de destino deve ser diferente do armazém de origem."
        });


        return false;
    }


    return true;
}


/*
|--------------------------------------------------------------------------
| PRODUTOS
|--------------------------------------------------------------------------
*/

async function carregarProdutosTransferencia() {

    produtosTransferenciaStock = [];


    const origemId =
        $("#transferenciaArmazemOrigem")
            .val();


    if (!origemId) {
        return;
    }


    try {

        const resposta =
            await requisicaoTransferencia(
                "produtos",
                {
                    armazem_id:
                        origemId
                }
            );


        produtosTransferenciaStock =
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
| LINHAS
|--------------------------------------------------------------------------
*/

function adicionarLinhaTransferencia() {

    const tbody =
        document.getElementById(
            "transferenciaProdutosBody"
        );


    if (!tbody) {
        return;
    }


    contadorLinhasTransferencia++;


    const tr =
        document.createElement(
            "tr"
        );


    tr.innerHTML = `

        <td>

            <select
                class="form-control produtoTransferenciaSelect"
                required
            >
                ${criarOpcoesProdutosTransferencia()}
            </select>

        </td>


        <td class="unidadeTransferencia text-center">
            -
        </td>


        <td class="saldoTransferencia text-center">
            0
        </td>


        <td>

            <input
                type="number"
                class="form-control quantidadeTransferencia"
                min="0.001"
                step="0.001"
                value="1"
                required
            >

        </td>


        <td>

            <input
                type="text"
                class="form-control custoTransferencia"
                readonly
                value="0,00 MT"
            >

        </td>


        <td class="subtotalTransferencia text-right">
            0,00 MT
        </td>


        <td class="text-center">

            <button
                type="button"
                class="btn btn-sm btn-danger btnRemoverLinhaTransferencia"
            >
                <i class="fa fa-trash"></i>
            </button>

        </td>
    `;


    tbody.appendChild(
        tr
    );


    calcularTotalTransferencia();
}


function criarOpcoesProdutosTransferencia() {

    let html = `
        <option value="">
            Selecione o produto
        </option>
    `;


    produtosTransferenciaStock
        .forEach(
            produto => {

                html += `
                    <option
                        value="${produto.id}"
                    >
                        ${escaparHtmlTransferencia(
                    produto.sku
                )}
                        -
                        ${escaparHtmlTransferencia(
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

function preencherProdutoTransferencia(
    select
) {

    const tr =
        select.closest("tr");


    const produto =
        produtosTransferenciaStock
            .find(
                p =>
                    Number(p.id)
                    === Number(
                        select.value
                    )
            );


    if (!produto) {

        tr.dataset.saldo = "0";
        tr.dataset.custo = "0";


        tr.querySelector(
            ".unidadeTransferencia"
        ).textContent = "-";


        tr.querySelector(
            ".saldoTransferencia"
        ).textContent = "0";


        tr.querySelector(
            ".custoTransferencia"
        ).value = "0,00 MT";


        calcularTotalTransferencia();

        return;
    }


    const saldo =
        Number(
            produto.stock_disponivel
            || 0
        );


    const custo =
        Number(
            produto.preco_custo
            || 0
        );


    tr.dataset.saldo =
        String(saldo);


    tr.dataset.custo =
        String(custo);


    tr.querySelector(
        ".unidadeTransferencia"
    ).textContent =
        produto.unidade_codigo
        || "-";


    tr.querySelector(
        ".saldoTransferencia"
    ).textContent =
        formatarQuantidadeTransferencia(
            saldo,
            produto.casas_decimais
        );


    tr.querySelector(
        ".custoTransferencia"
    ).value =
        formatarMoedaTransferencia(
            custo
        );


    calcularTotalTransferencia();
}


/*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/

function calcularTotalTransferencia() {

    let total = 0;


    document
        .querySelectorAll(
            "#transferenciaProdutosBody tr"
        )
        .forEach(
            tr => {

                const quantidade =
                    Number(
                        tr.querySelector(
                            ".quantidadeTransferencia"
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


                total +=
                    subtotal;


                const td =
                    tr.querySelector(
                        ".subtotalTransferencia"
                    );


                if (td) {

                    td.textContent =
                        formatarMoedaTransferencia(
                            subtotal
                        );
                }
            }
        );


    $("#transferenciaValorTotal")
        .text(
            formatarMoedaTransferencia(
                total
            )
        );
}


/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/

async function guardarTransferencia(
    evento
) {

    evento.preventDefault();


    const origem =
        $("#transferenciaArmazemOrigem")
            .val();


    const destino =
        $("#transferenciaArmazemDestino")
            .val();


    if (!origem) {

        Swal.fire({
            icon: "warning",
            title: "Atenção",
            text:
                "Selecione o armazém de origem."
        });

        return;
    }


    if (!destino) {

        Swal.fire({
            icon: "warning",
            title: "Atenção",
            text:
                "Selecione o armazém de destino."
        });

        return;
    }


    if (
        origem === destino
    ) {

        Swal.fire({
            icon: "warning",
            title: "Atenção",
            text:
                "Origem e destino devem ser diferentes."
        });

        return;
    }


    const itens =
        recolherItensTransferencia();


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


    /*
     * Validação rápida no frontend.
     * O backend volta a validar.
     */

    for (
        const item of itens
    ) {

        if (
            item.quantidade <= 0
        ) {

            Swal.fire({
                icon: "warning",
                title: "Quantidade inválida",
                text:
                    "Informe quantidades superiores a zero."
            });

            return;
        }


        if (
            item.quantidade
            > item.stock_disponivel
        ) {

            Swal.fire({
                icon: "warning",
                title: "Stock insuficiente",
                text:
                    "Uma das quantidades excede o stock disponível."
            });

            return;
        }
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

                    quantidade:
                        item.quantidade
                })
            )
        )
    );


    const botao =
        document.getElementById(
            "btnGuardarTransferencia"
        );


    const htmlOriginal =
        botao.innerHTML;


    botao.disabled =
        true;


    botao.innerHTML = `
        <i class="fa fa-spinner fa-spin"></i>
        A transferir...
    `;


    try {

        const resposta =
            await fetch(
                TRANSFERENCIA_STOCK_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await lerJsonTransferencia(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
                || "Não foi possível efetuar a transferência."
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


        limparTransferencia();


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
| RECOLHER ITENS
|--------------------------------------------------------------------------
*/

function recolherItensTransferencia() {

    const itens = [];


    document
        .querySelectorAll(
            "#transferenciaProdutosBody tr"
        )
        .forEach(
            tr => {

                const produtoId =
                    Number(
                        tr.querySelector(
                            ".produtoTransferenciaSelect"
                        )?.value
                        || 0
                    );


                const quantidade =
                    Number(
                        tr.querySelector(
                            ".quantidadeTransferencia"
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

function limparLinhasTransferencia() {

    const tbody =
        document.getElementById(
            "transferenciaProdutosBody"
        );


    if (tbody) {

        tbody.innerHTML =
            "";
    }


    contadorLinhasTransferencia =
        0;


    calcularTotalTransferencia();
}


function limparTransferencia() {

    const form =
        document.getElementById(
            "formTransferenciaStock"
        );


    if (!form) {
        return;
    }


    form.reset();


    produtosTransferenciaStock =
        [];


    limparLinhasTransferencia();


    configurarDataTransferencia();


    atualizarDestinosTransferencia();


    adicionarLinhaTransferencia();
}


/*
|--------------------------------------------------------------------------
| HTTP
|--------------------------------------------------------------------------
*/

async function requisicaoTransferencia(
    acao,
    parametros = {}
) {

    const url =
        new URL(
            TRANSFERENCIA_STOCK_URL,
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


    return await validarRespostaTransferencia(
        resposta
    );
}


async function validarRespostaTransferencia(
    resposta
) {

    const json =
        await lerJsonTransferencia(
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


async function lerJsonTransferencia(
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

function formatarQuantidadeTransferencia(
    valor,
    casasDecimais = 0
) {

    const numero =
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
            minimumFractionDigits:
                0,

            maximumFractionDigits:
                Math.min(
                    casas,
                    6
                ),

            useGrouping:
                false
        }
    );
}


function formatarMoedaTransferencia(
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


function escaparHtmlTransferencia(
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