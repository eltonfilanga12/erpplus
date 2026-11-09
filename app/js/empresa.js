'use strict';

(() => {
    const EMPRESA_URL =
        '/erpplus/app/ajax/empresa_ajax.php';

    const EMPRESA_LOGO_URL =
        '/erpplus/public/uploads/empresas/';

    const EMPRESA_LOGO_PADRAO =
        '/erpplus/assets/img/default-logo.png';

    let empresas = [];
    let empresaTabela = null;
    let empresaModuloInicializado = false;

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
            iniciarEmpresas
        );
    } else {
        iniciarEmpresas();
    }

    async function iniciarEmpresas() {
        if (empresaModuloInicializado) {
            return;
        }

        const tabela =
            document.getElementById(
                'empresaTabela'
            );

        if (!tabela) {
            return;
        }

        empresaModuloInicializado = true;

        configurarEventosEmpresa();

        await Promise.all([
            carregarStatusEmpresa(),
            carregarEmpresas()
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Eventos
    |--------------------------------------------------------------------------
    */

    function configurarEventosEmpresa() {
        document
            .getElementById(
                'empresaBtnNova'
            )
            ?.addEventListener(
                'click',
                abrirNovaEmpresa
            );

        document
            .getElementById(
                'empresaForm'
            )
            ?.addEventListener(
                'submit',
                guardarEmpresa
            );

        document
            .getElementById(
                'empresaFormEliminar'
            )
            ?.addEventListener(
                'submit',
                eliminarEmpresa
            );

        document
            .getElementById(
                'empresaLogo'
            )
            ?.addEventListener(
                'change',
                visualizarLogoEmpresa
            );

        document.addEventListener(
            'click',
            processarAcaoEmpresa
        );
    }

    function processarAcaoEmpresa(evento) {
        const botao =
            evento.target.closest(
                '[data-empresa-acao]'
            );

        if (!botao) {
            return;
        }

        const id =
            Number(
                botao.dataset.empresaId
            );

        if (!id) {
            return;
        }

        if (
            botao.dataset.empresaAcao
            === 'editar'
        ) {
            editarEmpresa(id);
        }

        if (
            botao.dataset.empresaAcao
            === 'eliminar'
        ) {
            abrirEliminarEmpresa(id);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Requisições
    |--------------------------------------------------------------------------
    */

    async function requisicaoEmpresa(
        acao,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        let url =
            EMPRESA_URL;

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
    | Listar
    |--------------------------------------------------------------------------
    */

    async function carregarEmpresas() {
        const tbody =
            document.getElementById(
                'empresaTabelaBody'
            );

        if (!tbody) {
            return;
        }

        esconderErroEmpresa(
            'empresaErroLista'
        );

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="9"
                    class="text-center"
                >
                    <i class="fa fa-spinner fa-spin"></i>
                    A carregar empresas...
                </td>
            </tr>
        `;

        try {
            const resultado =
                await requisicaoEmpresa(
                    'listar'
                );

            empresas =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            destruirDataTableEmpresa();

            renderizarEmpresas();

            iniciarDataTableEmpresa();
        } catch (erro) {
            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="9"
                        class="text-center text-danger"
                    >
                        ${escaparHtmlEmpresa(
                erro.message
            )}
                    </td>
                </tr>
            `;

            mostrarErroEmpresa(
                'empresaErroLista',
                erro.message
            );
        }
    }

    function renderizarEmpresas() {
        const tbody =
            document.getElementById(
                'empresaTabelaBody'
            );

        if (!tbody) {
            return;
        }

        if (!empresas.length) {
            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="9"
                        class="text-center text-muted"
                    >
                        Nenhuma empresa encontrada.
                    </td>
                </tr>
            `;

            return;
        }

        tbody.innerHTML =
            empresas
                .map(criarLinhaEmpresa)
                .join('');
    }

    function criarLinhaEmpresa(empresa) {
        const id =
            Number(empresa.id);

        const localizacao =
            [
                empresa.cidade,
                empresa.provincia
            ]
                .filter(Boolean)
                .join(' - ')
            || '-';

        const logo =
            empresa.logo
                ? EMPRESA_LOGO_URL
                + encodeURIComponent(
                    empresa.logo
                )
                : EMPRESA_LOGO_PADRAO;

        return `
            <tr>
                <td>
                    <img
                        src="${escaparAtributoEmpresa(logo)}"
                        alt="Logo"
                        width="42"
                        height="42"
                        class="img-thumbnail"
                        style="object-fit: contain;"
                    >
                </td>

                <td>
                    <strong>
                        ${escaparHtmlEmpresa(
            empresa.nome || '-'
        )}
                    </strong>

                    ${empresa.nome_fantasia
                ? `
                                <div class="small text-muted">
                                    ${escaparHtmlEmpresa(
                    empresa.nome_fantasia
                )}
                                </div>
                            `
                : ''
            }
                </td>

                <td>
                    ${escaparHtmlEmpresa(
                empresa.nuit || '-'
            )}
                </td>

                <td>
                    ${escaparHtmlEmpresa(
                empresa.telefone || '-'
            )}
                </td>

                <td>
                    ${escaparHtmlEmpresa(
                localizacao
            )}
                </td>

                <td>
                    ${escaparHtmlEmpresa(
                empresa.tipo_empresa || '-'
            )}
                </td>

                <td>
                    ${criarBadgeEmpresa(
                empresa.status_nome
            )}
                </td>

                <td>
                    ${escaparHtmlEmpresa(
                empresa.moeda || 'MZN'
            )}
                </td>

                <td class="text-right">
                    <button
                        type="button"
                        class="btn btn-sm btn-info"
                        title="Editar"
                        data-empresa-acao="editar"
                        data-empresa-id="${id}"
                    >
                        <i class="fa fa-edit"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        title="Eliminar"
                        data-empresa-acao="eliminar"
                        data-empresa-id="${id}"
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

    async function carregarStatusEmpresa() {
        const select =
            document.getElementById(
                'empresaStatusId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {
            const resultado =
                await requisicaoEmpresa(
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
                        normalizarTextoEmpresa(
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

    function abrirNovaEmpresa() {
        limparFormularioEmpresa();

        atualizarTextoEmpresa(
            'empresaModalTitulo',
            'Nova Empresa'
        );

        const status =
            document.getElementById(
                'empresaStatusId'
            );

        if (
            status
            && status.dataset.activoId
        ) {
            status.value =
                status.dataset.activoId;
        }

        $('#empresaModal')
            .modal('show');
    }

    /*
    |--------------------------------------------------------------------------
    | Editar
    |--------------------------------------------------------------------------
    */

    async function editarEmpresa(id) {
        esconderErroEmpresa(
            'empresaErroFormulario'
        );

        try {
            const resultado =
                await requisicaoEmpresa(
                    'buscar',
                    {
                        parametros: {
                            id
                        }
                    }
                );

            const empresa =
                resultado.dados || {};

            limparFormularioEmpresa();

            definirValorEmpresa(
                'empresaId',
                empresa.id
            );

            definirValorEmpresa(
                'empresaNome',
                empresa.nome
            );

            definirValorEmpresa(
                'empresaNomeFantasia',
                empresa.nome_fantasia
            );

            definirValorEmpresa(
                'empresaNuit',
                empresa.nuit
            );

            definirValorEmpresa(
                'empresaTipo',
                empresa.tipo_empresa
            );

            definirValorEmpresa(
                'empresaRamoActividade',
                empresa.ramo_actividade
            );

            definirValorEmpresa(
                'empresaStatusId',
                empresa.status_id
            );

            definirValorEmpresa(
                'empresaEmail',
                empresa.email
            );

            definirValorEmpresa(
                'empresaTelefone',
                empresa.telefone
            );

            definirValorEmpresa(
                'empresaWebsite',
                empresa.website
            );

            definirValorEmpresa(
                'empresaContactoPrincipal',
                empresa.contacto_principal
            );

            definirValorEmpresa(
                'empresaLogradouro',
                empresa.logradouro
            );

            definirValorEmpresa(
                'empresaProvincia',
                empresa.provincia
            );

            definirValorEmpresa(
                'empresaCidade',
                empresa.cidade
            );

            definirValorEmpresa(
                'empresaCodigoPostal',
                empresa.codigo_postal
            );

            definirValorEmpresa(
                'empresaPais',
                empresa.pais
            );

            definirValorEmpresa(
                'empresaMoeda',
                empresa.moeda
            );

            definirValorEmpresa(
                'empresaFusoHorario',
                empresa.fuso_horario
            );

            definirValorEmpresa(
                'empresaSlogan',
                empresa.slogan
            );

            definirValorEmpresa(
                'empresaDescricao',
                empresa.descricao
            );

            definirValorEmpresa(
                'empresaLogoAtual',
                empresa.logo
            );

            atualizarPreviewLogo(
                empresa.logo
                    ? EMPRESA_LOGO_URL
                    + encodeURIComponent(
                        empresa.logo
                    )
                    : EMPRESA_LOGO_PADRAO
            );

            atualizarTextoEmpresa(
                'empresaModalTitulo',
                'Editar Empresa'
            );

            $('#empresaModal')
                .modal('show');
        } catch (erro) {
            mostrarMensagemErroEmpresa(
                erro.message
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    async function guardarEmpresa(evento) {
        evento.preventDefault();

        esconderErroEmpresa(
            'empresaErroFormulario'
        );

        const formulario =
            evento.currentTarget;

        const botao =
            document.getElementById(
                'empresaBtnGuardar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoEmpresa(
                botao,
                'A guardar...'
            );

            const resultado =
                await requisicaoEmpresa(
                    'salvar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                formulario
                            )
                    }
                );

            retirarFocoEmpresa();

            $('#empresaModal')
                .modal('hide');

            mostrarMensagemSucessoEmpresa(
                resultado.mensagem
            );

            await carregarEmpresas();
        } catch (erro) {
            mostrarErroEmpresa(
                'empresaErroFormulario',
                erro.message
            );
        } finally {
            desbloquearBotaoEmpresa(
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

    function abrirEliminarEmpresa(id) {
        const empresa =
            empresas.find(
                item =>
                    Number(item.id)
                    === Number(id)
            );

        definirValorEmpresa(
            'empresaEliminarId',
            id
        );

        atualizarTextoEmpresa(
            'empresaEliminarNome',
            empresa?.nome || ''
        );

        esconderErroEmpresa(
            'empresaErroEliminar'
        );

        $('#empresaModalEliminar')
            .modal('show');
    }

    async function eliminarEmpresa(evento) {
        evento.preventDefault();

        esconderErroEmpresa(
            'empresaErroEliminar'
        );

        const botao =
            document.getElementById(
                'empresaBtnEliminar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoEmpresa(
                botao,
                'A eliminar...'
            );

            const resultado =
                await requisicaoEmpresa(
                    'eliminar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );

            retirarFocoEmpresa();

            $('#empresaModalEliminar')
                .modal('hide');

            mostrarMensagemSucessoEmpresa(
                resultado.mensagem
            );

            await carregarEmpresas();
        } catch (erro) {
            mostrarErroEmpresa(
                'empresaErroEliminar',
                erro.message
            );
        } finally {
            desbloquearBotaoEmpresa(
                botao,
                htmlOriginal
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    */

    function visualizarLogoEmpresa(evento) {
        const arquivo =
            evento.currentTarget
                .files?.[0];

        if (!arquivo) {
            return;
        }

        const url =
            URL.createObjectURL(
                arquivo
            );

        atualizarPreviewLogo(
            url
        );
    }

    function atualizarPreviewLogo(url) {
        const imagem =
            document.getElementById(
                'empresaPreviewLogo'
            );

        if (imagem) {
            imagem.src =
                url || EMPRESA_LOGO_PADRAO;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DataTable
    |--------------------------------------------------------------------------
    */

    function destruirDataTableEmpresa() {
        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        if (
            $.fn.DataTable.isDataTable(
                '#empresaTabela'
            )
        ) {
            $('#empresaTabela')
                .DataTable()
                .destroy();
        }

        empresaTabela = null;
    }

    function iniciarDataTableEmpresa() {
        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        empresaTabela =
            $('#empresaTabela')
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

                    language: {
                        url:
                            'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json'
                    },

                    dom:
                        "<'row'<'col-sm-12 col-md-8 d-flex align-items-center'Bl><'col-sm-12 col-md-4'f>>"
                        + "<'row'<'col-sm-12'tr>>"
                        + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",

                    columnDefs: [
                        {
                            targets: [0, 8],
                            orderable: false,
                            searchable: false
                        }
                    ],

                    buttons: [
                        {
                            extend: 'excelHtml5',
                            text:
                                '<i class="fa fa-file-excel"></i> Excel',
                            title:
                                'Lista de Empresas',
                            exportOptions: {
                                columns:
                                    [1, 2, 3, 4, 5, 6, 7]
                            }
                        },
                        {
                            extend: 'pdfHtml5',
                            text:
                                '<i class="fa fa-file-pdf"></i> PDF',
                            title:
                                'Lista de Empresas',
                            orientation:
                                'landscape',
                            pageSize:
                                'A4',
                            exportOptions: {
                                columns:
                                    [1, 2, 3, 4, 5, 6, 7]
                            }
                        },
                        {
                            extend: 'print',
                            text:
                                '<i class="fa fa-print"></i> Imprimir',
                            title:
                                'Lista de Empresas',
                            exportOptions: {
                                columns:
                                    [1, 2, 3, 4, 5, 6, 7]
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

    function limparFormularioEmpresa() {
        const formulario =
            document.getElementById(
                'empresaForm'
            );

        formulario?.reset();

        definirValorEmpresa(
            'empresaId',
            ''
        );

        definirValorEmpresa(
            'empresaLogoAtual',
            ''
        );

        definirValorEmpresa(
            'empresaPais',
            'Moçambique'
        );

        definirValorEmpresa(
            'empresaMoeda',
            'MZN'
        );

        definirValorEmpresa(
            'empresaFusoHorario',
            'Africa/Maputo'
        );

        atualizarPreviewLogo(
            EMPRESA_LOGO_PADRAO
        );

        esconderErroEmpresa(
            'empresaErroFormulario'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Auxiliares
    |--------------------------------------------------------------------------
    */

    function definirValorEmpresa(
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

    function atualizarTextoEmpresa(
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

    function mostrarErroEmpresa(
        id,
        mensagem
    ) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            mostrarMensagemErroEmpresa(
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

    function esconderErroEmpresa(id) {
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

    function bloquearBotaoEmpresa(
        botao,
        texto
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = true;

        botao.innerHTML = `
            <i class="fa fa-spinner fa-spin"></i>
            ${escaparHtmlEmpresa(texto)}
        `;
    }

    function desbloquearBotaoEmpresa(
        botao,
        html
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = false;
        botao.innerHTML = html;
    }

    function retirarFocoEmpresa() {
        if (
            document.activeElement
            instanceof HTMLElement
        ) {
            document.activeElement.blur();
        }
    }

    function normalizarTextoEmpresa(valor) {
        return String(valor || '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            );
    }

    function escaparHtmlEmpresa(valor) {
        const elemento =
            document.createElement(
                'div'
            );

        elemento.textContent =
            valor ?? '';

        return elemento.innerHTML;
    }

    function escaparAtributoEmpresa(valor) {
        return escaparHtmlEmpresa(
            valor
        );
    }

    function criarBadgeEmpresa(status) {
        const nome =
            String(status || '-');

        const normalizado =
            normalizarTextoEmpresa(
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
                ${escaparHtmlEmpresa(nome)}
            </span>
        `;
    }

    function mostrarMensagemSucessoEmpresa(
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

    function mostrarMensagemErroEmpresa(
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