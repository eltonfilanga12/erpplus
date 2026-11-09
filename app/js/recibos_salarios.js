'use strict';

/*
|--------------------------------------------------------------------------
| Configuração
|--------------------------------------------------------------------------
*/

const RECIBOS_AJAX_URL =
    '/erpplus/app/ajax/recibos_salarios_ajax.php';

let tabelaRecibos = null;

let reciboSelecionadoId = null;

/*
|--------------------------------------------------------------------------
| Inicialização
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {
        inicializarModuloRecibos();
    }
);

async function inicializarModuloRecibos() {
    configurarEventosRecibos();

    inicializarTabelaRecibos();

    await Promise.all([
        carregarPeriodosRecibos(),
        carregarResumoRecibos()
    ]);

    await carregarRecibos();
}

/*
|--------------------------------------------------------------------------
| Eventos
|--------------------------------------------------------------------------
*/

function configurarEventosRecibos() {
    const formFiltros =
        document.getElementById(
            'formFiltrosRecibos'
        );

    const filtroPeriodo =
        document.getElementById(
            'filtroPeriodoRecibo'
        );

    const filtroFolha =
        document.getElementById(
            'filtroFolhaRecibo'
        );

    const formGerar =
        document.getElementById(
            'formGerarRecibos'
        );

    const periodoGeracao =
        document.getElementById(
            'periodoReciboId'
        );

    const folhaGeracao =
        document.getElementById(
            'folhaReciboId'
        );

    const radioTodos =
        document.getElementById(
            'gerarTodosFuncionarios'
        );

    const radioIndividual =
        document.getElementById(
            'gerarFuncionarioIndividual'
        );

    const formCancelar =
        document.getElementById(
            'formCancelarRecibo'
        );

    const btnImprimir =
        document.getElementById(
            'btnImprimirReciboDetalhe'
        );

    if (formFiltros) {
        formFiltros.addEventListener(
            'submit',
            async function (event) {
                event.preventDefault();

                await carregarRecibos();
            }
        );
    }

    if (filtroPeriodo) {
        filtroPeriodo.addEventListener(
            'change',
            async function () {
                await carregarFolhasFiltroRecibos(
                    filtroPeriodo.value
                );

                await carregarRecibos();
            }
        );
    }

    if (filtroFolha) {
        filtroFolha.addEventListener(
            'change',
            carregarRecibos
        );
    }

    $('#periodoReciboId')
        .off('change.recibos')
        .on(
            'change.recibos',
            async function () {

                const periodoId =
                    $(this).val();

                limparSelectFolhaGeracao();
                limparSelectFuncionario();

                if (!periodoId) {
                    return;
                }

                await carregarFolhasGeracaoRecibos(
                    periodoId
                );
            }
        );

    if (folhaGeracao) {
        folhaGeracao.addEventListener(
            'change',
            async function () {
                limparSelectFuncionario();

                if (!folhaGeracao.value) {
                    return;
                }

                if (
                    radioIndividual
                    && radioIndividual.checked
                ) {
                    await carregarFuncionariosFolha(
                        folhaGeracao.value
                    );
                }
            }
        );
    }

    if (radioTodos) {
        radioTodos.addEventListener(
            'change',
            function () {
                alternarTipoGeracao();
            }
        );
    }

    if (radioIndividual) {
        radioIndividual.addEventListener(
            'change',
            async function () {
                alternarTipoGeracao();

                if (
                    radioIndividual.checked
                    && folhaGeracao
                    && folhaGeracao.value
                ) {
                    await carregarFuncionariosFolha(
                        folhaGeracao.value
                    );
                }
            }
        );
    }

    if (formGerar) {
        formGerar.addEventListener(
            'submit',
            gerarRecibos
        );
    }

    if (formCancelar) {
        formCancelar.addEventListener(
            'submit',
            confirmarCancelamentoRecibo
        );
    }

    if (btnImprimir) {
        btnImprimir.addEventListener(
            'click',
            function () {
                if (!reciboSelecionadoId) {
                    mostrarMensagemRecibo(
                        'Aviso',
                        'Nenhum recibo foi selecionado.',
                        'warning'
                    );

                    return;
                }

                imprimirRecibo(
                    reciboSelecionadoId
                );
            }
        );
    }

    $('#modalGerarRecibos')
    .off('shown.bs.modal.recibos')
    .on(
        'shown.bs.modal.recibos',
        async function () {

            const selectPeriodo =
                document.getElementById(
                    'periodoReciboId'
                );

            if (!selectPeriodo) {
                return;
            }

            /*
             * placeholder + 1 período
             */
            if (
                selectPeriodo.options.length === 2
            ) {
                selectPeriodo.value =
                    selectPeriodo.options[1].value;

                $(selectPeriodo)
                    .trigger('change.recibos');
            }
        }
    );

    $('#modalCancelarRecibo').on(
        'hidden.bs.modal',
        function () {
            limparFormularioCancelamento();
        }
    );

    document.addEventListener(
        'click',
        tratarCliqueAcoesRecibo
    );
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

function obterCsrfTokenRecibos() {
    const campo =
        document.querySelector(
            '#formGerarRecibos '
            + 'input[name="csrf_token"]'
        );

    if (
        campo
        && campo.value
    ) {
        return campo.value;
    }

    const campoCancelar =
        document.querySelector(
            '#formCancelarRecibo '
            + 'input[name="csrf_token"]'
        );

    if (
        campoCancelar
        && campoCancelar.value
    ) {
        return campoCancelar.value;
    }

    const meta =
        document.querySelector(
            'meta[name="csrf-token"]'
        );

    return meta
        ? meta.getAttribute('content') || ''
        : '';
}

/*
|--------------------------------------------------------------------------
| Comunicação AJAX
|--------------------------------------------------------------------------
*/

async function enviarReciboAjax(
    dados
) {
    const payload = {
        ...dados,
        csrf_token:
            dados.csrf_token
            || obterCsrfTokenRecibos()
    };

    let resposta;

    try {
        resposta = await fetch(
            RECIBOS_AJAX_URL,
            {
                method: 'POST',

                headers: {
                    'Content-Type':
                        'application/json',

                    'Accept':
                        'application/json'
                },

                credentials:
                    'same-origin',

                body:
                    JSON.stringify(payload)
            }
        );
    } catch (erro) {
        console.error(
            'Erro de rede:',
            erro
        );

        throw new Error(
            'Não foi possível comunicar com o servidor.'
        );
    }

    let resultado;

    try {
        resultado =
            await resposta.json();
    } catch (erro) {
        console.error(
            'Resposta inválida:',
            erro
        );

        throw new Error(
            'O servidor retornou uma resposta inválida.'
        );
    }

    if (
        !resposta.ok
        || !resultado.success
    ) {
        throw new Error(
            resultado.message
            || 'Não foi possível concluir a operação.'
        );
    }

    return resultado;
}

/*
|--------------------------------------------------------------------------
| DataTable
|--------------------------------------------------------------------------
*/

function inicializarTabelaRecibos() {
    const tabela =
        document.getElementById(
            'recibosSalariosTable'
        );

    if (!tabela) {
        return;
    }

    if (
        typeof $ !== 'undefined'
        && $.fn.DataTable
    ) {
        tabelaRecibos =
            $('#recibosSalariosTable')
                .DataTable({
                    destroy: true,

                    processing: false,

                    searching: false,

                    ordering: true,

                    pageLength: 10,

                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],

                    language: {
                        emptyTable:
                            'Nenhum recibo encontrado.',

                        info:
                            'A mostrar _START_ a _END_ de _TOTAL_ recibos',

                        infoEmpty:
                            'Nenhum recibo disponível',

                        lengthMenu:
                            'Mostrar _MENU_ registos',

                        paginate: {
                            first: 'Primeiro',
                            last: 'Último',
                            next: 'Seguinte',
                            previous: 'Anterior'
                        }
                    }
                });
    }
}

/*
|--------------------------------------------------------------------------
| Carregar recibos
|--------------------------------------------------------------------------
*/

async function carregarRecibos() {
    const tbody =
        document.querySelector(
            '#recibosSalariosTable tbody'
        );

    if (
        !tbody
        && !tabelaRecibos
    ) {
        return;
    }

    mostrarCarregamentoTabela();

    try {
        const resultado =
            await enviarReciboAjax({
                acao: 'listar',

                periodo_id:
                    obterValorElementoRecibo(
                        'filtroPeriodoRecibo'
                    ),

                folha_salarial_id:
                    obterValorElementoRecibo(
                        'filtroFolhaRecibo'
                    ),

                estado:
                    obterValorElementoRecibo(
                        'filtroEstadoRecibo'
                    ),

                pesquisa:
                    obterValorElementoRecibo(
                        'filtroPesquisaRecibo'
                    )
            });

        const recibos =
            Array.isArray(resultado.data)
                ? resultado.data
                : [];

        renderizarTabelaRecibos(
            recibos
        );
    } catch (erro) {
        console.error(erro);

        renderizarErroTabela(
            erro.message
        );

        mostrarMensagemRecibo(
            'Erro',
            erro.message,
            'error'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Renderizar tabela
|--------------------------------------------------------------------------
*/

function renderizarTabelaRecibos(
    recibos
) {
    if (tabelaRecibos) {
        tabelaRecibos.clear();

        recibos.forEach(
            function (recibo) {
                tabelaRecibos.row.add([
                    escaparHtml(
                        recibo.codigo || '-'
                    ),

                    criarCelulaFuncionario(
                        recibo
                    ),

                    criarDescricaoPeriodo(
                        recibo
                    ),

                    escaparHtml(
                        recibo.folha_codigo
                        || recibo.folha_descricao
                        || '-'
                    ),

                    formatarMoedaRecibo(
                        recibo.salario_bruto,
                        recibo.moeda
                    ),

                    formatarMoedaRecibo(
                        recibo.total_descontos,
                        recibo.moeda
                    ),

                    criarValorLiquido(
                        recibo
                    ),

                    criarBadgeEstado(
                        recibo.estado
                    ),

                    formatarDataHoraRecibo(
                        recibo.emitido_em
                    ),

                    criarBotoesAcao(
                        recibo
                    )
                ]);
            }
        );

        tabelaRecibos.draw();

        return;
    }

    const tbody =
        document.querySelector(
            '#recibosSalariosTable tbody'
        );

    if (!tbody) {
        return;
    }

    if (recibos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td
                    colspan="10"
                    class="text-center text-muted"
                >
                    Nenhum recibo encontrado.
                </td>
            </tr>
        `;

        return;
    }

    tbody.innerHTML =
        recibos.map(
            function (recibo) {
                return `
                    <tr>
                        <td>
                            ${escaparHtml(
                    recibo.codigo || '-'
                )}
                        </td>

                        <td>
                            ${criarCelulaFuncionario(
                    recibo
                )}
                        </td>

                        <td>
                            ${criarDescricaoPeriodo(
                    recibo
                )}
                        </td>

                        <td>
                            ${escaparHtml(
                    recibo.folha_codigo
                    || recibo.folha_descricao
                    || '-'
                )}
                        </td>

                        <td>
                            ${formatarMoedaRecibo(
                    recibo.salario_bruto,
                    recibo.moeda
                )}
                        </td>

                        <td>
                            ${formatarMoedaRecibo(
                    recibo.total_descontos,
                    recibo.moeda
                )}
                        </td>

                        <td>
                            ${criarValorLiquido(
                    recibo
                )}
                        </td>

                        <td>
                            ${criarBadgeEstado(
                    recibo.estado
                )}
                        </td>

                        <td>
                            ${formatarDataHoraRecibo(
                    recibo.emitido_em
                )}
                        </td>

                        <td class="text-right">
                            ${criarBotoesAcao(
                    recibo
                )}
                        </td>
                    </tr>
                `;
            }
        ).join('');
}

/*
|--------------------------------------------------------------------------
| Elementos da tabela
|--------------------------------------------------------------------------
*/

function criarCelulaFuncionario(
    recibo
) {
    const numero =
        recibo.numero_funcionario
            ? `
            <small class="text-muted">
                ${escaparHtml(
                recibo.numero_funcionario
            )}
            </small>
        `
            : '';

    return `
        <div>
            <strong>
                ${escaparHtml(
        recibo.funcionario_nome
        || '-'
    )}
            </strong>

            <br>

            ${numero}
        </div>
    `;
}

function criarDescricaoPeriodo(
    recibo
) {
    if (recibo.periodo_descricao) {
        return escaparHtml(
            recibo.periodo_descricao
        );
    }

    if (
        recibo.periodo_mes
        && recibo.periodo_ano
    ) {
        return escaparHtml(
            obterNomeMes(
                Number(
                    recibo.periodo_mes
                )
            )
            + ' '
            + recibo.periodo_ano
        );
    }

    return escaparHtml(
        recibo.periodo_codigo || '-'
    );
}

function criarValorLiquido(
    recibo
) {
    return `
        <strong class="text-success">
            ${formatarMoedaRecibo(
        recibo.salario_liquido,
        recibo.moeda
    )}
        </strong>
    `;
}

function criarBadgeEstado(
    estado
) {
    const classes = {
        Emitido: 'badge-info',
        Enviado: 'badge-primary',
        Entregue: 'badge-success',
        Cancelado: 'badge-danger'
    };

    const classe =
        classes[estado]
        || 'badge-secondary';

    return `
        <span class="badge ${classe}">
            ${escaparHtml(
        estado || 'Desconhecido'
    )}
        </span>
    `;
}

function criarBotoesAcao(
    recibo
) {
    const id =
        Number(recibo.id);

    const estado =
        recibo.estado || '';

    let botoes = `
        <button
            type="button"
            class="btn btn-sm btn-outline-primary mr-1"
            data-acao-recibo="visualizar"
            data-id="${id}"
            title="Visualizar"
        >
            <i class="fa fa-eye"></i>
        </button>

        <button
            type="button"
            class="btn btn-sm btn-outline-secondary mr-1"
            data-acao-recibo="imprimir"
            data-id="${id}"
            title="Imprimir"
        >
            <i class="fa fa-print"></i>
        </button>
    `;

    if (estado === 'Emitido') {
        botoes += `
            <button
                type="button"
                class="btn btn-sm btn-outline-info mr-1"
                data-acao-recibo="enviar"
                data-id="${id}"
                title="Marcar como enviado"
            >
                <i class="fa fa-paper-plane"></i>
            </button>
        `;
    }

    if (
        estado === 'Emitido'
        || estado === 'Enviado'
    ) {
        botoes += `
            <button
                type="button"
                class="btn btn-sm btn-outline-success mr-1"
                data-acao-recibo="entregar"
                data-id="${id}"
                title="Marcar como entregue"
            >
                <i class="fa fa-check-circle"></i>
            </button>
        `;
    }

    if (estado !== 'Cancelado') {
        botoes += `
            <button
                type="button"
                class="btn btn-sm btn-outline-danger"
                data-acao-recibo="cancelar"
                data-id="${id}"
                title="Cancelar"
            >
                <i class="fa fa-times"></i>
            </button>
        `;
    }

    return `
        <div class="btn-group">
            ${botoes}
        </div>
    `;
}

/*
|--------------------------------------------------------------------------
| Ações dos botões
|--------------------------------------------------------------------------
*/

async function tratarCliqueAcoesRecibo(
    event
) {
    const botao =
        event.target.closest(
            '[data-acao-recibo]'
        );

    if (!botao) {
        return;
    }

    const acao =
        botao.dataset.acaoRecibo;

    const id =
        Number(botao.dataset.id);

    if (!id) {
        return;
    }

    switch (acao) {
        case 'visualizar':
            await visualizarRecibo(id);
            break;

        case 'imprimir':
            imprimirRecibo(id);
            break;

        case 'enviar':
            await marcarReciboEnviado(id);
            break;

        case 'entregar':
            await marcarReciboEntregue(id);
            break;

        case 'cancelar':
            abrirModalCancelarRecibo(id);
            break;
    }
}

/*
|--------------------------------------------------------------------------
| Resumo
|--------------------------------------------------------------------------
*/

async function carregarResumoRecibos() {
    try {
        const resultado =
            await enviarReciboAjax({
                acao: 'resumo'
            });

        const resumo =
            resultado.data || {};

        definirTextoElementoRecibo(
            'resumoRecibosTotal',
            resumo.total_recibos || 0
        );

        definirTextoElementoRecibo(
            'resumoRecibosEmitidos',
            resumo.total_emitidos || 0
        );

        definirTextoElementoRecibo(
            'resumoRecibosEntregues',
            resumo.total_entregues || 0
        );

        definirTextoElementoRecibo(
            'resumoRecibosCancelados',
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
| Períodos
|--------------------------------------------------------------------------
*/

async function carregarPeriodosRecibos() {
    try {
        const resultado =
            await enviarReciboAjax({
                acao: 'periodos'
            });

        const periodos =
            Array.isArray(resultado.data)
                ? resultado.data
                : [];

        preencherSelectPeriodosRecibos(
            'filtroPeriodoRecibo',
            periodos,
            'Todos os períodos'
        );

        preencherSelectPeriodosRecibos(
            'periodoReciboId',
            periodos,
            'Selecione o período'
        );
    } catch (erro) {
        console.error(
            'Erro ao carregar períodos:',
            erro
        );

        mostrarMensagemRecibo(
            'Erro',
            erro.message,
            'error'
        );
    }
}

function preencherSelectPeriodosRecibos(
    id,
    periodos,
    placeholder
) {
    const select =
        document.getElementById(id);

    if (!select) {
        return;
    }

    select.innerHTML =
        `<option value="">
            ${escaparHtml(placeholder)}
        </option>`;

    periodos.forEach(
        function (periodo) {
            const descricao =
                periodo.descricao
                || (
                    obterNomeMes(
                        Number(periodo.mes)
                    )
                    + ' '
                    + periodo.ano
                );

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                periodo.id;

            option.textContent =
                descricao;

            select.appendChild(option);
        }
    );
}

/*
|--------------------------------------------------------------------------
| Folhas
|--------------------------------------------------------------------------
*/

async function carregarFolhasFiltroRecibos(
    periodoId
) {
    const select =
        document.getElementById(
            'filtroFolhaRecibo'
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
        const resultado =
            await enviarReciboAjax({
                acao: 'folhas',
                periodo_id: periodoId
            });

        preencherSelectFolhasRecibos(
            'filtroFolhaRecibo',
            resultado.data || [],
            'Todas as folhas'
        );
    } catch (erro) {
        select.innerHTML = `
            <option value="">
                Todas as folhas
            </option>
        `;

        mostrarMensagemRecibo(
            'Erro',
            erro.message,
            'error'
        );
    }
}

async function carregarFolhasGeracaoRecibos(
    periodoId
) {
    const select =
        document.getElementById(
            'folhaReciboId'
        );

    if (!select) {
        return;
    }

    select.disabled = true;

    select.innerHTML = `
        <option value="">
            A carregar folhas...
        </option>
    `;

    try {
        const resultado =
            await enviarReciboAjax({
                acao: 'folhas',
                periodo_id: periodoId
            });

        preencherSelectFolhasRecibos(
            'folhaReciboId',
            resultado.data || [],
            'Selecione a folha'
        );
    } catch (erro) {
        select.innerHTML = `
            <option value="">
                Selecione a folha
            </option>
        `;

        mostrarMensagemRecibo(
            'Erro',
            erro.message,
            'error'
        );
    } finally {
        select.disabled = false;
    }
}

function preencherSelectFolhasRecibos(
    id,
    folhas,
    placeholder
) {
    const select =
        document.getElementById(id);

    if (!select) {
        return;
    }

    select.innerHTML =
        `<option value="">
            ${escaparHtml(placeholder)}
        </option>`;

    folhas.forEach(
        function (folha) {
            const descricao =
                folha.codigo
                + (
                    folha.descricao
                        ? ' - '
                        + folha.descricao
                        : ''
                );

            const option =
                document.createElement(
                    'option'
                );

            option.value =
                folha.id;

            option.textContent =
                descricao;

            select.appendChild(option);
        }
    );
}

/*
|--------------------------------------------------------------------------
| Funcionários da folha
|--------------------------------------------------------------------------
*/

async function carregarFuncionariosFolha(
    folhaSalarialId
) {
    const select =
        document.getElementById(
            'funcionarioReciboId'
        );

    if (!select) {
        return;
    }

    select.disabled = true;

    select.innerHTML = `
        <option value="">
            A carregar funcionários...
        </option>
    `;

    try {
        const resultado =
            await enviarReciboAjax({
                acao: 'funcionarios',

                folha_salarial_id:
                    folhaSalarialId
            });

        const funcionarios =
            Array.isArray(resultado.data)
                ? resultado.data
                : [];

        select.innerHTML = `
            <option value="">
                Selecione o funcionário
            </option>
        `;

        funcionarios.forEach(
            function (funcionario) {
                const option =
                    document.createElement(
                        'option'
                    );

                option.value =
                    funcionario.processamento_id;

                option.textContent =
                    criarNomeFuncionarioSelect(
                        funcionario
                    );

                if (
                    Number(
                        funcionario.possui_recibo
                    ) === 1
                ) {
                    option.disabled = true;

                    option.textContent +=
                        ' — Recibo já emitido';
                }

                select.appendChild(option);
            }
        );
    } catch (erro) {
        select.innerHTML = `
            <option value="">
                Selecione o funcionário
            </option>
        `;

        mostrarMensagemRecibo(
            'Erro',
            erro.message,
            'error'
        );
    } finally {
        select.disabled = false;
    }
}

function criarNomeFuncionarioSelect(
    funcionario
) {
    let texto =
        funcionario.funcionario_nome
        || 'Funcionário';

    if (
        funcionario.numero_funcionario
    ) {
        texto =
            funcionario.numero_funcionario
            + ' - '
            + texto;
    }

    if (funcionario.cargo_nome) {
        texto +=
            ' ('
            + funcionario.cargo_nome
            + ')';
    }

    return texto;
}

/*
|--------------------------------------------------------------------------
| Tipo de geração
|--------------------------------------------------------------------------
*/

function alternarTipoGeracao() {
    const radioIndividual =
        document.getElementById(
            'gerarFuncionarioIndividual'
        );

    const grupo =
        document.getElementById(
            'grupoFuncionarioRecibo'
        );

    const select =
        document.getElementById(
            'funcionarioReciboId'
        );

    if (
        !radioIndividual
        || !grupo
        || !select
    ) {
        return;
    }

    if (radioIndividual.checked) {
        grupo.classList.remove(
            'd-none'
        );

        select.required = true;
    } else {
        grupo.classList.add(
            'd-none'
        );

        select.required = false;
        select.value = '';
    }
}

/*
|--------------------------------------------------------------------------
| Gerar recibos
|--------------------------------------------------------------------------
*/

async function gerarRecibos(event) {
    event.preventDefault();

    const formulario =
        event.currentTarget;

    const botao =
        formulario.querySelector(
            'button[type="submit"]'
        );

    try {
        bloquearBotaoRecibo(
            botao,
            true
        );

        const tipoGeracao =
            formulario.querySelector(
                'input[name="tipo_geracao"]:checked'
            )?.value || '';

        const folhaSalarialId =
            document.getElementById(
                'folhaReciboId'
            )?.value || '';

        const processamentoId =
            document.getElementById(
                'funcionarioReciboId'
            )?.value || '';

        const observacoes =
            formulario.querySelector(
                '[name="observacoes"]'
            )?.value.trim() || '';

        if (!folhaSalarialId) {
            throw new Error(
                'Selecione a folha salarial.'
            );
        }

        if (
            tipoGeracao === 'individual'
            && !processamentoId
        ) {
            throw new Error(
                'Selecione o funcionário.'
            );
        }

        const resultado =
            await enviarReciboAjax({
                acao:
                    'processar_geracao',

                tipo_geracao:
                    tipoGeracao,

                folha_salarial_id:
                    folhaSalarialId,

                processamento_id:
                    processamentoId,

                observacoes:
                    observacoes
            });

        mostrarMensagemRecibo(
            'Sucesso',
            resultado.message,
            'success'
        );

        $('#modalGerarRecibos')
            .modal('hide');

        await Promise.all([
            carregarRecibos(),
            carregarResumoRecibos()
        ]);
    } catch (erro) {
        console.error(
            'Erro ao gerar recibo:',
            erro
        );

        mostrarMensagemRecibo(
            'Erro',
            erro.message
            || 'Não foi possível gerar o recibo.',
            'error'
        );
    } finally {
        bloquearBotaoRecibo(
            botao,
            false
        );
    }
}

/*
|--------------------------------------------------------------------------
| Visualizar recibo
|--------------------------------------------------------------------------
*/

async function visualizarRecibo(
    id
) {
    try {
        const resultado =
            await enviarReciboAjax({
                acao: 'buscar',
                id: id
            });

        const recibo =
            resultado.data;

        reciboSelecionadoId =
            Number(recibo.id);

        preencherDetalhesRecibo(
            recibo
        );

        $('#modalDetalhesRecibo')
            .modal('show');
    } catch (erro) {
        mostrarMensagemRecibo(
            'Erro',
            erro.message,
            'error'
        );
    }
}

function preencherDetalhesRecibo(
    recibo
) {
    definirTextoElementoRecibo(
        'detalheReciboTitulo',
        'Recibo '
        + (
            recibo.codigo || ''
        )
    );

    definirTextoElementoRecibo(
        'detalheNumeroFuncionario',
        recibo.numero_funcionario || '-'
    );

    definirTextoElementoRecibo(
        'detalheFuncionarioNome',
        recibo.funcionario_nome || '-'
    );

    definirTextoElementoRecibo(
        'detalheCargoNome',
        recibo.cargo_nome || '-'
    );

    definirTextoElementoRecibo(
        'detalheDepartamentoNome',
        recibo.departamento_nome || '-'
    );

    definirTextoElementoRecibo(
        'detalheFilialNome',
        recibo.filial_nome || '-'
    );

    definirTextoElementoRecibo(
        'detalheReciboCodigo',
        recibo.codigo || '-'
    );

    definirTextoElementoRecibo(
        'detalhePeriodoRecibo',
        criarDescricaoPeriodoTexto(
            recibo
        )
    );

    definirTextoElementoRecibo(
        'detalheFolhaRecibo',
        recibo.folha_codigo
        || recibo.folha_descricao
        || '-'
    );

    const estadoElemento =
        document.getElementById(
            'detalheEstadoRecibo'
        );

    if (estadoElemento) {
        estadoElemento.innerHTML =
            criarBadgeEstado(
                recibo.estado
            );
    }

    definirTextoElementoRecibo(
        'detalheEmitidoEm',
        formatarDataHoraRecibo(
            recibo.emitido_em
        )
    );

    preencherValorDetalhe(
        'detalheSalarioBase',
        recibo.salario_base,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheTotalSubsidios',
        recibo.total_subsidios,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheHorasExtras',
        recibo.total_horas_extras,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheOutrosProventos',
        recibo.total_outros_proventos,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheTotalProventos',
        recibo.total_proventos,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheTotalFaltas',
        recibo.total_faltas,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheTotalAtrasos',
        recibo.total_atrasos,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheTotalInss',
        recibo.total_inss,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheTotalIrps',
        recibo.total_irps,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheOutrosDescontos',
        recibo.total_outros_descontos,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheTotalDescontos',
        recibo.total_descontos,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheSalarioBruto',
        recibo.salario_bruto,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheResumoDescontos',
        recibo.total_descontos,
        recibo.moeda
    );

    preencherValorDetalhe(
        'detalheSalarioLiquido',
        recibo.salario_liquido,
        recibo.moeda
    );
}

/*
|--------------------------------------------------------------------------
| Marcar como enviado
|--------------------------------------------------------------------------
*/

async function marcarReciboEnviado(
    id
) {
    const confirmado =
        await confirmarAcaoRecibo({
            titulo:
                'Marcar como enviado?',

            texto:
                'O recibo será marcado como enviado ao funcionário.',

            confirmButtonText:
                'Sim, marcar'
        });

    if (!confirmado) {
        return;
    }

    try {
        const resultado =
            await enviarReciboAjax({
                acao: 'marcar_enviado',
                id: id
            });

        mostrarMensagemRecibo(
            'Sucesso',
            resultado.message,
            'success'
        );

        await Promise.all([
            carregarRecibos(),
            carregarResumoRecibos()
        ]);
    } catch (erro) {
        mostrarMensagemRecibo(
            'Erro',
            erro.message,
            'error'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Marcar como entregue
|--------------------------------------------------------------------------
*/

async function marcarReciboEntregue(
    id
) {
    const confirmado =
        await confirmarAcaoRecibo({
            titulo:
                'Marcar como entregue?',

            texto:
                'Confirma que o recibo foi entregue ao funcionário?',

            confirmButtonText:
                'Sim, entregar'
        });

    if (!confirmado) {
        return;
    }

    try {
        const resultado =
            await enviarReciboAjax({
                acao: 'marcar_entregue',
                id: id
            });

        mostrarMensagemRecibo(
            'Sucesso',
            resultado.message,
            'success'
        );

        await Promise.all([
            carregarRecibos(),
            carregarResumoRecibos()
        ]);
    } catch (erro) {
        mostrarMensagemRecibo(
            'Erro',
            erro.message,
            'error'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Cancelar recibo
|--------------------------------------------------------------------------
*/

function abrirModalCancelarRecibo(
    id
) {
    const form =
        document.getElementById(
            'formCancelarRecibo'
        );

    if (!form) {
        return;
    }

    form.querySelector(
        '[name="id"]'
    ).value = id;

    form.querySelector(
        '[name="motivo"]'
    ).value = '';

    $('#modalCancelarRecibo')
        .modal('show');
}

async function confirmarCancelamentoRecibo(
    event
) {
    event.preventDefault();

    const form =
        event.currentTarget;

    const id =
        form.querySelector(
            '[name="id"]'
        )?.value;

    const motivo =
        form.querySelector(
            '[name="motivo"]'
        )?.value.trim();

    if (!id) {
        mostrarMensagemRecibo(
            'Erro',
            'Recibo não identificado.',
            'error'
        );

        return;
    }

    if (
        !motivo
        || motivo.length < 5
    ) {
        mostrarMensagemRecibo(
            'Atenção',
            'Informe um motivo com pelo menos 5 caracteres.',
            'warning'
        );

        return;
    }

    const confirmado =
        await confirmarAcaoRecibo({
            titulo:
                'Cancelar recibo?',

            texto:
                'Esta ação alterará o estado do recibo para Cancelado.',

            confirmButtonText:
                'Sim, cancelar',

            confirmButtonColor:
                '#dc3545'
        });

    if (!confirmado) {
        return;
    }

    const botao =
        form.querySelector(
            'button[type="submit"]'
        );

    bloquearBotaoRecibo(
        botao,
        true,
        'A cancelar...'
    );

    try {
        const resultado =
            await enviarReciboAjax({
                acao: 'cancelar',
                id: id,
                motivo: motivo
            });

        $('#modalCancelarRecibo')
            .modal('hide');

        mostrarMensagemRecibo(
            'Sucesso',
            resultado.message,
            'success'
        );

        await Promise.all([
            carregarRecibos(),
            carregarResumoRecibos()
        ]);
    } catch (erro) {
        mostrarMensagemRecibo(
            'Erro',
            erro.message,
            'error'
        );
    } finally {
        bloquearBotaoRecibo(
            botao,
            false
        );
    }
}

/*
|--------------------------------------------------------------------------
| Impressão
|--------------------------------------------------------------------------
*/

function imprimirRecibo(
    id
) {
    const url =
        'index.php?page=imprimir_recibo_salario'
        + '&id='
        + encodeURIComponent(id);

    window.open(
        url,
        '_blank',
        'noopener,noreferrer'
    );
}

/*
|--------------------------------------------------------------------------
| Confirmações
|--------------------------------------------------------------------------
*/

async function confirmarAcaoRecibo(
    opcoes
) {
    if (
        typeof Swal !== 'undefined'
    ) {
        const resultado =
            await Swal.fire({
                title:
                    opcoes.titulo
                    || 'Confirmar ação',

                text:
                    opcoes.texto
                    || '',

                icon:
                    opcoes.icon
                    || 'question',

                showCancelButton:
                    true,

                confirmButtonText:
                    opcoes.confirmButtonText
                    || 'Confirmar',

                cancelButtonText:
                    'Cancelar',

                confirmButtonColor:
                    opcoes.confirmButtonColor,

                reverseButtons:
                    true
            });

        return resultado.isConfirmed;
    }

    return window.confirm(
        opcoes.texto
        || opcoes.titulo
        || 'Confirmar ação?'
    );
}

/*
|--------------------------------------------------------------------------
| Mensagens
|--------------------------------------------------------------------------
*/

function mostrarMensagemRecibo(
    titulo,
    mensagem,
    tipo = 'info'
) {
    if (
        typeof Swal !== 'undefined'
    ) {
        Swal.fire({
            title: titulo,
            text: mensagem,
            icon: tipo,
            confirmButtonText: 'OK'
        });

        return;
    }

    console.log(
        `[${titulo}] ${mensagem}`
    );
}

/*
|--------------------------------------------------------------------------
| Estados de carregamento
|--------------------------------------------------------------------------
*/

function mostrarCarregamentoTabela() {
    if (tabelaRecibos) {
        tabelaRecibos.clear().draw();

        return;
    }

    const tbody = document.querySelector(
        '#recibosSalariosTable tbody'
    );

    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-muted">
                    <i class="fa fa-spinner fa-spin"></i>
                    A carregar recibos...
                </td>
            </tr>
        `;
    }
}

function renderizarErroTabela(mensagem) {
    if (tabelaRecibos) {
        tabelaRecibos.clear().draw();

        mostrarMensagemRecibo(
            'Erro',
            mensagem,
            'error'
        );

        return;
    }

    const tbody = document.querySelector(
        '#recibosSalariosTable tbody'
    );

    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center text-danger">
                    ${escaparHtmlRecibo(mensagem)}
                </td>
            </tr>
        `;
    }
}

/*
|--------------------------------------------------------------------------
| Limpeza dos formulários
|--------------------------------------------------------------------------
*/

function limparFormularioGeracao() {
    const form =
        document.getElementById(
            'formGerarRecibos'
        );

    if (!form) {
        return;
    }

    form.reset();

    limparSelectFolhaGeracao();

    limparSelectFuncionario();

    alternarTipoGeracao();
}

function limparFormularioCancelamento() {
    const form =
        document.getElementById(
            'formCancelarRecibo'
        );

    if (!form) {
        return;
    }

    form.reset();
}

function limparSelectFolhaGeracao() {
    const select =
        document.getElementById(
            'folhaReciboId'
        );

    if (select) {
        select.innerHTML = `
            <option value="">
                Selecione a folha
            </option>
        `;
    }
}

function limparSelectFuncionario() {
    const select =
        document.getElementById(
            'funcionarioReciboId'
        );

    if (select) {
        select.innerHTML = `
            <option value="">
                Selecione o funcionário
            </option>
        `;

        select.value = '';
    }
}

/*
|--------------------------------------------------------------------------
| Funções auxiliares
|--------------------------------------------------------------------------
*/

function obterValorElementoRecibo(
    id
) {
    const elemento =
        document.getElementById(id);

    return elemento
        ? elemento.value.trim()
        : '';
}

function definirTextoElementoRecibo(
    id,
    valor
) {
    const elemento =
        document.getElementById(id);

    if (elemento) {
        elemento.textContent =
            valor ?? '-';
    }
}

function preencherValorDetalhe(
    id,
    valor,
    moeda
) {
    definirTextoElementoRecibo(
        id,
        formatarMoedaRecibo(
            valor,
            moeda
        )
    );
}

function bloquearBotaoRecibo(
    botao,
    bloquear = true
) {
    if (!botao) {
        return;
    }

    if (bloquear) {
        if (!botao.dataset.textoOriginal) {
            botao.dataset.textoOriginal =
                botao.innerHTML;
        }

        botao.disabled = true;

        botao.innerHTML = `
            <span
                class="spinner-border spinner-border-sm mr-1"
                role="status"
                aria-hidden="true"
            ></span>
            A processar...
        `;

        return;
    }

    botao.disabled = false;

    if (botao.dataset.textoOriginal) {
        botao.innerHTML =
            botao.dataset.textoOriginal;
    }
}

function formatarMoedaRecibo(
    valor,
    moeda = 'MZN'
) {
    const numero =
        Number(valor || 0);

    try {
        return new Intl.NumberFormat(
            'pt-PT',
            {
                style: 'currency',
                currency:
                    moeda || 'MZN',

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
            + (moeda || 'MZN');
    }
}

function formatarDataHoraRecibo(
    data
) {
    if (!data) {
        return '-';
    }

    const dataNormalizada =
        String(data).replace(
            ' ',
            'T'
        );

    const objetoData =
        new Date(dataNormalizada);

    if (
        Number.isNaN(
            objetoData.getTime()
        )
    ) {
        return escaparHtml(data);
    }

    return objetoData.toLocaleString(
        'pt-PT',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }
    );
}

function criarDescricaoPeriodoTexto(
    recibo
) {
    if (recibo.periodo_descricao) {
        return recibo.periodo_descricao;
    }

    if (
        recibo.periodo_mes
        && recibo.periodo_ano
    ) {
        return obterNomeMes(
            Number(recibo.periodo_mes)
        )
            + ' '
            + recibo.periodo_ano;
    }

    return recibo.periodo_codigo || '-';
}

function obterNomeMes(
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

function escaparHtml(
    valor
) {
    return String(
        valor ?? ''
    )
        .replace(
            /&/g,
            '&amp;'
        )
        .replace(
            /</g,
            '&lt;'
        )
        .replace(
            />/g,
            '&gt;'
        )
        .replace(
            /"/g,
            '&quot;'
        )
        .replace(
            /'/g,
            '&#039;'
        );
}