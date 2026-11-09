const CAIXA_URL =
    "/erpplus/app/ajax/caixa_ajax.php";


function mensagemSucesso(titulo, texto = "") {
    return Swal.fire({
        icon: "success",
        title: titulo,
        text: texto,
        confirmButtonText: "OK",
        timer: 2500,
        timerProgressBar: true
    });
}

function mensagemErro(mensagem, titulo = "Não foi possível concluir") {
    return Swal.fire({
        icon: "error",
        title: titulo,
        text: mensagem || "Ocorreu um erro inesperado.",
        confirmButtonText: "Fechar"
    });
}

function mensagemAviso(mensagem, titulo = "Atenção") {
    return Swal.fire({
        icon: "warning",
        title: titulo,
        text: mensagem,
        confirmButtonText: "Entendi"
    });
}

function toastSucesso(mensagem) {
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

function confirmarAcao({
    titulo,
    texto,
    textoConfirmar = "Confirmar",
    corConfirmar = "#dc3545"
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

function obterMensagemErro(json) {
    return json.mensagem
        || json.message
        || "Ocorreu um erro inesperado.";
}


let tabelaCaixas;



$(document).ready(function () {

    carregarCaixas();
    carregarStatus();
    carregarCaixasSelect();

    $("#formCaixa").on("submit", guardarCaixa);

    $("#formAbertura").on("submit", abrirCaixaSalvar);

    $("#formMovimento").on("submit", salvarMovimento);

    $("#formFechamento").on("submit", salvarFechamento);

    $('#formTransferencia').on('submit', salvarTransferencia);

});

$(document)
    .off(
        "show.bs.modal.caixa",
        "#modalCaixa"
    )
    .on(
        "show.bs.modal.caixa",
        "#modalCaixa",
        function () {

            console.log(
                "[CAIXA] modal aberto"
            );

            carregarFiliaisCaixa();
        }
    );

async function requisicaoCaixa(url, opcoes = {}) {
    try {
        const resposta = await fetch(url, opcoes);

        let json;

        try {
            json = await resposta.json();
        } catch {
            throw new Error(
                "O servidor retornou uma resposta inválida."
            );
        }

        if (!resposta.ok || json.success === false) {
            throw new Error(obterMensagemErro(json));
        }

        return json;
    } catch (erro) {
        await mensagemErro(erro.message);

        throw erro;
    }
}

async function carregarCaixasSelect() {
    try {
        const json = await requisicaoCaixa(
            CAIXA_URL + "?acao=listar"
        );

        const origem = $("#caixa_origem_id");
        const destino = $("#caixa_destino_id");

        origem.html(`
            <option value="">
                Selecione o caixa de origem
            </option>
        `);

        destino.html(`
            <option value="">
                Selecione o caixa de destino
            </option>
        `);

        const caixasAbertos = json.data.filter(
            caixa => caixa.status === "Aberto"
        );

        caixasAbertos.forEach(caixa => {
            const saldo = moeda(caixa.saldo_atual);

            origem.append(`
                <option value="${caixa.id}">
                    ${caixa.nome} — ${saldo}
                </option>
            `);

            destino.append(`
                <option value="${caixa.id}">
                    ${caixa.nome} — ${saldo}
                </option>
            `);
        });

        if (caixasAbertos.length < 2) {
            $("#formTransferencia button[type=submit]")
                .prop("disabled", true);
        } else {
            $("#formTransferencia button[type=submit]")
                .prop("disabled", false);
        }
    } catch {
        $("#formTransferencia button[type=submit]")
            .prop("disabled", true);
    }
}

$("#caixa_origem_id").on("change", function () {
    const origemId = $(this).val();

    $("#caixa_destino_id option").prop(
        "disabled",
        false
    );

    if (origemId) {
        $("#caixa_destino_id option[value='" + origemId + "']")
            .prop("disabled", true);
    }

    if ($("#caixa_destino_id").val() === origemId) {
        $("#caixa_destino_id").val("");
    }
});

$("#caixa_destino_id").on("change", function () {
    const destinoId = $(this).val();

    $("#caixa_origem_id option").prop(
        "disabled",
        false
    );

    if (destinoId) {
        $("#caixa_origem_id option[value='" + destinoId + "']")
            .prop("disabled", true);
    }

    if ($("#caixa_origem_id").val() === destinoId) {
        $("#caixa_origem_id").val("");
    }
});

async function abrirTransferencia() {
    $("#formTransferencia")[0].reset();

    await carregarCaixasSelect();

    const quantidadeCaixas =
        $("#caixa_origem_id option").length - 1;

    if (quantidadeCaixas < 2) {
        await mensagemAviso(
            "É necessário ter pelo menos dois caixas abertos para realizar uma transferência.",
            "Transferência indisponível"
        );

        return;
    }

    $("#modalTransferencia").modal("show");
}

async function salvarTransferencia(e) {
    e.preventDefault();

    const formulario = e.target;
    const botao = $(formulario)
        .find("button[type=submit]");

    const origemId =
        $("#caixa_origem_id").val();

    const destinoId =
        $("#caixa_destino_id").val();

    const valor = Number(
        $(formulario)
            .find("input[name=valor]")
            .val()
    );

    if (!origemId || !destinoId) {
        await mensagemAviso(
            "Selecione os caixas de origem e destino."
        );

        return;
    }

    if (origemId === destinoId) {
        await mensagemAviso(
            "O caixa de origem e o caixa de destino devem ser diferentes."
        );

        return;
    }

    if (!Number.isFinite(valor) || valor <= 0) {
        await mensagemAviso(
            "Informe um valor maior que zero."
        );

        return;
    }

    const confirmacao = await confirmarAcao({
        titulo: "Confirmar transferência?",
        texto: `Será transferido ${moeda(valor)} entre os caixas selecionados.`,
        textoConfirmar: "Transferir",
        corConfirmar: "#007bff"
    });

    if (!confirmacao.isConfirmed) {
        return;
    }

    const form = new FormData(formulario);

    form.append("acao", "transferir");

    try {
        botao.prop("disabled", true).html(`
            <span class="spinner-border spinner-border-sm"></span>
            A transferir...
        `);

        await requisicaoCaixa(CAIXA_URL, {
            method: "POST",
            body: form
        });

        $("#modalTransferencia").modal("hide");

        formulario.reset();

        tabelaCaixas.ajax.reload(null, false);

        await carregarCaixasSelect();

        toastSucesso(
            "Transferência realizada com sucesso."
        );
    } finally {
        botao.prop("disabled", false).html(`
            <i class="fa fa-exchange-alt"></i>
            Transferir
        `);
    }
}
function extratoCaixa(id) {
    $("#extrato_caixa_id").val(id);

    $("#data_inicio").val("");
    $("#data_fim").val("");
    $("#filtro_tipo").val("");

    $("#modalExtrato").modal("show");

    carregarExtrato();
}

let tabelaExtrato;

function carregarExtrato() {
    const caixaId =
        $("#extrato_caixa_id").val();

    if (!caixaId) {
        mensagemAviso(
            "Não foi possível identificar o caixa."
        );

        return;
    }

    if ($.fn.DataTable.isDataTable("#extratoTable")) {
        $("#extratoTable")
            .DataTable()
            .destroy();
    }

    tabelaExtrato =
        $("#extratoTable").DataTable({
            ajax: {
                url: CAIXA_URL,

                data: function () {
                    return {
                        acao: "extrato",
                        caixa_id: caixaId,
                        inicio:
                            $("#data_inicio").val(),
                        fim:
                            $("#data_fim").val(),
                        tipo:
                            $("#filtro_tipo").val()
                    };
                },

                dataSrc: function (json) {
                    if (!json.success) {
                        mensagemErro(
                            obterMensagemErro(json)
                        );

                        return [];
                    }

                    return json.data || [];
                },

                error: function () {
                    mensagemErro(
                        "Não foi possível carregar o extrato."
                    );
                }
            },

            columns: [
                {
                    data: "data_movimento",
                    defaultContent: "-"
                },
                {
                    data: "tipo",
                    render: function (tipo) {
                        const classes = {
                            ENTRADA: "success",
                            SUPRIMENTO: "info",
                            SAIDA: "danger",
                            SANGRIA: "warning"
                        };

                        const classe =
                            classes[tipo] || "secondary";

                        return `
                            <span class="badge badge-${classe}">
                                ${tipo || "-"}
                            </span>
                        `;
                    }
                },
                {
                    data: "origem",
                    defaultContent: "MANUAL"
                },
                {
                    data: "descricao",
                    defaultContent: "-"
                },
                {
                    data: "operador",
                    defaultContent: "-"
                },
                {
                    data: "valor",
                    className: "text-right",
                    render: function (valor, tipo, row) {
                        const sinal =
                            ["SAIDA", "SANGRIA"]
                                .includes(row.tipo)
                                ? "-"
                                : "+";

                        return `
                            <strong>
                                ${sinal}${moeda(valor)}
                            </strong>
                        `;
                    }
                }
            ],

            order: [[0, "desc"]],

            pageLength: 10,

            responsive: true,

            autoWidth: false,

            language: {
                url:
                    "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
            },

            dom:
                "<'row'<'col-md-8'B><'col-md-4'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row'<'col-md-5'i><'col-md-7'p>>",

            buttons: [
                {
                    extend: "excelHtml5",
                    text:
                        '<i class="fa fa-file-excel"></i> Excel',
                    title: "Extrato do Caixa",
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: "pdfHtml5",
                    text:
                        '<i class="fa fa-file-pdf"></i> PDF',
                    title: "Extrato do Caixa",
                    orientation: "landscape",
                    pageSize: "A4",
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: "print",
                    text:
                        '<i class="fa fa-print"></i> Imprimir',
                    title: "Extrato do Caixa",
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                }
            ]
        });
}
// =====================================
// DATATABLE
// =====================================


function carregarCaixas() {


    tabelaCaixas =

        $("#caixasTable").DataTable({


            ajax: {

                url: CAIXA_URL,

                data: {
                    acao: "listar"
                },

                dataSrc: function (json) {


                    if (json.perfil_id == 1) {

                        $("#campoStatusCaixa")
                            .show();


                    } else {


                        $("#campoStatusCaixa")
                            .hide();


                    }


                    return json.data;


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
                    data: "nome"
                },



                {
                    data: "codigo",
                    defaultContent: ""
                },



                {
                    data: "filial",
                    defaultContent: "Sem filial"
                },



                {
                    data: "saldo_inicial",

                    render: function (v) {

                        return moeda(v);

                    }

                },



                {
                    data: "saldo_atual",

                    render: function (v) {

                        return moeda(v);

                    }

                },



                {
                    data: "status",

                    render: function (s) {


                        let classe =
                            "secondary";



                        if (s == "Aberto")
                            classe = "success";


                        if (s == "Fechado")
                            classe = "warning";


                        if (s == "Bloqueado")
                            classe = "danger";



                        return `

                    <span class="badge badge-${classe}">
                    ${s}
                    </span>

                    `;

                    }


                },





                {
                    data: "id",
                    render: function (id, type, row) {

                        let botoes = "";

                        if (row.status === "Fechado") {

                            botoes += `
                                        <button class="btn btn-info btn-sm"
                                            onclick="editarCaixa(${id})">
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <button class="btn btn-success btn-sm"
                                            onclick="abrirCaixa(${id})">
                                            <i class="fa fa-unlock"></i>
                                        </button>

                                        <button class="btn btn-danger btn-sm"
                                            onclick="eliminarCaixa(${id})">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    `;

                        } else if (row.status === "Aberto") {

                            botoes += `
                            <button class="btn btn-primary btn-sm"
                                onclick="movimentoCaixa(${id})">
                                <i class="fa fa-money"></i>
                            </button>

                            <button class="btn btn-warning btn-sm"
                                onclick="fecharCaixa(${id})">
                                <i class="fa fa-lock"></i>
                            </button>
                        `;

                        } else if (row.status === "Bloqueado") {

                            botoes += `
                            <button class="btn btn-secondary btn-sm"
                                onclick="visualizarCaixa(${id})">
                                <i class="fa fa-eye"></i>
                            </button>
                        `;

                        }

                        botoes += `
                        <button
                            class="btn btn-dark btn-sm"
                            onclick="extratoCaixa(${id})"
                            title="Ver extrato">
                            <i class="fa fa-file"></i>
                        </button>
                    `;

                        return botoes;
                    }
                }



            ],



            pageLength: 10,

            lengthMenu: [
                [5, 10, 25, 50, 100],
                [5, 10, 25, 50, 100]
            ],

            autoWidth: false,

            responsive: false,

            dom:
                "<'row'<'col-sm-12 col-md-8 d-flex align-items-center'Bl><'col-sm-12 col-md-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",



            buttons: [


                {

                    extend: "excelHtml5",

                    text:
                        '<i class="fa fa-file-excel"></i> Excel',

                    title: "Lista de Caixas",


                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }

                },



                {

                    extend: "pdfHtml5",

                    text:
                        '<i class="fa fa-file-pdf"></i> PDF',


                    title: "Lista de Caixas",


                    orientation: "landscape",

                    pageSize: "A4",


                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }

                },



                {

                    extend: "print",

                    text:
                        '<i class="fa fa-print"></i> Imprimir',




                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    }

                }


            ],



            language: {

                url:
                    "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"

            }



        });



}





function pad(num, size) {
    var s = num + "";
    while (s.length < size) s = "0" + s;
    return s;
}



// =====================================
// MOEDA
// =====================================


function moeda(valor) {


    return Number(valor || 0)
        .toLocaleString(
            "pt-PT",
            {
                style: "currency",
                currency: "MZN"
            }
        );


}



// =====================================
// FILIAIS
// =====================================

async function carregarFiliaisCaixa() {

    console.log(
        "[CAIXA] iniciar carregamento de filiais"
    );

    const select =
        document.getElementById(
            "caixaFilialId"
        );

    console.log(
        "[CAIXA] select encontrado:",
        select
    );

    if (!select) {

        console.error(
            "[CAIXA] #caixaFilialId não existe no DOM."
        );

        return;
    }

    select.innerHTML = `
        <option value="">
            A carregar filiais...
        </option>
    `;

    try {

        const resposta =
            await fetch(
                "/erpplus/app/ajax/caixa_ajax.php?acao=filiais",
                {
                    method: "GET",

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers: {
                        "Accept":
                            "application/json"
                    }
                }
            );

        const texto =
            await resposta.text();

        console.log(
            "[CAIXA] resposta bruta:",
            texto
        );

        let json;

        try {

            json =
                JSON.parse(
                    texto
                );

        } catch (erro) {

            throw new Error(
                "O servidor não retornou JSON válido."
            );
        }

        console.log(
            "[CAIXA] JSON:",
            json
        );

        if (
            !resposta.ok
            ||
            json.success === false
        ) {
            throw new Error(
                json.mensagem
                || "Erro ao carregar filiais."
            );
        }

        const filiais =
            Array.isArray(
                json.data
            )
                ? json.data
                : [];

        select.innerHTML = "";

        /*
         * Se quiseres permitir
         * caixas sem filial.
         */
        const opcaoVazia =
            document.createElement(
                "option"
            );

        opcaoVazia.value = "";

        opcaoVazia.textContent =
            "Sem filial";

        select.appendChild(
            opcaoVazia
        );

        filiais.forEach(
            function (filial) {

                const option =
                    document.createElement(
                        "option"
                    );

                option.value =
                    String(
                        filial.id
                    );

                option.textContent =
                    filial.codigo
                        ? filial.codigo
                        + " - "
                        + filial.nome
                        : filial.nome;

                select.appendChild(
                    option
                );
            }
        );

        console.log(
            "[CAIXA] total filiais:",
            filiais.length
        );

        console.log(
            "[CAIXA] total options:",
            select.options.length
        );

    } catch (erro) {

        console.error(
            "[CAIXA] erro:",
            erro
        );

        select.innerHTML = `
            <option value="">
                Erro ao carregar filiais
            </option>
        `;
    }
}


function limparFormularioCaixa() {
    const form = document.getElementById("formCaixa");

    if (form) {
        form.reset();
    }

    $("#caixa_id").val("");
    $("#status_caixa").val("");
    $("#tituloModalCaixa").text("Novo Caixa");

    $("#formCaixa input").prop("disabled", false);
    $("#formCaixa select").prop("disabled", false);
    $("#formCaixa textarea").prop("disabled", false);
    $("#formCaixa button[type=submit]").show();
}

$("#modalCaixa").on("hidden.bs.modal", function () {
    limparFormularioCaixa();
});





// =====================================
// GUARDAR
// =====================================


async function guardarCaixa(e) {
    e.preventDefault();

    const formulario = e.target;
    const botao = $(formulario)
        .find("button[type=submit]");

    const nome = $("#nome").val().trim();
    const caixaId = $("#caixa_id").val();

    if (!nome) {
        await mensagemAviso(
            "Informe o nome do caixa."
        );

        $("#nome").focus();

        return;
    }

    const form = new FormData(formulario);

    form.append("acao", "guardar");

    try {
        botao.prop("disabled", true).html(`
            <span class="spinner-border spinner-border-sm"></span>
            A guardar...
        `);

        await requisicaoCaixa(CAIXA_URL, {
            method: "POST",
            body: form
        });

        $("#modalCaixa").modal("hide");

        limparFormularioCaixa();

        tabelaCaixas.ajax.reload(null, false);

        await carregarCaixasSelect();

        toastSucesso(
            caixaId
                ? "Caixa atualizado com sucesso."
                : "Caixa cadastrado com sucesso."
        );
    } finally {
        botao.prop("disabled", false).html(`
            <i class="fa fa-save"></i>
            Guardar
        `);
    }
}









// =====================================
// EDITAR
// =====================================

async function editarCaixa(id) {

    try {

        const json =
            await requisicaoCaixa(
                CAIXA_URL
                + "?acao=obter&id="
                + id
            );

        const c =
            json.data;

        if (!c) {
            throw new Error(
                "Caixa não encontrado."
            );
        }

        $("#caixa_id")
            .val(
                c.id
            );

        $("#nome")
            .val(
                c.nome
            );

        $("#codigo")
            .val(
                c.codigo
            );

        $("#descricao")
            .val(
                c.descricao
            );

        $("#localizacao")
            .val(
                c.localizacao
            );

        $("#status_caixa")
            .val(
                c.status_id
            );

        $("#saldo_inicial")
            .val(
                c.saldo_inicial
            )
            .prop(
                "disabled",
                true
            );

        $("#tituloModalCaixa")
            .text(
                "Editar Caixa"
            );

        /*
         * Primeiro carregar.
         */
        await carregarFiliaisCaixa();

        /*
         * Depois selecionar.
         */
        $("#caixaFilialId")
            .val(
                c.filial_id
                || ""
            );

        $("#modalCaixa")
            .modal(
                "show"
            );

    } catch (erro) {

        console.error(
            erro
        );
    }
}

async function carregarStatus() {

    const select =
        $("#status_caixa");

    select.html(`
        <option value="">
            Seleccione
        </option>
    `);

    try {

        const json =
            await requisicaoCaixa(
                CAIXA_URL + "?acao=status"
            );

        const status =
            Array.isArray(json.data)
                ? json.data
                : [];

        status.forEach(function (s) {

            select.append(`
                <option value="${s.id}">
                    ${s.nome}
                </option>
            `);

        });

    } catch (erro) {

        console.error(
            "Erro ao carregar os status do caixa:",
            erro
        );

    }
}

// =====================================
// ELIMINAR
// =====================================


async function eliminarCaixa(id) {
    const confirmacao = await confirmarAcao({
        titulo: "Eliminar caixa?",
        texto:
            "Esta operação não poderá ser desfeita. " +
            "Caixas com movimentos não podem ser eliminados.",
        textoConfirmar: "Eliminar",
        corConfirmar: "#dc3545"
    });

    if (!confirmacao.isConfirmed) {
        return;
    }

    const form = new FormData();

    form.append("acao", "eliminar");
    form.append("id", id);

    try {
        Swal.fire({
            title: "A eliminar...",
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        await requisicaoCaixa(CAIXA_URL, {
            method: "POST",
            body: form
        });

        Swal.close();

        tabelaCaixas.ajax.reload(null, false);

        await carregarCaixasSelect();

        toastSucesso(
            "Caixa eliminado com sucesso."
        );
    } catch {
        Swal.close();
    }
}








// =====================================
// ABRIR CAIXA
// =====================================


function abrirCaixa(id) {
    $("#formAbertura")[0].reset();

    $("#abertura_caixa_id").val(id);

    $("#modalAbertura").modal("show");
}

async function abrirCaixaSalvar(e) {
    e.preventDefault();

    const formulario = e.target;
    const botao = $(formulario)
        .find("button[type=submit]");

    const valorInicial = Number(
        $("#valor_inicial").val()
    );

    if (!Number.isFinite(valorInicial) || valorInicial < 0) {
        await mensagemAviso(
            "O valor inicial não pode ser negativo."
        );

        return;
    }

    const confirmacao = await confirmarAcao({
        titulo: "Abrir este caixa?",
        texto:
            "O caixa será aberto com o valor inicial de " +
            moeda(valorInicial) +
            ".",
        textoConfirmar: "Abrir caixa",
        corConfirmar: "#28a745"
    });

    if (!confirmacao.isConfirmed) {
        return;
    }

    const form = new FormData(formulario);

    form.append("acao", "abrir");

    try {
        botao.prop("disabled", true).html(`
            <span class="spinner-border spinner-border-sm"></span>
            A abrir...
        `);

        await requisicaoCaixa(CAIXA_URL, {
            method: "POST",
            body: form
        });

        $("#modalAbertura").modal("hide");

        formulario.reset();

        tabelaCaixas.ajax.reload(null, false);

        await carregarCaixasSelect();

        toastSucesso(
            "Caixa aberto com sucesso."
        );
    } finally {
        botao.prop("disabled", false).html(
            "Abrir Caixa"
        );
    }
}

async function salvarMovimento(e) {
    e.preventDefault();

    const formulario = e.target;
    const botao = $(formulario)
        .find("button[type=submit]");

    const tipo = $("#tipo").val();

    const valor = Number(
        $("#valor_movimento").val()
    );

    const descricao =
        $("#descricao_movimento")
            .val()
            .trim();

    if (!tipo) {
        await mensagemAviso(
            "Selecione o tipo de movimento."
        );

        return;
    }

    if (!Number.isFinite(valor) || valor <= 0) {
        await mensagemAviso(
            "Informe um valor maior que zero."
        );

        return;
    }

    if (!descricao) {
        await mensagemAviso(
            "Informe a descrição do movimento."
        );

        $("#descricao_movimento").focus();

        return;
    }

    const nomesTipos = {
        ENTRADA: "Entrada",
        SAIDA: "Saída",
        SANGRIA: "Sangria",
        SUPRIMENTO: "Suprimento"
    };

    const confirmacao = await confirmarAcao({
        titulo: "Confirmar movimento?",
        texto:
            `${nomesTipos[tipo]} no valor de ${moeda(valor)}.`,
        textoConfirmar: "Registar",
        corConfirmar:
            ["SAIDA", "SANGRIA"].includes(tipo)
                ? "#dc3545"
                : "#28a745"
    });

    if (!confirmacao.isConfirmed) {
        return;
    }

    const form = new FormData(formulario);

    form.append("acao", "movimento");

    try {
        botao.prop("disabled", true).html(`
            <span class="spinner-border spinner-border-sm"></span>
            A guardar...
        `);

        await requisicaoCaixa(CAIXA_URL, {
            method: "POST",
            body: form
        });

        $("#modalMovimento").modal("hide");

        formulario.reset();

        tabelaCaixas.ajax.reload(null, false);

        await carregarCaixasSelect();

        toastSucesso(
            "Movimento registado com sucesso."
        );
    } finally {
        botao.prop("disabled", false).html(
            "Guardar Movimento"
        );
    }
}

async function salvarFechamento(e) {
    e.preventDefault();

    const formulario = e.target;

    const botao = $(formulario)
        .find("button[type=submit]");

    const saldoInformado = Number(
        $("#saldo_informado").val()
    );

    if (
        !Number.isFinite(saldoInformado)
        || saldoInformado < 0
    ) {
        await mensagemAviso(
            "Informe um saldo contado válido."
        );

        return;
    }

    const confirmacao = await confirmarAcao({
        titulo: "Fechar este caixa?",
        texto:
            "O saldo contado informado é " +
            moeda(saldoInformado) +
            ". Depois de fechado, o caixa não aceitará novos movimentos.",
        textoConfirmar: "Fechar caixa",
        corConfirmar: "#dc3545"
    });

    if (!confirmacao.isConfirmed) {
        return;
    }

    const form = new FormData(formulario);

    form.append("acao", "fechar");

    try {
        botao.prop("disabled", true).html(`
            <span class="spinner-border spinner-border-sm"></span>
            A fechar...
        `);

        const json = await requisicaoCaixa(
            CAIXA_URL,
            {
                method: "POST",
                body: form
            }
        );

        $("#modalFechamento").modal("hide");

        formulario.reset();

        tabelaCaixas.ajax.reload(null, false);

        await carregarCaixasSelect();

        const dados = json.data;

        const diferenca = Number(
            dados.diferenca || 0
        );

        let titulo =
            "Caixa fechado corretamente";

        let icone = "success";

        let situacao = "Diferença";

        if (diferenca < 0) {
            titulo = "Caixa fechado com falta";
            icone = "warning";
            situacao = "Falta no caixa";
        } else if (diferenca > 0) {
            titulo = "Caixa fechado com sobra";
            icone = "warning";
            situacao = "Sobra no caixa";
        } else {
            situacao = "Diferença";
        }

        await Swal.fire({
            icon: icone,
            title: titulo,
            html: `
                <div class="text-left">

                    <p>
                        <strong>Saldo do sistema:</strong>
                        ${moeda(dados.saldo_sistema)}
                    </p>

                    <p>
                        <strong>Saldo informado:</strong>
                        ${moeda(dados.saldo_informado)}
                    </p>

                    <p>
                        <strong>${situacao}:</strong>
                        ${moeda(Math.abs(diferenca))}
                    </p>

                </div>
            `,
            confirmButtonText: "Entendi"
        });
    } finally {
        botao.prop("disabled", false).html(
            "Fechar Caixa"
        );
    }
}

async function visualizarCaixa(id) {
    await editarCaixa(id);

    $("#tituloModalCaixa").text("Visualizar Caixa");
    $("#formCaixa input").prop("disabled", true);
    $("#formCaixa select").prop("disabled", true);
    $("#formCaixa textarea").prop("disabled", true);
    $("#formCaixa button[type=submit]").hide();
}

function movimentoCaixa(id) {
    $("#formMovimento")[0].reset();
    $("#mov_caixa_id").val(id);
    $("#modalMovimento").modal("show");
}
function fecharCaixa(id) {

    $("#fechar_caixa_id").val(id);

    $("#saldo_informado").val("");

    $("#observacao_fechamento").val("");

    $("#modalFechamento").modal("show");

}

