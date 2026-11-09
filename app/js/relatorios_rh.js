'use strict';


/*
|--------------------------------------------------------------------------
| CONFIGURAÇÃO
|--------------------------------------------------------------------------
*/

const RELATORIO_RH_AJAX =
    '/erpplus/app/ajax/relatorios_rh_ajax.php';


let tipoRelatorioRHAtual =
    'ASSIDUIDADE';


/*
|--------------------------------------------------------------------------
| INICIALIZAÇÃO
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    async function () {

        configurarEventosRelatorioRH();

        await carregarDadosIniciaisRelatorioRH();

        configurarInterfaceRelatorioRH(
            tipoRelatorioRHAtual
        );

        await carregarRelatorioRH();
    }
);


/*
|--------------------------------------------------------------------------
| EVENTOS
|--------------------------------------------------------------------------
*/

function configurarEventosRelatorioRH() {
    /*
    |--------------------------------------------------------------------------
    | Abas desktop
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '#relatorioRHTabs [data-relatorio]'
        )
        .forEach(
            link => {

                link.addEventListener(
                    'click',
                    async function (event) {

                        event.preventDefault();

                        const tipo =
                            this.dataset.relatorio;

                        await alterarTipoRelatorioRH(
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
            'tipoRelatorioRHMobile'
        );


    if (selectMobile) {

        selectMobile.addEventListener(
            'change',
            async function () {

                await alterarTipoRelatorioRH(
                    this.value
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Formulário
    |--------------------------------------------------------------------------
    */

    const form =
        document.getElementById(
            'formFiltrosRelatorioRH'
        );


    if (form) {

        form.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();

                await carregarRelatorioRH();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Limpar filtros
    |--------------------------------------------------------------------------
    */

    const limpar =
        document.getElementById(
            'btnLimparFiltrosRH'
        );


    if (limpar) {

        limpar.addEventListener(
            'click',
            async function () {

                await limparFiltrosRelatorioRH();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Filial
    |--------------------------------------------------------------------------
    */

    const filial =
        document.getElementById(
            'rhFilial'
        );


    if (filial) {

        filial.addEventListener(
            'change',
            async function () {

                await carregarDepartamentosRH();

                await carregarCargosRH();

                await carregarFuncionariosRH();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Departamento
    |--------------------------------------------------------------------------
    */

    const departamento =
        document.getElementById(
            'rhDepartamento'
        );


    if (departamento) {

        departamento.addEventListener(
            'change',
            async function () {

                await carregarCargosRH();

                await carregarFuncionariosRH();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Cargo
    |--------------------------------------------------------------------------
    */

    const cargo =
        document.getElementById(
            'rhCargo'
        );


    if (cargo) {

        cargo.addEventListener(
            'change',
            async function () {

                await carregarFuncionariosRH();
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
            'btnImprimirRelatorioRH'
        );


    if (imprimir) {

        imprimir.addEventListener(
            'click',
            function () {

                imprimirRelatorioRH();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Exportar
    |--------------------------------------------------------------------------
    */

    const exportar =
        document.getElementById(
            'btnExportarRelatorioRH'
        );


    if (exportar) {

        exportar.addEventListener(
            'click',
            function () {

                exportarRelatorioRH();
            }
        );
    }
}


/*
|--------------------------------------------------------------------------
| DADOS INICIAIS
|--------------------------------------------------------------------------
*/

async function carregarDadosIniciaisRelatorioRH() {
    await Promise.all([
        carregarFiliaisRH(),
        carregarPeriodosRH(),
        carregarOpcoesRH()
    ]);


    await carregarDepartamentosRH();

    await carregarCargosRH();

    await carregarFuncionariosRH();
}


/*
|--------------------------------------------------------------------------
| TROCAR RELATÓRIO
|--------------------------------------------------------------------------
*/

async function alterarTipoRelatorioRH(
    tipo
) {

    /*
    |--------------------------------------------------------------------------
    | Tipo anterior
    |--------------------------------------------------------------------------
    */

    const tipoAnterior =
        tipoRelatorioRHAtual;


    /*
    |--------------------------------------------------------------------------
    | Novo tipo
    |--------------------------------------------------------------------------
    */

    tipoRelatorioRHAtual =
        String(tipo || '')
            .trim()
            .toUpperCase();


    /*
    |--------------------------------------------------------------------------
    | Limpar funcionário ao trocar de relatório
    |--------------------------------------------------------------------------
    |
    | Evita que um funcionário selecionado no Perfil continue
    | filtrando Assiduidade, Faltas, Horas Extras, etc.
    |--------------------------------------------------------------------------
    */

    if (
        tipoAnterior !==
        tipoRelatorioRHAtual
    ) {

        definirValorRH(
            'rhFuncionario',
            ''
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Se saiu do Perfil, limpar ficha anterior
    |--------------------------------------------------------------------------
    */

    if (
        tipoAnterior ===
        'PERFIL_FUNCIONARIO'
        && tipoRelatorioRHAtual !==
        'PERFIL_FUNCIONARIO'
    ) {

        limparPerfilRH();
    }


    /*
    |--------------------------------------------------------------------------
    | Hidden
    |--------------------------------------------------------------------------
    */

    definirValorRH(
        'tipoRelatorioRH',
        tipoRelatorioRHAtual
    );


    /*
    |--------------------------------------------------------------------------
    | Mobile
    |--------------------------------------------------------------------------
    */

    definirValorRH(
        'tipoRelatorioRHMobile',
        tipoRelatorioRHAtual
    );


    /*
    |--------------------------------------------------------------------------
    | Desktop
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '#relatorioRHTabs [data-relatorio]'
        )
        .forEach(
            link => {

                link.classList.toggle(
                    'active',
                    link.dataset.relatorio
                    === tipoRelatorioRHAtual
                );
            }
        );


    /*
    |--------------------------------------------------------------------------
    | Interface
    |--------------------------------------------------------------------------
    */

    configurarInterfaceRelatorioRH(
        tipoRelatorioRHAtual
    );


    /*
    |--------------------------------------------------------------------------
    | Atualizar origem
    |--------------------------------------------------------------------------
    */

    atualizarSelectOrigemRH();


    /*
    |--------------------------------------------------------------------------
    | Recarregar funcionário
    |--------------------------------------------------------------------------
    |
    | Isto também altera corretamente o primeiro option:
    |
    | Relatórios normais:
    | "Todos os funcionários"
    |
    | Perfil:
    | "Selecione um funcionário"
    |--------------------------------------------------------------------------
    */

    await carregarFuncionariosRH();


    /*
    |--------------------------------------------------------------------------
    | Limpar erros
    |--------------------------------------------------------------------------
    */

    limparErroRelatorioRH();


    /*
    |--------------------------------------------------------------------------
    | Carregar novo relatório
    |--------------------------------------------------------------------------
    */

    await carregarRelatorioRH();
}


/*
|--------------------------------------------------------------------------
| CONFIGURAR INTERFACE
|--------------------------------------------------------------------------
*/

function configurarInterfaceRelatorioRH(
    tipo
) {

    /*
    |--------------------------------------------------------------------------
    | Primeiro escondemos filtros específicos
    |--------------------------------------------------------------------------
    */

    [
        'grupoRHDataInicio',
        'grupoRHDataFim',
        'grupoRHPeriodo',
        'grupoRHFilial',
        'grupoRHDepartamento',
        'grupoRHCargo',
        'grupoRHFuncionario',
        'grupoRHOcorrencia',
        'grupoRHTipoFalta',
        'grupoRHTipoHoraExtra',
        'grupoRHOrigem',
        'grupoRHSexo',
        'grupoRHTipoVinculo',
        'grupoRHPesquisa'
    ].forEach(
        id => esconderElementoRH(id)
    );


    /*
    |--------------------------------------------------------------------------
    | Perfil
    |--------------------------------------------------------------------------
    */

    esconderElementoRH(
        'rhPerfilContainer'
    );

    mostrarElementoRH(
        'rhResultadoContainer'
    );

    mostrarElementoRH(
        'relatorioRHResumo'
    );


    esconderElementoRH(
        'avisoRHFuncionarioObrigatorio'
    );


    /*
    |--------------------------------------------------------------------------
    | Configuração por relatório
    |--------------------------------------------------------------------------
    */

    switch (tipo) {


        /*
        |--------------------------------------------------------------------------
        | ASSIDUIDADE
        |--------------------------------------------------------------------------
        */

        case 'ASSIDUIDADE':

            mostrarFiltrosRH([
                'grupoRHDataInicio',
                'grupoRHDataFim',
                'grupoRHFilial',
                'grupoRHDepartamento',
                'grupoRHCargo',
                'grupoRHFuncionario',
                'grupoRHOcorrencia',
                'grupoRHPesquisa'
            ]);


            configurarResumoRH([
                'Presenças',
                'Faltas',
                'Atrasos',
                'Horas trabalhadas'
            ]);


            definirTextoRH(
                'rhRelatorioTitulo',
                'Relatório de Assiduidade'
            );

            break;


        /*
        |--------------------------------------------------------------------------
        | FALTAS
        |--------------------------------------------------------------------------
        */

        case 'FALTAS':

            mostrarFiltrosRH([
                'grupoRHDataInicio',
                'grupoRHDataFim',
                'grupoRHFilial',
                'grupoRHDepartamento',
                'grupoRHCargo',
                'grupoRHFuncionario',
                'grupoRHTipoFalta',
                'grupoRHOrigem',
                'grupoRHPesquisa'
            ]);


            configurarResumoRH([
                'Registos',
                'Dias de falta',
                'Horas de falta',
                'Com desconto'
            ]);


            definirTextoRH(
                'rhRelatorioTitulo',
                'Relatório de Faltas'
            );

            break;


        /*
        |--------------------------------------------------------------------------
        | HORAS EXTRAS
        |--------------------------------------------------------------------------
        */

        case 'HORAS_EXTRAS':

            mostrarFiltrosRH([
                'grupoRHDataInicio',
                'grupoRHDataFim',
                'grupoRHFilial',
                'grupoRHDepartamento',
                'grupoRHCargo',
                'grupoRHFuncionario',
                'grupoRHTipoHoraExtra',
                'grupoRHOrigem',
                'grupoRHPesquisa'
            ]);


            configurarResumoRH([
                'Registos',
                'Horas apuradas',
                'Horas aprovadas',
                'Valor total'
            ]);


            definirTextoRH(
                'rhRelatorioTitulo',
                'Relatório de Horas Extras'
            );

            break;


        /*
        |--------------------------------------------------------------------------
        | FOLHA SALARIAL
        |--------------------------------------------------------------------------
        */

        case 'FOLHA_SALARIAL':

            mostrarFiltrosRH([
                'grupoRHDataInicio',
                'grupoRHDataFim',
                'grupoRHPeriodo',
                'grupoRHPesquisa'
            ]);


            configurarResumoRH([
                'Funcionários',
                'Total bruto',
                'Descontos',
                'Total líquido'
            ]);


            definirTextoRH(
                'rhRelatorioTitulo',
                'Relatório de Folha Salarial'
            );

            break;


        /*
        |--------------------------------------------------------------------------
        | ENCARGOS
        |--------------------------------------------------------------------------
        */

        case 'ENCARGOS':

            mostrarFiltrosRH([
                'grupoRHDataInicio',
                'grupoRHDataFim',
                'grupoRHPeriodo'
            ]);


            configurarResumoRH([
                'INSS trabalhador',
                'INSS patronal',
                'IRPS',
                'Encargos totais'
            ]);


            definirTextoRH(
                'rhRelatorioTitulo',
                'Relatório de Encargos'
            );

            break;


        /*
        |--------------------------------------------------------------------------
        | FUNCIONÁRIOS
        |--------------------------------------------------------------------------
        */

        case 'FUNCIONARIOS':

            mostrarFiltrosRH([
                'grupoRHFilial',
                'grupoRHDepartamento',
                'grupoRHCargo',
                'grupoRHFuncionario',
                'grupoRHSexo',
                'grupoRHTipoVinculo',
                'grupoRHPesquisa'
            ]);


            configurarResumoRH([
                'Funcionários',
                'Em quadro',
                'Feminino',
                'Massa salarial base'
            ]);


            definirTextoRH(
                'rhRelatorioTitulo',
                'Relatório de Funcionários'
            );

            break;


        /*
        |--------------------------------------------------------------------------
        | PERFIL
        |--------------------------------------------------------------------------
        */

        case 'PERFIL_FUNCIONARIO':

            mostrarFiltrosRH([
                'grupoRHDataInicio',
                'grupoRHDataFim',
                'grupoRHFilial',
                'grupoRHDepartamento',
                'grupoRHCargo',
                'grupoRHFuncionario'
            ]);


            mostrarElementoRH(
                'avisoRHFuncionarioObrigatorio'
            );


            esconderElementoRH(
                'relatorioRHResumo'
            );


            esconderElementoRH(
                'rhResultadoContainer'
            );


            mostrarElementoRH(
                'rhPerfilContainer'
            );

            break;
    }
}


/*
|--------------------------------------------------------------------------
| MOSTRAR FILTROS
|--------------------------------------------------------------------------
*/

function mostrarFiltrosRH(
    ids
) {

    ids.forEach(
        id => mostrarElementoRH(id)
    );
}


/*
|--------------------------------------------------------------------------
| CONFIGURAR RESUMO
|--------------------------------------------------------------------------
*/

function configurarResumoRH(
    titulos
) {

    for (
        let i = 0;
        i < 4;
        i++
    ) {

        definirTextoRH(
            `rhResumoTitulo${i + 1}`,
            titulos[i] || '-'
        );


        definirTextoRH(
            `rhResumoValor${i + 1}`,
            '-'
        );
    }
}


/*
|--------------------------------------------------------------------------
| CARREGAR RELATÓRIO
|--------------------------------------------------------------------------
*/

async function carregarRelatorioRH() {
    limparErroRelatorioRH();


    /*
    |--------------------------------------------------------------------------
    | Perfil exige funcionário
    |--------------------------------------------------------------------------
    */

    if (
        tipoRelatorioRHAtual
        === 'PERFIL_FUNCIONARIO'
        && !obterValorRH(
            'rhFuncionario'
        )
    ) {

        limparPerfilRH();

        return;
    }


    mostrarCarregamentoRelatorioRH();


    const payload =
        obterFiltrosRelatorioRH();


    payload.acao =
        'relatorio';

    payload.tipo =
        tipoRelatorioRHAtual;


    try {

        const resposta =
            await requisicaoRelatorioRH(
                payload,
                'POST'
            );


        if (!resposta.success) {

            throw new Error(
                resposta.message
                || 'Não foi possível carregar o relatório.'
            );
        }


        renderizarRelatorioRH(
            resposta.data || {}
        );

    } catch (erro) {

        mostrarErroRelatorioRH(
            erro.message
            || 'Erro ao carregar o relatório.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

function obterFiltrosRelatorioRH() {
    return {

        data_inicio:
            obterValorRH(
                'rhDataInicio'
            ),

        data_fim:
            obterValorRH(
                'rhDataFim'
            ),

        periodo_id:
            obterValorRH(
                'rhPeriodo'
            ),

        filial_id:
            obterValorRH(
                'rhFilial'
            ),

        departamento_id:
            obterValorRH(
                'rhDepartamento'
            ),

        cargo_id:
            obterValorRH(
                'rhCargo'
            ),

        funcionario_id:
            obterValorRH(
                'rhFuncionario'
            ),

        ocorrencia:
            obterValorRH(
                'rhOcorrencia'
            ),

        tipo_falta:
            obterValorRH(
                'rhTipoFalta'
            ),

        tipo_hora_extra:
            obterValorRH(
                'rhTipoHoraExtra'
            ),

        origem:
            obterValorRH(
                'rhOrigem'
            ),

        sexo:
            obterValorRH(
                'rhSexo'
            ),

        tipo_vinculo:
            obterValorRH(
                'rhTipoVinculo'
            ),

        pesquisa:
            obterValorRH(
                'rhPesquisa'
            )
    };
}


/*
|--------------------------------------------------------------------------
| RENDERIZAR
|--------------------------------------------------------------------------
*/

function renderizarRelatorioRH(
    dados
) {

    switch (
    tipoRelatorioRHAtual
    ) {

        case 'ASSIDUIDADE':

            renderizarAssiduidadeRH(
                dados
            );

            break;


        case 'FALTAS':

            renderizarFaltasRH(
                dados
            );

            break;


        case 'HORAS_EXTRAS':

            renderizarHorasExtrasRH(
                dados
            );

            break;


        case 'FOLHA_SALARIAL':

            renderizarFolhaSalarialRH(
                dados
            );

            break;


        case 'ENCARGOS':

            renderizarEncargosRH(
                dados
            );

            break;


        case 'FUNCIONARIOS':

            renderizarFuncionariosRH(
                dados
            );

            break;


        case 'PERFIL_FUNCIONARIO':

            renderizarPerfilFuncionarioRH(
                dados
            );

            break;
    }
}


/*
|--------------------------------------------------------------------------
| ASSIDUIDADE
|--------------------------------------------------------------------------
*/

function renderizarAssiduidadeRH(
    dados
) {

    const resumo =
        dados.resumo || {};

    const registos =
        Array.isArray(
            dados.registos
        )
            ? dados.registos
            : [];


    definirResumoValoresRH([
        formatarNumeroRH(
            resumo.presencas
        ),

        formatarNumeroRH(
            resumo.faltas
        ),

        formatarNumeroRH(
            resumo.atrasos
        ),

        formatarMinutosRH(
            resumo.minutos_trabalhados
        )
    ]);


    const colunas = [
        'Data',
        'Funcionário',
        'Filial',
        'Departamento',
        'Entrada',
        'Saída',
        'Trabalhado',
        'Atraso',
        'Extra',
        'Ocorrência'
    ];


    const linhas =
        registos.map(
            linha => [

                formatarDataRH(
                    linha.data_assiduidade
                ),

                formatarFuncionarioRH(
                    linha
                ),

                linha.filial_nome || '-',

                linha.departamento_nome || '-',

                formatarHoraRH(
                    linha.hora_real_entrada
                ),

                formatarHoraRH(
                    linha.hora_real_saida
                ),

                formatarMinutosRH(
                    linha.minutos_trabalhados
                ),

                formatarMinutosRH(
                    linha.minutos_atraso
                ),

                formatarMinutosRH(
                    linha.minutos_extra
                ),

                formatarBadgeRH(
                    linha.ocorrencia || '-',
                    classeOcorrenciaRH(
                        linha.ocorrencia
                    )
                )
            ]
        );


    renderizarTabelaRH(
        colunas,
        linhas,
        registos.length
    );
}


/*
|--------------------------------------------------------------------------
| FALTAS
|--------------------------------------------------------------------------
*/

function renderizarFaltasRH(
    dados
) {

    const resumo =
        dados.resumo || {};

    const registos =
        Array.isArray(
            dados.registos
        )
            ? dados.registos
            : [];


    definirResumoValoresRH([
        formatarNumeroRH(
            resumo.total_faltas
        ),

        formatarNumeroDecimalRH(
            resumo.total_dias
        ),

        formatarMinutosRH(
            resumo.total_minutos
        ),

        formatarNumeroRH(
            resumo.descontam_salario
        )
    ]);


    const colunas = [
        'Início',
        'Fim',
        'Funcionário',
        'Tipo',
        'Dias',
        'Duração',
        'Remunerada',
        'Desconta salário',
        'Motivo',
        'Estado'
    ];


    const linhas =
        registos.map(
            linha => [

                formatarDataRH(
                    linha.data_inicio
                ),

                formatarDataRH(
                    linha.data_fim
                ),

                formatarFuncionarioRH(
                    linha
                ),

                formatarTextoCodigoRH(
                    linha.tipo_falta
                ),

                formatarNumeroDecimalRH(
                    linha.quantidade_dias
                ),

                formatarMinutosRH(
                    linha.minutos_falta
                ),

                formatarSimNaoRH(
                    linha.remunerada
                ),

                formatarSimNaoRH(
                    linha.desconta_salario
                ),

                linha.motivo || '-',

                linha.estado || '-'
            ]
        );


    renderizarTabelaRH(
        colunas,
        linhas,
        registos.length
    );
}


/*
|--------------------------------------------------------------------------
| HORAS EXTRAS
|--------------------------------------------------------------------------
*/

function renderizarHorasExtrasRH(
    dados
) {

    const resumo =
        dados.resumo || {};

    const registos =
        Array.isArray(
            dados.registos
        )
            ? dados.registos
            : [];


    definirResumoValoresRH([
        formatarNumeroRH(
            resumo.total_registos
        ),

        formatarMinutosRH(
            resumo.minutos_apurados
        ),

        formatarMinutosRH(
            resumo.minutos_aprovados
        ),

        formatarMoedaRH(
            resumo.valor_total
        )
    ]);


    const colunas = [
        'Data',
        'Funcionário',
        'Tipo',
        'Início',
        'Fim',
        'Apuradas',
        'Aprovadas',
        'Acréscimo',
        'Valor',
        'Estado'
    ];


    const linhas =
        registos.map(
            linha => [

                formatarDataRH(
                    linha.data_hora_extra
                ),

                formatarFuncionarioRH(
                    linha
                ),

                formatarTextoCodigoRH(
                    linha.tipo_hora_extra
                ),

                formatarHoraRH(
                    linha.hora_inicio
                ),

                formatarHoraRH(
                    linha.hora_fim
                ),

                formatarMinutosRH(
                    linha.minutos_apurados
                ),

                formatarMinutosRH(
                    linha.minutos_aprovados
                ),

                formatarPercentagemRH(
                    linha.percentual_acrescimo
                ),

                formatarMoedaRH(
                    linha.valor_total
                ),

                linha.estado || '-'
            ]
        );


    renderizarTabelaRH(
        colunas,
        linhas,
        registos.length
    );
}


/*
|--------------------------------------------------------------------------
| FOLHA SALARIAL
|--------------------------------------------------------------------------
*/

function renderizarFolhaSalarialRH(
    dados
) {

    const resumo =
        dados.resumo || {};

    const registos =
        Array.isArray(
            dados.registos
        )
            ? dados.registos
            : [];


    definirResumoValoresRH([
        formatarNumeroRH(
            resumo.numero_funcionarios
        ),

        formatarMoedaRH(
            resumo.total_bruto
        ),

        formatarMoedaRH(
            resumo.total_descontos
        ),

        formatarMoedaRH(
            resumo.total_liquido
        )
    ]);


    const colunas = [
        'Período',
        'Código',
        'Funcionários',
        'Salário base',
        'Proventos',
        'Descontos',
        'Bruto',
        'Líquido',
        'Pago',
        'Pendente',
        'Estado'
    ];


    const linhas =
        registos.map(
            linha => [

                linha.periodo_descricao
                || linha.periodo_codigo
                || '-',

                linha.codigo || '-',

                formatarNumeroRH(
                    linha.numero_funcionarios
                ),

                formatarMoedaRH(
                    linha.total_salario_base
                ),

                formatarMoedaRH(
                    linha.total_proventos
                ),

                formatarMoedaRH(
                    linha.total_descontos
                ),

                formatarMoedaRH(
                    linha.total_bruto
                ),

                formatarMoedaRH(
                    linha.total_liquido
                ),

                formatarMoedaRH(
                    linha.total_pago
                ),

                formatarMoedaRH(
                    linha.saldo_pendente
                ),

                linha.estado || '-'
            ]
        );


    renderizarTabelaRH(
        colunas,
        linhas,
        registos.length
    );
}


/*
|--------------------------------------------------------------------------
| ENCARGOS
|--------------------------------------------------------------------------
*/

function renderizarEncargosRH(
    dados
) {

    const resumo =
        dados.resumo || {};

    const registos =
        Array.isArray(
            dados.registos
        )
            ? dados.registos
            : [];


    definirResumoValoresRH([
        formatarMoedaRH(
            resumo.total_inss_trabalhador
        ),

        formatarMoedaRH(
            resumo.total_inss_patronal
        ),

        formatarMoedaRH(
            resumo.total_irps
        ),

        formatarMoedaRH(
            resumo.encargos_fiscais_sociais
        )
    ]);


    const colunas = [
        'Período',
        'Funcionários',
        'INSS trabalhador',
        'INSS patronal',
        'IRPS',
        'Outros descontos',
        'Retenções trabalhador',
        'Encargos fiscais/sociais',
        'Estado'
    ];


    const linhas =
        registos.map(
            linha => [

                linha.periodo_descricao
                || linha.periodo_codigo
                || '-',

                formatarNumeroRH(
                    linha.numero_funcionarios
                ),

                formatarMoedaRH(
                    linha.total_inss
                ),

                formatarMoedaRH(
                    linha.total_inss_patronal
                ),

                formatarMoedaRH(
                    linha.total_irps
                ),

                formatarMoedaRH(
                    linha.total_outros_descontos
                ),

                formatarMoedaRH(
                    linha.retencoes_trabalhador
                ),

                formatarMoedaRH(
                    linha.encargos_fiscais_sociais
                ),

                linha.estado || '-'
            ]
        );


    renderizarTabelaRH(
        colunas,
        linhas,
        registos.length
    );
}


/*
|--------------------------------------------------------------------------
| FUNCIONÁRIOS
|--------------------------------------------------------------------------
*/

function renderizarFuncionariosRH(
    dados
) {

    const resumo =
        dados.resumo || {};

    const registos =
        Array.isArray(
            dados.registos
        )
            ? dados.registos
            : [];


    definirResumoValoresRH([
        formatarNumeroRH(
            resumo.total_funcionarios
        ),

        formatarNumeroRH(
            resumo.em_quadro
        ),

        formatarNumeroRH(
            resumo.feminino
        ),

        formatarMoedaRH(
            resumo.massa_salarial_base
        )
    ]);


    const colunas = [
        'Número',
        'Funcionário',
        'Sexo',
        'Filial',
        'Departamento',
        'Cargo',
        'Vínculo',
        'Admissão',
        'Salário base',
        'Estado'
    ];


    const linhas =
        registos.map(
            linha => [

                linha.numero_funcionario
                || '-',

                linha.nome_completo
                || '-',

                formatarTextoCodigoRH(
                    linha.sexo
                ),

                linha.filial_nome
                || '-',

                linha.departamento_nome
                || '-',

                linha.cargo_nome
                || '-',

                formatarTextoCodigoRH(
                    linha.tipo_vinculo
                ),

                formatarDataRH(
                    linha.data_admissao
                ),

                formatarMoedaRH(
                    linha.salario_base_atual
                ),

                linha.estado
                || '-'
            ]
        );


    renderizarTabelaRH(
        colunas,
        linhas,
        registos.length
    );
}


/*
|--------------------------------------------------------------------------
| PERFIL DO FUNCIONÁRIO
|--------------------------------------------------------------------------
*/

function renderizarPerfilFuncionarioRH(
    dados
) {

    const funcionario =
        dados.funcionario || {};

    const contrato =
        dados.contrato || {};

    const salario =
        dados.salario || {};

    const assiduidade =
        dados.assiduidade || {};

    const faltas =
        dados.faltas || {};

    const horasExtras =
        dados.horas_extras || {};


    mostrarElementoRH(
        'rhPerfilContainer'
    );


    definirTextoRH(
        'rhPerfilNumero',
        funcionario.numero_funcionario
        || '-'
    );


    definirTextoRH(
        'rhPerfilNome',
        funcionario.nome_completo
        || '-'
    );


    definirTextoRH(
        'rhPerfilEstado',
        funcionario.estado
        || '-'
    );


    definirTextoRH(
        'rhPerfilVinculo',
        formatarTextoCodigoRH(
            funcionario.tipo_vinculo
        )
    );


    definirTextoRH(
        'rhPerfilFilial',
        funcionario.filial_nome
        || '-'
    );


    definirTextoRH(
        'rhPerfilDepartamento',
        funcionario.departamento_nome
        || '-'
    );


    definirTextoRH(
        'rhPerfilCargo',
        funcionario.cargo_nome
        || '-'
    );


    definirTextoRH(
        'rhPerfilAdmissao',
        formatarDataRH(
            funcionario.data_admissao
        )
    );


    /*
    |--------------------------------------------------------------------------
    | Contrato
    |--------------------------------------------------------------------------
    */

    definirTextoRH(
        'rhPerfilContratoNumero',
        contrato.numero_contrato
        || '-'
    );


    definirTextoRH(
        'rhPerfilContratoEstado',
        contrato.estado
        || '-'
    );


    definirTextoRH(
        'rhPerfilContratoInicio',
        formatarDataRH(
            contrato.data_inicio
        )
    );


    definirTextoRH(
        'rhPerfilContratoFim',
        contrato.data_fim
            ? formatarDataRH(
                contrato.data_fim
            )
            : 'Sem termo'
    );


    definirTextoRH(
        'rhPerfilContratoHoras',
        contrato.horas_semanais
            ? `${contrato.horas_semanais} h`
            : '-'
    );


    definirTextoRH(
        'rhPerfilContratoLocal',
        contrato.local_trabalho
        || '-'
    );


    /*
    |--------------------------------------------------------------------------
    | Salário
    |--------------------------------------------------------------------------
    */

    const salarioBase =
        salario.salario_base
        ?? funcionario.salario_base
        ?? 0;


    definirTextoRH(
        'rhPerfilSalarioBase',
        formatarMoedaRH(
            salarioBase
        )
    );


    definirTextoRH(
        'rhPerfilSalarioPeriodicidade',
        formatarTextoCodigoRH(
            salario.periodicidade
            || contrato.periodicidade_salario
            || '-'
        )
    );


    definirTextoRH(
        'rhPerfilSalarioHoras',
        salario.horas_mensais
            ? `${salario.horas_mensais} h`
            : '-'
    );


    definirTextoRH(
        'rhPerfilSalarioDias',
        salario.dias_mensais
        || '-'
    );


    /*
    |--------------------------------------------------------------------------
    | Indicadores individuais
    |--------------------------------------------------------------------------
    */

    definirTextoRH(
        'rhPerfilPresencas',
        formatarNumeroRH(
            assiduidade.resumo
                ?.presencas
        )
    );


    definirTextoRH(
        'rhPerfilFaltas',
        formatarNumeroRH(
            faltas.resumo
                ?.total_faltas
        )
    );


    definirTextoRH(
        'rhPerfilHorasExtras',
        formatarMinutosRH(
            horasExtras.resumo
                ?.minutos_aprovados
        )
    );
}


/*
|--------------------------------------------------------------------------
| LIMPAR PERFIL
|--------------------------------------------------------------------------
*/

function limparPerfilRH() {
    [
        'rhPerfilNumero',
        'rhPerfilNome',
        'rhPerfilEstado',
        'rhPerfilVinculo',
        'rhPerfilFilial',
        'rhPerfilDepartamento',
        'rhPerfilCargo',
        'rhPerfilAdmissao',
        'rhPerfilContratoNumero',
        'rhPerfilContratoEstado',
        'rhPerfilContratoInicio',
        'rhPerfilContratoFim',
        'rhPerfilContratoHoras',
        'rhPerfilContratoLocal',
        'rhPerfilSalarioBase',
        'rhPerfilSalarioPeriodicidade',
        'rhPerfilSalarioHoras',
        'rhPerfilSalarioDias'
    ].forEach(
        id => definirTextoRH(
            id,
            '-'
        )
    );


    definirTextoRH(
        'rhPerfilPresencas',
        '0'
    );

    definirTextoRH(
        'rhPerfilFaltas',
        '0'
    );

    definirTextoRH(
        'rhPerfilHorasExtras',
        '0h'
    );
}


/*
|--------------------------------------------------------------------------
| TABELA GENÉRICA
|--------------------------------------------------------------------------
*/

function renderizarTabelaRH(
    colunas,
    linhas,
    quantidade
) {

    definirTextoRH(
        'rhRelatorioQuantidade',

        quantidade === 1
            ? '1 registo'
            : `${quantidade} registos`
    );


    const head =
        document.getElementById(
            'rhRelatorioTableHead'
        );


    const body =
        document.getElementById(
            'rhRelatorioTableBody'
        );


    if (!head || !body) {
        return;
    }


    head.innerHTML = `

        <tr>

            ${colunas
            .map(
                coluna => `
                            <th>
                                ${escaparHtmlRH(coluna)}
                            </th>
                        `
            )
            .join('')
        }

        </tr>
    `;


    if (!linhas.length) {

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
        linhas
            .map(
                linha => `

                    <tr>

                        ${linha
                        .map(
                            valor => `
                                        <td>
                                            ${valor
                                ?? '-'
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
| RESUMO
|--------------------------------------------------------------------------
*/

function definirResumoValoresRH(
    valores
) {

    for (
        let i = 0;
        i < 4;
        i++
    ) {

        definirTextoRH(
            `rhResumoValor${i + 1}`,
            valores[i] ?? '-'
        );
    }
}


/*
|--------------------------------------------------------------------------
| FILIAIS
|--------------------------------------------------------------------------
*/

async function carregarFiliaisRH() {
    try {

        const resposta =
            await requisicaoRelatorioRH({
                acao: 'filiais'
            });


        preencherSelectRH(
            'rhFilial',
            resposta.data || [],
            'Todas as filiais',
            item => item.nome,
            item => item.id
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
| DEPARTAMENTOS
|--------------------------------------------------------------------------
*/

async function carregarDepartamentosRH() {
    try {

        const resposta =
            await requisicaoRelatorioRH({

                acao:
                    'departamentos',

                filial_id:
                    obterValorRH(
                        'rhFilial'
                    )
            });


        preencherSelectRH(
            'rhDepartamento',
            resposta.data || [],
            'Todos os departamentos',
            item =>
                item.codigo
                    ? `${item.codigo} - ${item.nome}`
                    : item.nome,
            item => item.id
        );

    } catch (erro) {

        console.error(
            'Erro ao carregar departamentos:',
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| CARGOS
|--------------------------------------------------------------------------
*/

async function carregarCargosRH() {
    try {

        const resposta =
            await requisicaoRelatorioRH({

                acao:
                    'cargos',

                departamento_id:
                    obterValorRH(
                        'rhDepartamento'
                    )
            });


        preencherSelectRH(
            'rhCargo',
            resposta.data || [],
            'Todos os cargos',
            item =>
                item.codigo
                    ? `${item.codigo} - ${item.nome}`
                    : item.nome,
            item => item.id
        );

    } catch (erro) {

        console.error(
            'Erro ao carregar cargos:',
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| FUNCIONÁRIOS
|--------------------------------------------------------------------------
*/

async function carregarFuncionariosRH() {
    try {

        const valorAtual =
            obterValorRH(
                'rhFuncionario'
            );


        const resposta =
            await requisicaoRelatorioRH({

                acao:
                    'funcionarios',

                filial_id:
                    obterValorRH(
                        'rhFilial'
                    ),

                departamento_id:
                    obterValorRH(
                        'rhDepartamento'
                    ),

                cargo_id:
                    obterValorRH(
                        'rhCargo'
                    )
            });


        const textoPadrao =
            tipoRelatorioRHAtual
                === 'PERFIL_FUNCIONARIO'
                ? 'Selecione um funcionário'
                : 'Todos os funcionários';


        preencherSelectRH(
            'rhFuncionario',
            resposta.data || [],
            textoPadrao,

            item =>
                item.numero_funcionario
                    ? `${item.numero_funcionario} - ${item.nome_completo}`
                    : item.nome_completo,

            item => item.id
        );


        /*
        |--------------------------------------------------------------------------
        | Tentar preservar seleção
        |--------------------------------------------------------------------------
        */

        if (
            valorAtual
            && (
                resposta.data || []
            ).some(
                item =>
                    String(item.id)
                    === String(valorAtual)
            )
        ) {

            definirValorRH(
                'rhFuncionario',
                valorAtual
            );
        }

    } catch (erro) {

        console.error(
            'Erro ao carregar funcionários:',
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| PERÍODOS
|--------------------------------------------------------------------------
*/

async function carregarPeriodosRH() {
    try {

        const resposta =
            await requisicaoRelatorioRH({
                acao: 'periodos'
            });


        preencherSelectRH(
            'rhPeriodo',
            resposta.data || [],
            'Todos os períodos',

            item => {

                const descricao =
                    item.descricao
                    || item.codigo
                    || '-';


                if (
                    item.data_inicio
                    && item.data_fim
                ) {

                    return `${descricao} (${formatarDataRH(
                        item.data_inicio
                    )} - ${formatarDataRH(
                        item.data_fim
                    )})`;
                }


                return descricao;
            },

            item => item.id
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
| OPÇÕES
|--------------------------------------------------------------------------
*/

async function carregarOpcoesRH() {
    try {

        const resposta =
            await requisicaoRelatorioRH({
                acao: 'opcoes'
            });


        const dados =
            resposta.data || {};


        preencherSelectOpcaoRH(
            'rhOcorrencia',
            dados.ocorrencias,
            'Todas as ocorrências'
        );


        preencherSelectOpcaoRH(
            'rhTipoFalta',
            dados.tipos_falta,
            'Todos os tipos'
        );


        preencherSelectOpcaoRH(
            'rhTipoHoraExtra',
            dados.tipos_hora_extra,
            'Todos os tipos'
        );


        preencherSelectOpcaoRH(
            'rhSexo',
            dados.sexos,
            'Todos'
        );


        preencherSelectOpcaoRH(
            'rhTipoVinculo',
            dados.tipos_vinculo,
            'Todos os vínculos'
        );


        /*
        |--------------------------------------------------------------------------
        | Origem começa com falta.
        | Ao trocar relatório atualizamos.
        |--------------------------------------------------------------------------
        */

        atualizarSelectOrigemRH(
            dados
        );


        window.opcoesRelatorioRH =
            dados;

    } catch (erro) {

        console.error(
            'Erro ao carregar opções:',
            erro
        );
    }
}


/*
|--------------------------------------------------------------------------
| ORIGEM
|--------------------------------------------------------------------------
*/

function atualizarSelectOrigemRH(
    dados = null
) {

    const opcoes =
        dados
        || window.opcoesRelatorioRH
        || {};


    let lista = [];


    if (
        tipoRelatorioRHAtual
        === 'HORAS_EXTRAS'
    ) {

        lista =
            opcoes.origens_hora_extra
            || [];

    } else {

        lista =
            opcoes.origens_falta
            || [];
    }


    preencherSelectOpcaoRH(
        'rhOrigem',
        lista,
        'Todas as origens'
    );
}


/*
|--------------------------------------------------------------------------
| SELECT GENÉRICO
|--------------------------------------------------------------------------
*/

function preencherSelectRH(
    id,
    itens,
    textoInicial,
    obterTexto,
    obterValor
) {

    const select =
        document.getElementById(id);


    if (!select) {
        return;
    }


    select.innerHTML =
        `<option value="">${escaparHtmlRH(
            textoInicial
        )}</option>`;


    itens.forEach(
        item => {

            const option =
                document.createElement(
                    'option'
                );


            option.value =
                obterValor(item);


            option.textContent =
                obterTexto(item);


            select.appendChild(
                option
            );
        }
    );
}


function preencherSelectOpcaoRH(
    id,
    itens = [],
    textoInicial
) {

    preencherSelectRH(

        id,

        Array.isArray(itens)
            ? itens
            : [],

        textoInicial,

        item =>
            item.nome
            || item.valor,

        item =>
            item.valor
    );
}


/*
|--------------------------------------------------------------------------
| LIMPAR FILTROS
|--------------------------------------------------------------------------
*/

async function limparFiltrosRelatorioRH() {
    [
        'rhDataInicio',
        'rhDataFim',
        'rhPeriodo',
        'rhFilial',
        'rhDepartamento',
        'rhCargo',
        'rhFuncionario',
        'rhOcorrencia',
        'rhTipoFalta',
        'rhTipoHoraExtra',
        'rhOrigem',
        'rhSexo',
        'rhTipoVinculo',
        'rhPesquisa'
    ].forEach(
        id => definirValorRH(
            id,
            ''
        )
    );


    await carregarDepartamentosRH();

    await carregarCargosRH();

    await carregarFuncionariosRH();


    if (
        tipoRelatorioRHAtual
        === 'PERFIL_FUNCIONARIO'
    ) {

        limparPerfilRH();

        return;
    }


    await carregarRelatorioRH();
}


/*
|--------------------------------------------------------------------------
| CARREGAMENTO
|--------------------------------------------------------------------------
*/

function mostrarCarregamentoRelatorioRH() {
    if (
        tipoRelatorioRHAtual
        === 'PERFIL_FUNCIONARIO'
    ) {
        return;
    }


    const body =
        document.getElementById(
            'rhRelatorioTableBody'
        );


    const head =
        document.getElementById(
            'rhRelatorioTableHead'
        );


    if (head) {

        head.innerHTML = `

            <tr>

                <th>
                    A carregar...
                </th>

            </tr>
        `;
    }


    if (body) {

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
}


/*
|--------------------------------------------------------------------------
| ERRO
|--------------------------------------------------------------------------
*/

function mostrarErroRelatorioRH(
    mensagem
) {

    const elemento =
        document.getElementById(
            'rhRelatorioErro'
        );


    if (
        tipoRelatorioRHAtual
        === 'PERFIL_FUNCIONARIO'
    ) {

        alert(mensagem);

        return;
    }


    if (!elemento) {
        return;
    }


    elemento.textContent =
        mensagem;


    elemento.classList.remove(
        'd-none'
    );


    definirTextoRH(
        'rhRelatorioQuantidade',
        '0 registos'
    );
}


function limparErroRelatorioRH() {
    const elemento =
        document.getElementById(
            'rhRelatorioErro'
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
| REQUEST
|--------------------------------------------------------------------------
*/

async function requisicaoRelatorioRH(
    dados,
    metodo = 'GET'
) {

    metodo =
        String(metodo)
            .toUpperCase();


    let url =
        RELATORIO_RH_AJAX;


    const configuracao = {
        method: metodo,

        credentials:
            'same-origin',

        headers: {
            'Accept':
                'application/json'
        }
    };


    if (metodo === 'GET') {

        const parametros =
            new URLSearchParams();


        Object.entries(
            dados || {}
        ).forEach(
            ([chave, valor]) => {

                if (
                    valor !== undefined
                    && valor !== null
                ) {

                    parametros.append(
                        chave,
                        valor
                    );
                }
            }
        );


        url +=
            '?'
            + parametros.toString();

    } else {

        configuracao.headers[
            'Content-Type'
        ] =
            'application/json';


        configuracao.body =
            JSON.stringify(
                dados || {}
            );
    }


    const response =
        await fetch(
            url,
            configuracao
        );


    let resultado;


    try {

        resultado =
            await response.json();

    } catch (erro) {

        throw new Error(
            'O servidor retornou uma resposta inválida.'
        );
    }


    if (
        !response.ok
        || !resultado.success
    ) {

        throw new Error(
            resultado.message
            || 'Erro ao processar a solicitação.'
        );
    }


    return resultado;
}


/*
|--------------------------------------------------------------------------
| IMPRESSÃO
|--------------------------------------------------------------------------
*/

function imprimirRelatorioRH() {
    const filtros =
        obterFiltrosRelatorioRH();

    /*
    |--------------------------------------------------------------------------
    | Validar perfil do funcionário
    |--------------------------------------------------------------------------
    */

    if (
        tipoRelatorioRHAtual
        === 'PERFIL_FUNCIONARIO'
        && !filtros.funcionario_id
    ) {
        mostrarErroRelatorioRH(
            'Selecione um funcionário para imprimir o perfil.'
        );

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Parâmetros da impressão
    |--------------------------------------------------------------------------
    */

    const parametros =
        new URLSearchParams();

    parametros.set(
        'tipo',
        tipoRelatorioRHAtual
    );

    Object.entries(
        filtros
    ).forEach(
        ([chave, valor]) => {

            if (
                valor !== undefined
                && valor !== null
                && String(valor).trim() !== ''
            ) {
                parametros.set(
                    chave,
                    String(valor)
                );
            }
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Abrir impressão
    |--------------------------------------------------------------------------
    */

    const url =
        '/erpplus/app/ajax/'
        + 'relatorios_rh_imprimir.php?'
        + parametros.toString();

    window.open(
        url,
        '_blank',
        'noopener,noreferrer'
    );
}


/*
|--------------------------------------------------------------------------
| EXPORTAÇÃO
|--------------------------------------------------------------------------
*/

function exportarRelatorioRH() {
    limparErroRelatorioRH();

    const filtros =
        obterFiltrosRelatorioRH();

    if (
        tipoRelatorioRHAtual === 'PERFIL_FUNCIONARIO'
        && !filtros.funcionario_id
    ) {
        mostrarErroRelatorioRH(
            'Selecione um funcionário para exportar o perfil.'
        );

        return;
    }

    const parametros =
        new URLSearchParams();

    parametros.set(
        'tipo',
        tipoRelatorioRHAtual
    );

    Object.entries(filtros).forEach(
        ([chave, valor]) => {
            if (
                valor !== undefined
                && valor !== null
                && String(valor).trim() !== ''
            ) {
                parametros.set(
                    chave,
                    String(valor)
                );
            }
        }
    );

    const url =
        '/erpplus/app/ajax/'
        + 'relatorios_rh_exportar.php?'
        + parametros.toString();

    window.location.href = url;
}


/*
|--------------------------------------------------------------------------
| FORMATADORES
|--------------------------------------------------------------------------
*/

function formatarMoedaRH(
    valor
) {

    const numero =
        Number(valor || 0);


    return new Intl.NumberFormat(
        'pt-PT',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    ).format(numero)
        + ' MTn';
}


function formatarNumeroRH(
    valor
) {

    return new Intl.NumberFormat(
        'pt-PT'
    ).format(
        Number(valor || 0)
    );
}


function formatarNumeroDecimalRH(
    valor
) {

    return new Intl.NumberFormat(
        'pt-PT',
        {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }
    ).format(
        Number(valor || 0)
    );
}


function formatarPercentagemRH(
    valor
) {

    return new Intl.NumberFormat(
        'pt-PT',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    ).format(
        Number(valor || 0)
    )
        + '%';
}


function formatarMinutosRH(
    minutos
) {

    minutos =
        Number(minutos || 0);


    const negativo =
        minutos < 0;


    minutos =
        Math.abs(
            Math.round(minutos)
        );


    const horas =
        Math.floor(
            minutos / 60
        );


    const resto =
        minutos % 60;


    const resultado =
        resto > 0
            ? `${horas}h ${resto}min`
            : `${horas}h`;


    return negativo
        ? `-${resultado}`
        : resultado;
}


function formatarDataRH(
    valor
) {

    if (!valor) {
        return '-';
    }


    const partes =
        String(valor)
            .substring(0, 10)
            .split('-');


    if (partes.length !== 3) {
        return escaparHtmlRH(valor);
    }


    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}


function formatarHoraRH(
    valor
) {

    if (!valor) {
        return '-';
    }


    return escaparHtmlRH(
        String(valor)
            .substring(0, 5)
    );
}


function formatarFuncionarioRH(
    linha
) {

    const numero =
        linha.numero_funcionario
        || '';

    const nome =
        linha.funcionario_nome
        || linha.nome_completo
        || '-';


    if (numero) {

        return `
            <strong>
                ${escaparHtmlRH(nome)}
            </strong>

            <div class="small text-muted">
                ${escaparHtmlRH(numero)}
            </div>
        `;
    }


    return escaparHtmlRH(
        nome
    );
}


function formatarTextoCodigoRH(
    valor
) {

    if (!valor) {
        return '-';
    }


    return escaparHtmlRH(
        String(valor)
            .replaceAll(
                '_',
                ' '
            )
            .toLowerCase()
            .replace(
                /\b\w/g,
                letra =>
                    letra.toUpperCase()
            )
    );
}


function formatarSimNaoRH(
    valor
) {

    const sim =
        Number(valor || 0) === 1;


    return formatarBadgeRH(
        sim
            ? 'Sim'
            : 'Não',

        sim
            ? 'badge-success'
            : 'badge-light'
    );
}


function formatarBadgeRH(
    texto,
    classe = 'badge-light'
) {

    return `
        <span class="badge ${classe}">
            ${escaparHtmlRH(texto)}
        </span>
    `;
}


function classeOcorrenciaRH(
    ocorrencia
) {

    switch (
    String(
        ocorrencia || ''
    ).toUpperCase()
    ) {

        case 'NORMAL':
            return 'badge-success';


        case 'ATRASO':
        case 'SAIDA_ANTECIPADA':
        case 'FALTA_PARCIAL':
            return 'badge-warning';


        case 'FALTA_TOTAL':
            return 'badge-danger';


        case 'FERIAS':
        case 'LICENCA':
            return 'badge-info';


        default:
            return 'badge-light';
    }
}


/*
|--------------------------------------------------------------------------
| HELPERS DOM
|--------------------------------------------------------------------------
*/

function obterValorRH(
    id
) {

    const elemento =
        document.getElementById(id);


    if (!elemento) {
        return '';
    }


    return String(
        elemento.value
        ?? ''
    ).trim();
}


function definirValorRH(
    id,
    valor
) {

    const elemento =
        document.getElementById(id);


    if (elemento) {

        elemento.value =
            valor ?? '';
    }
}


function definirTextoRH(
    id,
    valor
) {

    const elemento =
        document.getElementById(id);


    if (elemento) {

        elemento.textContent =
            valor ?? '';
    }
}


function mostrarElementoRH(
    id
) {

    const elemento =
        document.getElementById(id);


    if (elemento) {

        elemento.classList.remove(
            'd-none'
        );
    }
}


function esconderElementoRH(
    id
) {

    const elemento =
        document.getElementById(id);


    if (elemento) {

        elemento.classList.add(
            'd-none'
        );
    }
}


/*
|--------------------------------------------------------------------------
| HTML
|--------------------------------------------------------------------------
*/

function escaparHtmlRH(
    valor
) {

    return String(
        valor ?? ''
    )
        .replaceAll(
            '&',
            '&amp;'
        )
        .replaceAll(
            '<',
            '&lt;'
        )
        .replaceAll(
            '>',
            '&gt;'
        )
        .replaceAll(
            '"',
            '&quot;'
        )
        .replaceAll(
            "'",
            '&#039;'
        );
}