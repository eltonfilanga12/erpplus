const BANCO_URL =
    "/erpplus/app/ajax/banco_ajax.php";

let tabelaBancos;


// ==================================================
// MENSAGENS
// ==================================================

function bancoMensagemErro(
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


function bancoMensagemAviso(
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


function bancoToastSucesso(mensagem) {
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


function bancoConfirmar({
    titulo,
    texto,
    confirmar = "Confirmar",
    cor = "#dc3545"
}) {
    return Swal.fire({
        icon: "warning",
        title: titulo,
        text: texto,
        showCancelButton: true,
        confirmButtonText: confirmar,
        cancelButtonText: "Cancelar",
        confirmButtonColor: cor,
        reverseButtons: true,
        focusCancel: true
    });
}


function obterMensagemBanco(json) {
    return json.mensagem
        || json.message
        || "Ocorreu um erro inesperado.";
}


// ==================================================
// REQUISIÇÃO
// ==================================================

async function requisicaoBanco(
    url,
    opcoes = {}
) {
    try {
        const resposta = await fetch(
            url,
            opcoes
        );

        let json;

        try {
            json = await resposta.json();
        } catch {
            throw new Error(
                "O servidor retornou uma resposta inválida."
            );
        }

        if (!resposta.ok || json.success === false) {
            throw new Error(
                obterMensagemBanco(json)
            );
        }

        return json;
    } catch (erro) {
        await bancoMensagemErro(
            erro.message
        );

        throw erro;
    }
}


// ==================================================
// INICIALIZAÇÃO
// ==================================================

$(document).ready(function () {

    // ==========================================
    // EVENTOS
    // ==========================================

    $("#btnNovoBanco").on(
        "click",
        abrirNovoBanco
    );


    $("#formBanco").on(
        "submit",
        guardarBanco
    );


    $("#modalBanco").on(
        "hidden.bs.modal",
        limparFormularioBanco
    );


    // ==========================================
    // CARREGAMENTOS
    // ==========================================

    carregarTabelaBancos();

    carregarFiliaisBanco();

    carregarStatusBanco();

});


// ==================================================
// DATATABLE
// ==================================================

function carregarTabelaBancos() {

    tabelaBancos =
        $("#bancosTable").DataTable({

            ajax: {
                url: BANCO_URL,
                type: "POST",

                data: {
                    acao: "listar"
                },

                dataSrc: function (json) {

                    if (!json.success) {

                        bancoMensagemErro(
                            obterMensagemBanco(json)
                        );

                        return [];
                    }

                    return json.data || [];
                },

                error: function () {
                    bancoMensagemErro(
                        "Não foi possível carregar os bancos."
                    );
                }
            },

            columns: [

                {
                    data: null,
                    searchable: false,
                    orderable: false,
                    render: function (data, type, row, meta) {
                        // meta.row é o índice da linha (começa em 0)
                        // meta.settings._iDisplayStart lida com a paginação correta
                        const indice = meta.row + 1 + meta.settings._iDisplayStart;
                        return pad(indice, 4); // Exibe 0001, 0002, 0003... para cada empresa
                    }
                },

                {
                    data: "nome_banco",
                    defaultContent: "-"
                },

                {
                    data: null,

                    render: function (row) {

                        if (!row.agencia) {
                            return "-";
                        }

                        return row.agencia
                            + (
                                row.digito_agencia
                                    ? "-"
                                    + row.digito_agencia
                                    : ""
                            );
                    }
                },

                {
                    data: null,

                    render: function (row) {

                        return row.conta
                            + (
                                row.digito_conta
                                    ? "-"
                                    + row.digito_conta
                                    : ""
                            );
                    }
                },

                {
                    data: "titular",
                    defaultContent: "-"
                },

                {
                    data: "tipo_conta",
                    defaultContent: "-"
                },

                {
                    data: "moeda",
                    defaultContent: "MZN"
                },

                {
                    data: "saldo_atual",
                    className: "text-right",

                    render: function (valor) {

                        return formatarMoedaBanco(
                            valor
                        );
                    }
                },

                {
                    data: "status",

                    render: function (status) {

                        const classes = {
                            Activo: "success",
                            Inactivo: "secondary",
                            Bloqueado: "danger",
                            Encerrado: "dark"
                        };

                        const classe =
                            classes[status]
                            || "secondary";

                        return `
                            <span
                                class="badge badge-${classe}">

                                ${status || "-"}

                            </span>
                        `;
                    }
                },

                {
                    data: "id",
                    className: "text-right",
                    orderable: false,
                    searchable: false,

                    render: function (id) {

                        return `
                            <div class="btn-group btn-group-sm">

                                <button
                                    type="button"
                                    class="btn btn-info"
                                    onclick="editarBanco(${id})"
                                    title="Editar">

                                    <i class="fa fa-edit"></i>

                                </button>

                                <button
                                    type="button"
                                    class="btn btn-danger"
                                    onclick="eliminarBanco(${id})"
                                    title="Eliminar">

                                    <i class="fa fa-trash"></i>

                                </button>

                            </div>
                        `;
                    }
                }

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
                        "Lista de Bancos",

                    exportOptions: {
                        columns: [
                            0, 1, 2, 3,
                            4, 5, 6, 7, 8
                        ]
                    }
                },

                {
                    extend: "pdfHtml5",

                    text:
                        '<i class="fa fa-file-pdf"></i> PDF',

                    title:
                        "Lista de Bancos",

                    orientation:
                        "landscape",

                    pageSize:
                        "A4",

                    exportOptions: {
                        columns: [
                            0, 1, 2, 3,
                            4, 5, 6, 7, 8
                        ]
                    }
                },

                {
                    extend: "print",

                    text:
                        '<i class="fa fa-print"></i> Imprimir',

                    title:
                        "Lista de Bancos",

                    exportOptions: {
                        columns: [
                            0, 1, 2, 3,
                            4, 5, 6, 7, 8
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
// MOEDA
// ==================================================

function formatarMoedaBanco(valor) {

    return Number(valor || 0)
        .toLocaleString(
            "pt-PT",
            {
                style: "currency",
                currency: "MZN"
            }
        );
}


// ==================================================
// NOVO
// ==================================================

function abrirNovoBanco() {

    limparFormularioBanco();

    $("#tituloModalBanco")
        .text("Novo Banco");

    $("#saldo_inicial")
        .prop("disabled", false);

    $("#modalBanco")
        .modal("show");
}


// ==================================================
// LIMPAR FORMULÁRIO
// ==================================================

function limparFormularioBanco() {

    const formulario =
        document.getElementById(
            "formBanco"
        );

    if (formulario) {
        formulario.reset();
    }

    $("#banco_id").val("");

    $("#tipo_conta").val("Corrente");

    $("#moeda").val("MZN");

    $("#saldo_inicial")
        .val("0.00")
        .prop("disabled", false);

    $("#tituloModalBanco")
        .text("Novo Banco");

    $("#btnGuardarBanco")
        .prop("disabled", false)
        .html(`
            <i class="fa fa-save"></i>
            Guardar
        `);
}

function pad(num, size) {
    var s = num + "";
    while (s.length < size) s = "0" + s;
    return s;
}


// ==================================================
// CARREGAR FILIAIS
// ==================================================

async function carregarFiliaisBanco() {

    const select =
        $("#banco_filial_id");


    select.html(`
        <option value="">
            Sem filial
        </option>
    `);


    try {

        const json =
            await requisicaoBanco(
                BANCO_URL
                + "?acao=filiais"
            );


        const filiais =
            json.data
            || [];


        filiais.forEach(
            function (filial) {

                select.append(`
                    <option value="${filial.id}">
                        ${filial.nome}
                    </option>
                `);
            }
        );


    } catch (erro) {

        console.error(
            "Erro ao carregar filiais:",
            erro
        );

    }
}


// ==================================================
// CARREGAR STATUS
// ==================================================

async function carregarStatusBanco() {

    const select =
        $("#banco_status_id");

    select.html(`
        <option value="">
            Selecione
        </option>
    `);

    try {

        const resposta = await fetch(
            "/erpplus/app/ajax/banco_ajax.php?acao=listar_status"
        );

        const json =
            await resposta.json();

        if (!resposta.ok || json.success === false) {
            throw new Error(
                json.mensagem
                || "Não foi possível carregar os status."
            );
        }

        const statusBanco =
            (json.data || []).filter(function (status) {

                return String(status.tipo)
                    .toLowerCase()
                    .trim() === "banco";

            });

        statusBanco.forEach(function (status) {

            select.append(`
                <option value="${status.id}">
                    ${status.nome}
                </option>
            `);

        });

        const statusActivo =
            statusBanco.find(function (status) {

                return String(status.nome)
                    .toLowerCase()
                    .trim() === "activo";

            });

        if (statusActivo) {

            select.val(
                String(statusActivo.id)
            );

        }

        if (statusBanco.length === 0) {

            throw new Error(
                "Nenhum status do tipo banco foi encontrado."
            );

        }

    } catch (erro) {

        select.html(`
            <option value="">
                Status indisponível
            </option>
        `);

        await bancoMensagemErro(
            erro.message
        );

    }
}

// ==================================================
// GUARDAR
// ==================================================

async function guardarBanco(evento) {

    evento.preventDefault();

    const formulario =
        evento.target;

    const botao =
        $("#btnGuardarBanco");

    const id =
        $("#banco_id").val();

    const nomeBanco =
        $("#nome_banco")
            .val()
            .trim();

    const conta =
        $("#conta")
            .val()
            .trim();

    const titular =
        $("#titular")
            .val()
            .trim();

    const statusId =
        $("#banco_status_id").val();

    const saldoInicial =
        Number(
            $("#saldo_inicial").val()
        );

    if (!nomeBanco) {

        await bancoMensagemAviso(
            "Informe o nome do banco."
        );

        $("#nome_banco").focus();

        return;
    }

    if (!conta) {

        await bancoMensagemAviso(
            "Informe o número da conta."
        );

        $("#conta").focus();

        return;
    }

    if (!titular) {

        await bancoMensagemAviso(
            "Informe o titular da conta."
        );

        $("#titular").focus();

        return;
    }

    if (!statusId) {

        await bancoMensagemAviso(
            "Selecione o status do banco."
        );

        $("#banco_status_id").focus();

        return;
    }

    if (
        !id
        && (
            !Number.isFinite(saldoInicial)
            || saldoInicial < 0
        )
    ) {

        await bancoMensagemAviso(
            "O saldo inicial não pode ser negativo."
        );

        return;
    }

    const form =
        new FormData(formulario);

    form.append(
        "acao",
        "guardar"
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
            await requisicaoBanco(
                BANCO_URL,
                {
                    method: "POST",
                    body: form
                }
            );

        $("#modalBanco")
            .modal("hide");

        tabelaBancos.ajax.reload(
            null,
            false
        );

        bancoToastSucesso(
            json.mensagem
            || (
                id
                    ? "Banco atualizado com sucesso."
                    : "Banco cadastrado com sucesso."
            )
        );
    } finally {
        botao
            .prop("disabled", false)
            .html(`
                <i class="fa fa-save"></i>
                Guardar
            `);
    }
}


// ==================================================
// EDITAR
// ==================================================

async function editarBanco(id) {

    try {
        const form =
            new FormData();

        form.append(
            "acao",
            "buscar"
        );

        form.append(
            "id",
            id
        );

        const json =
            await requisicaoBanco(
                BANCO_URL,
                {
                    method: "POST",
                    body: form
                }
            );

        const banco =
            json.data;

        $("#banco_id")
            .val(banco.id);

        $("#banco_filial_id")
            .val(banco.filial_id);

        $("#banco_status_id")
            .val(banco.status_id);

        $("#nome_banco")
            .val(banco.nome_banco);

        $("#codigo_banco")
            .val(banco.codigo_banco);

        $("#agencia")
            .val(banco.agencia);

        $("#digito_agencia")
            .val(banco.digito_agencia);

        $("#conta")
            .val(banco.conta);

        $("#digito_conta")
            .val(banco.digito_conta);

        $("#iban")
            .val(banco.iban);

        $("#swift")
            .val(banco.swift);

        $("#titular")
            .val(banco.titular);

        $("#documento_titular")
            .val(
                banco.documento_titular
            );

        $("#tipo_conta")
            .val(banco.tipo_conta);

        $("#moeda")
            .val(banco.moeda);

        $("#saldo_inicial")
            .val(banco.saldo_inicial)
            .prop("disabled", true);

        $("#observacoes")
            .val(banco.observacoes);

        $("#tituloModalBanco")
            .text("Editar Banco");

        $("#modalBanco")
            .modal("show");

    } catch {
        // A função requisicaoBanco já mostra a mensagem.
    }
}


// ==================================================
// ELIMINAR
// ==================================================

async function eliminarBanco(id) {

    const confirmacao =
        await bancoConfirmar({
            titulo:
                "Eliminar banco?",

            texto:
                "Esta operação não poderá ser desfeita.",

            confirmar:
                "Eliminar",

            cor:
                "#dc3545"
        });

    if (!confirmacao.isConfirmed) {
        return;
    }

    const form =
        new FormData();

    form.append(
        "acao",
        "eliminar"
    );

    form.append(
        "id",
        id
    );

    try {
        Swal.fire({
            title:
                "A eliminar...",

            allowOutsideClick:
                false,

            allowEscapeKey:
                false,

            didOpen: function () {
                Swal.showLoading();
            }
        });

        const json =
            await requisicaoBanco(
                BANCO_URL,
                {
                    method: "POST",
                    body: form
                }
            );

        Swal.close();

        tabelaBancos.ajax.reload(
            null,
            false
        );

        bancoToastSucesso(
            json.mensagem
            || "Banco eliminado com sucesso."
        );

    } catch {
        Swal.close();
    }
}