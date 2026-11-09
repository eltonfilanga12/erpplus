"use strict";

/*
|--------------------------------------------------------------------------
| Configuração
|--------------------------------------------------------------------------
*/

const PROCESSAMENTOS_SALARIAIS_URL =
    "/erpplus/app/ajax/processamentos_salariais_ajax.php";

const PERIODOS_SALARIAIS_URL =
    "/erpplus/app/ajax/periodos_salariais_ajax.php";

let processamentosSalariaisTable = null;
let periodosSalariais = [];

/*
|--------------------------------------------------------------------------
| Inicialização
|--------------------------------------------------------------------------
*/
$(document).ready(async function () {

    inicializarSelect2Processamentos();

    registarEventosProcessamentos();

    /*
     * Primeiro carregamos os períodos.
     */
    await carregarPeriodosProcessamentoSalarial();

    /*
     * Só depois inicializamos tabela e resumo.
     */
    inicializarTabelaProcessamentos();

    await carregarResumoProcessamentos();

});

/*
|--------------------------------------------------------------------------
| Select2
|--------------------------------------------------------------------------
*/

function inicializarSelect2Processamentos() {
    if (typeof $.fn.select2 !== "function") {
        return;
    }

    $("#filtroPeriodo").select2({
        width: "100%",
        placeholder: "Todos os períodos",
        allowClear: true
    });

    $("#periodoProcessamento").select2({
        width: "100%",
        placeholder: "Selecione o período",
        allowClear: true,
        dropdownParent: $("#modalProcessarPeriodo")
    });
}

/*
|--------------------------------------------------------------------------
| DataTable
|--------------------------------------------------------------------------
*/

function inicializarTabelaProcessamentos() {
    const tabela = $("#processamentosSalariaisTable");

    if (!tabela.length) {
        return;
    }

    if (
        $.fn.DataTable.isDataTable(
            "#processamentosSalariaisTable"
        )
    ) {
        tabela.DataTable().destroy();
    }

    processamentosSalariaisTable = tabela.DataTable({
        processing: true,
        serverSide: false,
        responsive: false,
        autoWidth: false,
        pageLength: 10,

        ajax: function (_data, callback) {

            const filtros =
                obterFiltrosProcessamentos();


            requisicaoProcessamentos({
                acao: "listar",
                ...filtros
            })
                .then(function (resposta) {


                    if (!resposta.success) {

                        throw new Error(
                            resposta.message ||
                            "Não foi possível carregar os processamentos."
                        );
                    }


                    let lista = [];


                    if (
                        Array.isArray(
                            resposta.data
                        )
                    ) {

                        lista =
                            resposta.data;

                    } else if (
                        Array.isArray(
                            resposta.dados
                        )
                    ) {

                        lista =
                            resposta.dados;
                    }



                    callback({
                        data: lista
                    });
                })
                .catch(function (erro) {

                    mostrarErroProcessamento(
                        erro.message ||
                        "Erro ao carregar os processamentos salariais."
                    );


                    /*
                     * DataTables exige callback,
                     * mas agora o erro ficará claramente
                     * visível no console.
                     */
                    callback({
                        data: []
                    });
                });
        },

        columns: [
            {
                data: "numero_funcionario",
                defaultContent: "-",
                render: function (data) {
                    return escaparHtmlProcessamento(
                        data || "-"
                    );
                }
            },
            {
                data: null,
                render: function (data) {
                    const nome =
                        escaparHtmlProcessamento(
                            data.funcionario_nome || "-"
                        );

                    const cargo =
                        escaparHtmlProcessamento(
                            data.cargo_nome || ""
                        );

                    return `
                        <div>
                            <strong>${nome}</strong>
                            ${cargo
                            ? `<br><small class="text-muted">${cargo}</small>`
                            : ""
                        }
                        </div>
                    `;
                }
            },
            {
                data: null,
                render: function (data) {
                    const codigo =
                        escaparHtmlProcessamento(
                            data.periodo_codigo || "-"
                        );

                    const inicio =
                        formatarDataProcessamento(
                            data.periodo_inicio
                        );

                    const fim =
                        formatarDataProcessamento(
                            data.periodo_fim
                        );

                    return `
                        <div>
                            <strong>${codigo}</strong>
                            <br>
                            <small class="text-muted">
                                ${inicio} - ${fim}
                            </small>
                        </div>
                    `;
                }
            },
            {
                data: "salario_base",
                className: "text-right",
                render: function (data, type, row) {
                    return renderizarValorMonetario(
                        data,
                        row.moeda,
                        type
                    );
                }
            },
            {
                data: "total_proventos",
                className: "text-right",
                render: function (data, type, row) {
                    return renderizarValorMonetario(
                        data,
                        row.moeda,
                        type,
                        "text-success"
                    );
                }
            },
            {
                data: "total_descontos",
                className: "text-right",
                render: function (data, type, row) {
                    return renderizarValorMonetario(
                        data,
                        row.moeda,
                        type,
                        "text-danger"
                    );
                }
            },
            {
                data: "salario_bruto",
                className: "text-right",
                render: function (data, type, row) {
                    return renderizarValorMonetario(
                        data,
                        row.moeda,
                        type
                    );
                }
            },
            {
                data: "salario_liquido",
                className: "text-right",
                render: function (data, type, row) {
                    return renderizarValorMonetario(
                        data,
                        row.moeda,
                        type,
                        "text-primary font-weight-bold"
                    );
                }
            },
            {
                data: "estado",
                className: "text-center",
                render: function (data) {
                    return criarBadgeEstadoProcessamento(
                        data
                    );
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className:
                    "text-center acoes-processamento",
                render: function (data) {
                    return criarBotoesProcessamento(
                        data
                    );
                }
            }
        ],

        order: [[2, "desc"], [1, "asc"]],

        language: {
            processing: "A carregar...",
            search: "Pesquisar:",
            lengthMenu: "Mostrar _MENU_ registos",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registos",
            infoEmpty: "Nenhum registo encontrado",
            infoFiltered: "(filtrado de _MAX_ registos)",
            zeroRecords: "Nenhum processamento encontrado",
            emptyTable: "Nenhum processamento disponível",
            paginate: {
                first: "Primeiro",
                previous: "Anterior",
                next: "Seguinte",
                last: "Último"
            }
        }
    });
}

/*
|--------------------------------------------------------------------------
| Eventos
|--------------------------------------------------------------------------
*/

function registarEventosProcessamentos() {
    $("#btnAbrirProcessamento").on(
        "click",
        abrirModalProcessamento
    );

    $("#formProcessarPeriodo").on(
        "submit",
        submeterProcessamentoPeriodo
    );

    $("#periodoProcessamento").on(
        "change",
        atualizarDetalhesPeriodoSelecionado
    );

    $("#filtroPeriodo, #filtroEstado").on(
        "change",
        aplicarFiltrosProcessamentos
    );

    let temporizadorPesquisa = null;

    $("#filtroPesquisa").on("input", function () {
        clearTimeout(temporizadorPesquisa);

        temporizadorPesquisa = setTimeout(
            aplicarFiltrosProcessamentos,
            400
        );
    });

    $("#btnLimparFiltros").on(
        "click",
        limparFiltrosProcessamentos
    );

    $(document).on(
        "click",
        ".btn-ver-processamento",
        function () {
            const id = Number(
                $(this).data("id")
            );

            abrirDetalhesProcessamento(id);
        }
    );

    $(document).on(
        "click",
        "#btnRecalcularPeriodo",
        recalcularPeriodoSalarial
    );

    $(document).on(
        "click",
        ".btn-aprovar-processamento",
        function () {
            const id = Number(
                $(this).data("id")
            );

            abrirModalAprovacao(id);
        }
    );

    $(document).on(
        "click",
        ".btn-cancelar-processamento",
        function () {
            const id = Number(
                $(this).data("id")
            );

            abrirModalCancelamento(id);
        }
    );

    $("#btnConfirmarAprovacao").on(
        "click",
        confirmarAprovacaoProcessamento
    );

    $("#formCancelarProcessamento").on(
        "submit",
        confirmarCancelamentoProcessamento
    );

    $("#modalProcessarPeriodo").on(
        "hidden.bs.modal",
        limparModalProcessamento
    );

    $("#modalCancelarProcessamento").on(
        "hidden.bs.modal",
        limparModalCancelamento
    );
}

/*
|--------------------------------------------------------------------------
| Períodos salariais
|--------------------------------------------------------------------------
*/

async function carregarPeriodosProcessamentoSalarial() {

    try {

        const resposta =
            await requisicaoPeriodosSalariais({
                acao: "listar"
            });


        if (!resposta.success) {

            throw new Error(
                resposta.message ||
                "Não foi possível carregar os períodos."
            );
        }


        const lista =
            Array.isArray(resposta.data)
                ? resposta.data
                : (
                    Array.isArray(resposta.dados)
                        ? resposta.dados
                        : []
                );


        periodosSalariais =
            lista;



        preencherSelectPeriodosProcessamentoSalarial();


        // ESTA LINHA ESTAVA A FALTAR
        return lista;

    } catch (erro) {

        console.error(
            "[PERÍODOS] erro:",
            erro
        );


        /*
         * Não apagar os períodos que
         * eventualmente já tenham sido carregados.
         */
        return Array.isArray(periodosSalariais)
            ? periodosSalariais
            : [];
    }
}

function obterEstadoPeriodo(
    periodo
) {

    if (!periodo) {
        return "";
    }


    const estado =
        periodo.status_nome
        || periodo.estado
        || periodo.status
        || periodo.estado_nome
        || "";


    return normalizarTextoProcessamento(
        estado
    );
}

function preencherSelectPeriodosProcessamentoSalarial() {

    const filtro =
        $("#filtroPeriodo");


    const processamento =
        $("#periodoProcessamento");


    filtro
        .empty()
        .append(
            '<option value="">Todos os períodos</option>'
        );


    processamento
        .empty()
        .append(
            '<option value="">Selecione o período</option>'
        );


    periodosSalariais.forEach(
        function (periodo) {

            const id =
                Number(
                    periodo.id || 0
                );


            if (id <= 0) {
                return;
            }


            const codigo =
                escaparHtmlProcessamento(
                    periodo.codigo || ""
                );


            const descricao =
                escaparHtmlProcessamento(
                    periodo.descricao || ""
                );


            const estado =
                obterEstadoPeriodo(
                    periodo
                );


            const texto =
                descricao
                    ? `${codigo} - ${descricao}`
                    : codigo;


            /*
             * Filtro geral:
             * mostra todos os períodos.
             */
            filtro.append(
                new Option(
                    texto,
                    id
                )
            );


            /*
             * Para PROCESSAR:
             *
             * Aceitamos:
             * - Aberto
             * - Em Processamento
             *
             * Também suportamos nomes antigos
             * caso existam no banco.
             */
            const podeProcessar =
                [
                    "aberto",
                    "em processamento",
                    "processamento",
                    "a processar"
                ].includes(
                    estado
                );


            if (podeProcessar) {

                processamento.append(
                    new Option(
                        texto,
                        id
                    )
                );
            }
        }
    );


    filtro.trigger(
        "change.select2"
    );


    processamento.trigger(
        "change.select2"
    );
}

async function recalcularPeriodoSalarial() {

    const periodoId =
        Number(
            $("#filtroPeriodo").val() || 0
        );

    if (periodoId <= 0) {

        mostrarAvisoProcessamento(
            "Selecione o período salarial que deseja recalcular."
        );

        return;
    }


    const confirmado =
        await confirmarOperacaoProcessamento({
            titulo: "Recalcular período?",
            texto:
                "Os processamentos actuais deste período "
                + "serão substituídos pelos novos cálculos.",
            confirmButtonText:
                "Sim, recalcular",
            icon:
                "warning"
        });


    if (!confirmado) {
        return;
    }


    try {

        const resposta =
            await requisicaoProcessamentos({
                acao:
                    "recalcular_periodo",

                periodo_id:
                    periodoId
            });


        if (!resposta.success) {

            throw new Error(
                resposta.message ||
                "Não foi possível recalcular o período."
            );
        }


        mostrarSucessoProcessamento(
            resposta.message ||
            "Período recalculado com sucesso."
        );


        /*
        |--------------------------------------------------------------------------
        | Atualizar página
        |--------------------------------------------------------------------------
        */

        recarregarTabelaProcessamentos();

        await carregarResumoProcessamentos();

        await carregarPeriodosProcessamentoSalarial();


    } catch (erro) {

        console.error(
            "Erro ao recalcular período:",
            erro
        );


        mostrarErroProcessamento(
            erro.message ||
            "Erro ao recalcular o período salarial."
        );
    }
}

function atualizarDetalhesPeriodoSelecionado() {
    const periodoId = Number(
        $("#periodoProcessamento").val()
    );

    const periodo = periodosSalariais.find(
        function (item) {
            return Number(item.id) === periodoId;
        }
    );

    if (!periodo) {
        $("#detalhesPeriodo").hide();
        return;
    }

    $("#detalhePeriodoInicio").text(
        formatarDataProcessamento(
            periodo.data_inicio
        )
    );

    $("#detalhePeriodoFim").text(
        formatarDataProcessamento(
            periodo.data_fim
        )
    );

    let diasCalendario = Number(
        periodo.dias_calendario || 0
    );

    /*
     * Caso o backend não devolva
     * dias_calendario, calculamos
     * através das datas.
     */
    if (
        diasCalendario <= 0 &&
        periodo.data_inicio &&
        periodo.data_fim
    ) {
        const inicio = new Date(
            periodo.data_inicio + "T00:00:00"
        );

        const fim = new Date(
            periodo.data_fim + "T00:00:00"
        );

        diasCalendario =
            Math.floor(
                (fim - inicio) /
                (1000 * 60 * 60 * 24)
            ) + 1;
    }

    $("#detalheDiasCalendario").text(
        diasCalendario
    );

    $("#detalheDiasUteis").text(
        periodo.dias_uteis || 0
    );

    $("#detalhesPeriodo").show();
}
/*
|--------------------------------------------------------------------------
| Processar período
|--------------------------------------------------------------------------
*/

async function abrirModalProcessamento() {

    limparModalProcessamento();


    const resultado =
        await carregarPeriodosProcessamentoSalarial();


    const lista =
        Array.isArray(resultado)
            ? resultado
            : [];


    const periodosDisponiveis =
        lista.filter(
            function (periodo) {

                const estado =
                    obterEstadoPeriodo(
                        periodo
                    );

                return (
                    estado === "aberto"
                    || estado === "em processamento"
                );
            }
        );


    if (!periodosDisponiveis.length) {

        mostrarAvisoProcessamento(
            "Não existe nenhum período salarial Aberto ou Em Processamento."
        );

        return;
    }

    preencherSelectPeriodosProcessamentoSalarial();


    if (
        periodosDisponiveis.length === 1
    ) {

        $("#periodoProcessamento")
            .val(
                String(
                    periodosDisponiveis[0].id
                )
            )
            .trigger(
                "change"
            );
    }


    $("#modalProcessarPeriodo")
        .modal("show");
}

async function submeterProcessamentoPeriodo(
    evento
) {
    evento.preventDefault();

    const periodoId =
        Number(
            $("#periodoProcessamento").val()
        );

    if (!periodoId) {
        $("#periodoProcessamento")
            .addClass("is-invalid");

        $("#erroPeriodoProcessamento").text(
            "Selecione um período salarial."
        );

        return;
    }

    const confirmado =
        await confirmarOperacaoProcessamento({
            titulo: "Processar período?",
            texto:
                "Todos os funcionários elegíveis serão processados.",
            confirmButtonText: "Sim, processar",
            icon: "question"
        });

    if (!confirmado) {
        return;
    }

    const botao =
        $("#btnConfirmarProcessamento");

    ativarCarregamentoBotao(
        botao,
        "A processar..."
    );

    try {
        const resposta =
            await requisicaoProcessamentos({
                acao: "processar_periodo",
                periodo_id: periodoId
            });

        if (!resposta.success) {
            throw new Error(
                resposta.message ||
                "Não foi possível processar o período."
            );
        }

        $("#modalProcessarPeriodo").modal(
            "hide"
        );

        mostrarSucessoProcessamento(
            resposta.message ||
            "Período processado com sucesso."
        );

        await carregarPeriodosProcessamentoSalarial();

        recarregarTabelaProcessamentos();
        carregarResumoProcessamentos();
    } catch (erro) {
        console.error(erro);

        mostrarErroProcessamento(
            erro.message ||
            "Erro ao processar o período salarial."
        );
    } finally {
        desativarCarregamentoBotao(
            botao,
            '<i class="fa fa-calculator"></i> Processar'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Detalhes
|--------------------------------------------------------------------------
*/

async function abrirDetalhesProcessamento(
    id
) {
    if (!id) {
        mostrarErroProcessamento(
            "Processamento inválido."
        );

        return;
    }

    limparDetalhesProcessamento();

    $("#modalDetalhesProcessamento").modal(
        "show"
    );

    try {
        const resposta =
            await requisicaoProcessamentos({
                acao: "buscar",
                id: id
            });

        if (!resposta.success) {
            throw new Error(
                resposta.message ||
                "Não foi possível carregar os detalhes."
            );
        }

        preencherDetalhesProcessamento(
            resposta.data
        );
    } catch (erro) {
        console.error(erro);

        $("#modalDetalhesProcessamento").modal(
            "hide"
        );

        mostrarErroProcessamento(
            erro.message ||
            "Erro ao carregar os detalhes."
        );
    }
}

function preencherDetalhesProcessamento(
    processamento
) {
    if (!processamento) {
        return;
    }

    const moeda =
        processamento.moeda || "MZN";

    $("#detalheProcessamentoId").val(
        processamento.id || ""
    );

    $("#detalheNumeroFuncionario").val(
        processamento.numero_funcionario || ""
    );

    $("#detalheFuncionarioNome").val(
        processamento.funcionario_nome || ""
    );

    $("#detalheCargo").val(
        processamento.cargo_nome || ""
    );

    $("#detalheDepartamento").val(
        processamento.departamento_nome || ""
    );

    $("#detalheFilial").val(
        processamento.filial_nome || ""
    );

    $("#detalheSalarioBase").text(
        formatarMoedaProcessamento(
            processamento.salario_base,
            moeda
        )
    );

    $("#detalheTotalProventos").text(
        formatarMoedaProcessamento(
            processamento.total_proventos,
            moeda
        )
    );

    $("#detalheTotalDescontos").text(
        formatarMoedaProcessamento(
            processamento.total_descontos,
            moeda
        )
    );

    $("#detalheSalarioLiquido").text(
        formatarMoedaProcessamento(
            processamento.salario_liquido,
            moeda
        )
    );

    preencherItensProcessamento(
        processamento.itens || [],
        moeda
    );
}

function preencherItensProcessamento(
    itens,
    moeda
) {
    const tbody =
        $("#tbodyItensProcessamento");

    tbody.empty();

    if (!Array.isArray(itens) || !itens.length) {
        tbody.html(`
            <tr>
                <td
                    colspan="8"
                    class="text-center text-muted">

                    Nenhum item encontrado.

                </td>
            </tr>
        `);

        return;
    }

    itens.forEach(function (item) {
        const tipo =
            normalizarTextoProcessamento(
                item.tipo
            );

        let classeTipo = "badge-secondary";

        if (tipo === "provento") {
            classeTipo = "badge-success";
        } else if (tipo === "desconto") {
            classeTipo = "badge-danger";
        } else if (tipo === "informativo") {
            classeTipo = "badge-info";
        }

        const quantidade =
            formatarNumeroProcessamento(
                item.quantidade,
                2
            );

        const valorUnitario =
            formatarMoedaProcessamento(
                item.valor_unitario,
                moeda
            );

        const percentagem =
            `${formatarNumeroProcessamento(
                item.percentagem,
                2
            )}%`;

        const total =
            formatarMoedaProcessamento(
                item.valor_total,
                moeda
            );

        tbody.append(`
            <tr>
                <td>
                    ${escaparHtmlProcessamento(
            item.codigo_rubrica || "-"
        )}
                </td>

                <td>
                    ${escaparHtmlProcessamento(
            item.nome_rubrica || "-"
        )}
                </td>

                <td>
                    <span class="badge ${classeTipo}">
                        ${escaparHtmlProcessamento(
            item.tipo || "-"
        )}
                    </span>
                </td>

                <td>
                    ${escaparHtmlProcessamento(
            item.origem || "-"
        )}
                </td>

                <td class="text-right">
                    ${quantidade}
                </td>

                <td class="text-right">
                    ${valorUnitario}
                </td>

                <td class="text-right">
                    ${percentagem}
                </td>

                <td class="text-right font-weight-bold">
                    ${total}
                </td>
            </tr>
        `);
    });
}

/*
|--------------------------------------------------------------------------
| Aprovação
|--------------------------------------------------------------------------
*/

function abrirModalAprovacao(id) {
    if (!id) {
        mostrarErroProcessamento(
            "Processamento inválido."
        );

        return;
    }

    $("#aprovarProcessamentoId").val(id);

    $("#modalAprovarProcessamento").modal(
        "show"
    );
}

async function confirmarAprovacaoProcessamento() {
    const id =
        Number(
            $("#aprovarProcessamentoId").val()
        );

    if (!id) {
        mostrarErroProcessamento(
            "Processamento inválido."
        );

        return;
    }

    const botao =
        $("#btnConfirmarAprovacao");

    ativarCarregamentoBotao(
        botao,
        "A aprovar..."
    );

    try {
        const resposta =
            await requisicaoProcessamentos({
                acao: "aprovar",
                id: id
            });

        if (!resposta.success) {
            throw new Error(
                resposta.message ||
                "Não foi possível aprovar o processamento."
            );
        }

        $("#modalAprovarProcessamento").modal(
            "hide"
        );

        mostrarSucessoProcessamento(
            resposta.message ||
            "Processamento aprovado."
        );

        recarregarTabelaProcessamentos();
        carregarResumoProcessamentos();
    } catch (erro) {
        console.error(erro);

        mostrarErroProcessamento(
            erro.message ||
            "Erro ao aprovar o processamento."
        );
    } finally {
        desativarCarregamentoBotao(
            botao,
            '<i class="fa fa-check"></i> Aprovar'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Cancelamento
|--------------------------------------------------------------------------
*/

function abrirModalCancelamento(id) {
    if (!id) {
        mostrarErroProcessamento(
            "Processamento inválido."
        );

        return;
    }

    limparModalCancelamento();

    $("#cancelarProcessamentoId").val(id);

    $("#modalCancelarProcessamento").modal(
        "show"
    );
}

async function confirmarCancelamentoProcessamento(
    evento
) {
    evento.preventDefault();

    const id =
        Number(
            $("#cancelarProcessamentoId").val()
        );

    const motivo =
        $("#motivoCancelamento")
            .val()
            .trim();

    if (!id) {
        mostrarErroProcessamento(
            "Processamento inválido."
        );

        return;
    }

    if (!motivo) {
        $("#motivoCancelamento")
            .addClass("is-invalid")
            .focus();

        return;
    }

    const botao =
        $("#btnConfirmarCancelamento");

    ativarCarregamentoBotao(
        botao,
        "A cancelar..."
    );

    try {
        const resposta =
            await requisicaoProcessamentos({
                acao: "cancelar",
                id: id,
                motivo: motivo
            });

        if (!resposta.success) {
            throw new Error(
                resposta.message ||
                "Não foi possível cancelar o processamento."
            );
        }

        $("#modalCancelarProcessamento").modal(
            "hide"
        );

        mostrarSucessoProcessamento(
            resposta.message ||
            "Processamento cancelado."
        );

        recarregarTabelaProcessamentos();
        carregarResumoProcessamentos();
    } catch (erro) {
        console.error(erro);

        mostrarErroProcessamento(
            erro.message ||
            "Erro ao cancelar o processamento."
        );
    } finally {
        desativarCarregamentoBotao(
            botao,
            '<i class="fa fa-ban"></i> Cancelar processamento'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Resumo
|--------------------------------------------------------------------------
*/

async function carregarResumoProcessamentos() {

    const periodoId =
        Number(
            $("#filtroPeriodo").val() || 0
        );


    try {

        const resposta =
            await requisicaoProcessamentos({
                acao: "resumo",

                periodo_id:
                    periodoId > 0
                        ? periodoId
                        : ""
            });

        if (!resposta.success) {

            throw new Error(
                resposta.message ||
                "Não foi possível carregar o resumo."
            );
        }


        const resumo =
            resposta.data ||
            resposta.dados ||
            {};

        preencherResumoProcessamentos(
            resumo
        );


        return resumo;

    } catch (erro) {

        console.error(
            "[RESUMO] erro:",
            erro
        );


        /*
         * NÃO limpar os cards aqui.
         *
         * Antes tinhas:
         *
         * preencherResumoProcessamentos({});
         *
         * Isso fazia os valores aparecerem
         * e logo depois voltarem a zero.
         */


        return null;
    }
}

function preencherResumoProcessamentos(
    resumo
) {
    $("#resumoQuantidade").text(
        Number(resumo.quantidade || 0)
    );

    $("#resumoBruto").text(
        formatarMoedaProcessamento(
            resumo.total_bruto,
            "MZN"
        )
    );

    $("#resumoDescontos").text(
        formatarMoedaProcessamento(
            resumo.total_descontos,
            "MZN"
        )
    );

    $("#resumoLiquido").text(
        formatarMoedaProcessamento(
            resumo.total_liquido,
            "MZN"
        )
    );
}

/*
|--------------------------------------------------------------------------
| Filtros
|--------------------------------------------------------------------------
*/

function obterFiltrosProcessamentos() {
    return {
        periodo_id:
            $("#filtroPeriodo").val() || "",

        estado:
            $("#filtroEstado").val() || "",

        pesquisa:
            $("#filtroPesquisa")
                .val()
                .trim()
    };
}

function aplicarFiltrosProcessamentos() {
    recarregarTabelaProcessamentos();
    carregarResumoProcessamentos();
}

function limparFiltrosProcessamentos() {
    $("#filtroPeriodo")
        .val("")
        .trigger("change.select2");

    $("#filtroEstado").val("");

    $("#filtroPesquisa").val("");

    aplicarFiltrosProcessamentos();
}

function recarregarTabelaProcessamentos() {
    if (processamentosSalariaisTable) {
        processamentosSalariaisTable.ajax.reload(
            null,
            false
        );
    }
}

/*
|--------------------------------------------------------------------------
| Botões e estados
|--------------------------------------------------------------------------
*/

function criarBotoesProcessamento(
    processamento
) {
    const id =
        Number(processamento.id);

    const estado =
        normalizarTextoProcessamento(
            processamento.estado
        );

    let botoes = `
        <button
            type="button"
            class="btn btn-sm btn-info btn-ver-processamento"
            data-id="${id}"
            title="Ver detalhes">

            <i class="fa fa-eye"></i>

        </button>
    `;

    if (estado === "calculado") {
        botoes += `
            <button
                type="button"
                class="btn btn-sm btn-success btn-aprovar-processamento"
                data-id="${id}"
                title="Aprovar">

                <i class="fa fa-check"></i>

            </button>
        `;
    }

    if (
        estado === "pendente" ||
        estado === "calculado" ||
        estado === "aprovado"
    ) {
        botoes += `
            <button
                type="button"
                class="btn btn-sm btn-danger btn-cancelar-processamento"
                data-id="${id}"
                title="Cancelar">

                <i class="fa fa-ban"></i>

            </button>
        `;
    }

    return `
        <div class="btn-group btn-group-sm">
            ${botoes}
        </div>
    `;
}

function criarBadgeEstadoProcessamento(
    estado
) {
    const texto =
        estado || "Desconhecido";

    const estadoNormalizado =
        normalizarTextoProcessamento(texto);

    const classes = {
        pendente:
            "processamento-pendente",

        calculado:
            "processamento-calculado",

        aprovado:
            "processamento-aprovado",

        pago:
            "processamento-pago",

        cancelado:
            "processamento-cancelado"
    };

    const classe =
        classes[estadoNormalizado] ||
        "processamento-desconhecido";

    return `
        <span class="badge-processamento ${classe}">
            ${escaparHtmlProcessamento(texto)}
        </span>
    `;
}

/*
|--------------------------------------------------------------------------
| Requisições
|--------------------------------------------------------------------------
*/

async function requisicaoProcessamentos(
    dados
) {
    return enviarFormularioAjax(
        PROCESSAMENTOS_SALARIAIS_URL,
        dados
    );
}

async function requisicaoPeriodosSalariais(
    dados
) {
    return enviarFormularioAjax(
        PERIODOS_SALARIAIS_URL,
        dados
    );
}

function obterTokenCsrf() {
    const meta = document.querySelector(
        'meta[name="csrf-token"]'
    );

    if (meta && meta.content) {
        return meta.content;
    }

    const input = document.querySelector(
        'input[name="csrf_token"]'
    );

    if (input && input.value) {
        return input.value;
    }

    return "";
}

async function enviarFormularioAjax(
    url,
    dados
) {
    const corpo = new URLSearchParams();

    const csrfToken = obterTokenCsrf();

    Object.entries(dados).forEach(
        function ([chave, valor]) {
            if (
                valor !== null &&
                valor !== undefined
            ) {
                corpo.append(
                    chave,
                    String(valor)
                );
            }
        }
    );

    /*
     * Envia o token no corpo da requisição.
     */
    if (csrfToken) {
        corpo.append(
            "csrf_token",
            csrfToken
        );
    }

    const resposta = await fetch(url, {
        method: "POST",

        headers: {
            "Content-Type":
                "application/x-www-form-urlencoded; charset=UTF-8",

            "X-Requested-With":
                "XMLHttpRequest",

            /*
             * Também envia no cabeçalho,
             * caso o Security.php valide por header.
             */
            "X-CSRF-TOKEN":
                csrfToken
        },

        credentials: "same-origin",

        body: corpo.toString()
    });

    const texto = await resposta.text();

    let json = null;

    try {
        json = JSON.parse(texto);
    } catch (erro) {
        console.error(
            "Resposta não JSON:",
            texto
        );

        throw new Error(
            texto ||
            "O servidor devolveu uma resposta inválida."
        );
    }

    /*
     * Normaliza o padrão do backend:
     *
     * sucesso -> success
     * mensagem -> message
     */
    const resultado = {
        ...json,

        success:
            json.success ??
            json.sucesso ??
            false,

        message:
            json.message ??
            json.mensagem ??
            ""
    };

    if (
        resultado.csrf_invalido === true
    ) {
        throw new Error(
            resultado.message ||
            "A validação de segurança falhou. Atualize a página."
        );
    }

    if (!resposta.ok) {
        console.error(
            "Erro HTTP:",
            resposta.status
        );

        console.error(
            "Resposta do servidor:",
            texto
        );

        throw new Error(
            resultado.message ||
            `Erro HTTP ${resposta.status}.`
        );
    }

    return resultado;
}
/*
|--------------------------------------------------------------------------
| Formatação
|--------------------------------------------------------------------------
*/

function renderizarValorMonetario(
    valor,
    moeda,
    tipo,
    classe = ""
) {
    const numero =
        converterNumeroProcessamento(valor);

    if (
        tipo === "sort" ||
        tipo === "type"
    ) {
        return numero;
    }

    const formatado =
        formatarMoedaProcessamento(
            numero,
            moeda || "MZN"
        );

    if (!classe) {
        return formatado;
    }

    return `
        <span class="${classe}">
            ${formatado}
        </span>
    `;
}

function formatarMoedaProcessamento(
    valor,
    moeda = "MZN"
) {
    const numero =
        converterNumeroProcessamento(valor);

    try {
        return new Intl.NumberFormat(
            "pt-PT",
            {
                style: "currency",
                currency:
                    String(moeda).toUpperCase(),
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(numero);
    } catch (_erro) {
        return (
            formatarNumeroProcessamento(
                numero,
                2
            ) +
            " " +
            String(moeda).toUpperCase()
        );
    }
}

function formatarNumeroProcessamento(
    valor,
    casas = 2
) {
    return converterNumeroProcessamento(
        valor
    ).toLocaleString(
        "pt-PT",
        {
            minimumFractionDigits: casas,
            maximumFractionDigits: casas
        }
    );
}

function converterNumeroProcessamento(
    valor
) {
    if (
        valor === null ||
        valor === undefined ||
        valor === ""
    ) {
        return 0;
    }

    if (typeof valor === "number") {
        return Number.isFinite(valor)
            ? valor
            : 0;
    }

    const numero =
        Number(
            String(valor)
                .replace(/\s/g, "")
                .replace(",", ".")
        );

    return Number.isFinite(numero)
        ? numero
        : 0;
}

function formatarDataProcessamento(
    data
) {
    if (!data) {
        return "-";
    }

    const partes =
        String(data)
            .substring(0, 10)
            .split("-");

    if (partes.length !== 3) {
        return data;
    }

    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function normalizarTextoProcessamento(
    texto
) {
    return String(texto || "")
        .normalize("NFD")
        .replace(
            /[\u0300-\u036f]/g,
            ""
        )
        .trim()
        .toLowerCase();
}

function escaparHtmlProcessamento(
    valor
) {
    return $("<div>")
        .text(
            valor === null ||
                valor === undefined
                ? ""
                : String(valor)
        )
        .html();
}

/*
|--------------------------------------------------------------------------
| Limpeza dos modais
|--------------------------------------------------------------------------
*/

function limparModalProcessamento() {
    const formulario =
        document.getElementById(
            "formProcessarPeriodo"
        );

    if (formulario) {
        formulario.reset();
    }

    $("#periodoProcessamento")
        .val("")
        .trigger("change");

    $("#periodoProcessamento")
        .removeClass("is-invalid");

    $("#erroPeriodoProcessamento").text("");

    $("#detalhesPeriodo").hide();
}

function limparDetalhesProcessamento() {
    $("#detalheProcessamentoId").val("");
    $("#detalheNumeroFuncionario").val("");
    $("#detalheFuncionarioNome").val("");
    $("#detalheCargo").val("");
    $("#detalheDepartamento").val("");
    $("#detalheFilial").val("");

    $("#detalheSalarioBase").text(
        "0,00 MZN"
    );

    $("#detalheTotalProventos").text(
        "0,00 MZN"
    );

    $("#detalheTotalDescontos").text(
        "0,00 MZN"
    );

    $("#detalheSalarioLiquido").text(
        "0,00 MZN"
    );

    $("#tbodyItensProcessamento").html(`
        <tr>
            <td
                colspan="8"
                class="text-center text-muted">

                A carregar...

            </td>
        </tr>
    `);
}

function limparModalCancelamento() {
    const formulario =
        document.getElementById(
            "formCancelarProcessamento"
        );

    if (formulario) {
        formulario.reset();
    }

    $("#cancelarProcessamentoId").val("");

    $("#motivoCancelamento")
        .removeClass("is-invalid");
}

/*
|--------------------------------------------------------------------------
| Botões com carregamento
|--------------------------------------------------------------------------
*/

function ativarCarregamentoBotao(
    botao,
    texto
) {
    botao
        .prop("disabled", true)
        .data(
            "conteudo-original",
            botao.html()
        )
        .html(`
            <span
                class="spinner-border spinner-border-sm"
                role="status"
                aria-hidden="true">
            </span>

            ${texto}
        `);
}

function desativarCarregamentoBotao(
    botao,
    conteudoPadrao = ""
) {
    const original =
        botao.data("conteudo-original");

    botao
        .prop("disabled", false)
        .html(
            original ||
            conteudoPadrao
        );

    botao.removeData(
        "conteudo-original"
    );
}

/*
|--------------------------------------------------------------------------
| Alertas
|--------------------------------------------------------------------------
*/

function mostrarSucessoProcessamento(
    mensagem
) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            icon: "success",
            title: "Sucesso",
            text: mensagem,
            confirmButtonText: "OK"
        });

        return;
    }

    alert(mensagem);
}

function mostrarErroProcessamento(
    mensagem
) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            icon: "error",
            title: "Erro",
            text: mensagem,
            confirmButtonText: "OK"
        });

        return;
    }

    alert(mensagem);
}

function mostrarAvisoProcessamento(
    mensagem
) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            icon: "warning",
            title: "Atenção",
            text: mensagem,
            confirmButtonText: "OK"
        });

        return;
    }

    alert(mensagem);
}

async function confirmarOperacaoProcessamento(
    opcoes
) {
    if (typeof Swal !== "undefined") {
        const resultado =
            await Swal.fire({
                title:
                    opcoes.titulo ||
                    "Confirmar operação?",

                text:
                    opcoes.texto || "",

                icon:
                    opcoes.icon ||
                    "question",

                showCancelButton: true,

                confirmButtonText:
                    opcoes.confirmButtonText ||
                    "Confirmar",

                cancelButtonText:
                    "Cancelar",

                reverseButtons: true
            });

        return resultado.isConfirmed;
    }

    return window.confirm(
        opcoes.texto ||
        opcoes.titulo ||
        "Confirmar operação?"
    );
}