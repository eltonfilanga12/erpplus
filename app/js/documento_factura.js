
"use strict";

(function () {
    const API_URL =
        "/erpplus/app/ajax/documento_venda_ajax.php";

    const elemento = (id) => document.getElementById(id);

    function texto(id, valor) {
        elemento(id).textContent =
            valor === null || valor === undefined || valor === ""
                ? "-"
                : String(valor);
    }

    function numero(valor) {
        const n = Number(valor ?? 0);
        return Number.isFinite(n) ? n : 0;
    }

    function moeda(valor) {
        return new Intl.NumberFormat("pt-MZ", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(numero(valor)) + " MT";
    }

    function dataFormatada(valor, incluirHora = false) {
        if (!valor) return "-";

        // Interpretar a data sem conversões de fuso horário.
        const parte = String(valor).replace("T", " ").split(" ");
        const d = parte[0].split("-");

        if (d.length !== 3) return String(valor);

        const data = `${d[2]}/${d[1]}/${d[0]}`;

        return incluirHora && parte[1]
            ? `${data} ${parte[1].slice(0, 5)}`
            : data;
    }

    function adicionarCelula(linha, valor, classe = "") {
        const td = document.createElement("td");
        td.textContent =
            valor === null || valor === undefined ? "" : String(valor);

        if (classe) td.className = classe;
        linha.appendChild(td);
        return td;
    }

    function mostrarMensagem(mensagem, erro = false) {
        const caixa = elemento("mensagem");
        caixa.hidden = false;
        caixa.textContent = mensagem;
        caixa.style.background = erro ? "#f8d7da" : "#fff3cd";
        caixa.style.color = erro ? "#842029" : "#664d03";
    }

    function carregarItens(itens) {
        const tbody = elemento("itensFactura");
        tbody.replaceChildren();

        itens.forEach((item, indice) => {
            const linha = document.createElement("tr");

            adicionarCelula(linha, indice + 1);

            adicionarCelula(
                linha,
                item.produto_nome || item.nome_produto || "Produto"
            );

            adicionarCelula(
                linha,
                new Intl.NumberFormat("pt-MZ", {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 3
                }).format(numero(item.quantidade)),
                "centro"
            );

            adicionarCelula(
                linha,
                moeda(item.preco_unitario),
                "direita"
            );

            // O subtotal do artigo já vem calculado da base de dados.
            // Não somamos novamente o imposto global da venda.
            adicionarCelula(
                linha,
                moeda(item.subtotal),
                "direita"
            );

            tbody.appendChild(linha);
        });

        if (!itens.length) {
            const linha = document.createElement("tr");
            const celula = adicionarCelula(
                linha,
                "Não existem artigos associados a esta factura."
            );
            celula.colSpan = 5;
            tbody.appendChild(linha);
        }
    }

    function preencherDocumento(dados) {
        const empresa = dados.empresa || {};
        const venda = dados.venda || {};
        const conta = dados.conta_receber || {};
        const itens = Array.isArray(dados.itens) ? dados.itens : [];

        if (String(venda.tipo_documento || "").toUpperCase() !== "FACTURA") {
            throw new Error(
                "Este documento não é do tipo FACTURA."
            );
        }

        texto("empresaNome", empresa.nome_fantasia || empresa.nome);
        texto("empresaSlogan", empresa.slogan);
        texto(
            "empresaEndereco",
            [empresa.logradouro, empresa.cidade, empresa.provincia, empresa.pais]
                .filter(Boolean)
                .join(", ")
        );
        texto("empresaContacto", empresa.telefone ? "Tel.: " + empresa.telefone : "");
        texto("empresaEmail", empresa.email);
        texto("empresaNuit", empresa.nuit ? "NUIT: " + empresa.nuit : "");

        const logo = elemento("empresaLogo");
        if (empresa.logo) {
            // Ajustar este caminho caso o ERP guarde os logótipos
            // numa pasta diferente.
            logo.src = "/erpplus/public/uploads/empresas/" +
                encodeURIComponent(empresa.logo);

            logo.onload = () => { logo.hidden = false; };
            logo.onerror = () => { logo.hidden = true; };
        } else {
            logo.hidden = true;
        }

        texto(
            "numeroFactura",
            "N.º " + (venda.numero_fatura || venda.numero_documento || venda.id)
        );

        texto("clienteNome", venda.cliente_nome || "Cliente não identificado");
        texto("clienteDetalhes", venda.cliente_nuit
            ? "NUIT: " + venda.cliente_nuit
            : "");

        texto("dataEmissao", dataFormatada(venda.data_venda, true));
        texto("dataVencimento", dataFormatada(venda.data_vencimento));
        texto(
            "condicaoPagamento",
            String(venda.condicao_pagamento || "").toUpperCase() === "APRAZO"
                ? "A prazo"
                : "Pronto pagamento"
        );
        texto("filialNome", venda.filial_nome || empresa.nome_fantasia || empresa.nome);

        const total = numero(venda.total);
        const recebido = numero(conta.valor_recebido);
        const saldo = conta.saldo !== undefined && conta.saldo !== null
            ? numero(conta.saldo)
            : Math.max(0, total - recebido);

        const subtotal = numero(venda.subtotal);
        const desconto = numero(venda.desconto);
        const imposto = numero(venda.imposto);
        const taxaImposto = numero(venda.taxa_imposto);

        texto("subtotal", moeda(subtotal));
        texto("desconto", moeda(desconto));
        texto(
            "textoImposto",
            "IVA" + (taxaImposto > 0 ? ` (${taxaImposto}%)` : "")
        );
        texto("imposto", moeda(imposto));
        texto("total", moeda(total));
        texto("valorRecebido", moeda(recebido));
        texto("saldo", moeda(saldo));
        texto("observacoes", venda.observacoes || "Sem observações.");

        const estado = elemento("estadoPagamento");
        if (saldo <= 0) {
            estado.textContent = "PAGA";
            estado.style.background = "#d1e7dd";
            estado.style.color = "#0f5132";
        } else {
            estado.textContent = recebido > 0 ? "PARCIALMENTE PAGA" : "A PRAZO";
        }

        texto(
            "rodapeEmpresa",
            [empresa.slogan, empresa.website].filter(Boolean).join(" · ")
        );

        carregarItens(itens);

        elemento("mensagem").hidden = true;
        elemento("factura").hidden = false;
        elemento("btnImprimir").disabled = false;
        document.title =
            "Factura " + (venda.numero_fatura || venda.id) + " | ERP Plus";
    }

    async function iniciar() {
        const parametros = new URLSearchParams(window.location.search);
        const id = Number(parametros.get("id") || 0);

        if (!Number.isInteger(id) || id <= 0) {
            mostrarMensagem("Identificador da factura inválido.", true);
            return;
        }

        try {
            const resposta = await fetch(
                `${API_URL}?id=${encodeURIComponent(id)}`,
                {
                    method: "GET",
                    credentials: "same-origin",
                    headers: { "Accept": "application/json" }
                }
            );

            const json = await resposta.json();

            if (!resposta.ok || json.success !== true) {
                throw new Error(
                    json.mensagem || "Não foi possível carregar a factura."
                );
            }

            preencherDocumento(json.data || {});
        } catch (erro) {
            console.error("Erro ao carregar factura:", erro);
            mostrarMensagem(
                erro.message || "Erro ao carregar os dados da factura.",
                true
            );
        }
    }

    elemento("btnImprimir").addEventListener("click", function () {
        window.print();
    });

    iniciar();
})();