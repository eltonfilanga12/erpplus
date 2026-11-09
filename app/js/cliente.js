'use strict';

(() => {
    const CLIENTE_URL =
        '/erpplus/app/ajax/cliente_ajax.php';

    let clientes = [];
    let clienteDataTable = null;
    let clienteModuloInicializado = false;
    let clienteImportacaoToken = '';
    let clienteImportacaoDados = null;
    let clienteImportacaoProcessando = false;

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarModuloClientes
        );
    } else {
        iniciarModuloClientes();
    }

    async function iniciarModuloClientes() {
        if (clienteModuloInicializado) {
            return;
        }

        if (
            !document.getElementById(
                'clienteTabela'
            )
        ) {
            return;
        }

        clienteModuloInicializado = true;

        configurarEventosCliente();

        await Promise.all([
            carregarStatusCliente(),
            carregarClientes()
        ]);
    }

    function configurarEventosCliente() {

        document
            .getElementById(
                'clienteForm'
            )
            ?.addEventListener(
                'input',
                function (evento) {

                    const campo =
                        evento.target;

                    if (
                        !(
                            campo
                            instanceof HTMLElement
                        )
                    ) {
                        return;
                    }


                    campo.classList.remove(
                        'is-invalid'
                    );


                    const feedback =
                        campo.nextElementSibling;

                    if (
                        feedback
                        && feedback.classList.contains(
                            'cliente-validacao'
                        )
                    ) {
                        feedback.remove();
                    }
                }
            );

        document
            .getElementById(
                'clienteBtnNovo'
            )
            ?.addEventListener(
                'click',
                abrirNovoCliente
            );

        document
            .getElementById(
                'clienteForm'
            )
            ?.addEventListener(
                'submit',
                guardarCliente
            );

        document.getElementById('clienteBtnImportar')?.addEventListener('click', abrirImportacaoCliente);
document.getElementById('clienteBtnModeloExcel')?.addEventListener('click', baixarModeloExcelCliente);
document.getElementById('clienteBtnValidarExcel')?.addEventListener('click', validarImportacaoCliente);
document.getElementById('clienteBtnConfirmarImportacao')?.addEventListener('click', confirmarImportacaoCliente);
document.getElementById('clienteBtnVoltarImportacao')?.addEventListener('click', voltarImportacaoCliente);
document.getElementById('clienteFicheiroExcel')?.addEventListener('change', alterarFicheiroExcelCliente);

        document
            .getElementById(
                'clienteFormEliminar'
            )
            ?.addEventListener(
                'submit',
                eliminarCliente
            );

        document.addEventListener(
            'click',
            processarCliqueCliente
        );
    }

    async function requisicaoCliente(
        acao,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        let url = CLIENTE_URL;

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

            configuracao.body = dados;
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

    async function carregarClientes() {

        const tbody =
            document.getElementById(
                'clienteTabelaBody'
            );

        if (!tbody) {
            return;
        }

        esconderErroCliente(
            'clienteErroLista'
        );

        try {

            const resultado =
                await requisicaoCliente(
                    'listar'
                );

            clientes =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            // Destruir DataTable antes de mexer no tbody
            destruirDataTableCliente();

            // IMPORTANTE:
            // nunca deixar <td colspan> antes de
            // inicializar o DataTables.
            tbody.innerHTML = '';

            if (clientes.length > 0) {

                tbody.innerHTML =
                    clientes
                        .map(
                            (cliente, indice) =>
                                criarLinhaCliente(
                                    cliente,
                                    indice
                                )
                        )
                        .join('');

            }

            iniciarDataTableCliente();

        } catch (erro) {

            destruirDataTableCliente();

            // Mantém a tabela estruturalmente válida.
            tbody.innerHTML = '';

            mostrarErroCliente(
                'clienteErroLista',
                erro.message
            );
        }
    }

    // Adicionar as funções correspondentes:
function abrirImportacaoCliente() {
    clienteImportacaoToken = '';
    clienteImportacaoDados = null;
    clienteImportacaoProcessando = false;

    const ficheiro = document.getElementById('clienteFicheiroExcel');
    if (ficheiro) ficheiro.value = '';

    const label = document.querySelector('label[for="clienteFicheiroExcel"]');
    if (label) label.textContent = 'Selecione o ficheiro';

    document.getElementById('clienteImportacaoPasso1')?.classList.remove('d-none');
    document.getElementById('clienteImportacaoPasso2')?.classList.add('d-none');
    document.getElementById('clienteBtnValidarExcel')?.classList.remove('d-none');
    document.getElementById('clienteBtnConfirmarImportacao')?.classList.add('d-none');
    document.getElementById('clienteBtnVoltarImportacao')?.classList.add('d-none');

    esconderErroImportacaoCliente();
    esconderSucessoImportacaoCliente();

    $('#clienteModalImportar').modal('show');
}

function alterarFicheiroExcelCliente(evento) {
    const ficheiro = evento.target.files?.[0];
    const label = document.querySelector('label[for="clienteFicheiroExcel"]');
    if (label) {
        label.textContent = ficheiro ? ficheiro.name : 'Selecione o ficheiro';
    }
}

function baixarModeloExcelCliente() {
    const url = CLIENTE_URL + '?acao=baixar_modelo_excel&_=' + Date.now();
    window.location.href = url;
}

async function validarImportacaoCliente() {
    if (clienteImportacaoProcessando) return;

    esconderErroImportacaoCliente();
    esconderSucessoImportacaoCliente();

    const input = document.getElementById('clienteFicheiroExcel');
    const ficheiro = input?.files?.[0];

    if (!ficheiro) {
        mostrarErroImportacaoCliente('Selecione um ficheiro Excel antes de continuar.');
        return;
    }

    const extensao = ficheiro.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls'].includes(extensao)) {
        mostrarErroImportacaoCliente('O ficheiro deve estar no formato XLSX ou XLS.');
        return;
    }

    const botao = document.getElementById('clienteBtnValidarExcel');
    const htmlOriginal = botao?.innerHTML || '';

    try {
        clienteImportacaoProcessando = true;
        bloquearBotaoCliente(botao, 'A validar...');

        const dados = new FormData();
        dados.append('ficheiro', ficheiro);

        const resultado = await requisicaoCliente('validar_importacao', {
            method: 'POST',
            dados
        });

        clienteImportacaoDados = resultado.dados || {};
        clienteImportacaoToken = String(clienteImportacaoDados.token || '');

        if (!clienteImportacaoToken) {
            throw new Error('O servidor não devolveu o token da importação.');
        }

        renderizarResultadoImportacaoCliente(clienteImportacaoDados);

        document.getElementById('clienteImportacaoPasso1')?.classList.add('d-none');
        document.getElementById('clienteImportacaoPasso2')?.classList.remove('d-none');
        document.getElementById('clienteBtnValidarExcel')?.classList.add('d-none');
        document.getElementById('clienteBtnVoltarImportacao')?.classList.remove('d-none');

        const totalValidos = Number(clienteImportacaoDados.total_validos || 0);
        if (totalValidos > 0) {
            document.getElementById('clienteBtnConfirmarImportacao')?.classList.remove('d-none');
        } else {
            document.getElementById('clienteBtnConfirmarImportacao')?.classList.add('d-none');
        }
    } catch (erro) {
        mostrarErroImportacaoCliente(erro.message);
    } finally {
        clienteImportacaoProcessando = false;
        desbloquearBotaoCliente(botao, htmlOriginal);
    }
}

function renderizarResultadoImportacaoCliente(dados) {
    const validos = Array.isArray(dados.validos) ? dados.validos : [];
    const erros = Array.isArray(dados.erros) ? dados.erros : [];

    renderizarClientesValidosImportacao(validos);
    renderizarErrosImportacaoCliente(erros);

    document.getElementById('clienteImportacaoValidosContainer')?.classList.toggle('d-none', validos.length === 0);
    document.getElementById('clienteImportacaoErrosContainer')?.classList.toggle('d-none', erros.length === 0);
}

function renderizarClientesValidosImportacao(validos) {
    const tbody = document.getElementById('clienteImportacaoValidos');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (validos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Nenhum cliente válido encontrado.</td></tr>`;
        return;
    }

    validos.forEach(item => {
        const preview = item.preview || {};
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escaparHtmlCliente(item.linha ?? '')}</td>
            <td><strong>${escaparHtmlCliente(preview.nome ?? '')}</strong></td>
            <td>${escaparHtmlCliente(preview.tipo ?? '')}</td>
            <td>${escaparHtmlCliente(preview.nuit ?? '')}</td>
            <td>${escaparHtmlCliente(preview.telefone ?? '')}</td>
            <td>${escaparHtmlCliente(preview.email ?? '')}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderizarErrosImportacaoCliente(erros) {
    const tbody = document.getElementById('clienteImportacaoErros');
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

        const listaErros = mensagens.map(m => `<div>${escaparHtmlCliente(m)}</div>`).join('');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escaparHtmlCliente(item.linha ?? '-')}</td>
            <td>${escaparHtmlCliente(dados.nome ?? '')}</td>
            <td>${escaparHtmlCliente(dados.nuit ?? '-')}</td>
            <td class="text-danger">${listaErros}</td>
        `;
        tbody.appendChild(tr);
    });
}

async function confirmarImportacaoCliente() {
    if (clienteImportacaoProcessando || !clienteImportacaoToken) return;

    const totalValidos = Number(clienteImportacaoDados?.total_validos || 0);
    if (totalValidos <= 0) return;

    if (typeof Swal !== 'undefined') {
        const confirmacao = await Swal.fire({
            icon: 'question',
            title: 'Confirmar importação?',
            html: `Serão registados <strong>${totalValidos}</strong> clientes.<br><br>Os clientes existentes não serão alterados.`,
            showCancelButton: true,
            confirmButtonText: 'Sim, importar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmacao.isConfirmed) return;
    }

    const botao = document.getElementById('clienteBtnConfirmarImportacao');
    const htmlOriginal = botao?.innerHTML || '';

    try {
        clienteImportacaoProcessando = true;
        bloquearBotaoCliente(botao, 'A importar...');

        const dados = new FormData();
        dados.append('token', clienteImportacaoToken);

        const resultado = await requisicaoCliente('confirmar_importacao', {
            method: 'POST',
            dados
        });

        mostrarMensagemSucessoCliente(resultado.mensagem || 'Importação concluída com sucesso.');
        clienteImportacaoToken = '';
        clienteImportacaoDados = null;

        document.getElementById('clienteBtnConfirmarImportacao')?.classList.add('d-none');
        await carregarClientes();

        setTimeout(() => {
            $('#clienteModalImportar').modal('hide');
        }, 1200);
    } catch (erro) {
        mostrarErroImportacaoCliente(erro.message);
    } finally {
        clienteImportacaoProcessando = false;
        desbloquearBotaoCliente(botao, htmlOriginal);
    }
}

function voltarImportacaoCliente() {
    clienteImportacaoToken = '';
    clienteImportacaoDados = null;

    document.getElementById('clienteImportacaoPasso2')?.classList.add('d-none');
    document.getElementById('clienteImportacaoPasso1')?.classList.remove('d-none');
    document.getElementById('clienteBtnConfirmarImportacao')?.classList.add('d-none');
    document.getElementById('clienteBtnVoltarImportacao')?.classList.add('d-none');
    document.getElementById('clienteBtnValidarExcel')?.classList.remove('d-none');

    const input = document.getElementById('clienteFicheiroExcel');
    if (input) input.value = '';

    const label = document.querySelector('label[for="clienteFicheiroExcel"]');
    if (label) label.textContent = 'Selecione o ficheiro';

    esconderErroImportacaoCliente();
}

function mostrarErroImportacaoCliente(mensagem) {
    const el = document.getElementById('clienteImportacaoErro');
    if (el) {
        el.textContent = mensagem;
        el.classList.remove('d-none');
    }
}

function esconderErroImportacaoCliente() {
    document.getElementById('clienteImportacaoErro')?.classList.add('d-none');
}

function mostrarSucessoImportacaoCliente(mensagem) {
    const el = document.getElementById('clienteImportacaoSucesso');
    if (el) {
        el.textContent = mensagem;
        el.classList.remove('d-none');
    }
}

function esconderSucessoImportacaoCliente() {
    document.getElementById('clienteImportacaoSucesso')?.classList.add('d-none');
}

    function criarLinhaCliente(cliente, indice) {
        const id =
            Number(cliente.id);

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
                        ${escaparHtmlCliente(
            cliente.nome || '-'
        )}
                    </strong>

                    ${cliente.nome_comercial
                ? `
                                <div class="small text-muted">
                                    ${escaparHtmlCliente(
                    cliente.nome_comercial
                )}
                                </div>
                            `
                : ''
            }
                </td>

                <td>
                    ${criarBadgeTipoCliente(
                cliente.tipo
            )}
                </td>

                <td>
                    ${escaparHtmlCliente(
                cliente.nuit || '-'
            )}
                </td>

                <td>
                    ${escaparHtmlCliente(
                cliente.telefone || '-'
            )}
                </td>

                <td>
                    ${escaparHtmlCliente(
                cliente.email || '-'
            )}
                </td>

                <td>
                    ${criarBadgeStatusCliente(
                cliente.status_nome
            )}
                </td>

                <td>
                    ${formatarMoedaCliente(
                cliente.limite_credito
            )}
                </td>

                <td>
                    ${formatarMoedaCliente(
                cliente.saldo_credito
            )}
                </td>

                <td class="text-right">
                    <button
                        type="button"
                        class="btn btn-sm btn-info"
                        title="Editar"
                        data-cliente-acao="editar"
                        data-cliente-id="${id}"
                    >
                        <i class="fa fa-edit"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        title="Eliminar"
                        data-cliente-acao="eliminar"
                        data-cliente-id="${id}"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    async function carregarStatusCliente() {
        const select =
            document.getElementById(
                'clienteStatusId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {
            const resultado =
                await requisicaoCliente(
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
                        normalizarTextoCliente(
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

    function abrirNovoCliente() {
        limparFormularioCliente();

        atualizarTextoCliente(
            'clienteModalTitulo',
            'Novo Cliente'
        );

        const status =
            document.getElementById(
                'clienteStatusId'
            );

        if (status?.dataset.activoId) {
            status.value =
                status.dataset.activoId;
        }

        $('#clienteModal')
            .modal('show');
    }

    async function editarCliente(id) {
        esconderErroCliente(
            'clienteErroFormulario'
        );

        try {
            const resultado =
                await requisicaoCliente(
                    'buscar',
                    {
                        parametros: {
                            id
                        }
                    }
                );

            const cliente =
                resultado.dados || {};

            limparFormularioCliente();

            definirValorCliente(
                'clienteId',
                cliente.id
            );

            definirValorCliente(
                'clienteStatusId',
                cliente.status_id
            );

            definirValorCliente(
                'clienteTipo',
                cliente.tipo
            );

            definirValorCliente(
                'clienteNome',
                cliente.nome
            );

            definirValorCliente(
                'clienteNomeComercial',
                cliente.nome_comercial
            );

            definirValorCliente(
                'clienteNuit',
                cliente.nuit
            );

            definirValorCliente(
                'clienteEmail',
                cliente.email
            );

            definirValorCliente(
                'clienteTelefone',
                cliente.telefone
            );

            definirValorCliente(
                'clienteContacto',
                cliente.contacto
            );

            definirValorCliente(
                'clienteEndereco',
                cliente.endereco
            );

            definirValorCliente(
                'clienteBairro',
                cliente.bairro
            );

            definirValorCliente(
                'clienteCidade',
                cliente.cidade
            );

            definirValorCliente(
                'clienteProvincia',
                cliente.provincia
            );

            definirValorCliente(
                'clientePais',
                cliente.pais
            );

            definirValorCliente(
                'clienteLimiteCredito',
                cliente.limite_credito
            );

            definirValorCliente(
                'clienteObservacoes',
                cliente.observacoes
            );

            atualizarTextoCliente(
                'clienteModalTitulo',
                'Editar Cliente'
            );

            $('#clienteModal')
                .modal('show');
        } catch (erro) {
            mostrarMensagemErroCliente(
                erro.message
            );
        }
    }

    function limparValidacaoCliente() {

        const form =
            document.getElementById(
                'clienteForm'
            );

        if (!form) {
            return;
        }

        form
            .querySelectorAll(
                '.is-invalid'
            )
            .forEach(
                campo => {
                    campo.classList.remove(
                        'is-invalid'
                    );
                }
            );

        form
            .querySelectorAll(
                '.invalid-feedback.cliente-validacao'
            )
            .forEach(
                elemento => {
                    elemento.remove();
                }
            );
    }


    function marcarCampoInvalidoCliente(
        campoId,
        mensagem
    ) {

        const campo =
            document.getElementById(
                campoId
            );

        if (!campo) {
            return;
        }

        campo.classList.add(
            'is-invalid'
        );

        const feedback =
            document.createElement(
                'div'
            );

        feedback.className =
            'invalid-feedback cliente-validacao';

        feedback.textContent =
            mensagem;

        campo.insertAdjacentElement(
            'afterend',
            feedback
        );
    }


    function validarFormularioCliente() {

        limparValidacaoCliente();

        const erros = [];


        const status =
            document
                .getElementById(
                    'clienteStatusId'
                )
                ?.value
                ?.trim()
            || '';


        const tipo =
            document
                .getElementById(
                    'clienteTipo'
                )
                ?.value
                ?.trim()
            || '';


        const nome =
            document
                .getElementById(
                    'clienteNome'
                )
                ?.value
                ?.trim()
            || '';


        const nuit =
            document
                .getElementById(
                    'clienteNuit'
                )
                ?.value
                ?.trim()
            || '';


        const email =
            document
                .getElementById(
                    'clienteEmail'
                )
                ?.value
                ?.trim()
            || '';


        const telefone =
            document
                .getElementById(
                    'clienteTelefone'
                )
                ?.value
                ?.trim()
            || '';


        const limiteCredito =
            document
                .getElementById(
                    'clienteLimiteCredito'
                )
                ?.value
                ?.trim()
            || '';


        // ========================================
        // ESTADO
        // ========================================

        if (!status) {

            const mensagem =
                'Selecione o estado do cliente.';

            erros.push(mensagem);

            marcarCampoInvalidoCliente(
                'clienteStatusId',
                mensagem
            );
        }


        // ========================================
        // TIPO
        // ========================================

        if (
            ![
                'PARTICULAR',
                'EMPRESA'
            ].includes(tipo)
        ) {

            const mensagem =
                'Selecione um tipo de cliente válido.';

            erros.push(mensagem);

            marcarCampoInvalidoCliente(
                'clienteTipo',
                mensagem
            );
        }


        // ========================================
        // NOME
        // ========================================

        if (!nome) {

            const mensagem =
                'Informe o nome do cliente.';

            erros.push(mensagem);

            marcarCampoInvalidoCliente(
                'clienteNome',
                mensagem
            );

        } else if (
            nome.length > 150
        ) {

            const mensagem =
                'O nome do cliente não pode ter mais de 150 caracteres.';

            erros.push(mensagem);

            marcarCampoInvalidoCliente(
                'clienteNome',
                mensagem
            );
        }


        // ========================================
        // NUIT
        // ========================================

        if (nuit) {

            /*
             * Em Moçambique o NUIT normalmente
             * possui 9 dígitos.
             */
            if (
                !/^\d{9}$/.test(nuit)
            ) {

                const mensagem =
                    'Informe um NUIT válido com 9 dígitos.';

                erros.push(mensagem);

                marcarCampoInvalidoCliente(
                    'clienteNuit',
                    mensagem
                );
            }
        }


        // ========================================
        // EMAIL
        // ========================================

        if (email) {

            const regexEmail =
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (
                !regexEmail.test(email)
            ) {

                const mensagem =
                    'Informe um endereço de email válido. Exemplo: cliente@empresa.com';

                erros.push(mensagem);

                marcarCampoInvalidoCliente(
                    'clienteEmail',
                    mensagem
                );
            }
        }


        // ========================================
        // TELEFONE
        // ========================================

        if (telefone) {

            const telefoneLimpo =
                telefone.replace(
                    /[\s()+-]/g,
                    ''
                );

            if (
                !/^\d{8,15}$/.test(
                    telefoneLimpo
                )
            ) {

                const mensagem =
                    'Informe um número de telefone válido.';

                erros.push(mensagem);

                marcarCampoInvalidoCliente(
                    'clienteTelefone',
                    mensagem
                );
            }
        }


        // ========================================
        // LIMITE DE CRÉDITO
        // ========================================

        if (
            limiteCredito !== ''
            && (
                Number.isNaN(
                    Number(limiteCredito)
                )
                || Number(limiteCredito) < 0
            )
        ) {

            const mensagem =
                'O limite de crédito não pode ser negativo.';

            erros.push(mensagem);

            marcarCampoInvalidoCliente(
                'clienteLimiteCredito',
                mensagem
            );
        }


        // ========================================
        // RESULTADO
        // ========================================

        if (erros.length > 0) {

            mostrarErroCliente(
                'clienteErroFormulario',
                erros.join(' ')
            );


            const primeiroCampo =
                document.querySelector(
                    '#clienteForm .is-invalid'
                );

            primeiroCampo?.focus();


            return false;
        }


        return true;
    }

    async function guardarCliente(
        evento
    ) {

        evento.preventDefault();


        esconderErroCliente(
            'clienteErroFormulario'
        );


        /*
         * Primeiro valida todos os campos.
         */
        if (
            !validarFormularioCliente()
        ) {
            return;
        }


        const botao =
            document.getElementById(
                'clienteBtnGuardar'
            );


        const htmlOriginal =
            botao?.innerHTML || '';


        try {

            bloquearBotaoCliente(
                botao,
                'A guardar...'
            );


            const resultado =
                await requisicaoCliente(
                    'salvar',
                    {
                        method:
                            'POST',

                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );


            retirarFocoCliente();


            $('#clienteModal')
                .modal('hide');


            mostrarMensagemSucessoCliente(
                resultado.mensagem
            );


            await carregarClientes();

        } catch (erro) {

            /*
             * Aqui entram erros que só o
             * servidor consegue confirmar:
             *
             * - NUIT duplicado
             * - email duplicado
             * - estado inválido
             * - empresa inválida
             * - restrições da base de dados
             */
            mostrarErroCliente(
                'clienteErroFormulario',
                erro.message
            );

        } finally {

            desbloquearBotaoCliente(
                botao,
                htmlOriginal
            );
        }
    }

    function abrirEliminarCliente(id) {
        const cliente =
            clientes.find(
                item =>
                    Number(item.id)
                    === Number(id)
            );

        definirValorCliente(
            'clienteEliminarId',
            id
        );

        atualizarTextoCliente(
            'clienteEliminarNome',
            cliente?.nome || ''
        );

        esconderErroCliente(
            'clienteErroEliminar'
        );

        $('#clienteModalEliminar')
            .modal('show');
    }

    async function eliminarCliente(evento) {
        evento.preventDefault();

        esconderErroCliente(
            'clienteErroEliminar'
        );

        const botao =
            document.getElementById(
                'clienteBtnEliminar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoCliente(
                botao,
                'A eliminar...'
            );

            const resultado =
                await requisicaoCliente(
                    'eliminar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );

            retirarFocoCliente();

            $('#clienteModalEliminar')
                .modal('hide');

            mostrarMensagemSucessoCliente(
                resultado.mensagem
            );

            await carregarClientes();
        } catch (erro) {
            mostrarErroCliente(
                'clienteErroEliminar',
                erro.message
            );
        } finally {
            desbloquearBotaoCliente(
                botao,
                htmlOriginal
            );
        }
    }

    function processarCliqueCliente(evento) {
        const botao =
            evento.target.closest(
                '[data-cliente-acao]'
            );

        if (!botao) {
            return;
        }

        const id =
            Number(
                botao.dataset.clienteId
            );

        if (!id) {
            return;
        }

        if (
            botao.dataset.clienteAcao
            === 'editar'
        ) {
            editarCliente(id);
        }

        if (
            botao.dataset.clienteAcao
            === 'eliminar'
        ) {
            abrirEliminarCliente(id);
        }
    }

    function limparFormularioCliente() {
        document
            .getElementById(
                'clienteForm'
            )
            ?.reset();

        definirValorCliente(
            'clienteId',
            ''
        );

        definirValorCliente(
            'clientePais',
            'Moçambique'
        );

        definirValorCliente(
            'clienteTipo',
            'PARTICULAR'
        );

        definirValorCliente(
            'clienteLimiteCredito',
            '0.00'
        );

        esconderErroCliente(
            'clienteErroFormulario'
        );
    }

    function destruirDataTableCliente() {
        if (
            typeof $ !== 'undefined'
            && $.fn.DataTable
            && $.fn.DataTable.isDataTable(
                '#clienteTabela'
            )
        ) {
            $('#clienteTabela')
                .DataTable()
                .destroy();
        }

        clienteDataTable = null;
    }

    function iniciarDataTableCliente() {

        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        // Proteção extra contra inicialização duplicada
        if (
            $.fn.DataTable.isDataTable(
                '#clienteTabela'
            )
        ) {

            $('#clienteTabela')
                .DataTable()
                .destroy();
        }

        clienteDataTable =
            $('#clienteTabela')
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
                            targets: [9],
                            orderable: false,
                            searchable: false
                        }
                    ],

                    language: {

                        emptyTable:
                            'Nenhum cliente encontrado.',

                        zeroRecords:
                            'Nenhum cliente encontrado.',

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
                                'Lista de Clientes',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7, 8]
                            }
                        },

                        {
                            extend:
                                'pdfHtml5',

                            text:
                                '<i class="fa fa-file-pdf"></i> PDF',

                            title:
                                'Lista de Clientes',

                            orientation:
                                'landscape',

                            pageSize:
                                'A4',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7, 8]
                            }
                        },

                        {
                            extend:
                                'print',

                            text:
                                '<i class="fa fa-print"></i> Imprimir',

                            title:
                                'Lista de Clientes',

                            exportOptions: {
                                columns:
                                    [0, 1, 2, 3, 4, 5, 6, 7, 8]
                            }
                        }
                    ]
                });
    }

    function definirValorCliente(
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

    function atualizarTextoCliente(
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

    function mostrarErroCliente(
        id,
        mensagem
    ) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            mostrarMensagemErroCliente(
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

    function esconderErroCliente(id) {
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

    function bloquearBotaoCliente(
        botao,
        texto
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = true;

        botao.innerHTML = `
            <i class="fa fa-spinner fa-spin"></i>
            ${escaparHtmlCliente(texto)}
        `;
    }

    function desbloquearBotaoCliente(
        botao,
        html
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = false;
        botao.innerHTML = html;
    }

    function retirarFocoCliente() {
        if (
            document.activeElement
            instanceof HTMLElement
        ) {
            document.activeElement.blur();
        }
    }

    function normalizarTextoCliente(valor) {
        return String(valor || '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            );
    }

    function escaparHtmlCliente(valor) {
        const elemento =
            document.createElement(
                'div'
            );

        elemento.textContent =
            valor ?? '';

        return elemento.innerHTML;
    }

    function formatarMoedaCliente(valor) {
        const numero =
            Number(valor || 0);

        return numero.toLocaleString(
            'pt-MZ',
            {
                style: 'currency',
                currency: 'MZN'
            }
        );
    }

    function criarBadgeTipoCliente(tipo) {
        const valor =
            String(tipo || '');

        const classe =
            valor === 'EMPRESA'
                ? 'badge-info'
                : 'badge-secondary';

        const texto =
            valor === 'EMPRESA'
                ? 'Empresa'
                : 'Particular';

        return `
            <span class="badge ${classe}">
                ${texto}
            </span>
        `;
    }

    function criarBadgeStatusCliente(status) {
        const nome =
            String(status || '-');

        const normalizado =
            normalizarTextoCliente(nome);

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
                ${escaparHtmlCliente(nome)}
            </span>
        `;
    }

    function mostrarMensagemSucessoCliente(
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

    function mostrarMensagemErroCliente(
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