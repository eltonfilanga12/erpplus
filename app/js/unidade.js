const UNIDADE_URL =
    "/erpplus/app/ajax/unidade_ajax.php";

let tabelaUnidades = null;
let moduloUnidadesInicializado = false;
let unidadeImportacaoToken = '';
let unidadeImportacaoDados = null;
let unidadeImportacaoProcessando = false;


document.addEventListener(
    "DOMContentLoaded",
    inicializarUnidades
);


async function inicializarUnidades() {

    if (moduloUnidadesInicializado) {
        return;
    }

    moduloUnidadesInicializado = true;

    configurarEventosUnidades();

    await carregarStatusUnidades();

    carregarTabelaUnidades();
}


function configurarEventosUnidades() {

    $("#btnNovaUnidade")
        .off("click.unidade")
        .on(
            "click.unidade",
            abrirNovaUnidade
        );

    document.getElementById('btnImportarUnidade')?.addEventListener('click', abrirImportacaoUnidade);
    document.getElementById('btnModeloExcelUnidade')?.addEventListener('click', baixarModeloExcelUnidade);
    document.getElementById('btnValidarExcelUnidade')?.addEventListener('click', validarImportacaoUnidade);
    document.getElementById('btnConfirmarImportacaoUnidade')?.addEventListener('click', confirmarImportacaoUnidade);
    document.getElementById('btnVoltarImportacaoUnidade')?.addEventListener('click', voltarImportacaoUnidade);
    document.getElementById('unidadeFicheiroExcel')?.addEventListener('change', alterarFicheiroExcelUnidade);


    $("#formUnidade")
        .off("submit.unidade")
        .on(
            "submit.unidade",
            guardarUnidade
        );


    $("#formEliminarUnidade")
        .off("submit.unidade")
        .on(
            "submit.unidade",
            eliminarUnidade
        );


    $(document)
        .off(
            "click.unidade",
            "#unidadesTable .editarUnidade"
        )
        .on(
            "click.unidade",
            "#unidadesTable .editarUnidade",
            function () {

                editarUnidade(
                    Number(
                        $(this).data("id")
                    )
                );
            }
        );


    $(document)
        .off(
            "click.unidade",
            "#unidadesTable .eliminarUnidade"
        )
        .on(
            "click.unidade",
            "#unidadesTable .eliminarUnidade",
            function () {

                abrirEliminarUnidade(
                    Number(
                        $(this).data("id")
                    )
                );
            }
        );


    $("#modalUnidade")
        .off("hidden.bs.modal.unidade")
        .on(
            "hidden.bs.modal.unidade",
            limparFormularioUnidade
        );
}


// Adicionar as funções auxiliares de importação:
function abrirImportacaoUnidade() {
    unidadeImportacaoToken = '';
    unidadeImportacaoDados = null;
    unidadeImportacaoProcessando = false;

    const ficheiro = document.getElementById('unidadeFicheiroExcel');
    if (ficheiro) ficheiro.value = '';

    const label = document.querySelector('label[for="unidadeFicheiroExcel"]');
    if (label) label.textContent = 'Selecione o ficheiro';

    document.getElementById('unidadeImportacaoPasso1')?.classList.remove('d-none');
    document.getElementById('unidadeImportacaoPasso2')?.classList.add('d-none');
    document.getElementById('btnValidarExcelUnidade')?.classList.remove('d-none');
    document.getElementById('btnConfirmarImportacaoUnidade')?.classList.add('d-none');
    document.getElementById('btnVoltarImportacaoUnidade')?.classList.add('d-none');

    esconderErroImportacaoUnidade();
    esconderSucessoImportacaoUnidade();

    $('#modalImportarUnidade').modal('show');
}

function alterarFicheiroExcelUnidade(evento) {
    const ficheiro = evento.target.files?.[0];
    const label = document.querySelector('label[for="unidadeFicheiroExcel"]');
    if (label) {
        label.textContent = ficheiro ? ficheiro.name : 'Selecione o ficheiro';
    }
}

function baixarModeloExcelUnidade() {
    const url = UNIDADE_URL + '?acao=baixar_modelo_excel&_=' + Date.now();
    window.location.href = url;
}

async function validarImportacaoUnidade() {
    if (unidadeImportacaoProcessando) return;

    esconderErroImportacaoUnidade();
    esconderSucessoImportacaoUnidade();

    const input = document.getElementById('unidadeFicheiroExcel');
    const ficheiro = input?.files?.[0];

    if (!ficheiro) {
        mostrarErroImportacaoUnidade('Selecione um ficheiro Excel antes de continuar.');
        return;
    }

    const extensao = ficheiro.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls'].includes(extensao)) {
        mostrarErroImportacaoUnidade('O ficheiro deve estar no formato XLSX ou XLS.');
        return;
    }

    const botao = document.getElementById('btnValidarExcelUnidade');
    const htmlOriginal = botao?.innerHTML || '';

    try {
        unidadeImportacaoProcessando = true;
        if (botao) {
            botao.disabled = true;
            botao.innerHTML = '<i class="fa fa-spinner fa-spin"></i> A validar...';
        }

        const dados = new FormData();
        dados.append('acao', 'validar_importacao');
        dados.append('ficheiro', ficheiro);

        const resposta = await fetch(UNIDADE_URL, {
            method: 'POST',
            body: dados,
            headers: { Accept: 'application/json' }
        });

        const json = await lerJsonUnidade(resposta);
        if (!resposta.ok || !json.sucesso) {
            throw new Error(json.mensagem || 'Erro ao validar ficheiro.');
        }

        unidadeImportacaoDados = json.dados || {};
        unidadeImportacaoToken = String(unidadeImportacaoDados.token || '');

        if (!unidadeImportacaoToken) {
            throw new Error('O servidor não devolveu o token da importação.');
        }

        renderizarResultadoImportacaoUnidade(unidadeImportacaoDados);

        document.getElementById('unidadeImportacaoPasso1')?.classList.add('d-none');
        document.getElementById('unidadeImportacaoPasso2')?.classList.remove('d-none');
        document.getElementById('btnValidarExcelUnidade')?.classList.add('d-none');
        document.getElementById('btnVoltarImportacaoUnidade')?.classList.remove('d-none');

        const totalValidos = Number(unidadeImportacaoDados.total_validos || 0);
        if (totalValidos > 0) {
            document.getElementById('btnConfirmarImportacaoUnidade')?.classList.remove('d-none');
        } else {
            document.getElementById('btnConfirmarImportacaoUnidade')?.classList.add('d-none');
        }
    } catch (erro) {
        mostrarErroImportacaoUnidade(erro.message);
    } finally {
        unidadeImportacaoProcessando = false;
        if (botao) {
            botao.disabled = false;
            botao.innerHTML = htmlOriginal;
        }
    }
}

function renderizarResultadoImportacaoUnidade(dados) {
    const validos = Array.isArray(dados.validos) ? dados.validos : [];
    const erros = Array.isArray(dados.erros) ? dados.erros : [];

    renderizarUnidadesValidasImportacao(validos);
    renderizarErrosImportacaoUnidade(erros);

    document.getElementById('unidadeImportacaoValidosContainer')?.classList.toggle('d-none', validos.length === 0);
    document.getElementById('unidadeImportacaoErrosContainer')?.classList.toggle('d-none', erros.length === 0);
}

function renderizarUnidadesValidasImportacao(validos) {
    const tbody = document.getElementById('unidadeImportacaoValidos');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (validos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Nenhuma unidade válida encontrada.</td></tr>`;
        return;
    }

    validos.forEach(item => {
        const preview = item.preview || {};
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escaparHtmlUnidade(item.linha ?? '')}</td>
            <td><strong>${escaparHtmlUnidade(preview.codigo ?? '')}</strong></td>
            <td>${escaparHtmlUnidade(preview.nome ?? '')}</td>
            <td>${escaparHtmlUnidade(preview.casas_decimais ?? 0)}</td>
            <td>${escaparHtmlUnidade(preview.descricao ?? '')}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderizarErrosImportacaoUnidade(erros) {
    const tbody = document.getElementById('unidadeImportacaoErros');
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

        const listaErros = mensagens.map(m => `<div>${escaparHtmlUnidade(m)}</div>`).join('');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escaparHtmlUnidade(item.linha ?? '-')}</td>
            <td>${escaparHtmlUnidade(dados.codigo ?? '')} - ${escaparHtmlUnidade(dados.nome ?? '')}</td>
            <td class="text-danger">${listaErros}</td>
        `;
        tbody.appendChild(tr);
    });
}

async function confirmarImportacaoUnidade() {
    if (unidadeImportacaoProcessando || !unidadeImportacaoToken) return;

    const totalValidos = Number(unidadeImportacaoDados?.total_validos || 0);
    if (totalValidos <= 0) return;

    if (typeof Swal !== 'undefined') {
        const confirmacao = await Swal.fire({
            icon: 'question',
            title: 'Confirmar importação?',
            html: `Serão registadas <strong>${totalValidos}</strong> unidades.`,
            showCancelButton: true,
            confirmButtonText: 'Sim, importar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmacao.isConfirmed) return;
    }

    const botao = document.getElementById('btnConfirmarImportacaoUnidade');
    const htmlOriginal = botao?.innerHTML || '';

    try {
        unidadeImportacaoProcessando = true;
        if (botao) {
            botao.disabled = true;
            botao.innerHTML = '<i class="fa fa-spinner fa-spin"></i> A importar...';
        }

        const dados = new FormData();
        dados.append('acao', 'confirmar_importacao');
        dados.append('token', unidadeImportacaoToken);

        const resposta = await fetch(UNIDADE_URL, {
            method: 'POST',
            body: dados,
            headers: { Accept: 'application/json' }
        });

        const json = await lerJsonUnidade(resposta);
        if (!resposta.ok || !json.sucesso) {
            throw new Error(json.mensagem || 'Erro ao importar unidades.');
        }

        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: json.mensagem || 'Importação concluída com sucesso.',
            confirmButtonText: 'OK'
        });

        unidadeImportacaoToken = '';
        unidadeImportacaoDados = null;

        if (botao) botao.classList.add('d-none');
        tabelaUnidades?.ajax.reload(null, false);

        setTimeout(() => {
            $('#modalImportarUnidade').modal('hide');
        }, 1200);
    } catch (erro) {
        mostrarErroImportacaoUnidade(erro.message);
    } finally {
        unidadeImportacaoProcessando = false;
        if (botao) {
            botao.disabled = false;
            botao.innerHTML = htmlOriginal;
        }
    }
}

function voltarImportacaoUnidade() {
    unidadeImportacaoToken = '';
    unidadeImportacaoDados = null;

    document.getElementById('unidadeImportacaoPasso2')?.classList.add('d-none');
    document.getElementById('unidadeImportacaoPasso1')?.classList.remove('d-none');
    document.getElementById('btnConfirmarImportacaoUnidade')?.classList.add('d-none');
    document.getElementById('btnVoltarImportacaoUnidade')?.classList.add('d-none');
    document.getElementById('btnValidarExcelUnidade')?.classList.remove('d-none');

    const input = document.getElementById('unidadeFicheiroExcel');
    if (input) input.value = '';

    const label = document.querySelector('label[for="unidadeFicheiroExcel"]');
    if (label) label.textContent = 'Selecione o ficheiro';

    esconderErroImportacaoUnidade();
}

function mostrarErroImportacaoUnidade(mensagem) {
    const el = document.getElementById('unidadeImportacaoErro');
    if (el) {
        el.textContent = mensagem;
        el.classList.remove('d-none');
    }
}

function esconderErroImportacaoUnidade() {
    document.getElementById('unidadeImportacaoErro')?.classList.add('d-none');
}

function mostrarSucessoImportacaoUnidade(mensagem) {
    const el = document.getElementById('unidadeImportacaoSucesso');
    if (el) {
        el.textContent = mensagem;
        el.classList.remove('d-none');
    }
}

function esconderSucessoImportacaoUnidade() {
    document.getElementById('unidadeImportacaoSucesso')?.classList.add('d-none');
}

function carregarTabelaUnidades() {

    if (
        $.fn.DataTable.isDataTable(
            "#unidadesTable"
        )
    ) {
        tabelaUnidades =
            $("#unidadesTable")
                .DataTable();

        tabelaUnidades.ajax.reload(
            null,
            false
        );

        return;
    }


    tabelaUnidades =
        $("#unidadesTable")
            .DataTable({

                ajax: {
                    url:
                        UNIDADE_URL
                        + "?acao=listar",

                    dataSrc: function (
                        resposta
                    ) {

                        if (
                            !resposta
                            || resposta.sucesso !== true
                        ) {
                            Swal.fire({
                                icon: "error",
                                title: "Erro!",
                                text:
                                    resposta?.mensagem
                                    || "Erro ao carregar unidades."
                            });

                            return [];
                        }

                        return (
                            resposta.dados
                            ?? []
                        );
                    }
                },


                columns: [

                    {
                        data: null,

                        render: function (
                            data,
                            type,
                            row,
                            meta
                        ) {
                            const numero =
                                meta.row
                                + meta.settings._iDisplayStart
                                + 1;

                            return String(numero)
                                .padStart(4, "0");
                        }
                    },

                    {
                        data: "codigo"
                    },

                    {
                        data: "nome"
                    },

                    {
                        data: "casas_decimais",

                        render:
                            function (data) {

                                return Number(
                                    data
                                    ?? 0
                                );
                            }
                    },

                    {
                        data: "status_nome",

                        render:
                            function (data) {

                                const nome =
                                    String(
                                        data
                                        ?? ""
                                    );

                                const ativo =
                                    [
                                        "activo",
                                        "ativo",
                                        "activa",
                                        "ativa"
                                    ].includes(
                                        nome
                                            .trim()
                                            .toLowerCase()
                                    );

                                return ativo
                                    ? `<span class="badge badge-success">${escaparHtmlUnidade(nome)}</span>`
                                    : `<span class="badge badge-secondary">${escaparHtmlUnidade(nome || "-")}</span>`;
                            }
                    },

                    {
                        data: "criado_em",

                        render:
                            function (data) {
                                return formatarDataUnidade(
                                    data
                                );
                            }
                    },

                    {
                        data: "id",

                        orderable: false,
                        searchable: false,

                        render:
                            function (id) {

                                return `
                                <div class="text-right">

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-info editarUnidade"
                                        data-id="${id}"
                                    >
                                        <i class="fa fa-edit"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger eliminarUnidade"
                                        data-id="${id}"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </button>

                                </div>
                                `;
                            }
                    }

                ],


                language: {
                    url:
                        "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-PT.json"
                },


                pageLength: 10,

                lengthMenu: [
                    [5, 10, 25, 50, 100],
                    [5, 10, 25, 50, 100]
                ],

                responsive: true,

                autoWidth: false,


                dom:
                    "<'row'<'col-sm-12 col-md-8 d-flex align-items-center'Bl><'col-sm-12 col-md-4'f>>"
                    +
                    "<'row'<'col-sm-12'tr>>"
                    +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",


                buttons: [

                    {
                        extend:
                            "excelHtml5",

                        text:
                            '<i class="fa fa-file-excel"></i> Excel',

                        title:
                            "Lista de Unidades",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4, 5]
                        }
                    },

                    {
                        extend:
                            "pdfHtml5",

                        text:
                            '<i class="fa fa-file-pdf"></i> PDF',

                        title:
                            "Lista de Unidades",

                        orientation:
                            "landscape",

                        pageSize:
                            "A4",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4, 5]
                        }
                    },

                    {
                        extend:
                            "print",

                        text:
                            '<i class="fa fa-print"></i> Imprimir',

                        title:
                            "Lista de Unidades",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4, 5]
                        }
                    }

                ]
            });
}


async function carregarStatusUnidades() {

    const select =
        document.getElementById(
            "unidadeStatusId"
        );

    if (!select) {
        return;
    }

    select.innerHTML =
        '<option value="">A carregar estados...</option>';


    try {

        const resposta =
            await fetch(
                UNIDADE_URL
                + "?acao=status",
                {
                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonUnidade(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {
            throw new Error(
                json.mensagem
                || "Erro ao carregar estados."
            );
        }


        select.innerHTML =
            '<option value="">Selecione...</option>';


        (
            json.dados
            ?? []
        ).forEach(
            function (status) {

                select.insertAdjacentHTML(
                    "beforeend",
                    `
                    <option value="${status.id}">
                        ${escaparHtmlUnidade(status.nome)}
                    </option>
                    `
                );
            }
        );

    } catch (erro) {

        console.error(
            erro
        );

        select.innerHTML =
            '<option value="">Erro ao carregar estados</option>';
    }
}


function abrirNovaUnidade() {

    limparFormularioUnidade();

    $("#tituloModalUnidade")
        .text(
            "Nova Unidade"
        );

    $("#modalUnidade")
        .modal("show");
}


async function editarUnidade(id) {

    try {

        const resposta =
            await fetch(
                UNIDADE_URL
                + "?acao=buscar&id="
                + encodeURIComponent(id)
            );


        const json =
            await lerJsonUnidade(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {
            throw new Error(
                json.mensagem
                || "Erro ao buscar unidade."
            );
        }


        const u =
            json.dados;


        $("#unidadeId")
            .val(u.id);

        $("#unidadeCodigo")
            .val(u.codigo);

        $("#unidadeNome")
            .val(u.nome);

        $("#unidadeCasasDecimais")
            .val(u.casas_decimais);

        $("#unidadeStatusId")
            .val(u.status_id);

        $("#unidadeDescricao")
            .val(
                u.descricao
                ?? ""
            );


        $("#tituloModalUnidade")
            .text(
                "Editar Unidade"
            );


        $("#modalUnidade")
            .modal("show");

    } catch (erro) {

        Swal.fire({
            icon: "error",
            title: "Erro!",
            text:
                erro.message
                || "Erro ao carregar unidade."
        });
    }
}


async function guardarUnidade(
    evento
) {

    evento.preventDefault();

    const botao =
        document.getElementById(
            "btnGuardarUnidade"
        );

    const dados =
        new FormData(
            evento.currentTarget
        );

    dados.append(
        "acao",
        "guardar"
    );


    if (botao) {
        botao.disabled = true;

        botao.innerHTML =
            '<i class="fa fa-spinner fa-spin"></i> A guardar...';
    }


    try {

        const resposta =
            await fetch(
                UNIDADE_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await lerJsonUnidade(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {
            throw new Error(
                json.mensagem
                || "Erro ao guardar unidade."
            );
        }


        $("#modalUnidade")
            .modal("hide");


        limparFormularioUnidade();


        tabelaUnidades
            ?.ajax
            .reload(
                null,
                false
            );


        Swal.fire({
            icon:
                "success",

            title:
                "Sucesso!",

            text:
                json.mensagem
                || "Unidade guardada com sucesso.",

            confirmButtonText:
                "OK"
        });

    } catch (erro) {

        Swal.fire({
            icon:
                "error",

            title:
                "Erro!",

            text:
                erro.message
                || "Erro ao guardar unidade.",

            confirmButtonText:
                "OK"
        });

    } finally {

        if (botao) {
            botao.disabled =
                false;

            botao.innerHTML =
                '<i class="fa fa-save"></i> Guardar';
        }
    }
}


function abrirEliminarUnidade(
    id
) {

    $("#eliminarUnidadeId")
        .val(id);

    $("#modalEliminarUnidade")
        .modal("show");
}


async function eliminarUnidade(
    evento
) {

    evento.preventDefault();

    const dados =
        new FormData(
            evento.currentTarget
        );

    dados.append(
        "acao",
        "eliminar"
    );


    try {

        const resposta =
            await fetch(
                UNIDADE_URL,
                {
                    method:
                        "POST",

                    body:
                        dados
                }
            );


        const json =
            await lerJsonUnidade(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {
            throw new Error(
                json.mensagem
                || "Erro ao eliminar unidade."
            );
        }


        $("#modalEliminarUnidade")
            .modal("hide");


        tabelaUnidades
            ?.ajax
            .reload(
                null,
                false
            );


        Swal.fire({
            icon:
                "success",

            title:
                "Sucesso!",

            text:
                json.mensagem
                || "Unidade eliminada com sucesso.",

            confirmButtonText:
                "OK"
        });

    } catch (erro) {

        Swal.fire({
            icon:
                "error",

            title:
                "Erro!",

            text:
                erro.message
                || "Erro ao eliminar unidade."
        });
    }
}


function limparFormularioUnidade() {

    document
        .getElementById(
            "formUnidade"
        )
        ?.reset();

    $("#unidadeId")
        .val("");

    $("#unidadeCasasDecimais")
        .val("0");

    $("#tituloModalUnidade")
        .text(
            "Nova Unidade"
        );
}


async function lerJsonUnidade(
    resposta
) {

    const texto =
        await resposta.text();

    try {
        return JSON.parse(
            texto
        );

    } catch {

        console.error(
            "Resposta do servidor:",
            texto
        );

        throw new Error(
            "O servidor devolveu uma resposta inválida."
        );
    }
}


function escaparHtmlUnidade(
    valor
) {

    return String(
        valor
        ?? ""
    )
        .replaceAll(
            "&",
            "&amp;"
        )
        .replaceAll(
            "<",
            "&lt;"
        )
        .replaceAll(
            ">",
            "&gt;"
        )
        .replaceAll(
            '"',
            "&quot;"
        )
        .replaceAll(
            "'",
            "&#039;"
        );
}


function formatarDataUnidade(
    valor
) {

    if (!valor) {
        return "-";
    }

    const data =
        new Date(
            String(valor)
                .replace(
                    " ",
                    "T"
                )
        );

    if (
        Number.isNaN(
            data.getTime()
        )
    ) {
        return valor;
    }

    return data.toLocaleString(
        "pt-MZ"
    );
}