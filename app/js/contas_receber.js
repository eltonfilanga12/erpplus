"use strict";

(function ($) {

    // ==================================================
    // URLs DO MÓDULO
    // ==================================================

    const RECEBER_CONTA_URL =
        "/erpplus/app/ajax/conta_receber_ajax.php";

    const RECEBER_CENTRO_URL =
        "/erpplus/app/ajax/centro_custo_ajax.php";

    const RECEBER_FILIAL_URL =
        "/erpplus/app/ajax/filial_ajax.php";

    const RECEBER_CAIXA_URL =
        "/erpplus/app/ajax/caixa_ajax.php";

    const RECEBER_BANCO_URL =
        "/erpplus/app/ajax/banco_ajax.php";


    let tabelaContasReceber = null;

    let contaRecebimentoAtual = null;

    let parcelasRecebimentoAtual = [];


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(async function () {

        try {

            inicializarTabelaContasReceber();

        } catch (erro) {

            console.error(
                "Erro ao iniciar tabela:",
                erro
            );

        }

        configurarEventosContaReceber();

        await Promise.allSettled([
            carregarClientesContaReceber(),
            carregarFormasPagamentoContaReceber(),
            carregarCentrosContaReceber(),
            carregarFiliaisContaReceber(),
            carregarCaixasRecebimento(),
            carregarBancosRecebimento()
        ]);

        definirDatasPadraoContaReceber();

    });


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosContaReceber() {

        $("#formContaReceber").on(
            "submit",
            guardarContaReceber
        );


        $("#formEliminarConta").on(
            "submit",
            eliminarContaReceber
        );


        $("#formReceber").on(
            "submit",
            confirmarRecebimento
        );

        $(document).on(
            "click",
            ".btn-selecionar-parcela",
            function () {

                const parcelaId =
                    Number(
                        $(this).data("id")
                    );


                selecionarParcelaRecebimento(
                    parcelaId
                );
            }
        );

        // Evento para abrir o histórico ao clicar no botão da tabela principal
        $(document).on("click", ".btn-historico-recebimento", function () {
            const contaId = Number($(this).data("id"));
            carregarHistoricoRecebimentos(contaId);
        });

        $("#destino_recebimento").on(
            "change",
            alterarDestinoRecebimento
        );


        $("#filial_id_receber").on(
            "change",
            function () {

                carregarCentrosContaReceber(
                    $(this).val()
                );

            }
        );

        // Imprimir recibo de um recebimento individual
        $(document).on(
            "click",
            ".btn-imprimir-recibo-recebimento",
            function () {

                const recebimentoId = Number(
                    $(this).data("id")
                );

                if (
                    !Number.isInteger(recebimentoId)
                    || recebimentoId <= 0
                ) {
                    mensagemErroReceber(
                        "ID do recebimento inválido."
                    );

                    return;
                }

                const url =
                    "/erpplus/app/ajax/documento_recibo_pdf.php?id="
                    + encodeURIComponent(recebimentoId);

                window.open(
                    url,
                    "_blank",
                    "noopener"
                );
            }
        );

        $("#modalContaReceber").on(
            "hidden.bs.modal",
            function () {

                limparFormularioContaReceber();

            }
        );


        $("#modalReceber").on(
            "hidden.bs.modal",
            function () {

                limparFormularioRecebimento();

            }
        );


        $(document).on(
            "click",
            ".btn-editar-conta-receber",
            function () {

                editarContaReceber(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-receber-conta",
            function () {

                abrirRecebimento(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-eliminar-conta-receber",
            function () {

                abrirEliminarContaReceber(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );

    }

    function selecionarParcelaRecebimento(
        parcelaId
    ) {

        const parcela =
            parcelasRecebimentoAtual
                .find(
                    function (item) {

                        return Number(
                            item.id
                        ) === Number(
                            parcelaId
                        );
                    }
                );


        if (!parcela) {

            mensagemErroReceber(
                "Parcela não encontrada."
            );

            return;
        }


        const saldo =
            Number(
                parcela.saldo
                || 0
            );


        if (saldo <= 0) {

            mensagemAvisoReceber(
                "Esta parcela já está paga."
            );

            return;
        }


        $("#receberParcelaId")
            .val(
                parcela.id
            );


        /*
         * Sugere liquidar a parcela inteira,
         * mas o utilizador pode escrever menos.
         */
        $("#valor_recebido")
            .prop(
                "disabled",
                false
            )
            .val(
                saldo.toFixed(2)
            )
            .attr(
                "max",
                saldo.toFixed(2)
            )
            .focus();


        $("#receberParcelasBody tr")
            .removeClass(
                "table-success"
            );


        $(
            '#receberParcelasBody tr[data-parcela-id="'
            + parcela.id
            + '"]'
        ).addClass(
            "table-success"
        );
    }

    async function carregarParcelasRecebimento(
        contaId
    ) {

        const resposta =
            await requisicaoContaReceber(
                RECEBER_CONTA_URL
                + "?acao=parcelas&conta_id="
                + encodeURIComponent(
                    contaId
                )
            );


        parcelasRecebimentoAtual =
            resposta.data || [];


        renderizarParcelasRecebimento();
    }

    function renderizarParcelasRecebimento() {

        const grupo =
            $("#grupoReceberParcelas");

        const tbody =
            $("#receberParcelasBody");


        tbody.empty();


        /*
         * Contas antigas, criadas antes
         * do sistema de parcelas.
         */
        if (
            !parcelasRecebimentoAtual.length
        ) {

            grupo.hide();

            $("#receberParcelaId")
                .val("");

            return;
        }


        grupo.show();


        parcelasRecebimentoAtual
            .forEach(
                function (parcela) {

                    const saldo =
                        Number(
                            parcela.saldo
                            || 0
                        );


                    const status =
                        String(
                            parcela.status
                            || "PENDENTE"
                        ).toUpperCase();


                    const paga =
                        saldo <= 0
                        || status === "PAGA";


                    const botao =
                        paga
                            ? `
                            <button
                                type="button"
                                class="btn btn-sm btn-light"
                                disabled>

                                Paga

                            </button>
                        `
                            : `
                            <button
                                type="button"
                                class="btn btn-sm btn-success btn-selecionar-parcela"
                                data-id="${parcela.id}">

                                Pagar

                            </button>
                        `;


                    tbody.append(`
                    <tr
                        data-parcela-id="${parcela.id}">

                        <td>
                            ${parcela.numero_parcela}
                            /
                            ${parcela.total_parcelas}
                        </td>

                        <td>
                            ${formatarDataReceber(
                        parcela.data_vencimento
                    )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaReceber(
                        parcela.valor
                    )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaReceber(
                        parcela.valor_recebido
                    )}
                        </td>

                        <td class="text-right">
                            <strong>
                                ${formatarMoedaReceber(
                        saldo
                    )}
                            </strong>
                        </td>

                        <td>
                            ${escaparHtmlReceber(
                        status
                    )}
                        </td>

                        <td>
                            ${botao}
                        </td>

                    </tr>
                `);
                }
            );
    }

    // ==================================================
    // REQUISIÇÃO PADRÃO
    // ==================================================

    async function requisicaoContaReceber(
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

    function inicializarTabelaContasReceber() {

        if (
            $.fn.DataTable.isDataTable(
                "#contasReceberTable"
            )
        ) {

            $("#contasReceberTable")
                .DataTable()
                .destroy();

        }

        tabelaContasReceber =
            $("#contasReceberTable")
                .DataTable({

                    ajax: {

                        url:
                            RECEBER_CONTA_URL,

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

                                mensagemErroReceber(
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
                                "Erro ao listar contas:",
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
                                "numero_documento",

                            defaultContent:
                                "-"
                        },

                        {
                            data:
                                "cliente_nome",

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
                                                ${escaparHtmlReceber(
                                            row.centro_custo_codigo
                                        )}
                                            </small>
                                            <br>
                                        `
                                        : "";

                                return `
                                    ${codigo}
                                    ${escaparHtmlReceber(
                                    valor
                                    || "Sem centro de custo"
                                )}
                                `;

                            }
                        },

                        {
                            data:
                                "data_emissao",

                            render:
                                formatarDataReceber
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
                                    data < dataHojeReceber()
                                    && Number(
                                        row.saldo || 0
                                    ) > 0;

                                const texto =
                                    formatarDataReceber(
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
                                "valor",

                            className:
                                "text-right",

                            render:
                                formatarMoedaReceber
                        },

                        {
                            data:
                                "valor_recebido",

                            className:
                                "text-right",

                            render:
                                formatarMoedaReceber
                        },

                        {
                            data:
                                "saldo",

                            className:
                                "text-right",

                            render: function (
                                valor
                            ) {

                                const numero =
                                    Number(
                                        valor || 0
                                    );

                                const classe =
                                    numero > 0
                                        ? "text-warning"
                                        : "text-success";

                                return `
                                    <span class="${classe}">
                                        ${formatarMoedaReceber(numero)}
                                    </span>
                                `;

                            }
                        },

                        {
                            data:
                                "status_nome",

                            render:
                                renderStatusContaReceber
                        },

                        {
                            data: null,
                            className: "text-right",
                            orderable: false,
                            searchable: false,
                            render: function (row) {
                                const saldo = Number(row.saldo || 0);

                                return `
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-info btn-editar-conta-receber"
                                        data-id="${row.id}"
                                        title="Editar">
                                        <i class="fa fa-edit"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success btn-receber-conta"
                                        data-id="${row.id}"
                                        title="Registar recebimento"
                                        ${saldo > 0 ? "" : "disabled"}>
                                        <i class="fa fa-money"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-secondary btn-historico-recebimento"
                                        data-id="${row.id}"
                                        title="Ver histórico de recebimentos">
                                        <i class="fa fa-history"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger btn-eliminar-conta-receber"
                                        data-id="${row.id}"
                                        title="Eliminar">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                `;
                            }
                        }

                    ],

                    order: [
                        [5, "asc"]
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
                                "Contas a Receber",

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                            }
                        },

                        {
                            extend:
                                "pdfHtml5",

                            text:
                                '<i class="fa fa-file-pdf"></i> PDF',

                            title:
                                "Contas a Receber",

                            orientation:
                                "landscape",

                            pageSize:
                                "A4",

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                            }
                        },

                        {
                            extend:
                                "print",

                            text:
                                '<i class="fa fa-print"></i> Imprimir',

                            title:
                                "Contas a Receber",

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
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

    function recarregarTabelaReceber() {

        if (tabelaContasReceber) {

            tabelaContasReceber.ajax.reload(
                null,
                false
            );

        }
    }


    // ==================================================
    // CLIENTES
    // ==================================================

    async function carregarClientesContaReceber() {

        try {

            const resposta =
                await requisicaoContaReceber(
                    RECEBER_CONTA_URL
                    + "?acao=clientes"
                );

            const select =
                $("#cliente_id");

            select.html(`
                <option value="">
                    Seleccione
                </option>
            `);

            (resposta.data || [])
                .forEach(
                    function (cliente) {

                        select.append(`
                            <option value="${cliente.id}">
                                ${escaparHtmlReceber(
                            cliente.nome
                            || "Cliente"
                        )}
                            </option>
                        `);

                    }
                );

        } catch (erro) {

            console.error(
                "Erro ao carregar clientes:",
                erro
            );

        }
    }


    // ==================================================
    // FORMAS DE PAGAMENTO
    // ==================================================

    async function carregarFormasPagamentoContaReceber() {

        try {

            const resposta =
                await requisicaoContaReceber(
                    RECEBER_CONTA_URL
                    + "?acao=formas_pagamento"
                );

            const formas =
                resposta.data || [];

            const selectConta =
                $("#forma_pagamento_id");

            const selectReceber =
                $("#receber_forma_pagamento_id");

            selectConta.html(`
                <option value="">
                    Seleccione
                </option>
            `);

            selectReceber.html(`
                <option value="">
                    Seleccione
                </option>
            `);

            formas.forEach(
                function (forma) {

                    const opcao = `
                        <option value="${forma.id}">
                            ${escaparHtmlReceber(
                        forma.nome
                        || "Forma de pagamento"
                    )}
                        </option>
                    `;

                    selectConta.append(
                        opcao
                    );

                    selectReceber.append(
                        opcao
                    );

                }
            );

        } catch (erro) {

            console.error(
                "Erro ao carregar formas de pagamento:",
                erro
            );

        }
    }


    // ==================================================
    // CENTROS DE CUSTO
    // ==================================================

    async function carregarCentrosContaReceber(
        filialId = null
    ) {
        try {

            const resposta =
                await requisicaoContaReceber(
                    RECEBER_CENTRO_URL
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
                                "RECEITA",
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
                                ${escaparHtmlReceber(
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
    // FILIAIS
    // ==================================================

    async function carregarFiliaisContaReceber() {

        try {

            const resposta =
                await requisicaoContaReceber(
                    RECEBER_FILIAL_URL
                    + "?acao=listar"
                );

            const select =
                $("#filial_id_receber");

            select.html(`
                <option value="">
                    Sede Principal / Sem filial
                </option>
            `);

            (resposta.data || [])
                .forEach(
                    function (filial) {

                        select.append(`
                            <option value="${filial.id}">
                                ${escaparHtmlReceber(
                            filial.nome
                            || "Filial"
                        )}
                            </option>
                        `);

                    }
                );

        } catch (erro) {

            console.warn(
                "Erro ao carregar filiais:",
                erro
            );

        }
    }


    // ==================================================
    // CAIXAS
    // ==================================================

    async function carregarCaixasRecebimento() {

        try {

            const resposta =
                await requisicaoContaReceber(
                    RECEBER_CAIXA_URL
                    + "?acao=listar"
                );

            const select =
                $("#receber_caixa_id");

            select.html(`
                <option value="">
                    Seleccione o caixa
                </option>
            `);

            (resposta.data || [])
                .forEach(
                    function (caixa) {

                        const status =
                            String(
                                caixa.status || ""
                            ).toLowerCase();

                        if (
                            status
                            && status !== "aberto"
                        ) {
                            return;
                        }

                        select.append(`
                            <option value="${caixa.id}">

                                ${escaparHtmlReceber(
                            caixa.nome
                            || "Caixa"
                        )}
                                —
                                ${formatarMoedaReceber(
                            caixa.saldo_atual || 0
                        )}

                            </option>
                        `);

                    }
                );

        } catch (erro) {

            console.warn(
                "Erro ao carregar caixas:",
                erro
            );

        }
    }


    // ==================================================
    // BANCOS
    // ==================================================

    async function carregarBancosRecebimento() {

        try {

            const resposta =
                await requisicaoContaReceber(
                    RECEBER_BANCO_URL
                    + "?acao=listar"
                );

            const select =
                $("#receber_banco_id");

            select.html(`
                <option value="">
                    Seleccione o banco
                </option>
            `);

            (resposta.data || [])
                .forEach(
                    function (banco) {

                        const status =
                            String(
                                banco.status || ""
                            ).toLowerCase();

                        if (
                            status
                            && ![
                                "activo",
                                "ativo"
                            ].includes(status)
                        ) {
                            return;
                        }

                        const identificacao =
                            [
                                banco.nome_banco,
                                banco.conta
                            ]
                                .filter(Boolean)
                                .join(" - ");

                        select.append(`
                            <option value="${banco.id}">

                                ${escaparHtmlReceber(
                            identificacao
                            || "Banco"
                        )}
                                —
                                ${formatarMoedaReceber(
                            banco.saldo_atual || 0
                        )}

                            </option>
                        `);

                    }
                );

        } catch (erro) {

            console.warn(
                "Erro ao carregar bancos:",
                erro
            );

        }
    }


    // ==================================================
    // GUARDAR
    // ==================================================

    async function guardarContaReceber(
        evento
    ) {
        evento.preventDefault();

        const formulario =
            evento.currentTarget;

        const botao =
            $(formulario)
                .find(
                    'button[type="submit"]'
                );

        bloquearBotaoReceber(
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

            const resposta =
                await requisicaoContaReceber(
                    RECEBER_CONTA_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalContaReceber")
                .modal("hide");

            limparFormularioContaReceber();

            recarregarTabelaReceber();

            toastReceber(
                resposta.mensagem
                || "Conta guardada com sucesso."
            );

        } catch (erro) {

            mensagemErroReceber(
                erro.message
            );

        } finally {

            restaurarBotaoReceber(
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

    async function editarContaReceber(
        id
    ) {
        try {

            const resposta =
                await requisicaoContaReceber(
                    RECEBER_CONTA_URL
                    + "?acao=buscar&id="
                    + encodeURIComponent(id)
                );

            const conta =
                resposta.data;

            if (!conta) {
                throw new Error(
                    "Conta a receber não encontrada."
                );
            }

            await Promise.allSettled([
                carregarClientesContaReceber(),
                carregarFormasPagamentoContaReceber(),
                carregarFiliaisContaReceber(),
                carregarCentrosContaReceber(
                    conta.filial_id
                )
            ]);

            $("#contaReceberId")
                .val(
                    conta.id
                );

            $("#cliente_id")
                .val(
                    conta.cliente_id
                    || ""
                );

            $("#filial_id_receber")
                .val(
                    conta.filial_id
                    || ""
                );

            $("#centro_custo_id")
                .val(
                    conta.centro_custo_id
                    || ""
                );

            $("#forma_pagamento_id")
                .val(
                    conta.forma_pagamento_id
                    || ""
                );

            $("#numero_documento")
                .val(
                    conta.numero_documento
                    || ""
                );

            $("#data_emissao")
                .val(
                    conta.data_emissao
                    || ""
                );

            $("#data_vencimento")
                .val(
                    conta.data_vencimento
                    || ""
                );

            $("#valor")
                .val(
                    conta.valor
                    || ""
                );

            $("#desconto")
                .val(
                    conta.desconto
                    || "0.00"
                );

            $("#juros")
                .val(
                    conta.juros
                    || "0.00"
                );

            $("#multa")
                .val(
                    conta.multa
                    || "0.00"
                );

            $("#observacoes")
                .val(
                    conta.observacoes
                    || ""
                );

            $("#modalContaReceber")
                .modal("show");

        } catch (erro) {

            mensagemErroReceber(
                erro.message
            );

        }
    }


    // ==================================================
    // ABRIR RECEBIMENTO
    // ==================================================

    async function abrirRecebimento(
        id
    ) {
        try {

            const resposta =
                await requisicaoContaReceber(
                    RECEBER_CONTA_URL
                    + "?acao=buscar&id="
                    + encodeURIComponent(id)
                );

            const conta =
                resposta.data;

            if (!conta) {
                throw new Error(
                    "Conta a receber não encontrada."
                );
            }

            const saldo =
                Number(
                    conta.saldo || 0
                );

            if (saldo <= 0) {
                throw new Error(
                    "Esta conta já foi totalmente recebida."
                );
            }

            contaRecebimentoAtual =
                conta;

            await carregarParcelasRecebimento(
                conta.id
            );

            $("#receberContaId")
                .val(
                    conta.id
                );

            $("#receberCentroCustoId")
                .val(
                    conta.centro_custo_id
                    || ""
                );

            $("#receberFilialId")
                .val(
                    conta.filial_id
                    || ""
                );

            $("#receberClienteNome")
                .text(
                    conta.cliente_nome
                    || "-"
                );

            $("#receberSaldoConta")
                .text(
                    formatarMoedaReceber(
                        saldo
                    )
                );

            $("#receberCentroNome")
                .text(
                    conta.centro_custo_nome
                    || "Sem centro de custo"
                );

            /*
 * Se houver parcelas,
 * esperamos o utilizador escolher uma.
 */
            if (
                parcelasRecebimentoAtual.length
            ) {

                $("#valor_recebido")
                    .val("")
                    .attr(
                        "max",
                        ""
                    )
                    .prop(
                        "disabled",
                        true
                    );

            } else {

                /*
                 * Compatibilidade com contas antigas.
                 */
                $("#valor_recebido")
                    .val(
                        saldo.toFixed(2)
                    )
                    .attr(
                        "max",
                        saldo.toFixed(2)
                    )
                    .prop(
                        "disabled",
                        false
                    );
            }

            $("#data_recebimento")
                .val(
                    dataHojeReceber()
                );

            $("#receber_forma_pagamento_id")
                .val(
                    conta.forma_pagamento_id
                    || ""
                );

            $("#receber_documento")
                .val(
                    conta.numero_documento
                    || ""
                );

            $("#destino_recebimento")
                .val("")
                .trigger("change");

            await Promise.allSettled([
                carregarCaixasRecebimento(),
                carregarBancosRecebimento()
            ]);

            $("#modalReceber")
                .modal("show");

        } catch (erro) {

            mensagemErroReceber(
                erro.message
            );

        }
    }



    // Função para carregar os dados via AJAX e preencher a modal
    // ==================================================
    // HISTÓRICO DE RECEBIMENTOS
    // ==================================================

    async function carregarHistoricoRecebimentos(contaId) {

        try {

            const resposta = await requisicaoContaReceber(
                RECEBER_CONTA_URL
                + "?acao=historico_recebimentos&conta_id="
                + encodeURIComponent(contaId),
                {
                    method: "GET"
                }
            );

            const tbody = $(
                "#tabelaHistoricoRecebimentos tbody"
            );

            tbody.empty();

            const recebimentos = resposta.dados || [];

            if (recebimentos.length === 0) {

                tbody.append(`
                <tr>
                    <td
                        colspan="7"
                        class="text-center text-muted">

                        Nenhum recebimento registado
                        para esta conta.

                    </td>
                </tr>
            `);

            } else {

                recebimentos.forEach(function (rec) {

                    const recebimentoId = Number(rec.id);

                    const dataFormatada =
                        rec.data_recebimento
                            ? formatarDataReceber(
                                rec.data_recebimento
                            )
                            : "-";

                    const valorFormatado =
                        formatarMoedaReceber(rec.valor);

                    const formaPagamento =
                        escaparHtmlReceber(
                            rec.forma_pagamento || "-"
                        );

                    const usuario =
                        escaparHtmlReceber(
                            rec.usuario_nome || "-"
                        );

                    const observacao =
                        escaparHtmlReceber(
                            rec.observacao || "-"
                        );

                    /*
                     * O comprovativo original continua
                     * disponível, quando existir.
                     */
                    const comprovativoHtml = rec.comprovativo
                        ? `
                        <a
                            href="${escaparHtmlReceber(
                            rec.comprovativo
                        )}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-sm btn-outline-info">

                            <i class="fa fa-paperclip"></i>
                            Ver

                        </a>
                    `
                        : "-";

                    /*
                     * O recibo é gerado a partir do ID
                     * do recebimento individual.
                     */
                    const botaoRecibo =
                        Number.isInteger(recebimentoId)
                            && recebimentoId > 0
                            ? `
                            <button
                                type="button"
                                class="btn btn-sm btn-danger btn-imprimir-recibo-recebimento"
                                data-id="${recebimentoId}"
                                title="Imprimir recibo">

                                <i class="fa fa-file-pdf"></i>
                                Recibo

                            </button>
                        `
                            : "-";

                    tbody.append(`
                    <tr>

                        <td>
                            ${dataFormatada}
                        </td>

                        <td>
                            ${formaPagamento}
                        </td>

                        <td class="text-right">
                            <strong>
                                ${valorFormatado}
                            </strong>
                        </td>

                        <td>
                            ${usuario}
                        </td>

                        <td>
                            ${observacao}
                        </td>

                        <td>
                            ${comprovativoHtml}
                        </td>

                        <td class="text-center">
                            ${botaoRecibo}
                        </td>

                    </tr>
                `);

                });

            }

            $("#modalHistoricoRecebimentos").modal("show");

        } catch (erro) {

            mensagemErroReceber(erro.message);

        }
    }

    // ==================================================
    // ALTERAR DESTINO
    // ==================================================

    function alterarDestinoRecebimento() {
        const destino = String($("#destino_recebimento").val() || "").toUpperCase();
        const grupoCaixa = $("#grupoReceberCaixa");
        const grupoBanco = $("#grupoReceberBanco");
        const campoComprovativo = $("#comprovativo_banco");

        if (destino === "CAIXA") {
            grupoCaixa.show();
            grupoBanco.hide();

            $("#receber_caixa_id").prop("required", true);
            $("#receber_banco_id").prop("required", false).val("");

            // Desabilita e limpa comprovativo ao escolher Caixa
            campoComprovativo.prop("disabled", false).val("");
        } else if (destino === "BANCO") {
            grupoCaixa.hide();
            grupoBanco.show();

            $("#receber_caixa_id").prop("required", false).val("");
            $("#receber_banco_id").prop("required", true);

            // Habilita comprovativo apenas para Banco
            campoComprovativo.prop("disabled", false);
        } else {
            grupoCaixa.hide();
            grupoBanco.hide();

            $("#receber_caixa_id").prop("required", false).val("");
            $("#receber_banco_id").prop("required", false).val("");
            campoComprovativo.prop("disabled", true).val("");
        }
    }


    // ==================================================
    // CONFIRMAR RECEBIMENTO
    // ==================================================

    async function confirmarRecebimento(
        evento
    ) {
        evento.preventDefault();

        if (!contaRecebimentoAtual) {

            mensagemErroReceber(
                "Conta não selecionada."
            );

            return;
        }

        const valor =
            Number(
                $("#valor_recebido").val()
                || 0
            );

        const saldo =
            Number(
                contaRecebimentoAtual.saldo
                || 0
            );

        if (valor <= 0) {

            mensagemAvisoReceber(
                "O valor recebido deve ser superior a zero."
            );

            return;
        }

        if (valor > saldo) {

            mensagemAvisoReceber(
                "O valor não pode ser superior ao saldo pendente de "
                + formatarMoedaReceber(
                    saldo
                )
                + "."
            );

            return;
        }

        const destino =
            String(
                $("#destino_recebimento")
                    .val()
                || ""
            ).toUpperCase();

        if (
            ![
                "CAIXA",
                "BANCO"
            ].includes(destino)
        ) {

            mensagemAvisoReceber(
                "Selecione o destino do recebimento."
            );

            return;
        }

        if (
            destino === "CAIXA"
            && !$("#receber_caixa_id").val()
        ) {

            mensagemAvisoReceber(
                "Selecione o caixa de destino."
            );

            return;
        }

        if (
            destino === "BANCO"
            && !$("#receber_banco_id").val()
        ) {

            mensagemAvisoReceber(
                "Selecione o banco de destino."
            );

            return;
        }

        const confirmacao =
            await Swal.fire({

                icon:
                    "question",

                title:
                    "Confirmar recebimento?",

                html:
                    `
                        <p>
                            Cliente:
                            <strong>
                                ${escaparHtmlReceber(
                        contaRecebimentoAtual.cliente_nome
                        || "-"
                    )}
                            </strong>
                        </p>

                        <p>
                            Valor:
                            <strong class="text-success">
                                ${formatarMoedaReceber(valor)}
                            </strong>
                        </p>

                        <p>
                            Destino:
                            <strong>
                                ${destino === "CAIXA"
                        ? "Caixa"
                        : "Banco"}
                            </strong>
                        </p>
                    `,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Confirmar recebimento",

                cancelButtonText:
                    "Cancelar",

                confirmButtonColor:
                    "#28a745"

            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        const botao =
            $("#btnConfirmarRecebimento");

        bloquearBotaoReceber(
            botao,
            "A processar..."
        );

        try {

            const dados =
                new FormData(
                    evento.currentTarget
                );

            dados.append(
                "acao",
                "receber"
            );

            const resposta =
                await requisicaoContaReceber(
                    RECEBER_CONTA_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalReceber")
                .modal("hide");

            limparFormularioRecebimento();

            recarregarTabelaReceber();

            await Promise.allSettled([
                carregarCaixasRecebimento(),
                carregarBancosRecebimento()
            ]);

            await Swal.fire({

                icon:
                    "success",

                title:
                    "Recebimento concluído",

                text:
                    resposta.mensagem
                    || "Recebimento registado com sucesso.",

                confirmButtonText:
                    "Fechar"

            });

        } catch (erro) {

            mensagemErroReceber(
                erro.message
            );

        } finally {

            restaurarBotaoReceber(
                botao,
                `
                    <i class="fa fa-money"></i>
                    Confirmar Recebimento
                `
            );

        }
    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    function abrirEliminarContaReceber(
        id
    ) {
        $("#eliminarContaReceberId")
            .val(id);

        $("#modalEliminarConta")
            .modal("show");
    }


    async function eliminarContaReceber(
        evento
    ) {
        evento.preventDefault();

        const botao =
            $(evento.currentTarget)
                .find(
                    'button[type="submit"]'
                );

        bloquearBotaoReceber(
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
                await requisicaoContaReceber(
                    RECEBER_CONTA_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalEliminarConta")
                .modal("hide");

            recarregarTabelaReceber();

            toastReceber(
                resposta.mensagem
                || "Conta eliminada com sucesso."
            );

        } catch (erro) {

            mensagemErroReceber(
                erro.message
            );

        } finally {

            restaurarBotaoReceber(
                botao,
                `
                    <i class="fa fa-trash"></i>
                    Eliminar
                `
            );

        }
    }


    // ==================================================
    // LIMPEZA
    // ==================================================

    function limparFormularioContaReceber() {

        const formulario =
            document.getElementById(
                "formContaReceber"
            );

        if (formulario) {
            formulario.reset();
        }

        $("#contaReceberId")
            .val("");

        $("#desconto")
            .val("0.00");

        $("#juros")
            .val("0.00");

        $("#multa")
            .val("0.00");

        definirDatasPadraoContaReceber();
    }


    function limparFormularioRecebimento() {

        const formulario =
            document.getElementById(
                "formReceber"
            );

        if (formulario) {
            formulario.reset();
        }

        contaRecebimentoAtual =
            null;

        $("#receberContaId")
            .val("");

        $("#receberCentroCustoId")
            .val("");

        $("#receberFilialId")
            .val("");

        $("#receberClienteNome")
            .text("-");

        $("#receberSaldoConta")
            .text(
                formatarMoedaReceber(0)
            );

        $("#receberCentroNome")
            .text("-");

        $("#valor_recebido")
            .removeAttr("max");

        $("#data_recebimento")
            .val(
                dataHojeReceber()
            );

        $("#destino_recebimento")
            .val("")
            .trigger("change");
    }


    // ==================================================
    // DATAS
    // ==================================================

    function definirDatasPadraoContaReceber() {

        const hoje =
            dataHojeReceber();

        if (
            !$("#data_emissao").val()
        ) {

            $("#data_emissao")
                .val(hoje);

        }

        if (
            !$("#data_vencimento").val()
        ) {

            $("#data_vencimento")
                .val(hoje);

        }

        if (
            !$("#data_recebimento").val()
        ) {

            $("#data_recebimento")
                .val(hoje);

        }
    }


    function dataHojeReceber() {

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


    function formatarDataReceber(
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


    // ==================================================
    // STATUS
    // ==================================================

    function renderStatusContaReceber(
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
            normalizado === "recebido"
            || normalizado === "pago"
        ) {

            classe =
                "success";

        } else if (
            normalizado === "parcial"
        ) {

            classe =
                "info";

        } else if (
            normalizado === "vencido"
        ) {

            classe =
                "danger";

        } else if (
            normalizado === "pendente"
        ) {

            classe =
                "warning";

        } else if (
            normalizado === "cancelado"
        ) {

            classe =
                "secondary";

        }

        return `
            <span class="badge badge-${classe}">
                ${escaparHtmlReceber(
            texto || "-"
        )}
            </span>
        `;
    }


    // ==================================================
    // MOEDA
    // ==================================================

    function formatarMoedaReceber(
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

    function mensagemErroReceber(
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


    function mensagemAvisoReceber(
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


    function toastReceber(
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

    function bloquearBotaoReceber(
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


    function restaurarBotaoReceber(
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

    function escaparHtmlReceber(
        valor
    ) {
        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();
    }

})(jQuery);