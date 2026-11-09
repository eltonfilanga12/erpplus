"use strict";


const INVENTARIO_STOCK_URL =
    "/erpplus/app/ajax/inventario_stock_ajax.php";


let inventarioAtualId = null;


/*
|--------------------------------------------------------------------------
| INICIALIZAÇÃO
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    async function () {

        const tabela =
            document.getElementById(
                "inventariosTable"
            );


        if (!tabela) {
            return;
        }


        registrarEventosInventario();

        configurarDataInventario();


        await Promise.all([
            carregarArmazensInventario(),
            carregarResumoInventario()
        ]);


        await carregarInventarios();
    }
);


/*
|--------------------------------------------------------------------------
| EVENTOS
|--------------------------------------------------------------------------
*/

function registrarEventosInventario() {

    $(document).on(
        "click",
        ".btnVerMovimentoInventario",
        function () {

            const movimentoId =
                Number(
                    $(this).data("movimento")
                );

            if (!movimentoId) {
                return;
            }

            window.location.href =
                "/erpplus/index.php?page=movimentos_stock&movimento_id="
                + movimentoId;
        }
    );

    $("#btnNovoInventario")
        .on(
            "click",
            function () {

                document
                    .getElementById(
                        "formNovoInventario"
                    )
                    ?.reset();


                configurarDataInventario();


                $("#modalNovoInventario")
                    .modal("show");
            }
        );


    $("#formNovoInventario")
        .on(
            "submit",
            criarInventario
        );


    $("#btnGuardarContagem")
        .on(
            "click",
            guardarContagemInventario
        );


    $("#btnFinalizarInventario")
        .on(
            "click",
            finalizarInventario
        );

    $("#btnCancelarInventario")
        .on(
            "click",
            cancelarInventario
        );


    $(document)
        .on(
            "click",
            ".btnAbrirInventario",
            function () {

                abrirInventario(
                    Number(
                        $(this).data("id")
                    )
                );
            }
        );


    $(document)
        .on(
            "input",
            ".quantidadeFisicaInventario",
            function () {

                atualizarDiferencaLinhaInventario(
                    this.closest("tr")
                );
            }
        );
}


async function cancelarInventario() {

    const id =
        Number(
            $("#inventarioContagemId")
                .val()
        );

    if (!id) {
        return;
    }

    const confirmacao =
        await Swal.fire({
            icon: "warning",
            title: "Cancelar inventário?",
            text:
                "A contagem será encerrada sem alterar o stock.",
            showCancelButton: true,
            confirmButtonText:
                "Sim, cancelar",
            cancelButtonText:
                "Voltar"
        });

    if (!confirmacao.isConfirmed) {
        return;
    }

    const dados =
        new FormData();

    dados.append(
        "acao",
        "cancelar"
    );

    dados.append(
        "inventario_id",
        String(id)
    );

    try {

        const resposta =
            await fetch(
                INVENTARIO_STOCK_URL,
                {
                    method: "POST",
                    body: dados
                }
            );

        const json =
            await lerJsonInventario(
                resposta
            );

        if (
            !resposta.ok
            || !json.sucesso
        ) {
            throw new Error(
                json.mensagem
                || "Não foi possível cancelar o inventário."
            );
        }

        $("#modalContagemInventario")
            .modal("hide");

        await Swal.fire({
            icon: "success",
            title: "Cancelado",
            text: json.mensagem
        });

        await Promise.all([
            carregarInventarios(),
            carregarResumoInventario()
        ]);

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
| DATA
|--------------------------------------------------------------------------
*/

function configurarDataInventario() {

    const input =
        document.getElementById(
            "inventarioDataAbertura"
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
| ARMAZÉNS
|--------------------------------------------------------------------------
*/

async function carregarArmazensInventario() {

    try {

        const resposta =
            await requisicaoInventario(
                "armazens"
            );


        const select =
            document.getElementById(
                "inventarioArmazemId"
            );


        if (!select) {
            return;
        }


        select.replaceChildren(
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
| LISTAR
|--------------------------------------------------------------------------
*/

async function carregarInventarios() {

    try {

        const resposta =
            await requisicaoInventario(
                "listar"
            );


        renderizarInventarios(
            resposta.dados
            || []
        );


    } catch (erro) {

        Swal.fire({
            icon: "error",
            title: "Erro!",
            text: erro.message
        });
    }
}


function renderizarInventarios(
    dados
) {

    const tbody =
        document.getElementById(
            "inventariosBody"
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
                    colspan="9"
                    class="text-center text-muted"
                >
                    Nenhum inventário encontrado.
                </td>
            </tr>
        `;

        return;
    }


    tbody.innerHTML =
        dados.map(
            item => `

                <tr>

                    <td>
                        ${escaparHtmlInventario(
                item.codigo
            )}
                    </td>

                    <td>
                        ${escaparHtmlInventario(
                item.armazem_nome
            )}
                    </td>

                    <td>
                        ${formatarDataInventario(
                item.data_abertura
            )}
                    </td>

                    <td>
                        ${Number(
                item.total_produtos
                || 0
            )}
                    </td>

                    <td>
                        ${Number(
                item.total_contados
                || 0
            )}
                    </td>

                    <td>
                        ${Number(
                item.total_diferencas
                || 0
            )}
                    </td>

                    <td>
                        ${badgeStatusInventario(
                item.status_nome
            )}
                    </td>

                    <td>
                        ${escaparHtmlInventario(
                item.usuario_nome
                || "-"
            )}
                    </td>

                    <td class="text-right">

    <button
        type="button"
        class="btn btn-sm btn-primary btnAbrirInventario"
        data-id="${item.id}"
        title="Abrir inventário"
    >
        <i class="fa fa-eye"></i>
    </button>

    ${item.movimento_ajuste_id
                    ? `
            <button
                type="button"
                class="btn btn-sm btn-secondary btnVerMovimentoInventario"
                data-movimento="${item.movimento_ajuste_id}"
                title="Ver movimento de ajuste"
            >
                <i class="fa fa-exchange"></i>
            </button>
        `
                    : ""
                }
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

async function carregarResumoInventario() {

    try {

        const resposta =
            await requisicaoInventario(
                "resumo"
            );


        const dados =
            resposta.dados
            || {};


        $("#resumoInventariosAbertos")
            .text(
                dados.abertos
                || 0
            );


        $("#resumoInventariosFinalizados")
            .text(
                dados.finalizados
                || 0
            );


        $("#resumoInventariosCancelados")
            .text(
                dados.cancelados
                || 0
            );


    } catch (erro) {

        console.error(
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| CRIAR
|--------------------------------------------------------------------------
*/

async function criarInventario(
    evento
) {

    evento.preventDefault();


    const dados =
        new FormData(
            evento.currentTarget
        );


    dados.append(
        "acao",
        "criar"
    );


    try {

        const resposta =
            await fetch(
                INVENTARIO_STOCK_URL,
                {
                    method: "POST",
                    body: dados
                }
            );


        const json =
            await lerJsonInventario(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
                || "Não foi possível abrir o inventário."
            );
        }


        $("#modalNovoInventario")
            .modal("hide");


        await Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: json.mensagem
        });


        await Promise.all([
            carregarInventarios(),
            carregarResumoInventario()
        ]);


        await abrirInventario(
            Number(
                json.inventario_id
            )
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
| ABRIR CONTAGEM
|--------------------------------------------------------------------------
*/

async function abrirInventario(
    id
) {

    try {

        const resposta =
            await requisicaoInventario(
                "buscar",
                {
                    id: id
                }
            );


        inventarioAtualId =
            id;


        const inventario =
            resposta.dados
                ?.inventario;


        const itens =
            resposta.dados
                ?.itens
            || [];


        $("#inventarioContagemId")
            .val(id);


        $("#contagemCodigo")
            .text(
                inventario.codigo
                || "-"
            );


        $("#contagemArmazem")
            .text(
                inventario.armazem_nome
                || "-"
            );


        $("#contagemStatus")
            .html(
                badgeStatusInventario(
                    inventario.status_nome
                )
            );


        renderizarItensInventario(
            itens,
            inventario.status_nome
        );


        const aberto =
            inventario.status_nome
            === "Aberto";


        $("#btnGuardarContagem")
            .toggle(aberto);


        $("#btnFinalizarInventario")
            .toggle(aberto);

        $("#btnCancelarInventario")
            .toggle(aberto);

        $("#modalContagemInventario")
            .modal("show");


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
| ITENS
|--------------------------------------------------------------------------
*/

function renderizarItensInventario(
    itens,
    status
) {

    const tbody =
        document.getElementById(
            "contagemInventarioBody"
        );


    if (!tbody) {
        return;
    }


    const readonly =
        status !== "Aberto";


    tbody.innerHTML =
        itens.map(
            item => {

                const fisico =
                    item.quantidade_fisica !== null
                        && item.quantidade_fisica !== undefined
                        ? formatarValorInputInventario(
                            item.quantidade_fisica
                        )
                        : "";


                return `

                    <tr
                        data-id="${item.id}"
                        data-sistema="${Number(
                    item.quantidade_sistema
                    || 0
                )}"
                    >

                        <td>
                            ${escaparHtmlInventario(
                    item.produto_nome
                )}
                        </td>

                        <td>
                            ${escaparHtmlInventario(
                    item.sku
                )}
                        </td>

                        <td>
                            ${escaparHtmlInventario(
                    item.unidade_codigo
                    || "-"
                )}
                        </td>

                        <td class="text-center">
                            ${formatarQuantidadeInventario(
                    item.quantidade_sistema
                )}
                        </td>

                        <td>

                            <input
                                type="number"
                                class="form-control quantidadeFisicaInventario"
                                value="${fisico}"
                                min="0"
                                step="0.001"
                                ${readonly
                        ? "readonly"
                        : ""}
                            >

                        </td>

                        <td class="diferencaInventario text-center">
                            ${fisico === ""
                        ? "-"
                        : formatarDiferencaInventario(
                            Number(fisico)
                            -
                            Number(
                                item.quantidade_sistema
                                || 0
                            )
                        )
                    }
                        </td>

                        <td>

                            <input
                                type="text"
                                class="form-control observacaoInventario"
                                value="${escaparHtmlInventario(
                        item.observacoes
                        || ""
                    )}"
                                ${readonly
                        ? "readonly"
                        : ""}
                            >

                        </td>

                    </tr>
                `;
            }
        ).join("");
}


/*
|--------------------------------------------------------------------------
| DIFERENÇA
|--------------------------------------------------------------------------
*/

function atualizarDiferencaLinhaInventario(
    tr
) {

    if (!tr) {
        return;
    }


    const sistema =
        Number(
            tr.dataset.sistema
            || 0
        );


    const input =
        tr.querySelector(
            ".quantidadeFisicaInventario"
        );


    const td =
        tr.querySelector(
            ".diferencaInventario"
        );


    if (
        !input
        || !td
    ) {
        return;
    }


    if (input.value === "") {

        td.textContent = "-";

        return;
    }


    const fisico =
        Number(
            input.value
        );


    td.textContent =
        formatarDiferencaInventario(
            fisico
            - sistema
        );
}


/*
|--------------------------------------------------------------------------
| GUARDAR CONTAGEM
|--------------------------------------------------------------------------
*/

async function guardarContagemInventario() {

    const id =
        Number(
            $("#inventarioContagemId")
                .val()
        );


    const itens =
        recolherContagemInventario();


    if (
        itens.length === 0
    ) {

        Swal.fire({
            icon: "warning",
            title: "Atenção",
            text:
                "Não existem produtos para guardar."
        });

        return false;
    }


    const dados =
        new FormData();


    dados.append(
        "acao",
        "guardar_contagem"
    );


    dados.append(
        "inventario_id",
        String(id)
    );


    dados.append(
        "itens",
        JSON.stringify(
            itens
        )
    );


    try {

        const resposta =
            await fetch(
                INVENTARIO_STOCK_URL,
                {
                    method: "POST",
                    body: dados
                }
            );


        const json =
            await lerJsonInventario(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
            );
        }


        Swal.fire({
            icon: "success",
            title: "Guardado",
            text: json.mensagem
        });


        await carregarInventarios();


        return true;


    } catch (erro) {

        Swal.fire({
            icon: "error",
            title: "Erro!",
            text: erro.message
        });


        return false;
    }
}


/*
|--------------------------------------------------------------------------
| FINALIZAR
|--------------------------------------------------------------------------
*/

async function finalizarInventario() {

    const confirmado =
        await Swal.fire({
            icon: "warning",
            title: "Finalizar inventário?",
            text:
                "As diferenças encontradas serão aplicadas ao stock.",
            showCancelButton: true,
            confirmButtonText:
                "Sim, finalizar",
            cancelButtonText:
                "Cancelar"
        });


    if (
        !confirmado.isConfirmed
    ) {
        return;
    }


    /*
     * Primeiro guarda a contagem.
     */

    const guardado =
        await guardarContagemInventario();


    if (!guardado) {
        return;
    }


    const id =
        Number(
            $("#inventarioContagemId")
                .val()
        );


    const dados =
        new FormData();


    dados.append(
        "acao",
        "finalizar"
    );


    dados.append(
        "inventario_id",
        String(id)
    );


    try {

        const resposta =
            await fetch(
                INVENTARIO_STOCK_URL,
                {
                    method: "POST",
                    body: dados
                }
            );


        const json =
            await lerJsonInventario(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
            );
        }


        $("#modalContagemInventario")
            .modal("hide");


        await Swal.fire({
            icon: "success",
            title: "Inventário finalizado",
            text: json.mensagem
        });


        await Promise.all([
            carregarInventarios(),
            carregarResumoInventario()
        ]);


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
| RECOLHER CONTAGEM
|--------------------------------------------------------------------------
*/

function recolherContagemInventario() {

    const itens = [];


    document
        .querySelectorAll(
            "#contagemInventarioBody tr"
        )
        .forEach(
            tr => {

                const quantidade =
                    tr.querySelector(
                        ".quantidadeFisicaInventario"
                    );


                const observacao =
                    tr.querySelector(
                        ".observacaoInventario"
                    );


                /*
                 * IMPORTANTE:
                 * vazio significa "não contado".
                 */

                if (
                    !quantidade
                    || quantidade.value === ""
                ) {

                    return;
                }


                itens.push({
                    id:
                        Number(
                            tr.dataset.id
                        ),

                    quantidade_fisica:
                        Number(
                            quantidade.value
                        ),

                    observacoes:
                        observacao
                            ?.value
                            ?.trim()
                        || ""
                });
            }
        );


    return itens;
}


/*
|--------------------------------------------------------------------------
| HTTP
|--------------------------------------------------------------------------
*/

async function requisicaoInventario(
    acao,
    parametros = {}
) {

    const url =
        new URL(
            INVENTARIO_STOCK_URL,
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
        await lerJsonInventario(
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


async function lerJsonInventario(
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
            "Resposta inválida:",
            texto
        );


        throw new Error(
            "O servidor devolveu uma resposta inválida."
        );
    }
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function badgeStatusInventario(
    status
) {

    switch (status) {

        case "Aberto":

            return `
                <span class="badge badge-warning">
                    Aberto
                </span>
            `;


        case "Finalizado":

            return `
                <span class="badge badge-success">
                    Finalizado
                </span>
            `;


        case "Cancelado":

            return `
                <span class="badge badge-danger">
                    Cancelado
                </span>
            `;


        default:

            return `
                <span class="badge badge-secondary">
                    ${escaparHtmlInventario(
                status
                || "-"
            )}
                </span>
            `;
    }
}


function formatarQuantidadeInventario(
    valor
) {

    return Number(
        valor
        || 0
    ).toLocaleString(
        "pt-PT",
        {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3,
            useGrouping: false
        }
    );
}


function formatarValorInputInventario(
    valor
) {

    if (
        valor === null
        || valor === undefined
        || valor === ""
    ) {
        return "";
    }

    const numero =
        Number(valor);

    if (
        Number.isNaN(numero)
    ) {
        return "";
    }

    return String(numero);
}


function formatarDiferencaInventario(
    valor
) {

    const numero =
        Number(
            valor
            || 0
        );


    if (numero > 0) {

        return "+"
            + formatarQuantidadeInventario(
                numero
            );
    }


    return formatarQuantidadeInventario(
        numero
    );
}


function formatarDataInventario(
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

        return valor;
    }


    return data.toLocaleString(
        "pt-MZ"
    );
}


function escaparHtmlInventario(
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