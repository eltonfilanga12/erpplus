'use strict';

const FOLHAS_SALARIAIS_URL =
    '/erpplus/app/ajax/folhas_salariais_ajax.php';

document.addEventListener(
    'DOMContentLoaded',
    () => {
        iniciarFolhasSalariais();
    }
);

function iniciarFolhasSalariais() {
    carregarPeriodosFolhaSalarial();

    carregarFolhasSalariais();
    carregarResumoFolhas();

    const formulario =
        document.getElementById(
            'formFolhaSalarial'
        );

    if (formulario) {
        formulario.addEventListener(
            'submit',
            submeterFolhaSalarial
        );
    }

    const formularioCancelamento =
        document.getElementById(
            'formCancelarFolha'
        );

    if (formularioCancelamento) {
        formularioCancelamento
            .addEventListener(
                'submit',
                submeterCancelamentoFolha
            );
    }

    const filtros =
        document.getElementById(
            'formFiltrosFolhas'
        );

    if (filtros) {
        filtros.addEventListener(
            'submit',
            event => {
                event.preventDefault();
                carregarFolhasSalariais();
            }
        );
    }
}

async function enviarFolhaAjax(dados) {
    const resposta =
        await fetch(
            FOLHAS_SALARIAIS_URL,
            {
                method: 'POST',
                headers: {
                    'Content-Type':
                        'application/json',
                    'Accept':
                        'application/json'
                },
                body:
                    JSON.stringify(dados)
            }
        );

    const texto =
        await resposta.text();

    let resultado;

    try {
        resultado =
            JSON.parse(texto);
    } catch (erro) {
        console.error(
            'Resposta inválida:',
            texto
        );

        throw new Error(
            'O servidor devolveu uma resposta inválida.'
        );
    }

    if (
        !resposta.ok
        || resultado.success === false
    ) {
        throw new Error(
            resultado.message
            || 'Não foi possível realizar a operação.'
        );
    }

    return resultado;
}

function obterCsrfTokenFolhas() {
    const campo =
        document.querySelector(
            '[name="csrf_token"]'
        );

    if (campo?.value) {
        return campo.value;
    }

    const meta =
        document.querySelector(
            'meta[name="csrf-token"]'
        );

    return meta?.content || '';
}

async function carregarFolhasSalariais() {
    try {
        const pesquisa =
            document.getElementById(
                'filtroPesquisaFolha'
            )?.value || '';

        const periodoId =
            document.getElementById(
                'filtroPeriodo'
            )?.value || '';

        const estado =
            document.getElementById(
                'filtroEstadoFolha'
            )?.value || '';

        const resultado =
            await enviarFolhaAjax({
                acao: 'listar',
                pesquisa,
                periodo_id: periodoId,
                estado,
                csrf_token:
                    obterCsrfTokenFolhas()
            });

        renderizarTabelaFolhas(
            resultado.data || []
        );
    } catch (erro) {
        console.error(erro);

        mostrarMensagemFolha(
            erro.message,
            'error'
        );
    }
}

function renderizarTabelaFolhas(folhas) {
    const corpo =
        document.querySelector(
            '#folhasSalariaisTable tbody'
        );

    if (!corpo) {
        return;
    }

    if (!Array.isArray(folhas)
        || folhas.length === 0) {
        corpo.innerHTML = `
            <tr>
                <td colspan="11"
                    class="text-center text-muted">
                    Nenhuma folha salarial encontrada.
                </td>
            </tr>
        `;

        return;
    }

    corpo.innerHTML =
        folhas.map(folha => {
            const estado =
                escaparHtmlFolha(
                    folha.estado || ''
                );

            const situacaoPagamento =
                obterSituacaoPagamentoFolha(folha);

            return `
                <tr>
                    <td>
                        ${escaparHtmlFolha(
                folha.codigo
            )}
                    </td>

                    <td>
                        ${escaparHtmlFolha(
                folha.periodo_descricao
                || folha.periodo_codigo
                || ''
            )}
                    </td>

                    <td>
                        ${folha.numero_funcionarios || 0}
                    </td>

                    <td>
                        ${formatarMoedaFolha(
                folha.total_bruto,
                folha.moeda
            )}
                    </td>

                    <td>
                        ${formatarMoedaFolha(
                folha.total_descontos,
                folha.moeda
            )}
                    </td>

                    <td>
                        ${formatarMoedaFolha(
                folha.total_liquido,
                folha.moeda
            )}
                    </td>

                    <td>
                        ${formatarMoedaFolha(
                folha.total_pago,
                folha.moeda
            )}
                    </td>

                    <td>
                        ${formatarMoedaFolha(
                folha.saldo_pendente,
                folha.moeda
            )}
                    </td>

                    <td>
                        <span class="badge
                            ${classeSituacaoPagamentoFolha(
                situacaoPagamento
            )}">
                            ${situacaoPagamento}
                        </span>
                    </td>

                    <td>
                        <span class="badge
                            ${classeEstadoFolha(
                estado
            )}">
                            ${estado}
                        </span>
                    </td>

                    <td class="text-right">
                        ${botoesFolha(folha)}
                    </td>
                </tr>
            `;
        }).join('');
}

async function carregarPeriodosFolhaSalarial() {
    const selectFiltro =
        document.getElementById(
            'filtroPeriodo'
        );

    const selectFormulario =
        document.getElementById(
            'periodoId'
        );

    try {
        const resposta =
            await enviarFolhaAjax({
                acao: 'periodos',
                csrf_token:
                    obterCsrfTokenFolhas()
            });

        const periodos =
            Array.isArray(resposta.data)
                ? resposta.data
                : [];

        if (selectFiltro) {
            selectFiltro.innerHTML = `
                <option value="">
                    Todos os períodos
                </option>
            `;
        }

        if (selectFormulario) {
            selectFormulario.innerHTML = `
                <option value="">
                    Selecione o período
                </option>
            `;
        }

        periodos.forEach(periodo => {
            if (selectFiltro) {
                selectFiltro.appendChild(
                    criarOpcaoPeriodo(periodo)
                );
            }

            if (selectFormulario) {
                selectFormulario.appendChild(
                    criarOpcaoPeriodo(periodo)
                );
            }
        });

        if (
            periodos.length === 0
            && selectFormulario
        ) {
            selectFormulario.innerHTML = `
                <option value="">
                    Nenhum período disponível
                </option>
            `;
        }
    } catch (erro) {
        console.error(
            'Erro ao carregar períodos:',
            erro
        );

        if (selectFormulario) {
            selectFormulario.innerHTML = `
                <option value="">
                    Erro ao carregar períodos
                </option>
            `;
        }
    }
}

function criarOpcaoPeriodo(periodo) {
    const option =
        document.createElement('option');

    option.value =
        String(periodo.id);

    const descricao =
        periodo.descricao
        || periodo.codigo
        || `Período ${periodo.id}`;

    const estado =
        periodo.estado
            ? ` - ${periodo.estado}`
            : '';

    option.textContent =
        `${descricao}${estado}`;

    return option;
}

function botoesFolha(folha) {

    const id =
        Number(folha.id || 0);

    const estado =
        String(folha.estado || '');

    let botoes = `
        <button
            type="button"
            class="btn btn-sm btn-info"
            onclick="visualizarFolhaSalarial(${id})"
            title="Visualizar"
        >
            <i class="fa fa-eye"></i>
        </button>
    `;


    if (estado === 'Gerada') {

        botoes += `
            <button
                type="button"
                class="btn btn-sm btn-warning"
                onclick="recalcularFolhaSalarial(${id})"
                title="Recalcular"
            >
                <i class="fa fa-refresh"></i>
            </button>

            <button
                type="button"
                class="btn btn-sm btn-success"
                onclick="fecharFolhaSalarial(${id})"
                title="Fechar"
            >
                <i class="fa fa-lock"></i>
            </button>
        `;
    }


    if (estado === 'Fechada') {

        botoes += `
            <button
                type="button"
                class="btn btn-sm btn-primary"
                onclick="contabilizarFolhaSalarial(${id})"
                title="Contabilizar"
            >
                <i class="fa fa-calculator"></i>
            </button>
        `;
    }


    if (
        estado !== 'Cancelada'
        && Number(
            folha.total_pago || 0
        ) <= 0
    ) {

        botoes += `
            <button
                type="button"
                class="btn btn-sm btn-danger"
                onclick="abrirCancelamentoFolha(${id})"
                title="Cancelar"
            >
                <i class="fa fa-times"></i>
            </button>
        `;
    }


    return botoes;
}

async function submeterFolhaSalarial(event) {
    event.preventDefault();

    const formulario =
        event.currentTarget;

    const botao =
        formulario.querySelector(
            '[type="submit"]'
        );

    try {
        bloquearBotaoFolha(botao, true);

        const periodoId =
            formulario.querySelector(
                '[name="periodo_id"]'
            )?.value;

        const observacoes =
            formulario.querySelector(
                '[name="observacoes"]'
            )?.value || '';

        const resultado =
            await enviarFolhaAjax({
                acao: 'gerar',
                periodo_id: periodoId,
                observacoes,
                csrf_token:
                    obterCsrfTokenFolhas()
            });

        mostrarMensagemFolha(
            resultado.message,
            'success'
        );

        formulario.reset();

        fecharModalFolha(
            '#modalFolhaSalarial'
        );

        await carregarFolhasSalariais();
        await carregarResumoFolhas();
    } catch (erro) {
        console.error(erro);

        mostrarMensagemFolha(
            erro.message,
            'error'
        );
    } finally {
        bloquearBotaoFolha(botao, false);
    }
}

async function recalcularFolhaSalarial(id) {
    const confirmado =
        await confirmarAcaoFolha({
            titulo: 'Recalcular folha?',
            texto:
                'Os valores da folha serão '
                + 'calculados novamente.',
            confirmButtonText: 'Sim, recalcular',
            icon: 'question'
        });

    if (!confirmado) {
        return;
    }

    try {
        const resultado =
            await enviarFolhaAjax({
                acao: 'recalcular',
                id,
                csrf_token:
                    obterCsrfTokenFolhas()
            });

        mostrarMensagemFolha(
            resultado.message,
            'success'
        );

        await carregarFolhasSalariais();
        await carregarResumoFolhas();
    } catch (erro) {
        console.error(erro);

        mostrarMensagemFolha(
            erro.message,
            'error'
        );
    }
}

async function fecharFolhaSalarial(id) {
    const confirmado =
        await confirmarAcaoFolha({
            titulo: 'Fechar folha salarial?',
            texto:
                'Depois de fechada, a folha '
                + 'não poderá ser recalculada.',
            confirmButtonText: 'Sim, fechar',
            icon: 'warning'
        });

    if (!confirmado) {
        return;
    }

    try {
        const resultado =
            await enviarFolhaAjax({
                acao: 'fechar',
                id,
                csrf_token:
                    obterCsrfTokenFolhas()
            });

        mostrarMensagemFolha(
            resultado.message,
            'success'
        );

        await carregarFolhasSalariais();
        await carregarResumoFolhas();
    } catch (erro) {
        console.error(erro);

        mostrarMensagemFolha(
            erro.message,
            'error'
        );
    }
}

async function contabilizarFolhaSalarial(
    id
) {

    const confirmado =
        await confirmarAcaoFolha({
            titulo:
                'Contabilizar folha salarial?',

            texto:
                'Serão reconhecidos os gastos com pessoal, '
                + 'salários a pagar, INSS, IRPS e demais '
                + 'rubricas contabilísticas configuradas.',

            confirmButtonText:
                'Sim, contabilizar',

            icon:
                'warning'
        });


    if (!confirmado) {
        return;
    }


    try {

        const resultado =
            await enviarFolhaAjax({
                acao:
                    'contabilizar',

                id:
                    id,

                csrf_token:
                    obterCsrfTokenFolhas()
            });


        mostrarMensagemFolha(
            resultado.message
            || 'Folha salarial contabilizada com sucesso.',
            'success'
        );


        await carregarFolhasSalariais();

        await carregarResumoFolhas();

    } catch (erro) {

        console.error(
            'Erro ao contabilizar folha:',
            erro
        );


        mostrarMensagemFolha(
            erro.message,
            'error'
        );
    }
}

function abrirCancelamentoFolha(id) {
    const campoId =
        document.querySelector(
            '#formCancelarFolha [name="id"]'
        );

    if (campoId) {
        campoId.value = id;
    }

    abrirModalFolha(
        '#modalCancelarFolha'
    );
}

async function submeterCancelamentoFolha(
    event
) {
    event.preventDefault();

    const formulario =
        event.currentTarget;

    const botao =
        formulario.querySelector(
            '[type="submit"]'
        );

    try {
        bloquearBotaoFolha(botao, true);

        const id =
            formulario.querySelector(
                '[name="id"]'
            )?.value;

        const motivo =
            formulario.querySelector(
                '[name="motivo"]'
            )?.value || '';

        const resultado =
            await enviarFolhaAjax({
                acao: 'cancelar',
                id,
                motivo,
                csrf_token:
                    obterCsrfTokenFolhas()
            });

        mostrarMensagemFolha(
            resultado.message,
            'success'
        );

        formulario.reset();

        fecharModalFolha(
            '#modalCancelarFolha'
        );

        await carregarFolhasSalariais();
        await carregarResumoFolhas();
    } catch (erro) {
        console.error(erro);

        mostrarMensagemFolha(
            erro.message,
            'error'
        );
    } finally {
        bloquearBotaoFolha(botao, false);
    }
}

async function visualizarFolhaSalarial(id) {
    try {
        const resultado =
            await enviarFolhaAjax({
                acao: 'buscar',
                id,
                csrf_token:
                    obterCsrfTokenFolhas()
            });

        preencherDetalhesFolha(
            resultado.data
        );

        abrirModalFolha(
            '#modalDetalhesFolha'
        );
    } catch (erro) {
        console.error(erro);

        mostrarMensagemFolha(
            erro.message,
            'error'
        );
    }
}

function preencherDetalhesFolha(folha) {
    const titulo =
        document.getElementById(
            'detalheFolhaTitulo'
        );

    if (titulo) {
        titulo.textContent =
            `${folha.codigo} - `
            + `${folha.periodo_descricao || ''}`;
    }

    const corpo =
        document.querySelector(
            '#detalhesFolhaTable tbody'
        );

    if (!corpo) {
        return;
    }

    const funcionarios =
        folha.funcionarios || [];

    corpo.innerHTML =
        funcionarios.map(item => `
            <tr>
                <td>
                    ${escaparHtmlFolha(
            item.numero_funcionario
        )}
                </td>

                <td>
                    ${escaparHtmlFolha(
            item.funcionario_nome
        )}
                </td>

                <td>
                    ${escaparHtmlFolha(
            item.cargo_nome || ''
        )}
                </td>

                <td>
                    ${formatarMoedaFolha(
            item.total_salario_base,
            item.moeda
        )}
                </td>

                <td>
                    ${formatarMoedaFolha(
            Number(item.total_proventos)
            - Number(
                item.total_salario_base
            ),
            item.moeda
        )}
                </td>

                <td>
                    ${formatarMoedaFolha(
            item.total_descontos,
            item.moeda
        )}
                </td>

                <td>
                    ${formatarMoedaFolha(
            item.salario_liquido,
            item.moeda
        )}
                </td>
            </tr>
        `).join('');
}

async function carregarResumoFolhas() {
    try {
        const resultado =
            await enviarFolhaAjax({
                acao: 'resumo',
                csrf_token:
                    obterCsrfTokenFolhas()
            });

        const resumo =
            resultado.data || {};

        definirTextoFolha(
            'resumoFolhasQuantidade',
            resumo.quantidade_folhas || 0
        );

        definirTextoFolha(
            'resumoFolhasFuncionarios',
            resumo.numero_funcionarios || 0
        );

        definirTextoFolha(
            'resumoFolhasLiquido',
            formatarMoedaFolha(
                resumo.total_liquido,
                'MZN'
            )
        );

        definirTextoFolha(
            'resumoFolhasPendente',
            formatarMoedaFolha(
                resumo.saldo_pendente,
                'MZN'
            )
        );
    } catch (erro) {
        console.error(
            'Erro ao carregar resumo:',
            erro
        );
    }
}

function definirTextoFolha(id, valor) {
    const elemento =
        document.getElementById(id);

    if (elemento) {
        elemento.textContent = valor;
    }
}

function formatarMoedaFolha(
    valor,
    moeda = 'MZN'
) {
    return new Intl.NumberFormat(
        'pt-MZ',
        {
            style: 'currency',
            currency: moeda || 'MZN',
            minimumFractionDigits: 2
        }
    ).format(Number(valor || 0));
}

function classeEstadoFolha(estado) {
    switch (estado) {
        case 'Gerada':
            return 'badge-warning';

        case 'Fechada':
            return 'badge-success';

        case 'Cancelada':
            return 'badge-danger';

        default:
            return 'badge-secondary';
    }
}

function obterSituacaoPagamentoFolha(folha) {
    const totalLiquido =
        Number(folha.total_liquido || 0);

    const totalPago =
        Number(folha.total_pago || 0);

    const saldoPendente =
        Number(folha.saldo_pendente || 0);

    const estado =
        String(folha.estado || '')
            .trim()
            .toLowerCase();

    /*
    |--------------------------------------------------------------------------
    | Folha cancelada
    |--------------------------------------------------------------------------
    */

    if (estado === 'cancelada') {
        return 'Cancelado';
    }

    /*
    |--------------------------------------------------------------------------
    | Totalmente paga
    |--------------------------------------------------------------------------
    */

    if (
        totalLiquido > 0
        && saldoPendente <= 0.01
        && totalPago >= totalLiquido - 0.01
    ) {
        return 'Pago';
    }

    /*
    |--------------------------------------------------------------------------
    | Pagamento parcial
    |--------------------------------------------------------------------------
    */

    if (
        totalPago > 0
        && saldoPendente > 0.01
    ) {
        return 'Parcial';
    }

    /*
    |--------------------------------------------------------------------------
    | Ainda sem pagamento
    |--------------------------------------------------------------------------
    */

    return 'Pendente';
}


function classeSituacaoPagamentoFolha(
    situacao
) {
    switch (situacao) {
        case 'Pago':
            return 'badge-success';

        case 'Parcial':
            return 'badge-warning';

        case 'Pendente':
            return 'badge-secondary';

        case 'Cancelado':
            return 'badge-danger';

        default:
            return 'badge-secondary';
    }
}

function escaparHtmlFolha(valor) {
    const elemento =
        document.createElement('div');

    elemento.textContent =
        valor ?? '';

    return elemento.innerHTML;
}

function bloquearBotaoFolha(
    botao,
    bloquear
) {
    if (!botao) {
        return;
    }

    if (bloquear) {
        botao.dataset.textoOriginal =
            botao.innerHTML;

        botao.disabled = true;

        botao.innerHTML = `
            <span class="spinner-border
                spinner-border-sm">
            </span>
            Aguarde...
        `;
    } else {
        botao.disabled = false;

        botao.innerHTML =
            botao.dataset.textoOriginal
            || 'Guardar';
    }
}

function abrirModalFolha(seletor) {
    if (
        typeof window.jQuery !== 'undefined'
    ) {
        window.jQuery(seletor).modal('show');
    }
}

function fecharModalFolha(seletor) {
    if (
        typeof window.jQuery !== 'undefined'
    ) {
        window.jQuery(seletor).modal('hide');
    }
}

async function confirmarAcaoFolha({
    titulo,
    texto,
    confirmButtonText = 'Confirmar',
    icon = 'warning'
}) {
    if (
        typeof window.Swal !== 'undefined'
    ) {
        const resultado =
            await Swal.fire({
                title: titulo,
                text: texto,
                icon,
                showCancelButton: true,
                confirmButtonText,
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                focusCancel: true
            });

        return resultado.isConfirmed;
    }

    return window.confirm(
        `${titulo}\n\n${texto}`
    );
}

function mostrarMensagemFolha(
    mensagem,
    tipo = 'success'
) {
    if (
        typeof window.Swal !== 'undefined'
    ) {
        Swal.fire({
            icon:
                tipo === 'error'
                    ? 'error'
                    : 'success',

            title:
                tipo === 'error'
                    ? 'Erro'
                    : 'Sucesso',

            text: mensagem,

            confirmButtonText: 'OK'
        });

        return;
    }

    console[
        tipo === 'error'
            ? 'error'
            : 'log'
    ](mensagem);
}