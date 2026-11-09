'use strict';

(() => {

    const AUDITORIA_URL =
        '/erpplus/app/ajax/auditoria_ajax.php';


    let auditorias = [];

    let auditoriaDataTable =
        null;

    let auditoriaModuloInicializado =
        false;


    /*
    |--------------------------------------------------------------------------
    | Inicialização
    |--------------------------------------------------------------------------
    */

    if (
        document.readyState
        === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarModuloAuditoria
        );
    } else {
        iniciarModuloAuditoria();
    }


    async function iniciarModuloAuditoria() {

        if (
            auditoriaModuloInicializado
        ) {
            return;
        }


        if (
            !document.getElementById(
                'auditoriaTabela'
            )
        ) {
            return;
        }


        auditoriaModuloInicializado =
            true;


        configurarEventosAuditoria();


        await carregarFiltrosAuditoria();

        await carregarAuditoria();
    }


    /*
    |--------------------------------------------------------------------------
    | Eventos
    |--------------------------------------------------------------------------
    */

    function configurarEventosAuditoria() {

        document
            .getElementById(
                'auditoriaFormFiltros'
            )
            ?.addEventListener(
                'submit',
                evento => {

                    evento.preventDefault();

                    carregarAuditoria();
                }
            );


        document
            .getElementById(
                'auditoriaBtnAtualizar'
            )
            ?.addEventListener(
                'click',
                carregarAuditoria
            );


        document
            .getElementById(
                'auditoriaBtnLimpar'
            )
            ?.addEventListener(
                'click',
                limparFiltrosAuditoria
            );


        document.addEventListener(
            'click',
            processarCliqueAuditoria
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Requisição
    |--------------------------------------------------------------------------
    */

    async function requisicaoAuditoria(
        acao,
        parametros = {}
    ) {
        const query =
            new URLSearchParams(
                parametros
            );


        query.set(
            'acao',
            acao
        );


        query.set(
            '_',
            String(
                Date.now()
            )
        );


        const url =
            AUDITORIA_URL
            + '?'
            + query.toString();


        let resposta;


        try {

            resposta =
                await fetch(
                    url,
                    {
                        method:
                            'GET',

                        credentials:
                            'same-origin',

                        cache:
                            'no-store'
                    }
                );

        } catch (erro) {

            throw new Error(
                'Não foi possível conectar ao servidor.'
            );
        }


        const texto =
            await resposta.text();


        let resultado;


        try {

            resultado =
                JSON.parse(
                    texto
                );

        } catch (erro) {

            console.error(
                'Resposta inválida:',
                texto
            );


            throw new Error(
                'O servidor devolveu uma resposta inválida.'
            );
        }


        const sucesso =
            resultado.sucesso
            ?? resultado.success
            ?? false;


        if (
            !resposta.ok
            || sucesso !== true
        ) {
            throw new Error(
                resultado.mensagem
                || resultado.message
                || 'Não foi possível concluir a operação.'
            );
        }


        return {
            ...resultado,

            dados:
                resultado.dados
                ?? resultado.data
                ?? []
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Filtros
    |--------------------------------------------------------------------------
    */

    async function carregarFiltrosAuditoria() {

        try {

            const resultado =
                await requisicaoAuditoria(
                    'filtros'
                );


            const dados =
                resultado.dados || {};


            preencherUtilizadoresAuditoria(
                Array.isArray(
                    dados.utilizadores
                )
                    ? dados.utilizadores
                    : []
            );

        } catch (erro) {

            console.error(
                'Erro ao carregar filtros de auditoria:',
                erro
            );
        }
    }


    function preencherUtilizadoresAuditoria(
        utilizadores
    ) {
        const select =
            document.getElementById(
                'auditoriaUsuarioId'
            );


        if (!select) {
            return;
        }


        select.replaceChildren();


        select.add(
            new Option(
                'Todos',
                ''
            )
        );


        utilizadores.forEach(
            utilizador => {

                select.add(
                    new Option(
                        utilizador.nome
                        || 'Utilizador',

                        String(
                            utilizador.id
                        )
                    )
                );
            }
        );
    }


    function obterFiltrosAuditoria() {

        const filtros = {};


        const dataInicial =
            document
                .getElementById(
                    'auditoriaDataInicial'
                )
                ?.value
                ?.trim()
            || '';


        const dataFinal =
            document
                .getElementById(
                    'auditoriaDataFinal'
                )
                ?.value
                ?.trim()
            || '';


        const usuarioId =
            document
                .getElementById(
                    'auditoriaUsuarioId'
                )
                ?.value
                ?.trim()
            || '';


        const operacao =
            document
                .getElementById(
                    'auditoriaOperacao'
                )
                ?.value
                ?.trim()
            || '';


        if (dataInicial) {
            filtros.data_inicial =
                dataInicial;
        }


        if (dataFinal) {
            filtros.data_final =
                dataFinal;
        }


        if (usuarioId) {
            filtros.usuario_id =
                usuarioId;
        }


        if (operacao) {
            filtros.operacao =
                operacao;
        }


        return filtros;
    }


    async function limparFiltrosAuditoria() {

        document
            .getElementById(
                'auditoriaFormFiltros'
            )
            ?.reset();


        await carregarAuditoria();
    }


    /*
    |--------------------------------------------------------------------------
    | Listagem
    |--------------------------------------------------------------------------
    */

    async function carregarAuditoria() {

        const tbody =
            document.getElementById(
                'auditoriaTabelaBody'
            );


        if (!tbody) {
            return;
        }


        esconderErroAuditoria(
            'auditoriaErroLista'
        );


        try {

            const resultado =
                await requisicaoAuditoria(
                    'listar',
                    obterFiltrosAuditoria()
                );


            auditorias =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];


            /*
             * Destruir antes de alterar tbody.
             */
            destruirDataTableAuditoria();


            /*
             * Não usamos <td colspan>.
             */
            tbody.innerHTML = '';


            if (
                auditorias.length > 0
            ) {
                tbody.innerHTML =
                    auditorias
                        .map(
                            criarLinhaAuditoria
                        )
                        .join('');
            }


            iniciarDataTableAuditoria();

        } catch (erro) {

            destruirDataTableAuditoria();


            tbody.innerHTML = '';


            mostrarErroAuditoria(
                'auditoriaErroLista',
                erro.message
            );
        }
    }


    function criarLinhaAuditoria(
        auditoria
    ) {
        const id =
            Number(
                auditoria.id
            );


        const operacao =
            String(
                auditoria.operacao
                || ''
            )
                .toUpperCase();


        return `
            <tr>

                <td
                    data-order="${escaparHtmlAuditoria(
            auditoria.criado_em
            || ''
        )}">

                    ${formatarDataHoraAuditoria(
            auditoria.criado_em
        )}

                </td>


                <td>

                    <strong>
                        ${escaparHtmlAuditoria(
            auditoria.usuario_nome
            || 'Sistema'
        )}
                    </strong>

                </td>


                <td>
                    ${criarBadgeOperacaoAuditoria(
            operacao
        )}
                </td>


                <td class="text-center">

                    <button
                        type="button"
                        class="btn btn-sm btn-info"
                        title="${escaparHtmlAuditoria(
            tituloBotaoAuditoria(
                operacao
            )
        )}"
                        data-auditoria-acao="detalhes"
                        data-auditoria-id="${id}">

                        <i class="fa fa-eye"></i>

                        <span class="d-none d-md-inline">
                            ${escaparHtmlAuditoria(
            textoBotaoAuditoria(
                operacao
            )
        )}
                        </span>

                    </button>

                </td>

            </tr>
        `;
    }


    /*
    |--------------------------------------------------------------------------
    | Clique
    |--------------------------------------------------------------------------
    */

    function processarCliqueAuditoria(
        evento
    ) {
        const botao =
            evento.target.closest(
                '[data-auditoria-acao]'
            );


        if (!botao) {
            return;
        }


        const id =
            Number(
                botao.dataset.auditoriaId
            );


        if (!id) {
            return;
        }


        if (
            botao.dataset.auditoriaAcao
            === 'detalhes'
        ) {
            abrirDetalhesAuditoria(
                id
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Detalhes
    |--------------------------------------------------------------------------
    */

    async function abrirDetalhesAuditoria(
        id
    ) {
        esconderErroAuditoria(
            'auditoriaErroDetalhes'
        );


        limparDetalhesAuditoria();


        try {

            const resultado =
                await requisicaoAuditoria(
                    'buscar',
                    {
                        id
                    }
                );


            const auditoria =
                resultado.dados || {};


            preencherDetalhesAuditoria(
                auditoria
            );


            $('#auditoriaModalDetalhes')
                .modal('show');

        } catch (erro) {

            mostrarErroAuditoria(
                'auditoriaErroLista',
                erro.message
            );
        }
    }


    function preencherDetalhesAuditoria(
        auditoria
    ) {
        const operacao =
            String(
                auditoria.operacao
                || ''
            )
                .toUpperCase();


        atualizarTextoAuditoria(
            'auditoriaDetalheUsuario',
            auditoria.usuario_nome
            || 'Sistema'
        );


        const elementoOperacao =
            document.getElementById(
                'auditoriaDetalheOperacao'
            );


        if (elementoOperacao) {

            elementoOperacao.innerHTML =
                criarBadgeOperacaoAuditoria(
                    operacao
                );
        }


        atualizarTextoAuditoria(
            'auditoriaDetalheData',
            formatarDataHoraAuditoria(
                auditoria.criado_em
            )
        );


        const anteriores =
            objetoAuditoria(
                auditoria.dados_anteriores
            );


        const novos =
            objetoAuditoria(
                auditoria.dados_novos
            );


        if (
            operacao === 'UPDATE'
        ) {
            montarAlteracaoAuditoria(
                anteriores,
                novos
            );

            return;
        }


        if (
            operacao === 'INSERT'
        ) {
            montarCriacaoAuditoria(
                novos
            );

            return;
        }


        if (
            operacao === 'DELETE'
        ) {
            montarEliminacaoAuditoria(
                anteriores
            );

            return;
        }


        mostrarSemAlteracoesAuditoria();
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    function montarAlteracaoAuditoria(
        anteriores,
        novos
    ) {
        atualizarTextoAuditoria(
            'auditoriaDetalheTitulo',
            'Alterações realizadas'
        );


        const thead =
            document.getElementById(
                'auditoriaDetalheTabelaCabecalho'
            );


        const tbody =
            document.getElementById(
                'auditoriaDetalheTabelaBody'
            );


        if (
            !thead
            || !tbody
        ) {
            return;
        }


        thead.innerHTML = `
            <tr>
                <th>Campo</th>
                <th>Antes</th>
                <th>Depois</th>
            </tr>
        `;


        const campos =
            new Set([
                ...Object.keys(
                    anteriores
                ),
                ...Object.keys(
                    novos
                )
            ]);


        const alteracoes = [];


        campos.forEach(
            campo => {

                if (
                    ignorarCampoAuditoria(
                        campo
                    )
                ) {
                    return;
                }


                const antes =
                    anteriores[campo];

                const depois =
                    novos[campo];


                if (
                    valoresIguaisAuditoria(
                        antes,
                        depois
                    )
                ) {
                    return;
                }


                alteracoes.push({
                    campo,
                    antes,
                    depois
                });
            }
        );


        tbody.innerHTML = '';


        if (
            alteracoes.length === 0
        ) {
            mostrarSemAlteracoesAuditoria();

            return;
        }


        esconderSemAlteracoesAuditoria();


        tbody.innerHTML =
            alteracoes
                .map(
                    alteracao => `
                        <tr>

                            <td>
                                <strong>
                                    ${escaparHtmlAuditoria(
                        nomeCampoAuditoria(
                            alteracao.campo
                        )
                    )}
                                </strong>
                            </td>

                            <td>
                                ${formatarValorAuditoria(
                        alteracao.antes
                    )}
                            </td>

                            <td>
                                ${formatarValorAuditoria(
                        alteracao.depois
                    )}
                            </td>

                        </tr>
                    `
                )
                .join('');
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT
    |--------------------------------------------------------------------------
    */

    function montarCriacaoAuditoria(
        novos
    ) {
        atualizarTextoAuditoria(
            'auditoriaDetalheTitulo',
            'Dados criados'
        );


        montarTabelaDadosAuditoria(
            novos
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    function montarEliminacaoAuditoria(
        anteriores
    ) {
        atualizarTextoAuditoria(
            'auditoriaDetalheTitulo',
            'Dados eliminados'
        );


        montarTabelaDadosAuditoria(
            anteriores
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Tabela simples para INSERT/DELETE
    |--------------------------------------------------------------------------
    */

    function montarTabelaDadosAuditoria(
        dados
    ) {
        const thead =
            document.getElementById(
                'auditoriaDetalheTabelaCabecalho'
            );


        const tbody =
            document.getElementById(
                'auditoriaDetalheTabelaBody'
            );


        if (
            !thead
            || !tbody
        ) {
            return;
        }


        thead.innerHTML = `
            <tr>
                <th style="width: 35%;">
                    Campo
                </th>

                <th>
                    Valor
                </th>
            </tr>
        `;


        const linhas =
            Object.entries(
                dados
            )
                .filter(
                    ([campo]) =>
                        !ignorarCampoAuditoria(
                            campo
                        )
                );


        tbody.innerHTML = '';


        if (
            linhas.length === 0
        ) {
            mostrarSemAlteracoesAuditoria();

            return;
        }


        esconderSemAlteracoesAuditoria();


        tbody.innerHTML =
            linhas
                .map(
                    ([campo, valor]) => `
                        <tr>

                            <td>
                                <strong>
                                    ${escaparHtmlAuditoria(
                        nomeCampoAuditoria(
                            campo
                        )
                    )}
                                </strong>
                            </td>

                            <td>
                                ${formatarValorAuditoria(
                        valor
                    )}
                            </td>

                        </tr>
                    `
                )
                .join('');
    }


    /*
    |--------------------------------------------------------------------------
    | Campos que não queremos mostrar
    |--------------------------------------------------------------------------
    */

    function ignorarCampoAuditoria(
        campo
    ) {
        return [
            'id',
            'empresa_id'
        ].includes(
            String(campo)
                .toLowerCase()
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Nome amigável do campo
    |--------------------------------------------------------------------------
    */

    function nomeCampoAuditoria(
        campo
    ) {
        const nomes = {

            nome:
                'Nome',

            descricao:
                'Descrição',

            observacoes:
                'Observações',

            status:
                'Estado',

            status_id:
                'Estado',

            tipo:
                'Tipo',

            nuit:
                'NUIT',

            email:
                'Email',

            telefone:
                'Telefone',

            contacto:
                'Contacto',

            nome_comercial:
                'Nome Comercial',

            endereco:
                'Endereço',

            bairro:
                'Bairro',

            cidade:
                'Cidade',

            provincia:
                'Província',

            pais:
                'País',

            codigo:
                'Código',

            sku:
                'SKU',

            quantidade:
                'Quantidade',

            valor:
                'Valor',

            valor_total:
                'Valor Total',

            total:
                'Total',

            saldo:
                'Saldo',

            desconto:
                'Desconto',

            juros:
                'Juros',

            multa:
                'Multa',

            data:
                'Data',

            data_emissao:
                'Data de Emissão',

            data_vencimento:
                'Data de Vencimento',

            data_pagamento:
                'Data de Pagamento',

            criado_em:
                'Criado em',

            atualizado_em:
                'Atualizado em',

            filial_id:
                'Filial',

            centro_custo_id:
                'Centro de Custo',

            fornecedor_id:
                'Fornecedor',

            cliente_id:
                'Cliente',

            produto_id:
                'Produto',

            armazem_id:
                'Armazém',

            forma_pagamento_id:
                'Forma de Pagamento',

            caixa_id:
                'Caixa',

            banco_id:
                'Banco',

            conta_id:
                'Conta',

            conta_pagar_id:
                'Conta a Pagar',

            conta_receber_id:
                'Conta a Receber',

            numero_documento:
                'N.º Documento'
        };


        const chave =
            String(
                campo || ''
            );


        if (
            nomes[chave]
        ) {
            return nomes[chave];
        }


        return chave
            .replace(
                /_id$/i,
                ''
            )
            .replace(
                /_/g,
                ' '
            )
            .replace(
                /\b\w/g,
                letra =>
                    letra.toUpperCase()
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Formatação de valores
    |--------------------------------------------------------------------------
    */

    function formatarValorAuditoria(
        valor
    ) {
        if (
            valor === null
            || valor === undefined
            || valor === ''
        ) {
            return `
                <span class="text-muted">
                    —
                </span>
            `;
        }


        if (
            typeof valor === 'boolean'
        ) {
            return valor
                ? 'Sim'
                : 'Não';
        }


        if (
            typeof valor === 'object'
        ) {
            return `
                <pre class="mb-0 small">${escaparHtmlAuditoria(
                JSON.stringify(
                    valor,
                    null,
                    2
                )
            )}</pre>
            `;
        }


        return escaparHtmlAuditoria(
            String(valor)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Comparação
    |--------------------------------------------------------------------------
    */

    function valoresIguaisAuditoria(
        primeiro,
        segundo
    ) {
        if (
            primeiro === segundo
        ) {
            return true;
        }


        if (
            primeiro === null
            && (
                segundo === ''
                || segundo === undefined
            )
        ) {
            return true;
        }


        if (
            segundo === null
            && (
                primeiro === ''
                || primeiro === undefined
            )
        ) {
            return true;
        }


        if (
            typeof primeiro === 'object'
            || typeof segundo === 'object'
        ) {
            try {

                return JSON.stringify(
                    primeiro
                ) === JSON.stringify(
                    segundo
                );

            } catch (erro) {

                return false;
            }
        }


        return String(
            primeiro
        ) === String(
            segundo
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Operação
    |--------------------------------------------------------------------------
    */

    function criarBadgeOperacaoAuditoria(
        operacao
    ) {
        const valor =
            String(
                operacao || ''
            )
                .toUpperCase();


        let classe =
            'badge badge-secondary';


        if (
            valor === 'INSERT'
        ) {
            classe =
                'badge badge-success';
        }


        if (
            valor === 'UPDATE'
        ) {
            classe =
                'badge badge-info';
        }


        if (
            valor === 'DELETE'
        ) {
            classe =
                'badge badge-danger';
        }


        return `
            <span class="${classe}">
                ${escaparHtmlAuditoria(
            nomeOperacaoAuditoria(
                valor
            )
        )}
            </span>
        `;
    }


    function nomeOperacaoAuditoria(
        operacao
    ) {
        switch (
        String(
            operacao || ''
        )
            .toUpperCase()
        ) {

            case 'INSERT':
                return 'Criação';

            case 'UPDATE':
                return 'Alteração';

            case 'DELETE':
                return 'Eliminação';

            default:
                return operacao || '-';
        }
    }


    function textoBotaoAuditoria(
        operacao
    ) {
        switch (
        String(
            operacao || ''
        )
            .toUpperCase()
        ) {

            case 'INSERT':
                return 'Ver dados';

            case 'DELETE':
                return 'Ver dados';

            default:
                return 'Ver alterações';
        }
    }


    function tituloBotaoAuditoria(
        operacao
    ) {
        switch (
        String(
            operacao || ''
        )
            .toUpperCase()
        ) {

            case 'INSERT':
                return 'Ver dados criados';

            case 'DELETE':
                return 'Ver dados eliminados';

            default:
                return 'Ver alterações realizadas';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Data
    |--------------------------------------------------------------------------
    */

    function formatarDataHoraAuditoria(
        valor
    ) {
        if (!valor) {
            return '-';
        }


        const partes =
            String(valor)
                .split(' ');


        if (
            partes.length < 2
        ) {
            return escaparHtmlAuditoria(
                valor
            );
        }


        const data =
            partes[0]
                .split('-');


        if (
            data.length !== 3
        ) {
            return escaparHtmlAuditoria(
                valor
            );
        }


        return escaparHtmlAuditoria(
            `${data[2]}/${data[1]}/${data[0]} ${partes[1]}`
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DataTable
    |--------------------------------------------------------------------------
    */

    function destruirDataTableAuditoria() {

        if (
            typeof $ !== 'undefined'
            && $.fn.DataTable
            && $.fn.DataTable.isDataTable(
                '#auditoriaTabela'
            )
        ) {
            $('#auditoriaTabela')
                .DataTable()
                .destroy();
        }


        auditoriaDataTable =
            null;
    }


    function iniciarDataTableAuditoria() {

        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }


        if (
            $.fn.DataTable.isDataTable(
                '#auditoriaTabela'
            )
        ) {
            $('#auditoriaTabela')
                .DataTable()
                .destroy();
        }


        auditoriaDataTable =
            $('#auditoriaTabela')
                .DataTable({

                    pageLength:
                        10,


                    lengthMenu: [
                        [5, 10, 25, 50, 100],
                        [5, 10, 25, 50, 100]
                    ],


                    responsive:
                        true,


                    autoWidth:
                        false,


                    order: [
                        [0, 'desc']
                    ],


                    columnDefs: [
                        {
                            targets: [3],

                            orderable:
                                false,

                            searchable:
                                false
                        }
                    ],


                    language: {

                        emptyTable:
                            'Nenhum registo de auditoria encontrado.',


                        zeroRecords:
                            'Nenhum registo de auditoria encontrado.',


                        search:
                            'Pesquisar:',


                        lengthMenu:
                            'Mostrar _MENU_ registos',


                        info:
                            'Mostrando _START_ a _END_ de _TOTAL_ registos',


                        infoEmpty:
                            'Mostrando 0 a 0 de 0 registos',


                        infoFiltered:
                            '(filtrado de _MAX_ registos)',


                        paginate: {

                            first:
                                'Primeiro',

                            last:
                                'Último',

                            next:
                                'Seguinte',

                            previous:
                                'Anterior'
                        }
                    }
                });
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    function objetoAuditoria(
        valor
    ) {
        if (
            !valor
            || typeof valor
            !== 'object'
            || Array.isArray(
                valor
            )
        ) {
            return {};
        }


        return valor;
    }


    function limparDetalhesAuditoria() {

        atualizarTextoAuditoria(
            'auditoriaDetalheUsuario',
            '-'
        );


        atualizarTextoAuditoria(
            'auditoriaDetalheData',
            '-'
        );


        atualizarTextoAuditoria(
            'auditoriaDetalheTitulo',
            'Alterações realizadas'
        );


        const operacao =
            document.getElementById(
                'auditoriaDetalheOperacao'
            );


        if (operacao) {
            operacao.textContent = '-';
        }


        const thead =
            document.getElementById(
                'auditoriaDetalheTabelaCabecalho'
            );


        if (thead) {
            thead.innerHTML = '';
        }


        const tbody =
            document.getElementById(
                'auditoriaDetalheTabelaBody'
            );


        if (tbody) {
            tbody.innerHTML = '';
        }


        esconderSemAlteracoesAuditoria();


        esconderErroAuditoria(
            'auditoriaErroDetalhes'
        );
    }


    function mostrarSemAlteracoesAuditoria() {

        const elemento =
            document.getElementById(
                'auditoriaSemAlteracoes'
            );


        if (elemento) {
            elemento.classList.remove(
                'd-none'
            );
        }
    }


    function esconderSemAlteracoesAuditoria() {

        const elemento =
            document.getElementById(
                'auditoriaSemAlteracoes'
            );


        if (elemento) {
            elemento.classList.add(
                'd-none'
            );
        }
    }


    function atualizarTextoAuditoria(
        id,
        valor
    ) {
        const elemento =
            document.getElementById(
                id
            );


        if (elemento) {
            elemento.textContent =
                valor ?? '';
        }
    }


    function mostrarErroAuditoria(
        id,
        mensagem
    ) {
        const elemento =
            document.getElementById(
                id
            );


        if (!elemento) {

            console.error(
                mensagem
            );

            return;
        }


        elemento.textContent =
            mensagem;


        elemento.classList.remove(
            'd-none'
        );
    }


    function esconderErroAuditoria(
        id
    ) {
        const elemento =
            document.getElementById(
                id
            );


        if (!elemento) {
            return;
        }


        elemento.textContent = '';


        elemento.classList.add(
            'd-none'
        );
    }


    function escaparHtmlAuditoria(
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

})();