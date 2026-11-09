'use strict';

(() => {
    const PERFILAJAX_URL =
        '/erpplus/app/ajax/perfil_ajax.php';

    let perfisModuloDados = [];
    let perfisModuloTabela = null;
    let perfisModuloInicializado = false;

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
            iniciarModuloPerfis
        );
    } else {
        iniciarModuloPerfis();
    }

    async function iniciarModuloPerfis() {
        if (perfisModuloInicializado) {
            return;
        }

        const tabela =
            document.getElementById(
                'perfilTabela'
            );

        if (!tabela) {
            return;
        }

        perfisModuloInicializado = true;

        configurarEventosPerfis();

        await Promise.all([
            carregarStatusPerfis(),
            carregarListaPerfis()
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Eventos
    |--------------------------------------------------------------------------
    */

    function configurarEventosPerfis() {
        const btnNovo =
            document.getElementById(
                'perfilBtnNovo'
            );

        const formulario =
            document.getElementById(
                'perfilForm'
            );

        const formularioEliminar =
            document.getElementById(
                'perfilFormEliminar'
            );

        if (btnNovo) {
            btnNovo.addEventListener(
                'click',
                abrirNovoPerfil
            );
        }

        if (formulario) {
            formulario.addEventListener(
                'submit',
                guardarPerfil
            );
        }

        if (formularioEliminar) {
            formularioEliminar.addEventListener(
                'submit',
                eliminarPerfil
            );
        }

        document.addEventListener(
            'click',
            processarCliquePerfil
        );
    }

    function processarCliquePerfil(evento) {
        const botao =
            evento.target.closest(
                '[data-perfil-acao]'
            );

        if (!botao) {
            return;
        }

        const id =
            Number(
                botao.dataset.perfilId
            );

        if (!id) {
            return;
        }

        const acao =
            botao.dataset.perfilAcao;

        if (acao === 'editar') {
            editarPerfil(id);
        }

        if (acao === 'eliminar') {
            abrirEliminarPerfil(id);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Requisições
    |--------------------------------------------------------------------------
    */

    async function requisicaoPerfil(
        acao,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        let url =
            PERFILAJAX_URL;

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
                'Resposta inválida do perfil:',
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
    | Listar perfis
    |--------------------------------------------------------------------------
    */

    async function carregarListaPerfis() {
        const tbody =
            document.getElementById(
                'perfilTabelaBody'
            );

        if (!tbody) {
            return;
        }

        esconderErroPerfil(
            'perfilErroLista'
        );

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="6"
                    class="text-center"
                >
                    <i class="fa fa-spinner fa-spin"></i>
                    A carregar perfis...
                </td>
            </tr>
        `;

        try {
            const resultado =
                await requisicaoPerfil(
                    'listar'
                );

            perfisModuloDados =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            destruirTabelaPerfis();

            renderizarPerfis();

            iniciarTabelaPerfis();
        } catch (erro) {
            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="6"
                        class="text-center text-danger"
                    >
                        ${escaparHtmlPerfil(
                            erro.message
                        )}
                    </td>
                </tr>
            `;

            mostrarErroPerfil(
                'perfilErroLista',
                erro.message
            );
        }
    }

    function renderizarPerfis() {
        const tbody =
            document.getElementById(
                'perfilTabelaBody'
            );

        if (!tbody) {
            return;
        }

        if (perfisModuloDados.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="6"
                        class="text-center text-muted"
                    >
                        Nenhum perfil encontrado.
                    </td>
                </tr>
            `;

            return;
        }

        tbody.innerHTML =
            perfisModuloDados
                .map(criarLinhaPerfil)
                .join('');
    }

    function criarLinhaPerfil(perfil) {
        const id =
            Number(perfil.id);

        const protegido =
            id === 1;

        return `
            <tr>
                <td>
                    ${id}
                </td>

                <td>
                    <strong>
                        ${escaparHtmlPerfil(
                            perfil.nome || '-'
                        )}
                    </strong>
                </td>

                <td>
                    ${escaparHtmlPerfil(
                        perfil.descricao || '-'
                    )}
                </td>

                <td>
                    ${criarBadgePerfil(
                        perfil.status_nome
                    )}
                </td>

                <td>
                    ${formatarDataPerfil(
                        perfil.criado_em
                    )}
                </td>

                <td class="text-right">
                    <button
                        type="button"
                        class="btn btn-sm btn-info"
                        title="Editar"
                        data-perfil-acao="editar"
                        data-perfil-id="${id}"
                    >
                        <i class="fa fa-pencil"></i>
                    </button>

                    ${
                        protegido
                            ? ''
                            : `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger"
                                    title="Eliminar"
                                    data-perfil-acao="eliminar"
                                    data-perfil-id="${id}"
                                >
                                    <i class="fa fa-trash"></i>
                                </button>
                            `
                    }
                </td>
            </tr>
        `;
    }

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    async function carregarStatusPerfis() {
        const select =
            document.getElementById(
                'perfilStatusId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {
            const resultado =
                await requisicaoPerfil(
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
                    estados.length > 0
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
                estados.find(
                    estado => {
                        const nome =
                            normalizarTextoPerfil(
                                estado.nome
                            );

                        return nome === 'activo'
                            || nome === 'ativo';
                    }
                );

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
                'Erro ao carregar estados dos perfis:',
                erro
            );
        } finally {
            select.disabled = false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Novo
    |--------------------------------------------------------------------------
    */

    async function abrirNovoPerfil() {
        limparFormularioPerfil();

        atualizarTextoPerfil(
            'perfilModalTitulo',
            'Novo Perfil'
        );

        const status =
            document.getElementById(
                'perfilStatusId'
            );

        if (
            status
            && status.dataset.activoId
        ) {
            status.value =
                status.dataset.activoId;
        }

        $('#perfilModal')
            .modal('show');
    }

    /*
    |--------------------------------------------------------------------------
    | Editar
    |--------------------------------------------------------------------------
    */

    async function editarPerfil(id) {
        esconderErroPerfil(
            'perfilErroFormulario'
        );

        try {
            const resultado =
                await requisicaoPerfil(
                    'buscar',
                    {
                        parametros: {
                            id
                        }
                    }
                );

            const perfil =
                resultado.dados || {};

            limparFormularioPerfil();

            definirValorPerfil(
                'perfilId',
                perfil.id
            );

            definirValorPerfil(
                'perfilNome',
                perfil.nome
            );

            definirValorPerfil(
                'perfilDescricao',
                perfil.descricao
            );

            definirValorPerfil(
                'perfilStatusId',
                perfil.status_id
            );

            atualizarTextoPerfil(
                'perfilModalTitulo',
                'Editar Perfil'
            );

            $('#perfilModal')
                .modal('show');
        } catch (erro) {
            mostrarMensagemErroPerfil(
                erro.message
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    async function guardarPerfil(evento) {
        evento.preventDefault();

        esconderErroPerfil(
            'perfilErroFormulario'
        );

        const formulario =
            evento.currentTarget;

        const botao =
            document.getElementById(
                'perfilBtnGuardar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoPerfil(
                botao,
                'A guardar...'
            );

            const dados =
                new FormData(
                    formulario
                );

            const resultado =
                await requisicaoPerfil(
                    'guardar',
                    {
                        method: 'POST',
                        dados
                    }
                );

            retirarFocoPerfil();

            $('#perfilModal')
                .modal('hide');

            mostrarMensagemSucessoPerfil(
                resultado.mensagem
                || 'Perfil guardado com sucesso.'
            );

            await carregarListaPerfis();
        } catch (erro) {
            mostrarErroPerfil(
                'perfilErroFormulario',
                erro.message
            );
        } finally {
            desbloquearBotaoPerfil(
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

    function abrirEliminarPerfil(id) {
        const perfil =
            perfisModuloDados.find(
                item =>
                    Number(item.id)
                    === Number(id)
            );

        definirValorPerfil(
            'perfilEliminarId',
            id
        );

        atualizarTextoPerfil(
            'perfilEliminarNome',
            perfil?.nome || ''
        );

        esconderErroPerfil(
            'perfilErroEliminar'
        );

        $('#perfilModalEliminar')
            .modal('show');
    }

    async function eliminarPerfil(evento) {
        evento.preventDefault();

        esconderErroPerfil(
            'perfilErroEliminar'
        );

        const botao =
            document.getElementById(
                'perfilBtnEliminar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoPerfil(
                botao,
                'A eliminar...'
            );

            const dados =
                new FormData(
                    evento.currentTarget
                );

            const resultado =
                await requisicaoPerfil(
                    'eliminar',
                    {
                        method: 'POST',
                        dados
                    }
                );

            retirarFocoPerfil();

            $('#perfilModalEliminar')
                .modal('hide');

            mostrarMensagemSucessoPerfil(
                resultado.mensagem
                || 'Perfil eliminado com sucesso.'
            );

            await carregarListaPerfis();
        } catch (erro) {
            mostrarErroPerfil(
                'perfilErroEliminar',
                erro.message
            );
        } finally {
            desbloquearBotaoPerfil(
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

    function destruirTabelaPerfis() {
        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        if (
            $.fn.DataTable.isDataTable(
                '#perfilTabela'
            )
        ) {
            $('#perfilTabela')
                .DataTable()
                .destroy();
        }

        perfisModuloTabela = null;
    }

    function iniciarTabelaPerfis() {
        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        perfisModuloTabela =
            $('#perfilTabela')
                .DataTable({
                    pageLength: 10,

                    lengthMenu: [
                        [5, 10, 25, 50],
                        [5, 10, 25, 50]
                    ],

                    responsive: true,

                    order: [
                        [1, 'asc']
                    ],

                    columnDefs: [
                        {
                            targets: [5],
                            orderable: false,
                            searchable: false
                        }
                    ],

                    language: {
                        url:
                            'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json'
                    }
                });
    }

    /*
    |--------------------------------------------------------------------------
    | Auxiliares
    |--------------------------------------------------------------------------
    */

    function limparFormularioPerfil() {
        const formulario =
            document.getElementById(
                'perfilForm'
            );

        formulario?.reset();

        definirValorPerfil(
            'perfilId',
            ''
        );

        esconderErroPerfil(
            'perfilErroFormulario'
        );
    }

    function definirValorPerfil(
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

    function atualizarTextoPerfil(
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

    function mostrarErroPerfil(
        id,
        mensagem
    ) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            mostrarMensagemErroPerfil(
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

    function esconderErroPerfil(id) {
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

    function bloquearBotaoPerfil(
        botao,
        texto
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = true;

        botao.innerHTML = `
            <i class="fa fa-spinner fa-spin"></i>
            ${escaparHtmlPerfil(texto)}
        `;
    }

    function desbloquearBotaoPerfil(
        botao,
        html
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = false;
        botao.innerHTML = html;
    }

    function retirarFocoPerfil() {
        if (
            document.activeElement
            instanceof HTMLElement
        ) {
            document.activeElement.blur();
        }
    }

    function normalizarTextoPerfil(valor) {
        return String(valor || '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            );
    }

    function escaparHtmlPerfil(valor) {
        const elemento =
            document.createElement(
                'div'
            );

        elemento.textContent =
            valor ?? '';

        return elemento.innerHTML;
    }

    function formatarDataPerfil(valor) {
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
            return escaparHtmlPerfil(
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

    function criarBadgePerfil(status) {
        const nome =
            String(status || '-');

        const normalizado =
            normalizarTextoPerfil(
                nome
            );

        let classe =
            'badge-secondary';

        if (
            normalizado === 'activo'
            || normalizado === 'ativo'
        ) {
            classe =
                'badge-success';
        } else if (
            normalizado === 'inactivo'
            || normalizado === 'inativo'
        ) {
            classe =
                'badge-warning';
        }

        return `
            <span class="badge ${classe}">
                ${escaparHtmlPerfil(nome)}
            </span>
        `;
    }

    function mostrarMensagemSucessoPerfil(
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

    function mostrarMensagemErroPerfil(
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