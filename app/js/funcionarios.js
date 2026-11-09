"use strict";

(function ($) {

    // ==================================================
    // CONFIGURAÇÃO
    // ==================================================

    const FUNCIONARIOS_URL =
        "/erpplus/app/ajax/funcionarios_ajax.php";

    let funcionariosCache = [];

    let filiaisCache = [];

    let departamentosCache = [];

    let cargosCache = [];

    let horariosCache = [];

    let tiposVinculoCache = [];

    let sexosCache = [];

    let estadosCivisCache = [];

    let tabelaFuncionarios = null;


    let funcionarioImportacaoToken = '';
    let funcionarioImportacaoDados = null;
    let funcionarioImportacaoProcessando = false;



    // ==================================================
    // INICIALIZAÇÃO
    // ==================================================

    $(document).ready(function () {

        if (!paginaFuncionariosExiste()) {
            return;
        }

        configurarEventosFuncionarios();

        carregarDadosIniciaisFuncionarios()
            .catch(function (erro) {

                console.error(
                    "Erro ao iniciar a gestão de funcionários:",
                    erro
                );

            });

    });


    // ==================================================
    // VERIFICAR PÁGINA
    // ==================================================

    function paginaFuncionariosExiste() {

        return (
            $("#funcionariosTable").length > 0
            && $("#formFuncionario").length > 0
            && $("#modalFuncionario").length > 0
        );

    }


    // ==================================================
    // EVENTOS
    // ==================================================

    function configurarEventosFuncionarios() {

        $("#formFuncionario").on(
            "input change",
            "input, select, textarea",
            function () {

                const campo =
                    this;


                campo.classList.remove(
                    "is-invalid"
                );


                const proximo =
                    campo.nextElementSibling;


                if (
                    proximo
                    && proximo.classList.contains(
                        "funcionario-validacao"
                    )
                ) {

                    proximo.remove();
                }
            }
        );

        document.getElementById('funcionarioBtnImportar')?.addEventListener('click', abrirImportacaoFuncionario);
        document.getElementById('funcionarioBtnModeloExcel')?.addEventListener('click', baixarModeloExcelFuncionario);
        document.getElementById('funcionarioBtnValidarExcel')?.addEventListener('click', validarImportacaoFuncionario);
        document.getElementById('funcionarioBtnConfirmarImportacao')?.addEventListener('click', confirmarImportacaoFuncionario);
        document.getElementById('funcionarioBtnVoltarImportacao')?.addEventListener('click', voltarImportacaoFuncionario);
        document.getElementById('funcionarioFicheiroExcel')?.addEventListener('change', alterarFicheiroExcelFuncionario);


        $("#btnNovoFuncionario").on(
            "click",
            function () {

                abrirModalNovoFuncionario();

            }
        );

        $("#formFuncionario").on(
            "submit",
            function (evento) {

                evento.preventDefault();

                guardarFuncionario()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao guardar funcionário:",
                            erro
                        );

                    });

            }
        );

        $("#funcionarioNumero").on(
            "input",
            function () {

                const valor =
                    String(
                        $(this).val() || ""
                    )
                        .toUpperCase()
                        .replace(
                            /[^A-Z0-9._-]/g,
                            ""
                        );

                $(this).val(valor);

            }
        );

        $("#funcionarioPrimeiroNome, #funcionarioApelido").on(
            "input",
            function () {

                atualizarNomeCompletoFuncionario();

            }
        );

        $("#funcionarioDepartamentoId").on(
            "change",
            function () {

                filtrarCargosPorDepartamento();

            }
        );

        $("#funcionarioStatusId").on(
            "change",
            function () {

                atualizarCamposDesligamentoFuncionario();

            }
        );

        $("#funcionarioDataAdmissao, #funcionarioDataDesligamento").on(
            "change",
            function () {

                validarPeriodoFuncionario();

            }
        );

        $("#funcionarioFotografiaArquivo")
            .on(
                "change",
                function () {

                    const arquivo =
                        this.files
                            && this.files.length
                            ? this.files[0]
                            : null;

                    if (!arquivo) {

                        $("#funcionarioFotografiaPreview")
                            .attr(
                                "src",
                                ""
                            );

                        $("#funcionarioFotografiaPreviewContainer")
                            .hide();

                        return;
                    }


                    const tiposPermitidos = [
                        "image/jpeg",
                        "image/png",
                        "image/webp"
                    ];


                    if (
                        !tiposPermitidos.includes(
                            arquivo.type
                        )
                    ) {

                        this.value = "";

                        mostrarErroFuncionario(
                            "A fotografia deve ser JPG, PNG ou WEBP."
                        );

                        return;
                    }


                    const limite =
                        5 * 1024 * 1024;

                    if (
                        arquivo.size > limite
                    ) {

                        this.value = "";

                        mostrarErroFuncionario(
                            "A fotografia não pode exceder 5 MB."
                        );

                        return;
                    }


                    const url =
                        URL.createObjectURL(
                            arquivo
                        );


                    $("#funcionarioFotografiaPreview")
                        .attr(
                            "src",
                            url
                        );


                    $("#funcionarioFotografiaPreviewContainer")
                        .show();
                }
            );

        $("#funcionarioDataEmissaoBi, #funcionarioDataValidadeBi").on(
            "change",
            function () {

                validarDatasBiFuncionario();

            }
        );

        $("#btnFiltrarFuncionarios").on(
            "click",
            function () {

                carregarFuncionariosFiltrados()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao filtrar funcionários:",
                            erro
                        );

                    });

            }
        );

        $("#btnLimparFiltrosFuncionarios").on(
            "click",
            function () {

                limparFiltrosFuncionarios();

                carregarFuncionariosFiltrados()
                    .catch(function (erro) {

                        console.error(
                            "Erro ao limpar filtros:",
                            erro
                        );

                    });

            }
        );

        $("#filtroFuncionarioPesquisa").on(
            "keydown",
            function (evento) {

                if (evento.key === "Enter") {

                    evento.preventDefault();

                    carregarFuncionariosFiltrados()
                        .catch(function (erro) {

                            console.error(
                                "Erro ao pesquisar funcionário:",
                                erro
                            );

                        });

                }

            }
        );

        $("#funcionariosTable").on(
            "click",
            ".btn-ver-funcionario",
            function () {

                const funcionarioId =
                    Number(
                        $(this).data("id") || 0
                    );

                mostrarDetalhesFuncionario(
                    funcionarioId
                ).catch(
                    function (erro) {

                        mostrarErroFuncionario(
                            erro.message
                        );
                    }
                );
            }
        );

        $("#funcionariosTable").on(
            "click",
            ".btn-editar-funcionario",
            function () {

                const funcionarioId =
                    Number(
                        $(this).data("id") || 0
                    );

                abrirModalEditarFuncionario(
                    funcionarioId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao carregar funcionário:",
                            erro
                        );

                    });

            }
        );

        $("#funcionariosTable").on(
            "click",
            ".btn-eliminar-funcionario",
            function () {

                const funcionarioId =
                    Number(
                        $(this).data("id") || 0
                    );

                confirmarEliminarFuncionario(
                    funcionarioId
                )
                    .catch(function (erro) {

                        console.error(
                            "Erro ao eliminar funcionário:",
                            erro
                        );

                    });

            }
        );

        $("#modalFuncionario").on(
            "hidden.bs.modal",
            function () {

                limparFormularioFuncionario();

            }
        );

    }


    function abrirImportacaoFuncionario() {
        funcionarioImportacaoToken = '';
        funcionarioImportacaoDados = null;
        funcionarioImportacaoProcessando = false;

        const ficheiro = document.getElementById('funcionarioFicheiroExcel');
        if (ficheiro) ficheiro.value = '';

        const label = document.querySelector('label[for="funcionarioFicheiroExcel"]');
        if (label) label.textContent = 'Seleccione o ficheiro';

        document.getElementById('funcionarioImportacaoPasso1')?.classList.remove('d-none');
        document.getElementById('funcionarioImportacaoPasso2')?.classList.add('d-none');
        document.getElementById('funcionarioBtnValidarExcel')?.classList.remove('d-none');
        document.getElementById('funcionarioBtnConfirmarImportacao')?.classList.add('d-none');
        document.getElementById('funcionarioBtnVoltarImportacao')?.classList.add('d-none');

        esconderErroImportacaoFuncionario();
        esconderSucessoImportacaoFuncionario();

        $('#funcionarioModalImportar').modal('show');
    }

    function alterarFicheiroExcelFuncionario(evento) {
        const ficheiro = evento.target.files?.[0];
        const label = document.querySelector('label[for="funcionarioFicheiroExcel"]');
        if (label) label.textContent = ficheiro ? ficheiro.name : 'Seleccione o ficheiro';
    }

    function baixarModeloExcelFuncionario() {
        window.location.href = FUNCIONARIOS_URL + '?acao=baixar_modelo_excel&_=' + Date.now();
    }

    async function validarImportacaoFuncionario() {
        if (funcionarioImportacaoProcessando) return;
        esconderErroImportacaoFuncionario();

        const input = document.getElementById('funcionarioFicheiroExcel');
        const ficheiro = input?.files?.[0];

        if (!ficheiro) {
            mostrarErroImportacaoFuncionario('Seleccione um ficheiro Excel antes de continuar.');
            return;
        }

        const botao = document.getElementById('funcionarioBtnValidarExcel');
        const htmlOriginal = botao?.innerHTML || '';

        try {
            funcionarioImportacaoProcessando = true;
            bloquearBotaoFuncionario(botao, 'A validar...');

            const dados = new FormData();
            dados.append('ficheiro', ficheiro);

            const resposta = await requisicaoFuncionario('validar_importacao', { method: 'POST', body: dados }); // Ajustado para 'body: dados'

            // Alterado de .dados para .data para corresponder ao padrão do ERP
            funcionarioImportacaoDados = resposta.data || {};
            funcionarioImportacaoToken = String(funcionarioImportacaoDados.token || '');

            if (!funcionarioImportacaoToken) {
                throw new Error('O servidor não devolveu o token da importação.');
            }

            renderizarResultadoImportacaoFuncionario(funcionarioImportacaoDados);

            document.getElementById('funcionarioImportacaoPasso1')?.classList.add('d-none');
            document.getElementById('funcionarioImportacaoPasso2')?.classList.remove('d-none');
            document.getElementById('funcionarioBtnValidarExcel')?.classList.add('d-none');
            document.getElementById('funcionarioBtnVoltarImportacao')?.classList.remove('d-none');

            const totalValidos = Number(funcionarioImportacaoDados.total_validas || 0);
            if (totalValidos > 0) {
                document.getElementById('funcionarioBtnConfirmarImportacao')?.classList.remove('d-none');
            } else {
                document.getElementById('funcionarioBtnConfirmarImportacao')?.classList.add('d-none');
            }

        } catch (erro) {
            mostrarErroImportacaoFuncionario(erro.message);
        } finally {
            funcionarioImportacaoProcessando = false;
            desbloquearBotaoFuncionario(botao, htmlOriginal);
        }
    }

    function bloquearBotaoFuncionario(botao, texto) {
        if (!botao) return;
        botao.disabled = true;
        botao.dataset.htmlOriginal = botao.innerHTML;
        botao.innerHTML = `<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>${texto}`;
    }

    function desbloquearBotaoFuncionario(botao, htmlOriginal) {
        if (!botao) return;
        botao.disabled = false;
        botao.innerHTML = htmlOriginal || botao.dataset.htmlOriginal || '';
    }

    async function requisicaoFuncionario(acao, opcoes = {}) {
        const url = FUNCIONARIOS_URL + '?acao=' + acao;
        return await requisicaoFuncionarios(url, opcoes);
    }

    async function carregarFuncionarios() {
        return await carregarFuncionariosFiltrados();
    }

    function renderizarResultadoImportacaoFuncionario(dados) {
        const validas = Array.isArray(dados.validas) ? dados.validas : [];
        const erros = Array.isArray(dados.erros) ? dados.erros : [];

        const tbodyValidos = document.getElementById('funcionarioImportacaoValidos');
        if (tbodyValidos) {
            tbodyValidos.innerHTML = validas.length === 0
                ? '<tr><td colspan="13" class="text-center text-muted">Nenhum funcionário válido.</td></tr>'
                : validas.map(item => {
                    const p = item.preview || {};
                    const d = item.dados || {};
                    return `
                        <tr>
                            <td>${item.linha}</td>
                            <td><strong>${escaparHtml(p.numero_funcionario || d.numero_funcionario || '-')}</strong></td>
                            <td>${escaparHtml(p.nome_completo || d.nome_completo || '-')}</td>
                            <td>${escaparHtml(p.sexo || d.sexo || '-')}</td>
                            <td>${escaparHtml(p.filial_nome || d.filial_nome || '-')}</td>
                            <td>${escaparHtml(p.departamento_nome || d.departamento_nome || '-')}</td>
                            <td>${escaparHtml(p.cargo_nome || d.cargo_nome || '-')}</td>
                            <td>${escaparHtml(p.nuit || d.nuit || '-')}</td>
                            <td>${escaparHtml(p.numero_bi || d.numero_bi || '-')}</td>
                            <td>${escaparHtml(p.telefone || d.telefone_principal || '-')}</td>
                            <td>${escaparHtml(p.email || d.email_profissional || '-')}</td>
                            <td>${escaparHtml(p.data_admissao || d.data_admissao || '-')}</td>
                            <td class="text-right">${escaparHtml(p.salario_base || d.salario_base || '0.00')}</td>
                            <td>${escaparHtml(p.tipo_vinculo || d.tipo_vinculo || '-')}</td>
                        </tr>
                    `;
                }).join('');
        }

        const tbodyErros = document.getElementById('funcionarioImportacaoErros');
        const containerErros = document.getElementById('funcionarioImportacaoErrosContainer');
        if (containerErros && tbodyErros) {
            containerErros.classList.toggle('d-none', erros.length === 0);
            tbodyErros.innerHTML = erros.map(item => `
                <tr>
                    <td>${item.linha}</td>
                    <td>${escaparHtml(item.sku || '-')}</td>
                    <td>${escaparHtml(item.nome || '-')}</td>
                    <td class="text-danger">${(Array.isArray(item.erro) ? item.erro : [item.erro]).join('<br>')}</td>
                </tr>
            `).join('');
        }
    }

    async function confirmarImportacaoFuncionario() {
        if (funcionarioImportacaoProcessando || !funcionarioImportacaoToken) return;

        if (typeof Swal !== 'undefined') {
            const confirmacao = await Swal.fire({
                icon: 'question',
                title: 'Confirmar importação?',
                text: 'Deseja prosseguir com o registo dos funcionários válidos?',
                showCancelButton: true,
                confirmButtonText: 'Sim, importar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirmacao.isConfirmed) return;
        }

        const botao = document.getElementById('funcionarioBtnConfirmarImportacao');
        const htmlOriginal = botao?.innerHTML || '';

        try {
            funcionarioImportacaoProcessando = true;
            bloquearBotaoFuncionario(botao, 'A importar...');

            const dados = new FormData();
            dados.append('token', funcionarioImportacaoToken);

            // Certifique-se de enviar com a propriedade 'body: dados'
            const resposta = await requisicaoFuncionario('confirmar_importacao', {
                method: 'POST',
                body: dados
            });

            mostrarMensagemSucesso(resposta.mensagem || 'Importação concluída com sucesso.');
            $('#funcionarioModalImportar').modal('hide');
            await carregarFuncionarios();

        } catch (erro) {
            mostrarErroImportacaoFuncionario(erro.message);
        } finally {
            funcionarioImportacaoProcessando = false;
            desbloquearBotaoFuncionario(botao, htmlOriginal);
        }
    }

    function voltarImportacaoFuncionario() {
        funcionarioImportacaoToken = '';
        funcionarioImportacaoDados = null;
        document.getElementById('funcionarioImportacaoPasso2')?.classList.add('d-none');
        document.getElementById('funcionarioImportacaoPasso1')?.classList.remove('d-none');
        document.getElementById('funcionarioBtnConfirmarImportacao')?.classList.add('d-none');
        document.getElementById('funcionarioBtnVoltarImportacao')?.classList.add('d-none');
        document.getElementById('funcionarioBtnValidarExcel')?.classList.remove('d-none');
    }

    function mostrarErroImportacaoFuncionario(msg) {
        const el = document.getElementById('funcionarioImportacaoErro');
        if (el) { el.textContent = msg; el.classList.remove('d-none'); }
    }
    function esconderErroImportacaoFuncionario() {
        document.getElementById('funcionarioImportacaoErro')?.classList.add('d-none');
    }
    function esconderSucessoImportacaoFuncionario() {
        document.getElementById('funcionarioImportacaoSucesso')?.classList.add('d-none');
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    async function carregarDadosIniciaisFuncionarios() {

        mostrarCarregamentoFuncionarios(true);

        try {

            const resposta =
                await requisicaoFuncionarios(
                    FUNCIONARIOS_URL
                    + "?acao=dados_iniciais"
                );

            const dados =
                resposta.data || {};

            funcionariosCache =
                Array.isArray(
                    dados.funcionarios
                )
                    ? dados.funcionarios
                    : [];

            filiaisCache =
                Array.isArray(
                    dados.filiais
                )
                    ? dados.filiais
                    : [];

            departamentosCache =
                Array.isArray(
                    dados.departamentos
                )
                    ? dados.departamentos
                    : [];

            cargosCache =
                Array.isArray(
                    dados.cargos
                )
                    ? dados.cargos
                    : [];

            horariosCache =
                Array.isArray(
                    dados.horarios
                )
                    ? dados.horarios
                    : [];

            tiposVinculoCache =
                Array.isArray(
                    dados.tipos_vinculo
                )
                    ? dados.tipos_vinculo
                    : [];

            sexosCache =
                Array.isArray(
                    dados.sexos
                )
                    ? dados.sexos
                    : [];

            estadosCivisCache =
                Array.isArray(
                    dados.estados_civis
                )
                    ? dados.estados_civis
                    : [];

            preencherFiliaisFuncionarios(
                filiaisCache
            );

            preencherDepartamentosFuncionarios(
                departamentosCache
            );

            preencherCargosFuncionarios(
                cargosCache
            );

            preencherHorariosFuncionarios(
                horariosCache
            );

            preencherStatusFuncionarios(
                dados.status || []
            );

            preencherTiposVinculoFuncionarios(
                tiposVinculoCache
            );

            preencherSexosFuncionarios(
                sexosCache
            );

            preencherEstadosCivisFuncionarios(
                estadosCivisCache
            );

            preencherFiltrosFuncionarios(
                dados
            );

            renderizarTabelaFuncionarios(
                funcionariosCache
            );

            atualizarResumoFuncionarios(
                funcionariosCache
            );

        } catch (erro) {

            mostrarErroFuncionario(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoFuncionarios(false);

        }

    }


    // ==================================================
    // FILTRAR
    // ==================================================

    async function carregarFuncionariosFiltrados() {

        mostrarCarregamentoFuncionarios(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "listar"
                });

            adicionarParametroSePreenchido(
                parametros,
                "filial_id",
                $("#filtroFuncionarioFilialId").val()
            );

            adicionarParametroSePreenchido(
                parametros,
                "departamento_id",
                $("#filtroFuncionarioDepartamentoId").val()
            );

            adicionarParametroSePreenchido(
                parametros,
                "cargo_id",
                $("#filtroFuncionarioCargoId").val()
            );

            adicionarParametroSePreenchido(
                parametros,
                "status_id",
                $("#filtroFuncionarioStatusId").val()
            );

            adicionarParametroSePreenchido(
                parametros,
                "pesquisa",
                $("#filtroFuncionarioPesquisa").val()
            );

            const resposta =
                await requisicaoFuncionarios(
                    FUNCIONARIOS_URL
                    + "?"
                    + parametros.toString()
                );

            funcionariosCache =
                Array.isArray(
                    resposta.data
                )
                    ? resposta.data
                    : [];

            renderizarTabelaFuncionarios(
                funcionariosCache
            );

            atualizarResumoFuncionarios(
                funcionariosCache
            );

        } catch (erro) {

            mostrarErroFuncionario(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoFuncionarios(false);

        }

    }


    // ==================================================
    // PREENCHER SELECTS
    // ==================================================

    function preencherFiliaisFuncionarios(
        filiais
    ) {

        preencherSelectFuncionario(
            "#funcionarioFilialId",
            filiais,
            "Sem filial definida",
            function (filial) {

                return [
                    filial.codigo || "",
                    filial.nome || ""
                ]
                    .filter(Boolean)
                    .join(" - ");

            }
        );

    }


    function preencherDepartamentosFuncionarios(
        departamentos
    ) {

        preencherSelectFuncionario(
            "#funcionarioDepartamentoId",
            departamentos,
            "Sem departamento",
            function (departamento) {

                return [
                    departamento.codigo || "",
                    departamento.nome || ""
                ]
                    .filter(Boolean)
                    .join(" - ");

            }
        );

    }


    function preencherCargosFuncionarios(
        cargos
    ) {

        preencherSelectFuncionario(
            "#funcionarioCargoId",
            cargos,
            "Sem cargo definido",
            function (cargo) {

                return [
                    cargo.codigo || "",
                    cargo.nome || ""
                ]
                    .filter(Boolean)
                    .join(" - ");

            }
        );

    }


    function preencherHorariosFuncionarios(
        horarios
    ) {

        preencherSelectFuncionario(
            "#funcionarioHorarioId",
            horarios,
            "Sem horário definido",
            function (horario) {

                const periodo =
                    horario.hora_entrada
                        && horario.hora_saida
                        ? " | "
                        + formatarHoraVisivelFuncionario(
                            horario.hora_entrada
                        )
                        + " - "
                        + formatarHoraVisivelFuncionario(
                            horario.hora_saida
                        )
                        : "";

                return (
                    (horario.nome || "-")
                    + periodo
                );

            }
        );

    }


    function preencherStatusFuncionarios(
        status
    ) {

        preencherSelectFuncionario(
            "#funcionarioStatusId",
            status,
            "Selecione o estado",
            function (item) {

                return item.nome || "-";

            }
        );

        const statusAtivo =
            status.find(
                function (item) {

                    return [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        normalizarTextoFuncionario(
                            item.nome || ""
                        )
                    );

                }
            );

        if (statusAtivo) {

            $("#funcionarioStatusId")
                .data(
                    "status-ativo-id",
                    Number(statusAtivo.id)
                );

        }

    }


    function preencherTiposVinculoFuncionarios(
        tipos
    ) {

        preencherSelectComCodigoFuncionario(
            "#funcionarioTipoVinculo",
            tipos,
            "Selecione o vínculo"
        );

    }


    function preencherSexosFuncionarios(
        sexos
    ) {

        preencherSelectComCodigoFuncionario(
            "#funcionarioSexo",
            sexos,
            "Selecione"
        );

    }


    function preencherEstadosCivisFuncionarios(
        estados
    ) {

        preencherSelectComCodigoFuncionario(
            "#funcionarioEstadoCivil",
            estados,
            "Selecione"
        );

    }


    function preencherFiltrosFuncionarios(
        dados
    ) {

        preencherSelectFuncionario(
            "#filtroFuncionarioFilialId",
            dados.filiais || [],
            "Todas as filiais",
            function (item) {

                return item.nome || "-";

            }
        );

        preencherSelectFuncionario(
            "#filtroFuncionarioDepartamentoId",
            dados.departamentos || [],
            "Todos os departamentos",
            function (item) {

                return item.nome || "-";

            }
        );

        preencherSelectFuncionario(
            "#filtroFuncionarioCargoId",
            dados.cargos || [],
            "Todos os cargos",
            function (item) {

                return item.nome || "-";

            }
        );

        preencherSelectFuncionario(
            "#filtroFuncionarioStatusId",
            dados.status || [],
            "Todos os estados",
            function (item) {

                return item.nome || "-";

            }
        );

    }


    // ==================================================
    // FILTRAR CARGOS
    // ==================================================

    function filtrarCargosPorDepartamento(
        cargoSelecionado = null
    ) {

        const departamentoId =
            Number(
                $("#funcionarioDepartamentoId").val()
                || 0
            );

        const cargosFiltrados =
            departamentoId > 0
                ? cargosCache.filter(
                    function (cargo) {

                        return (
                            Number(
                                cargo.departamento_id || 0
                            ) === departamentoId
                            || Number(
                                cargo.departamento_id || 0
                            ) === 0
                        );

                    }
                )
                : cargosCache;

        preencherCargosFuncionarios(
            cargosFiltrados
        );

        if (
            cargoSelecionado !== null
            && Number(cargoSelecionado) > 0
        ) {

            $("#funcionarioCargoId")
                .val(
                    String(cargoSelecionado)
                );

        }

    }


    // ==================================================
    // TABELA
    // ==================================================

    function renderizarTabelaFuncionarios(
        funcionarios
    ) {

        destruirDataTableFuncionarios();

        const tbody =
            $("#funcionariosTable tbody");

        tbody.empty();

        if (!funcionarios.length) {

            tbody.html(`
                <tr>
                    <td
                        colspan="9"
                        class="text-center text-muted py-4">

                        Nenhum funcionário encontrado.

                    </td>
                </tr>
            `);

            return;

        }

        funcionarios.forEach(
            function (funcionario) {

                const statusNome =
                    String(
                        funcionario.status_nome || "-"
                    );

                const badgeStatus =
                    obterClasseStatusFuncionario(
                        statusNome
                    );

                const fotografia =
                    obterFotografiaFuncionario(
                        funcionario
                    );

                tbody.append(`
                    <tr>

                        <td>
                            <strong>
                                ${escaparHtmlFuncionario(
                    funcionario.numero_funcionario || "-"
                )}
                            </strong>
                        </td>

                        <td>

                            <div class="d-flex align-items-center">

                                ${fotografia}

                                <div class="ml-2">

                                    <strong>
                                        ${escaparHtmlFuncionario(
                    funcionario.nome_completo || "-"
                )}
                                    </strong>

                                    ${funcionario.email_profissional
                        ? `
                                                <div class="text-muted small">
                                                    ${escaparHtmlFuncionario(
                            funcionario.email_profissional
                        )}
                                                </div>
                                            `
                        : ""
                    }

                                </div>

                            </div>

                        </td>

                        <td>
                            ${escaparHtmlFuncionario(
                        funcionario.filial_nome || "-"
                    )}
                        </td>

                        <td>
                            ${escaparHtmlFuncionario(
                        funcionario.departamento_nome || "-"
                    )}
                        </td>

                        <td>
                            ${escaparHtmlFuncionario(
                        funcionario.cargo_nome || "-"
                    )}
                        </td>

                        <td>
                            ${escaparHtmlFuncionario(
                        formatarTipoVinculoFuncionario(
                            funcionario.tipo_vinculo
                        )
                    )}
                        </td>

                        <td>
                            ${formatarDataFuncionario(
                        funcionario.data_admissao
                    )}
                        </td>

                        <td>
                            <span class="badge ${badgeStatus}">
                                ${escaparHtmlFuncionario(
                        statusNome
                    )}
                            </span>
                        </td>

                        <td class="text-right">

                            <div class="dropdown dropdown-action">

                                <a
                                    href="#"
                                    class="action-icon dropdown-toggle"
                                    data-toggle="dropdown"
                                    aria-expanded="false">

                                    <i class="material-icons">
                                        more_vert
                                    </i>

                                </a>

                                <div class="dropdown-menu dropdown-menu-right">

                                    <button
                                        type="button"
                                        class="dropdown-item btn-ver-funcionario"
                                        data-id="${Number(
                        funcionario.id
                    )}">

                                        <i class="fa fa-eye m-r-5"></i>
                                        Ver detalhes

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item btn-editar-funcionario"
                                        data-id="${Number(
                        funcionario.id
                    )}">

                                        <i class="fa fa-pencil m-r-5"></i>
                                        Editar

                                    </button>

                                    <button
                                        type="button"
                                        class="dropdown-item text-danger btn-eliminar-funcionario"
                                        data-id="${Number(
                        funcionario.id
                    )}">

                                        <i class="fa fa-trash-o m-r-5"></i>
                                        Eliminar

                                    </button>

                                </div>

                            </div>

                        </td>

                    </tr>
                `);

            }
        );

        iniciarDataTableFuncionarios();

    }


    // ==================================================
    // DATATABLE
    // ==================================================

    function iniciarDataTableFuncionarios() {

        if (
            typeof $.fn.DataTable
            === "undefined"
        ) {
            return;
        }

        tabelaFuncionarios =
            $("#funcionariosTable")
                .DataTable({
                    pageLength:
                        10,

                    order: [
                        [
                            1,
                            "asc"
                        ]
                    ],

                    columnDefs: [
                        {
                            targets:
                                8,

                            orderable:
                                false,

                            searchable:
                                false
                        }
                    ],

                    language: {
                        search:
                            "Pesquisar:",

                        lengthMenu:
                            "Mostrar _MENU_ registos",

                        info:
                            "A mostrar _START_ a _END_ de _TOTAL_ registos",

                        infoEmpty:
                            "Nenhum registo disponível",

                        zeroRecords:
                            "Nenhum funcionário encontrado",

                        paginate: {
                            first:
                                "Primeiro",

                            last:
                                "Último",

                            next:
                                "Seguinte",

                            previous:
                                "Anterior"
                        }
                    }
                });

    }


    function destruirDataTableFuncionarios() {

        if (
            tabelaFuncionarios
            && typeof tabelaFuncionarios.destroy
            === "function"
        ) {

            tabelaFuncionarios.destroy();

            tabelaFuncionarios = null;

        }

        if (
            typeof $.fn.DataTable
            !== "undefined"
            && $.fn.DataTable.isDataTable(
                "#funcionariosTable"
            )
        ) {

            $("#funcionariosTable")
                .DataTable()
                .destroy();

        }

    }


    // ==================================================
    // RESUMO
    // ==================================================

    function atualizarResumoFuncionarios(
        funcionarios
    ) {

        const total =
            funcionarios.length;

        const ativos =
            funcionarios.filter(
                function (funcionario) {

                    return [
                        "activo",
                        "ativo",
                        "activa",
                        "ativa"
                    ].includes(
                        normalizarTextoFuncionario(
                            funcionario.status_nome || ""
                        )
                    );

                }
            ).length;

        const suspensos =
            funcionarios.filter(
                function (funcionario) {

                    return normalizarTextoFuncionario(
                        funcionario.status_nome || ""
                    ) === "suspenso";

                }
            ).length;

        const desligados =
            funcionarios.filter(
                function (funcionario) {

                    return normalizarTextoFuncionario(
                        funcionario.status_nome || ""
                    ) === "desligado";

                }
            ).length;

        definirTextoFuncionario(
            "#resumoTotalFuncionarios",
            total.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoFuncionario(
            "#resumoFuncionariosAtivos",
            ativos.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoFuncionario(
            "#resumoFuncionariosSuspensos",
            suspensos.toLocaleString(
                "pt-PT"
            )
        );

        definirTextoFuncionario(
            "#resumoFuncionariosDesligados",
            desligados.toLocaleString(
                "pt-PT"
            )
        );

    }


    // ==================================================
    // NOVO
    // ==================================================

    function abrirModalNovoFuncionario() {

        limparFormularioFuncionario();

        $("#modalFuncionarioTitulo")
            .text(
                "Novo Funcionário"
            );

        $("#btnGuardarFuncionarioTexto")
            .text(
                "Guardar Funcionário"
            );

        const statusAtivoId =
            Number(
                $("#funcionarioStatusId")
                    .data(
                        "status-ativo-id"
                    )
                || 0
            );

        if (statusAtivoId > 0) {

            $("#funcionarioStatusId")
                .val(
                    String(statusAtivoId)
                );

        }

        $("#funcionarioTipoVinculo")
            .val("CONTRATADO");

        $("#funcionarioNacionalidade")
            .val("Moçambicana");

        $("#funcionarioMoeda")
            .val("MZN");

        $("#funcionarioDataAdmissao")
            .val(
                obterDataAtualIsoFuncionario()
            );

        atualizarCamposDesligamentoFuncionario();

        $("#modalFuncionario")
            .modal("show");

        setTimeout(
            function () {

                $("#funcionarioNumero")
                    .trigger("focus");

            },
            300
        );

    }


    // ==================================================
    // EDITAR
    // ==================================================

    async function abrirModalEditarFuncionario(
        funcionarioId
    ) {

        if (funcionarioId <= 0) {

            mostrarErroFuncionario(
                "Funcionário inválido."
            );

            return;

        }

        mostrarCarregamentoFuncionarios(true);

        try {

            const parametros =
                new URLSearchParams({
                    acao:
                        "buscar",

                    id:
                        String(funcionarioId)
                });

            const resposta =
                await requisicaoFuncionarios(
                    FUNCIONARIOS_URL
                    + "?"
                    + parametros.toString()
                );

            const funcionario =
                resposta.data || {};

            limparFormularioFuncionario();

            preencherFormularioFuncionario(
                funcionario
            );

            $("#modalFuncionarioTitulo")
                .text(
                    "Editar Funcionário"
                );

            $("#btnGuardarFuncionarioTexto")
                .text(
                    "Atualizar Funcionário"
                );

            $("#modalFuncionario")
                .modal("show");

        } catch (erro) {

            mostrarErroFuncionario(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoFuncionarios(false);

        }

    }


    function preencherFormularioFuncionario(
        funcionario
    ) {

        $("#funcionarioId").val(
            funcionario.id || ""
        );

        $("#funcionarioNumero").val(
            funcionario.numero_funcionario || ""
        );

        $("#funcionarioPrimeiroNome").val(
            funcionario.primeiro_nome || ""
        );

        $("#funcionarioApelido").val(
            funcionario.apelido || ""
        );

        $("#funcionarioNomeCompleto").val(
            funcionario.nome_completo || ""
        );

        $("#funcionarioSexo").val(
            funcionario.sexo || ""
        );

        $("#funcionarioDataNascimento").val(
            funcionario.data_nascimento || ""
        );

        $("#funcionarioEstadoCivil").val(
            funcionario.estado_civil || ""
        );

        $("#funcionarioNacionalidade").val(
            funcionario.nacionalidade || ""
        );

        $("#funcionarioNaturalidade").val(
            funcionario.naturalidade || ""
        );

        $("#funcionarioNuit").val(
            funcionario.nuit || ""
        );

        $("#funcionarioNumeroDependentes")
            .val(
                Number(
                    funcionario.numero_dependentes
                    ?? 0
                )
            );


        $("#funcionarioResidenteFiscal")
            .val(
                String(
                    Number(
                        funcionario.residente_fiscal
                        ?? 1
                    )
                )
            );

        $("#funcionarioNumeroBi").val(
            funcionario.numero_bi || ""
        );

        $("#funcionarioDataEmissaoBi").val(
            funcionario.data_emissao_bi || ""
        );

        $("#funcionarioDataValidadeBi").val(
            funcionario.data_validade_bi || ""
        );

        $("#funcionarioEntidadeEmissoraBi").val(
            funcionario.entidade_emissora_bi || ""
        );

        $("#funcionarioNumeroInss").val(
            funcionario.numero_inss || ""
        );

        $("#funcionarioEmailPessoal").val(
            funcionario.email_pessoal || ""
        );

        $("#funcionarioEmailProfissional").val(
            funcionario.email_profissional || ""
        );

        $("#funcionarioTelefonePrincipal").val(
            funcionario.telefone_principal || ""
        );

        $("#funcionarioTelefoneAlternativo").val(
            funcionario.telefone_alternativo || ""
        );

        $("#funcionarioEndereco").val(
            funcionario.endereco || ""
        );

        $("#funcionarioProvincia").val(
            funcionario.provincia || ""
        );

        $("#funcionarioDistrito").val(
            funcionario.distrito || ""
        );

        $("#funcionarioCidade").val(
            funcionario.cidade || ""
        );

        $("#funcionarioBairro").val(
            funcionario.bairro || ""
        );

        $("#funcionarioContactoEmergenciaNome").val(
            funcionario.contacto_emergencia_nome || ""
        );

        $("#funcionarioContactoEmergenciaParentesco").val(
            funcionario.contacto_emergencia_parentesco || ""
        );

        $("#funcionarioContactoEmergenciaTelefone").val(
            funcionario.contacto_emergencia_telefone || ""
        );

        $("#funcionarioFilialId").val(
            funcionario.filial_id
                ? String(funcionario.filial_id)
                : ""
        );

        $("#funcionarioDepartamentoId").val(
            funcionario.departamento_id
                ? String(funcionario.departamento_id)
                : ""
        );

        filtrarCargosPorDepartamento(
            funcionario.cargo_id || null
        );

        $("#funcionarioHorarioId").val(
            funcionario.horario_trabalho_id
                ? String(funcionario.horario_trabalho_id)
                : ""
        );

        $("#funcionarioDataAdmissao").val(
            funcionario.data_admissao || ""
        );

        $("#funcionarioDataDesligamento").val(
            funcionario.data_desligamento || ""
        );

        $("#funcionarioMotivoDesligamento").val(
            funcionario.motivo_desligamento || ""
        );

        $("#funcionarioTipoVinculo").val(
            funcionario.tipo_vinculo || ""
        );

        $("#funcionarioSalarioBase").val(
            funcionario.salario_base
            ?? "0.00"
        );

        $("#funcionarioMoeda").val(
            funcionario.moeda || "MZN"
        );

        $("#funcionarioNumeroContaBancaria").val(
            funcionario.numero_conta_bancaria || ""
        );

        $("#funcionarioNib").val(
            funcionario.nib || ""
        );

        $("#funcionarioBancoNome").val(
            funcionario.banco_nome || ""
        );

        $("#funcionarioFotografiaAtual").val(
            funcionario.fotografia || ""
        );

        /*
|--------------------------------------------------------------------------
| FOTOGRAFIA EXISTENTE
|--------------------------------------------------------------------------
*/

        if (funcionario.fotografia) {

            $("#funcionarioFotografiaPreview")
                .attr(
                    "src",
                    funcionario.fotografia
                );

            $("#funcionarioFotografiaPreviewContainer")
                .show();

        } else {

            $("#funcionarioFotografiaPreview")
                .attr(
                    "src",
                    ""
                );

            $("#funcionarioFotografiaPreviewContainer")
                .hide();
        }

        renderizarDocumentosExistentesFuncionario(
            funcionario.documentos || []
        );

        $("#funcionarioObservacoes").val(
            funcionario.observacoes || ""
        );

        $("#funcionarioStatusId").val(
            funcionario.status_id
                ? String(funcionario.status_id)
                : ""
        );

        atualizarCamposDesligamentoFuncionario();

    }


    function renderizarDocumentosExistentesFuncionario(
        documentos
    ) {

        const container =
            $("#funcionarioDocumentosExistentes");

        const bloco =
            $("#funcionarioDocumentosExistentesContainer");


        container.empty();


        if (
            !Array.isArray(documentos)
            || documentos.length === 0
        ) {

            bloco.hide();

            return;
        }


        const nomesTipos = {

            BI:
                "Bilhete de Identidade",

            PASSAPORTE:
                "Passaporte",

            NUIT:
                "NUIT",

            INSS:
                "INSS",

            REGISTO_CRIMINAL:
                "Registo Criminal",

            HABILITACOES:
                "Certificado de Habilitações",

            CV:
                "Curriculum Vitae",

            ATESTADO_SANIDADE:
                "Atestado de Sanidade",

            OUTRO:
                "Outro Documento"
        };


        documentos.forEach(
            function (documento) {

                const tipo =
                    nomesTipos[
                    documento.tipo
                    ]
                    || documento.tipo
                    || "Documento";


                const nome =
                    documento.nome_original
                    || "Documento";


                const caminho =
                    documento.caminho_arquivo
                    || "";


                const tamanho =
                    formatarTamanhoArquivoFuncionario(
                        documento.tamanho_bytes
                    );


                container.append(`
                <div
                    class="d-flex
                           justify-content-between
                           align-items-center
                           border-bottom
                           py-2">

                    <div>

                        <strong>
                            ${escaparHtmlFuncionario(tipo)}
                        </strong>

                        <div class="small text-muted">

                            ${escaparHtmlFuncionario(nome)}

                            ${tamanho
                        ? " • "
                        + escaparHtmlFuncionario(
                            tamanho
                        )
                        : ""
                    }

                        </div>

                    </div>

                    <div>

                        ${caminho
                        ? `
                                    <a
                                        href="${escaparHtmlFuncionario(caminho)}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-sm btn-outline-primary">

                                        <i class="fa fa-eye mr-1"></i>

                                        Abrir

                                    </a>
                                `
                        : ""
                    }

                    </div>

                </div>
            `);
            }
        );


        bloco.show();
    }

    function formatarTamanhoArquivoFuncionario(
        bytes
    ) {

        const tamanho =
            Number(
                bytes || 0
            );


        if (tamanho <= 0) {
            return "";
        }


        if (tamanho < 1024) {

            return (
                tamanho.toLocaleString(
                    "pt-PT"
                )
                + " B"
            );
        }


        if (
            tamanho <
            1024 * 1024
        ) {

            return (
                (
                    tamanho / 1024
                ).toFixed(1)
                + " KB"
            );
        }


        return (
            (
                tamanho
                / 1024
                / 1024
            ).toFixed(1)
            + " MB"
        );
    }

    // ==================================================
    // GUARDAR
    // ==================================================

    async function guardarFuncionario() {

        const dados =
            obterDadosFormularioFuncionario();

        if (
            !validarFormularioFuncionario(
                dados
            )
        ) {
            return;
        }


        const formulario =
            document.getElementById(
                "formFuncionario"
            );

        if (!formulario) {
            throw new Error(
                "Formulário do funcionário não encontrado."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CRIAR FORMDATA ANTES DE DESABILITAR OS CAMPOS
        |--------------------------------------------------------------------------
        */

        const formData =
            new FormData(
                formulario
            );


        /*
        |--------------------------------------------------------------------------
        | AÇÃO
        |--------------------------------------------------------------------------
        */

        formData.set(
            "acao",
            dados.id > 0
                ? "editar"
                : "criar"
        );


        /*
        |--------------------------------------------------------------------------
        | ID
        |--------------------------------------------------------------------------
        */

        if (dados.id > 0) {

            formData.set(
                "id",
                String(dados.id)
            );

        } else {

            formData.delete(
                "id"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | GARANTIR DADOS NORMALIZADOS
        |--------------------------------------------------------------------------
        */

        Object.entries(
            dados
        ).forEach(
            function ([campo, valor]) {

                if (campo === "id") {
                    return;
                }

                formData.set(
                    campo,
                    valor === null
                        ? ""
                        : String(valor)
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | DEBUG DOS FICHEIROS
        |--------------------------------------------------------------------------
        */



        for (
            const [campo, valor]
            of formData.entries()
        ) {

            if (valor instanceof File) {

                console.log(
                    campo,
                    {
                        nome:
                            valor.name,

                        tipo:
                            valor.type,

                        tamanho:
                            valor.size
                    }
                );

            } else {

                console.log(
                    campo,
                    valor
                );
            }
        }



        /*
        |--------------------------------------------------------------------------
        | SÓ AGORA BLOQUEAR O FORMULÁRIO
        |--------------------------------------------------------------------------
        */

        bloquearModalFuncionario(
            true
        );


        try {

            const resposta =
                await requisicaoFuncionarios(
                    FUNCIONARIOS_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );


            console.log(
                "[FUNCIONÁRIO] Resposta do servidor:",
                resposta
            );


            $("#modalFuncionario")
                .modal(
                    "hide"
                );


            await Swal.fire({
                icon:
                    "success",

                title:
                    dados.id > 0
                        ? "Funcionário atualizado"
                        : "Funcionário criado",

                text:
                    resposta.mensagem
                    || "Operação concluída com sucesso.",

                confirmButtonText:
                    "Concluir"
            });


            await carregarFuncionariosFiltrados();

        } catch (erro) {

            console.error(
                "[FUNCIONÁRIO] Erro ao guardar:",
                erro
            );

            mostrarErroFuncionario(
                erro.message
            );

            throw erro;

        } finally {

            bloquearModalFuncionario(
                false
            );
        }
    }


    // ==================================================
    // DADOS DO FORMULÁRIO
    // ==================================================

    function obterDadosFormularioFuncionario() {

        return {
            id:
                Number(
                    $("#funcionarioId").val()
                    || 0
                ),

            numero_funcionario:
                textoFuncionario(
                    "#funcionarioNumero"
                ).toUpperCase(),

            primeiro_nome:
                textoFuncionario(
                    "#funcionarioPrimeiroNome"
                ),

            apelido:
                textoFuncionario(
                    "#funcionarioApelido"
                ),

            sexo:
                valorOpcionalFuncionario(
                    $("#funcionarioSexo").val()
                ),

            data_nascimento:
                valorOpcionalFuncionario(
                    $("#funcionarioDataNascimento").val()
                ),

            estado_civil:
                valorOpcionalFuncionario(
                    $("#funcionarioEstadoCivil").val()
                ),

            nacionalidade:
                textoFuncionario(
                    "#funcionarioNacionalidade"
                ),

            naturalidade:
                textoFuncionario(
                    "#funcionarioNaturalidade"
                ),

            numero_dependentes:
                Math.max(
                    0,
                    Number(
                        $("#funcionarioNumeroDependentes")
                            .val()
                        || 0
                    )
                ),

            residente_fiscal:
                String(
                    $("#funcionarioResidenteFiscal")
                        .val()
                    || "1"
                ),

            nuit:
                textoFuncionario(
                    "#funcionarioNuit"
                ),

            numero_bi:
                textoFuncionario(
                    "#funcionarioNumeroBi"
                ),

            data_emissao_bi:
                valorOpcionalFuncionario(
                    $("#funcionarioDataEmissaoBi").val()
                ),

            data_validade_bi:
                valorOpcionalFuncionario(
                    $("#funcionarioDataValidadeBi").val()
                ),

            entidade_emissora_bi:
                textoFuncionario(
                    "#funcionarioEntidadeEmissoraBi"
                ),

            numero_inss:
                textoFuncionario(
                    "#funcionarioNumeroInss"
                ),

            email_pessoal:
                textoFuncionario(
                    "#funcionarioEmailPessoal"
                ),

            email_profissional:
                textoFuncionario(
                    "#funcionarioEmailProfissional"
                ),

            telefone_principal:
                textoFuncionario(
                    "#funcionarioTelefonePrincipal"
                ),

            telefone_alternativo:
                textoFuncionario(
                    "#funcionarioTelefoneAlternativo"
                ),

            endereco:
                textoFuncionario(
                    "#funcionarioEndereco"
                ),

            provincia:
                textoFuncionario(
                    "#funcionarioProvincia"
                ),

            distrito:
                textoFuncionario(
                    "#funcionarioDistrito"
                ),

            cidade:
                textoFuncionario(
                    "#funcionarioCidade"
                ),

            bairro:
                textoFuncionario(
                    "#funcionarioBairro"
                ),

            contacto_emergencia_nome:
                textoFuncionario(
                    "#funcionarioContactoEmergenciaNome"
                ),

            contacto_emergencia_parentesco:
                textoFuncionario(
                    "#funcionarioContactoEmergenciaParentesco"
                ),

            contacto_emergencia_telefone:
                textoFuncionario(
                    "#funcionarioContactoEmergenciaTelefone"
                ),

            filial_id:
                inteiroOpcionalFuncionario(
                    $("#funcionarioFilialId").val()
                ),

            departamento_id:
                inteiroOpcionalFuncionario(
                    $("#funcionarioDepartamentoId").val()
                ),

            cargo_id:
                inteiroOpcionalFuncionario(
                    $("#funcionarioCargoId").val()
                ),

            horario_trabalho_id:
                inteiroOpcionalFuncionario(
                    $("#funcionarioHorarioId").val()
                ),

            data_admissao:
                textoFuncionario(
                    "#funcionarioDataAdmissao"
                ),

            data_desligamento:
                valorOpcionalFuncionario(
                    $("#funcionarioDataDesligamento").val()
                ),

            motivo_desligamento:
                textoFuncionario(
                    "#funcionarioMotivoDesligamento"
                ),

            tipo_vinculo:
                textoFuncionario(
                    "#funcionarioTipoVinculo"
                ),

            salario_base:
                normalizarDecimalFuncionario(
                    $("#funcionarioSalarioBase").val()
                ),

            moeda:
                textoFuncionario(
                    "#funcionarioMoeda"
                ).toUpperCase(),

            numero_conta_bancaria:
                textoFuncionario(
                    "#funcionarioNumeroContaBancaria"
                ),

            nib:
                textoFuncionario(
                    "#funcionarioNib"
                ),

            banco_nome:
                textoFuncionario(
                    "#funcionarioBancoNome"
                ),

            fotografia:
                textoFuncionario(
                    "#funcionarioFotografiaAtual"
                ),

            observacoes:
                textoFuncionario(
                    "#funcionarioObservacoes"
                ),

            status_id:
                Number(
                    $("#funcionarioStatusId").val()
                    || 0
                )
        };

    }

    function limparValidacaoFuncionario() {

        const formulario =
            document.getElementById(
                "formFuncionario"
            );


        if (!formulario) {
            return;
        }


        formulario
            .querySelectorAll(
                ".is-invalid"
            )
            .forEach(
                function (campo) {

                    campo.classList.remove(
                        "is-invalid"
                    );
                }
            );


        formulario
            .querySelectorAll(
                ".invalid-feedback.funcionario-validacao"
            )
            .forEach(
                function (elemento) {

                    elemento.remove();
                }
            );
    }


    function marcarCampoInvalidoFuncionario(
        campoId,
        mensagem
    ) {

        const campo =
            document.getElementById(
                campoId
            );


        if (!campo) {
            return;
        }


        campo.classList.add(
            "is-invalid"
        );


        const feedback =
            document.createElement(
                "div"
            );


        feedback.className =
            "invalid-feedback funcionario-validacao";


        feedback.textContent =
            mensagem;


        campo.insertAdjacentElement(
            "afterend",
            feedback
        );
    }


    function adicionarErroCampoFuncionario(
        campoId,
        mensagem,
        erros
    ) {

        erros.push(
            mensagem
        );


        marcarCampoInvalidoFuncionario(
            campoId,
            mensagem
        );
    }


    function emailValidoFuncionario(
        email
    ) {

        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            .test(
                String(email).trim()
            );
    }


    function telefoneValidoFuncionario(
        telefone
    ) {

        const numero =
            String(
                telefone || ""
            )
                .replace(
                    /[\s()+-]/g,
                    ""
                );


        return /^\d{8,15}$/
            .test(
                numero
            );
    }

    // ==================================================
    // VALIDAR FORMULÁRIO
    // ==================================================

    function validarFormularioFuncionario(
        dados
    ) {

        limparValidacaoFuncionario();


        const erros = [];


        // ==========================================
        // NÚMERO DO FUNCIONÁRIO
        // ==========================================

        if (!dados.numero_funcionario) {

            adicionarErroCampoFuncionario(
                "funcionarioNumero",
                "Informe o número do funcionário.",
                erros
            );

        } else if (
            dados.numero_funcionario.length > 30
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioNumero",
                "O número do funcionário não pode exceder 30 caracteres.",
                erros
            );
        }


        // ==========================================
        // ESTADO
        // ==========================================

        if (
            Number(
                dados.status_id || 0
            ) <= 0
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioStatusId",
                "Selecione o estado do funcionário.",
                erros
            );
        }


        // ==========================================
        // VÍNCULO
        // ==========================================

        if (!dados.tipo_vinculo) {

            adicionarErroCampoFuncionario(
                "funcionarioTipoVinculo",
                "Selecione o tipo de vínculo.",
                erros
            );
        }


        // ==========================================
        // DATA DE ADMISSÃO
        // ==========================================

        if (!dados.data_admissao) {

            adicionarErroCampoFuncionario(
                "funcionarioDataAdmissao",
                "Informe a data de admissão.",
                erros
            );
        }


        // ==========================================
        // PRIMEIRO NOME
        // ==========================================

        if (!dados.primeiro_nome) {

            adicionarErroCampoFuncionario(
                "funcionarioPrimeiroNome",
                "Informe o primeiro nome do funcionário.",
                erros
            );

        } else if (
            dados.primeiro_nome.length > 80
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioPrimeiroNome",
                "O primeiro nome não pode exceder 80 caracteres.",
                erros
            );
        }


        // ==========================================
        // APELIDO
        // ==========================================

        if (!dados.apelido) {

            adicionarErroCampoFuncionario(
                "funcionarioApelido",
                "Informe o apelido do funcionário.",
                erros
            );

        } else if (
            dados.apelido.length > 80
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioApelido",
                "O apelido não pode exceder 80 caracteres.",
                erros
            );
        }


        // ==========================================
        // DATA DE NASCIMENTO
        // ==========================================

        if (
            dados.data_nascimento
            && dados.data_nascimento
            >= obterDataAtualIsoFuncionario()
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioDataNascimento",
                "A data de nascimento deve ser anterior à data atual.",
                erros
            );
        }


        // ==========================================
        // DESLIGAMENTO
        // ==========================================

        if (
            dados.data_desligamento
            && dados.data_admissao
            && dados.data_desligamento
            < dados.data_admissao
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioDataDesligamento",
                "A data de desligamento não pode ser anterior à admissão.",
                erros
            );
        }


        // ==========================================
        // BI - DATAS
        // ==========================================

        if (
            dados.data_emissao_bi
            && dados.data_validade_bi
            && dados.data_validade_bi
            < dados.data_emissao_bi
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioDataValidadeBi",
                "A validade do BI não pode ser anterior à data de emissão.",
                erros
            );
        }


        // ==========================================
        // NUIT
        // ==========================================

        if (
            dados.nuit
            && !/^\d{9}$/.test(
                String(dados.nuit)
            )
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioNuit",
                "Informe um NUIT válido com 9 dígitos.",
                erros
            );
        }


        // ==========================================
        // EMAIL PESSOAL
        // ==========================================

        if (
            dados.email_pessoal
            && !emailValidoFuncionario(
                dados.email_pessoal
            )
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioEmailPessoal",
                "Informe um e-mail pessoal válido.",
                erros
            );
        }


        // ==========================================
        // EMAIL PROFISSIONAL
        // ==========================================

        if (
            dados.email_profissional
            && !emailValidoFuncionario(
                dados.email_profissional
            )
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioEmailProfissional",
                "Informe um e-mail profissional válido.",
                erros
            );
        }


        // ==========================================
        // TELEFONE PRINCIPAL
        // ==========================================

        if (
            dados.telefone_principal
            && !telefoneValidoFuncionario(
                dados.telefone_principal
            )
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioTelefonePrincipal",
                "Informe um telefone principal válido.",
                erros
            );
        }


        // ==========================================
        // TELEFONE ALTERNATIVO
        // ==========================================

        if (
            dados.telefone_alternativo
            && !telefoneValidoFuncionario(
                dados.telefone_alternativo
            )
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioTelefoneAlternativo",
                "Informe um telefone alternativo válido.",
                erros
            );
        }


        // ==========================================
        // CONTACTO DE EMERGÊNCIA
        // ==========================================

        if (
            dados.contacto_emergencia_telefone
            && !telefoneValidoFuncionario(
                dados.contacto_emergencia_telefone
            )
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioContactoEmergenciaTelefone",
                "Informe um telefone de emergência válido.",
                erros
            );
        }


        // ==========================================
        // SALÁRIO BASE
        // ==========================================

        if (
            Number.isNaN(
                Number(dados.salario_base)
            )
            || Number(dados.salario_base) < 0
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioSalarioBase",
                "O salário base deve ser um valor igual ou superior a zero.",
                erros
            );
        }


        // ==========================================
        // MOEDA
        // ==========================================

        if (
            !/^[A-Z]{3}$/.test(
                String(
                    dados.moeda || ""
                )
            )
        ) {

            adicionarErroCampoFuncionario(
                "funcionarioMoeda",
                "A moeda deve possuir três letras. Exemplo: MZN.",
                erros
            );
        }


        // ==========================================
        // RESULTADO
        // ==========================================

        if (erros.length > 0) {

            const primeiroCampo =
                document.querySelector(
                    "#formFuncionario .is-invalid"
                );


            if (primeiroCampo) {

                primeiroCampo.scrollIntoView({
                    behavior:
                        "smooth",

                    block:
                        "center"
                });


                setTimeout(
                    function () {
                        primeiroCampo.focus();
                    },
                    300
                );
            }


            mostrarErroFuncionario(
                erros.join("\n")
            );


            return false;
        }


        return true;
    }

    // ==================================================
    // DESLIGAMENTO
    // ==================================================

    function atualizarCamposDesligamentoFuncionario() {

        const statusTexto =
            normalizarTextoFuncionario(
                $("#funcionarioStatusId option:selected")
                    .text()
            );

        const desligado =
            statusTexto === "desligado";

        $("#funcionarioDataDesligamento")
            .prop(
                "disabled",
                !desligado
            );

        $("#funcionarioMotivoDesligamento")
            .prop(
                "disabled",
                !desligado
            );

        if (!desligado) {

            $("#funcionarioDataDesligamento")
                .val("");

            $("#funcionarioMotivoDesligamento")
                .val("");

        }

    }


    // ==================================================
    // ELIMINAR
    // ==================================================

    async function confirmarEliminarFuncionario(
        funcionarioId
    ) {

        const funcionario =
            funcionariosCache.find(
                function (item) {

                    return Number(item.id)
                        === Number(funcionarioId);

                }
            );

        if (!funcionario) {

            mostrarErroFuncionario(
                "Funcionário não encontrado."
            );

            return;

        }

        const confirmacao =
            await Swal.fire({
                icon:
                    "warning",

                title:
                    "Eliminar funcionário?",

                html:
                    `
                        <p>
                            Pretende eliminar:
                        </p>

                        <strong>
                            ${escaparHtmlFuncionario(
                        funcionario.numero_funcionario
                        + " - "
                        + funcionario.nome_completo
                    )}
                        </strong>

                        <p class="mt-3 mb-0">
                            Quando existirem contratos, salários,
                            presenças ou outros registos associados,
                            a eliminação deverá ser bloqueada.
                        </p>
                    `,

                showCancelButton:
                    true,

                confirmButtonText:
                    "Sim, eliminar",

                cancelButtonText:
                    "Cancelar",

                confirmButtonColor:
                    "#d33",

                reverseButtons:
                    true
            });

        if (!confirmacao.isConfirmed) {
            return;
        }

        mostrarCarregamentoFuncionarios(true);

        try {

            const formData =
                new FormData();

            formData.append(
                "acao",
                "eliminar"
            );

            formData.append(
                "id",
                String(funcionarioId)
            );

            const resposta =
                await requisicaoFuncionarios(
                    FUNCIONARIOS_URL,
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );

            await Swal.fire({
                icon:
                    "success",

                title:
                    "Funcionário eliminado",

                text:
                    resposta.mensagem
                    || "Funcionário eliminado com sucesso.",

                confirmButtonText:
                    "Concluir"
            });

            await carregarFuncionariosFiltrados();

        } catch (erro) {

            mostrarErroFuncionario(
                erro.message
            );

            throw erro;

        } finally {

            mostrarCarregamentoFuncionarios(false);

        }

    }


    // ==================================================
    // DETALHES
    // ==================================================

    async function mostrarDetalhesFuncionario(
        funcionarioId
    ) {

        if (funcionarioId <= 0) {

            throw new Error(
                "Funcionário inválido."
            );
        }


        const parametros =
            new URLSearchParams({
                acao:
                    "buscar",

                id:
                    String(
                        funcionarioId
                    )
            });


        const resposta =
            await requisicaoFuncionarios(
                FUNCIONARIOS_URL
                + "?"
                + parametros.toString()
            );


        const funcionario =
            resposta.data || {};


        const documentos =
            Array.isArray(
                funcionario.documentos
            )
                ? funcionario.documentos
                : [];


        const fotografia =
            funcionario.fotografia
                ? `
                <div class="text-center mb-4">

                    <a
                        href="${escaparHtmlFuncionario(
                    funcionario.fotografia
                )}"
                        target="_blank"
                        rel="noopener noreferrer">

                        <img
                            src="${escaparHtmlFuncionario(
                    funcionario.fotografia
                )}"
                            alt="Fotografia"
                            class="img-thumbnail"
                            style="
                                width:140px;
                                height:140px;
                                object-fit:cover;
                            ">

                    </a>

                    <div class="small text-muted mt-1">
                        Clique na fotografia para abrir
                    </div>

                </div>
            `
                : `
                <div class="text-center text-muted mb-4">
                    Sem fotografia
                </div>
            `;


        const documentosHtml =
            gerarDocumentosDetalhesFuncionario(
                documentos
            );


        await Swal.fire({

            title:
                escaparHtmlFuncionario(
                    funcionario.nome_completo
                    || "Funcionário"
                ),

            width:
                950,

            html:
                `
                <div class="text-left">

                    ${fotografia}

                    <div class="row">

                        ${campoDetalheFuncionario(
                    "Número",
                    funcionario.numero_funcionario
                    || "-"
                )}

                        ${campoDetalheFuncionario(
                    "Estado",
                    funcionario.status_nome
                    || "-"
                )}

                        ${campoDetalheFuncionario(
                    "Filial",
                    funcionario.filial_nome
                    || "-"
                )}

                        ${campoDetalheFuncionario(
                    "Departamento",
                    funcionario.departamento_nome
                    || "-"
                )}

                        ${campoDetalheFuncionario(
                    "Cargo",
                    funcionario.cargo_nome
                    || "-"
                )}

                        ${campoDetalheFuncionario(
                    "Horário",
                    funcionario.horario_nome
                    || "-"
                )}

                        ${campoDetalheFuncionario(
                    "Vínculo",
                    formatarTipoVinculoFuncionario(
                        funcionario.tipo_vinculo
                    )
                )}

                        ${campoDetalheFuncionario(
                    "Admissão",
                    formatarDataFuncionario(
                        funcionario.data_admissao
                    )
                )}

                        ${campoDetalheFuncionario(
                    "Telefone",
                    funcionario.telefone_principal
                    || "-"
                )}

                        ${campoDetalheFuncionario(
                    "E-mail profissional",
                    funcionario.email_profissional
                    || "-"
                )}

                        ${campoDetalheFuncionario(
                    "NUIT",
                    funcionario.nuit
                    || "-"
                )}

                        ${campoDetalheFuncionario(
                    "Número de BI",
                    funcionario.numero_bi
                    || "-"
                )}

                        ${campoDetalheFuncionario(
                    "INSS",
                    funcionario.numero_inss
                    || "-"
                )}

                        ${campoDetalheFuncionario(
                    "Salário base",
                    formatarDinheiroFuncionario(
                        funcionario.salario_base,
                        funcionario.moeda
                        || "MZN"
                    )
                )}

                    </div>


                    <hr>


                    <h6>
                        <i class="fa fa-folder-open mr-1"></i>
                        Documentos
                    </h6>

                    ${documentosHtml}


                    <hr>


                    <strong>
                        Observações:
                    </strong>

                    <div class="mt-2">
                        ${formatarTextoLongoFuncionario(
                    funcionario.observacoes
                )}
                    </div>

                </div>
            `,

            confirmButtonText:
                "Fechar"
        });
    }

    function gerarDocumentosDetalhesFuncionario(
        documentos
    ) {

        if (
            !Array.isArray(documentos)
            || documentos.length === 0
        ) {

            return `
            <div class="text-muted py-2">
                Nenhum documento anexado.
            </div>
        `;
        }


        const nomesTipos = {

            BI:
                "Bilhete de Identidade",

            PASSAPORTE:
                "Passaporte",

            NUIT:
                "NUIT",

            INSS:
                "INSS",

            REGISTO_CRIMINAL:
                "Registo Criminal",

            HABILITACOES:
                "Certificado de Habilitações",

            CV:
                "Curriculum Vitae",

            ATESTADO_SANIDADE:
                "Atestado de Sanidade",

            OUTRO:
                "Outro Documento"
        };


        return documentos
            .map(
                function (documento) {

                    const tipo =
                        nomesTipos[
                        documento.tipo
                        ]
                        || documento.tipo
                        || "Documento";


                    const tamanho =
                        formatarTamanhoArquivoFuncionario(
                            documento.tamanho_bytes
                        );


                    return `
                    <div
                        class="
                            d-flex
                            justify-content-between
                            align-items-center
                            border
                            rounded
                            p-2
                            mb-2
                        ">

                        <div>

                            <strong>
                                ${escaparHtmlFuncionario(tipo)}
                            </strong>

                            <div class="small text-muted">

                                ${escaparHtmlFuncionario(
                        documento.nome_original
                        || "Documento"
                    )}

                                ${tamanho
                            ? " • "
                            + escaparHtmlFuncionario(
                                tamanho
                            )
                            : ""
                        }

                            </div>

                        </div>


                        ${documento.caminho_arquivo
                            ? `
                                    <a
                                        href="${escaparHtmlFuncionario(
                                documento.caminho_arquivo
                            )}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-sm btn-primary">

                                        <i class="fa fa-external-link mr-1"></i>

                                        Abrir

                                    </a>
                                `
                            : ""
                        }

                    </div>
                `;
                }
            )
            .join("");
    }


    // ==================================================
    // LIMPAR
    // ==================================================

    function limparFormularioFuncionario() {

        const formulario =
            $("#formFuncionario")[0];

        if (formulario) {
            formulario.reset();
        }


        /*
        |--------------------------------------------------------------------------
        | IDENTIFICADOR
        |--------------------------------------------------------------------------
        */

        $("#funcionarioId").val("");

        $("#funcionarioNomeCompleto").val("");


        /*
        |--------------------------------------------------------------------------
        | FOTOGRAFIA
        |--------------------------------------------------------------------------
        */

        $("#funcionarioFotografiaArquivo")
            .val("");

        $("#funcionarioFotografiaAtual")
            .val("");

        $("#funcionarioFotografiaPreview")
            .attr(
                "src",
                ""
            );

        $("#funcionarioFotografiaPreviewContainer")
            .hide();

        $("#funcionarioNumeroDependentes")
            .val("0");

        $("#funcionarioResidenteFiscal")
            .val("1");

        /*
        |--------------------------------------------------------------------------
        | DOCUMENTOS
        |--------------------------------------------------------------------------
        */

        $("#funcionarioDocumentosExistentes")
            .empty();

        $("#funcionarioDocumentosExistentesContainer")
            .hide();

        $("#funcionarioDocumentoBi").val("");

        $("#funcionarioDocumentoPassaporte").val("");

        $("#funcionarioDocumentoNuit").val("");

        $("#funcionarioDocumentoInss").val("");

        $("#funcionarioDocumentoRegistoCriminal")
            .val("");

        $("#funcionarioDocumentoHabilitacoes")
            .val("");

        $("#funcionarioDocumentoCv").val("");

        $("#funcionarioDocumentoSanidade")
            .val("");


        /*
        |--------------------------------------------------------------------------
        | DESLIGAMENTO
        |--------------------------------------------------------------------------
        */

        $("#funcionarioDataDesligamento")
            .prop(
                "disabled",
                true
            );

        $("#funcionarioMotivoDesligamento")
            .prop(
                "disabled",
                true
            );


        preencherCargosFuncionarios(
            cargosCache
        );


        $("#formFuncionario")
            .find(
                ".is-invalid"
            )
            .removeClass(
                "is-invalid"
            );


        $("#modalFuncionarioTitulo")
            .text(
                "Novo Funcionário"
            );


        $("#btnGuardarFuncionarioTexto")
            .text(
                "Guardar Funcionário"
            );
    }


    function limparFiltrosFuncionarios() {

        $("#filtroFuncionarioPesquisa").val("");

        $("#filtroFuncionarioFilialId").val("");

        $("#filtroFuncionarioDepartamentoId").val("");

        $("#filtroFuncionarioCargoId").val("");

        $("#filtroFuncionarioStatusId").val("");

    }


    // ==================================================
    // REQUISIÇÃO
    // ==================================================

    async function requisicaoFuncionarios(
        url,
        opcoes = {}
    ) {

        console.log(
            "URL:",
            url
        );

        console.log(
            "Método:",
            opcoes.method || "GET"
        );


        const resposta =
            await fetch(
                url,
                {
                    credentials:
                        "same-origin",

                    ...opcoes
                }
            );


        console.log(
            "HTTP Status:",
            resposta.status
        );


        const texto =
            await resposta.text();


        console.log(
            "Resposta bruta:",
            texto
        );


        let json;

        try {

            json =
                JSON.parse(
                    texto
                );

        } catch (erro) {

            console.error(
                "JSON inválido:",
                erro
            );

            throw new Error(
                "O servidor não retornou uma resposta JSON válida."
            );
        }


        console.log(
            "JSON:",
            json
        );


        if (
            json.linha
            || json.arquivo
            || json.detalhe
        ) {

            console.warn(
                "Detalhes PHP:",
                {
                    mensagem:
                        json.mensagem,

                    detalhe:
                        json.detalhe,

                    arquivo:
                        json.arquivo,

                    linha:
                        json.linha
                }
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
                json.detalhe
                || json.mensagem
                || json.message
                || "Não foi possível processar o funcionário."
            );
        }


        return json;
    }

    // ==================================================
    // ESTADO DA INTERFACE
    // ==================================================

    function mostrarCarregamentoFuncionarios(
        mostrar
    ) {

        $("#funcionariosCarregando")
            .toggle(
                Boolean(mostrar)
            );

        $("#btnNovoFuncionario")
            .prop(
                "disabled",
                Boolean(mostrar)
            );

        $("#btnFiltrarFuncionarios")
            .prop(
                "disabled",
                Boolean(mostrar)
            );

        $("#btnLimparFiltrosFuncionarios")
            .prop(
                "disabled",
                Boolean(mostrar)
            );

    }


    function bloquearModalFuncionario(
        bloquear
    ) {

        $("#formFuncionario")
            .find(
                "input, textarea, select, button"
            )
            .prop(
                "disabled",
                Boolean(bloquear)
            );

        $("#btnGuardarFuncionarioSpinner")
            .toggle(
                Boolean(bloquear)
            );

    }


    // ==================================================
    // ERRO
    // ==================================================

    function mostrarErroFuncionario(
        mensagem
    ) {

        const mensagens =
            String(
                mensagem
                || "Ocorreu um erro na gestão de funcionários."
            )
                .split("\n")
                .filter(Boolean);


        const html =
            mensagens.length > 1
                ? `
                <div class="text-left">
                    <p>
                        Corrija os seguintes campos:
                    </p>

                    <ul class="mb-0">
                        ${mensagens
                    .map(
                        item => `
                                    <li>
                                        ${escaparHtmlFuncionario(
                            item
                        )}
                                    </li>
                                `
                    )
                    .join("")
                }
                    </ul>
                </div>
            `
                : escaparHtmlFuncionario(
                    mensagens[0]
                    || "Ocorreu um erro."
                );


        return Swal.fire({
            icon:
                "error",

            title:
                "Verifique os dados",

            html:
                html,

            confirmButtonText:
                "Corrigir"
        });
    }


    // ==================================================
    // HELPERS
    // ==================================================

    function preencherSelectFuncionario(
        seletor,
        itens,
        textoVazio,
        obterTexto
    ) {

        const select =
            $(seletor);

        select.empty();

        select.append(`
            <option value="">
                ${escaparHtmlFuncionario(
            textoVazio
        )}
            </option>
        `);

        itens.forEach(
            function (item) {

                select.append(`
                    <option value="${Number(
                    item.id
                )}">
                        ${escaparHtmlFuncionario(
                    obterTexto(item)
                )}
                    </option>
                `);

            }
        );

    }


    function preencherSelectComCodigoFuncionario(
        seletor,
        itens,
        textoVazio
    ) {

        const select =
            $(seletor);

        select.empty();

        select.append(`
            <option value="">
                ${escaparHtmlFuncionario(
            textoVazio
        )}
            </option>
        `);

        itens.forEach(
            function (item) {

                select.append(`
                    <option value="${escaparHtmlFuncionario(
                    item.codigo || ""
                )}">
                        ${escaparHtmlFuncionario(
                    item.nome || "-"
                )}
                    </option>
                `);

            }
        );

    }


    function atualizarNomeCompletoFuncionario() {

        const primeiroNome =
            textoFuncionario(
                "#funcionarioPrimeiroNome"
            );

        const apelido =
            textoFuncionario(
                "#funcionarioApelido"
            );

        $("#funcionarioNomeCompleto")
            .val(
                [
                    primeiroNome,
                    apelido
                ]
                    .filter(Boolean)
                    .join(" ")
            );

    }


    function validarPeriodoFuncionario() {

        const admissao =
            textoFuncionario(
                "#funcionarioDataAdmissao"
            );

        const desligamento =
            textoFuncionario(
                "#funcionarioDataDesligamento"
            );

        if (
            admissao
            && desligamento
            && desligamento < admissao
        ) {
            throw new Error(
                "A data de desligamento não pode ser anterior à admissão."
            );
        }

    }


    function validarDatasBiFuncionario() {

        const emissao =
            textoFuncionario(
                "#funcionarioDataEmissaoBi"
            );

        const validade =
            textoFuncionario(
                "#funcionarioDataValidadeBi"
            );

        if (
            emissao
            && validade
            && validade < emissao
        ) {
            throw new Error(
                "A validade do BI não pode ser anterior à emissão."
            );
        }

    }


    function validarEmailOpcionalFuncionario(
        email,
        campo
    ) {

        if (!email) {
            return;
        }

        const regex =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!regex.test(email)) {
            throw new Error(
                "O " + campo + " é inválido."
            );
        }

    }


    function obterFotografiaFuncionario(
        funcionario
    ) {

        if (funcionario.fotografia) {

            return `
                <img
                    src="${escaparHtmlFuncionario(
                funcionario.fotografia
            )}"
                    alt="Fotografia"
                    class="rounded-circle"
                    width="40"
                    height="40"
                    style="object-fit:cover;">
            `;

        }

        const inicial =
            String(
                funcionario.nome_completo || "F"
            )
                .charAt(0)
                .toUpperCase();

        return `
            <span
                class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center"
                style="
                    width:40px;
                    height:40px;
                    font-weight:600;
                ">

                ${escaparHtmlFuncionario(
            inicial
        )}

            </span>
        `;

    }


    function obterClasseStatusFuncionario(
        status
    ) {

        const normalizado =
            normalizarTextoFuncionario(
                status
            );

        if (
            [
                "activo",
                "ativo",
                "activa",
                "ativa"
            ].includes(normalizado)
        ) {
            return "badge-success";
        }

        if (normalizado === "suspenso") {
            return "badge-warning";
        }

        if (normalizado === "desligado") {
            return "badge-danger";
        }

        return "badge-secondary";

    }


    function formatarTipoVinculoFuncionario(
        codigo
    ) {

        const item =
            tiposVinculoCache.find(
                function (tipo) {

                    return String(
                        tipo.codigo || ""
                    ) === String(
                        codigo || ""
                    );

                }
            );

        return item
            ? item.nome
            : String(
                codigo || "-"
            ).replace(
                /_/g,
                " "
            );

    }


    function formatarDinheiroFuncionario(
        valor,
        moeda
    ) {

        return Number(
            valor || 0
        ).toLocaleString(
            "pt-PT",
            {
                style:
                    "currency",

                currency:
                    moeda || "MZN"
            }
        );

    }


    function formatarDataFuncionario(
        data
    ) {

        if (!data) {
            return "-";
        }

        const partes =
            String(data)
                .split("-");

        if (partes.length !== 3) {
            return String(data);
        }

        return (
            partes[2]
            + "/"
            + partes[1]
            + "/"
            + partes[0]
        );

    }


    function formatarHoraVisivelFuncionario(
        hora
    ) {

        if (!hora) {
            return "-";
        }

        return String(hora)
            .substring(0, 5);

    }


    function normalizarDecimalFuncionario(
        valor
    ) {

        const texto =
            String(
                valor ?? "0"
            )
                .trim()
                .replace(
                    ",",
                    "."
                );

        const numero =
            Number(texto);

        if (
            !Number.isFinite(numero)
            || numero < 0
        ) {
            return "0.00";
        }

        return numero.toFixed(2);

    }


    function inteiroOpcionalFuncionario(
        valor
    ) {

        const numero =
            Number(
                valor || 0
            );

        return Number.isInteger(numero)
            && numero > 0
            ? numero
            : null;

    }


    function valorOpcionalFuncionario(
        valor
    ) {

        const texto =
            String(
                valor ?? ""
            ).trim();

        return texto === ""
            ? null
            : texto;

    }


    function textoFuncionario(
        seletor
    ) {

        return String(
            $(seletor).val()
            || ""
        ).trim();

    }


    function adicionarParametroSePreenchido(
        parametros,
        nome,
        valor
    ) {

        const texto =
            String(
                valor ?? ""
            ).trim();

        if (texto !== "") {

            parametros.append(
                nome,
                texto
            );

        }

    }


    function obterDataAtualIsoFuncionario() {

        const hoje =
            new Date();

        const ano =
            hoje.getFullYear();

        const mes =
            String(
                hoje.getMonth() + 1
            ).padStart(
                2,
                "0"
            );

        const dia =
            String(
                hoje.getDate()
            ).padStart(
                2,
                "0"
            );

        return `${ano}-${mes}-${dia}`;

    }


    function campoDetalheFuncionario(
        titulo,
        valor
    ) {

        return `
            <div class="col-md-6 mb-3">

                <strong>
                    ${escaparHtmlFuncionario(
            titulo
        )}:
                </strong>

                <br>

                ${escaparHtmlFuncionario(
            valor
        )}

            </div>
        `;

    }


    function formatarTextoLongoFuncionario(
        valor
    ) {

        const texto =
            String(
                valor || ""
            ).trim();

        if (texto === "") {

            return `
                <span class="text-muted">
                    Não informado
                </span>
            `;

        }

        return escaparHtmlFuncionario(
            texto
        ).replace(
            /\n/g,
            "<br>"
        );

    }


    function definirTextoFuncionario(
        seletor,
        valor
    ) {

        $(seletor).text(valor);

    }


    function normalizarTextoFuncionario(
        valor
    ) {

        return String(
            valor || ""
        )
            .trim()
            .toLocaleLowerCase(
                "pt-PT"
            );

    }


    function escaparHtmlFuncionario(
        valor
    ) {

        return $("<div>")
            .text(
                valor ?? ""
            )
            .html();

    }

})(jQuery);