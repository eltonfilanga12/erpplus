"use strict";

(function ($) {

    // ==================================================
    // URLs DO MÓDULO
    // ==================================================

    const VENDA_MODULO_URL =
        "/erpplus/app/ajax/venda_ajax.php";

    const VENDA_CENTRO_URL =
        "/erpplus/app/ajax/centro_custo_ajax.php";

    const VENDA_FILIAL_URL =
        "/erpplus/app/ajax/filial_ajax.php";

    const VENDA_FORMA_URL =
        "/erpplus/app/ajax/forma_pagamento_ajax.php";

    const VENDA_CAIXA_URL =
        "/erpplus/app/ajax/caixa_ajax.php";

    const VENDA_BANCO_URL =
        "/erpplus/app/ajax/banco_ajax.php";


    let tabelaVendas = null;

    let itensVenda = [];

    let itensVendaRapida = [];

    let numeroVendaVisualizado = false;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(async function () {

        inicializarTabelaVendas();

        configurarEventosVenda();

        await Promise.allSettled([
            carregarClientesVenda(),
            carregarArmazensVenda(),
            carregarProdutosVenda(),
            carregarFiliaisVenda(),
            carregarFormasPagamentoVenda(),
            carregarStatusVenda(),
            carregarCaixasVenda(),
            carregarBancosVenda()
        ]);

        await carregarCentrosVenda(
            $("#venda_filial_id").val()
        );

        definirDatasPadraoVenda();

        atualizarCondicaoPagamentoVenda();

        mostrarItensVenda();

    });

    function atualizarDestinoRecebimentoVenda() {
        const condicao =
            String(
                $("#venda_condicao_pagamento").val()
                || "APRAZO"
            ).toUpperCase();

        const destino =
            String(
                $("#venda_destino_recebimento").val()
                || ""
            ).toUpperCase();

        const grupoDestino =
            $("#grupoVendaDestino");

        const grupoCaixa =
            $("#grupoVendaCaixa");

        const grupoBanco =
            $("#grupoVendaBanco");

        const campoDestino =
            $("#venda_destino_recebimento");

        const campoCaixa =
            $("#venda_caixa_id");

        const campoBanco =
            $("#venda_banco_id");

        if (condicao !== "AVISTA") {
            grupoDestino.hide();
            grupoCaixa.hide();
            grupoBanco.hide();

            campoDestino
                .prop("required", false)
                .val("");

            campoCaixa
                .prop("required", false)
                .val("");

            campoBanco
                .prop("required", false)
                .val("");

            return;
        }

        grupoDestino.show();

        campoDestino.prop(
            "required",
            true
        );

        if (destino === "CAIXA") {
            grupoCaixa.show();
            grupoBanco.hide();

            campoCaixa.prop(
                "required",
                true
            );

            campoBanco
                .prop("required", false)
                .val("");

        } else if (destino === "BANCO") {
            grupoCaixa.hide();
            grupoBanco.show();

            campoCaixa
                .prop("required", false)
                .val("");

            campoBanco.prop(
                "required",
                true
            );

        } else {
            grupoCaixa.hide();
            grupoBanco.hide();

            campoCaixa.prop(
                "required",
                false
            );

            campoBanco.prop(
                "required",
                false
            );
        }
    }
    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosVenda() {

        $("#formVenda").on(
            "submit",
            guardarVenda
        );


        $("#formEliminarVenda").on(
            "submit",
            eliminarVenda
        );

        $("#venda_tipo_parcelamento").on(
            "change",
            function () {

                atualizarTipoParcelamentoVenda();

                if (
                    String(
                        $(this).val()
                        || "AUTOMATICO"
                    ).toUpperCase()
                    === "AUTOMATICO"
                ) {
                    atualizarValoresParcelasVenda();
                }
            }
        );

        $(document).on(
            "click",
            ".btn-imprimir-factura-recibo",
            function () {
                const id = Number($(this).attr("data-id") || 0);

                if (!Number.isInteger(id) || id <= 0) {
                    Swal.fire(
                        "Erro",
                        "Identificador da Factura-Recibo inválido.",
                        "error"
                    );
                    return;
                }

                const url =
                    "/erpplus/app/ajax/documento_factura_recibo_pdf.php?id="
                    + encodeURIComponent(id);

                window.open(url, "_blank");
            }
        );

        $(document).on("click", ".btn-imprimir-factura", function () {
            const id = Number($(this).attr("data-id") || 0);

            if (!Number.isInteger(id) || id <= 0) {
                alert("Identificador da factura inválido.");
                return;
            }

            const url =
                "/erpplus/app/ajax/documento_factura_pdf.php?id=" +
                encodeURIComponent(id);

            window.open(url, "_blank");
        });

        $("#vendaAdicionarProduto").on(
            "click",
            adicionarProdutoVenda
        );

        $("#vendaRapidaImpostoHabilitado").on(
            "change",
            function () {

                atualizarTotalVendaRapida();
            }
        );

        $("#venda_numero_parcelas").on(
            "change",
            function () {

                atualizarCamposParcelasVenda();

                atualizarValoresParcelasVenda();
            }
        );


        $("#venda_condicao_pagamento").on(
            "change",
            function () {
                atualizarCondicaoPagamentoVenda();
                atualizarDestinoRecebimentoVenda();
            }
        );

        $("#venda_destino_recebimento").on(
            "change",
            atualizarDestinoRecebimentoVenda
        );


        $("#venda_filial_id").on(
            "change",
            function () {

                carregarCentrosVenda(
                    $(this).val()
                );

            }
        );

        $("#vendaRapidaArmazem").on(
            "change",
            function () {

                definirCentroReceitaVendaRapida();

                filtrarCaixasVendaRapidaPorFilial();
            }
        );

        $("#venda_tipo_documento").on(
            "change",
            async function () {

                if (!$("#vendaId").val()) {
                    numeroVendaVisualizado = false;

                    await visualizarProximoNumeroVenda(
                        true
                    );
                }

            }
        );

        $("#venda_data").on(
            "change",
            async function () {

                atualizarCondicaoPagamentoVenda();

                /*
                 * Alterar a data pode alterar o ano da série.
                 */

                if (
                    !$("#vendaId").val()
                ) {

                    await visualizarProximoNumeroVenda(
                        true
                    );

                }

            }
        );


        $("#venda_desconto").on(
            "input",
            atualizarTotaisVenda
        );


        $("#venda_imposto_habilitado").on(
            "change",
            atualizarTotaisVenda
        );


        $("#venda_produto_id").on(
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

                $("#venda_preco_unitario")
                    .val(
                        preco.toFixed(2)
                    );

            }
        );


        /*
         * Ao abrir uma nova venda, reserva o número.
         */

        $("#modalVenda").on(
            "shown.bs.modal",
            async function () {

                if (
                    !$("#vendaId").val()
                    && !numeroVendaVisualizado
                ) {

                    await visualizarProximoNumeroVenda();

                }

            }
        );

        $("#modalVenda").on(
            "hidden.bs.modal",
            function () {

                limparFormularioVenda();

            }
        );


        $(document).on(
            "click",
            ".btn-editar-venda",
            function () {

                editarVenda(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-eliminar-venda",
            function () {

                abrirEliminarVenda(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-remover-item-venda",
            function () {

                removerItemVenda(
                    Number(
                        $(this).data("index")
                    )
                );

            }
        );

        $("#btnVendaRapida").on(
            "click",
            abrirVendaRapida
        );


        $("#btnAdicionarProdutoVendaRapida").on(
            "click",
            adicionarProdutoVendaRapida
        );


        $("#formVendaRapida").on(
            "submit",
            guardarVendaRapida
        );


        $("#vendaRapidaProduto").on(
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

                $("#vendaRapidaPreco")
                    .val(
                        preco.toFixed(2)
                    );
            }
        );


        $("#vendaRapidaDestino").on(
            "change",
            atualizarDestinoVendaRapida
        );


        $(document).on(
            "click",
            ".btn-remover-venda-rapida",
            function () {

                removerProdutoVendaRapida(
                    Number(
                        $(this).data("index")
                    )
                );
            }
        );

    }

    function atualizarCamposParcelasVenda() {

        const condicao =
            String(
                $("#venda_condicao_pagamento").val()
                || ""
            ).toUpperCase();


        const quantidade =
            Math.max(
                1,
                Math.min(
                    3,
                    Number(
                        $("#venda_numero_parcelas").val()
                        || 1
                    )
                )
            );


        const parcela1 =
            $("#venda_parcela_1_data");

        const parcela2 =
            $("#venda_parcela_2_data");

        const parcela3 =
            $("#venda_parcela_3_data");


        /*
        |--------------------------------------------------------------------------
        | VENDA À VISTA
        |--------------------------------------------------------------------------
        */

        if (condicao !== "APRAZO") {

            $("#grupoVendaParcelas")
                .hide();

            $("#grupoVendaPlanoParcelas")
                .hide();


            /*
             * MUITO IMPORTANTE:
             * campos escondidos não podem continuar required.
             */

            $("#grupoVendaTipoParcelamento")
                .hide();

            $("#venda_tipo_parcelamento")
                .val("AUTOMATICO");

            parcela1
                .prop("required", false)
                .val("");

            parcela2
                .prop("required", false)
                .val("");

            parcela3
                .prop("required", false)
                .val("");


            $("#venda_numero_parcelas")
                .val("1");


            $("#venda_parcela_1_valor")
                .val("");

            $("#venda_parcela_2_valor")
                .val("");

            $("#venda_parcela_3_valor")
                .val("");


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | VENDA A PRAZO
        |--------------------------------------------------------------------------
        */

        $("#grupoVendaParcelas")
            .show();

        $("#grupoVendaPlanoParcelas")
            .show();


        $("#grupoVendaParcela1")
            .show();


        $("#grupoVendaParcela2")
            .toggle(
                quantidade >= 2
            );


        $("#grupoVendaParcela3")
            .toggle(
                quantidade >= 3
            );


        /*
        |--------------------------------------------------------------------------
        | REQUIRED CONFORME QUANTIDADE DE PARCELAS
        |--------------------------------------------------------------------------
        */

        parcela1
            .prop(
                "required",
                true
            );


        parcela2
            .prop(
                "required",
                quantidade >= 2
            );


        parcela3
            .prop(
                "required",
                quantidade >= 3
            );

        /*
        |--------------------------------------------------------------------------
        | LIMPAR PARCELAS NÃO UTILIZADAS
        |--------------------------------------------------------------------------
        */

        if (quantidade < 2) {

            parcela2.val("");

            $("#venda_parcela_2_valor")
                .val("");
        }


        if (quantidade < 3) {

            parcela3.val("");

            $("#venda_parcela_3_valor")
                .val("");
        }

        atualizarTipoParcelamentoVenda();

    }

    function validarValoresParcelasVenda() {

        const condicao =
            String(
                $("#venda_condicao_pagamento").val()
                || ""
            ).toUpperCase();


        if (condicao !== "APRAZO") {
            return;
        }


        const quantidade =
            Math.max(
                1,
                Math.min(
                    3,
                    Number(
                        $("#venda_numero_parcelas").val()
                        || 1
                    )
                )
            );


        const tipo =
            String(
                $("#venda_tipo_parcelamento").val()
                || "AUTOMATICO"
            ).toUpperCase();


        /*
         * Para automático o próprio sistema calcula.
         */

        if (tipo !== "PERSONALIZADO") {
            return;
        }


        let somaCentavos = 0;


        for (
            let numero = 1;
            numero <= quantidade;
            numero++
        ) {

            const valor =
                Number(
                    $(
                        "#venda_parcela_"
                        + numero
                        + "_valor"
                    ).val()
                    || 0
                );


            if (valor <= 0) {

                throw new Error(
                    "Informe um valor válido para a "
                    + numero
                    + "ª parcela."
                );
            }


            somaCentavos +=
                Math.round(
                    valor * 100
                );
        }


        const totalCentavos =
            Math.round(
                Number(
                    $("#venda_total").val()
                    || 0
                ) * 100
            );


        if (
            somaCentavos
            !== totalCentavos
        ) {

            const soma =
                somaCentavos / 100;

            const total =
                totalCentavos / 100;


            throw new Error(
                "A soma das parcelas é "
                + soma.toFixed(2)
                + " MT, mas o total da venda é "
                + total.toFixed(2)
                + " MT."
            );
        }
    }

    function atualizarValoresParcelasVenda() {

        const condicao =
            String(
                $("#venda_condicao_pagamento").val()
                || ""
            ).toUpperCase();


        const tipoParcelamento =
            String(
                $("#venda_tipo_parcelamento").val()
                || "AUTOMATICO"
            ).toUpperCase();


        if (condicao !== "APRAZO") {

            $("#venda_parcela_1_valor")
                .val("");

            $("#venda_parcela_2_valor")
                .val("");

            $("#venda_parcela_3_valor")
                .val("");

            return;
        }
        /*
         * No personalizado não recalculamos.
         */

        if (
            tipoParcelamento
            === "PERSONALIZADO"
        ) {
            return;
        }


        const total =
            Number(
                $("#venda_total").val()
                || 0
            );


        const quantidade =
            Math.max(
                1,
                Math.min(
                    3,
                    Number(
                        $("#venda_numero_parcelas").val()
                        || 1
                    )
                )
            );


        /*
         * Trabalhar em centavos evita problemas
         * de arredondamento.
         */

        const totalCentavos =
            Math.round(
                total * 100
            );


        const base =
            Math.floor(
                totalCentavos
                / quantidade
            );


        const resto =
            totalCentavos
            - (
                base
                * quantidade
            );


        const valores = [];


        for (
            let i = 0;
            i < quantidade;
            i++
        ) {

            /*
             * A última parcela absorve
             * eventual diferença de centavos.
             */

            const centavos =
                i === quantidade - 1
                    ? base + resto
                    : base;


            valores.push(
                centavos / 100
            );
        }


        $("#venda_parcela_1_valor")
            .val(
                valores[0] !== undefined
                    ? valores[0].toFixed(2)
                    : ""
            );


        $("#venda_parcela_2_valor")
            .val(
                valores[1] !== undefined
                    ? valores[1].toFixed(2)
                    : ""
            );


        $("#venda_parcela_3_valor")
            .val(
                valores[2] !== undefined
                    ? valores[2].toFixed(2)
                    : ""
            );
    }

    function abrirVendaRapida() {

        itensVendaRapida = [];

        /*
        |--------------------------------------------------------------------------
        | CLIENTES
        |--------------------------------------------------------------------------
        */

        copiarOpcoesSelectVendaRapida(
            "#venda_cliente_id",
            "#vendaRapidaCliente"
        );


        /*
        |--------------------------------------------------------------------------
        | ARMAZÉNS
        |--------------------------------------------------------------------------
        */

        copiarOpcoesSelectVendaRapida(
            "#venda_armazem_id",
            "#vendaRapidaArmazem"
        );


        /*
        |--------------------------------------------------------------------------
        | FORMAS DE PAGAMENTO
        |--------------------------------------------------------------------------
        */

        copiarOpcoesSelectVendaRapida(
            "#venda_forma_pagamento_id",
            "#vendaRapidaFormaPagamento"
        );

        $("#vendaRapidaImpostoHabilitado")
            .prop(
                "checked",
                true
            );

        /*
        |--------------------------------------------------------------------------
        | CAIXAS
        |--------------------------------------------------------------------------
        */

        copiarOpcoesSelectVendaRapida(
            "#venda_caixa_id",
            "#vendaRapidaCaixa"
        );


        /*
        |--------------------------------------------------------------------------
        | BANCOS
        |--------------------------------------------------------------------------
        */

        copiarOpcoesSelectVendaRapida(
            "#venda_banco_id",
            "#vendaRapidaBanco"
        );


        seleccionarConsumidorFinalVendaRapida();

        seleccionarPrimeiroValorVendaRapida(
            "#vendaRapidaArmazem"
        );

        seleccionarPrimeiroValorVendaRapida(
            "#vendaRapidaFormaPagamento"
        );

        seleccionarPrimeiroValorVendaRapida(
            "#vendaRapidaCaixa"
        );


        definirCentroReceitaVendaRapida();

        definirStatusVendaRapida();


        $("#vendaRapidaDestino")
            .val("CAIXA");


        $("#vendaRapidaQuantidade")
            .val("1");


        $("#vendaRapidaPreco")
            .val("");


        atualizarDestinoVendaRapida();

        mostrarItensVendaRapida();

        atualizarTotalVendaRapida();


        $("#modalVendaRapida")
            .modal("show");
    }

    function definirCentroReceitaVendaRapida() {

        const origem =
            $("#venda_centro_custo_id");

        const destino =
            $("#vendaRapidaCentroReceita");

        const opcaoArmazem =
            $("#vendaRapidaArmazem")
                .find("option:selected");

        const filialArmazemId =
            Number(
                opcaoArmazem.data("filial-id")
                || 0
            );

        destino.empty();

        destino.append(
            $("<option>", {
                value: "",
                text: "Seleccione o centro de receita"
            })
        );

        const centrosCompativeis = [];

        origem
            .find("option[value!='']")
            .each(function () {

                const opcao =
                    $(this);

                const filialCentroId =
                    Number(
                        opcao.data("filial-id")
                        || 0
                    );

                /*
                 * Aceitar:
                 * - centro global, filial_id NULL;
                 * - centro da mesma filial do armazém.
                 */
                if (
                    filialCentroId > 0
                    && filialArmazemId > 0
                    && filialCentroId
                    !== filialArmazemId
                ) {
                    return;
                }

                const novaOpcao =
                    $("<option>")
                        .val(
                            opcao.val()
                        )
                        .text(
                            opcao.text()
                        )
                        .attr(
                            "data-filial-id",
                            filialCentroId > 0
                                ? filialCentroId
                                : ""
                        );

                destino.append(
                    novaOpcao
                );

                centrosCompativeis.push({
                    id:
                        opcao.val(),

                    texto:
                        normalizarTextoVendaRapida(
                            opcao.text()
                        ),

                    filial_id:
                        filialCentroId
                });
            });

        /*
         * Prioridade:
         * 1. Centro da própria filial;
         * 2. Centro global;
         * 3. Nome relacionado com venda/receita.
         */
        let centroSelecionado =
            centrosCompativeis.find(
                function (centro) {

                    return (
                        centro.filial_id
                        === filialArmazemId
                        && (
                            centro.texto.includes("venda")
                            || centro.texto.includes("balcao")
                            || centro.texto.includes("receita")
                        )
                    );
                }
            );

        if (!centroSelecionado) {

            centroSelecionado =
                centrosCompativeis.find(
                    function (centro) {

                        return (
                            centro.texto.includes("venda")
                            || centro.texto.includes("balcao")
                            || centro.texto.includes("receita")
                        );
                    }
                );
        }

        if (!centroSelecionado) {

            centroSelecionado =
                centrosCompativeis[0]
                || null;
        }

        destino.val(
            centroSelecionado
                ? centroSelecionado.id
                : ""
        );
    }

    function copiarOpcoesSelectVendaRapida(
        origem,
        destino
    ) {

        const selectOrigem =
            $(origem);

        const selectDestino =
            $(destino);

        selectDestino.empty();

        selectOrigem
            .find("option")
            .each(
                function () {

                    const original =
                        $(this);

                    const nova =
                        $("<option>")
                            .val(
                                original.val()
                            )
                            .text(
                                original.text()
                            );


                    $.each(
                        this.attributes,
                        function () {

                            if (
                                this.name.startsWith(
                                    "data-"
                                )
                            ) {
                                nova.attr(
                                    this.name,
                                    this.value
                                );
                            }
                        }
                    );


                    selectDestino.append(
                        nova
                    );
                }
            );
    }

    function seleccionarConsumidorFinalVendaRapida() {

        const select =
            $("#vendaRapidaCliente");

        let encontrado = null;


        select
            .find("option")
            .each(
                function () {

                    const texto =
                        normalizarTextoVendaRapida(
                            $(this).text()
                        );

                    if (
                        texto.includes(
                            "consumidor final"
                        )
                        || texto.includes(
                            "cliente balcao"
                        )
                    ) {

                        encontrado =
                            $(this).val();

                        return false;
                    }
                }
            );


        if (encontrado) {

            select.val(
                encontrado
            );

            return;
        }


        select.val("");
    }

    function seleccionarPrimeiroValorVendaRapida(
        seletor
    ) {

        const select =
            $(seletor);

        const primeira =
            select
                .find(
                    "option[value!='']"
                )
                .first();


        if (primeira.length) {

            select.val(
                primeira.val()
            );
        }
    }

    function definirStatusVendaRapida() {

        let statusId = "";


        $("#venda_status_id")
            .find("option")
            .each(
                function () {

                    const texto =
                        normalizarTextoVendaRapida(
                            $(this).text()
                        );


                    if (
                        texto === "confirmada"
                        || texto === "confirmado"
                    ) {

                        statusId =
                            $(this).val();

                        return false;
                    }
                }
            );


        $("#vendaRapidaStatus")
            .val(
                statusId
            );
    }

    function atualizarDestinoVendaRapida() {

        const destino =
            String(
                $("#vendaRapidaDestino")
                    .val()
                || ""
            ).toUpperCase();


        if (destino === "BANCO") {

            $("#grupoVendaRapidaCaixa")
                .hide();

            $("#grupoVendaRapidaBanco")
                .show();

            return;
        }


        $("#grupoVendaRapidaBanco")
            .hide();

        $("#grupoVendaRapidaCaixa")
            .show();
    }

    function adicionarProdutoVendaRapida() {

        const selectProduto =
            $("#vendaRapidaProduto");


        const produtoId =
            Number(
                selectProduto.val()
                || 0
            );


        const quantidade =
            Number(
                $("#vendaRapidaQuantidade")
                    .val()
                || 0
            );


        const preco =
            Number(
                $("#vendaRapidaPreco")
                    .val()
                || 0
            );


        if (produtoId <= 0) {

            mensagemAvisoVenda(
                "Seleccione um produto."
            );

            return;
        }


        if (quantidade <= 0) {

            mensagemAvisoVenda(
                "Informe uma quantidade válida."
            );

            return;
        }


        if (preco < 0) {

            mensagemAvisoVenda(
                "Informe um preço válido."
            );

            return;
        }


        const nomeProduto =
            selectProduto
                .find(
                    "option:selected"
                )
                .text()
                .trim();


        const existente =
            itensVendaRapida.find(
                function (item) {

                    return (
                        Number(
                            item.produto_id
                        )
                        === produtoId
                    );
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
                existente.quantidade
                * preco;

        } else {

            itensVendaRapida.push({

                produto_id:
                    produtoId,

                produto_nome:
                    nomeProduto,

                quantidade:
                    quantidade,

                preco_unitario:
                    preco,

                subtotal:
                    quantidade * preco

            });
        }


        /*
        |--------------------------------------------------------------------------
        | Limpar campos para o próximo produto
        |--------------------------------------------------------------------------
        */

        selectProduto.val("");


        $("#vendaRapidaQuantidade")
            .val("1");


        $("#vendaRapidaPreco")
            .val("");


        /*
        |--------------------------------------------------------------------------
        | Atualizar tabela e total
        |--------------------------------------------------------------------------
        */

        mostrarItensVendaRapida();
    }

    function mostrarItensVendaRapida() {

        const tbody =
            $("#vendaRapidaListaItens");


        tbody.empty();


        if (
            itensVendaRapida.length === 0
        ) {

            tbody.html(`
            <tr>
                <td
                    colspan="5"
                    class="text-center text-muted"
                >
                    Nenhum produto adicionado.
                </td>
            </tr>
        `);

            atualizarTotalVendaRapida();

            return;
        }


        itensVendaRapida.forEach(
            function (
                item,
                index
            ) {

                tbody.append(`
                <tr>

                    <td>
                        ${escaparHtmlVenda(
                    item.produto_nome
                )}
                    </td>

                    <td>
                        ${Number(
                    item.quantidade
                ).toLocaleString(
                    "pt-MZ"
                )}
                    </td>

                    <td class="text-right">
                        ${formatarMoedaVenda(
                    item.preco_unitario
                )}
                    </td>

                    <td class="text-right font-weight-bold">
                        ${formatarMoedaVenda(
                    item.subtotal
                )}
                    </td>

                    <td class="text-center">

                        <button
                            type="button"
                            class="btn btn-sm btn-danger btn-remover-venda-rapida"
                            data-index="${index}">

                            <i class="fa fa-trash"></i>

                        </button>

                    </td>

                </tr>
            `);
            }
        );


        atualizarTotalVendaRapida();
    }

    function removerProdutoVendaRapida(
        index
    ) {

        if (
            index < 0
            || index >= itensVendaRapida.length
        ) {
            return;
        }


        itensVendaRapida.splice(
            index,
            1
        );


        mostrarItensVendaRapida();
    }


    // ==================================================
    // CALCULAR TOTAIS VENDA RÁPIDA
    // ==================================================

    function calcularTotaisVendaRapida() {

        const TAXA_IMPOSTO =
            16;


        const subtotal =
            itensVendaRapida.reduce(
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


        const impostoHabilitado =
            $("#vendaRapidaImpostoHabilitado")
                .is(":checked");


        const imposto =
            impostoHabilitado
                ? (
                    subtotal
                    * TAXA_IMPOSTO
                    / 100
                )
                : 0;


        const total =
            subtotal
            + imposto;


        return {

            subtotal:
                Number(
                    subtotal.toFixed(2)
                ),

            imposto:
                Number(
                    imposto.toFixed(2)
                ),

            total:
                Number(
                    total.toFixed(2)
                ),

            impostoHabilitado:
                impostoHabilitado,

            taxaImposto:
                impostoHabilitado
                    ? TAXA_IMPOSTO
                    : 0
        };
    }


    // ==================================================
    // OBTER TOTAL VENDA RÁPIDA
    // ==================================================

    function obterTotalVendaRapida() {

        return calcularTotaisVendaRapida()
            .total;
    }


    // ==================================================
    // ATUALIZAR TOTAIS VENDA RÁPIDA
    // ==================================================

    function atualizarTotalVendaRapida() {

        const totais =
            calcularTotaisVendaRapida();


        $("#vendaRapidaSubtotalTexto")
            .text(
                formatarMoedaVenda(
                    totais.subtotal
                )
            );


        $("#vendaRapidaImpostoTexto")
            .text(
                formatarMoedaVenda(
                    totais.imposto
                )
            );


        $("#vendaRapidaTotalTexto")
            .text(
                formatarMoedaVenda(
                    totais.total
                )
            );
    }

    function normalizarTextoVendaRapida(
        valor
    ) {

        return String(
            valor || ""
        )
            .trim()
            .toLowerCase()
            .normalize("NFD")
            .replace(
                /[\u0300-\u036f]/g,
                ""
            );
    }

    async function guardarVendaRapida(
        evento
    ) {

        evento.preventDefault();


        if (
            itensVendaRapida.length === 0
        ) {

            mensagemAvisoVenda(
                "Adicione pelo menos um produto."
            );

            return;
        }


        const clienteId =
            Number(
                $("#vendaRapidaCliente")
                    .val()
                || 0
            );


        if (clienteId <= 0) {

            mensagemAvisoVenda(
                "Cadastre ou seleccione o cliente Consumidor Final."
            );

            return;
        }


        const armazemId =
            Number(
                $("#vendaRapidaArmazem")
                    .val()
                || 0
            );


        if (armazemId <= 0) {

            mensagemAvisoVenda(
                "Seleccione o armazém."
            );

            return;
        }


        const centroId =
            Number(
                $("#vendaRapidaCentroReceita")
                    .val()
                || 0
            );


        if (centroId <= 0) {

            mensagemAvisoVenda(
                "Não existe um centro de receita disponível."
            );

            return;
        }

        const opcaoArmazem =
            $("#vendaRapidaArmazem")
                .find("option:selected");

        const filialArmazemId =
            Number(
                opcaoArmazem.data("filial-id")
                || 0
            );

        const filialFormularioId =
            Number(
                $("#venda_filial_id").val()
                || 0
            );

        /*
         * A filial do armazém tem prioridade.
         * Para utilizador de uma filial, os armazéns
         * já foram filtrados pelo backend.
         */
        const filialId =
            filialArmazemId > 0
                ? filialArmazemId
                : filialFormularioId;


        const formaPagamentoId =
            Number(
                $("#vendaRapidaFormaPagamento")
                    .val()
                || 0
            );


        if (formaPagamentoId <= 0) {

            mensagemAvisoVenda(
                "Seleccione a forma de pagamento."
            );

            return;
        }


        const statusId =
            Number(
                $("#vendaRapidaStatus")
                    .val()
                || 0
            );


        if (statusId <= 0) {

            mensagemAvisoVenda(
                'Não foi encontrado o status "Confirmada".'
            );

            return;
        }


        const destino =
            String(
                $("#vendaRapidaDestino")
                    .val()
                || ""
            ).toUpperCase();


        const caixaId =
            destino === "CAIXA"
                ? Number(
                    $("#vendaRapidaCaixa")
                        .val()
                    || 0
                )
                : 0;


        const bancoId =
            destino === "BANCO"
                ? Number(
                    $("#vendaRapidaBanco")
                        .val()
                    || 0
                )
                : 0;


        if (
            destino === "CAIXA"
            && caixaId <= 0
        ) {

            mensagemAvisoVenda(
                "Seleccione o caixa."
            );

            return;
        }


        if (
            destino === "BANCO"
            && bancoId <= 0
        ) {

            mensagemAvisoVenda(
                "Seleccione a conta bancária."
            );

            return;
        }


        const totais =
            calcularTotaisVendaRapida();


        if (totais.total <= 0) {

            mensagemAvisoVenda(
                "O total da venda deve ser superior a zero."
            );

            return;
        }


        const botao =
            $("#btnFinalizarVendaRapida");


        bloquearBotaoVenda(
            botao,
            "A finalizar..."
        );


        try {

            /*
            |--------------------------------------------------------------------------
            | Obter próximo número
            |--------------------------------------------------------------------------
            */

            const dataVenda =
                dataHoraLocalVenda();


            const consultaNumero =
                await requisicaoVenda(
                    VENDA_MODULO_URL
                    + "?acao=proximo_numero"
                    + "&tipo_documento=FACTURA_RECIBO"
                    + "&data_venda="
                    + encodeURIComponent(
                        dataVenda
                    )
                );


            const numeroFatura =
                consultaNumero.data?.numero
                || "";


            if (!numeroFatura) {

                throw new Error(
                    "Não foi possível gerar o número da venda."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Construir dados completos esperados pelo Model
            |--------------------------------------------------------------------------
            */

            const dados =
                new FormData();


            dados.append(
                "acao",
                "salvar"
            );


            dados.append(
                "cliente_id",
                String(clienteId)
            );


            dados.append(
                "armazem_id",
                String(armazemId)
            );

            dados.append(
                "filial_id",
                filialId > 0
                    ? String(filialId)
                    : ""
            );


            dados.append(
                "centro_custo_id",
                String(centroId)
            );


            dados.append(
                "forma_pagamento_id",
                String(
                    formaPagamentoId
                )
            );


            dados.append(
                "condicao_pagamento",
                "AVISTA"
            );

            dados.append(
                "numero_parcelas",
                "1"
            );


            dados.append(
                "tipo_parcelamento",
                "AUTOMATICO"
            );


            dados.append(
                "data_vencimento",
                ""
            );


            dados.append(
                "status_id",
                String(statusId)
            );


            dados.append(
                "numero_fatura",
                numeroFatura
            );


            dados.append(
                "tipo_documento",
                "FACTURA_RECIBO"
            );


            dados.append(
                "numero_documento",
                ""
            );


            dados.append(
                "data_venda",
                dataVenda
            );


            dados.append(
                "data_vencimento",
                ""
            );


            dados.append(
                "subtotal",
                totais.subtotal
                    .toFixed(2)
            );


            dados.append(
                "desconto",
                "0.00"
            );


            dados.append(
                "imposto_habilitado",
                totais.impostoHabilitado
                    ? "1"
                    : "0"
            );


            dados.append(
                "taxa_imposto",
                totais.taxaImposto
                    .toFixed(2)
            );


            dados.append(
                "imposto",
                totais.imposto
                    .toFixed(2)
            );


            dados.append(
                "total",
                totais.total
                    .toFixed(2)
            );

            dados.append(
                "origem",
                "BALCAO"
            );


            dados.append(
                "destino_recebimento",
                destino
            );


            dados.append(
                "caixa_id",
                caixaId > 0
                    ? String(caixaId)
                    : ""
            );


            dados.append(
                "banco_id",
                bancoId > 0
                    ? String(bancoId)
                    : ""
            );


            dados.append(
                "observacoes",
                "Venda rápida de balcão"
            );


            dados.append(
                "itens",
                JSON.stringify(
                    itensVendaRapida
                )
            );


            /*
            |--------------------------------------------------------------------------
            | Usar exactamente o mesmo endpoint da venda normal
            |--------------------------------------------------------------------------
            */

            const resposta =
                await requisicaoVenda(
                    VENDA_MODULO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );


            $("#modalVendaRapida")
                .modal("hide");


            itensVendaRapida = [];


            mostrarItensVendaRapida();


            recarregarTabelaVendas();


            await Swal.fire({

                icon:
                    "success",

                title:
                    "Venda concluída",

                text:
                    resposta.mensagem
                    || "Venda rápida registada com sucesso.",

                confirmButtonText:
                    "Fechar"

            });


        } catch (erro) {

            mensagemErroVenda(
                erro.message
            );

        } finally {

            botao
                .prop(
                    "disabled",
                    false
                )
                .html(`
                <i class="fa fa-check"></i>
                Finalizar Venda
            `);
        }
    }


    // ==================================================
    // REQUISIÇÃO PADRÃO
    // ==================================================

    async function requisicaoVenda(
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

    function inicializarTabelaVendas() {

        if (
            $.fn.DataTable.isDataTable(
                "#vendasTable"
            )
        ) {

            $("#vendasTable")
                .DataTable()
                .destroy();

        }

        tabelaVendas =
            $("#vendasTable")
                .DataTable({

                    ajax: {

                        url:
                            VENDA_MODULO_URL,

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

                                mensagemErroVenda(
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
                                "Erro ao listar vendas:",
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
                                "cliente_nome",

                            defaultContent:
                                "-"
                        },

                        {
                            data:
                                "centro_custo_nome",

                            defaultContent:
                                "Sem centro de receita",

                            render: function (
                                valor,
                                tipo,
                                row
                            ) {

                                const codigo =
                                    row.centro_custo_codigo
                                        ? `
                                            <small class="text-muted">
                                                ${escaparHtmlVenda(
                                            row.centro_custo_codigo
                                        )}
                                            </small>
                                            <br>
                                        `
                                        : "";

                                return `
                                    ${codigo}
                                    ${escaparHtmlVenda(
                                    valor
                                    || "Sem centro de receita"
                                )}
                                `;

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
                                "numero_fatura",

                            defaultContent:
                                "-"
                        },

                        {
                            data:
                                "data_venda",

                            render:
                                formatarDataHoraVenda
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

                                const condicao =
                                    String(
                                        row.condicao_pagamento
                                        || ""
                                    ).toUpperCase();

                                const vencida =
                                    condicao === "APRAZO"
                                    && data
                                    < dataHojeVenda();

                                const texto =
                                    formatarDataVenda(
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
                                "total",

                            className:
                                "text-right",

                            render:
                                formatarMoedaVenda
                        },

                        {
                            data:
                                "condicao_pagamento",

                            render:
                                renderCondicaoVenda
                        },

                        {
                            data:
                                "status_nome",

                            render:
                                renderStatusVenda
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
                                const id = Number(row.id || 0);

                                const status = String(row.status_nome || "")
                                    .trim()
                                    .toLowerCase();

                                const confirmada = status === "confirmada";

                                const tipoDocumento = String(row.tipo_documento || "")
                                    .trim()
                                    .toUpperCase();

                                const botaoImprimirFR =
                                    confirmada && tipoDocumento === "FACTURA_RECIBO"
                                        ? `
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-success btn-imprimir-factura-recibo"
                                                data-id="${row.id}"
                                                title="Imprimir Factura-Recibo">
                                                <i class="fa fa-print"></i>
                                            </button>
        `
                                        : "";

                                const botaoFactura =
                                    confirmada && tipoDocumento === "FACTURA"
                                        ? `
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary btn-imprimir-factura"
                                                data-id="${id}"
                                                title="Imprimir factura">
                                                <i class="fa fa-print"></i>
                                            </button>
                                        `
                                        : "";

                                return `
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-info btn-editar-venda"
                                        data-id="${id}"
                                        title="${confirmada ? "Ver venda" : "Editar venda"}">
                                        <i class="fa ${confirmada ? "fa-eye" : "fa-edit"}"></i>
                                    </button>

                                    ${botaoFactura}
                                    ${botaoImprimirFR}

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger btn-eliminar-venda"
                                        data-id="${id}"
                                        title="Eliminar"
                                        ${confirmada ? "disabled" : ""}>
                                        <i class="fa fa-trash"></i>
                                    </button>
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
                                "Lista de Vendas",

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
                                "Lista de Vendas",

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
                                "Lista de Vendas",

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

    function recarregarTabelaVendas() {

        if (tabelaVendas) {

            tabelaVendas.ajax.reload(
                null,
                false
            );

        }
    }


    // ==================================================
    // RESERVAR NÚMERO
    // ==================================================

    async function visualizarProximoNumeroVenda(
        forcarConsulta = false
    ) {
        if ($("#vendaId").val()) {
            return;
        }

        if (
            numeroVendaVisualizado
            && !forcarConsulta
        ) {
            return;
        }

        const campo =
            $("#venda_numero_fatura");

        const tipoDocumento =
            String(
                $("#venda_tipo_documento").val()
                || "FACTURA"
            );

        const dataVenda =
            String(
                $("#venda_data").val()
                || dataHoraLocalVenda()
            );

        campo.val(
            "A calcular..."
        );

        try {
            const resposta =
                await requisicaoVenda(
                    VENDA_MODULO_URL
                    + "?acao=proximo_numero"
                    + "&tipo_documento="
                    + encodeURIComponent(
                        tipoDocumento
                    )
                    + "&data_venda="
                    + encodeURIComponent(
                        dataVenda
                    )
                );

            const numero =
                resposta.data?.numero
                || "";

            if (!numero) {
                throw new Error(
                    "Não foi possível calcular o próximo número."
                );
            }

            campo.val(
                numero
            );

            numeroVendaVisualizado =
                true;

        } catch (erro) {
            campo.val("");

            numeroVendaVisualizado =
                false;

            mensagemErroVenda(
                erro.message
            );
        }
    }


    // ==================================================
    // CLIENTES
    // ==================================================

    async function carregarClientesVenda() {

        try {

            const resposta =
                await requisicaoVenda(
                    VENDA_MODULO_URL
                    + "?acao=clientes"
                );

            const select =
                $("#venda_cliente_id");

            const valorAtual =
                select.val();

            select.html(`
                <option value="">
                    Seleccione o cliente
                </option>
            `);

            (resposta.data || [])
                .forEach(
                    function (cliente) {

                        select.append(`
                            <option value="${cliente.id}">
                                ${escaparHtmlVenda(
                            cliente.nome
                            || "Cliente"
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
                "Erro ao carregar clientes:",
                erro
            );

        }
    }


    // ==================================================
    // ARMAZÉNS
    // ==================================================

    async function carregarArmazensVenda() {

        try {

            const resposta =
                await requisicaoVenda(
                    VENDA_MODULO_URL
                    + "?acao=armazens"
                );

            const select =
                $("#venda_armazem_id");

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
    <option
        value="${Number(armazem.id)}"
        data-filial-id="${armazem.filial_id
                                ? Number(armazem.filial_id)
                                : ""
                            }">

        ${escaparHtmlVenda(
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
    // PRODUTOS
    // ==================================================

    async function carregarProdutosVenda() {

        try {

            const resposta =
                await requisicaoVenda(
                    VENDA_MODULO_URL
                    + "?acao=produtos"
                );


            const produtos =
                Array.isArray(
                    resposta.data
                )
                    ? resposta.data
                    : [];


            /*
            |--------------------------------------------------------------------------
            | VENDA NORMAL
            |--------------------------------------------------------------------------
            */

            const select =
                $("#venda_produto_id");


            if (
                select.hasClass(
                    "select2-hidden-accessible"
                )
            ) {

                select.select2(
                    "destroy"
                );
            }


            select.off(
                ".produtoVenda"
            );


            select.html(`
            <option value="">
                Seleccione o produto
            </option>
        `);


            produtos.forEach(
                function (produto) {

                    adicionarOpcaoProdutoVenda(
                        select,
                        produto
                    );
                }
            );


            select.select2({

                width:
                    "100%",

                placeholder:
                    "Pesquise pelo nome ou SKU",

                allowClear:
                    true,

                dropdownParent:
                    $("#vendaProdutoSelectContainer"),

                dropdownCssClass:
                    "select2-produto-venda",

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


            /*
            |--------------------------------------------------------------------------
            | VENDA RÁPIDA
            |--------------------------------------------------------------------------
            */

            inicializarProdutosVendaRapida(
                produtos
            );


        } catch (erro) {

            console.error(
                "Erro ao carregar produtos:",
                erro
            );
        }
    }

    function adicionarOpcaoProdutoVenda(
        select,
        produto
    ) {

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
                    produto.preco_venda
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

    function inicializarProdutosVendaRapida(
        produtos
    ) {

        const select =
            $("#vendaRapidaProduto");


        if (!select.length) {
            return;
        }


        if (
            select.hasClass(
                "select2-hidden-accessible"
            )
        ) {

            select.select2(
                "destroy"
            );
        }


        select.off(
            ".produtoVendaRapida"
        );


        select.html(`
        <option value="">
            Seleccione o produto
        </option>
    `);


        produtos.forEach(
            function (produto) {

                adicionarOpcaoProdutoVenda(
                    select,
                    produto
                );
            }
        );


        select.select2({

            width:
                "100%",

            placeholder:
                "Pesquise pelo nome ou SKU",

            allowClear:
                true,

            dropdownParent:
                $(
                    "#vendaRapidaProdutoSelectContainer"
                ),

            dropdownCssClass:
                "select2-produto-venda",

            language: {

                noResults:
                    function () {
                        return "Nenhum produto encontrado";
                    }
            }
        });
    }


    // ==================================================
    // FILIAIS
    // ==================================================

    async function carregarFiliaisVenda() {

        try {

            const resposta =
                await requisicaoVenda(
                    VENDA_FILIAL_URL
                    + "?acao=listar"
                );

            const select =
                $("#venda_filial_id");

            const valorAtual =
                select.val();

            select.html(`
                <option value="">
                    Sede Principal / Sem filial
                </option>
            `);

            (resposta.dados || [])
                .forEach(
                    function (filial) {

                        select.append(`
                            <option value="${filial.id}">
                                ${escaparHtmlVenda(
                            filial.nome
                            || "Filial"
                        )}
                            </option>
                        `);

                    }
                );

            if (valorAtual) {
                select.val(valorAtual);
            }

        } catch (erro) {

            console.warn(
                "Erro ao carregar filiais:",
                erro
            );

        }
    }


    // ==================================================
    // CENTROS DE RECEITA
    // ==================================================

    async function carregarCentrosVenda(
        filialId = null
    ) {
        try {

            const resposta =
                await requisicaoVenda(
                    VENDA_CENTRO_URL
                    + "?acao=listar_select"
                );

            const select =
                $("#venda_centro_custo_id");

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
    <option
        value="${Number(centro.id)}"
        data-filial-id="${centro.filial_id
                                ? Number(centro.filial_id)
                                : ""
                            }">

        ${escaparHtmlVenda(
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
    // FORMAS DE PAGAMENTO
    // ==================================================

    async function carregarFormasPagamentoVenda() {

        try {

            const resposta =
                await requisicaoVenda(
                    VENDA_FORMA_URL
                    + "?acao=listar"
                );

            const select =
                $("#venda_forma_pagamento_id");

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
                                ${escaparHtmlVenda(
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

    async function carregarCaixasVenda() {
        try {
            const resposta =
                await requisicaoVenda(
                    VENDA_CAIXA_URL
                    + "?acao=listar"
                );

            const select =
                $("#venda_caixa_id");

            select.html(`
            <option value="">
                Seleccione o caixa
            </option>
        `);

            (resposta.data || []).forEach(
                function (caixa) {
                    select.append(`
                    <option value="${caixa.id}">
                        ${escaparHtmlVenda(
                        caixa.nome
                        || "Caixa"
                    )}
                    </option>
                `);
                }
            );

        } catch (erro) {
            console.error(
                "Erro ao carregar caixas:",
                erro
            );
        }
    }


    async function carregarBancosVenda() {
        try {
            const resposta =
                await requisicaoVenda(
                    VENDA_BANCO_URL
                    + "?acao=listar"
                );

            const select =
                $("#venda_banco_id");

            select.html(`
            <option value="">
                Seleccione a conta bancária
            </option>
        `);

            (resposta.data || []).forEach(
                function (banco) {

                    const nome =
                        banco.nome_banco
                        || banco.nome
                        || "Conta bancária";

                    const conta =
                        banco.numero_conta
                        || banco.conta
                        || "";

                    select.append(`
                    <option value="${banco.id}">
                        ${escaparHtmlVenda(
                        conta
                            ? nome + " - " + conta
                            : nome
                    )}
                    </option>
                `);
                }
            );

        } catch (erro) {
            console.error(
                "Erro ao carregar bancos:",
                erro
            );
        }
    }
    // ==================================================
    // STATUS
    // ==================================================

    async function carregarStatusVenda() {

        try {

            const resposta =
                await requisicaoVenda(
                    VENDA_MODULO_URL
                    + "?acao=status"
                );

            const select =
                $("#venda_status_id");

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
                                ${escaparHtmlVenda(
                            status.nome
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

            } else {

                selecionarStatusRascunhoVenda();

            }

        } catch (erro) {

            mensagemErroVenda(
                erro.message
            );

        }
    }


    function selecionarStatusRascunhoVenda() {

        const select =
            $("#venda_status_id");

        const opcao =
            select
                .find("option")
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

            select.val(
                opcao.val()
            );

        }
    }


    // ==================================================
    // CONDIÇÃO DE PAGAMENTO
    // ==================================================

    function atualizarCondicaoPagamentoVenda() {

        const condicao =
            String(
                $("#venda_condicao_pagamento").val()
                || "APRAZO"
            ).toUpperCase();


        const grupoVencimento =
            $("#grupoVendaVencimento");


        const campoVencimento =
            $("#venda_data_vencimento");


        if (condicao === "APRAZO") {

            /*
             * Agora usamos as datas das parcelas.
             * O campo antigo fica escondido.
             */

            grupoVencimento.hide();

            campoVencimento
                .prop(
                    "required",
                    false
                )
                .val("");


        } else {

            /*
             * Venda à vista não precisa vencimento.
             */

            grupoVencimento.hide();

            campoVencimento
                .prop(
                    "required",
                    false
                )
                .val("");
        }


        atualizarCamposParcelasVenda();

        atualizarValoresParcelasVenda();
    }


    // ==================================================
    // ADICIONAR PRODUTO
    // ==================================================

    function adicionarProdutoVenda() {

        const produto =
            $("#venda_produto_id option:selected");

        const produtoId =
            Number(
                produto.val()
                || 0
            );

        const quantidade =
            Number(
                $("#venda_quantidade").val()
                || 0
            );

        const preco =
            Number(
                $("#venda_preco_unitario").val()
                || produto.data("preco")
                || 0
            );

        if (produtoId <= 0) {

            mensagemAvisoVenda(
                "Seleccione um produto."
            );

            return;
        }

        if (quantidade <= 0) {

            mensagemAvisoVenda(
                "A quantidade deve ser superior a zero."
            );

            return;
        }

        if (preco < 0) {

            mensagemAvisoVenda(
                "O preço unitário é inválido."
            );

            return;
        }

        const existente =
            itensVenda.find(
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
                calcularSubtotalItemVenda(
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
                calcularSubtotalItemVenda(
                    item
                );

            itensVenda.push(
                item
            );

        }

        mostrarItensVenda();

        $("#venda_produto_id")
            .val("")
            .trigger(
                "change.select2"
            );

        $("#vendaRapidaProduto")
            .val("")
            .trigger(
                "change.select2"
            );

        $("#venda_quantidade")
            .val(1);

        $("#venda_preco_unitario")
            .val("");

    }

    function atualizarTipoParcelamentoVenda() {

        const condicao =
            String(
                $("#venda_condicao_pagamento").val()
                || ""
            ).toUpperCase();


        const tipo =
            String(
                $("#venda_tipo_parcelamento").val()
                || "AUTOMATICO"
            ).toUpperCase();


        /*
        |--------------------------------------------------------------------------
        | NÃO É VENDA A PRAZO
        |--------------------------------------------------------------------------
        */

        if (condicao !== "APRAZO") {

            $("#grupoVendaTipoParcelamento")
                .hide();

            $("#venda_tipo_parcelamento")
                .val("AUTOMATICO");

            $("#venda_parcela_1_valor")
                .prop("readonly", true);

            $("#venda_parcela_2_valor")
                .prop("readonly", true);

            $("#venda_parcela_3_valor")
                .prop("readonly", true);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | VENDA A PRAZO
        |--------------------------------------------------------------------------
        */

        $("#grupoVendaTipoParcelamento")
            .show();


        const personalizado =
            tipo === "PERSONALIZADO";


        $("#venda_parcela_1_valor")
            .prop(
                "readonly",
                !personalizado
            );

        $("#venda_parcela_2_valor")
            .prop(
                "readonly",
                !personalizado
            );

        $("#venda_parcela_3_valor")
            .prop(
                "readonly",
                !personalizado
            );


        /*
        |--------------------------------------------------------------------------
        | AUTOMÁTICO
        |--------------------------------------------------------------------------
        */

        if (!personalizado) {

            atualizarValoresParcelasVenda();
        }
    }


    function calcularSubtotalItemVenda(
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

    function mostrarItensVenda() {

        let html = "";

        itensVenda.forEach(
            function (
                item,
                index
            ) {

                html += `
                    <tr>

                        <td>
                            ${escaparHtmlVenda(
                    item.nome
                )}
                        </td>

                        <td>
                            ${formatarNumeroVenda(
                    item.quantidade
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaVenda(
                    item.preco_unitario
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaVenda(
                    item.subtotal
                )}
                        </td>

                        <td class="text-center">

                            <button
                                type="button"
                                class="btn btn-danger btn-sm btn-remover-item-venda"
                                data-index="${index}">

                                <i class="fa fa-trash"></i>

                            </button>

                        </td>

                    </tr>
                `;

            }
        );

        if (itensVenda.length === 0) {

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

        $("#venda_lista_itens")
            .html(html);

        atualizarTotaisVenda();
    }


    function removerItemVenda(
        index
    ) {
        if (
            index < 0
            || index >= itensVenda.length
        ) {
            return;
        }

        itensVenda.splice(
            index,
            1
        );

        mostrarItensVenda();
    }


    // ==================================================
    // TOTAIS
    // ==================================================

    function atualizarTotaisVenda() {

        const TAXA_IMPOSTO =
            16;


        const subtotal =
            itensVenda.reduce(
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


        const desconto =
            Math.max(
                0,
                Number(
                    $("#venda_desconto").val()
                    || 0
                )
            );


        const impostoHabilitado =
            $("#venda_imposto_habilitado")
                .is(":checked");


        const baseTributavel =
            Math.max(
                0,
                subtotal - desconto
            );


        const imposto =
            impostoHabilitado
                ? (
                    baseTributavel
                    * TAXA_IMPOSTO
                    / 100
                )
                : 0;


        const total =
            baseTributavel
            + imposto;


        $("#venda_subtotal")
            .val(
                subtotal.toFixed(2)
            );


        $("#venda_imposto")
            .val(
                imposto.toFixed(2)
            );


        $("#venda_taxa_imposto")
            .val(
                TAXA_IMPOSTO.toFixed(2)
            );


        $("#venda_total")
            .val(
                total.toFixed(2)
            );


        atualizarValoresParcelasVenda();
    }


    // ==================================================
    // GUARDAR VENDA
    // ==================================================

    async function guardarVenda(
        evento
    ) {
        evento.preventDefault();

        if (itensVenda.length === 0) {

            mensagemAvisoVenda(
                "Adicione pelo menos um produto à venda."
            );

            return;
        }

        const numeroFatura =
            String(
                $("#venda_numero_fatura")
                    .val()
                || ""
            ).trim();

        if (!numeroFatura) {

            mensagemAvisoVenda(
                "O número da factura ainda não foi gerado."
            );

            return;
        }

        const statusId =
            Number(
                $("#venda_status_id").val()
                || 0
            );

        if (statusId <= 0) {

            mensagemAvisoVenda(
                "Seleccione o status da venda."
            );

            return;
        }

        atualizarTotaisVenda();

        const formulario =
            evento.currentTarget;

        const botao =
            $(formulario)
                .find(
                    'button[type="submit"]'
                );

        bloquearBotaoVenda(
            botao,
            "A guardar..."
        );

        try {
            const condicaoPagamento =
                String(
                    $("#venda_condicao_pagamento").val()
                    || ""
                ).toUpperCase();

            if (condicaoPagamento === "AVISTA") {
                const destino =
                    String(
                        $("#venda_destino_recebimento").val()
                        || ""
                    ).toUpperCase();

                if (
                    destino !== "CAIXA"
                    && destino !== "BANCO"
                ) {
                    mensagemAvisoVenda(
                        "Seleccione onde o recebimento da venda entrou."
                    );

                    return;
                }

                if (
                    destino === "CAIXA"
                    && !$("#venda_caixa_id").val()
                ) {
                    mensagemAvisoVenda(
                        "Seleccione o caixa que recebeu o valor."
                    );

                    return;
                }

                if (
                    destino === "BANCO"
                    && !$("#venda_banco_id").val()
                ) {
                    mensagemAvisoVenda(
                        "Seleccione a conta bancária que recebeu o valor."
                    );

                    return;
                }
            }

            validarValoresParcelasVenda();

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
                    itensVenda
                )
            );

            const resposta =
                await requisicaoVenda(
                    VENDA_MODULO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalVenda")
                .modal("hide");

            limparFormularioVenda();

            recarregarTabelaVendas();

            await Swal.fire({

                icon:
                    "success",

                title:
                    "Venda guardada",

                text:
                    resposta.mensagem
                    || "A venda foi guardada com sucesso.",

                confirmButtonText:
                    "Fechar"

            });

        } catch (erro) {

            mensagemErroVenda(
                erro.message
            );

        } finally {

            restaurarBotaoVenda(
                botao,
                `
                    <i class="fa fa-save"></i>
                    Guardar Venda
                `
            );

        }
    }


    // ==================================================
    // EDITAR VENDA
    // ==================================================

    async function editarVenda(
        id
    ) {
        try {

            const resposta =
                await requisicaoVenda(
                    VENDA_MODULO_URL
                    + "?acao=buscar&id="
                    + encodeURIComponent(id)
                );

            const venda =
                resposta.data.venda;

            const itens =
                resposta.data.itens || [];

            const parcelas =
                resposta.data.parcelas || [];

            if (!venda) {

                throw new Error(
                    "Venda não encontrada."
                );

            }

            await Promise.allSettled([
                carregarClientesVenda(),
                carregarArmazensVenda(),
                carregarProdutosVenda(),
                carregarFiliaisVenda(),
                carregarCentrosVenda(
                    venda.filial_id
                ),
                carregarFormasPagamentoVenda(),
                carregarStatusVenda()
            ]);

            $("#vendaId")
                .val(
                    venda.id
                );

            $("#venda_cliente_id")
                .val(
                    venda.cliente_id
                    || ""
                );

            $("#venda_armazem_id")
                .val(
                    venda.armazem_id
                    || ""
                );

            $("#venda_filial_id")
                .val(
                    venda.filial_id
                    || ""
                );

            $("#venda_centro_custo_id")
                .val(
                    venda.centro_custo_id
                    || ""
                );

            $("#venda_forma_pagamento_id")
                .val(
                    venda.forma_pagamento_id
                    || ""
                );

            $("#venda_condicao_pagamento")
                .val(
                    venda.condicao_pagamento
                    || "APRAZO"
                );

            $("#venda_numero_fatura")
                .val(
                    venda.numero_fatura
                    || ""
                );

            $("#venda_numero_documento")
                .val(
                    venda.numero_documento
                    || ""
                );

            $("#venda_tipo_documento")
                .val(
                    venda.tipo_documento
                    || "FACTURA"
                );

            $("#venda_data")
                .val(
                    converterDataVendaParaInput(
                        venda.data_venda
                    )
                );

            $("#venda_data_vencimento")
                .val(
                    venda.data_vencimento
                    || ""
                );

            $("#venda_origem")
                .val(
                    venda.origem
                    || "BALCAO"
                );

            $("#venda_status_id")
                .val(
                    venda.status_id
                    || ""
                );

            $("#venda_desconto")
                .val(
                    venda.desconto
                    || "0.00"
                );

            $("#venda_imposto_habilitado")
                .prop(
                    "checked",
                    Number(
                        venda.imposto_habilitado
                        ?? 1
                    ) === 1
                );


            $("#venda_taxa_imposto")
                .val(
                    Number(
                        venda.taxa_imposto
                        ?? 16
                    ).toFixed(2)
                );


            $("#venda_numero_parcelas")
                .val(
                    String(
                        venda.numero_parcelas
                        || 1
                    )
                );

            $("#venda_tipo_parcelamento")
                .val(
                    String(
                        venda.tipo_parcelamento
                        || "AUTOMATICO"
                    ).toUpperCase()
                );

            $("#venda_parcela_1_data")
                .val("");

            $("#venda_parcela_2_data")
                .val("");

            $("#venda_parcela_3_data")
                .val("");


            $("#venda_parcela_1_valor")
                .val("");

            $("#venda_parcela_2_valor")
                .val("");

            $("#venda_parcela_3_valor")
                .val("");


            parcelas.forEach(
                function (parcela) {

                    const numero =
                        Number(
                            parcela.numero_parcela
                            || 0
                        );


                    if (
                        numero < 1
                        || numero > 3
                    ) {
                        return;
                    }


                    $(
                        "#venda_parcela_"
                        + numero
                        + "_data"
                    ).val(
                        parcela.data_vencimento
                        || ""
                    );


                    $(
                        "#venda_parcela_"
                        + numero
                        + "_valor"
                    ).val(
                        Number(
                            parcela.valor
                            || 0
                        ).toFixed(2)
                    );
                }
            );

            $("#venda_imposto")
                .val(
                    venda.imposto
                    || "0.00"
                );

            $("#venda_observacoes")
                .val(
                    venda.observacoes
                    || ""
                );

            itensVenda =
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

            numeroVendaVisualizado = true;

            mostrarItensVenda();

            atualizarCondicaoPagamentoVenda();

            const confirmada =
                String(
                    venda.status_nome
                    || ""
                )
                    .trim()
                    .toLowerCase()
                === "confirmada";

            definirModoSomenteLeituraVenda(
                confirmada
            );

            $("#modalVenda")
                .modal("show");

        } catch (erro) {

            mensagemErroVenda(
                erro.message
            );

        }
    }


    // ==================================================
    // SOMENTE LEITURA
    // ==================================================

    function definirModoSomenteLeituraVenda(
        somenteLeitura
    ) {
        const formulario =
            $("#formVenda");

        formulario
            .find(
                "input:not([type='hidden']), select, textarea"
            )
            .prop(
                "disabled",
                somenteLeitura
            );

        /*
         * Número da factura nunca pode ser editado.
         * Quando não estiver em modo de leitura,
         * readonly continua activo.
         */

        $("#venda_numero_fatura")
            .prop(
                "disabled",
                false
            )
            .prop(
                "readonly",
                true
            );

        $("#vendaAdicionarProduto")
            .prop(
                "disabled",
                somenteLeitura
            );

        $(".btn-remover-item-venda")
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

        $("#modalVenda .modal-title")
            .text(
                somenteLeitura
                    ? "Detalhes da Venda"
                    : "Editar Venda"
            );
    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    function abrirEliminarVenda(
        id
    ) {
        $("#eliminarVendaId")
            .val(id);

        $("#modalEliminarVenda")
            .modal("show");
    }


    async function eliminarVenda(
        evento
    ) {
        evento.preventDefault();

        const botao =
            $(evento.currentTarget)
                .find(
                    'button[type="submit"]'
                );

        bloquearBotaoVenda(
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
                await requisicaoVenda(
                    VENDA_MODULO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalEliminarVenda")
                .modal("hide");

            recarregarTabelaVendas();

            toastVenda(
                resposta.mensagem
                || "Venda eliminada com sucesso."
            );

        } catch (erro) {

            mensagemErroVenda(
                erro.message
            );

        } finally {

            restaurarBotaoVenda(
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

    function limparFormularioVenda() {

        const formulario =
            document.getElementById(
                "formVenda"
            );

        if (formulario) {
            formulario.reset();
        }

        definirModoSomenteLeituraVenda(
            false
        );

        $("#vendaId")
            .val("");

        $("#venda_numero_fatura")
            .val("")
            .prop(
                "readonly",
                true
            );

        $("#venda_desconto")
            .val("0.00");

        $("#venda_imposto_habilitado")
            .prop(
                "checked",
                true
            );


        $("#venda_taxa_imposto")
            .val("16.00");


        $("#venda_numero_parcelas")
            .val("1");


        $("#venda_parcela_1_data")
            .val("");

        $("#venda_parcela_2_data")
            .val("");

        $("#venda_parcela_3_data")
            .val("");

        $("#venda_tipo_parcelamento")
            .val("AUTOMATICO");

        $("#venda_parcela_1_valor")
            .prop("readonly", true);

        $("#venda_parcela_2_valor")
            .prop("readonly", true);

        $("#venda_parcela_3_valor")
            .prop("readonly", true);

        $("#venda_imposto")
            .val("0.00");

        $("#venda_subtotal")
            .val("0.00");

        $("#venda_total")
            .val("0.00");

        $("#venda_quantidade")
            .val(1);

        $("#venda_preco_unitario")
            .val("");

        $("#venda_tipo_documento")
            .val("FACTURA");

        $("#venda_origem")
            .val("BALCAO");

        $("#venda_condicao_pagamento")
            .val("APRAZO");

        $("#venda_destino_recebimento")
            .val("");

        $("#venda_caixa_id")
            .val("");

        $("#venda_banco_id")
            .val("");

        $("#grupoVendaDestino")
            .hide();

        $("#grupoVendaCaixa")
            .hide();

        $("#grupoVendaBanco")
            .hide();

        atualizarDestinoRecebimentoVenda();

        itensVenda = [];

        numeroVendaVisualizado = false;

        mostrarItensVenda();

        definirDatasPadraoVenda();

        selecionarStatusRascunhoVenda();

        atualizarCondicaoPagamentoVenda();

        atualizarTipoParcelamentoVenda();

        atualizarCamposParcelasVenda();

        atualizarTotaisVenda();

        $("#modalVenda .modal-title")
            .text(
                "Nova Venda"
            );
    }


    // ==================================================
    // DATAS
    // ==================================================

    function definirDatasPadraoVenda() {

        if (
            !$("#venda_data").val()
        ) {

            $("#venda_data")
                .val(
                    dataHoraLocalVenda()
                );

        }

        if (
            !$("#venda_data_vencimento").val()
        ) {

            $("#venda_data_vencimento")
                .val(
                    dataHojeVenda()
                );

        }
    }


    function dataHojeVenda() {

        const agora =
            new Date();

        const ano =
            agora.getFullYear();

        const mes =
            String(
                agora.getMonth() + 1
            ).padStart(
                2,
                "0"
            );

        const dia =
            String(
                agora.getDate()
            ).padStart(
                2,
                "0"
            );

        return `${ano}-${mes}-${dia}`;
    }


    function dataHoraLocalVenda() {

        const agora =
            new Date();

        const ano =
            agora.getFullYear();

        const mes =
            String(
                agora.getMonth() + 1
            ).padStart(
                2,
                "0"
            );

        const dia =
            String(
                agora.getDate()
            ).padStart(
                2,
                "0"
            );

        const hora =
            String(
                agora.getHours()
            ).padStart(
                2,
                "0"
            );

        const minuto =
            String(
                agora.getMinutes()
            ).padStart(
                2,
                "0"
            );

        return (
            `${ano}-${mes}-${dia}`
            + `T${hora}:${minuto}`
        );
    }


    function converterDataVendaParaInput(
        data
    ) {
        if (!data) {
            return "";
        }

        return String(data)
            .replace(
                " ",
                "T"
            )
            .substring(
                0,
                16
            );
    }


    function formatarDataVenda(
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


    function formatarDataHoraVenda(
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

        const hora =
            valor.length >= 16
                ? valor.substring(
                    11,
                    16
                )
                : "";

        return (
            formatarDataVenda(
                dataParte
            )
            + (
                hora
                    ? " " + hora
                    : ""
            )
        );
    }


    // ==================================================
    // STATUS
    // ==================================================

    function renderStatusVenda(
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
                ${escaparHtmlVenda(
            texto || "-"
        )}
            </span>
        `;
    }


    function renderCondicaoVenda(
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

    function formatarMoedaVenda(
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


    function formatarNumeroVenda(
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

    function mensagemErroVenda(
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


    function mensagemAvisoVenda(
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


    function toastVenda(
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

    function bloquearBotaoVenda(
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


    function restaurarBotaoVenda(
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

    function escaparHtmlVenda(
        valor
    ) {
        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();
    }

})(jQuery);