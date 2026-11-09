'use strict';

(() => {
    const URL_USUARIOS =
        '/erpplus/app/ajax/usuario_ajax.php';

    let usuarios = [];
    let tabelaUsuarios = null;
    let inicializado = false;
    let contextoUsuario = {
        super_administrador: false,
        empresa_id: null
    };

    /*
    |--------------------------------------------------------------------------
    | Inicialização
    |--------------------------------------------------------------------------
    */

    function iniciarModuloUsuarios() {
        if (inicializado) {
            return;
        }

        const tabela =
            document.getElementById(
                'usuariosTable'
            );

        if (!tabela) {
            return;
        }

        inicializado = true;

        configurarEventos();

        inicializarDados();
    }

    async function inicializarDados() {
        try {
            await carregarUsuarios();
        } catch (erro) {
            mostrarErro(
                'erroListaUsuarios',
                erro.message
            );
        }
    }

    if (
        document.readyState
        === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarModuloUsuarios
        );
    } else {
        iniciarModuloUsuarios();
    }

    /*
    |--------------------------------------------------------------------------
    | Eventos
    |--------------------------------------------------------------------------
    */

    function configurarEventos() {
        const btnNovo =
            document.getElementById(
                'btnNovoUsuario'
            );

        const formUsuario =
            document.getElementById(
                'formUsuario'
            );

        const formEliminar =
            document.getElementById(
                'formEliminarUsuario'
            );

        const usuarioEmpresaId =
            document.getElementById(
                'usuarioEmpresaId'
            );


        if (usuarioEmpresaId) {

            usuarioEmpresaId
                .addEventListener(
                    'change',
                    async function () {

                        await carregarFiliaisEmpresa(
                            this.value
                        );
                    }
                );
        }

        if (btnNovo) {
            btnNovo.addEventListener(
                'click',
                abrirNovoUsuario
            );
        }

        if (formUsuario) {
            formUsuario.addEventListener(
                'submit',
                guardarUsuario
            );
        }

        if (formEliminar) {
            formEliminar.addEventListener(
                'submit',
                eliminarUsuario
            );
        }

        /*
         * Sempre carrega os selects
         * antes de mostrar o modal.
         */
        if (
            typeof $ !== 'undefined'
        ) {
            $('#modalUsuario').on(
                'show.bs.modal',
                async function () {
                    try {
                        await carregarDadosFormulario();
                    } catch (erro) {
                        mostrarErro(
                            'erroFormularioUsuario',
                            erro.message
                        );
                    }
                }
            );
        }
    }

    async function carregarFiliaisEmpresa(
        empresaId
    ) {

        if (!empresaId) {

            preencherSelectFiliais(
                []
            );

            return;
        }


        const resultado =
            await requisicaoUsuario(
                'filiais',
                {
                    parametros: {
                        empresa_id:
                            empresaId
                    }
                }
            );


        preencherSelectFiliais(
            resultado.dados
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Requisição
    |--------------------------------------------------------------------------
    */

    async function requisicaoUsuario(
        acao,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        let url =
            URL_USUARIOS;

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
            let dados;

            if (
                opcoes.dados
                instanceof FormData
            ) {
                dados =
                    opcoes.dados;
            } else {
                dados =
                    new FormData();

                Object.entries(
                    opcoes.dados || {}
                ).forEach(
                    ([chave, valor]) => {
                        dados.append(
                            chave,
                            valor ?? ''
                        );
                    }
                );
            }

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
                'Não foi possível ligar ao servidor.'
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
                'Resposta PHP inválida:',
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
    | Dados do formulário
    |--------------------------------------------------------------------------
    */

    async function carregarDadosFormulario() {

        const resultadoContexto =
            await requisicaoUsuario(
                'contexto'
            );


        contextoUsuario =
            resultadoContexto.dados
            || {};


        const superAdministrador =
            contextoUsuario
                .super_administrador
            === true;


        /*
         * Mostrar ou esconder Empresa
         */
        const grupoEmpresa =
            document.getElementById(
                'grupoUsuarioEmpresa'
            );


        const selectEmpresa =
            document.getElementById(
                'usuarioEmpresaId'
            );


        if (grupoEmpresa) {

            grupoEmpresa.classList.toggle(
                'd-none',
                !superAdministrador
            );
        }


        if (selectEmpresa) {

            selectEmpresa.required =
                superAdministrador;
        }


        /*
         * Dados comuns
         */
        const [
            resultadoPerfis,
            resultadoStatus
        ] =
            await Promise.all([
                requisicaoUsuario('perfis'),
                requisicaoUsuario('status')
            ]);


        preencherSelectPerfis(
            resultadoPerfis.dados
        );


        preencherSelectStatus(
            resultadoStatus.dados
        );


        /*
         * Super Administrador
         */
        if (superAdministrador) {

            const resultadoEmpresas =
                await requisicaoUsuario(
                    'empresas'
                );


            preencherSelectEmpresas(
                resultadoEmpresas.dados
            );


            /*
             * Filial só depois de escolher empresa.
             */
            preencherSelectFiliais([]);

        } else {

            const resultadoFiliais =
                await requisicaoUsuario(
                    'filiais'
                );


            preencherSelectFiliais(
                resultadoFiliais.dados
            );
        }
    }

    function preencherSelectEmpresas(
        dados
    ) {

        const select =
            document.getElementById(
                'usuarioEmpresaId'
            );


        if (!select) {
            return;
        }


        const valorAtual =
            select.value;


        const empresas =
            Array.isArray(dados)
                ? dados
                : [];


        select.replaceChildren();


        select.add(
            new Option(
                empresas.length
                    ? 'Selecione a empresa'
                    : 'Nenhuma empresa disponível',
                ''
            )
        );


        empresas.forEach(
            empresa => {

                select.add(
                    new Option(
                        empresa.nome
                        || `Empresa #${empresa.id}`,

                        String(
                            empresa.id
                        )
                    )
                );
            }
        );


        if (
            valorAtual
            && existeOpcao(
                select,
                valorAtual
            )
        ) {

            select.value =
                valorAtual;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Perfis
    |--------------------------------------------------------------------------
    */

    function preencherSelectPerfis(
        dados
    ) {
        const select =
            document.getElementById(
                'usuarioPerfilId'
            );

        if (!select) {
            return;
        }

        const valorAtual =
            select.value;

        const perfis =
            Array.isArray(dados)
                ? dados
                : [];

        select.replaceChildren();

        select.add(
            new Option(
                perfis.length > 0
                    ? 'Selecione o perfil'
                    : 'Nenhum perfil disponível',
                ''
            )
        );

        perfis.forEach(perfil => {
            select.add(
                new Option(
                    perfil.nome
                    || `Perfil #${perfil.id}`,
                    String(perfil.id)
                )
            );
        });

        if (
            valorAtual
            && existeOpcao(
                select,
                valorAtual
            )
        ) {
            select.value =
                valorAtual;
        }

        select.disabled = false;
    }

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    function preencherSelectStatus(
        dados
    ) {
        const select =
            document.getElementById(
                'usuarioStatusId'
            );

        if (!select) {
            return;
        }

        const valorAtual =
            select.value;

        const estados =
            Array.isArray(dados)
                ? dados
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
                    estado.nome
                    || `Estado #${estado.id}`,
                    String(estado.id)
                )
            );
        });

        const estadoActivo =
            estados.find(estado => {
                const nome =
                    normalizarTexto(
                        estado.nome
                    );

                return nome === 'activo'
                    || nome === 'ativo';
            });

        if (estadoActivo) {
            select.dataset.activoId =
                String(estadoActivo.id);
        }

        if (
            valorAtual
            && existeOpcao(
                select,
                valorAtual
            )
        ) {
            select.value =
                valorAtual;
        }

        select.disabled = false;
    }

    /*
    |--------------------------------------------------------------------------
    | Filiais
    |--------------------------------------------------------------------------
    */

    function preencherSelectFiliais(
        dados
    ) {
        const select =
            document.getElementById(
                'usuarioFilialId'
            );

        if (!select) {
            return;
        }

        const valorAtual =
            select.value;

        const filiais =
            Array.isArray(dados)
                ? dados
                : [];

        select.replaceChildren();

        select.add(
            new Option(
                'Todas / Sede',
                ''
            )
        );

        filiais.forEach(filial => {
            const texto =
                filial.codigo
                    ? `${filial.nome} (${filial.codigo})`
                    : filial.nome;

            select.add(
                new Option(
                    texto
                    || `Filial #${filial.id}`,
                    String(filial.id)
                )
            );
        });

        if (
            valorAtual
            && existeOpcao(
                select,
                valorAtual
            )
        ) {
            select.value =
                valorAtual;
        }

        select.disabled = false;
    }

    function existeOpcao(
        select,
        valor
    ) {
        return Array.from(
            select.options
        ).some(
            option =>
                String(option.value)
                === String(valor)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Listar utilizadores
    |--------------------------------------------------------------------------
    */

    async function carregarUsuarios() {
        const tbody =
            document.getElementById(
                'usuariosTableBody'
            );

        if (!tbody) {
            return;
        }

        esconderErro(
            'erroListaUsuarios'
        );

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="9"
                    class="text-center"
                >
                    <i class="fa fa-spinner fa-spin"></i>
                    A carregar utilizadores...
                </td>
            </tr>
        `;

        try {
            const resultado =
                await requisicaoUsuario(
                    'listar'
                );

            usuarios =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            destruirDataTable();

            renderizarUsuarios();

            iniciarDataTable();
        } catch (erro) {
            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="9"
                        class="text-center text-danger"
                    >
                        ${escaparHtml(
                erro.message
            )}
                    </td>
                </tr>
            `;

            throw erro;
        }
    }

    function renderizarUsuarios() {
        const tbody =
            document.getElementById(
                'usuariosTableBody'
            );

        if (!tbody) {
            return;
        }

        if (usuarios.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="9"
                        class="text-center text-muted"
                    >
                        Nenhum utilizador encontrado.
                    </td>
                </tr>
            `;

            return;
        }

        tbody.innerHTML =
            usuarios
                .map(
                    (usuario, indice) =>
                        criarLinhaUsuario(
                            usuario,
                            indice
                        )
                )
                .join('');
    }

    function criarLinhaUsuario(
        usuario,
        indice
    ) {
        const id =
            Number(usuario.id);

        const numero =
            String(indice + 1)
                .padStart(4, '0');

        return `
            <tr>
                <td>
                    ${numero}
                </td>

                <td>
                    ${escaparHtml(
            usuario.nome || '-'
        )}
                </td>

                <td>
                    ${escaparHtml(
            usuario.username || '-'
        )}
                </td>

                <td>
                    ${escaparHtml(
            usuario.email || '-'
        )}
                </td>

                <td>
                    ${escaparHtml(
            usuario.telefone || '-'
        )}
                </td>

                <td>
                    ${escaparHtml(
            usuario.perfil_nome || '-'
        )}
                </td>

                <td>
                    ${criarBadgeEstado(
            usuario.status_nome
        )}
                </td>

                <td>
                    ${formatarDataHora(
            usuario.criado_em
        )}
                </td>

                <td class="text-right">
                    <button
                        type="button"
                        class="btn btn-sm btn-info"
                        title="Editar"
                        data-acao="editar"
                        data-id="${id}"
                    >
                        <i class="fa fa-pencil"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        title="Eliminar"
                        data-acao="eliminar"
                        data-id="${id}"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    /*
    |--------------------------------------------------------------------------
    | Novo utilizador
    |--------------------------------------------------------------------------
    */

    async function abrirNovoUsuario() {
        limparFormulario();

        atualizarTexto(
            'modalUsuarioTitulo',
            'Novo Utilizador'
        );

        definirSenhaObrigatoria(
            true
        );

        /*
         * O evento show.bs.modal
         * carregará perfis, estados e filiais.
         */
        $('#modalUsuario')
            .modal('show');
    }

    /*
    |--------------------------------------------------------------------------
    | Editar
    |--------------------------------------------------------------------------
    */

    async function editarUsuario(id) {
        try {
            limparFormulario();

            await carregarDadosFormulario();

            const resultado =
                await requisicaoUsuario(
                    'buscar',
                    {
                        parametros: {
                            id
                        }
                    }
                );

            const usuario =
                resultado.dados || {};

            definirValor(
                'usuarioId',
                usuario.id
            );

            definirValor(
                'usuarioNome',
                usuario.nome
            );

            definirValor(
                'usuarioUsername',
                usuario.username
            );

            definirValor(
                'usuarioEmail',
                usuario.email
            );

            definirValor(
                'usuarioTelefone',
                usuario.telefone
            );

            definirValorSelect(
                'usuarioPerfilId',
                usuario.perfil_id
            );

            if (
                contextoUsuario
                    .super_administrador
            ) {

                definirValorSelect(
                    'usuarioEmpresaId',
                    usuario.empresa_id
                );


                await carregarFiliaisEmpresa(
                    usuario.empresa_id
                );
            }

            definirValorSelect(
                'usuarioFilialId',
                usuario.filial_id
            );

            definirValorSelect(
                'usuarioStatusId',
                usuario.status_id
            );

            atualizarTexto(
                'modalUsuarioTitulo',
                'Editar Utilizador'
            );

            definirSenhaObrigatoria(
                false
            );

            $('#modalUsuario')
                .modal('show');
        } catch (erro) {
            mostrarMensagemErro(
                erro.message
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    async function guardarUsuario(
        evento
    ) {
        evento.preventDefault();

        esconderErro(
            'erroFormularioUsuario'
        );

        const formulario =
            evento.currentTarget;

        const botao =
            document.getElementById(
                'btnGuardarUsuario'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotao(
                botao,
                'A guardar...'
            );

            const dados =
                new FormData(
                    formulario
                );

            const resultado =
                await requisicaoUsuario(
                    'guardar',
                    {
                        method: 'POST',
                        dados
                    }
                );

            retirarFoco();

            $('#modalUsuario')
                .modal('hide');

            mostrarMensagemSucesso(
                resultado.mensagem
                || 'Utilizador guardado com sucesso.'
            );

            await carregarUsuarios();
        } catch (erro) {
            mostrarErro(
                'erroFormularioUsuario',
                erro.message
            );
        } finally {
            desbloquearBotao(
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

    function abrirEliminarUsuario(
        id
    ) {
        const usuario =
            usuarios.find(
                item =>
                    Number(item.id)
                    === Number(id)
            );

        definirValor(
            'eliminarUsuarioId',
            id
        );

        atualizarTexto(
            'eliminarUsuarioNome',
            usuario?.nome || ''
        );

        esconderErro(
            'erroEliminarUsuario'
        );

        $('#modalEliminarUsuario')
            .modal('show');
    }

    async function eliminarUsuario(
        evento
    ) {
        evento.preventDefault();

        const botao =
            document.getElementById(
                'btnEliminarUsuario'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotao(
                botao,
                'A eliminar...'
            );

            const resultado =
                await requisicaoUsuario(
                    'eliminar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );

            retirarFoco();

            $('#modalEliminarUsuario')
                .modal('hide');

            mostrarMensagemSucesso(
                resultado.mensagem
                || 'Utilizador eliminado com sucesso.'
            );

            await carregarUsuarios();
        } catch (erro) {
            mostrarErro(
                'erroEliminarUsuario',
                erro.message
            );
        } finally {
            desbloquearBotao(
                botao,
                htmlOriginal
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Clique nos botões da tabela
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        function (evento) {
            const botao =
                evento.target.closest(
                    '[data-acao][data-id]'
                );

            if (!botao) {
                return;
            }

            const id =
                Number(botao.dataset.id);

            if (
                botao.dataset.acao
                === 'editar'
            ) {
                editarUsuario(id);
            }

            if (
                botao.dataset.acao
                === 'eliminar'
            ) {
                abrirEliminarUsuario(id);
            }
        }
    );

    /*
    |--------------------------------------------------------------------------
    | Formulário
    |--------------------------------------------------------------------------
    */

    function limparFormulario() {
        const formulario =
            document.getElementById(
                'formUsuario'
            );

        formulario?.reset();

        definirValor(
            'usuarioId',
            ''
        );

        definirValor(
            'usuarioSenha',
            ''
        );

        definirValor(
            'usuarioConfirmarSenha',
            ''
        );

        esconderErro(
            'erroFormularioUsuario'
        );
    }

    function definirSenhaObrigatoria(
        obrigatoria
    ) {
        const senha =
            document.getElementById(
                'usuarioSenha'
            );

        const confirmar =
            document.getElementById(
                'usuarioConfirmarSenha'
            );

        const ajuda =
            document.getElementById(
                'ajudaSenha'
            );

        const indicadorSenha =
            document.getElementById(
                'senhaObrigatoria'
            );

        const indicadorConfirmar =
            document.getElementById(
                'confirmarSenhaObrigatoria'
            );

        if (senha) {
            senha.required =
                obrigatoria;
        }

        if (confirmar) {
            confirmar.required =
                obrigatoria;
        }

        indicadorSenha?.classList.toggle(
            'd-none',
            !obrigatoria
        );

        indicadorConfirmar?.classList.toggle(
            'd-none',
            !obrigatoria
        );

        if (ajuda) {
            ajuda.textContent =
                obrigatoria
                    ? 'Informe uma senha com pelo menos 6 caracteres.'
                    : 'Deixe em branco para manter a senha atual.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DataTable
    |--------------------------------------------------------------------------
    */

    function destruirDataTable() {
        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        if (
            $.fn.DataTable.isDataTable(
                '#usuariosTable'
            )
        ) {
            $('#usuariosTable')
                .DataTable()
                .destroy();
        }

        tabelaUsuarios = null;
    }

    function iniciarDataTable() {
        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        tabelaUsuarios =
            $('#usuariosTable')
                .DataTable({
                    pageLength: 10,

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

    function definirValor(
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

    function definirValorSelect(
        id,
        valor
    ) {
        const select =
            document.getElementById(id);

        if (!select) {
            return;
        }

        const valorTexto =
            valor === null
                || valor === undefined
                ? ''
                : String(valor);

        if (
            existeOpcao(
                select,
                valorTexto
            )
        ) {
            select.value =
                valorTexto;
        }
    }

    function atualizarTexto(
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

    function mostrarErro(
        id,
        mensagem
    ) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            mostrarMensagemErro(
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

    function esconderErro(id) {
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

    function bloquearBotao(
        botao,
        texto
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = true;

        botao.innerHTML = `
            <i class="fa fa-spinner fa-spin"></i>
            ${escaparHtml(texto)}
        `;
    }

    function desbloquearBotao(
        botao,
        html
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = false;
        botao.innerHTML = html;
    }

    function retirarFoco() {
        if (
            document.activeElement
            instanceof HTMLElement
        ) {
            document.activeElement.blur();
        }
    }

    function normalizarTexto(valor) {
        return String(valor || '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            );
    }

    function escaparHtml(valor) {
        const div =
            document.createElement(
                'div'
            );

        div.textContent =
            valor ?? '';

        return div.innerHTML;
    }

    function formatarDataHora(valor) {
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
            return escaparHtml(
                valor
            );
        }

        return data.toLocaleString(
            'pt-MZ'
        );
    }

    function criarBadgeEstado(
        estado
    ) {
        const nome =
            String(estado || '-');

        const normalizado =
            normalizarTexto(nome);

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
        } else if (
            normalizado === 'bloqueado'
        ) {
            classe =
                'badge-danger';
        } else if (
            normalizado === 'pendente'
        ) {
            classe =
                'badge-info';
        }

        return `
            <span class="badge ${classe}">
                ${escaparHtml(nome)}
            </span>
        `;
    }

    function mostrarMensagemSucesso(
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

    function mostrarMensagemErro(
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