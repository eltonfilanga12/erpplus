const MARCA_URL =
    "/erpplus/app/ajax/marca_ajax.php";

let tabelaMarcas = null;
let moduloMarcasInicializado = false;
let marcaImportacaoToken = '';
let marcaImportacaoDados = null;
let marcaImportacaoProcessando = false;


document.addEventListener(
    "DOMContentLoaded",
    inicializarMarcas
);


async function inicializarMarcas() {

    if (moduloMarcasInicializado) {
        return;
    }

    moduloMarcasInicializado = true;

    configurarEventosMarcas();

    await carregarStatusMarcas();

    carregarTabelaMarcas();
}


function configurarEventosMarcas() {

    $("#btnNovaMarca")
        .off("click.marca")
        .on(
            "click.marca",
            abrirNovaMarca
        );


    $("#formMarca")
        .off("submit.marca")
        .on(
            "submit.marca",
            guardarMarca
        );


    $("#formEliminarMarca")
        .off("submit.marca")
        .on(
            "submit.marca",
            eliminarMarca
        );


    document.getElementById('btnImportarMarca')?.addEventListener('click', abrirImportacaoMarca);
    document.getElementById('btnModeloExcelMarca')?.addEventListener('click', baixarModeloExcelMarca);
    document.getElementById('btnValidarExcelMarca')?.addEventListener('click', validarImportacaoMarca);
    document.getElementById('btnConfirmarImportacaoMarca')?.addEventListener('click', confirmarImportacaoMarca);
    document.getElementById('btnVoltarImportacaoMarca')?.addEventListener('click', voltarImportacaoMarca);
    document.getElementById('marcaFicheiroExcel')?.addEventListener('change', alterarFicheiroExcelMarca);


    $(document)
        .off(
            "click.marca",
            "#marcasTable .editarMarca"
        )
        .on(
            "click.marca",
            "#marcasTable .editarMarca",
            function () {

                const id =
                    Number(
                        $(this).data("id")
                    );

                editarMarca(id);
            }
        );


    $(document)
        .off(
            "click.marca",
            "#marcasTable .eliminarMarca"
        )
        .on(
            "click.marca",
            "#marcasTable .eliminarMarca",
            function () {

                const id =
                    Number(
                        $(this).data("id")
                    );

                abrirEliminarMarca(id);
            }
        );


    $("#modalMarca")
        .off("hidden.bs.modal.marca")
        .on(
            "hidden.bs.modal.marca",
            function () {
                limparFormularioMarca();
            }
        );
}


function carregarTabelaMarcas() {

    if (
        $.fn.DataTable.isDataTable(
            "#marcasTable"
        )
    ) {
        tabelaMarcas =
            $("#marcasTable")
                .DataTable();

        tabelaMarcas.ajax.reload(
            null,
            false
        );

        return;
    }


    tabelaMarcas =
        $("#marcasTable")
            .DataTable({

                ajax: {
                    url:
                        MARCA_URL
                        + "?acao=listar",

                    dataSrc: function (
                        resposta
                    ) {

                        if (
                            !resposta
                            || resposta.sucesso
                            !== true
                        ) {
                            mostrarMensagemMarca(
                                false,
                                resposta?.mensagem
                                || "Erro ao carregar marcas."
                            );

                            return [];
                        }

                        return (
                            resposta.dados
                            ?? []
                        );
                    },

                    error: function (
                        xhr
                    ) {
                        console.error(
                            "Erro ao carregar marcas:",
                            xhr.responseText
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
                        data: "nome",
                        defaultContent: "-"
                    },

                    {
                        data: "descricao",

                        render:
                            function (data) {
                                return data
                                    || "-";
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
                                    ? `<span class="badge badge-success">${escaparHtmlMarca(nome)}</span>`
                                    : `<span class="badge badge-secondary">${escaparHtmlMarca(nome || "-")}</span>`;
                            }
                    },

                    {
                        data: "criado_em",

                        render:
                            function (data) {

                                return formatarDataMarca(
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
                                            class="btn btn-sm btn-info editarMarca"
                                            data-id="${id}"
                                            title="Editar"
                                        >
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger eliminarMarca"
                                            data-id="${id}"
                                            title="Eliminar"
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
                            "Lista de Marcas",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4]
                        }
                    },

                    {
                        extend:
                            "pdfHtml5",

                        text:
                            '<i class="fa fa-file-pdf"></i> PDF',

                        title:
                            "Lista de Marcas",

                        orientation:
                            "landscape",

                        pageSize:
                            "A4",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4]
                        }
                    },

                    {
                        extend:
                            "print",

                        text:
                            '<i class="fa fa-print"></i> Imprimir',

                        title:
                            "Lista de Marcas",

                        exportOptions: {
                            columns:
                                [0, 1, 2, 3, 4]
                        }
                    }

                ]
            });
}



// Adicionar as funções auxiliares de importação:
function abrirImportacaoMarca() {
    marcaImportacaoToken = '';
    marcaImportacaoDados = null;
    marcaImportacaoProcessando = false;

    const ficheiro = document.getElementById('marcaFicheiroExcel');
    if (ficheiro) ficheiro.value = '';

    const label = document.querySelector('label[for="marcaFicheiroExcel"]');
    if (label) label.textContent = 'Selecione o ficheiro';

    document.getElementById('marcaImportacaoPasso1')?.classList.remove('d-none');
    document.getElementById('marcaImportacaoPasso2')?.classList.add('d-none');
    document.getElementById('btnValidarExcelMarca')?.classList.remove('d-none');
    document.getElementById('btnConfirmarImportacaoMarca')?.classList.add('d-none');
    document.getElementById('btnVoltarImportacaoMarca')?.classList.add('d-none');

    esconderErroImportacaoMarca();
    esconderSucessoImportacaoMarca();

    $('#modalImportarMarca').modal('show');
}

function alterarFicheiroExcelMarca(evento) {
    const ficheiro = evento.target.files?.[0];
    const label = document.querySelector('label[for="marcaFicheiroExcel"]');
    if (label) {
        label.textContent = ficheiro ? ficheiro.name : 'Selecione o ficheiro';
    }
}

function baixarModeloExcelMarca() {
    const url = MARCA_URL + '?acao=baixar_modelo_excel&_=' + Date.now();
    window.location.href = url;
}

async function validarImportacaoMarca() {
    if (marcaImportacaoProcessando) return;

    esconderErroImportacaoMarca();
    esconderSucessoImportacaoMarca();

    const input = document.getElementById('marcaFicheiroExcel');
    const ficheiro = input?.files?.[0];

    if (!ficheiro) {
        mostrarErroImportacaoMarca('Selecione um ficheiro Excel antes de continuar.');
        return;
    }

    const extensao = ficheiro.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls'].includes(extensao)) {
        mostrarErroImportacaoMarca('O ficheiro deve estar no formato XLSX ou XLS.');
        return;
    }

    const botao = document.getElementById('btnValidarExcelMarca');
    const htmlOriginal = botao?.innerHTML || '';

    try {
        marcaImportacaoProcessando = true;
        if (botao) {
            botao.disabled = true;
            botao.innerHTML = '<i class="fa fa-spinner fa-spin"></i> A validar...';
        }

        const dados = new FormData();
        dados.append('acao', 'validar_importacao');
        dados.append('ficheiro', ficheiro);

        const resposta = await fetch(MARCA_URL, {
            method: 'POST',
            body: dados,
            headers: { Accept: 'application/json' }
        });

        const json = await lerJsonMarca(resposta);
        if (!resposta.ok || !json.sucesso) {
            throw new Error(json.mensagem || 'Erro ao validar ficheiro.');
        }

        marcaImportacaoDados = json.dados || {};
        marcaImportacaoToken = String(marcaImportacaoDados.token || '');

        if (!marcaImportacaoToken) {
            throw new Error('O servidor não devolveu o token da importação.');
        }

        renderizarResultadoImportacaoMarca(marcaImportacaoDados);

        document.getElementById('marcaImportacaoPasso1')?.classList.add('d-none');
        document.getElementById('marcaImportacaoPasso2')?.classList.remove('d-none');
        document.getElementById('btnValidarExcelMarca')?.classList.add('d-none');
        document.getElementById('btnVoltarImportacaoMarca')?.classList.remove('d-none');

        const totalValidos = Number(marcaImportacaoDados.total_validos || 0);
        if (totalValidos > 0) {
            document.getElementById('btnConfirmarImportacaoMarca')?.classList.remove('d-none');
        } else {
            document.getElementById('btnConfirmarImportacaoMarca')?.classList.add('d-none');
        }
    } catch (erro) {
        mostrarErroImportacaoMarca(erro.message);
    } finally {
        marcaImportacaoProcessando = false;
        if (botao) {
            botao.disabled = false;
            botao.innerHTML = htmlOriginal;
        }
    }
}

function renderizarResultadoImportacaoMarca(dados) {
    const validos = Array.isArray(dados.validos) ? dados.validos : [];
    const erros = Array.isArray(dados.erros) ? dados.erros : [];

    renderizarMarcasValidasImportacao(validos);
    renderizarErrosImportacaoMarca(erros);

    document.getElementById('marcaImportacaoValidosContainer')?.classList.toggle('d-none', validos.length === 0);
    document.getElementById('marcaImportacaoErrosContainer')?.classList.toggle('d-none', erros.length === 0);
}

function renderizarMarcasValidasImportacao(validos) {
    const tbody = document.getElementById('marcaImportacaoValidos');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (validos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted">Nenhuma marca válida encontrada.</td></tr>`;
        return;
    }

    validos.forEach(item => {
        const preview = item.preview || {};
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escaparHtmlMarca(item.linha ?? '')}</td>
            <td><strong>${escaparHtmlMarca(preview.nome ?? '')}</strong></td>
            <td>${escaparHtmlMarca(preview.descricao ?? '')}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderizarErrosImportacaoMarca(erros) {
    const tbody = document.getElementById('marcaImportacaoErros');
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

        const listaErros = mensagens.map(m => `<div>${escaparHtmlMarca(m)}</div>`).join('');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escaparHtmlMarca(item.linha ?? '-')}</td>
            <td>${escaparHtmlMarca(dados.nome ?? '')}</td>
            <td class="text-danger">${listaErros}</td>
        `;
        tbody.appendChild(tr);
    });
}

async function confirmarImportacaoMarca() {
    if (marcaImportacaoProcessando || !marcaImportacaoToken) return;

    const totalValidos = Number(marcaImportacaoDados?.total_validos || 0);
    if (totalValidos <= 0) return;

    if (typeof Swal !== 'undefined') {
        const confirmacao = await Swal.fire({
            icon: 'question',
            title: 'Confirmar importação?',
            html: `Serão registadas <strong>${totalValidos}</strong> marcas.`,
            showCancelButton: true,
            confirmButtonText: 'Sim, importar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmacao.isConfirmed) return;
    }

    const botao = document.getElementById('btnConfirmarImportacaoMarca');
    const htmlOriginal = botao?.innerHTML || '';

    try {
        marcaImportacaoProcessando = true;
        if (botao) {
            botao.disabled = true;
            botao.innerHTML = '<i class="fa fa-spinner fa-spin"></i> A importar...';
        }

        const dados = new FormData();
        dados.append('acao', 'confirmar_importacao');
        dados.append('token', marcaImportacaoToken);

        const resposta = await fetch(MARCA_URL, {
            method: 'POST',
            body: dados,
            headers: { Accept: 'application/json' }
        });

        const json = await lerJsonMarca(resposta);
        if (!resposta.ok || !json.sucesso) {
            throw new Error(json.mensagem || 'Erro ao importar marcas.');
        }

        mostrarMensagemMarca(true, json.mensagem || 'Importação concluída com sucesso.');
        marcaImportacaoToken = '';
        marcaImportacaoDados = null;

        if (botao) botao.classList.add('d-none');
        carregarTabelaMarcas();

        setTimeout(() => {
            $('#modalImportarMarca').modal('hide');
        }, 1200);
    } catch (erro) {
        mostrarErroImportacaoMarca(erro.message);
    } finally {
        marcaImportacaoProcessando = false;
        if (botao) {
            botao.disabled = false;
            botao.innerHTML = htmlOriginal;
        }
    }
}

function voltarImportacaoMarca() {
    marcaImportacaoToken = '';
    marcaImportacaoDados = null;

    document.getElementById('marcaImportacaoPasso2')?.classList.add('d-none');
    document.getElementById('marcaImportacaoPasso1')?.classList.remove('d-none');
    document.getElementById('btnConfirmarImportacaoMarca')?.classList.add('d-none');
    document.getElementById('btnVoltarImportacaoMarca')?.classList.add('d-none');
    document.getElementById('btnValidarExcelMarca')?.classList.remove('d-none');

    const input = document.getElementById('marcaFicheiroExcel');
    if (input) input.value = '';

    const label = document.querySelector('label[for="marcaFicheiroExcel"]');
    if (label) label.textContent = 'Selecione o ficheiro';

    esconderErroImportacaoMarca();
}

function mostrarErroImportacaoMarca(mensagem) {
    const el = document.getElementById('marcaImportacaoErro');
    if (el) {
        el.textContent = mensagem;
        el.classList.remove('d-none');
    }
}

function esconderErroImportacaoMarca() {
    document.getElementById('marcaImportacaoErro')?.classList.add('d-none');
}

function mostrarSucessoImportacaoMarca(mensagem) {
    const el = document.getElementById('marcaImportacaoSucesso');
    if (el) {
        el.textContent = mensagem;
        el.classList.remove('d-none');
    }
}

function esconderSucessoImportacaoMarca() {
    document.getElementById('marcaImportacaoSucesso')?.classList.add('d-none');
}

async function carregarStatusMarcas() {

    const select =
        document.getElementById(
            "marcaStatusId"
        );

    if (!select) {
        return;
    }

    select.innerHTML =
        `<option value="">A carregar estados...</option>`;

    try {

        const resposta =
            await fetch(
                MARCA_URL
                + "?acao=status",
                {
                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonMarca(
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


        const estados =
            json.dados
            ?? [];


        select.innerHTML =
            `<option value="">Selecione...</option>`;


        estados.forEach(
            function (status) {

                select.insertAdjacentHTML(
                    "beforeend",
                    `
                    <option value="${status.id}">
                        ${escaparHtmlMarca(status.nome)}
                    </option>
                    `
                );

            }
        );


        if (estados.length === 0) {
            select.innerHTML =
                `<option value="">Nenhum estado configurado</option>`;
        }

    } catch (erro) {

        console.error(
            "Erro ao carregar status das marcas:",
            erro
        );

        select.innerHTML =
            `<option value="">Erro ao carregar estados</option>`;
    }
}


function abrirNovaMarca() {

    limparFormularioMarca();

    $("#tituloModalMarca")
        .text(
            "Nova Marca"
        );

    $("#modalMarca")
        .modal("show");
}


async function editarMarca(id) {

    if (!id) {
        return;
    }

    try {

        const resposta =
            await fetch(
                MARCA_URL
                + "?acao=buscar&id="
                + encodeURIComponent(id),

                {
                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonMarca(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {
            throw new Error(
                json.mensagem
                || "Erro ao buscar marca."
            );
        }


        const marca =
            json.dados;


        $("#marcaId")
            .val(
                marca.id
            );

        $("#marcaNome")
            .val(
                marca.nome
                ?? ""
            );

        $("#marcaDescricao")
            .val(
                marca.descricao
                ?? ""
            );

        $("#marcaStatusId")
            .val(
                marca.status_id
            );


        $("#tituloModalMarca")
            .text(
                "Editar Marca"
            );


        $("#modalMarca")
            .modal("show");

    } catch (erro) {

        console.error(
            "Erro ao editar marca:",
            erro
        );

        mostrarMensagemMarca(
            false,
            erro.message
        );
    }
}


async function guardarMarca(evento) {

    evento.preventDefault();

    const formulario =
        evento.currentTarget;

    const botao =
        document.getElementById(
            "btnGuardarMarca"
        );

    const dados =
        new FormData(
            formulario
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
                MARCA_URL,
                {
                    method:
                        "POST",

                    body:
                        dados,

                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonMarca(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {
            throw new Error(
                json.mensagem
                || "Erro ao guardar marca."
            );
        }


        $("#modalMarca")
            .modal("hide");


        limparFormularioMarca();


        if (tabelaMarcas) {
            tabelaMarcas.ajax.reload(
                null,
                false
            );
        }


        mostrarMensagemMarca(
            true,
            json.mensagem
        );

    } catch (erro) {

        console.error(
            "Erro ao guardar marca:",
            erro
        );

        mostrarMensagemMarca(
            false,
            erro.message
        );

    } finally {

        if (botao) {
            botao.disabled = false;

            botao.innerHTML =
                '<i class="fa fa-save"></i> Guardar';
        }
    }
}


function abrirEliminarMarca(id) {

    $("#eliminarMarcaId")
        .val(id);

    $("#modalEliminarMarca")
        .modal("show");
}


async function eliminarMarca(evento) {

    evento.preventDefault();

    const formulario =
        evento.currentTarget;

    const dados =
        new FormData(
            formulario
        );

    dados.append(
        "acao",
        "eliminar"
    );


    try {

        const resposta =
            await fetch(
                MARCA_URL,
                {
                    method:
                        "POST",

                    body:
                        dados,

                    headers: {
                        Accept:
                            "application/json"
                    }
                }
            );


        const json =
            await lerJsonMarca(
                resposta
            );


        if (
            !resposta.ok
            || !json.sucesso
        ) {
            throw new Error(
                json.mensagem
                || "Erro ao eliminar marca."
            );
        }


        $("#modalEliminarMarca")
            .modal("hide");


        if (tabelaMarcas) {
            tabelaMarcas.ajax.reload(
                null,
                false
            );
        }


        mostrarMensagemMarca(
            true,
            json.mensagem
        );

    } catch (erro) {

        console.error(
            "Erro ao eliminar marca:",
            erro
        );

        mostrarMensagemMarca(
            false,
            erro.message
        );
    }
}


function limparFormularioMarca() {

    const formulario =
        document.getElementById(
            "formMarca"
        );

    if (formulario) {
        formulario.reset();
    }

    $("#marcaId")
        .val("");

    $("#tituloModalMarca")
        .text(
            "Nova Marca"
        );
}


function mostrarMensagemMarca(
    sucesso,
    mensagem
) {
    Swal.fire({
        icon: sucesso
            ? "success"
            : "error",

        title: sucesso
            ? "Sucesso!"
            : "Erro!",

        text: mensagem
            || (
                sucesso
                    ? "Operação realizada com sucesso."
                    : "Ocorreu um erro ao processar a operação."
            ),

        confirmButtonText: "OK"
    });
}

async function lerJsonMarca(
    resposta
) {

    const texto =
        await resposta.text();

    try {
        return JSON.parse(texto);

    } catch (erro) {

        console.error(
            "Resposta inválida:",
            texto
        );

        throw new Error(
            "O servidor devolveu uma resposta inválida."
        );
    }
}


function escaparHtmlMarca(
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


function formatarDataMarca(
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