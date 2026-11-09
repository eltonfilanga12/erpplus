"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const PLANO_CONTA_URL =
        "/erpplus/app/ajax/plano_conta_ajax.php";

    let planosContas = [];

    let contasContabilisticas = [];

    let planoAtualId = null;

    let statusPlanos = [];

    let statusContas = [];


    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(async function () {

        configurarEventosPlanoConta();

        limparResumoContas();

        await Promise.all([
            carregarStatusPlanos(),
            carregarStatusContas()
        ]);

        await carregarPlanosContas();

    });

    async function carregarStatusPlanos() {

        try {

            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL
                    + "?acao=status_planos"
                );

            statusPlanos =
                resposta.data || [];

            preencherSelectStatusPlanos();

        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
            );

        }

    }

    function preencherSelectStatusPlanos() {

        const selects = [
            $("#planoStatusId"),
            $("#novoStatusPlanoId")
        ];

        selects.forEach(function (select) {

            select.html(`
            <option value="">
                Seleccione o status
            </option>
        `);

            statusPlanos.forEach(
                function (status) {

                    select.append(`
                    <option value="${status.id}">
                        ${escaparHtmlPlanoConta(
                        status.nome
                    )}
                    </option>
                `);

                }
            );

        });

    }

    async function carregarStatusContas() {

        try {

            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL
                    + "?acao=status_contas"
                );

            statusContas =
                resposta.data || [];

            preencherSelectStatusContas();

        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
            );

        }

    }

    function preencherSelectStatusContas() {

        const selects = [
            $("#contaStatusId"),
            $("#novoStatusContaId"),
            $("#filtroStatusConta")
        ];

        selects.forEach(function (select) {

            const filtro =
                select.attr("id")
                === "filtroStatusConta";

            select.html(`
            <option value="">
                ${filtro
                    ? "Todos os status"
                    : "Seleccione o status"
                }
            </option>
        `);

            statusContas.forEach(
                function (status) {

                    select.append(`
                    <option value="${status.id}">
                        ${escaparHtmlPlanoConta(
                        status.nome
                    )}
                    </option>
                `);

                }
            );

        });

    }

    function obterStatusPorNome(
        lista,
        nome
    ) {

        const nomeNormalizado =
            normalizarTextoPlanoConta(
                nome
            );

        return lista.find(
            function (status) {

                return normalizarTextoPlanoConta(
                    status.nome
                ) === nomeNormalizado;

            }
        ) || null;

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosPlanoConta() {

        // ----------------------------------------------
        // PLANO SELECIONADO
        // ----------------------------------------------

        $("#formStatusPlano").on(
            "submit",
            guardarStatusPlano
        );


        $("#formStatusConta").on(
            "submit",
            guardarStatusConta
        );

        $("#planoSelecionado").on(
            "change",
            async function () {

                const planoId =
                    Number(
                        $(this).val()
                        || 0
                    );

                planoAtualId =
                    planoId > 0
                        ? planoId
                        : null;

                atualizarBotoesPlano();

                if (!planoAtualId) {

                    contasContabilisticas = [];

                    renderizarArvoreContas([]);

                    limparResumoContas();

                    mostrarMensagemPlano(
                        "Seleccione um plano de contas para visualizar a estrutura."
                    );

                    return;
                }

                await carregarContasPlano();

            }
        );


        // ----------------------------------------------
        // NOVO PLANO
        // ----------------------------------------------

        $("#btnNovoPlano").on(
            "click",
            abrirNovoPlano
        );

        // ----------------------------------------------
        // IMPORTAR PGC-NIRF
        // ----------------------------------------------

        $("#btnImportarPgc").on(
            "click",
            importarPgcNirf
        );
        // ----------------------------------------------
        // EDITAR PLANO
        // ----------------------------------------------

        $("#btnEditarPlano").on(
            "click",
            editarPlanoAtual
        );


        // ----------------------------------------------
        // ELIMINAR PLANO
        // ----------------------------------------------

        $("#btnEliminarPlano").on(
            "click",
            function () {

                if (!planoAtualId) {
                    return;
                }

                abrirModalEliminar(
                    "plano",
                    planoAtualId,
                    "Confirma a eliminação deste plano de contas?"
                );

            }
        );


        // ----------------------------------------------
        // NOVA CONTA
        // ----------------------------------------------

        $("#btnNovaConta").on(
            "click",
            function () {

                abrirNovaConta(
                    null
                );

            }
        );


        // ----------------------------------------------
        // GUARDAR PLANO
        // ----------------------------------------------

        $("#formPlanoConta").on(
            "submit",
            guardarPlano
        );


        // ----------------------------------------------
        // GUARDAR CONTA
        // ----------------------------------------------

        $("#formContaContabil").on(
            "submit",
            guardarConta
        );


        // ----------------------------------------------
        // ELIMINAR
        // ----------------------------------------------

        $("#formEliminarPlanoConta").on(
            "submit",
            confirmarEliminacao
        );


        // ----------------------------------------------
        // TIPO DA CONTA
        // ----------------------------------------------

        $("#contaTipo").on(
            "change",
            atualizarConfiguracaoTipoConta
        );


        // ----------------------------------------------
        // FILTROS
        // ----------------------------------------------

        $("#pesquisaConta").on(
            "input",
            aplicarFiltrosContas
        );

        $("#filtroGrupoConta").on(
            "change",
            aplicarFiltrosContas
        );

        $("#filtroTipoConta").on(
            "change",
            aplicarFiltrosContas
        );

        $("#filtroStatusConta").on(
            "change",
            aplicarFiltrosContas
        );


        $("#btnLimparFiltrosConta").on(
            "click",
            function () {

                $("#pesquisaConta").val("");

                $("#filtroGrupoConta").val("");

                $("#filtroTipoConta").val("");

                $("#filtroStatusConta").val("");

                aplicarFiltrosContas();

            }
        );


        // ----------------------------------------------
        // ÁRVORE
        // ----------------------------------------------

        $("#btnExpandirArvore").on(
            "click",
            function () {

                $(".conta-arvore-filhas")
                    .removeClass("recolhida");

                $(".conta-arvore-toggle i")
                    .removeClass(
                        "fa-plus-square"
                    )
                    .addClass(
                        "fa-minus-square"
                    );

            }
        );


        $("#btnRecolherArvore").on(
            "click",
            function () {

                $(".conta-arvore-filhas")
                    .addClass("recolhida");

                $(".conta-arvore-toggle i")
                    .removeClass(
                        "fa-minus-square"
                    )
                    .addClass(
                        "fa-plus-square"
                    );

            }
        );


        $("#btnAtualizarArvore").on(
            "click",
            carregarContasPlano
        );


        $("#btnStatusPlano").on(
            "click",
            abrirModalStatusPlanoAtual
        );


        // ----------------------------------------------
        // TOGGLE DE NÓ
        // ----------------------------------------------

        $(document).on(
            "click",
            ".conta-arvore-toggle",
            function () {

                const contaId =
                    Number(
                        $(this).data("id")
                        || 0
                    );

                if (contaId <= 0) {
                    return;
                }

                const filhos =
                    $(
                        `#filhasConta${contaId}`
                    );

                filhos.toggleClass(
                    "recolhida"
                );

                const icone =
                    $(this).find("i");

                if (
                    filhos.hasClass(
                        "recolhida"
                    )
                ) {

                    icone
                        .removeClass(
                            "fa-minus-square"
                        )
                        .addClass(
                            "fa-plus-square"
                        );

                } else {

                    icone
                        .removeClass(
                            "fa-plus-square"
                        )
                        .addClass(
                            "fa-minus-square"
                        );

                }

            }
        );


        // ----------------------------------------------
        // NOVA CONTA FILHA
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-nova-conta-filha",
            function () {

                const paiId =
                    Number(
                        $(this).data("id")
                        || 0
                    );

                abrirNovaConta(
                    paiId
                );

            }
        );


        // ----------------------------------------------
        // EDITAR CONTA
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-editar-conta",
            function () {

                editarConta(
                    Number(
                        $(this).data("id")
                    )
                );

            }
        );



        // ----------------------------------------------
        // ALTERAR ESTADO
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-status-conta",
            function () {

                abrirModalStatusConta(
                    Number(
                        $(this).data("id")
                    ),
                    Number(
                        $(this).data("status-id")
                    )
                );

            }
        );

        function abrirModalStatusConta(
            contaId,
            statusId
        ) {

            $("#statusContaId")
                .val(contaId);

            $("#novoStatusContaId")
                .val(
                    String(
                        statusId
                        || ""
                    )
                );

            $("#modalStatusConta")
                .modal("show");

        }


        // ----------------------------------------------
        // ELIMINAR CONTA
        // ----------------------------------------------

        $(document).on(
            "click",
            ".btn-eliminar-conta",
            function () {

                abrirModalEliminar(
                    "conta",
                    Number(
                        $(this).data("id")
                    ),
                    "Confirma a eliminação desta conta contabilística?"
                );

            }
        );


        // ----------------------------------------------
        // LIMPAR MODAIS
        // ----------------------------------------------

        $("#modalPlanoConta").on(
            "hidden.bs.modal",
            limparFormularioPlano
        );

        $("#modalContaContabil").on(
            "hidden.bs.modal",
            limparFormularioConta
        );

    }

    async function importarPgcNirf() {

        if (!planoAtualId) {

            mostrarAvisoPlanoConta(
                "Seleccione primeiro um plano de contas."
            );

            return;
        }


        const plano =
            planosContas.find(
                function (item) {

                    return Number(
                        item.id
                    ) === Number(
                        planoAtualId
                    );
                }
            );


        if (!plano) {

            mostrarErroPlanoConta(
                "Plano de contas não encontrado."
            );

            return;
        }


        const confirmacao =
            await Swal.fire({

                icon:
                    "question",

                title:
                    "Importar PGC-NIRF?",

                html: `
                <div class="text-left">

                    <p>
                        O PGC-NIRF será importado para:
                    </p>

                    <p class="mb-2">
                        <strong>
                            ${escaparHtmlPlanoConta(
                    plano.codigo
                    + " - "
                    + plano.nome
                )}
                        </strong>
                    </p>

                    <p class="mb-0 text-muted">
                        Contas já existentes com o mesmo código
                        poderão ser atualizadas.
                    </p>

                </div>
            `,

                showCancelButton:
                    true,

                confirmButtonText:
                    '<i class="fa fa-download"></i> Importar',

                cancelButtonText:
                    "Cancelar",

                reverseButtons:
                    true
            });


        if (!confirmacao.isConfirmed) {
            return;
        }


        const botao =
            $("#btnImportarPgc");


        bloquearBotaoPlanoConta(
            botao,
            "A importar..."
        );


        try {

            const dados =
                new FormData();


            dados.append(
                "acao",
                "importar_pgc_nirf"
            );


            dados.append(
                "plano_id",
                String(
                    planoAtualId
                )
            );


            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );


            const resultado =
                resposta.data
                || {};


            await Swal.fire({

                icon:
                    "success",

                title:
                    "Importação concluída",

                html: `
                <div class="text-left">

                    <p>
                        ${escaparHtmlPlanoConta(
                    resposta.mensagem
                    || "PGC-NIRF importado com sucesso."
                )
                    }
                    </p>

                    <hr>

                    <div>
                        <strong>Inseridas:</strong>
                        ${Number(
                        resultado.inseridas
                        || 0
                    )
                    }
                    </div>

                    <div>
                        <strong>Atualizadas:</strong>
                        ${Number(
                        resultado.atualizadas
                        || 0
                    )
                    }
                    </div>

                    <div>
                        <strong>Ignoradas:</strong>
                        ${Number(
                        resultado.ignoradas
                        || 0
                    )
                    }
                    </div>

                </div>
            `,

                confirmButtonText:
                    "OK"
            });


            await carregarPlanosContas(
                planoAtualId
            );


            await carregarContasPlano();


        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
                || "Não foi possível importar o PGC-NIRF."
            );


        } finally {

            restaurarBotaoPlanoConta(
                botao,
                `
                <i class="fa fa-download"></i>
                Importar PGC-NIRF
            `
            );

            atualizarBotoesPlano();
        }
    }

    function abrirModalStatusPlanoAtual() {

        if (!planoAtualId) {

            mostrarAvisoPlanoConta(
                "Seleccione um plano de contas."
            );

            return;
        }

        const plano =
            planosContas.find(
                function (item) {

                    return Number(item.id)
                        === Number(planoAtualId);

                }
            );

        if (!plano) {

            mostrarErroPlanoConta(
                "Plano de contas não encontrado."
            );

            return;
        }

        $("#statusPlanoId")
            .val(
                plano.id
            );

        $("#novoStatusPlanoId")
            .val(
                String(
                    plano.status_id
                    || ""
                )
            );

        $("#modalStatusPlano")
            .modal("show");

    }
    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoPlanoConta(
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
                "Resposta não JSON:",
                texto
            );

            throw new Error(
                "O servidor não retornou uma resposta JSON válida."
            );

        }

        if (
            resposta.status === 401
        ) {

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

            throw new Error(
                json.mensagem
                || "Não foi possível concluir a operação."
            );

        }

        return json;
    }


    // ==================================================
    // CARREGAR PLANOS
    // ==================================================

    async function carregarPlanosContas(
        selecionarId = null
    ) {

        try {

            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL
                    + "?acao=listar_planos"
                );

            planosContas =
                resposta.data || [];

            preencherSelectPlanos(
                selecionarId
            );

        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
            );

        }
    }


    function preencherSelectPlanos(
        selecionarId = null
    ) {

        const select =
            $("#planoSelecionado");

        select.html(`
            <option value="">
                Seleccione o plano
            </option>
        `);

        planosContas.forEach(
            function (plano) {

                const padrao =
                    Number(
                        plano.padrao
                        || 0
                    ) === 1;

                const statusNome =
                    String(
                        plano.status_nome
                        || ""
                    );

                let complemento = "";

                if (padrao) {
                    complemento +=
                        " — Padrão";
                }

                if (
                    normalizarTextoPlanoConta(
                        statusNome
                    ) !== "activo"
                ) {
                    complemento +=
                        " — "
                        + statusNome;
                }

                select.append(`
                    <option value="${plano.id}">
                        ${escaparHtmlPlanoConta(
                    plano.codigo
                    + " - "
                    + plano.nome
                    + complemento
                )}
                    </option>
                `);

            }
        );

        let idSelecionar =
            selecionarId
            || planoAtualId;

        if (!idSelecionar) {

            const planoPadrao =
                planosContas.find(
                    function (plano) {

                        return Number(
                            plano.padrao
                            || 0
                        ) === 1;

                    }
                );

            if (planoPadrao) {
                idSelecionar =
                    planoPadrao.id;
            }

        }

        if (idSelecionar) {

            select
                .val(
                    String(idSelecionar)
                )
                .trigger("change");

        } else {

            planoAtualId = null;

            atualizarBotoesPlano();

            mostrarMensagemPlano(
                "Nenhum plano de contas disponível."
            );

        }

    }


    // ==================================================
    // NOVO PLANO
    // ==================================================

    function abrirNovoPlano() {

        limparFormularioPlano();

        $("#tituloModalPlano")
            .text(
                "Novo Plano de Contas"
            );

        $("#planoDataInicio")
            .val(
                dataHojePlanoConta()
            );

        const statusActivo =
            obterStatusPorNome(
                statusPlanos,
                "Activo"
            );

        $("#planoStatusId")
            .val(
                statusActivo
                    ? String(statusActivo.id)
                    : ""
            );

        $("#modalPlanoConta")
            .modal("show");

    }


    // ==================================================
    // EDITAR PLANO
    // ==================================================

    async function editarPlanoAtual() {

        if (!planoAtualId) {

            mostrarAvisoPlanoConta(
                "Seleccione um plano de contas."
            );

            return;
        }

        try {

            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL
                    + "?acao=buscar_plano&id="
                    + encodeURIComponent(
                        planoAtualId
                    )
                );

            const plano =
                resposta.data;

            $("#planoId")
                .val(
                    plano.id
                );

            $("#planoCodigo")
                .val(
                    plano.codigo
                    || ""
                );

            $("#planoNome")
                .val(
                    plano.nome
                    || ""
                );

            $("#planoVersao")
                .val(
                    plano.versao
                    || ""
                );

            $("#planoDataInicio")
                .val(
                    plano.data_inicio_vigencia
                    || ""
                );

            $("#planoDataFim")
                .val(
                    plano.data_fim_vigencia
                    || ""
                );

            $("#planoDescricao")
                .val(
                    plano.descricao
                    || ""
                );

            $("#planoStatusId")
                .val(
                    String(
                        plano.status_id
                        || ""
                    )
                );

            $("#planoPadrao")
                .prop(
                    "checked",
                    Number(
                        plano.padrao
                        || 0
                    ) === 1
                );

            $("#tituloModalPlano")
                .text(
                    "Editar Plano de Contas"
                );

            $("#modalPlanoConta")
                .modal("show");

        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
            );

        }
    }


    // ==================================================
    // GUARDAR PLANO
    // ==================================================

    async function guardarPlano(
        evento
    ) {

        evento.preventDefault();

        const formulario =
            evento.currentTarget;

        const botao =
            $("#btnGuardarPlano");

        bloquearBotaoPlanoConta(
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
                "guardar_plano"
            );

            if (
                !$("#planoPadrao")
                    .is(":checked")
            ) {
                dados.set(
                    "padrao",
                    "0"
                );
            }

            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            const planoId =
                resposta.data?.id
                || null;

            $("#modalPlanoConta")
                .modal("hide");

            await carregarPlanosContas(
                planoId
            );

            toastPlanoConta(
                resposta.mensagem
                || "Plano guardado com sucesso."
            );

        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
            );

        } finally {

            restaurarBotaoPlanoConta(
                botao,
                `
                    <i class="fa fa-save"></i>
                    Guardar Plano
                `
            );

        }

    }


    // ==================================================
    // CARREGAR CONTAS
    // ==================================================

    async function carregarContasPlano() {

        if (!planoAtualId) {
            return;
        }

        mostrarCarregamentoContas(
            true
        );

        try {

            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL
                    + "?acao=listar_contas&plano_id="
                    + encodeURIComponent(
                        planoAtualId
                    )
                );

            contasContabilisticas =
                resposta.data || [];

            atualizarResumoContas();

            aplicarFiltrosContas();

        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
            );

        } finally {

            mostrarCarregamentoContas(
                false
            );

        }

    }


    // ==================================================
    // FILTROS
    // ==================================================

    function aplicarFiltrosContas() {

        if (!planoAtualId) {
            return;
        }

        const pesquisa =
            normalizarTextoPlanoConta(
                $("#pesquisaConta").val()
            );

        const grupo =
            String(
                $("#filtroGrupoConta").val()
                || ""
            ).toUpperCase();

        const tipo =
            String(
                $("#filtroTipoConta").val()
                || ""
            ).toUpperCase();

        const statusId =
            String(
                $("#filtroStatusConta").val()
                || ""
            );

        const filtradas =
            contasContabilisticas.filter(
                function (conta) {

                    const correspondePesquisa =
                        !pesquisa
                        || normalizarTextoPlanoConta(
                            conta.codigo
                            + " "
                            + conta.nome
                        ).includes(
                            pesquisa
                        );

                    const correspondeGrupo =
                        !grupo
                        || String(
                            conta.grupo
                            || ""
                        ).toUpperCase()
                        === grupo;

                    const correspondeTipo =
                        !tipo
                        || String(
                            conta.tipo
                            || ""
                        ).toUpperCase()
                        === tipo;

                    const correspondeStatus =
                        statusId === ""
                        || String(
                            conta.status_id
                        ) === statusId;

                    return (
                        correspondePesquisa
                        && correspondeGrupo
                        && correspondeTipo
                        && correspondeStatus
                    );

                }
            );

        const arvore =
            construirArvoreLocal(
                filtradas
            );

        renderizarArvoreContas(
            arvore
        );

    }


    // ==================================================
    // CONSTRUIR ÁRVORE
    // ==================================================

    function construirArvoreLocal(
        contas
    ) {

        const mapa = new Map();

        contas.forEach(
            function (conta) {

                mapa.set(
                    Number(conta.id),
                    {
                        ...conta,
                        filhas: []
                    }
                );

            }
        );

        const arvore = [];

        mapa.forEach(
            function (conta) {

                const paiId =
                    Number(
                        conta.conta_pai_id
                        || 0
                    );

                if (
                    paiId > 0
                    && mapa.has(paiId)
                ) {

                    mapa
                        .get(paiId)
                        .filhas
                        .push(conta);

                } else {

                    arvore.push(conta);

                }

            }
        );

        ordenarArvoreContas(
            arvore
        );

        return arvore;
    }


    function ordenarArvoreContas(
        contas
    ) {

        contas.sort(
            function (a, b) {

                return compararCodigosConta(
                    a.codigo,
                    b.codigo
                );

            }
        );

        contas.forEach(
            function (conta) {

                if (
                    Array.isArray(
                        conta.filhas
                    )
                ) {

                    ordenarArvoreContas(
                        conta.filhas
                    );

                }

            }
        );

    }


    // ==================================================
    // RENDERIZAR ÁRVORE
    // ==================================================

    function renderizarArvoreContas(
        arvore
    ) {

        const container =
            $("#arvorePlanoContas");

        container.empty();

        if (!planoAtualId) {

            mostrarMensagemPlano(
                "Seleccione um plano de contas para visualizar a estrutura."
            );

            return;
        }

        if (
            !Array.isArray(arvore)
            || arvore.length === 0
        ) {

            mostrarMensagemPlano(
                "Este plano ainda não possui contas contabilísticas."
            );

            return;
        }

        $("#mensagemPlanoVazio")
            .hide();

        arvore.forEach(
            function (conta) {

                container.append(
                    criarHtmlContaArvore(
                        conta,
                        0
                    )
                );

            }
        );

    }


    function criarHtmlContaArvore(
        conta,
        nivelVisual
    ) {

        const possuiFilhas =
            Array.isArray(
                conta.filhas
            )
            && conta.filhas.length > 0;

        const tipo =
            String(
                conta.tipo
                || ""
            ).toUpperCase();

        const statusNome =
            String(
                conta.status_nome
                || ""
            );

        const contaActiva =
            normalizarTextoPlanoConta(
                statusNome
            ) === "activa";

        const contaInactiva =
            normalizarTextoPlanoConta(
                statusNome
            ) === "inactiva";

        const contaBloqueada =
            normalizarTextoPlanoConta(
                statusNome
            ) === "bloqueada";

        const grupo =
            formatarGrupoConta(
                conta.grupo
            );

        const natureza =
            formatarNaturezaConta(
                conta.natureza
            );

        const padding =
            nivelVisual * 26;

        let filhosHtml = "";

        if (possuiFilhas) {

            conta.filhas.forEach(
                function (filha) {

                    filhosHtml +=
                        criarHtmlContaArvore(
                            filha,
                            nivelVisual + 1
                        );

                }
            );

        }

        const botaoFilha =
            tipo === "SINTETICA"
                ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-success btn-nova-conta-filha"
                        data-id="${conta.id}"
                        title="Adicionar conta filha">

                        <i class="fa fa-plus"></i>

                    </button>
                `
                : "";

        return `
            <div
                class="conta-arvore-item ${contaActiva
                ? ""
                : "conta-inativa"
            }"
                data-id="${conta.id}">

                <div
                    class="conta-arvore-linha"
                    style="padding-left:${10 + padding
            }px;">

                    <span
                        class="conta-arvore-toggle"
                        data-id="${conta.id}">

                        ${possuiFilhas
                ? `
                                    <i class="fa fa-minus-square"></i>
                                `
                : `
                                    <i class="fa fa-circle-o text-muted"></i>
                                `
            }

                    </span>

                    <div class="conta-arvore-identificacao">

                        <div>

                            <span class="conta-arvore-codigo">
                                ${escaparHtmlPlanoConta(
                conta.codigo
            )}
                            </span>

                            <span class="conta-arvore-nome">
                                ${escaparHtmlPlanoConta(
                conta.nome
            )}
                            </span>

                            <span
                                class="badge ml-2 ${tipo === "SINTETICA"
                ? "badge-conta-sintetica"
                : "badge-conta-analitica"
            }">

                                ${tipo === "SINTETICA"
                ? "Sintética"
                : "Analítica"
            }

                            </span>

                            ${contaActiva
                ? ""
                : `
            <span
                class="badge ${contaBloqueada
                    ? "badge-warning"
                    : "badge-danger"
                } ml-1">

                ${escaparHtmlPlanoConta(
                    statusNome
                )}

            </span>
        `
            }

                        </div>

                        <div class="conta-arvore-detalhes">

                            ${escaparHtmlPlanoConta(
                grupo
            )}

                            ·

                            ${escaparHtmlPlanoConta(
                natureza
            )}

                            ·

                            Nível ${Number(
                conta.nivel
                || 1
            )}

                            ${Number(
                conta.aceita_lancamento
                || 0
            ) === 1
                ? " · Aceita lançamentos"
                : ""
            }

                            ${Number(
                conta.exige_centro_custo
                || 0
            ) === 1
                ? " · Exige centro de custo"
                : ""
            }

                        </div>

                    </div>

                    <div class="conta-arvore-acoes">

                        ${botaoFilha}

                        <button
                            type="button"
                            class="btn btn-sm btn-info btn-editar-conta"
                            data-id="${conta.id}"
                            title="Editar conta">

                            <i class="fa fa-edit"></i>

                        </button>

                        <button
                            type="button"
                            class="btn btn-sm btn-warning btn-status-conta"
                            data-id="${conta.id}"
                            data-status-id="${conta.status_id}"
                            title="Alterar status">

                            <i class="fa fa-exchange"></i>

                        </button>

                        <button
                            type="button"
                            class="btn btn-sm btn-danger btn-eliminar-conta"
                            data-id="${conta.id}"
                            title="Eliminar conta">

                            <i class="fa fa-trash"></i>

                        </button>

                    </div>

                </div>

                ${possuiFilhas
                ? `
                            <div
                                class="conta-arvore-filhas"
                                id="filhasConta${conta.id}">

                                ${filhosHtml}

                            </div>
                        `
                : ""
            }

            </div>
        `;

    }


    // ==================================================
    // NOVA CONTA
    // ==================================================

    async function abrirNovaConta(
        contaPaiId = null
    ) {

        if (!planoAtualId) {

            mostrarAvisoPlanoConta(
                "Seleccione primeiro um plano de contas."
            );

            return;
        }

        limparFormularioConta();

        $("#contaPlanoId")
            .val(
                planoAtualId
            );

        await preencherContasSuperiores(
            contaPaiId
        );

        $("#contaTipo")
            .val("SINTETICA");

        const statusActivoConta =
            obterStatusPorNome(
                statusContas,
                "Activa"
            );

        $("#contaStatusId").val(
            statusActivoConta
                ? String(statusActivoConta.id)
                : ""
        );

        $("#contaIntegracaoCodigo")
            .val("")
            .trigger("change");

        $("#contaFluxoCaixaCategoria")
            .val("NENHUM");

        $("#contaFluxoCaixaDescricao")
            .val("");

        atualizarConfiguracaoTipoConta();

        $("#tituloModalConta")
            .text(
                contaPaiId
                    ? "Nova Conta Filha"
                    : "Nova Conta Contabilística"
            );

        $("#modalContaContabil")
            .modal("show");

    }


    // ==================================================
    // EDITAR CONTA
    // ==================================================

    async function editarConta(
        contaId
    ) {

        try {

            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL
                    + "?acao=buscar_conta&id="
                    + encodeURIComponent(
                        contaId
                    )
                );

            const conta =
                resposta.data;

            limparFormularioConta();

            $("#contaId")
                .val(
                    conta.id
                );

            $("#contaPlanoId")
                .val(
                    conta.plano_contas_id
                );

            await preencherContasSuperiores(
                conta.conta_pai_id,
                conta.id
            );

            $("#contaCodigo")
                .val(
                    conta.codigo
                    || ""
                );

            $("#contaNome")
                .val(
                    conta.nome
                    || ""
                );

            $("#contaTipo")
                .val(
                    conta.tipo
                    || "ANALITICA"
                );

            $("#contaNatureza")
                .val(
                    conta.natureza
                    || ""
                );

            $("#contaGrupo")
                .val(
                    conta.grupo
                    || ""
                );

            $("#contaStatusId")
                .val(
                    String(
                        conta.status_id
                        || ""
                    )
                );

            $("#contaDescricao")
                .val(
                    conta.descricao
                    || ""
                );

            $("#contaAceitaLancamento")
                .prop(
                    "checked",
                    Number(
                        conta.aceita_lancamento
                        || 0
                    ) === 1
                );

            $("#contaExigeCentroCusto")
                .prop(
                    "checked",
                    Number(
                        conta.exige_centro_custo
                        || 0
                    ) === 1
                );

            $("#contaExigeEntidade")
                .prop(
                    "checked",
                    Number(
                        conta.exige_entidade
                        || 0
                    ) === 1
                );

            $("#contaCaixa")
                .prop(
                    "checked",
                    Number(
                        conta.conta_caixa
                        || 0
                    ) === 1
                );

            $("#contaBanco")
                .prop(
                    "checked",
                    Number(
                        conta.conta_banco
                        || 0
                    ) === 1
                );

            $("#contaCliente")
                .prop(
                    "checked",
                    Number(
                        conta.conta_cliente
                        || 0
                    ) === 1
                );

            $("#contaFornecedor")
                .prop(
                    "checked",
                    Number(
                        conta.conta_fornecedor
                        || 0
                    ) === 1
                );

            $("#contaImposto")
                .prop(
                    "checked",
                    Number(
                        conta.conta_imposto
                        || 0
                    ) === 1
                );

            $("#contaIntegracaoCodigo")
                .val(
                    conta.integracao_codigo || ""
                )
                .trigger("change");

            $("#contaFluxoCaixaCategoria")
                .val(
                    conta.fluxo_caixa_categoria
                    || "NENHUM"
                );

            $("#contaFluxoCaixaDescricao")
                .val(
                    conta.fluxo_caixa_descricao
                    || ""
                );

            atualizarConfiguracaoTipoConta();

            $("#tituloModalConta")
                .text(
                    "Editar Conta Contabilística"
                );

            $("#modalContaContabil")
                .modal("show");

        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
            );

        }

    }


    // ==================================================
    // CONTAS SUPERIORES
    // ==================================================

    async function preencherContasSuperiores(
        selecionarId = null,
        ignorarId = null
    ) {

        const select =
            $("#contaPaiId");

        select.html(`
            <option value="">
                Conta de nível principal
            </option>
        `);

        contasContabilisticas
            .filter(
                function (conta) {

                    return (
                        String(
                            conta.tipo
                            || ""
                        ).toUpperCase()
                        === "SINTETICA"
                        && normalizarTextoPlanoConta(
                            conta.status_nome
                        ) === "activa"
                        && Number(
                            conta.id
                        ) !== Number(
                            ignorarId
                        )
                    );

                }
            )
            .sort(
                function (a, b) {

                    return compararCodigosConta(
                        a.codigo,
                        b.codigo
                    );

                }
            )
            .forEach(
                function (conta) {

                    const prefixo =
                        "— ".repeat(
                            Math.max(
                                0,
                                Number(
                                    conta.nivel
                                    || 1
                                ) - 1
                            )
                        );

                    select.append(`
                        <option value="${conta.id}">
                            ${escaparHtmlPlanoConta(
                        prefixo
                        + conta.codigo
                        + " - "
                        + conta.nome
                    )}
                        </option>
                    `);

                }
            );

        if (selecionarId) {

            select.val(
                String(selecionarId)
            );

        }

    }


    // ==================================================
    // TIPO DA CONTA
    // ==================================================

    function atualizarConfiguracaoTipoConta() {

        const tipo =
            String(
                $("#contaTipo").val()
                || "SINTETICA"
            ).toUpperCase();

        const aceitaLancamento =
            $("#contaAceitaLancamento");

        if (tipo === "SINTETICA") {

            aceitaLancamento
                .prop(
                    "checked",
                    false
                )
                .prop(
                    "disabled",
                    true
                );

        } else {

            aceitaLancamento
                .prop(
                    "disabled",
                    false
                );

            if (
                !$("#contaId").val()
            ) {

                aceitaLancamento
                    .prop(
                        "checked",
                        true
                    );

            }

        }

    }


    // ==================================================
    // GUARDAR CONTA
    // ==================================================

    async function guardarConta(
        evento
    ) {

        evento.preventDefault();

        if (!planoAtualId) {

            mostrarAvisoPlanoConta(
                "Seleccione um plano de contas."
            );

            return;
        }

        const formulario =
            evento.currentTarget;

        // ==================================================
        // VALIDAR CLASSIFICAÇÃO DFC
        // ==================================================

        const categoriaFluxo =
            String(
                $("#contaFluxoCaixaCategoria")
                    .val()
                || "NENHUM"
            );

        const descricaoFluxo =
            String(
                $("#contaFluxoCaixaDescricao")
                    .val()
                || ""
            ).trim();


        if (
            categoriaFluxo !== "NENHUM"
            && descricaoFluxo === ""
        ) {

            mostrarAvisoPlanoConta(
                "Informe a descrição que será apresentada na Demonstração de Fluxos de Caixa."
            );

            return;
        }

        const botao =
            $("#btnGuardarConta");

        bloquearBotaoPlanoConta(
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
                "guardar_conta"
            );

            dados.set(
                "plano_contas_id",
                String(planoAtualId)
            );

            dados.set(
                "status_id",
                String(
                    $("#contaStatusId").val()
                    || ""
                )
            );

            preencherCheckboxFormData(
                dados,
                "aceita_lancamento",
                "#contaAceitaLancamento"
            );

            preencherCheckboxFormData(
                dados,
                "exige_centro_custo",
                "#contaExigeCentroCusto"
            );

            preencherCheckboxFormData(
                dados,
                "exige_entidade",
                "#contaExigeEntidade"
            );

            preencherCheckboxFormData(
                dados,
                "conta_caixa",
                "#contaCaixa"
            );

            preencherCheckboxFormData(
                dados,
                "conta_banco",
                "#contaBanco"
            );

            preencherCheckboxFormData(
                dados,
                "conta_cliente",
                "#contaCliente"
            );

            preencherCheckboxFormData(
                dados,
                "conta_fornecedor",
                "#contaFornecedor"
            );

            preencherCheckboxFormData(
                dados,
                "conta_imposto",
                "#contaImposto"
            );

            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalContaContabil")
                .modal("hide");

            await carregarContasPlano();

            toastPlanoConta(
                resposta.mensagem
                || "Conta guardada com sucesso."
            );

        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
            );

        } finally {

            restaurarBotaoPlanoConta(
                botao,
                `
                    <i class="fa fa-save"></i>
                    Guardar Conta
                `
            );

        }

    }


    async function guardarStatusConta(
        evento
    ) {

        evento.preventDefault();

        const contaId =
            Number(
                $("#statusContaId").val()
                || 0
            );

        const statusId =
            Number(
                $("#novoStatusContaId").val()
                || 0
            );

        if (
            contaId <= 0
            || statusId <= 0
        ) {

            mostrarAvisoPlanoConta(
                "Seleccione um status válido."
            );

            return;
        }

        const botao =
            $("#btnGuardarStatusConta");

        bloquearBotaoPlanoConta(
            botao,
            "A actualizar..."
        );

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                "alterar_status_conta"
            );

            dados.append(
                "id",
                String(contaId)
            );

            dados.append(
                "status_id",
                String(statusId)
            );

            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalStatusConta")
                .modal("hide");

            await carregarContasPlano();

            toastPlanoConta(
                resposta.mensagem
                || "Status actualizado."
            );

        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
            );

        } finally {

            restaurarBotaoPlanoConta(
                botao,
                `
                <i class="fa fa-save"></i>
                Atualizar Status
            `
            );

        }

    }

    // ==================================================
    // ELIMINAÇÃO
    // ==================================================

    function abrirModalEliminar(
        tipo,
        id,
        mensagem
    ) {

        $("#eliminarTipo")
            .val(tipo);

        $("#eliminarId")
            .val(id);

        $("#mensagemEliminarPlanoConta")
            .text(mensagem);

        $("#modalEliminarPlanoConta")
            .modal("show");

    }


    async function confirmarEliminacao(
        evento
    ) {

        evento.preventDefault();

        const tipo =
            String(
                $("#eliminarTipo").val()
                || ""
            );

        const id =
            Number(
                $("#eliminarId").val()
                || 0
            );

        if (
            !["plano", "conta"]
                .includes(tipo)
            || id <= 0
        ) {

            mostrarErroPlanoConta(
                "Registo inválido para eliminação."
            );

            return;
        }

        const botao =
            $("#btnConfirmarEliminarPlanoConta");

        bloquearBotaoPlanoConta(
            botao,
            "A eliminar..."
        );

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                tipo === "plano"
                    ? "eliminar_plano"
                    : "eliminar_conta"
            );

            dados.append(
                "id",
                String(id)
            );

            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalEliminarPlanoConta")
                .modal("hide");

            if (tipo === "plano") {

                planoAtualId = null;

                contasContabilisticas = [];

                await carregarPlanosContas();

            } else {

                await carregarContasPlano();

            }

            toastPlanoConta(
                resposta.mensagem
                || "Registo eliminado."
            );

        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
            );

        } finally {

            restaurarBotaoPlanoConta(
                botao,
                `
                    <i class="fa fa-trash"></i>
                    Eliminar
                `
            );

        }

    }


    // ==================================================
    // RESUMO
    // ==================================================

    function atualizarResumoContas() {

        const total =
            contasContabilisticas.length;

        const sinteticas =
            contasContabilisticas.filter(
                function (conta) {

                    return String(
                        conta.tipo
                        || ""
                    ).toUpperCase()
                        === "SINTETICA";

                }
            ).length;

        const analiticas =
            contasContabilisticas.filter(
                function (conta) {

                    return String(
                        conta.tipo
                        || ""
                    ).toUpperCase()
                        === "ANALITICA";

                }
            ).length;

        const indisponiveis =
            contasContabilisticas.filter(
                function (conta) {

                    return normalizarTextoPlanoConta(
                        conta.status_nome
                    ) !== "activa";

                }
            ).length;

        $("#resumoTotalContas")
            .text(total);

        $("#resumoContasSinteticas")
            .text(sinteticas);

        $("#resumoContasAnaliticas")
            .text(analiticas);

        $("#resumoContasIndisponiveis")
            .text(indisponiveis);

    }


    function limparResumoContas() {

        $("#resumoTotalContas")
            .text("0");

        $("#resumoContasSinteticas")
            .text("0");

        $("#resumoContasAnaliticas")
            .text("0");

        $("#resumoContasIndisponiveis")
            .text("0");

    }


    // ==================================================
    // FORMULÁRIOS
    // ==================================================

    function limparFormularioPlano() {

        const formulario =
            document.getElementById(
                "formPlanoConta"
            );

        if (formulario) {
            formulario.reset();
        }

        $("#planoId").val("");

        const statusActivo =
            obterStatusPorNome(
                statusPlanos,
                "Activo"
            );

        $("#planoStatusId")
            .val(
                statusActivo
                    ? String(statusActivo.id)
                    : ""
            );

        $("#planoPadrao")
            .prop(
                "checked",
                false
            );

        $("#tituloModalPlano")
            .text(
                "Novo Plano de Contas"
            );

    }


    function limparFormularioConta() {

        const formulario =
            document.getElementById(
                "formContaContabil"
            );

        if (formulario) {
            formulario.reset();
        }

        $("#contaId").val("");

        $("#contaPlanoId").val(
            planoAtualId || ""
        );

        const statusActivoConta =
            obterStatusPorNome(
                statusContas,
                "Activa"
            );

        $("#contaStatusId").val(
            statusActivoConta
                ? String(statusActivoConta.id)
                : ""
        );

        $("#contaTipo").val(
            "SINTETICA"
        );

        $("#contaAceitaLancamento")
            .prop("checked", false);

        atualizarConfiguracaoTipoConta();
    }


    // ==================================================
    // BOTÕES E ESTADO
    // ==================================================

    function atualizarBotoesPlano() {

        const existePlano =
            Boolean(
                planoAtualId
            );


        $("#btnStatusPlano")
            .prop(
                "disabled",
                !existePlano
            );


        $("#btnEditarPlano")
            .prop(
                "disabled",
                !existePlano
            );


        $("#btnEliminarPlano")
            .prop(
                "disabled",
                !existePlano
            );


        $("#btnNovaConta")
            .prop(
                "disabled",
                !existePlano
            );


        $("#btnImportarPgc")
            .prop(
                "disabled",
                !existePlano
            );
    }

    function mostrarCarregamentoContas(
        mostrar
    ) {

        $("#planoContasCarregando")
            .toggle(
                Boolean(mostrar)
            );

    }


    function mostrarMensagemPlano(
        mensagem
    ) {

        $("#arvorePlanoContas")
            .empty();

        $("#mensagemPlanoVazio")
            .text(mensagem)
            .show();

    }


    // ==================================================
    // FORMATADORES
    // ==================================================

    function formatarGrupoConta(
        grupo
    ) {

        const grupos = {

            ATIVO:
                "Ativo",

            PASSIVO:
                "Passivo",

            CAPITAL_PROPRIO:
                "Capital Próprio",

            RENDIMENTO:
                "Rendimento",

            GASTO:
                "Gasto",

            RESULTADO:
                "Resultado",

            EXTRAPATRIMONIAL:
                "Extrapatrimonial"

        };

        return grupos[
            String(
                grupo || ""
            ).toUpperCase()
        ] || grupo || "-";

    }


    function formatarNaturezaConta(
        natureza
    ) {

        return String(
            natureza || ""
        ).toUpperCase()
            === "DEVEDORA"
            ? "Natureza devedora"
            : "Natureza credora";

    }


    function compararCodigosConta(
        codigoA,
        codigoB
    ) {

        return String(
            codigoA || ""
        ).localeCompare(
            String(
                codigoB || ""
            ),
            "pt",
            {
                numeric:
                    true,

                sensitivity:
                    "base"
            }
        );

    }

    async function guardarStatusPlano(
        evento
    ) {

        evento.preventDefault();

        const planoId =
            Number(
                $("#statusPlanoId").val()
                || 0
            );

        const statusId =
            Number(
                $("#novoStatusPlanoId").val()
                || 0
            );

        if (
            planoId <= 0
            || statusId <= 0
        ) {

            mostrarAvisoPlanoConta(
                "Seleccione um status válido."
            );

            return;
        }

        const botao =
            $("#btnGuardarStatusPlano");

        bloquearBotaoPlanoConta(
            botao,
            "A actualizar..."
        );

        try {

            const dados =
                new FormData();

            dados.append(
                "acao",
                "alterar_status_plano"
            );

            dados.append(
                "id",
                String(planoId)
            );

            dados.append(
                "status_id",
                String(statusId)
            );

            const resposta =
                await requisicaoPlanoConta(
                    PLANO_CONTA_URL,
                    {
                        method:
                            "POST",

                        body:
                            dados
                    }
                );

            $("#modalStatusPlano")
                .modal("hide");

            await carregarPlanosContas(
                planoId
            );

            toastPlanoConta(
                resposta.mensagem
                || "Status actualizado."
            );

        } catch (erro) {

            mostrarErroPlanoConta(
                erro.message
            );

        } finally {

            restaurarBotaoPlanoConta(
                botao,
                `
                <i class="fa fa-save"></i>
                Atualizar Status
            `
            );

        }

    }


    function normalizarTextoPlanoConta(
        texto
    ) {

        return String(
            texto || ""
        )
            .normalize("NFD")
            .replace(
                /[\u0300-\u036f]/g,
                ""
            )
            .toLowerCase()
            .trim();

    }


    function escaparHtmlPlanoConta(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }


    function dataHojePlanoConta() {

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


    // ==================================================
    // CHECKBOXES
    // ==================================================

    function preencherCheckboxFormData(
        formData,
        nome,
        seletor
    ) {

        formData.set(
            nome,
            $(seletor).is(":checked")
                ? "1"
                : "0"
        );

    }


    // ==================================================
    // MENSAGENS
    // ==================================================

    function mostrarErroPlanoConta(
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


    function mostrarAvisoPlanoConta(
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


    function toastPlanoConta(
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
    // BOTÕES
    // ==================================================

    function bloquearBotaoPlanoConta(
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


    function restaurarBotaoPlanoConta(
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

})(jQuery);