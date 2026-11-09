'use strict';

(() => {
    const CONFIG_RH_URL =
        '/erpplus/app/ajax/configuracao_contabilistica_rh_ajax.php';

    let configRhInicializado = false;

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarConfiguracaoContabilisticaRh
        );
    } else {
        iniciarConfiguracaoContabilisticaRh();
    }

    async function iniciarConfiguracaoContabilisticaRh() {
        if (configRhInicializado) {
            return;
        }

        if (
            !document.getElementById(
                'configRhForm'
            )
        ) {
            return;
        }

        configRhInicializado = true;

        configurarEventosRh();

        await carregarDadosAuxiliaresRh();

        await carregarConfiguracaoRh();
    }

    function configurarEventosRh() {
        document
            .getElementById(
                'configRhForm'
            )
            ?.addEventListener(
                'submit',
                guardarConfiguracaoRh
            );

        document
            .getElementById(
                'configRhBtnRecarregar'
            )
            ?.addEventListener(
                'click',
                async () => {
                    await carregarDadosAuxiliaresRh();
                    await carregarConfiguracaoRh();
                }
            );
    }

    async function requisicaoConfigRh(
        acao,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        let url =
            CONFIG_RH_URL;

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
                'Resposta inválida da configuração contabilística:',
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
                ?? null
        };
    }

    async function carregarDadosAuxiliaresRh() {

        esconderMensagemRh(
            'configRhErroGeral'
        );

        try {

            const [
                respostaSalarios,
                respostaSalarioBase,
                respostaInss,
                respostaIrps,
                respostaOutrosDescontos,
                respostaEncargos,
                respostaBanco,
                respostaCaixa,
                respostaDiarios,
                respostaStatus
            ] = await Promise.all([

                requisicaoConfigRh(
                    'contas',
                    {
                        parametros: {
                            finalidade:
                                'salarios_pagar'
                        }
                    }
                ),

                requisicaoConfigRh(
                    'contas',
                    {
                        parametros: {
                            finalidade: 'salario_base'
                        }
                    }
                ),

                requisicaoConfigRh(
                    'contas',
                    {
                        parametros: {
                            finalidade:
                                'inss_pagar'
                        }
                    }
                ),

                requisicaoConfigRh(
                    'contas',
                    {
                        parametros: {
                            finalidade:
                                'irps_pagar'
                        }
                    }
                ),

                requisicaoConfigRh(
                    'contas',
                    {
                        parametros: {
                            finalidade:
                                'outros_descontos'
                        }
                    }
                ),

                requisicaoConfigRh(
                    'contas',
                    {
                        parametros: {
                            finalidade:
                                'encargos_patronais'
                        }
                    }
                ),

                requisicaoConfigRh(
                    'contas',
                    {
                        parametros: {
                            finalidade:
                                'banco'
                        }
                    }
                ),

                requisicaoConfigRh(
                    'contas',
                    {
                        parametros: {
                            finalidade:
                                'caixa'
                        }
                    }
                ),

                requisicaoConfigRh(
                    'diarios'
                ),

                requisicaoConfigRh(
                    'status'
                )
            ]);


            preencherSelectContaRh(
                'configRhContaSalariosPagarId',
                respostaSalarios.dados,
                'Selecione a conta'
            );

            preencherSelectContaRh(
                'configRhContaSalarioBaseId',
                respostaSalarioBase.dados,
                'Selecione a conta'
            );

            preencherSelectContaRh(
                'configRhContaInssId',
                respostaInss.dados,
                'Nenhuma conta selecionada'
            );

            preencherSelectContaRh(
                'configRhContaIrpsId',
                respostaIrps.dados,
                'Nenhuma conta selecionada'
            );

            preencherSelectContaRh(
                'configRhContaOutrosDescontosId',
                respostaOutrosDescontos.dados,
                'Nenhuma conta selecionada'
            );

            preencherSelectContaRh(
                'configRhContaEncargosId',
                respostaEncargos.dados,
                'Nenhuma conta selecionada'
            );

            preencherSelectContaRh(
                'configRhContaBancoId',
                respostaBanco.dados,
                'Nenhuma conta selecionada'
            );

            preencherSelectContaRh(
                'configRhContaCaixaId',
                respostaCaixa.dados,
                'Nenhuma conta selecionada'
            );


            preencherDiariosRh(
                Array.isArray(
                    respostaDiarios.dados
                )
                    ? respostaDiarios.dados
                    : []
            );


            preencherStatusRh(
                Array.isArray(
                    respostaStatus.dados
                )
                    ? respostaStatus.dados
                    : []
            );

        } catch (erro) {

            mostrarMensagemRh(
                'configRhErroGeral',
                erro.message
            );
        }
    }

    function preencherSelectContaRh(
        id,
        contas,
        placeholder
    ) {

        const select =
            document.getElementById(id);

        if (!select) {
            return;
        }


        const lista =
            Array.isArray(contas)
                ? contas
                : [];


        select.replaceChildren(
            new Option(
                placeholder,
                ''
            )
        );


        lista.forEach(
            conta => {

                const codigo =
                    conta.codigo
                        ? `${conta.codigo} — `
                        : '';

                const natureza =
                    conta.natureza
                        ? ` [${conta.natureza}]`
                        : '';

                select.add(
                    new Option(
                        codigo
                        + conta.nome
                        + natureza,

                        String(conta.id)
                    )
                );
            }
        );
    }



    function preencherDiariosRh(diarios) {
        const select =
            document.getElementById(
                'configRhDiarioId'
            );

        if (!select) {
            return;
        }

        select.replaceChildren(
            new Option(
                diarios.length
                    ? 'Selecione o diário'
                    : 'Nenhum diário disponível',
                ''
            )
        );

        diarios.forEach(diario => {
            const codigo =
                diario.codigo
                    ? `${diario.codigo} — `
                    : '';

            select.add(
                new Option(
                    codigo + diario.nome,
                    String(diario.id)
                )
            );
        });
    }

    function preencherStatusRh(estados) {
        const select =
            document.getElementById(
                'configRhStatusId'
            );

        if (!select) {
            return;
        }

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
                    normalizarTextoConfigRh(
                        estado.nome
                    );

                return nome === 'activo'
                    || nome === 'ativo';
            });

        if (activo) {
            select.dataset.activoId =
                String(activo.id);
        }
    }

    async function carregarConfiguracaoRh() {
        esconderMensagemRh(
            'configRhErroGeral'
        );

        try {
            const resultado =
                await requisicaoConfigRh(
                    'buscar'
                );

            const configuracao =
                resultado.dados;

            if (!configuracao) {
                prepararNovaConfiguracaoRh();
                return;
            }

            definirValorConfigRh(
                'configRhId',
                configuracao.id
            );

            definirValorConfigRh(
                'configRhDiarioId',
                configuracao.diario_contabilistico_id
            );

            definirValorConfigRh(
                'configRhContaSalariosPagarId',
                configuracao.conta_salarios_pagar_id
            );

            definirValorConfigRh(
                'configRhContaSalarioBaseId',
                configuracao.conta_salario_base_id
            );

            definirValorConfigRh(
                'configRhContaInssId',
                configuracao.conta_inss_pagar_id
            );

            definirValorConfigRh(
                'configRhContaIrpsId',
                configuracao.conta_irps_pagar_id
            );

            definirValorConfigRh(
                'configRhContaOutrosDescontosId',
                configuracao.conta_outros_descontos_id
            );

            definirValorConfigRh(
                'configRhContaEncargosId',
                configuracao.conta_encargos_patronais_id
            );

            definirValorConfigRh(
                'configRhContaBancoId',
                configuracao.conta_banco_padrao_id
            );

            definirValorConfigRh(
                'configRhContaCaixaId',
                configuracao.conta_caixa_padrao_id
            );

            definirValorConfigRh(
                'configRhStatusId',
                configuracao.status_id
            );

            definirCheckboxConfigRh(
                'configRhAgruparFilial',
                configuracao.agrupar_por_filial
            );

            definirCheckboxConfigRh(
                'configRhAgruparCentroCusto',
                configuracao.agrupar_por_centro_custo
            );

            definirCheckboxConfigRh(
                'configRhContabilizarRubrica',
                configuracao.contabilizar_por_rubrica
            );

            definirCheckboxConfigRh(
                'configRhGerarAutomaticamente',
                configuracao.gerar_lancamento_automaticamente
            );
        } catch (erro) {
            mostrarMensagemRh(
                'configRhErroGeral',
                erro.message
            );
        }
    }

    function prepararNovaConfiguracaoRh() {
        document
            .getElementById(
                'configRhForm'
            )
            ?.reset();

        definirValorConfigRh(
            'configRhId',
            ''
        );

        definirCheckboxConfigRh(
            'configRhContabilizarRubrica',
            true
        );

        const status =
            document.getElementById(
                'configRhStatusId'
            );

        if (status?.dataset.activoId) {
            status.value =
                status.dataset.activoId;
        }
    }

    async function guardarConfiguracaoRh(
        evento
    ) {
        evento.preventDefault();

        esconderMensagemRh(
            'configRhErroGeral'
        );

        esconderMensagemRh(
            'configRhSucessoGeral'
        );

        const botao =
            document.getElementById(
                'configRhBtnGuardar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoConfigRh(
                botao,
                'A guardar...'
            );

            const dados =
                new FormData(
                    evento.currentTarget
                );

            normalizarCheckboxesConfigRh(
                dados
            );

            const resultado =
                await requisicaoConfigRh(
                    'guardar',
                    {
                        method: 'POST',
                        dados
                    }
                );

            mostrarMensagemRh(
                'configRhSucessoGeral',
                resultado.mensagem
            );

            await carregarConfiguracaoRh();
        } catch (erro) {
            mostrarMensagemRh(
                'configRhErroGeral',
                erro.message
            );
        } finally {
            desbloquearBotaoConfigRh(
                botao,
                htmlOriginal
            );
        }
    }

    function normalizarCheckboxesConfigRh(
        dados
    ) {
        const campos = [
            'agrupar_por_filial',
            'agrupar_por_centro_custo',
            'contabilizar_por_rubrica',
            'gerar_lancamento_automaticamente'
        ];

        campos.forEach(campo => {
            if (!dados.has(campo)) {
                dados.set(
                    campo,
                    '0'
                );
            }
        });
    }

    function definirValorConfigRh(
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

    function definirCheckboxConfigRh(
        id,
        valor
    ) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            return;
        }

        elemento.checked =
            Number(valor) === 1
            || valor === true
            || valor === '1';
    }

    function mostrarMensagemRh(
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

        elemento.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    function esconderMensagemRh(id) {
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

    function bloquearBotaoConfigRh(
        botao,
        texto
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = true;

        botao.innerHTML = `
            <i class="fa fa-spinner fa-spin"></i>
            ${escaparHtmlConfigRh(texto)}
        `;
    }

    function desbloquearBotaoConfigRh(
        botao,
        html
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = false;
        botao.innerHTML = html;
    }

    function normalizarTextoConfigRh(
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

    function escaparHtmlConfigRh(valor) {
        const elemento =
            document.createElement('div');

        elemento.textContent =
            valor ?? '';

        return elemento.innerHTML;
    }
})();