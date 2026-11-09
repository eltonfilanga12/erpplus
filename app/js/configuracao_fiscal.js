const CONFIG_FISCAL_URL = "/erpplus/app/ajax/configuracao_fiscal_ajax.php";
let tabelaConfigFiscal;

$(document).ready(function () {
    // Eventos
    $("#btnNovaConfigFiscal").on("click", abrirNovaConfigFiscal);
    $("#formConfigFiscal").on("submit", guardarConfigFiscal);
    $("#modalConfigFiscal").on("hidden.bs.modal", limparFormularioConfigFiscal);

    // Eventos para cálculo automático da taxa do ISPC
    $("#previsao_faturacao_anual, #tipo_atividade").on("change input", calcularTaxaAutomatica);

    // Carregamentos iniciais
    carregarTabelaConfigFiscal();
    carregarFiliaisConfigFiscal();
    carregarStatusConfigFiscal();
});

// ==================================================
// DATATABLE
// ==================================================
function carregarTabelaConfigFiscal() {
    tabelaConfigFiscal = $("#configFiscalTable").DataTable({
        ajax: {
            url: CONFIG_FISCAL_URL,
            type: "POST",
            data: { acao: "listar" },
            dataSrc: function (json) {
                if (!json.success) {
                    configMensagemErro(json.mensagem || "Erro ao carregar dados.");
                    return [];
                }
                return json.data || [];
            },
            error: function () {
                configMensagemErro("Não foi possível carregar as configurações fiscais.");
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
            { 
                data: "regime_tributario",
                render: function (regime) {
                    return `<span class="badge badge-info">${regime}</span>`;
                }
            },
            { data: "tipo_atividade", defaultContent: "-" },
            { 
                data: "sujeito_passivo_iva",
                render: function (val) {
                    return Number(val) === 1 
                        ? '<span class="badge badge-success">Sim (Regime Geral)</span>' 
                        : '<span class="badge badge-secondary">Não (Isento ISPC)</span>';
                }
            },
            {
                data: "previsao_faturacao_anual",
                className: "text-right",
                render: function (valor) {
                    return Number(valor || 0).toLocaleString("pt-PT", { style: "currency", currency: "MZN" });
                }
            },
            { data: "exercicio_fiscal", className: "text-center" },
            {
                data: "status",
                render: function (status) {
                    const classes = { Activo: "success", Ativa: "success", Inactivo: "secondary", Suspenso: "danger" };
                    const classe = classes[status] || "secondary";
                    return `<span class="badge badge-${classe}">${status || "-"}</span>`;
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
                            <button type="button" class="btn btn-info" onclick="editarConfigFiscal(${id})" title="Editar">
                                <i class="fa fa-edit"></i>
                            </button>
                        </div>
                    `;
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
// CÁLCULO AUTOMÁTICO DE TAXA ISPC
// ==================================================
async function calcularTaxaAutomatica() {
    const tipoAtividade = $("#tipo_atividade").val();
    const faturacao = $("#previsao_faturacao_anual").val();
    const regime = $("#regime_tributario").val();

    if (regime !== "ISPC" || !tipoAtividade) {
        $("#taxa_imposto_preview").text("-");
        return;
    }

    try {
        const form = new FormData();
        form.append("acao", "calcular_taxa");
        form.append("tipo_atividade", tipoAtividade);
        form.append("previsao_faturacao_anual", faturacao);

        const resposta = await fetch(CONFIG_FISCAL_URL, { method: "POST", body: form });
        const json = await resposta.json();

        if (json.success) {
            const taxa = json.taxa || 0;
            $("#taxa_imposto_preview").text(taxa + "%");
        }
    } catch (erro) {
        console.error("Erro ao calcular taxa:", erro);
    }
}

// ==================================================
// MODAL E FORMULÁRIO
// ==================================================
function abrirNovaConfigFiscal() {
    limparFormularioConfigFiscal();
    $("#tituloModalConfigFiscal").text("Nova Configuração Fiscal");
    $("#modalConfigFiscal").modal("show");
}

function limparFormularioConfigFiscal() {
    const form = document.getElementById("formConfigFiscal");
    if (form) form.reset();
    $("#config_fiscal_id").val("");
    $("#taxa_imposto_preview").text("-");
}

async function guardarConfigFiscal(evento) {
    evento.preventDefault();
    const formulario = evento.target;
    const botao = $("#btnGuardarConfigFiscal");
    const id = $("#config_fiscal_id").val();

    const form = new FormData(formulario);
    form.append("acao", "guardar");

    try {
        botao.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> A guardar...');

        const resposta = await fetch(CONFIG_FISCAL_URL, { method: "POST", body: form });
        const json = await resposta.json();

        if (!resposta.ok || json.success === false) {
            throw new Error(json.mensagem || "Erro ao guardar configuração.");
        }

        $("#modalConfigFiscal").modal("hide");
        tabelaConfigFiscal.ajax.reload(null, false);
        
        Swal.fire({
            toast: true,
            position: "top-end",
            icon: "success",
            title: json.mensagem || "Salvo com sucesso!",
            showConfirmButton: false,
            timer: 3000
        });
    } catch (erro) {
        Swal.fire("Erro", erro.message, "error");
    } finally {
        botao.prop("disabled", false).html('<i class="fa fa-save"></i> Guardar');
    }
}

async function editarConfigFiscal(id) {
    try {
        const form = new FormData();
        form.append("acao", "buscar");
        form.append("id", id);

        const resposta = await fetch(CONFIG_FISCAL_URL, { method: "POST", body: form });
        const json = await resposta.json();

        if (!json.success) throw new Error(json.mensagem);

        const dados = json.data;
        $("#config_fiscal_id").val(dados.id);
        $("#config_filial_id").val(dados.filial_id);
        $("#regime_tributario").val(dados.regime_tributario);
        $("#tipo_atividade").val(dados.tipo_atividade);
        $("#sujeito_passivo_iva").val(dados.sujeito_passivo_iva);
        $("#numero_trabalhadores").val(dados.numero_trabalhadores);
        $("#previsao_faturacao_anual").val(dados.previsao_faturacao_anual);
        $("#exercicio_fiscal").val(dados.exercicio_fiscal);
        $("#config_status_id").val(dados.estado_id);

        calcularTaxaAutomatica();

        $("#tituloModalConfigFiscal").text("Editar Configuração Fiscal");
        $("#modalConfigFiscal").modal("show");
    } catch (erro) {
        Swal.fire("Erro", erro.message, "error");
    }
}

// Auxiliares de carregamento de selects
async function carregarFiliaisConfigFiscal() {
    try {
        const resposta = await fetch("/erpplus/app/ajax/banco_ajax.php?acao=filiais");
        const json = await resposta.json();
        const select = $("#config_filial_id");
        select.html('<option value="">Sede / Matriz (Sem Filial Especifica)</option>');
        (json.data || []).forEach(f => {
            select.append(`<option value="${f.id}">${f.nome}</option>`);
        });
    } catch (e) { console.error(e); }
}

async function carregarStatusConfigFiscal() {
    try {
        const resposta = await fetch(CONFIG_FISCAL_URL + "?acao=listar_status");
        const json = await resposta.json();
        const select = $("#config_status_id");
        select.html('<option value="">Selecione</option>');
        (json.data || []).forEach(s => {
            select.append(`<option value="${s.id}">${s.nome}</option>`);
        });
    } catch (e) { console.error(e); }
}

function pad(num, size) {
    var s = num + "";
    while (s.length < size) s = "0" + s;
    return s;
}

function configMensagemErro(msg) {
    Swal.fire("Erro", msg, "error");
}