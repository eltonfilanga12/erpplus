'use strict';

(() => {
    const CATEGORIA_URLL =
        '/erpplus/app/ajax/categoria_ajax.php';

    let categorias = [];
    let categoriaDataTable = null;
    let categoriaModuloInicializado = false;
    let categoriaImportacaoToken = '';
    let categoriaImportacaoDados = null;
    let categoriaImportacaoProcessando = false;

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarModuloCategorias
        );
    } else {
        iniciarModuloCategorias();
    }

    async function iniciarModuloCategorias() {
        if (categoriaModuloInicializado) {
            return;
        }

        if (
            !document.getElementById(
                'categoriaTabela'
            )
        ) {
            return;
        }

        categoriaModuloInicializado = true;

        configurarEventosCategoria();

        await Promise.all([
            carregarStatusCategoria(),
            carregarOpcoesCategorias(),
            async function carregarCategorias() {

                const tbody =
                    document.getElementById(
                        'categoriaTabelaBody'
                    );

                if (!tbody) {
                    return;
                }

                esconderErroCategoria(
                    'categoriaErroLista'
                );

                try {

                    const resultado =
                        await requisicaoCategoria(
                            'listar'
                        );

                    categorias =
                        Array.isArray(
                            resultado.dados
                        )
                            ? resultado.dados
                            : [];

                    // Destruir antes de alterar o tbody
                    destruirDataTableCategoria();

                    // Não usar colspan no tbody
                    tbody.innerHTML = '';

                    if (categorias.length > 0) {

                        tbody.innerHTML =
                            categorias
                                .map(
                                    (categoria, indice) =>
                                        criarLinhaCategoria(
                                            categoria,
                                            indice
                                        )
                                )
                                .join('');
                    }
                    iniciarDataTableCategoria();

                } catch (erro) {

                    destruirDataTableCategoria();

                    tbody.innerHTML = '';

                    mostrarErroCategoria(
                        'categoriaErroLista',
                        erro.message
                    );
                }
            }()
        ]);
    }

    function configurarEventosCategoria() {
        document
            .getElementById(
                'categoriaBtnNova'
            )
            ?.addEventListener(
                'click',
                abrirNovaCategoria
            );

        document
            .getElementById(
                'categoriaForm'
            )
            ?.addEventListener(
                'submit',
                guardarCategoria
            );

        document.getElementById('categoriaBtnImportar')?.addEventListener('click', abrirImportacaoCategoria);
        document.getElementById('categoriaBtnModeloExcel')?.addEventListener('click', baixarModeloExcelCategoria);
        document.getElementById('categoriaBtnValidarExcel')?.addEventListener('click', validarImportacaoCategoria);
        document.getElementById('categoriaBtnConfirmarImportacao')?.addEventListener('click', confirmarImportacaoCategoria);
        document.getElementById('categoriaBtnVoltarImportacao')?.addEventListener('click', voltarImportacaoCategoria);
        document.getElementById('categoriaFicheiroExcel')?.addEventListener('change', alterarFicheiroExcelCategoria);


        document
            .getElementById(
                'categoriaFormEliminar'
            )
            ?.addEventListener(
                'submit',
                eliminarCategoria
            );

        document.addEventListener(
            'click',
            processarCliqueCategoria
        );
    }

    async function requisicaoCategoria(
        acao,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        let url =
            CATEGORIA_URLL;

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
                'Resposta inválida da categoria:',
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

    async function carregarCategorias() {

        const tbody =
            document.getElementById(
                'categoriaTabelaBody'
            );

        if (!tbody) {
            return;
        }

        esconderErroCategoria(
            'categoriaErroLista'
        );

        try {

            const resultado =
                await requisicaoCategoria(
                    'listar'
                );

            categorias =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            // Destruir antes de alterar o tbody
            destruirDataTableCategoria();

            // Não usar colspan no tbody
            tbody.innerHTML = '';

            if (categorias.length > 0) {

                tbody.innerHTML =
                    categorias
                        .map(criarLinhaCategoria)
                        .join('');
            }

            iniciarDataTableCategoria();

        } catch (erro) {

            destruirDataTableCategoria();

            tbody.innerHTML = '';

            mostrarErroCategoria(
                'categoriaErroLista',
                erro.message
            );
        }
    }

    function criarLinhaCategoria(
        categoria,
        indice
    ) {
        const id =
            Number(categoria.id);

        const numero =
            String(indice + 1)
                .padStart(4, '0');

        return `
            <tr>
                <td>
                    ${numero}
                </td>

                <td>
                    <strong>
                        ${escaparHtmlCategoria(
            categoria.nome || '-'
        )}
                    </strong>
                </td>

                <td>
                    ${escaparHtmlCategoria(
            categoria.categoria_pai_nome
            || 'Categoria principal'
        )}
                </td>

                <td>
                    ${escaparHtmlCategoria(
            categoria.descricao || '-'
        )}
                </td>

                <td>
                    ${criarBadgeCategoria(
            categoria.status_nome
        )}
                </td>

                <td>
                    ${formatarDataCategoria(
            categoria.criado_em
        )}
                </td>

                <td class="text-right">
                    <button
                        type="button"
                        class="btn btn-sm btn-info"
                        title="Editar"
                        data-categoria-acao="editar"
                        data-categoria-id="${id}"
                    >
                        <i class="fa fa-edit"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        title="Eliminar"
                        data-categoria-acao="eliminar"
                        data-categoria-id="${id}"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    async function carregarStatusCategoria() {
        const select =
            document.getElementById(
                'categoriaStatusId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {
            const resultado =
                await requisicaoCategoria(
                    'status'
                );

            const estados =
                Array.isArray(resultado.dados)
                    ? resultado.dados
                    : [];

            select.replaceChildren(
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
                        normalizarTextoCategoria(
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
        } finally {
            select.disabled = false;
        }
    }

    async function carregarOpcoesCategorias(
        ignorarId = null,
        valorSeleccionado = null
    ) {
        const select =
            document.getElementById(
                'categoriaPaiId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {
            const parametros = {};

            if (ignorarId) {
                parametros.ignorar_id =
                    String(ignorarId);
            }

            const resultado =
                await requisicaoCategoria(
                    'opcoes',
                    {
                        parametros
                    }
                );

            const opcoes =
                Array.isArray(resultado.dados)
                    ? resultado.dados
                    : [];

            select.replaceChildren(
                new Option(
                    'Categoria principal',
                    ''
                )
            );

            opcoes.forEach(categoria => {
                const texto =
                    categoria.categoria_pai_nome
                        ? `${categoria.categoria_pai_nome} › ${categoria.nome}`
                        : categoria.nome;

                select.add(
                    new Option(
                        texto,
                        String(categoria.id)
                    )
                );
            });

            if (
                valorSeleccionado !== null
                && valorSeleccionado !== undefined
            ) {
                select.value =
                    String(valorSeleccionado);
            }
        } catch (erro) {
            select.replaceChildren(
                new Option(
                    'Erro ao carregar categorias',
                    ''
                )
            );
        } finally {
            select.disabled = false;
        }
    }

    async function abrirNovaCategoria() {
        limparFormularioCategoria();

        atualizarTextoCategoria(
            'categoriaModalTitulo',
            'Nova Categoria'
        );

        await carregarOpcoesCategorias();

        const status =
            document.getElementById(
                'categoriaStatusId'
            );

        if (status?.dataset.activoId) {
            status.value =
                status.dataset.activoId;
        }

        $('#categoriaModal')
            .modal('show');
    }

    async function editarCategoria(id) {
        esconderErroCategoria(
            'categoriaErroFormulario'
        );

        try {
            const resultado =
                await requisicaoCategoria(
                    'buscar',
                    {
                        parametros: {
                            id
                        }
                    }
                );

            const categoria =
                resultado.dados || {};

            limparFormularioCategoria();

            definirValorCategoria(
                'categoriaId',
                categoria.id
            );

            definirValorCategoria(
                'categoriaNome',
                categoria.nome
            );

            definirValorCategoria(
                'categoriaStatusId',
                categoria.status_id
            );

            definirValorCategoria(
                'categoriaDescricao',
                categoria.descricao
            );

            await carregarOpcoesCategorias(
                categoria.id,
                categoria.categoria_pai_id
            );

            atualizarTextoCategoria(
                'categoriaModalTitulo',
                'Editar Categoria'
            );

            $('#categoriaModal')
                .modal('show');
        } catch (erro) {
            mostrarMensagemErroCategoria(
                erro.message
            );
        }
    }

    async function guardarCategoria(
        evento
    ) {
        evento.preventDefault();

        esconderErroCategoria(
            'categoriaErroFormulario'
        );

        const botao =
            document.getElementById(
                'categoriaBtnGuardar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoCategoria(
                botao,
                'A guardar...'
            );

            const resultado =
                await requisicaoCategoria(
                    'salvar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );

            retirarFocoCategoria();

            $('#categoriaModal')
                .modal('hide');

            mostrarMensagemSucessoCategoria(
                resultado.mensagem
            );

            await Promise.all([
                carregarCategorias(),
                carregarOpcoesCategorias()
            ]);
        } catch (erro) {
            mostrarErroCategoria(
                'categoriaErroFormulario',
                erro.message
            );
        } finally {
            desbloquearBotaoCategoria(
                botao,
                htmlOriginal
            );
        }
    }

    // Adicionar as funções auxiliares de importação:
    function abrirImportacaoCategoria() {
        categoriaImportacaoToken = '';
        categoriaImportacaoDados = null;
        categoriaImportacaoProcessando = false;

        const ficheiro = document.getElementById('categoriaFicheiroExcel');
        if (ficheiro) ficheiro.value = '';

        const label = document.querySelector('label[for="categoriaFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';

        document.getElementById('categoriaImportacaoPasso1')?.classList.remove('d-none');
        document.getElementById('categoriaImportacaoPasso2')?.classList.add('d-none');
        document.getElementById('categoriaBtnValidarExcel')?.classList.remove('d-none');
        document.getElementById('categoriaBtnConfirmarImportacao')?.classList.add('d-none');
        document.getElementById('categoriaBtnVoltarImportacao')?.classList.add('d-none');

        esconderErroImportacaoCategoria();
        esconderSucessoImportacaoCategoria();

        $('#categoriaModalImportar').modal('show');
    }

    function alterarFicheiroExcelCategoria(evento) {
        const ficheiro = evento.target.files?.[0];
        const label = document.querySelector('label[for="categoriaFicheiroExcel"]');
        if (label) {
            label.textContent = ficheiro ? ficheiro.name : 'Selecione o ficheiro';
        }
    }

    function baixarModeloExcelCategoria() {
        const url = CATEGORIA_URLL + '?acao=baixar_modelo_excel&_=' + Date.now();
        window.location.href = url;
    }

    async function validarImportacaoCategoria() {
        if (categoriaImportacaoProcessando) return;

        esconderErroImportacaoCategoria();
        esconderSucessoImportacaoCategoria();

        const input = document.getElementById('categoriaFicheiroExcel');
        const ficheiro = input?.files?.[0];

        if (!ficheiro) {
            mostrarErroImportacaoCategoria('Selecione um ficheiro Excel antes de continuar.');
            return;
        }

        const extensao = ficheiro.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(extensao)) {
            mostrarErroImportacaoCategoria('O ficheiro deve estar no formato XLSX ou XLS.');
            return;
        }

        const botao = document.getElementById('categoriaBtnValidarExcel');
        const htmlOriginal = botao?.innerHTML || '';

        try {
            categoriaImportacaoProcessando = true;
            bloquearBotaoCategoria(botao, 'A validar...');

            const dados = new FormData();
            dados.append('ficheiro', ficheiro);

            const resultado = await requisicaoCategoria('validar_importacao', {
                method: 'POST',
                dados
            });

            categoriaImportacaoDados = resultado.dados || {};
            categoriaImportacaoToken = String(categoriaImportacaoDados.token || '');

            if (!categoriaImportacaoToken) {
                throw new Error('O servidor não devolveu o token da importação.');
            }

            renderizarResultadoImportacaoCategoria(categoriaImportacaoDados);

            document.getElementById('categoriaImportacaoPasso1')?.classList.add('d-none');
            document.getElementById('categoriaImportacaoPasso2')?.classList.remove('d-none');
            document.getElementById('categoriaBtnValidarExcel')?.classList.add('d-none');
            document.getElementById('categoriaBtnVoltarImportacao')?.classList.remove('d-none');

            const totalValidos = Number(categoriaImportacaoDados.total_validos || 0);
            if (totalValidos > 0) {
                document.getElementById('categoriaBtnConfirmarImportacao')?.classList.remove('d-none');
            } else {
                document.getElementById('categoriaBtnConfirmarImportacao')?.classList.add('d-none');
            }
        } catch (erro) {
            mostrarErroImportacaoCategoria(erro.message);
        } finally {
            categoriaImportacaoProcessando = false;
            desbloquearBotaoCategoria(botao, htmlOriginal);
        }
    }

    function renderizarResultadoImportacaoCategoria(dados) {
        const validos = Array.isArray(dados.validos) ? dados.validos : [];
        const erros = Array.isArray(dados.erros) ? dados.erros : [];

        renderizarCategoriasValidasImportacao(validos);
        renderizarErrosImportacaoCategoria(erros);

        document.getElementById('categoriaImportacaoValidosContainer')?.classList.toggle('d-none', validos.length === 0);
        document.getElementById('categoriaImportacaoErrosContainer')?.classList.toggle('d-none', erros.length === 0);
    }

    function renderizarCategoriasValidasImportacao(validos) {
        const tbody = document.getElementById('categoriaImportacaoValidos');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (validos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Nenhuma categoria válida encontrada.</td></tr>`;
            return;
        }

        validos.forEach(item => {
            const preview = item.preview || {};
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>${escaparHtmlCategoria(item.linha ?? '')}</td>
            <td><strong>${escaparHtmlCategoria(preview.nome ?? '')}</strong></td>
            <td>${escaparHtmlCategoria(preview.categoria_pai_nome ?? '')}</td>
            <td>${escaparHtmlCategoria(preview.descricao ?? '')}</td>
        `;
            tbody.appendChild(tr);
        });
    }

    function renderizarErrosImportacaoCategoria(erros) {
        const tbody = document.getElementById('categoriaImportacaoErros');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (erros.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted">Nenhum erro encontrado.</td></tr>`;
            return;
        }

        erros.forEach(item => {
            const dados = item.dados || {};
            let mensagens = item.erros || ['Erro de validação.'];
            if (!Array.isArray(mensagens)) mensagens = [mensagens];

            const listaErros = mensagens.map(m => `<div>${escaparHtmlCategoria(m)}</div>`).join('');
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>${escaparHtmlCategoria(item.linha ?? '-')}</td>
            <td>${escaparHtmlCategoria(dados.nome ?? '')}</td>
            <td class="text-danger">${listaErros}</td>
        `;
            tbody.appendChild(tr);
        });
    }

    async function confirmarImportacaoCategoria() {
        if (categoriaImportacaoProcessando || !categoriaImportacaoToken) return;

        const totalValidos = Number(categoriaImportacaoDados?.total_validos || 0);
        if (totalValidos <= 0) return;

        if (typeof Swal !== 'undefined') {
            const confirmacao = await Swal.fire({
                icon: 'question',
                title: 'Confirmar importação?',
                html: `Serão registadas <strong>${totalValidos}</strong> categorias.`,
                showCancelButton: true,
                confirmButtonText: 'Sim, importar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirmacao.isConfirmed) return;
        }

        const botao = document.getElementById('categoriaBtnConfirmarImportacao');
        const htmlOriginal = botao?.innerHTML || '';

        try {
            categoriaImportacaoProcessando = true;
            bloquearBotaoCategoria(botao, 'A importar...');

            const dados = new FormData();
            dados.append('token', categoriaImportacaoToken);

            const resultado = await requisicaoCategoria('confirmar_importacao', {
                method: 'POST',
                dados
            });

            mostrarMensagemSucessoCategoria(resultado.mensagem || 'Importação concluída com sucesso.');
            categoriaImportacaoToken = '';
            categoriaImportacaoDados = null;

            document.getElementById('categoriaBtnConfirmarImportacao')?.classList.add('d-none');
            await Promise.all([
                carregarCategorias(),
                carregarOpcoesCategorias()
            ]);

            setTimeout(() => {
                $('#categoriaModalImportar').modal('hide');
            }, 1200);
        } catch (erro) {
            mostrarErroImportacaoCategoria(erro.message);
        } finally {
            categoriaImportacaoProcessando = false;
            desbloquearBotaoCategoria(botao, htmlOriginal);
        }
    }

    function voltarImportacaoCategoria() {
        categoriaImportacaoToken = '';
        categoriaImportacaoDados = null;

        document.getElementById('categoriaImportacaoPasso2')?.classList.add('d-none');
        document.getElementById('categoriaImportacaoPasso1')?.classList.remove('d-none');
        document.getElementById('categoriaBtnConfirmarImportacao')?.classList.add('d-none');
        document.getElementById('categoriaBtnVoltarImportacao')?.classList.add('d-none');
        document.getElementById('categoriaBtnValidarExcel')?.classList.remove('d-none');

        const input = document.getElementById('categoriaFicheiroExcel');
        if (input) input.value = '';

        const label = document.querySelector('label[for="categoriaFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';

        esconderErroImportacaoCategoria();
    }

    function mostrarErroImportacaoCategoria(mensagem) {
        const el = document.getElementById('categoriaImportacaoErro');
        if (el) {
            el.textContent = mensagem;
            el.classList.remove('d-none');
        }
    }

    function esconderErroImportacaoCategoria() {
        document.getElementById('categoriaImportacaoErro')?.classList.add('d-none');
    }

    function mostrarSucessoImportacaoCategoria(mensagem) {
        const el = document.getElementById('categoriaImportacaoSucesso');
        if (el) {
            el.textContent = mensagem;
            el.classList.remove('d-none');
        }
    }

    function esconderSucessoImportacaoCategoria() {
        document.getElementById('categoriaImportacaoSucesso')?.classList.add('d-none');
    }

    function abrirEliminarCategoria(id) {
        const categoria =
            categorias.find(
                item =>
                    Number(item.id)
                    === Number(id)
            );

        definirValorCategoria(
            'categoriaEliminarId',
            id
        );

        atualizarTextoCategoria(
            'categoriaEliminarNome',
            categoria?.nome || ''
        );

        esconderErroCategoria(
            'categoriaErroEliminar'
        );

        $('#categoriaModalEliminar')
            .modal('show');
    }

    async function eliminarCategoria(
        evento
    ) {
        evento.preventDefault();

        esconderErroCategoria(
            'categoriaErroEliminar'
        );

        const botao =
            document.getElementById(
                'categoriaBtnEliminar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoCategoria(
                botao,
                'A eliminar...'
            );

            const resultado =
                await requisicaoCategoria(
                    'eliminar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );

            retirarFocoCategoria();

            $('#categoriaModalEliminar')
                .modal('hide');

            mostrarMensagemSucessoCategoria(
                resultado.mensagem
            );

            await Promise.all([
                carregarCategorias(),
                carregarOpcoesCategorias()
            ]);
        } catch (erro) {
            mostrarErroCategoria(
                'categoriaErroEliminar',
                erro.message
            );
        } finally {
            desbloquearBotaoCategoria(
                botao,
                htmlOriginal
            );
        }
    }

    function processarCliqueCategoria(
        evento
    ) {
        const botao =
            evento.target.closest(
                '[data-categoria-acao]'
            );

        if (!botao) {
            return;
        }

        const id =
            Number(
                botao.dataset.categoriaId
            );

        if (!id) {
            return;
        }

        if (
            botao.dataset.categoriaAcao
            === 'editar'
        ) {
            editarCategoria(id);
        }

        if (
            botao.dataset.categoriaAcao
            === 'eliminar'
        ) {
            abrirEliminarCategoria(id);
        }
    }

    function limparFormularioCategoria() {
        document
            .getElementById(
                'categoriaForm'
            )
            ?.reset();

        definirValorCategoria(
            'categoriaId',
            ''
        );

        esconderErroCategoria(
            'categoriaErroFormulario'
        );
    }

    function destruirDataTableCategoria() {
        if (
            typeof $ !== 'undefined'
            && $.fn.DataTable
            && $.fn.DataTable.isDataTable(
                '#categoriaTabela'
            )
        ) {
            $('#categoriaTabela')
                .DataTable()
                .destroy();
        }

        categoriaDataTable = null;
    }

    function iniciarDataTableCategoria() {

        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        // Proteção contra inicialização duplicada
        if (
            $.fn.DataTable.isDataTable(
                '#categoriaTabela'
            )
        ) {

            $('#categoriaTabela')
                .DataTable()
                .destroy();
        }

        categoriaDataTable =
            $('#categoriaTabela')
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
                            targets: [6],
                            orderable: false,
                            searchable: false
                        }
                    ],

                    language: {

                        emptyTable:
                            'Nenhuma categoria encontrada.',

                        zeroRecords:
                            'Nenhuma categoria encontrada.',

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
                                'Lista de Categorias',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5]
                            }
                        },

                        {
                            extend:
                                'pdfHtml5',

                            text:
                                '<i class="fa fa-file-pdf"></i> PDF',

                            title:
                                'Lista de Categorias',

                            orientation:
                                'landscape',

                            pageSize:
                                'A4',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5]
                            }
                        },

                        {
                            extend:
                                'print',

                            text:
                                '<i class="fa fa-print"></i> Imprimir',

                            title:
                                'Lista de Categorias',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5]
                            }
                        }
                    ]
                });
    }

    function definirValorCategoria(
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

    function atualizarTextoCategoria(
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

    function mostrarErroCategoria(
        id,
        mensagem
    ) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            mostrarMensagemErroCategoria(
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

    function esconderErroCategoria(id) {
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

    function bloquearBotaoCategoria(
        botao,
        texto
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = true;

        botao.innerHTML = `
            <i class="fa fa-spinner fa-spin"></i>
            ${escaparHtmlCategoria(texto)}
        `;
    }

    function desbloquearBotaoCategoria(
        botao,
        html
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = false;
        botao.innerHTML = html;
    }

    function retirarFocoCategoria() {
        if (
            document.activeElement
            instanceof HTMLElement
        ) {
            document.activeElement.blur();
        }
    }

    function normalizarTextoCategoria(
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

    function escaparHtmlCategoria(
        valor
    ) {
        const elemento =
            document.createElement('div');

        elemento.textContent =
            valor ?? '';

        return elemento.innerHTML;
    }

    function formatarDataCategoria(valor) {
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
            return escaparHtmlCategoria(
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

    function criarBadgeCategoria(status) {
        const nome =
            String(status || '-');

        const normalizado =
            normalizarTextoCategoria(
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
            normalizado === 'arquivada'
        ) {
            classe =
                'badge-warning';
        }

        return `
            <span class="badge ${classe}">
                ${escaparHtmlCategoria(nome)}
            </span>
        `;
    }

    function mostrarMensagemSucessoCategoria(
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

    function mostrarMensagemErroCategoria(
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