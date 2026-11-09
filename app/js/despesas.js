"use strict";

$(document).ready(function () {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const URL_DESPESA =
        "app/ajax/despesa_ajax.php";

    let dadosIniciaisDespesa = {
        contas_gasto: [],
        fornecedores: [],
        centros_custo: [],
        caixas: [],
        bancos: [],
        status: []
    };



    // ==================================================
    // INICIAR
    // ==================================================

    iniciarDespesas();


    async function iniciarDespesas() {

        registrarEventosDespesa();


        await carregarDadosIniciaisDespesa();

        await carregarDespesas();
    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function registrarEventosDespesa() {

        $("#btnNovaDespesa")
            .off("click.despesa")
            .on(
                "click.despesa",
                function () {

                    abrirNovaDespesa();
                }
            );


        $("#btnGuardarDespesa")
            .off("click.despesa")
            .on(
                "click.despesa",
                function () {

                    guardarDespesa();
                }
            );


        $("#despesaTipoLiquidacao")
            .off("change.despesa")
            .on(
                "change.despesa",
                function () {

                    atualizarCamposLiquidacao();
                }
            );


        $("#despesaContaGasto")
            .off("change.despesa")
            .on(
                "change.despesa",
                function () {

                    atualizarCentroCustoObrigatorio();
                }
            );


        $("#despesaValorBase, #despesaImposto")
            .off("input.despesa")
            .on(
                "input.despesa",
                function () {

                    calcularTotalDespesa();
                }
            );


        $(document)
            .off(
                "click.despesaEditar",
                ".btn-editar-despesa"
            )
            .on(
                "click.despesaEditar",
                ".btn-editar-despesa",
                function () {

                    const id =
                        Number(
                            $(this).data("id")
                            || 0
                        );

                    if (id > 0) {

                        editarDespesa(
                            id
                        );
                    }
                }
            );

        $(document)
            .off(
                "click.despesaContabilizar",
                ".btn-contabilizar-despesa"
            )
            .on(
                "click.despesaContabilizar",
                ".btn-contabilizar-despesa",
                function () {

                    const id =
                        Number(
                            $(this).data("id")
                            || 0
                        );


                    if (id > 0) {

                        contabilizarDespesa(
                            id
                        );
                    }
                }
            );
    }

    // ==================================================
    // CONTABILIZAR
    // ==================================================

    async function contabilizarDespesa(
        id
    ) {

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Contabilizar despesa?",

                html:
                    "Esta operação irá:<br><br>"
                    + "• criar o lançamento contabilístico;<br>"
                    + "• registar a saída financeira;<br>"
                    + "• atualizar o saldo do Caixa ou Banco selecionado;<br>"
                    + "• alterar a despesa para <strong>Contabilizada</strong>.<br><br>"
                    + "<strong>Depois disso a despesa não poderá ser editada diretamente.</strong>",

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, contabilizar",

                cancelButtonText:
                    "Cancelar"
            });


        if (
            !confirmacao.isConfirmed
        ) {

            return;
        }


        try {

            const resposta =
                await $.ajax({
                    url:
                        URL_DESPESA,

                    method:
                        "POST",

                    dataType:
                        "json",

                    data: {
                        acao:
                            "contabilizar",

                        id:
                            id
                    }
                });


            if (!resposta.success) {

                throw new Error(
                    resposta.mensagem
                    || "Não foi possível contabilizar a despesa."
                );
            }


            await Swal.fire({
                icon:
                    "success",

                title:
                    "Despesa contabilizada",

                text:
                    resposta.mensagem
                    || "A despesa foi contabilizada com sucesso."
            });


            /*
             * Recarrega dados para atualizar:
             * - tabela
             * - cards
             * - saldo mostrado nos selects
             */
            await carregarDadosIniciaisDespesa();

            await carregarDespesas();

        } catch (erro) {

            console.error(
                "Erro ao contabilizar despesa:",
                erro
            );


            mostrarErroDespesa(
                obterMensagemErroDespesa(
                    erro,
                    "Não foi possível contabilizar a despesa."
                )
            );
        }
    }


    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisDespesa() {

        try {

            const resposta =
                await $.ajax({
                    url: URL_DESPESA,
                    method: "GET",
                    dataType: "json",

                    data: {
                        acao:
                            "dados_iniciais"
                    }
                });


            if (!resposta.success) {

                throw new Error(
                    resposta.mensagem
                    || "Não foi possível carregar os dados iniciais."
                );
            }


            dadosIniciaisDespesa =
                resposta.data
                || {};


            preencherSelectContasGasto();

            preencherSelectFornecedores();

            preencherSelectCentrosCusto();

            preencherSelectCaixas();

            preencherSelectBancos();

        } catch (erro) {

            console.error(
                "Erro ao carregar dados iniciais:",
                erro
            );


            mostrarErroDespesa(
                obterMensagemErroDespesa(
                    erro,
                    "Não foi possível carregar os dados necessários."
                )
            );
        }
    }


    // ==================================================
    // CONTAS DE GASTO
    // ==================================================

    function preencherSelectContasGasto() {

        const select =
            $("#despesaContaGasto");


        select.empty();


        select.append(
            $("<option>", {
                value: "",
                text:
                    "Selecione a conta"
            })
        );


        const contas =
            dadosIniciaisDespesa
                .contas_gasto
            || [];


        const grupos = {};


        contas.forEach(
            function (conta) {

                const classe =
                    conta.classe_gasto
                    || "Outros Gastos";


                if (!grupos[classe]) {

                    grupos[classe] =
                        $("<optgroup>", {
                            label:
                                classe
                        });


                    select.append(
                        grupos[classe]
                    );
                }


                const option =
                    $("<option>", {
                        value:
                            conta.id,

                        text:
                            `${conta.codigo} - ${conta.nome}`
                    });


                option.attr(
                    "data-exige-centro-custo",
                    Number(
                        conta.exige_centro_custo
                        || 0
                    )
                );


                grupos[classe]
                    .append(
                        option
                    );
            }
        );
    }


    // ==================================================
    // FORNECEDORES
    // ==================================================

    function preencherSelectFornecedores() {

        const select =
            $("#despesaFornecedor");


        select.empty();


        select.append(
            $("<option>", {
                value: "",
                text:
                    "Sem fornecedor"
            })
        );


        const fornecedores =
            dadosIniciaisDespesa
                .fornecedores
            || [];


        fornecedores.forEach(
            function (fornecedor) {

                let texto =
                    fornecedor.nome
                    || `Fornecedor #${fornecedor.id}`;


                if (fornecedor.nuit) {

                    texto +=
                        ` - NUIT ${fornecedor.nuit}`;
                }


                select.append(
                    $("<option>", {
                        value:
                            fornecedor.id,

                        text:
                            texto
                    })
                );
            }
        );
    }


    // ==================================================
    // CENTROS DE CUSTO
    // ==================================================

    function preencherSelectCentrosCusto() {

        const select =
            $("#despesaCentroCusto");


        select.empty();


        select.append(
            $("<option>", {
                value: "",
                text:
                    "Sem centro de custo"
            })
        );


        const centros =
            dadosIniciaisDespesa
                .centros_custo
            || [];


        centros.forEach(
            function (centro) {

                select.append(
                    $("<option>", {
                        value:
                            centro.id,

                        text:
                            `${centro.codigo} - ${centro.nome}`
                    })
                );
            }
        );
    }


    // ==================================================
    // CAIXAS
    // ==================================================

    function preencherSelectCaixas() {

        const select =
            $("#despesaCaixa");


        select.empty();


        select.append(
            $("<option>", {
                value: "",
                text:
                    "Selecione o caixa"
            })
        );


        const caixas =
            dadosIniciaisDespesa
                .caixas
            || [];


        caixas.forEach(
            function (caixa) {

                let texto =
                    caixa.codigo
                        ? `${caixa.codigo} - ${caixa.nome}`
                        : caixa.nome;


                if (
                    caixa.saldo_atual
                    !== undefined
                ) {

                    texto +=
                        ` | ${formatarMoedaDespesa(
                            caixa.saldo_atual
                        )}`;
                }


                select.append(
                    $("<option>", {
                        value:
                            caixa.id,

                        text:
                            texto
                    })
                );
            }
        );
    }


    // ==================================================
    // BANCOS
    // ==================================================

    function preencherSelectBancos() {

        const select =
            $("#despesaBanco");


        select.empty();


        select.append(
            $("<option>", {
                value: "",
                text:
                    "Selecione o banco"
            })
        );


        const bancos =
            dadosIniciaisDespesa
                .bancos
            || [];


        bancos.forEach(
            function (banco) {

                let texto =
                    banco.nome_banco
                    || `Banco #${banco.id}`;


                if (banco.conta) {

                    texto +=
                        ` - ${banco.conta}`;
                }


                if (banco.moeda) {

                    texto +=
                        ` (${banco.moeda})`;
                }


                if (
                    banco.saldo_atual
                    !== undefined
                ) {

                    texto +=
                        ` | ${formatarMoedaDespesa(
                            banco.saldo_atual
                        )}`;
                }


                select.append(
                    $("<option>", {
                        value:
                            banco.id,

                        text:
                            texto
                    })
                );
            }
        );
    }


    // ==================================================
    // LISTAR
    // ==================================================

    async function carregarDespesas() {

        try {

            const resposta =
                await $.ajax({
                    url: URL_DESPESA,
                    method: "GET",
                    dataType: "json",

                    data: {
                        acao:
                            "listar"
                    }
                });


            if (!resposta.success) {

                throw new Error(
                    resposta.mensagem
                    || "Não foi possível carregar as despesas."
                );
            }


            const despesas =
                Array.isArray(
                    resposta.data
                )
                    ? resposta.data
                    : [];


            preencherTabelaDespesas(
                despesas
            );


            preencherResumoDespesas(
                despesas
            );

        } catch (erro) {

            console.error(
                "Erro ao listar despesas:",
                erro
            );


            mostrarErroDespesa(
                obterMensagemErroDespesa(
                    erro,
                    "Não foi possível carregar as despesas."
                )
            );
        }
    }


    // ==================================================
    // TABELA
    // ==================================================

    function preencherTabelaDespesas(
        despesas
    ) {

        const tbody =
            $("#tabelaDespesas tbody");


        tbody.empty();


        if (
            !Array.isArray(despesas)
            || despesas.length === 0
        ) {

            tbody.append(`
                <tr>
                    <td
                        colspan="9"
                        class="text-center text-muted py-4"
                    >
                        Nenhuma despesa registada.
                    </td>
                </tr>
            `);

            return;
        }


        despesas.forEach(
            function (despesa) {

                const status =
                    String(
                        despesa.status_nome
                        || ""
                    );


                const podeEditar =
                    normalizarTextoDespesa(
                        status
                    ) === "rascunho";


                const documento =
                    despesa.numero_documento
                    || "—";


                const fornecedor =
                    despesa.fornecedor_nome
                    || "—";


                const conta =
                    [
                        despesa.conta_codigo,
                        despesa.conta_nome
                    ]
                        .filter(Boolean)
                        .join(" - ");


                let acoes = "";


                if (podeEditar) {

                    acoes += `
        <button
            type="button"
            class="btn btn-sm btn-outline-primary btn-editar-despesa"
            data-id="${Number(
                        despesa.id
                    )}"
            title="Editar"
        >
            <i class="fa fa-edit"></i>
        </button>

        <button
            type="button"
            class="btn btn-sm btn-outline-success btn-contabilizar-despesa"
            data-id="${Number(
                        despesa.id
                    )}"
            title="Contabilizar"
        >
            <i class="fa fa-check"></i>
        </button>
    `;

                } else {

                    acoes = `
        <span class="text-muted">
            —
        </span>
    `;
                }

                if (
                    Number(
                        despesa.conta_pagar_id
                        || 0
                    ) > 0
                ) {

                    const contaPagarId =
                        Number(
                            despesa.conta_pagar_id
                        );


                    const statusContaPagar =
                        normalizarTextoDespesa(
                            despesa.conta_pagar_status
                            || ""
                        );


                    const saldoContaPagar =
                        numeroDespesa(
                            despesa.conta_pagar_saldo
                            || 0
                        );


                    if (
                        saldoContaPagar > 0
                        && statusContaPagar !== "pago"
                    ) {

                        // ======================================
                        // AINDA TEM SALDO A PAGAR
                        // ======================================

                        acoes += `
            <a
                href="index.php?page=contas_pagar&conta_id=${contaPagarId}&acao=pagamento"
                class="btn btn-sm btn-outline-success"
                title="Registar Pagamento"
            >
                <i class="fa fa-money"></i>
            </a>
        `;

                    } else {

                        // ======================================
                        // CONTA JÁ PAGA
                        // ======================================

                        acoes += `
            <a
                href="index.php?page=contas_pagar&conta_id=${contaPagarId}&acao=visualizar"
                class="btn btn-sm btn-outline-info"
                title="Ver Conta a Pagar"
            >
                <i class="fa fa-eye"></i>
            </a>
        `;
                    }
                }


                tbody.append(`
                    <tr>

                        <td>
                            ${escaparHtmlDespesa(
                    formatarDataDespesa(
                        despesa.data_despesa
                    )
                )}
                        </td>

                        <td>
                            ${escaparHtmlDespesa(
                    documento
                )}
                        </td>

                        <td>
                            ${escaparHtmlDespesa(
                    despesa.descricao
                    || ""
                )}
                        </td>

                        <td>
                            ${escaparHtmlDespesa(
                    fornecedor
                )}
                        </td>

                        <td>
                            ${escaparHtmlDespesa(
                    conta
                    || "—"
                )}
                        </td>

                        <td>
                            ${montarLiquidacaoDespesa(
                    despesa
                )}
                        </td>

                        <td>
                            <strong>
                                ${escaparHtmlDespesa(
                    formatarMoedaDespesa(
                        despesa.valor_total
                    )
                )}
                            </strong>
                        </td>

                        <td>
                            ${montarStatusDespesa(
                    status
                )}
                        </td>

                        <td>
                            ${acoes}
                        </td>

                    </tr>
                `);
            }
        );
    }

    function montarLiquidacaoDespesa(
        despesa
    ) {

        const tipo =
            String(
                despesa.tipo_liquidacao
                || ""
            ).toUpperCase();


        if (
            tipo !== "CONTA_PAGAR"
        ) {

            return escaparHtmlDespesa(
                formatarLiquidacaoDespesa(
                    tipo
                )
            );
        }


        const status =
            despesa.conta_pagar_status
            || "Pendente";


        const saldo =
            formatarMoedaDespesa(
                despesa.conta_pagar_saldo
                || 0
            );


        return `
        <div>
            <strong>
                Conta a Pagar
            </strong>

            <br>

            <small>
                ${escaparHtmlDespesa(status)}
                • Saldo:
                ${escaparHtmlDespesa(saldo)}
            </small>
        </div>
    `;
    }

    // ==================================================
    // RESUMO
    // ==================================================

    function preencherResumoDespesas(
        despesas
    ) {

        let total =
            0;

        let rascunhos =
            0;

        let contabilizadas =
            0;

        let canceladas =
            0;


        despesas.forEach(
            function (despesa) {

                const status =
                    normalizarTextoDespesa(
                        despesa.status_nome
                    );


                /*
                 * Canceladas não entram no
                 * total económico apresentado.
                 */
                if (
                    status !==
                    "cancelada"
                ) {

                    total +=
                        numeroDespesa(
                            despesa.valor_total
                        );
                }


                if (
                    status ===
                    "rascunho"
                ) {

                    rascunhos++;
                }


                if (
                    status ===
                    "contabilizada"
                ) {

                    contabilizadas++;
                }


                if (
                    status ===
                    "cancelada"
                ) {

                    canceladas++;
                }
            }
        );


        $("#resumoTotalDespesas")
            .text(
                formatarMoedaDespesa(
                    total
                )
            );


        $("#resumoRascunhos")
            .text(
                rascunhos
            );


        $("#resumoContabilizadas")
            .text(
                contabilizadas
            );


        $("#resumoCanceladas")
            .text(
                canceladas
            );
    }


    // ==================================================
    // NOVA DESPESA
    // ==================================================

    function abrirNovaDespesa() {

        limparFormularioDespesa();


        $("#tituloModalDespesa")
            .text(
                "Nova Despesa"
            );


        $("#btnGuardarDespesa")
            .text(
                "Guardar Rascunho"
            );


        $("#despesaData")
            .val(
                dataAtualDespesa()
            );


        atualizarCamposLiquidacao();

        atualizarCentroCustoObrigatorio();

        calcularTotalDespesa();


        abrirModalDespesa();
    }


    // ==================================================
    // EDITAR
    // ==================================================

    async function editarDespesa(
        id
    ) {

        try {

            const resposta =
                await $.ajax({
                    url: URL_DESPESA,
                    method: "GET",
                    dataType: "json",

                    data: {
                        acao:
                            "buscar",

                        id:
                            id
                    }
                });


            if (!resposta.success) {

                throw new Error(
                    resposta.mensagem
                    || "Não foi possível carregar a despesa."
                );
            }


            const despesa =
                resposta.data;


            if (!despesa) {

                throw new Error(
                    "Despesa não encontrada."
                );
            }


            limparFormularioDespesa();


            $("#despesaId")
                .val(
                    despesa.id
                    || ""
                );


            $("#despesaData")
                .val(
                    despesa.data_despesa
                    || ""
                );


            $("#despesaDocumento")
                .val(
                    despesa.numero_documento
                    || ""
                );


            $("#despesaReferencia")
                .val(
                    despesa.referencia
                    || ""
                );


            $("#despesaDescricao")
                .val(
                    despesa.descricao
                    || ""
                );


            $("#despesaContaGasto")
                .val(
                    despesa.conta_gasto_id
                    || ""
                );


            $("#despesaCentroCusto")
                .val(
                    despesa.centro_custo_id
                    || ""
                );


            $("#despesaFornecedor")
                .val(
                    despesa.fornecedor_id
                    || ""
                );


            $("#despesaValorBase")
                .val(
                    despesa.valor_base
                    || ""
                );


            const valorBaseEdicao =
                numeroDespesa(
                    despesa.valor_base
                );

            const impostoValorEdicao =
                numeroDespesa(
                    despesa.imposto_valor
                );

            const impostoPercentualEdicao =
                valorBaseEdicao > 0
                    ? (
                        impostoValorEdicao
                        / valorBaseEdicao
                        * 100
                    )
                    : 0;

            $("#despesaImposto")
                .val(
                    impostoPercentualEdicao
                        .toFixed(2)
                );


            $("#despesaTipoLiquidacao")
                .val(
                    despesa.tipo_liquidacao
                    || ""
                );


            $("#despesaCaixa")
                .val(
                    despesa.caixa_id
                    || ""
                );


            $("#despesaBanco")
                .val(
                    despesa.banco_id
                    || ""
                );


            $("#despesaVencimento")
                .val(
                    despesa.data_vencimento
                    || ""
                );


            $("#despesaObservacoes")
                .val(
                    despesa.observacoes
                    || ""
                );


            $("#tituloModalDespesa")
                .text(
                    "Editar Despesa"
                );


            $("#btnGuardarDespesa")
                .text(
                    "Guardar Alterações"
                );


            atualizarCamposLiquidacao();

            atualizarCentroCustoObrigatorio();

            calcularTotalDespesa();


            abrirModalDespesa();

        } catch (erro) {

            console.error(
                "Erro ao buscar despesa:",
                erro
            );


            mostrarErroDespesa(
                obterMensagemErroDespesa(
                    erro,
                    "Não foi possível carregar a despesa."
                )
            );
        }
    }


    // ==================================================
    // CAMPOS DE LIQUIDAÇÃO
    // ==================================================

    function atualizarCamposLiquidacao() {

        const tipo =
            String(
                $("#despesaTipoLiquidacao")
                    .val()
                || ""
            )
                .toUpperCase();


        $("#grupoCaixaDespesa")
            .addClass(
                "d-none"
            );


        $("#grupoBancoDespesa")
            .addClass(
                "d-none"
            );


    

        $("#grupoVencimentoDespesa")
            .addClass(
                "d-none"
            );


        $("#despesaCaixa")
            .prop(
                "required",
                false
            );


        $("#despesaBanco")
            .prop(
                "required",
                false
            );


        $("#despesaFornecedor")
            .prop(
                "required",
                false
            );


        $("#despesaVencimento")
            .prop(
                "required",
                false
            );


        if (
            tipo ===
            "CAIXA"
        ) {

            $("#grupoCaixaDespesa")
                .removeClass(
                    "d-none"
                );

            $("#despesaCaixa")
                .prop(
                    "required",
                    true
                );


            $("#despesaBanco")
                .val(
                    ""
                );


            $("#despesaVencimento")
                .val(
                    ""
                );
        }


        if (
            tipo ===
            "BANCO"
        ) {

            $("#grupoBancoDespesa")
                .removeClass(
                    "d-none"
                );


            $("#despesaBanco")
                .prop(
                    "required",
                    true
                );


            $("#despesaCaixa")
                .val(
                    ""
                );


            $("#despesaVencimento")
                .val(
                    ""
                );
        }
        atualizarCentroCustoObrigatorio();

        if (
            tipo ===
            "CONTA_PAGAR"
        ) {

            $("#grupoVencimentoDespesa")
                .removeClass(
                    "d-none"
                );


            $("#despesaFornecedor")
                .prop(
                    "required",
                    true
                );


            $("#despesaVencimento")
                .prop(
                    "required",
                    true
                );


            $("#despesaCaixa")
                .val(
                    ""
                );


            $("#despesaBanco")
                .val(
                    ""
                );


        }
    }


    // ==================================================
    // CENTRO DE CUSTO
    // ==================================================

    function atualizarCentroCustoObrigatorio() {

        const option =
            $("#despesaContaGasto option:selected");


        const exige =
            Number(
                option.attr(
                    "data-exige-centro-custo"
                )
                || 0
            ) === 1;


        $("#despesaCentroCusto")
            .prop(
                "required",
                exige
            );


        if (exige) {

            $("#avisoCentroCustoDespesa")
                .text(
                    "Esta conta exige centro de custo."
                );

        } else {

            $("#avisoCentroCustoDespesa")
                .text(
                    ""
                );
        }
    }


    // ==================================================
    // CALCULAR TOTAL
    // ==================================================

    // ==================================================
    // CALCULAR TOTAL
    // ==================================================

    function calcularTotalDespesa() {

        const valorBase =
            numeroDespesa(
                $("#despesaValorBase")
                    .val()
            );

        const impostoPercentual =
            numeroDespesa(
                $("#despesaImposto")
                    .val()
            );

        const percentualValido =
            Math.max(
                0,
                Math.min(
                    100,
                    impostoPercentual
                )
            );

        const impostoValor =
            valorBase
            * percentualValido
            / 100;

        const total =
            valorBase
            + impostoValor;

        $("#despesaImpostoValorCalculado")
            .text(
                "Valor do imposto: "
                + formatarMoedaDespesa(
                    impostoValor
                )
            );

        $("#despesaValorTotal")
            .val(
                formatarMoedaDespesa(
                    total
                )
            );

        return total;
    }


    // ==================================================
    // GUARDAR
    // ==================================================

    async function guardarDespesa() {

        if (
            !validarFormularioDespesa()
        ) {

            return;
        }


        const botao =
            $("#btnGuardarDespesa");


        const textoOriginal =
            botao.text();


        botao
            .prop(
                "disabled",
                true
            )
            .text(
                "A guardar..."
            );


        try {

            const dados =
                obterDadosFormularioDespesa();


            const resposta =
                await $.ajax({
                    url: URL_DESPESA,
                    method: "POST",
                    dataType: "json",
                    data: dados
                });


            if (!resposta.success) {

                throw new Error(
                    resposta.mensagem
                    || "Não foi possível guardar a despesa."
                );
            }


            fecharModalDespesa();


            mostrarSucessoDespesa(
                resposta.mensagem
                || "Despesa guardada com sucesso."
            );


            await carregarDespesas();

        } catch (erro) {

            console.error(
                "Erro ao guardar despesa:",
                erro
            );


            mostrarErroDespesa(
                obterMensagemErroDespesa(
                    erro,
                    "Não foi possível guardar a despesa."
                )
            );

        } finally {

            botao
                .prop(
                    "disabled",
                    false
                )
                .text(
                    textoOriginal
                );
        }
    }


    // ==================================================
    // OBTER DADOS
    // ==================================================

    function obterDadosFormularioDespesa() {

        return {
            acao:
                "guardar",

            id:
                $("#despesaId")
                    .val(),

            data_despesa:
                $("#despesaData")
                    .val(),

            numero_documento:
                $("#despesaDocumento")
                    .val()
                    .trim(),

            referencia:
                $("#despesaReferencia")
                    .val()
                    .trim(),

            descricao:
                $("#despesaDescricao")
                    .val()
                    .trim(),

            conta_gasto_id:
                $("#despesaContaGasto")
                    .val(),

            centro_custo_id:
                $("#despesaCentroCusto")
                    .val(),

            fornecedor_id:
                $("#despesaFornecedor")
                    .val(),

            valor_base:
                $("#despesaValorBase")
                    .val(),

            imposto_percentual:
                $("#despesaImposto")
                    .val(),

            tipo_liquidacao:
                $("#despesaTipoLiquidacao")
                    .val(),

            caixa_id:
                $("#despesaCaixa")
                    .val(),

            banco_id:
                $("#despesaBanco")
                    .val(),

            data_vencimento:
                $("#despesaVencimento")
                    .val(),

            observacoes:
                $("#despesaObservacoes")
                    .val()
                    .trim()
        };
    }


    // ==================================================
    // VALIDAR FORMULÁRIO
    // ==================================================

    function validarFormularioDespesa() {

        const descricao =
            $("#despesaDescricao")
                .val()
                .trim();


        const conta =
            Number(
                $("#despesaContaGasto")
                    .val()
                || 0
            );


        const valor =
            numeroDespesa(
                $("#despesaValorBase")
                    .val()
            );


        const impostoPercentual =
            numeroDespesa(
                $("#despesaImposto")
                    .val()
            );


        const data =
            $("#despesaData")
                .val();


        const tipo =
            String(
                $("#despesaTipoLiquidacao")
                    .val()
                || ""
            );


        if (!data) {

            mostrarAvisoDespesa(
                "Informe a data da despesa."
            );

            return false;
        }


        if (!descricao) {

            mostrarAvisoDespesa(
                "Informe a descrição da despesa."
            );

            return false;
        }


        if (conta <= 0) {

            mostrarAvisoDespesa(
                "Selecione a conta de gasto."
            );

            return false;
        }


        if (valor <= 0) {

            mostrarAvisoDespesa(
                "Informe um valor superior a zero."
            );

            return false;
        }


        if (
            impostoPercentual < 0
            || impostoPercentual > 100
        ) {

            mostrarAvisoDespesa(
                "A percentagem do imposto deve estar entre 0% e 100%."
            );

            return false;
        }


        if (
            $("#despesaCentroCusto")
                .prop("required")
            && !$("#despesaCentroCusto")
                .val()
        ) {

            mostrarAvisoDespesa(
                "A conta selecionada exige um centro de custo."
            );

            return false;
        }


        if (!tipo) {

            mostrarAvisoDespesa(
                "Selecione o tipo de liquidação."
            );

            return false;
        }


        if (
            tipo === "CAIXA"
            && !$("#despesaCaixa")
                .val()
        ) {

            mostrarAvisoDespesa(
                "Selecione o caixa."
            );

            return false;
        }


        if (
            tipo === "BANCO"
            && !$("#despesaBanco")
                .val()
        ) {

            mostrarAvisoDespesa(
                "Selecione o banco."
            );

            return false;
        }


        if (
            tipo === "CONTA_PAGAR"
        ) {

            if (
                !$("#despesaFornecedor")
                    .val()
            ) {

                mostrarAvisoDespesa(
                    "Selecione o fornecedor da despesa a prazo."
                );

                return false;
            }


            if (
                !$("#despesaCentroCusto")
                    .val()
            ) {

                mostrarAvisoDespesa(
                    "Selecione o centro de custo da despesa."
                );

                return false;
            }


            if (
                !$("#despesaVencimento")
                    .val()
            ) {

                mostrarAvisoDespesa(
                    "Informe a data de vencimento."
                );

                return false;
            }
        }


        return true;
    }


    // ==================================================
    // LIMPAR FORMULÁRIO
    // ==================================================

    function limparFormularioDespesa() {

        const formulario =
            document.getElementById(
                "formDespesa"
            );


        if (formulario) {

            formulario.reset();
        }


        $("#despesaId")
            .val(
                ""
            );


        $("#despesaImposto")
            .val(
                "0.00"
            );


        $("#despesaValorTotal")
            .val(
                formatarMoedaDespesa(
                    0
                )
            );


        $("#despesaCentroCusto")
            .prop(
                "required",
                false
            );


        $("#despesaFornecedor")
            .prop(
                "required",
                false
            );


        $("#despesaCaixa")
            .prop(
                "required",
                false
            );


        $("#despesaBanco")
            .prop(
                "required",
                false
            );


        $("#despesaVencimento")
            .prop(
                "required",
                false
            );


        $("#avisoCentroCustoDespesa")
            .text(
                ""
            );
    }


    // ==================================================
    // MODAL
    // ==================================================

    // ==================================================
    // MODAL
    // ==================================================

    function abrirModalDespesa() {

        $("#modalDespesa")
            .modal("show");
    }


    function fecharModalDespesa() {

        $("#modalDespesa")
            .modal("hide");
    }



    // ==================================================
    // STATUS
    // ==================================================

    function montarStatusDespesa(
        status
    ) {

        const normalizado =
            normalizarTextoDespesa(
                status
            );


        let classe =
            "bg-secondary";


        if (
            normalizado ===
            "rascunho"
        ) {

            classe =
                "bg-warning text-dark";
        }


        if (
            normalizado ===
            "contabilizada"
        ) {

            classe =
                "bg-success";
        }


        if (
            normalizado ===
            "cancelada"
        ) {

            classe =
                "bg-danger";
        }


        return `
            <span class="badge ${classe}">
                ${escaparHtmlDespesa(
            status
            || "—"
        )}
            </span>
        `;
    }


    // ==================================================
    // FORMATAÇÕES
    // ==================================================

    function formatarLiquidacaoDespesa(
        tipo
    ) {

        switch (
        String(
            tipo
            || ""
        ).toUpperCase()
        ) {

            case "CAIXA":
                return "Caixa";

            case "BANCO":
                return "Banco";

            case "CONTA_PAGAR":
                return "Conta a Pagar";

            default:
                return "—";
        }
    }


    function formatarMoedaDespesa(
        valor
    ) {

        return new Intl.NumberFormat(
            "pt-MZ",
            {
                minimumFractionDigits:
                    2,

                maximumFractionDigits:
                    2
            }
        ).format(
            numeroDespesa(
                valor
            )
        ) + " MZN";
    }


    function formatarDataDespesa(
        data
    ) {

        if (!data) {

            return "—";
        }


        const partes =
            String(data)
                .split("-");


        if (
            partes.length !== 3
        ) {

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


    function numeroDespesa(
        valor
    ) {

        const numero =
            Number(
                valor
                || 0
            );


        return Number.isFinite(
            numero
        )
            ? numero
            : 0;
    }


    function dataAtualDespesa() {

        const hoje =
            new Date();


        const ano =
            hoje.getFullYear();


        const mes =
            String(
                hoje.getMonth()
                + 1
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


    function normalizarTextoDespesa(
        texto
    ) {

        return String(
            texto
            || ""
        )
            .normalize("NFD")
            .replace(
                /[\u0300-\u036f]/g,
                ""
            )
            .trim()
            .toLowerCase();
    }


    function escaparHtmlDespesa(
        texto
    ) {

        return $("<div>")
            .text(
                String(
                    texto
                    ?? ""
                )
            )
            .html();
    }


    // ==================================================
    // MENSAGEM DO AJAX
    // ==================================================

    function obterMensagemErroDespesa(
        erro,
        padrao
    ) {

        if (
            erro
            && erro.responseJSON
            && erro.responseJSON.mensagem
        ) {

            return erro
                .responseJSON
                .mensagem;
        }


        if (
            erro
            && erro.message
        ) {

            return erro.message;
        }


        return padrao;
    }


    // ==================================================
    // ALERTAS
    // ==================================================

    function mostrarSucessoDespesa(
        mensagem
    ) {

        if (
            typeof Swal
            !== "undefined"
        ) {

            Swal.fire({
                icon:
                    "success",

                title:
                    "Sucesso",

                text:
                    mensagem
            });

            return;
        }


        alert(
            mensagem
        );
    }


    function mostrarAvisoDespesa(
        mensagem
    ) {

        if (
            typeof Swal
            !== "undefined"
        ) {

            Swal.fire({
                icon:
                    "warning",

                title:
                    "Atenção",

                text:
                    mensagem
            });

            return;
        }


        alert(
            mensagem
        );
    }


    function mostrarErroDespesa(
        mensagem
    ) {

        if (
            typeof Swal
            !== "undefined"
        ) {

            Swal.fire({
                icon:
                    "error",

                title:
                    "Erro",

                text:
                    mensagem
            });

            return;
        }


        alert(
            mensagem
        );
    }

});