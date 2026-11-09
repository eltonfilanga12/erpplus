"use strict";

(function ($) {

    // ==================================================
    // URLs DO MÓDULO
    // ==================================================

    const COMPRA_MODULO_URL =
        "/erpplus/app/ajax/compra_ajax.php";

    const COMPRA_CENTRO_URL =
        "/erpplus/app/ajax/centro_custo_ajax.php";

    const COMPRA_FILIAL_URL =
        "/erpplus/app/ajax/filial_ajax.php";

    const COMPRA_FORMA_URL =
        "/erpplus/app/ajax/forma_pagamento_ajax.php";

    const COMPRA_CAIXA_URL =
        "/erpplus/app/ajax/caixa_ajax.php";

    const COMPRA_BANCO_URL =
        "/erpplus/app/ajax/banco_ajax.php";


    let tabelaCompras = null;

    let itensCompra = [];


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(async function () {

        inicializarTabelaCompras();

        configurarEventosCompra();

        await Promise.allSettled([
            carregarFornecedoresCompra(),
            carregarArmazensCompra(),
            carregarProdutosCompra(),
            carregarCentrosCompra(),
            carregarFiliaisCompra(),
            carregarFormasPagamentoCompra(),
            carregarStatusCompra(),

            carregarCaixasCompra(),
            carregarBancosCompra()
        ]);

        definirDatasPadraoCompra();

        atualizarCondicaoPagamento();

        atualizarTotaisCompra();

    });

    async function carregarCaixasCompra() {

        const select =
            $("#caixa_id_compra");

        const valorAtual =
            select.val();

        select.html(`
        <option value="">
            A carregar caixas...
        </option>
    `);

        try {

            const resposta =
                await fetch(
                    COMPRA_CAIXA_URL
                    + "?acao=listar",
                    {
                        credentials:
                            "same-origin"
                    }
                );

            const json =
                await resposta.json();

            if (!json.success) {

                throw new Error(
                    json.mensagem
                    || "Não foi possível carregar os caixas."
                );
            }

            select.html(`
            <option value="">
                Seleccione o caixa
            </option>
        `);

            const caixas =
                Array.isArray(json.data)
                    ? json.data
                    : [];

            caixas.forEach(
                function (caixa) {

                    const id =
                        Number(
                            caixa.id || 0
                        );

                    if (id <= 0) {
                        return;
                    }

                    const nome =
                        caixa.nome
                        || caixa.codigo
                        || ("Caixa #" + id);

                    const saldo =
                        Number(
                            caixa.saldo_atual
                            || 0
                        );

                    select.append(
                        $("<option>", {
                            value:
                                id,

                            text:
                                nome
                                + " — "
                                + formatarMoedaCompra(
                                    saldo
                                )
                        })
                    );
                }
            );

            if (valorAtual) {

                select.val(
                    valorAtual
                );
            }

        } catch (erro) {

            console.error(
                "[COMPRA][CAIXAS]",
                erro
            );

            select.html(`
            <option value="">
                Não foi possível carregar
            </option>
        `);
        }
    }

    async function carregarBancosCompra() {

        const select =
            $("#banco_id_compra");

        const valorAtual =
            select.val();

        select.html(`
        <option value="">
            A carregar bancos...
        </option>
    `);

        try {

            const resposta =
                await fetch(
                    COMPRA_BANCO_URL
                    + "?acao=listar",
                    {
                        credentials:
                            "same-origin"
                    }
                );

            const json =
                await resposta.json();

            if (!json.success) {

                throw new Error(
                    json.mensagem
                    || "Não foi possível carregar os bancos."
                );
            }

            select.html(`
            <option value="">
                Seleccione a conta bancária
            </option>
        `);

            const bancos =
                Array.isArray(json.data)
                    ? json.data
                    : [];

            bancos.forEach(
                function (banco) {

                    const id =
                        Number(
                            banco.id || 0
                        );

                    if (id <= 0) {
                        return;
                    }

                    const nome =
                        banco.nome
                        || banco.nome_banco
                        || banco.banco
                        || banco.numero_conta
                        || ("Banco #" + id);

                    const saldo =
                        Number(
                            banco.saldo_atual
                            || 0
                        );

                    select.append(
                        $("<option>", {
                            value:
                                id,

                            text:
                                nome
                                + " — "
                                + formatarMoedaCompra(
                                    saldo
                                )
                        })
                    );
                }
            );

            if (valorAtual) {

                select.val(
                    valorAtual
                );
            }

        } catch (erro) {

            console.error(
                "[COMPRA][BANCOS]",
                erro
            );

            select.html(`
            <option value="">
                Não foi possível carregar
            </option>
        `);
        }
    }


    // ==================================================
    // CANCELAR / ESTORNAR COMPRA
    // ==================================================

    async function cancelarCompra(
        id
    ) {

        if (
            !id
            || id <= 0
        ) {
            mensagemErroCompra(
                "Compra inválida."
            );

            return;
        }


        const resultado =
            await Swal.fire({

                icon:
                    "warning",

                title:
                    "Cancelar esta compra?",

                html: `
                <div class="text-left">

                    <p>
                        Esta operação fará o
                        <strong>estorno da compra</strong>.
                    </p>

                    <p class="mb-2">
                        O sistema irá:
                    </p>

                    <ul>
                        <li>
                            retirar do stock os produtos
                            que entraram pela compra;
                        </li>

                        <li>
                            cancelar a conta a pagar;
                        </li>

                        <li>
                            criar o lançamento
                            contabilístico inverso;
                        </li>

                        <li>
                            marcar a compra como
                            cancelada.
                        </li>
                    </ul>

                </div>
            `,

                input:
                    "textarea",

                inputLabel:
                    "Motivo do cancelamento",

                inputPlaceholder:
                    "Ex.: Documento emitido incorretamente pelo fornecedor...",

                inputAttributes: {
                    maxlength:
                        "500"
                },

                showCancelButton:
                    true,

                confirmButtonText:
                    '<i class="fa fa-undo"></i> Confirmar estorno',

                cancelButtonText:
                    "Voltar",

                confirmButtonColor:
                    "#d39e00",

                reverseButtons:
                    true,

                focusCancel:
                    true,

                preConfirm:
                    function (motivo) {

                        motivo =
                            String(
                                motivo || ""
                            )
                                .trim();

                        if (
                            motivo.length < 5
                        ) {

                            Swal.showValidationMessage(
                                "Informe um motivo válido com pelo menos 5 caracteres."
                            );

                            return false;
                        }

                        return motivo;
                    }
            });


        if (!resultado.isConfirmed) {
            return;
        }


        const motivo =
            String(
                resultado.value || ""
            )
                .trim();


        Swal.fire({
            title:
                "A processar estorno...",

            text:
                "Não feche esta janela.",

            allowOutsideClick:
                false,

            allowEscapeKey:
                false,

            didOpen:
                function () {
                    Swal.showLoading();
                }
        });


        try {

            const dados =
                new FormData();


            dados.append(
                "acao",
                "cancelar"
            );


            dados.append(
                "id",
                String(id)
            );


            dados.append(
                "motivo",
                motivo
            );


            const resposta =
                await requisicaoCompra(
                    COMPRA_MODULO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );


            recarregarTabelaCompras();


            const movimentoId =
                Number(
                    resposta.data
                        ?.movimento_estorno_id
                    || 0
                );


            const lancamentoId =
                Number(
                    resposta.data
                        ?.lancamento_estorno_id
                    || 0
                );


            await Swal.fire({

                icon:
                    "success",

                title:
                    "Compra cancelada",

                html: `
                <p>
                    ${escaparHtml(
                    resposta.mensagem
                    || "Compra cancelada e estornada com sucesso."
                )
                    }
                </p>

                ${movimentoId > 0
                        ? `
                            <div class="mt-2">
                                Movimento de stock:
                                <strong>
                                    #${movimentoId}
                                </strong>
                            </div>
                        `
                        : ""
                    }

                ${lancamentoId > 0
                        ? `
                            <div class="mt-1">
                                Lançamento contabilístico:
                                <strong>
                                    #${lancamentoId}
                                </strong>
                            </div>
                        `
                        : ""
                    }
            `,

                confirmButtonText:
                    "Fechar"
            });


        } catch (erro) {

            Swal.close();

            mensagemErroCompra(
                erro.message
                || "Não foi possível cancelar a compra."
            );
        }
    }

    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosCompra() {

        $("#formCompra").on(
            "submit",
            guardarCompra
        );


        $("#formEliminarCompra").on(
            "submit",
            eliminarCompra
        );


        $("#adicionarProduto").on(
            "click",
            adicionarProdutoCompra
        );


        $("#condicao_pagamento").on(
            "change",
            atualizarCondicaoPagamento
        );

        $("#origem_pagamento").on(
            "change",
            atualizarOrigemPagamentoCompra
        );


        $("#filial_id_compra").on(
            "change",
            function () {

                carregarCentrosCompra(
                    $(this).val()
                );

            }
        );


        $("#desconto").on(
            "input",
            atualizarTotaisCompra
        );


        $("#compra_imposto_habilitado").on(
            "change",
            atualizarTotaisCompra
        );

        $("#produto_id").on(
            "change",
            function () {

                const opcao =
                    $(this).find(
                        "option:selected"
                    );

                const preco =
                    Number(
                        opcao.data("preco")
                        || 0
                    );

                $("#preco_unitario_item")
                    .val(
                        preco.toFixed(2)
                    );

            }
        );


        $("#modalCompra").on(
            "hidden.bs.modal",
            function () {

                limparFormularioCompra();

            }
        );


        $(document).on(
            "click",
            ".btn-editar-compra",
            function () {

                editarCompra(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-eliminar-compra",
            function () {

                abrirEliminarCompra(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );

        $(document).on(
            "click",
            ".btn-cancelar-compra",
            function () {

                cancelarCompra(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );

        $(document).on(
            "click",
            ".btn-remover-item-compra",
            function () {

                removerItemCompra(
                    Number(
                        $(this).data("index")
                    )
                );

            }
        );

    }


    // ==================================================
    // REQUISIÇÃO PADRÃO
    // ==================================================

    async function requisicaoCompra(
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
                "Resposta inválida do servidor:",
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
                || "Não foi possível concluir a operação."
            );

        }

        return json;
    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function inicializarTabelaCompras() {

        if (
            $.fn.DataTable.isDataTable(
                "#comprasTable"
            )
        ) {

            $("#comprasTable")
                .DataTable()
                .destroy();

        }

        tabelaCompras =
            $("#comprasTable")
                .DataTable({

                    ajax: {

                        url:
                            COMPRA_MODULO_URL,

                        data: {
                            acao:
                                "listar"
                        },

                        dataSrc: function (
                            json
                        ) {

                            if (
                                json.success === false
                            ) {

                                mensagemErroCompra(
                                    json.mensagem
                                );

                                return [];

                            }

                            return json.data || [];

                        },

                        error: function (
                            xhr
                        ) {

                            console.error(
                                "Erro ao listar compras:",
                                xhr.responseText
                            );

                        }

                    },

                    columns: [

                        {
                            data: null,

                            render: function (
                                data,
                                type,
                                row,
                                meta
                            ) {

                                const numero =
                                    meta.row
                                    + meta.settings._iDisplayStart
                                    + 1;

                                return String(
                                    numero
                                ).padStart(
                                    4,
                                    "0"
                                );
                            }
                        },

                        {
                            data:
                                "fornecedor_nome",

                            defaultContent:
                                "-"
                        },

                        {
                            data:
                                "centro_custo_nome",

                            defaultContent:
                                "Sem centro de custo",

                            render: function (
                                valor,
                                tipo,
                                row
                            ) {

                                const codigo =
                                    row.centro_custo_codigo
                                        ? `
                                            <small class="text-muted">
                                                ${escaparHtmlCompra(
                                            row.centro_custo_codigo
                                        )}
                                            </small>
                                            <br>
                                        `
                                        : "";

                                return `
                                    ${codigo}
                                    ${escaparHtmlCompra(
                                    valor
                                    || "Sem centro de custo"
                                )}
                                `;

                            }
                        },

                        {
                            data:
                                "numero_documento",

                            defaultContent:
                                "-"
                        },

                        {
                            data:
                                "data_compra",

                            render:
                                formatarDataCompra
                        },

                        {
                            data:
                                "data_vencimento",

                            render: function (
                                data,
                                tipo,
                                row
                            ) {

                                if (!data) {
                                    return "-";
                                }

                                const vencida =
                                    String(
                                        row.condicao_pagamento
                                        || ""
                                    ).toUpperCase()
                                    === "APRAZO"
                                    && data
                                    < dataHojeCompra();

                                const texto =
                                    formatarDataCompra(
                                        data
                                    );

                                if (vencida) {

                                    return `
                                        <span class="text-danger font-weight-bold">
                                            ${texto}
                                        </span>
                                    `;

                                }

                                return texto;

                            }
                        },

                        {
                            data:
                                "armazem_nome",

                            defaultContent:
                                "-"
                        },

                        {
                            data:
                                "total",

                            className:
                                "text-right",

                            render:
                                formatarMoedaCompra
                        },

                        {
                            data:
                                "condicao_pagamento",

                            render:
                                renderCondicaoPagamento
                        },

                        {
                            data:
                                "status_nome",

                            render:
                                renderStatusCompra
                        },

                        {
                            data:
                                "criado_em",

                            render:
                                formatarDataHoraCompra
                        },

                        {
                            data:
                                null,

                            className:
                                "text-right",

                            orderable:
                                false,

                            searchable:
                                false,

                            render: function (row) {

                                const status =
                                    String(
                                        row.status_nome || ""
                                    )
                                        .trim()
                                        .toLowerCase();

                                const confirmada =
                                    status === "confirmada";

                                const cancelada =
                                    status === "cancelada";


                                return `
        <button
            type="button"
            class="btn btn-sm btn-info btn-editar-compra"
            data-id="${row.id}"
            title="${confirmada || cancelada
                                        ? "Ver compra"
                                        : "Editar compra"
                                    }"
        >
            <i class="fa ${confirmada || cancelada
                                        ? "fa-eye"
                                        : "fa-edit"
                                    }"></i>
        </button>


        ${confirmada
                                        ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-warning btn-cancelar-compra"
                        data-id="${row.id}"
                        title="Cancelar / Estornar compra"
                    >
                        <i class="fa fa-undo"></i>
                    </button>
                `
                                        : ""
                                    }


        ${!confirmada && !cancelada
                                        ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-danger btn-eliminar-compra"
                        data-id="${row.id}"
                        title="Eliminar"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                `
                                        : ""
                                    }
    `;
                            }
                        }

                    ],

                    order: [
                        [0, "desc"]
                    ],

                    pageLength:
                        10,

                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
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
                                "Lista de Compras",

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
                                "Lista de Compras",

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
                                "Lista de Compras",

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
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
    // RECARREGAR TABELA
    // ==================================================

    function recarregarTabelaCompras() {

        if (tabelaCompras) {

            tabelaCompras.ajax.reload(
                null,
                false
            );

        }
    }


    // ==================================================
    // CARREGAR FORNECEDORES
    // ==================================================

    async function carregarFornecedoresCompra() {

        try {

            const resposta =
                await requisicaoCompra(
                    COMPRA_MODULO_URL
                    + "?acao=fornecedores"
                );

            const select =
                $("#fornecedor_id_compra");

            const valorAtual =
                select.val();

            select.html(`
            <option value="">
                Seleccione o fornecedor
            </option>
        `);

            const fornecedores =
                Array.isArray(
                    resposta.data
                )
                    ? resposta.data
                    : [];

            if (
                fornecedores.length === 0
            ) {

                select.append(`
                <option
                    value=""
                    disabled
                >
                    Nenhum fornecedor encontrado
                </option>
            `);

                return;
            }

            fornecedores.forEach(
                function (fornecedor) {

                    select.append(`
                    <option value="${fornecedor.id}">
                        ${escaparHtmlCompra(
                        fornecedor.nome
                        || "Fornecedor"
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
                "Erro ao carregar fornecedores:",
                erro
            );

            mensagemErroCompra(
                "Não foi possível carregar os fornecedores: "
                + erro.message
            );
        }
    }

    // ==================================================
    // CARREGAR ARMAZÉNS
    // ==================================================

    async function carregarArmazensCompra() {

        try {

            const resposta =
                await requisicaoCompra(
                    COMPRA_MODULO_URL
                    + "?acao=armazens"
                );

            const select =
                $("#armazem_id");

            const valorAtual =
                select.val();

            select.html(`
                <option value="">
                    Seleccione
                </option>
            `);

            (resposta.data || [])
                .forEach(
                    function (armazem) {

                        select.append(`
                            <option value="${armazem.id}">
                                ${escaparHtmlCompra(
                            armazem.nome
                            || "Armazém"
                        )}
                            </option>
                        `);

                    }
                );

            if (valorAtual) {
                select.val(valorAtual);
            }

        } catch (erro) {

            console.error(
                "Erro ao carregar armazéns:",
                erro
            );

        }
    }


    // ==================================================
    // CARREGAR PRODUTOS
    // ==================================================

    async function carregarProdutosCompra() {

        try {

            const resposta =
                await requisicaoCompra(
                    COMPRA_MODULO_URL
                    + "?acao=produtos"
                );


            const select =
                $("#produto_id");

            /*
            |--------------------------------------------------------------------------
            | Remover eventos Select2 anteriores
            |--------------------------------------------------------------------------
            */

            select.off(
                ".produtoCompra"
            );
            /*
             * Destruir Select2 anterior,
             * caso já esteja inicializado.
             */
            if (
                select.hasClass(
                    "select2-hidden-accessible"
                )
            ) {

                select.select2(
                    "destroy"
                );
            }


            select.html(`
            <option value="">
                Seleccione o produto
            </option>
        `);


            (resposta.data || [])
                .forEach(
                    function (produto) {

                        const sku =
                            String(
                                produto.sku
                                || ""
                            ).trim();


                        const nome =
                            String(
                                produto.nome
                                || "Produto"
                            ).trim();


                        /*
                         * O texto será pesquisável
                         * tanto pelo SKU como pelo nome.
                         */
                        const texto =
                            sku
                                ? sku + " — " + nome
                                : nome;


                        const option =
                            new Option(
                                texto,
                                produto.id,
                                false,
                                false
                            );


                        $(option)
                            .attr(
                                "data-preco",
                                Number(
                                    produto.preco_custo
                                    || 0
                                )
                            );


                        $(option)
                            .attr(
                                "data-sku",
                                sku
                            );


                        $(option)
                            .attr(
                                "data-nome",
                                nome
                            );


                        select.append(
                            option
                        );
                    }
                );


            /*
             * Tornar pesquisável.
             */
            select.select2({

                width:
                    "100%",

                placeholder:
                    "Pesquise pelo nome ou SKU",

                allowClear:
                    true,

                dropdownParent:
                    $("#produtoSelectContainer"),

                dropdownCssClass:
                    "select2-produto-compra",

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
            select.on(
                "select2:select.produtoCompra",
                function () {

                    /*
                     * Mantemos somente a seleção.
                     * Não tentamos reabrir ou reposicionar
                     * o dropdown manualmente.
                     */

                    $(this)
                        .select2(
                            "close"
                        );
                }
            );

        } catch (erro) {

            console.error(
                "Erro ao carregar produtos:",
                erro
            );
        }
    }


    // ==================================================
    // CARREGAR CENTROS
    // ==================================================

    async function carregarCentrosCompra(
        filialId = null
    ) {
        try {

            const resposta =
                await requisicaoCompra(
                    COMPRA_CENTRO_URL
                    + "?acao=listar_select"
                );

            const select =
                $("#centro_custo_id");

            const valorAtual =
                select.val();

            select.html(`
                <option value="">
                    Seleccione
                </option>
            `);

            (resposta.data || [])
                .forEach(
                    function (centro) {

                        if (
                            filialId
                            && centro.filial_id
                            && String(
                                centro.filial_id
                            ) !== String(
                                filialId
                            )
                        ) {
                            return;
                        }

                        if (
                            centro.tipo
                            && ![
                                "CUSTO",
                                "MISTO"
                            ].includes(
                                String(
                                    centro.tipo
                                ).toUpperCase()
                            )
                        ) {
                            return;
                        }

                        select.append(`
                            <option value="${centro.id}">
                                ${escaparHtmlCompra(
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
    // CARREGAR FILIAIS
    // ==================================================

    async function carregarFiliaisCompra() {

        try {

            const resposta =
                await requisicaoCompra(
                    COMPRA_MODULO_URL
                    + "?acao=filiais"
                );

            const select =
                $("#filial_id_compra");

            const valorAtual =
                select.val();

            select.html(`
            <option value="">
                Sede Principal / Sem filial
            </option>
        `);

            const filiais =
                Array.isArray(
                    resposta.data
                )
                    ? resposta.data
                    : [];

            filiais.forEach(
                function (filial) {

                    const codigo =
                        filial.codigo
                            ? filial.codigo + " - "
                            : "";

                    select.append(`
                    <option value="${filial.id}">
                        ${escaparHtmlCompra(
                        codigo
                        + (
                            filial.nome
                            || "Filial"
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
                "Erro ao carregar filiais:",
                erro
            );

            mensagemErroCompra(
                "Não foi possível carregar as filiais: "
                + erro.message
            );
        }
    }


    // ==================================================
    // FORMAS DE PAGAMENTO
    // ==================================================

    async function carregarFormasPagamentoCompra() {

        try {

            const resposta =
                await requisicaoCompra(
                    COMPRA_FORMA_URL
                    + "?acao=listar"
                );

            const select =
                $("#forma_pagamento_id");

            const valorAtual =
                select.val();

            select.html(`
                <option value="">
                    Seleccione
                </option>
            `);

            (resposta.data || [])
                .forEach(
                    function (forma) {

                        select.append(`
                            <option value="${forma.id}">
                                ${escaparHtmlCompra(
                            forma.nome
                            || "Forma de pagamento"
                        )}
                            </option>
                        `);

                    }
                );

            if (valorAtual) {
                select.val(valorAtual);
            }

        } catch (erro) {

            console.error(
                "Erro ao carregar formas de pagamento:",
                erro
            );

        }
    }


    // ==================================================
    // STATUS
    // ==================================================

    async function carregarStatusCompra() {

        try {

            const resposta =
                await requisicaoCompra(
                    COMPRA_MODULO_URL
                    + "?acao=status"
                );

            const select =
                $("#compra_status_id");

            const valorAtual =
                select.val();

            select.html(`
                <option value="">
                    Seleccione
                </option>
            `);

            (resposta.data || [])
                .forEach(
                    function (status) {

                        select.append(`
                            <option value="${status.id}">
                                ${escaparHtmlCompra(
                            status.nome
                        )}
                            </option>
                        `);

                    }
                );

            if (valorAtual) {

                select.val(
                    valorAtual
                );

            } else {

                selecionarStatusRascunho();

            }

        } catch (erro) {

            mensagemErroCompra(
                erro.message
            );

        }
    }


    function selecionarStatusRascunho() {

        const opcao =
            $("#compra_status_id option")
                .filter(function () {

                    return String(
                        $(this).text()
                    )
                        .trim()
                        .toLowerCase()
                        === "rascunho";

                })
                .first();

        if (opcao.length) {

            $("#compra_status_id")
                .val(
                    opcao.val()
                );

        }
    }


    // ==================================================
    // CONDIÇÃO DE PAGAMENTO
    // ==================================================

    function atualizarCondicaoPagamento() {

        const condicao =
            String(
                $("#condicao_pagamento")
                    .val()
                || "APRAZO"
            )
                .trim()
                .toUpperCase();


        const grupoVencimento =
            $("#grupoDataVencimento");

        const dataVencimento =
            $("#data_vencimento");


        const grupoOrigem =
            $("#grupoOrigemPagamentoCompra");

        const origem =
            $("#origem_pagamento");


        const grupoCaixa =
            $("#grupoCaixaPagamentoCompra");

        const caixa =
            $("#caixa_id_compra");


        const grupoBanco =
            $("#grupoBancoPagamentoCompra");

        const banco =
            $("#banco_id_compra");


        /*
        |--------------------------------------------------------------------------
        | COMPRA A PRAZO
        |--------------------------------------------------------------------------
        */

        if (condicao === "APRAZO") {

            grupoVencimento.show();

            dataVencimento
                .prop(
                    "required",
                    true
                )
                .prop(
                    "disabled",
                    false
                );


            if (!dataVencimento.val()) {

                dataVencimento.val(
                    $("#data_compra").val()
                    || dataHojeCompra()
                );
            }


            /*
             * Não existe saída imediata
             * de Caixa/Banco.
             */

            grupoOrigem.hide();

            grupoCaixa.hide();

            grupoBanco.hide();


            origem
                .val("")
                .prop(
                    "required",
                    false
                )
                .prop(
                    "disabled",
                    true
                );


            caixa
                .val("")
                .prop(
                    "required",
                    false
                )
                .prop(
                    "disabled",
                    true
                );


            banco
                .val("")
                .prop(
                    "required",
                    false
                )
                .prop(
                    "disabled",
                    true
                );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | COMPRA À VISTA
        |--------------------------------------------------------------------------
        */

        grupoVencimento.hide();

        dataVencimento
            .prop(
                "required",
                false
            )
            .prop(
                "disabled",
                true
            );


        grupoOrigem.show();

        origem
            .prop(
                "disabled",
                false
            )
            .prop(
                "required",
                true
            );


        atualizarOrigemPagamentoCompra();
    }

    function atualizarOrigemPagamentoCompra() {

        const origem =
            String(
                $("#origem_pagamento")
                    .val()
                || ""
            )
                .trim()
                .toUpperCase();


        const grupoCaixa =
            $("#grupoCaixaPagamentoCompra");

        const caixa =
            $("#caixa_id_compra");


        const grupoBanco =
            $("#grupoBancoPagamentoCompra");

        const banco =
            $("#banco_id_compra");


        /*
        |--------------------------------------------------------------------------
        | CAIXA
        |--------------------------------------------------------------------------
        */

        if (origem === "CAIXA") {

            grupoCaixa.show();

            grupoBanco.hide();


            caixa
                .prop(
                    "disabled",
                    false
                )
                .prop(
                    "required",
                    true
                );


            banco
                .val("")
                .prop(
                    "disabled",
                    true
                )
                .prop(
                    "required",
                    false
                );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | BANCO
        |--------------------------------------------------------------------------
        */

        if (origem === "BANCO") {

            grupoBanco.show();

            grupoCaixa.hide();


            banco
                .prop(
                    "disabled",
                    false
                )
                .prop(
                    "required",
                    true
                );


            caixa
                .val("")
                .prop(
                    "disabled",
                    true
                )
                .prop(
                    "required",
                    false
                );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | NENHUMA ORIGEM
        |--------------------------------------------------------------------------
        */

        grupoCaixa.hide();

        grupoBanco.hide();


        caixa
            .val("")
            .prop(
                "disabled",
                true
            )
            .prop(
                "required",
                false
            );


        banco
            .val("")
            .prop(
                "disabled",
                true
            )
            .prop(
                "required",
                false
            );
    }


    // ==================================================
    // ADICIONAR PRODUTO
    // ==================================================

    function adicionarProdutoCompra() {

        const produto =
            $("#produto_id option:selected");

        const produtoId =
            Number(
                produto.val()
                || 0
            );

        const quantidade =
            Number(
                $("#quantidade").val()
                || 0
            );

        const preco =
            Number(
                $("#preco_unitario_item").val()
                || produto.data("preco")
                || 0
            );

        if (produtoId <= 0) {

            mensagemAvisoCompra(
                "Seleccione um produto."
            );

            return;
        }

        if (quantidade <= 0) {

            mensagemAvisoCompra(
                "A quantidade deve ser superior a zero."
            );

            return;
        }

        if (preco < 0) {

            mensagemAvisoCompra(
                "O preço unitário é inválido."
            );

            return;
        }

        const existente =
            itensCompra.find(
                function (item) {

                    return Number(
                        item.produto_id
                    ) === produtoId;

                }
            );

        if (existente) {

            existente.quantidade =
                Number(
                    existente.quantidade
                )
                + quantidade;

            existente.preco_unitario =
                preco;

            existente.subtotal =
                calcularSubtotalItem(
                    existente
                );

        } else {

            const item = {

                produto_id:
                    produtoId,

                nome:
                    String(
                        produto.text()
                    ).trim(),

                quantidade:
                    quantidade,

                preco_unitario:
                    preco,

                desconto:
                    0,

                imposto:
                    0,

                subtotal:
                    0

            };

            item.subtotal =
                calcularSubtotalItem(
                    item
                );

            itensCompra.push(
                item
            );

        }

        mostrarItensCompra();

        $("#produto_id")
            .val("")
            .trigger(
                "change.select2"
            );

        $("#quantidade")
            .val(1);

        $("#preco_unitario_item")
            .val("");

    }


    // ==================================================
    // CALCULAR SUBTOTAL DO ITEM
    // ==================================================

    function calcularSubtotalItem(
        item
    ) {
        const bruto =
            Number(
                item.quantidade || 0
            )
            * Number(
                item.preco_unitario || 0
            );

        return Math.max(
            0,
            bruto
            - Number(
                item.desconto || 0
            )
            + Number(
                item.imposto || 0
            )
        );
    }


    // ==================================================
    // MOSTRAR ITENS
    // ==================================================

    function mostrarItensCompra() {

        let html = "";

        itensCompra.forEach(
            function (
                item,
                index
            ) {

                html += `
                    <tr>

                        <td>
                            ${escaparHtmlCompra(
                    item.nome
                )}
                        </td>

                        <td>
                            ${formatarNumeroCompra(
                    item.quantidade
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaCompra(
                    item.preco_unitario
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaCompra(
                    item.subtotal
                )}
                        </td>

                        <td class="text-center">

                            <button
                                type="button"
                                class="btn btn-danger btn-sm btn-remover-item-compra"
                                data-index="${index}">

                                <i class="fa fa-trash"></i>

                            </button>

                        </td>

                    </tr>
                `;

            }
        );

        if (itensCompra.length === 0) {

            html = `
                <tr>

                    <td
                        colspan="5"
                        class="text-center text-muted">

                        Nenhum produto adicionado.

                    </td>

                </tr>
            `;

        }

        $("#listaItens")
            .html(html);

        atualizarTotaisCompra();
    }


    // ==================================================
    // REMOVER ITEM
    // ==================================================

    function removerItemCompra(
        index
    ) {
        if (
            index < 0
            || index >= itensCompra.length
        ) {
            return;
        }

        itensCompra.splice(
            index,
            1
        );

        mostrarItensCompra();
    }


    // ==================================================
    // ATUALIZAR TOTAIS
    // ==================================================

    function atualizarTotaisCompra() {

        const subtotal =
            itensCompra.reduce(
                function (
                    total,
                    item
                ) {

                    return total
                        + Number(
                            item.subtotal
                            || 0
                        );
                },
                0
            );


        let desconto =
            Number(
                $("#desconto").val()
                || 0
            );


        if (
            !Number.isFinite(desconto)
            || desconto < 0
        ) {
            desconto = 0;
        }


        /*
        |--------------------------------------------------------------------------
        | O desconto nunca pode ultrapassar o subtotal
        |--------------------------------------------------------------------------
        */

        desconto =
            Math.min(
                desconto,
                subtotal
            );


        const baseTributavel =
            Math.max(
                0,
                subtotal - desconto
            );


        const impostoHabilitado =
            $("#compra_imposto_habilitado")
                .is(":checked");


        const taxaImposto =
            impostoHabilitado
                ? 16
                : 0;


        const imposto =
            impostoHabilitado
                ? (
                    baseTributavel
                    * taxaImposto
                    / 100
                )
                : 0;


        const total =
            baseTributavel
            + imposto;


        $("#subtotal")
            .val(
                subtotal.toFixed(2)
            );


        $("#desconto")
            .val(
                desconto.toFixed(2)
            );


        $("#compra_taxa_imposto")
            .val(
                taxaImposto.toFixed(2)
            );


        $("#imposto")
            .val(
                imposto.toFixed(2)
            );


        $("#total")
            .val(
                total.toFixed(2)
            );
    }

    // ==================================================
    // GUARDAR COMPRA
    // ==================================================

    async function guardarCompra(
        evento
    ) {
        evento.preventDefault();

        if (itensCompra.length === 0) {

            mensagemAvisoCompra(
                "Adicione pelo menos um produto à compra."
            );

            return;
        }

        atualizarTotaisCompra();

        const condicao =
            String(
                $("#condicao_pagamento")
                    .val()
                || ""
            )
                .trim()
                .toUpperCase();


        if (condicao === "AVISTA") {

            const origem =
                String(
                    $("#origem_pagamento")
                        .val()
                    || ""
                )
                    .trim()
                    .toUpperCase();


            if (
                origem !== "CAIXA"
                && origem !== "BANCO"
            ) {

                mensagemAvisoCompra(
                    "Seleccione se o pagamento sairá do Caixa ou do Banco."
                );

                return;
            }


            if (
                origem === "CAIXA"
                && !$("#caixa_id_compra").val()
            ) {

                mensagemAvisoCompra(
                    "Seleccione o caixa utilizado no pagamento."
                );

                return;
            }


            if (
                origem === "BANCO"
                && !$("#banco_id_compra").val()
            ) {

                mensagemAvisoCompra(
                    "Seleccione a conta bancária utilizada no pagamento."
                );

                return;
            }
        }

        const formulario =
            evento.currentTarget;

        const botao =
            $(formulario)
                .find(
                    'button[type="submit"]'
                );

        bloquearBotaoCompra(
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
                "salvar"
            );

            dados.append(
                "itens",
                JSON.stringify(
                    itensCompra
                )
            );

            const resposta =
                await requisicaoCompra(
                    COMPRA_MODULO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalCompra")
                .modal("hide");

            limparFormularioCompra();

            recarregarTabelaCompras();

            await Swal.fire({

                icon:
                    "success",

                title:
                    "Compra guardada",

                text:
                    resposta.mensagem
                    || "A compra foi guardada com sucesso.",

                confirmButtonText:
                    "Fechar"

            });

        } catch (erro) {

            mensagemErroCompra(
                erro.message
            );

        } finally {

            restaurarBotaoCompra(
                botao,
                `
                    <i class="fa fa-save"></i>
                    Guardar
                `
            );

        }
    }


    // ==================================================
    // EDITAR COMPRA
    // ==================================================

    async function editarCompra(
        id
    ) {
        try {

            const resposta =
                await requisicaoCompra(
                    COMPRA_MODULO_URL
                    + "?acao=buscar&id="
                    + encodeURIComponent(id)
                );

            const compra =
                resposta.data.compra;

            const itens =
                resposta.data.itens || [];

            if (!compra) {

                throw new Error(
                    "Compra não encontrada."
                );

            }

            await Promise.allSettled([
                carregarFornecedoresCompra(),
                carregarArmazensCompra(),
                carregarProdutosCompra(),
                carregarFiliaisCompra(),
                carregarCentrosCompra(
                    compra.filial_id
                ),
                carregarFormasPagamentoCompra(),
                carregarStatusCompra()
            ]);

            $("#compraId")
                .val(
                    compra.id
                );

            $("#fornecedor_id_compra")
                .val(
                    compra.fornecedor_id
                    || ""
                );

            $("#armazem_id")
                .val(
                    compra.armazem_id
                    || ""
                );

            $("#filial_id_compra")
                .val(
                    compra.filial_id
                    || ""
                );

            $("#centro_custo_id")
                .val(
                    compra.centro_custo_id
                    || ""
                );

            $("#forma_pagamento_id")
                .val(
                    compra.forma_pagamento_id
                    || ""
                );

            $("#condicao_pagamento")
                .val(
                    compra.condicao_pagamento
                    || "APRAZO"
                );

            $("#numero_documento")
                .val(
                    compra.numero_documento
                    || ""
                );

            $("#data_compra")
                .val(
                    compra.data_compra
                    || ""
                );

            $("#data_vencimento")
                .val(
                    compra.data_vencimento
                    || ""
                );

            $("#compra_status_id")
                .val(
                    compra.status_id
                    || ""
                );

            $("#observacoes")
                .val(
                    compra.observacoes
                    || ""
                );

            $("#desconto")
                .val(
                    compra.desconto
                    || "0.00"
                );

            $("#compra_imposto_habilitado")
                .prop(
                    "checked",
                    Number(
                        compra.imposto_habilitado
                        ?? 1
                    ) === 1
                );


            $("#compra_taxa_imposto")
                .val(
                    Number(
                        compra.taxa_imposto
                        ?? 16
                    ).toFixed(2)
                );

            $("#imposto")
                .val(
                    compra.imposto
                    || "0.00"
                );

            itensCompra =
                itens.map(
                    function (item) {

                        return {

                            produto_id:
                                Number(
                                    item.produto_id
                                ),

                            nome:
                                item.produto_nome,

                            quantidade:
                                Number(
                                    item.quantidade
                                ),

                            preco_unitario:
                                Number(
                                    item.preco_unitario
                                ),

                            desconto:
                                Number(
                                    item.desconto || 0
                                ),

                            imposto:
                                Number(
                                    item.imposto || 0
                                ),

                            subtotal:
                                Number(
                                    item.subtotal || 0
                                )

                        };

                    }
                );

            mostrarItensCompra();

            atualizarCondicaoPagamento();

            const confirmada =
                String(
                    compra.status_nome
                    || ""
                )
                    .trim()
                    .toLowerCase()
                === "confirmada";

            definirModoSomenteLeituraCompra(
                confirmada
            );

            $("#modalCompra")
                .modal("show");

        } catch (erro) {

            mensagemErroCompra(
                erro.message
            );

        }
    }


    // ==================================================
    // SOMENTE LEITURA
    // ==================================================

    function definirModoSomenteLeituraCompra(
        somenteLeitura
    ) {
        const formulario =
            $("#formCompra");

        formulario
            .find(
                "input:not([type='hidden']), select, textarea"
            )
            .prop(
                "disabled",
                somenteLeitura
            );

        $("#adicionarProduto")
            .prop(
                "disabled",
                somenteLeitura
            );

        $(".btn-remover-item-compra")
            .prop(
                "disabled",
                somenteLeitura
            );

        formulario
            .find(
                'button[type="submit"]'
            )
            .toggle(
                !somenteLeitura
            );

        $(".modal-title")
            .first()
            .text(
                somenteLeitura
                    ? "Detalhes da Compra"
                    : "Editar Compra"
            );
    }


    // ==================================================
    // ELIMINAR COMPRA
    // ==================================================

    function abrirEliminarCompra(
        id
    ) {
        $("#eliminarCompraId")
            .val(id);

        $("#modalEliminarCompra")
            .modal("show");
    }


    async function eliminarCompra(
        evento
    ) {
        evento.preventDefault();

        const botao =
            $(evento.currentTarget)
                .find(
                    'button[type="submit"]'
                );

        bloquearBotaoCompra(
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
                await requisicaoCompra(
                    COMPRA_MODULO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalEliminarCompra")
                .modal("hide");

            recarregarTabelaCompras();

            toastCompra(
                resposta.mensagem
                || "Compra eliminada com sucesso."
            );

        } catch (erro) {

            mensagemErroCompra(
                erro.message
            );

        } finally {

            restaurarBotaoCompra(
                botao,
                `
                    <i class="fa fa-trash"></i>
                    Eliminar
                `
            );

        }
    }


    // ==================================================
    // LIMPAR FORMULÁRIO
    // ==================================================

    function limparFormularioCompra() {

        const formulario =
            document.getElementById(
                "formCompra"
            );

        if (formulario) {
            formulario.reset();
        }

        definirModoSomenteLeituraCompra(
            false
        );

        $("#compraId")
            .val("");

        $("#desconto")
            .val("0.00");

        $("#compra_imposto_habilitado")
            .prop(
                "checked",
                true
            );


        $("#compra_taxa_imposto")
            .val(
                "16.00"
            );


        $("#imposto")
            .val(
                "0.00"
            );

        $("#subtotal")
            .val("0.00");

        $("#total")
            .val("0.00");

        $("#quantidade")
            .val(1);

        $("#preco_unitario_item")
            .val("");

        itensCompra = [];

        mostrarItensCompra();

        atualizarTotaisCompra();

        definirDatasPadraoCompra();

        selecionarStatusRascunho();

        $("#condicao_pagamento")
            .val("APRAZO");

        $("#origem_pagamento")
            .val("");

        $("#caixa_id_compra")
            .val("");

        $("#banco_id_compra")
            .val("");

        atualizarCondicaoPagamento();

        $("#modalCompra .modal-title")
            .text(
                "Nova Compra"
            );
    }


    // ==================================================
    // DATAS
    // ==================================================

    function definirDatasPadraoCompra() {

        const hoje =
            dataHojeCompra();

        if (
            !$("#data_compra").val()
        ) {

            $("#data_compra")
                .val(hoje);

        }

        if (
            !$("#data_vencimento").val()
        ) {

            $("#data_vencimento")
                .val(hoje);

        }
    }


    function dataHojeCompra() {

        const hoje =
            new Date();

        const ano =
            hoje.getFullYear();

        const mes =
            String(
                hoje.getMonth() + 1
            ).padStart(
                2,
                "0"
            );

        const dia =
            String(
                hoje.getDate()
            ).padStart(
                2,
                "0"
            );

        return `${ano}-${mes}-${dia}`;
    }


    function formatarDataCompra(
        data
    ) {
        if (!data) {
            return "-";
        }

        const partes =
            String(data)
                .substring(
                    0,
                    10
                )
                .split("-");

        if (partes.length !== 3) {
            return data;
        }

        return (
            partes[2]
            + "/"
            + partes[1]
            + "/"
            + partes[0]
        );
    }


    function formatarDataHoraCompra(
        data
    ) {
        if (!data) {
            return "-";
        }

        const valor =
            String(data);

        const dataParte =
            valor.substring(
                0,
                10
            );

        const horaParte =
            valor.length >= 16
                ? valor.substring(
                    11,
                    16
                )
                : "";

        return (
            formatarDataCompra(
                dataParte
            )
            + (
                horaParte
                    ? " " + horaParte
                    : ""
            )
        );
    }


    // ==================================================
    // RENDER STATUS
    // ==================================================

    function renderStatusCompra(
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
            normalizado === "confirmada"
        ) {

            classe =
                "success";

        } else if (
            normalizado === "rascunho"
        ) {

            classe =
                "warning";

        } else if (
            normalizado === "cancelada"
        ) {

            classe =
                "danger";

        }

        return `
            <span class="badge badge-${classe}">
                ${escaparHtmlCompra(
            texto || "-"
        )}
            </span>
        `;
    }


    // ==================================================
    // CONDIÇÃO
    // ==================================================

    function renderCondicaoPagamento(
        condicao
    ) {
        const valor =
            String(
                condicao || ""
            ).toUpperCase();

        if (valor === "AVISTA") {

            return `
                <span class="badge badge-success">
                    À vista
                </span>
            `;

        }

        if (valor === "APRAZO") {

            return `
                <span class="badge badge-info">
                    A prazo
                </span>
            `;

        }

        return `
            <span class="badge badge-secondary">
                -
            </span>
        `;
    }


    // ==================================================
    // FORMATAÇÃO
    // ==================================================

    function formatarMoedaCompra(
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


    function formatarNumeroCompra(
        valor
    ) {
        return Number(
            valor || 0
        ).toLocaleString(
            "pt-PT",
            {
                minimumFractionDigits:
                    0,

                maximumFractionDigits:
                    3
            }
        );
    }


    // ==================================================
    // MENSAGENS
    // ==================================================

    function mensagemErroCompra(
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


    function mensagemAvisoCompra(
        mensagem
    ) {
        return Swal.fire({

            icon:
                "warning",

            title:
                "Atenção",

            text:
                mensagem,

            confirmButtonText:
                "Entendi"

        });
    }


    function toastCompra(
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

    function bloquearBotaoCompra(
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


    function restaurarBotaoCompra(
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
    // SEGURANÇA HTML
    // ==================================================

    function escaparHtmlCompra(
        valor
    ) {
        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();
    }

})(jQuery);