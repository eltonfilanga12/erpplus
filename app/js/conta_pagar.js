"use strict";

(function ($) {

    /*
    |--------------------------------------------------------------------------
    | URLs exclusivas deste módulo
    |--------------------------------------------------------------------------
    |
    | Todas ficam dentro desta função para evitar erros como:
    |
    | Identifier 'FILIAL_URL' has already been declared
    |
    */

    const CONTA_PAGAR_URL =
        "/erpplus/app/ajax/conta_pagar_ajax.php";

    const CONTA_PAGAR_FORNECEDOR_URL =
        "/erpplus/app/ajax/fornecedor_ajax.php";

    const CONTA_PAGAR_FORMA_URL =
        "/erpplus/app/ajax/forma_pagamento_ajax.php";

    const CONTA_PAGAR_CENTRO_URL =
        "/erpplus/app/ajax/centro_custo_ajax.php";

    const CONTA_PAGAR_FILIAL_URL =
        "/erpplus/app/ajax/filial_ajax.php";

    const CONTA_PAGAR_CAIXA_URL =
        "/erpplus/app/ajax/caixa_ajax.php";

    const CONTA_PAGAR_BANCO_URL =
        "/erpplus/app/ajax/banco_ajax.php";


    let tabelaContasPagar = null;

    let contaPagamentoAtual = null;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(async function () {

        try {

            inicializarTabelaContasPagar();

        } catch (erro) {

            console.error(
                "Erro ao iniciar DataTable:",
                erro
            );

        }

        configurarEventosContaPagar();

        await Promise.allSettled([
            carregarFornecedoresContaPagar(),
            carregarFormasPagamentoContaPagar(),
            carregarCentrosCustoContaPagar(),
            carregarFiliaisContaPagar(),
            carregarCaixasPagamento(),
            carregarBancosPagamento()
        ]);

        definirDatasPadraoContaPagar();

        // ==========================================
        // ABRIR CONTA RECEBIDA PELA URL
        // ==========================================

        const parametros =
            new URLSearchParams(
                window.location.search
            );

        const contaId =
            Number(
                parametros.get("conta_id")
                || 0
            );


        const acaoUrl =
            String(
                parametros.get("acao")
                || ""
            )
                .trim()
                .toLowerCase();


        if (contaId > 0) {

            if (
                acaoUrl === "pagamento"
            ) {

                await abrirPagamentoConta(
                    contaId
                );

            } else {

                await editarContaPagar(
                    contaId
                );
            }
        }
    });


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosContaPagar() {

        $("#formContaPagar").on(
            "submit",
            guardarContaPagar
        );


        $("#formPagamento").on(
            "submit",
            confirmarPagamentoConta
        );


        $("#formEliminarPagar").on(
            "submit",
            eliminarContaPagar
        );


        $("#pagamento_origem").on(
            "change",
            alterarOrigemPagamento
        );


        $("#filial_id_conta").on(
            "change",
            function () {

                carregarCentrosCustoContaPagar(
                    $(this).val()
                );

            }
        );


        $("#modalContaPagar").on(
            "hidden.bs.modal",
            function () {

                limparFormularioContaPagar();

            }
        );

        $(document).on(
            "click",
            ".btn-historico-pagamentos",
            function () {
                carregarHistoricoPagamentos(
                    Number($(this).data("id"))
                );
            }
        );

        $("#modalPagamento").on(
            "hidden.bs.modal",
            function () {

                limparFormularioPagamento();

            }
        );


        $(document).on(
            "click",
            ".btn-editar-conta-pagar",
            function () {

                editarContaPagar(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-pagar-conta",
            function () {

                abrirPagamentoConta(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-eliminar-conta-pagar",
            function () {

                abrirEliminarContaPagar(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );

    }


    // ==================================================
    // REQUISIÇÃO PADRÃO
    // ==================================================

    async function requisicaoContaPagar(
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
                "Resposta não JSON:",
                texto
            );

            throw new Error(
                "O servidor não retornou uma resposta JSON válida."
            );
        }


        if (
            resposta.status === 401
        ) {

            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Sessão terminada",

                text:
                    json.mensagem
                    || json.message
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


        /*
         * Compatibilidade com:
         *
         * success / data
         * sucesso / dados
         */
        const sucesso =
            json.success
            ?? json.sucesso
            ?? resposta.ok;


        if (
            !resposta.ok
            || sucesso === false
        ) {

            throw new Error(
                json.mensagem
                || json.message
                || "Não foi possível concluir a operação."
            );
        }


        /*
         * Normaliza a resposta para todos os módulos.
         */
        return {
            ...json,

            success:
                sucesso === true,

            data:
                json.data
                ?? json.dados
                ?? []
        };
    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function inicializarTabelaContasPagar() {

        if (
            $.fn.DataTable.isDataTable(
                "#contasPagarTable"
            )
        ) {

            $("#contasPagarTable")
                .DataTable()
                .destroy();

        }

        tabelaContasPagar =
            $("#contasPagarTable")
                .DataTable({

                    ajax: {

                        url:
                            CONTA_PAGAR_URL,

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

                                mensagemErroContaPagar(
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
                                "fornecedor",

                            defaultContent:
                                "-"
                        },

                        {
                            data:
                                "numero_documento",

                            defaultContent:
                                "-"
                        },

                        {
                            data:
                                "descricao",

                            defaultContent:
                                "-"
                        },

                        {
                            data:
                                "centro_custo",

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
                                                ${escaparHtmlConta(
                                            row.centro_custo_codigo
                                        )}
                                            </small>
                                            <br>
                                        `
                                        : "";

                                return `
                                    ${codigo}
                                    ${escaparHtmlConta(
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
                                formatarDataContaPagar
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

                                const dataFormatada =
                                    formatarDataContaPagar(
                                        data
                                    );

                                const vencida =
                                    data
                                    < dataHojeContaPagar()
                                    && Number(
                                        row.saldo || 0
                                    ) > 0;

                                if (vencida) {

                                    return `
                                        <span class="text-danger font-weight-bold">
                                            ${dataFormatada}
                                        </span>
                                    `;

                                }

                                return dataFormatada;

                            }
                        },

                        {
                            data:
                                "valor",

                            className:
                                "text-right",

                            render:
                                formatarMoedaContaPagar
                        },

                        {
                            data:
                                "valor_pago",

                            className:
                                "text-right",

                            render:
                                formatarMoedaContaPagar
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
                                        ? "text-danger"
                                        : "text-success";

                                return `
                                    <span class="${classe}">
                                        ${formatarMoedaContaPagar(numero)}
                                    </span>
                                `;

                            }
                        },

                        {
                            data:
                                "status",

                            render:
                                renderStatusContaPagar
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

                            render: function (
                                row
                            ) {

                                const saldo =
                                    Number(
                                        row.saldo || 0
                                    );

                                const podePagar =
                                    saldo > 0;

                                // ... dentro da coluna de ações render(row):
                                return `
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-info btn-editar-conta-pagar"
                                        data-id="${row.id}"
                                        title="Editar">
                                        <i class="fa fa-edit"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-secondary btn-historico-pagamentos"
                                        data-id="${row.id}"
                                        title="Ver Histórico de Pagamentos">
                                        <i class="fa fa-history"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success btn-pagar-conta"
                                        data-id="${row.id}"
                                        title="Registar pagamento"
                                        ${podePagar ? "" : "disabled"}>
                                        <i class="fa fa-money"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger btn-eliminar-conta-pagar"
                                        data-id="${row.id}"
                                        title="Eliminar">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                `;

                            }
                        }

                    ],

                    order: [
                        [6, "asc"]
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
                                "Lista de Contas a Pagar",

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
                                "Lista de Contas a Pagar",

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
                                "Lista de Contas a Pagar",

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
    // CARREGAR HISTÓRICO DE PAGAMENTOS
    // ==================================================

    async function carregarHistoricoPagamentos(contaId) {
        try {
            const dados = new FormData();
            dados.append("acao", "historico_pagamentos");
            dados.append("id", contaId);

            const resposta = await requisicaoContaPagar(
                CONTA_PAGAR_URL,
                {
                    method: "POST",
                    body: dados
                }
            );

            const dadosConta = resposta.data.conta;
            const historico = resposta.data.historico_pagamentos || [];

            // Preenche o resumo no topo do modal
            $("#historico_conta_id_titulo").text("#" + dadosConta.id);
            $("#hist_fornecedor").text(dadosConta.fornecedor || "-");
            $("#hist_valor_total").text(formatarMoedaContaPagar(dadosConta.valor));
            $("#hist_saldo").text(formatarMoedaContaPagar(dadosConta.saldo));

            const tbody = $("#tbodyHistoricoPagamentos");
            tbody.empty();

            if (historico.length === 0) {
                tbody.append(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">
                        Nenhum pagamento efetuado para esta conta até ao momento.
                    </td>
                </tr>
            `);
            } else {
                historico.forEach(function (item) {
                    // Monta o botão do comprovativo
                    let btnComprovativo = `<span class="badge badge-light text-muted">Sem ficheiro</span>`;

                    if (item.comprovativo && item.comprovativo.trim() !== "") {
                        btnComprovativo = `
                        <a href="${item.comprovativo}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="btn btn-sm btn-outline-primary"
                           title="Visualizar Comprovativo">
                            <i class="fa fa-file-text-o mr-1"></i> Ver
                        </a>
                    `;
                    }

                    tbody.append(`
                    <tr>
                        <td>${formatarDataContaPagar(item.data_pagamento)}</td>
                        <td>${escaparHtmlConta(item.forma_pagamento || "Não informada")}</td>
                        <td>${escaparHtmlConta(item.numero_documento || "-")}</td>
                        <td>${escaparHtmlConta(item.observacoes || "-")}</td>
                        <td class="text-center">${btnComprovativo}</td>
                        <td class="text-right text-success font-weight-bold">
                            ${formatarMoedaContaPagar(item.valor)}
                        </td>
                    </tr>
                `);
                });
            }

            $("#modalHistoricoPagamentos").modal("show");

        } catch (erro) {
            mensagemErroContaPagar(erro.message);
        }
    }
    // ==================================================
    // RECARREGAR TABELA
    // ==================================================

    function recarregarTabelaContasPagar() {

        if (tabelaContasPagar) {

            tabelaContasPagar.ajax.reload(
                null,
                false
            );

        }

    }


    // ==================================================
    // FORNECEDORES
    // ==================================================

    async function carregarFornecedoresContaPagar() {

        try {

            const resposta =
                await requisicaoContaPagar(
                    CONTA_PAGAR_FORNECEDOR_URL
                    + "?acao=listar"
                );


            const fornecedores =
                resposta.data
                ?? resposta.dados
                ?? [];


            const select =
                $("#fornecedor_id");


            /*
             * Guarda o valor que estava selecionado,
             * caso seja uma recarga.
             */
            const valorAtual =
                select.val();


            select.html(`
            <option value="">
                Seleccione
            </option>
        `);


            fornecedores.forEach(
                function (fornecedor) {

                    select.append(`
                    <option value="${fornecedor.id}">
                        ${escaparHtmlConta(
                        fornecedor.nome
                        || "Fornecedor"
                    )}
                    </option>
                `);
                }
            );


            /*
             * Se havia fornecedor selecionado,
             * tenta restaurá-lo.
             */
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


            return fornecedores;

        } catch (erro) {

            console.error(
                "Erro ao carregar fornecedores:",
                erro
            );

            return [];
        }
    }

    // ==================================================
    // FORMAS DE PAGAMENTO
    // ==================================================

    async function carregarFormasPagamentoContaPagar() {

        try {

            const resposta =
                await requisicaoContaPagar(
                    CONTA_PAGAR_FORMA_URL
                    + "?acao=listar"
                );

            const formas =
                resposta.data || [];

            const selectConta =
                $("#forma_pagamento_id");

            const selectPagamento =
                $("#pagamento_forma_id");

            selectConta.html(`
                <option value="">
                    Seleccione
                </option>
            `);

            selectPagamento.html(`
                <option value="">
                    Seleccione
                </option>
            `);

            formas.forEach(
                function (forma) {

                    const opcao = `
                        <option value="${forma.id}">
                            ${escaparHtmlConta(
                        forma.nome
                        || "Forma de pagamento"
                    )}
                        </option>
                    `;

                    selectConta.append(
                        opcao
                    );

                    selectPagamento.append(
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

    async function carregarCentrosCustoContaPagar(
        filialId = null
    ) {
        try {

            const resposta =
                await requisicaoContaPagar(
                    CONTA_PAGAR_CENTRO_URL
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

                        /*
                         * Caso listar_select futuramente devolva
                         * filial_id e tipo, o filtro já estará pronto.
                         */

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
                                ${escaparHtmlConta(
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
                "Erro ao carregar centros de custo:",
                erro
            );

        }
    }


    // ==================================================
    // FILIAIS
    // ==================================================

    async function carregarFiliaisContaPagar() {

        try {

            const resposta =
                await requisicaoContaPagar(
                    CONTA_PAGAR_FILIAL_URL
                    + "?acao=listar"
                );

            const select =
                $("#filial_id_conta");

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
                                ${escaparHtmlConta(
                            filial.nome
                            || "Filial"
                        )}
                            </option>
                        `);

                    }
                );

        } catch (erro) {

            console.warn(
                "Não foi possível carregar filiais:",
                erro
            );

        }
    }


    // ==================================================
    // CAIXAS
    // ==================================================

    async function carregarCaixasPagamento() {

        try {

            const resposta =
                await requisicaoContaPagar(
                    CONTA_PAGAR_CAIXA_URL
                    + "?acao=listar"
                );

            const select =
                $("#pagamento_caixa_id");

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

                        /*
                         * Se o endpoint devolver status,
                         * apresenta apenas caixas abertos.
                         */

                        if (
                            status
                            && status !== "aberto"
                        ) {
                            return;
                        }

                        select.append(`
                            <option
                                value="${caixa.id}"
                                data-saldo="${Number(
                            caixa.saldo_atual || 0
                        )}">

                                ${escaparHtmlConta(
                            caixa.nome
                            || "Caixa"
                        )}
                                —
                                ${formatarMoedaContaPagar(
                            caixa.saldo_atual || 0
                        )}

                            </option>
                        `);

                    }
                );

        } catch (erro) {

            console.warn(
                "Não foi possível carregar caixas:",
                erro
            );

        }
    }


    // ==================================================
    // BANCOS
    // ==================================================

    async function carregarBancosPagamento() {

        try {

            const resposta =
                await requisicaoContaPagar(
                    CONTA_PAGAR_BANCO_URL
                    + "?acao=listar"
                );

            const select =
                $("#pagamento_banco_id");

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
                            <option
                                value="${banco.id}"
                                data-saldo="${Number(
                            banco.saldo_atual || 0
                        )}">

                                ${escaparHtmlConta(
                            identificacao
                            || "Banco"
                        )}
                                —
                                ${formatarMoedaContaPagar(
                            banco.saldo_atual || 0
                        )}

                            </option>
                        `);

                    }
                );

        } catch (erro) {

            console.warn(
                "Não foi possível carregar bancos:",
                erro
            );

        }
    }


    // ==================================================
    // GUARDAR CONTA
    // ==================================================

    async function guardarContaPagar(
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

        bloquearBotaoContaPagar(
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
                "guardar"
            );

            const resposta =
                await requisicaoContaPagar(
                    CONTA_PAGAR_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalContaPagar")
                .modal("hide");

            limparFormularioContaPagar();

            recarregarTabelaContasPagar();

            toastContaPagar(
                resposta.mensagem
                || "Conta guardada com sucesso."
            );

        } catch (erro) {

            mensagemErroContaPagar(
                erro.message
            );

        } finally {

            restaurarBotaoContaPagar(
                botao,
                `
                    <i class="fa fa-save"></i>
                    Guardar
                `
            );

        }
    }


    // ==================================================
    // EDITAR CONTA
    // ==================================================

    async function editarContaPagar(
        id
    ) {

        try {

            const resposta =
                await requisicaoContaPagar(
                    CONTA_PAGAR_URL
                    + "?acao=obter&id="
                    + encodeURIComponent(id)
                );


            const conta =
                resposta.data;


            if (!conta) {

                throw new Error(
                    "Conta a pagar não encontrada."
                );
            }


            /*
             * Primeiro carregar os selects independentes.
             */
            await Promise.allSettled([

                carregarFornecedoresContaPagar(),

                carregarFormasPagamentoContaPagar(),

                carregarFiliaisContaPagar()
            ]);


            // ID
            $("#contaPagarId")
                .val(
                    conta.id
                );


            // Fornecedor
            $("#fornecedor_id")
                .val(
                    String(
                        conta.fornecedor_id
                        || ""
                    )
                );


            // Forma de pagamento
            $("#forma_pagamento_id")
                .val(
                    String(
                        conta.forma_pagamento_id
                        || ""
                    )
                );


            // Filial
            $("#filial_id_conta")
                .val(
                    conta.filial_id
                        ? String(
                            conta.filial_id
                        )
                        : ""
                );


            /*
             * Depois da filial estar definida,
             * carregar os centros dessa filial.
             */
            await carregarCentrosCustoContaPagar(
                conta.filial_id
                || null
            );


            $("#centro_custo_id")
                .val(
                    conta.centro_custo_id
                        ? String(
                            conta.centro_custo_id
                        )
                        : ""
                );


            $("#numero_documento")
                .val(
                    conta.numero_documento
                    || ""
                );


            $("#referencia")
                .val(
                    conta.referencia
                    || ""
                );


            $("#descricao")
                .val(
                    conta.descricao
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


            $("#observacoes")
                .val(
                    conta.observacoes
                    || ""
                );


            $("#modalContaPagar")
                .modal("show");


        } catch (erro) {

            mensagemErroContaPagar(
                erro.message
            );
        }
    }


    // ==================================================
    // ABRIR PAGAMENTO
    // ==================================================

    async function abrirPagamentoConta(
        id
    ) {
        try {

            const resposta =
                await requisicaoContaPagar(
                    CONTA_PAGAR_URL
                    + "?acao=obter&id="
                    + encodeURIComponent(id)
                );

            const conta =
                resposta.data;

            if (!conta) {

                throw new Error(
                    "Conta a pagar não encontrada."
                );

            }

            const saldo =
                Number(
                    conta.saldo || 0
                );

            if (saldo <= 0) {

                throw new Error(
                    "Esta conta já está totalmente paga."
                );

            }

            contaPagamentoAtual =
                conta;

            $("#pagamento_conta_id")
                .val(
                    conta.id
                );

            $("#pagamento_centro_custo_id")
                .val(
                    conta.centro_custo_id
                    || ""
                );

            $("#pagamento_filial_id")
                .val(
                    conta.filial_id
                    || ""
                );

            $("#pagamento_fornecedor")
                .text(
                    conta.fornecedor
                    || "-"
                );

            $("#pagamento_saldo")
                .text(
                    formatarMoedaContaPagar(
                        saldo
                    )
                );

            $("#pagamento_centro_nome")
                .text(
                    conta.centro_custo
                    || "Sem centro de custo"
                );

            $("#valor_pago")
                .val(
                    saldo.toFixed(2)
                )
                .attr(
                    "max",
                    saldo.toFixed(2)
                );

            $("#pagamento_data")
                .val(
                    dataHojeContaPagar()
                );

            $("#pagamento_forma_id")
                .val(
                    conta.forma_pagamento_id
                    || ""
                );

            $("#pagamento_documento")
                .val(
                    conta.numero_documento
                    || ""
                );

            $("#pagamento_referencia")
                .val(
                    conta.referencia
                    || ""
                );

            $("#pagamento_origem")
                .val("")
                .trigger("change");

            await Promise.allSettled([
                carregarCaixasPagamento(),
                carregarBancosPagamento()
            ]);

            $("#modalPagamento")
                .modal("show");

        } catch (erro) {

            mensagemErroContaPagar(
                erro.message
            );

        }
    }


    // ==================================================
    // ALTERAR ORIGEM DO PAGAMENTO
    // ==================================================

    function alterarOrigemPagamento() {

        const origem =
            String(
                $("#pagamento_origem")
                    .val()
                || ""
            ).toUpperCase();

        const grupoCaixa =
            $("#pagamento_caixa_id")
                .closest(
                    ".form-group"
                );

        const grupoBanco =
            $("#grupoPagamentoBanco");

        if (origem === "CAIXA") {

            grupoCaixa.show();

            grupoBanco.hide();

            $("#pagamento_caixa_id")
                .prop(
                    "required",
                    true
                );

            $("#pagamento_banco_id")
                .prop(
                    "required",
                    false
                )
                .val("");

        } else if (origem === "BANCO") {

            grupoCaixa.hide();

            grupoBanco.show();

            $("#pagamento_caixa_id")
                .prop(
                    "required",
                    false
                )
                .val("");

            $("#pagamento_banco_id")
                .prop(
                    "required",
                    true
                );

        } else {

            grupoCaixa.hide();

            grupoBanco.hide();

            $("#pagamento_caixa_id")
                .prop(
                    "required",
                    false
                )
                .val("");

            $("#pagamento_banco_id")
                .prop(
                    "required",
                    false
                )
                .val("");

        }
    }


    // ==================================================
    // CONFIRMAR PAGAMENTO
    // ==================================================

    async function confirmarPagamentoConta(
        evento
    ) {
        evento.preventDefault();

        if (!contaPagamentoAtual) {

            mensagemErroContaPagar(
                "Conta a pagar não selecionada."
            );

            return;
        }

        const valor =
            Number(
                $("#valor_pago").val()
                || 0
            );

        const saldo =
            Number(
                contaPagamentoAtual.saldo
                || 0
            );

        if (valor <= 0) {

            mensagemAvisoContaPagar(
                "O valor do pagamento deve ser superior a zero."
            );

            return;
        }

        if (valor > saldo) {

            mensagemAvisoContaPagar(
                "O valor do pagamento não pode ser superior ao saldo pendente de "
                + formatarMoedaContaPagar(
                    saldo
                )
                + "."
            );

            return;
        }

        const origem =
            String(
                $("#pagamento_origem")
                    .val()
                || ""
            ).toUpperCase();

        if (
            ![
                "CAIXA",
                "BANCO"
            ].includes(origem)
        ) {

            mensagemAvisoContaPagar(
                "Selecione a origem do dinheiro."
            );

            return;
        }

        /*
         * Validação visual do saldo da origem.
         * O servidor fará uma segunda validação com FOR UPDATE.
         */

        let saldoOrigem = 0;

        if (origem === "CAIXA") {

            const opcao =
                $("#pagamento_caixa_id option:selected");

            if (!opcao.val()) {

                mensagemAvisoContaPagar(
                    "Selecione o caixa."
                );

                return;
            }

            saldoOrigem =
                Number(
                    opcao.data("saldo")
                    || 0
                );

        } else {

            const opcao =
                $("#pagamento_banco_id option:selected");

            if (!opcao.val()) {

                mensagemAvisoContaPagar(
                    "Selecione o banco."
                );

                return;
            }

            saldoOrigem =
                Number(
                    opcao.data("saldo")
                    || 0
                );

        }

        if (saldoOrigem < valor) {

            mensagemAvisoContaPagar(
                "A origem selecionada não possui saldo suficiente."
            );

            return;
        }

        const confirmacao =
            await Swal.fire({

                icon:
                    "question",

                title:
                    "Confirmar pagamento?",

                html:
                    `
                        <p>
                            Conta:
                            <strong>
                                #${contaPagamentoAtual.id}
                            </strong>
                        </p>

                        <p>
                            Fornecedor:
                            <strong>
                                ${escaparHtmlConta(
                        contaPagamentoAtual.fornecedor
                        || "-"
                    )}
                            </strong>
                        </p>

                        <p>
                            Valor:
                            <strong class="text-danger">
                                ${formatarMoedaContaPagar(valor)}
                            </strong>
                        </p>

                        <p>
                            Origem:
                            <strong>
                                ${origem === "CAIXA"
                        ? "Caixa"
                        : "Banco"}
                            </strong>
                        </p>
                    `,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Confirmar pagamento",

                cancelButtonText:
                    "Cancelar",

                confirmButtonColor:
                    "#28a745"

            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        const botao =
            $("#btnConfirmarPagamento");

        bloquearBotaoContaPagar(
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
                "pagar"
            );

            const resposta =
                await requisicaoContaPagar(
                    CONTA_PAGAR_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalPagamento")
                .modal("hide");

            limparFormularioPagamento();

            recarregarTabelaContasPagar();

            /*
             * Recarrega saldos para um próximo pagamento.
             */

            await Promise.allSettled([
                carregarCaixasPagamento(),
                carregarBancosPagamento()
            ]);

            await Swal.fire({

                icon:
                    "success",

                title:
                    "Pagamento concluído",

                text:
                    resposta.mensagem
                    || "O pagamento foi registado com sucesso.",

                confirmButtonText:
                    "Fechar"

            });

        } catch (erro) {

            mensagemErroContaPagar(
                erro.message
            );

        } finally {

            restaurarBotaoContaPagar(
                botao,
                `
                    <i class="fa fa-check"></i>
                    Confirmar Pagamento
                `
            );

        }
    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    function abrirEliminarContaPagar(
        id
    ) {
        $("#eliminarPagarId")
            .val(id);

        $("#modalEliminarPagar")
            .modal("show");
    }


    async function eliminarContaPagar(
        evento
    ) {
        evento.preventDefault();

        const botao =
            $(evento.currentTarget)
                .find(
                    'button[type="submit"]'
                );

        bloquearBotaoContaPagar(
            botao,
            "A eliminar..."
        );

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                "eliminar"
            );

            dados.append(
                "id",
                $("#eliminarPagarId")
                    .val()
            );

            const resposta =
                await requisicaoContaPagar(
                    CONTA_PAGAR_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalEliminarPagar")
                .modal("hide");

            recarregarTabelaContasPagar();

            toastContaPagar(
                resposta.mensagem
                || "Conta eliminada com sucesso."
            );

        } catch (erro) {

            mensagemErroContaPagar(
                erro.message
            );

        } finally {

            restaurarBotaoContaPagar(
                botao,
                `
                    <i class="fa fa-trash"></i>
                    Eliminar
                `
            );

        }
    }


    // ==================================================
    // LIMPAR FORMULÁRIOS
    // ==================================================

    function limparFormularioContaPagar() {

        const formulario =
            document.getElementById(
                "formContaPagar"
            );

        if (formulario) {
            formulario.reset();
        }

        $("#contaPagarId")
            .val("");

        $("#desconto")
            .val("0.00");

        $("#juros")
            .val("0.00");

        $("#multa")
            .val("0.00");

        definirDatasPadraoContaPagar();
    }


    function limparFormularioPagamento() {
        const formulario = document.getElementById("formPagamento");
        if (formulario) {
            formulario.reset();
        }

        contaPagamentoAtual = null;

        $("#pagamento_conta_id").val("");
        $("#pagamento_centro_custo_id").val("");
        $("#pagamento_filial_id").val("");
        $("#pagamento_fornecedor").text("-");
        $("#pagamento_saldo").text(formatarMoedaContaPagar(0));
        $("#pagamento_centro_nome").text("-");
        $("#valor_pago").removeAttr("max");
        $("#pagamento_data").val(dataHojeContaPagar());
        $("#pagamento_origem").val("").trigger("change");
        $("#pagamento_comprovativo").val(""); // Limpa o input de comprovativo
    }


    // ==================================================
    // DATAS
    // ==================================================

    function definirDatasPadraoContaPagar() {

        const hoje =
            dataHojeContaPagar();

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
            !$("#pagamento_data").val()
        ) {

            $("#pagamento_data")
                .val(hoje);

        }
    }


    function dataHojeContaPagar() {

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


    function formatarDataContaPagar(
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

    function renderStatusContaPagar(
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
            normalizado === "pago"
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
            [
                "pendente",
                "aberto"
            ].includes(normalizado)
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
                ${escaparHtmlConta(
            texto || "-"
        )}
            </span>
        `;
    }


    // ==================================================
    // MOEDA
    // ==================================================

    function formatarMoedaContaPagar(
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

    function mensagemErroContaPagar(
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


    function mensagemAvisoContaPagar(
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


    function toastContaPagar(
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

    function bloquearBotaoContaPagar(
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


    function restaurarBotaoContaPagar(
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

    function escaparHtmlConta(
        valor
    ) {
        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();
    }

})(jQuery);