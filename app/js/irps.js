"use strict";


// ==========================================================
// CONFIGURAÇÃO
// ==========================================================

const IRPS_URL =
    "/erpplus/app/ajax/irps_ajax.php";


let dadosIrps = {
    anoAtual: null,
    tabelaVigente: null,
    tabelas: [],
    resumo: {}
};


// ==========================================================
// INICIALIZAÇÃO
// ==========================================================

$(document).ready(
    async function () {

        configurarEventosIrps();

        await carregarDadosIniciaisIrps();

    }
);


// ==========================================================
// EVENTOS
// ==========================================================

function configurarEventosIrps() {

    $(
        "#btnPesquisarIrps, "
        + "#btnConsultarAnoIrps"
    ).on(
        "click",
        async function () {

            await consultarIrpsAno();

        }
    );


    $("#filtroAnoIrps").on(
        "keypress",
        async function (evento) {

            if (evento.key === "Enter") {

                evento.preventDefault();

                await consultarIrpsAno();

            }

        }
    );


    $(document).on(
        "click",
        ".btn-visualizar-irps",
        async function () {

            const id =
                Number(
                    $(this).data("id")
                    || 0
                );

            if (id <= 0) {
                return;
            }

            await carregarTabelaIrpsPorId(
                id
            );

        }
    );
}


// ==========================================================
// DADOS INICIAIS
// ==========================================================

async function carregarDadosIniciaisIrps() {

    try {

        bloquearPaginaIrps(true);


        const resposta =
            await requisicaoIrps(
                `${IRPS_URL}?acao=dados_iniciais`
            );


        const data =
            resposta.data || {};


        dadosIrps.anoAtual =
            Number(
                data.ano_atual
                || new Date().getFullYear()
            );


        dadosIrps.tabelaVigente =
            data.tabela_vigente
            || null;


        dadosIrps.tabelas =
            Array.isArray(data.tabelas)
                ? data.tabelas
                : [];


        dadosIrps.resumo =
            data.resumo
            || {};


        $("#filtroAnoIrps")
            .val(
                dadosIrps.anoAtual
            );


        renderizarTabelaVigenteIrps(
            dadosIrps.tabelaVigente,
            dadosIrps.anoAtual
        );


        renderizarHistoricoIrps(
            dadosIrps.tabelas
        );


    } catch (erro) {

        console.error(
            "Erro ao carregar IRPS:",
            erro
        );


        mostrarErroIrps(
            erro.message
            || "Não foi possível carregar a tabela de IRPS."
        );


    } finally {

        bloquearPaginaIrps(false);

    }
}


// ==========================================================
// CONSULTAR POR ANO
// ==========================================================

async function consultarIrpsAno() {

    const ano =
        Number(
            $("#filtroAnoIrps")
                .val()
            || 0
        );


    if (ano <= 0) {

        mostrarErroIrps(
            "Informe um ano válido."
        );

        return;
    }


    try {

        bloquearPaginaIrps(true);


        const resposta =
            await requisicaoIrps(
                `${IRPS_URL}`
                + `?acao=tabela_vigente`
                + `&ano=${encodeURIComponent(ano)}`
            );


        const tabela =
            resposta.data
            || null;


        dadosIrps.tabelaVigente =
            tabela;


        renderizarTabelaVigenteIrps(
            tabela,
            ano
        );


    } catch (erro) {

        limparTabelaVigenteIrps(
            ano
        );


        mostrarErroIrps(
            erro.message
            || "Não existe tabela de IRPS para o ano informado."
        );


    } finally {

        bloquearPaginaIrps(false);

    }
}


// ==========================================================
// CARREGAR TABELA PELO ID
// ==========================================================

async function carregarTabelaIrpsPorId(
    id
) {

    try {

        bloquearPaginaIrps(true);


        const resposta =
            await requisicaoIrps(
                `${IRPS_URL}`
                + `?acao=buscar`
                + `&id=${encodeURIComponent(id)}`
            );


        const tabela =
            resposta.data
            || null;


        if (!tabela) {

            throw new Error(
                "Tabela de IRPS não encontrada."
            );

        }


        const ano =
            Number(
                tabela.ano_inicio
                || 0
            );


        renderizarTabelaVigenteIrps(
            tabela,
            ano
        );


        if (ano > 0) {

            $("#filtroAnoIrps")
                .val(ano);

        }


        $("html, body").animate(
            {
                scrollTop:
                    $("#irpsNomeTabela")
                        .offset()
                        .top - 120
            },
            300
        );


    } catch (erro) {

        mostrarErroIrps(
            erro.message
        );


    } finally {

        bloquearPaginaIrps(false);

    }
}


// ==========================================================
// RENDERIZAR TABELA
// ==========================================================

function renderizarTabelaVigenteIrps(
    tabela,
    ano
) {

    $("#cardAnoIrps")
        .text(
            ano || "-"
        );


    if (!tabela) {

        limparTabelaVigenteIrps(
            ano
        );

        return;
    }


    const escaloes =
        Array.isArray(
            tabela.escaloes
        )
            ? tabela.escaloes
            : [];


    $("#cardEscaloesIrps")
        .text(
            escaloes.length
        );


    $("#cardMoedaIrps")
        .text(
            tabela.moeda
            || "-"
        );


    $("#cardEstadoIrps")
        .text(
            Number(tabela.ativo) === 1
                ? "Activa"
                : "Inactiva"
        );


    $("#irpsNomeTabela")
        .val(
            tabela.nome || "-"
        );


    $("#irpsDescricao")
        .val(
            tabela.descricao || "-"
        );

    $("#irpsMoeda")
        .val(
            tabela.moeda || "-"
        );

    $("#irpsVigencia")
        .val(
            formatarVigenciaIrps(
                tabela
            )
        );


    $("#irpsEstado")
        .html(
            badgeEstadoIrps(
                tabela.ativo
            )
        );


    renderizarEscaloesIrps(
        escaloes,
        tabela.moeda
    );
}


// ==========================================================
// ESCALÕES
// ==========================================================

function renderizarEscaloesIrps(
    escaloes,
    moeda
) {

    const tbody =
        $("#tbodyEscaloesIrps");


    tbody.empty();


    if (
        !Array.isArray(escaloes)
        || escaloes.length === 0
    ) {

        tbody.html(`
            <tr>
                <td
                    colspan="7"
                    class="text-center text-muted">

                    Nenhum escalão configurado.

                </td>
            </tr>
        `);

        return;
    }


    escaloes.forEach(
        function (escalao) {

            const faixa =
                formatarFaixaIrps(
                    escalao.limite_inferior,
                    escalao.limite_superior,
                    moeda
                );


            const coeficiente =
                escalao.coeficiente === null
                    || escalao.coeficiente === ""
                    ? "—"
                    : formatarPercentagemIrps(
                        escalao.coeficiente
                    );


            tbody.append(`
                <tr>

                    <td>
                        ${escaparHtmlIrps(faixa)}
                    </td>

                    <td class="text-right">
                        ${formatarValorBaseIrps(
                escalao.valor_base_0
            )}
                    </td>

                    <td class="text-right">
                        ${formatarValorBaseIrps(
                escalao.valor_base_1
            )}
                    </td>

                    <td class="text-right">
                        ${formatarValorBaseIrps(
                escalao.valor_base_2
            )}
                    </td>

                    <td class="text-right">
                        ${formatarValorBaseIrps(
                escalao.valor_base_3
            )}
                    </td>

                    <td class="text-right">
                        ${formatarValorBaseIrps(
                escalao.valor_base_4_mais
            )}
                    </td>

                    <td class="text-center">
                        ${coeficiente}
                    </td>

                </tr>
            `);

        }
    );
}


// ==========================================================
// HISTÓRICO
// ==========================================================

function renderizarHistoricoIrps(
    tabelas
) {

    const tbody =
        $("#tbodyHistoricoIrps");


    tbody.empty();


    if (
        !Array.isArray(tabelas)
        || tabelas.length === 0
    ) {

        tbody.html(`
            <tr>

                <td
                    colspan="6"
                    class="text-center text-muted">

                    Nenhuma tabela de IRPS cadastrada.

                </td>

            </tr>
        `);

        return;
    }


    tabelas.forEach(
        function (tabela) {

            tbody.append(`
                <tr>

                    <td>
                        ${escaparHtmlIrps(
                tabela.nome || ""
            )}
                    </td>

                    <td>
                        ${tabela.ano_inicio || "-"}
                    </td>

                    <td>
                        ${tabela.ano_fim
                    ? tabela.ano_fim
                    : "Actual"
                }
                    </td>

                    <td>
                        ${escaparHtmlIrps(
                    tabela.moeda || ""
                )}
                    </td>

                    <td>
                        ${badgeEstadoIrps(
                    tabela.ativo
                )}
                    </td>

                    <td class="text-center">

                        <button
                            type="button"
                            class="
                                btn
                                btn-sm
                                btn-outline-primary
                                btn-visualizar-irps
                            "
                            data-id="${Number(
                    tabela.id
                )}">

                            <i class="fas fa-eye"></i>

                        </button>

                    </td>

                </tr>
            `);

        }
    );
}


// ==========================================================
// LIMPAR
// ==========================================================

function limparTabelaVigenteIrps(
    ano
) {

    $("#cardAnoIrps")
        .text(
            ano || "-"
        );


    $("#cardEscaloesIrps")
        .text("0");


    $("#cardMoedaIrps")
        .text("-");


    $("#cardEstadoIrps")
        .text("-");


    $("#irpsNomeTabela")
        .val("-");

    $("#irpsDescricao")
        .val("-");

    $("#irpsMoeda")
        .val("-");

    $("#irpsVigencia")
        .val("-");


    $("#irpsEstado")
        .html("-");


    $("#tbodyEscaloesIrps")
        .html(`
            <tr>

                <td
                    colspan="7"
                    class="text-center text-muted">

                    Nenhuma tabela encontrada.

                </td>

            </tr>
        `);
}


// ==========================================================
// FORMATAR FAIXA
// ==========================================================

function formatarFaixaIrps(
    inferior,
    superior,
    moeda
) {

    const inicio =
        Number(
            inferior
            || 0
        );


    const temSuperior =
        superior !== null
        && superior !== ""
        && typeof superior !== "undefined";


    const moedaTexto =
        moeda
            ? ` ${moeda}`
            : "";


    if (inicio <= 0 && temSuperior) {

        return (
            "Até "
            + formatarNumeroIrps(
                Number(superior)
            )
            + moedaTexto
        );

    }


    if (!temSuperior) {

        return (
            "De "
            + formatarNumeroIrps(
                inicio
            )
            + " em diante"
            + moedaTexto
        );

    }


    return (
        "De "
        + formatarNumeroIrps(
            inicio
        )
        + " até "
        + formatarNumeroIrps(
            Number(superior)
        )
        + moedaTexto
    );
}


// ==========================================================
// VALOR BASE
// ==========================================================

function formatarValorBaseIrps(
    valor
) {

    /*
     * NULL = traço na tabela oficial.
     *
     * Zero deve continuar aparecendo
     * como 0,00, pois tem significado fiscal.
     */

    if (
        valor === null
        || typeof valor === "undefined"
        || valor === ""
    ) {

        return "—";

    }


    return formatarNumeroIrps(
        Number(valor)
    );
}


// ==========================================================
// NÚMEROS
// ==========================================================

function formatarNumeroIrps(
    valor
) {

    const numero =
        Number(valor);


    if (
        !Number.isFinite(
            numero
        )
    ) {

        return "0,00";

    }


    return numero.toLocaleString(
        "pt-PT",
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );
}


// ==========================================================
// PERCENTAGEM
// ==========================================================

function formatarPercentagemIrps(
    coeficiente
) {

    const valor =
        Number(
            coeficiente
        );


    if (
        !Number.isFinite(valor)
    ) {

        return "—";

    }


    return (
        formatarNumeroIrps(
            valor * 100
        )
        + "%"
    );
}


// ==========================================================
// VIGÊNCIA
// ==========================================================

function formatarVigenciaIrps(
    tabela
) {

    const inicio =
        tabela.ano_inicio
        || "-";


    const fim =
        tabela.ano_fim
            ? tabela.ano_fim
            : "Actual";


    return `${inicio} - ${fim}`;
}


// ==========================================================
// BADGE
// ==========================================================

function badgeEstadoIrps(
    ativo
) {

    if (
        Number(ativo) === 1
    ) {

        return `
            <span class="badge badge-success">
                Activa
            </span>
        `;

    }


    return `
        <span class="badge badge-secondary">
            Inactiva
        </span>
    `;
}


// ==========================================================
// REQUISIÇÃO
// ==========================================================

async function requisicaoIrps(
    url,
    opcoes = {}
) {

    const resposta =
        await fetch(
            url,
            {
                credentials:
                    "same-origin",

                ...opcoes
            }
        );


    let dados;


    try {

        dados =
            await resposta.json();

    } catch (erro) {

        throw new Error(
            "O servidor devolveu uma resposta inválida."
        );

    }


    if (
        !resposta.ok
        || dados.success === false
    ) {

        throw new Error(
            dados.mensagem
            || dados.message
            || "Não foi possível concluir a operação."
        );

    }


    return dados;
}


// ==========================================================
// BLOQUEAR
// ==========================================================

function bloquearPaginaIrps(
    bloquear
) {

    $(
        "#btnPesquisarIrps, "
        + "#btnConsultarAnoIrps"
    ).prop(
        "disabled",
        bloquear
    );


    $("#filtroAnoIrps")
        .prop(
            "disabled",
            bloquear
        );


    if (bloquear) {

        $("#btnPesquisarIrps")
            .html(`
                <span
                    class="spinner-border
                           spinner-border-sm
                           mr-1">
                </span>

                A consultar...
            `);

    } else {

        $("#btnPesquisarIrps")
            .html(`
                <i class="fas fa-search"></i>
                Consultar
            `);

    }
}


// ==========================================================
// ERRO
// ==========================================================

function mostrarErroIrps(
    mensagem
) {

    if (
        typeof Swal !== "undefined"
    ) {

        Swal.fire({
            icon:
                "error",

            title:
                "Não foi possível concluir",

            text:
                mensagem,

            confirmButtonText:
                "OK"
        });

        return;
    }


    alert(
        mensagem
    );
}


// ==========================================================
// SEGURANÇA HTML
// ==========================================================

function escaparHtmlIrps(
    valor
) {

    return $("<div>")
        .text(
            String(
                valor ?? ""
            )
        )
        .html();
}