"use strict";

const MOVIMENTO_CAIXA_URL =
    "/erpplus/app/ajax/movimento_caixa_ajax.php";

const CENTRO_CUSTO_URLI =
    "/erpplus/app/ajax/centro_custo_ajax.php";


let tabelaMovimentosCaixa = null;


/* ==================================================
   MENSAGENS
================================================== */

function movimentoCaixaErro(
    mensagem,
    titulo = "Não foi possível concluir"
) {

    return Swal.fire({
        icon: "error",
        title: titulo,
        text:
            mensagem
            || "Ocorreu um erro inesperado.",
        confirmButtonText: "Fechar"
    });
}


function movimentoCaixaAviso(
    mensagem,
    titulo = "Atenção"
) {

    return Swal.fire({
        icon: "warning",
        title: titulo,
        text: mensagem,
        confirmButtonText: "Entendi"
    });
}


function movimentoCaixaSucesso(
    mensagem
) {

    return Swal.fire({
        toast: true,
        position: "top-end",
        icon: "success",
        title: mensagem,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}


function confirmarMovimentoCaixa({
    titulo,
    texto,
    textoConfirmar = "Confirmar"
}) {

    return Swal.fire({
        icon: "warning",
        title: titulo,
        text: texto,
        showCancelButton: true,
        confirmButtonText:
            textoConfirmar,
        cancelButtonText:
            "Cancelar",
        reverseButtons: true,
        focusCancel: true
    });
}


function obterMensagemMovimentoCaixa(
    json
) {

    return json?.mensagem
        || json?.message
        || "Ocorreu um erro inesperado.";
}


/* ==================================================
   REQUISIÇÕES
================================================== */

async function requisicaoMovimentoCaixa(
    url,
    opcoes = {}
) {

    try {

        const resposta =
            await fetch(
                url,
                opcoes
            );


        let json;

        try {

            json =
                await resposta.json();

        } catch {

            throw new Error(
                "O servidor retornou uma resposta inválida."
            );
        }


        if (
            !resposta.ok
            || json.success === false
        ) {

            throw new Error(
                obterMensagemMovimentoCaixa(
                    json
                )
            );
        }


        return json;

    } catch (erro) {

        await movimentoCaixaErro(
            erro.message
        );

        throw erro;
    }
}


/* ==================================================
   INICIALIZAÇÃO
================================================== */

$(document).ready(function () {

    carregarCaixasAbertos();

    carregarCentrosCustoCaixa();

    carregarTabelaMovimentosCaixa();

    definirDataAtualMovimentoCaixa();


    $("#btnNovoMovimentoCaixa").on(
        "click",
        abrirNovoMovimentoCaixa
    );


    $("#btnTransferenciaCaixa").on(
        "click",
        abrirTransferenciaCaixa
    );


    $("#formMovimentoCaixa").on(
        "submit",
        guardarMovimentoCaixa
    );


    $("#formTransferenciaCaixa").on(
        "submit",
        guardarTransferenciaCaixa
    );


    $("#formFiltrosMovimentosCaixa").on(
        "submit",
        function (event) {

            event.preventDefault();

            tabelaMovimentosCaixa
                ?.ajax
                .reload();
        }
    );


    $("#btnLimparFiltrosCaixa").on(
        "click",
        limparFiltrosMovimentosCaixa
    );


    $("#filtroCaixa, #filtroTipo, #filtroOrigem")
        .on(
            "change",
            function () {

                tabelaMovimentosCaixa
                    ?.ajax
                    .reload();
            }
        );


    $("#movimentoCaixaId").on(
        "change",
        carregarSaldoMovimentoCaixa
    );


    $("#transferenciaCaixaOrigem").on(
        "change",
        function () {

            carregarSaldoTransferenciaOrigem();

            impedirMesmoCaixaDestino();
        }
    );


    $("#transferenciaCaixaDestino").on(
        "change",
        impedirMesmoCaixaOrigem
    );


    $("#modalMovimentoCaixa").on(
        "hidden.bs.modal",
        limparFormularioMovimentoCaixa
    );


    $("#modalTransferenciaCaixa").on(
        "hidden.bs.modal",
        limparFormularioTransferenciaCaixa
    );
});


/* ==================================================
   DATATABLE
================================================== */

function carregarTabelaMovimentosCaixa() {

    tabelaMovimentosCaixa =
        $("#movimentosCaixaTable")
            .DataTable({

                ajax: {

                    url:
                        MOVIMENTO_CAIXA_URL,

                    type:
                        "GET",

                    data:
                        function () {

                            return {

                                acao:
                                    "listar",

                                caixa_id:
                                    $("#filtroCaixa")
                                        .val(),

                                tipo:
                                    $("#filtroTipo")
                                        .val(),

                                origem:
                                    $("#filtroOrigem")
                                        .val(),

                                data_inicio:
                                    $("#filtroDataInicio")
                                        .val(),

                                data_fim:
                                    $("#filtroDataFim")
                                        .val()

                            };
                        },


                    dataSrc:
                        function (json) {

                            if (
                                !json.success
                            ) {

                                movimentoCaixaErro(
                                    obterMensagemMovimentoCaixa(
                                        json
                                    )
                                );

                                return [];
                            }


                            const movimentos =
                                json.data
                                || [];


                            atualizarResumoMovimentosCaixa(
                                movimentos
                            );


                            return movimentos;
                        },


                    error:
                        function () {

                            movimentoCaixaErro(
                                "Não foi possível carregar os movimentos de caixa."
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
                            "data_movimento",

                        render:
                            function (
                                data
                            ) {

                                return formatarDataHoraCaixa(
                                    data
                                );
                            }
                    },

                    {
                        data:
                            "caixa",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "tipo",

                        render:
                            function (
                                tipo
                            ) {

                                return formatarTipoMovimentoCaixa(
                                    tipo
                                );
                            }
                    },

                    {
                        data:
                            "origem",

                        render:
                            function (
                                origem
                            ) {

                                return formatarOrigemMovimentoCaixa(
                                    origem
                                );
                            }
                    },

                    {
                        data:
                            "descricao",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "operador",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "valor",

                        className:
                            "text-right",

                        render:
                            function (
                                valor,
                                tipo,
                                row
                            ) {

                                const sinal =
                                    movimentoEhEntrada(
                                        row
                                    )
                                        ? "+"
                                        : "-";


                                return `
                                    <strong>
                                        ${sinal}${formatarMoedaCaixa(
                                    valor
                                )}
                                    </strong>
                                `;
                            }
                    },

                    {
                        data:
                            "id",

                        className:
                            "text-center",

                        orderable:
                            false,

                        searchable:
                            false,

                        render:
                            function (
                                id
                            ) {

                                return `
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-info"
                                        onclick="verDetalhesMovimentoCaixa(${id})"
                                        title="Ver detalhes"
                                    >
                                        <i class="fa fa-eye"></i>
                                    </button>
                                `;
                            }
                    }

                ],


                order: [
                    [1, "desc"]
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
                            "Movimentos de Caixa",

                        exportOptions: {
                            columns: [
                                0, 1, 2, 3,
                                4, 5, 6, 7
                            ]
                        }
                    },

                    {
                        extend:
                            "pdfHtml5",

                        text:
                            '<i class="fa fa-file-pdf"></i> PDF',

                        title:
                            "Movimentos de Caixa",

                        orientation:
                            "landscape",

                        pageSize:
                            "A4",

                        exportOptions: {
                            columns: [
                                0, 1, 2, 3,
                                4, 5, 6, 7
                            ]
                        }
                    },

                    {
                        extend:
                            "print",

                        text:
                            '<i class="fa fa-print"></i> Imprimir',

                        title:
                            "Movimentos de Caixa",

                        exportOptions: {
                            columns: [
                                0, 1, 2, 3,
                                4, 5, 6, 7
                            ]
                        }
                    }

                ],


                language: {
                    url:
                        "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
                }

            });
}


/* ==================================================
   RESUMO
================================================== */

function atualizarResumoMovimentosCaixa(
    movimentos
) {

    let entradas = 0;
    let saidas = 0;


    movimentos.forEach(
        function (
            movimento
        ) {

            const valor =
                Number(
                    movimento.valor
                    || 0
                );


            if (
                movimentoEhEntrada(
                    movimento
                )
            ) {

                entradas +=
                    valor;

            } else {

                saidas +=
                    valor;
            }
        }
    );


    const saldo =
        entradas
        - saidas;


    $("#resumoEntradas").text(
        formatarMoedaCaixa(
            entradas
        )
    );


    $("#resumoSaidas").text(
        formatarMoedaCaixa(
            saidas
        )
    );


    $("#resumoSaldoMovimentos").text(
        formatarMoedaCaixa(
            saldo
        )
    );


    $("#resumoTotalMovimentos").text(
        movimentos.length
    );
}


/* ==================================================
   CAIXAS ABERTOS
================================================== */

async function carregarCaixasAbertos() {

    try {

        const json =
            await requisicaoMovimentoCaixa(
                MOVIMENTO_CAIXA_URL
                + "?acao=caixas_abertos"
            );


        const caixas =
            json.data
            || [];


        preencherSelectCaixa(
            "#filtroCaixa",
            caixas,
            "Todos os caixas"
        );


        preencherSelectCaixa(
            "#movimentoCaixaId",
            caixas,
            "Selecione"
        );


        preencherSelectCaixa(
            "#transferenciaCaixaOrigem",
            caixas,
            "Selecione"
        );


        preencherSelectCaixa(
            "#transferenciaCaixaDestino",
            caixas,
            "Selecione"
        );


        if (
            caixas.length === 0
        ) {

            $("#btnNovoMovimentoCaixa")
                .prop(
                    "disabled",
                    true
                );


            $("#btnTransferenciaCaixa")
                .prop(
                    "disabled",
                    true
                );

        } else {

            $("#btnNovoMovimentoCaixa")
                .prop(
                    "disabled",
                    false
                );
        }


        if (
            caixas.length < 2
        ) {

            $("#btnTransferenciaCaixa")
                .prop(
                    "disabled",
                    true
                )
                .attr(
                    "title",
                    "É necessário possuir pelo menos dois caixas abertos."
                );

        } else {

            $("#btnTransferenciaCaixa")
                .prop(
                    "disabled",
                    false
                )
                .removeAttr(
                    "title"
                );
        }


    } catch {

        $("#btnNovoMovimentoCaixa")
            .prop(
                "disabled",
                true
            );


        $("#btnTransferenciaCaixa")
            .prop(
                "disabled",
                true
            );
    }
}


function preencherSelectCaixa(
    seletor,
    caixas,
    textoInicial
) {

    const select =
        $(seletor);


    select.html(`
        <option value="">
            ${textoInicial}
        </option>
    `);


    caixas.forEach(
        function (
            caixa
        ) {

            let texto =
                caixa.nome
                || `Caixa #${caixa.id}`;


            if (
                caixa.filial
            ) {

                texto +=
                    ` - ${caixa.filial}`;
            }


            select.append(`
                <option
                    value="${caixa.id}"
                    data-saldo="${Number(
                caixa.saldo_atual
                || 0
            )}"
                >
                    ${texto}
                </option>
            `);
        }
    );
}


/* ==================================================
   CENTROS DE CUSTO
================================================== */

async function carregarCentrosCustoCaixa() {

    if (
        !document.getElementById(
            "movimentoCentroCusto"
        )
    ) {

        return;
    }


    try {

        const resposta =
            await fetch(
                CENTRO_CUSTO_URLI
                + "?acao=listar"
            );


        const json =
            await resposta.json();


        if (
            !resposta.ok
            || json.success === false
        ) {

            return;
        }


        const centros =
            json.data
            || [];


        const select =
            $("#movimentoCentroCusto");


        select.html(`
            <option value="">
                Nenhum
            </option>
        `);


        centros.forEach(
            function (
                centro
            ) {

                select.append(`
                    <option value="${centro.id}">
                        ${centro.codigo
                        ? centro.codigo + " - "
                        : ""
                    }${centro.nome}
                    </option>
                `);
            }
        );

    } catch (
    erro
    ) {

        console.warn(
            "Não foi possível carregar centros de custo.",
            erro
        );
    }
}


/* ==================================================
   ABRIR NOVO MOVIMENTO
================================================== */

function abrirNovoMovimentoCaixa() {

    limparFormularioMovimentoCaixa();

    definirDataAtualMovimentoCaixa();

    $("#modalMovimentoCaixa")
        .modal(
            "show"
        );
}


/* ==================================================
   GUARDAR MOVIMENTO
================================================== */

async function guardarMovimentoCaixa(
    event
) {

    event.preventDefault();


    const form =
        document.getElementById(
            "formMovimentoCaixa"
        );


    if (
        !form.checkValidity()
    ) {

        form.reportValidity();

        return;
    }


    const tipo =
        $("#movimentoTipo")
            .val();


    const valor =
        Number(
            $("#movimentoValor")
                .val()
            || 0
        );


    const saldo =
        obterSaldoSelect(
            "#movimentoCaixaId"
        );


    if (
        ["SAIDA", "SANGRIA"]
            .includes(
                tipo
            )
        &&
        valor > saldo
    ) {

        await movimentoCaixaAviso(
            "O valor informado é superior ao saldo disponível no caixa."
        );

        return;
    }


    const confirmacao =
        await confirmarMovimentoCaixa({

            titulo:
                "Confirmar movimento",

            texto:
                `Deseja registar este movimento no valor de ${formatarMoedaCaixa(
                    valor
                )}?`,

            textoConfirmar:
                "Registar"
        });


    if (
        !confirmacao.isConfirmed
    ) {

        return;
    }


    const formData =
        new FormData(
            form
        );


    formData.append(
        "acao",
        "registrar"
    );


    try {

        desabilitarBotao(
            "#btnGuardarMovimentoCaixa",
            true
        );


        const json =
            await requisicaoMovimentoCaixa(
                MOVIMENTO_CAIXA_URL,
                {
                    method:
                        "POST",

                    body:
                        formData
                }
            );


        $("#modalMovimentoCaixa")
            .modal(
                "hide"
            );


        await movimentoCaixaSucesso(
            json.mensagem
            || "Movimento registado com sucesso."
        );


        await recarregarModuloMovimentoCaixa();


    } finally {

        desabilitarBotao(
            "#btnGuardarMovimentoCaixa",
            false
        );
    }
}


/* ==================================================
   TRANSFERÊNCIA
================================================== */

function abrirTransferenciaCaixa() {

    limparFormularioTransferenciaCaixa();

    definirDataAtualTransferenciaCaixa();

    $("#modalTransferenciaCaixa")
        .modal(
            "show"
        );
}


async function guardarTransferenciaCaixa(
    event
) {

    event.preventDefault();


    const form =
        document.getElementById(
            "formTransferenciaCaixa"
        );


    if (
        !form.checkValidity()
    ) {

        form.reportValidity();

        return;
    }


    const origemId =
        $("#transferenciaCaixaOrigem")
            .val();


    const destinoId =
        $("#transferenciaCaixaDestino")
            .val();


    if (
        origemId === destinoId
    ) {

        await movimentoCaixaAviso(
            "O caixa de origem e destino devem ser diferentes."
        );

        return;
    }


    const valor =
        Number(
            $("#transferenciaValor")
                .val()
            || 0
        );


    const saldoOrigem =
        obterSaldoSelect(
            "#transferenciaCaixaOrigem"
        );


    if (
        valor > saldoOrigem
    ) {

        await movimentoCaixaAviso(
            "O caixa de origem não possui saldo suficiente para a transferência."
        );

        return;
    }


    const confirmacao =
        await confirmarMovimentoCaixa({

            titulo:
                "Confirmar transferência",

            texto:
                `Transferir ${formatarMoedaCaixa(
                    valor
                )} entre os caixas selecionados?`,

            textoConfirmar:
                "Transferir"
        });


    if (
        !confirmacao.isConfirmed
    ) {

        return;
    }


    const formData =
        new FormData(
            form
        );


    formData.append(
        "acao",
        "transferir"
    );


    try {

        desabilitarBotao(
            "#btnConfirmarTransferenciaCaixa",
            true
        );


        const json =
            await requisicaoMovimentoCaixa(
                MOVIMENTO_CAIXA_URL,
                {
                    method:
                        "POST",

                    body:
                        formData
                }
            );


        $("#modalTransferenciaCaixa")
            .modal(
                "hide"
            );


        await movimentoCaixaSucesso(
            json.mensagem
            || "Transferência realizada com sucesso."
        );


        await recarregarModuloMovimentoCaixa();


    } finally {

        desabilitarBotao(
            "#btnConfirmarTransferenciaCaixa",
            false
        );
    }
}


/* ==================================================
   SALDOS
================================================== */

function carregarSaldoMovimentoCaixa() {

    const saldo =
        obterSaldoSelect(
            "#movimentoCaixaId"
        );


    $("#movimentoSaldoAtual")
        .val(
            formatarMoedaCaixa(
                saldo
            )
        );
}


function carregarSaldoTransferenciaOrigem() {

    const saldo =
        obterSaldoSelect(
            "#transferenciaCaixaOrigem"
        );


    $("#transferenciaSaldoOrigem")
        .val(
            formatarMoedaCaixa(
                saldo
            )
        );
}


function obterSaldoSelect(
    seletor
) {

    const option =
        $(`${seletor} option:selected`);


    return Number(
        option.data(
            "saldo"
        )
        || 0
    );
}


/* ==================================================
   IMPEDIR MESMO CAIXA
================================================== */

function impedirMesmoCaixaDestino() {

    const origemId =
        $("#transferenciaCaixaOrigem")
            .val();


    $("#transferenciaCaixaDestino option")
        .prop(
            "disabled",
            false
        );


    if (
        origemId
    ) {

        $(
            `#transferenciaCaixaDestino option[value="${origemId}"]`
        )
            .prop(
                "disabled",
                true
            );
    }


    if (
        $("#transferenciaCaixaDestino")
            .val()
        === origemId
    ) {

        $("#transferenciaCaixaDestino")
            .val(
                ""
            );
    }
}


function impedirMesmoCaixaOrigem() {

    const destinoId =
        $("#transferenciaCaixaDestino")
            .val();


    $("#transferenciaCaixaOrigem option")
        .prop(
            "disabled",
            false
        );


    if (
        destinoId
    ) {

        $(
            `#transferenciaCaixaOrigem option[value="${destinoId}"]`
        )
            .prop(
                "disabled",
                true
            );
    }


    if (
        $("#transferenciaCaixaOrigem")
            .val()
        === destinoId
    ) {

        $("#transferenciaCaixaOrigem")
            .val(
                ""
            );

        carregarSaldoTransferenciaOrigem();
    }
}


/* ==================================================
   DETALHES
================================================== */

async function verDetalhesMovimentoCaixa(
    id
) {

    try {

        const json =
            await requisicaoMovimentoCaixa(
                MOVIMENTO_CAIXA_URL
                + "?acao=buscar&id="
                + encodeURIComponent(
                    id
                )
            );


        const movimento =
            json.data
            || {};


        $("#detalheCaixa").text(
            movimento.caixa
            || "-"
        );


        $("#detalheData").text(
            formatarDataHoraCaixa(
                movimento.data_movimento
            )
        );


        $("#detalheOperador").text(
            movimento.operador
            || "-"
        );


        $("#detalheTipo").html(
            formatarTipoMovimentoCaixa(
                movimento.tipo
            )
        );


        $("#detalheOrigem").text(
            nomeOrigemMovimentoCaixa(
                movimento.origem
            )
        );


        $("#detalheValor").text(
            formatarMoedaCaixa(
                movimento.valor
            )
        );


        $("#detalheFilial").text(
            movimento.filial
            || "-"
        );


        $("#detalheCentroCusto").text(
            movimento.centro_custo
            || "-"
        );


        $("#detalheDocumento").text(
            movimento.documento_id
            || "-"
        );


        $("#detalheDescricao").text(
            movimento.descricao
            || "-"
        );


        $("#modalDetalhesMovimentoCaixa")
            .modal(
                "show"
            );


    } catch {
        // helper já exibe o erro.
    }
}


window.verDetalhesMovimentoCaixa =
    verDetalhesMovimentoCaixa;


/* ==================================================
   FILTROS
================================================== */

function limparFiltrosMovimentosCaixa() {

    $("#filtroCaixa")
        .val(
            ""
        );


    $("#filtroTipo")
        .val(
            ""
        );


    $("#filtroOrigem")
        .val(
            ""
        );


    $("#filtroDataInicio")
        .val(
            ""
        );


    $("#filtroDataFim")
        .val(
            ""
        );


    tabelaMovimentosCaixa
        ?.ajax
        .reload();
}


/* ==================================================
   RECARREGAR MÓDULO
================================================== */

async function recarregarModuloMovimentoCaixa() {

    await carregarCaixasAbertos();


    if (
        tabelaMovimentosCaixa
    ) {

        tabelaMovimentosCaixa
            .ajax
            .reload(
                null,
                false
            );
    }
}


/* ==================================================
   LIMPAR FORMULÁRIOS
================================================== */

function limparFormularioMovimentoCaixa() {

    const form =
        document.getElementById(
            "formMovimentoCaixa"
        );


    if (
        form
    ) {

        form.reset();
    }


    $("#movimentoSaldoAtual")
        .val(
            formatarMoedaCaixa(
                0
            )
        );
}


function limparFormularioTransferenciaCaixa() {

    const form =
        document.getElementById(
            "formTransferenciaCaixa"
        );


    if (
        form
    ) {

        form.reset();
    }


    $("#transferenciaSaldoOrigem")
        .val(
            formatarMoedaCaixa(
                0
            )
        );


    $("#transferenciaCaixaOrigem option")
        .prop(
            "disabled",
            false
        );


    $("#transferenciaCaixaDestino option")
        .prop(
            "disabled",
            false
        );
}


/* ==================================================
   DATA ATUAL
================================================== */

function definirDataAtualMovimentoCaixa() {

    $("#movimentoData")
        .val(
            dataHoraLocalInput()
        );
}


function definirDataAtualTransferenciaCaixa() {

    $("#transferenciaData")
        .val(
            dataHoraLocalInput()
        );
}


function dataHoraLocalInput() {

    const data =
        new Date();


    data.setMinutes(
        data.getMinutes()
        - data.getTimezoneOffset()
    );


    return data
        .toISOString()
        .slice(
            0,
            16
        );
}


/* ==================================================
   TIPOS / ORIGENS
================================================== */

function movimentoEhEntrada(
    movimento
) {

    const tipo =
        String(
            movimento?.tipo
            || ""
        )
            .trim()
            .toUpperCase();


    return [
        "ENTRADA",
        "SUPRIMENTO"
    ].includes(
        tipo
    );
}


function formatarTipoMovimentoCaixa(
    tipo
) {

    const chave =
        String(
            tipo
            || ""
        )
            .trim()
            .toUpperCase();


    const tipos = {

        ENTRADA: {
            texto:
                "Entrada",

            classe:
                "success"
        },

        SAIDA: {
            texto:
                "Saída",

            classe:
                "danger"
        },

        SUPRIMENTO: {
            texto:
                "Suprimento",

            classe:
                "info"
        },

        SANGRIA: {
            texto:
                "Sangria",

            classe:
                "warning"
        },

        TRANSFERENCIA: {
            texto:
                "Transferência",

            classe:
                "secondary"
        }

    };


    const item =
        tipos[chave]
        || {
            texto:
                chave
                || "-",

            classe:
                "secondary"
        };


    return `
        <span class="badge badge-${item.classe}">
            ${item.texto}
        </span>
    `;
}


function nomeOrigemMovimentoCaixa(
    origem
) {

    const chave =
        String(
            origem
            || ""
        )
            .trim()
            .toUpperCase();


    const origens = {

        VENDA:
            "Venda",

        COMPRA:
            "Compra",

        RECEBIMENTO:
            "Recebimento",

        PAGAMENTO:
            "Pagamento",

        TRANSFERENCIA:
            "Transferência",

        MANUAL:
            "Manual"

    };


    return origens[chave]
        || origem
        || "-";
}


function formatarOrigemMovimentoCaixa(
    origem
) {

    return nomeOrigemMovimentoCaixa(
        origem
    );
}


/* ==================================================
   FORMATAÇÕES
================================================== */

function formatarMoedaCaixa(
    valor
) {

    const numero =
        Number(
            valor
        );


    return new Intl.NumberFormat(
        "pt-MZ",
        {
            minimumFractionDigits:
                2,

            maximumFractionDigits:
                2
        }
    ).format(
        Number.isFinite(
            numero
        )
            ? numero
            : 0
    )
        + " MTn";
}


function formatarDataHoraCaixa(
    valor
) {

    if (
        !valor
    ) {

        return "-";
    }


    const texto =
        String(
            valor
        );


    const data =
        new Date(
            texto.replace(
                " ",
                "T"
            )
        );


    if (
        Number.isNaN(
            data.getTime()
        )
    ) {

        return texto;
    }


    return new Intl.DateTimeFormat(
        "pt-PT",
        {
            day:
                "2-digit",

            month:
                "2-digit",

            year:
                "numeric",

            hour:
                "2-digit",

            minute:
                "2-digit"
        }
    ).format(
        data
    );
}


/* ==================================================
   BOTÕES
================================================== */

function desabilitarBotao(
    seletor,
    bloquear
) {

    $(seletor)
        .prop(
            "disabled",
            bloquear
        );
}