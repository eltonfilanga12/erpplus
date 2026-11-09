'use strict';


const PAGAMENTO_IVA_URL =
    '/erpplus/app/ajax/pagamento_iva_ajax.php';


let pagamentoIvaDados = {
    periodos: [],
    caixas: [],
    bancos: [],
    centros: []
};


let pagamentoIvaCalculo = null;


document.addEventListener(
    'DOMContentLoaded',
    iniciarPagamentoIva
);


async function iniciarPagamentoIva() {

    /*
    |--------------------------------------------------------------------------
    | Impedir que este JS execute noutras páginas
    |--------------------------------------------------------------------------
    */

    const formulario =
        document.getElementById(
            "formApuramentoIva"
        );

    const periodo =
        document.getElementById(
            "ivaPeriodoId"
        );

    if (!formulario || !periodo) {
        return;
    }

    configurarEventosPagamentoIva();

    try {

        const resposta =
            await pagamentoIvaAjax({
                acao: "dados_iniciais"
            });

        pagamentoIvaDados =
            resposta.data
            || pagamentoIvaDados;

        preencherDadosPagamentoIva();

        await listarApuramentosIva();

    } catch (erro) {

        mostrarAlertaIva(
            erro.message,
            "danger"
        );
    }
}


function configurarEventosPagamentoIva() {

    document
        .getElementById('ivaPeriodoId')
        ?.addEventListener(
            'change',
            sincronizarPeriodoIva
        );


    document
        .getElementById('ivaPeriodicidade')
        ?.addEventListener(
            'change',
            sincronizarPeriodoIva
        );


    document
        .getElementById('btnCalcularIva')
        ?.addEventListener(
            'click',
            calcularIva
        );


    document
        .getElementById('btnApurarIva')
        ?.addEventListener(
            'click',
            confirmarApuramentoIva
        );


    document
        .getElementById('pagamentoIvaFonte')
        ?.addEventListener(
            'change',
            alternarFontePagamentoIva
        );


    document
        .getElementById('formPagamentoIva')
        ?.addEventListener(
            'submit',
            confirmarPagamentoIva
        );
}


function preencherDadosPagamentoIva() {

    garantirCampoCentroCustoIva();

    const periodo =
        document.getElementById(
            'ivaPeriodoId'
        );


    periodo.innerHTML =
        '<option value="">Selecione</option>'
        + pagamentoIvaDados.periodos
            .map(function (item) {

                return `
                    <option
                        value="${item.id}"
                        data-inicio="${item.data_inicio}"
                        data-fim="${item.data_fim}">

                        ${escIva(item.nome)}
                        (${item.data_inicio.substring(0, 4)})

                    </option>
                `;
            })
            .join('');


    const caixas =
        document.getElementById(
            'pagamentoIvaCaixa'
        );


    caixas.innerHTML =
        pagamentoIvaDados.caixas
            .map(function (caixa) {

                return `
                    <option value="${caixa.id}">
                        ${escIva(caixa.nome)}
                        — ${moedaIva(caixa.saldo_atual)}
                    </option>
                `;
            })
            .join('');


    const bancos =
        document.getElementById(
            'pagamentoIvaBanco'
        );


    bancos.innerHTML =
        pagamentoIvaDados.bancos
            .map(function (banco) {

                return `
                    <option value="${banco.id}">
                        ${escIva(banco.nome_banco)}
                        — ${escIva(banco.conta)}
                        — ${moedaIva(banco.saldo_atual)}
                    </option>
                `;
            })
            .join('');

    const centros =
        document.getElementById(
            'pagamentoIvaCentroCusto'
        );

    centros.innerHTML =
        '<option value="">Selecione</option>'
        + (pagamentoIvaDados.centros || [])
            .map(function (centro) {
                return `
                    <option value="${centro.id}">
                        ${escIva(centro.codigo)} — ${escIva(centro.nome)}
                    </option>
                `;
            })
            .join('');

    if ((pagamentoIvaDados.centros || []).length === 1) {
        centros.value = String(pagamentoIvaDados.centros[0].id);
    }
}


function garantirCampoCentroCustoIva() {
    if (document.getElementById('pagamentoIvaCentroCusto')) {
        return;
    }

    const grupoBanco =
        document.getElementById('grupoPagamentoIvaBanco');

    if (!grupoBanco) {
        return;
    }

    const grupo = document.createElement('div');
    grupo.className = 'form-group';
    grupo.innerHTML = `
        <label for="pagamentoIvaCentroCusto">Centro de custo</label>
        <select id="pagamentoIvaCentroCusto" class="form-control" required>
            <option value="">Selecione</option>
        </select>
    `;

    grupoBanco.parentNode.insertBefore(grupo, grupoBanco);
}


function sincronizarPeriodoIva() {

    const select =
        document.getElementById(
            'ivaPeriodoId'
        );


    const opcao =
        select.options[
        select.selectedIndex
        ];


    if (!opcao || !opcao.value) {
        return;
    }


    let inicio =
        opcao.dataset.inicio;


    const fim =
        opcao.dataset.fim;


    const periodicidade =
        document.getElementById(
            'ivaPeriodicidade'
        ).value;


    if (periodicidade === 'TRIMESTRAL') {

        const data =
            new Date(
                `${fim}T00:00:00`
            );


        const primeiroMesTrimestre =
            Math.floor(
                data.getMonth() / 3
            ) * 3;


        inicio =
            `${data.getFullYear()}-`
            + `${String(
                primeiroMesTrimestre + 1
            ).padStart(2, '0')}-01`;
    }


    document.getElementById(
        'ivaDataInicio'
    ).value = inicio;


    document.getElementById(
        'ivaDataFim'
    ).value = fim;


    pagamentoIvaCalculo = null;


    document.getElementById(
        'btnApurarIva'
    ).disabled = true;
}



async function calcularIva() {
    const botaoCalcular = document.getElementById('btnCalcularIva');
    const botaoApurar = document.getElementById('btnApurarIva');

    // Reset às variáveis
    pagamentoIvaCalculo = null;
    botaoApurar.disabled = true;

    document.getElementById('ivaLiquidado').textContent = moedaIva(0);
    document.getElementById('ivaDedutivel').textContent = moedaIva(0);
    document.getElementById('ivaPagar').textContent = moedaIva(0);

    const resumo = document.getElementById('ivaCreditoFiscalResumo');
    if (resumo) {
        resumo.remove();
    }

    try {
        botaoCalcular.disabled = true;

        const payload = obterPeriodoIva();

        // CORREÇÃO: Usar Optional Chaining (?.) ou omitir o filial_id no payload
        const resposta = await pagamentoIvaAjax({
            acao: 'calcular', // ou 'apurar' consoante a sua rota AJAX
            ...payload
        }, true);

        const calculo = resposta.data;

        if (!calculo || typeof calculo !== 'object') {
            throw new Error('O servidor não devolveu os dados do cálculo do IVA.');
        }

        pagamentoIvaCalculo = calculo;

        document.getElementById('ivaLiquidado').textContent =
            moedaIva(calculo.iva_liquidado);

        document.getElementById('ivaDedutivel').textContent =
            moedaIva(calculo.iva_dedutivel);

        document.getElementById('ivaPagar').textContent =
            moedaIva(calculo.iva_pagar);

        atualizarResumoCreditoFiscal(calculo);

        if (calculo.filial_id != null) {
            mostrarAlertaIva(
                `IVA calculado para a filial ID ${calculo.filial_id}.`,
                'success'
            );
        }

        botaoApurar.disabled = false;

    } catch (erro) {
        pagamentoIvaCalculo = null;
        await mostrarAlertaIva(erro.message, 'danger');
    } finally {
        botaoCalcular.disabled = false;
    }
}


async function confirmarApuramentoIva() {

    if (!pagamentoIvaCalculo) {
        await mostrarAlertaIva(
            'Primeiro deve calcular o IVA do período.',
            'warning'
        );

        return;
    }

    const confirmacao = await Swal.fire({
        icon: 'question',
        title: 'Confirmar apuramento?',
        html: `
            <div class="text-left">
                <p>
                    <strong>IVA liquidado:</strong>
                    ${moedaIva(pagamentoIvaCalculo.iva_liquidado)}
                </p>

                <p>
                    <strong>IVA dedutível:</strong>
                    ${moedaIva(pagamentoIvaCalculo.iva_dedutivel)}
                </p>

                <p>
                    <strong>Crédito anterior:</strong>
                    ${moedaIva(pagamentoIvaCalculo.credito_fiscal_anterior)}
                </p>

                <p>
                    <strong>Crédito utilizado:</strong>
                    ${moedaIva(pagamentoIvaCalculo.credito_fiscal_utilizado)}
                </p>

                <p>
                    <strong>IVA a pagar:</strong>
                    ${moedaIva(pagamentoIvaCalculo.iva_pagar)}
                </p>

                <p>
                    <strong>Crédito para o período seguinte:</strong>
                    ${moedaIva(pagamentoIvaCalculo.credito_fiscal_seguinte)}
                </p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Sim, confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    });

    if (!confirmacao.isConfirmed) {
        return;
    }

    const botao =
        document.getElementById(
            'btnApurarIva'
        );

    try {

        botao.disabled = true;

        Swal.fire({
            title: 'A processar...',
            text: 'Aguarde enquanto o IVA é apurado.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });

        const payload =
            obterPeriodoIva();

        const resposta =
            await pagamentoIvaAjax(
                {
                    acao: 'apurar',
                    ...payload
                },
                true
            );

        await Swal.fire({
            icon: 'success',
            title: 'Apuramento concluído',
            text:
                resposta.message
                || 'IVA apurado com sucesso.',
            confirmButtonText: 'OK'
        });

        pagamentoIvaCalculo = null;

        await listarApuramentosIva();

    } catch (erro) {

        botao.disabled = false;

        await mostrarAlertaIva(
            erro.message,
            'danger'
        );
    }
}

function atualizarResumoCreditoFiscal(calculo) {
    let resumo = document.getElementById('ivaCreditoFiscalResumo');
    if (!resumo) {
        resumo = document.createElement('div');
        resumo.id = 'ivaCreditoFiscalResumo';
        resumo.className = 'alert alert-info';
        const tabela = document.getElementById('ivaApuramentosBody')?.closest('.card');
        tabela?.parentNode.insertBefore(resumo, tabela);
    }
    resumo.innerHTML = `
        <strong>Crédito fiscal:</strong>
        anterior ${moedaIva(calculo.credito_fiscal_anterior)},
        utilizado ${moedaIva(calculo.credito_fiscal_utilizado)},
        gerado ${moedaIva(calculo.credito_fiscal_gerado)} e
        a transportar ${moedaIva(calculo.credito_fiscal_seguinte)}.
    `;
}


function obterPeriodoIva() {

    const periodoId =
        document.getElementById(
            'ivaPeriodoId'
        ).value;


    const dataInicio =
        document.getElementById(
            'ivaDataInicio'
        ).value;


    const dataFim =
        document.getElementById(
            'ivaDataFim'
        ).value;


    if (
        !periodoId
        || !dataInicio
        || !dataFim
    ) {
        throw new Error(
            'Selecione o período do IVA.'
        );
    }


    return {
        periodo_id: periodoId,

        periodicidade:
            document.getElementById(
                'ivaPeriodicidade'
            ).value,

        data_inicio: dataInicio,

        data_fim: dataFim
    };
}


async function listarApuramentosIva() {

    try {

        const resposta =
            await pagamentoIvaAjax({
                acao: 'listar'
            });


        const linhas =
            Array.isArray(resposta.data)
                ? resposta.data
                : [];


        const body =
            document.getElementById(
                'ivaApuramentosBody'
            );


        if (!linhas.length) {

            body.innerHTML = `
                <tr>
                    <td
                        colspan="9"
                        class="text-center text-muted">

                        Nenhum apuramento registado.

                    </td>
                </tr>
            `;

            return;
        }


        body.innerHTML =
            linhas
                .map(function (apuramento) {

                    const saldo =
                        Number(
                            apuramento.saldo_pagar
                            || 0
                        );


                    const quantidadePagamentos =
                        Number(
                            apuramento.quantidade_pagamentos
                            || 0
                        );

                    const botaoPagar =
                        saldo > 0
                            ? `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-success"
                                    onclick="abrirPagamentoIva(
                                        ${Number(apuramento.id)},
                                        ${saldo}
                                    )">

                                    <i class="fa fa-money"></i>
                                    Pagar
                                </button>
                                 `
                            : '';

                    const botaoAnular =
                        quantidadePagamentos === 0
                            ? `
                            <button
                                type="button"
                                class="btn btn-sm btn-danger"
                                onclick="anularApuramentoIva(
                                    ${Number(apuramento.id)}
                                )">

                                <i class="fa fa-times"></i>
                                Anular
                            </button>
                        `
                            : '';

                    const botaoHistorico = quantidadePagamentos > 0
                        ? `
                            <button type="button" class="btn btn-sm btn-info" onclick="verHistoricoPagamentos(${Number(apuramento.id)})" title="Ver Histórico">
                                <i class="fa fa-history"></i>
                            </button>
                        `
                        : '';

                    const botao = `
                        <div class="btn-group">
                            ${botaoPagar}
                            ${botaoHistorico}
                            ${botaoAnular}
                        </div>
                    `;


                    return `
                        <tr>

                            <td>
                                <div class="small text-muted">
                                    Filial: ${escIva(apuramento.filial_nome || 'Sem filial')}
                                </div>
                                ${escIva(apuramento.data_inicio)}
                                a
                                ${escIva(apuramento.data_fim)}
                                <div class="small text-muted">
                                    Crédito anterior: ${moedaIva(apuramento.credito_fiscal_anterior)}<br>
                                    Crédito seguinte: ${moedaIva(apuramento.credito_fiscal_seguinte)}
                                </div>
                            </td>

                            <td>
                                ${escIva(apuramento.data_limite)}
                            </td>

                            <td>
                                ${moedaIva(apuramento.iva_liquidado)}
                            </td>

                            <td>
                                ${moedaIva(apuramento.iva_dedutivel)}
                            </td>

                            <td>
                                ${moedaIva(apuramento.iva_pagar)}
                            </td>

                            <td>
                                ${moedaIva(apuramento.valor_pago)}
                            </td>

                            <td>
                                ${moedaIva(apuramento.saldo_pagar)}
                            </td>

                            <td>
                                <span
                                    class="badge
                                    ${classeEstadoIva(
                        apuramento.situacao
                    )}">

                                    ${escIva(
                        apuramento.situacao
                    )}

                                </span>
                            </td>

                            <td>
                                ${botao}
                            </td>

                        </tr>
                    `;
                })
                .join('');

    } catch (erro) {

        mostrarAlertaIva(
            erro.message,
            'danger'
        );
    }
}

async function verHistoricoPagamentos(apuramentoId) {
    try {
        const body = document.getElementById('historicoPagamentosIvaBody');
        body.innerHTML = '<tr><td colspan="6" class="text-center">A carregar...</td></tr>';

        if (window.jQuery && window.jQuery.fn.modal) {
            window.jQuery('#modalHistoricoPagamentosIva').modal('show');
        }

        const resposta = await pagamentoIvaAjax({
            acao: 'pagamentos',
            apuramento_id: apuramentoId
        });

        const lista = Array.isArray(resposta.data) ? resposta.data : [];

        if (!lista.length) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhum pagamento registado.</td></tr>';
            return;
        }

        body.innerHTML = lista.map(p => {
            const linkComprovativo = p.comprovativo
                ? `<a href="/erpplus/${p.comprovativo}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-download"></i> Ver</a>`
                : '<span class="text-muted">Sem ficheiro</span>';

            return `
                <tr>
                    <td>${escIva(p.data_pagamento)}</td>
                    <td><span class="badge badge-${p.fonte === 'BANCO' ? 'primary' : 'warning'}">${escIva(p.fonte)}</span></td>
                    <td>${escIva(p.conta_nome || 'N/A')}</td>
                    <td>${escIva(p.referencia)}</td>
                    <td>${moedaIva(p.valor)}</td>
                    <td class="text-center">${linkComprovativo}</td>
                </tr>
            `;
        }).join('');

    } catch (erro) {
        mostrarAlertaIva(erro.message, 'danger');
    }
}

async function anularApuramentoIva(
    apuramentoId
) {
    const confirmacao =
        await Swal.fire({
            icon: 'warning',
            title: 'Anular apuramento?',
            html: `
                <p>
                    O lançamento contabilístico deste
                    apuramento também será removido.
                </p>

                <p class="mb-0">
                    Depois poderá calcular e apurar
                    novamente o mesmo período.
                </p>
            `,
            showCancelButton: true,
            confirmButtonText: 'Sim, anular',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true
        });

    if (!confirmacao.isConfirmed) {
        return;
    }

    try {

        Swal.fire({
            title: 'A anular...',
            text: 'Por favor, aguarde.',
            allowOutsideClick: false,
            allowEscapeKey: false,

            didOpen: function () {
                Swal.showLoading();
            }
        });

        const resposta =
            await pagamentoIvaAjax(
                {
                    acao: 'anular',
                    apuramento_id: apuramentoId
                },
                true
            );

        pagamentoIvaCalculo = null;

        document.getElementById(
            'btnApurarIva'
        ).disabled = true;

        await Swal.fire({
            icon: 'success',
            title: 'Apuramento anulado',
            text:
                resposta.message
                || 'Apuramento do IVA anulado com sucesso.',
            confirmButtonText: 'OK'
        });

        await listarApuramentosIva();

        document.getElementById(
            'ivaLiquidado'
        ).textContent = moedaIva(0);

        document.getElementById(
            'ivaDedutivel'
        ).textContent = moedaIva(0);

        document.getElementById(
            'ivaPagar'
        ).textContent = moedaIva(0);

    } catch (erro) {

        await mostrarAlertaIva(
            erro.message,
            'danger'
        );
    }
}


function abrirPagamentoIva(
    id,
    saldo
) {

    document.getElementById(
        'formPagamentoIva'
    ).reset();

    if ((pagamentoIvaDados.centros || []).length === 1) {
        document.getElementById(
            'pagamentoIvaCentroCusto'
        ).value = String(pagamentoIvaDados.centros[0].id);
    }


    document.getElementById(
        'pagamentoIvaApuramentoId'
    ).value = id;


    document.getElementById(
        'pagamentoIvaSaldo'
    ).textContent =
        moedaIva(saldo);


    document.getElementById(
        'pagamentoIvaValor'
    ).value =
        Number(saldo).toFixed(2);


    document.getElementById(
        'pagamentoIvaValor'
    ).max = saldo;


    const agora =
        new Date();


    const local =
        new Date(
            agora.getTime()
            - agora.getTimezoneOffset()
            * 60000
        )
            .toISOString()
            .slice(0, 16);


    document.getElementById(
        'pagamentoIvaData'
    ).value = local;


    alternarFontePagamentoIva();


    if (
        window.jQuery
        && window.jQuery.fn.modal
    ) {
        window
            .jQuery('#modalPagamentoIva')
            .modal('show');
    }
}


function alternarFontePagamentoIva() {

    const usarCaixa =
        document.getElementById(
            'pagamentoIvaFonte'
        ).value === 'CAIXA';


    document.getElementById(
        'grupoPagamentoIvaCaixa'
    ).classList.toggle(
        'd-none',
        !usarCaixa
    );


    document.getElementById(
        'grupoPagamentoIvaBanco'
    ).classList.toggle(
        'd-none',
        usarCaixa
    );


    document.getElementById(
        'pagamentoIvaCaixa'
    ).required = usarCaixa;


    document.getElementById(
        'pagamentoIvaBanco'
    ).required = !usarCaixa;
}


async function confirmarPagamentoIva(event) {
    event.preventDefault();

    const formulario = document.getElementById('formPagamentoIva');

    if (!formulario.checkValidity()) {
        formulario.reportValidity();
        return;
    }

    const fonte = document.getElementById('pagamentoIvaFonte').value;
    const valor = Number(document.getElementById('pagamentoIvaValor').value);
    const saldo = Number(document.getElementById('pagamentoIvaValor').max);

    if (!Number.isFinite(valor) || valor <= 0) {
        await mostrarAlertaIva('Informe um valor válido para o pagamento.', 'warning');
        return;
    }

    if (Number.isFinite(saldo) && valor > saldo) {
        await mostrarAlertaIva('O valor do pagamento não pode ser superior ao saldo pendente.', 'warning');
        return;
    }

    const nomeFonte = fonte === 'CAIXA' ? 'Caixa' : 'Banco';

    const confirmacao = await Swal.fire({
        icon: 'warning',
        title: 'Confirmar pagamento?',
        html: `
            <p>
                Será registado um pagamento de
                <strong>${moedaIva(valor)}</strong>
                através de <strong>${nomeFonte}</strong>.
            </p>
            <p class="mb-0">
                Esta operação movimentará o saldo da conta selecionada.
            </p>
        `,
        showCancelButton: true,
        confirmButtonText: 'Sim, pagar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    });

    if (!confirmacao.isConfirmed) {
        return;
    }

    const botaoSubmit = formulario.querySelector('button[type="submit"]');

    try {
        botaoSubmit.disabled = true;

        Swal.fire({
            title: 'A processar pagamento...',
            text: 'Por favor, aguarde.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });

        // 1. Criar FormData para suportar texto e o ficheiro anexado
        const formData = new FormData();
        formData.append('acao', 'pagar');
        formData.append('apuramento_id', document.getElementById('pagamentoIvaApuramentoId').value);
        formData.append('fonte', fonte);
        formData.append('centro_custo_id', document.getElementById('pagamentoIvaCentroCusto').value);
        formData.append('caixa_id', fonte === 'CAIXA' ? document.getElementById('pagamentoIvaCaixa').value : '');
        formData.append('banco_id', fonte === 'BANCO' ? document.getElementById('pagamentoIvaBanco').value : '');
        formData.append('valor', valor);
        formData.append('data_pagamento', document.getElementById('pagamentoIvaData').value);
        formData.append('referencia', document.getElementById('pagamentoIvaReferencia').value.trim());
        formData.append('documento', document.getElementById('pagamentoIvaDocumento').value.trim());
        formData.append('observacoes', document.getElementById('pagamentoIvaObservacoes').value.trim());

        const ficheiroInput = document.getElementById('pagamentoIvaComprovativo');
        if (ficheiroInput && ficheiroInput.files[0]) {
            formData.append('comprovativo', ficheiroInput.files[0]);
        }

        // 2. Enviar a requisição POST via fetch com FormData
        const requisicao = await fetch(PAGAMENTO_IVA_URL, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const resposta = await requisicao.json();

        if (!requisicao.ok || !resposta.success) {
            throw new Error(resposta.message || 'Erro ao processar pagamento.');
        }

        // 3. Fechar modal e redefinir o formulário
        if (window.jQuery && window.jQuery.fn.modal) {
            window.jQuery('#modalPagamentoIva').modal('hide');
        }

        formulario.reset();

        await Swal.fire({
            icon: 'success',
            title: 'Pagamento concluído',
            text: resposta.message || 'Pagamento do IVA registado com sucesso.',
            confirmButtonText: 'OK'
        });

        await listarApuramentosIva();

    } catch (erro) {
        await mostrarAlertaIva(erro.message, 'danger');
    } finally {
        botaoSubmit.disabled = false;
    }
}

async function pagamentoIvaAjax(
    dados,
    post = false
) {

    const url =
        post
            ? PAGAMENTO_IVA_URL
            : PAGAMENTO_IVA_URL
            + '?'
            + new URLSearchParams(
                dados
            ).toString();


    const opcoes = {
        method:
            post
                ? 'POST'
                : 'GET',

        credentials:
            'same-origin'
    };


    if (post) {

        opcoes.headers = {
            'Content-Type':
                'application/json'
        };


        opcoes.body =
            JSON.stringify(dados);
    }


    const resposta =
        await fetch(
            url,
            opcoes
        );


    const texto =
        await resposta.text();


    let json;


    try {

        json =
            JSON.parse(texto);

    } catch (erro) {

        throw new Error(
            'O servidor não devolveu JSON válido. '
            + texto.substring(0, 200)
        );
    }


    if (
        !resposta.ok
        || !json.success
    ) {
        throw new Error(
            json.message
            || 'Erro ao processar IVA.'
        );
    }


    return json;
}

function mostrarAlertaIva(
    mensagem,
    tipo = 'info'
) {
    const icones = {
        success: 'success',
        danger: 'error',
        warning: 'warning',
        info: 'info'
    };

    if (typeof Swal !== 'undefined') {
        return Swal.fire({
            icon: icones[tipo] || 'info',
            title:
                tipo === 'success'
                    ? 'Sucesso'
                    : tipo === 'danger'
                        ? 'Erro'
                        : tipo === 'warning'
                            ? 'Atenção'
                            : 'Informação',
            text: mensagem,
            confirmButtonText: 'OK'
        });
    }

    alert(mensagem);
}


function moedaIva(
    valor
) {

    return Number(
        valor || 0
    ).toLocaleString(
        'pt-PT',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    ) + ' MTn';
}


function classeEstadoIva(
    estado
) {

    estado =
        String(
            estado || ''
        ).toUpperCase();


    if (estado === 'PAGO') {
        return 'badge-success';
    }


    if (estado === 'PARCIAL') {
        return 'badge-warning';
    }


    if (estado === 'VENCIDO') {
        return 'badge-danger';
    }


    if (
        estado.includes(
            'RECUPERAR'
        )
    ) {
        return 'badge-info';
    }


    return 'badge-secondary';
}


function escIva(
    valor
) {

    const div =
        document.createElement(
            'div'
        );


    div.textContent =
        valor == null
            ? ''
            : String(valor);


    return div.innerHTML;
}
