const MOVIMENTO_BANCARIO_URL =
    "/erpplus/app/ajax/movimento_bancario_ajax.php";

const BANCO_AJAX_URL =
    "/erpplus/app/ajax/banco_ajax.php";

let tabelaMovimentosBancarios;
let tabelaExtratoBancario;

// ==================================================
// MENSAGENS
// ==================================================

function movimentoBancoErro(
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


function movimentoBancoAviso(
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

$("#btnExtratoBancario").on(
    "click",
    abrirExtratoBancario
);

$("#btnCarregarExtratoBancario").on(
    "click",
    carregarExtratoBancario
);


function movimentoBancoSucesso(
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


function confirmarMovimentoBanco({
    titulo,
    texto,
    textoConfirmar = "Confirmar",
    corConfirmar = "#007bff"
}) {
    return Swal.fire({
        icon: "warning",
        title: titulo,
        text: texto,
        showCancelButton: true,
        confirmButtonText: textoConfirmar,
        cancelButtonText: "Cancelar",
        confirmButtonColor: corConfirmar,
        reverseButtons: true,
        focusCancel: true
    });
}


function obterMensagemMovimentoBanco(json) {
    return json.mensagem
        || json.message
        || "Ocorreu um erro inesperado.";
}


// ==================================================
// REQUISIÇÕES
// ==================================================

async function requisicaoMovimentoBanco(
    url,
    opcoes = {}
) {
    try {
        const resposta =
            await fetch(url, opcoes);

        let json;

        try {
            json = await resposta.json();
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
                obterMensagemMovimentoBanco(json)
            );
        }

        return json;

    } catch (erro) {
        await movimentoBancoErro(
            erro.message
        );

        throw erro;
    }
}


// ==================================================
// INICIALIZAÇÃO
// ==================================================

$(document).ready(function () {

    carregarBancosMovimento();

    carregarTabelaMovimentosBancarios();

    definirDataAtualMovimento();

    $("#btnNovoMovimento").on(
        "click",
        abrirNovoMovimentoBancario
    );

    $("#btnNovaTransferencia").on(
        "click",
        abrirNovaTransferenciaBancaria
    );

    $("#formMovimentoBancario").on(
        "submit",
        guardarMovimentoBancario
    );

    $("#formTransferenciaBancaria").on(
        "submit",
        guardarTransferenciaBancaria
    );

    $("#btnFiltrarMovimentos").on(
        "click",
        function () {
            tabelaMovimentosBancarios
                .ajax
                .reload();
        }
    );

    $("#filtro_banco_id, #filtro_tipo, #filtro_categoria")
        .on("change", function () {
            tabelaMovimentosBancarios
                .ajax
                .reload();
        });

    $("#modalMovimentoBancario").on(
        "hidden.bs.modal",
        limparFormularioMovimentoBancario
    );

    $("#modalTransferenciaBancaria").on(
        "hidden.bs.modal",
        limparFormularioTransferenciaBancaria
    );

    $("#banco_origem_id").on(
        "change",
        impedirMesmoBancoDestino
    );

    $("#banco_destino_id").on(
        "change",
        impedirMesmoBancoOrigem
    );

    $("#tipo").on(
        "change",
        ajustarCategoriasPorTipo
    );
});


// ==================================================
// DATATABLE
// ==================================================

function carregarTabelaMovimentosBancarios() {

    tabelaMovimentosBancarios =
        $("#movimentosBancariosTable")
            .DataTable({

                ajax: {
                    url: MOVIMENTO_BANCARIO_URL,

                    type: "GET",

                    data: function () {
                        return {
                            acao: "listar",

                            banco_id:
                                $("#filtro_banco_id").val(),

                            tipo:
                                $("#filtro_tipo").val(),

                            categoria:
                                $("#filtro_categoria").val(),

                            data_inicio:
                                $("#filtro_data_inicio").val(),

                            data_fim:
                                $("#filtro_data_fim").val()
                        };
                    },

                    dataSrc: function (json) {

                        if (!json.success) {
                            movimentoBancoErro(
                                obterMensagemMovimentoBanco(json)
                            );

                            return [];
                        }

                        return json.data || [];
                    },

                    error: function () {
                        movimentoBancoErro(
                            "Não foi possível carregar os movimentos bancários."
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
                        data: "data_movimento",

                        render: function (data) {
                            return formatarDataHoraBanco(
                                data
                            );
                        }
                    },

                    {
                        data: "banco",

                        defaultContent: "-"
                    },

                    {
                        data: "tipo",

                        render: function (tipo) {

                            const classe =
                                tipo === "ENTRADA"
                                    ? "success"
                                    : "danger";

                            return `
                                <span class="badge badge-${classe}">
                                    ${tipo}
                                </span>
                            `;
                        }
                    },

                    {
                        data: "categoria",

                        render: function (categoria) {
                            return formatarCategoriaBanco(
                                categoria
                            );
                        }
                    },

                    {
                        data: "descricao",

                        defaultContent: "-"
                    },

                    {
                        data: "documento",

                        defaultContent: "-"
                    },

                    {
                        data: "saldo_anterior",

                        className: "text-right",

                        render: function (
                            valor,
                            tipo,
                            row
                        ) {
                            return formatarMoedaMovimento(
                                valor,
                                row.moeda
                            );
                        }
                    },

                    {
                        data: "valor",

                        className: "text-right",

                        render: function (
                            valor,
                            tipo,
                            row
                        ) {
                            const sinal =
                                row.tipo === "SAIDA"
                                    ? "-"
                                    : "+";

                            return `
                                <strong>
                                    ${sinal}${formatarMoedaMovimento(
                                valor,
                                row.moeda
                            )}
                                </strong>
                            `;
                        }
                    },

                    {
                        data: "saldo_posterior",

                        className: "text-right",

                        render: function (
                            valor,
                            tipo,
                            row
                        ) {
                            return formatarMoedaMovimento(
                                valor,
                                row.moeda
                            );
                        }
                    },

                    {
                        data: "conciliado",

                        render: function (valor) {

                            return Number(valor) === 1
                                ? `
                                    <span class="badge badge-success">
                                        Sim
                                    </span>
                                `
                                : `
                                    <span class="badge badge-secondary">
                                        Não
                                    </span>
                                `;
                        }
                    },

                    {
                        data: "operador",

                        defaultContent: "-"
                    },

                    {
                        data: "id",

                        className: "text-right",

                        orderable: false,

                        searchable: false,

                        render: function (id) {

                            return `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-info"
                                    onclick="verDetalhesMovimentoBanco(${id})"
                                    title="Ver detalhes">

                                    <i class="fa fa-eye"></i>

                                </button>
                            `;
                        }
                    }

                ],

                order: [
                    [1, "desc"]
                ],

                pageLength: 10,

                lengthMenu: [
                    [5, 10, 25, 50, 100],
                    [5, 10, 25, 50, 100]
                ],

                responsive: true,

                autoWidth: false,

                dom:
                    "<'row'<'col-sm-12 col-md-8 d-flex align-items-center'Bl><'col-sm-12 col-md-4'f>>"
                    + "<'row'<'col-sm-12'tr>>"
                    + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",

                buttons: [

                    {
                        extend: "excelHtml5",

                        text:
                            '<i class="fa fa-file-excel"></i> Excel',

                        title:
                            "Movimentos Bancários",

                        exportOptions: {
                            columns: [
                                0, 1, 2, 3, 4,
                                5, 6, 7, 8, 9,
                                10, 11
                            ]
                        }
                    },

                    {
                        extend: "pdfHtml5",

                        text:
                            '<i class="fa fa-file-pdf"></i> PDF',

                        title:
                            "Movimentos Bancários",

                        orientation:
                            "landscape",

                        pageSize:
                            "A4",

                        exportOptions: {
                            columns: [
                                0, 1, 2, 3, 4,
                                5, 6, 7, 8, 9,
                                10, 11
                            ]
                        }
                    },

                    {
                        extend: "print",

                        text:
                            '<i class="fa fa-print"></i> Imprimir',

                        title:
                            "Movimentos Bancários",

                        exportOptions: {
                            columns: [
                                0, 1, 2, 3, 4,
                                5, 6, 7, 8, 9,
                                10, 11
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


// ==================================================
// CARREGAR BANCOS
// ==================================================

async function carregarBancosMovimento() {

    try {
        const json =
            await requisicaoMovimentoBanco(
                BANCO_AJAX_URL
                + "?acao=listar"
            );

        const bancos =
            (json.data || []).filter(
                banco =>
                    String(banco.status)
                        .trim()
                        .toLowerCase()
                    === "activo"
            );

        preencherSelectBanco(
            "#filtro_banco_id",
            bancos,
            "Todos os bancos"
        );

        preencherSelectBanco(
            "#banco_id",
            bancos,
            "Selecione o banco"
        );

        preencherSelectBanco(
            "#banco_origem_id",
            bancos,
            "Selecione o banco de origem"
        );

        preencherSelectBanco(
            "#extrato_banco_id",
            bancos,
            "Selecione o banco"
        );

        preencherSelectBanco(
            "#banco_destino_id",
            bancos,
            "Selecione o banco de destino"
        );

        if (bancos.length < 2) {
            $("#btnNovaTransferencia")
                .prop("disabled", true)
                .attr(
                    "title",
                    "É necessário possuir pelo menos duas contas bancárias activas."
                );
        } else {
            $("#btnNovaTransferencia")
                .prop("disabled", false)
                .removeAttr("title");
        }

    } catch {
        $("#btnNovoMovimento")
            .prop("disabled", true);

        $("#btnNovaTransferencia")
            .prop("disabled", true);
    }
}

function abrirExtratoBancario() {

    $("#extrato_banco_id").val("");

    $("#extrato_data_inicio").val("");

    $("#extrato_data_fim").val("");

    $("#extrato_saldo_inicial").text(
        formatarMoedaMovimento(0)
    );

    $("#extrato_total_entradas").text(
        formatarMoedaMovimento(0)
    );

    $("#extrato_total_saidas").text(
        formatarMoedaMovimento(0)
    );

    if (
        $.fn.DataTable.isDataTable(
            "#extratoBancarioTable"
        )
    ) {
        $("#extratoBancarioTable")
            .DataTable()
            .clear()
            .draw();
    }

    $("#modalExtratoBancario")
        .modal("show");
}

async function carregarExtratoBancario() {

    const bancoId =
        $("#extrato_banco_id").val();

    if (!bancoId) {

        await movimentoBancoAviso(
            "Selecione o banco para consultar o extrato."
        );

        return;
    }

    const params =
        new URLSearchParams({
            acao: "extrato",
            banco_id: bancoId,
            data_inicio:
                $("#extrato_data_inicio").val(),
            data_fim:
                $("#extrato_data_fim").val()
        });

    try {

        const json =
            await requisicaoMovimentoBanco(
                MOVIMENTO_BANCARIO_URL
                + "?"
                + params.toString()
            );

        const movimentos =
            json.data || [];

        let totalEntradas = 0;
        let totalSaidas = 0;
        let saldoInicial = 0;

        if (movimentos.length > 0) {

            const movimentosOrdenados =
                [...movimentos].sort(
                    (a, b) =>
                        Number(a.id)
                        - Number(b.id)
                );

            saldoInicial =
                Number(
                    movimentosOrdenados[0]
                        .saldo_anterior
                    || 0
                );
        }

        movimentos.forEach(function (movimento) {

            if (movimento.tipo === "ENTRADA") {
                totalEntradas +=
                    Number(movimento.valor || 0);
            }

            if (movimento.tipo === "SAIDA") {
                totalSaidas +=
                    Number(movimento.valor || 0);
            }
        });

        const moeda =
            movimentos[0]?.moeda || "MZN";

        $("#extrato_saldo_inicial").text(
            formatarMoedaMovimento(
                saldoInicial,
                moeda
            )
        );

        $("#extrato_total_entradas").text(
            formatarMoedaMovimento(
                totalEntradas,
                moeda
            )
        );

        $("#extrato_total_saidas").text(
            formatarMoedaMovimento(
                totalSaidas,
                moeda
            )
        );

        montarTabelaExtratoBancario(
            movimentos
        );

    } catch {
        // A função de requisição já mostra a mensagem.
    }
}

function montarTabelaExtratoBancario(
    movimentos
) {

    if (
        $.fn.DataTable.isDataTable(
            "#extratoBancarioTable"
        )
    ) {
        tabelaExtratoBancario
            .clear()
            .rows.add(movimentos)
            .draw();

        return;
    }

    tabelaExtratoBancario =
        $("#extratoBancarioTable")
            .DataTable({

                data: movimentos,

                columns: [

                    {
                        data: "data_movimento",

                        render: function (data) {
                            return formatarDataHoraBanco(
                                data
                            );
                        }
                    },

                    {
                        data: "tipo",

                        render: function (tipo) {

                            const classe =
                                tipo === "ENTRADA"
                                    ? "success"
                                    : "danger";

                            return `
                                <span class="badge badge-${classe}">
                                    ${tipo}
                                </span>
                            `;
                        }
                    },

                    {
                        data: "categoria",

                        render: function (categoria) {
                            return formatarCategoriaBanco(
                                categoria
                            );
                        }
                    },

                    {
                        data: "descricao",
                        defaultContent: "-"
                    },

                    {
                        data: "documento",
                        defaultContent: "-"
                    },

                    {
                        data: null,
                        className: "text-right",

                        render: function (row) {

                            if (row.tipo !== "ENTRADA") {
                                return "-";
                            }

                            return formatarMoedaMovimento(
                                row.valor,
                                row.moeda
                            );
                        }
                    },

                    {
                        data: null,
                        className: "text-right",

                        render: function (row) {

                            if (row.tipo !== "SAIDA") {
                                return "-";
                            }

                            return formatarMoedaMovimento(
                                row.valor,
                                row.moeda
                            );
                        }
                    },

                    {
                        data: "saldo_posterior",
                        className: "text-right",

                        render: function (
                            valor,
                            tipo,
                            row
                        ) {
                            return formatarMoedaMovimento(
                                valor,
                                row.moeda
                            );
                        }
                    }

                ],

                order: [
                    [0, "asc"]
                ],

                pageLength: 10,

                responsive: true,

                autoWidth: false,

                dom:
                    "<'row'<'col-md-8'B><'col-md-4'f>>"
                    + "<'row'<'col-12'tr>>"
                    + "<'row'<'col-md-5'i><'col-md-7'p>>",

                buttons: [

                    {
                        extend: "excelHtml5",
                        text:
                            '<i class="fa fa-file-excel"></i> Excel',
                        title:
                            "Extrato Bancário"
                    },

                    {
                        extend: "pdfHtml5",
                        text:
                            '<i class="fa fa-file-pdf"></i> PDF',
                        title:
                            "Extrato Bancário",
                        orientation:
                            "landscape",
                        pageSize:
                            "A4"
                    },

                    {
                        extend: "print",
                        text:
                            '<i class="fa fa-print"></i> Imprimir',
                        title:
                            "Extrato Bancário"
                    }

                ],

                language: {
                    url:
                        "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
                }

            });
}


function preencherSelectBanco(
    seletor,
    bancos,
    textoInicial
) {
    const select =
        $(seletor);

    select.html(`
        <option value="">
            ${textoInicial}
        </option>
    `);

    bancos.forEach(function (banco) {

        select.append(`
            <option
                value="${banco.id}"
                data-moeda="${banco.moeda}"
                data-saldo="${banco.saldo_atual}">

                ${banco.nome_banco}
                —
                ${banco.conta}
                —
                ${formatarMoedaMovimento(
            banco.saldo_atual,
            banco.moeda
        )}

            </option>
        `);
    });
}


// ==================================================
// NOVO MOVIMENTO
// ==================================================

function abrirNovoMovimentoBancario() {

    limparFormularioMovimentoBancario();

    definirDataAtualMovimento();

    $("#modalMovimentoBancario")
        .modal("show");
}


// ==================================================
// TRANSFERÊNCIA
// ==================================================

function abrirNovaTransferenciaBancaria() {

    limparFormularioTransferenciaBancaria();

    definirDataAtualTransferencia();

    $("#modalTransferenciaBancaria")
        .modal("show");
}


// ==================================================
// GUARDAR MOVIMENTO
// ==================================================

async function guardarMovimentoBancario(
    evento
) {
    evento.preventDefault();

    const formulario =
        evento.target;

    const botao =
        $("#btnGuardarMovimento");

    const bancoId =
        $("#banco_id").val();

    const tipo =
        $("#tipo").val();

    const categoria =
        $("#categoria").val();

    const descricao =
        $("#descricao")
            .val()
            .trim();

    const valor =
        Number(
            $("#valor").val()
        );

    if (!bancoId) {
        await movimentoBancoAviso(
            "Selecione o banco."
        );

        return;
    }

    if (!tipo) {
        await movimentoBancoAviso(
            "Selecione o tipo de movimento."
        );

        return;
    }

    if (!categoria) {
        await movimentoBancoAviso(
            "Selecione a categoria."
        );

        return;
    }

    if (!descricao) {
        await movimentoBancoAviso(
            "Informe a descrição do movimento."
        );

        $("#descricao").focus();

        return;
    }

    if (
        !Number.isFinite(valor)
        || valor <= 0
    ) {
        await movimentoBancoAviso(
            "Informe um valor maior que zero."
        );

        return;
    }

    const confirmacao =
        await confirmarMovimentoBanco({
            titulo:
                "Confirmar movimento?",

            texto:
                `${tipo === "ENTRADA" ? "Entrada" : "Saída"} de `
                + formatarMoedaMovimento(valor)
                + ".",

            textoConfirmar:
                "Registar",

            corConfirmar:
                tipo === "ENTRADA"
                    ? "#28a745"
                    : "#dc3545"
        });

    if (!confirmacao.isConfirmed) {
        return;
    }

    const form =
        new FormData(formulario);

    form.append(
        "acao",
        "registrar"
    );

    try {
        botao
            .prop("disabled", true)
            .html(`
                <span
                    class="spinner-border spinner-border-sm">
                </span>

                A guardar...
            `);

        const json =
            await requisicaoMovimentoBanco(
                MOVIMENTO_BANCARIO_URL,
                {
                    method: "POST",
                    body: form
                }
            );

        $("#modalMovimentoBancario")
            .modal("hide");

        tabelaMovimentosBancarios
            .ajax
            .reload(null, false);

        await carregarBancosMovimento();

        movimentoBancoSucesso(
            json.mensagem
            || "Movimento registado com sucesso."
        );

    } finally {
        botao
            .prop("disabled", false)
            .html(`
                <i class="fa fa-save"></i>
                Guardar Movimento
            `);
    }
}


// ==================================================
// GUARDAR TRANSFERÊNCIA
// ==================================================

async function guardarTransferenciaBancaria(
    evento
) {
    evento.preventDefault();

    const formulario =
        evento.target;

    const botao =
        $("#btnGuardarTransferencia");

    const origemId =
        $("#banco_origem_id").val();

    const destinoId =
        $("#banco_destino_id").val();

    const valor =
        Number(
            $("#valor_transferencia").val()
        );

    const taxa =
        Number(
            $("#taxa_transferencia").val()
            || 0
        );

    if (!origemId || !destinoId) {
        await movimentoBancoAviso(
            "Selecione os bancos de origem e destino."
        );

        return;
    }

    if (origemId === destinoId) {
        await movimentoBancoAviso(
            "O banco de origem e destino devem ser diferentes."
        );

        return;
    }

    if (
        !Number.isFinite(valor)
        || valor <= 0
    ) {
        await movimentoBancoAviso(
            "Informe um valor maior que zero."
        );

        return;
    }

    if (
        !Number.isFinite(taxa)
        || taxa < 0
    ) {
        await movimentoBancoAviso(
            "A taxa não pode ser negativa."
        );

        return;
    }

    const totalDebitado =
        valor + taxa;

    const confirmacao =
        await confirmarMovimentoBanco({
            titulo:
                "Confirmar transferência?",

            texto:
                "Será transferido "
                + formatarMoedaMovimento(valor)
                + ". Total debitado da origem: "
                + formatarMoedaMovimento(
                    totalDebitado
                )
                + ".",

            textoConfirmar:
                "Transferir",

            corConfirmar:
                "#007bff"
        });

    if (!confirmacao.isConfirmed) {
        return;
    }

    const form =
        new FormData(formulario);

    form.append(
        "acao",
        "transferir"
    );

    try {
        botao
            .prop("disabled", true)
            .html(`
                <span
                    class="spinner-border spinner-border-sm">
                </span>

                A transferir...
            `);

        const json =
            await requisicaoMovimentoBanco(
                MOVIMENTO_BANCARIO_URL,
                {
                    method: "POST",
                    body: form
                }
            );

        $("#modalTransferenciaBancaria")
            .modal("hide");

        tabelaMovimentosBancarios
            .ajax
            .reload(null, false);

        await carregarBancosMovimento();

        const dados =
            json.data || {};

        await Swal.fire({
            icon: "success",

            title:
                "Transferência concluída",

            html: `
                <div class="text-left">

                    <p>
                        <strong>Valor transferido:</strong>
                        ${formatarMoedaMovimento(
                dados.valor || valor
            )}
                    </p>

                    <p>
                        <strong>Taxa:</strong>
                        ${formatarMoedaMovimento(
                dados.taxa || taxa
            )}
                    </p>

                    <p>
                        <strong>Total debitado:</strong>
                        ${formatarMoedaMovimento(
                dados.total_debitado
                || totalDebitado
            )}
                    </p>

                </div>
            `,

            confirmButtonText:
                "Entendi"
        });

    } finally {
        botao
            .prop("disabled", false)
            .html(`
                <i class="fa fa-exchange-alt"></i>
                Transferir
            `);
    }
}


// ==================================================
// DETALHES
// ==================================================

async function verDetalhesMovimentoBanco(id) {

    try {
        const json =
            await requisicaoMovimentoBanco(
                MOVIMENTO_BANCARIO_URL
                + "?acao=buscar&id="
                + encodeURIComponent(id)
            );

        const movimento =
            json.data;

        $("#detalhe_banco")
            .text(
                movimento.banco || "-"
            );

        $("#detalhe_tipo")
            .text(
                movimento.tipo || "-"
            );

        $("#detalhe_categoria")
            .text(
                formatarCategoriaBanco(
                    movimento.categoria
                )
            );

        $("#detalhe_data")
            .text(
                formatarDataHoraBanco(
                    movimento.data_movimento
                )
            );

        $("#detalhe_valor")
            .text(
                formatarMoedaMovimento(
                    movimento.valor,
                    movimento.moeda
                )
            );

        $("#detalhe_saldo_anterior")
            .text(
                formatarMoedaMovimento(
                    movimento.saldo_anterior,
                    movimento.moeda
                )
            );

        $("#detalhe_saldo_posterior")
            .text(
                formatarMoedaMovimento(
                    movimento.saldo_posterior,
                    movimento.moeda
                )
            );

        $("#detalhe_operador")
            .text(
                movimento.operador || "-"
            );

        $("#detalhe_descricao")
            .text(
                movimento.descricao || "-"
            );

        $("#detalhe_documento")
            .text(
                movimento.documento || "-"
            );

        $("#detalhe_referencia")
            .text(
                movimento.referencia || "-"
            );

        $("#detalhe_observacoes")
            .text(
                movimento.observacoes || "-"
            );

        $("#modalDetalhesMovimento")
            .modal("show");

    } catch {
        // A função de requisição já apresenta o erro.
    }
}


// ==================================================
// CATEGORIAS POR TIPO
// ==================================================

function ajustarCategoriasPorTipo() {

    const tipo =
        $("#tipo").val();

    const categoria =
        $("#categoria");

    categoria.find("option")
        .prop("disabled", false);

    if (tipo === "ENTRADA") {

        bloquearCategorias([
            "LEVANTAMENTO",
            "PAGAMENTO",
            "TARIFA"
        ]);

    } else if (tipo === "SAIDA") {

        bloquearCategorias([
            "DEPOSITO",
            "RECEBIMENTO",
            "JURO"
        ]);
    }

    if (
        categoria.find(
            "option:selected"
        ).prop("disabled")
    ) {
        categoria.val("");
    }
}


function bloquearCategorias(
    categorias
) {
    categorias.forEach(function (categoria) {

        $("#categoria option[value='"
            + categoria
            + "']")
            .prop("disabled", true);
    });
}


// ==================================================
// IMPEDIR MESMO BANCO
// ==================================================

function impedirMesmoBancoDestino() {

    const origemId =
        $("#banco_origem_id").val();

    $("#banco_destino_id option")
        .prop("disabled", false);

    if (origemId) {
        $("#banco_destino_id option[value='"
            + origemId
            + "']")
            .prop("disabled", true);
    }

    if (
        $("#banco_destino_id").val()
        === origemId
    ) {
        $("#banco_destino_id")
            .val("");
    }
}


function impedirMesmoBancoOrigem() {

    const destinoId =
        $("#banco_destino_id").val();

    $("#banco_origem_id option")
        .prop("disabled", false);

    if (destinoId) {
        $("#banco_origem_id option[value='"
            + destinoId
            + "']")
            .prop("disabled", true);
    }

    if (
        $("#banco_origem_id").val()
        === destinoId
    ) {
        $("#banco_origem_id")
            .val("");
    }
}


// ==================================================
// LIMPAR FORMULÁRIOS
// ==================================================

function limparFormularioMovimentoBancario() {

    const formulario =
        document.getElementById(
            "formMovimentoBancario"
        );

    if (formulario) {
        formulario.reset();
    }

    $("#movimento_bancario_id")
        .val("");

    $("#origem")
        .val("MANUAL");

    $("#btnGuardarMovimento")
        .prop("disabled", false)
        .html(`
            <i class="fa fa-save"></i>
            Guardar Movimento
        `);
}


function limparFormularioTransferenciaBancaria() {

    const formulario =
        document.getElementById(
            "formTransferenciaBancaria"
        );

    if (formulario) {
        formulario.reset();
    }

    $("#taxa_transferencia")
        .val("0.00");

    $("#banco_origem_id option")
        .prop("disabled", false);

    $("#banco_destino_id option")
        .prop("disabled", false);

    $("#btnGuardarTransferencia")
        .prop("disabled", false)
        .html(`
            <i class="fa fa-exchange-alt"></i>
            Transferir
        `);
}


// ==================================================
// DATAS
// ==================================================

function definirDataAtualMovimento() {

    $("#data_movimento")
        .val(
            obterDataHoraLocal()
        );
}


function definirDataAtualTransferencia() {

    $("#data_transferencia")
        .val(
            obterDataHoraLocal()
        );
}


function obterDataHoraLocal() {

    const agora =
        new Date();

    const ano =
        agora.getFullYear();

    const mes =
        String(
            agora.getMonth() + 1
        ).padStart(2, "0");

    const dia =
        String(
            agora.getDate()
        ).padStart(2, "0");

    const horas =
        String(
            agora.getHours()
        ).padStart(2, "0");

    const minutos =
        String(
            agora.getMinutes()
        ).padStart(2, "0");

    return (
        `${ano}-${mes}-${dia}`
        + `T${horas}:${minutos}`
    );
}


// ==================================================
// FORMATAÇÃO
// ==================================================

function formatarMoedaMovimento(
    valor,
    moeda = "MZN"
) {
    try {
        return Number(valor || 0)
            .toLocaleString(
                "pt-MZ",
                {
                    style: "currency",
                    currency:
                        moeda || "MZN",
                    minimumFractionDigits: 2
                }
            );
    } catch {
        return (
            Number(valor || 0)
                .toFixed(2)
            + " "
            + (moeda || "MZN")
        );
    }
}


function formatarDataHoraBanco(data) {

    if (!data) {
        return "-";
    }

    const valor =
        String(data)
            .replace(" ", "T");

    const dataObjeto =
        new Date(valor);

    if (
        Number.isNaN(
            dataObjeto.getTime()
        )
    ) {
        return data;
    }

    return dataObjeto
        .toLocaleString(
            "pt-PT"
        );
}


function formatarCategoriaBanco(
    categoria
) {
    const nomes = {
        DEPOSITO:
            "Depósito",

        LEVANTAMENTO:
            "Levantamento",

        TRANSFERENCIA:
            "Transferência",

        PAGAMENTO:
            "Pagamento",

        RECEBIMENTO:
            "Recebimento",

        TARIFA:
            "Tarifa",

        JURO:
            "Juro",

        AJUSTE:
            "Ajuste",

        OUTRO:
            "Outro"
    };

    return nomes[categoria]
        || categoria
        || "-";
}