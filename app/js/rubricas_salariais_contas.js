'use strict';

(() => {
    const RUBRICA_CONTA_URL =
        '/erpplus/app/ajax/rubricas_salariais_contas_ajax.php';

    let rubricasConta = [];
    let contasContabilisticas = [];
    let statusMapeamento = [];
    let moduloRubricaContaInicializado = false;

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            inicializarRubricasContas
        );
    } else {
        inicializarRubricasContas();
    }

    async function inicializarRubricasContas() {
        if (
            moduloRubricaContaInicializado
        ) {
            return;
        }

        if (
            !document.getElementById(
                'rubricaContaTabela'
            )
        ) {
            return;
        }

        moduloRubricaContaInicializado = true;

        configurarEventosRubricaConta();

        await carregarAuxiliaresRubricaConta();

        await carregarRubricasConta();
    }

    function configurarEventosRubricaConta() {
        document
            .getElementById(
                'rubricaContaBtnGuardarTodos'
            )
            ?.addEventListener(
                'click',
                guardarTodosMapeamentos
            );

        document
            .getElementById(
                'rubricaContaBtnRecarregar'
            )
            ?.addEventListener(
                'click',
                carregarRubricasConta
            );

        document
            .getElementById(
                'rubricaContaPesquisa'
            )
            ?.addEventListener(
                'input',
                aplicarFiltrosRubricaConta
            );

        document
            .getElementById(
                'rubricaContaFiltroTipo'
            )
            ?.addEventListener(
                'change',
                aplicarFiltrosRubricaConta
            );

        document
            .getElementById(
                'rubricaContaFiltroConfiguracao'
            )
            ?.addEventListener(
                'change',
                aplicarFiltrosRubricaConta
            );

        document.addEventListener(
            'change',
            function (evento) {

                if (
                    !evento.target.matches(
                        '.rubrica-conta-salarios-pagar'
                    )
                ) {
                    return;
                }

                const linha =
                    evento.target.closest(
                        'tr[data-rubrica-id]'
                    );

                ajustarLinhaSalariosPagar(
                    linha
                );
            }
        );

        document.addEventListener(
            'click',
            processarCliqueRubricaConta
        );
    }

    async function requisicaoRubricaConta(
        acao,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        let url =
            RUBRICA_CONTA_URL;

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
        } else if (
            opcoes.json !== undefined
        ) {
            configuracao.headers = {
                'Content-Type':
                    'application/json'
            };

            configuracao.body =
                JSON.stringify({
                    acao,
                    ...opcoes.json
                });
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
                'O servidor devolveu HTML ou uma resposta inválida.'
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

    function ajustarLinhaSalariosPagar(
        linha
    ) {
        if (!linha) {
            return;
        }

        const tipo =
            String(
                linha.dataset.rubricaTipo
                || ''
            ).toUpperCase();

        const switchSalarios =
            linha.querySelector(
                '.rubrica-conta-salarios-pagar'
            );

        const contaDebito =
            linha.querySelector(
                '.rubrica-conta-debito'
            );

        const contaCredito =
            linha.querySelector(
                '.rubrica-conta-credito'
            );

        if (
            !switchSalarios
            || !contaDebito
            || !contaCredito
        ) {
            return;
        }

        if (!switchSalarios.checked) {
            contaDebito.disabled = false;
            contaCredito.disabled = false;

            return;
        }

        if (tipo === 'PROVENTO') {

            /*
             * PROVENTO:
             * D - conta da rubrica
             * C - salários a pagar
             */

            contaDebito.disabled = false;

            contaCredito.value = '';
            contaCredito.disabled = true;

            return;
        }

        if (tipo === 'DESCONTO') {

            /*
             * DESCONTO:
             * D - salários a pagar
             * C - conta da rubrica
             */

            contaDebito.value = '';
            contaDebito.disabled = true;

            contaCredito.disabled = false;

            return;
        }

        contaDebito.disabled = false;
        contaCredito.disabled = false;
    }

    async function carregarAuxiliaresRubricaConta() {
        esconderMensagemRubricaConta(
            'rubricaContaErroGeral'
        );

        try {
            const [
                respostaContas,
                respostaStatus
            ] = await Promise.all([
                requisicaoRubricaConta(
                    'contas'
                ),
                requisicaoRubricaConta(
                    'status'
                )
            ]);

            contasContabilisticas =
                Array.isArray(
                    respostaContas.dados
                )
                    ? respostaContas.dados
                    : [];

            statusMapeamento =
                Array.isArray(
                    respostaStatus.dados
                )
                    ? respostaStatus.dados
                    : [];
        } catch (erro) {
            mostrarMensagemRubricaConta(
                'rubricaContaErroGeral',
                erro.message
            );
        }
    }

    async function carregarRubricasConta() {
        esconderMensagemRubricaConta(
            'rubricaContaErroGeral'
        );

        const tbody =
            document.getElementById(
                'rubricaContaTabelaBody'
            );

        tbody.innerHTML = `
            <tr>
                <td
                    colspan="8"
                    class="text-center"
                >
                    <i class="fa fa-spinner fa-spin"></i>
                    A carregar rubricas...
                </td>
            </tr>
        `;

        try {
            const resultado =
                await requisicaoRubricaConta(
                    'listar'
                );

            rubricasConta =
                Array.isArray(resultado.dados)
                    ? resultado.dados
                    : [];

            renderizarRubricasConta(
                rubricasConta
            );

            inicializarSelectsRubricas();

            document
                .querySelectorAll(
                    '#rubricaContaTabelaBody '
                    + 'tr[data-rubrica-id]'
                )
                .forEach(
                    ajustarLinhaSalariosPagar
                );

            atualizarResumoRubricaConta();
        } catch (erro) {
            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="8"
                        class="text-center text-danger"
                    >
                        ${escaparHtmlRubricaConta(
                erro.message
            )}
                    </td>
                </tr>
            `;

            mostrarMensagemRubricaConta(
                'rubricaContaErroGeral',
                erro.message
            );
        }
    }

    function renderizarRubricasConta(lista) {
        const tbody =
            document.getElementById(
                'rubricaContaTabelaBody'
            );

        if (!lista.length) {
            tbody.innerHTML = `
                <tr>
                    <td
                        colspan="8"
                        class="text-center text-muted"
                    >
                        Nenhuma rubrica encontrada.
                    </td>
                </tr>
            `;

            return;
        }

        tbody.innerHTML =
            lista
                .map(criarLinhaRubricaConta)
                .join('');
    }

    function criarLinhaRubricaConta(rubrica) {
        const rubricaId =
            Number(
                rubrica.rubrica_salarial_id
            );

        const configurada =
            Number(rubrica.mapeamento_id) > 0;

        const statusSelecionado =
            rubrica.mapeamento_status_id
            || obterStatusActivoId();

        return `
            <tr
                data-rubrica-id="${rubricaId}"
                data-rubrica-tipo="${escaparHtmlRubricaConta(
            rubrica.rubrica_tipo
        )}"
                data-configurada="${configurada ? '1' : '0'
            }"
            >
                <td>
                    <strong>
                        ${escaparHtmlRubricaConta(
                rubrica.rubrica_nome
            )}
                    </strong>

                    <div class="small text-muted">
                        ${escaparHtmlRubricaConta(
                rubrica.rubrica_codigo
            )}
                        ·
                        ${escaparHtmlRubricaConta(
                rubrica.rubrica_categoria
            )}
                    </div>

                    ${configurada
                ? `
                                <span class="badge badge-success mt-1">
                                    Configurada
                                </span>
                            `
                : `
                                <span class="badge badge-warning mt-1">
                                    Não configurada
                                </span>
                            `
            }
                </td>

                <td>
                    ${criarBadgeTipoRubricaConta(
                rubrica.rubrica_tipo
            )}
                </td>

                <td>
                    <select
                        class="form-control form-control-sm rubrica-conta-debito rubrica-conta-pesquisavel"
                    >
                        ${criarOpcoesContasRubricaConta(
                rubrica.conta_debito_id,
                'Sem conta de débito',
                rubrica.rubrica_tipo,
                'DEBITO'
            )}
                    </select>
                </td>

                <td>
                    <select
                        class="form-control form-control-sm rubrica-conta-credito rubrica-conta-pesquisavel"
                    >
                        ${criarOpcoesContasRubricaConta(
                rubrica.conta_credito_id,
                'Sem conta de crédito',
                rubrica.rubrica_tipo,
                'CREDITO'
            )}
                    </select>
                </td>

                <td class="text-center align-middle">
                    <div class="custom-control custom-switch d-inline-block">
                        <input
                            type="checkbox"
                            class="custom-control-input rubrica-conta-salarios-pagar"
                            id="rubricaContaSalariosPagar${rubricaId}"
                            ${Number(
                rubrica.usar_conta_salarios_pagar
            ) === 1
                ? 'checked'
                : ''
            }
                        >

                        <label
                            class="custom-control-label"
                            for="rubricaContaSalariosPagar${rubricaId}"
                        ></label>
                    </div>
                </td>

                <td class="text-center align-middle">
                    <div class="custom-control custom-switch d-inline-block">
                        <input
                            type="checkbox"
                            class="custom-control-input rubrica-conta-centro-custo"
                            id="rubricaContaCentroCusto${rubricaId}"
                            ${Number(
                rubrica.centro_custo_obrigatorio
            ) === 1
                ? 'checked'
                : ''
            }
                        >

                        <label
                            class="custom-control-label"
                            for="rubricaContaCentroCusto${rubricaId}"
                        ></label>
                    </div>
                </td>

                <td>
                    <select
                        class="form-control form-control-sm rubrica-conta-status"
                    >
                        ${criarOpcoesStatusRubricaConta(
                statusSelecionado
            )}
                    </select>
                </td>

                <td class="text-right align-middle">
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        title="Guardar"
                        data-rubrica-conta-acao="guardar"
                        data-rubrica-id="${rubricaId}"
                    >
                        <i class="fa fa-save"></i>
                    </button>

                    ${configurada
                ? `
                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger"
                                    title="Remover configuração"
                                    data-rubrica-conta-acao="eliminar"
                                    data-rubrica-id="${rubricaId}"
                                >
                                    <i class="fa fa-trash"></i>
                                </button>
                            `
                : ''
            }
                </td>
            </tr>
        `;
    }

    function inicializarSelectsRubricas() {

        if (
            typeof window.jQuery === 'undefined'
            || !jQuery.fn.select2
        ) {
            return;
        }

        $('.rubrica-conta-pesquisavel')
            .select2({
                width: '100%',
                placeholder:
                    'Pesquisar conta...',
                allowClear: true
            });
    }

    function criarOpcoesContasRubricaConta(
        selecionado,
        textoVazio,
        tipoRubrica,
        lado
    ) {
        let html = `
        <option value="">
            ${escaparHtmlRubricaConta(
            textoVazio
        )}
        </option>
    `;

        const tipo =
            String(
                tipoRubrica || ''
            ).toUpperCase();

        const ladoConta =
            String(
                lado || ''
            ).toUpperCase();


        const contasFiltradas =
            contasContabilisticas.filter(
                conta => {

                    /*
                     * Nunca remover da lista a conta que já está
                     * guardada no mapeamento. Caso contrário, o select
                     * aparece vazio mesmo com um ID válido na base de dados.
                     */
                    if (
                        String(conta.id)
                        === String(selecionado)
                    ) {
                        return true;
                    }

                    const natureza =
                        String(
                            conta.natureza || ''
                        ).toUpperCase();

                    const grupo =
                        String(
                            conta.grupo || ''
                        ).toUpperCase();

                    const texto =
                        (
                            String(
                                conta.codigo || ''
                            )
                            + ' '
                            + String(
                                conta.nome || ''
                            )
                        )
                            .toLowerCase();


                    /*
                    |--------------------------------------------------------------------------
                    | PROVENTOS
                    |--------------------------------------------------------------------------
                    |
                    | Normalmente:
                    |
                    | D - Gasto da rubrica
                    | C - Salários a pagar
                    |
                    */

                    if (tipo === 'PROVENTO') {

                        if (
                            ladoConta === 'DEBITO'
                        ) {
                            return (
                                grupo === 'GASTO'
                                && natureza === 'DEVEDORA'
                            );
                        }

                        if (
                            ladoConta === 'CREDITO'
                        ) {
                            return (
                                grupo === 'PASSIVO'
                                && natureza === 'CREDORA'
                                && (
                                    texto.includes(
                                        'trabalh'
                                    )
                                    || texto.includes(
                                        'salario'
                                    )
                                    || texto.includes(
                                        'salário'
                                    )
                                    || texto.includes(
                                        'remunera'
                                    )
                                )
                            );
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | DESCONTOS
                    |--------------------------------------------------------------------------
                    |
                    | Normalmente:
                    |
                    | D - Salários a pagar
                    | C - Conta do desconto
                    |
                    */

                    if (tipo === 'DESCONTO') {

                        if (
                            ladoConta === 'DEBITO'
                        ) {
                            return (
                                grupo === 'PASSIVO'
                                && natureza === 'CREDORA'
                                && (
                                    texto.includes(
                                        'trabalh'
                                    )
                                    || texto.includes(
                                        'salario'
                                    )
                                    || texto.includes(
                                        'salário'
                                    )
                                    || texto.includes(
                                        'remunera'
                                    )
                                )
                            );
                        }

                        if (
                            ladoConta === 'CREDITO'
                        ) {
                            return (
                                /*
                                 * INSS, IRPS e outros valores a entregar:
                                 * contas passivas de natureza credora.
                                 */
                                (
                                    grupo === 'PASSIVO'
                                    && natureza === 'CREDORA'
                                )

                                /*
                                 * Faltas e atrasos reduzem o gasto salarial:
                                 * permite creditar a conta 6.2.2.
                                 */
                                || (
                                    grupo === 'GASTO'
                                    && natureza === 'DEVEDORA'
                                )
                            );
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | INFORMATIVO
                    |--------------------------------------------------------------------------
                    */

                    if (tipo === 'INFORMATIVO') {
                        return true;
                    }


                    return true;
                }
            );


        contasFiltradas.forEach(
            conta => {

                const marcado =
                    String(conta.id)
                        === String(selecionado)
                        ? 'selected'
                        : '';

                const natureza =
                    conta.natureza
                        ? ` [${conta.natureza}]`
                        : '';

                html += `
                <option
                    value="${Number(
                    conta.id
                )}"
                    ${marcado}
                >
                    ${escaparHtmlRubricaConta(
                    `${conta.codigo || ''}`
                    + ` — ${conta.nome}`
                    + natureza
                )}
                </option>
            `;
            }
        );

        return html;
    }

    function criarOpcoesStatusRubricaConta(
        selecionado
    ) {
        let html = '';

        statusMapeamento.forEach(
            status => {
                const marcado =
                    String(status.id)
                        === String(selecionado)
                        ? 'selected'
                        : '';

                html += `
                    <option
                        value="${Number(status.id)}"
                        ${marcado}
                    >
                        ${escaparHtmlRubricaConta(
                    status.nome
                )}
                    </option>
                `;
            }
        );

        return html;
    }

    function obterStatusActivoId() {
        const activo =
            statusMapeamento.find(
                status => {
                    const nome =
                        normalizarTextoRubricaConta(
                            status.nome
                        );

                    return nome === 'activo'
                        || nome === 'ativo';
                }
            );

        return activo?.id || '';
    }

    async function guardarMapeamentoIndividual(
        rubricaId,
        botao
    ) {
        const linha =
            document.querySelector(
                `tr[data-rubrica-id="${rubricaId}"]`
            );

        if (!linha) {
            return;
        }

        const dados =
            recolherDadosLinhaRubricaConta(
                linha
            );

        const htmlOriginal =
            botao.innerHTML;

        try {
            bloquearBotaoRubricaConta(
                botao,
                ''
            );

            const formData =
                new FormData();

            Object.entries(dados)
                .forEach(([campo, valor]) => {
                    formData.append(
                        campo,
                        String(valor ?? '')
                    );
                });

            const resultado =
                await requisicaoRubricaConta(
                    'guardar',
                    {
                        method: 'POST',
                        dados: formData
                    }
                );

            mostrarMensagemRubricaConta(
                'rubricaContaSucessoGeral',
                resultado.mensagem
            );

            await carregarRubricasConta();
        } catch (erro) {
            mostrarMensagemRubricaConta(
                'rubricaContaErroGeral',
                erro.message
            );
        } finally {
            desbloquearBotaoRubricaConta(
                botao,
                htmlOriginal
            );
        }
    }

    async function guardarTodosMapeamentos() {
        esconderMensagemRubricaConta(
            'rubricaContaErroGeral'
        );

        esconderMensagemRubricaConta(
            'rubricaContaSucessoGeral'
        );

        const linhas = [
            ...document.querySelectorAll(
                '#rubricaContaTabelaBody tr[data-rubrica-id]'
            )
        ];

        if (!linhas.length) {
            mostrarMensagemRubricaConta(
                'rubricaContaErroGeral',
                'Nenhuma rubrica foi encontrada.'
            );

            return;
        }

        const itens =
            linhas.map(
                recolherDadosLinhaRubricaConta
            );

        const botao =
            document.getElementById(
                'rubricaContaBtnGuardarTodos'
            );

        const htmlOriginal =
            botao.innerHTML;

        try {
            bloquearBotaoRubricaConta(
                botao,
                'A guardar...'
            );

            const resultado =
                await requisicaoRubricaConta(
                    'guardar_todos',
                    {
                        method: 'POST',
                        json: {
                            itens
                        }
                    }
                );

            mostrarMensagemRubricaConta(
                'rubricaContaSucessoGeral',
                resultado.mensagem
            );

            await carregarRubricasConta();
        } catch (erro) {
            mostrarMensagemRubricaConta(
                'rubricaContaErroGeral',
                erro.message
            );
        } finally {
            desbloquearBotaoRubricaConta(
                botao,
                htmlOriginal
            );
        }
    }

    function recolherDadosLinhaRubricaConta(
        linha
    ) {
        const selectDebito =
            linha.querySelector(
                '.rubrica-conta-debito'
            );

        const selectCredito =
            linha.querySelector(
                '.rubrica-conta-credito'
            );

        const contaDebitoId =
            selectDebito
                ? (
                    typeof window.jQuery !== 'undefined'
                        ? $(selectDebito).val()
                        : selectDebito.value
                )
                : '';

        const contaCreditoId =
            selectCredito
                ? (
                    typeof window.jQuery !== 'undefined'
                        ? $(selectCredito).val()
                        : selectCredito.value
                )
                : '';

        return {
            rubrica_salarial_id:
                Number(
                    linha.dataset.rubricaId
                ),

            conta_debito_id:
                contaDebitoId || '',

            conta_credito_id:
                contaCreditoId || '',

            usar_conta_salarios_pagar:
                linha.querySelector(
                    '.rubrica-conta-salarios-pagar'
                )?.checked
                    ? 1
                    : 0,

            centro_custo_obrigatorio:
                linha.querySelector(
                    '.rubrica-conta-centro-custo'
                )?.checked
                    ? 1
                    : 0,

            status_id:
                linha.querySelector(
                    '.rubrica-conta-status'
                )?.value || ''
        };
    }

    async function eliminarMapeamentoRubricaConta(
        rubricaId
    ) {
        const confirmou =
            window.confirm(
                'Pretende remover o mapeamento contabilístico desta rubrica?'
            );

        if (!confirmou) {
            return;
        }

        try {
            const dados =
                new FormData();

            dados.append(
                'rubrica_salarial_id',
                String(rubricaId)
            );

            const resultado =
                await requisicaoRubricaConta(
                    'eliminar',
                    {
                        method: 'POST',
                        dados
                    }
                );

            mostrarMensagemRubricaConta(
                'rubricaContaSucessoGeral',
                resultado.mensagem
            );

            await carregarRubricasConta();
        } catch (erro) {
            mostrarMensagemRubricaConta(
                'rubricaContaErroGeral',
                erro.message
            );
        }
    }

    function processarCliqueRubricaConta(
        evento
    ) {
        const botao =
            evento.target.closest(
                '[data-rubrica-conta-acao]'
            );

        if (!botao) {
            return;
        }

        const rubricaId =
            Number(
                botao.dataset.rubricaId
            );

        if (!rubricaId) {
            return;
        }

        if (
            botao.dataset.rubricaContaAcao
            === 'guardar'
        ) {
            guardarMapeamentoIndividual(
                rubricaId,
                botao
            );
        }

        if (
            botao.dataset.rubricaContaAcao
            === 'eliminar'
        ) {
            eliminarMapeamentoRubricaConta(
                rubricaId
            );
        }
    }

    function aplicarFiltrosRubricaConta() {
        const pesquisa =
            normalizarTextoRubricaConta(
                document.getElementById(
                    'rubricaContaPesquisa'
                )?.value
            );

        const tipo =
            document.getElementById(
                'rubricaContaFiltroTipo'
            )?.value || '';

        const configuracao =
            document.getElementById(
                'rubricaContaFiltroConfiguracao'
            )?.value || '';

        const filtradas =
            rubricasConta.filter(
                rubrica => {
                    const texto =
                        normalizarTextoRubricaConta(
                            [
                                rubrica.rubrica_codigo,
                                rubrica.rubrica_nome,
                                rubrica.rubrica_categoria
                            ].join(' ')
                        );

                    const correspondePesquisa =
                        !pesquisa
                        || texto.includes(
                            pesquisa
                        );

                    const correspondeTipo =
                        !tipo
                        || rubrica.rubrica_tipo
                        === tipo;

                    const configurada =
                        Number(
                            rubrica.mapeamento_id
                        ) > 0;

                    const correspondeConfiguracao =
                        !configuracao
                        || (
                            configuracao
                            === 'CONFIGURADA'
                            && configurada
                        )
                        || (
                            configuracao
                            === 'NAO_CONFIGURADA'
                            && !configurada
                        );

                    return (
                        correspondePesquisa
                        && correspondeTipo
                        && correspondeConfiguracao
                    );
                }
            );

        renderizarRubricasConta(
            filtradas
        );
    }

    function atualizarResumoRubricaConta() {
        const total =
            rubricasConta.length;

        const configuradas =
            rubricasConta.filter(
                item =>
                    Number(item.mapeamento_id) > 0
            ).length;

        const inactivas =
            rubricasConta.filter(
                item => {
                    const nome =
                        normalizarTextoRubricaConta(
                            item.mapeamento_status_nome
                        );

                    return nome === 'inactivo'
                        || nome === 'inativo';
                }
            ).length;

        definirTextoRubricaConta(
            'rubricaContaTotal',
            total
        );

        definirTextoRubricaConta(
            'rubricaContaConfiguradas',
            configuradas
        );

        definirTextoRubricaConta(
            'rubricaContaNaoConfiguradas',
            total - configuradas
        );

        definirTextoRubricaConta(
            'rubricaContaInactivas',
            inactivas
        );
    }

    function criarBadgeTipoRubricaConta(
        tipo
    ) {
        let classe =
            'badge-secondary';

        if (tipo === 'PROVENTO') {
            classe =
                'badge-success';
        } else if (
            tipo === 'DESCONTO'
        ) {
            classe =
                'badge-danger';
        } else if (
            tipo === 'INFORMATIVO'
        ) {
            classe =
                'badge-info';
        }

        return `
            <span class="badge ${classe}">
                ${escaparHtmlRubricaConta(tipo)}
            </span>
        `;
    }

    function mostrarMensagemRubricaConta(
        id,
        mensagem
    ) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            console.error(mensagem);
            return;
        }

        elemento.textContent =
            mensagem;

        elemento.classList.remove(
            'd-none'
        );

        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function esconderMensagemRubricaConta(
        id
    ) {
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

    function definirTextoRubricaConta(
        id,
        valor
    ) {
        const elemento =
            document.getElementById(id);

        if (elemento) {
            elemento.textContent =
                String(valor ?? '');
        }
    }

    function bloquearBotaoRubricaConta(
        botao,
        texto
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = true;

        botao.innerHTML = `
            <i class="fa fa-spinner fa-spin"></i>
            ${escaparHtmlRubricaConta(texto)}
        `;
    }

    function desbloquearBotaoRubricaConta(
        botao,
        html
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = false;
        botao.innerHTML = html;
    }

    function escaparHtmlRubricaConta(
        valor
    ) {
        const div =
            document.createElement(
                'div'
            );

        div.textContent =
            valor ?? '';

        return div.innerHTML;
    }

    function normalizarTextoRubricaConta(
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
})();
