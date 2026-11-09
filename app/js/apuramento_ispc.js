const APURAMENTO_ISPC_URL = "/erpplus/app/ajax/apuramento_ispc_ajax.php";
let tabelaApuramentosISPC;

$(document).ready(function () {
    // Eventos
    $("#btnNovoApuramentoISPC").on("click", abrirNovoApuramentoISPC);
    $("#formApuramentoISPC").on("submit", guardarApuramentoISPC);
    $("#modalApuramentoISPC").on("hidden.bs.modal", limparFormularioApuramentoISPC);

    // Carregamentos
    carregarTabelaApuramentosISPC();
    carregarFiliaisApuramentoISPC();
});

// ==================================================
// DATATABLE
// ==================================================
function carregarTabelaApuramentosISPC() {
    tabelaApuramentosISPC = $("#apuramentosISPCTable").DataTable({
        ajax: {
            url: APURAMENTO_ISPC_URL,
            type: "POST",
            data: { acao: "listar" },
            dataSrc: function (json) {
                if (!json.success) {
                    Swal.fire("Erro", json.mensagem || "Erro ao carregar apuramentos.", "error");
                    return [];
                }
                return json.data || [];
            },
            error: function () {
                Swal.fire("Erro", "Não foi possível carregar os apuramentos de ISPC.", "error");
            }
        },
        columns: [
            {
                data: null,
                searchable: false,
                orderable: false,
                render: function (data, type, row, meta) {
                    const indice = meta.row + 1 + meta.settings._iDisplayStart;
                    return pad(indice, 4);
                }
            },
            { data: "filial_nome", defaultContent: "Sede / Matriz" },
            { data: "exercicio_fiscal", className: "text-center" },
            {
                data: "volume_negocios",
                className: "text-right",
                render: function (valor) {
                    return Number(valor || 0).toLocaleString("pt-PT", { style: "currency", currency: "MZN" });
                }
            },
            {
                data: "taxa_aplicada",
                className: "text-center",
                render: function (taxa) {
                    return `<span class="badge badge-secondary">${taxa}%</span>`;
                }
            },
            {
                data: "imposto_apurado",
                className: "text-right font-weight-bold text-primary",
                render: function (valor) {
                    return Number(valor || 0).toLocaleString("pt-PT", { style: "currency", currency: "MZN" });
                }
            },
            {
                data: "status",
                render: function (status) {
                    const classes = { Pendente: "warning", Pago: "success", Parcial: "info" };
                    const classe = classes[status] || "secondary";
                    return `<span class="badge badge-${classe}">${status || "-"}</span>`;
                }
            }
        ],
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
        }
    });
}

// ==================================================
// MODAL E FORMULÁRIO
// ==================================================
function abrirNovoApuramentoISPC() {
    limparFormularioApuramentoISPC();
    $("#modalApuramentoISPC").modal("show");
}

function limparFormularioApuramentoISPC() {
    const form = document.getElementById("formApuramentoISPC");
    if (form) form.reset();
    $("#exercicio_fiscal").val(new Date().getFullYear());
}

async function guardarApuramentoISPC(evento) {
    evento.preventDefault();
    const formulario = evento.target;
    const botao = $("#btnGuardarApuramentoISPC");

    const form = new FormData(formulario);
    form.append("acao", "apurar");

    try {
        botao.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> A processar apuramento...');

        const resposta = await fetch(APURAMENTO_ISPC_URL, { method: "POST", body: form });
        const json = await resposta.json();

        if (!resposta.ok || json.success === false) {
            throw new Error(json.mensagem || "Erro ao efetuar o apuramento.");
        }

        $("#modalApuramentoISPC").modal("hide");
        tabelaApuramentosISPC.ajax.reload(null, false);

        Swal.fire({
            toast: true,
            position: "top-end",
            icon: "success",
            title: json.mensagem || "Apuramento efetuado com sucesso!",
            showConfirmButton: false,
            timer: 3000
        });
    } catch (erro) {
        Swal.fire("Erro", erro.message, "error");
    } finally {
        botao.prop("disabled", false).html('<i class="fa fa-calculator"></i> Calcular e Registar Apuramento');
    }
}

async function carregarFiliaisApuramentoISPC() {
    try {
        const resposta = await fetch("/erpplus/app/ajax/banco_ajax.php?acao=filiais");
        const json = await resposta.json();
        const select = $("#apuramento_filial_id");
        select.html('<option value="">Sede / Matriz (Todas as Filiais)</option>');
        (json.data || []).forEach(f => {
            select.append(`<option value="${f.id}">${f.nome}</option>`);
        });
    } catch (e) { console.error(e); }
}

function pad(num, size) {
    var s = num + "";
    while (s.length < size) s = "0" + s;
    return s;
}