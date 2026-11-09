"use strict";

const FLUXO_CAIXA_URL =
    "/erpplus/app/ajax/fluxo_caixa_ajax.php";

const FLUXO_FILIAL_URL =
    "/erpplus/app/ajax/filial_ajax.php";

let tabelaFluxoConsolidado = null;
let tabelaFluxoPrevisto = null;
let tabelaFluxoRealizado = null;
let tabelaSaldosCaixas = null;
let tabelaSaldosBancos = null;

let graficoFluxoCaixaDetalhado = null;


// ==================================================
// INICIALIZAÇÃO
// ==================================================

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | Verificar se estamos realmente na página Fluxo de Caixa
    |--------------------------------------------------------------------------
    */

    const tabelaFluxo =
        document.getElementById(
            "fluxoCaixaTable"
        );

    if (!tabelaFluxo) {

        return;
    }


    definirPeriodoPadrao();

    inicializarTabelas();

    carregarFiliais();

    carregarFluxoCompleto();


    $("#btnFiltrarFluxo").on(
        "click",
        carregarFluxoCompleto
    );


    $("#btnAtualizarFluxo").on(
        "click",
        carregarFluxoCompleto
    );


    $("#btnLimparFiltrosFluxo").on(
        "click",
        limparFiltrosFluxo
    );


    $("#btnExportarFluxo").on(
        "click",
        exportarFluxo
    );


    $(
        "#fluxo_tipo, "
        + "#fluxo_natureza, "
        + "#fluxo_origem, "
        + "#fluxo_filial_id"
    ).on(
        "change",
        function () {

            carregarFluxoCompleto();

        }
    );


    $(
        "#fluxo_data_inicio, "
        + "#fluxo_data_fim"
    ).on(
        "change",
        function () {

            validarPeriodoFluxo();

        }
    );

});


// ==================================================
// REQUISIÇÃO PADRÃO
// ==================================================

async function requisicaoFluxo(
    url,
    opcoes = {}
) {
    const configuracao = {
        credentials: "same-origin",
        ...opcoes
    };

    const resposta =
        await fetch(
            url,
            configuracao
        );

    const texto =
        await resposta.text();

    let json;

    try {

        json = JSON.parse(texto);

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
// CARREGAR TODO O FLUXO
// ==================================================

async function carregarFluxoCompleto() {

    if (!validarPeriodoFluxo()) {
        return;
    }

    const botao =
        $("#btnFiltrarFluxo");

    bloquearBotao(
        botao,
        "A consultar..."
    );

    mostrarCarregamentoFluxo();

    try {

        const filtros =
            obterFiltrosFluxo();

        const query =
            new URLSearchParams({
                acao: "dashboard",
                ...filtros
            });

        const resposta =
            await requisicaoFluxo(
                FLUXO_CAIXA_URL
                + "?"
                + query.toString()
            );

        preencherDashboard(
            resposta.data || {}
        );

        const resultados =
            await Promise.allSettled([
                carregarFluxoConsolidado(),
                carregarFluxoPrevisto(),
                carregarFluxoRealizado(),
                carregarSaldosCaixas(),
                carregarSaldosBancos()
            ]);

        resultados.forEach(
            function (
                resultado,
                indice
            ) {

                if (
                    resultado.status
                    === "rejected"
                ) {

                    console.error(
                        "Falha no carregamento da tabela "
                        + indice
                        + ":",
                        resultado.reason
                    );

                }

            }
        );

    } catch (erro) {

        console.error(
            "Erro no Fluxo de Caixa:",
            erro
        );

        mensagemErroFluxo(
            erro.message
        );

    } finally {

        restaurarBotao(
            botao,
            `
                <i class="fa fa-search"></i>
                Consultar
            `
        );

        esconderCarregamentoFluxo();

    }
}


// ==================================================
// DASHBOARD
// ==================================================

function preencherDashboard(data) {

    const disponibilidades =
        data.disponibilidades || {};

    const previsto =
        data.previsto || {};

    const realizado =
        data.realizado || {};

    preencherValor(
        "#saldo_total_caixas",
        disponibilidades
            .saldo_total_caixas
    );

    preencherValor(
        "#saldo_total_bancos",
        disponibilidades
            .saldo_total_bancos
    );

    preencherValor(
        "#saldo_total_disponivel",
        disponibilidades
            .saldo_total_disponivel
    );

    preencherValor(
        "#saldo_previsto_final",
        disponibilidades
            .saldo_previsto_final
    );


    preencherValor(
        "#total_entradas_previstas",
        previsto
            .entradas_previstas
    );

    preencherValor(
        "#total_saidas_previstas",
        previsto
            .saidas_previstas
    );

    preencherValor(
        "#resultado_previsto",
        previsto
            .resultado_previsto,
        true
    );

    preencherValor(
        "#total_contas_vencidas",
        previsto
            .total_contas_vencidas
    );

    preencherValor(
        "#total_receber_vencido",
        previsto
            .total_receber_vencido
    );

    preencherValor(
        "#total_pagar_vencido",
        previsto
            .total_pagar_vencido
    );


    preencherValor(
        "#total_entradas_realizadas",
        realizado
            .entradas_realizadas
    );

    preencherValor(
        "#total_saidas_realizadas",
        realizado
            .saidas_realizadas
    );

    preencherValor(
        "#resultado_realizado",
        realizado
            .resultado_realizado,
        true
    );


    atualizarGraficoFluxo(
        data.grafico || {}
    );
}


// ==================================================
// PREENCHER VALORES DOS CARDS
// ==================================================

function preencherValor(
    seletor,
    valor,
    destacarResultado = false
) {
    const elemento =
        $(seletor);

    const numero =
        Number(valor || 0);

    elemento.text(
        formatarMoedaFluxo(numero)
    );

    if (!destacarResultado) {
        return;
    }

    elemento.removeClass(
        "text-success text-danger text-muted"
    );

    if (numero > 0) {

        elemento.addClass(
            "text-success"
        );

    } else if (numero < 0) {

        elemento.addClass(
            "text-danger"
        );

    } else {

        elemento.addClass(
            "text-muted"
        );

    }
}


// ==================================================
// GRÁFICO
// ==================================================

function atualizarGraficoFluxo(
    dados
) {

    const canvas =
        document.getElementById(
            "graficoFluxoCaixaDetalhado"
        );

    if (!canvas) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Destruir qualquer Chart já associado ao canvas
    |--------------------------------------------------------------------------
    */

    if (
        typeof Chart !== "undefined"
        && typeof Chart.getChart === "function"
    ) {

        const graficoExistente =
            Chart.getChart(
                canvas
            );

        if (graficoExistente) {

            graficoExistente.destroy();

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Limpar referência local
    |--------------------------------------------------------------------------
    */

    if (graficoFluxoCaixaDetalhado) {

        try {

            graficoFluxoCaixaDetalhado.destroy();

        } catch (erro) {

            console.warn(
                "Não foi necessário destruir novamente o gráfico.",
                erro
            );

        }

        graficoFluxoCaixaDetalhado = null;
    }


    const labels =
        dados.labels || [];


    const saldos =
        (dados.saldos || [])
            .map(Number);


    const entradas =
        (dados.entradas || [])
            .map(Number);


    const saidas =
        (dados.saidas || [])
            .map(Number);


    graficoFluxoCaixaDetalhado =
        new Chart(
            canvas,
            {

                type:
                    "line",

                data: {

                    labels:
                        labels,

                    datasets: [

                        {
                            label:
                                "Saldo projetado",

                            data:
                                saldos,

                            borderWidth:
                                3,

                            tension:
                                0.25,

                            fill:
                                false,

                            pointRadius:
                                4,

                            pointHoverRadius:
                                6
                        },

                        {
                            label:
                                "Entradas",

                            data:
                                entradas,

                            borderWidth:
                                1,

                            borderDash:
                                [5, 5],

                            tension:
                                0.2,

                            fill:
                                false,

                            pointRadius:
                                2
                        },

                        {
                            label:
                                "Saídas",

                            data:
                                saidas,

                            borderWidth:
                                1,

                            borderDash:
                                [5, 5],

                            tension:
                                0.2,

                            fill:
                                false,

                            pointRadius:
                                2
                        }
                    ]
                },


                options: {

                    responsive:
                        true,

                    maintainAspectRatio:
                        false,

                    interaction: {

                        mode:
                            "index",

                        intersect:
                            false
                    },

                    plugins: {

                        legend: {

                            position:
                                "bottom"
                        },

                        tooltip: {

                            callbacks: {

                                label:
                                    function (
                                        contexto
                                    ) {

                                        return (
                                            contexto.dataset.label
                                            + ": "
                                            + formatarMoedaFluxo(
                                                contexto.parsed.y
                                            )
                                        );
                                    }
                            }
                        }
                    },

                    scales: {

                        y: {

                            beginAtZero:
                                false,

                            ticks: {

                                callback:
                                    function (
                                        valor
                                    ) {

                                        return formatarNumeroFluxo(
                                            valor
                                        );
                                    }
                            }
                        }
                    }
                }
            }
        );
}


// ==================================================
// FLUXO CONSOLIDADO
// ==================================================

async function carregarFluxoConsolidado() {

    const resposta =
        await consultarAcaoFluxo(
            "consolidado"
        );

    atualizarTabela(
        tabelaFluxoConsolidado,
        resposta.data || []
    );
}


// ==================================================
// FLUXO PREVISTO
// ==================================================

async function carregarFluxoPrevisto() {

    const resposta =
        await consultarAcaoFluxo(
            "previsto"
        );

    atualizarTabela(
        tabelaFluxoPrevisto,
        resposta.data || []
    );
}


// ==================================================
// FLUXO REALIZADO
// ==================================================

async function carregarFluxoRealizado() {

    const resposta =
        await consultarAcaoFluxo(
            "realizado"
        );

    atualizarTabela(
        tabelaFluxoRealizado,
        resposta.data || []
    );
}


// ==================================================
// SALDOS DOS CAIXAS
// ==================================================

async function carregarSaldosCaixas() {

    const resposta =
        await consultarAcaoFluxo(
            "saldos_caixas"
        );

    atualizarTabela(
        tabelaSaldosCaixas,
        resposta.data || []
    );
}


// ==================================================
// SALDOS DOS BANCOS
// ==================================================

async function carregarSaldosBancos() {

    const resposta =
        await consultarAcaoFluxo(
            "saldos_bancos"
        );

    atualizarTabela(
        tabelaSaldosBancos,
        resposta.data || []
    );
}


// ==================================================
// CONSULTAR UMA AÇÃO
// ==================================================

async function consultarAcaoFluxo(
    acao
) {
    const query =
        new URLSearchParams({
            acao: acao,
            ...obterFiltrosFluxo()
        });

    return requisicaoFluxo(
        FLUXO_CAIXA_URL
        + "?"
        + query.toString()
    );
}


// ==================================================
// INICIALIZAR DATATABLES
// ==================================================

function inicializarTabelas() {

    tabelaFluxoConsolidado =
        $("#fluxoCaixaTable")
            .DataTable({

                data: [],

                columns: [

                    {
                        data:
                            "data",

                        render:
                            formatarDataFluxo
                    },

                    {
                        data:
                            "situacao",

                        render:
                            renderSituacao
                    },

                    {
                        data:
                            "origem",

                        render:
                            formatarOrigemFluxo
                    },

                    {
                        data:
                            "documento",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "descricao",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "entrada",

                        className:
                            "text-right",

                        render: function (
                            valor
                        ) {

                            const numero =
                                Number(
                                    valor || 0
                                );

                            return numero > 0
                                ? `
                                    <span class="text-success">
                                        ${formatarMoedaFluxo(numero)}
                                    </span>
                                `
                                : "-";

                        }
                    },

                    {
                        data:
                            "saida",

                        className:
                            "text-right",

                        render: function (
                            valor
                        ) {

                            const numero =
                                Number(
                                    valor || 0
                                );

                            return numero > 0
                                ? `
                                    <span class="text-danger">
                                        ${formatarMoedaFluxo(numero)}
                                    </span>
                                `
                                : "-";

                        }
                    },

                    {
                        data:
                            "saldo_acumulado",

                        className:
                            "text-right",

                        render:
                            formatarMoedaFluxo
                    },

                    {
                        data:
                            "filial",

                        defaultContent:
                            "Sem filial"
                    }

                ],

                createdRow: function (
                    row,
                    data
                ) {

                    $(row)
                        .addClass(
                            "cursor-pointer"
                        )
                        .attr(
                            "title",
                            "Clique para ver detalhes"
                        )
                        .on(
                            "click",
                            function () {

                                abrirDetalhesFluxo(
                                    data
                                );

                            }
                        );

                },

                order: [
                    [0, "asc"]
                ],

                pageLength:
                    10,

                responsive:
                    true,

                autoWidth:
                    false,

                dom:
                    "<'row'<'col-md-8'Bl><'col-md-4'f>>"
                    + "<'row'<'col-12'tr>>"
                    + "<'row'<'col-md-5'i><'col-md-7'p>>",

                buttons:
                    criarBotoesExportacao(
                        "Fluxo de Caixa Consolidado"
                    ),

                language:
                    idiomaDataTableFluxo()

            });


    tabelaFluxoPrevisto =
        $("#fluxoPrevistoTable")
            .DataTable({

                data: [],

                columns: [

                    {
                        data:
                            "data",

                        render:
                            formatarDataFluxo
                    },

                    {
                        data:
                            "natureza",

                        render:
                            renderNatureza
                    },

                    {
                        data:
                            "origem",

                        render:
                            formatarOrigemFluxo
                    },

                    {
                        data:
                            "entidade",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "documento",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "descricao",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "valor",

                        className:
                            "text-right",

                        render:
                            formatarMoedaFluxo
                    },

                    {
                        data:
                            "status",

                        render:
                            renderStatusFluxo
                    },

                    {
                        data:
                            "filial",

                        defaultContent:
                            "Sem filial"
                    }

                ],

                createdRow: function (
                    row,
                    data
                ) {

                    $(row)
                        .addClass(
                            "cursor-pointer"
                        )
                        .on(
                            "click",
                            function () {

                                abrirDetalhesFluxo(
                                    data
                                );

                            }
                        );

                },

                order: [],

                pageLength:
                    10,

                responsive:
                    true,

                autoWidth:
                    false,

                buttons:
                    criarBotoesExportacao(
                        "Fluxo de Caixa Previsto"
                    ),

                language:
                    idiomaDataTableFluxo()

            });


    tabelaFluxoRealizado =
        $("#fluxoRealizadoTable")
            .DataTable({

                data: [],

                columns: [

                    {
                        data:
                            "data",

                        render:
                            formatarDataHoraFluxo
                    },

                    {
                        data:
                            "natureza",

                        render:
                            renderNatureza
                    },

                    {
                        data:
                            "origem",

                        render:
                            formatarOrigemFluxo
                    },

                    {
                        data:
                            "conta_origem",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "documento",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "descricao",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "valor",

                        className:
                            "text-right",

                        render: function (
                            valor,
                            tipo,
                            row
                        ) {

                            const numero =
                                Number(
                                    valor || 0
                                );

                            const classe =
                                row.natureza
                                    === "ENTRADA"
                                    ? "text-success"
                                    : "text-danger";

                            return `
                                <span class="${classe}">
                                    ${formatarMoedaFluxo(numero)}
                                </span>
                            `;

                        }
                    },

                    {
                        data:
                            "operador",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "filial",

                        defaultContent:
                            "Sem filial"
                    }

                ],

                createdRow: function (
                    row,
                    data
                ) {

                    $(row)
                        .addClass(
                            "cursor-pointer"
                        )
                        .on(
                            "click",
                            function () {

                                abrirDetalhesFluxo(
                                    data
                                );

                            }
                        );

                },

                order: [
                    [0, "desc"]
                ],

                pageLength:
                    10,

                responsive:
                    true,

                autoWidth:
                    false,

                buttons:
                    criarBotoesExportacao(
                        "Fluxo de Caixa Realizado"
                    ),

                language:
                    idiomaDataTableFluxo()

            });


    tabelaSaldosCaixas =
        $("#saldosCaixasTable")
            .DataTable({

                data: [],

                columns: [

                    {
                        data:
                            "caixa"
                    },

                    {
                        data:
                            "filial",

                        defaultContent:
                            "Sem filial"
                    },

                    {
                        data:
                            "status",

                        render:
                            renderStatusFluxo
                    },

                    {
                        data:
                            "saldo",

                        className:
                            "text-right",

                        render:
                            formatarMoedaFluxo
                    }

                ],

                paging:
                    false,

                searching:
                    false,

                info:
                    false,

                ordering:
                    true,

                responsive:
                    true,

                autoWidth:
                    false,

                language:
                    idiomaDataTableFluxo()

            });


    tabelaSaldosBancos =
        $("#saldosBancosTable")
            .DataTable({

                data: [],

                columns: [

                    {
                        data:
                            "banco"
                    },

                    {
                        data:
                            "conta",

                        defaultContent:
                            "-"
                    },

                    {
                        data:
                            "status",

                        render:
                            renderStatusFluxo
                    },

                    {
                        data:
                            "saldo",

                        className:
                            "text-right",

                        render: function (
                            valor,
                            tipo,
                            row
                        ) {

                            return formatarMoedaFluxo(
                                valor,
                                row.moeda || "MZN"
                            );

                        }
                    }

                ],

                paging:
                    false,

                searching:
                    false,

                info:
                    false,

                ordering:
                    true,

                responsive:
                    true,

                autoWidth:
                    false,

                language:
                    idiomaDataTableFluxo()

            });

}


// ==================================================
// ATUALIZAR DATATABLE
// ==================================================

function atualizarTabela(
    tabela,
    dados
) {
    if (!tabela) {
        return;
    }

    tabela
        .clear()
        .rows
        .add(
            Array.isArray(dados)
                ? dados
                : []
        )
        .draw();
}


// ==================================================
// DETALHES
// ==================================================

function abrirDetalhesFluxo(
    item
) {
    $("#detalhe_fluxo_data")
        .text(
            item.data
                ? formatarDataHoraFluxo(
                    item.data
                )
                : "-"
        );

    $("#detalhe_fluxo_situacao")
        .html(
            renderSituacao(
                item.situacao
            )
        );

    $("#detalhe_fluxo_natureza")
        .html(
            renderNatureza(
                item.natureza
            )
        );

    $("#detalhe_fluxo_origem")
        .text(
            formatarOrigemFluxo(
                item.origem
            )
        );

    $("#detalhe_fluxo_documento")
        .text(
            item.documento
            || "-"
        );

    $("#detalhe_fluxo_entidade")
        .text(
            item.entidade
            || item.conta_origem
            || "-"
        );

    $("#detalhe_fluxo_valor")
        .text(
            formatarMoedaFluxo(
                item.valor || 0
            )
        );

    $("#detalhe_fluxo_filial")
        .text(
            item.filial
            || "Sem filial"
        );

    $("#detalhe_fluxo_descricao")
        .text(
            item.descricao
            || "-"
        );

    $("#modalDetalhesFluxo")
        .modal("show");
}


// ==================================================
// FILIAIS
// ==================================================

async function carregarFiliais() {

    const select =
        document.getElementById(
            "fluxo_filial_id"
        );

    if (!select) {
        console.warn(
            "O select #fluxo_filial_id não existe na página."
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
                FLUXO_FILIAL_URL
                + "?acao=listar",
                {
                    method: "GET",
                    credentials: "same-origin",
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );

        const texto =
            await resposta.text();

        let json;

        try {
            json = JSON.parse(texto);
        } catch (erro) {

            console.error(
                "Resposta inválida das filiais:",
                texto
            );

            throw new Error(
                "O servidor não devolveu uma resposta JSON válida ao carregar as filiais."
            );
        }

        if (
            !resposta.ok
            || json.sucesso === false
            || json.success === false
        ) {
            throw new Error(
                json.mensagem
                || json.message
                || "Não foi possível carregar as filiais."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | O FilialController pode devolver "dados" ou "data"
        |--------------------------------------------------------------------------
        */

        const filiais =
            Array.isArray(json.dados)
                ? json.dados
                : Array.isArray(json.data)
                    ? json.data
                    : Array.isArray(json.filiais)
                        ? json.filiais
                        : [];

        select.innerHTML = `
            <option value="">
                Todas as filiais
            </option>
        `;

        filiais.forEach(
            function (filial) {

                const option =
                    document.createElement(
                        "option"
                    );

                option.value =
                    filial.id;

                option.textContent =
                    filial.nome
                    || filial.designacao
                    || filial.razao_social
                    || `Filial #${filial.id}`;

                select.appendChild(option);
            }
        );

        if (filiais.length === 0) {

            const option =
                document.createElement(
                    "option"
                );

            option.value = "";
            option.disabled = true;
            option.textContent =
                "Nenhuma filial encontrada";

            select.appendChild(option);
        }

    } catch (erro) {

        console.error(
            "Erro ao carregar filiais:",
            erro
        );

        select.innerHTML = `
            <option value="">
                Todas as filiais
            </option>
        `;

        if (typeof Swal !== "undefined") {

            Swal.fire({
                icon: "error",
                title: "Erro",
                text:
                    erro.message
                    || "Não foi possível carregar as filiais.",
                confirmButtonText: "OK"
            });
        }
    }
}


// ==================================================
// FILTROS
// ==================================================

function obterFiltrosFluxo() {

    return {

        data_inicio:
            $("#fluxo_data_inicio")
                .val()
            || "",

        data_fim:
            $("#fluxo_data_fim")
                .val()
            || "",

        tipo_fluxo:
            $("#fluxo_tipo")
                .val()
            || "TODOS",

        natureza:
            $("#fluxo_natureza")
                .val()
            || "",

        origem:
            $("#fluxo_origem")
                .val()
            || "",

        filial_id:
            $("#fluxo_filial_id")
                .val()
            || ""

    };
}


function limparFiltrosFluxo() {

    definirPeriodoPadrao();

    $("#fluxo_tipo")
        .val("TODOS");

    $("#fluxo_natureza")
        .val("");

    $("#fluxo_origem")
        .val("");

    $("#fluxo_filial_id")
        .val("");

    carregarFluxoCompleto();
}


function validarPeriodoFluxo() {

    const inicio =
        $("#fluxo_data_inicio")
            .val();

    const fim =
        $("#fluxo_data_fim")
            .val();

    if (
        inicio
        && fim
        && inicio > fim
    ) {

        mensagemAvisoFluxo(
            "A data inicial não pode ser superior à data final."
        );

        return false;

    }

    return true;
}


function definirPeriodoPadrao() {

    const hoje =
        new Date();

    const primeiroDia =
        new Date(
            hoje.getFullYear(),
            hoje.getMonth(),
            1
        );

    $("#fluxo_data_inicio")
        .val(
            formatarDataInput(
                primeiroDia
            )
        );

    $("#fluxo_data_fim")
        .val(
            formatarDataInput(
                hoje
            )
        );
}


// ==================================================
// EXPORTAÇÃO PRINCIPAL
// ==================================================

function exportarFluxo() {

    if (
        !tabelaFluxoConsolidado
    ) {
        return;
    }

    tabelaFluxoConsolidado
        .button(
            ".buttons-excel"
        )
        .trigger();
}


// ==================================================
// BOTÕES DATATABLE
// ==================================================

function criarBotoesExportacao(
    titulo
) {
    return [

        {
            extend:
                "excelHtml5",

            text:
                '<i class="fa fa-file-excel"></i> Excel',

            title:
                titulo,

            exportOptions: {
                columns:
                    ":visible"
            }
        },

        {
            extend:
                "pdfHtml5",

            text:
                '<i class="fa fa-file-pdf"></i> PDF',

            title:
                titulo,

            orientation:
                "landscape",

            pageSize:
                "A4",

            exportOptions: {
                columns:
                    ":visible"
            }
        },

        {
            extend:
                "print",

            text:
                '<i class="fa fa-print"></i> Imprimir',

            title:
                titulo,

            exportOptions: {
                columns:
                    ":visible"
            }
        }

    ];
}


// ==================================================
// BADGES
// ==================================================

function renderSituacao(
    situacao
) {
    const valor =
        String(
            situacao || ""
        ).toUpperCase();

    if (valor === "REALIZADO") {

        return `
            <span class="badge badge-success">
                Realizado
            </span>
        `;

    }

    if (valor === "PREVISTO") {

        return `
            <span class="badge badge-warning">
                Previsto
            </span>
        `;

    }

    return `
        <span class="badge badge-secondary">
            ${escaparHtml(valor || "-")}
        </span>
    `;
}


function renderNatureza(
    natureza
) {
    const valor =
        String(
            natureza || ""
        ).toUpperCase();

    if (valor === "ENTRADA") {

        return `
            <span class="badge badge-success">
                Entrada
            </span>
        `;

    }

    if (valor === "SAIDA") {

        return `
            <span class="badge badge-danger">
                Saída
            </span>
        `;

    }

    return `
        <span class="badge badge-secondary">
            ${escaparHtml(valor || "-")}
        </span>
    `;
}


function renderStatusFluxo(
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
        [
            "activo",
            "ativo",
            "pago",
            "recebido",
            "aberto"
        ].includes(normalizado)
    ) {

        classe =
            "success";

    } else if (
        [
            "pendente",
            "parcial"
        ].includes(normalizado)
    ) {

        classe =
            "warning";

    } else if (
        [
            "vencido",
            "bloqueado",
            "cancelado",
            "inactivo",
            "inativo",
            "fechado"
        ].includes(normalizado)
    ) {

        classe =
            "danger";

    }

    return `
        <span class="badge badge-${classe}">
            ${escaparHtml(texto || "-")}
        </span>
    `;
}


// ==================================================
// FORMATAR ORIGEM
// ==================================================

function formatarOrigemFluxo(
    origem
) {
    const origens = {

        CONTA_RECEBER:
            "Contas a Receber",

        CONTA_PAGAR:
            "Contas a Pagar",

        CAIXA:
            "Caixa",

        BANCO:
            "Banco"

    };

    return origens[origem]
        || origem
        || "-";
}


// ==================================================
// FORMATAÇÃO DE MOEDA
// ==================================================

function formatarMoedaFluxo(
    valor,
    moeda = "MZN"
) {

    /*
    |--------------------------------------------------------------------------
    | DATATABLES
    |--------------------------------------------------------------------------
    |
    | Quando a função é usada diretamente como render:
    |
    | render: formatarMoedaFluxo
    |
    | DataTables envia:
    |
    | valor, type, row, meta
    |
    | Portanto o segundo argumento pode chegar como:
    | display, sort, filter, type
    |
    */

    const tiposDataTable = [
        "display",
        "sort",
        "filter",
        "type"
    ];


    if (
        tiposDataTable.includes(
            String(moeda)
        )
    ) {
        moeda = "MZN";
    }


    const numero =
        Number(
            valor || 0
        );


    if (!Number.isFinite(numero)) {
        return "0,00 MT";
    }


    try {

        return numero.toLocaleString(
            "pt-MZ",
            {
                style:
                    "currency",

                currency:
                    moeda || "MZN",

                minimumFractionDigits:
                    2,

                maximumFractionDigits:
                    2
            }
        );

    } catch (erro) {

        return (
            numero.toLocaleString(
                "pt-MZ",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            )
            + " MT"
        );
    }
}


function formatarNumeroFluxo(
    valor
) {
    return Number(
        valor || 0
    ).toLocaleString(
        "pt-MZ",
        {
            maximumFractionDigits:
                0
        }
    );
}


// ==================================================
// FORMATAÇÃO DE DATAS
// ==================================================

function formatarDataFluxo(
    data
) {
    if (!data) {
        return "-";
    }

    const parte =
        String(data)
            .substring(
                0,
                10
            );

    const componentes =
        parte.split("-");

    if (
        componentes.length !== 3
    ) {
        return data;
    }

    return (
        componentes[2]
        + "/"
        + componentes[1]
        + "/"
        + componentes[0]
    );
}


function formatarDataHoraFluxo(
    data
) {
    if (!data) {
        return "-";
    }

    const texto =
        String(data);

    const apenasData =
        texto.substring(
            0,
            10
        );

    const dataFormatada =
        formatarDataFluxo(
            apenasData
        );

    const hora =
        texto.length >= 16
            ? texto.substring(
                11,
                16
            )
            : "";

    return hora
        ? dataFormatada
        + " "
        + hora
        : dataFormatada;
}


function formatarDataInput(
    data
) {
    const ano =
        data.getFullYear();

    const mes =
        String(
            data.getMonth() + 1
        ).padStart(
            2,
            "0"
        );

    const dia =
        String(
            data.getDate()
        ).padStart(
            2,
            "0"
        );

    return (
        ano
        + "-"
        + mes
        + "-"
        + dia
    );
}


// ==================================================
// DATATABLE LANGUAGE
// ==================================================

function idiomaDataTableFluxo() {

    return {
        url:
            "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
    };
}


// ==================================================
// MENSAGENS
// ==================================================

function mensagemErroFluxo(
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


function mensagemAvisoFluxo(
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


function toastFluxo(
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
// BOTÕES E CARREGAMENTO
// ==================================================

function bloquearBotao(
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


function restaurarBotao(
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


function mostrarCarregamentoFluxo() {

    $("#btnAtualizarFluxo")
        .prop(
            "disabled",
            true
        )
        .find("i")
        .addClass(
            "fa-spin"
        );
}


function esconderCarregamentoFluxo() {

    $("#btnAtualizarFluxo")
        .prop(
            "disabled",
            false
        )
        .find("i")
        .removeClass(
            "fa-spin"
        );
}


// ==================================================
// SEGURANÇA HTML
// ==================================================

function escaparHtml(
    valor
) {
    return $("<div>")
        .text(
            valor ?? ""
        )
        .html();
}