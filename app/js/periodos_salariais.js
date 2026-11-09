'use strict';

/**
 * ============================================================
 * MÓDULO: PERÍODOS SALARIAIS
 * ============================================================
 *
 * Dependências:
 * - jQuery
 * - DataTables
 * - Bootstrap
 * - SweetAlert2
 * - Select2 (opcional)
 */

const PeriodoSalarial = (() => {

    /**
     * ========================================================
     * CONFIGURAÇÕES
     * ========================================================
     */
    const config = {
        endpoint: '/erpplus/app/ajax/periodos_salariais_ajax.php',

        tabela: '#periodosSalariaisTable',

        modalFormulario: '#modalPeriodoSalarial',
        formulario: '#formPeriodoSalarial',
        botaoGuardar: '#btnGuardarPeriodo',
        botaoNovo: '#btnNovoPeriodo',

        modalCancelamento: '#modalCancelarPeriodo',
        formularioCancelamento: '#formCancelarPeriodo',
        botaoConfirmarCancelamento: '#btnConfirmarCancelamento',

        filtroAno: '#filtroAno',
        filtroMes: '#filtroMes',
        filtroEstado: '#filtroEstado',

        botaoFiltrar: '#btnFiltrarPeriodos',
        botaoLimparFiltros: '#btnLimparFiltros'
    };

    let tabela = null;
    let periodoAtualId = null;

    const meses = {
        1: 'Janeiro',
        2: 'Fevereiro',
        3: 'Março',
        4: 'Abril',
        5: 'Maio',
        6: 'Junho',
        7: 'Julho',
        8: 'Agosto',
        9: 'Setembro',
        10: 'Outubro',
        11: 'Novembro',
        12: 'Dezembro'
    };

    /**
     * ========================================================
     * INICIALIZAÇÃO
     * ========================================================
     */
    function iniciar() {

        // Este ficheiro pode estar carregado globalmente.
        // Se a página atual não for Períodos Salariais,
        // não inicializa o módulo.
        if (!document.querySelector(config.tabela)) {
            return;
        }

        iniciarSelect2();
        iniciarTabela();
        carregarEstados();
        carregarAnos();
        carregarResumo();
        bindEventos();
    }

    /**
     * ========================================================
     * DATATABLE
     * ========================================================
     */
    function iniciarTabela() {
        const elemento = document.querySelector(config.tabela);

        if (!elemento) {
            return;
        }

        if (!window.jQuery || !$.fn.DataTable) {
            console.error('DataTables não está carregado.');
            return;
        }

        tabela = $(config.tabela).DataTable({
            processing: true,
            serverSide: false,
            responsive: true,
            autoWidth: false,
            destroy: true,

            order: [
                [2, 'desc'],
                [3, 'desc']
            ],

            ajax: {
                url: config.endpoint,
                type: 'GET',

                data: function (dados) {
                    dados.acao = 'listar';
                    dados.ano = obterValor(config.filtroAno);
                    dados.mes = obterValor(config.filtroMes);
                    dados.status_id = obterValor(config.filtroEstado);
                },

                dataSrc: function (resposta) {
                    if (!resposta || resposta.sucesso !== true) {
                        mostrarErro(
                            resposta?.mensagem ||
                            'Não foi possível carregar os períodos salariais.'
                        );

                        return [];
                    }

                    if (Array.isArray(resposta.data)) {
                        return resposta.data;
                    }

                    if (Array.isArray(resposta.dados)) {
                        return resposta.dados;
                    }

                    return [];
                },

                error: function (xhr) {
                    const mensagem =
                        xhr?.responseJSON?.mensagem ||
                        'Não foi possível carregar os períodos salariais.';

                    mostrarErro(mensagem);
                }
            },

            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',

                    render: function (_data, _type, _row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },

                {
                    data: 'codigo',
                    defaultContent: '-',

                    render: function (valor) {
                        return escaparHtml(valor || '-');
                    }
                },

                {
                    data: 'ano',
                    className: 'text-center',
                    defaultContent: '-'
                },

                {
                    data: 'mes',
                    className: 'text-center',

                    render: function (valor, tipo) {
                        if (tipo === 'sort' || tipo === 'type') {
                            return Number(valor) || 0;
                        }

                        return escaparHtml(
                            meses[Number(valor)] || valor || '-'
                        );
                    }
                },

                {
                    data: null,

                    render: function (_valor, _tipo, linha) {
                        return `
                            ${formatarData(linha.data_inicio)}
                            até
                            ${formatarData(linha.data_fim)}
                        `;
                    }
                },

                {
                    data: 'data_prevista_pagamento',
                    className: 'text-center',

                    render: function (valor) {
                        return formatarData(valor);
                    }
                },

                {
                    data: 'dias_uteis',
                    className: 'text-center',
                    defaultContent: '0'
                },

                {
                    data: null,
                    className: 'text-center',

                    render: function (_valor, _tipo, linha) {
                        return renderizarEstado(linha);
                    }
                },

                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-right',

                    render: function (_valor, _tipo, linha) {
                        return renderizarAcoes(linha);
                    }
                }
            ],

            language: {
                emptyTable: 'Nenhum período salarial encontrado.',
                info: 'A mostrar _START_ a _END_ de _TOTAL_ registos',
                infoEmpty: 'A mostrar 0 a 0 de 0 registos',
                infoFiltered: '(filtrado de _MAX_ registos)',
                lengthMenu: 'Mostrar _MENU_ registos',
                loadingRecords: 'A carregar...',
                processing: 'A processar...',
                search: 'Pesquisar:',
                zeroRecords: 'Nenhum registo correspondente encontrado.',

                paginate: {
                    first: 'Primeiro',
                    last: 'Último',
                    next: 'Seguinte',
                    previous: 'Anterior'
                }
            }
        });
    }

    function atualizarTabela() {
        if (tabela && tabela.ajax) {
            tabela.ajax.reload(null, false);
        }
    }

    /**
     * ========================================================
     * RESUMO
     * ========================================================
     */
    async function carregarResumo() {
        try {
            const resposta = await requisicao(
                `${config.endpoint}?acao=resumo`
            );

            const resumo = resposta.dados || {};

            atualizarTexto('#resumoTotal', resumo.total ?? 0);
            atualizarTexto('#resumoAbertos', resumo.abertos ?? 0);

            atualizarTexto(
                '#resumoProcessamento',
                resumo.em_processamento ?? 0
            );

            atualizarTexto(
                '#resumoProcessados',
                resumo.processados ?? 0
            );

            atualizarTexto(
                '#resumoFechados',
                resumo.fechados ?? 0
            );

            atualizarTexto(
                '#resumoCancelados',
                resumo.cancelados ?? 0
            );

        } catch (erro) {
            console.error(
                '[Períodos Salariais][Resumo]',
                erro
            );
        }
    }

    /**
     * ========================================================
     * ESTADOS
     * ========================================================
     */
    async function carregarEstados() {
        const select = document.querySelector(
            config.filtroEstado
        );

        if (!select) {
            return;
        }

        try {
            const resposta = await requisicao(
                `${config.endpoint}?acao=listar_estados`
            );

            const estados = Array.isArray(resposta.dados)
                ? resposta.dados
                : [];

            const valorAtual = select.value;

            select.innerHTML = `
                <option value="">Todos os estados</option>
            `;

            estados.forEach((estado) => {
                const option = document.createElement('option');

                option.value = estado.id;

                option.textContent =
                    estado.nome ||
                    estado.codigo ||
                    `Estado ${estado.id}`;

                select.appendChild(option);
            });

            select.value = valorAtual;
            atualizarSelect2(select);

        } catch (erro) {
            console.error(
                '[Períodos Salariais][Estados]',
                erro
            );
        }
    }

    /**
     * ========================================================
     * ANOS
     * ========================================================
     */
    async function carregarAnos() {
        const select = document.querySelector(
            config.filtroAno
        );

        if (!select) {
            return;
        }

        try {
            const resposta = await requisicao(
                `${config.endpoint}?acao=listar_anos`
            );

            let anos = Array.isArray(resposta.dados)
                ? resposta.dados
                : [];

            anos = anos
                .map((item) => {
                    if (typeof item === 'object') {
                        return Number(item.ano);
                    }

                    return Number(item);
                })
                .filter((ano) => Number.isInteger(ano))
                .sort((a, b) => b - a);

            const anoAtual = new Date().getFullYear();

            if (!anos.includes(anoAtual)) {
                anos.unshift(anoAtual);
            }

            anos = [...new Set(anos)];

            const valorAtual = select.value;

            select.innerHTML = `
                <option value="">Todos os anos</option>
            `;

            anos.forEach((ano) => {
                const option = document.createElement('option');

                option.value = ano;
                option.textContent = ano;

                select.appendChild(option);
            });

            select.value = valorAtual;
            atualizarSelect2(select);

        } catch (erro) {
            console.error(
                '[Períodos Salariais][Anos]',
                erro
            );
        }
    }

    /**
     * ========================================================
     * NOVO PERÍODO
     * ========================================================
     */
    function abrirNovo() {
        periodoAtualId = null;

        limparFormulario();

        atualizarTexto(
            '#tituloModalPeriodo',
            'Novo Período Salarial'
        );

        mostrarModal(config.modalFormulario);

        setTimeout(() => {
            document.querySelector('#codigo')?.focus();
        }, 300);
    }

    /**
     * ========================================================
     * EDITAR
     * ========================================================
     */
    async function abrirEditar(id) {
        if (!id || Number(id) <= 0) {
            mostrarErro('Período salarial inválido.');
            return;
        }

        try {
            bloquearPagina(true);

            const resposta = await requisicao(
                `${config.endpoint}?acao=buscar&id=${encodeURIComponent(id)}`
            );

            const periodo = resposta.dados || {};

            periodoAtualId = Number(
                periodo.id || id
            );

            preencherFormulario(periodo);

            atualizarTexto(
                '#tituloModalPeriodo',
                'Editar Período Salarial'
            );

            mostrarModal(config.modalFormulario);

        } catch (erro) {
            mostrarErro(erro.message);

        } finally {
            bloquearPagina(false);
        }
    }

    /**
     * ========================================================
     * GUARDAR
     * ========================================================
     */
    async function guardar(evento) {
        if (evento) {
            evento.preventDefault();
        }

        limparErrosFormulario();

        const dados = obterDadosFormulario();
        const erros = validarFormulario(dados);

        if (Object.keys(erros).length > 0) {
            apresentarErrosFormulario(erros);

            mostrarAviso(
                'Verifique os campos obrigatórios.'
            );

            return;
        }

        const id = Number(
            dados.id ||
            periodoAtualId ||
            0
        );

        if (id > 0) {
            dados.acao = 'atualizar';
            dados.id = id;
        } else {
            dados.acao = 'criar';
            delete dados.id;
        }

        try {
            alterarEstadoBotao(
                config.botaoGuardar,
                true,
                'A guardar...'
            );

            const resposta = await requisicao(
                config.endpoint,
                {
                    method: 'POST',
                    body: dados
                }
            );

            esconderModal(config.modalFormulario);

            await mostrarSucesso(
                resposta.mensagem ||
                'Período salarial guardado com sucesso.'
            );

            atualizarModulo();

        } catch (erro) {
            if (
                erro.erros &&
                typeof erro.erros === 'object'
            ) {
                apresentarErrosFormulario(
                    erro.erros
                );
            }

            mostrarErro(erro.message);

        } finally {
            alterarEstadoBotao(
                config.botaoGuardar,
                false
            );
        }
    }

    /**
     * ========================================================
     * PROCESSAMENTO
     * ========================================================
     */
    async function processar(id) {
        await executarTransicao({
            id: id,

            acao: 'iniciar_processamento',

            titulo: 'Iniciar processamento?',

            texto:
                'O período será colocado no estado de processamento.',

            confirmar: 'Sim, iniciar'
        });
    }

    async function concluir(id) {
        await executarTransicao({
            id: id,

            acao: 'concluir_processamento',

            titulo: 'Concluir processamento?',

            texto:
                'Confirme que o processamento salarial foi concluído.',

            confirmar: 'Sim, concluir'
        });
    }

    /**
     * ========================================================
     * FECHAR E REABRIR
     * ========================================================
     */
    async function fechar(id) {
        await executarTransicao({
            id: id,

            acao: 'fechar',

            titulo: 'Fechar período salarial?',

            texto:
                'Depois de fechado, o período deixará de aceitar alterações normais.',

            confirmar: 'Sim, fechar'
        });
    }

    async function reabrir(id) {
        await executarTransicao({
            id: id,

            acao: 'reabrir',

            titulo: 'Reabrir período salarial?',

            texto:
                'O período voltará a permitir alterações.',

            confirmar: 'Sim, reabrir'
        });
    }

    async function executarTransicao(opcoes) {
        const confirmado = await confirmarOperacao(
            opcoes.titulo,
            opcoes.texto,
            opcoes.confirmar
        );

        if (!confirmado) {
            return;
        }

        try {
            bloquearPagina(true);

            const resposta = await requisicao(
                config.endpoint,
                {
                    method: 'POST',

                    body: {
                        acao: opcoes.acao,
                        id: Number(opcoes.id)
                    }
                }
            );

            await mostrarSucesso(
                resposta.mensagem ||
                'Operação concluída com sucesso.'
            );

            atualizarModulo();

        } catch (erro) {
            mostrarErro(erro.message);

        } finally {
            bloquearPagina(false);
        }
    }

    /**
     * ========================================================
     * CANCELAMENTO
     * ========================================================
     */
    function abrirCancelamento(id) {
        periodoAtualId = Number(id);

        definirValor(
            '#cancelar_periodo_id',
            id
        );

        definirValor(
            '#motivo_cancelamento',
            ''
        );

        limparErrosFormulario(
            config.formularioCancelamento
        );

        mostrarModal(
            config.modalCancelamento
        );

        setTimeout(() => {
            document
                .querySelector('#motivo_cancelamento')
                ?.focus();
        }, 300);
    }

    async function cancelar(evento) {
        if (evento) {
            evento.preventDefault();
        }

        const id = Number(
            obterValor('#cancelar_periodo_id') ||
            periodoAtualId ||
            0
        );

        const motivo = obterValor(
            '#motivo_cancelamento'
        ).trim();

        limparErrosFormulario(
            config.formularioCancelamento
        );

        if (id <= 0) {
            mostrarErro(
                'Não foi possível identificar o período salarial.'
            );

            return;
        }

        if (motivo.length < 5) {
            apresentarErrosFormulario(
                {
                    motivo_cancelamento:
                        'Informe um motivo com pelo menos 5 caracteres.'
                },
                config.formularioCancelamento
            );

            return;
        }

        const confirmado = await confirmarOperacao(
            'Cancelar período salarial?',
            'Esta operação irá cancelar o período selecionado.',
            'Sim, cancelar'
        );

        if (!confirmado) {
            return;
        }

        try {
            alterarEstadoBotao(
                config.botaoConfirmarCancelamento,
                true,
                'A cancelar...'
            );

            const resposta = await requisicao(
                config.endpoint,
                {
                    method: 'POST',

                    body: {
                        acao: 'cancelar',
                        id: id,
                        motivo_cancelamento: motivo
                    }
                }
            );

            esconderModal(
                config.modalCancelamento
            );

            await mostrarSucesso(
                resposta.mensagem ||
                'Período salarial cancelado com sucesso.'
            );

            atualizarModulo();

        } catch (erro) {
            if (erro.erros) {
                apresentarErrosFormulario(
                    erro.erros,
                    config.formularioCancelamento
                );
            }

            mostrarErro(erro.message);

        } finally {
            alterarEstadoBotao(
                config.botaoConfirmarCancelamento,
                false
            );
        }
    }

    /**
     * ========================================================
     * FORMULÁRIO
     * ========================================================
     */
    function limparFormulario() {
        const formulario = document.querySelector(
            config.formulario
        );

        if (formulario) {
            formulario.reset();
        }

        periodoAtualId = null;

        limparErrosFormulario();

        definirValor('#periodo_id', '');
        definirValor('#ano', new Date().getFullYear());
        definirValor('#mes', '');
        definirValor('#dias_uteis', '');

        atualizarSelect2(
            document.querySelector('#mes')
        );
    }

    function preencherFormulario(periodo) {
        definirValor(
            '#periodo_id',
            periodo.id || ''
        );

        definirValor(
            '#codigo',
            periodo.codigo || ''
        );

        definirValor(
            '#descricao',
            periodo.descricao || ''
        );

        definirValor(
            '#ano',
            periodo.ano || ''
        );

        definirValor(
            '#mes',
            periodo.mes || ''
        );

        definirValor(
            '#data_inicio',
            normalizarDataInput(
                periodo.data_inicio
            )
        );

        definirValor(
            '#data_fim',
            normalizarDataInput(
                periodo.data_fim
            )
        );

        definirValor(
            '#data_limite_assiduidade',
            normalizarDataInput(
                periodo.data_limite_assiduidade
            )
        );

        definirValor(
            '#data_prevista_pagamento',
            normalizarDataInput(
                periodo.data_prevista_pagamento
            )
        );

        definirValor(
            '#dias_uteis',
            periodo.dias_uteis ?? ''
        );

        definirValor(
            '#observacoes',
            periodo.observacoes || ''
        );

        atualizarSelect2(
            document.querySelector('#mes')
        );
    }

    function obterDadosFormulario() {
        const formulario = document.querySelector(
            config.formulario
        );

        if (!formulario) {
            return {};
        }

        const formData = new FormData(formulario);

        const dados = Object.fromEntries(
            formData.entries()
        );

        return {
            id:
                dados.id ||
                obterValor('#periodo_id'),

            codigo:
                String(dados.codigo || '').trim(),

            descricao:
                String(dados.descricao || '').trim(),

            ano:
                String(dados.ano || '').trim(),

            mes:
                String(dados.mes || '').trim(),

            data_inicio:
                String(dados.data_inicio || '').trim(),

            data_fim:
                String(dados.data_fim || '').trim(),

            data_limite_assiduidade:
                String(
                    dados.data_limite_assiduidade || ''
                ).trim(),

            data_prevista_pagamento:
                String(
                    dados.data_prevista_pagamento || ''
                ).trim(),

            dias_uteis:
                String(dados.dias_uteis || '').trim(),

            observacoes:
                String(dados.observacoes || '').trim()
        };
    }

    function validarFormulario(dados) {
        const erros = {};
        const anoAtual = new Date().getFullYear();

        if (!dados.codigo) {
            erros.codigo =
                'Informe o código do período.';

        } else if (
            !/^[A-Za-z0-9._/-]{2,30}$/.test(
                dados.codigo
            )
        ) {
            erros.codigo =
                'O código deve conter entre 2 e 30 caracteres.';
        }

        if (
            !dados.ano ||
            Number(dados.ano) < 2000 ||
            Number(dados.ano) > anoAtual + 10
        ) {
            erros.ano =
                'Informe um ano válido.';
        }

        if (
            !dados.mes ||
            Number(dados.mes) < 1 ||
            Number(dados.mes) > 12
        ) {
            erros.mes =
                'Selecione o mês.';
        }

        if (!dados.data_inicio) {
            erros.data_inicio =
                'Informe a data de início.';
        }

        if (!dados.data_fim) {
            erros.data_fim =
                'Informe a data de fim.';
        }

        if (
            dados.data_inicio &&
            dados.data_fim &&
            dados.data_fim < dados.data_inicio
        ) {
            erros.data_fim =
                'A data de fim não pode ser anterior à data de início.';
        }

        if (
            dados.data_limite_assiduidade &&
            dados.data_inicio &&
            dados.data_limite_assiduidade <
            dados.data_inicio
        ) {
            erros.data_limite_assiduidade =
                'A data limite não pode ser anterior ao início.';
        }

        if (
            dados.data_prevista_pagamento &&
            dados.data_inicio &&
            dados.data_prevista_pagamento <
            dados.data_inicio
        ) {
            erros.data_prevista_pagamento =
                'A data prevista de pagamento não pode ser anterior ao início.';
        }

        if (
            dados.dias_uteis &&
            (
                Number(dados.dias_uteis) < 0 ||
                Number(dados.dias_uteis) > 31
            )
        ) {
            erros.dias_uteis =
                'Informe entre 0 e 31 dias úteis.';
        }

        return erros;
    }

    function apresentarErrosFormulario(
        erros,
        seletorFormulario = config.formulario
    ) {
        const formulario = document.querySelector(
            seletorFormulario
        );

        Object.entries(erros || {}).forEach(
            ([campo, mensagem]) => {

                const elemento =
                    formulario?.querySelector(
                        `[name="${campo}"]`
                    ) ||
                    document.getElementById(campo);

                if (!elemento) {
                    return;
                }

                elemento.classList.add(
                    'is-invalid'
                );

                let feedback =
                    elemento
                        .parentElement
                        ?.querySelector(
                            '.invalid-feedback'
                        );

                if (!feedback) {
                    feedback =
                        document.createElement(
                            'div'
                        );

                    feedback.className =
                        'invalid-feedback';

                    elemento.insertAdjacentElement(
                        'afterend',
                        feedback
                    );
                }

                feedback.textContent =
                    Array.isArray(mensagem)
                        ? mensagem.join(' ')
                        : String(mensagem);
            }
        );
    }

    function limparErrosFormulario(
        seletorFormulario = config.formulario
    ) {
        const formulario = document.querySelector(
            seletorFormulario
        );

        if (!formulario) {
            return;
        }

        formulario
            .querySelectorAll('.is-invalid')
            .forEach((elemento) => {
                elemento.classList.remove(
                    'is-invalid'
                );
            });

        formulario
            .querySelectorAll('.invalid-feedback')
            .forEach((elemento) => {
                elemento.textContent = '';
            });
    }

    /**
     * ========================================================
     * ESTADO
     * ========================================================
     */
    function renderizarEstado(linha) {
        const nome = String(
            linha.status_nome || 'Sem estado'
        ).trim();

        const estado = normalizarEstado(nome);

        const estados = {
            ABERTO: {
                classe: 'primary',
                icone: 'fa fa-folder-open'
            },

            EM_PROCESSAMENTO: {
                classe: 'warning',
                icone: 'fa fa-cogs'
            },

            PROCESSADO: {
                classe: 'info',
                icone: 'fa fa-check-circle'
            },

            FECHADO: {
                classe: 'success',
                icone: 'fa fa-lock'
            },

            CANCELADO: {
                classe: 'danger',
                icone: 'fa fa-ban'
            }
        };

        const configuracao =
            estados[estado] || {
                classe: 'secondary',
                icone: 'fa fa-question-circle'
            };

        return `
        <span
            class="badge badge-${configuracao.classe}"
        >
            <i
                class="${configuracao.icone} mr-1"
            ></i>

            ${escaparHtml(nome)}
        </span>
    `;
    }

    /**
     * ========================================================
     * AÇÕES
     * ========================================================
     */
    function renderizarAcoes(linha) {
        const id = Number(
            linha.id || 0
        );

        const estado = normalizarEstado(
            linha.status_nome
        );

        const podeEditar = [
            '',
            'ABERTO',
            'RASCUNHO',
            'PENDENTE'
        ].includes(estado);

        const podeProcessar = [
            'ABERTO',
            'RASCUNHO',
            'PENDENTE'
        ].includes(estado);

        const podeConcluir = [
            'EM_PROCESSAMENTO',
            'PROCESSAMENTO',
            'A_PROCESSAR'
        ].includes(estado);

        const podeFechar = [
            'PROCESSADO',
            'CONCLUIDO'
        ].includes(estado);

        const podeReabrir =
            estado === 'FECHADO';

        const podeCancelar =
            ![
                'CANCELADO',
                'FECHADO'
            ].includes(estado);

        const itens = [];

        itens.push(`
            <button
                type="button"
                class="dropdown-item"
                data-acao="editar"
                data-id="${id}"
            >
                <i class="fa fa-pencil mr-2"></i>

                ${podeEditar
                ? 'Editar'
                : 'Consultar'
            }
            </button>
        `);

        if (podeProcessar) {
            itens.push(`
                <button
                    type="button"
                    class="dropdown-item"
                    data-acao="processar"
                    data-id="${id}"
                >
                    <i class="fa fa-cogs mr-2"></i>
                    Iniciar processamento
                </button>
            `);
        }

        if (podeConcluir) {
            itens.push(`
                <button
                    type="button"
                    class="dropdown-item"
                    data-acao="concluir"
                    data-id="${id}"
                >
                    <i class="fa fa-check-circle mr-2"></i>
                    Concluir processamento
                </button>
            `);
        }

        if (podeFechar) {
            itens.push(`
                <button
                    type="button"
                    class="dropdown-item"
                    data-acao="fechar"
                    data-id="${id}"
                >
                    <i class="fa fa-lock mr-2"></i>
                    Fechar período
                </button>
            `);
        }

        if (podeReabrir) {
            itens.push(`
                <button
                    type="button"
                    class="dropdown-item"
                    data-acao="reabrir"
                    data-id="${id}"
                >
                    <i class="fa fa-unlock mr-2"></i>
                    Reabrir período
                </button>
            `);
        }

        if (podeCancelar) {
            itens.push(`
                <div class="dropdown-divider"></div>

                <button
                    type="button"
                    class="dropdown-item text-danger"
                    data-acao="cancelar"
                    data-id="${id}"
                >
                    <i class="fa fa-ban mr-2"></i>
                    Cancelar
                </button>
            `);
        }

        return `
            <div class="dropdown dropdown-action">
                <button
                    type="button"
                    class="btn btn-sm btn-white dropdown-toggle"
                    data-toggle="dropdown"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <i class="fa fa-ellipsis-v"></i>
                </button>

                <div
                    class="dropdown-menu dropdown-menu-right"
                >
                    ${itens.join('')}
                </div>
            </div>
        `;
    }

    /**
     * ========================================================
     * EVENTOS
     * ========================================================
     */
    function bindEventos() {
        document.addEventListener(
            'click',
            function (evento) {

                const botaoAcao =
                    evento.target.closest(
                        '[data-acao][data-id]'
                    );

                if (botaoAcao) {
                    evento.preventDefault();

                    const id = Number(
                        botaoAcao.dataset.id
                    );

                    const acao =
                        botaoAcao.dataset.acao;

                    const acoes = {
                        editar: abrirEditar,
                        processar: processar,
                        concluir: concluir,
                        fechar: fechar,
                        reabrir: reabrir,
                        cancelar: abrirCancelamento
                    };

                    if (
                        id > 0 &&
                        typeof acoes[acao] ===
                        'function'
                    ) {
                        acoes[acao](id);
                    }

                    return;
                }

                if (
                    evento.target.closest(
                        config.botaoNovo
                    )
                ) {
                    evento.preventDefault();
                    abrirNovo();
                    return;
                }

                if (
                    evento.target.closest(
                        config.botaoFiltrar
                    )
                ) {
                    evento.preventDefault();
                    atualizarModulo();
                    return;
                }

                if (
                    evento.target.closest(
                        config.botaoLimparFiltros
                    )
                ) {
                    evento.preventDefault();

                    limparFiltros();
                    atualizarModulo();
                }
            }
        );

        const formulario = document.querySelector(
            config.formulario
        );

        if (formulario) {
            formulario.addEventListener(
                'submit',
                guardar
            );

            formulario.addEventListener(
                'input',
                function (evento) {
                    evento.target.classList.remove(
                        'is-invalid'
                    );
                }
            );
        }

        const formularioCancelamento =
            document.querySelector(
                config.formularioCancelamento
            );

        if (formularioCancelamento) {
            formularioCancelamento.addEventListener(
                'submit',
                cancelar
            );
        }

        const botaoGuardar =
            document.querySelector(
                config.botaoGuardar
            );

        if (
            botaoGuardar &&
            botaoGuardar.type !== 'submit'
        ) {
            botaoGuardar.addEventListener(
                'click',
                guardar
            );
        }

        const botaoConfirmarCancelamento =
            document.querySelector(
                config.botaoConfirmarCancelamento
            );

        if (
            botaoConfirmarCancelamento &&
            botaoConfirmarCancelamento.type !==
            'submit'
        ) {
            botaoConfirmarCancelamento.addEventListener(
                'click',
                cancelar
            );
        }

        [
            config.filtroAno,
            config.filtroMes,
            config.filtroEstado
        ].forEach((seletor) => {
            const elemento =
                document.querySelector(seletor);

            if (!elemento) {
                return;
            }

            elemento.addEventListener(
                'change',
                function () {
                    if (
                        this.dataset.autoFilter ===
                        'true'
                    ) {
                        atualizarModulo();
                    }
                }
            );
        });
    }

    /**
     * ========================================================
     * FILTROS
     * ========================================================
     */
    function limparFiltros() {
        definirValor(
            config.filtroAno,
            ''
        );

        definirValor(
            config.filtroMes,
            ''
        );

        definirValor(
            config.filtroEstado,
            ''
        );

        atualizarSelect2(
            document.querySelector(
                config.filtroAno
            )
        );

        atualizarSelect2(
            document.querySelector(
                config.filtroMes
            )
        );

        atualizarSelect2(
            document.querySelector(
                config.filtroEstado
            )
        );
    }

    /**
     * ========================================================
     * ATUALIZAR MÓDULO
     * ========================================================
     */
    function atualizarModulo() {
        atualizarTabela();
        carregarResumo();
        carregarAnos();
        carregarEstados();
    }

    /**
     * ========================================================
     * REQUISIÇÃO
     * ========================================================
     */
    async function requisicao(
        url,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        const configuracao = {
            method: metodo,

            headers: {
                Accept: 'application/json',
                ...(opcoes.headers || {})
            },

            credentials: 'same-origin'
        };

        if (metodo === 'POST') {
            configuracao.headers[
                'Content-Type'
            ] = 'application/json; charset=UTF-8';

            configuracao.headers[
                'X-CSRF-Token'
            ] = obterCsrfToken();

            configuracao.body = JSON.stringify(
                opcoes.body || {}
            );
        }

        let respostaHttp;

        try {
            respostaHttp = await fetch(
                url,
                configuracao
            );

        } catch (_erro) {
            throw criarErro(
                'Não foi possível comunicar com o servidor.'
            );
        }

        let resposta;

        try {
            resposta = await respostaHttp.json();

        } catch (_erro) {
            throw criarErro(
                'O servidor devolveu uma resposta inválida.'
            );
        }

        if (
            !respostaHttp.ok ||
            resposta.sucesso !== true
        ) {
            if (
                respostaHttp.status === 401 ||
                resposta.sessao_expirada
            ) {
                throw criarErro(
                    'A sua sessão expirou. Inicie sessão novamente.'
                );
            }

            if (
                respostaHttp.status === 419 ||
                resposta.csrf_invalido
            ) {
                throw criarErro(
                    'A validação de segurança expirou. Atualize a página.',
                    resposta.erros
                );
            }

            throw criarErro(
                resposta.mensagem ||
                `Erro HTTP ${respostaHttp.status}.`,
                resposta.erros
            );
        }

        return resposta;
    }

    function criarErro(
        mensagem,
        erros = null
    ) {
        const erro = new Error(mensagem);
        erro.erros = erros;

        return erro;
    }

    /**
     * ========================================================
     * CSRF
     * ========================================================
     */
    function obterCsrfToken() {
        return (
            document
                .querySelector(
                    'meta[name="csrf-token"]'
                )
                ?.getAttribute('content') ||

            document
                .querySelector(
                    'input[name="csrf_token"]'
                )
                ?.value ||

            ''
        );
    }

    /**
     * ========================================================
     * ELEMENTOS
     * ========================================================
     */
    function obterValor(seletor) {
        return (
            document.querySelector(seletor)
                ?.value ||
            ''
        );
    }

    function definirValor(
        seletor,
        valor
    ) {
        const elemento =
            document.querySelector(seletor);

        if (elemento) {
            elemento.value =
                valor ?? '';
        }
    }

    function atualizarTexto(
        seletor,
        valor
    ) {
        const elemento =
            document.querySelector(seletor);

        if (elemento) {
            elemento.textContent =
                String(valor ?? '');
        }
    }

    /**
     * ========================================================
     * DATAS
     * ========================================================
     */
    function formatarData(valor) {
        if (!valor) {
            return '-';
        }

        const texto =
            String(valor).slice(0, 10);

        const partes =
            texto.split('-');

        if (partes.length !== 3) {
            return escaparHtml(valor);
        }

        return `
            ${partes[2]}/
            ${partes[1]}/
            ${partes[0]}
        `.replace(/\s+/g, '');
    }

    function normalizarDataInput(valor) {
        if (!valor) {
            return '';
        }

        return String(valor).slice(0, 10);
    }

    /**
     * ========================================================
     * SEGURANÇA HTML
     * ========================================================
     */
    function escaparHtml(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escaparAtributo(valor) {
        return escaparHtml(valor)
            .replace(/`/g, '&#096;');
    }

    function normalizarEstado(valor) {
        return String(valor || '')
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            )
            .toUpperCase()
            .trim()
            .replace(/\s+/g, '_');
    }

    function normalizarClasseBootstrap(cor) {
        if (
            !cor ||
            String(cor).startsWith('#')
        ) {
            return 'secondary';
        }

        const permitidas = [
            'primary',
            'secondary',
            'success',
            'danger',
            'warning',
            'info',
            'light',
            'dark'
        ];

        const classe = String(cor)
            .toLowerCase()
            .replace('badge-', '')
            .replace('bg-', '');

        return permitidas.includes(classe)
            ? classe
            : 'secondary';
    }

    /**
     * ========================================================
     * SELECT2
     * ========================================================
     */
    function iniciarSelect2() {
        if (
            !window.jQuery ||
            !$.fn.select2
        ) {
            return;
        }

        $('.select2').each(function () {
            const elemento = $(this);

            if (
                elemento.hasClass(
                    'select2-hidden-accessible'
                )
            ) {
                return;
            }

            const modal =
                elemento.closest('.modal');

            elemento.select2({
                width: '100%',

                dropdownParent:
                    modal.length
                        ? modal
                        : $(document.body)
            });
        });
    }

    function atualizarSelect2(elemento) {
        if (
            elemento &&
            window.jQuery &&
            $.fn.select2
        ) {
            $(elemento).trigger(
                'change.select2'
            );
        }
    }

    /**
     * ========================================================
     * MODAIS
     * ========================================================
     */
    function mostrarModal(seletor) {
        if (
            window.jQuery &&
            $.fn.modal
        ) {
            $(seletor).modal('show');
            return;
        }

        const modal =
            document.querySelector(seletor);

        if (modal) {
            modal.style.display = 'block';
            modal.classList.add('show');
        }
    }

    function esconderModal(seletor) {
        if (
            window.jQuery &&
            $.fn.modal
        ) {
            $(seletor).modal('hide');
            return;
        }

        const modal =
            document.querySelector(seletor);

        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
        }
    }

    /**
     * ========================================================
     * BOTÕES
     * ========================================================
     */
    function alterarEstadoBotao(
        seletor,
        carregando,
        textoCarregando = ''
    ) {
        const botao =
            document.querySelector(seletor);

        if (!botao) {
            return;
        }

        if (carregando) {
            if (!botao.dataset.textoOriginal) {
                botao.dataset.textoOriginal =
                    botao.innerHTML;
            }

            botao.disabled = true;

            botao.innerHTML = `
                <span
                    class="
                        spinner-border
                        spinner-border-sm
                        mr-1
                    "
                ></span>

                ${escaparHtml(textoCarregando)}
            `;

            return;
        }

        botao.disabled = false;

        if (botao.dataset.textoOriginal) {
            botao.innerHTML =
                botao.dataset.textoOriginal;

            delete botao.dataset.textoOriginal;
        }
    }

    /**
     * ========================================================
     * LOADER
     * ========================================================
     */
    function bloquearPagina(bloquear) {
        let overlay =
            document.getElementById(
                'periodoSalarialOverlay'
            );

        if (bloquear) {
            if (!overlay) {
                overlay =
                    document.createElement(
                        'div'
                    );

                overlay.id =
                    'periodoSalarialOverlay';

                overlay.innerHTML = `
                    <div
                        class="spinner-border"
                        role="status"
                    >
                        <span class="sr-only">
                            A carregar...
                        </span>
                    </div>
                `;

                Object.assign(
                    overlay.style,
                    {
                        position: 'fixed',
                        top: '0',
                        right: '0',
                        bottom: '0',
                        left: '0',
                        zIndex: '9999',
                        background:
                            'rgba(255,255,255,.65)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center'
                    }
                );

                document.body.appendChild(
                    overlay
                );
            }

            return;
        }

        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * ========================================================
     * MENSAGENS
     * ========================================================
     */
    async function confirmarOperacao(
        titulo,
        texto,
        textoConfirmar
    ) {
        if (window.Swal) {
            const resultado = await Swal.fire({
                title: titulo,
                text: texto,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText:
                    textoConfirmar,
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            });

            return resultado.isConfirmed;
        }

        return window.confirm(
            `${titulo}\n\n${texto}`
        );
    }

    async function mostrarSucesso(mensagem) {
        if (window.Swal) {
            return Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: mensagem,
                timer: 1800,
                showConfirmButton: false
            });
        }

        window.alert(mensagem);
    }

    function mostrarErro(mensagem) {
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: mensagem
            });

            return;
        }

        window.alert(mensagem);
    }

    function mostrarAviso(mensagem) {
        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: mensagem
            });

            return;
        }

        window.alert(mensagem);
    }

    /**
     * ========================================================
     * MÉTODOS PÚBLICOS
     * ========================================================
     */
    return {
        iniciar,
        carregar: atualizarModulo,
        carregarResumo,
        carregarEstados,
        carregarAnos,
        abrirNovo,
        abrirEditar,
        guardar,
        processar,
        concluir,
        fechar,
        reabrir,
        abrirCancelamento,
        cancelar,
        limparFormulario
    };

})();

/**
 * ============================================================
 * EXECUTAR AO CARREGAR A PÁGINA
 * ============================================================
 */
document.addEventListener(
    'DOMContentLoaded',
    PeriodoSalarial.iniciar
);