const ESCALAO_ISPC_URL = "/erpplus/app/ajax/escalao_ispc_ajax.php";
let tabelaEscaloesISPC;

$(document).ready(function () {
    carregarTabelaEscaloesISPC();
});

function carregarTabelaEscaloesISPC() {
    tabelaEscaloesISPC = $("#escaloesISPCTable").DataTable({
        ajax: {
            url: ESCALAO_ISPC_URL,
            type: "POST",
            data: { acao: "listar" },
            dataSrc: function (json) {
                if (!json.success) {
                    Swal.fire("Erro", json.mensagem || "Erro ao carregar escalões.", "error");
                    return [];
                }
                return json.data || [];
            },
            error: function () {
                Swal.fire("Erro", "Não foi possível carregar os escalões do ISPC.", "error");
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
            { data: "tipo_atividade", defaultContent: "-" },
            {
                data: "limite_inferior",
                className: "text-right",
                render: function (valor) {
                    return Number(valor || 0).toLocaleString("pt-PT", { style: "currency", currency: "MZN" });
                }
            },
            {
                data: "limite_superior",
                className: "text-right",
                render: function (valor) {
                    if (valor === null || valor === undefined || valor === "") {
                        return '<span class="badge badge-info">Ilimitado</span>';
                    }
                    return Number(valor).toLocaleString("pt-PT", { style: "currency", currency: "MZN" });
                }
            },
            {
                data: "taxa_percentual",
                className: "text-center font-weight-bold text-primary",
                render: function (taxa) {
                    return `${taxa}%`;
                }
            },
            { data: "descricao", defaultContent: "-" }
        ],
        pageLength: 10,
        responsive: true,
        autoWidth: false,
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
        }
    });
}

function pad(num, size) {
    var s = num + "";
    while (s.length < size) s = "0" + s;
    return s;
}