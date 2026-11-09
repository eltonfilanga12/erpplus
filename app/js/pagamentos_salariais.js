'use strict';

/*
|--------------------------------------------------------------------------
| Configuração
|--------------------------------------------------------------------------
*/

const PAGAMENTOS_SALARIAIS_URL =
    '/erpplus/app/ajax/pagamentos_salariais_ajax.php';

const PERIODOS_SALARIAIS_UR =
    '/erpplus/app/ajax/periodos_salariais_ajax.php';

/*
|--------------------------------------------------------------------------
| Estado local
|--------------------------------------------------------------------------
*/

let pagamentosSalariais = [];
let recibosDisponiveis = [];
let pagamentoEmEdicao = null;
let processamentosDisponiveis = [];
let pagamentosDisponiveisLote = [];
let pagamentosSelecionados =
    new Set();

/*
|--------------------------------------------------------------------------
| Inicialização
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {
        inicializarPagamentosSalariais();
    }
);

async function inicializarPagamentosSalariais() {
    adicionarEventosPagamentos();

    definirDataAtual();

    alternarCamposBancarios();

    await Promise.all([
        carregarPeriodosSalariais(),
        carregarStatusPagamentos()
    ]);

    await Promise.all([
        carregarPagamentosSalariais(),
        carregarResumoPagamentos()
    ]);
}

/*
|--------------------------------------------------------------------------
| Eventos
|--------------------------------------------------------------------------
*/

function adicionarEventosPagamentos() {
    const btnNovo =
        document.getElementById(
            'btnNovoPagamento'
        );

    const btnPagamentoLote =
        document.getElementById(
            'btnPagamentoLote'
        );

    const checkTodosPagamentosPendentes =
        document.getElementById(
            'checkTodosPagamentosPendentes'
        );


    if (checkTodosPagamentosPendentes) {

        checkTodosPagamentosPendentes
            .addEventListener(
                'change',
                function () {

                    const marcar =
                        this.checked;


                    pagamentosSalariais
                        .forEach(
                            pagamento => {

                                const pendente =
                                    String(
                                        pagamento.status_nome
                                        || ''
                                    )
                                        .toLowerCase()
                                    === 'pendente';


                                if (!pendente) {
                                    return;
                                }


                                const id =
                                    Number(
                                        pagamento.id
                                    );


                                if (marcar) {

                                    pagamentosSelecionados
                                        .add(id);

                                } else {

                                    pagamentosSelecionados
                                        .delete(id);
                                }
                            }
                        );


                    renderizarPagamentosSalariais();
                }
            );
    }

    const periodoPagamentoLote =
        document.getElementById(
            'periodoPagamentoLote'
        );


    const filtroBancoLote =
        document.getElementById(
            'filtroBancoLote'
        );


    const pesquisaFuncionarioLote =
        document.getElementById(
            'pesquisaFuncionarioLote'
        );


    const checkTodosLote =
        document.getElementById(
            'checkTodosLote'
        );


    const btnSelecionarTodosLote =
        document.getElementById(
            'btnSelecionarTodosLote'
        );


    const btnLimparSelecaoLote =
        document.getElementById(
            'btnLimparSelecaoLote'
        );


    const formaPagamentoLote =
        document.getElementById(
            'formaPagamentoLote'
        );


    const formPagamentoLote =
        document.getElementById(
            'formPagamentoLote'
        );

    const btnFiltrar =
        document.getElementById(
            'btnFiltrarPagamentos'
        );

    const btnLimpar =
        document.getElementById(
            'btnLimparFiltros'
        );

    const formPagamento =
        document.getElementById(
            'formPagamentoSalarial'
        );

    const formConfirmar =
        document.getElementById(
            'formConfirmarPagamento'
        );

    const formCancelar =
        document.getElementById(
            'formCancelarPagamento'
        );

    const formaPagamento =
        document.getElementById(
            'formaPagamento'
        );

    const reciboSalarioId =
        document.getElementById(
            'reciboSalarioId'
        );

    const filtroPesquisa =
        document.getElementById(
            'filtroPesquisa'
        );

    const filtroPeriodo =
        document.getElementById(
            'filtroPeriodo'
        );

    const btnConfirmarSelecionados =
        document.getElementById(
            'btnConfirmarSelecionados'
        );


    if (btnConfirmarSelecionados) {

        btnConfirmarSelecionados
            .addEventListener(
                'click',
                abrirConfirmacaoLote
            );
    }


    const formConfirmarPagamentosLote =
        document.getElementById(
            'formConfirmarPagamentosLote'
        );


    if (formConfirmarPagamentosLote) {

        formConfirmarPagamentosLote
            .addEventListener(
                'submit',
                confirmarPagamentosLote
            );
    }

    if (btnNovo) {
        btnNovo.addEventListener(
            'click',
            abrirNovoPagamento
        );
    }

    if (btnPagamentoLote) {

        btnPagamentoLote.addEventListener(
            'click',
            abrirPagamentoLote
        );
    }


    if (periodoPagamentoLote) {

        periodoPagamentoLote.addEventListener(
            'change',
            carregarDisponiveisLote
        );
    }


    if (filtroBancoLote) {

        filtroBancoLote.addEventListener(
            'change',
            renderizarPagamentoLote
        );
    }


    if (pesquisaFuncionarioLote) {

        pesquisaFuncionarioLote.addEventListener(
            'input',
            renderizarPagamentoLote
        );
    }


    if (checkTodosLote) {

        checkTodosLote.addEventListener(
            'change',
            function () {

                selecionarVisiveisLote(
                    checkTodosLote.checked
                );
            }
        );
    }


    if (btnSelecionarTodosLote) {

        btnSelecionarTodosLote.addEventListener(
            'click',
            function () {

                selecionarVisiveisLote(
                    true
                );
            }
        );
    }


    if (btnLimparSelecaoLote) {

        btnLimparSelecaoLote.addEventListener(
            'click',
            function () {

                selecionarVisiveisLote(
                    false
                );
            }
        );
    }


    if (formaPagamentoLote) {

        formaPagamentoLote.addEventListener(
            'change',
            async function () {

                await carregarContasPagamentoLote(
                    formaPagamentoLote.value
                );

                /*
                 * Se mudou para transferência,
                 * retirar seleção de quem não possui
                 * banco e conta.
                 */
                if (
                    formaPagamentoLote.value
                    === 'TRANSFERENCIA_BANCARIA'
                ) {

                    pagamentosDisponiveisLote
                        .forEach(
                            item => {

                                const possuiBanco =
                                    String(
                                        item.banco || ''
                                    ).trim() !== '';

                                const possuiConta =
                                    String(
                                        item.conta_bancaria
                                        || ''
                                    ).trim() !== '';

                                if (
                                    !possuiBanco
                                    || !possuiConta
                                ) {
                                    item.selecionado =
                                        false;
                                }
                            }
                        );
                }

                renderizarPagamentoLote();
            }
        );
    }


    if (formPagamentoLote) {

        formPagamentoLote.addEventListener(
            'submit',
            guardarPagamentoLote
        );
    }

    if (btnFiltrar) {
        btnFiltrar.addEventListener(
            'click',
            async function () {
                await Promise.all([
                    carregarPagamentosSalariais(),
                    carregarResumoPagamentos()
                ]);
            }
        );
    }

    if (btnLimpar) {
        btnLimpar.addEventListener(
            'click',
            limparFiltrosPagamentos
        );
    }

    if (formPagamento) {
        formPagamento.addEventListener(
            'submit',
            guardarPagamentoSalarial
        );
    }

    if (formConfirmar) {
        formConfirmar.addEventListener(
            'submit',
            confirmarPagamentoSalarial
        );
    }

    if (formCancelar) {
        formCancelar.addEventListener(
            'submit',
            cancelarPagamentoSalarial
        );
    }

    if (formaPagamento) {

        formaPagamento.addEventListener(
            'change',
            async function () {

                const metodo =
                    formaPagamento.value;

                alternarCamposBancarios();

                await carregarContasPagamento(
                    metodo
                );
            }
        );
    }

    const processamentoId =
        document.getElementById(
            'processamentoId'
        );

    if (processamentoId) {
        processamentoId.addEventListener(
            'change',
            atualizarDadosProcessamento
        );
    }

    if (reciboSalarioId) {
        reciboSalarioId.addEventListener(
            'change',
            atualizarDetalhesRecibo
        );
    }

    if (filtroPesquisa) {
        filtroPesquisa.addEventListener(
            'keydown',
            async function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();

                    await carregarPagamentosSalariais();
                }
            }
        );
    }

    if (filtroPeriodo) {
        filtroPeriodo.addEventListener(
            'change',
            async function () {
                await Promise.all([
                    carregarPagamentosSalariais(),
                    carregarResumoPagamentos()
                ]);
            }
        );
    }
}

async function confirmarPagamentosLote(
    event
) {

    event.preventDefault();


    esconderErro(
        'erroConfirmacaoLote'
    );


    const ids =
        Array.from(
            pagamentosSelecionados
        );


    if (ids.length === 0) {

        mostrarErro(
            'erroConfirmacaoLote',
            'Nenhum pagamento selecionado.'
        );

        return;
    }


    const botao =
        document.getElementById(
            'btnExecutarConfirmacaoLote'
        );


    const htmlOriginal =
        botao
            ? botao.innerHTML
            : '';


    try {

        if (botao) {

            botao.disabled = true;

            botao.innerHTML = `
                <i class="fa fa-spinner fa-spin"></i>
                A confirmar...
            `;
        }


        const dados =
            new FormData();


        dados.append(
            'acao',
            'confirmar_lote'
        );


        dados.append(
            'pagamentos',
            JSON.stringify(ids)
        );


        dados.append(
            'data_pagamento',
            document.getElementById(
                'dataConfirmacaoLote'
            )?.value || ''
        );


        dados.append(
            'referencia',
            document.getElementById(
                'referenciaConfirmacaoLote'
            )?.value || ''
        );


        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL,
                {
                    method:
                        'POST',

                    body:
                        dados
                }
            );


        pagamentosSelecionados.clear();


        retirarFocoModal();


        $('#modalConfirmarPagamentosLote')
            .modal('hide');


        mostrarMensagemSucesso(
            resultado.mensagem
            || 'Pagamentos confirmados com sucesso.'
        );


        await Promise.all([
            carregarPagamentosSalariais(),
            carregarResumoPagamentos()
        ]);


    } catch (erro) {

        mostrarErro(
            'erroConfirmacaoLote',
            erro.message
        );


    } finally {

        if (botao) {

            botao.disabled = false;

            botao.innerHTML =
                htmlOriginal;
        }
    }
}

function abrirConfirmacaoLote() {

    if (
        pagamentosSelecionados.size === 0
    ) {

        mostrarMensagemErro(
            'Selecione pelo menos um pagamento.'
        );

        return;
    }


    atualizarTexto(
        'quantidadeConfirmacaoLote',
        String(
            pagamentosSelecionados.size
        )
    );


    definirValorCampo(
        'dataConfirmacaoLote',
        obterDataAtual()
    );


    definirValorCampo(
        'referenciaConfirmacaoLote',
        ''
    );


    esconderErro(
        'erroConfirmacaoLote'
    );


    $('#modalConfirmarPagamentosLote')
        .modal('show');
}

async function abrirPagamentoLote() {

    pagamentosDisponiveisLote = [];


    definirValorCampo(
        'dataPagamentoLote',
        obterDataAtual()
    );


    definirValorCampo(
        'formaPagamentoLote',
        'TRANSFERENCIA_BANCARIA'
    );


    definirValorCampo(
        'pesquisaFuncionarioLote',
        ''
    );


    definirValorCampo(
        'filtroBancoLote',
        ''
    );


    esconderErro(
        'erroPagamentoLote'
    );


    await carregarPeriodosPagamentoLote();


    await carregarContasPagamentoLote(
        'TRANSFERENCIA_BANCARIA'
    );


    $('#modalPagamentoLote')
        .modal('show');
}

async function carregarPeriodosPagamentoLote() {

    const origem =
        document.getElementById(
            'filtroPeriodo'
        );


    const destino =
        document.getElementById(
            'periodoPagamentoLote'
        );


    if (
        !origem
        || !destino
    ) {
        return;
    }


    destino.innerHTML =
        origem.innerHTML;


    destino.value =
        origem.value || '';
}

async function carregarDisponiveisLote() {

    const periodoId =
        document.getElementById(
            'periodoPagamentoLote'
        )?.value || '';


    const tbody =
        document.getElementById(
            'tbodyPagamentoLote'
        );


    if (!periodoId) {

        pagamentosDisponiveisLote = [];

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="7"
                    class="text-center text-muted">
                    Selecione um período salarial.
                </td>
            </tr>
        `;

        atualizarResumoLote();

        return;
    }


    tbody.innerHTML = `
        <tr>
            <td
                colspan="7"
                class="text-center">
                <i class="fa fa-spinner fa-spin"></i>
                A carregar funcionários...
            </td>
        </tr>
    `;


    try {

        const parametros =
            new URLSearchParams({
                acao:
                    'disponiveis_lote',

                periodo_id:
                    periodoId
            });


        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL
                + '?'
                + parametros.toString()
            );


        pagamentosDisponiveisLote =
            (resultado.dados || [])
                .map(
                    item => ({
                        ...item,
                        selecionado:
                            false
                    })
                );


        carregarBancosLote();

        renderizarPagamentoLote();


    } catch (erro) {

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="7"
                    class="text-center text-danger">
                    ${escaparHtml(
            erro.message
        )}
                </td>
            </tr>
        `;
    }
}

function carregarBancosLote() {

    const select =
        document.getElementById(
            'filtroBancoLote'
        );


    if (!select) {
        return;
    }


    const bancos =
        [
            ...new Set(
                pagamentosDisponiveisLote
                    .map(
                        item =>
                            String(
                                item.banco || ''
                            ).trim()
                    )
                    .filter(Boolean)
            )
        ].sort();


    select.innerHTML = `
        <option value="">
            Todos os bancos
        </option>
    `;


    bancos.forEach(
        banco => {

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                banco;

            option.textContent =
                banco;

            select.appendChild(
                option
            );
        }
    );
}

function obterDisponiveisLoteFiltrados() {

    const banco =
        String(
            document.getElementById(
                'filtroBancoLote'
            )?.value || ''
        )
            .trim()
            .toLowerCase();


    const pesquisa =
        String(
            document.getElementById(
                'pesquisaFuncionarioLote'
            )?.value || ''
        )
            .trim()
            .toLowerCase();


    return pagamentosDisponiveisLote
        .filter(
            item => {

                const bancoItem =
                    String(
                        item.banco || ''
                    )
                        .trim()
                        .toLowerCase();


                const funcionario =
                    String(
                        item.funcionario_nome
                        || ''
                    )
                        .toLowerCase();


                const numero =
                    String(
                        item.numero_funcionario
                        || ''
                    )
                        .toLowerCase();


                if (
                    banco
                    && bancoItem !== banco
                ) {
                    return false;
                }


                if (
                    pesquisa
                    && !funcionario.includes(
                        pesquisa
                    )
                    && !numero.includes(
                        pesquisa
                    )
                ) {
                    return false;
                }


                return true;
            }
        );
}

function renderizarPagamentoLote() {

    const tbody =
        document.getElementById(
            'tbodyPagamentoLote'
        );


    if (!tbody) {
        return;
    }


    const itens =
        obterDisponiveisLoteFiltrados();


    if (itens.length === 0) {

        tbody.innerHTML = `
            <tr>

                <td
                    colspan="7"
                    class="text-center text-muted">

                    Nenhum funcionário disponível.

                </td>

            </tr>
        `;

        atualizarResumoLote();

        return;
    }


    tbody.innerHTML =
        itens.map(
            item => {

                const valor =
                    Number(
                        item.saldo_pendente
                        || item.salario_liquido
                        || 0
                    );


                const possuiBanco =
                    Boolean(
                        String(
                            item.banco || ''
                        ).trim()
                    );


                const possuiConta =
                    Boolean(
                        String(
                            item.conta_bancaria
                            || ''
                        ).trim()
                    );


                const dadosCompletos =
                    possuiBanco
                    && possuiConta;

                const formaPagamento =
                    document.getElementById(
                        'formaPagamentoLote'
                    )?.value || '';

                const exigeDadosBancarios =
                    formaPagamento ===
                    'TRANSFERENCIA_BANCARIA';

                const podeSelecionar =
                    !exigeDadosBancarios
                    || dadosCompletos;


                return `
                    <tr>

                        <td>

                            <input
                                type="checkbox"
                                class="check-pagamento-lote"
                                data-recibo-id="${Number(
                    item.recibo_id
                )}"
                                ${item.selecionado
                        ? 'checked'
                        : ''
                    }
                                ${podeSelecionar
                        ? ''
                        : 'disabled'
                    }>

                        </td>


                        <td>

                            <strong>
                                ${escaparHtml(
                        item.funcionario_nome
                        || '-'
                    )}
                            </strong>

                            <br>

                            <small class="text-muted">
                                ${escaparHtml(
                        item.numero_funcionario
                        || ''
                    )}
                            </small>

                        </td>


                        <td>
                            ${escaparHtml(
                        item.banco
                        || '-'
                    )}
                        </td>


                        <td>
                            ${escaparHtml(
                        item.conta_bancaria
                        || '-'
                    )}
                        </td>


                        <td>
                            ${escaparHtml(
                        item.nib
                        || '-'
                    )}
                        </td>


                        <td class="text-right">

                            <strong>

                                ${formatarMoeda(
                        valor,
                        item.moeda
                        || 'MZN'
                    )}

                            </strong>

                        </td>


                        <td>

                 ${dadosCompletos
                        ? `
                <span class="badge badge-success">
                    Pronto
                </span>
            `
                        : `
                <span class="badge badge-danger">
                    Dados bancários incompletos
                </span>
            `
                    }

            </td>

                    </tr>
                `;
            }
        )
            .join('');


    document
        .querySelectorAll(
            '.check-pagamento-lote'
        )
        .forEach(
            checkbox => {

                checkbox.addEventListener(
                    'change',
                    function () {

                        const reciboId =
                            Number(
                                this.dataset
                                    .reciboId
                            );


                        const item =
                            pagamentosDisponiveisLote
                                .find(
                                    registo =>
                                        Number(
                                            registo.recibo_id
                                        )
                                        === reciboId
                                );


                        if (item) {

                            item.selecionado =
                                this.checked;
                        }


                        atualizarResumoLote();
                    }
                );
            }
        );


    atualizarResumoLote();
}

function selecionarVisiveisLote(
    selecionar
) {

    const visiveis =
        obterDisponiveisLoteFiltrados();


    const ids =
        new Set(
            visiveis.map(
                item =>
                    Number(
                        item.recibo_id
                    )
            )
        );


    pagamentosDisponiveisLote
        .forEach(
            item => {

                if (
                    ids.has(
                        Number(
                            item.recibo_id
                        )
                    )
                ) {

                    item.selecionado =
                        selecionar;
                }
            }
        );


    renderizarPagamentoLote();
}

function atualizarResumoLote() {

    const selecionados =
        pagamentosDisponiveisLote
            .filter(
                item =>
                    item.selecionado
            );


    const total =
        selecionados.reduce(
            (
                acumulado,
                item
            ) => {

                return acumulado
                    + Number(
                        item.saldo_pendente
                        || item.salario_liquido
                        || 0
                    );
            },
            0
        );


    atualizarTexto(
        'quantidadeSelecionadosLote',
        String(
            selecionados.length
        )
    );


    atualizarTexto(
        'totalSelecionadoLote',
        formatarMoeda(
            total,
            'MZN'
        )
    );
}

async function carregarContasPagamentoLote(
    formaPagamento
) {

    const select =
        document.getElementById(
            'contaContabilLote'
        );


    if (!select) {
        return;
    }


    select.innerHTML = `
        <option value="">
            A carregar...
        </option>
    `;


    try {

        const parametros =
            new URLSearchParams({
                acao:
                    'contas_pagamento',

                forma_pagamento:
                    formaPagamento
            });


        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL
                + '?'
                + parametros.toString()
            );


        const contas =
            Array.isArray(
                resultado.dados
            )
                ? resultado.dados
                : [];


        select.innerHTML = `
            <option value="">
                Selecione
            </option>
        `;


        contas.forEach(
            conta => {

                const option =
                    document.createElement(
                        'option'
                    );


                option.value =
                    String(
                        conta.id
                    );


                option.textContent =
                    (
                        conta.codigo
                            ? conta.codigo
                            + ' - '
                            : ''
                    )
                    + (
                        conta.nome
                        || 'Conta'
                    );


                select.appendChild(
                    option
                );


                if (
                    Number(
                        conta.conta_padrao
                    ) === 1
                ) {

                    select.value =
                        String(
                            conta.id
                        );
                }
            }
        );


    } catch (erro) {

        select.innerHTML = `
            <option value="">
                Erro ao carregar contas
            </option>
        `;
    }
}

async function guardarPagamentoLote(
    event
) {

    event.preventDefault();


    esconderErro(
        'erroPagamentoLote'
    );


    const selecionados =
        pagamentosDisponiveisLote
            .filter(
                item =>
                    item.selecionado
            );


    if (
        selecionados.length === 0
    ) {

        mostrarErro(
            'erroPagamentoLote',
            'Selecione pelo menos um funcionário.'
        );

        return;
    }


    const botao =
        document.getElementById(
            'btnCriarPagamentoLote'
        );


    const htmlOriginal =
        botao
            ? botao.innerHTML
            : '';


    try {

        if (botao) {

            botao.disabled = true;

            botao.innerHTML = `
                <i class="fa fa-spinner fa-spin"></i>
                A processar...
            `;
        }


        const dados =
            new FormData();


        dados.append(
            'acao',
            'criar_lote'
        );


        dados.append(
            'forma_pagamento',
            document.getElementById(
                'formaPagamentoLote'
            )?.value || ''
        );


        dados.append(
            'conta_contabil_id',
            document.getElementById(
                'contaContabilLote'
            )?.value || ''
        );


        dados.append(
            'data_pagamento',
            document.getElementById(
                'dataPagamentoLote'
            )?.value || ''
        );


        dados.append(
            'observacoes',
            document.getElementById(
                'observacoesPagamentoLote'
            )?.value || ''
        );


        dados.append(
            'recibos',
            JSON.stringify(
                selecionados.map(
                    item =>
                        Number(
                            item.recibo_id
                        )
                )
            )
        );


        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL,
                {
                    method:
                        'POST',

                    body:
                        dados
                }
            );


        retirarFocoModal();


        $('#modalPagamentoLote')
            .modal('hide');


        mostrarMensagemSucesso(
            resultado.mensagem
            || 'Pagamentos criados com sucesso.'
        );


        await Promise.all([
            carregarPagamentosSalariais(),
            carregarResumoPagamentos()
        ]);


    } catch (erro) {

        mostrarErro(
            'erroPagamentoLote',
            erro.message
        );


    } finally {

        if (botao) {

            botao.disabled = false;

            botao.innerHTML =
                htmlOriginal;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Requisição AJAX
|--------------------------------------------------------------------------
*/

async function requisicaoPagamento(
    url,
    opcoes = {}
) {
    let resposta;

    try {
        resposta = await fetch(
            url,
            {
                credentials: 'same-origin',
                ...opcoes
            }
        );
    } catch (erro) {
        console.error(
            'Erro de conexão:',
            erro
        );

        throw new Error(
            'Não foi possível conectar ao servidor.'
        );
    }

    const textoResposta =
        await resposta.text();

    let resultado;

    try {
        resultado = JSON.parse(
            textoResposta
        );
    } catch (erro) {
        console.error(
            'Resposta inválida recebida de:',
            url
        );

        console.error(
            'Status HTTP:',
            resposta.status
        );

        console.error(
            'Resposta do servidor:',
            textoResposta
        );

        if (resposta.status === 404) {
            throw new Error(
                'O endpoint solicitado não foi encontrado.'
            );
        }

        throw new Error(
            'O servidor devolveu uma resposta inválida. Verifique o erro PHP no console.'
        );
    }

    if (resposta.status === 401) {
        window.location.href =
            '/erpplus/index.php?page=login';

        throw new Error(
            resultado.mensagem
            || 'A sessão expirou.'
        );
    }

    if (
        !resposta.ok
        || resultado.sucesso === false
    ) {
        throw new Error(
            resultado.mensagem
            || 'Não foi possível concluir a operação.'
        );
    }

    return resultado;
}
/*
|--------------------------------------------------------------------------
| Listagem
|--------------------------------------------------------------------------
*/

async function carregarPagamentosSalariais() {
    const corpoTabela =
        document.getElementById(
            'pagamentosSalariaisBody'
        );

    if (!corpoTabela) {
        return;
    }

    corpoTabela.innerHTML = `
        <tr>
            <td colspan="10" class="text-center">
                <i class="fa fa-spinner fa-spin"></i>
                A carregar pagamentos...
            </td>
        </tr>
    `;

    try {
        const parametros =
            obterFiltrosPagamentos();

        parametros.set(
            'acao',
            'listar'
        );

        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL
                + '?'
                + parametros.toString()
            );

        pagamentosSalariais =
            Array.isArray(resultado.dados)
                ? resultado.dados
                : [];

        renderizarPagamentosSalariais();
    } catch (erro) {
        corpoTabela.innerHTML = `
            <tr>
                <td
                    colspan="10"
                    class="text-center text-danger"
                >
                    ${escaparHtml(erro.message)}
                </td>
            </tr>
        `;
    }
}

function renderizarPagamentosSalariais() {
    const corpoTabela =
        document.getElementById(
            'pagamentosSalariaisBody'
        );

    if (!corpoTabela) {
        return;
    }

    if (pagamentosSalariais.length === 0) {
        corpoTabela.innerHTML = `
            <tr>
                <td
                    colspan="10"
                    class="text-center text-muted"
                >
                    Nenhum pagamento salarial encontrado.
                </td>
            </tr>
        `;

        return;
    }

    corpoTabela.innerHTML =
        pagamentosSalariais
            .map(
                pagamento =>
                    criarLinhaPagamento(
                        pagamento
                    )
            )
            .join('');

    configurarSelecaoPagamentos();
}

function configurarSelecaoPagamentos() {

    document
        .querySelectorAll(
            '.check-pagamento-pendente'
        )
        .forEach(
            checkbox => {

                checkbox.addEventListener(
                    'change',
                    function () {

                        const id =
                            Number(
                                this.dataset.id
                            );

                        if (this.checked) {

                            pagamentosSelecionados
                                .add(id);

                        } else {

                            pagamentosSelecionados
                                .delete(id);
                        }

                        atualizarResumoSelecionados();
                    }
                );
            }
        );

    atualizarResumoSelecionados();
}

function atualizarResumoSelecionados() {

    const selecionados =
        pagamentosSalariais.filter(
            pagamento =>
                pagamentosSelecionados.has(
                    Number(
                        pagamento.id
                    )
                )
        );


    const total =
        selecionados.reduce(
            (
                acumulado,
                pagamento
            ) =>
                acumulado
                + Number(
                    pagamento.valor_pago
                    || 0
                ),
            0
        );


    atualizarTexto(
        'quantidadePagamentosSelecionados',
        String(
            selecionados.length
        )
    );


    atualizarTexto(
        'totalPagamentosSelecionados',
        formatarMoeda(
            total,
            'MZN'
        )
    );


    const botao =
        document.getElementById(
            'btnConfirmarSelecionados'
        );


    if (botao) {

        botao.disabled =
            selecionados.length === 0;
    }
}

function criarLinhaPagamento(
    pagamento
) {
    const id =
        Number(
            pagamento.id
        );

    const estado =
        String(
            pagamento.status_nome
            || ''
        );

    const pendente =
        estado
            .toLowerCase()
        === 'pendente';


    const funcionario =
        pagamento.funcionario_nome
        || pagamento.recibo_funcionario_nome
        || pagamento.numero_funcionario
        || '-';


    const numeroFuncionario =
        pagamento.numero_funcionario
        || pagamento.recibo_numero_funcionario
        || '';


    const periodo =
        pagamento.periodo_descricao
        || pagamento.periodo_codigo
        || (
            pagamento.periodo_mes
                && pagamento.periodo_ano

                ? obterNomeMesPagamento(
                    Number(
                        pagamento.periodo_mes
                    )
                )
                + '/'
                + pagamento.periodo_ano

                : '-'
        );


    const recibo =
        pagamento.recibo_codigo
        || (
            pagamento.recibo_id
                ? `Recibo #${pagamento.recibo_id}`
                : '-'
        );


    return `
        <tr>
        <td>

    ${pendente
            ? `
                <input
                    type="checkbox"
                    class="check-pagamento-pendente"
                    data-id="${id}"
                    ${pagamentosSelecionados.has(id)
                ? 'checked'
                : ''
            }>
            `
            : ''
        }

</td>

            <td>
                <strong>
                    ${escaparHtml(
            pagamento.numero
            || pagamento.codigo
            || '-'
        )}
                </strong>
            </td>


            <td>
                <strong>
                    ${escaparHtml(
            funcionario
        )}
                </strong>

                ${numeroFuncionario
            ? `
                            <br>

                            <small
                                class="text-muted"
                            >
                                ${escaparHtml(
                numeroFuncionario
            )}
                            </small>
                        `
            : ''
        }
            </td>


            <td>
                ${escaparHtml(
            periodo
        )}
            </td>


            <td>
                ${escaparHtml(
            recibo
        )}
            </td>


            <td>
                ${escaparHtml(
            formatarMetodoPagamento(
                pagamento.forma_pagamento
            )
        )}
            </td>


            <td>
                <strong>
                    ${formatarMoeda(
            pagamento.valor_pago,
            pagamento.moeda
            || 'MZN'
        )}
                </strong>
            </td>


            <td>
                ${formatarData(
            pagamento.data_pagamento
        )}
            </td>


            <td>
                ${criarBadgeEstado(
            estado
        )}
            </td>


            <td class="text-right">

                <button
                    type="button"
                    class="btn btn-sm btn-light"
                    onclick="
                        verPagamentoSalarial(
                            ${id}
                        )
                    "
                    title="Visualizar"
                >
                    <i class="fa fa-eye"></i>
                </button>


                ${pendente
            ? `

                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                onclick="
                                    editarPagamentoSalarial(
                                        ${id}
                                    )
                                "
                                title="Editar"
                            >
                                <i class="fa fa-pencil"></i>
                            </button>


                            <button
                                type="button"
                                class="btn btn-sm btn-success"
                                onclick="
                                    abrirConfirmacaoPagamento(
                                        ${id}
                                    )
                                "
                                title="Confirmar pagamento"
                            >
                                <i class="fa fa-check"></i>
                            </button>


                            <button
                                type="button"
                                class="btn btn-sm btn-danger"
                                onclick="
                                    abrirCancelamentoPagamento(
                                        ${id}
                                    )
                                "
                                title="Cancelar"
                            >
                                <i class="fa fa-times"></i>
                            </button>

                        `
            : ''
        }

            </td>

        </tr>
    `;
}

function obterNomeMesPagamento(
    mes
) {
    const meses = [
        '',
        'Janeiro',
        'Fevereiro',
        'Março',
        'Abril',
        'Maio',
        'Junho',
        'Julho',
        'Agosto',
        'Setembro',
        'Outubro',
        'Novembro',
        'Dezembro'
    ];

    return meses[mes] || '';
}

/*
|--------------------------------------------------------------------------
| Filtros
|--------------------------------------------------------------------------
*/

function obterFiltrosPagamentos() {

    const parametros =
        new URLSearchParams();


    const periodoId =
        document.getElementById(
            'filtroPeriodo'
        )?.value || '';


    const statusId =
        document.getElementById(
            'filtroStatus'
        )?.value || '';


    const formaPagamento =
        document.getElementById(
            'filtroFormaPagamento'
        )?.value || '';


    const pesquisa =
        document.getElementById(
            'filtroPesquisa'
        )?.value.trim() || '';


    if (periodoId) {

        parametros.set(
            'periodo_id',
            periodoId
        );
    }


    if (statusId) {

        parametros.set(
            'status_id',
            statusId
        );
    }


    if (formaPagamento) {

        parametros.set(
            'forma_pagamento',
            formaPagamento
        );
    }


    if (pesquisa) {

        parametros.set(
            'pesquisa',
            pesquisa
        );
    }


    return parametros;
}

async function carregarContasPagamento(
    formaPagamento,
    contaSelecionada = null
) {
    const select =
        document.getElementById(
            'conta_contabil_id'
        );

    if (!select) {
        return;
    }

    if (!formaPagamento) {

        select.innerHTML = `
            <option value="">
                Selecione primeiro a forma de pagamento
            </option>
        `;

        return;
    }

    select.disabled = true;

    select.innerHTML = `
        <option value="">
            A carregar contas...
        </option>
    `;

    try {

        const parametros =
            new URLSearchParams({
                acao: 'contas_pagamento',
                forma_pagamento:
                    formaPagamento
            });

        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL
                + '?'
                + parametros.toString()
            );

        const contas =
            Array.isArray(resultado.dados)
                ? resultado.dados
                : [];

        select.innerHTML = `
            <option value="">
                Selecione...
            </option>
        `;

        contas.forEach(conta => {

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                conta.id;

            option.textContent =
                `${conta.codigo} - ${conta.nome}`;

            if (
                contaSelecionada
                && Number(conta.id)
                === Number(
                    contaSelecionada
                )
            ) {
                option.selected = true;
            }

            select.appendChild(option);
        });

    } catch (erro) {

        select.innerHTML = `
            <option value="">
                Erro ao carregar contas
            </option>
        `;

        mostrarErro(
            'erroPagamentoSalarial',
            erro.message
        );

    } finally {

        select.disabled = false;
    }
}

async function limparFiltrosPagamentos() {
    const campos = [
        'filtroPeriodo',
        'filtroStatus',
        'filtroFormaPagamento',
        'filtroPesquisa'
    ];

    campos.forEach(
        id => {
            const campo =
                document.getElementById(id);

            if (campo) {
                campo.value = '';
            }
        }
    );

    await Promise.all([
        carregarPagamentosSalariais(),
        carregarResumoPagamentos()
    ]);
}

/*
|--------------------------------------------------------------------------
| Resumo
|--------------------------------------------------------------------------
*/

async function carregarResumoPagamentos() {
    try {
        const parametros =
            new URLSearchParams();

        parametros.set(
            'acao',
            'resumo'
        );

        const periodoId =
            document.getElementById(
                'filtroPeriodo'
            )?.value || '';

        if (periodoId) {
            parametros.set(
                'periodo_id',
                periodoId
            );
        }

        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL
                + '?'
                + parametros.toString()
            );

        const resumo =
            resultado.dados || {};

        atualizarTexto(
            'resumoValorPago',
            formatarMoeda(
                resumo.valor_total_pago,
                'MZN'
            )
        );

        atualizarTexto(
            'resumoValorPendente',
            formatarMoeda(
                resumo.valor_total_pendente,
                'MZN'
            )
        );

        atualizarTexto(
            'resumoTotalPagos',
            resumo.total_pagos || 0
        );

        atualizarTexto(
            'resumoTotalCancelados',
            resumo.total_cancelados || 0
        );
    } catch (erro) {
        console.error(
            'Erro ao carregar resumo:',
            erro
        );
    }
}

/*
|--------------------------------------------------------------------------
| Novo pagamento
|--------------------------------------------------------------------------
*/

async function abrirNovoPagamento() {
    pagamentoEmEdicao = null;

    limparFormularioPagamento();

    atualizarTexto(
        'modalPagamentoSalarialTitulo',
        'Novo Pagamento Salarial'
    );

    definirValorCampo(
        'acaoPagamento',
        'criar'
    );

    const grupoRecibo =
        document.getElementById(
            'grupoReciboSalarial'
        );

    if (grupoRecibo) {
        grupoRecibo.classList.remove(
            'd-none'
        );
    }

    definirCampoDesativado(
        'reciboSalarioId',
        false
    );

    alternarCamposBancarios();

    await Promise.all([
        carregarRecibosDisponiveis(),
        carregarProcessamentosDisponiveis(),
        carregarContasPagamento(
            document.getElementById(
                'formaPagamento'
            )?.value || ''
        )
    ]);

    $('#modalPagamentoSalarial')
        .modal('show');
}


async function carregarProcessamentosDisponiveis() {
    const select =
        document.getElementById(
            'processamentoId'
        );

    if (!select) {
        return;
    }

    select.innerHTML = `
        <option value="">
            A carregar processamentos...
        </option>
    `;

    try {
        const parametros =
            new URLSearchParams();

        parametros.set(
            'acao',
            'processamentos_disponiveis'
        );

        const periodoId =
            document.getElementById(
                'filtroPeriodo'
            )?.value || '';

        if (periodoId) {
            parametros.set(
                'periodo_id',
                periodoId
            );
        }

        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL
                + '?'
                + parametros.toString()
            );

        processamentosDisponiveis =
            Array.isArray(resultado.dados)
                ? resultado.dados
                : [];

        select.innerHTML = `
            <option value="">
                Selecione o processamento
            </option>
        `;

        processamentosDisponiveis.forEach(
            processamento => {
                const option =
                    document.createElement(
                        'option'
                    );

                option.value =
                    String(processamento.id);

                const funcionario =
                    processamento.funcionario_nome
                    || processamento.numero_funcionario
                    || `Funcionário #${processamento.funcionario_id}`;

                const periodo =
                    processamento.periodo_descricao
                    || (
                        `${processamento.periodo_mes}/`
                        + processamento.periodo_ano
                    );

                const valor =
                    formatarMoeda(
                        processamento.saldo_pendente
                        || processamento.salario_liquido,
                        processamento.moeda || 'MZN'
                    );

                option.textContent =
                    `${funcionario} — ${periodo} — ${valor}`;

                select.appendChild(option);
            }
        );
    } catch (erro) {
        select.innerHTML = `
            <option value="">
                Erro ao carregar processamentos
            </option>
        `;

        mostrarErro(
            'erroPagamentoSalarial',
            erro.message
        );
    }
}

/*
|--------------------------------------------------------------------------
| Recibos disponíveis
|--------------------------------------------------------------------------
*/

async function carregarRecibosDisponiveis(
    reciboSelecionado = ''
) {
    const select =
        document.getElementById(
            'reciboSalarioId'
        );

    if (!select) {
        return;
    }

    select.innerHTML = `
        <option value="">
            A carregar recibos...
        </option>
    `;

    try {
        const parametros =
            new URLSearchParams();

        parametros.set(
            'acao',
            'recibos_disponiveis'
        );

        const periodoId =
            document.getElementById(
                'filtroPeriodo'
            )?.value || '';

        if (periodoId) {
            parametros.set(
                'periodo_id',
                periodoId
            );
        }

        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL
                + '?'
                + parametros.toString()
            );

        recibosDisponiveis =
            Array.isArray(resultado.dados)
                ? resultado.dados
                : [];

        select.innerHTML = `
            <option value="">
                Selecione um recibo
            </option>
        `;

        recibosDisponiveis.forEach(
            recibo => {
                const option =
                    document.createElement(
                        'option'
                    );

                option.value =
                    String(recibo.id);

                option.textContent =
                    montarTextoRecibo(recibo);

                option.dataset.recibo =
                    JSON.stringify(recibo);

                select.appendChild(option);
            }
        );

        if (reciboSelecionado) {
            select.value =
                String(reciboSelecionado);
        }

        atualizarDetalhesRecibo();
    } catch (erro) {
        select.innerHTML = `
            <option value="">
                Não foi possível carregar os recibos
            </option>
        `;

        mostrarErro(
            'erroPagamentoSalarial',
            erro.message
        );
    }
}

function montarTextoRecibo(recibo) {
    const funcionario =
        recibo.funcionario_nome
        || recibo.funcionario_codigo
        || 'Funcionário';

    const numero =
        recibo.numero_recibo
        || `Recibo #${recibo.id}`;

    const valor =
        formatarMoeda(
            recibo.salario_liquido,
            recibo.moeda || 'MZN'
        );

    return `${numero} — ${funcionario} — ${valor}`;
}

function atualizarDetalhesRecibo() {
    const select =
        document.getElementById(
            'reciboSalarioId'
        );

    const detalhes =
        document.getElementById(
            'detalhesReciboPagamento'
        );

    if (!select) {
        return;
    }

    const recibo =
        recibosDisponiveis.find(
            item =>
                String(item.id)
                === String(select.value)
        );

    if (!recibo) {
        if (detalhes) {
            detalhes.classList.add(
                'd-none'
            );
        }

        definirValorCampo(
            'processamentoId',
            ''
        );

        definirValorCampo(
            'valorProcessamento',
            ''
        );

        definirValorCampo(
            'valorPago',
            ''
        );

        definirValorCampo(
            'banco',
            ''
        );

        definirValorCampo(
            'numeroContaDestino',
            ''
        );

        definirValorCampo(
            'iban',
            ''
        );

        return;
    }

    definirValorCampo(
        'processamentoId',
        recibo.processamento_id
    );

    const valor =
        Number(
            recibo.saldo_pendente
            || recibo.salario_liquido
            || 0
        );

    definirValorCampo(
        'valorProcessamento',
        Number(
            recibo.salario_liquido || 0
        ).toFixed(2)
    );

    definirValorCampo(
        'valorPago',
        valor.toFixed(2)
    );

    /*
|--------------------------------------------------------------------------
| Dados bancários do funcionário
|--------------------------------------------------------------------------
*/

    definirValorCampo(
        'banco',
        recibo.funcionario_banco
        || ''
    );

    definirValorCampo(
        'numeroContaDestino',
        recibo.funcionario_conta_bancaria
        || ''
    );

    definirValorCampo(
        'iban',
        recibo.funcionario_nib
        || ''
    );

    if (!detalhes) {
        return;
    }

    detalhes.classList.remove(
        'd-none'
    );

    atualizarTexto(
        'detalheFuncionario',
        recibo.funcionario_nome
        || recibo.numero_funcionario
        || '-'
    );

    atualizarTexto(
        'detalheSalarioBruto',
        formatarMoeda(
            recibo.salario_bruto,
            recibo.moeda || 'MZN'
        )
    );

    atualizarTexto(
        'detalheDescontos',
        formatarMoeda(
            recibo.total_descontos,
            recibo.moeda || 'MZN'
        )
    );

    atualizarTexto(
        'detalheSalarioLiquido',
        formatarMoeda(
            recibo.salario_liquido,
            recibo.moeda || 'MZN'
        )
    );

    atualizarTexto(
        'detalhePeriodo',
        recibo.periodo_descricao
        || `${recibo.periodo_mes}/${recibo.periodo_ano}`
    );

    atualizarTexto(
        'detalheNumeroRecibo',
        recibo.codigo || `Recibo #${recibo.id}`
    );
}


function atualizarDadosProcessamento() {
    const select =
        document.getElementById(
            'processamentoId'
        );

    if (!select) {
        return;
    }

    const processamento =
        processamentosDisponiveis.find(
            item =>
                String(item.id)
                === String(select.value)
        );

    if (!processamento) {
        return;
    }

    definirValorCampo(
        'valorProcessamento',
        Number(
            processamento.salario_liquido
            || 0
        ).toFixed(2)
    );

    definirValorCampo(
        'valorPago',
        Number(
            processamento.saldo_pendente
            || processamento.salario_liquido
            || 0
        ).toFixed(2)
    );

    if (processamento.recibo_id) {
        definirValorCampo(
            'reciboSalarioId',
            processamento.recibo_id
        );

        atualizarDetalhesRecibo();
    }
}

/*
|--------------------------------------------------------------------------
| Guardar
|--------------------------------------------------------------------------
*/

async function guardarPagamentoSalarial(
    event
) {
    event.preventDefault();

    esconderErro(
        'erroPagamentoSalarial'
    );

    const formulario =
        event.currentTarget;

    const botao =
        document.getElementById(
            'btnSalvarPagamento'
        );

    const htmlOriginal =
        botao
            ? botao.innerHTML
            : '';

    try {
        if (botao) {
            botao.disabled = true;
            botao.innerHTML = `
                <i class="fa fa-spinner fa-spin"></i>
                A guardar...
            `;
        }

        const dados =
            new FormData(formulario);

        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL,
                {
                    method: 'POST',
                    body: dados
                }
            );

        retirarFocoModal();

        $('#modalPagamentoSalarial')
            .modal('hide');

        mostrarMensagemSucesso(
            resultado.mensagem
            || 'Pagamento guardado com sucesso.'
        );

        await Promise.all([
            carregarPagamentosSalariais(),
            carregarResumoPagamentos()
        ]);
    } catch (erro) {
        mostrarErro(
            'erroPagamentoSalarial',
            erro.message
        );
    } finally {
        if (botao) {
            botao.disabled = false;
            botao.innerHTML =
                htmlOriginal;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Editar
|--------------------------------------------------------------------------
*/

async function editarPagamentoSalarial(id) {
    esconderErro(
        'erroPagamentoSalarial'
    );

    try {
        const pagamento =
            await buscarPagamentoPorId(id);

        if (
            String(
                pagamento.status_nome
                || ''
            ).toLowerCase()
            !== 'pendente'
        ) {
            throw new Error(
                'Somente pagamentos pendentes podem ser editados.'
            );
        }

        pagamentoEmEdicao =
            pagamento;

        limparFormularioPagamento();

        atualizarTexto(
            'modalPagamentoSalarialTitulo',
            'Editar Pagamento Salarial'
        );

        definirValorCampo(
            'pagamentoId',
            pagamento.id
        );

        definirValorCampo(
            'formaPagamento',
            pagamento.forma_pagamento
        );

        definirValorCampo(
            'valorPago',
            pagamento.valor_pago
        );

        definirValorCampo(
            'dataPagamento',
            pagamento.data_pagamento
        );

        definirValorCampo(
            'banco',
            pagamento.banco
        );

        definirValorCampo(
            'numeroContaDestino',
            pagamento.conta_bancaria
        );

        definirValorCampo(
            'iban',
            pagamento.iban
        );

        definirValorCampo(
            'referenciaPagamento',
            pagamento.referencia
        );

        definirValorCampo(
            'observacoesPagamento',
            pagamento.observacoes
        );

        await carregarContasPagamento(
            pagamento.forma_pagamento,
            pagamento.conta_contabil_id
        );

        const grupoRecibo =
            document.getElementById(
                'grupoReciboSalarial'
            );

        if (grupoRecibo) {
            grupoRecibo.classList.add(
                'd-none'
            );
        }

        definirCampoDesativado(
            'reciboSalarioId',
            true
        );

        preencherDetalhesPagamento(
            pagamento
        );

        alternarCamposBancarios();

        $('#modalPagamentoSalarial')
            .modal('show');
    } catch (erro) {
        mostrarMensagemErro(
            erro.message
        );
    }
}

/*
|--------------------------------------------------------------------------
| Ver detalhes
|--------------------------------------------------------------------------
*/

async function verPagamentoSalarial(id) {
    try {
        const pagamento =
            await buscarPagamentoPorId(id);

        const mensagem = `
            <div class="text-left">
                <p>
                    <strong>Código:</strong>
                    ${escaparHtml(
            pagamento.codigo || '-'
        )}
                </p>

                <p>
                    <strong>Funcionário:</strong>
                    ${escaparHtml(
            pagamento.funcionario_nome
            || '-'
        )}
                </p>

                <p>
                    <strong>Recibo:</strong>
                    ${escaparHtml(
            pagamento.numero_recibo
            || '-'
        )}
                </p>

                <p>
                    <strong>Método:</strong>
                    ${escaparHtml(
            formatarMetodoPagamento(
                pagamento.metodo_pagamento
            )
        )}
                </p>

                <p>
                    <strong>Valor:</strong>
                    ${formatarMoeda(
            pagamento.valor_pago,
            pagamento.moeda || 'MZN'
        )}
                </p>

                <p>
                    <strong>Data:</strong>
                    ${formatarData(
            pagamento.data_pagamento
        )}
                </p>

                <p>
                    <strong>Estado:</strong>
                    ${escaparHtml(
            pagamento.status_nome
            || '-'
        )}
                </p>

                <p>
                    <strong>Referência:</strong>
                    ${escaparHtml(
            pagamento.referencia_pagamento
            || '-'
        )}
                </p>

                <p>
                    <strong>Observações:</strong>
                    ${escaparHtml(
            pagamento.observacoes
            || '-'
        )}
                </p>
            </div>
        `;

        if (
            typeof Swal
            !== 'undefined'
        ) {
            Swal.fire({
                title:
                    'Detalhes do pagamento',
                html: mensagem,
                icon: 'info',
                confirmButtonText:
                    'Fechar'
            });

            return;
        }

        alert(
            [
                `Código: ${pagamento.codigo || '-'}`,
                `Funcionário: ${pagamento.funcionario_nome || '-'}`,
                `Valor: ${formatarMoeda(
                    pagamento.valor_pago,
                    pagamento.moeda || 'MZN'
                )}`,
                `Estado: ${pagamento.status_nome || '-'}`
            ].join('\n')
        );
    } catch (erro) {
        mostrarMensagemErro(
            erro.message
        );
    }
}

async function buscarPagamentoPorId(id) {
    const parametros =
        new URLSearchParams({
            acao: 'buscar',
            id: String(id)
        });

    const resultado =
        await requisicaoPagamento(
            PAGAMENTOS_SALARIAIS_URL
            + '?'
            + parametros.toString()
        );

    return resultado.dados;
}

/*
|--------------------------------------------------------------------------
| Confirmação
|--------------------------------------------------------------------------
*/

async function abrirConfirmacaoPagamento(id) {
    try {
        const pagamento =
            await buscarPagamentoPorId(id);

        if (
            String(
                pagamento.status_nome
                || ''
            ).toLowerCase()
            !== 'pendente'
        ) {
            throw new Error(
                'Este pagamento não está pendente.'
            );
        }

        definirValorCampo(
            'confirmarPagamentoId',
            pagamento.id
        );

        definirValorCampo(
            'confirmarDataPagamento',
            pagamento.data_pagamento
            || obterDataAtual()
        );

        definirValorCampo(
            'confirmarReferenciaPagamento',
            pagamento.referencia_pagamento
        );

        esconderErro(
            'erroConfirmarPagamento'
        );

        $('#modalConfirmarPagamento')
            .modal('show');
    } catch (erro) {
        mostrarMensagemErro(
            erro.message
        );
    }
}

async function confirmarPagamentoSalarial(
    event
) {
    event.preventDefault();

    esconderErro(
        'erroConfirmarPagamento'
    );

    const botao =
        document.getElementById(
            'btnConfirmarPagamento'
        );

    const htmlOriginal =
        botao
            ? botao.innerHTML
            : '';

    try {
        if (botao) {
            botao.disabled = true;
            botao.innerHTML = `
                <i class="fa fa-spinner fa-spin"></i>
                A confirmar...
            `;
        }

        const dados =
            new FormData(
                event.currentTarget
            );

        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL,
                {
                    method: 'POST',
                    body: dados
                }
            );

        retirarFocoModal();

        $('#modalConfirmarPagamento')
            .modal('hide');

        mostrarMensagemSucesso(
            resultado.mensagem
            || 'Pagamento confirmado com sucesso.'
        );

        await Promise.all([
            carregarPagamentosSalariais(),
            carregarResumoPagamentos()
        ]);
    } catch (erro) {
        mostrarErro(
            'erroConfirmarPagamento',
            erro.message
        );
    } finally {
        if (botao) {
            botao.disabled = false;
            botao.innerHTML =
                htmlOriginal;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Cancelamento
|--------------------------------------------------------------------------
*/

async function abrirCancelamentoPagamento(id) {
    try {
        const pagamento =
            await buscarPagamentoPorId(id);

        if (
            String(
                pagamento.status_nome
                || ''
            ).toLowerCase()
            === 'cancelado'
        ) {
            throw new Error(
                'Este pagamento já está cancelado.'
            );
        }

        definirValorCampo(
            'cancelarPagamentoId',
            pagamento.id
        );

        definirValorCampo(
            'motivoCancelamentoPagamento',
            ''
        );

        esconderErro(
            'erroCancelarPagamento'
        );

        $('#modalCancelarPagamento')
            .modal('show');
    } catch (erro) {
        mostrarMensagemErro(
            erro.message
        );
    }
}

async function cancelarPagamentoSalarial(
    event
) {
    event.preventDefault();

    esconderErro(
        'erroCancelarPagamento'
    );

    const botao =
        document.getElementById(
            'btnCancelarPagamento'
        );

    const htmlOriginal =
        botao
            ? botao.innerHTML
            : '';

    try {
        if (botao) {
            botao.disabled = true;
            botao.innerHTML = `
                <i class="fa fa-spinner fa-spin"></i>
                A cancelar...
            `;
        }

        const dados =
            new FormData(
                event.currentTarget
            );

        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL,
                {
                    method: 'POST',
                    body: dados
                }
            );

        retirarFocoModal();

        $('#modalCancelarPagamento')
            .modal('hide');

        mostrarMensagemSucesso(
            resultado.mensagem
            || 'Pagamento cancelado com sucesso.'
        );

        await Promise.all([
            carregarPagamentosSalariais(),
            carregarResumoPagamentos()
        ]);
    } catch (erro) {
        mostrarErro(
            'erroCancelarPagamento',
            erro.message
        );
    } finally {
        if (botao) {
            botao.disabled = false;
            botao.innerHTML =
                htmlOriginal;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Períodos salariais
|--------------------------------------------------------------------------
*/

async function carregarPeriodosSalariais() {
    const select =
        document.getElementById(
            'filtroPeriodo'
        );

    if (!select) {
        return;
    }

    try {
        const resultado =
            await requisicaoPagamento(
                PERIODOS_SALARIAIS_UR
                + '?acao=listar'
            );

        const periodos =
            Array.isArray(resultado.dados)
                ? resultado.dados
                : [];

        select.innerHTML = `
            <option value="">
                Todos os períodos
            </option>
        `;

        periodos.forEach(
            periodo => {
                const option =
                    document.createElement(
                        'option'
                    );

                option.value =
                    String(periodo.id);

                option.textContent =
                    formatarPeriodoPagamento(
                        periodo
                    );

                select.appendChild(option);
            }
        );
    } catch (erro) {
        console.error(
            'Erro ao carregar períodos:',
            erro
        );
    }
}

/*
|--------------------------------------------------------------------------
| Estados
|--------------------------------------------------------------------------
*/

async function carregarStatusPagamentos() {
    const select =
        document.getElementById(
            'filtroStatus'
        );

    if (!select) {
        return;
    }

    try {
        const resultado =
            await requisicaoPagamento(
                PAGAMENTOS_SALARIAIS_URL
                + '?acao=status'
            );

        const estados =
            Array.isArray(resultado.dados)
                ? resultado.dados
                : [];

        select.innerHTML = `
            <option value="">
                Todos os estados
            </option>
        `;

        estados.forEach(
            estado => {
                const option =
                    document.createElement(
                        'option'
                    );

                option.value =
                    String(estado.id);

                option.textContent =
                    estado.nome;

                select.appendChild(option);
            }
        );
    } catch (erro) {
        console.error(
            'Erro ao carregar estados:',
            erro
        );
    }
}

/*
|--------------------------------------------------------------------------
| Bancos
|--------------------------------------------------------------------------
*/

async function carregarBancos() {
    const select =
        document.getElementById(
            'bancoId'
        );

    if (!select) {
        return;
    }

    try {
        const resultado =
            await requisicaoPagamento(
                BANCOS_URL
                + '?acao=listar'
            );

        const bancos =
            Array.isArray(resultado.dados)
                ? resultado.dados
                : [];

        select.innerHTML = `
            <option value="">
                Selecione o banco
            </option>
        `;

        bancos.forEach(
            banco => {
                const option =
                    document.createElement(
                        'option'
                    );

                option.value =
                    String(banco.id);

                option.textContent =
                    banco.nome
                    || banco.designacao
                    || `Banco #${banco.id}`;

                select.appendChild(option);
            }
        );
    } catch (erro) {
        console.error(
            'Erro ao carregar bancos:',
            erro
        );
    }
}

/*
|--------------------------------------------------------------------------
| Campos bancários
|--------------------------------------------------------------------------
*/

function alternarCamposBancarios() {
    const metodo =
        document.getElementById(
            'formaPagamento'
        )?.value || '';

    const usaBanco =
        [
            'TRANSFERENCIA_BANCARIA',
            'CHEQUE'
        ].includes(metodo);

    const grupos = [
        'grupoBanco',
        'grupoNumeroConta',
        'grupoTitularConta'
    ];

    grupos.forEach(
        id => {
            const grupo =
                document.getElementById(id);

            if (!grupo) {
                return;
            }

            grupo.classList.toggle(
                'd-none',
                !usaBanco
            );
        }
    );

    const banco =
        document.getElementById(
            'bancoId'
        );

    if (banco) {
        banco.required =
            metodo
            === 'TRANSFERENCIA_BANCARIA';

        if (!usaBanco) {
            banco.value = '';
        }
    }

    if (!usaBanco) {
        definirValorCampo(
            'numeroContaDestino',
            ''
        );

        definirValorCampo(
            'titularConta',
            ''
        );
    }
}

/*
|--------------------------------------------------------------------------
| Formulário
|--------------------------------------------------------------------------
*/

function limparFormularioPagamento() {
    const formulario =
        document.getElementById(
            'formPagamentoSalarial'
        );

    if (formulario) {
        formulario.reset();
    }

    definirValorCampo(
        'pagamentoId',
        ''
    );

    definirValorCampo(
        'acaoPagamento',
        'criar'
    );

    definirValorCampo(
        'formaPagamento',
        'TRANSFERENCIA_BANCARIA'
    );

    definirValorCampo(
        'dataPagamento',
        obterDataAtual()
    );

    const detalhes =
        document.getElementById(
            'detalhesReciboPagamento'
        );

    if (detalhes) {
        detalhes.classList.add(
            'd-none'
        );
    }

    esconderErro(
        'erroPagamentoSalarial'
    );
}

function preencherDetalhesPagamento(
    pagamento
) {
    const detalhes =
        document.getElementById(
            'detalhesReciboPagamento'
        );

    if (detalhes) {
        detalhes.classList.remove(
            'd-none'
        );
    }

    atualizarTexto(
        'detalheFuncionario',
        pagamento.funcionario_nome
        || '-'
    );

    atualizarTexto(
        'detalheSalarioBruto',
        formatarMoeda(
            pagamento.salario_bruto,
            pagamento.moeda || 'MZN'
        )
    );

    atualizarTexto(
        'detalheDescontos',
        formatarMoeda(
            pagamento.total_descontos,
            pagamento.moeda || 'MZN'
        )
    );

    atualizarTexto(
        'detalheSalarioLiquido',
        formatarMoeda(
            pagamento.salario_liquido,
            pagamento.moeda || 'MZN'
        )
    );

    atualizarTexto(
        'detalhePeriodo',
        formatarPeriodoPagamento(
            pagamento
        )
    );

    atualizarTexto(
        'detalheNumeroRecibo',
        pagamento.numero_recibo
        || '-'
    );
}

/*
|--------------------------------------------------------------------------
| Mensagens
|--------------------------------------------------------------------------
*/

function mostrarErro(
    elementoId,
    mensagem
) {
    const elemento =
        document.getElementById(
            elementoId
        );

    if (!elemento) {
        mostrarMensagemErro(mensagem);
        return;
    }

    elemento.textContent =
        mensagem;

    elemento.classList.remove(
        'd-none'
    );
}

function esconderErro(elementoId) {
    const elemento =
        document.getElementById(
            elementoId
        );

    if (!elemento) {
        return;
    }

    elemento.textContent = '';

    elemento.classList.add(
        'd-none'
    );
}

function mostrarMensagemSucesso(
    mensagem
) {
    if (
        typeof Swal
        !== 'undefined'
    ) {
        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: mensagem,
            timer: 2500,
            showConfirmButton: false
        });

        return;
    }

    alert(mensagem);
}

function mostrarMensagemErro(
    mensagem
) {
    if (
        typeof Swal
        !== 'undefined'
    ) {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: mensagem
        });

        return;
    }

    alert(mensagem);
}

/*
|--------------------------------------------------------------------------
| Formatação
|--------------------------------------------------------------------------
*/

function formatarMoeda(
    valor,
    moeda = 'MZN'
) {
    const numero =
        Number(valor || 0);

    try {
        return new Intl.NumberFormat(
            'pt-MZ',
            {
                style: 'currency',
                currency: moeda,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(numero);
    } catch (erro) {
        return numero.toLocaleString(
            'pt-PT',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        )
            + ' '
            + moeda;
    }
}

function formatarData(data) {
    if (!data) {
        return '-';
    }

    const partes =
        String(data).split('-');

    if (partes.length !== 3) {
        return escaparHtml(
            String(data)
        );
    }

    return [
        partes[2],
        partes[1],
        partes[0]
    ].join('/');
}

function formatarPeriodoPagamento(
    dados
) {
    if (dados.periodo_nome) {
        return String(
            dados.periodo_nome
        );
    }

    const mes =
        Number(dados.mes || 0);

    const ano =
        Number(dados.ano || 0);

    if (mes > 0 && ano > 0) {
        const nomesMeses = [
            'Janeiro',
            'Fevereiro',
            'Março',
            'Abril',
            'Maio',
            'Junho',
            'Julho',
            'Agosto',
            'Setembro',
            'Outubro',
            'Novembro',
            'Dezembro'
        ];

        return `${nomesMeses[mes - 1]
            || mes
            }/${ano}`;
    }

    return '-';
}

function formatarMetodoPagamento(
    metodo
) {
    const metodos = {
        TRANSFERENCIA_BANCARIA:
            'Transferência bancária',

        NUMERARIO:
            'Numerário',

        CHEQUE:
            'Cheque',

        MPESA:
            'M-Pesa',

        EMOLA:
            'e-Mola',

        MKESH:
            'mKesh',

        CARTEIRA_DIGITAL:
            'Carteira digital',

        OUTRO:
            'Outro'
    };

    return metodos[metodo]
        || metodo
        || '-';
}

function criarBadgeEstado(estado) {
    const nome =
        String(estado || '');

    const normalizado =
        nome.toLowerCase();

    let classe =
        'badge-secondary';

    if (normalizado === 'pago') {
        classe = 'badge-success';
    } else if (
        normalizado === 'pendente'
    ) {
        classe = 'badge-warning';
    } else if (
        normalizado === 'cancelado'
    ) {
        classe = 'badge-danger';
    }

    return `
        <span class="badge ${classe}">
            ${escaparHtml(nome || '-')}
        </span>
    `;
}

/*
|--------------------------------------------------------------------------
| Auxiliares
|--------------------------------------------------------------------------
*/

function definirDataAtual() {
    definirValorCampo(
        'dataPagamento',
        obterDataAtual()
    );

    definirValorCampo(
        'confirmarDataPagamento',
        obterDataAtual()
    );
}

function obterDataAtual() {
    const hoje =
        new Date();

    const ano =
        hoje.getFullYear();

    const mes =
        String(
            hoje.getMonth() + 1
        ).padStart(2, '0');

    const dia =
        String(
            hoje.getDate()
        ).padStart(2, '0');

    return `${ano}-${mes}-${dia}`;
}

function atualizarTexto(
    elementoId,
    valor
) {
    const elemento =
        document.getElementById(
            elementoId
        );

    if (elemento) {
        elemento.textContent =
            valor ?? '';
    }
}

function definirValorCampo(
    elementoId,
    valor
) {
    const elemento =
        document.getElementById(
            elementoId
        );

    if (elemento) {
        elemento.value =
            valor ?? '';
    }
}

function definirCampoDesativado(
    elementoId,
    desativado
) {
    const elemento =
        document.getElementById(
            elementoId
        );

    if (elemento) {
        elemento.disabled =
            Boolean(desativado);
    }
}

function retirarFocoModal() {
    const elementoAtivo =
        document.activeElement;

    if (
        elementoAtivo
        && typeof elementoAtivo.blur
        === 'function'
    ) {
        elementoAtivo.blur();
    }
}

function escaparHtml(valor) {
    const div =
        document.createElement(
            'div'
        );

    div.textContent =
        valor === null
            || valor === undefined
            ? ''
            : String(valor);

    return div.innerHTML;
}