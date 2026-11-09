'use strict';

(() => {
    const ARMAZEM_URL =
        '/erpplus/app/ajax/armazem_ajax.php';

    let armazens = [];
    let armazemTabela = null;
    let armazemModuloInicializado = false;

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarArmazens
        );
    } else {
        iniciarArmazens();
    }

    async function iniciarArmazens() {
        if (armazemModuloInicializado) {
            return;
        }

        if (
            !document.getElementById(
                'armazemTabela'
            )
        ) {
            return;
        }

        armazemModuloInicializado = true;

        configurarEventos();

        await Promise.all([
            carregarFiliais(),
            carregarStatus(),
            carregarArmazens()
        ]);
    }

    function configurarEventos() {
        document
            .getElementById(
                'armazemBtnNovo'
            )
            ?.addEventListener(
                'click',
                abrirNovo
            );

        document
            .getElementById(
                'armazemForm'
            )
            ?.addEventListener(
                'submit',
                guardar
            );

        document
            .getElementById(
                'armazemFormEliminar'
            )
            ?.addEventListener(
                'submit',
                eliminar
            );

        document.addEventListener(
            'click',
            processarClique
        );
    }

    async function requisicao(
        acao,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        let url = ARMAZEM_URL;

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

            parametros.set('acao', acao);
            parametros.set('_', Date.now());

            url +=
                '?' + parametros.toString();
        } else {
            const dados =
                opcoes.dados
                    instanceof FormData
                    ? opcoes.dados
                    : new FormData();

            if (!dados.has('acao')) {
                dados.append('acao', acao);
            }

            configuracao.body = dados;
        }

        const resposta =
            await fetch(url, configuracao);

        const texto =
            await resposta.text();

        let resultado;

        try {
            resultado = JSON.parse(texto);
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

    async function carregarArmazens() {

        const tbody =
            document.getElementById(
                'armazemTabelaBody'
            );

        if (!tbody) {
            return;
        }

        esconderErro(
            'armazemErroLista'
        );

        try {

            const resultado =
                await requisicao(
                    'listar'
                );

            armazens =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            // Destruir antes de alterar o tbody
            destruirDataTable();

            // Nunca colocar colspan no tbody
            tbody.innerHTML = '';

            if (armazens.length > 0) {

                tbody.innerHTML =
                    armazens
                        .map(
                            (armazem, indice) =>
                                criarLinha(
                                    armazem,
                                    indice
                                )
                        )
                        .join('');
            }

            iniciarDataTable();

        } catch (erro) {

            destruirDataTable();

            // Mantém o tbody vazio
            tbody.innerHTML = '';

            mostrarErro(
                'armazemErroLista',
                erro.message
            );
        }
    }

    function criarLinha(
        armazem,
        indice
    ) {
        const id =
            Number(armazem.id);

        const numero =
            String(indice + 1)
                .padStart(4, '0');

        return `
            <tr>
                <td>${numero}</td>

                <td>
                    ${escaparHtml(
            armazem.filial_nome
            || 'Geral'
        )}
                </td>

                <td>
                    <strong>
                        ${escaparHtml(
            armazem.nome || '-'
        )}
                    </strong>
                </td>

                <td>
                    ${escaparHtml(
            armazem.codigo || '-'
        )}
                </td>

                <td>
                    ${escaparHtml(
            armazem.responsavel || '-'
        )}
                </td>

                <td>
                    ${escaparHtml(
            armazem.telefone || '-'
        )}
                </td>

                <td>
                    ${escaparHtml(
            armazem.endereco || '-'
        )}
                </td>

                <td>
                    ${criarBadge(
            armazem.status_nome
        )}
                </td>

                <td>
                    ${formatarData(
            armazem.criado_em
        )}
                </td>

                <td class="text-right">
                    <button
                        type="button"
                        class="btn btn-sm btn-info"
                        data-armazem-acao="editar"
                        data-armazem-id="${id}"
                    >
                        <i class="fa fa-edit"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        data-armazem-acao="eliminar"
                        data-armazem-id="${id}"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    async function carregarFiliais() {
        const select =
            document.getElementById(
                'armazemFilialId'
            );

        try {
            const resultado =
                await requisicao('filiais');

            select.replaceChildren(
                new Option(
                    'Armazém geral / Sem filial',
                    ''
                )
            );

            resultado.dados.forEach(
                filial => {
                    const texto =
                        filial.codigo
                            ? `${filial.nome} (${filial.codigo})`
                            : filial.nome;

                    select.add(
                        new Option(
                            texto,
                            String(filial.id)
                        )
                    );
                }
            );
        } catch (erro) {
            select.replaceChildren(
                new Option(
                    'Erro ao carregar filiais',
                    ''
                )
            );
        }
    }

    async function carregarStatus() {
        const select =
            document.getElementById(
                'armazemStatusId'
            );

        try {
            const resultado =
                await requisicao('status');

            select.replaceChildren(
                new Option(
                    'Selecione o estado',
                    ''
                )
            );

            resultado.dados.forEach(
                estado => {
                    select.add(
                        new Option(
                            estado.nome,
                            String(estado.id)
                        )
                    );
                }
            );

            const activo =
                resultado.dados.find(
                    estado => {
                        const nome =
                            normalizar(
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
        }
    }

    function abrirNovo() {
        limparFormulario();

        atualizarTexto(
            'armazemModalTitulo',
            'Novo Armazém'
        );

        const status =
            document.getElementById(
                'armazemStatusId'
            );

        if (status?.dataset.activoId) {
            status.value =
                status.dataset.activoId;
        }

        $('#armazemModal')
            .modal('show');
    }

    async function editar(id) {
        try {
            const resultado =
                await requisicao(
                    'buscar',
                    {
                        parametros: { id }
                    }
                );

            const armazem =
                resultado.dados;

            limparFormulario();

            definirValor(
                'armazemId',
                armazem.id
            );

            definirValor(
                'armazemFilialId',
                armazem.filial_id
            );

            definirValor(
                'armazemStatusId',
                armazem.status_id
            );

            definirValor(
                'armazemNome',
                armazem.nome
            );

            definirValor(
                'armazemCodigo',
                armazem.codigo
            );

            definirValor(
                'armazemResponsavel',
                armazem.responsavel
            );

            definirValor(
                'armazemTelefone',
                armazem.telefone
            );

            definirValor(
                'armazemEndereco',
                armazem.endereco
            );

            atualizarTexto(
                'armazemModalTitulo',
                'Editar Armazém'
            );

            $('#armazemModal')
                .modal('show');
        } catch (erro) {
            mostrarMensagemErro(
                erro.message
            );
        }
    }

    async function guardar(evento) {
        evento.preventDefault();

        const botao =
            document.getElementById(
                'armazemBtnGuardar'
            );

        const original =
            botao.innerHTML;

        try {
            bloquearBotao(
                botao,
                'A guardar...'
            );

            const resultado =
                await requisicao(
                    'salvar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );

            document.activeElement?.blur();

            $('#armazemModal')
                .modal('hide');

            mostrarMensagemSucesso(
                resultado.mensagem
            );

            await carregarArmazens();
        } catch (erro) {
            mostrarErro(
                'armazemErroFormulario',
                erro.message
            );
        } finally {
            desbloquearBotao(
                botao,
                original
            );
        }
    }

    function abrirEliminar(id) {
        const armazem =
            armazens.find(
                item =>
                    Number(item.id)
                    === Number(id)
            );

        definirValor(
            'armazemEliminarId',
            id
        );

        atualizarTexto(
            'armazemEliminarNome',
            armazem?.nome || ''
        );

        $('#armazemModalEliminar')
            .modal('show');
    }

    async function eliminar(evento) {
        evento.preventDefault();

        const botao =
            document.getElementById(
                'armazemBtnEliminar'
            );

        const original =
            botao.innerHTML;

        try {
            bloquearBotao(
                botao,
                'A eliminar...'
            );

            const resultado =
                await requisicao(
                    'eliminar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );

            document.activeElement?.blur();

            $('#armazemModalEliminar')
                .modal('hide');

            mostrarMensagemSucesso(
                resultado.mensagem
            );

            await carregarArmazens();
        } catch (erro) {
            mostrarErro(
                'armazemErroEliminar',
                erro.message
            );
        } finally {
            desbloquearBotao(
                botao,
                original
            );
        }
    }

    function processarClique(evento) {
        const botao =
            evento.target.closest(
                '[data-armazem-acao]'
            );

        if (!botao) {
            return;
        }

        const id =
            Number(
                botao.dataset.armazemId
            );

        if (
            botao.dataset.armazemAcao
            === 'editar'
        ) {
            editar(id);
        }

        if (
            botao.dataset.armazemAcao
            === 'eliminar'
        ) {
            abrirEliminar(id);
        }
    }

    function limparFormulario() {
        document
            .getElementById(
                'armazemForm'
            )
            ?.reset();

        definirValor(
            'armazemId',
            ''
        );

        esconderErro(
            'armazemErroFormulario'
        );
    }

    function destruirDataTable() {
        if (
            typeof $ !== 'undefined'
            && $.fn.DataTable
            && $.fn.DataTable.isDataTable(
                '#armazemTabela'
            )
        ) {
            $('#armazemTabela')
                .DataTable()
                .destroy();
        }

        armazemTabela = null;
    }

    function iniciarDataTable() {

        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        // Proteção contra inicialização duplicada
        if (
            $.fn.DataTable.isDataTable(
                '#armazemTabela'
            )
        ) {

            $('#armazemTabela')
                .DataTable()
                .destroy();
        }

        armazemTabela =
            $('#armazemTabela')
                .DataTable({

                    pageLength: 10,

                    lengthMenu: [
                        [5, 10, 25, 50, 100],
                        [5, 10, 25, 50, 100]
                    ],

                    responsive: true,

                    autoWidth: false,

                    order: [
                        [2, 'asc']
                    ],

                    columnDefs: [
                        {
                            targets: [9],
                            orderable: false,
                            searchable: false
                        }
                    ],

                    language: {

                        emptyTable:
                            'Nenhum armazém encontrado.',

                        zeroRecords:
                            'Nenhum armazém encontrado.',

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
                    }
                });
    }

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

        if (elemento) {
            elemento.textContent = '';

            elemento.classList.add(
                'd-none'
            );
        }
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

    function escaparHtml(valor) {
        const div =
            document.createElement('div');

        div.textContent =
            valor ?? '';

        return div.innerHTML;
    }

    function normalizar(valor) {
        return String(valor || '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            );
    }

    function criarBadge(status) {
        const nome =
            String(status || '-');

        const normalizado =
            normalizar(nome);

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
                'badge-secondary';
        } else if (
            normalizado === 'suspenso'
        ) {
            classe =
                'badge-warning';
        } else if (
            normalizado === 'encerrado'
        ) {
            classe =
                'badge-danger';
        }

        return `
            <span class="badge ${classe}">
                ${escaparHtml(nome)}
            </span>
        `;
    }

    function formatarData(valor) {
        if (!valor) {
            return '-';
        }

        const data =
            new Date(
                String(valor)
                    .replace(' ', 'T')
            );

        return Number.isNaN(
            data.getTime()
        )
            ? escaparHtml(valor)
            : data.toLocaleString(
                'pt-MZ'
            );
    }

    function mostrarMensagemSucesso(
        mensagem
    ) {
        if (typeof Swal !== 'undefined') {
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
        if (typeof Swal !== 'undefined') {
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