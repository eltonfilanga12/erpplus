'use strict';

const RubricasSalariais = (() => {

    const API_URL = '/erpplus/app/ajax/rubricas_salariais_ajax.php';

    let tabela = null;

    let estadosDisponiveis = [];

    const elementos = {};

    function iniciar() {

        cacheElementos();

        iniciarTabela();

        eventos();

        carregarEstados();

        carregarResumo();

        listarRubricas();

    }

    function cacheElementos() {

        elementos.tabela = $('#rubricasTable');

        elementos.modal = $('#modalRubrica');

        elementos.modalVisualizar = $('#modalVisualizarRubrica');

        elementos.form = $('#formRubrica');

        elementos.btnNova = $('#btnNovaRubrica');

        elementos.btnPesquisar = $('#btnFiltrar');

        elementos.btnLimpar = $('#btnLimparFiltros');

    }

    function iniciarTabela() {

        tabela = elementos.tabela.DataTable({

            processing: true,

            responsive: true,

            autoWidth: false,

            pageLength: 25,

            order: [[0, 'asc']],

            language: {

                url: 'public/assets/plugins/datatables/pt-PT.json'

            },

            columns: [

                { data: 'codigo' },

                { data: 'nome' },

                {

                    data: 'categoria',

                    render(data) {

                        return badgeCategoria(data);

                    }

                },

                {

                    data: 'tipo',

                    render(data) {

                        return badgeTipo(data);

                    }

                },

                {

                    data: 'modo_calculo'

                },

                {

                    data: 'valor_padrao',

                    className: 'text-right',

                    render(data) {

                        return formatarMoeda(data);

                    }

                },

                {

                    data: 'status_nome',

                    render(data) {

                        return badgeEstado(data);

                    }

                },

                {

                    data: null,

                    orderable: false,

                    searchable: false,

                    className: 'text-right',

                    render(linha) {

                        return botoesAcao(linha);

                    }

                }

            ]

        });

    }

    async function listarRubricas() {

        try {

            bloquearTabela();

            const filtros = obterFiltros();

            const resposta = await $.ajax({

                url: API_URL,

                method: 'GET',

                dataType: 'json',

                data: {

                    acao: 'listar',

                    ...filtros

                }

            });

            if (!resposta.sucesso) {

                throw resposta.mensagem;

            }

            tabela.clear();

            tabela.rows.add(resposta.dados);

            tabela.draw();

        }

        catch (erro) {

            mostrarErro(erro);

        }

        finally {

            desbloquearTabela();

        }

    }

    async function carregarResumo() {

        try {

            const resposta = await $.ajax({

                url: API_URL,

                dataType: 'json',

                data: {

                    acao: 'resumo'

                }

            });

            if (!resposta.sucesso) {

                return;

            }

            $('#resumoTotal').text(resposta.dados.total);

            $('#resumoProventos').text(resposta.dados.proventos);

            $('#resumoDescontos').text(resposta.dados.descontos);

            $('#resumoAtivas').text(resposta.dados.ativas);

        }

        catch (e) {

            console.error(e);

        }

    }

    async function carregarEstados() {

        try {

            const resposta = await $.ajax({

                url: API_URL,

                method: 'GET',

                dataType: 'json',

                data: {
                    acao: 'listar_estados'
                }

            });

            if (!resposta.sucesso) {

                return;

            }

            estadosDisponiveis = Array.isArray(resposta.dados)
                ? resposta.dados
                : [];

            let htmlFiltro =
                '<option value="">Todos</option>';

            estadosDisponiveis.forEach(item => {

                htmlFiltro += `
                <option value="${escaparHtml(item.id)}">
                    ${escaparHtml(item.nome)}
                </option>
            `;

            });

            $('#filtroEstado').html(
                htmlFiltro
            );

            let htmlFormulario =
                '<option value="">Seleccione</option>';

            estadosDisponiveis.forEach(item => {

                htmlFormulario += `
                <option value="${escaparHtml(item.id)}">
                    ${escaparHtml(item.nome)}
                </option>
            `;

            });

            $('#statusId').html(
                htmlFormulario
            );

        } catch (erro) {

            console.log(erro);

            console.log("Status:", erro.status);

            console.log("Resposta:");

            console.log(erro.responseText);

        }

    }

    function obterFiltros() {

        return {

            codigo: $('#filtroCodigo').val(),

            nome: $('#filtroNome').val(),

            tipo: $('#filtroTipo').val(),

            categoria: $('#filtroCategoria').val(),

            status_id: $('#filtroEstado').val()

        };

    }

    function limparFiltros() {

        $('#filtroCodigo').val('');

        $('#filtroNome').val('');

        $('#filtroTipo').val('');

        $('#filtroCategoria').val('');

        $('#filtroEstado').val('');

        listarRubricas();

    }

    function novaRubrica() {

        limparFormularioRubrica();

        $('#modalRubricaLabel').text(
            'Nova Rubrica Salarial'
        );

        const estadoAtivo = encontrarEstadoAtivo();

        if (estadoAtivo) {

            $('#statusId').val(
                estadoAtivo.id
            );

        }

        $('#permiteEdicao').prop(
            'checked',
            true
        );

        $('#ordem').val(0);

        $('#baseCalculo').val(
            'NENHUMA'
        );

        atualizarCamposModoCalculo();

        elementos.modal.modal('show');

    }
    function bloquearTabela() {

        elementos.tabela.css('opacity', '.4');

    }

    function desbloquearTabela() {

        elementos.tabela.css('opacity', '1');

    }

    function formatarMoeda(valor) {

        const numero = converterNumero(
            valor
        ) ?? 0;

        return new Intl.NumberFormat(
            'pt-MZ',
            {
                style: 'currency',
                currency: 'MZN',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(numero);

    }

    function badgeTipo(tipo) {

        const tipos = {

            PROVENTO: {
                classe: 'badge-success',
                nome: 'Provento'
            },

            DESCONTO: {
                classe: 'badge-danger',
                nome: 'Desconto'
            },

            ENCARGO: {
                classe: 'badge-warning',
                nome: 'Encargo'
            },

            INFORMATIVO: {
                classe: 'badge-info',
                nome: 'Informativo'
            }

        };

        const item = tipos[tipo] ?? {
            classe: 'badge-secondary',
            nome: tipo || '-'
        };

        return `
        <span class="badge ${item.classe}">
            ${escaparHtml(item.nome)}
        </span>
    `;

    }

    function badgeEstado(estado) {

        if (!estado) {

            return '-';

        }

        const valor = String(
            estado
        ).trim().toUpperCase();

        let classe = 'badge-secondary';

        if (
            ['ACTIVA', 'ACTIVO'].includes(valor)
        ) {

            classe = 'badge-success';

        } else if (
            ['INACTIVA', 'INACTIVO'].includes(valor)
        ) {

            classe = 'badge-danger';

        } else if (
            ['PENDENTE'].includes(valor)
        ) {

            classe = 'badge-warning';

        }

        return `
        <span class="badge ${classe}">
            ${escaparHtml(estado)}
        </span>
    `;

    }

    function formatarTextoEnum(valor) {

        if (!valor) {

            return '-';

        }

        return String(valor)
            .toLowerCase()
            .split('_')
            .map(
                palavra =>
                    palavra.charAt(0).toUpperCase()
                    + palavra.slice(1)
            )
            .join(' ');

    }

    function badgeCategoria(categoria) {

        const nome = formatarTextoEnum(
            categoria
        );

        return `
        <span class="badge badge-light">
            ${escaparHtml(nome)}
        </span>
    `;

    }
    function botoesAcao(linha) {

        return `

            <div class="btn-group">

                <button
                    class="btn btn-sm btn-info btnVisualizar"
                    data-id="${linha.id}"
                >
                    <i class="fa fa-eye"></i>
                </button>

                <button
                    class="btn btn-sm btn-primary btnEditar"
                    data-id="${linha.id}"
                >
                    <i class="fa fa-pencil"></i>
                </button>

                <button
                    class="btn btn-sm btn-warning btnEstado"
                    data-id="${linha.id}"
                    data-status="${linha.status_nome}"
                >
                    <i class="fa fa-refresh"></i>
                </button>

            </div>

        `;

    }

    function mostrarErro(erro) {

        let mensagem =
            'Ocorreu um erro inesperado.';

        if (
            typeof erro === 'string'
        ) {

            mensagem = erro;

        } else if (
            erro instanceof Error
        ) {

            mensagem = erro.message;

        } else if (
            erro?.responseJSON?.mensagem
        ) {

            mensagem =
                erro.responseJSON.mensagem;

        } else if (
            erro?.mensagem
        ) {

            mensagem = erro.mensagem;

        } else if (
            erro?.status === 401
        ) {

            mensagem =
                'A sessão expirou. Inicie sessão novamente.';

        } else if (
            erro?.status === 403
        ) {

            mensagem =
                'Não possui permissão para realizar esta operação.';

        } else if (
            erro?.status === 500
        ) {

            mensagem =
                'O servidor encontrou um erro interno.';

        }

        Swal.fire({

            icon: 'error',

            title: 'Erro',

            text: mensagem

        });

    }



    function aplicarRegraCategoria(categoria) {

        if (!categoria) {

            return;

        }

        const regras = {

            SALARIO_BASE: {
                tipo: 'PROVENTO',
                modoCalculo: 'VALOR_FIXO',
                baseCalculo: 'NENHUMA',
                obrigatoria: true,
                recorrente: true,
                tributavelInss: true,
                tributavelIrps: true
            },

            SUBSIDIO_ALIMENTACAO: {
                tipo: 'PROVENTO',
                modoCalculo: 'VALOR_FIXO',
                baseCalculo: 'NENHUMA',
                obrigatoria: false,
                recorrente: true,
                tributavelInss: false,
                tributavelIrps: false
            },

            SUBSIDIO_TRANSPORTE: {
                tipo: 'PROVENTO',
                modoCalculo: 'VALOR_FIXO',
                baseCalculo: 'NENHUMA',
                obrigatoria: false,
                recorrente: true,
                tributavelInss: false,
                tributavelIrps: false
            },

            SUBSIDIO_COMUNICACAO: {
                tipo: 'PROVENTO',
                modoCalculo: 'VALOR_FIXO',
                baseCalculo: 'NENHUMA',
                obrigatoria: false,
                recorrente: true,
                tributavelInss: false,
                tributavelIrps: false
            },

            SUBSIDIO_TURNO: {
                tipo: 'PROVENTO',
                modoCalculo: 'VALOR_FIXO',
                baseCalculo: 'NENHUMA',
                obrigatoria: false,
                recorrente: true,
                tributavelInss: true,
                tributavelIrps: true
            },

            SUBSIDIO_RISCO: {
                tipo: 'PROVENTO',
                modoCalculo: 'PERCENTAGEM',
                baseCalculo: 'SALARIO_BASE',
                obrigatoria: false,
                recorrente: true,
                tributavelInss: true,
                tributavelIrps: true
            },

            SUBSIDIO_OUTRO: {
                tipo: 'PROVENTO',
                modoCalculo: 'VALOR_FIXO',
                baseCalculo: 'NENHUMA',
                obrigatoria: false,
                recorrente: true,
                tributavelInss: false,
                tributavelIrps: false
            },

            HORA_EXTRA: {
                tipo: 'PROVENTO',
                modoCalculo: 'QUANTIDADE_VALOR',
                baseCalculo: 'VALOR_HORA',
                obrigatoria: false,
                recorrente: false,
                tributavelInss: true,
                tributavelIrps: true
            },

            BONUS: {
                tipo: 'PROVENTO',
                modoCalculo: 'MANUAL',
                baseCalculo: 'NENHUMA',
                obrigatoria: false,
                recorrente: false,
                tributavelInss: true,
                tributavelIrps: true
            },

            PREMIO: {
                tipo: 'PROVENTO',
                modoCalculo: 'MANUAL',
                baseCalculo: 'NENHUMA',
                obrigatoria: false,
                recorrente: false,
                tributavelInss: true,
                tributavelIrps: true
            },

            COMISSAO: {
                tipo: 'PROVENTO',
                modoCalculo: 'PERCENTAGEM',
                baseCalculo: 'TOTAL_PROVENTOS',
                obrigatoria: false,
                recorrente: false,
                tributavelInss: true,
                tributavelIrps: true
            },

            FALTA: {
                tipo: 'DESCONTO',
                modoCalculo: 'QUANTIDADE_VALOR',
                baseCalculo: 'VALOR_DIA',
                obrigatoria: false,
                recorrente: false,
                tributavelInss: false,
                tributavelIrps: false
            },

            ATRASO: {
                tipo: 'DESCONTO',
                modoCalculo: 'QUANTIDADE_VALOR',
                baseCalculo: 'VALOR_HORA',
                obrigatoria: false,
                recorrente: false,
                tributavelInss: false,
                tributavelIrps: false
            },

            INSS: {
                tipo: 'DESCONTO',
                modoCalculo: 'PERCENTAGEM',
                baseCalculo: 'SALARIO_BRUTO',
                obrigatoria: true,
                recorrente: true,
                tributavelInss: false,
                tributavelIrps: false
            },

            INSS_PATRONAL: {
                tipo: 'ENCARGO',
                modoCalculo: 'PERCENTAGEM',
                baseCalculo: 'SALARIO_BRUTO',
                obrigatoria: true,
                recorrente: true,
                tributavelInss: false,
                tributavelIrps: false
            },

            IRPS: {
                tipo: 'DESCONTO',
                modoCalculo: 'FORMULA',
                baseCalculo: 'SALARIO_BRUTO',
                obrigatoria: true,
                recorrente: true,
                tributavelInss: false,
                tributavelIrps: false
            },

            ADIANTAMENTO: {
                tipo: 'DESCONTO',
                modoCalculo: 'VALOR_FIXO',
                baseCalculo: 'NENHUMA',
                obrigatoria: false,
                recorrente: false,
                tributavelInss: false,
                tributavelIrps: false
            },

            EMPRESTIMO: {
                tipo: 'DESCONTO',
                modoCalculo: 'VALOR_FIXO',
                baseCalculo: 'NENHUMA',
                obrigatoria: false,
                recorrente: true,
                tributavelInss: false,
                tributavelIrps: false
            },

            PENSAO: {
                tipo: 'DESCONTO',
                modoCalculo: 'PERCENTAGEM',
                baseCalculo: 'SALARIO_BASE',
                obrigatoria: false,
                recorrente: true,
                tributavelInss: false,
                tributavelIrps: false
            },

            OUTRO: {
                tipo: 'INFORMATIVO',
                modoCalculo: 'MANUAL',
                baseCalculo: 'NENHUMA',
                obrigatoria: false,
                recorrente: false,
                tributavelInss: false,
                tributavelIrps: false
            }

        };

        const regra = regras[categoria];

        if (!regra) {

            return;

        }

        $('#tipo').val(
            regra.tipo
        );

        $('#modoCalculo').val(
            regra.modoCalculo
        );

        $('#baseCalculo').val(
            regra.baseCalculo
        );

        $('#obrigatoria').prop(
            'checked',
            regra.obrigatoria
        );

        $('#recorrente').prop(
            'checked',
            regra.recorrente
        );

        $('#tributavelInss').prop(
            'checked',
            regra.tributavelInss
        );

        $('#tributavelIrps').prop(
            'checked',
            regra.tributavelIrps
        );

        atualizarCamposModoCalculo();

        atualizarRegrasTipo();

    }
    function atualizarCamposModoCalculo() {

        const modo = $('#modoCalculo').val();

        const grupoValor = obterGrupoCampo(
            '#valorPadrao'
        );

        const grupoPercentagem = obterGrupoCampo(
            '#percentagemPadrao'
        );

        const grupoFormula = obterGrupoCampo(
            '#formulaCalculo'
        );

        const grupoBase = obterGrupoCampo(
            '#baseCalculo'
        );

        grupoValor.hide();

        grupoPercentagem.hide();

        grupoFormula.hide();

        grupoBase.hide();

        $('#valorPadrao').prop(
            'required',
            false
        );

        $('#percentagemPadrao').prop(
            'required',
            false
        );

        $('#formulaCalculo').prop(
            'required',
            false
        );

        $('#baseCalculo').prop(
            'required',
            false
        );

        switch (modo) {

            case 'VALOR_FIXO':

                grupoValor.show();

                $('#valorPadrao').prop(
                    'required',
                    true
                );

                $('#baseCalculo').val(
                    'NENHUMA'
                );

                break;

            case 'PERCENTAGEM':

                grupoPercentagem.show();

                grupoBase.show();

                $('#percentagemPadrao').prop(
                    'required',
                    true
                );

                $('#baseCalculo').prop(
                    'required',
                    true
                );

                if (
                    $('#baseCalculo').val() === 'NENHUMA'
                ) {

                    $('#baseCalculo').val(
                        'SALARIO_BASE'
                    );

                }

                break;

            case 'QUANTIDADE_VALOR':

                grupoValor.show();

                grupoBase.show();

                $('#valorPadrao').prop(
                    'required',
                    false
                );

                $('#baseCalculo').prop(
                    'required',
                    true
                );

                break;

            case 'FORMULA':

                grupoFormula.show();

                grupoBase.show();

                $('#formulaCalculo').prop(
                    'required',
                    true
                );

                $('#baseCalculo').prop(
                    'required',
                    true
                );

                break;

            case 'MANUAL':

                grupoValor.show();

                $('#valorPadrao').prop(
                    'required',
                    false
                );

                $('#baseCalculo').val(
                    'NENHUMA'
                );

                break;

            default:

                grupoValor.show();

                grupoPercentagem.show();

                grupoFormula.show();

                grupoBase.show();

                break;

        }

        atualizarAjudaModoCalculo(
            modo
        );

    }

    function obterGrupoCampo(seletor) {

        const campo = $(seletor);

        const grupo = campo.closest(
            '.form-group'
        );

        return grupo.length
            ? grupo
            : campo.parent();

    }

    function atualizarRegrasTipo() {

        const tipo = $('#tipo').val();

        const categoria = $('#categoria').val();

        if (tipo === 'INFORMATIVO') {

            $('#tributavelInss').prop(
                'checked',
                false
            );

            $('#tributavelIrps').prop(
                'checked',
                false
            );

        }

        if (
            tipo === 'DESCONTO'
            && !categoria
        ) {

            $('#tributavelInss').prop(
                'checked',
                false
            );

            $('#tributavelIrps').prop(
                'checked',
                false
            );

        }

    }

    function atualizarAjudaModoCalculo(modo) {

        const mensagens = {

            VALOR_FIXO:
                'Será utilizado um valor monetário fixo.',

            PERCENTAGEM:
                'O valor será calculado aplicando a percentagem sobre a base seleccionada.',

            QUANTIDADE_VALOR:
                'O processamento multiplicará uma quantidade pelo valor unitário ou pela base definida.',

            FORMULA:
                'O valor será determinado através de uma fórmula configurada.',

            MANUAL:
                'O valor será introduzido manualmente durante o processamento salarial.'

        };

        $('#ajudaModoCalculo').text(
            mensagens[modo] ?? ''
        );

    }

    function atualizarAjudaBaseCalculo() {

        const base = $('#baseCalculo').val();

        const mensagens = {

            NENHUMA:
                'Esta rubrica não depende de outra base de cálculo.',

            SALARIO_BASE:
                'O cálculo utilizará o salário-base do funcionário.',

            TOTAL_PROVENTOS:
                'O cálculo utilizará a soma dos proventos processados.',

            SALARIO_BRUTO:
                'O cálculo utilizará o salário bruto antes dos descontos.',

            VALOR_HORA:
                'O cálculo utilizará o valor correspondente a uma hora de trabalho.',

            VALOR_DIA:
                'O cálculo utilizará o valor correspondente a um dia de trabalho.'

        };

        $('#ajudaBaseCalculo').text(
            mensagens[base] ?? ''
        );

    }



    /* ===========================================================
     * CRUD
     * ===========================================================*/

    function eventos() {

        elementos.btnPesquisar.on('click', listarRubricas);

        elementos.btnLimpar.on('click', limparFiltros);

        elementos.btnNova.on('click', novaRubrica);

        elementos.form.on('submit', guardarRubrica);

        elementos.tabela.on('click', '.btnEditar', function () {

            editarRubrica(
                Number($(this).data('id'))
            );

        });

        elementos.tabela.on('click', '.btnVisualizar', function () {

            visualizarRubrica(
                Number($(this).data('id'))
            );

        });

        elementos.tabela.on('click', '.btnEstado', function () {

            alterarEstado(
                Number($(this).data('id')),
                String($(this).data('status') ?? '')
            );

        });

        $('#categoria').on('change', function () {

            aplicarRegraCategoria(
                $(this).val()
            );

        });

        $('#modoCalculo').on('change', function () {

            atualizarCamposModoCalculo();

        });

        $('#tipo').on('change', function () {

            atualizarRegrasTipo();

        });

        $('#baseCalculo').on('change', function () {

            atualizarAjudaBaseCalculo();

        });

        $('#codigo').on('input', function () {

            const valor = String($(this).val() ?? '')
                .toUpperCase()
                .replace(/[^A-Z0-9_]/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_+|_+$/g, '');

            $(this).val(valor);

        });

        $('#percentagemPadrao').on('input', function () {

            validarPercentagemCampo();

        });

        $('#valorPadrao').on('input', function () {

            validarValorCampo();

        });

        $('#filtroCodigo, #filtroNome').on('keydown', function (evento) {

            if (evento.key === 'Enter') {

                evento.preventDefault();

                listarRubricas();

            }

        });

        elementos.modal.on('hidden.bs.modal', function () {

            limparFormularioRubrica();

        });

    }
    async function editarRubrica(id) {

        try {

            const resposta = await $.ajax({

                url: API_URL,

                dataType: 'json',

                data: {

                    acao: 'buscar',

                    id

                }

            });

            if (!resposta.sucesso) {

                throw resposta.mensagem;

            }

            preencherFormulario(resposta.dados);

            $('#modalRubricaLabel').text('Editar Rubrica');

            elementos.modal.modal('show');

        }

        catch (erro) {

            mostrarErro(erro);

        }

    }

    async function visualizarRubrica(id) {

        try {

            const resposta = await $.ajax({

                url: API_URL,

                dataType: 'json',

                data: {

                    acao: 'buscar',

                    id

                }

            });

            if (!resposta.sucesso) {

                throw resposta.mensagem;

            }

            const r = resposta.dados;

            $('#visualizarCodigo').text(r.codigo);

            $('#visualizarNome').text(r.nome);

            $('#visualizarTipo').text(r.tipo);

            $('#visualizarCategoria').text(r.categoria);

            $('#visualizarEstado').text(r.status_nome);

            $('#visualizarModoCalculo').text(r.modo_calculo);

            $('#visualizarBaseCalculo').text(r.base_calculo);

            $('#visualizarValorPadrao').text(formatarMoeda(r.valor_padrao));

            $('#visualizarPercentagem').text(r.percentagem_padrao);

            $('#visualizarOrdem').text(r.ordem);

            $('#visualizarTributavelInss').text(r.tributavel_inss == 1 ? 'Sim' : 'Não');

            $('#visualizarTributavelIrps').text(r.tributavel_irps == 1 ? 'Sim' : 'Não');

            $('#visualizarObrigatoria').text(r.obrigatoria == 1 ? 'Sim' : 'Não');

            $('#visualizarRecorrente').text(r.recorrente == 1 ? 'Sim' : 'Não');

            $('#visualizarPermiteEdicao').text(r.permite_edicao == 1 ? 'Sim' : 'Não');

            $('#visualizarFormula').text(r.formula_calculo ?? '-');

            $('#visualizarDescricao').text(r.descricao ?? '-');

            elementos.modalVisualizar.modal('show');

        }

        catch (erro) {

            mostrarErro(erro);

        }

    }

    async function guardarRubrica(evento) {

        evento.preventDefault();

        if (!validarFormularioRubrica()) {

            return;

        }

        const botaoGuardar = elementos.form.find(
            '[type="submit"]'
        );

        const textoOriginal = botaoGuardar.html();

        try {

            botaoGuardar
                .prop('disabled', true)
                .html(
                    '<i class="fa fa-spinner fa-spin"></i> A guardar...'
                );

            const id = Number(
                $('#rubricaId').val() || 0
            );

            const dados = serializarFormulario();

            dados.acao = id > 0
                ? 'atualizar'
                : 'criar';

            const resposta = await $.ajax({

                url: API_URL,

                method: 'POST',

                dataType: 'json',

                data: dados

            });

            if (!resposta.sucesso) {

                throw new Error(
                    resposta.mensagem
                    || 'Não foi possível guardar a rubrica.'
                );

            }

            elementos.modal.modal('hide');

            await Swal.fire({

                icon: 'success',

                title: 'Sucesso',

                text: resposta.mensagem,

                timer: 1600,

                showConfirmButton: false

            });

            await Promise.all([

                listarRubricas(),

                carregarResumo()

            ]);

        } catch (erro) {

            mostrarErro(erro);

        } finally {

            botaoGuardar
                .prop('disabled', false)
                .html(textoOriginal);

        }

    }

    function validarFormularioRubrica() {

        limparErrosFormulario();

        const codigo = String(
            $('#codigo').val() ?? ''
        ).trim();

        const nome = String(
            $('#nome').val() ?? ''
        ).trim();

        const tipo = $('#tipo').val();

        const categoria = $('#categoria').val();

        const modo = $('#modoCalculo').val();

        const base = $('#baseCalculo').val();

        const statusId = Number(
            $('#statusId').val() || 0
        );

        const erros = [];

        if (!codigo) {

            erros.push({
                campo: '#codigo',
                mensagem: 'Informe o código da rubrica.'
            });

        } else if (codigo.length > 30) {

            erros.push({
                campo: '#codigo',
                mensagem: 'O código não pode ultrapassar 30 caracteres.'
            });

        }

        if (!nome) {

            erros.push({
                campo: '#nome',
                mensagem: 'Informe o nome da rubrica.'
            });

        } else if (nome.length > 120) {

            erros.push({
                campo: '#nome',
                mensagem: 'O nome não pode ultrapassar 120 caracteres.'
            });

        }

        if (!tipo) {

            erros.push({
                campo: '#tipo',
                mensagem: 'Seleccione o tipo da rubrica.'
            });

        }

        if (!categoria) {

            erros.push({
                campo: '#categoria',
                mensagem: 'Seleccione a categoria da rubrica.'
            });

        }

        if (!modo) {

            erros.push({
                campo: '#modoCalculo',
                mensagem: 'Seleccione o modo de cálculo.'
            });

        }

        if (statusId <= 0) {

            erros.push({
                campo: '#statusId',
                mensagem: 'Seleccione o estado da rubrica.'
            });

        }

        if (
            modo === 'VALOR_FIXO'
            && !numeroValido(
                $('#valorPadrao').val(),
                true
            )
        ) {

            erros.push({
                campo: '#valorPadrao',
                mensagem: 'Informe um valor padrão válido.'
            });

        }

        if (modo === 'PERCENTAGEM') {

            const percentagem = converterNumero(
                $('#percentagemPadrao').val()
            );

            if (
                percentagem === null
                || percentagem < 0
                || percentagem > 100
            ) {

                erros.push({
                    campo: '#percentagemPadrao',
                    mensagem: 'Informe uma percentagem entre 0 e 100.'
                });

            }

        }

        if (
            ['PERCENTAGEM', 'QUANTIDADE_VALOR', 'FORMULA']
                .includes(modo)
            && (
                !base
                || base === 'NENHUMA'
            )
        ) {

            erros.push({
                campo: '#baseCalculo',
                mensagem: 'Seleccione uma base de cálculo válida.'
            });

        }

        if (
            modo === 'FORMULA'
            && !String(
                $('#formulaCalculo').val() ?? ''
            ).trim()
        ) {

            erros.push({
                campo: '#formulaCalculo',
                mensagem: 'Informe a fórmula de cálculo.'
            });

        }

        if (erros.length > 0) {

            erros.forEach(erro => {

                marcarCampoInvalido(
                    erro.campo,
                    erro.mensagem
                );

            });

            const primeiroCampo = $(
                erros[0].campo
            );

            primeiroCampo.trigger(
                'focus'
            );

            Swal.fire({

                icon: 'warning',

                title: 'Verifique os dados',

                text: erros[0].mensagem

            });

            return false;

        }

        return true;

    }

    function marcarCampoInvalido(
        seletor,
        mensagem
    ) {

        const campo = $(seletor);

        campo.addClass(
            'is-invalid'
        );

        const grupo = campo.closest(
            '.form-group'
        );

        grupo.find(
            '.invalid-feedback.rubrica-validacao'
        ).remove();

        grupo.append(`
        <div class="invalid-feedback rubrica-validacao">
            ${escaparHtml(mensagem)}
        </div>
    `);

    }

    function limparErrosFormulario() {

        elementos.form
            .find('.is-invalid')
            .removeClass('is-invalid');

        elementos.form
            .find('.rubrica-validacao')
            .remove();

    }

    function validarPercentagemCampo() {

        const campo = $('#percentagemPadrao');

        const valor = converterNumero(
            campo.val()
        );

        campo.removeClass(
            'is-invalid'
        );

        if (
            valor !== null
            && (
                valor < 0
                || valor > 100
            )
        ) {

            campo.addClass(
                'is-invalid'
            );

        }

    }

    function validarValorCampo() {

        const campo = $('#valorPadrao');

        const valor = converterNumero(
            campo.val()
        );

        campo.removeClass(
            'is-invalid'
        );

        if (
            valor !== null
            && valor < 0
        ) {

            campo.addClass(
                'is-invalid'
            );

        }

    }

    function converterNumero(valor) {

        if (
            valor === null
            || valor === undefined
            || String(valor).trim() === ''
        ) {

            return null;

        }

        let texto = String(valor)
            .trim()
            .replace(/\s/g, '');

        if (
            texto.includes(',')
            && texto.includes('.')
        ) {

            if (
                texto.lastIndexOf(',')
                > texto.lastIndexOf('.')
            ) {

                texto = texto
                    .replace(/\./g, '')
                    .replace(',', '.');

            } else {

                texto = texto
                    .replace(/,/g, '');

            }

        } else {

            texto = texto.replace(
                ',',
                '.'
            );

        }

        const numero = Number(texto);

        return Number.isFinite(numero)
            ? numero
            : null;

    }

    function numeroValido(
        valor,
        permitirZero = false
    ) {

        const numero = converterNumero(
            valor
        );

        if (numero === null) {

            return false;

        }

        return permitirZero
            ? numero >= 0
            : numero > 0;

    }

    function limparFormularioRubrica() {

        elementos.form.trigger(
            'reset'
        );

        limparErrosFormulario();

        $('#rubricaId').val('');

        $('#rubricaUuid').val('');

        $('#codigo').val('');

        $('#nome').val('');

        $('#descricao').val('');

        $('#tipo').val('');

        $('#categoria').val('');

        $('#modoCalculo').val('');

        $('#baseCalculo').val(
            'NENHUMA'
        );

        $('#valorPadrao').val(
            '0.00'
        );

        $('#percentagemPadrao').val(
            '0.0000'
        );

        $('#formulaCalculo').val('');

        $('#ordem').val(0);

        $('#statusId').val('');

        $('#tributavelInss').prop(
            'checked',
            false
        );

        $('#tributavelIrps').prop(
            'checked',
            false
        );

        $('#obrigatoria').prop(
            'checked',
            false
        );

        $('#recorrente').prop(
            'checked',
            false
        );

        $('#permiteEdicao').prop(
            'checked',
            true
        );

        $('#ajudaModoCalculo').text('');

        $('#ajudaBaseCalculo').text('');

    }

    function encontrarEstadoAtivo() {

        return estadosDisponiveis.find(
            estado => {

                const nome = String(
                    estado.nome ?? ''
                ).trim().toUpperCase();

                const codigo = String(
                    estado.codigo ?? ''
                ).trim().toUpperCase();

                return [
                    'ACTIVO',
                    'ACTIVA'
                ].includes(nome)
                    || [
                        'ACTIVO',
                        'ACTIVA'
                    ].includes(codigo);

            }
        ) ?? null;

    }
    async function alterarEstado(id, estadoAtual) {

        const acao =

            estadoAtual.toUpperCase() === 'ACTIVA'

                ||

                estadoAtual.toUpperCase() === 'ACTIVO'

                ? 'inativar'

                : 'ativar';

        const confirmacao = await Swal.fire({

            icon: 'question',

            title: 'Confirma?',

            text: 'Deseja alterar o estado desta rubrica?',

            showCancelButton: true,

            confirmButtonText: 'Sim',

            cancelButtonText: 'Cancelar'

        });

        if (!confirmacao.isConfirmed) {

            return;

        }

        try {

            const resposta = await $.ajax({

                url: API_URL,

                method: 'POST',

                dataType: 'json',

                data: {

                    acao,

                    id

                }

            });

            if (!resposta.sucesso) {

                throw resposta.mensagem;

            }

            Swal.fire({

                icon: 'success',

                title: 'Sucesso',

                text: resposta.mensagem,

                timer: 1500,

                showConfirmButton: false

            });

            listarRubricas();

            carregarResumo();

        }

        catch (erro) {

            mostrarErro(erro);

        }

    }

    function preencherFormulario(rubrica) {

        limparFormularioRubrica();

        $('#rubricaId').val(
            rubrica.id ?? ''
        );

        $('#rubricaUuid').val(
            rubrica.uuid ?? ''
        );

        $('#codigo').val(
            rubrica.codigo ?? ''
        );

        $('#nome').val(
            rubrica.nome ?? ''
        );

        $('#descricao').val(
            rubrica.descricao ?? ''
        );

        $('#tipo').val(
            rubrica.tipo ?? ''
        );

        $('#categoria').val(
            rubrica.categoria ?? ''
        );

        $('#modoCalculo').val(
            rubrica.modo_calculo ?? ''
        );

        $('#baseCalculo').val(
            rubrica.base_calculo ?? 'NENHUMA'
        );

        $('#valorPadrao').val(
            rubrica.valor_padrao ?? '0.00'
        );

        $('#percentagemPadrao').val(
            rubrica.percentagem_padrao ?? '0.0000'
        );

        $('#formulaCalculo').val(
            rubrica.formula_calculo ?? ''
        );

        $('#ordem').val(
            rubrica.ordem ?? 0
        );

        $('#statusId').val(
            rubrica.status_id ?? ''
        );

        $('#tributavelInss').prop(
            'checked',
            converterBooleano(
                rubrica.tributavel_inss
            )
        );

        $('#tributavelIrps').prop(
            'checked',
            converterBooleano(
                rubrica.tributavel_irps
            )
        );

        $('#obrigatoria').prop(
            'checked',
            converterBooleano(
                rubrica.obrigatoria
            )
        );

        $('#recorrente').prop(
            'checked',
            converterBooleano(
                rubrica.recorrente
            )
        );

        $('#permiteEdicao').prop(
            'checked',
            converterBooleano(
                rubrica.permite_edicao
            )
        );

        atualizarCamposModoCalculo();

        atualizarRegrasTipo();

        atualizarAjudaBaseCalculo();

    }

    function converterBooleano(valor) {

        return [
            true,
            1,
            '1',
            'true',
            'SIM',
            'sim'
        ].includes(valor);

    }

    function escaparHtml(valor) {

        return String(
            valor ?? ''
        )
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    }

    function serializarFormulario() {

        return {

            id: Number(
                $('#rubricaId').val() || 0
            ),

            uuid: String(
                $('#rubricaUuid').val() ?? ''
            ).trim(),

            codigo: String(
                $('#codigo').val() ?? ''
            ).trim().toUpperCase(),

            nome: String(
                $('#nome').val() ?? ''
            ).trim(),

            descricao: String(
                $('#descricao').val() ?? ''
            ).trim(),

            tipo: $('#tipo').val(),

            categoria: $('#categoria').val(),

            modo_calculo:
                $('#modoCalculo').val(),

            base_calculo:
                $('#baseCalculo').val()
                || 'NENHUMA',

            valor_padrao:
                converterNumero(
                    $('#valorPadrao').val()
                ) ?? 0,

            percentagem_padrao:
                converterNumero(
                    $('#percentagemPadrao').val()
                ) ?? 0,

            formula_calculo: String(
                $('#formulaCalculo').val() ?? ''
            ).trim(),

            ordem: Number(
                $('#ordem').val() || 0
            ),

            status_id: Number(
                $('#statusId').val() || 0
            ),

            tributavel_inss:
                $('#tributavelInss')
                    .is(':checked')
                    ? 1
                    : 0,

            tributavel_irps:
                $('#tributavelIrps')
                    .is(':checked')
                    ? 1
                    : 0,

            obrigatoria:
                $('#obrigatoria')
                    .is(':checked')
                    ? 1
                    : 0,

            recorrente:
                $('#recorrente')
                    .is(':checked')
                    ? 1
                    : 0,

            permite_edicao:
                $('#permiteEdicao')
                    .is(':checked')
                    ? 1
                    : 0

        };

    }

    return {

        iniciar

    };
})();


$(function () {

    RubricasSalariais.iniciar();

});