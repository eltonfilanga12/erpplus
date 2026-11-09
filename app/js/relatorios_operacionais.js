'use strict';

const RELATORIOS_OPERACIONAIS_URL =
    '/erpplus/app/ajax/relatorios_operacionais_ajax.php';

let tipoRelatorioOperacionalAtual =
    'VENDAS';


document.addEventListener(
    'DOMContentLoaded',
    function () {
        iniciarRelatoriosOperacionais();
    }
);


async function iniciarRelatoriosOperacionais() {

    configurarEventosRelatoriosOperacionais();

    await carregarFiliaisRelatorioOperacional();

    await carregarRelatorioOperacional();
}


/*
|--------------------------------------------------------------------------
| Eventos
|--------------------------------------------------------------------------
*/

function configurarEventosRelatoriosOperacionais() {

    document
        .querySelectorAll(
            '#relatorioOperacionalTabs '
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

                        await selecionarTipoRelatorioOperacional(
                            tipo
                        );
                    }
                );
            }
        );


    const selectMobile =
        document.getElementById(
            'tipoRelatorioOperacionalMobile'
        );

    if (selectMobile) {

        selectMobile.addEventListener(
            'change',
            async function () {

                await selecionarTipoRelatorioOperacional(
                    selectMobile.value
                );
            }
        );
    }


    const formulario =
        document.getElementById(
            'formFiltrosRelatorioOperacional'
        );

    if (formulario) {

        formulario.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();

                await carregarRelatorioOperacional();
            }
        );
    }


    const btnLimpar =
        document.getElementById(
            'btnLimparFiltrosRelatorioOperacional'
        );

    if (btnLimpar) {

        btnLimpar.addEventListener(
            'click',
            async function () {

                limparFiltrosRelatorioOperacional();

                await carregarRelatorioOperacional();
            }
        );
    }


    const btnImprimir =
        document.getElementById(
            'btnImprimirRelatorioOperacional'
        );

    if (btnImprimir) {

        btnImprimir.addEventListener(
            'click',
            imprimirRelatorioOperacional
        );
    }


    const btnExportar =
        document.getElementById(
            'btnExportarRelatorioOperacional'
        );

    if (btnExportar) {

        btnExportar.addEventListener(
            'click',
            exportarRelatorioOperacional
        );
    }
}


/*
|--------------------------------------------------------------------------
| Selecionar tipo
|--------------------------------------------------------------------------
*/

async function selecionarTipoRelatorioOperacional(
    tipo
) {

    tipo =
        String(tipo || '')
            .toUpperCase();

    const tiposPermitidos = [
        'VENDAS',
        'COMPRAS',
        'STOCK',
        'CLIENTES',
        'FORNECEDORES',
        'PRODUTOS'
    ];

    if (
        !tiposPermitidos.includes(tipo)
    ) {
        return;
    }


    tipoRelatorioOperacionalAtual =
        tipo;


    const campoTipo =
        document.getElementById(
            'tipoRelatorioOperacional'
        );

    if (campoTipo) {
        campoTipo.value = tipo;
    }


    const selectMobile =
        document.getElementById(
            'tipoRelatorioOperacionalMobile'
        );

    if (selectMobile) {
        selectMobile.value = tipo;
    }


    document
        .querySelectorAll(
            '#relatorioOperacionalTabs '
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


    configurarInterfacePorTipo(
        tipo
    );


    await carregarEstadosRelatorioOperacional();

    await carregarRelatorioOperacional();
}


/*
|--------------------------------------------------------------------------
| Interface por tipo
|--------------------------------------------------------------------------
*/

function configurarInterfacePorTipo(
    tipo
) {

    const titulo =
        document.getElementById(
            'relatorioOperacionalTitulo'
        );

    if (titulo) {

        titulo.textContent =
            obterTituloRelatorioOperacional(
                tipo
            );
    }

    const labelEstado =
        document.getElementById(
            'relatorioOperacionalEstadoLabel'
        );

    if (labelEstado) {

        labelEstado.textContent =
            tipo === 'STOCK'
                ? 'Situação'
                : 'Estado';
    }


    const configuracao =
        obterConfiguracaoResumo(
            tipo
        );


    definirTextoRelatorioOperacional(
        'relatorioOperacionalResumoTitulo1',
        configuracao[0]
    );

    definirTextoRelatorioOperacional(
        'relatorioOperacionalResumoTitulo2',
        configuracao[1]
    );

    definirTextoRelatorioOperacional(
        'relatorioOperacionalResumoTitulo3',
        configuracao[2]
    );

    definirTextoRelatorioOperacional(
        'relatorioOperacionalResumoTitulo4',
        configuracao[3]
    );
}


function obterTituloRelatorioOperacional(
    tipo
) {

    const titulos = {
        VENDAS:
            'Relatório de Vendas',

        COMPRAS:
            'Relatório de Compras',

        STOCK:
            'Relatório de Stock',

        CLIENTES:
            'Relatório de Clientes',

        FORNECEDORES:
            'Relatório de Fornecedores',

        PRODUTOS:
            'Relatório de Produtos'
    };

    return titulos[tipo]
        || 'Relatório Operacional';
}


function obterConfiguracaoResumo(
    tipo
) {

    const configuracoes = {

        VENDAS: [
            'Total de vendas',
            'Valor vendido',
            'Descontos',
            'Ticket médio'
        ],

        COMPRAS: [
            'Total de compras',
            'Valor comprado',
            'Fornecedores',
            'Valor pendente'
        ],

        STOCK: [
            'Produtos',
            'Quantidade em stock',
            'Valor do stock',
            'Abaixo do mínimo'
        ],

        CLIENTES: [
            'Clientes',
            'Clientes activos',
            'Valor comprado',
            'Saldo a receber'
        ],

        FORNECEDORES: [
            'Fornecedores',
            'Fornecedores activos',
            'Valor comprado',
            'Saldo a pagar'
        ],

        PRODUTOS: [
            'Produtos',
            'Produtos activos',
            'Quantidade vendida',
            'Valor vendido'
        ]
    };

    return configuracoes[tipo]
        || [
            'Total',
            'Valor',
            'Indicador',
            'Resultado'
        ];
}


/*
|--------------------------------------------------------------------------
| Comunicação AJAX
|--------------------------------------------------------------------------
*/

async function enviarRelatorioOperacionalAjax(
    dados
) {

    const resposta =
        await fetch(
            RELATORIOS_OPERACIONAIS_URL,
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
            || 'Não foi possível carregar o relatório.'
        );
    }


    return resultado;
}


/*
|--------------------------------------------------------------------------
| Carregar relatório
|--------------------------------------------------------------------------
*/

async function carregarRelatorioOperacional() {

    esconderErroRelatorioOperacional();

    mostrarCarregamentoRelatorioOperacional();


    try {

        const resultado =
            await enviarRelatorioOperacionalAjax({
                acao: 'relatorio',

                tipo:
                    tipoRelatorioOperacionalAtual,

                data_inicio:
                    obterValorRelatorioOperacional(
                        'relatorioOperacionalDataInicio'
                    ),

                data_fim:
                    obterValorRelatorioOperacional(
                        'relatorioOperacionalDataFim'
                    ),

                filial_id:
                    obterValorRelatorioOperacional(
                        'relatorioOperacionalFilial'
                    ),

                estado:
                    obterValorRelatorioOperacional(
                        'relatorioOperacionalEstado'
                    ),

                pesquisa:
                    obterValorRelatorioOperacional(
                        'relatorioOperacionalPesquisa'
                    )
            });


        const dados =
            resultado.data || {};


        renderizarResumoRelatorioOperacional(
            dados.resumo || {}
        );


        renderizarTabelaRelatorioOperacional(
            tipoRelatorioOperacionalAtual,
            dados.registos || []
        );

    } catch (erro) {

        mostrarErroRelatorioOperacional(
            erro.message
        );

        renderizarTabelaRelatorioOperacional(
            tipoRelatorioOperacionalAtual,
            []
        );
    }
}


/*
|--------------------------------------------------------------------------
| Resumo
|--------------------------------------------------------------------------
*/

function renderizarResumoRelatorioOperacional(
    resumo
) {

    const tipo =
        tipoRelatorioOperacionalAtual;


    if (tipo === 'VENDAS') {

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor1',
            resumo.total_vendas || 0
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor2',
            formatarMoedaRelatorioOperacional(
                resumo.valor_total
            )
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor3',
            formatarMoedaRelatorioOperacional(
                resumo.total_descontos
            )
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor4',
            formatarMoedaRelatorioOperacional(
                resumo.ticket_medio
            )
        );

        return;
    }


    if (tipo === 'COMPRAS') {

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor1',
            resumo.total_compras || 0
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor2',
            formatarMoedaRelatorioOperacional(
                resumo.valor_total
            )
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor3',
            resumo.total_fornecedores || 0
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor4',
            formatarMoedaRelatorioOperacional(
                resumo.valor_pendente
            )
        );

        return;
    }


    if (tipo === 'STOCK') {

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor1',
            resumo.total_produtos || 0
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor2',
            formatarNumeroRelatorioOperacional(
                resumo.quantidade_stock
            )
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor3',
            formatarMoedaRelatorioOperacional(
                resumo.valor_stock
            )
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor4',
            resumo.abaixo_minimo || 0
        );

        return;
    }


    if (tipo === 'CLIENTES') {

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor1',
            resumo.total_clientes || 0
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor2',
            resumo.clientes_activos || 0
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor3',
            formatarMoedaRelatorioOperacional(
                resumo.valor_comprado
            )
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor4',
            formatarMoedaRelatorioOperacional(
                resumo.saldo_receber
            )
        );

        return;
    }


    if (tipo === 'FORNECEDORES') {

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor1',
            resumo.total_fornecedores || 0
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor2',
            resumo.fornecedores_activos || 0
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor3',
            formatarMoedaRelatorioOperacional(
                resumo.valor_comprado
            )
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor4',
            formatarMoedaRelatorioOperacional(
                resumo.saldo_pagar
            )
        );

        return;
    }


    if (tipo === 'PRODUTOS') {

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor1',
            resumo.total_produtos || 0
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor2',
            resumo.produtos_activos || 0
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor3',
            formatarNumeroRelatorioOperacional(
                resumo.quantidade_vendida
            )
        );

        definirTextoRelatorioOperacional(
            'relatorioOperacionalResumoValor4',
            formatarMoedaRelatorioOperacional(
                resumo.valor_vendido
            )
        );
    }
}


/*
|--------------------------------------------------------------------------
| Tabela dinâmica
|--------------------------------------------------------------------------
*/

function renderizarTabelaRelatorioOperacional(
    tipo,
    registos
) {

    const head =
        document.getElementById(
            'relatorioOperacionalTableHead'
        );

    const body =
        document.getElementById(
            'relatorioOperacionalTableBody'
        );

    if (!head || !body) {
        return;
    }


    const colunas =
        obterColunasRelatorioOperacional(
            tipo
        );


    head.innerHTML = `
        <tr>
            ${colunas.map(
        coluna => `
                    <th>
                        ${escaparHtmlRelatorioOperacional(
            coluna.titulo
        )}
                    </th>
                `
    ).join('')}
        </tr>
    `;


    definirTextoRelatorioOperacional(
        'relatorioOperacionalQuantidade',
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
                    class="text-center text-muted"
                >
                    Nenhum registo encontrado.
                </td>
            </tr>
        `;

        return;
    }


    body.innerHTML =
        registos.map(
            registo => `

                <tr>

                    ${colunas.map(
                coluna => `

                            <td>
                                ${formatarValorColunaRelatorioOperacional(
                    registo,
                    coluna
                )}
                            </td>

                        `
            ).join('')}

                </tr>

            `
        ).join('');
}


/*
|--------------------------------------------------------------------------
| Definição das colunas
|--------------------------------------------------------------------------
*/

function obterColunasRelatorioOperacional(
    tipo
) {

    const configuracoes = {

        VENDAS: [
            {
                campo: 'data',
                titulo: 'Data',
                tipo: 'data'
            },
            {
                campo: 'documento',
                titulo: 'Documento'
            },
            {
                campo: 'cliente',
                titulo: 'Cliente'
            },
            {
                campo: 'filial',
                titulo: 'Filial'
            },
            {
                campo: 'subtotal',
                titulo: 'Subtotal',
                tipo: 'moeda'
            },
            {
                campo: 'desconto',
                titulo: 'Desconto',
                tipo: 'moeda'
            },
            {
                campo: 'imposto',
                titulo: 'Imposto',
                tipo: 'moeda'
            },
            {
                campo: 'total',
                titulo: 'Total',
                tipo: 'moeda'
            },
            {
                campo: 'estado',
                titulo: 'Estado',
                tipo: 'estado'
            }
        ],


        COMPRAS: [
            {
                campo: 'data',
                titulo: 'Data',
                tipo: 'data'
            },
            {
                campo: 'documento',
                titulo: 'Documento'
            },
            {
                campo: 'fornecedor',
                titulo: 'Fornecedor'
            },
            {
                campo: 'filial',
                titulo: 'Filial'
            },
            {
                campo: 'subtotal',
                titulo: 'Subtotal',
                tipo: 'moeda'
            },
            {
                campo: 'imposto',
                titulo: 'Imposto',
                tipo: 'moeda'
            },
            {
                campo: 'total',
                titulo: 'Total',
                tipo: 'moeda'
            },
            {
                campo: 'estado',
                titulo: 'Estado',
                tipo: 'estado'
            }
        ],


        STOCK: [
            {
                campo: 'codigo',
                titulo: 'Código'
            },
            {
                campo: 'produto',
                titulo: 'Produto'
            },
            {
                campo: 'armazem',
                titulo: 'Armazém'
            },
            {
                campo: 'filial',
                titulo: 'Filial'
            },
            {
                campo: 'quantidade',
                titulo: 'Quantidade',
                tipo: 'numero'
            },
            {
                campo: 'stock_minimo',
                titulo: 'Mínimo',
                tipo: 'numero'
            },
            {
                campo: 'custo_medio',
                titulo: 'Custo médio',
                tipo: 'moeda'
            },
            {
                campo: 'valor_stock',
                titulo: 'Valor stock',
                tipo: 'moeda'
            },
            {
                campo: 'situacao',
                titulo: 'Situação',
                tipo: 'estado'
            }
        ],


        CLIENTES: [
            {
                campo: 'cliente',
                titulo: 'Cliente'
            },
            {
                campo: 'nuit',
                titulo: 'NUIT'
            },
            {
                campo: 'contacto',
                titulo: 'Contacto'
            },
            {
                campo: 'filial',
                titulo: 'Filial'
            },
            {
                campo: 'total_compras',
                titulo: 'Compras',
                tipo: 'numero'
            },
            {
                campo: 'valor_comprado',
                titulo: 'Valor comprado',
                tipo: 'moeda'
            },
            {
                campo: 'saldo',
                titulo: 'Saldo',
                tipo: 'moeda'
            },
            {
                campo: 'ultima_compra',
                titulo: 'Última compra',
                tipo: 'data'
            }
        ],


        FORNECEDORES: [
            {
                campo: 'fornecedor',
                titulo: 'Fornecedor'
            },
            {
                campo: 'nuit',
                titulo: 'NUIT'
            },
            {
                campo: 'contacto',
                titulo: 'Contacto'
            },
            {
                campo: 'filial',
                titulo: 'Filial'
            },
            {
                campo: 'total_compras',
                titulo: 'Compras',
                tipo: 'numero'
            },
            {
                campo: 'valor_comprado',
                titulo: 'Valor comprado',
                tipo: 'moeda'
            },
            {
                campo: 'saldo',
                titulo: 'Saldo',
                tipo: 'moeda'
            },
            {
                campo: 'ultima_compra',
                titulo: 'Última compra',
                tipo: 'data'
            }
        ],


        PRODUTOS: [
            {
                campo: 'codigo',
                titulo: 'Código'
            },
            {
                campo: 'produto',
                titulo: 'Produto'
            },
            {
                campo: 'categoria',
                titulo: 'Categoria'
            },
            {
                campo: 'filial',
                titulo: 'Filial'
            },
            {
                campo: 'quantidade_vendida',
                titulo: 'Qtd. vendida',
                tipo: 'numero'
            },
            {
                campo: 'valor_vendido',
                titulo: 'Valor vendido',
                tipo: 'moeda'
            },
            {
                campo: 'stock_actual',
                titulo: 'Stock actual',
                tipo: 'numero'
            },
            {
                campo: 'estado',
                titulo: 'Estado',
                tipo: 'estado'
            }
        ]
    };


    return configuracoes[tipo]
        || [];
}


/*
|--------------------------------------------------------------------------
| Formatar células
|--------------------------------------------------------------------------
*/

function formatarValorColunaRelatorioOperacional(
    registo,
    coluna
) {

    const valor =
        registo[coluna.campo];


    switch (coluna.tipo) {

        case 'moeda':

            return escaparHtmlRelatorioOperacional(
                formatarMoedaRelatorioOperacional(
                    valor
                )
            );


        case 'numero':

            return escaparHtmlRelatorioOperacional(
                formatarNumeroRelatorioOperacional(
                    valor
                )
            );


        case 'data':

            return escaparHtmlRelatorioOperacional(
                formatarDataRelatorioOperacional(
                    valor
                )
            );


        case 'estado':

            return criarBadgeRelatorioOperacional(
                valor
            );


        default:

            return escaparHtmlRelatorioOperacional(
                valor ?? '-'
            );
    }
}

/*
|--------------------------------------------------------------------------
| Filiais
|--------------------------------------------------------------------------
*/

async function carregarFiliaisRelatorioOperacional() {

    const select =
        document.getElementById(
            'relatorioOperacionalFilial'
        );

    if (!select) {
        return;
    }


    try {

        const resultado =
            await enviarRelatorioOperacionalAjax({
                acao: 'filiais'
            });


        const filiais =
            Array.isArray(resultado.data)
                ? resultado.data
                : [];


        select.innerHTML = `
            <option value="">
                Todas as filiais
            </option>
        `;


        filiais.forEach(
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

        select.innerHTML = `
            <option value="">
                Todas as filiais
            </option>
        `;
    }
}


/*
|--------------------------------------------------------------------------
| Limpar filtros
|--------------------------------------------------------------------------
*/

function limparFiltrosRelatorioOperacional() {

    const campos = [
        'relatorioOperacionalDataInicio',
        'relatorioOperacionalDataFim',
        'relatorioOperacionalFilial',
        'relatorioOperacionalEstado',
        'relatorioOperacionalPesquisa'
    ];


    campos.forEach(
        function (id) {

            const elemento =
                document.getElementById(id);

            if (elemento) {
                elemento.value = '';
            }
        }
    );
}


/*
|--------------------------------------------------------------------------
| Imprimir / Exportar
|--------------------------------------------------------------------------
*/

function imprimirRelatorioOperacional() {

    const titulo =
        obterTituloRelatorioOperacional(
            tipoRelatorioOperacionalAtual
        );


    const tabela =
        document.getElementById(
            'relatorioOperacionalTable'
        );


    const resumo =
        document.getElementById(
            'relatorioOperacionalResumo'
        );


    const quantidade =
        document.getElementById(
            'relatorioOperacionalQuantidade'
        );


    if (!tabela) {

        alert(
            'Não foi possível localizar o relatório.'
        );

        return;
    }


    const janela =
        window.open(
            '',
            '_blank',
            'width=1200,height=800'
        );


    if (!janela) {

        alert(
            'O navegador bloqueou a janela de impressão.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Filtros utilizados
    |--------------------------------------------------------------------------
    */

    const dataInicio =
        obterValorRelatorioOperacional(
            'relatorioOperacionalDataInicio'
        );


    const dataFim =
        obterValorRelatorioOperacional(
            'relatorioOperacionalDataFim'
        );


    const filialSelect =
        document.getElementById(
            'relatorioOperacionalFilial'
        );


    const filial =
        filialSelect
            && filialSelect.selectedIndex >= 0
            ? filialSelect.options[
                filialSelect.selectedIndex
            ].text
            : 'Todas as filiais';


    let periodo = 'Todos';


    if (
        dataInicio
        && dataFim
    ) {

        periodo =
            `${formatarDataRelatorioOperacional(
                dataInicio
            )} a ${formatarDataRelatorioOperacional(
                dataFim
            )
            }`;

    } else if (dataInicio) {

        periodo =
            `Desde ${formatarDataRelatorioOperacional(
                dataInicio
            )
            }`;

    } else if (dataFim) {

        periodo =
            `Até ${formatarDataRelatorioOperacional(
                dataFim
            )
            }`;
    }


    /*
    |--------------------------------------------------------------------------
    | HTML
    |--------------------------------------------------------------------------
    */

    janela.document.write(`
        <!DOCTYPE html>

        <html lang="pt">

        <head>

            <meta charset="UTF-8">

            <title>
                ${escaparHtmlRelatorioOperacional(
        titulo
    )}
            </title>

            <style>

                * {
                    box-sizing: border-box;
                }

                body {
                    font-family:
                        Arial,
                        Helvetica,
                        sans-serif;

                    margin: 0;
                    padding: 25px;

                    color: #222;
                    background: #fff;
                }

                .cabecalho {
                    border-bottom:
                        2px solid #333;

                    padding-bottom: 15px;
                    margin-bottom: 20px;
                }

                .cabecalho h1 {
                    margin: 0 0 5px;
                    font-size: 22px;
                }

                .cabecalho p {
                    margin: 3px 0;
                    font-size: 12px;
                    color: #555;
                }

                .resumo {
                    display: grid;
                    grid-template-columns:
                        repeat(4, 1fr);

                    gap: 10px;
                    margin-bottom: 25px;
                }

                .resumo .card {
                    border:
                        1px solid #ddd;

                    padding: 12px;
                }

                .resumo .card-body {
                    padding: 0;
                }

                .resumo span {
                    display: block;
                    font-size: 11px;
                    color: #666;
                }

                .resumo h3 {
                    margin:
                        5px 0 0;

                    font-size: 17px;
                }

                .titulo-tabela {
                    display: flex;
                    justify-content:
                        space-between;
                    align-items: center;

                    margin-bottom: 10px;
                }

                .titulo-tabela h2 {
                    margin: 0;
                    font-size: 17px;
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

                    padding: 7px;

                    font-size: 10px;

                    vertical-align: middle;
                }

                th {
                    background: #f0f0f0;
                    font-weight: bold;
                    text-align: left;
                }

                .badge {
                    padding:
                        3px 6px;

                    border:
                        1px solid #aaa;

                    border-radius: 3px;

                    font-size: 9px;
                }

                .rodape {
                    margin-top: 25px;

                    border-top:
                        1px solid #ddd;

                    padding-top: 8px;

                    text-align: right;

                    font-size: 9px;

                    color: #777;
                }

                @page {
                    size: A4 landscape;
                    margin: 10mm;
                }

            </style>

        </head>

        <body>

            <div class="cabecalho">

                <h1>
                    ${escaparHtmlRelatorioOperacional(
        titulo
    )}
                </h1>

                <p>
                    <strong>Período:</strong>
                    ${escaparHtmlRelatorioOperacional(
        periodo
    )}
                </p>

                <p>
                    <strong>Filial:</strong>
                    ${escaparHtmlRelatorioOperacional(
        filial
    )}
                </p>

            </div>


            <div class="resumo">

                ${resumo
            ? resumo.innerHTML
            : ''
        }

            </div>


            <div class="titulo-tabela">

                <h2>
                    ${escaparHtmlRelatorioOperacional(
            titulo
        )}
                </h2>

                <span>
                    ${quantidade
            ? quantidade.textContent
            : ''
        }
                </span>

            </div>


            ${tabela.outerHTML}


            <div class="rodape">

                ERP Plus |
                Documento gerado em
                ${new Date()
            .toLocaleString(
                'pt-PT'
            )
        }

            </div>

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

function exportarRelatorioOperacional() {

    const parametros =
        new URLSearchParams({

            tipo:
                tipoRelatorioOperacionalAtual,

            data_inicio:
                obterValorRelatorioOperacional(
                    'relatorioOperacionalDataInicio'
                ),

            data_fim:
                obterValorRelatorioOperacional(
                    'relatorioOperacionalDataFim'
                ),

            filial_id:
                obterValorRelatorioOperacional(
                    'relatorioOperacionalFilial'
                ),

            estado:
                obterValorRelatorioOperacional(
                    'relatorioOperacionalEstado'
                ),

            pesquisa:
                obterValorRelatorioOperacional(
                    'relatorioOperacionalPesquisa'
                )
        });


    window.open(
        '/erpplus/app/ajax/'
        + 'relatorios_operacionais_exportar.php?'
        + parametros.toString(),
        '_blank'
    );
}

/*
|--------------------------------------------------------------------------
| Estados visuais
|--------------------------------------------------------------------------
*/

function mostrarCarregamentoRelatorioOperacional() {

    const body =
        document.getElementById(
            'relatorioOperacionalTableBody'
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


function mostrarErroRelatorioOperacional(
    mensagem
) {

    const elemento =
        document.getElementById(
            'relatorioOperacionalErro'
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


function esconderErroRelatorioOperacional() {

    const elemento =
        document.getElementById(
            'relatorioOperacionalErro'
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
| Helpers
|--------------------------------------------------------------------------
*/

function obterValorRelatorioOperacional(
    id
) {

    const elemento =
        document.getElementById(id);

    return elemento
        ? String(
            elemento.value || ''
        ).trim()
        : '';
}


function definirTextoRelatorioOperacional(
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


function formatarMoedaRelatorioOperacional(
    valor,
    moeda = 'MZN'
) {

    return new Intl.NumberFormat(
        'pt-MZ',
        {
            style: 'currency',
            currency: moeda,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    ).format(
        Number(valor || 0)
    );
}


function formatarNumeroRelatorioOperacional(
    valor
) {

    return new Intl.NumberFormat(
        'pt-MZ',
        {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }
    ).format(
        Number(valor || 0)
    );
}


function formatarDataRelatorioOperacional(
    valor
) {

    if (!valor) {
        return '-';
    }


    const data =
        new Date(
            String(valor).replace(
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


    return data.toLocaleDateString(
        'pt-PT'
    );
}


function criarBadgeRelatorioOperacional(
    estado
) {

    const texto =
        String(
            estado || '-'
        );


    const normalizado =
        texto
            .toLowerCase();


    let classe =
        'badge-secondary';


    if (
        normalizado.includes('pago')
        || normalizado.includes('activo')
        || normalizado.includes('conclu')
        || normalizado.includes('normal')
    ) {

        classe =
            'badge-success';

    } else if (
        normalizado.includes('pendent')
        || normalizado.includes('baixo')
    ) {

        classe =
            'badge-warning';

    } else if (
        normalizado.includes('cancel')
        || normalizado.includes('inactivo')
        || normalizado.includes('sem stock')
    ) {

        classe =
            'badge-danger';
    }


    return `
        <span class="badge ${classe}">
            ${escaparHtmlRelatorioOperacional(
        texto
    )}
        </span>
    `;
}

async function carregarEstadosRelatorioOperacional() {

    const select =
        document.getElementById(
            'relatorioOperacionalEstado'
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
        tipoRelatorioOperacionalAtual
        === 'STOCK'
    ) {

        select.innerHTML = `
        <option value="">
            Todas as situações
        </option>

        <option value="NORMAL">
            Normal
        </option>

        <option value="STOCK_BAIXO">
            Stock baixo
        </option>

        <option value="SEM_STOCK">
            Sem stock
        </option>
    `;

        return;
    }

    try {

        const resultado =
            await enviarRelatorioOperacionalAjax({
                acao: 'estados',
                tipo:
                    tipoRelatorioOperacionalAtual
            });

        const estados =
            Array.isArray(resultado.data)
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


function escaparHtmlRelatorioOperacional(
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
