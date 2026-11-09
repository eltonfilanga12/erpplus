const COTACAO_URL =
    "/erpplus/app/ajax/cotacao_ajax.php";


let tabelaCotacoes = null;

let moduloCotacoesInicializado = false;

let timerPesquisaCliente = null;

let timerPesquisaProduto = null;


// =========================================================
// INICIALIZAÇÃO
// =========================================================

document.addEventListener(
    "DOMContentLoaded",
    inicializarCotacoes
);


async function inicializarCotacoes() {

    if (
        moduloCotacoesInicializado
    ) {
        return;
    }

    moduloCotacoesInicializado = true;


    configurarEventosCotacoes();


    await carregarStatusCotacoes();


    carregarTabelaCotacoes();
}


// =========================================================
// EVENTOS
// =========================================================

function configurarEventosCotacoes() {

    $("#btnNovaCotacao")
        .off("click.cotacao")
        .on(
            "click.cotacao",
            abrirNovaCotacao
        );


    $("#formCotacao")
        .off("submit.cotacao")
        .on(
            "submit.cotacao",
            guardarCotacao
        );


    $("#formEliminarCotacao")
        .off("submit.cotacao")
        .on(
            "submit.cotacao",
            eliminarCotacao
        );


    $(document)
        .off(
            "click.cotacao",
            "#cotacoesTable .editarCotacao"
        )
        .on(
            "click.cotacao",
            "#cotacoesTable .editarCotacao",
            function () {

                const id =
                    Number(
                        $(this).data("id")
                    );

                editarCotacao(id);
            }
        );


    $(document)
        .off(
            "click.cotacao",
            "#cotacoesTable .eliminarCotacao"
        )
        .on(
            "click.cotacao",
            "#cotacoesTable .eliminarCotacao",
            function () {

                const id =
                    Number(
                        $(this).data("id")
                    );

                abrirEliminarCotacao(id);
            }
        );


    // =====================================================
    // IMPRIMIR
    // =====================================================

    $(document)
        .off(
            "click.cotacao",
            "#cotacoesTable .imprimirCotacao"
        )
        .on(
            "click.cotacao",
            "#cotacoesTable .imprimirCotacao",
            function () {

                const id =
                    Number(
                        $(this).data("id")
                    );


                imprimirCotacao(id);
            }
        );


    // =====================================================
    // PESQUISA CLIENTE
    // =====================================================

    $("#cotacaoClienteNome")
        .off("input.cotacao")
        .on(
            "input.cotacao",
            function () {

                const termo =
                    $(this)
                        .val()
                        .trim();


                $("#cotacaoClienteId")
                    .val("");


                if (
                    termo.length < 2
                ) {

                    esconderSugestoesClientes();

                    return;
                }


                clearTimeout(
                    timerPesquisaCliente
                );


                timerPesquisaCliente =
                    setTimeout(
                        function () {

                            pesquisarClientesCotacao(
                                termo
                            );

                        },
                        300
                    );
            }
        );


    // =====================================================
    // PESQUISA PRODUTO
    // =====================================================

    $("#cotacaoProdutoPesquisa")
        .off("input.cotacao")
        .on(
            "input.cotacao",
            function () {

                const termo =
                    $(this)
                        .val()
                        .trim();


                if (
                    termo.length < 2
                ) {

                    esconderSugestoesProdutos();

                    return;
                }


                clearTimeout(
                    timerPesquisaProduto
                );


                timerPesquisaProduto =
                    setTimeout(
                        function () {

                            pesquisarProdutosCotacao(
                                termo
                            );

                        },
                        300
                    );
            }
        );


    // =====================================================
    // ADICIONAR ITEM MANUAL
    // =====================================================

    $("#btnAdicionarItemCotacao")
        .off("click.cotacao")
        .on(
            "click.cotacao",
            adicionarItemCotacao
        );


    // =====================================================
    // REMOVER ITEM
    // =====================================================

    $(document)
        .off(
            "click.cotacao",
            ".btn-remover-item-cotacao"
        )
        .on(
            "click.cotacao",
            ".btn-remover-item-cotacao",
            function () {

                $(this)
                    .closest("tr")
                    .remove();


                recalcularCotacao();
            }
        );


    // =====================================================
    // ALTERAÇÃO DOS CAMPOS DOS ITENS
    // =====================================================

    $(document)
        .off(
            "input.cotacaoCotacao change.cotacaoCotacao",
            "#cotacaoItensBody .item-quantidade, #cotacaoItensBody .item-preco, #cotacaoItensBody .item-desconto, #cotacaoItensBody .item-imposto"
        )
        .on(
            "input.cotacaoCotacao change.cotacaoCotacao",
            "#cotacaoItensBody .item-quantidade, #cotacaoItensBody .item-preco, #cotacaoItensBody .item-desconto, #cotacaoItensBody .item-imposto",
            function () {

                recalcularCotacao();
            }
        );

    // =====================================================
    // MODAL
    // =====================================================

    $("#modalCotacao")
        .off(
            "hidden.bs.modal.cotacao"
        )
        .on(
            "hidden.bs.modal.cotacao",
            function () {

                limparFormularioCotacao();
            }
        );


    // =====================================================
    // FECHAR SUGESTÕES
    // =====================================================

    $(document)
        .off(
            "click.cotacao",
            function () { }
        );


    $(document).on(
        "click.cotacao",
        function (evento) {

            if (
                !$(evento.target)
                    .closest(
                        "#cotacaoClienteNome, #listaSugestoesClientes"
                    ).length
            ) {

                esconderSugestoesClientes();
            }


            if (
                !$(evento.target)
                    .closest(
                        "#cotacaoProdutoPesquisa, #listaSugestoesProdutos"
                    ).length
            ) {

                esconderSugestoesProdutos();
            }
        }
    );
}


// =========================================================
// TABELA
// =========================================================

// =========================================================
// TABELA
// =========================================================

function carregarTabelaCotacoes() {

    if (
        $.fn.DataTable.isDataTable("#cotacoesTable")
    ) {

        tabelaCotacoes =
            $("#cotacoesTable")
                .DataTable();


        tabelaCotacoes.ajax.reload(
            null,
            false
        );


        return;
    }


    tabelaCotacoes =
        $("#cotacoesTable").DataTable({

            processing: true,

            responsive: true,

            pageLength: 10,

            order: [
                [1, "desc"]
            ],

            ajax: {

                url: COTACAO_URL,

                type: "GET",

                data: {
                    acao: "listar"
                },

                dataSrc: function (resposta) {

                    console.log(
                        "Resposta listar cotacoes:",
                        resposta
                    );


                    if (
                        !resposta
                        || resposta.sucesso !== true
                    ) {

                        Swal.fire(
                            "Erro",
                            resposta?.mensagem
                            || "Não foi possível carregar as cotações.",
                            "error"
                        );


                        return [];
                    }


                    const dados =
                        Array.isArray(
                            resposta.dados
                        )
                            ? resposta.dados
                            : [];


                    console.log(
                        "Dados das cotações:",
                        dados
                    );


                    return dados;
                }
            },


            columns: [

                // ==================================================
                // #
                // ==================================================
                {
                    data: null,

                    className: "text-center",

                    orderable: false,

                    searchable: false,

                    render: function (
                        data,
                        type,
                        row,
                        meta
                    ) {

                        return String(
                            meta.row + 1
                        ).padStart(
                            4,
                            "0"
                        );
                    }
                },


                // ==================================================
                // NÚMERO
                // ==================================================
                {
                    data: "numero",

                    defaultContent: "-",

                    render: function (
                        data
                    ) {

                        return data
                            || "-";
                    }
                },


                // ==================================================
                // EMISSÃO
                // ==================================================
                {
                    data: "data_emissao",

                    defaultContent: "-",

                    render: function (
                        data
                    ) {

                        return formatarDataCotacao(
                            data
                        );
                    }
                },


                // ==================================================
                // VALIDADE
                // ==================================================
                {
                    data: "validade",

                    defaultContent: "-",

                    render: function (
                        data
                    ) {

                        return formatarDataCotacao(
                            data
                        );
                    }
                },


                // ==================================================
                // CLIENTE
                // ==================================================
                {
                    data: "cliente_nome",

                    defaultContent: "-",

                    render: function (
                        data
                    ) {

                        return data
                            || "-";
                    }
                },


                // ==================================================
                // NUIT
                // ==================================================
                {
                    data: "cliente_nuit",

                    defaultContent: "-",

                    render: function (
                        data
                    ) {

                        return data
                            || "-";
                    }
                },


                // ==================================================
                // TOTAL
                // ==================================================
                {
                    data: "total",

                    defaultContent: "0",

                    className: "text-end",

                    render: function (
                        data
                    ) {

                        return formatarMoedaCotacao(
                            data
                        );
                    }
                },


                // ==================================================
                // ESTADO
                // ==================================================
                {
                    data: "status_nome",

                    defaultContent: "-",

                    className: "text-center",

                    render: function (
                        data
                    ) {

                        const estado =
                            String(
                                data
                                || ""
                            ).trim();


                        if (
                            !estado
                        ) {

                            return `
                                <span class="badge bg-secondary">
                                    -
                                </span>
                            `;
                        }


                        let classe =
                            "bg-secondary";


                        switch (
                        estado.toLowerCase()
                        ) {

                            case "rascunho":

                                classe =
                                    "bg-secondary";

                                break;


                            case "emitida":

                                classe =
                                    "bg-primary";

                                break;


                            case "aceita":

                                classe =
                                    "bg-success";

                                break;


                            case "recusada":

                                classe =
                                    "bg-danger";

                                break;


                            case "expirada":

                                classe =
                                    "bg-warning text-dark";

                                break;


                            case "cancelada":

                                classe =
                                    "bg-danger";

                                break;
                        }


                        return `
                            <span class="badge ${classe}">
                                ${escaparHtmlCotacao(estado)}
                            </span>
                        `;
                    }
                },


                // ==================================================
                // AÇÕES
                // ==================================================
                {
                    data: null,

                    className: "text-center",

                    orderable: false,

                    searchable: false,

                    render: function (
                        data,
                        type,
                        row
                    ) {

                        const id =
                            Number(
                                row.id
                            );


                        if (!id) {

                            return "";
                        }


                        return `
                            <div
                                class="btn-group"
                                role="group"
                            >

                                <button
                                    type="button"
                                    class="btn btn-sm btn-primary editarCotacao"
                                    data-id="${id}"
                                    title="Editar"
                                >
                                    <i class="fa fa-edit"></i>
                                </button>


                                <button
                                    type="button"
                                    class="btn btn-sm btn-warning imprimirCotacao"
                                    data-id="${id}"
                                    title="Imprimir"
                                >
                                    <i class="fa fa-print"></i>
                                </button>


                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger eliminarCotacao"
                                    data-id="${id}"
                                    title="Eliminar"
                                >
                                    <i class="fa fa-trash"></i>
                                </button>

                            </div>
                        `;
                    }
                }

            ],


            language: {

                processing:
                    "A processar...",

                search:
                    "Procurar:",

                lengthMenu:
                    "Mostrar _MENU_ registos",

                info:
                    "Mostrando os registos _START_ a _END_ num total de _TOTAL_",

                infoEmpty:
                    "Nenhum registo encontrado",

                infoFiltered:
                    "(filtrado de _MAX_ registos)",

                zeroRecords:
                    "Nenhuma cotação encontrada",

                emptyTable:
                    "Nenhuma cotação registada",

                paginate: {

                    first:
                        "Primeiro",

                    previous:
                        "Anterior",

                    next:
                        "Seguinte",

                    last:
                        "Último"
                }
            }
        });
}


// =========================================================
// STATUS
// =========================================================

async function carregarStatusCotacoes() {

    const select =
        document.getElementById(
            "cotacaoStatusId"
        );


    if (!select) {
        return;
    }


    select.innerHTML =
        `
        <option value="">
            A carregar estados...
        </option>
        `;


    try {

        const resposta =
            await fetch(
                COTACAO_URL
                + "?acao=status",
                {
                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonCotacao(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
                || "Erro ao carregar estados."
            );
        }


        const estados =
            json.dados
            ?? [];


        select.innerHTML =
            `
            <option value="">
                Selecione...
            </option>
            `;


        estados.forEach(
            function (
                status
            ) {

                select.insertAdjacentHTML(
                    "beforeend",
                    `
                    <option value="${status.id}">
                        ${escaparHtmlCotacao(status.nome)}
                    </option>
                    `
                );
            }
        );


        if (
            estados.length === 0
        ) {

            select.innerHTML =
                `
                <option value="">
                    Nenhum estado configurado
                </option>
                `;
        }

    } catch (erro) {

        console.error(
            "Erro ao carregar status:",
            erro
        );


        select.innerHTML =
            `
            <option value="">
                Erro ao carregar estados
            </option>
            `;
    }
}


// =========================================================
// NOVA COTAÇÃO
// =========================================================

async function abrirNovaCotacao() {

    limparFormularioCotacao();


    $("#tituloModalCotacao")
        .text(
            "Nova Cotação"
        );


    $("#modalCotacao")
        .modal("show");


    try {

        const resposta =
            await fetch(
                COTACAO_URL
                + "?acao=gerar_numero",
                {
                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonCotacao(
                resposta
            );


        if (
            resposta.ok
            && json.sucesso
            && json.dados
        ) {

            $("#cotacaoNumero")
                .val(
                    json.dados.numero
                    ?? ""
                );
        }

    } catch (erro) {

        console.error(
            "Erro ao gerar número:",
            erro
        );
    }


    const hoje =
        new Date();


    const dataHoje =
        hoje.toISOString()
            .slice(
                0,
                10
            );


    if (
        !$("#cotacaoDataEmissao").val()
    ) {

        $("#cotacaoDataEmissao")
            .val(
                dataHoje
            );
    }
}


// =========================================================
// EDITAR
// =========================================================

async function editarCotacao(
    id
) {

    if (!id) {
        return;
    }


    try {

        const resposta =
            await fetch(
                COTACAO_URL
                + "?acao=buscar&id="
                + encodeURIComponent(id),
                {
                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonCotacao(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
                || "Erro ao buscar cotação."
            );
        }


        const cotacao =
            json.dados;


        limparFormularioCotacao();


        $("#cotacaoId")
            .val(
                cotacao.id
            );


        $("#cotacaoNumero")
            .val(
                cotacao.numero
                ?? ""
            );


        $("#cotacaoDataEmissao")
            .val(
                cotacao.data_emissao
                ?? ""
            );


        $("#cotacaoValidade")
            .val(
                cotacao.validade
                ?? ""
            );


        $("#cotacaoStatusId")
            .val(
                cotacao.status_id
                ?? ""
            );


        $("#cotacaoClienteId")
            .val(
                cotacao.cliente_id
                ?? ""
            );


        $("#cotacaoClienteNome")
            .val(
                cotacao.cliente_nome
                ?? ""
            );


        $("#cotacaoClienteNuit")
            .val(
                cotacao.cliente_nuit
                ?? ""
            );


        $("#cotacaoClienteTelefone")
            .val(
                cotacao.cliente_telefone
                ?? ""
            );


        $("#cotacaoClienteEndereco")
            .val(
                cotacao.cliente_endereco
                ?? ""
            );


        $("#cotacaoObservacoes")
            .val(
                cotacao.observacoes
                ?? ""
            );


        const itens =
            cotacao.itens
            ?? [];


        itens.forEach(
            function (
                item
            ) {

                adicionarLinhaItemCotacao(
                    item
                );
            }
        );


        recalcularCotacao();


        $("#tituloModalCotacao")
            .text(
                "Editar Cotação"
            );


        $("#modalCotacao")
            .modal("show");

    } catch (erro) {

        console.error(
            "Erro ao editar cotação:",
            erro
        );


        mostrarMensagemCotacao(
            false,
            erro.message
        );
    }
}


// =========================================================
// PESQUISAR CLIENTES
// =========================================================

async function pesquisarClientesCotacao(
    termo
) {

    try {

        const resposta =
            await fetch(
                COTACAO_URL
                + "?acao=clientes&termo="
                + encodeURIComponent(termo),
                {
                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonCotacao(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
                || "Erro ao pesquisar clientes."
            );
        }


        mostrarSugestoesClientes(
            json.dados
            ?? []
        );

    } catch (erro) {

        console.error(
            "Erro ao pesquisar clientes:",
            erro
        );


        esconderSugestoesClientes();
    }
}


// =========================================================
// MOSTRAR CLIENTES
// =========================================================

// =========================================================
// MOSTRAR CLIENTES
// =========================================================

function mostrarSugestoesClientes(clientes) {

    let lista = $("#listaSugestoesClientes");

    if (!lista.length) {

        $("#cotacaoClienteNome").after(`
            <div
                id="listaSugestoesClientes"
                class="list-group position-absolute w-100"
                style="z-index:1050;"
            ></div>
        `);

        lista = $("#listaSugestoesClientes");
    }

    lista.empty();

    // =====================================================
    // NENHUM CLIENTE
    // =====================================================

    if (!Array.isArray(clientes) || clientes.length === 0) {

        lista.append(`
            <div class="list-group-item text-muted">
                Nenhum cliente encontrado.
            </div>
        `);

        lista.show();

        return;
    }

    // =====================================================
    // CLIENTES ENCONTRADOS
    // =====================================================

    clientes.forEach(function (cliente) {

        const id =
            cliente.id ?? "";

        const nome =
            cliente.nome ?? "";

        const nuit =
            cliente.nuit ?? "";

        const telefone =
            cliente.telefone ?? "";

        const endereco =
            cliente.endereco ?? "";

        lista.append(`
            <button
                type="button"
                class="list-group-item list-group-item-action clienteSugestaoCotacao"

                data-id="${escaparHtmlCotacao(id)}"
                data-nome="${escaparHtmlCotacao(nome)}"
                data-nuit="${escaparHtmlCotacao(nuit)}"
                data-telefone="${escaparHtmlCotacao(telefone)}"
                data-endereco="${escaparHtmlCotacao(endereco)}"
            >

                <strong>
                    ${escaparHtmlCotacao(nome)}
                </strong>

                <br>

                <small>
                    NUIT:
                    ${escaparHtmlCotacao(nuit || "-")}

                    &nbsp; | &nbsp;

                    Tel.:
                    ${escaparHtmlCotacao(telefone || "-")}
                </small>

            </button>
        `);
    });

    // =====================================================
    // EVENTO SELECIONAR CLIENTE
    // =====================================================

    $(".clienteSugestaoCotacao")
        .off("click.cotacao")
        .on(
            "click.cotacao",
            function () {

                const id =
                    $(this).attr("data-id");

                selecionarClienteCotacao(id);
            }
        );

    lista.show();
}


// =========================================================
// SELECIONAR CLIENTE
// =========================================================

// =========================================================
// SELECIONAR CLIENTE
// =========================================================

function selecionarClienteCotacao(id) {

    const botao =
        $(
            `.clienteSugestaoCotacao[data-id="${id}"]`
        );

    if (!botao.length) {
        return;
    }

    const clienteId =
        botao.attr("data-id") || "";

    const nome =
        botao.attr("data-nome") || "";

    const nuit =
        botao.attr("data-nuit") || "";

    const telefone =
        botao.attr("data-telefone") || "";

    const endereco =
        botao.attr("data-endereco") || "";

    // =====================================================
    // PREENCHER FORMULÁRIO
    // =====================================================

    $("#cotacaoClienteId")
        .val(clienteId);

    $("#cotacaoClienteNome")
        .val(nome);

    $("#cotacaoClienteNuit")
        .val(nuit);

    $("#cotacaoClienteTelefone")
        .val(telefone);

    $("#cotacaoClienteEndereco")
        .val(endereco);

    // =====================================================
    // ESCONDER SUGESTÕES
    // =====================================================

    esconderSugestoesClientes();
}


// =========================================================
// BUSCAR DADOS COMPLETOS DO CLIENTE
// =========================================================

async function pesquisarClienteCompletoCotacao(
    nome,
    id
) {

    try {

        const resposta =
            await fetch(
                COTACAO_URL
                + "?acao=clientes&termo="
                + encodeURIComponent(nome),
                {
                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonCotacao(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {
            return;
        }


        const cliente =
            (
                json.dados
                ?? []
            ).find(
                function (
                    item
                ) {

                    return Number(
                        item.id
                    ) === Number(id);
                }
            );


        if (!cliente) {
            return;
        }


        $("#cotacaoClienteId")
            .val(
                cliente.id
            );


        $("#cotacaoClienteNome")
            .val(
                cliente.nome
                ?? ""
            );


        $("#cotacaoClienteNuit")
            .val(
                cliente.nuit
                ?? ""
            );


        $("#cotacaoClienteTelefone")
            .val(
                cliente.telefone
                ?? ""
            );


        $("#cotacaoClienteEndereco")
            .val(
                cliente.endereco
                ?? ""
            );


    } catch (erro) {

        console.error(
            "Erro ao obter dados do cliente:",
            erro
        );
    }
}


// =========================================================
// ESCONDER CLIENTES
// =========================================================

function esconderSugestoesClientes() {

    $("#listaSugestoesClientes")
        .hide();
}


// =========================================================
// PESQUISAR PRODUTOS
// =========================================================

async function pesquisarProdutosCotacao(
    termo
) {

    try {

        const resposta =
            await fetch(
                COTACAO_URL
                + "?acao=produtos&termo="
                + encodeURIComponent(termo),
                {
                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonCotacao(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
                || "Erro ao pesquisar produtos."
            );
        }


        mostrarSugestoesProdutos(
            json.dados
            ?? []
        );

    } catch (erro) {

        console.error(
            "Erro ao pesquisar produtos:",
            erro
        );


        esconderSugestoesProdutos();
    }
}


// =========================================================
// MOSTRAR PRODUTOS
// =========================================================

function mostrarSugestoesProdutos(
    produtos
) {

    const lista =
        $("#listaSugestoesProdutos");


    if (!lista.length) {
        return;
    }


    lista.empty();


    if (
        produtos.length === 0
    ) {

        lista.append(
            `
            <div class="list-group-item text-muted">
                Nenhum produto encontrado.
            </div>
            `
        );


        lista.show();

        return;
    }


    produtos.forEach(
        function (
            produto
        ) {

            lista.append(
                `
                <button
                    type="button"
                    class="list-group-item list-group-item-action produtoSugestaoCotacao"
                    data-produto='${JSON.stringify(produto).replace(/'/g, "&#039;")}'
                >

                    <strong>
                        ${escaparHtmlCotacao(produto.nome || "")}
                    </strong>

                    <br>

                    <small>
                        SKU:
                        ${escaparHtmlCotacao(produto.sku || "-")}

                        &nbsp; | &nbsp;

                        Preço:
                        ${formatarMoedaCotacao(produto.preco_venda || 0)}
                    </small>

                </button>
                `
            );
        }
    );


    $(".produtoSugestaoCotacao")
        .off(
            "click.cotacao"
        )
        .on(
            "click.cotacao",
            function () {

                let produto;


                try {

                    produto =
                        JSON.parse(
                            $(this)
                                .attr(
                                    "data-produto"
                                )
                                .replace(
                                    /&#039;/g,
                                    "'"
                                )
                        );

                } catch (erro) {

                    console.error(
                        erro
                    );

                    return;
                }


                adicionarProdutoCotacao(
                    produto
                );
            }
        );


    lista.show();
}


// =========================================================
// ESCONDER PRODUTOS
// =========================================================

function esconderSugestoesProdutos() {

    $("#listaSugestoesProdutos")
        .hide();
}


// =========================================================
// ADICIONAR PRODUTO
// =========================================================

function adicionarProdutoCotacao(produto) {

    adicionarLinhaItemCotacao({

        produto_id:
            produto.id ?? null,

        produto_sku:
            produto.sku ?? "",

        produto_nome:
            produto.nome ?? "",

        produto_descricao:
            produto.descricao ?? "",

        unidade:
            produto.unidade_codigo ||
            produto.unidade_nome ||
            "UN",

        quantidade: 1,

        preco_unitario:
            Number(
                produto.preco_venda ?? 0
            ),

        desconto_percentual: 0,

        imposto_percentual: 16

    });

    esconderSugestoesProdutos();

    $("#cotacaoProdutoPesquisa")
        .val("");

}


// =========================================================
// ADICIONAR ITEM MANUAL
// =========================================================

function adicionarItemCotacao() {

    adicionarLinhaItemCotacao({

        produto_id:
            null,

        produto_sku:
            "",

        produto_nome:
            "",

        produto_descricao:
            "",

        unidade:
            "UN",

        quantidade:
            1,

        preco_unitario:
            0,

        desconto_percentual:
            0,

        imposto_percentual:
            16
    });


    recalcularCotacao();
}


// =========================================================
// ADICIONAR LINHA
// =========================================================

function adicionarLinhaItemCotacao(item) {

    const tbody = $("#cotacaoItensBody");

    if (!tbody.length) {
        console.error("Elemento #cotacaoItensBody não encontrado.");
        return;
    }

    const produtoId = item.produto_id ?? "";
    const produtoSku = item.produto_sku ?? "";
    const produtoNome = item.produto_nome ?? "";
    const produtoDescricao = item.produto_descricao ?? "";
    const unidade = item.unidade ?? "UN";

    const quantidade = Number(item.quantidade ?? 1);
    const precoUnitario = Number(item.preco_unitario ?? 0);
    const descontoPercentual = Number(
        item.desconto_percentual ?? 0
    );
    const impostoPercentual = Number(
        item.imposto_percentual ?? 16
    );

    const linha = $(`
        <tr class="cotacao-item">

            <!-- DADOS OCULTOS -->

            <input
                type="hidden"
                class="item-produto-id"
                value="${produtoId}"
            >

            <input
                type="hidden"
                class="item-produto-sku"
                value="${escaparHtmlCotacao(produtoSku)}"
            >

            <input
                type="hidden"
                class="item-produto-nome"
                value="${escaparHtmlCotacao(produtoNome)}"
            >

            <input
                type="hidden"
                class="item-produto-descricao"
                value="${escaparHtmlCotacao(produtoDescricao)}"
            >

            <input
                type="hidden"
                class="item-unidade"
                value="${escaparHtmlCotacao(unidade)}"
            >


            <!-- # -->

            <td class="text-center item-numero">
                1
            </td>


            <!-- PRODUTO -->

            <td>

                <div class="font-weight-bold">
                    ${escaparHtmlCotacao(
        produtoNome || "Produto"
    )}
                </div>

                ${produtoSku
            ? `
                            <small class="text-muted">
                                SKU: ${escaparHtmlCotacao(produtoSku)}
                            </small>
                        `
            : ""
        }

                ${produtoDescricao
            ? `
                            <div class="small text-muted">
                                ${escaparHtmlCotacao(
                produtoDescricao
            )}
                            </div>
                        `
            : ""
        }

                <div class="small text-muted">
                    Unidade:
                    ${escaparHtmlCotacao(unidade)}
                </div>

            </td>


            <!-- QUANTIDADE -->

            <td>

                <input
                    type="number"
                    class="form-control form-control-sm text-right item-quantidade"
                    min="0.001"
                    step="0.001"
                    value="${quantidade}"
                >

            </td>


            <!-- PREÇO UNITÁRIO -->

            <td>

                <input
                    type="number"
                    class="form-control form-control-sm text-right item-preco"
                    min="0"
                    step="0.01"
                    value="${precoUnitario.toFixed(2)}"
                >

            </td>


            <!-- DESCONTO -->

            <td>

                <input
                    type="number"
                    class="form-control form-control-sm text-right item-desconto"
                    min="0"
                    max="100"
                    step="0.01"
                    value="${descontoPercentual.toFixed(2)}"
                >

            </td>


            <!-- IMPOSTO -->

            <td>

                <input
                    type="number"
                    class="form-control form-control-sm text-right item-imposto"
                    min="0"
                    max="100"
                    step="0.01"
                    value="${impostoPercentual.toFixed(2)}"
                >

            </td>


            <!-- TOTAL -->

            <td class="text-right">

                <span class="font-weight-bold item-total">
                    0,00 MT
                </span>

            </td>


            <!-- AÇÃO -->

            <td class="text-center">

                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger removerItemCotacao"
                    title="Remover produto">

                    <i class="fa fa-trash"></i>

                </button>

            </td>

        </tr>
    `);

    tbody.append(linha);

    atualizarNumeracaoItens();

    // Recalcular imediatamente
    recalcularCotacao();

    // Atualizar estado da mensagem "Adicione pelo menos..."
    atualizarEstadoItensCotacao();
}


// =========================================================
// NUMERAÇÃO
// =========================================================

function atualizarNumeracaoItens() {

    $("#cotacaoItensBody tr")
        .each(
            function (
                indice
            ) {

                $(this)
                    .find(
                        ".numero-item"
                    )
                    .text(
                        indice + 1
                    );
            }
        );
}


function atualizarEstadoItensCotacao() {

    const quantidade =
        $("#cotacaoItensBody tr.cotacao-item").length;

    if (quantidade > 0) {

        $("#cotacaoItensVazio")
            .addClass("d-none");

    } else {

        $("#cotacaoItensVazio")
            .removeClass("d-none");

    }
}


// =========================================================
// RECALCULAR
// =========================================================

function recalcularCotacao() {

    let subtotalGeral = 0;
    let descontoGeral = 0;
    let impostoGeral = 0;
    let totalGeral = 0;

    $("#cotacaoItensBody tr.cotacao-item").each(function () {

        const linha = $(this);

        const quantidade =
            Number(
                linha.find(".item-quantidade").val()
            ) || 0;

        const precoUnitario =
            Number(
                linha.find(".item-preco").val()
            ) || 0;

        const descontoPercentual =
            Number(
                linha.find(".item-desconto").val()
            ) || 0;

        const impostoPercentual =
            Number(
                linha.find(".item-imposto").val()
            ) || 0;


        // ==============================
        // SUBTOTAL
        // ==============================

        const subtotal =
            quantidade * precoUnitario;


        // ==============================
        // DESCONTO
        // ==============================

        const descontoValor =
            subtotal *
            (descontoPercentual / 100);


        // ==============================
        // BASE TRIBUTÁVEL
        // ==============================

        const baseTributavel =
            subtotal - descontoValor;


        // ==============================
        // IMPOSTO / IVA
        // ==============================

        const impostoValor =
            baseTributavel *
            (impostoPercentual / 100);


        // ==============================
        // TOTAL
        // ==============================

        const total =
            baseTributavel + impostoValor;


        // ==============================
        // ACUMULAR
        // ==============================

        subtotalGeral += subtotal;

        descontoGeral += descontoValor;

        impostoGeral += impostoValor;

        totalGeral += total;


        // ==============================
        // TOTAL DA LINHA
        // ==============================

        linha
            .find(".item-total")
            .text(
                formatarMoedaCotacao(total) + " MT"
            );

    });


    // ==============================
    // PERCENTUAL DESCONTO
    // ==============================

    const descontoPercentualGeral =
        subtotalGeral > 0
            ? (
                descontoGeral /
                subtotalGeral
            ) * 100
            : 0;


    // ==============================
    // BASE GERAL
    // ==============================

    const baseGeral =
        subtotalGeral - descontoGeral;


    // ==============================
    // PERCENTUAL IMPOSTO
    // ==============================

    const impostoPercentualGeral =
        baseGeral > 0
            ? (
                impostoGeral /
                baseGeral
            ) * 100
            : 0;


    // ==================================================
    // ATUALIZAR TOTAIS VISÍVEIS
    // ==================================================

    $("#cotacaoSubtotal")
        .text(
            formatarMoedaCotacao(
                subtotalGeral
            ) + " MT"
        );


    $("#cotacaoDescontoValor")
        .text(
            formatarMoedaCotacao(
                descontoGeral
            ) + " MT"
        );


    $("#cotacaoDescontoPercentual")
        .text(
            descontoPercentualGeral.toFixed(2) + "%"
        );


    $("#cotacaoImpostoValor")
        .text(
            formatarMoedaCotacao(
                impostoGeral
            ) + " MT"
        );


    $("#cotacaoImpostoPercentual")
        .text(
            impostoPercentualGeral.toFixed(2) + "%"
        );


    $("#cotacaoTotal")
        .text(
            formatarMoedaCotacao(
                totalGeral
            ) + " MT"
        );


    // ==================================================
    // CAMPOS HIDDEN PARA ENVIO
    // ==================================================

    if ($("#cotacaoSubtotalHidden").length) {

        $("#cotacaoSubtotalHidden")
            .val(subtotalGeral.toFixed(2));

    }

    if ($("#cotacaoDescontoValorHidden").length) {

        $("#cotacaoDescontoValorHidden")
            .val(descontoGeral.toFixed(2));

    }

    if ($("#cotacaoDescontoPercentualHidden").length) {

        $("#cotacaoDescontoPercentualHidden")
            .val(
                descontoPercentualGeral.toFixed(2)
            );

    }

    if ($("#cotacaoImpostoValorHidden").length) {

        $("#cotacaoImpostoValorHidden")
            .val(impostoGeral.toFixed(2));

    }

    if ($("#cotacaoImpostoPercentualHidden").length) {

        $("#cotacaoImpostoPercentualHidden")
            .val(
                impostoPercentualGeral.toFixed(2)
            );

    }

    if ($("#cotacaoTotalHidden").length) {

        $("#cotacaoTotalHidden")
            .val(totalGeral.toFixed(2));

    }
}


// =========================================================
// PREPARAR ITENS PARA ENVIO
// =========================================================

function obterItensCotacao() {

    const itens =
        [];


    $("#cotacaoItensBody tr")
        .each(
            function (
                indice
            ) {

                const linha =
                    $(this);


                const nome =
                    linha
                        .find(
                            ".item-produto-nome"
                        )
                        .val()
                        .trim();


                if (
                    nome === ""
                ) {
                    return;
                }


                itens.push({

                    produto_id:
                        linha
                            .find(
                                ".item-produto-id"
                            )
                            .val()
                        || null,

                    produto_sku:
                        linha
                            .find(
                                ".item-produto-sku"
                            )
                            .val()
                        || null,

                    produto_nome:
                        nome,

                    produto_descricao:
                        "",

                    unidade:
                        linha
                            .find(
                                ".item-unidade"
                            )
                            .val()
                        || "UN",

                    quantidade:
                        linha
                            .find(
                                ".item-quantidade"
                            )
                            .val()
                        || 0,

                    preco_unitario:
                        linha
                            .find(
                                ".item-preco"
                            )
                            .val()
                        || 0,

                    desconto_percentual:
                        linha
                            .find(
                                ".item-desconto"
                            )
                            .val()
                        || 0,

                    imposto_percentual:
                        linha
                            .find(
                                ".item-imposto"
                            )
                            .val()
                        || 0,

                    ordem:
                        indice + 1
                });
            }
        );


    return itens;
}


// =========================================================
// GUARDAR
// =========================================================

async function guardarCotacao(
    evento
) {

    evento.preventDefault();


    recalcularCotacao();


    const formulario =
        evento.currentTarget;


    const botao =
        document.getElementById(
            "btnGuardarCotacao"
        );


    const itens =
        obterItensCotacao();


    if (
        itens.length === 0
    ) {

        mostrarMensagemCotacao(
            false,
            "Adicione pelo menos um item à cotação."
        );

        return;
    }


    const dados =
        new FormData(
            formulario
        );


    dados.append(
        "acao",
        "salvar"
    );


    dados.set(
        "itens",
        JSON.stringify(
            itens
        )
    );


    if (botao) {

        botao.disabled =
            true;


        botao.innerHTML =
            `
            <i class="fa fa-spinner fa-spin"></i>
            A guardar...
            `;
    }


    try {

        const resposta =
            await fetch(
                COTACAO_URL,
                {
                    method:
                        "POST",

                    body:
                        dados,

                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonCotacao(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
                || "Erro ao guardar cotação."
            );
        }


        $("#modalCotacao")
            .modal("hide");


        limparFormularioCotacao();


        if (
            tabelaCotacoes
        ) {

            tabelaCotacoes.ajax.reload(
                null,
                false
            );
        }


        mostrarMensagemCotacao(
            true,
            json.mensagem
        );

    } catch (erro) {

        console.error(
            "Erro ao guardar cotação:",
            erro
        );


        mostrarMensagemCotacao(
            false,
            erro.message
        );

    } finally {

        if (botao) {

            botao.disabled =
                false;


            botao.innerHTML =
                `
                <i class="fa fa-save"></i>
                Guardar
                `;
        }
    }
}


// =========================================================
// IMPRIMIR COTAÇÃO
// =========================================================

function imprimirCotacao(
    id
) {

    if (!id) {

        mostrarMensagemCotacao(
            false,
            "Cotação inválida."
        );

        return;
    }


    const url =
        COTACAO_URL
        + "?acao=imprimir&id="
        + encodeURIComponent(id);


    /*
     * Abre o PDF numa nova aba.
     *
     * O Dompdf envia o PDF diretamente.
     */

    const janela =
        window.open(
            url,
            "_blank"
        );


    if (!janela) {

        mostrarMensagemCotacao(
            false,
            "O navegador bloqueou a abertura da impressão. Permita pop-ups para este sistema."
        );
    }
}


// =========================================================
// ELIMINAR
// =========================================================

function abrirEliminarCotacao(
    id
) {

    $("#eliminarCotacaoId")
        .val(id);


    $("#modalEliminarCotacao")
        .modal("show");
}


async function eliminarCotacao(
    evento
) {

    evento.preventDefault();


    const formulario =
        evento.currentTarget;


    const dados =
        new FormData(
            formulario
        );


    dados.append(
        "acao",
        "eliminar"
    );


    try {

        const resposta =
            await fetch(
                COTACAO_URL,
                {
                    method:
                        "POST",

                    body:
                        dados,

                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonCotacao(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {

            throw new Error(
                json.mensagem
                || "Erro ao eliminar cotação."
            );
        }


        $("#modalEliminarCotacao")
            .modal("hide");


        if (
            tabelaCotacoes
        ) {

            tabelaCotacoes.ajax.reload(
                null,
                false
            );
        }


        mostrarMensagemCotacao(
            true,
            json.mensagem
        );

    } catch (erro) {

        console.error(
            "Erro ao eliminar cotação:",
            erro
        );


        mostrarMensagemCotacao(
            false,
            erro.message
        );
    }
}


// =========================================================
// LIMPAR FORMULÁRIO
// =========================================================

function limparFormularioCotacao() {

    const formulario =
        document.getElementById(
            "formCotacao"
        );


    if (formulario) {
        formulario.reset();
    }


    $("#cotacaoId")
        .val("");


    $("#cotacaoClienteId")
        .val("");


    $("#cotacaoClienteNome")
        .val("");


    $("#cotacaoClienteNuit")
        .val("");


    $("#cotacaoClienteTelefone")
        .val("");


    $("#cotacaoClienteEndereco")
        .val("");


    $("#cotacaoItensBody")
        .empty();


    $("#cotacaoProdutoPesquisa")
        .val("");


    $("#tituloModalCotacao")
        .text(
            "Nova Cotação"
        );


    esconderSugestoesClientes();

    esconderSugestoesProdutos();


    recalcularCotacao();
}


// =========================================================
// MENSAGEM
// =========================================================

function mostrarMensagemCotacao(
    sucesso,
    mensagem
) {

    if (
        typeof Swal !== "undefined"
    ) {

        Swal.fire({

            icon:
                sucesso
                    ? "success"
                    : "error",

            title:
                sucesso
                    ? "Sucesso!"
                    : "Erro!",

            text:
                mensagem
                || (
                    sucesso
                        ? "Operação realizada com sucesso."
                        : "Ocorreu um erro."
                ),

            confirmButtonText:
                "OK"
        });

        return;
    }


    alert(
        mensagem
        || "Operação concluída."
    );
}


// =========================================================
// JSON
// =========================================================

async function lerJsonCotacao(
    resposta
) {

    const texto =
        await resposta.text();


    try {

        return JSON.parse(
            texto
        );

    } catch (erro) {

        console.error(
            "Resposta inválida:",
            texto
        );


        throw new Error(
            "O servidor devolveu uma resposta inválida."
        );
    }
}


// =========================================================
// ESCAPAR HTML
// =========================================================

function escaparHtmlCotacao(
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


// =========================================================
// DATA
// =========================================================

function formatarDataCotacao(
    valor
) {

    if (!valor) {
        return "-";
    }


    const partes =
        String(valor)
            .split("-");


    if (
        partes.length === 3
    ) {

        return (
            partes[2]
            + "/"
            + partes[1]
            + "/"
            + partes[0]
        );
    }


    return valor;
}


// =========================================================
// MOEDA
// =========================================================

function formatarMoedaCotacao(
    valor
) {

    const numero =
        Number(
            String(
                valor
            )
                .replace(
                    ",",
                    "."
                )
        );


    if (
        Number.isNaN(
            numero
        )
    ) {

        return "0,00";
    }


    return numero.toLocaleString(
        "pt-MZ",
        {
            minimumFractionDigits: 2,

            maximumFractionDigits: 2
        }
    );
}