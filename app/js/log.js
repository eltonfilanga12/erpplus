'use strict';

(() => {

    const LOG_URL =
        '/erpplus/app/ajax/log_ajax.php';

    let logs = [];

    let logDataTable = null;

    let logModuloInicializado =
        false;


    if (
        document.readyState
        === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarModuloLogs
        );
    } else {
        iniciarModuloLogs();
    }


    async function iniciarModuloLogs() {

        if (
            logModuloInicializado
        ) {
            return;
        }


        if (
            !document.getElementById(
                'logTabela'
            )
        ) {
            return;
        }


        logModuloInicializado =
            true;


        configurarEventosLog();


        await carregarFiltrosLog();

        await carregarLogs();
    }


    function configurarEventosLog() {

        document
            .getElementById(
                'logFormFiltros'
            )
            ?.addEventListener(
                'submit',
                evento => {

                    evento.preventDefault();

                    carregarLogs();
                }
            );


        document
            .getElementById(
                'logBtnAtualizar'
            )
            ?.addEventListener(
                'click',
                carregarLogs
            );


        document
            .getElementById(
                'logBtnLimpar'
            )
            ?.addEventListener(
                'click',
                limparFiltrosLog
            );
    }


    async function requisicaoLog(
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
            LOG_URL
            + '?'
            + query.toString();


        let resposta;


        try {

            resposta =
                await fetch(
                    url,
                    {
                        method: 'GET',
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


    async function carregarFiltrosLog() {

        try {

            const resultado =
                await requisicaoLog(
                    'filtros'
                );


            const dados =
                resultado.dados || {};


            preencherUtilizadoresLog(
                Array.isArray(
                    dados.utilizadores
                )
                    ? dados.utilizadores
                    : []
            );


            preencherAcoesLog(
                Array.isArray(
                    dados.acoes
                )
                    ? dados.acoes
                    : []
            );

        } catch (erro) {

            console.error(
                'Erro ao carregar filtros:',
                erro
            );
        }
    }


    function preencherUtilizadoresLog(
        utilizadores
    ) {
        const select =
            document.getElementById(
                'logUsuarioId'
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


    function preencherAcoesLog(
        acoes
    ) {
        const select =
            document.getElementById(
                'logAcao'
            );


        if (!select) {
            return;
        }


        select.replaceChildren();


        select.add(
            new Option(
                'Todas',
                ''
            )
        );


        acoes.forEach(
            acao => {

                select.add(
                    new Option(
                        formatarAcaoLog(
                            acao
                        ),
                        String(
                            acao
                        )
                    )
                );
            }
        );
    }


    async function carregarLogs() {

        const tbody =
            document.getElementById(
                'logTabelaBody'
            );


        if (!tbody) {
            return;
        }


        esconderErroLog(
            'logErroLista'
        );


        const parametros =
            obterFiltrosLog();


        try {

            const resultado =
                await requisicaoLog(
                    'listar',
                    parametros
                );


            logs =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];


            /*
             * Muito importante:
             * destrói primeiro o DataTable.
             */
            destruirDataTableLog();


            /*
             * Nunca colocamos:
             *
             * <td colspan="5">
             *
             * Isso evita o erro:
             * _DT_CellIndex
             */
            tbody.innerHTML = '';


            if (
                logs.length > 0
            ) {
                tbody.innerHTML =
                    logs
                        .map(
                            criarLinhaLog
                        )
                        .join('');
            }


            iniciarDataTableLog();

        } catch (erro) {

            destruirDataTableLog();

            tbody.innerHTML = '';


            mostrarErroLog(
                'logErroLista',
                erro.message
            );
        }
    }


    function criarLinhaLog(
        log
    ) {
        return `
            <tr>

                <td data-order="${escaparHtmlLog(
            log.criado_em || ''
        )}">
                    ${formatarDataHoraLog(
            log.criado_em
        )}
                </td>

                <td>
                    <strong>
                        ${escaparHtmlLog(
            log.usuario_nome
            || 'Sistema'
        )}
                    </strong>
                </td>

                <td>
                    ${criarBadgeAcaoLog(
            log.acao
        )}
                </td>

                <td>
                    ${escaparHtmlLog(
            log.descricao
            || '-'
        )}
                </td>

                <td>
                    ${escaparHtmlLog(
            log.ip
            || '-'
        )}
                </td>

            </tr>
        `;
    }


    function obterFiltrosLog() {

        const filtros = {};


        const dataInicial =
            document
                .getElementById(
                    'logDataInicial'
                )
                ?.value
                ?.trim()
            || '';


        const dataFinal =
            document
                .getElementById(
                    'logDataFinal'
                )
                ?.value
                ?.trim()
            || '';


        const usuarioId =
            document
                .getElementById(
                    'logUsuarioId'
                )
                ?.value
                ?.trim()
            || '';


        const acao =
            document
                .getElementById(
                    'logAcao'
                )
                ?.value
                ?.trim()
            || '';


        const pesquisa =
            document
                .getElementById(
                    'logPesquisa'
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


        if (acao) {
            filtros.acao_log =
                acao;
        }


        if (pesquisa) {
            filtros.pesquisa =
                pesquisa;
        }


        return filtros;
    }


    async function limparFiltrosLog() {

        document
            .getElementById(
                'logFormFiltros'
            )
            ?.reset();


        await carregarLogs();
    }


    function criarBadgeAcaoLog(
        acao
    ) {
        const valor =
            String(
                acao || ''
            )
                .trim()
                .toUpperCase();


        let classe =
            'badge badge-secondary';


        if (
            [
                'CRIAR',
                'ABRIR',
                'RECEBER'
            ].includes(
                valor
            )
        ) {
            classe =
                'badge badge-success';
        }


        if (
            [
                'EDITAR',
                'ATUALIZAR',
                'CONTABILIZAR',
                'PAGAR',
                'TRANSFERIR'
            ].includes(
                valor
            )
        ) {
            classe =
                'badge badge-info';
        }


        if (
            [
                'ELIMINAR',
                'CANCELAR',
                'ESTORNAR'
            ].includes(
                valor
            )
        ) {
            classe =
                'badge badge-danger';
        }


        return `
            <span class="${classe}">
                ${escaparHtmlLog(
            formatarAcaoLog(
                valor
            )
        )}
            </span>
        `;
    }


    function formatarAcaoLog(
        acao
    ) {
        const texto =
            String(
                acao || ''
            )
                .trim()
                .replace(
                    /_/g,
                    ' '
                )
                .toLowerCase();


        if (!texto) {
            return '-';
        }


        return texto
            .replace(
                /\b\w/g,
                letra =>
                    letra.toUpperCase()
            );
    }


    function formatarDataHoraLog(
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
            return escaparHtmlLog(
                valor
            );
        }


        const data =
            partes[0]
                .split('-');


        if (
            data.length !== 3
        ) {
            return escaparHtmlLog(
                valor
            );
        }


        return escaparHtmlLog(
            `${data[2]}/${data[1]}/${data[0]} ${partes[1]}`
        );
    }


    function destruirDataTableLog() {

        if (
            typeof $ !== 'undefined'
            && $.fn.DataTable
            && $.fn.DataTable.isDataTable(
                '#logTabela'
            )
        ) {
            $('#logTabela')
                .DataTable()
                .destroy();
        }


        logDataTable = null;
    }


    function iniciarDataTableLog() {

        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }


        if (
            $.fn.DataTable.isDataTable(
                '#logTabela'
            )
        ) {
            $('#logTabela')
                .DataTable()
                .destroy();
        }


        logDataTable =
            $('#logTabela')
                .DataTable({

                    pageLength: 10,

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

                    language: {

                        emptyTable:
                            'Nenhum log encontrado.',

                        zeroRecords:
                            'Nenhum log encontrado.',

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


    function mostrarErroLog(
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


    function esconderErroLog(
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


    function escaparHtmlLog(
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