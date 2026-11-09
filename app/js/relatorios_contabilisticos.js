'use strict';


/*
|--------------------------------------------------------------------------
| CONFIGURAÇÃO
|--------------------------------------------------------------------------
*/

const RELATORIOS_CONTABILISTICOS_URL =
    '/erpplus/app/ajax/relatorios_contabilisticos_ajax.php';


let tipoRelatorioContabilisticoAtual =
    'DIARIO';


/*
|--------------------------------------------------------------------------
| INICIALIZAÇÃO
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        iniciarRelatoriosContabilisticos();
    }
);


async function iniciarRelatoriosContabilisticos() {

    configurarEventosRelatoriosContabilisticos();


    await Promise.all([
        carregarPeriodosRelatorioContabilistico(),
        carregarDiariosRelatorioContabilistico(),
        carregarContasRelatorioContabilistico(),
        carregarFiliaisRelatorioContabilistico()
    ]);


    configurarInterfaceRelatorioContabilistico(
        tipoRelatorioContabilisticoAtual
    );


    await carregarRelatorioContabilistico();
}


/*
|--------------------------------------------------------------------------
| EVENTOS
|--------------------------------------------------------------------------
*/

function configurarEventosRelatoriosContabilisticos() {

    /*
    |--------------------------------------------------------------------------
    | Abas desktop
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '#relatorioContabilisticoTabs '
            + '[data-relatorio]'
        )
        .forEach(
            function (aba) {

                aba.addEventListener(
                    'click',
                    async function (event) {

                        event.preventDefault();


                        const tipo =
                            String(
                                aba.dataset.relatorio
                                || ''
                            ).toUpperCase();


                        await selecionarTipoRelatorioContabilistico(
                            tipo
                        );
                    }
                );
            }
        );


    /*
    |--------------------------------------------------------------------------
    | Mobile
    |--------------------------------------------------------------------------
    */

    const mobile =
        document.getElementById(
            'tipoRelatorioContabilisticoMobile'
        );


    if (mobile) {

        mobile.addEventListener(
            'change',
            async function () {

                await selecionarTipoRelatorioContabilistico(
                    mobile.value
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    const form =
        document.getElementById(
            'formFiltrosRelatorioContabilistico'
        );


    if (form) {

        form.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();

                await carregarRelatorioContabilistico();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Limpar
    |--------------------------------------------------------------------------
    */

    const limpar =
        document.getElementById(
            'btnLimparFiltrosRelatorioContabilistico'
        );


    if (limpar) {

        limpar.addEventListener(
            'click',
            async function () {

                limparFiltrosRelatorioContabilistico();

                await carregarRelatorioContabilistico();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Período
    |--------------------------------------------------------------------------
    */

    const periodo =
        document.getElementById(
            'relatorioContabilisticoPeriodo'
        );


    if (periodo) {

        periodo.addEventListener(
            'change',
            function () {

                sincronizarDatasComPeriodoRelatorioContabilistico();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Imprimir
    |--------------------------------------------------------------------------
    */

    const imprimir =
        document.getElementById(
            'btnImprimirRelatorioContabilistico'
        );


    if (imprimir) {

        imprimir.addEventListener(
            'click',
            imprimirRelatorioContabilistico
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Exportar
    |--------------------------------------------------------------------------
    */

    const exportar =
        document.getElementById(
            'btnExportarRelatorioContabilistico'
        );


    if (exportar) {

        exportar.addEventListener(
            'click',
            exportarRelatorioContabilistico
        );
    }
}


/*
|--------------------------------------------------------------------------
| SELECIONAR RELATÓRIO
|--------------------------------------------------------------------------
*/

async function selecionarTipoRelatorioContabilistico(
    tipo
) {

    tipo =
        String(
            tipo || ''
        ).toUpperCase();


    const permitidos = [
        'DIARIO',
        'RAZAO',
        'BALANCETE',
        'DRE',
        'BALANCO_PATRIMONIAL',
        'IVA'
    ];


    if (!permitidos.includes(tipo)) {
        return;
    }


    tipoRelatorioContabilisticoAtual =
        tipo;


    const hidden =
        document.getElementById(
            'tipoRelatorioContabilistico'
        );


    if (hidden) {
        hidden.value = tipo;
    }


    const mobile =
        document.getElementById(
            'tipoRelatorioContabilisticoMobile'
        );


    if (mobile) {
        mobile.value = tipo;
    }


    document
        .querySelectorAll(
            '#relatorioContabilisticoTabs '
            + '[data-relatorio]'
        )
        .forEach(
            function (aba) {

                aba.classList.toggle(
                    'active',
                    String(
                        aba.dataset.relatorio
                        || ''
                    ).toUpperCase() === tipo
                );
            }
        );


    limparFiltrosEspecificosRelatorioContabilistico();


    configurarInterfaceRelatorioContabilistico(
        tipo
    );


    if (tipo === 'RAZAO') {

        zerarResumoRelatorioContabilistico();

        renderizarTabelaRelatorioContabilistico(
            tipo,
            []
        );

        mostrarMensagemRazaoSemConta();

        return;
    }


    await carregarRelatorioContabilistico();
}


/*
|--------------------------------------------------------------------------
| INTERFACE
|--------------------------------------------------------------------------
*/

function configurarInterfaceRelatorioContabilistico(
    tipo
) {

    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoTitulo',
        obterTituloRelatorioContabilistico(
            tipo
        )
    );


    /*
    |--------------------------------------------------------------------------
    | Por padrão
    |--------------------------------------------------------------------------
    */

    mostrarGrupoRelatorioContabilistico(
        'grupoRelatorioContabilisticoDataInicio',
        true
    );

    mostrarGrupoRelatorioContabilistico(
        'grupoRelatorioContabilisticoDataFim',
        true
    );

    mostrarGrupoRelatorioContabilistico(
        'grupoRelatorioContabilisticoPeriodo',
        true
    );

    mostrarGrupoRelatorioContabilistico(
        'grupoRelatorioContabilisticoDiario',
        false
    );

    mostrarGrupoRelatorioContabilistico(
        'grupoRelatorioContabilisticoConta',
        false
    );

    mostrarGrupoRelatorioContabilistico(
        'grupoRelatorioContabilisticoFilial',
        true
    );

    mostrarGrupoRelatorioContabilistico(
        'grupoRelatorioContabilisticoPesquisa',
        false
    );


    /*
    |--------------------------------------------------------------------------
    | Conta obrigatória
    |--------------------------------------------------------------------------
    */

    const avisoConta =
        document.getElementById(
            'relatorioContabilisticoContaObrigatoria'
        );


    if (avisoConta) {
        avisoConta.classList.add(
            'd-none'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Razão info
    |--------------------------------------------------------------------------
    */

    const razaoConta =
        document.getElementById(
            'relatorioContabilisticoRazaoConta'
        );


    if (razaoConta) {
        razaoConta.classList.add(
            'd-none'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Tabela vs estrutura
    |--------------------------------------------------------------------------
    */

    const tabela =
        document.getElementById(
            'relatorioContabilisticoTabelaContainer'
        );

    const estrutura =
        document.getElementById(
            'relatorioContabilisticoEstruturaContainer'
        );


    if (tabela) {
        tabela.classList.remove(
            'd-none'
        );
    }


    if (estrutura) {
        estrutura.classList.add(
            'd-none'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DIÁRIO
    |--------------------------------------------------------------------------
    */

    if (tipo === 'DIARIO') {

        mostrarGrupoRelatorioContabilistico(
            'grupoRelatorioContabilisticoDiario',
            true
        );

        mostrarGrupoRelatorioContabilistico(
            'grupoRelatorioContabilisticoConta',
            true
        );

        mostrarGrupoRelatorioContabilistico(
            'grupoRelatorioContabilisticoPesquisa',
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RAZÃO
    |--------------------------------------------------------------------------
    */

    if (tipo === 'RAZAO') {

        mostrarGrupoRelatorioContabilistico(
            'grupoRelatorioContabilisticoDiario',
            true
        );

        mostrarGrupoRelatorioContabilistico(
            'grupoRelatorioContabilisticoConta',
            true
        );

        mostrarGrupoRelatorioContabilistico(
            'grupoRelatorioContabilisticoPesquisa',
            true
        );


        if (avisoConta) {
            avisoConta.classList.remove(
                'd-none'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BALANCETE
    |--------------------------------------------------------------------------
    */

    if (tipo === 'BALANCETE') {

        mostrarGrupoRelatorioContabilistico(
            'grupoRelatorioContabilisticoConta',
            true
        );

        mostrarGrupoRelatorioContabilistico(
            'grupoRelatorioContabilisticoPesquisa',
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DRE
    |--------------------------------------------------------------------------
    */

    if (tipo === 'DRE') {

        if (tabela) {
            tabela.classList.add(
                'd-none'
            );
        }

        if (estrutura) {
            estrutura.classList.remove(
                'd-none'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BALANÇO
    |--------------------------------------------------------------------------
    */

    if (
        tipo === 'BALANCO_PATRIMONIAL'
    ) {

        mostrarGrupoRelatorioContabilistico(
            'grupoRelatorioContabilisticoDataInicio',
            false
        );

        mostrarGrupoRelatorioContabilistico(
            'grupoRelatorioContabilisticoPeriodo',
            false
        );


        if (tabela) {
            tabela.classList.add(
                'd-none'
            );
        }

        if (estrutura) {
            estrutura.classList.remove(
                'd-none'
            );
        }


        const labelDataFim =
            document.querySelector(
                'label[for="relatorioContabilisticoDataFim"]'
            );


        if (labelDataFim) {
            labelDataFim.textContent =
                'Data de referência';
        }

    } else {

        const labelDataFim =
            document.querySelector(
                'label[for="relatorioContabilisticoDataFim"]'
            );


        if (labelDataFim) {
            labelDataFim.textContent =
                'Data final';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | IVA
    |--------------------------------------------------------------------------
    */

    if (tipo === 'IVA') {

        mostrarGrupoRelatorioContabilistico(
            'grupoRelatorioContabilisticoPesquisa',
            false
        );
    }


    atualizarTitulosResumoRelatorioContabilistico(
        tipo
    );
}


/*
|--------------------------------------------------------------------------
| TÍTULO
|--------------------------------------------------------------------------
*/

function obterTituloRelatorioContabilistico(
    tipo
) {

    const mapa = {

        DIARIO:
            'Relatório Diário',

        RAZAO:
            'Razão Contabilística',

        BALANCETE:
            'Balancete',

        DRE:
            'Demonstração de Resultados',

        BALANCO_PATRIMONIAL:
            'Balanço Patrimonial',

        IVA:
            'Relatório de IVA'
    };


    return mapa[tipo]
        || 'Relatório Contabilístico';
}


/*
|--------------------------------------------------------------------------
| CARDS
|--------------------------------------------------------------------------
*/

function atualizarTitulosResumoRelatorioContabilistico(
    tipo
) {

    const configuracoes = {

        DIARIO: [
            'Lançamentos',
            'Débitos',
            'Créditos',
            'Diferença'
        ],

        RAZAO: [
            'Saldo inicial',
            'Débitos',
            'Créditos',
            'Saldo final'
        ],

        BALANCETE: [
            'Contas',
            'Débitos',
            'Créditos',
            'Diferença'
        ],

        DRE: [
            'Rendimentos',
            'Gastos',
            'Resultado líquido',
            'Margem'
        ],

        BALANCO_PATRIMONIAL: [
            'Ativo',
            'Passivo',
            'Capital Próprio',
            'Diferença'
        ],

        IVA: [
            'IVA liquidado',
            'IVA dedutível',
            'IVA a pagar',
            'IVA a recuperar'
        ]
    };


    const titulos =
        configuracoes[tipo]
        || [
            'Total',
            'Débito',
            'Crédito',
            'Resultado'
        ];


    for (
        let i = 0;
        i < 4;
        i++
    ) {

        definirTextoRelatorioContabilistico(
            `relatorioContabilisticoResumoTitulo${i + 1}`,
            titulos[i]
        );
    }
}


/*
|--------------------------------------------------------------------------
| AJAX
|--------------------------------------------------------------------------
*/

async function enviarRelatorioContabilisticoAjax(
    dados
) {

    const resposta =
        await fetch(
            RELATORIOS_CONTABILISTICOS_URL,
            {
                method:
                    'POST',

                headers: {

                    'Content-Type':
                        'application/json',

                    'Accept':
                        'application/json'
                },

                body:
                    JSON.stringify(
                        dados
                    )
            }
        );


    const texto =
        await resposta.text();


    let resultado;


    try {

        resultado =
            JSON.parse(
                texto
            );

    } catch (erro) {

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
            || 'Não foi possível carregar o relatório contabilístico.'
        );
    }


    return resultado;
}


/*
|--------------------------------------------------------------------------
| CARREGAR RELATÓRIO
|--------------------------------------------------------------------------
*/

async function carregarRelatorioContabilistico() {

    esconderErroRelatorioContabilistico();


    /*
    |--------------------------------------------------------------------------
    | Razão exige conta
    |--------------------------------------------------------------------------
    */

    if (
        tipoRelatorioContabilisticoAtual
        === 'RAZAO'
        && !obterValorRelatorioContabilistico(
            'relatorioContabilisticoConta'
        )
    ) {

        zerarResumoRelatorioContabilistico();

        mostrarMensagemRazaoSemConta();

        return;
    }


    mostrarCarregamentoRelatorioContabilistico();


    try {

        const resultado =
            await enviarRelatorioContabilisticoAjax({

                acao:
                    'relatorio',

                tipo:
                    tipoRelatorioContabilisticoAtual,

                data_inicio:
                    obterValorRelatorioContabilistico(
                        'relatorioContabilisticoDataInicio'
                    ),

                data_fim:
                    obterValorRelatorioContabilistico(
                        'relatorioContabilisticoDataFim'
                    ),

                periodo_id:
                    obterValorRelatorioContabilistico(
                        'relatorioContabilisticoPeriodo'
                    ),

                diario_id:
                    obterValorRelatorioContabilistico(
                        'relatorioContabilisticoDiario'
                    ),

                conta_id:
                    obterValorRelatorioContabilistico(
                        'relatorioContabilisticoConta'
                    ),

                filial_id:
                    obterValorRelatorioContabilistico(
                        'relatorioContabilisticoFilial'
                    ),

                pesquisa:
                    obterValorRelatorioContabilistico(
                        'relatorioContabilisticoPesquisa'
                    )
            });


        const dados =
            resultado.data
            || {};


        renderizarResumoRelatorioContabilistico(
            dados.resumo
            || {}
        );


        /*
        |--------------------------------------------------------------------------
        | Razão
        |--------------------------------------------------------------------------
        */

        if (
            tipoRelatorioContabilisticoAtual
            === 'RAZAO'
        ) {

            renderizarContaRazaoRelatorioContabilistico(
                dados.conta
                || {}
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DRE
        |--------------------------------------------------------------------------
        */

        if (
            tipoRelatorioContabilisticoAtual
            === 'DRE'
        ) {

            renderizarDreRelatorioContabilistico(
                dados
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Balanço
        |--------------------------------------------------------------------------
        */

        if (
            tipoRelatorioContabilisticoAtual
            === 'BALANCO_PATRIMONIAL'
        ) {

            renderizarBalancoRelatorioContabilistico(
                dados
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Tabela comum
        |--------------------------------------------------------------------------
        */

        renderizarTabelaRelatorioContabilistico(
            tipoRelatorioContabilisticoAtual,
            dados.registos
            || []
        );


    } catch (erro) {

        zerarResumoRelatorioContabilistico();


        mostrarErroRelatorioContabilistico(
            erro.message
        );


        if (
            tipoRelatorioContabilisticoAtual
            === 'DRE'
            || tipoRelatorioContabilisticoAtual
            === 'BALANCO_PATRIMONIAL'
        ) {

            limparEstruturaRelatorioContabilistico();

        } else {

            renderizarTabelaRelatorioContabilistico(
                tipoRelatorioContabilisticoAtual,
                []
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| RESUMO
|--------------------------------------------------------------------------
*/

function renderizarResumoRelatorioContabilistico(
    resumo
) {

    const tipo =
        tipoRelatorioContabilisticoAtual;


    if (tipo === 'DIARIO') {

        definirResumoContabilistico(
            resumo.total_lancamentos
            || 0,

            formatarMoedaRelatorioContabilistico(
                resumo.total_debito
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.total_credito
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.diferenca
            )
        );

        return;
    }


    if (tipo === 'RAZAO') {

        const inicial =
            Number(
                resumo.saldo_inicial_devedor
                || 0
            )
            -
            Number(
                resumo.saldo_inicial_credor
                || 0
            );


        const final =
            Number(
                resumo.saldo_final_devedor
                || 0
            )
            -
            Number(
                resumo.saldo_final_credor
                || 0
            );


        definirResumoContabilistico(
            formatarSaldoRelatorioContabilistico(
                inicial
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.total_debitos
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.total_creditos
            ),

            formatarSaldoRelatorioContabilistico(
                final
            )
        );

        return;
    }


    if (tipo === 'BALANCETE') {

        definirResumoContabilistico(
            resumo.total_contas
            || 0,

            formatarMoedaRelatorioContabilistico(
                resumo.total_debito
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.total_credito
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.diferenca
            )
        );

        return;
    }


    if (tipo === 'DRE') {

        definirResumoContabilistico(
            formatarMoedaRelatorioContabilistico(
                resumo.total_rendimentos
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.total_gastos
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.resultado_liquido
            ),

            formatarPercentagemRelatorioContabilistico(
                resumo.margem
            )
        );

        return;
    }


    if (
        tipo === 'BALANCO_PATRIMONIAL'
    ) {

        definirResumoContabilistico(
            formatarMoedaRelatorioContabilistico(
                resumo.total_ativo
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.total_passivo
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.capital_resultado
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.diferenca
            )
        );

        return;
    }


    if (tipo === 'IVA') {

        definirResumoContabilistico(
            formatarMoedaRelatorioContabilistico(
                resumo.iva_liquidado
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.iva_dedutivel
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.iva_pagar
            ),

            formatarMoedaRelatorioContabilistico(
                resumo.iva_recuperar
            )
        );
    }
}


function definirResumoContabilistico(
    valor1,
    valor2,
    valor3,
    valor4
) {

    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoResumoValor1',
        valor1
    );

    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoResumoValor2',
        valor2
    );

    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoResumoValor3',
        valor3
    );

    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoResumoValor4',
        valor4
    );
}


/*
|--------------------------------------------------------------------------
| TABELA
|--------------------------------------------------------------------------
*/

function renderizarTabelaRelatorioContabilistico(
    tipo,
    registos
) {

    const head =
        document.getElementById(
            'relatorioContabilisticoTableHead'
        );


    const body =
        document.getElementById(
            'relatorioContabilisticoTableBody'
        );


    if (!head || !body) {
        return;
    }


    const colunas =
        obterColunasRelatorioContabilistico(
            tipo
        );


    head.innerHTML = `
        <tr>

            ${colunas
            .map(
                coluna => `
                            <th>
                                ${escaparHtmlRelatorioContabilistico(
                    coluna.titulo
                )
                    }
                            </th>
                        `
            )
            .join('')
        }

        </tr>
    `;


    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoQuantidade',
        `${registos.length} registos`
    );


    if (
        !Array.isArray(registos)
        || registos.length === 0
    ) {

        body.innerHTML = `
            <tr>

                <td
                    colspan="${colunas.length}"
                    class="text-center text-muted">

                    Nenhum registo encontrado.

                </td>

            </tr>
        `;

        return;
    }


    body.innerHTML =
        registos
            .map(
                registo => `

                    <tr>

                        ${colunas
                        .map(
                            coluna => `
                                        <td>
                                            ${formatarCelulaRelatorioContabilistico(
                                registo,
                                coluna
                            )
                                }
                                        </td>
                                    `
                        )
                        .join('')
                    }

                    </tr>

                `
            )
            .join('');
}


/*
|--------------------------------------------------------------------------
| COLUNAS
|--------------------------------------------------------------------------
*/

function obterColunasRelatorioContabilistico(
    tipo
) {

    const mapa = {

        DIARIO: [

            {
                campo:
                    'data_lancamento',

                titulo:
                    'Data',

                tipo:
                    'data'
            },

            {
                campo:
                    'diario_codigo',

                titulo:
                    'Diário'
            },

            {
                campo:
                    'numero',

                titulo:
                    'N.º'
            },

            {
                campo:
                    'numero_documento',

                titulo:
                    'Documento'
            },

            {
                campo:
                    'conta_codigo',

                titulo:
                    'Conta'
            },

            {
                campo:
                    'conta_nome',

                titulo:
                    'Descrição da conta'
            },

            {
                campo:
                    'item_descricao',

                titulo:
                    'Descrição'
            },

            {
                campo:
                    'entidade',

                titulo:
                    'Entidade'
            },

            {
                campo:
                    'centro_custo',

                titulo:
                    'Centro de custo'
            },

            {
                campo:
                    'debito',

                titulo:
                    'Débito',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'credito',

                titulo:
                    'Crédito',

                tipo:
                    'moeda'
            }
        ],


        RAZAO: [

            {
                campo:
                    'data_lancamento',

                titulo:
                    'Data',

                tipo:
                    'data'
            },

            {
                campo:
                    'diario_codigo',

                titulo:
                    'Diário'
            },

            {
                campo:
                    'numero_documento',

                titulo:
                    'Documento',

                tipo:
                    'documento'
            },

            {
                campo:
                    'descricao',

                titulo:
                    'Descrição'
            },

            {
                campo:
                    'origem',

                titulo:
                    'Origem'
            },

            {
                campo:
                    'entidade',

                titulo:
                    'Entidade'
            },

            {
                campo:
                    'centro_custo',

                titulo:
                    'Centro de custo'
            },

            {
                campo:
                    'debito',

                titulo:
                    'Débito',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'credito',

                titulo:
                    'Crédito',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'saldo',

                titulo:
                    'Saldo',

                tipo:
                    'saldo_razao'
            }
        ],


        BALANCETE: [

            {
                campo:
                    'codigo',

                titulo:
                    'Código'
            },

            {
                campo:
                    'nome',

                titulo:
                    'Conta'
            },

            {
                campo:
                    'grupo',

                titulo:
                    'Grupo'
            },

            {
                campo:
                    'natureza',

                titulo:
                    'Natureza'
            },

            {
                campo:
                    'debito',

                titulo:
                    'Débito',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'credito',

                titulo:
                    'Crédito',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'saldo_devedor',

                titulo:
                    'Saldo Devedor',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'saldo_credor',

                titulo:
                    'Saldo Credor',

                tipo:
                    'moeda'
            }
        ],


        IVA: [

            {
                campo:
                    'data_lancamento',

                titulo:
                    'Data',

                tipo:
                    'data'
            },

            {
                campo:
                    'numero_documento',

                titulo:
                    'Documento',

                tipo:
                    'documento'
            },

            {
                campo:
                    'origem',

                titulo:
                    'Origem'
            },

            {
                campo:
                    'conta_codigo',

                titulo:
                    'Conta'
            },

            {
                campo:
                    'conta_nome',

                titulo:
                    'Conta IVA'
            },

            {
                campo:
                    'tipo_iva',

                titulo:
                    'Tipo',

                tipo:
                    'tipo_iva'
            },

            {
                campo:
                    'descricao',

                titulo:
                    'Descrição'
            },

            {
                campo:
                    'debito',

                titulo:
                    'Débito',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'credito',

                titulo:
                    'Crédito',

                tipo:
                    'moeda'
            }
        ]
    };


    return mapa[tipo]
        || [];
}


/*
|--------------------------------------------------------------------------
| FORMATAR CÉLULA
|--------------------------------------------------------------------------
*/

function formatarCelulaRelatorioContabilistico(
    registo,
    coluna
) {

    let valor =
        registo[coluna.campo];


    switch (coluna.tipo) {

        case 'moeda':

            return escaparHtmlRelatorioContabilistico(
                formatarMoedaRelatorioContabilistico(
                    valor
                )
            );


        case 'data':

            return escaparHtmlRelatorioContabilistico(
                formatarDataRelatorioContabilistico(
                    valor
                )
            );


        case 'documento':

            valor =
                valor
                || registo.referencia
                || registo.numero
                || '-';

            return escaparHtmlRelatorioContabilistico(
                valor
            );


        case 'saldo_razao':

            return escaparHtmlRelatorioContabilistico(
                formatarSaldoRazaoRelatorioContabilistico(
                    registo
                )
            );


        case 'tipo_iva':

            return criarBadgeIvaRelatorioContabilistico(
                valor
            );


        default:

            return escaparHtmlRelatorioContabilistico(
                valor
                ?? '-'
            );
    }
}


/*
|--------------------------------------------------------------------------
| DRE
|--------------------------------------------------------------------------
*/

function renderizarDreRelatorioContabilistico(
    dados
) {

    const container =
        document.getElementById(
            'relatorioContabilisticoEstrutura'
        );


    if (!container) {
        return;
    }


    const rendimentos =
        Array.isArray(
            dados.rendimentos
        )
            ? dados.rendimentos
            : [];


    const gastos =
        Array.isArray(
            dados.gastos
        )
            ? dados.gastos
            : [];


    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoQuantidade',
        `${rendimentos.length + gastos.length} contas`
    );


    container.innerHTML = `

        <div class="table-responsive">

            <table class="table table-bordered mb-0">

                <thead>

                    <tr>
                        <th>
                            Código
                        </th>

                        <th>
                            Conta
                        </th>

                        <th class="text-right">
                            Valor
                        </th>
                    </tr>

                </thead>


                <tbody>

                    <tr class="bg-light">

                        <th colspan="3">
                            RENDIMENTOS
                        </th>

                    </tr>


                    ${rendimentos.length
            ? rendimentos
                .map(
                    linha => `
                                        <tr>

                                            <td>
                                                ${escaparHtmlRelatorioContabilistico(
                        linha.codigo
                        || '-'
                    )
                        }
                                            </td>

                                            <td>
                                                ${escaparHtmlRelatorioContabilistico(
                            linha.nome
                            || '-'
                        )
                        }
                                            </td>

                                            <td class="text-right">
                                                ${escaparHtmlRelatorioContabilistico(
                            formatarMoedaRelatorioContabilistico(
                                linha.valor
                            )
                        )
                        }
                                            </td>

                                        </tr>
                                    `
                )
                .join('')
            : `
                                <tr>
                                    <td
                                        colspan="3"
                                        class="text-center text-muted">

                                        Sem rendimentos.

                                    </td>
                                </tr>
                            `
        }


                    <tr class="bg-light">

                        <th colspan="3">
                            GASTOS
                        </th>

                    </tr>


                    ${gastos.length
            ? gastos
                .map(
                    linha => `
                                        <tr>

                                            <td>
                                                ${escaparHtmlRelatorioContabilistico(
                        linha.codigo
                        || '-'
                    )
                        }
                                            </td>

                                            <td>
                                                ${escaparHtmlRelatorioContabilistico(
                            linha.nome
                            || '-'
                        )
                        }
                                            </td>

                                            <td class="text-right">
                                                ${escaparHtmlRelatorioContabilistico(
                            formatarMoedaRelatorioContabilistico(
                                linha.valor
                            )
                        )
                        }
                                            </td>

                                        </tr>
                                    `
                )
                .join('')
            : `
                                <tr>
                                    <td
                                        colspan="3"
                                        class="text-center text-muted">

                                        Sem gastos.

                                    </td>
                                </tr>
                            `
        }


                    <tr class="font-weight-bold">

                        <td colspan="2">
                            RESULTADO LÍQUIDO
                        </td>

                        <td class="text-right">

                            ${escaparHtmlRelatorioContabilistico(
            formatarMoedaRelatorioContabilistico(
                dados.resumo
                    ?.resultado_liquido
            )
        )
        }

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>
    `;
}


/*
|--------------------------------------------------------------------------
| BALANÇO
|--------------------------------------------------------------------------
*/

function renderizarBalancoRelatorioContabilistico(
    dados
) {

    const container =
        document.getElementById(
            'relatorioContabilisticoEstrutura'
        );


    if (!container) {
        return;
    }


    const ativos =
        Array.isArray(
            dados.ativos
        )
            ? dados.ativos
            : [];


    const passivos =
        Array.isArray(
            dados.passivos
        )
            ? dados.passivos
            : [];


    const resultadosAnteriores =
        Array.isArray(
            dados.resultados_anteriores
        )
            ? dados.resultados_anteriores
            : [];

    const linhasResultadosAnteriores =
        resultadosAnteriores.length
            ? resultadosAnteriores.map(function (linha) {

                const periodo =
                    formatarPeriodoMensalRelatorioContabilistico(
                        linha.periodo
                    );

                const resultado =
                    formatarMoedaRelatorioContabilistico(
                        linha.resultado
                    );

                return `
                    <tr>
                        <td>${escaparHtmlRelatorioContabilistico(periodo)}</td>
                        <td class="text-right">${escaparHtmlRelatorioContabilistico(resultado)}</td>
                    </tr>
                `;
            }).join('')
            : `
                <tr>
                    <td colspan="3" class="text-center text-muted">
                        Nenhum resultado de mês anterior.
                    </td>
                </tr>
            `;

    const totalAlertas =
        Number(
            dados.resumo?.total_alertas
            || 0
        );


    const avisoAlertas =
        totalAlertas > 0
            ? `
            <div class="alert alert-warning">

                <i class="fa fa-exclamation-triangle mr-1"></i>

                ${totalAlertas}
                ${totalAlertas === 1
                ? 'conta apresenta'
                : 'contas apresentam'
            }
                saldo contrário à sua natureza contabilística.

            </div>
        `
            : '';

    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoQuantidade',
        `${ativos.length + passivos.length} contas`
    );


    container.innerHTML = `

    ${avisoAlertas}

    <div class="row">


            <div class="col-lg-6">

                <div class="card border">

                    <div class="card-header">

                        <strong>
                            ATIVO
                        </strong>

                    </div>

                    <div class="card-body p-0">

                        ${criarTabelaGrupoBalancoRelatorioContabilistico(
        ativos
    )
        }

                    </div>

                    <div class="card-footer">

                        <strong>
                            Total do Ativo:
                        </strong>

                        <span class="float-right">

                            ${escaparHtmlRelatorioContabilistico(
            formatarMoedaRelatorioContabilistico(
                dados.resumo
                    ?.total_ativo
            )
        )
        }

                        </span>

                    </div>

                </div>

            </div>


            <div class="col-lg-6">


                <div class="card border">

                    <div class="card-header">

                        <strong>
                            PASSIVO
                        </strong>

                    </div>

                    <div class="card-body p-0">

                        ${criarTabelaGrupoBalancoRelatorioContabilistico(
            passivos
        )
        }

                    </div>

                    <div class="card-footer">

                        <strong>
                            Total do Passivo:
                        </strong>

                        <span class="float-right">

                            ${escaparHtmlRelatorioContabilistico(
            formatarMoedaRelatorioContabilistico(
                dados.resumo
                    ?.total_passivo
            )
        )
        }

                        </span>

                    </div>

                </div>


                <div class="card border">

                    <div class="card-header">

                        <strong>
                            CAPITAL PRÓPRIO
                        </strong>

                    </div>

                    <div class="card-body p-0">

                        <table class="table mb-0">
                            <tbody>
                                <tr>
                                    <td>
                                        Ativo − Passivo
                                    </td>
                                    <td class="text-right">
                                        <strong>
                                            ${escaparHtmlRelatorioContabilistico(
            formatarMoedaRelatorioContabilistico(
                dados.resumo?.capital_proprio
            )
        )}
                                        </strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>


                    <div class="card-footer">

                        <strong>
                            Capital Próprio:
                        </strong>

                        <span class="float-right">

                            ${escaparHtmlRelatorioContabilistico(
            formatarMoedaRelatorioContabilistico(
                dados.resumo
                    ?.capital_resultado
            )
        )
        }

                        </span>

                    </div>

                </div>

                <div class="card border">

                    <div class="card-header">
                        <strong>
                            RESULTADOS TRANSITADOS
                        </strong>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Período</th>
                                        <th class="text-right">Resultado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${linhasResultadosAnteriores}
                                </tbody>
                            </table>
                        </div>
                    </div>


                </div>

            </div>

        </div>
    `;
}


function criarTabelaGrupoBalancoRelatorioContabilistico(
    linhas
) {

    if (!linhas.length) {

        return `
            <div class="p-3 text-center text-muted">
                Nenhuma conta com saldo.
            </div>
        `;
    }


    return `

        <div class="table-responsive">

            <table class="table mb-0">

                <thead>

                    <tr>

                        <th style="width:130px;">
                            Código
                        </th>

                        <th>
                            Conta
                        </th>

                        <th class="text-right">
                            Valor
                        </th>

                        <th style="width:120px;">
                            Situação
                        </th>

                    </tr>

                </thead>


                <tbody>

                    ${linhas
            .map(
                linha => {

                    const contrario =
                        Number(
                            linha.saldo_contrario
                            || 0
                        ) === 1
                        || linha.saldo_contrario === true;


                    return `

                                        <tr
                                            class="${contrario
                            ? 'table-warning'
                            : ''
                        }">

                                            <td>

                                                ${escaparHtmlRelatorioContabilistico(
                            linha.codigo
                            || '-'
                        )
                        }

                                            </td>


                                            <td>

    <div>
        ${escaparHtmlRelatorioContabilistico(
                            linha.nome
                            || '-'
                        )
                        }
    </div>

    ${contrario
                            ? `
                <small
                    class="text-warning d-block mt-1">

                    <i
                        class="fa fa-exclamation-triangle">
                    </i>

                    ${escaparHtmlRelatorioContabilistico(
                                linha.alerta
                                || 'Saldo contrário à natureza da conta.'
                            )
                            }

                </small>
            `
                            : ''
                        }

</td>


                                            <td class="text-right">

                                                ${escaparHtmlRelatorioContabilistico(
                            formatarMoedaRelatorioContabilistico(
                                linha.valor
                            )
                        )
                        }

                                            </td>


                                            <td>

                                                ${contrario

                            ? `
                                                            <span
                                                                class="badge badge-warning">

                                                                Rever

                                                            </span>
                                                        `

                            : `
                                                            <span
                                                                class="badge badge-success">

                                                                Normal

                                                            </span>
                                                        `
                        }

                                            </td>

                                        </tr>
                                    `;
                }
            )
            .join('')
        }

                </tbody>

            </table>

        </div>
    `;
}

/*
|--------------------------------------------------------------------------
| RAZÃO - CONTA
|--------------------------------------------------------------------------
*/

function renderizarContaRazaoRelatorioContabilistico(
    conta
) {

    const card =
        document.getElementById(
            'relatorioContabilisticoRazaoConta'
        );


    if (card) {
        card.classList.remove(
            'd-none'
        );
    }


    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoRazaoCodigo',
        conta.codigo
        || '-'
    );


    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoRazaoNome',
        conta.nome
        || '-'
    );


    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoRazaoNatureza',
        conta.natureza
        || '-'
    );


    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoRazaoGrupo',
        conta.grupo
        || '-'
    );
}


function mostrarMensagemRazaoSemConta() {

    esconderErroRelatorioContabilistico();


    const card =
        document.getElementById(
            'relatorioContabilisticoRazaoConta'
        );


    if (card) {
        card.classList.add(
            'd-none'
        );
    }


    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoQuantidade',
        '0 registos'
    );


    const head =
        document.getElementById(
            'relatorioContabilisticoTableHead'
        );

    const body =
        document.getElementById(
            'relatorioContabilisticoTableBody'
        );


    if (head) {

        head.innerHTML = `
            <tr>
                <th>
                    Razão
                </th>
            </tr>
        `;
    }


    if (body) {

        body.innerHTML = `
            <tr>

                <td class="text-center text-muted">

                    Selecione uma conta contabilística
                    para gerar o Razão.

                </td>

            </tr>
        `;
    }
}


/*
|--------------------------------------------------------------------------
| CARREGAR PERÍODOS
|--------------------------------------------------------------------------
*/

async function carregarPeriodosRelatorioContabilistico() {

    const select =
        document.getElementById(
            'relatorioContabilisticoPeriodo'
        );


    if (!select) {
        return;
    }


    try {

        const resultado =
            await enviarRelatorioContabilisticoAjax({
                acao:
                    'periodos'
            });


        const periodos =
            Array.isArray(
                resultado.data
            )
                ? resultado.data
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
                    periodo.id;


                option.textContent =
                    `${periodo.nome} `
                    + `(${formatarDataRelatorioContabilistico(periodo.data_inicio)}`
                    + ` - ${formatarDataRelatorioContabilistico(periodo.data_fim)})`;


                option.dataset.inicio =
                    periodo.data_inicio
                    || '';


                option.dataset.fim =
                    periodo.data_fim
                    || '';


                select.appendChild(
                    option
                );
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
| SINCRONIZAR DATAS
|--------------------------------------------------------------------------
*/

function sincronizarDatasComPeriodoRelatorioContabilistico() {

    const periodo =
        document.getElementById(
            'relatorioContabilisticoPeriodo'
        );


    if (!periodo) {
        return;
    }


    const option =
        periodo.options[
        periodo.selectedIndex
        ];


    if (
        !option
        || !periodo.value
    ) {
        return;
    }


    const inicio =
        option.dataset.inicio
        || '';

    const fim =
        option.dataset.fim
        || '';


    const campoInicio =
        document.getElementById(
            'relatorioContabilisticoDataInicio'
        );

    const campoFim =
        document.getElementById(
            'relatorioContabilisticoDataFim'
        );


    if (
        campoInicio
        && inicio
    ) {
        campoInicio.value =
            inicio;
    }


    if (
        campoFim
        && fim
    ) {
        campoFim.value =
            fim;
    }
}


/*
|--------------------------------------------------------------------------
| DIÁRIOS
|--------------------------------------------------------------------------
*/

async function carregarDiariosRelatorioContabilistico() {

    const select =
        document.getElementById(
            'relatorioContabilisticoDiario'
        );


    if (!select) {
        return;
    }


    try {

        const resultado =
            await enviarRelatorioContabilisticoAjax({
                acao:
                    'diarios'
            });


        const diarios =
            Array.isArray(
                resultado.data
            )
                ? resultado.data
                : [];


        select.innerHTML = `
            <option value="">
                Todos os diários
            </option>
        `;


        diarios.forEach(
            diario => {

                const option =
                    document.createElement(
                        'option'
                    );


                option.value =
                    diario.id;


                option.textContent =
                    `${diario.codigo} - ${diario.nome}`;


                select.appendChild(
                    option
                );
            }
        );


    } catch (erro) {

        console.error(
            'Erro ao carregar diários:',
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| CONTAS
|--------------------------------------------------------------------------
*/

async function carregarContasRelatorioContabilistico() {

    const select =
        document.getElementById(
            'relatorioContabilisticoConta'
        );


    if (!select) {
        return;
    }


    try {

        const resultado =
            await enviarRelatorioContabilisticoAjax({
                acao:
                    'contas'
            });


        const contas =
            Array.isArray(
                resultado.data
            )
                ? resultado.data
                : [];


        select.innerHTML = `
            <option value="">
                Todas as contas
            </option>
        `;


        contas.forEach(
            conta => {

                const option =
                    document.createElement(
                        'option'
                    );


                option.value =
                    conta.id;


                option.textContent =
                    `${conta.codigo} - ${conta.nome}`;


                select.appendChild(
                    option
                );
            }
        );


    } catch (erro) {

        console.error(
            'Erro ao carregar contas:',
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| FILIAIS
|--------------------------------------------------------------------------
*/

async function carregarFiliaisRelatorioContabilistico() {

    const select =
        document.getElementById(
            'relatorioContabilisticoFilial'
        );


    if (!select) {
        return;
    }


    try {

        const resultado =
            await enviarRelatorioContabilisticoAjax({
                acao:
                    'filiais'
            });


        const filiais =
            Array.isArray(
                resultado.data
            )
                ? resultado.data
                : [];


        select.innerHTML = `
            <option value="">
                Todas as filiais
            </option>
        `;


        filiais.forEach(
            filial => {

                const option =
                    document.createElement(
                        'option'
                    );


                option.value =
                    filial.id;


                option.textContent =
                    filial.nome;


                select.appendChild(
                    option
                );
            }
        );


    } catch (erro) {

        console.error(
            'Erro ao carregar filiais:',
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| LIMPAR
|--------------------------------------------------------------------------
*/

function limparFiltrosRelatorioContabilistico() {

    const ids = [

        'relatorioContabilisticoDataInicio',

        'relatorioContabilisticoDataFim',

        'relatorioContabilisticoPeriodo',

        'relatorioContabilisticoDiario',

        'relatorioContabilisticoConta',

        'relatorioContabilisticoFilial',

        'relatorioContabilisticoPesquisa'
    ];


    ids.forEach(
        id => {

            const elemento =
                document.getElementById(
                    id
                );


            if (elemento) {
                elemento.value = '';
            }
        }
    );
}


function limparFiltrosEspecificosRelatorioContabilistico() {

    const ids = [

        'relatorioContabilisticoDiario',

        'relatorioContabilisticoConta',

        'relatorioContabilisticoPesquisa'
    ];


    ids.forEach(
        id => {

            const elemento =
                document.getElementById(
                    id
                );


            if (elemento) {
                elemento.value = '';
            }
        }
    );
}


/*
|--------------------------------------------------------------------------
| CARREGAMENTO
|--------------------------------------------------------------------------
*/

function mostrarCarregamentoRelatorioContabilistico() {

    if (
        tipoRelatorioContabilisticoAtual
        === 'DRE'
        || tipoRelatorioContabilisticoAtual
        === 'BALANCO_PATRIMONIAL'
    ) {

        const estrutura =
            document.getElementById(
                'relatorioContabilisticoEstrutura'
            );


        if (estrutura) {

            estrutura.innerHTML = `
                <div class="text-center text-muted p-4">

                    <i class="fa fa-spinner fa-spin"></i>

                    A carregar relatório...

                </div>
            `;
        }

        return;
    }


    const body =
        document.getElementById(
            'relatorioContabilisticoTableBody'
        );


    if (body) {

        body.innerHTML = `
            <tr>

                <td class="text-center text-muted">

                    <i class="fa fa-spinner fa-spin"></i>

                    A carregar relatório...

                </td>

            </tr>
        `;
    }
}


/*
|--------------------------------------------------------------------------
| ERRO
|--------------------------------------------------------------------------
*/

function mostrarErroRelatorioContabilistico(
    mensagem
) {

    const elemento =
        document.getElementById(
            'relatorioContabilisticoErro'
        );


    if (!elemento) {
        return;
    }


    elemento.textContent =
        mensagem;


    elemento.classList.remove(
        'd-none'
    );
}


function esconderErroRelatorioContabilistico() {

    const elemento =
        document.getElementById(
            'relatorioContabilisticoErro'
        );


    if (!elemento) {
        return;
    }


    elemento.textContent =
        '';


    elemento.classList.add(
        'd-none'
    );
}


function limparEstruturaRelatorioContabilistico() {

    const estrutura =
        document.getElementById(
            'relatorioContabilisticoEstrutura'
        );


    if (estrutura) {

        estrutura.innerHTML = `
            <div class="text-center text-muted p-4">
                Nenhum dado disponível.
            </div>
        `;
    }


    definirTextoRelatorioContabilistico(
        'relatorioContabilisticoQuantidade',
        '0 registos'
    );
}


/*
|--------------------------------------------------------------------------
| ZERAR
|--------------------------------------------------------------------------
*/

function zerarResumoRelatorioContabilistico() {

    for (
        let i = 1;
        i <= 4;
        i++
    ) {

        definirTextoRelatorioContabilistico(
            `relatorioContabilisticoResumoValor${i}`,
            '-'
        );
    }
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function obterValorRelatorioContabilistico(
    id
) {

    const elemento =
        document.getElementById(
            id
        );


    return elemento
        ? String(
            elemento.value
            || ''
        ).trim()
        : '';
}


function definirTextoRelatorioContabilistico(
    id,
    valor
) {

    const elemento =
        document.getElementById(
            id
        );


    if (elemento) {
        elemento.textContent =
            valor ?? '-';
    }
}


function mostrarGrupoRelatorioContabilistico(
    id,
    mostrar
) {

    const elemento =
        document.getElementById(
            id
        );


    if (!elemento) {
        return;
    }


    elemento.classList.toggle(
        'd-none',
        !mostrar
    );
}


/*
|--------------------------------------------------------------------------
| MOEDA
|--------------------------------------------------------------------------
*/

function formatarMoedaRelatorioContabilistico(
    valor
) {

    return new Intl.NumberFormat(
        'pt-MZ',
        {
            style:
                'currency',

            currency:
                'MZN',

            minimumFractionDigits:
                2,

            maximumFractionDigits:
                2
        }
    ).format(
        Number(
            valor || 0
        )
    );
}


/*
|--------------------------------------------------------------------------
| SALDO
|--------------------------------------------------------------------------
*/

function formatarSaldoRelatorioContabilistico(
    valor
) {

    valor =
        Number(
            valor || 0
        );


    if (valor > 0) {

        return formatarMoedaRelatorioContabilistico(
            valor
        ) + ' D';
    }


    if (valor < 0) {

        return formatarMoedaRelatorioContabilistico(
            Math.abs(valor)
        ) + ' C';
    }


    return formatarMoedaRelatorioContabilistico(
        0
    );
}


function formatarSaldoRazaoRelatorioContabilistico(
    registo
) {

    const devedor =
        Number(
            registo.saldo_devedor
            || 0
        );


    const credor =
        Number(
            registo.saldo_credor
            || 0
        );


    if (devedor > 0) {

        return formatarMoedaRelatorioContabilistico(
            devedor
        ) + ' D';
    }


    if (credor > 0) {

        return formatarMoedaRelatorioContabilistico(
            credor
        ) + ' C';
    }


    return formatarMoedaRelatorioContabilistico(
        0
    );
}


/*
|--------------------------------------------------------------------------
| PERCENTAGEM
|--------------------------------------------------------------------------
*/

function formatarPercentagemRelatorioContabilistico(
    valor
) {

    return new Intl.NumberFormat(
        'pt-MZ',
        {
            minimumFractionDigits:
                2,

            maximumFractionDigits:
                2
        }
    ).format(
        Number(
            valor || 0
        )
    )
        + '%';
}


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

function formatarDataRelatorioContabilistico(
    valor
) {

    if (!valor) {
        return '-';
    }


    const partes =
        String(valor)
            .substring(
                0,
                10
            )
            .split('-');


    if (
        partes.length !== 3
    ) {

        return String(valor);
    }


    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}


/*
|--------------------------------------------------------------------------
| IVA BADGE
|--------------------------------------------------------------------------
*/

function criarBadgeIvaRelatorioContabilistico(
    tipo
) {

    const valor =
        String(
            tipo || ''
        );


    if (
        valor === 'IVA_LIQUIDADO'
    ) {

        return `
            <span class="badge badge-danger">
                IVA Liquidado
            </span>
        `;
    }


    if (
        valor === 'IVA_DEDUTIVEL'
    ) {

        return `
            <span class="badge badge-success">
                IVA Dedutível
            </span>
        `;
    }


    return `
        <span class="badge badge-secondary">
            ${escaparHtmlRelatorioContabilistico(
        valor || 'Outro'
    )
        }
        </span>
    `;
}


/*
|--------------------------------------------------------------------------
| IMPRIMIR
|--------------------------------------------------------------------------
*/

function imprimirRelatorioContabilistico() {

    /*
    |--------------------------------------------------------------------------
    | Razão exige conta
    |--------------------------------------------------------------------------
    */

    if (
        tipoRelatorioContabilisticoAtual
        === 'RAZAO'
        && !obterValorRelatorioContabilistico(
            'relatorioContabilisticoConta'
        )
    ) {

        alert(
            'Selecione uma conta contabilística para imprimir o Razão.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Balanço exige data
    |--------------------------------------------------------------------------
    */

    if (
        tipoRelatorioContabilisticoAtual
        === 'BALANCO_PATRIMONIAL'
        && !obterValorRelatorioContabilistico(
            'relatorioContabilisticoDataFim'
        )
    ) {

        alert(
            'Informe a data de referência do Balanço Patrimonial.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Parâmetros
    |--------------------------------------------------------------------------
    */

    const parametros =
        new URLSearchParams({

            tipo:
                tipoRelatorioContabilisticoAtual,

            data_inicio:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoDataInicio'
                ),

            data_fim:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoDataFim'
                ),

            periodo_id:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoPeriodo'
                ),

            diario_id:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoDiario'
                ),

            conta_id:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoConta'
                ),

            filial_id:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoFilial'
                ),

            pesquisa:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoPesquisa'
                )
        });


    /*
    |--------------------------------------------------------------------------
    | Abrir versão profissional
    |--------------------------------------------------------------------------
    */

    window.open(
        '/erpplus/app/ajax/'
        + 'relatorios_contabilisticos_imprimir.php?'
        + parametros.toString(),

        '_blank'
    );
}


/*
|--------------------------------------------------------------------------
| EXPORTAR
|--------------------------------------------------------------------------
*/

function exportarRelatorioContabilistico() {

    const parametros =
        new URLSearchParams({

            tipo:
                tipoRelatorioContabilisticoAtual,

            data_inicio:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoDataInicio'
                ),

            data_fim:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoDataFim'
                ),

            periodo_id:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoPeriodo'
                ),

            diario_id:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoDiario'
                ),

            conta_id:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoConta'
                ),

            filial_id:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoFilial'
                ),

            pesquisa:
                obterValorRelatorioContabilistico(
                    'relatorioContabilisticoPesquisa'
                )
        });


    window.open(
        '/erpplus/app/ajax/'
        + 'relatorios_contabilisticos_exportar.php?'
        + parametros.toString(),

        '_blank'
    );
}


/*
|--------------------------------------------------------------------------
| ESCAPAR HTML
|--------------------------------------------------------------------------
*/

function escaparHtmlRelatorioContabilistico(
    valor
) {

    const elemento =
        document.createElement(
            'div'
        );


    elemento.textContent =
        valor ?? '';


    return elemento.innerHTML;
}


function formatarPeriodoMensalRelatorioContabilistico(
    periodo
) {

    const partes =
        String(periodo || '')
            .split('-');

    if (partes.length !== 2) {
        return String(periodo || '-');
    }

    const ano = Number(partes[0]);
    const mes = Number(partes[1]);

    if (!ano || mes < 1 || mes > 12) {
        return String(periodo || '-');
    }

    const nomes = [
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

    return `${nomes[mes - 1]} de ${ano}`;
}
