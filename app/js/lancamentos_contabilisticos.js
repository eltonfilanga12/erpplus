"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const LANCAMENTO_URL =
        "/erpplus/app/ajax/lancamento_contabilistico_ajax.php";

    let tabelaLancamentos = null;

    let lancamentos = [];

    let exercicios = [];

    let periodos = [];

    let diarios = [];

    let contas = [];

    let statusLancamentos = [];

    let centrosCusto = [];

    let clientes = [];

    let fornecedores = [];

    let itensLancamento = [];

    let indiceItemEmEdicao = null;


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(async function () {

        configurarEventosLancamento();

        limparResumoLancamentos();

        await carregarDadosIniciais();

        iniciarTabelaLancamentos();

    });


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosLancamento() {

        $("#btnNovoLancamento").on(
            "click",
            abrirNovoLancamento
        );


        $("#formLancamentoContabilistico").on(
            "submit",
            guardarLancamento
        );


        $("#btnAdicionarItemLancamento").on(
            "click",
            adicionarOuAtualizarItem
        );


        $("#itemDebito").on(
            "input",
            function () {

                const valor =
                    numeroLancamento(
                        $(this).val()
                    );

                if (valor > 0) {
                    $("#itemCredito").val(
                        "0.00"
                    );
                }

            }
        );


        $("#itemCredito").on(
            "input",
            function () {

                const valor =
                    numeroLancamento(
                        $(this).val()
                    );

                if (valor > 0) {
                    $("#itemDebito").val(
                        "0.00"
                    );
                }

            }
        );


        $("#itemTipoEntidade").on(
            "change",
            atualizarCamposEntidade
        );


        $("#lancamentoExercicioId").on(
            "change",
            async function () {

                await carregarPeriodos(
                    Number(
                        $(this).val() || 0
                    ),
                    null,
                    false
                );

            }
        );


        $("#filtroExercicioLancamento").on(
            "change",
            async function () {

                await carregarPeriodos(
                    Number(
                        $(this).val() || 0
                    ),
                    null,
                    true
                );

                recarregarTabelaLancamentos();

            }
        );


        $([
            "#filtroPeriodoLancamento",
            "#filtroDiarioLancamento",
            "#filtroStatusLancamento",
            "#filtroOrigemLancamento",
            "#filtroDataInicioLancamento",
            "#filtroDataFimLancamento"
        ].join(",")).on(
            "change",
            recarregarTabelaLancamentos
        );


        $("#btnLimparFiltrosLancamento").on(
            "click",
            limparFiltrosLancamentos
        );


        $(document).on(
            "click",
            ".btn-detalhes-lancamento",
            function () {

                visualizarLancamento(
                    Number(
                        $(this).data("id") || 0
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-editar-lancamento",
            function () {

                editarLancamento(
                    Number(
                        $(this).data("id") || 0
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-contabilizar-lancamento",
            function () {

                abrirContabilizacao(
                    Number(
                        $(this).data("id") || 0
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-anular-lancamento",
            function () {

                abrirAnulacao(
                    Number(
                        $(this).data("id") || 0
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-eliminar-lancamento",
            function () {

                abrirEliminacao(
                    Number(
                        $(this).data("id") || 0
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-editar-item-lancamento",
            function () {

                editarItemLancamento(
                    Number(
                        $(this).data("indice")
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-remover-item-lancamento",
            function () {

                removerItemLancamento(
                    Number(
                        $(this).data("indice")
                    )
                );

            }
        );


        $(document).on(
            "click",
            ".btn-subir-item-lancamento",
            function () {

                moverItemLancamento(
                    Number(
                        $(this).data("indice")
                    ),
                    -1
                );

            }
        );


        $(document).on(
            "click",
            ".btn-descer-item-lancamento",
            function () {

                moverItemLancamento(
                    Number(
                        $(this).data("indice")
                    ),
                    1
                );

            }
        );


        $("#formContabilizarLancamento").on(
            "submit",
            contabilizarLancamento
        );


        $("#formAnularLancamento").on(
            "submit",
            anularLancamento
        );


        $("#formEliminarLancamento").on(
            "submit",
            eliminarLancamento
        );


        $("#modalLancamentoContabilistico").on(
            "hidden.bs.modal",
            limparFormularioLancamento
        );


        $("#modalAnularLancamento").on(
            "hidden.bs.modal",
            function () {

                $("#formAnularLancamento")[0]
                    ?.reset();

                $("#anularLancamentoId").val("");

            }
        );


        $("#modalContabilizarLancamento").on(
            "hidden.bs.modal",
            function () {

                $("#contabilizarLancamentoId")
                    .val("");

            }
        );


        $("#modalEliminarLancamento").on(
            "hidden.bs.modal",
            function () {

                $("#eliminarLancamentoId")
                    .val("");

            }
        );

    }


    // ==================================================
    // CARREGAMENTO INICIAL
    // ==================================================

    async function carregarDadosIniciais() {

        try {

            const respostas =
                await Promise.all([

                    requisicaoLancamento(
                        LANCAMENTO_URL
                        + "?acao=exercicios"
                    ),

                    requisicaoLancamento(
                        LANCAMENTO_URL
                        + "?acao=diarios"
                    ),

                    requisicaoLancamento(
                        LANCAMENTO_URL
                        + "?acao=contas"
                    ),

                    requisicaoLancamento(
                        LANCAMENTO_URL
                        + "?acao=status"
                    ),

                    requisicaoLancamento(
                        LANCAMENTO_URL
                        + "?acao=centros_custo"
                    ),

                    requisicaoLancamento(
                        LANCAMENTO_URL
                        + "?acao=clientes"
                    ),

                    requisicaoLancamento(
                        LANCAMENTO_URL
                        + "?acao=fornecedores"
                    )

                ]);


            exercicios =
                respostas[0].data || [];

            diarios =
                respostas[1].data || [];

            contas =
                respostas[2].data || [];

            statusLancamentos =
                respostas[3].data || [];

            centrosCusto =
                respostas[4].data || [];

            clientes =
                respostas[5].data || [];

            fornecedores =
                respostas[6].data || [];


            preencherExercicios();

            preencherDiarios();

            preencherContas();

            preencherStatusLancamentos();

            preencherCentrosCusto();

            preencherClientes();

            preencherFornecedores();

        } catch (erro) {

            mostrarErroLancamento(
                erro.message
            );

        }

    }


    // ==================================================
    // REQUISIÇÕES
    // ==================================================

    async function requisicaoLancamento(
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
                JSON.parse(texto);

        } catch (erro) {

            console.error(
                "Resposta inválida:",
                texto
            );

            throw new Error(
                "O servidor não retornou uma resposta JSON válida."
            );

        }


        if (resposta.status === 401) {

            await Swal.fire({

                icon:
                    "warning",

                title:
                    "Sessão terminada",

                text:
                    json.mensagem
                    || "Faça login novamente.",

                confirmButtonText:
                    "Ir para o login"

            });

            window.location.href =
                "/erpplus/index.php?page=login";

            throw new Error(
                "Sessão expirada."
            );

        }


        if (
            !resposta.ok
            || json.success === false
        ) {

            if (json.erro_tecnico) {
                console.error(
                    json.erro_tecnico
                );
            }

            throw new Error(
                json.mensagem
                || "Não foi possível concluir a operação."
            );

        }


        return json;
    }


    // ==================================================
    // PREENCHER EXERCÍCIOS
    // ==================================================

    function preencherExercicios() {

        const selects = [
            $("#filtroExercicioLancamento"),
            $("#lancamentoExercicioId")
        ];


        selects.forEach(function (select) {

            const filtro =
                select.attr("id")
                === "filtroExercicioLancamento";


            select.html(`
                <option value="">
                    ${filtro
                    ? "Todos"
                    : "Seleccione"
                }
                </option>
            `);


            exercicios.forEach(
                function (exercicio) {

                    select.append(`
                        <option value="${exercicio.id}">
                            ${escaparHtmlLancamento(
                        exercicio.codigo
                        + " — "
                        + exercicio.descricao
                    )
                        }
                        </option>
                    `);

                }
            );

        });

    }


    // ==================================================
    // PERÍODOS
    // ==================================================

    async function carregarPeriodos(
        exercicioId,
        periodoSelecionado = null,
        filtro = false
    ) {

        const select =
            filtro
                ? $("#filtroPeriodoLancamento")
                : $("#lancamentoPeriodoId");


        select.html(`
            <option value="">
                ${filtro
                ? "Todos"
                : "Seleccione"
            }
            </option>
        `);


        if (exercicioId <= 0) {

            if (!filtro) {
                periodos = [];
            }

            return;
        }


        try {

            const resposta =
                await requisicaoLancamento(
                    LANCAMENTO_URL
                    + "?acao=periodos&exercicio_id="
                    + encodeURIComponent(
                        exercicioId
                    )
                );


            const lista =
                resposta.data || [];


            if (!filtro) {
                periodos = lista;
            }


            lista.forEach(function (periodo) {

                const status =
                    periodo.status_nome
                        ? " — "
                        + periodo.status_nome
                        : "";

                select.append(`
                    <option
                        value="${periodo.id}"
                        data-inicio="${periodo.data_inicio}"
                        data-fim="${periodo.data_fim}"
                        data-status="${escaparHtmlLancamento(
                    periodo.status_nome || ""
                )}">

                        ${escaparHtmlLancamento(
                    String(
                        periodo.numero
                    ).padStart(2, "0")
                    + " — "
                    + periodo.nome
                    + status
                )
                    }

                    </option>
                `);

            });


            if (periodoSelecionado) {

                select.val(
                    String(
                        periodoSelecionado
                    )
                );

            }

        } catch (erro) {

            mostrarErroLancamento(
                erro.message
            );

        }

    }


    // ==================================================
    // PREENCHER DIÁRIOS
    // ==================================================

    function preencherDiarios() {

        const selects = [
            $("#filtroDiarioLancamento"),
            $("#lancamentoDiarioId")
        ];


        selects.forEach(function (select) {

            const filtro =
                select.attr("id")
                === "filtroDiarioLancamento";


            select.html(`
                <option value="">
                    ${filtro
                    ? "Todos"
                    : "Seleccione"
                }
                </option>
            `);


            diarios.forEach(function (diario) {

                select.append(`
                    <option value="${diario.id}">
                        ${escaparHtmlLancamento(
                    diario.codigo
                    + " — "
                    + diario.nome
                )
                    }
                    </option>
                `);

            });

        });

    }


    // ==================================================
    // CONTAS
    // ==================================================

    function preencherContas() {

        const select =
            $("#itemContaContabilId");


        select.html(`
            <option value="">
                Seleccione a conta
            </option>
        `);


        contas.forEach(function (conta) {

            select.append(`
                <option
                    value="${conta.id}"
                    data-centro-custo="${Number(
                conta.exige_centro_custo
                || 0
            )
                }"
                    data-entidade="${Number(
                    conta.exige_entidade
                    || 0
                )
                }">

                    ${escaparHtmlLancamento(
                    conta.codigo
                    + " — "
                    + conta.nome
                )
                }

                </option>
            `);

        });

    }


    // ==================================================
    // STATUS
    // ==================================================

    function preencherStatusLancamentos() {

        const select =
            $("#filtroStatusLancamento");


        select.html(`
            <option value="">
                Todos
            </option>
        `);


        statusLancamentos.forEach(
            function (status) {

                select.append(`
                    <option value="${status.id}">
                        ${escaparHtmlLancamento(
                    status.nome
                )
                    }
                    </option>
                `);

            }
        );

    }


    // ==================================================
    // CENTROS DE CUSTO
    // ==================================================

    function preencherCentrosCusto() {

        const select =
            $("#itemCentroCustoId");


        select.html(`
            <option value="">
                Nenhum
            </option>
        `);


        centrosCusto.forEach(
            function (centro) {

                select.append(`
                    <option value="${centro.id}">
                        ${escaparHtmlLancamento(
                    centro.codigo
                    + " — "
                    + centro.nome
                )
                    }
                    </option>
                `);

            }
        );

    }


    // ==================================================
    // CLIENTES
    // ==================================================

    function preencherClientes() {

        const select =
            $("#itemClienteId");


        select.html(`
            <option value="">
                Seleccione
            </option>
        `);


        clientes.forEach(function (cliente) {

            select.append(`
                <option value="${cliente.id}">
                    ${escaparHtmlLancamento(
                cliente.nome
            )
                }
                </option>
            `);

        });

    }


    // ==================================================
    // FORNECEDORES
    // ==================================================

    function preencherFornecedores() {

        const select =
            $("#itemFornecedorId");


        select.html(`
            <option value="">
                Seleccione
            </option>
        `);


        fornecedores.forEach(
            function (fornecedor) {

                select.append(`
                    <option value="${fornecedor.id}">
                        ${escaparHtmlLancamento(
                    fornecedor.nome
                )
                    }
                    </option>
                `);

            }
        );

    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function iniciarTabelaLancamentos() {

        tabelaLancamentos =
            $("#lancamentosContabilisticosTable")
                .DataTable({

                    ajax: {

                        url:
                            LANCAMENTO_URL,

                        data:
                            function (dados) {

                                dados.acao =
                                    "listar";

                                dados.exercicio_id =
                                    $("#filtroExercicioLancamento")
                                        .val()
                                    || "";

                                dados.periodo_id =
                                    $("#filtroPeriodoLancamento")
                                        .val()
                                    || "";

                                dados.diario_id =
                                    $("#filtroDiarioLancamento")
                                        .val()
                                    || "";

                                dados.status_id =
                                    $("#filtroStatusLancamento")
                                        .val()
                                    || "";

                                dados.origem =
                                    $("#filtroOrigemLancamento")
                                        .val()
                                    || "";

                                dados.data_inicio =
                                    $("#filtroDataInicioLancamento")
                                        .val()
                                    || "";

                                dados.data_fim =
                                    $("#filtroDataFimLancamento")
                                        .val()
                                    || "";

                            },

                        dataSrc:
                            function (resposta) {

                                lancamentos =
                                    resposta.data || [];

                                atualizarResumoLancamentos();

                                return lancamentos;

                            },

                        error:
                            function (xhr) {

                                let mensagem =
                                    "Não foi possível carregar os lançamentos.";

                                try {

                                    const resposta =
                                        JSON.parse(
                                            xhr.responseText
                                        );

                                    mensagem =
                                        resposta.mensagem
                                        || mensagem;

                                } catch (erro) {

                                    console.error(
                                        xhr.responseText
                                    );

                                }

                                mostrarErroLancamento(
                                    mensagem
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
                                "data_lancamento",

                            render:
                                function (valor) {

                                    return formatarDataLancamento(
                                        valor
                                    );

                                }
                        },

                        {
                            data:
                                null,

                            render:
                                function (
                                    dados,
                                    tipo,
                                    lancamento
                                ) {

                                    return escaparHtmlLancamento(
                                        lancamento.diario_codigo
                                        + "-"
                                        + String(
                                            lancamento.numero
                                        ).padStart(
                                            6,
                                            "0"
                                        )
                                    );

                                }
                        },

                        {
                            data:
                                null,

                            render:
                                function (
                                    dados,
                                    tipo,
                                    lancamento
                                ) {

                                    return escaparHtmlLancamento(
                                        lancamento.diario_codigo
                                        + " — "
                                        + lancamento.diario_nome
                                    );

                                }
                        },

                        {
                            data:
                                "numero_documento",

                            defaultContent:
                                "-"
                        },

                        {
                            data:
                                "descricao"
                        },

                        {
                            data:
                                "origem",

                            render:
                                function (valor) {

                                    return formatarOrigemLancamento(
                                        valor
                                    );

                                }
                        },

                        {
                            data:
                                "total_debito",

                            className:
                                "text-right",

                            render:
                                function (valor) {

                                    return formatarMoedaLancamento(
                                        valor
                                    );

                                }
                        },

                        {
                            data:
                                "total_credito",

                            className:
                                "text-right",

                            render:
                                function (valor) {

                                    return formatarMoedaLancamento(
                                        valor
                                    );

                                }
                        },

                        {
                            data:
                                "status_nome",

                            render:
                                function (valor) {

                                    return criarBadgeStatusLancamento(
                                        valor
                                    );

                                }
                        },

                        {
                            data:
                                null,

                            orderable:
                                false,

                            searchable:
                                false,

                            className:
                                "text-right acoes-lancamento",

                            render:
                                function (
                                    dados,
                                    tipo,
                                    lancamento
                                ) {

                                    return criarBotoesLancamento(
                                        lancamento
                                    );

                                }
                        }

                    ],


                    order: [
                        [
                            1,
                            "desc"
                        ]
                    ],


                    pageLength:
                        10,


                    responsive:
                        true,


                    language: {
                        url:
                            "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
                    }

                });

    }


    // ==================================================
    // BOTÕES DA TABELA
    // ==================================================

    function criarBotoesLancamento(
        lancamento
    ) {

        const status =
            normalizarTextoLancamento(
                lancamento.status_nome
            );

        const origem =
            String(
                lancamento.origem || ""
            ).toUpperCase();

        const rascunho =
            status === "rascunho";

        const contabilizado =
            status === "contabilizado";

        const manual =
            origem === "MANUAL";


        const editar =
            rascunho && manual
                ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-info btn-editar-lancamento"
                        data-id="${lancamento.id}"
                        title="Editar">

                        <i class="fa fa-edit"></i>

                    </button>
                `
                : "";


        const contabilizar =
            rascunho
                ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-success btn-contabilizar-lancamento"
                        data-id="${lancamento.id}"
                        title="Contabilizar">

                        <i class="fa fa-check"></i>

                    </button>
                `
                : "";


        const anular =
            contabilizado
                ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-warning btn-anular-lancamento"
                        data-id="${lancamento.id}"
                        title="Anular">

                        <i class="fa fa-ban"></i>

                    </button>
                `
                : "";


        const eliminar =
            rascunho && manual
                ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-danger btn-eliminar-lancamento"
                        data-id="${lancamento.id}"
                        title="Eliminar">

                        <i class="fa fa-trash"></i>

                    </button>
                `
                : "";


        return `
            <button
                type="button"
                class="btn btn-sm btn-primary btn-detalhes-lancamento"
                data-id="${lancamento.id}"
                title="Detalhes">

                <i class="fa fa-eye"></i>

            </button>

            ${editar}
            ${contabilizar}
            ${anular}
            ${eliminar}
        `;

    }


    // ==================================================
    // NOVO LANÇAMENTO
    // ==================================================

    async function abrirNovoLancamento() {

        limparFormularioLancamento();


        const hoje =
            dataAtualLancamento();


        $("#lancamentoData")
            .val(hoje);

        $("#lancamentoDataDocumento")
            .val(hoje);


        const exercicio =
            encontrarExercicioPorData(
                hoje
            );


        if (exercicio) {

            $("#lancamentoExercicioId")
                .val(
                    String(
                        exercicio.id
                    )
                );

            await carregarPeriodos(
                Number(exercicio.id),
                null,
                false
            );


            const periodo =
                encontrarPeriodoPorData(
                    hoje
                );


            if (periodo) {

                $("#lancamentoPeriodoId")
                    .val(
                        String(
                            periodo.id
                        )
                    );

            }

        }


        $("#tituloModalLancamento")
            .text(
                "Novo Lançamento Contabilístico"
            );


        $("#numeroLancamentoInformacao")
            .text(
                "O número será gerado ao guardar."
            );


        $("#modalLancamentoContabilistico")
            .modal("show");

    }


    function encontrarExercicioPorData(
        data
    ) {

        return exercicios.find(
            function (exercicio) {

                return data
                    >= exercicio.data_inicio

                    && data
                    <= exercicio.data_fim;

            }
        ) || null;

    }


    function encontrarPeriodoPorData(
        data
    ) {

        return periodos.find(
            function (periodo) {

                return data
                    >= periodo.data_inicio

                    && data
                    <= periodo.data_fim

                    && normalizarTextoLancamento(
                        periodo.status_nome
                    ) === "aberto";

            }
        ) || null;

    }


    // ==================================================
    // ADICIONAR ITEM
    // ==================================================

    function adicionarOuAtualizarItem() {

        const contaId =
            Number(
                $("#itemContaContabilId").val()
                || 0
            );

        const conta =
            contas.find(
                function (item) {

                    return Number(item.id)
                        === contaId;

                }
            );


        if (!conta) {

            mostrarAvisoLancamento(
                "Seleccione uma conta contabilística."
            );

            return;
        }


        const debito =
            numeroLancamento(
                $("#itemDebito").val()
            );

        const credito =
            numeroLancamento(
                $("#itemCredito").val()
            );


        if (
            debito <= 0
            && credito <= 0
        ) {

            mostrarAvisoLancamento(
                "Informe um valor de débito ou crédito."
            );

            return;
        }


        if (
            debito > 0
            && credito > 0
        ) {

            mostrarAvisoLancamento(
                "O item não pode possuir débito e crédito simultaneamente."
            );

            return;
        }


        const centroCustoId =
            Number(
                $("#itemCentroCustoId").val()
                || 0
            );


        if (
            Number(
                conta.exige_centro_custo
                || 0
            ) === 1

            && centroCustoId <= 0
        ) {

            mostrarAvisoLancamento(
                "A conta seleccionada exige centro de custo."
            );

            return;
        }


        const tipoEntidade =
            String(
                $("#itemTipoEntidade").val()
                || ""
            );


        const clienteId =
            tipoEntidade === "CLIENTE"
                ? Number(
                    $("#itemClienteId").val()
                    || 0
                )
                : 0;


        const fornecedorId =
            tipoEntidade === "FORNECEDOR"
                ? Number(
                    $("#itemFornecedorId").val()
                    || 0
                )
                : 0;


        if (
            Number(
                conta.exige_entidade
                || 0
            ) === 1

            && clienteId <= 0
            && fornecedorId <= 0
        ) {

            mostrarAvisoLancamento(
                "A conta seleccionada exige cliente ou fornecedor."
            );

            return;
        }


        const centro =
            centrosCusto.find(
                function (item) {

                    return Number(item.id)
                        === centroCustoId;

                }
            ) || null;


        const cliente =
            clientes.find(
                function (item) {

                    return Number(item.id)
                        === clienteId;

                }
            ) || null;


        const fornecedor =
            fornecedores.find(
                function (item) {

                    return Number(item.id)
                        === fornecedorId;

                }
            ) || null;


        const item = {

            conta_contabil_id:
                contaId,

            conta_codigo:
                conta.codigo,

            conta_nome:
                conta.nome,

            centro_custo_id:
                centroCustoId > 0
                    ? centroCustoId
                    : null,

            centro_custo_nome:
                centro
                    ? centro.nome
                    : null,

            cliente_id:
                clienteId > 0
                    ? clienteId
                    : null,

            cliente_nome:
                cliente
                    ? cliente.nome
                    : null,

            fornecedor_id:
                fornecedorId > 0
                    ? fornecedorId
                    : null,

            fornecedor_nome:
                fornecedor
                    ? fornecedor.nome
                    : null,

            descricao:
                String(
                    $("#itemDescricao").val()
                    || ""
                ).trim(),

            debito:
                debito,

            credito:
                credito

        };


        if (
            indiceItemEmEdicao !== null
        ) {

            itensLancamento[
                indiceItemEmEdicao
            ] = item;

        } else {

            itensLancamento.push(
                item
            );

        }


        renderizarItensLancamento();

        limparFormularioItem();

    }


    // ==================================================
    // EDITAR ITEM
    // ==================================================

    function editarItemLancamento(
        indice
    ) {

        const item =
            itensLancamento[indice];


        if (!item) {
            return;
        }


        indiceItemEmEdicao =
            indice;


        $("#itemContaContabilId")
            .val(
                String(
                    item.conta_contabil_id
                )
            );


        $("#itemCentroCustoId")
            .val(
                item.centro_custo_id
                    ? String(
                        item.centro_custo_id
                    )
                    : ""
            );


        if (item.cliente_id) {

            $("#itemTipoEntidade")
                .val("CLIENTE")
                .trigger("change");

            $("#itemClienteId")
                .val(
                    String(
                        item.cliente_id
                    )
                );

        } else if (item.fornecedor_id) {

            $("#itemTipoEntidade")
                .val("FORNECEDOR")
                .trigger("change");

            $("#itemFornecedorId")
                .val(
                    String(
                        item.fornecedor_id
                    )
                );

        } else {

            $("#itemTipoEntidade")
                .val("")
                .trigger("change");

        }


        $("#itemDescricao")
            .val(
                item.descricao || ""
            );


        $("#itemDebito")
            .val(
                Number(
                    item.debito || 0
                ).toFixed(2)
            );


        $("#itemCredito")
            .val(
                Number(
                    item.credito || 0
                ).toFixed(2)
            );


        $("#btnAdicionarItemLancamento")
            .html(`
                <i class="fa fa-save"></i>
                Atualizar Item
            `);

    }


    // ==================================================
    // REMOVER ITEM
    // ==================================================

    async function removerItemLancamento(
        indice
    ) {

        const item =
            itensLancamento[indice];


        if (!item) {
            return;
        }


        const confirmacao =
            await Swal.fire({

                icon:
                    "question",

                title:
                    "Remover item",

                text:
                    "Deseja remover este item do lançamento?",

                showCancelButton:
                    true,

                confirmButtonText:
                    "Remover",

                cancelButtonText:
                    "Cancelar"

            });


        if (!confirmacao.isConfirmed) {
            return;
        }


        itensLancamento.splice(
            indice,
            1
        );


        if (
            indiceItemEmEdicao === indice
        ) {

            limparFormularioItem();

        } else if (
            indiceItemEmEdicao !== null
            && indiceItemEmEdicao > indice
        ) {

            indiceItemEmEdicao--;

        }


        renderizarItensLancamento();

    }


    // ==================================================
    // MOVER ITEM
    // ==================================================

    function moverItemLancamento(
        indice,
        direcao
    ) {

        const novoIndice =
            indice + direcao;


        if (
            novoIndice < 0
            || novoIndice
            >= itensLancamento.length
        ) {
            return;
        }


        const item =
            itensLancamento[indice];


        itensLancamento[indice] =
            itensLancamento[novoIndice];


        itensLancamento[novoIndice] =
            item;


        renderizarItensLancamento();

    }


    // ==================================================
    // RENDERIZAR ITENS
    // ==================================================

    function renderizarItensLancamento() {

        const corpo =
            $("#listaItensLancamento");


        corpo.empty();


        if (itensLancamento.length === 0) {

            corpo.html(`
                <tr id="linhaSemItensLancamento">

                    <td
                        colspan="8"
                        class="text-center text-muted py-4">

                        Nenhum item adicionado.

                    </td>

                </tr>
            `);

            atualizarTotaisLancamento();

            return;
        }


        itensLancamento.forEach(
            function (item, indice) {

                const entidade =
                    item.cliente_nome
                    || item.fornecedor_nome
                    || "-";


                corpo.append(`
                    <tr>

                        <td class="text-center">
                            ${indice + 1}
                        </td>

                        <td>
                            <strong>
                                ${escaparHtmlLancamento(
                    item.conta_codigo
                )
                    }
                            </strong>

                            <br>

                            <small>
                                ${escaparHtmlLancamento(
                        item.conta_nome
                    )
                    }
                            </small>
                        </td>

                        <td>
                            ${escaparHtmlLancamento(
                        item.descricao || "-"
                    )
                    }
                        </td>

                        <td>
                            ${escaparHtmlLancamento(
                        item.centro_custo_nome
                        || "-"
                    )
                    }
                        </td>

                        <td>
                            ${escaparHtmlLancamento(
                        entidade
                    )
                    }
                        </td>

                        <td class="text-right">
                            ${item.debito > 0
                        ? formatarMoedaLancamento(
                            item.debito
                        )
                        : "-"
                    }
                        </td>

                        <td class="text-right">
                            ${item.credito > 0
                        ? formatarMoedaLancamento(
                            item.credito
                        )
                        : "-"
                    }
                        </td>

                        <td class="text-center">

                            <button
                                type="button"
                                class="btn btn-sm btn-light btn-subir-item-lancamento"
                                data-indice="${indice}"
                                title="Mover para cima"
                                ${indice === 0
                        ? "disabled"
                        : ""
                    }>

                                <i class="fa fa-arrow-up"></i>

                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-light btn-descer-item-lancamento"
                                data-indice="${indice}"
                                title="Mover para baixo"
                                ${indice
                        === itensLancamento.length - 1
                        ? "disabled"
                        : ""
                    }>

                                <i class="fa fa-arrow-down"></i>

                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-info btn-editar-item-lancamento"
                                data-indice="${indice}"
                                title="Editar">

                                <i class="fa fa-edit"></i>

                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-danger btn-remover-item-lancamento"
                                data-indice="${indice}"
                                title="Remover">

                                <i class="fa fa-trash"></i>

                            </button>

                        </td>

                    </tr>
                `);

            }
        );


        atualizarTotaisLancamento();

    }


    // ==================================================
    // TOTAIS
    // ==================================================

    function calcularTotaisLancamento() {

        return itensLancamento.reduce(
            function (totais, item) {

                totais.debito +=
                    numeroLancamento(
                        item.debito
                    );

                totais.credito +=
                    numeroLancamento(
                        item.credito
                    );

                return totais;

            },
            {
                debito:
                    0,

                credito:
                    0
            }
        );

    }


    function atualizarTotaisLancamento() {

        const totais =
            calcularTotaisLancamento();


        const diferenca =
            Math.abs(
                totais.debito
                - totais.credito
            );


        $("#totalDebitoLancamento")
            .text(
                formatarMoedaLancamento(
                    totais.debito
                )
            );


        $("#totalCreditoLancamento")
            .text(
                formatarMoedaLancamento(
                    totais.credito
                )
            );


        $("#diferencaLancamento")
            .text(
                formatarMoedaLancamento(
                    diferenca
                )
            )
            .toggleClass(
                "valor-lancamento-negativo",
                diferenca > 0.009
            )
            .toggleClass(
                "valor-lancamento-positivo",
                diferenca <= 0.009
                && totais.debito > 0
            );


        const balanceado =
            totais.debito > 0

            && totais.credito > 0

            && diferenca <= 0.009;


        $("#alertaLancamentoBalanceado")
            .toggle(
                balanceado
            );


        $("#alertaLancamentoDesbalanceado")
            .toggle(
                itensLancamento.length > 0
                && !balanceado
            );

    }


    // ==================================================
    // GUARDAR LANÇAMENTO
    // ==================================================

    async function guardarLancamento(
        evento
    ) {

        evento.preventDefault();


        if (itensLancamento.length < 2) {

            mostrarAvisoLancamento(
                "Adicione pelo menos dois itens ao lançamento."
            );

            return;
        }


        const totais =
            calcularTotaisLancamento();


        if (
            totais.debito <= 0
            || totais.credito <= 0
        ) {

            mostrarAvisoLancamento(
                "O lançamento deve possuir débito e crédito."
            );

            return;
        }


        if (
            Math.abs(
                totais.debito
                - totais.credito
            ) > 0.009
        ) {

            mostrarAvisoLancamento(
                "O total de débito deve ser igual ao total de crédito."
            );

            return;
        }


        const formulario =
            evento.currentTarget;


        const botao =
            $("#btnGuardarLancamento");


        bloquearBotaoLancamento(
            botao,
            "A guardar..."
        );


        try {

            const dados =
                new FormData(
                    formulario
                );


            dados.append(
                "acao",
                "guardar_lancamento"
            );


            dados.set(
                "itens",
                JSON.stringify(
                    itensLancamento.map(
                        function (item) {

                            return {

                                conta_contabil_id:
                                    item.conta_contabil_id,

                                centro_custo_id:
                                    item.centro_custo_id,

                                cliente_id:
                                    item.cliente_id,

                                fornecedor_id:
                                    item.fornecedor_id,

                                descricao:
                                    item.descricao,

                                debito:
                                    item.debito,

                                credito:
                                    item.credito

                            };

                        }
                    )
                )
            );


            const resposta =
                await requisicaoLancamento(
                    LANCAMENTO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );


            $("#modalLancamentoContabilistico")
                .modal("hide");


            recarregarTabelaLancamentos();


            toastLancamento(
                resposta.mensagem
                || "Lançamento guardado."
            );

        } catch (erro) {

            mostrarErroLancamento(
                erro.message
            );

        } finally {

            restaurarBotaoLancamento(
                botao,
                `
                    <i class="fa fa-save"></i>
                    Guardar Rascunho
                `
            );

        }

    }


    // ==================================================
    // EDITAR LANÇAMENTO
    // ==================================================

    async function editarLancamento(
        lancamentoId
    ) {

        try {

            const resposta =
                await buscarLancamento(
                    lancamentoId
                );


            const lancamento =
                resposta.data;


            limparFormularioLancamento();


            $("#lancamentoId")
                .val(
                    lancamento.id
                );


            $("#lancamentoExercicioId")
                .val(
                    String(
                        lancamento.exercicio_id
                    )
                );

            $("#lancamentoExercicioId")
                .prop("disabled", true);

            $("#lancamentoDiarioId")
                .prop("disabled", true);
            await carregarPeriodos(
                Number(
                    lancamento.exercicio_id
                ),
                Number(
                    lancamento.periodo_id
                ),
                false
            );


            $("#lancamentoDiarioId")
                .val(
                    String(
                        lancamento.diario_id
                    )
                );


            $("#lancamentoData")
                .val(
                    lancamento.data_lancamento
                    || ""
                );


            $("#lancamentoDataDocumento")
                .val(
                    lancamento.data_documento
                    || ""
                );


            $("#lancamentoNumeroDocumento")
                .val(
                    lancamento.numero_documento
                    || ""
                );


            $("#lancamentoReferencia")
                .val(
                    lancamento.referencia
                    || ""
                );


            $("#lancamentoDescricao")
                .val(
                    lancamento.descricao
                    || ""
                );


            itensLancamento =
                (lancamento.itens || [])
                    .map(
                        function (item) {

                            return {

                                conta_contabil_id:
                                    Number(
                                        item.conta_contabil_id
                                    ),

                                conta_codigo:
                                    item.conta_codigo,

                                conta_nome:
                                    item.conta_nome,

                                centro_custo_id:
                                    item.centro_custo_id
                                        ? Number(
                                            item.centro_custo_id
                                        )
                                        : null,

                                centro_custo_nome:
                                    item.centro_custo_nome,

                                cliente_id:
                                    item.cliente_id
                                        ? Number(
                                            item.cliente_id
                                        )
                                        : null,

                                cliente_nome:
                                    item.cliente_nome,

                                fornecedor_id:
                                    item.fornecedor_id
                                        ? Number(
                                            item.fornecedor_id
                                        )
                                        : null,

                                fornecedor_nome:
                                    item.fornecedor_nome,

                                descricao:
                                    item.descricao || "",

                                debito:
                                    numeroLancamento(
                                        item.debito
                                    ),

                                credito:
                                    numeroLancamento(
                                        item.credito
                                    )

                            };

                        }
                    );


            renderizarItensLancamento();


            $("#tituloModalLancamento")
                .text(
                    "Editar Lançamento Contabilístico"
                );


            $("#numeroLancamentoInformacao")
                .text(
                    lancamento.diario_codigo
                    + "-"
                    + String(
                        lancamento.numero
                    ).padStart(
                        6,
                        "0"
                    )
                );


            $("#modalLancamentoContabilistico")
                .modal("show");

        } catch (erro) {

            mostrarErroLancamento(
                erro.message
            );

        }

    }


    // ==================================================
    // BUSCAR
    // ==================================================

    async function buscarLancamento(
        lancamentoId
    ) {

        if (lancamentoId <= 0) {

            throw new Error(
                "Lançamento contabilístico inválido."
            );

        }


        return requisicaoLancamento(
            LANCAMENTO_URL
            + "?acao=buscar_lancamento&id="
            + encodeURIComponent(
                lancamentoId
            )
        );

    }


    // ==================================================
    // DETALHES
    // ==================================================

    async function visualizarLancamento(
        lancamentoId
    ) {

        try {

            const resposta =
                await buscarLancamento(
                    lancamentoId
                );


            const lancamento =
                resposta.data;


            $("#detalhesLancamentoNumero")
                .text(
                    lancamento.diario_codigo
                    + "-"
                    + String(
                        lancamento.numero
                    ).padStart(
                        6,
                        "0"
                    )
                );


            $("#detalhesLancamentoData")
                .text(
                    formatarDataLancamento(
                        lancamento.data_lancamento
                    )
                );


            $("#detalhesLancamentoDiario")
                .text(
                    lancamento.diario_codigo
                    + " — "
                    + lancamento.diario_nome
                );


            $("#detalhesLancamentoPeriodo")
                .text(
                    lancamento.periodo_numero
                    + " — "
                    + lancamento.periodo_nome
                );


            $("#detalhesLancamentoStatus")
                .html(
                    criarBadgeStatusLancamento(
                        lancamento.status_nome
                    )
                );


            $("#detalhesLancamentoDocumento")
                .text(
                    lancamento.numero_documento
                    || "-"
                );


            $("#detalhesLancamentoReferencia")
                .text(
                    lancamento.referencia
                    || "-"
                );


            $("#detalhesLancamentoOrigem")
                .text(
                    formatarOrigemLancamento(
                        lancamento.origem
                    )
                );


            $("#detalhesLancamentoDescricao")
                .text(
                    lancamento.descricao
                    || "-"
                );


            const corpo =
                $("#detalhesItensLancamento");


            corpo.empty();


            (lancamento.itens || [])
                .forEach(
                    function (item) {

                        const entidade =
                            item.cliente_nome
                            || item.fornecedor_nome
                            || "-";


                        corpo.append(`
                            <tr>

                                <td>
                                    ${escaparHtmlLancamento(
                            item.conta_codigo
                            + " — "
                            + item.conta_nome
                        )
                            }
                                </td>

                                <td>
                                    ${escaparHtmlLancamento(
                                item.descricao
                                || "-"
                            )
                            }
                                </td>

                                <td>
                                    ${escaparHtmlLancamento(
                                item.centro_custo_nome
                                || "-"
                            )
                            }
                                </td>

                                <td>
                                    ${escaparHtmlLancamento(
                                entidade
                            )
                            }
                                </td>

                                <td class="text-right">
                                    ${Number(
                                item.debito
                            ) > 0
                                ? formatarMoedaLancamento(
                                    item.debito
                                )
                                : "-"
                            }
                                </td>

                                <td class="text-right">
                                    ${Number(
                                item.credito
                            ) > 0
                                ? formatarMoedaLancamento(
                                    item.credito
                                )
                                : "-"
                            }
                                </td>

                            </tr>
                        `);

                    }
                );


            $("#detalhesTotalDebito")
                .text(
                    formatarMoedaLancamento(
                        lancamento.total_debito
                    )
                );


            $("#detalhesTotalCredito")
                .text(
                    formatarMoedaLancamento(
                        lancamento.total_credito
                    )
                );


            const anulado =
                normalizarTextoLancamento(
                    lancamento.status_nome
                ) === "anulado";


            $("#detalhesMotivoAnulacao")
                .toggle(
                    anulado
                );


            $("#textoMotivoAnulacao")
                .text(
                    lancamento.motivo_anulacao
                    || "-"
                );


            $("#modalDetalhesLancamento")
                .modal("show");

        } catch (erro) {

            mostrarErroLancamento(
                erro.message
            );

        }

    }


    // ==================================================
    // CONTABILIZAR
    // ==================================================

    function abrirContabilizacao(
        lancamentoId
    ) {

        $("#contabilizarLancamentoId")
            .val(
                lancamentoId
            );


        $("#modalContabilizarLancamento")
            .modal("show");

    }


    async function contabilizarLancamento(
        evento
    ) {

        evento.preventDefault();


        const id =
            Number(
                $("#contabilizarLancamentoId")
                    .val()
                || 0
            );


        const botao =
            $("#btnConfirmarContabilizacao");


        bloquearBotaoLancamento(
            botao,
            "A contabilizar..."
        );


        try {

            const dados =
                new FormData();


            dados.append(
                "acao",
                "contabilizar_lancamento"
            );


            dados.append(
                "id",
                String(id)
            );


            const resposta =
                await requisicaoLancamento(
                    LANCAMENTO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );


            $("#modalContabilizarLancamento")
                .modal("hide");


            recarregarTabelaLancamentos();


            toastLancamento(
                resposta.mensagem
            );

        } catch (erro) {

            mostrarErroLancamento(
                erro.message
            );

        } finally {

            restaurarBotaoLancamento(
                botao,
                `
                    <i class="fa fa-check"></i>
                    Contabilizar
                `
            );

        }

    }


    // ==================================================
    // ANULAR
    // ==================================================

    function abrirAnulacao(
        lancamentoId
    ) {

        $("#anularLancamentoId")
            .val(
                lancamentoId
            );

        $("#motivoAnulacaoLancamento")
            .val("");

        $("#dataEstornoLancamento")
            .val(
                dataAtualLancamento()
            );

        $("#modalAnularLancamento")
            .modal("show");
    }


    async function anularLancamento(
        evento
    ) {

        evento.preventDefault();

        const dataEstorno =
            String(
                $("#dataEstornoLancamento")
                    .val()
                || ""
            ).trim();

        if (dataEstorno === "") {

            mostrarAvisoLancamento(
                "Informe a data do estorno."
            );

            return;
        }

        const id =
            Number(
                $("#anularLancamentoId").val()
                || 0
            );


        const motivo =
            String(
                $("#motivoAnulacaoLancamento")
                    .val()
                || ""
            ).trim();


        if (motivo === "") {

            mostrarAvisoLancamento(
                "Informe o motivo da anulação."
            );

            return;
        }


        const botao =
            $("#btnConfirmarAnulacao");


        bloquearBotaoLancamento(
            botao,
            "A anular..."
        );


        try {

            const dados =
                new FormData();


            dados.append(
                "acao",
                "estornar_lancamento"
            );


            dados.append(
                "id",
                String(id)
            );


            dados.append(
                "motivo",
                motivo
            );

            dados.append(
                "data_estorno",
                dataEstorno
            );


            const resposta =
                await requisicaoLancamento(
                    LANCAMENTO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );


            $("#modalAnularLancamento")
                .modal("hide");


            recarregarTabelaLancamentos();


            toastLancamento(
                resposta.mensagem
            );

        } catch (erro) {

            mostrarErroLancamento(
                erro.message
            );

        } finally {

            restaurarBotaoLancamento(
                botao,
                `
        <i class="fa fa-undo"></i>
        Criar Estorno
    `
            );

        }

    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    function abrirEliminacao(
        lancamentoId
    ) {

        $("#eliminarLancamentoId")
            .val(
                lancamentoId
            );


        $("#modalEliminarLancamento")
            .modal("show");

    }


    async function eliminarLancamento(
        evento
    ) {

        evento.preventDefault();


        const id =
            Number(
                $("#eliminarLancamentoId")
                    .val()
                || 0
            );


        const botao =
            $("#btnConfirmarEliminarLancamento");


        bloquearBotaoLancamento(
            botao,
            "A eliminar..."
        );


        try {

            const dados =
                new FormData();


            dados.append(
                "acao",
                "eliminar_lancamento"
            );


            dados.append(
                "id",
                String(id)
            );


            const resposta =
                await requisicaoLancamento(
                    LANCAMENTO_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );


            $("#modalEliminarLancamento")
                .modal("hide");


            recarregarTabelaLancamentos();


            toastLancamento(
                resposta.mensagem
            );

        } catch (erro) {

            mostrarErroLancamento(
                erro.message
            );

        } finally {

            restaurarBotaoLancamento(
                botao,
                `
                    <i class="fa fa-trash"></i>
                    Eliminar
                `
            );

        }

    }


    // ==================================================
    // FILTROS
    // ==================================================

    function limparFiltrosLancamentos() {

        $("#filtroExercicioLancamento")
            .val("");

        $("#filtroPeriodoLancamento")
            .html(`
                <option value="">
                    Todos
                </option>
            `);

        $("#filtroDiarioLancamento")
            .val("");

        $("#filtroStatusLancamento")
            .val("");

        $("#filtroOrigemLancamento")
            .val("");

        $("#filtroDataInicioLancamento")
            .val("");

        $("#filtroDataFimLancamento")
            .val("");


        recarregarTabelaLancamentos();

    }


    function recarregarTabelaLancamentos() {

        if (tabelaLancamentos) {

            tabelaLancamentos.ajax.reload(
                null,
                false
            );

        }

    }


    // ==================================================
    // RESUMOS
    // ==================================================

    function atualizarResumoLancamentos() {

        const total =
            lancamentos.length;


        const rascunhos =
            lancamentos.filter(
                function (lancamento) {

                    return normalizarTextoLancamento(
                        lancamento.status_nome
                    ) === "rascunho";

                }
            ).length;


        const contabilizados =
            lancamentos.filter(
                function (lancamento) {

                    return normalizarTextoLancamento(
                        lancamento.status_nome
                    ) === "contabilizado";

                }
            );


        const valorContabilizado =
            contabilizados.reduce(
                function (totalAtual, lancamento) {

                    return totalAtual
                        + numeroLancamento(
                            lancamento.total_debito
                        );

                },
                0
            );


        $("#resumoTotalLancamentos")
            .text(total);


        $("#resumoLancamentosRascunho")
            .text(rascunhos);


        $("#resumoLancamentosContabilizados")
            .text(
                contabilizados.length
            );


        $("#resumoValorContabilizado")
            .text(
                formatarMoedaLancamento(
                    valorContabilizado
                )
            );

    }


    function limparResumoLancamentos() {

        $("#resumoTotalLancamentos")
            .text("0");

        $("#resumoLancamentosRascunho")
            .text("0");

        $("#resumoLancamentosContabilizados")
            .text("0");

        $("#resumoValorContabilizado")
            .text("0,00 MZN");

    }


    // ==================================================
    // ENTIDADE
    // ==================================================

    function atualizarCamposEntidade() {

        const tipo =
            String(
                $("#itemTipoEntidade").val()
                || ""
            );


        $("#grupoItemCliente")
            .toggle(
                tipo === "CLIENTE"
            );


        $("#grupoItemFornecedor")
            .toggle(
                tipo === "FORNECEDOR"
            );


        if (tipo !== "CLIENTE") {
            $("#itemClienteId").val("");
        }


        if (tipo !== "FORNECEDOR") {
            $("#itemFornecedorId").val("");
        }

    }


    // ==================================================
    // LIMPEZA
    // ==================================================

    function limparFormularioLancamento() {

        const formulario =
            document.getElementById(
                "formLancamentoContabilistico"
            );


        if (formulario) {
            formulario.reset();
        }


        $("#lancamentoId").val("");


        $("#lancamentoPeriodoId")
            .html(`
                <option value="">
                    Seleccione
                </option>
            `);


        itensLancamento = [];

        indiceItemEmEdicao = null;


        renderizarItensLancamento();

        limparFormularioItem();


        $("#tituloModalLancamento")
            .text(
                "Novo Lançamento Contabilístico"
            );


        $("#numeroLancamentoInformacao")
            .text(
                "O número será gerado ao guardar."
            );

        $("#lancamentoExercicioId")
            .prop("disabled", false);

        $("#lancamentoDiarioId")
            .prop("disabled", false);

    }


    function limparFormularioItem() {

        indiceItemEmEdicao =
            null;


        $("#itemContaContabilId")
            .val("");

        $("#itemCentroCustoId")
            .val("");

        $("#itemTipoEntidade")
            .val("")
            .trigger("change");

        $("#itemClienteId")
            .val("");

        $("#itemFornecedorId")
            .val("");

        $("#itemDescricao")
            .val("");

        $("#itemDebito")
            .val("0.00");

        $("#itemCredito")
            .val("0.00");


        $("#btnAdicionarItemLancamento")
            .html(`
                <i class="fa fa-plus"></i>
                Adicionar Item
            `);

    }


    // ==================================================
    // BADGES
    // ==================================================

    function criarBadgeStatusLancamento(
        status
    ) {

        const normalizado =
            normalizarTextoLancamento(
                status
            );


        let classe =
            "status-lancamento-desconhecido";


        if (normalizado === "rascunho") {
            classe =
                "status-lancamento-rascunho";
        }


        if (normalizado === "contabilizado") {
            classe =
                "status-lancamento-contabilizado";
        }


        if (normalizado === "anulado") {
            classe =
                "status-lancamento-anulado";
        }


        return `
            <span
                class="badge-status-lancamento ${classe}">

                ${escaparHtmlLancamento(
            status || "-"
        )
            }

            </span>
        `;

    }


    // ==================================================
    // FORMATAÇÃO
    // ==================================================

    function formatarOrigemLancamento(
        origem
    ) {

        const origens = {

            MANUAL:
                "Manual",

            VENDA:
                "Venda",

            COMPRA:
                "Compra",

            RECEBIMENTO:
                "Recebimento",

            PAGAMENTO:
                "Pagamento",

            CAIXA:
                "Caixa",

            BANCO:
                "Banco",

            STOCK:
                "Stock",

            SALARIO:
                "Salário",

            FOLHA_SALARIAL:
                "Folha Salarial",

            PAGAMENTO_SALARIAL:
                "Pagamento Salarial",

            IMPOSTO:
                "Imposto",

            ABERTURA:
                "Abertura",

            ENCERRAMENTO:
                "Encerramento",

            ESTORNO:
                "Estorno"

        };


        const chave =
            String(
                origem || ""
            )
                .trim()
                .toUpperCase();


        return origens[chave]
            || origem
            || "-";
    }

    function formatarMoedaLancamento(
        valor
    ) {

        return numeroLancamento(
            valor
        ).toLocaleString(
            "pt-MZ",
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


    function formatarDataLancamento(
        valor
    ) {

        if (!valor) {
            return "-";
        }


        const partes =
            String(valor)
                .substring(0, 10)
                .split("-");


        if (partes.length !== 3) {
            return valor;
        }


        return (
            partes[2]
            + "/"
            + partes[1]
            + "/"
            + partes[0]
        );

    }


    function dataAtualLancamento() {

        const data =
            new Date();


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


        return `${ano}-${mes}-${dia}`;

    }


    function numeroLancamento(
        valor
    ) {

        if (
            typeof valor === "string"
        ) {

            valor =
                valor
                    .replace(/\s/g, "")
                    .replace(",", ".");

        }


        const numero =
            Number(valor);


        return Number.isFinite(numero)
            ? Math.max(
                0,
                Math.round(
                    numero * 100
                ) / 100
            )
            : 0;

    }


    function normalizarTextoLancamento(
        valor
    ) {

        return String(
            valor || ""
        )
            .normalize("NFD")
            .replace(
                /[\u0300-\u036f]/g,
                ""
            )
            .toLowerCase()
            .trim();

    }


    function escaparHtmlLancamento(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }


    // ==================================================
    // BOTÕES
    // ==================================================

    function bloquearBotaoLancamento(
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


    function restaurarBotaoLancamento(
        botao,
        html
    ) {

        botao
            .prop(
                "disabled",
                false
            )
            .html(html);

    }


    // ==================================================
    // MENSAGENS
    // ==================================================

    function mostrarErroLancamento(
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


    function mostrarAvisoLancamento(
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


    function toastLancamento(
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

})(jQuery);