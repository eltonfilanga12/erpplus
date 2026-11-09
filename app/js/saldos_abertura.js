"use strict";

(function ($) {

    const SALDOS_ABERTURA_URL =
        "/erpplus/app/ajax/saldos_abertura_ajax.php";

    let dadosIniciaisAbertura = {
        exercicios: [],
        planos: [],
        filiais: [],
        contas: [],
        filial_sessao_id: null
    };

    let itensAbertura = [];

    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaSaldosAberturaExiste()) {
            return;
        }

        configurarEventosSaldosAbertura();

        carregarDadosIniciaisSaldosAbertura();

        carregarListaSaldosAbertura();
    });

    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaSaldosAberturaExiste() {

        return (
            $("#tabelaSaldosAbertura").length > 0
            && $("#modalSaldoAbertura").length > 0
        );
    }

    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosSaldosAbertura() {

        $(document)
            .on(
                "click",
                ".btn-importar-ativo-abertura",
                function () {

                    importarAtivoAbertura(
                        Number(
                            $(this).data(
                                "ativo-id"
                            )
                        )
                    );
                }
            );

        $(document)
            .on(
                "click",
                ".btn-importar-cliente-abertura",
                function () {

                    importarClienteAbertura(
                        Number(
                            $(this).data(
                                "cliente-id"
                            )
                        )
                    );
                }
            );


        $(document)
            .on(
                "click",
                ".btn-importar-fornecedor-abertura",
                function () {

                    importarFornecedorAbertura(
                        Number(
                            $(this).data(
                                "fornecedor-id"
                            )
                        )
                    );
                }
            );

        $(document)
            .on(
                "click",
                ".btn-importar-caixa-abertura",
                function () {

                    importarCaixaAbertura(
                        Number(
                            $(this).data(
                                "caixa-id"
                            )
                        )
                    );
                }
            );


        $(document)
            .on(
                "click",
                ".btn-importar-banco-abertura",
                function () {

                    importarBancoAbertura(
                        Number(
                            $(this).data(
                                "banco-id"
                            )
                        )
                    );
                }
            );

        $("#btnNovoSaldoAbertura")
            .on("click", function () {

                abrirNovoSaldoAbertura();
            });


        $("#btnAdicionarContaAbertura")
            .on(
                "click",
                function () {

                    adicionarItemManualAbertura();
                }
            );


        $("#btnGuardarSaldoAbertura")
            .on("click", function () {

                guardarSaldoAbertura();
            });


        $("#btnConfirmarSaldoAbertura")
            .on("click", function () {

                confirmarSaldoAbertura();
            });


        $("#btnImportarSaldosAbertura")
            .on(
                "click",
                function () {

                    abrirImportacaoSaldosAbertura();
                }
            );


        $("#saldoAberturaPlanoContasId")
            .on("change", function () {

                preencherSelectContasAbertura();
            });


        $(document)
            .on(
                "input",
                ".item-abertura-debito, .item-abertura-credito",
                function () {

                    const campo =
                        $(this);


                    const linha =
                        campo.closest(
                            "tr"
                        );


                    const debito =
                        numeroSaldosAbertura(
                            linha
                                .find(
                                    ".item-abertura-debito"
                                )
                                .val()
                        );


                    const credito =
                        numeroSaldosAbertura(
                            linha
                                .find(
                                    ".item-abertura-credito"
                                )
                                .val()
                        );


                    if (
                        campo.hasClass(
                            "item-abertura-debito"
                        )
                        && debito > 0
                    ) {

                        linha
                            .find(
                                ".item-abertura-credito"
                            )
                            .val(
                                "0.00"
                            );
                    }


                    if (
                        campo.hasClass(
                            "item-abertura-credito"
                        )
                        && credito > 0
                    ) {

                        linha
                            .find(
                                ".item-abertura-debito"
                            )
                            .val(
                                "0.00"
                            );
                    }


                    sincronizarItensAberturaDaTabela();

                    atualizarTotaisAbertura();
                }
            );


        $(document)
            .on(
                "change",
                ".item-abertura-conta",
                function () {

                    orientarNaturezaContaAbertura(
                        $(this)
                    );

                    atualizarInformacaoContaAbertura(
                        $(this)
                    );

                    sincronizarItensAberturaDaTabela();

                    atualizarTotaisAbertura();
                }
            );


        $(document)
            .on(
                "click",
                ".btn-remover-item-abertura",
                function () {

                    const indice =
                        Number(
                            $(this).data("indice")
                        );

                    removerItemAbertura(
                        indice
                    );
                }
            );


        $(document)
            .on(
                "click",
                ".btn-editar-saldo-abertura",
                function () {

                    const id =
                        Number(
                            $(this).data("id")
                        );

                    buscarSaldoAbertura(
                        id
                    );
                }
            );


        $(document)
            .on(
                "click",
                ".btn-importar-stock-abertura",
                function () {

                    const movimentoId =
                        Number(
                            $(this).data(
                                "movimento-id"
                            )
                        );

                    importarMovimentoStockAbertura(
                        movimentoId
                    );
                }
            );

        $("#modalEntradasStockAbertura")
            .on(
                "hidden.bs.modal",
                function () {

                    /*
                     * Se ainda estivermos trabalhando
                     * numa abertura, volta ao modal principal.
                     */
                    $("#modalSaldoAbertura")
                        .modal(
                            "show"
                        );
                }
            );
    }

    async function importarAtivoAbertura(
        ativoId
    ) {

        if (
            itemImportadoJaExiste(
                "ATIVO",
                ativoId
            )
        ) {

            mostrarErroSaldosAbertura(
                "Este ativo já foi importado."
            );

            return;
        }


        const planoId =
            $("#saldoAberturaPlanoContasId")
                .val();


        const data =
            $("#saldoAberturaData")
                .val();


        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "importar_ativo",

                    plano_contas_id:
                        planoId,

                    ativo_id:
                        ativoId,

                    data_abertura:
                        data
                });


            const resposta =
                await requisicaoSaldosAbertura(
                    SALDOS_ABERTURA_URL
                    + "?"
                    + parametros.toString()
                );


            const dados =
                resposta.data
                || {};


            const novosItens =
                Array.isArray(
                    dados.itens
                )
                    ? dados.itens
                    : [];


            if (!novosItens.length) {

                throw new Error(
                    "O ativo não gerou itens para a abertura."
                );
            }


            novosItens.forEach(
                function (item) {

                    itensAbertura.push({

                        conta_contabil_id:
                            item.conta_contabil_id,

                        centro_custo_id:
                            item.centro_custo_id
                            ?? null,

                        descricao:
                            item.descricao
                            || "",

                        debito:
                            Number(
                                item.debito
                                || 0
                            ),

                        credito:
                            Number(
                                item.credito
                                || 0
                            ),

                        origem:
                            "ATIVO",

                        referencia_id:
                            ativoId
                    });
                }
            );


            renderizarItensAbertura();

            atualizarTotaisAbertura();


            $("#modalImportarSaldosAbertura")
                .modal(
                    "hide"
                );


            Swal.fire({
                icon:
                    "success",

                title:
                    "Ativo importado",

                html:
                    "Valor de aquisição: <strong>"
                    + formatarMoedaSaldosAbertura(
                        dados.valor_aquisicao
                        || 0
                    )
                    + "</strong><br>"
                    + "Depreciação/Amortização acumulada: <strong>"
                    + formatarMoedaSaldosAbertura(
                        dados.depreciacao_acumulada
                        || 0
                    )
                    + "</strong><br>"
                    + "Valor líquido: <strong>"
                    + formatarMoedaSaldosAbertura(
                        dados.valor_liquido
                        || 0
                    )
                    + "</strong>"
            });


        } catch (erro) {

            mostrarErroSaldosAbertura(
                erro.message
            );
        }
    }

    async function importarCaixaAbertura(
        caixaId
    ) {

        const planoId =
            $("#saldoAberturaPlanoContasId")
                .val();


        const data =
            $("#saldoAberturaData")
                .val();


        const duplicado =
            itensAbertura.some(
                item =>
                    item.origem === "CAIXA"
                    && Number(
                        item.referencia_id
                    ) === Number(
                        caixaId
                    )
            );


        if (duplicado) {

            mostrarErroSaldosAbertura(
                "Este caixa já foi importado."
            );

            return;
        }


        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "importar_caixa",

                    plano_contas_id:
                        planoId,

                    caixa_id:
                        caixaId,

                    data_abertura:
                        data
                });


            const resposta =
                await requisicaoSaldosAbertura(
                    SALDOS_ABERTURA_URL
                    + "?"
                    + parametros.toString()
                );


            adicionarItemImportadoAbertura(
                resposta.data
            );


            $("#modalImportarSaldosAbertura")
                .modal(
                    "hide"
                );


        } catch (erro) {

            mostrarErroSaldosAbertura(
                erro.message
            );
        }
    }


    async function importarBancoAbertura(
        bancoId
    ) {

        const planoId =
            $("#saldoAberturaPlanoContasId")
                .val();


        const data =
            $("#saldoAberturaData")
                .val();


        const duplicado =
            itensAbertura.some(
                item =>
                    item.origem === "BANCO"
                    && Number(
                        item.referencia_id
                    ) === Number(
                        bancoId
                    )
            );


        if (duplicado) {

            mostrarErroSaldosAbertura(
                "Esta conta bancária já foi importada."
            );

            return;
        }


        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "importar_banco",

                    plano_contas_id:
                        planoId,

                    banco_id:
                        bancoId,

                    data_abertura:
                        data
                });


            const resposta =
                await requisicaoSaldosAbertura(
                    SALDOS_ABERTURA_URL
                    + "?"
                    + parametros.toString()
                );


            adicionarItemImportadoAbertura(
                resposta.data
            );


            $("#modalImportarSaldosAbertura")
                .modal(
                    "hide"
                );


        } catch (erro) {

            mostrarErroSaldosAbertura(
                erro.message
            );
        }
    }

    function adicionarItemImportadoAbertura(
        item
    ) {

        if (!item) {
            return;
        }


        itensAbertura.push({
            conta_contabil_id:
                item.conta_contabil_id,

            centro_custo_id:
                item.centro_custo_id
                ?? null,

            descricao:
                item.descricao
                || "",

            debito:
                Number(
                    item.debito
                    || 0
                ),

            credito:
                Number(
                    item.credito
                    || 0
                ),

            origem:
                item.origem
                || "MANUAL",

            referencia_id:
                item.referencia_id
                ?? null
        });


        renderizarItensAbertura();

        atualizarTotaisAbertura();
    }

    async function abrirImportacaoSaldosAbertura() {

        const data =
            $("#saldoAberturaData")
                .val();


        if (!data) {

            mostrarErroSaldosAbertura(
                "Informe primeiro a data de abertura."
            );

            return;
        }


        const planoId =
            $("#saldoAberturaPlanoContasId")
                .val();


        if (!planoId) {

            mostrarErroSaldosAbertura(
                "Selecione primeiro o plano de contas."
            );

            return;
        }


        try {

            $("#btnImportarSaldosAbertura")
                .prop(
                    "disabled",
                    true
                );


            /*
             * Carregamos as três origens
             * antes de abrir o modal.
             */
            await Promise.all([
                carregarStockImportacaoAbertura(),
                carregarCaixasImportacaoAbertura(),
                carregarBancosImportacaoAbertura(),
                carregarClientesImportacaoAbertura(),
                carregarFornecedoresImportacaoAbertura(),
                carregarAtivosImportacaoAbertura()
            ]);

            $("#modalSaldoAbertura")
                .modal(
                    "hide"
                );


            $("#modalSaldoAbertura")
                .one(
                    "hidden.bs.modal",
                    function () {

                        $("#modalImportarSaldosAbertura")
                            .modal(
                                "show"
                            );
                    }
                );


        } catch (erro) {

            mostrarErroSaldosAbertura(
                erro.message
            );


        } finally {

            $("#btnImportarSaldosAbertura")
                .prop(
                    "disabled",
                    false
                );
        }
    }

    async function carregarStockImportacaoAbertura() {

        const data =
            $("#saldoAberturaData")
                .val();


        const parametros =
            new URLSearchParams({
                acao:
                    "entradas_stock",

                data_inicial:
                    data,

                data_final:
                    data,

                filial_id:
                    $("#saldoAberturaFilialId")
                        .val()
                    || ""
            });


        const resposta =
            await requisicaoSaldosAbertura(
                SALDOS_ABERTURA_URL
                + "?"
                + parametros.toString()
            );


        preencherTabelaEntradasStockAbertura(
            resposta.data || []
        );
    }

    async function carregarCaixasImportacaoAbertura() {

        const parametros =
            new URLSearchParams({
                acao:
                    "listar_caixas_abertura",

                data_abertura:
                    $("#saldoAberturaData")
                        .val(),

                filial_id:
                    $("#saldoAberturaFilialId")
                        .val()
                    || ""
            });


        const resposta =
            await requisicaoSaldosAbertura(
                SALDOS_ABERTURA_URL
                + "?"
                + parametros.toString()
            );


        preencherTabelaCaixasAbertura(
            resposta.data || []
        );
    }

    async function carregarBancosImportacaoAbertura() {

        const parametros =
            new URLSearchParams({
                acao:
                    "listar_bancos_abertura",

                data_abertura:
                    $("#saldoAberturaData")
                        .val(),

                filial_id:
                    $("#saldoAberturaFilialId")
                        .val()
                    || ""
            });


        const resposta =
            await requisicaoSaldosAbertura(
                SALDOS_ABERTURA_URL
                + "?"
                + parametros.toString()
            );


        preencherTabelaBancosAbertura(
            resposta.data || []
        );
    }

    function preencherTabelaCaixasAbertura(
        caixas
    ) {

        const tbody =
            $("#tabelaCaixasAbertura tbody");


        tbody.empty();


        if (!caixas.length) {

            tbody.html(`
            <tr>

                <td
                    colspan="5"
                    class="text-center text-muted py-4">

                    Nenhum saldo de caixa existente
                    antes da data de abertura.

                </td>

            </tr>
        `);

            return;
        }


        caixas.forEach(
            function (caixa) {

                tbody.append(`
                <tr>

                    <td>
                        ${escaparHtmlSaldosAbertura(
                    caixa.codigo
                    || "-"
                )}
                    </td>

                    <td>
                        ${escaparHtmlSaldosAbertura(
                    caixa.nome
                    || "-"
                )}
                    </td>

                    <td class="text-right">

                        ${formatarMoedaSaldosAbertura(
                    caixa.saldo_inicial
                    || 0
                )}

                    </td>

                    <td class="text-right">

                        <strong>
                            ${formatarMoedaSaldosAbertura(
                    caixa.saldo_data
                    || 0
                )}
                        </strong>

                    </td>

                    <td class="text-center">

                        <button
                            type="button"
                            class="btn btn-sm btn-primary btn-importar-caixa-abertura"

                            data-caixa-id="${Number(
                    caixa.id
                )}"

                            data-saldo="${Number(
                    caixa.saldo_data
                    || 0
                )}">

                            Importar

                        </button>

                    </td>

                </tr>
            `);
            }
        );
    }

    function atualizarInformacaoContaAbertura(
        select
    ) {

        const linha =
            select.closest(
                "tr"
            );


        const opcao =
            select.find(
                "option:selected"
            );


        const natureza =
            String(
                opcao.data(
                    "natureza"
                )
                || ""
            );


        const grupo =
            String(
                opcao.data(
                    "grupo"
                )
                || ""
            );


        let texto = "";


        if (grupo) {

            texto +=
                grupo;
        }


        if (natureza) {

            texto +=
                (
                    texto
                        ? " • "
                        : ""
                )
                + natureza;
        }


        linha
            .find(
                ".item-abertura-info-conta"
            )
            .text(
                texto
            );
    }

    function preencherTabelaBancosAbertura(
        bancos
    ) {

        const tbody =
            $("#tabelaBancosAbertura tbody");


        tbody.empty();


        if (!bancos.length) {

            tbody.html(`
            <tr>

                <td
                    colspan="6"
                    class="text-center text-muted py-4">

                    Nenhum saldo bancário existente
                    antes da data de abertura.

                </td>

            </tr>
        `);

            return;
        }


        bancos.forEach(
            function (banco) {

                tbody.append(`
                <tr>

                    <td>
                        ${escaparHtmlSaldosAbertura(
                    banco.nome_banco
                    || "-"
                )}
                    </td>

                    <td>
                        ${escaparHtmlSaldosAbertura(
                    banco.conta
                    || "-"
                )}
                    </td>

                    <td>
                        ${escaparHtmlSaldosAbertura(
                    banco.tipo_conta
                    || "-"
                )}
                    </td>

                    <td>
                        ${escaparHtmlSaldosAbertura(
                    banco.moeda
                    || "MZN"
                )}
                    </td>

                    <td class="text-right">

                        <strong>
                            ${formatarMoedaSaldosAbertura(
                    banco.saldo_data
                    || 0
                )}
                        </strong>

                    </td>

                    <td class="text-center">

                        <button
                            type="button"
                            class="btn btn-sm btn-primary btn-importar-banco-abertura"

                            data-banco-id="${Number(
                    banco.id
                )}"

                            data-saldo="${Number(
                    banco.saldo_data
                    || 0
                )}">

                            Importar

                        </button>

                    </td>

                </tr>
            `);
            }
        );
    }

    async function carregarAtivosImportacaoAbertura() {

        const parametros =
            new URLSearchParams({
                acao:
                    "listar_ativos_abertura",

                data_abertura:
                    $("#saldoAberturaData")
                        .val(),

                filial_id:
                    $("#saldoAberturaFilialId")
                        .val()
                    || ""
            });


        const resposta =
            await requisicaoSaldosAbertura(
                SALDOS_ABERTURA_URL
                + "?"
                + parametros.toString()
            );


        preencherTabelaAtivosAbertura(
            resposta.data
            || []
        );
    }

    function preencherTabelaAtivosAbertura(
        ativos
    ) {

        const tbody =
            $("#tabelaAtivosAbertura tbody");


        tbody.empty();


        if (!ativos.length) {

            tbody.html(`
            <tr>

                <td
                    colspan="8"
                    class="text-center text-muted py-4">

                    Nenhum ativo anterior
                    à data de abertura.

                </td>

            </tr>
        `);

            return;
        }


        ativos.forEach(
            function (ativo) {

                const depreciacao =
                    Number(
                        ativo.depreciacao_acumulada
                        || 0
                    );


                const configuracaoOk =
                    depreciacao <= 0
                    || Number(
                        ativo.conta_acumulada_id
                        || 0
                    ) > 0;


                const botao =
                    configuracaoOk
                        ? `
                        <button
                            type="button"
                            class="btn btn-sm btn-primary btn-importar-ativo-abertura"
                            data-ativo-id="${Number(
                            ativo.id
                        )}">

                            Importar

                        </button>
                    `
                        : `
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary"
                            disabled
                            title="Configure a conta de depreciação/amortização acumulada">

                            Configuração pendente

                        </button>
                    `;


                tbody.append(`
                <tr>

                    <td>

                        ${escaparHtmlSaldosAbertura(
                    ativo.codigo
                    || "-"
                )}

                    </td>


                    <td>

                        <strong>
                            ${escaparHtmlSaldosAbertura(
                    ativo.descricao
                    || "-"
                )}
                        </strong>

                        ${ativo.marca
                        ? `
                                    <br>
                                    <small class="text-muted">

                                        ${escaparHtmlSaldosAbertura(
                            ativo.marca
                        )}

                                        ${ativo.modelo
                            ? " — "
                            + escaparHtmlSaldosAbertura(
                                ativo.modelo
                            )
                            : ""
                        }

                                    </small>
                                `
                        : ""
                    }

                    </td>


                    <td>

                        ${escaparHtmlSaldosAbertura(
                        ativo.tipo
                        || "-"
                    )}

                    </td>


                    <td>

                        ${escaparHtmlSaldosAbertura(
                        (
                            ativo.conta_codigo
                            || ""
                        )
                        + " — "
                        + (
                            ativo.conta_nome
                            || ""
                        )
                    )}

                    </td>


                    <td class="text-right">

                        ${formatarMoedaSaldosAbertura(
                        ativo.valor_aquisicao
                        || 0
                    )}

                    </td>


                    <td class="text-right">

                        ${formatarMoedaSaldosAbertura(
                        ativo.depreciacao_acumulada
                        || 0
                    )}

                    </td>


                    <td class="text-right font-weight-bold">

                        ${formatarMoedaSaldosAbertura(
                        ativo.valor_liquido
                        || 0
                    )}

                    </td>


                    <td class="text-center">

                        ${botao}

                    </td>

                </tr>
            `);
            }
        );
    }

    function existeContaDuplicadaAbertura() {

        const usadas =
            new Set();


        for (
            const item
            of itensAbertura
        ) {

            const contaId =
                String(
                    item.conta_contabil_id
                    || ""
                );


            if (!contaId) {
                continue;
            }


            if (
                usadas.has(
                    contaId
                )
            ) {
                return true;
            }


            usadas.add(
                contaId
            );
        }


        return false;
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisSaldosAbertura() {

        try {

            const resposta =
                await requisicaoSaldosAbertura(
                    SALDOS_ABERTURA_URL
                    + "?acao=dados_iniciais"
                );

            dadosIniciaisAbertura =
                resposta.data || {};

            preencherExerciciosAbertura();
            preencherPlanosAbertura();
            preencherFiliaisAbertura();
            preencherSelectContasAbertura();

        } catch (erro) {

            console.error(
                erro
            );

            mostrarErroSaldosAbertura(
                erro.message
            );
        }
    }

    function itemImportadoJaExiste(
        origem,
        referenciaId
    ) {

        return itensAbertura.some(
            item =>
                String(
                    item.origem
                    || ""
                ).toUpperCase()
                === String(
                    origem
                    || ""
                ).toUpperCase()

                && Number(
                    item.referencia_id
                    || 0
                )
                === Number(
                    referenciaId
                    || 0
                )
        );
    }

    // ==================================================
    // LISTAR ABERTURAS
    // ==================================================

    async function carregarListaSaldosAbertura() {

        try {

            const resposta =
                await requisicaoSaldosAbertura(
                    SALDOS_ABERTURA_URL
                    + "?acao=listar"
                );

            preencherTabelaSaldosAbertura(
                resposta.data || []
            );

        } catch (erro) {

            console.error(
                erro
            );

            mostrarErroSaldosAbertura(
                erro.message
            );
        }
    }

    function orientarNaturezaContaAbertura(
        select
    ) {

        const linha =
            select.closest("tr");


        const contaId =
            select.val();


        if (!contaId) {
            return;
        }


        const conta =
            (
                dadosIniciaisAbertura
                    .contas
                || []
            )
                .find(
                    item =>
                        String(item.id)
                        === String(contaId)
                );


        if (!conta) {
            return;
        }

        const indice =
            Number(
                linha.data(
                    "indice"
                )
            );


        if (
            !Number.isNaN(indice)
            && itensAbertura[indice]
        ) {

            const origem =
                determinarOrigemContaAbertura(
                    conta
                );


            /*
             * Não altera uma linha STOCK
             * que veio de importação automática.
             */
            if (
                itensAbertura[indice]
                    .origem !== "STOCK"
            ) {

                itensAbertura[indice]
                    .origem =
                    origem;


                linha
                    .find(
                        "td"
                    )
                    .eq(4)
                    .text(
                        origem
                    );
            }
        }


        const natureza =
            String(
                conta.natureza
                || ""
            )
                .toUpperCase();


        const inputDebito =
            linha.find(
                ".item-abertura-debito"
            );


        const inputCredito =
            linha.find(
                ".item-abertura-credito"
            );


        if (
            natureza === "DEVEDORA"
        ) {

            inputDebito
                .addClass(
                    "border-primary"
                );

            inputCredito
                .removeClass(
                    "border-primary"
                );


            inputDebito
                .attr(
                    "placeholder",
                    "Valor preferencial"
                );


            inputCredito
                .attr(
                    "placeholder",
                    "0,00"
                );

        } else if (
            natureza === "CREDORA"
        ) {

            inputCredito
                .addClass(
                    "border-primary"
                );

            inputDebito
                .removeClass(
                    "border-primary"
                );


            inputCredito
                .attr(
                    "placeholder",
                    "Valor preferencial"
                );


            inputDebito
                .attr(
                    "placeholder",
                    "0,00"
                );

        } else {

            inputDebito
                .removeClass(
                    "border-primary"
                );

            inputCredito
                .removeClass(
                    "border-primary"
                );
        }
    }

    // ==================================================
    // NOVO
    // ==================================================

    function abrirNovoSaldoAbertura() {

        $("#saldoAberturaId")
            .val("");

        $("#saldoAberturaDescricao")
            .val("");

        itensAbertura = [];

        definirExercicioPadraoAbertura();

        definirPlanoPadraoAbertura();

        definirFilialPadraoAbertura();

        definirDataPadraoAbertura();

        renderizarItensAbertura();

        atualizarTotaisAbertura();

        $("#btnConfirmarSaldoAbertura")
            .prop(
                "disabled",
                true
            );

        $("#modalSaldoAbertura")
            .modal("show");
    }

    // ==================================================
    // EXERCÍCIOS
    // ==================================================

    function preencherExerciciosAbertura() {

        const select =
            $("#saldoAberturaExercicioId");

        select.empty();

        select.append(`
            <option value="">
                Selecione o exercício
            </option>
        `);

        (
            dadosIniciaisAbertura.exercicios
            || []
        )
            .forEach(
                function (exercicio) {

                    select.append(`
                        <option value="${Number(exercicio.id)}">
                            ${escaparHtmlSaldosAbertura(
                        exercicio.codigo
                        + " - "
                        + exercicio.descricao
                    )}
                        </option>
                    `);
                }
            );
    }

    async function carregarClientesImportacaoAbertura() {

        const parametros =
            new URLSearchParams({
                acao:
                    "listar_clientes_abertura",

                data_abertura:
                    $("#saldoAberturaData").val(),

                filial_id:
                    $("#saldoAberturaFilialId").val()
                    || ""
            });

        const resposta =
            await requisicaoSaldosAbertura(
                SALDOS_ABERTURA_URL
                + "?"
                + parametros.toString()
            );

        preencherTabelaClientesAbertura(
            resposta.data || []
        );
    }


    async function carregarFornecedoresImportacaoAbertura() {

        const parametros =
            new URLSearchParams({
                acao:
                    "listar_fornecedores_abertura",

                data_abertura:
                    $("#saldoAberturaData").val(),

                filial_id:
                    $("#saldoAberturaFilialId").val()
                    || ""
            });

        const resposta =
            await requisicaoSaldosAbertura(
                SALDOS_ABERTURA_URL
                + "?"
                + parametros.toString()
            );

        preencherTabelaFornecedoresAbertura(
            resposta.data || []
        );
    }

    function preencherTabelaClientesAbertura(
        clientes
    ) {

        const tbody =
            $("#tabelaClientesAbertura tbody");

        tbody.empty();

        if (!clientes.length) {

            tbody.html(`
            <tr>
                <td
                    colspan="5"
                    class="text-center text-muted py-4">

                    Nenhum saldo de cliente anterior
                    à data de abertura.

                </td>
            </tr>
        `);

            return;
        }

        clientes.forEach(
            function (cliente) {

                tbody.append(`
                <tr>

                    <td>
                        ${escaparHtmlSaldosAbertura(
                    cliente.nome || "-"
                )}
                    </td>

                    <td>
                        ${escaparHtmlSaldosAbertura(
                    cliente.nuit || "-"
                )}
                    </td>

                    <td class="text-center">
                        ${Number(
                    cliente.documentos || 0
                )}
                    </td>

                    <td class="text-right">
                        <strong>
                            ${formatarMoedaSaldosAbertura(
                    cliente.saldo_data || 0
                )}
                        </strong>
                    </td>

                    <td class="text-center">

                        <button
                            type="button"
                            class="btn btn-sm btn-primary btn-importar-cliente-abertura"
                            data-cliente-id="${Number(
                    cliente.id
                )}">

                            Importar

                        </button>

                    </td>

                </tr>
            `);
            }
        );
    }

    function preencherTabelaFornecedoresAbertura(
        fornecedores
    ) {

        const tbody =
            $("#tabelaFornecedoresAbertura tbody");

        tbody.empty();

        if (!fornecedores.length) {

            tbody.html(`
            <tr>
                <td
                    colspan="5"
                    class="text-center text-muted py-4">

                    Nenhum saldo de fornecedor anterior
                    à data de abertura.

                </td>
            </tr>
        `);

            return;
        }

        fornecedores.forEach(
            function (fornecedor) {

                tbody.append(`
                <tr>

                    <td>
                        ${escaparHtmlSaldosAbertura(
                    fornecedor.nome || "-"
                )}
                    </td>

                    <td>
                        ${escaparHtmlSaldosAbertura(
                    fornecedor.nuit || "-"
                )}
                    </td>

                    <td class="text-center">
                        ${Number(
                    fornecedor.documentos || 0
                )}
                    </td>

                    <td class="text-right">
                        <strong>
                            ${formatarMoedaSaldosAbertura(
                    fornecedor.saldo_data || 0
                )}
                        </strong>
                    </td>

                    <td class="text-center">

                        <button
                            type="button"
                            class="btn btn-sm btn-primary btn-importar-fornecedor-abertura"
                            data-fornecedor-id="${Number(
                    fornecedor.id
                )}">

                            Importar

                        </button>

                    </td>

                </tr>
            `);
            }
        );
    }

    function definirExercicioPadraoAbertura() {

        const exercicios =
            dadosIniciaisAbertura
                .exercicios
            || [];

        if (!exercicios.length) {
            return;
        }

        $("#saldoAberturaExercicioId")
            .val(
                exercicios[0].id
            );
    }

    // ==================================================
    // PLANOS
    // ==================================================

    function preencherPlanosAbertura() {

        const select =
            $("#saldoAberturaPlanoContasId");

        select.empty();

        select.append(`
            <option value="">
                Selecione o plano
            </option>
        `);

        (
            dadosIniciaisAbertura.planos
            || []
        )
            .forEach(
                function (plano) {

                    const selecionado =
                        Number(
                            plano.padrao || 0
                        ) === 1
                            ? "selected"
                            : "";

                    select.append(`
                        <option
                            value="${Number(plano.id)}"
                            ${selecionado}>

                            ${escaparHtmlSaldosAbertura(
                        (
                            plano.codigo
                                ? plano.codigo + " - "
                                : ""
                        )
                        + plano.nome
                    )}

                        </option>
                    `);
                }
            );
    }

    function definirPlanoPadraoAbertura() {

        const planos =
            dadosIniciaisAbertura
                .planos
            || [];

        const planoPadrao =
            planos.find(
                plano =>
                    Number(
                        plano.padrao || 0
                    ) === 1
            );

        if (planoPadrao) {

            $("#saldoAberturaPlanoContasId")
                .val(
                    planoPadrao.id
                );

        } else if (planos.length) {

            $("#saldoAberturaPlanoContasId")
                .val(
                    planos[0].id
                );
        }

        preencherSelectContasAbertura();
    }

    // ==================================================
    // FILIAIS
    // ==================================================

    function preencherFiliaisAbertura() {

        const select =
            $("#saldoAberturaFilialId");

        select.empty();

        const filialSessaoId =
            dadosIniciaisAbertura
                .filial_sessao_id
            ?? null;

        if (filialSessaoId) {

            select.append(`
                <option
                    value="${Number(
                filialSessaoId
            )}"
                    selected>

                    Filial da sessão

                </option>
            `);

            select.prop(
                "disabled",
                true
            );

            return;
        }

        select.prop(
            "disabled",
            false
        );

        select.append(`
            <option value="">
                Todas / Sem filial específica
            </option>
        `);

        (
            dadosIniciaisAbertura.filiais
            || []
        )
            .forEach(
                function (filial) {

                    select.append(`
                        <option value="${Number(filial.id)}">

                            ${escaparHtmlSaldosAbertura(
                        (
                            filial.codigo
                                ? filial.codigo + " - "
                                : ""
                        )
                        + filial.nome
                    )}

                        </option>
                    `);
                }
            );
    }

    function definirFilialPadraoAbertura() {

        const filialSessaoId =
            dadosIniciaisAbertura
                .filial_sessao_id
            ?? null;

        if (filialSessaoId) {

            $("#saldoAberturaFilialId")
                .val(
                    filialSessaoId
                );
        }
    }

    // ==================================================
    // DATA
    // ==================================================

    function definirDataPadraoAbertura() {

        const exercicioId =
            $("#saldoAberturaExercicioId")
                .val();

        const exercicio =
            (
                dadosIniciaisAbertura
                    .exercicios
                || []
            )
                .find(
                    item =>
                        String(item.id)
                        === String(
                            exercicioId
                        )
                );

        if (exercicio) {

            $("#saldoAberturaData")
                .val(
                    exercicio.data_inicio
                );

            return;
        }

        const hoje =
            new Date();

        const data =
            hoje
                .toISOString()
                .slice(
                    0,
                    10
                );

        $("#saldoAberturaData")
            .val(
                data
            );
    }

    // ==================================================
    // CONTAS
    // ==================================================

    function contasPlanoSelecionadoAbertura() {

        const planoId =
            $("#saldoAberturaPlanoContasId")
                .val();

        if (!planoId) {
            return [];
        }

        return (
            dadosIniciaisAbertura.contas
            || []
        )
            .filter(
                conta =>
                    String(
                        conta.plano_contas_id
                    )
                    === String(
                        planoId
                    )
            );
    }

    function preencherSelectContasAbertura() {

        renderizarItensAbertura();
    }

    function htmlOpcoesContasAbertura(
        valorSelecionado = ""
    ) {

        const contas =
            contasPlanoSelecionadoAbertura();

        const grupos = {};


        contas.forEach(
            function (conta) {

                const classe =
                    String(
                        conta.classe
                        || String(
                            conta.codigo
                            || ""
                        ).split(".")[0]
                        || "OUTROS"
                    );


                if (!grupos[classe]) {

                    grupos[classe] = {
                        nome:
                            conta.classe_nome
                            || nomeClasseContabilAbertura(
                                classe
                            ),

                        contas:
                            []
                    };
                }


                grupos[classe]
                    .contas
                    .push(
                        conta
                    );
            }
        );


        const html = [
            `<option value="">Selecione a conta contabilística</option>`
        ];


        Object.keys(grupos)
            .sort(
                function (a, b) {

                    return String(a)
                        .localeCompare(
                            String(b),
                            undefined,
                            {
                                numeric: true
                            }
                        );
                }
            )
            .forEach(
                function (classe) {

                    const grupo =
                        grupos[classe];


                    html.push(`
                    <optgroup
                        label="${escaparAtributoSaldosAbertura(
                        "CLASSE "
                        + classe
                        + " — "
                        + grupo.nome
                    )}">
                `);


                    grupo.contas
                        .sort(
                            function (a, b) {

                                return String(
                                    a.codigo
                                )
                                    .localeCompare(
                                        String(
                                            b.codigo
                                        ),
                                        undefined,
                                        {
                                            numeric: true
                                        }
                                    );
                            }
                        )
                        .forEach(
                            function (conta) {

                                const selecionado =
                                    String(conta.id)
                                        === String(
                                            valorSelecionado
                                        )
                                        ? "selected"
                                        : "";


                                const grupoConta =
                                    String(
                                        conta.grupo
                                        || ""
                                    )
                                        .replace(
                                            /_/g,
                                            " "
                                        );


                                const natureza =
                                    String(
                                        conta.natureza
                                        || ""
                                    );


                                html.push(`
                                <option
                                    value="${Number(
                                    conta.id
                                )}"

                                    data-natureza="${escaparAtributoSaldosAbertura(
                                    natureza
                                )}"

                                    data-grupo="${escaparAtributoSaldosAbertura(
                                    grupoConta
                                )}"

                                    data-classe="${escaparAtributoSaldosAbertura(
                                    classe
                                )}"

                                    ${selecionado}>

                                    ${escaparHtmlSaldosAbertura(
                                    conta.codigo
                                    + " — "
                                    + conta.nome
                                    + (
                                        natureza
                                            ? " [" + natureza + "]"
                                            : ""
                                    )
                                )}

                                </option>
                            `);
                            }
                        );


                    html.push(
                        "</optgroup>"
                    );
                }
            );


        return html.join("");
    }

    function nomeClasseContabilAbertura(
        classe
    ) {

        const nomes = {

            "1":
                "Meios financeiros",

            "2":
                "Inventários",

            "3":
                "Ativos não correntes",

            "4":
                "Terceiros",

            "5":
                "Capital próprio",

            "6":
                "Gastos e perdas",

            "7":
                "Rendimentos e ganhos",

            "8":
                "Resultados",

            "9":
                "Contas diversas"
        };


        return nomes[
            String(classe)
        ]
            || "Outras contas";
    }

    // ==================================================
    // ADICIONAR ITEM MANUAL
    // ==================================================

    function adicionarItemManualAbertura() {

        itensAbertura.push({
            conta_contabil_id:
                "",

            centro_custo_id:
                null,

            descricao:
                "",

            debito:
                0,

            credito:
                0,

            origem:
                "MANUAL",

            referencia_id:
                null
        });


        renderizarItensAbertura();

        atualizarTotaisAbertura();
    }

    // ==================================================
    // REMOVER ITEM
    // ==================================================

    function removerItemAbertura(
        indice
    ) {

        sincronizarItensAberturaDaTabela();

        if (
            indice < 0
            || indice >= itensAbertura.length
        ) {
            return;
        }

        itensAbertura.splice(
            indice,
            1
        );

        renderizarItensAbertura();

        atualizarTotaisAbertura();
    }

    // ==================================================
    // RENDERIZAR ITENS
    // ==================================================

    function renderizarItensAbertura() {

        const tbody =
            $("#tabelaItensAbertura tbody");

        tbody.empty();

        if (!itensAbertura.length) {

            tbody.html(`
                <tr>
                    <td
                        colspan="7"
                        class="text-center text-muted py-4">

                        Nenhum item adicionado.

                    </td>
                </tr>
            `);

            atualizarTotaisAbertura();

            return;
        }

        itensAbertura.forEach(
            function (item, indice) {

                const bloqueadoStock =
                    item.origem === "STOCK";

                tbody.append(`
                    <tr
                        data-indice="${indice}">

                        <td>

                            <select
                                class="form-control item-abertura-conta"
                                ${bloqueadoStock ? "disabled" : ""}>

                                ${htmlOpcoesContasAbertura(
                    item.conta_contabil_id
                )}

                            </select>

                        </td>

                        <td>

                            <input
                                type="text"
                                class="form-control item-abertura-descricao"
                                value="${escaparAtributoSaldosAbertura(
                    item.descricao
                    || ""
                )}">

                        </td>

                        <td>

                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                class="form-control text-right item-abertura-debito"
                                value="${Number(
                    item.debito || 0
                ).toFixed(2)}">

                        </td>

                        <td>

                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                class="form-control text-right item-abertura-credito"
                                value="${Number(
                    item.credito || 0
                ).toFixed(2)}">

                        </td>

                        <td class="text-center">

                            ${escaparHtmlSaldosAbertura(
                    item.origem
                    || "MANUAL"
                )}

                        </td>

                        <td class="text-center">

                            ${item.referencia_id
                        ? "#"
                        + escaparHtmlSaldosAbertura(
                            item.referencia_id
                        )
                        : "-"
                    }

                        </td>

                        <td class="text-center">

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger btn-remover-item-abertura"
                                data-indice="${indice}">

                                <i class="fa fa-trash"></i>

                            </button>

                        </td>

                    </tr>
                `);
            }
        );

        atualizarTotaisAbertura();
    }

    // ==================================================
    // SINCRONIZAR TABELA -> ARRAY
    // ==================================================

    function sincronizarItensAberturaDaTabela() {

        const novosItens = [];

        $("#tabelaItensAbertura tbody tr")
            .each(
                function () {

                    const linha =
                        $(this);

                    const indice =
                        Number(
                            linha.data(
                                "indice"
                            )
                        );

                    if (
                        Number.isNaN(
                            indice
                        )
                        || !itensAbertura[indice]
                    ) {
                        return;
                    }

                    const itemOriginal =
                        itensAbertura[indice];

                    novosItens.push({
                        conta_contabil_id:
                            linha
                                .find(
                                    ".item-abertura-conta"
                                )
                                .val()
                            || itemOriginal
                                .conta_contabil_id,

                        centro_custo_id:
                            itemOriginal
                                .centro_custo_id
                            ?? null,

                        descricao:
                            linha
                                .find(
                                    ".item-abertura-descricao"
                                )
                                .val()
                            || "",

                        debito:
                            numeroSaldosAbertura(
                                linha
                                    .find(
                                        ".item-abertura-debito"
                                    )
                                    .val()
                            ),

                        credito:
                            numeroSaldosAbertura(
                                linha
                                    .find(
                                        ".item-abertura-credito"
                                    )
                                    .val()
                            ),

                        origem:
                            itemOriginal
                                .origem
                            || "MANUAL",

                        referencia_id:
                            itemOriginal
                                .referencia_id
                            ?? null
                    });
                }
            );

        itensAbertura =
            novosItens;
    }

    // ==================================================
    // TOTAIS
    // ==================================================

    function atualizarTotaisAbertura() {

        sincronizarItensAberturaDaTabelaSeguro();

        let totalDebito = 0;
        let totalCredito = 0;

        itensAbertura.forEach(
            function (item) {

                totalDebito +=
                    Number(
                        item.debito || 0
                    );

                totalCredito +=
                    Number(
                        item.credito || 0
                    );
            }
        );

        const diferenca =
            totalDebito
            - totalCredito;


        const equilibrado =
            totalDebito > 0
            && totalCredito > 0
            && Math.abs(
                diferenca
            ) <= 0.009;

        const faltaCredito =
            diferenca > 0;


        const faltaDebito =
            diferenca < 0;


        let textoEstado =
            "Abertura equilibrada";


        if (!equilibrado) {

            if (faltaCredito) {

                textoEstado =
                    "Faltam "
                    + formatarMoedaSaldosAbertura(
                        Math.abs(
                            diferenca
                        )
                    )
                    + " a crédito";

            } else if (faltaDebito) {

                textoEstado =
                    "Faltam "
                    + formatarMoedaSaldosAbertura(
                        Math.abs(
                            diferenca
                        )
                    )
                    + " a débito";

            } else {

                textoEstado =
                    "Abertura não equilibrada";
            }
        }

        $("#saldoAberturaTotalDebito")
            .text(
                formatarMoedaSaldosAbertura(
                    totalDebito
                )
            );

        $("#saldoAberturaTotalCredito")
            .text(
                formatarMoedaSaldosAbertura(
                    totalCredito
                )
            );

        $("#saldoAberturaDiferenca")
            .text(
                formatarMoedaSaldosAbertura(
                    diferenca
                )
            );


        $("#saldoAberturaEstadoEquilibrio")
            .text(
                textoEstado
            );

        $("#btnConfirmarSaldoAbertura")
            .prop(
                "disabled",
                !equilibrado
                || !$("#saldoAberturaId").val()
            );
    }

    function sincronizarItensAberturaDaTabelaSeguro() {

        if (
            $("#tabelaItensAbertura tbody tr[data-indice]")
                .length
        ) {
            sincronizarItensAberturaDaTabela();
        }
    }

    // ==================================================
    // GUARDAR
    // ==================================================

    async function guardarSaldoAbertura() {

        sincronizarItensAberturaDaTabela();

        if (
            existeContaDuplicadaAbertura()
        ) {

            mostrarErroSaldosAbertura(
                "A mesma conta contabilística foi adicionada mais de uma vez."
            );

            return;
        }

        const payload = {
            acao:
                "guardar",

            id:
                $("#saldoAberturaId")
                    .val()
                || null,

            exercicio_id:
                $("#saldoAberturaExercicioId")
                    .val(),

            plano_contas_id:
                $("#saldoAberturaPlanoContasId")
                    .val(),

            filial_id:
                $("#saldoAberturaFilialId")
                    .val()
                || null,

            data_abertura:
                $("#saldoAberturaData")
                    .val(),

            descricao:
                $("#saldoAberturaDescricao")
                    .val(),

            itens:
                itensAbertura
        };

        if (!payload.exercicio_id) {

            mostrarErroSaldosAbertura(
                "Selecione o exercício contabilístico."
            );

            return;
        }

        if (!payload.plano_contas_id) {

            mostrarErroSaldosAbertura(
                "Selecione o plano de contas."
            );

            return;
        }

        if (!payload.data_abertura) {

            mostrarErroSaldosAbertura(
                "Informe a data de abertura."
            );

            return;
        }

        if (!payload.itens.length) {

            mostrarErroSaldosAbertura(
                "Adicione pelo menos um item."
            );

            return;
        }

        try {

            bloquearModalSaldosAbertura(
                true
            );

            const resposta =
                await requisicaoSaldosAbertura(
                    SALDOS_ABERTURA_URL,
                    {
                        method:
                            "POST",

                        headers: {
                            "Content-Type":
                                "application/json"
                        },

                        body:
                            JSON.stringify(
                                payload
                            )
                    }
                );

            const dados =
                resposta.data || {};

            $("#saldoAberturaId")
                .val(
                    dados.id
                    || ""
                );

            Swal.fire({
                icon:
                    "success",

                title:
                    "Rascunho guardado",

                text:
                    resposta.mensagem
                    || "Saldo de abertura guardado com sucesso."
            });

            atualizarTotaisAbertura();

            carregarListaSaldosAbertura();

        } catch (erro) {

            mostrarErroSaldosAbertura(
                erro.message
            );

        } finally {

            bloquearModalSaldosAbertura(
                false
            );
        }
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    async function buscarSaldoAbertura(
        id
    ) {

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "buscar",

                    id:
                        id
                });

            const resposta =
                await requisicaoSaldosAbertura(
                    SALDOS_ABERTURA_URL
                    + "?"
                    + parametros.toString()
                );

            const abertura =
                resposta.data || {};

            $("#saldoAberturaId")
                .val(
                    abertura.id
                    || ""
                );

            $("#saldoAberturaExercicioId")
                .val(
                    abertura.exercicio_id
                    || ""
                );

            $("#saldoAberturaPlanoContasId")
                .val(
                    abertura.plano_contas_id
                    || ""
                );

            $("#saldoAberturaFilialId")
                .val(
                    abertura.filial_id
                    || ""
                );

            $("#saldoAberturaData")
                .val(
                    abertura.data_abertura
                    || ""
                );

            $("#saldoAberturaDescricao")
                .val(
                    abertura.descricao
                    || ""
                );

            itensAbertura =
                (
                    abertura.itens
                    || []
                )
                    .map(
                        item => ({
                            conta_contabil_id:
                                item.conta_contabil_id,

                            centro_custo_id:
                                item.centro_custo_id,

                            descricao:
                                item.descricao
                                || "",

                            debito:
                                Number(
                                    item.debito
                                    || 0
                                ),

                            credito:
                                Number(
                                    item.credito
                                    || 0
                                ),

                            origem:
                                item.origem
                                || "MANUAL",

                            referencia_id:
                                item.referencia_id
                                ?? null
                        })
                    );

            renderizarItensAbertura();

            $("#modalSaldoAbertura")
                .modal("show");

        } catch (erro) {

            mostrarErroSaldosAbertura(
                erro.message
            );
        }
    }

    // ==================================================
    // PROCURAR ENTRADAS DE STOCK
    // ==================================================

    async function procurarEntradasStockAbertura() {

        const data =
            $("#saldoAberturaData")
                .val();

        if (!data) {

            mostrarErroSaldosAbertura(
                "Informe primeiro a data de abertura."
            );

            return;
        }


        const parametros =
            new URLSearchParams({
                acao:
                    "entradas_stock",

                data_inicial:
                    data,

                data_final:
                    data,

                filial_id:
                    $("#saldoAberturaFilialId")
                        .val()
                    || ""
            });


        try {

            $("#btnProcurarStockAbertura")
                .prop(
                    "disabled",
                    true
                );


            const resposta =
                await requisicaoSaldosAbertura(
                    SALDOS_ABERTURA_URL
                    + "?"
                    + parametros.toString()
                );


            console.log(
                "Entradas de stock:",
                resposta
            );


            preencherTabelaEntradasStockAbertura(
                resposta.data || []
            );


            /*
             * Bootstrap não trabalha bem
             * com dois modais simultâneos.
             *
             * Fecha primeiro o principal.
             */
            $("#modalSaldoAbertura")
                .modal(
                    "hide"
                );


            /*
             * Espera o modal principal
             * terminar de fechar.
             */
            $("#modalImportarSaldosAbertura")
                .on(
                    "hidden.bs.modal",
                    function () {

                        $("#modalSaldoAbertura")
                            .modal(
                                "show"
                            );
                    }
                );


        } catch (erro) {

            console.error(
                "Erro ao procurar entradas de stock:",
                erro
            );


            mostrarErroSaldosAbertura(
                erro.message
            );


        } finally {

            $("#btnProcurarStockAbertura")
                .prop(
                    "disabled",
                    false
                );
        }
    }

    // ==================================================
    // TABELA DE ENTRADAS DE STOCK
    // ==================================================

    function preencherTabelaEntradasStockAbertura(
        movimentos
    ) {

        const tbody =
            $("#tabelaEntradasStockAbertura tbody");

        tbody.empty();

        if (!movimentos.length) {

            tbody.html(`
                <tr>
                    <td
                        colspan="7"
                        class="text-center text-muted py-4">

                        Nenhuma entrada de stock
                        não contabilizada encontrada.

                    </td>
                </tr>
            `);

            return;
        }

        movimentos.forEach(
            function (movimento) {

                tbody.append(`
                    <tr>

                        <td>
                            #${Number(
                    movimento.movimento_id
                )}
                        </td>

                        <td>
                            ${escaparHtmlSaldosAbertura(
                    formatarDataHoraSaldosAbertura(
                        movimento.data_movimento
                    )
                )}
                        </td>

                        <td>
                            ${escaparHtmlSaldosAbertura(
                    (
                        movimento.armazem_codigo
                            ? movimento.armazem_codigo + " - "
                            : ""
                    )
                    + (
                        movimento.armazem_nome
                        || "-"
                    )
                )}
                        </td>

                        <td class="text-center">
                            ${Number(
                    movimento.produtos || 0
                )}
                        </td>

                        <td class="text-center">
                            ${Number(
                    movimento.quantidade_total || 0
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaSaldosAbertura(
                    movimento.valor_total
                    || 0
                )}
                        </td>

                        <td class="text-center">

                            <button
                                type="button"
                                class="btn btn-sm btn-primary btn-importar-stock-abertura"
                                data-movimento-id="${Number(
                    movimento.movimento_id
                )}">

                                Importar

                            </button>

                        </td>

                    </tr>
                `);
            }
        );
    }

    // ==================================================
    // IMPORTAR MOVIMENTO DE STOCK
    // ==================================================

    async function importarMovimentoStockAbertura(
        movimentoId
    ) {

        const planoId =
            $("#saldoAberturaPlanoContasId")
                .val();

        if (!planoId) {

            mostrarErroSaldosAbertura(
                "Selecione primeiro o plano de contas."
            );

            return;
        }

        const duplicado =
            itensAbertura.some(
                item =>
                    item.origem === "STOCK"
                    && Number(
                        item.referencia_id
                    ) === Number(
                        movimentoId
                    )
            );

        if (duplicado) {

            mostrarErroSaldosAbertura(
                "Este movimento de stock já foi importado."
            );

            return;
        }

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "importar_stock",

                    plano_contas_id:
                        planoId,

                    movimento_id:
                        movimentoId
                });

            const resposta =
                await requisicaoSaldosAbertura(
                    SALDOS_ABERTURA_URL
                    + "?"
                    + parametros.toString()
                );

            const item =
                resposta.data || {};

            itensAbertura.push({
                conta_contabil_id:
                    item.conta_contabil_id,

                centro_custo_id:
                    item.centro_custo_id
                    ?? null,

                descricao:
                    item.descricao
                    || "",

                debito:
                    Number(
                        item.debito
                        || 0
                    ),

                credito:
                    Number(
                        item.credito
                        || 0
                    ),

                origem:
                    item.origem
                    || "STOCK",

                referencia_id:
                    item.referencia_id
                    ?? movimentoId
            });

            renderizarItensAbertura();

            $("#modalImportarSaldosAbertura")
                .modal("hide");

            Swal.fire({
                icon:
                    "success",

                title:
                    "Stock importado",

                text:
                    "A entrada de stock foi adicionada ao saldo de abertura."
            });

        } catch (erro) {

            mostrarErroSaldosAbertura(
                erro.message
            );
        }
    }

    // ==================================================
    // CONFIRMAR
    // ==================================================

    async function confirmarSaldoAbertura() {

        const id =
            Number(
                $("#saldoAberturaId")
                    .val()
            );

        if (!id) {

            mostrarErroSaldosAbertura(
                "Guarde o saldo de abertura antes de confirmar."
            );

            return;
        }

        sincronizarItensAberturaDaTabela();

        let debito = 0;
        let credito = 0;

        itensAbertura.forEach(
            item => {

                debito +=
                    Number(
                        item.debito
                        || 0
                    );

                credito +=
                    Number(
                        item.credito
                        || 0
                    );
            }
        );

        const diferenca =
            debito - credito;


        if (
            Math.abs(
                diferenca
            ) > 0.009
        ) {

            let mensagem;


            if (diferenca > 0) {

                mensagem =
                    "Faltam "
                    + formatarMoedaSaldosAbertura(
                        Math.abs(
                            diferenca
                        )
                    )
                    + " a crédito para equilibrar a abertura.";

            } else {

                mensagem =
                    "Faltam "
                    + formatarMoedaSaldosAbertura(
                        Math.abs(
                            diferenca
                        )
                    )
                    + " a débito para equilibrar a abertura.";
            }


            mostrarErroSaldosAbertura(
                mensagem
            );

            return;
        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Confirmar abertura?",

                text:
                    "Será criado um lançamento contabilístico no Diário de Abertura. Depois disso o documento deixará de ser rascunho.",

                showCancelButton:
                    true,

                confirmButtonText:
                    "Confirmar",

                cancelButtonText:
                    "Cancelar"
            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        try {

            bloquearModalSaldosAbertura(
                true
            );

            const resposta =
                await requisicaoSaldosAbertura(
                    SALDOS_ABERTURA_URL,
                    {
                        method:
                            "POST",

                        headers: {
                            "Content-Type":
                                "application/json"
                        },

                        body:
                            JSON.stringify({
                                acao:
                                    "confirmar",

                                id:
                                    id
                            })
                    }
                );

            await Swal.fire({
                icon:
                    "success",

                title:
                    "Abertura confirmada",

                text:
                    resposta.mensagem
                    || "O lançamento de abertura foi criado."
            });

            $("#modalSaldoAbertura")
                .modal("hide");

            carregarListaSaldosAbertura();

        } catch (erro) {

            mostrarErroSaldosAbertura(
                erro.message
            );

        } finally {

            bloquearModalSaldosAbertura(
                false
            );
        }
    }

    function determinarOrigemContaAbertura(
        conta
    ) {

        const integracao =
            String(
                conta.integracao_codigo
                || ""
            )
                .toUpperCase();


        if (
            integracao === "CAIXA"
        ) {
            return "CAIXA";
        }


        if (
            integracao === "BANCO"
            || integracao === "DEPOSITOS_ORDEM"
        ) {
            return "BANCO";
        }


        if (
            integracao === "CLIENTES"
        ) {
            return "CLIENTE";
        }


        if (
            integracao === "FORNECEDORES"
        ) {
            return "FORNECEDOR";
        }


        if (
            integracao === "STOCK"
        ) {
            return "STOCK";
        }


        if (
            String(
                conta.grupo
                || ""
            )
                .toUpperCase()
            === "ATIVO"
            && String(
                conta.codigo
                || ""
            ).startsWith("3.")
        ) {
            return "ATIVO";
        }


        return "MANUAL";
    }

    // ==================================================
    // TABELA DE ABERTURAS
    // ==================================================

    function preencherTabelaSaldosAbertura(
        registros
    ) {

        const tbody =
            $("#tabelaSaldosAbertura tbody");

        tbody.empty();

        if (!registros.length) {

            tbody.html(`
                <tr>

                    <td
                        colspan="9"
                        class="text-center text-muted py-4">

                        Nenhum saldo de abertura registado.

                    </td>

                </tr>
            `);

            return;
        }

        registros.forEach(
            function (registro) {

                tbody.append(`
                    <tr>

                        <td>
                            ${Number(
                    registro.id
                )}
                        </td>

                        <td>
                            ${escaparHtmlSaldosAbertura(
                    formatarDataSaldosAbertura(
                        registro.data_abertura
                    )
                )}
                        </td>

                        <td>
                            ${escaparHtmlSaldosAbertura(
                    registro.exercicio_codigo
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlSaldosAbertura(
                    registro.plano_codigo
                    || "-"
                )}
                        </td>

                        <td>
                            ${escaparHtmlSaldosAbertura(
                    registro.filial_nome
                    || "Geral"
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaSaldosAbertura(
                    registro.total_debito
                    || 0
                )}
                        </td>

                        <td class="text-right">
                            ${formatarMoedaSaldosAbertura(
                    registro.total_credito
                    || 0
                )}
                        </td>

                        <td>
                            ${escaparHtmlSaldosAbertura(
                    registro.status_nome
                    || "-"
                )}
                        </td>

                        <td class="text-center">

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary btn-editar-saldo-abertura"
                                data-id="${Number(
                    registro.id
                )}">

                                <i class="fa fa-eye"></i>

                            </button>

                        </td>

                    </tr>
                `);
            }
        );
    }

    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoSaldosAbertura(
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
                JSON.parse(
                    texto
                );

        } catch (erro) {

            console.error(
                "Resposta do servidor:",
                texto
            );

            throw new Error(
                "O servidor não retornou JSON válido."
            );
        }

        if (
            !resposta.ok
            || json.success === false
        ) {

            throw new Error(
                json.mensagem
                || "Ocorreu um erro em Saldos de Abertura."
            );
        }

        return json;
    }

    // ==================================================
    // BLOQUEAR MODAL
    // ==================================================

    function bloquearModalSaldosAbertura(
        bloquear
    ) {

        $("#modalSaldoAbertura")
            .find(
                "input, select, textarea, button"
            )
            .prop(
                "disabled",
                Boolean(
                    bloquear
                )
            );
    }

    // ==================================================
    // FORMATAÇÕES
    // ==================================================

    function formatarMoedaSaldosAbertura(
        valor
    ) {

        return Number(
            valor || 0
        )
            .toLocaleString(
                "pt-PT",
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

    function formatarDataSaldosAbertura(
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

        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    function formatarDataHoraSaldosAbertura(
        data
    ) {

        if (!data) {
            return "-";
        }

        return String(
            data
        );
    }

    function numeroSaldosAbertura(
        valor
    ) {

        const numero =
            Number(
                valor || 0
            );

        return Number.isFinite(
            numero
        )
            ? numero
            : 0;
    }

    function escaparHtmlSaldosAbertura(
        valor
    ) {

        return $("<div>")
            .text(
                valor
                ?? ""
            )
            .html();
    }

    function escaparAtributoSaldosAbertura(
        valor
    ) {

        return escaparHtmlSaldosAbertura(
            valor
        )
            .replace(
                /"/g,
                "&quot;"
            );
    }

    // ==================================================
    // ERRO
    // ==================================================

    function mostrarErroSaldosAbertura(
        mensagem
    ) {

        return Swal.fire({
            icon:
                "error",

            title:
                "Erro",

            text:
                mensagem
                || "Não foi possível concluir a operação."
        });
    }

})(jQuery);