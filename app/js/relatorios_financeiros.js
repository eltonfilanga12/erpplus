'use strict';


/*
|--------------------------------------------------------------------------
| CONFIGURAÇÃO
|--------------------------------------------------------------------------
*/

const RELATORIOS_FINANCEIROS_URL =
    '/erpplus/app/ajax/relatorios_financeiros_ajax.php';


let tipoRelatorioFinanceiroAtual =
    'FLUXO_CAIXA';


/*
|--------------------------------------------------------------------------
| INICIALIZAÇÃO
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        iniciarRelatoriosFinanceiros();
    }
);


async function iniciarRelatoriosFinanceiros() {

    configurarEventosRelatoriosFinanceiros();

    await Promise.all([
        carregarFiliaisRelatorioFinanceiro(),
        carregarBancosRelatorioFinanceiro(),
        carregarCentrosCustoRelatorioFinanceiro()
    ]);


    configurarInterfaceRelatorioFinanceiro(
        tipoRelatorioFinanceiroAtual
    );


    await carregarEstadosRelatorioFinanceiro();


    await carregarRelatorioFinanceiro();
}


/*
|--------------------------------------------------------------------------
| EVENTOS
|--------------------------------------------------------------------------
*/

function configurarEventosRelatoriosFinanceiros() {

    /*
    |--------------------------------------------------------------------------
    | Abas desktop
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '#relatorioFinanceiroTabs '
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


                        await selecionarTipoRelatorioFinanceiro(
                            tipo
                        );
                    }
                );
            }
        );


    /*
    |--------------------------------------------------------------------------
    | Select mobile
    |--------------------------------------------------------------------------
    */

    const selectMobile =
        document.getElementById(
            'tipoRelatorioFinanceiroMobile'
        );


    if (selectMobile) {

        selectMobile.addEventListener(
            'change',
            async function () {

                await selecionarTipoRelatorioFinanceiro(
                    selectMobile.value
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Formulário
    |--------------------------------------------------------------------------
    */

    const formulario =
        document.getElementById(
            'formFiltrosRelatorioFinanceiro'
        );


    if (formulario) {

        formulario.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();

                await carregarRelatorioFinanceiro();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Limpar
    |--------------------------------------------------------------------------
    */

    const btnLimpar =
        document.getElementById(
            'btnLimparFiltrosRelatorioFinanceiro'
        );


    if (btnLimpar) {

        btnLimpar.addEventListener(
            'click',
            async function () {

                limparFiltrosRelatorioFinanceiro();

                await carregarEstadosRelatorioFinanceiro();

                await carregarRelatorioFinanceiro();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Imprimir
    |--------------------------------------------------------------------------
    */

    const btnImprimir =
        document.getElementById(
            'btnImprimirRelatorioFinanceiro'
        );


    if (btnImprimir) {

        btnImprimir.addEventListener(
            'click',
            imprimirRelatorioFinanceiro
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Exportar
    |--------------------------------------------------------------------------
    */

    const btnExportar =
        document.getElementById(
            'btnExportarRelatorioFinanceiro'
        );


    if (btnExportar) {

        btnExportar.addEventListener(
            'click',
            exportarRelatorioFinanceiro
        );
    }
}


/*
|--------------------------------------------------------------------------
| SELECIONAR TIPO
|--------------------------------------------------------------------------
*/

async function selecionarTipoRelatorioFinanceiro(
    tipo
) {

    tipo =
        String(
            tipo || ''
        ).toUpperCase();


    const permitidos = [
        'FLUXO_CAIXA',
        'RECEITAS_DESPESAS',
        'CONTAS_RECEBER',
        'CONTAS_PAGAR',
        'CENTROS_CUSTO',
        'MOVIMENTACAO_BANCARIA'
    ];


    if (
        !permitidos.includes(
            tipo
        )
    ) {
        return;
    }


    tipoRelatorioFinanceiroAtual =
        tipo;


    /*
    |--------------------------------------------------------------------------
    | Hidden
    |--------------------------------------------------------------------------
    */

    const campoTipo =
        document.getElementById(
            'tipoRelatorioFinanceiro'
        );


    if (campoTipo) {
        campoTipo.value = tipo;
    }


    /*
    |--------------------------------------------------------------------------
    | Mobile
    |--------------------------------------------------------------------------
    */

    const selectMobile =
        document.getElementById(
            'tipoRelatorioFinanceiroMobile'
        );


    if (selectMobile) {
        selectMobile.value = tipo;
    }


    /*
    |--------------------------------------------------------------------------
    | Aba ativa
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '#relatorioFinanceiroTabs '
            + '[data-relatorio]'
        )
        .forEach(
            function (aba) {

                const ativo =
                    String(
                        aba.dataset.relatorio
                        || ''
                    ).toUpperCase()
                    === tipo;


                aba.classList.toggle(
                    'active',
                    ativo
                );
            }
        );


    /*
    |--------------------------------------------------------------------------
    | Limpar filtros específicos
    |--------------------------------------------------------------------------
    */

    limparFiltrosEspecificosRelatorioFinanceiro();


    /*
    |--------------------------------------------------------------------------
    | Interface
    |--------------------------------------------------------------------------
    */

    configurarInterfaceRelatorioFinanceiro(
        tipo
    );


    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    await carregarEstadosRelatorioFinanceiro();


    /*
    |--------------------------------------------------------------------------
    | Dados
    |--------------------------------------------------------------------------
    */

    await carregarRelatorioFinanceiro();
}


/*
|--------------------------------------------------------------------------
| INTERFACE POR RELATÓRIO
|--------------------------------------------------------------------------
*/

function configurarInterfaceRelatorioFinanceiro(
    tipo
) {

    /*
    |--------------------------------------------------------------------------
    | Título
    |--------------------------------------------------------------------------
    */

    definirTextoRelatorioFinanceiro(
        'relatorioFinanceiroTitulo',
        obterTituloRelatorioFinanceiro(
            tipo
        )
    );


    /*
    |--------------------------------------------------------------------------
    | Esconder filtros
    |--------------------------------------------------------------------------
    */

    mostrarGrupoRelatorioFinanceiro(
        'grupoRelatorioFinanceiroEstado',
        false
    );


    mostrarGrupoRelatorioFinanceiro(
        'grupoRelatorioFinanceiroBanco',
        false
    );


    mostrarGrupoRelatorioFinanceiro(
        'grupoRelatorioFinanceiroCentroCusto',
        false
    );


    mostrarGrupoRelatorioFinanceiro(
        'grupoRelatorioFinanceiroTipoMovimento',
        false
    );


    /*
    |--------------------------------------------------------------------------
    | Mostrar filtros aplicáveis
    |--------------------------------------------------------------------------
    */

    if (
        tipo === 'CONTAS_RECEBER'
        || tipo === 'CONTAS_PAGAR'
    ) {

        mostrarGrupoRelatorioFinanceiro(
            'grupoRelatorioFinanceiroEstado',
            true
        );
    }


    if (
        tipo === 'RECEITAS_DESPESAS'
        || tipo === 'CENTROS_CUSTO'
        || tipo === 'MOVIMENTACAO_BANCARIA'
    ) {

        mostrarGrupoRelatorioFinanceiro(
            'grupoRelatorioFinanceiroCentroCusto',
            true
        );
    }


    if (
        tipo === 'MOVIMENTACAO_BANCARIA'
    ) {

        mostrarGrupoRelatorioFinanceiro(
            'grupoRelatorioFinanceiroBanco',
            true
        );


        mostrarGrupoRelatorioFinanceiro(
            'grupoRelatorioFinanceiroTipoMovimento',
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Títulos dos cards
    |--------------------------------------------------------------------------
    */

    const configuracao =
        obterConfiguracaoResumoRelatorioFinanceiro(
            tipo
        );


    definirTextoRelatorioFinanceiro(
        'relatorioFinanceiroResumoTitulo1',
        configuracao[0]
    );


    definirTextoRelatorioFinanceiro(
        'relatorioFinanceiroResumoTitulo2',
        configuracao[1]
    );


    definirTextoRelatorioFinanceiro(
        'relatorioFinanceiroResumoTitulo3',
        configuracao[2]
    );


    definirTextoRelatorioFinanceiro(
        'relatorioFinanceiroResumoTitulo4',
        configuracao[3]
    );
}


/*
|--------------------------------------------------------------------------
| TÍTULOS
|--------------------------------------------------------------------------
*/

function obterTituloRelatorioFinanceiro(
    tipo
) {

    const titulos = {

        FLUXO_CAIXA:
            'Relatório de Fluxo de Caixa',

        RECEITAS_DESPESAS:
            'Relatório de Receitas e Despesas',

        CONTAS_RECEBER:
            'Relatório de Contas a Receber',

        CONTAS_PAGAR:
            'Relatório de Contas a Pagar',

        CENTROS_CUSTO:
            'Relatório de Centros de Custo',

        MOVIMENTACAO_BANCARIA:
            'Relatório de Movimentação Bancária'
    };


    return titulos[tipo]
        || 'Relatório Financeiro';
}


/*
|--------------------------------------------------------------------------
| CARDS
|--------------------------------------------------------------------------
*/

function obterConfiguracaoResumoRelatorioFinanceiro(
    tipo
) {

    const configuracoes = {

        FLUXO_CAIXA: [
            'Entradas',
            'Saídas',
            'Saldo do período',
            'Movimentos'
        ],

        RECEITAS_DESPESAS: [
            'Receitas',
            'Despesas',
            'Resultado',
            'Margem'
        ],

        CONTAS_RECEBER: [
            'Total a receber',
            'Recebido',
            'Pendente',
            'Vencido'
        ],

        CONTAS_PAGAR: [
            'Total a pagar',
            'Pago',
            'Pendente',
            'Vencido'
        ],

        CENTROS_CUSTO: [
            'Centros de custo',
            'Receitas',
            'Despesas',
            'Resultado'
        ],

        MOVIMENTACAO_BANCARIA: [
            'Entradas',
            'Saídas',
            'Saldo líquido',
            'Movimentos'
        ]
    };


    return configuracoes[tipo]
        || [
            'Total',
            'Valor',
            'Resultado',
            'Indicador'
        ];
}


/*
|--------------------------------------------------------------------------
| AJAX
|--------------------------------------------------------------------------
*/

async function enviarRelatorioFinanceiroAjax(
    dados
) {

    const resposta =
        await fetch(
            RELATORIOS_FINANCEIROS_URL,
            {
                method: 'POST',

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
            || 'Não foi possível carregar o relatório financeiro.'
        );
    }


    return resultado;
}


/*
|--------------------------------------------------------------------------
| CARREGAR RELATÓRIO
|--------------------------------------------------------------------------
*/

async function carregarRelatorioFinanceiro() {

    esconderErroRelatorioFinanceiro();

    mostrarCarregamentoRelatorioFinanceiro();


    try {

        const resultado =
            await enviarRelatorioFinanceiroAjax({

                acao:
                    'relatorio',

                tipo:
                    tipoRelatorioFinanceiroAtual,

                data_inicio:
                    obterValorRelatorioFinanceiro(
                        'relatorioFinanceiroDataInicio'
                    ),

                data_fim:
                    obterValorRelatorioFinanceiro(
                        'relatorioFinanceiroDataFim'
                    ),

                filial_id:
                    obterValorRelatorioFinanceiro(
                        'relatorioFinanceiroFilial'
                    ),

                banco_id:
                    obterValorRelatorioFinanceiro(
                        'relatorioFinanceiroBanco'
                    ),

                centro_custo_id:
                    obterValorRelatorioFinanceiro(
                        'relatorioFinanceiroCentroCusto'
                    ),

                estado:
                    obterValorRelatorioFinanceiro(
                        'relatorioFinanceiroEstado'
                    ),

                tipo_movimento:
                    obterValorRelatorioFinanceiro(
                        'relatorioFinanceiroTipoMovimento'
                    ),

                pesquisa:
                    obterValorRelatorioFinanceiro(
                        'relatorioFinanceiroPesquisa'
                    )
            });


        const dados =
            resultado.data || {};


        /*
        |--------------------------------------------------------------------------
        | IMPORTANTE: atualizar cards
        |--------------------------------------------------------------------------
        */

        renderizarResumoRelatorioFinanceiro(
            dados.resumo || {}
        );


        /*
        |--------------------------------------------------------------------------
        | Tabela
        |--------------------------------------------------------------------------
        */

        renderizarTabelaRelatorioFinanceiro(
            tipoRelatorioFinanceiroAtual,
            dados.registos || []
        );


    } catch (erro) {

        mostrarErroRelatorioFinanceiro(
            erro.message
        );


        zerarResumoRelatorioFinanceiro();


        renderizarTabelaRelatorioFinanceiro(
            tipoRelatorioFinanceiroAtual,
            []
        );
    }
}

/*
|--------------------------------------------------------------------------
| RENDERIZAR RESUMO
|--------------------------------------------------------------------------
*/

function renderizarResumoRelatorioFinanceiro(
    resumo
) {

    const tipo =
        tipoRelatorioFinanceiroAtual;


    /*
    |--------------------------------------------------------------------------
    | FLUXO
    |--------------------------------------------------------------------------
    */

    if (
        tipo === 'FLUXO_CAIXA'
    ) {

        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor1',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_entradas
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor2',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_saidas
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor3',
            formatarMoedaRelatorioFinanceiro(
                resumo.saldo_periodo
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor4',
            resumo.total_movimentos
            || 0
        );


        return;
    }


    /*
    |--------------------------------------------------------------------------
    | RECEITAS / DESPESAS
    |--------------------------------------------------------------------------
    */

    if (
        tipo === 'RECEITAS_DESPESAS'
    ) {

        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor1',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_receitas
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor2',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_despesas
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor3',
            formatarMoedaRelatorioFinanceiro(
                resumo.resultado
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor4',
            formatarPercentagemRelatorioFinanceiro(
                resumo.margem
            )
        );


        return;
    }


    /*
    |--------------------------------------------------------------------------
    | RECEBER
    |--------------------------------------------------------------------------
    */

    if (
        tipo === 'CONTAS_RECEBER'
    ) {

        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor1',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_receber
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor2',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_recebido
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor3',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_pendente
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor4',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_vencido
            )
        );


        return;
    }


    /*
    |--------------------------------------------------------------------------
    | PAGAR
    |--------------------------------------------------------------------------
    */

    if (
        tipo === 'CONTAS_PAGAR'
    ) {

        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor1',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_pagar
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor2',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_pago
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor3',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_pendente
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor4',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_vencido
            )
        );


        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CENTROS
    |--------------------------------------------------------------------------
    */

    if (
        tipo === 'CENTROS_CUSTO'
    ) {

        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor1',
            resumo.total_centros
            || 0
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor2',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_receitas
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor3',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_despesas
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor4',
            formatarMoedaRelatorioFinanceiro(
                resumo.resultado
            )
        );


        return;
    }


    /*
    |--------------------------------------------------------------------------
    | BANCÁRIO
    |--------------------------------------------------------------------------
    */

    if (
        tipo === 'MOVIMENTACAO_BANCARIA'
    ) {

        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor1',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_entradas
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor2',
            formatarMoedaRelatorioFinanceiro(
                resumo.total_saidas
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor3',
            formatarMoedaRelatorioFinanceiro(
                resumo.saldo_liquido
            )
        );


        definirTextoRelatorioFinanceiro(
            'relatorioFinanceiroResumoValor4',
            resumo.total_movimentos
            || 0
        );
    }
}


/*
|--------------------------------------------------------------------------
| TABELA
|--------------------------------------------------------------------------
*/

function renderizarTabelaRelatorioFinanceiro(
    tipo,
    registos
) {

    const head =
        document.getElementById(
            'relatorioFinanceiroTableHead'
        );


    const body =
        document.getElementById(
            'relatorioFinanceiroTableBody'
        );


    if (
        !head
        || !body
    ) {
        return;
    }


    const colunas =
        obterColunasRelatorioFinanceiro(
            tipo
        );


    /*
    |--------------------------------------------------------------------------
    | Head
    |--------------------------------------------------------------------------
    */

    head.innerHTML = `
        <tr>

            ${colunas
            .map(
                coluna => `
                            <th>
                                ${escaparHtmlRelatorioFinanceiro(
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


    /*
    |--------------------------------------------------------------------------
    | Quantidade
    |--------------------------------------------------------------------------
    */

    definirTextoRelatorioFinanceiro(
        'relatorioFinanceiroQuantidade',
        `${registos.length} registos`
    );


    /*
    |--------------------------------------------------------------------------
    | Vazio
    |--------------------------------------------------------------------------
    */

    if (
        !Array.isArray(
            registos
        )
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


    /*
    |--------------------------------------------------------------------------
    | Linhas
    |--------------------------------------------------------------------------
    */

    body.innerHTML =
        registos
            .map(
                registo => `

                    <tr>

                        ${colunas
                        .map(
                            coluna => `

                                        <td>
                                            ${formatarValorRelatorioFinanceiro(
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

function obterColunasRelatorioFinanceiro(
    tipo
) {

    const configuracoes = {


        /*
        |--------------------------------------------------------------------------
        | FLUXO
        |--------------------------------------------------------------------------
        */

        FLUXO_CAIXA: [

            {
                campo:
                    'data_movimento',

                titulo:
                    'Data',

                tipo:
                    'data_hora'
            },

            {
                campo:
                    'origem_financeira',

                titulo:
                    'Fonte'
            },

            {
                campo:
                    'conta',

                titulo:
                    'Conta'
            },

            {
                campo:
                    'tipo',

                titulo:
                    'Tipo',

                tipo:
                    'tipo_movimento'
            },

            {
                campo:
                    'origem',

                titulo:
                    'Origem'
            },

            {
                campo:
                    'descricao',

                titulo:
                    'Descrição'
            },

            {
                campo:
                    'valor',

                titulo:
                    'Valor',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'filial',

                titulo:
                    'Filial'
            }
        ],


        /*
        |--------------------------------------------------------------------------
        | RECEITAS E DESPESAS
        |--------------------------------------------------------------------------
        */

        RECEITAS_DESPESAS: [

            {
                campo:
                    'data_movimento',

                titulo:
                    'Data',

                tipo:
                    'data_hora'
            },

            {
                campo:
                    'natureza',

                titulo:
                    'Natureza',

                tipo:
                    'natureza'
            },

            {
                campo:
                    'fonte',

                titulo:
                    'Fonte'
            },

            {
                campo:
                    'conta',

                titulo:
                    'Conta'
            },

            {
                campo:
                    'categoria',

                titulo:
                    'Categoria'
            },

            {
                campo:
                    'descricao',

                titulo:
                    'Descrição'
            },

            {
                campo:
                    'centro_custo',

                titulo:
                    'Centro de custo'
            },

            {
                campo:
                    'valor',

                titulo:
                    'Valor',

                tipo:
                    'moeda'
            }
        ],


        /*
        |--------------------------------------------------------------------------
        | CONTAS A RECEBER
        |--------------------------------------------------------------------------
        */

        CONTAS_RECEBER: [

            {
                campo:
                    'numero_documento',

                titulo:
                    'Documento'
            },

            {
                campo:
                    'cliente',

                titulo:
                    'Cliente'
            },

            {
                campo:
                    'data_emissao',

                titulo:
                    'Emissão',

                tipo:
                    'data'
            },

            {
                campo:
                    'data_vencimento',

                titulo:
                    'Vencimento',

                tipo:
                    'data'
            },

            {
                campo:
                    'valor',

                titulo:
                    'Valor',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'valor_recebido',

                titulo:
                    'Recebido',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'saldo',

                titulo:
                    'Saldo',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'estado',

                titulo:
                    'Estado',

                tipo:
                    'estado'
            },

            {
                campo:
                    'vencido',

                titulo:
                    'Situação',

                tipo:
                    'vencido'
            }
        ],


        /*
        |--------------------------------------------------------------------------
        | CONTAS A PAGAR
        |--------------------------------------------------------------------------
        */

        CONTAS_PAGAR: [

            {
                campo:
                    'numero_documento',

                titulo:
                    'Documento'
            },

            {
                campo:
                    'fornecedor',

                titulo:
                    'Fornecedor'
            },

            {
                campo:
                    'data_emissao',

                titulo:
                    'Emissão',

                tipo:
                    'data'
            },

            {
                campo:
                    'data_vencimento',

                titulo:
                    'Vencimento',

                tipo:
                    'data'
            },

            {
                campo:
                    'valor',

                titulo:
                    'Valor',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'valor_pago',

                titulo:
                    'Pago',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'saldo',

                titulo:
                    'Saldo',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'estado',

                titulo:
                    'Estado',

                tipo:
                    'estado'
            },

            {
                campo:
                    'vencido',

                titulo:
                    'Situação',

                tipo:
                    'vencido'
            }
        ],


        /*
        |--------------------------------------------------------------------------
        | CENTROS
        |--------------------------------------------------------------------------
        */

        CENTROS_CUSTO: [

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
                    'Centro de custo'
            },

            {
                campo:
                    'tipo',

                titulo:
                    'Tipo'
            },

            {
                campo:
                    'responsavel',

                titulo:
                    'Responsável'
            },

            {
                campo:
                    'orcamento_mensal',

                titulo:
                    'Orçamento',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'receitas',

                titulo:
                    'Receitas',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'despesas',

                titulo:
                    'Despesas',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'resultado',

                titulo:
                    'Resultado',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'percentual_orcamento',

                titulo:
                    'Orçamento usado',

                tipo:
                    'percentagem'
            }
        ],


        /*
        |--------------------------------------------------------------------------
        | MOVIMENTAÇÃO BANCÁRIA
        |--------------------------------------------------------------------------
        */

        MOVIMENTACAO_BANCARIA: [

            {
                campo:
                    'data_movimento',

                titulo:
                    'Data',

                tipo:
                    'data_hora'
            },

            {
                campo:
                    'nome_banco',

                titulo:
                    'Banco'
            },

            {
                campo:
                    'conta',

                titulo:
                    'Conta'
            },

            {
                campo:
                    'tipo',

                titulo:
                    'Tipo',

                tipo:
                    'tipo_movimento'
            },

            {
                campo:
                    'categoria',

                titulo:
                    'Categoria'
            },

            {
                campo:
                    'descricao',

                titulo:
                    'Descrição'
            },

            {
                campo:
                    'valor',

                titulo:
                    'Valor',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'saldo_anterior',

                titulo:
                    'Saldo anterior',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'saldo_posterior',

                titulo:
                    'Saldo posterior',

                tipo:
                    'moeda'
            },

            {
                campo:
                    'conciliado',

                titulo:
                    'Conciliação',

                tipo:
                    'conciliado'
            }
        ]
    };


    return configuracoes[tipo]
        || [];
}


/*
|--------------------------------------------------------------------------
| FORMATAR CÉLULA
|--------------------------------------------------------------------------
*/

function formatarValorRelatorioFinanceiro(
    registo,
    coluna
) {

    const valor =
        registo[coluna.campo];


    switch (
    coluna.tipo
    ) {

        case 'moeda':

            return escaparHtmlRelatorioFinanceiro(
                formatarMoedaRelatorioFinanceiro(
                    valor
                )
            );


        case 'percentagem':

            return escaparHtmlRelatorioFinanceiro(
                formatarPercentagemRelatorioFinanceiro(
                    valor
                )
            );


        case 'data':

            return escaparHtmlRelatorioFinanceiro(
                formatarDataRelatorioFinanceiro(
                    valor
                )
            );


        case 'data_hora':

            return escaparHtmlRelatorioFinanceiro(
                formatarDataHoraRelatorioFinanceiro(
                    valor
                )
            );


        case 'estado':

            return criarBadgeEstadoRelatorioFinanceiro(
                valor
            );


        case 'natureza':

            return criarBadgeNaturezaRelatorioFinanceiro(
                valor
            );


        case 'tipo_movimento':

            return criarBadgeMovimentoRelatorioFinanceiro(
                valor
            );


        case 'vencido':

            return criarBadgeVencimentoRelatorioFinanceiro(
                valor
            );


        case 'conciliado':

            return criarBadgeConciliacaoRelatorioFinanceiro(
                valor
            );


        default:

            return escaparHtmlRelatorioFinanceiro(
                valor ?? '-'
            );
    }
}


/*
|--------------------------------------------------------------------------
| FILIAIS
|--------------------------------------------------------------------------
*/

async function carregarFiliaisRelatorioFinanceiro() {

    const select =
        document.getElementById(
            'relatorioFinanceiroFilial'
        );


    if (!select) {
        return;
    }


    try {

        const resultado =
            await enviarRelatorioFinanceiroAjax({
                acao:
                    'filiais'
            });


        const registos =
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


        registos.forEach(
            function (filial) {

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
| BANCOS
|--------------------------------------------------------------------------
*/

async function carregarBancosRelatorioFinanceiro() {

    const select =
        document.getElementById(
            'relatorioFinanceiroBanco'
        );


    if (!select) {
        return;
    }


    try {

        const resultado =
            await enviarRelatorioFinanceiroAjax({
                acao:
                    'bancos'
            });


        const registos =
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


        registos.forEach(
            function (banco) {

                const option =
                    document.createElement(
                        'option'
                    );


                option.value =
                    banco.id;


                option.textContent =
                    `${banco.nome_banco} - ${banco.conta}`;


                select.appendChild(
                    option
                );
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
| CENTROS
|--------------------------------------------------------------------------
*/

async function carregarCentrosCustoRelatorioFinanceiro() {

    const select =
        document.getElementById(
            'relatorioFinanceiroCentroCusto'
        );


    if (!select) {
        return;
    }


    try {

        const resultado =
            await enviarRelatorioFinanceiroAjax({
                acao:
                    'centros_custo'
            });


        const registos =
            Array.isArray(
                resultado.data
            )
                ? resultado.data
                : [];


        select.innerHTML = `
            <option value="">
                Todos os centros
            </option>
        `;


        registos.forEach(
            function (centro) {

                const option =
                    document.createElement(
                        'option'
                    );


                option.value =
                    centro.id;


                option.textContent =
                    `${centro.codigo} - ${centro.nome}`;


                select.appendChild(
                    option
                );
            }
        );


    } catch (erro) {

        console.error(
            'Erro ao carregar centros de custo:',
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| ESTADOS
|--------------------------------------------------------------------------
*/

async function carregarEstadosRelatorioFinanceiro() {

    const select =
        document.getElementById(
            'relatorioFinanceiroEstado'
        );


    if (!select) {
        return;
    }


    select.innerHTML = `
        <option value="">
            Todos
        </option>
    `;


    if (
        tipoRelatorioFinanceiroAtual
        !== 'CONTAS_RECEBER'
        && tipoRelatorioFinanceiroAtual
        !== 'CONTAS_PAGAR'
    ) {

        return;
    }


    try {

        const resultado =
            await enviarRelatorioFinanceiroAjax({

                acao:
                    'estados',

                tipo:
                    tipoRelatorioFinanceiroAtual
            });


        const estados =
            Array.isArray(
                resultado.data
            )
                ? resultado.data
                : [];


        estados.forEach(
            function (estado) {

                const option =
                    document.createElement(
                        'option'
                    );


                option.value =
                    estado.nome;


                option.textContent =
                    estado.nome;


                select.appendChild(
                    option
                );
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
| LIMPAR
|--------------------------------------------------------------------------
*/

function limparFiltrosRelatorioFinanceiro() {

    const campos = [

        'relatorioFinanceiroDataInicio',

        'relatorioFinanceiroDataFim',

        'relatorioFinanceiroFilial',

        'relatorioFinanceiroBanco',

        'relatorioFinanceiroCentroCusto',

        'relatorioFinanceiroEstado',

        'relatorioFinanceiroTipoMovimento',

        'relatorioFinanceiroPesquisa'
    ];


    campos.forEach(
        function (id) {

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


function limparFiltrosEspecificosRelatorioFinanceiro() {

    const campos = [

        'relatorioFinanceiroBanco',

        'relatorioFinanceiroCentroCusto',

        'relatorioFinanceiroEstado',

        'relatorioFinanceiroTipoMovimento'
    ];


    campos.forEach(
        function (id) {

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
| GRUPOS
|--------------------------------------------------------------------------
*/

function mostrarGrupoRelatorioFinanceiro(
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
| CARREGAMENTO
|--------------------------------------------------------------------------
*/

function mostrarCarregamentoRelatorioFinanceiro() {

    const body =
        document.getElementById(
            'relatorioFinanceiroTableBody'
        );


    if (!body) {
        return;
    }


    body.innerHTML = `
        <tr>

            <td
                class="text-center text-muted">

                <i class="fa fa-spinner fa-spin"></i>

                A carregar relatório...

            </td>

        </tr>
    `;
}


/*
|--------------------------------------------------------------------------
| ERRO
|--------------------------------------------------------------------------
*/

function mostrarErroRelatorioFinanceiro(
    mensagem
) {

    const elemento =
        document.getElementById(
            'relatorioFinanceiroErro'
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


function esconderErroRelatorioFinanceiro() {

    const elemento =
        document.getElementById(
            'relatorioFinanceiroErro'
        );


    if (!elemento) {
        return;
    }


    elemento.textContent = '';


    elemento.classList.add(
        'd-none'
    );
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function obterValorRelatorioFinanceiro(
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


function definirTextoRelatorioFinanceiro(
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


/*
|--------------------------------------------------------------------------
| MOEDA
|--------------------------------------------------------------------------
*/

function formatarMoedaRelatorioFinanceiro(
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
| PERCENTAGEM
|--------------------------------------------------------------------------
*/

function formatarPercentagemRelatorioFinanceiro(
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

function formatarDataRelatorioFinanceiro(
    valor
) {

    if (!valor) {
        return '-';
    }


    const texto =
        String(valor);


    const partes =
        texto
            .substring(
                0,
                10
            )
            .split('-');


    if (
        partes.length !== 3
    ) {
        return texto;
    }


    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}


/*
|--------------------------------------------------------------------------
| DATA/HORA
|--------------------------------------------------------------------------
*/

function formatarDataHoraRelatorioFinanceiro(
    valor
) {

    if (!valor) {
        return '-';
    }


    const data =
        new Date(
            String(valor)
                .replace(
                    ' ',
                    'T'
                )
        );


    if (
        Number.isNaN(
            data.getTime()
        )
    ) {
        return valor;
    }


    return data.toLocaleString(
        'pt-PT'
    );
}


/*
|--------------------------------------------------------------------------
| BADGE ESTADO
|--------------------------------------------------------------------------
*/

function criarBadgeEstadoRelatorioFinanceiro(
    estado
) {

    const texto =
        String(
            estado || '-'
        );


    const normalizado =
        texto.toLowerCase();


    let classe =
        'badge-secondary';


    if (
        normalizado.includes(
            'pago'
        )
        || normalizado.includes(
            'recebido'
        )
        || normalizado.includes(
            'liquid'
        )
        || normalizado.includes(
            'activo'
        )
    ) {

        classe =
            'badge-success';

    } else if (
        normalizado.includes(
            'pendent'
        )
        || normalizado.includes(
            'parcial'
        )
    ) {

        classe =
            'badge-warning';

    } else if (
        normalizado.includes(
            'cancel'
        )
        || normalizado.includes(
            'vencid'
        )
    ) {

        classe =
            'badge-danger';
    }


    return `
        <span class="badge ${classe}">
            ${escaparHtmlRelatorioFinanceiro(
        texto
    )
        }
        </span>
    `;
}


/*
|--------------------------------------------------------------------------
| BADGE NATUREZA
|--------------------------------------------------------------------------
*/

function criarBadgeNaturezaRelatorioFinanceiro(
    natureza
) {

    const texto =
        String(
            natureza || '-'
        );


    const classe =
        texto === 'RECEITA'
            ? 'badge-success'
            : texto === 'DESPESA'
                ? 'badge-danger'
                : 'badge-secondary';


    return `
        <span class="badge ${classe}">
            ${escaparHtmlRelatorioFinanceiro(
        texto
    )
        }
        </span>
    `;
}


/*
|--------------------------------------------------------------------------
| BADGE MOVIMENTO
|--------------------------------------------------------------------------
*/

function criarBadgeMovimentoRelatorioFinanceiro(
    tipo
) {

    const texto =
        String(
            tipo || '-'
        );


    const classe =
        texto === 'ENTRADA'
            ? 'badge-success'
            : texto === 'SAIDA'
                ? 'badge-danger'
                : 'badge-secondary';


    return `
        <span class="badge ${classe}">
            ${escaparHtmlRelatorioFinanceiro(
        texto
    )
        }
        </span>
    `;
}


/*
|--------------------------------------------------------------------------
| BADGE VENCIMENTO
|--------------------------------------------------------------------------
*/

function criarBadgeVencimentoRelatorioFinanceiro(
    valor
) {

    const vencido =
        Number(valor) === 1;


    return vencido
        ? `
            <span class="badge badge-danger">
                Vencido
            </span>
        `
        : `
            <span class="badge badge-success">
                Regular
            </span>
        `;
}


/*
|--------------------------------------------------------------------------
| BADGE CONCILIAÇÃO
|--------------------------------------------------------------------------
*/

function criarBadgeConciliacaoRelatorioFinanceiro(
    valor
) {

    const conciliado =
        Number(valor) === 1;


    return conciliado
        ? `
            <span class="badge badge-success">
                Conciliado
            </span>
        `
        : `
            <span class="badge badge-warning">
                Não conciliado
            </span>
        `;
}


/*
|--------------------------------------------------------------------------
| IMPRIMIR
|--------------------------------------------------------------------------
*/

function imprimirRelatorioFinanceiro() {

    const titulo =
        obterTituloRelatorioFinanceiro(
            tipoRelatorioFinanceiroAtual
        );


    const tabela =
        document.getElementById(
            'relatorioFinanceiroTable'
        );


    const resumo =
        document.getElementById(
            'relatorioFinanceiroResumo'
        );


    if (!tabela) {
        return;
    }


    const janela =
        window.open(
            '',
            '_blank',
            'width=1200,height=800'
        );


    if (!janela) {
        return;
    }


    janela.document.write(`
        <!DOCTYPE html>

        <html lang="pt">

        <head>

            <meta charset="UTF-8">

            <title>
                ${escaparHtmlRelatorioFinanceiro(
        titulo
    )
        }
            </title>

            <style>

                body {
                    font-family:
                        Arial,
                        sans-serif;

                    padding: 25px;

                    color: #222;
                }

                h1 {
                    font-size: 21px;
                }

                .resumo {
                    display: grid;

                    grid-template-columns:
                        repeat(4, 1fr);

                    gap: 10px;

                    margin:
                        20px 0;
                }

                .resumo .card {
                    border:
                        1px solid #ddd;

                    padding: 10px;
                }

                .resumo .card-body {
                    padding: 0;
                }

                table {
                    width: 100%;

                    border-collapse:
                        collapse;
                }

                th,
                td {
                    border:
                        1px solid #ccc;

                    padding: 6px;

                    font-size: 10px;
                }

                th {
                    background:
                        #f1f1f1;
                }

                .badge {
                    border:
                        1px solid #aaa;

                    padding:
                        2px 5px;
                }

                @page {
                    size:
                        A4 landscape;

                    margin:
                        10mm;
                }

            </style>

        </head>

        <body>

            <h1>
                ${escaparHtmlRelatorioFinanceiro(
            titulo
        )
        }
            </h1>

            <p>
                Gerado em:
                ${new Date()
            .toLocaleString(
                'pt-PT'
            )
        }
            </p>

            <div class="resumo">

                ${resumo
            ? resumo.innerHTML
            : ''
        }

            </div>

            ${tabela.outerHTML
        }

        </body>

        </html>
    `);


    janela.document.close();


    janela.onload =
        function () {

            janela.focus();

            janela.print();


            janela.onafterprint =
                function () {

                    janela.close();
                };
        };
}


/*
|--------------------------------------------------------------------------
| EXPORTAR
|--------------------------------------------------------------------------
*/

function exportarRelatorioFinanceiro() {

    const parametros =
        new URLSearchParams({

            tipo:
                tipoRelatorioFinanceiroAtual,

            data_inicio:
                obterValorRelatorioFinanceiro(
                    'relatorioFinanceiroDataInicio'
                ),

            data_fim:
                obterValorRelatorioFinanceiro(
                    'relatorioFinanceiroDataFim'
                ),

            filial_id:
                obterValorRelatorioFinanceiro(
                    'relatorioFinanceiroFilial'
                ),

            banco_id:
                obterValorRelatorioFinanceiro(
                    'relatorioFinanceiroBanco'
                ),

            centro_custo_id:
                obterValorRelatorioFinanceiro(
                    'relatorioFinanceiroCentroCusto'
                ),

            estado:
                obterValorRelatorioFinanceiro(
                    'relatorioFinanceiroEstado'
                ),

            tipo_movimento:
                obterValorRelatorioFinanceiro(
                    'relatorioFinanceiroTipoMovimento'
                ),

            pesquisa:
                obterValorRelatorioFinanceiro(
                    'relatorioFinanceiroPesquisa'
                )
        });


    window.open(
        '/erpplus/app/ajax/'
        + 'relatorios_financeiros_exportar.php?'
        + parametros.toString(),

        '_blank'
    );
}

function zerarResumoRelatorioFinanceiro() {

    definirTextoRelatorioFinanceiro(
        'relatorioFinanceiroResumoValor1',
        '-'
    );

    definirTextoRelatorioFinanceiro(
        'relatorioFinanceiroResumoValor2',
        '-'
    );

    definirTextoRelatorioFinanceiro(
        'relatorioFinanceiroResumoValor3',
        '-'
    );

    definirTextoRelatorioFinanceiro(
        'relatorioFinanceiroResumoValor4',
        '-'
    );
}


/*
|--------------------------------------------------------------------------
| ESCAPAR HTML
|--------------------------------------------------------------------------
*/

function escaparHtmlRelatorioFinanceiro(
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