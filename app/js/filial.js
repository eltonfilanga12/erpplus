'use strict';

(() => {
    const FILIAL_URL =
        '/erpplus/app/ajax/filial_ajax.php';

    let filiais = [];
    let filialTabela = null;
    let filialModuloInicializado = false;

    /*
    |--------------------------------------------------------------------------
    | Inicialização
    |--------------------------------------------------------------------------
    */

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarModuloFiliais
        );
    } else {
        iniciarModuloFiliais();
    }

    async function iniciarModuloFiliais() {
        if (filialModuloInicializado) {
            return;
        }

        const tabela =
            document.getElementById(
                'filialTabela'
            );

        if (!tabela) {
            return;
        }

        filialModuloInicializado = true;

        configurarEventosFilial();

        await Promise.all([
            carregarStatusFilial(),
            carregarFiliais()
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Eventos
    |--------------------------------------------------------------------------
    */

    function configurarEventosFilial() {
        document
            .getElementById(
                'filialBtnNova'
            )
            ?.addEventListener(
                'click',
                abrirNovaFilial
            );

        document
            .getElementById(
                'filialForm'
            )
            ?.addEventListener(
                'submit',
                guardarFilial
            );

        document
            .getElementById(
                'filialFormEliminar'
            )
            ?.addEventListener(
                'submit',
                eliminarFilial
            );

        document.addEventListener(
            'click',
            processarAcaoFilial
        );
    }

    function processarAcaoFilial(
        evento
    ) {
        const botao =
            evento.target.closest(
                '[data-filial-acao]'
            );

        if (!botao) {
            return;
        }

        const id =
            Number(
                botao.dataset.filialId
            );

        if (!id) {
            return;
        }

        const acao =
            botao.dataset.filialAcao;

        if (acao === 'editar') {
            editarFilial(id);
        }

        if (acao === 'eliminar') {
            abrirEliminarFilial(id);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Requisição
    |--------------------------------------------------------------------------
    */

    async function requisicaoFilial(
        acao,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        let url =
            FILIAL_URL;

        const configuracao = {
            method: metodo,
            credentials: 'same-origin',
            cache: 'no-store'
        };

        if (metodo === 'GET') {
            const parametros =
                new URLSearchParams(
                    opcoes.parametros || {}
                );

            parametros.set(
                'acao',
                acao
            );

            parametros.set(
                '_',
                String(Date.now())
            );

            url +=
                '?' + parametros.toString();
        } else {
            const dados =
                opcoes.dados
                    instanceof FormData
                    ? opcoes.dados
                    : new FormData();

            if (!dados.has('acao')) {
                dados.append(
                    'acao',
                    acao
                );
            }

            configuracao.body =
                dados;
        }

        let resposta;

        try {
            resposta =
                await fetch(
                    url,
                    configuracao
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
                JSON.parse(texto);
        } catch (erro) {
            console.error(
                'Resposta inválida do servidor:',
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
    | Listar
    |--------------------------------------------------------------------------
    */

    async function carregarFiliais() {
        const tbody =
            document.getElementById(
                'filialTabelaBody'
            );

        if (!tbody) {
            return;
        }

        esconderErroFilial(
            'filialErroLista'
        );

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="9"
                    class="text-center"
                >
                    <i class="fa fa-spinner fa-spin"></i>
                    A carregar filiais...
                </td>
            </tr>
        `;

        try {
            const resultado =
                await requisicaoFilial(
                    'listar'
                );

            filiais =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            destruirTabelaFilial();

            renderizarFiliais();

            iniciarTabelaFilial();
        } catch (erro) {
            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="9"
                        class="text-center text-danger"
                    >
                        ${escaparHtmlFilial(
                erro.message
            )}
                    </td>
                </tr>
            `;

            mostrarErroFilial(
                'filialErroLista',
                erro.message
            );
        }
    }

    function renderizarFiliais() {

        const tbody =
            document.getElementById(
                'filialTabelaBody'
            );

        if (!tbody) {
            return;
        }

        // Sempre limpar primeiro.
        // DataTables não deve receber linhas com colspan no tbody.
        tbody.innerHTML = '';

        if (!filiais.length) {
            return;
        }

        tbody.innerHTML =
            filiais
                .map(
                    (filial, indice) =>
                        criarLinhaFilial(
                            filial,
                            indice
                        )
                )
                .join('');
    }

    function criarLinhaFilial(
        filial,
        indice
    ) {
        const id =
            Number(filial.id);

        const numero =
            String(indice + 1)
                .padStart(4, '0');

        const contacto =
            filial.telefone
            || filial.email
            || '-';

        const localizacao =
            [
                filial.bairro,
                filial.cidade,
                filial.provincia
            ]
                .filter(Boolean)
                .join(', ')
            || filial.endereco
            || '-';

        return `
            <tr>
                <td>
                    ${numero}
                </td>

                <td>
                    <strong>
                        ${escaparHtmlFilial(
            filial.nome || '-'
        )}
                    </strong>

                    ${filial.email
                ? `
                                <div class="small text-muted">
                                    ${escaparHtmlFilial(
                    filial.email
                )}
                                </div>
                            `
                : ''
            }
                </td>

                <td>
                    ${escaparHtmlFilial(
                filial.codigo || '-'
            )}
                </td>

                <td>
                    ${escaparHtmlFilial(
                contacto
            )}
                </td>

                <td>
                    ${escaparHtmlFilial(
                localizacao
            )}
                </td>

                <td>
                    ${escaparHtmlFilial(
                filial.gerente || '-'
            )}
                </td>

                <td>
                    ${criarBadgeFilial(
                filial.status_nome
            )}
                </td>

                <td>
                    ${formatarDataFilial(
                filial.criado_em
            )}
                </td>

                <td class="text-right">
                    <button
                        type="button"
                        class="btn btn-sm btn-info"
                        title="Editar"
                        data-filial-acao="editar"
                        data-filial-id="${id}"
                    >
                        <i class="fa fa-pencil"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        title="Eliminar"
                        data-filial-acao="eliminar"
                        data-filial-id="${id}"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    async function carregarStatusFilial() {
        const select =
            document.getElementById(
                'filialStatusId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {
            const resultado =
                await requisicaoFilial(
                    'status'
                );

            const estados =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            select.replaceChildren();

            select.add(
                new Option(
                    estados.length
                        ? 'Selecione o estado'
                        : 'Nenhum estado disponível',
                    ''
                )
            );

            estados.forEach(estado => {
                select.add(
                    new Option(
                        estado.nome,
                        String(estado.id)
                    )
                );
            });

            const activo =
                estados.find(estado => {
                    const nome =
                        normalizarTextoFilial(
                            estado.nome
                        );

                    return nome === 'activa'
                        || nome === 'ativa';
                });

            if (activo) {
                select.dataset.activoId =
                    String(activo.id);
            }
        } catch (erro) {
            select.replaceChildren(
                new Option(
                    'Erro ao carregar estados',
                    ''
                )
            );

            console.error(
                'Erro ao carregar estados:',
                erro
            );
        } finally {
            select.disabled = false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Nova
    |--------------------------------------------------------------------------
    */

    function abrirNovaFilial() {
        limparFormularioFilial();

        atualizarTextoFilial(
            'filialModalTitulo',
            'Nova Filial'
        );

        const status =
            document.getElementById(
                'filialStatusId'
            );

        if (
            status
            && status.dataset.activoId
        ) {
            status.value =
                status.dataset.activoId;
        }

        $('#filialModal')
            .modal('show');
    }

    /*
    |--------------------------------------------------------------------------
    | Editar
    |--------------------------------------------------------------------------
    */

    async function editarFilial(id) {
        esconderErroFilial(
            'filialErroFormulario'
        );

        try {
            const resultado =
                await requisicaoFilial(
                    'buscar',
                    {
                        parametros: {
                            id
                        }
                    }
                );

            const filial =
                resultado.dados || {};

            limparFormularioFilial();

            definirValorFilial(
                'filialId',
                filial.id
            );

            definirValorFilial(
                'filialNome',
                filial.nome
            );

            definirValorFilial(
                'filialCodigo',
                filial.codigo
            );

            definirValorFilial(
                'filialStatusId',
                filial.status_id
            );

            definirValorFilial(
                'filialGerente',
                filial.gerente
            );

            definirValorFilial(
                'filialEmail',
                filial.email
            );

            definirValorFilial(
                'filialTelefone',
                filial.telefone
            );

            definirValorFilial(
                'filialProvincia',
                filial.provincia
            );

            definirValorFilial(
                'filialCidade',
                filial.cidade
            );

            definirValorFilial(
                'filialBairro',
                filial.bairro
            );

            definirValorFilial(
                'filialEndereco',
                filial.endereco
            );

            atualizarTextoFilial(
                'filialModalTitulo',
                'Editar Filial'
            );

            $('#filialModal')
                .modal('show');
        } catch (erro) {
            mostrarMensagemErroFilial(
                erro.message
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    async function guardarFilial(
        evento
    ) {
        evento.preventDefault();

        esconderErroFilial(
            'filialErroFormulario'
        );

        const formulario =
            evento.currentTarget;

        const botao =
            document.getElementById(
                'filialBtnGuardar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoFilial(
                botao,
                'A guardar...'
            );

            const resultado =
                await requisicaoFilial(
                    'salvar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                formulario
                            )
                    }
                );

            retirarFocoFilial();

            $('#filialModal')
                .modal('hide');

            mostrarMensagemSucessoFilial(
                resultado.mensagem
                || 'Filial guardada com sucesso.'
            );

            await carregarFiliais();
        } catch (erro) {
            mostrarErroFilial(
                'filialErroFormulario',
                erro.message
            );
        } finally {
            desbloquearBotaoFilial(
                botao,
                htmlOriginal
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    function abrirEliminarFilial(id) {
        const filial =
            filiais.find(
                item =>
                    Number(item.id)
                    === Number(id)
            );

        definirValorFilial(
            'filialEliminarId',
            id
        );

        atualizarTextoFilial(
            'filialEliminarNome',
            filial?.nome || ''
        );

        esconderErroFilial(
            'filialErroEliminar'
        );

        $('#filialModalEliminar')
            .modal('show');
    }

    async function eliminarFilial(
        evento
    ) {
        evento.preventDefault();

        esconderErroFilial(
            'filialErroEliminar'
        );

        const botao =
            document.getElementById(
                'filialBtnEliminar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoFilial(
                botao,
                'A eliminar...'
            );

            const resultado =
                await requisicaoFilial(
                    'eliminar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );

            retirarFocoFilial();

            $('#filialModalEliminar')
                .modal('hide');

            mostrarMensagemSucessoFilial(
                resultado.mensagem
                || 'Filial eliminada com sucesso.'
            );

            await carregarFiliais();
        } catch (erro) {
            mostrarErroFilial(
                'filialErroEliminar',
                erro.message
            );
        } finally {
            desbloquearBotaoFilial(
                botao,
                htmlOriginal
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DataTable
    |--------------------------------------------------------------------------
    */

    function destruirTabelaFilial() {
        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        if (
            $.fn.DataTable.isDataTable(
                '#filialTabela'
            )
        ) {
            $('#filialTabela')
                .DataTable()
                .destroy();
        }

        filialTabela = null;
    }

    function iniciarTabelaFilial() {

        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        // Segurança contra inicialização duplicada
        if (
            $.fn.DataTable.isDataTable(
                '#filialTabela'
            )
        ) {
            $('#filialTabela')
                .DataTable()
                .destroy();
        }

        filialTabela =
            $('#filialTabela')
                .DataTable({

                    pageLength: 10,

                    lengthMenu: [
                        [5, 10, 25, 50, 100],
                        [5, 10, 25, 50, 100]
                    ],

                    responsive: true,

                    autoWidth: false,

                    order: [
                        [1, 'asc']
                    ],

                    columnDefs: [
                        {
                            targets: [8],
                            orderable: false,
                            searchable: false
                        }
                    ],

                    language: {

                        emptyTable:
                            'Nenhuma filial encontrada.',

                        zeroRecords:
                            'Nenhuma filial encontrada.',

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

                        loadingRecords:
                            'A carregar...',

                        processing:
                            'A processar...',

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
                    },

                    dom:
                        "<'row'<'col-sm-12 col-md-8 d-flex align-items-center'Bl><'col-sm-12 col-md-4'f>>"
                        + "<'row'<'col-sm-12'tr>>"
                        + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",

                    buttons: [
                        {
                            extend:
                                'excelHtml5',

                            text:
                                '<i class="fa fa-file-excel"></i> Excel',

                            title:
                                'Lista de Filiais',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7]
                            }
                        },

                        {
                            extend:
                                'pdfHtml5',

                            text:
                                '<i class="fa fa-file-pdf"></i> PDF',

                            title:
                                'Lista de Filiais',

                            orientation:
                                'landscape',

                            pageSize:
                                'A4',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7]
                            }
                        },

                        {
                            extend:
                                'print',

                            text:
                                '<i class="fa fa-print"></i> Imprimir',

                            title:
                                'Lista de Filiais',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7]
                            }
                        }
                    ]
                });
    }

    /*
    |--------------------------------------------------------------------------
    | Formulário
    |--------------------------------------------------------------------------
    */

    function limparFormularioFilial() {
        const formulario =
            document.getElementById(
                'filialForm'
            );

        formulario?.reset();

        definirValorFilial(
            'filialId',
            ''
        );

        esconderErroFilial(
            'filialErroFormulario'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Auxiliares
    |--------------------------------------------------------------------------
    */

    function definirValorFilial(
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

    function atualizarTextoFilial(
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

    function mostrarErroFilial(
        id,
        mensagem
    ) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            mostrarMensagemErroFilial(
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

    function esconderErroFilial(id) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            return;
        }

        elemento.textContent = '';

        elemento.classList.add(
            'd-none'
        );
    }

    function bloquearBotaoFilial(
        botao,
        texto
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = true;

        botao.innerHTML = `
            <i class="fa fa-spinner fa-spin"></i>
            ${escaparHtmlFilial(texto)}
        `;
    }

    function desbloquearBotaoFilial(
        botao,
        html
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = false;
        botao.innerHTML = html;
    }

    function retirarFocoFilial() {
        if (
            document.activeElement
            instanceof HTMLElement
        ) {
            document.activeElement.blur();
        }
    }

    function normalizarTextoFilial(
        valor
    ) {
        return String(valor || '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            );
    }

    function escaparHtmlFilial(
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

    function formatarDataFilial(
        valor
    ) {
        if (!valor) {
            return '-';
        }

        const data =
            new Date(
                String(valor)
                    .replace(' ', 'T')
            );

        if (
            Number.isNaN(
                data.getTime()
            )
        ) {
            return escaparHtmlFilial(
                valor
            );
        }

        return data.toLocaleString(
            'pt-MZ',
            {
                dateStyle: 'short',
                timeStyle: 'short'
            }
        );
    }

    function criarBadgeFilial(
        status
    ) {
        const nome =
            String(status || '-');

        const normalizado =
            normalizarTextoFilial(
                nome
            );

        let classe =
            'badge-secondary';

        if (
            normalizado === 'activa'
            || normalizado === 'ativa'
        ) {
            classe =
                'badge-success';
        } else if (
            normalizado === 'inactiva'
            || normalizado === 'inativa'
        ) {
            classe =
                'badge-secondary';
        } else if (
            normalizado === 'suspensa'
        ) {
            classe =
                'badge-warning';
        } else if (
            normalizado === 'encerrada'
        ) {
            classe =
                'badge-danger';
        }

        return `
            <span class="badge ${classe}">
                ${escaparHtmlFilial(nome)}
            </span>
        `;
    }

    function mostrarMensagemSucessoFilial(
        mensagem
    ) {
        if (
            typeof Swal !== 'undefined'
        ) {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso',
                text: mensagem,
                timer: 2200,
                showConfirmButton: false
            });

            return;
        }

        console.log(mensagem);
    }

    function mostrarMensagemErroFilial(
        mensagem
    ) {
        if (
            typeof Swal !== 'undefined'
        ) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: mensagem
            });

            return;
        }

        console.error(mensagem);
    }
})();