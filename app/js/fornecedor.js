'use strict';

(() => {
    const FORNECEDOR_UR =
        '/erpplus/app/ajax/fornecedor_ajax.php';

    let fornecedores = [];
    let fornecedorDataTable = null;
    let fornecedorModuloInicializado = false;
    let fornecedorExtratoDataTable = null;
    let fornecedorImportacaoToken = '';
    let fornecedorImportacaoDados = null;
    let fornecedorImportacaoProcessando = false;

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarModuloFornecedores
        );
    } else {
        iniciarModuloFornecedores();
    }

    async function iniciarModuloFornecedores() {
        if (fornecedorModuloInicializado) {
            return;
        }

        if (
            !document.getElementById(
                'fornecedorTabela'
            )
        ) {
            return;
        }

        fornecedorModuloInicializado = true;

        configurarEventosFornecedor();

        await Promise.all([
            carregarStatusFornecedor(),
            carregarFornecedores()
        ]);
    }

    function configurarEventosFornecedor() {

        $('#fornecedorModalExtrato')
            .on(
                'hidden.bs.modal',
                function () {

                    destruirDataTableExtratoFornecedor();

                    const tbody =
                        document.getElementById(
                            'fornecedorExtratoTabelaBody'
                        );

                    if (tbody) {
                        tbody.innerHTML = '';
                    }
                }
            );

        document
            .getElementById(
                'fornecedorBtnNovo'
            )
            ?.addEventListener(
                'click',
                abrirNovoFornecedor
            );

        document
            .getElementById(
                'fornecedorForm'
            )
            ?.addEventListener(
                'submit',
                guardarFornecedor
            );

        document.getElementById('fornecedorBtnImportar')?.addEventListener('click', abrirImportacaoFornecedor);
        document.getElementById('fornecedorBtnModeloExcel')?.addEventListener('click', baixarModeloExcelFornecedor);
        document.getElementById('fornecedorBtnValidarExcel')?.addEventListener('click', validarImportacaoFornecedor);
        document.getElementById('fornecedorBtnConfirmarImportacao')?.addEventListener('click', confirmarImportacaoFornecedor);
        document.getElementById('fornecedorBtnVoltarImportacao')?.addEventListener('click', voltarImportacaoFornecedor);
        document.getElementById('fornecedorFicheiroExcel')?.addEventListener('change', alterarFicheiroExcelFornecedor);

        document
            .getElementById(
                'fornecedorFormEliminar'
            )
            ?.addEventListener(
                'submit',
                eliminarFornecedor
            );

        document
            .getElementById(
                'fornecedorExtratoBtnPesquisar'
            )
            ?.addEventListener(
                'click',
                carregarExtratoFornecedor
            );

        document.addEventListener(
            'click',
            processarCliqueFornecedor
        );
    }

    async function requisicaoFornecedor(
        acao,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        let url =
            FORNECEDOR_UR;

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
                'Resposta inválida do fornecedor:',
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


    // Adicionar as funções auxiliares de importação:
    function abrirImportacaoFornecedor() {
        fornecedorImportacaoToken = '';
        fornecedorImportacaoDados = null;
        fornecedorImportacaoProcessando = false;

        const ficheiro = document.getElementById('fornecedorFicheiroExcel');
        if (ficheiro) ficheiro.value = '';

        const label = document.querySelector('label[for="fornecedorFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';

        document.getElementById('fornecedorImportacaoPasso1')?.classList.remove('d-none');
        document.getElementById('fornecedorImportacaoPasso2')?.classList.add('d-none');
        document.getElementById('fornecedorBtnValidarExcel')?.classList.remove('d-none');
        document.getElementById('fornecedorBtnConfirmarImportacao')?.classList.add('d-none');
        document.getElementById('fornecedorBtnVoltarImportacao')?.classList.add('d-none');

        esconderErroImportacaoFornecedor();
        esconderSucessoImportacaoFornecedor();

        $('#fornecedorModalImportar').modal('show');
    }

    function alterarFicheiroExcelFornecedor(evento) {
        const ficheiro = evento.target.files?.[0];
        const label = document.querySelector('label[for="fornecedorFicheiroExcel"]');
        if (label) {
            label.textContent = ficheiro ? ficheiro.name : 'Selecione o ficheiro';
        }
    }

    function baixarModeloExcelFornecedor() {
        const url = FORNECEDOR_UR + '?acao=baixar_modelo_excel&_=' + Date.now();
        window.location.href = url;
    }

    async function validarImportacaoFornecedor() {
        if (fornecedorImportacaoProcessando) return;

        esconderErroImportacaoFornecedor();
        esconderSucessoImportacaoFornecedor();

        const input = document.getElementById('fornecedorFicheiroExcel');
        const ficheiro = input?.files?.[0];

        if (!ficheiro) {
            mostrarErroImportacaoFornecedor('Selecione um ficheiro Excel antes de continuar.');
            return;
        }

        const extensao = ficheiro.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(extensao)) {
            mostrarErroImportacaoFornecedor('O ficheiro deve estar no formato XLSX ou XLS.');
            return;
        }

        const botao = document.getElementById('fornecedorBtnValidarExcel');
        const htmlOriginal = botao?.innerHTML || '';

        try {
            fornecedorImportacaoProcessando = true;
            bloquearBotaoFornecedor(botao, 'A validar...');

            const dados = new FormData();
            dados.append('ficheiro', ficheiro);

            const resultado = await requisicaoFornecedor('validar_importacao', {
                method: 'POST',
                dados
            });

            fornecedorImportacaoDados = resultado.dados || {};
            fornecedorImportacaoToken = String(fornecedorImportacaoDados.token || '');

            if (!fornecedorImportacaoToken) {
                throw new Error('O servidor não devolveu o token da importação.');
            }

            renderizarResultadoImportacaoFornecedor(fornecedorImportacaoDados);

            document.getElementById('fornecedorImportacaoPasso1')?.classList.add('d-none');
            document.getElementById('fornecedorImportacaoPasso2')?.classList.remove('d-none');
            document.getElementById('fornecedorBtnValidarExcel')?.classList.add('d-none');
            document.getElementById('fornecedorBtnVoltarImportacao')?.classList.remove('d-none');

            const totalValidos = Number(fornecedorImportacaoDados.total_validos || 0);
            if (totalValidos > 0) {
                document.getElementById('fornecedorBtnConfirmarImportacao')?.classList.remove('d-none');
            } else {
                document.getElementById('fornecedorBtnConfirmarImportacao')?.classList.add('d-none');
            }
        } catch (erro) {
            mostrarErroImportacaoFornecedor(erro.message);
        } finally {
            fornecedorImportacaoProcessando = false;
            desbloquearBotaoFornecedor(botao, htmlOriginal);
        }
    }

    function renderizarResultadoImportacaoFornecedor(dados) {
        const validos = Array.isArray(dados.validos) ? dados.validos : [];
        const erros = Array.isArray(dados.erros) ? dados.erros : [];

        renderizarFornecedoresValidosImportacao(validos);
        renderizarErrosImportacaoFornecedor(erros);

        document.getElementById('fornecedorImportacaoValidosContainer')?.classList.toggle('d-none', validos.length === 0);
        document.getElementById('fornecedorImportacaoErrosContainer')?.classList.toggle('d-none', erros.length === 0);
    }

    function renderizarFornecedoresValidosImportacao(validos) {
        const tbody = document.getElementById('fornecedorImportacaoValidos');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (validos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Nenhum fornecedor válido encontrado.</td></tr>`;
            return;
        }

        validos.forEach(item => {
            const preview = item.preview || {};
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>${escaparHtmlFornecedor(item.linha ?? '')}</td>
            <td><strong>${escaparHtmlFornecedor(preview.nome ?? '')}</strong></td>
            <td>${escaparHtmlFornecedor(preview.nuit ?? '')}</td>
            <td>${escaparHtmlFornecedor(preview.telefone ?? '')}</td>
            <td>${escaparHtmlFornecedor(preview.email ?? '')}</td>
            <td>${escaparHtmlFornecedor(preview.cidade ?? '')}</td>
        `;
            tbody.appendChild(tr);
        });
    }

    function renderizarErrosImportacaoFornecedor(erros) {
        const tbody = document.getElementById('fornecedorImportacaoErros');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (erros.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Nenhum erro encontrado.</td></tr>`;
            return;
        }

        erros.forEach(item => {
            const dados = item.dados || {};
            let mensagens = item.erros || ['Erro de validação.'];
            if (!Array.isArray(mensagens)) mensagens = [mensagens];

            const listaErros = mensagens.map(m => `<div>${escaparHtmlFornecedor(m)}</div>`).join('');
            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td>${escaparHtmlFornecedor(item.linha ?? '-')}</td>
            <td>${escaparHtmlFornecedor(dados.nome ?? '')}</td>
            <td>${escaparHtmlFornecedor(dados.nuit ?? '-')}</td>
            <td class="text-danger">${listaErros}</td>
        `;
            tbody.appendChild(tr);
        });
    }

    async function confirmarImportacaoFornecedor() {
        if (fornecedorImportacaoProcessando || !fornecedorImportacaoToken) return;

        const totalValidos = Number(fornecedorImportacaoDados?.total_validos || 0);
        if (totalValidos <= 0) return;

        if (typeof Swal !== 'undefined') {
            const confirmacao = await Swal.fire({
                icon: 'question',
                title: 'Confirmar importação?',
                html: `Serão registados <strong>${totalValidos}</strong> fornecedores.<br><br>Os fornecedores existentes não serão alterados.`,
                showCancelButton: true,
                confirmButtonText: 'Sim, importar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirmacao.isConfirmed) return;
        }

        const botao = document.getElementById('fornecedorBtnConfirmarImportacao');
        const htmlOriginal = botao?.innerHTML || '';

        try {
            fornecedorImportacaoProcessando = true;
            bloquearBotaoFornecedor(botao, 'A importar...');

            const dados = new FormData();
            dados.append('token', fornecedorImportacaoToken);

            const resultado = await requisicaoFornecedor('confirmar_importacao', {
                method: 'POST',
                dados
            });

            mostrarMensagemSucessoFornecedor(resultado.mensagem || 'Importação concluída com sucesso.');
            fornecedorImportacaoToken = '';
            fornecedorImportacaoDados = null;

            document.getElementById('fornecedorBtnConfirmarImportacao')?.classList.add('d-none');
            await carregarFornecedores();

            setTimeout(() => {
                $('#fornecedorModalImportar').modal('hide');
            }, 1200);
        } catch (erro) {
            mostrarErroImportacaoFornecedor(erro.message);
        } finally {
            fornecedorImportacaoProcessando = false;
            desbloquearBotaoFornecedor(botao, htmlOriginal);
        }
    }

    function voltarImportacaoFornecedor() {
        fornecedorImportacaoToken = '';
        fornecedorImportacaoDados = null;

        document.getElementById('fornecedorImportacaoPasso2')?.classList.add('d-none');
        document.getElementById('fornecedorImportacaoPasso1')?.classList.remove('d-none');
        document.getElementById('fornecedorBtnConfirmarImportacao')?.classList.add('d-none');
        document.getElementById('fornecedorBtnVoltarImportacao')?.classList.add('d-none');
        document.getElementById('fornecedorBtnValidarExcel')?.classList.remove('d-none');

        const input = document.getElementById('fornecedorFicheiroExcel');
        if (input) input.value = '';

        const label = document.querySelector('label[for="fornecedorFicheiroExcel"]');
        if (label) label.textContent = 'Selecione o ficheiro';

        esconderErroImportacaoFornecedor();
    }

    function mostrarErroImportacaoFornecedor(mensagem) {
        const el = document.getElementById('fornecedorImportacaoErro');
        if (el) {
            el.textContent = mensagem;
            el.classList.remove('d-none');
        }
    }

    function esconderErroImportacaoFornecedor() {
        document.getElementById('fornecedorImportacaoErro')?.classList.add('d-none');
    }

    function mostrarSucessoImportacaoFornecedor(mensagem) {
        const el = document.getElementById('fornecedorImportacaoSucesso');
        if (el) {
            el.textContent = mensagem;
            el.classList.remove('d-none');
        }
    }

    function esconderSucessoImportacaoFornecedor() {
        document.getElementById('fornecedorImportacaoSucesso')?.classList.add('d-none');
    }

    async function carregarFornecedores() {

        const tbody =
            document.getElementById(
                'fornecedorTabelaBody'
            );

        if (!tbody) {
            return;
        }

        esconderErroFornecedor(
            'fornecedorErroLista'
        );

        try {

            const resultado =
                await requisicaoFornecedor(
                    'listar'
                );

            fornecedores =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            // Destruir antes de alterar o tbody
            destruirDataTableFornecedor();

            // Nunca inserir colspan no tbody
            tbody.innerHTML = '';

            if (fornecedores.length > 0) {

                tbody.innerHTML =
                    fornecedores
                        .map(
                            (fornecedor, indice) =>
                                criarLinhaFornecedor(
                                    fornecedor,
                                    indice
                                )
                        )
                        .join('');
            }

            iniciarDataTableFornecedor();

        } catch (erro) {

            destruirDataTableFornecedor();

            tbody.innerHTML = '';

            mostrarErroFornecedor(
                'fornecedorErroLista',
                erro.message
            );
        }
    }

    function criarLinhaFornecedor(
        fornecedor,
        indice
    ) {
        const id =
            Number(fornecedor.id);

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
                        ${escaparHtmlFornecedor(
            fornecedor.nome || '-'
        )}
                    </strong>

                    ${fornecedor.nome_comercial
                ? `
                                <div class="small text-muted">
                                    ${escaparHtmlFornecedor(
                    fornecedor.nome_comercial
                )}
                                </div>
                            `
                : ''
            }
                </td>

                <td>
                    ${escaparHtmlFornecedor(
                fornecedor.nuit || '-'
            )}
                </td>

                <td>
                    ${escaparHtmlFornecedor(
                fornecedor.telefone || '-'
            )}
                </td>

                <td>
                    ${escaparHtmlFornecedor(
                fornecedor.email || '-'
            )}
                </td>

                <td>
                    ${escaparHtmlFornecedor(
                fornecedor.cidade || '-'
            )}
                </td>

                <td>
                    ${escaparHtmlFornecedor(
                fornecedor.contacto || '-'
            )}
                </td>

                <td class="text-right">

    <button
        type="button"
        class="btn btn-link p-0 text-right"
        style="text-decoration:none;"
        data-fornecedor-acao="extrato"
        data-fornecedor-id="${id}"
        title="Ver extrato do fornecedor">

        ${criarSaldoFornecedor(
                fornecedor.saldo_fornecedor
            )}

    </button>

</td>

                <td>
                    ${criarBadgeFornecedor(
                fornecedor.status_nome
            )}
                </td>

                <td>
                    ${formatarDataFornecedor(
                fornecedor.criado_em
            )}
                </td>

                <td class="text-right">
                    <button
                        type="button"
                        class="btn btn-sm btn-info"
                        title="Editar"
                        data-fornecedor-acao="editar"
                        data-fornecedor-id="${id}"
                    >
                        <i class="fa fa-edit"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        title="Eliminar"
                        data-fornecedor-acao="eliminar"
                        data-fornecedor-id="${id}"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    async function carregarStatusFornecedor() {
        const select =
            document.getElementById(
                'fornecedorStatusId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {
            const resultado =
                await requisicaoFornecedor(
                    'status'
                );

            const estados =
                Array.isArray(resultado.dados)
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
                        normalizarTextoFornecedor(
                            estado.nome
                        );

                    return nome === 'activo'
                        || nome === 'ativo';
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

    function abrirNovoFornecedor() {
        limparFormularioFornecedor();

        atualizarTextoFornecedor(
            'fornecedorModalTitulo',
            'Novo Fornecedor'
        );

        const status =
            document.getElementById(
                'fornecedorStatusId'
            );

        if (status?.dataset.activoId) {
            status.value =
                status.dataset.activoId;
        }

        $('#fornecedorModal')
            .modal('show');
    }

    async function editarFornecedor(id) {
        esconderErroFornecedor(
            'fornecedorErroFormulario'
        );

        try {
            const resultado =
                await requisicaoFornecedor(
                    'buscar',
                    {
                        parametros: {
                            id
                        }
                    }
                );

            const fornecedor =
                resultado.dados || {};

            limparFormularioFornecedor();

            definirValorFornecedor(
                'fornecedorId',
                fornecedor.id
            );

            definirValorFornecedor(
                'fornecedorStatusId',
                fornecedor.status_id
            );

            definirValorFornecedor(
                'fornecedorNome',
                fornecedor.nome
            );

            definirValorFornecedor(
                'fornecedorNomeComercial',
                fornecedor.nome_comercial
            );

            definirValorFornecedor(
                'fornecedorNuit',
                fornecedor.nuit
            );

            definirValorFornecedor(
                'fornecedorTelefone',
                fornecedor.telefone
            );

            definirValorFornecedor(
                'fornecedorEmail',
                fornecedor.email
            );

            definirValorFornecedor(
                'fornecedorContacto',
                fornecedor.contacto
            );

            definirValorFornecedor(
                'fornecedorEndereco',
                fornecedor.endereco
            );

            definirValorFornecedor(
                'fornecedorCidade',
                fornecedor.cidade
            );

            definirValorFornecedor(
                'fornecedorPais',
                fornecedor.pais
            );

            definirValorFornecedor(
                'fornecedorObservacoes',
                fornecedor.observacoes
            );

            atualizarTextoFornecedor(
                'fornecedorModalTitulo',
                'Editar Fornecedor'
            );

            $('#fornecedorModal')
                .modal('show');
        } catch (erro) {
            mostrarMensagemErroFornecedor(
                erro.message
            );
        }
    }

    async function guardarFornecedor(
        evento
    ) {
        evento.preventDefault();

        esconderErroFornecedor(
            'fornecedorErroFormulario'
        );

        const botao =
            document.getElementById(
                'fornecedorBtnGuardar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoFornecedor(
                botao,
                'A guardar...'
            );

            const resultado =
                await requisicaoFornecedor(
                    'salvar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );

            retirarFocoFornecedor();

            $('#fornecedorModal')
                .modal('hide');

            mostrarMensagemSucessoFornecedor(
                resultado.mensagem
            );

            await carregarFornecedores();
        } catch (erro) {
            mostrarErroFornecedor(
                'fornecedorErroFormulario',
                erro.message
            );
        } finally {
            desbloquearBotaoFornecedor(
                botao,
                htmlOriginal
            );
        }
    }

    function abrirEliminarFornecedor(id) {
        const fornecedor =
            fornecedores.find(
                item =>
                    Number(item.id)
                    === Number(id)
            );

        definirValorFornecedor(
            'fornecedorEliminarId',
            id
        );

        atualizarTextoFornecedor(
            'fornecedorEliminarNome',
            fornecedor?.nome || ''
        );

        esconderErroFornecedor(
            'fornecedorErroEliminar'
        );

        $('#fornecedorModalEliminar')
            .modal('show');
    }

    async function eliminarFornecedor(
        evento
    ) {
        evento.preventDefault();

        esconderErroFornecedor(
            'fornecedorErroEliminar'
        );

        const botao =
            document.getElementById(
                'fornecedorBtnEliminar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoFornecedor(
                botao,
                'A eliminar...'
            );

            const resultado =
                await requisicaoFornecedor(
                    'eliminar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );

            retirarFocoFornecedor();

            $('#fornecedorModalEliminar')
                .modal('hide');

            mostrarMensagemSucessoFornecedor(
                resultado.mensagem
            );

            await carregarFornecedores();
        } catch (erro) {
            mostrarErroFornecedor(
                'fornecedorErroEliminar',
                erro.message
            );
        } finally {
            desbloquearBotaoFornecedor(
                botao,
                htmlOriginal
            );
        }
    }

    function processarCliqueFornecedor(
        evento
    ) {
        const botao =
            evento.target.closest(
                '[data-fornecedor-acao]'
            );

        if (!botao) {
            return;
        }

        const id =
            Number(
                botao.dataset.fornecedorId
            );

        if (!id) {
            return;
        }

        if (
            botao.dataset.fornecedorAcao
            === 'editar'
        ) {
            editarFornecedor(id);
        }

        if (
            botao.dataset.fornecedorAcao
            === 'eliminar'
        ) {
            abrirEliminarFornecedor(id);
        }

        if (
            botao.dataset.fornecedorAcao
            === 'extrato'
        ) {

            abrirExtratoFornecedor(
                id
            );

            return;
        }
    }

    async function abrirExtratoFornecedor(
        id
    ) {

        const fornecedor =
            fornecedores.find(
                item =>
                    Number(item.id)
                    === Number(id)
            );


        definirValorFornecedor(
            'fornecedorExtratoId',
            id
        );


        definirValorFornecedor(
            'fornecedorExtratoDataInicial',
            ''
        );


        definirValorFornecedor(
            'fornecedorExtratoDataFinal',
            ''
        );


        atualizarTextoFornecedor(
            'fornecedorExtratoTitulo',
            'Extrato do Fornecedor'
        );


        atualizarTextoFornecedor(
            'fornecedorExtratoIdentificacao',
            fornecedor
                ? (
                    fornecedor.nome
                    + (
                        fornecedor.nuit
                            ? ' | NUIT: '
                            + fornecedor.nuit
                            : ''
                    )
                )
                : ''
        );


        $('#fornecedorModalExtrato')
            .modal('show');


        await carregarExtratoFornecedor();
    }

    async function carregarExtratoFornecedor() {

        const fornecedorId =
            Number(
                document
                    .getElementById(
                        'fornecedorExtratoId'
                    )
                    ?.value
                || 0
            );


        if (!fornecedorId) {
            return;
        }


        esconderErroFornecedor(
            'fornecedorExtratoErro'
        );


        try {

            const dataInicial =
                document
                    .getElementById(
                        'fornecedorExtratoDataInicial'
                    )
                    ?.value
                || '';


            const dataFinal =
                document
                    .getElementById(
                        'fornecedorExtratoDataFinal'
                    )
                    ?.value
                || '';


            const resultado =
                await requisicaoFornecedor(
                    'extrato',
                    {
                        parametros: {

                            fornecedor_id:
                                fornecedorId,

                            data_inicial:
                                dataInicial,

                            data_final:
                                dataFinal
                        }
                    }
                );


            const dados =
                resultado.dados
                || {};


            preencherResumoExtratoFornecedor(
                dados
            );


            preencherMovimentosExtratoFornecedor(
                dados.movimentos
                || []
            );


        } catch (erro) {

            mostrarErroFornecedor(
                'fornecedorExtratoErro',
                erro.message
            );
        }
    }

    function preencherResumoExtratoFornecedor(
        dados
    ) {

        atualizarTextoFornecedor(
            'fornecedorExtratoSaldoAnterior',
            formatarMoedaExtratoFornecedor(
                dados.saldo_anterior
            )
        );


        atualizarTextoFornecedor(
            'fornecedorExtratoDebitos',
            formatarMoedaExtratoFornecedor(
                dados.total_debito
            )
        );


        atualizarTextoFornecedor(
            'fornecedorExtratoCreditos',
            formatarMoedaExtratoFornecedor(
                dados.total_credito
            )
        );


        atualizarTextoFornecedor(
            'fornecedorExtratoSaldoAtual',
            formatarMoedaExtratoFornecedor(
                dados.saldo_atual
            )
        );
    }

    function preencherMovimentosExtratoFornecedor(
        movimentos
    ) {

        const tbody =
            document.getElementById(
                'fornecedorExtratoTabelaBody'
            );

        if (!tbody) {
            return;
        }


        /*
         * Destruir o DataTable antes
         * de alterar o tbody.
         */
        destruirDataTableExtratoFornecedor();


        tbody.innerHTML = '';


        if (
            Array.isArray(movimentos)
            && movimentos.length > 0
        ) {

            tbody.innerHTML =
                movimentos
                    .map(
                        movimento => {

                            const documento =
                                movimento.numero_documento
                                || movimento.referencia
                                || movimento.numero
                                || '-';


                            const descricao =
                                movimento.item_descricao
                                || movimento.descricao
                                || '-';


                            const conta =
                                [
                                    movimento.conta_codigo,
                                    movimento.conta_nome
                                ]
                                    .filter(Boolean)
                                    .join(' - ');


                            return `
                            <tr>

                                <td>
                                    ${formatarDataSimplesFornecedor(
                                movimento.data_lancamento
                            )}
                                </td>

                                <td>
                                    ${escaparHtmlFornecedor(
                                documento
                            )}
                                </td>

                                <td>
                                    ${escaparHtmlFornecedor(
                                descricao
                            )}
                                </td>

                                <td>
                                    ${escaparHtmlFornecedor(
                                conta || '-'
                            )}
                                </td>

                                <td
                                    class="text-right"
                                    data-order="${Number(
                                movimento.debito || 0
                            )}"
                                >
                                    ${formatarMoedaExtratoFornecedor(
                                movimento.debito
                            )}
                                </td>

                                <td
                                    class="text-right"
                                    data-order="${Number(
                                movimento.credito || 0
                            )}"
                                >
                                    ${formatarMoedaExtratoFornecedor(
                                movimento.credito
                            )}
                                </td>

                                <td
                                    class="text-right"
                                    data-order="${Number(
                                movimento.saldo_acumulado || 0
                            )}"
                                >
                                    ${formatarSaldoExtratoFornecedor(
                                movimento.saldo_acumulado
                            )}
                                </td>

                            </tr>
                        `;
                        }
                    )
                    .join('');
        }


        iniciarDataTableExtratoFornecedor();
    }

    function formatarMoedaExtratoFornecedor(
        valor
    ) {

        return Number(
            valor || 0
        ).toLocaleString(
            'pt-MZ',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ) + ' MZN';
    }


    function formatarSaldoExtratoFornecedor(
        valor
    ) {

        const saldo =
            Number(
                valor || 0
            );


        const moeda =
            Math.abs(saldo)
                .toLocaleString(
                    'pt-MZ',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                );


        if (saldo < -0.005) {

            return `
            <strong class="text-danger">
                - ${moeda} MZN
            </strong>
        `;
        }


        if (saldo > 0.005) {

            return `
            <strong class="text-success">
                + ${moeda} MZN
            </strong>
        `;
        }


        return `
        <strong class="text-muted">
            0,00 MZN
        </strong>
    `;
    }


    function formatarDataSimplesFornecedor(
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
            return escaparHtmlFornecedor(
                valor
            );
        }


        return (
            partes[2]
            + '/'
            + partes[1]
            + '/'
            + partes[0]
        );
    }

    function limparFormularioFornecedor() {
        document
            .getElementById(
                'fornecedorForm'
            )
            ?.reset();

        definirValorFornecedor(
            'fornecedorId',
            ''
        );

        definirValorFornecedor(
            'fornecedorPais',
            'Moçambique'
        );

        esconderErroFornecedor(
            'fornecedorErroFormulario'
        );
    }

    function destruirDataTableExtratoFornecedor() {

        if (
            typeof $ !== 'undefined'
            && $.fn.DataTable
            && $.fn.DataTable.isDataTable(
                '#fornecedorExtratoTabela'
            )
        ) {

            $('#fornecedorExtratoTabela')
                .DataTable()
                .destroy();
        }


        fornecedorExtratoDataTable =
            null;
    }

    function iniciarDataTableExtratoFornecedor() {

        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }


        if (
            $.fn.DataTable.isDataTable(
                '#fornecedorExtratoTabela'
            )
        ) {

            $('#fornecedorExtratoTabela')
                .DataTable()
                .destroy();
        }


        const fornecedor =
            fornecedores.find(
                item =>
                    Number(item.id)
                    === Number(
                        document
                            .getElementById(
                                'fornecedorExtratoId'
                            )
                            ?.value
                        || 0
                    )
            );


        const nomeFornecedor =
            fornecedor?.nome
            || 'Fornecedor';


        fornecedorExtratoDataTable =
            $('#fornecedorExtratoTabela')
                .DataTable({

                    pageLength: 10,

                    lengthMenu: [
                        [5, 10, 25, 50, 100],
                        [5, 10, 25, 50, 100]
                    ],

                    responsive: false,

                    autoWidth: false,

                    order: [
                        [0, 'asc']
                    ],


                    language: {

                        emptyTable:
                            'Nenhum movimento encontrado.',

                        zeroRecords:
                            'Nenhum movimento encontrado.',

                        search:
                            'Pesquisar:',

                        lengthMenu:
                            'Mostrar _MENU_ registos',

                        info:
                            'Mostrando _START_ a _END_ de _TOTAL_ movimentos',

                        infoEmpty:
                            'Mostrando 0 a 0 de 0 movimentos',

                        infoFiltered:
                            '(filtrado de _MAX_ movimentos)',

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
                        "<'row mb-2'"
                        + "<'col-sm-12 col-md-8 d-flex align-items-center'Bl>"
                        + "<'col-sm-12 col-md-4'f>"
                        + ">"
                        + "<'row'<'col-sm-12'tr>>"
                        + "<'row mt-2'"
                        + "<'col-sm-12 col-md-5'i>"
                        + "<'col-sm-12 col-md-7'p>"
                        + ">",


                    buttons: [

                        // =====================================
                        // EXCEL
                        // =====================================

                        {
                            extend:
                                'excelHtml5',

                            text:
                                '<i class="fa fa-file-excel"></i> Excel',

                            title:
                                'Extrato do Fornecedor - '
                                + nomeFornecedor,

                            filename:
                                'Extrato_Fornecedor_'
                                + sanitizarNomeArquivoFornecedor(
                                    nomeFornecedor
                                ),

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6],

                                format: {

                                    body:
                                        limparConteudoExportacaoFornecedor
                                }
                            }
                        },


                        // =====================================
                        // PDF
                        // =====================================

                        {
                            extend:
                                'pdfHtml5',

                            text:
                                '<i class="fa fa-file-pdf"></i> PDF',

                            title:
                                'Extrato do Fornecedor',

                            messageTop:
                                criarCabecalhoExportacaoExtratoFornecedor(
                                    fornecedor
                                ),

                            filename:
                                'Extrato_Fornecedor_'
                                + sanitizarNomeArquivoFornecedor(
                                    nomeFornecedor
                                ),

                            orientation:
                                'landscape',

                            pageSize:
                                'A4',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6],

                                format: {

                                    body:
                                        limparConteudoExportacaoFornecedor
                                }
                            },

                            customize:
                                function (doc) {

                                    doc.defaultStyle.fontSize =
                                        8;

                                    doc.styles.tableHeader.fontSize =
                                        8;

                                    doc.styles.title.fontSize =
                                        14;

                                    doc.content.push({
                                        text:
                                            criarResumoTextoExtratoFornecedor(),
                                        margin:
                                            [0, 12, 0, 0],
                                        fontSize:
                                            9
                                    });
                                }
                        },


                        // =====================================
                        // IMPRIMIR
                        // =====================================

                        {
                            extend:
                                'print',

                            text:
                                '<i class="fa fa-print"></i> Imprimir',

                            title:
                                'Extrato do Fornecedor',

                            messageTop:
                                criarCabecalhoImpressaoExtratoFornecedor(
                                    fornecedor
                                ),

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6],

                                format: {

                                    body:
                                        limparConteudoExportacaoFornecedor
                                }
                            },

                            customize:
                                function (janela) {

                                    const documento =
                                        janela.document;


                                    const resumo =
                                        documento.createElement(
                                            'div'
                                        );


                                    resumo.style.marginTop =
                                        '15px';

                                    resumo.style.marginBottom =
                                        '15px';

                                    resumo.innerHTML =
                                        criarResumoHtmlExtratoFornecedor();


                                    const tabela =
                                        documento.querySelector(
                                            'table'
                                        );


                                    if (tabela) {

                                        tabela.parentNode
                                            .insertBefore(
                                                resumo,
                                                tabela
                                            );
                                    }
                                }
                        }
                    ]
                });
    }

    function limparConteudoExportacaoFornecedor(
        data
    ) {

        const elemento =
            document.createElement(
                'div'
            );

        elemento.innerHTML =
            String(
                data ?? ''
            );


        return (
            elemento.textContent
            || elemento.innerText
            || ''
        )
            .replace(/\s+/g, ' ')
            .trim();
    }


    function sanitizarNomeArquivoFornecedor(
        valor
    ) {

        return String(
            valor || 'Fornecedor'
        )
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            )
            .replace(
                /[^a-zA-Z0-9_-]+/g,
                '_'
            )
            .replace(
                /^_+|_+$/g,
                ''
            );
    }

    function criarCabecalhoExportacaoExtratoFornecedor(
        fornecedor
    ) {

        const partes = [];


        if (fornecedor?.nome) {

            partes.push(
                'Fornecedor: '
                + fornecedor.nome
            );
        }


        if (fornecedor?.nuit) {

            partes.push(
                'NUIT: '
                + fornecedor.nuit
            );
        }


        const periodo =
            obterTextoPeriodoExtratoFornecedor();


        if (periodo) {

            partes.push(
                periodo
            );
        }


        return partes.join(
            ' | '
        );
    }

    function criarCabecalhoImpressaoExtratoFornecedor(
        fornecedor
    ) {

        const nome =
            escaparHtmlFornecedor(
                fornecedor?.nome
                || '-'
            );


        const nuit =
            fornecedor?.nuit
                ? escaparHtmlFornecedor(
                    fornecedor.nuit
                )
                : '-';


        const periodo =
            escaparHtmlFornecedor(
                obterTextoPeriodoExtratoFornecedor()
            );


        return `
        <div style="margin-bottom:15px;">

            <strong>
                Fornecedor:
            </strong>
            ${nome}

            &nbsp;&nbsp;

            <strong>
                NUIT:
            </strong>
            ${nuit}

            ${periodo
                ? `
                        <br>

                        <strong>
                            Período:
                        </strong>

                        ${periodo}
                    `
                : ''
            }

        </div>
    `;
    }

    function obterTextoPeriodoExtratoFornecedor() {

        const dataInicial =
            document
                .getElementById(
                    'fornecedorExtratoDataInicial'
                )
                ?.value
            || '';


        const dataFinal =
            document
                .getElementById(
                    'fornecedorExtratoDataFinal'
                )
                ?.value
            || '';


        if (
            !dataInicial
            && !dataFinal
        ) {

            return 'Todos os movimentos';
        }


        if (
            dataInicial
            && dataFinal
        ) {

            return (
                formatarDataSimplesFornecedor(
                    dataInicial
                )
                + ' a '
                + formatarDataSimplesFornecedor(
                    dataFinal
                )
            );
        }


        if (dataInicial) {

            return (
                'Desde '
                + formatarDataSimplesFornecedor(
                    dataInicial
                )
            );
        }


        return (
            'Até '
            + formatarDataSimplesFornecedor(
                dataFinal
            )
        );
    }

    function criarResumoTextoExtratoFornecedor() {

        const saldoAnterior =
            document
                .getElementById(
                    'fornecedorExtratoSaldoAnterior'
                )
                ?.textContent
                ?.trim()
            || '0,00 MZN';


        const debitos =
            document
                .getElementById(
                    'fornecedorExtratoDebitos'
                )
                ?.textContent
                ?.trim()
            || '0,00 MZN';


        const creditos =
            document
                .getElementById(
                    'fornecedorExtratoCreditos'
                )
                ?.textContent
                ?.trim()
            || '0,00 MZN';


        const saldoAtual =
            document
                .getElementById(
                    'fornecedorExtratoSaldoAtual'
                )
                ?.textContent
                ?.trim()
            || '0,00 MZN';


        return (
            'Saldo anterior: '
            + saldoAnterior
            + '   |   Débitos: '
            + debitos
            + '   |   Créditos: '
            + creditos
            + '   |   Saldo atual: '
            + saldoAtual
        );
    }

    function criarResumoHtmlExtratoFornecedor() {

        const texto =
            criarResumoTextoExtratoFornecedor();


        return `
        <div
            style="
                border:1px solid #ddd;
                padding:10px;
                font-size:12px;
            "
        >
            ${escaparHtmlFornecedor(
            texto
        )}
        </div>
    `;
    }

    function destruirDataTableFornecedor() {
        if (
            typeof $ !== 'undefined'
            && $.fn.DataTable
            && $.fn.DataTable.isDataTable(
                '#fornecedorTabela'
            )
        ) {
            $('#fornecedorTabela')
                .DataTable()
                .destroy();
        }

        fornecedorDataTable = null;
    }

    function iniciarDataTableFornecedor() {

        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        // Proteção contra inicialização duplicada
        if (
            $.fn.DataTable.isDataTable(
                '#fornecedorTabela'
            )
        ) {

            $('#fornecedorTabela')
                .DataTable()
                .destroy();
        }

        fornecedorDataTable =
            $('#fornecedorTabela')
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
                            targets: [10],
                            orderable: false,
                            searchable: false
                        }
                    ],

                    language: {

                        emptyTable:
                            'Nenhum fornecedor encontrado.',

                        zeroRecords:
                            'Nenhum fornecedor encontrado.',

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
                                'Lista de Fornecedores',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                            }
                        },

                        {
                            extend:
                                'pdfHtml5',

                            text:
                                '<i class="fa fa-file-pdf"></i> PDF',

                            title:
                                'Lista de Fornecedores',

                            orientation:
                                'landscape',

                            pageSize:
                                'A4',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                            }
                        },

                        {
                            extend:
                                'print',

                            text:
                                '<i class="fa fa-print"></i> Imprimir',

                            title:
                                'Lista de Fornecedores',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                            }
                        }
                    ]
                });
    }

    function definirValorFornecedor(
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

    function atualizarTextoFornecedor(
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

    function mostrarErroFornecedor(
        id,
        mensagem
    ) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            mostrarMensagemErroFornecedor(
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

    function esconderErroFornecedor(id) {
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

    function bloquearBotaoFornecedor(
        botao,
        texto
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = true;

        botao.innerHTML = `
            <i class="fa fa-spinner fa-spin"></i>
            ${escaparHtmlFornecedor(texto)}
        `;
    }

    function desbloquearBotaoFornecedor(
        botao,
        html
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = false;
        botao.innerHTML = html;
    }

    function retirarFocoFornecedor() {
        if (
            document.activeElement
            instanceof HTMLElement
        ) {
            document.activeElement.blur();
        }
    }

    function normalizarTextoFornecedor(
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

    function escaparHtmlFornecedor(
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

    function formatarDataFornecedor(
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
            return escaparHtmlFornecedor(
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

    function criarSaldoFornecedor(
        valor
    ) {

        const saldo =
            Number(
                valor || 0
            );


        const moeda =
            Math.abs(
                saldo
            )
                .toLocaleString(
                    'pt-MZ',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                );


        /*
         * A empresa deve ao fornecedor.
         */
        if (saldo < -0.005) {

            return `
            <div>
                <strong class="text-danger">
                    - ${moeda} MZN
                </strong>

                <div class="small text-danger">
                    Devemos ao fornecedor
                </div>
            </div>
        `;
        }


        /*
         * O fornecedor deve à empresa.
         */
        if (saldo > 0.005) {

            return `
            <div>
                <strong class="text-success">
                    + ${moeda} MZN
                </strong>

                <div class="small text-success">
                    Fornecedor deve-nos
                </div>
            </div>
        `;
        }


        /*
         * Saldo liquidado.
         */
        return `
        <div>
            <strong class="text-muted">
                0,00 MZN
            </strong>

            <div class="small text-muted">
                Sem saldo
            </div>
        </div>
    `;
    }

    function criarBadgeFornecedor(
        status
    ) {
        const nome =
            String(status || '-');

        const normalizado =
            normalizarTextoFornecedor(
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
                'badge-secondary';
        } else if (
            normalizado === 'bloqueado'
        ) {
            classe =
                'badge-danger';
        } else if (
            normalizado === 'suspenso'
        ) {
            classe =
                'badge-warning';
        }

        return `
            <span class="badge ${classe}">
                ${escaparHtmlFornecedor(nome)}
            </span>
        `;
    }

    function mostrarMensagemSucessoFornecedor(
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

    function mostrarMensagemErroFornecedor(
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