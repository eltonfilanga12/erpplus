(function ($) {
    'use strict';
    const endpoint = 'app/ajax/obrigacoes_fiscais_ajax.php';
    let tipo = 'INSS';
    let iniciais = { bancos: [], caixas: [], filiais: [] };

    const moeda = v => Number(v || 0).toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MTn';
    const esc = v => $('<div>').text(v == null ? '' : String(v)).html();
    const alerta = (icone, titulo, texto) => Swal.fire({ icon: icone, title: titulo, text: texto });

    async function api(acao, dados = {}, metodo = 'GET') {
        const dadosLimpos = {};

        Object.entries({
            ...dados,
            acao
        }).forEach(([chave, valor]) => {
            if (
                valor !== undefined &&
                valor !== null &&
                valor !== 'undefined'
            ) {
                dadosLimpos[chave] = valor;
            }
        });

        const op = {
            method: metodo
        };

        let url = endpoint;

        if (metodo === 'POST') {
            op.headers = {
                'Content-Type': 'application/json'
            };

            op.body = JSON.stringify(dadosLimpos);
        } else {
            url += '?' + new URLSearchParams(dadosLimpos).toString();
        }

        const resposta = await fetch(url, op);
        const texto = await resposta.text();

        let json;

        try {
            json = JSON.parse(texto);
        } catch (erro) {
            throw new Error(
                'O servidor não devolveu uma resposta JSON válida.'
            );
        }

        if (!resposta.ok || !json.success) {
            throw new Error(
                json.message ||
                'Não foi possível processar a solicitação.'
            );
        }

        return json.data;
    }

    function preencherSelect(id, itens, valor, texto) {
        const el = $(id).empty().append('<option value="">Selecione</option>');
        itens.forEach(i => el.append(`<option value="${esc(i[valor])}">${esc(i[texto])}</option>`));
    }

    async function carregarIniciais() {
        iniciais = await api('dados_iniciais');
        const ano = new Date().getFullYear();
        for (let a = ano - 2; a <= ano + 1; a++) $('#obrigacaoAno').append(`<option value="${a}" ${a === ano ? 'selected' : ''}>${a}</option>`);
        preencherSelect('#obrigacaoFilial', iniciais.filiais || [], 'id', 'nome');
        $('#obrigacaoFilial option:first').text('Todas as filiais');
        preencherSelect('#pagBanco', iniciais.bancos || [], 'id', 'descricao');
        preencherSelect('#pagCaixa', iniciais.caixas || [], 'id', 'descricao');
        preencherSelect(
            '#pagCentroCusto',
            iniciais.centros || [],
            'id',
            'nome'
        );

        if ((iniciais.centros || []).length === 1) {
            $('#pagCentroCusto').val(
                String(iniciais.centros[0].id)
            );
        }
    }

    async function listar() {
        try {
            const data = await api('listar', { tipo, ano: $('#obrigacaoAno').val(), filial_id: $('#obrigacaoFilial').val() });
            $('#totalTrabalhador').text(moeda(data.resumo.trabalhador));
            $('#totalPatronal').text(moeda(data.resumo.patronal));
            $('#totalApurado').text(moeda(data.resumo.apurado));
            $('#totalPendente').text(moeda(data.resumo.saldo));
            $('#cardPatronal').toggleClass('d-none', tipo === 'IRPS');
            $('#rotuloTrabalhador').text(tipo === 'INSS' ? 'INSS do trabalhador' : 'IRPS retido');
            const body = $('#obrigacoesBody').empty();
            if (!data.registos.length) return body.html('<tr><td colspan="8" class="text-center">Nenhum apuramento encontrado.</td></tr>');

            data.registos.forEach(r => {
                const botaoPagar = Number(r.saldo) > 0
                    ? `<button class="btn btn-sm btn-success btn-pagar mr-1" data-id="${r.folha_id}" data-saldo="${r.saldo}"><i class="fa fa-money"></i> Pagar</button>`
                    : '';

                const botaoHistorico = Number(r.pago) > 0
                    ? `<button class="btn btn-sm btn-info btn-historico" data-id="${r.folha_id}" data-periodo="${esc(r.periodo)}"><i class="fa fa-history"></i> Histórico</button>`
                    : '';

                body.append(`
                    <tr>
                        <td>${esc(r.periodo)}</td>
                        <td>${moeda(r.trabalhador)}</td>
                        <td>${moeda(r.patronal)}</td>
                        <td>${moeda(r.apurado)}</td>
                        <td>${moeda(r.pago)}</td>
                        <td>${moeda(r.saldo)}</td>
                        <td><span class="badge badge-${r.estado === 'PAGO' ? 'success' : r.estado === 'PARCIAL' ? 'warning' : 'danger'}">${r.estado}</span></td>
                        <td>
                            <div class="btn-group">
                                ${botaoPagar}
                                ${botaoHistorico}
                            </div>
                        </td>
                    </tr>
                `);
            });
        } catch (e) { alerta('error', 'Erro', e.message); }
    }

    $(document).on('click', '#abasObrigacoes a', function () { tipo = $(this).data('tipo'); listar(); }); $(document).on('click', '#btnFiltrarObrigacoes', listar);

    function atualizarFontePagamento() {
        const fonte = $('#pagFonte').val();
        const usarBanco = fonte === 'BANCO';

        $('#grupoBanco').toggleClass('d-none', !usarBanco);
        $('#grupoCaixa').toggleClass('d-none', usarBanco);

        $('#pagBanco')
            .prop('required', usarBanco)
            .prop('disabled', !usarBanco);

        $('#pagCaixa')
            .prop('required', !usarBanco)
            .prop('disabled', usarBanco);

        if (usarBanco) {
            $('#pagCaixa').val('');
        } else {
            $('#pagBanco').val('');
        }
    }

    $(document).on('change', '#pagFonte', atualizarFontePagamento);

    $(document).on('click', '.btn-pagar', function () {
        const saldo = Number($(this).data('saldo'));

        $('#pagFolhaId').val($(this).data('id')); $('#pagTipo').val(tipo);
        $('#pagSaldoMaximo').val(saldo);

        $('#pagSaldoTexto').text(moeda(saldo));

        $('#pagValor')
            .val(saldo)
            .attr('max', saldo);

        const agora = new Date();
        agora.setMinutes(agora.getMinutes() - agora.getTimezoneOffset());
        $('#pagData').val(agora.toISOString().slice(0, 16));

        $('#pagFonte').val('BANCO');
        $('#pagComprovativo').val('');
        atualizarFontePagamento();

        $('#modalPagarObrigacao').modal('show');
    });

    // Ação do botão para exibir Histórico de Pagamentos
    $(document).on('click', '.btn-historico', async function () {
        const folhaId = $(this).data('id');
        const periodo = $(this).data('periodo');
        const body = $('#historicoPagamentosBody');

        $('#tituloHistoricoPagamentos').text(`Histórico de Pagamentos de ${tipo} - ${periodo}`);
        body.html('<tr><td colspan="6" class="text-center">A carregar...</td></tr>');
        $('#modalHistoricoPagamentos').modal('show');

        try {
            const registos = await api('pagamentos', { folha_id: folhaId, tipo, filial_id: $('#obrigacaoFilial').val() });

            if (!registos.length) {
                body.html('<tr><td colspan="6" class="text-center text-muted">Nenhum pagamento registado.</td></tr>');
                return;
            }

            body.html(registos.map(p => {
                const linkComprovativo = p.comprovativo
                    ? `<a href="${esc(p.comprovativo)}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-download"></i> Ver</a>`
                    : '<span class="text-muted">Sem ficheiro</span>';

                return `
                    <tr>
                        <td>${esc(p.data_pagamento)}</td>
                        <td><span class="badge badge-${p.fonte === 'BANCO' ? 'primary' : 'warning'}">${esc(p.fonte)}</span></td>
                        <td>${esc(p.conta_nome)}</td>
                        <td>${esc(p.referencia)}</td>
                        <td>${moeda(p.valor)}</td>
                        <td class="text-center">${linkComprovativo}</td>
                    </tr>
                `;
            }).join(''));
        } catch (erro) {
            body.html(`<tr><td colspan="6" class="text-center text-danger">${esc(erro.message)}</td></tr>`);
        }
    });

    // Submissão do Formulário de Pagamento com Suporte a Upload
    $(document).on('submit', '#formPagarObrigacao', async function (e) {
        e.preventDefault();
        const valor = Number($('#pagValor').val()), saldo = Number($('#pagSaldoMaximo').val());
        if (valor <= 0 || valor > saldo + 0.001) return alerta('warning', 'Valor inválido', 'O pagamento deve ser maior que zero e não pode ultrapassar o saldo.');

        const confirmar = await Swal.fire({
            icon: 'question',
            title: 'Confirmar pagamento?',
            text: `Será registado o pagamento de ${moeda(valor)}.`,
            showCancelButton: true,
            confirmButtonText: 'Sim, confirmar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmar.isConfirmed) return;

        try {
            Swal.fire({
                title: 'A processar pagamento...',
                text: 'Por favor, aguarde.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            const formData = new FormData();
            formData.append('acao', 'pagar');
            formData.append('folha_id', $('#pagFolhaId').val());
            formData.append('tipo', $('#pagTipo').val());
            formData.append('filial_id', $('#obrigacaoFilial').val());
            formData.append('centro_custo_id', $('#pagCentroCusto').val());
            formData.append('fonte', $('#pagFonte').val());
            formData.append('banco_id', $('#pagBanco').val());
            formData.append('caixa_id', $('#pagCaixa').val());
            formData.append('valor', valor);
            formData.append('data_pagamento', $('#pagData').val());
            formData.append('referencia', $('#pagReferencia').val().trim());
            formData.append('documento', $('#pagDocumento').val().trim());
            formData.append('observacoes', $('#pagObservacoes').val().trim());

            const ficheiroInput = document.getElementById('pagComprovativo');
            if (ficheiroInput && ficheiroInput.files[0]) {
                formData.append('comprovativo', ficheiroInput.files[0]);
            }

            const resposta = await fetch(endpoint, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const json = await resposta.json();

            if (!resposta.ok || !json.success) {
                throw new Error(json.message || 'Não foi possível registar o pagamento.');
            }

            $('#modalPagarObrigacao').modal('hide');
            this.reset();
            $('#pagFonte').val('BANCO');
            atualizarFontePagamento();
            await listar();

            alerta('success', 'Sucesso', 'Pagamento registado com sucesso.');
        } catch (erro) {
            alerta('error', 'Erro', erro.message);
        }
    });

    $(async function () {
        if (!document.getElementById('obrigacoesBody')) {
            return;
        }

        try {
            await carregarIniciais();
            await listar();
        } catch (e) {
            alerta('error', 'Erro', e.message);
        }
    });
})(jQuery);