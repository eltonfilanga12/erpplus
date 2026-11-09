"use strict";

/*
|--------------------------------------------------------------------------
| Variáveis Globais
|--------------------------------------------------------------------------
*/

const AJAX_URL = "/erpplus/app/ajax/salarios_funcionarios_ajax.php";

let tabelaSalarios = null;

let rubricasDisponiveis = [];

let modoEdicao = false;

let salarioAtual = null;


/*
|--------------------------------------------------------------------------
| Inicialização
|--------------------------------------------------------------------------
*/

$(document).ready(function () {

    inicializarDataTable();

    carregarFuncionarios();

    carregarRubricas();

    eventos();

});

function eventos() {

    /*
    |--------------------------------------------------------------------------
    | Novo salário
    |--------------------------------------------------------------------------
    */

    $("#btnNovoSalario").on("click", function () {

        modoEdicao = false;
        salarioAtual = null;

        limparFormulario();

        $("#tituloModalSalario").text(
            "Novo Salário"
        );

        $("#btnGuardarSalario").html(`
            <i class="fa fa-save"></i>
            Guardar
        `);

        activarModoFormulario(true);

    });


    /*
    |--------------------------------------------------------------------------
    | Funcionário
    |--------------------------------------------------------------------------
    */

    $("#funcionario_id").on(
        "change",
        function () {

            const funcionarioId =
                Number(
                    $(this).val() || 0
                );

            if (funcionarioId <= 0) {

                limparDadosContrato();

                return;
            }

            carregarContratos(
                funcionarioId
            );

            /*
             * Durante uma nova configuração salarial,
             * carrega automaticamente os dados do
             * contrato activo.
             *
             * Na edição não sobrescrevemos os dados
             * que já pertencem ao registo salarial.
             */
            if (!modoEdicao) {

                carregarDadosContratoActivo(
                    funcionarioId
                );
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Adicionar rubrica
    |--------------------------------------------------------------------------
    */

    $("#btnAdicionarRubrica").on(
        "click",
        function () {

            adicionarLinhaRubrica();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Remover rubrica
    |--------------------------------------------------------------------------
    */

    $("#tbodyRubricas").on(
        "click",
        ".removerRubrica",
        function () {

            $(this).closest("tr").remove();

            actualizarMensagemSemRubricas();

            calcularResumo();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Recalcular resumo
    |--------------------------------------------------------------------------
    */

    $("#salario_base").on(
        "input change",
        calcularResumo
    );

    $("#tbodyRubricas").on(
        "input change",
        [
            ".rubrica_id",
            ".valor",
            ".percentagem",
            ".quantidade"
        ].join(","),
        calcularResumo
    );


    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    $("#formSalarioFuncionario").on(
        "submit",
        function (evento) {

            evento.preventDefault();

            guardar();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Editar
    |--------------------------------------------------------------------------
    */

    $("#salariosFuncionariosTable").on(
        "click",
        ".btn-editar-salario",
        function () {

            const id = Number(
                $(this).data("id")
            );

            editarSalario(id);

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Visualizar
    |--------------------------------------------------------------------------
    */

    $("#salariosFuncionariosTable").on(
        "click",
        ".btn-ver-salario",
        function () {

            const id = Number(
                $(this).data("id")
            );

            visualizarSalario(id);

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Activar
    |--------------------------------------------------------------------------
    */

    $("#salariosFuncionariosTable").on(
        "click",
        ".btn-activar",
        function () {

            const id = Number(
                $(this).data("id")
            );

            alterarEstadoSalario(
                id,
                "activar"
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Inactivar
    |--------------------------------------------------------------------------
    */

    $("#salariosFuncionariosTable").on(
        "click",
        ".btn-inactivar",
        function () {

            const id = Number(
                $(this).data("id")
            );

            alterarEstadoSalario(
                id,
                "inactivar"
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Limpar ao fechar
    |--------------------------------------------------------------------------
    */

    $("#modalSalarioFuncionario").on(
        "hidden.bs.modal",
        function () {

            limparFormulario();

        }
    );

}

function carregarDadosContratoActivo(
    funcionarioId
) {

    funcionarioId =
        Number(
            funcionarioId || 0
        );

    if (funcionarioId <= 0) {
        return;
    }

    $.ajax({

        url: AJAX_URL,

        type: "POST",

        dataType: "json",

        data: {
            acao:
                "contrato_activo",

            funcionario_id:
                funcionarioId
        },

        success: function (
            resposta
        ) {

            if (!resposta.success) {

                limparDadosContrato();

                Swal.fire(
                    "Atenção",
                    resposta.message ||
                    "O funcionário não possui contrato activo.",
                    "warning"
                );

                return;
            }

            const contrato =
                resposta.data || {};

            preencherDadosContrato(
                contrato
            );
        },

        error: function (xhr) {

            limparDadosContrato();

            let mensagem =
                "Não foi possível carregar os dados do contrato.";

            try {

                const resposta =
                    JSON.parse(
                        xhr.responseText
                    );

                mensagem =
                    resposta.message
                    || resposta.mensagem
                    || mensagem;

            } catch (erro) {
                // mantém a mensagem padrão
            }

            Swal.fire(
                "Atenção",
                mensagem,
                "warning"
            );
        }

    });
}

function preencherDadosContrato(
    contrato
) {

    if (!contrato) {
        return;
    }


    // ==========================================
    // CONTRATO
    // ==========================================

    if (contrato.id) {

        /*
         * carregarContratos() é assíncrono.
         * Por isso aguardamos o select ser
         * preenchido antes de selecionar.
         */
        const contratoId =
            String(
                contrato.id
            );

        const tentarSelecionar =
            function () {

                const opcao =
                    $(
                        "#contrato_id option[value='"
                        + contratoId
                        + "']"
                    );

                if (opcao.length) {

                    $("#contrato_id")
                        .val(
                            contratoId
                        );

                    return;
                }

                setTimeout(
                    tentarSelecionar,
                    100
                );
            };

        tentarSelecionar();
    }


    // ==========================================
    // SALÁRIO BASE
    // ==========================================

    $("#salario_base")
        .val(
            Number(
                contrato.salario_base
                || 0
            ).toFixed(2)
        );


    // ==========================================
    // MOEDA
    // ==========================================

    $("#moeda")
        .val(
            String(
                contrato.moeda
                || "MZN"
            ).toUpperCase()
        );


    // ==========================================
    // PERIODICIDADE
    // ==========================================

    $("#periodicidade")
        .val(
            String(
                contrato.periodicidade_salario
                || "MENSAL"
            ).toUpperCase()
        );


    // ==========================================
    // DATA DE INÍCIO
    // ==========================================

    $("#data_inicio")
        .val(
            String(
                contrato.data_inicio
                || ""
            ).substring(
                0,
                10
            )
        );


    // ==========================================
    // HORAS MENSAIS
    // ==========================================

    const horasSemanais =
        Number(
            contrato.horas_semanais
            || 0
        );

    if (horasSemanais > 0) {

        /*
         * Mantemos a convenção que estamos
         * usando no módulo salarial:
         *
         * 40h semanais -> 176h mensais.
         *
         * 176 / 40 = 4.4 semanas médias.
         */
        const horasMensais =
            horasSemanais
            * 4.4;

        $("#horas_mensais")
            .val(
                horasMensais.toFixed(2)
            );
    }


    // ==========================================
    // DIAS MENSAIS
    // ==========================================

    /*
     * O contrato não possui directamente
     * dias_mensais.
     *
     * Para o padrão salarial que estamos
     * usando, mantemos 22.
     */
    $("#dias_mensais")
        .val(
            22
        );


    // ==========================================
    // MOTIVO
    // ==========================================

    if (
        !$("#motivo_alteracao")
            .val()
    ) {

        $("#motivo_alteracao")
            .val(
                "Salário inicial"
            );
    }


    calcularResumo();
}

function limparDadosContrato() {

    $("#contrato_id")
        .html(
            '<option value="">Selecione...</option>'
        );

    $("#salario_base")
        .val("");

    $("#moeda")
        .val("MZN");

    $("#periodicidade")
        .val("MENSAL");

    $("#horas_mensais")
        .val("");

    $("#dias_mensais")
        .val("");

    $("#data_inicio")
        .val("");

    calcularResumo();
}

function criarBadgeEstadoSalario(
    estado
) {
    const texto = String(
        estado || ""
    ).trim();

    const normalizado = texto
        .toLowerCase()
        .normalize("NFD")
        .replace(
            /[\u0300-\u036f]/g,
            ""
        );

    let classe =
        "badge badge-secondary";

    if (
        normalizado === "activo" ||
        normalizado === "ativo"
    ) {
        classe =
            "badge badge-success";
    }

    if (
        normalizado === "inactivo" ||
        normalizado === "inativo"
    ) {
        classe =
            "badge badge-primary";
    }

    if (
        normalizado === "suspenso" ||
        normalizado === "pendente"
    ) {
        classe =
            "badge badge-warning";
    }

    if (
        normalizado === "cancelado" ||
        normalizado === "bloqueado"
    ) {
        classe =
            "badge badge-danger";
    }

    return `
        <span
            class="${classe}"
            style="
                min-width: 75px;
                padding: 6px 10px;
                border-radius: 15px;
                font-size: 12px;
            ">
            ${escaparHtmlSalario(texto)}
        </span>
    `;
}

function formatarMoedaTabela(
    valor,
    moeda = "MZN"
) {
    const numero = Number(valor || 0);

    try {

        return new Intl.NumberFormat(
            "pt-MZ",
            {
                style: "currency",
                currency: moeda || "MZN",
                minimumFractionDigits: 2
            }
        ).format(numero);

    } catch (erro) {

        return numero.toLocaleString(
            "pt-PT",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ) + " " + (moeda || "MZN");
    }
}

function inicializarDataTable() {

    tabelaSalarios =
        $("#salariosFuncionariosTable")
            .DataTable({

                processing: true,
                responsive: true,
                autoWidth: false,
                destroy: true,

                ajax: {

                    /*
                     * Colocamos a ação também na URL para evitar
                     * endpoints que estejam a ler apenas $_GET.
                     */
                    url: AJAX_URL + "?acao=listar",

                    type: "POST",

                    dataType: "json",

                    data: function (dados) {

                        /*
                         * Mantém a ação também no POST.
                         */
                        dados.acao = "listar";

                        return dados;
                    },

                    dataSrc: function (resposta) {

                        console.log(
                            "Resposta da listagem:",
                            resposta
                        );

                        const sucesso =
                            resposta.sucesso ??
                            resposta.success ??
                            false;

                        const mensagem =
                            resposta.mensagem ??
                            resposta.message ??
                            "Não foi possível carregar os salários.";

                        const dados =
                            resposta.dados ??
                            resposta.data ??
                            [];

                        if (!sucesso) {

                            Swal.fire(
                                "Erro",
                                mensagem,
                                "error"
                            );

                            return [];
                        }

                        return normalizarLista(dados);
                    },

                    error: function (
                        xhr,
                        status,
                        erro
                    ) {

                        console.error(
                            "Erro DataTables:",
                            {
                                status: status,
                                erro: erro,
                                resposta: xhr.responseText
                            }
                        );

                        let mensagem =
                            "Não foi possível carregar os salários.";

                        try {

                            const resposta =
                                JSON.parse(xhr.responseText);

                            mensagem =
                                resposta.mensagem ??
                                resposta.message ??
                                mensagem;

                        } catch (e) {

                            if (xhr.responseText) {
                                mensagem =
                                    xhr.responseText;
                            }
                        }

                        Swal.fire(
                            "Erro",
                            mensagem,
                            "error"
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

                            return String(
                                numero
                            ).padStart(
                                4,
                                "0"
                            );
                        }
                    },

                    {
                        data: "funcionario"
                    },

                    {
                        data: "contrato",

                        defaultContent: "-"
                    },

                    {
                        data: "salario_base",

                        render: function (
                            valor,
                            tipo,
                            linha
                        ) {
                            if (
                                tipo === "sort" ||
                                tipo === "type"
                            ) {
                                return Number(valor || 0);
                            }

                            return formatarMoedaTabela(
                                valor,
                                linha.moeda
                            );
                        }
                    },

                    {
                        data: "salario_bruto",

                        render: function (
                            valor,
                            tipo,
                            linha
                        ) {
                            if (
                                tipo === "sort" ||
                                tipo === "type"
                            ) {
                                return Number(valor || 0);
                            }

                            return formatarMoedaTabela(
                                valor,
                                linha.moeda
                            );
                        }
                    },

                    {
                        data: "salario_liquido",

                        render: function (
                            valor,
                            tipo,
                            linha
                        ) {
                            if (
                                tipo === "sort" ||
                                tipo === "type"
                            ) {
                                return Number(valor || 0);
                            }

                            return formatarMoedaTabela(
                                valor,
                                linha.moeda
                            );
                        }
                    },

                    {
                        data: "data_inicio"
                    },

                    {
                        data: "data_fim",

                        defaultContent: "-"
                    },

                    {
                        data: "estado",

                        className: "text-center",

                        render: function (
                            valor,
                            tipo
                        ) {
                            if (tipo !== "display") {
                                return valor || "";
                            }

                            return criarBadgeEstadoSalario(
                                valor
                            );
                        }
                    },

                    {
                        data: null,

                        orderable: false,

                        searchable: false,

                        className: "text-center",

                        render: criarBotoesAcao
                    }

                ],

                language: {
                    processing:
                        "A processar...",

                    search:
                        "Pesquisar:",

                    lengthMenu:
                        "Mostrar _MENU_ registos",

                    info:
                        "Mostrando _START_ a _END_ de _TOTAL_ registos",

                    infoEmpty:
                        "Nenhum registo encontrado",

                    zeroRecords:
                        "Nenhum salário encontrado",

                    emptyTable:
                        "Nenhum salário registado",

                    paginate: {
                        first: "Primeiro",
                        previous: "Anterior",
                        next: "Seguinte",
                        last: "Último"
                    }
                }

            });

}



/*
|--------------------------------------------------------------------------
| URLs auxiliares
|--------------------------------------------------------------------------
|
| Ajusta apenas estes caminhos caso os teus ficheiros AJAX tenham
| nomes diferentes.
|
*/

const FUNCIONARIOS_AJAX_URL =
    "/erpplus/app/ajax/funcionarios_ajax.php";

const CONTRATOS_AJAX_URL =
    "/erpplus/app/ajax/contratos_trabalho_ajax.php";

const RUBRICAS_AJAX_URL =
    "/erpplus/app/ajax/rubricas_salariais_ajax.php";


/*
|--------------------------------------------------------------------------
| Botões de ação da DataTable
|--------------------------------------------------------------------------
*/

function criarBotoesAcao(data, type, row) {

    const id = Number(row.id || 0);

    const estado = String(
        row.estado || row.status_nome || ""
    ).toLowerCase();

    let botaoEstado = "";

    if (
        estado === "activo" ||
        estado === "ativo"
    ) {

        botaoEstado = `
            <button
                type="button"
                class="btn btn-sm btn-warning btn-inactivar"
                data-id="${id}"
                title="Inactivar">

                <i class="fa fa-ban"></i>

            </button>
        `;

    } else {

        botaoEstado = `
            <button
                type="button"
                class="btn btn-sm btn-success btn-activar"
                data-id="${id}"
                title="Activar">

                <i class="fa fa-check"></i>

            </button>
        `;

    }

    return `
        <div class="btn-group btn-group-sm">

            <button
                type="button"
                class="btn btn-info btn-ver-salario"
                data-id="${id}"
                title="Ver">

                <i class="fa fa-eye"></i>

            </button>

            <button
                type="button"
                class="btn btn-primary btn-editar-salario"
                data-id="${id}"
                title="Editar">

                <i class="fa fa-edit"></i>

            </button>

            ${botaoEstado}

        </div>
    `;

}


/*
|--------------------------------------------------------------------------
| Carregar funcionários
|--------------------------------------------------------------------------
*/

function carregarFuncionarios() {

    $.ajax({

        url: FUNCIONARIOS_AJAX_URL,

        type: "POST",

        dataType: "json",

        data: {
            acao: "listar"
        },

        success: function (resposta) {

            if (!resposta.success) {

                console.error(
                    resposta.message ||
                    "Não foi possível carregar os funcionários."
                );

                return;

            }

            const funcionarios = normalizarLista(
                resposta.data
            );

            const select = $("#funcionario_id");

            select.empty();

            select.append(`
                <option value="">
                    Selecione...
                </option>
            `);

            funcionarios.forEach(function (funcionario) {

                const id = funcionario.id;

                const nome =
                    funcionario.nome_completo ||
                    funcionario.nome ||
                    funcionario.funcionario ||
                    `Funcionário #${id}`;

                select.append(
                    $("<option>", {
                        value: id,
                        text: nome
                    })
                );

            });

        },

        error: function (xhr) {

            console.error(
                "Erro ao carregar funcionários:",
                xhr.responseText
            );

        }

    });

}


/*
|--------------------------------------------------------------------------
| Carregar contratos do funcionário
|--------------------------------------------------------------------------
*/

function carregarContratos(
    funcionarioId,
    contratoSelecionado = null
) {

    const select = $("#contrato_id");

    select.empty();

    select.append(`
        <option value="">
            Selecione...
        </option>
    `);

    funcionarioId = Number(funcionarioId || 0);

    if (funcionarioId <= 0) {
        return;
    }

    select.prop("disabled", true);

    $.ajax({

        url: CONTRATOS_AJAX_URL,

        type: "POST",

        dataType: "json",

        data: {
            acao: "listar",
            funcionario_id: funcionarioId
        },

        success: function (resposta) {

            if (!resposta.success) {

                Swal.fire(
                    "Atenção",
                    resposta.message ||
                    "Não foi possível carregar os contratos.",
                    "warning"
                );

                return;

            }

            const contratos = normalizarLista(
                resposta.data
            );

            contratos.forEach(function (contrato) {

                const id = contrato.id;

                const codigo =
                    contrato.codigo ||
                    contrato.numero_contrato ||
                    `Contrato #${id}`;

                const tipo =
                    contrato.tipo_contrato ||
                    contrato.tipo ||
                    "";

                const texto = tipo
                    ? `${codigo} - ${tipo}`
                    : codigo;

                select.append(
                    $("<option>", {
                        value: id,
                        text: texto
                    })
                );

            });

            if (contratoSelecionado) {

                select.val(
                    String(contratoSelecionado)
                );

            }

        },

        error: function (xhr) {

            console.error(
                "Erro ao carregar contratos:",
                xhr.responseText
            );

            Swal.fire(
                "Erro",
                "Ocorreu um erro ao carregar os contratos.",
                "error"
            );

        },

        complete: function () {

            select.prop("disabled", false);

        }

    });

}


/*
|--------------------------------------------------------------------------
| Carregar rubricas salariais
|--------------------------------------------------------------------------
*/

function carregarRubricas() {

    $.ajax({

        url: RUBRICAS_AJAX_URL,

        type: "POST",

        dataType: "json",

        data: {
            acao: "listar"
        },

        success: function (resposta) {

            const sucesso =
                resposta.sucesso ??
                resposta.success ??
                false;

            const mensagem =
                resposta.mensagem ??
                resposta.message ??
                "Não foi possível carregar as rubricas.";

            const dados =
                resposta.dados ??
                resposta.data ??
                resposta.registos ??
                [];

            if (!sucesso) {

                console.error(
                    "Erro ao carregar rubricas:",
                    mensagem
                );

                rubricasDisponiveis = [];

                Swal.fire(
                    "Atenção",
                    mensagem,
                    "warning"
                );

                return;
            }

            rubricasDisponiveis =
                normalizarLista(dados)
                    .filter(function (rubrica) {

                        const estado = String(
                            rubrica.estado ??
                            rubrica.status_nome ??
                            rubrica.status ??
                            ""
                        )
                            .trim()
                            .toLowerCase();

                        return (
                            estado === "" ||
                            estado === "activo" ||
                            estado === "ativo" ||
                            estado === "ativa" ||
                            estado === "activa"
                        );
                    });

        },

        error: function (
            xhr,
            status,
            erro
        ) {

            rubricasDisponiveis = [];

            console.error(
                "Erro HTTP ao carregar rubricas:",
                {
                    status: status,
                    erro: erro,
                    resposta: xhr.responseText
                }
            );

            let mensagem =
                "Ocorreu um erro ao carregar as rubricas.";

            try {

                const resposta =
                    JSON.parse(xhr.responseText);

                mensagem =
                    resposta.mensagem ??
                    resposta.message ??
                    mensagem;

            } catch (e) {
                // resposta não é JSON
            }

            Swal.fire(
                "Erro",
                mensagem,
                "error"
            );
        }

    });
}
/*
|--------------------------------------------------------------------------
| Adicionar linha de rubrica
|--------------------------------------------------------------------------
*/

function adicionarLinhaRubrica(
    dados = {}
) {

    $("#linhaSemRubricas").remove();

    const template = $("#templateRubrica")
        .clone();

    template.removeAttr("id");

    template.removeClass("d-none");

    template.addClass("linha-rubrica");

    preencherSelectRubricas(
        template.find(".rubrica_id"),
        dados.rubrica_id || ""
    );

    template.find(".valor").val(
        dados.valor ?? ""
    );

    template.find(".percentagem").val(
        dados.percentagem ?? ""
    );

    template.find(".quantidade").val(
        dados.quantidade_padrao ??
        dados.quantidade ??
        ""
    );

    template.find(".data_inicio").val(
        normalizarDataInput(
            dados.data_inicio || ""
        )
    );

    template.find(".data_fim").val(
        normalizarDataInput(
            dados.data_fim || ""
        )
    );

    template.find(".observacoes").val(
        dados.observacoes || ""
    );

    /*
     * ID da relação existente.
     * Útil durante a edição.
     */
    template.attr(
        "data-id",
        dados.id || ""
    );

    $("#tbodyRubricas").append(
        template
    );

    calcularResumo();

}


/*
|--------------------------------------------------------------------------
| Preencher select de rubricas
|--------------------------------------------------------------------------
*/

function preencherSelectRubricas(
    select,
    valorSelecionado = ""
) {

    select.empty();

    select.append(`
        <option value="">
            Selecione...
        </option>
    `);

    rubricasDisponiveis.forEach(
        function (rubrica) {

            const id = rubrica.id;

            const codigo =
                rubrica.codigo || "";

            const nome =
                rubrica.nome ||
                rubrica.designacao ||
                `Rubrica #${id}`;

            const tipo =
                rubrica.tipo ||
                rubrica.natureza ||
                rubrica.categoria ||
                "";

            const textoCodigo = codigo
                ? `${codigo} - `
                : "";

            const option = $("<option>", {
                value: id,
                text: `${textoCodigo}${nome}`
            });

            option.attr(
                "data-tipo",
                tipo
            );

            option.attr(
                "data-nome",
                nome
            );

            option.attr(
                "data-calculo",
                rubrica.tipo_calculo ||
                rubrica.forma_calculo ||
                ""
            );

            select.append(option);

        }
    );

    if (valorSelecionado !== "") {

        select.val(
            String(valorSelecionado)
        );

    }

}


/*
|--------------------------------------------------------------------------
| Mensagem da tabela de rubricas
|--------------------------------------------------------------------------
*/

function actualizarMensagemSemRubricas() {

    const quantidade = $(
        "#tbodyRubricas .linha-rubrica"
    ).length;

    if (quantidade > 0) {

        $("#linhaSemRubricas").remove();

        return;

    }

    $("#tbodyRubricas").html(`
        <tr id="linhaSemRubricas">

            <td
                colspan="8"
                class="text-center text-muted">

                Nenhuma rubrica adicionada.

            </td>

        </tr>
    `);

}

/*
|--------------------------------------------------------------------------
| Recolher rubricas
|--------------------------------------------------------------------------
*/

function obterRubricasFormulario() {

    const rubricas = [];

    $("#tbodyRubricas .linha-rubrica").each(
        function () {

            const linha = $(this);

            const rubricaId = Number(
                linha.find(".rubrica_id").val() || 0
            );

            if (rubricaId <= 0) {
                return;
            }

            rubricas.push({

                id: linha.attr("data-id") || null,

                rubrica_id: rubricaId,

                valor: converterNumero(
                    linha.find(".valor").val()
                ),

                percentagem: converterNumero(
                    linha.find(".percentagem").val()
                ),

                quantidade_padrao: converterNumero(
                    linha.find(".quantidade").val()
                ),

                data_inicio:
                    linha.find(".data_inicio").val() ||
                    null,

                data_fim:
                    linha.find(".data_fim").val() ||
                    null,

                observacoes:
                    linha.find(".observacoes").val()
                        .trim() ||
                    null

            });

        }
    );

    return rubricas;

}

/*
|--------------------------------------------------------------------------
| Calcular resumo
|--------------------------------------------------------------------------
*/

function calcularResumo() {

    const salarioBase = converterNumero(
        $("#salario_base").val()
    );

    let totalProventos = 0;
    let totalDescontos = 0;

    const linhasResumo = [];

    $("#tbodyRubricas .linha-rubrica").each(
        function () {

            const linha = $(this);

            const select = linha.find(
                ".rubrica_id"
            );

            const option = select.find(
                "option:selected"
            );

            const rubricaId = Number(
                select.val() || 0
            );

            if (rubricaId <= 0) {
                return;
            }

            const nome =
                option.data("nome") ||
                option.text();

            const tipo = normalizarTipoRubrica(
                option.data("tipo")
            );

            const valorInformado = converterNumero(
                linha.find(".valor").val()
            );

            const percentagem = converterNumero(
                linha.find(".percentagem").val()
            );

            const quantidade = converterNumero(
                linha.find(".quantidade").val()
            );

            let valorCalculado = valorInformado;

            /*
             * Quando houver percentagem, calcula
             * sobre o salário base.
             */
            if (percentagem > 0) {

                valorCalculado =
                    salarioBase *
                    (percentagem / 100);

            }

            /*
             * Quando houver quantidade e valor,
             * multiplica ambos.
             */
            if (
                quantidade > 0 &&
                valorInformado > 0 &&
                percentagem <= 0
            ) {

                valorCalculado =
                    valorInformado *
                    quantidade;

            }

            if (tipo === "DESCONTO") {

                totalDescontos += valorCalculado;

            } else {

                totalProventos += valorCalculado;

            }

            linhasResumo.push({

                nome: nome,

                tipo: tipo,

                valor: valorCalculado

            });

        }
    );

    const salarioBruto =
        salarioBase +
        totalProventos;

    const salarioLiquido =
        salarioBruto -
        totalDescontos;

    const moeda =
        String(
            $("#moeda").val() || "MZN"
        ).toUpperCase();

    $("#resumoSalarioBase").text(
        formatarMoeda(
            salarioBase,
            moeda
        )
    );

    $("#resumoTotalProventos").text(
        formatarMoeda(
            totalProventos,
            moeda
        )
    );

    $("#resumoTotalDescontos").text(
        formatarMoeda(
            totalDescontos,
            moeda
        )
    );

    $("#resumoSalarioBruto").text(
        formatarMoeda(
            salarioBruto,
            moeda
        )
    );

    $("#resumoSalarioLiquido").text(
        formatarMoeda(
            salarioLiquido,
            moeda
        )
    );

    renderizarResumoRubricas(
        linhasResumo,
        moeda
    );

}


/*
|--------------------------------------------------------------------------
| Renderizar detalhes do resumo
|--------------------------------------------------------------------------
*/

function renderizarResumoRubricas(
    rubricas,
    moeda
) {

    const tbody = $("#tbodyResumoRubricas");

    tbody.empty();

    if (!rubricas.length) {

        tbody.html(`
            <tr id="linhaResumoSemRubricas">

                <td
                    colspan="3"
                    class="text-center text-muted">

                    Nenhuma rubrica adicionada.

                </td>

            </tr>
        `);

        return;

    }

    rubricas.forEach(function (rubrica) {

        tbody.append(`
            <tr>

                <td>
                    ${escaparHtml(rubrica.nome)}
                </td>

                <td>
                    ${criarBadgeTipoRubrica(
            rubrica.tipo
        )}
                </td>

                <td class="text-right">
                    ${formatarMoeda(
            rubrica.valor,
            moeda
        )}
                </td>

            </tr>
        `);

    });

}

/*
|--------------------------------------------------------------------------
| Normalizar resultado de endpoint
|--------------------------------------------------------------------------
*/

function normalizarLista(resposta) {

    if (Array.isArray(resposta)) {
        return resposta;
    }

    if (
        resposta &&
        Array.isArray(resposta.dados)
    ) {
        return resposta.dados;
    }

    if (
        resposta &&
        Array.isArray(resposta.data)
    ) {
        return resposta.data;
    }

    if (
        resposta &&
        Array.isArray(resposta.registos)
    ) {
        return resposta.registos;
    }

    return [];
}

/*
|--------------------------------------------------------------------------
| Converter número
|--------------------------------------------------------------------------
*/

function converterNumero(valor) {

    if (
        valor === null ||
        valor === undefined ||
        valor === ""
    ) {
        return 0;
    }

    if (typeof valor === "number") {

        return Number.isFinite(valor)
            ? valor
            : 0;

    }

    let texto = String(valor)
        .trim()
        .replace(/\s/g, "");

    if (
        texto.includes(",") &&
        texto.includes(".")
    ) {

        texto = texto
            .replace(/\./g, "")
            .replace(",", ".");

    } else {

        texto = texto.replace(",", ".");

    }

    const numero = Number(texto);

    return Number.isFinite(numero)
        ? numero
        : 0;

}


/*
|--------------------------------------------------------------------------
| Formatar moeda
|--------------------------------------------------------------------------
*/

function formatarMoeda(
    valor,
    moeda = "MZN"
) {

    const numero = converterNumero(valor);

    try {

        return new Intl.NumberFormat(
            "pt-PT",
            {
                style: "currency",
                currency: moeda,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(numero);

    } catch (erro) {

        return (
            numero.toLocaleString(
                "pt-PT",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            ) +
            " " +
            moeda
        );

    }

}


/*
|--------------------------------------------------------------------------
| Normalizar tipo da rubrica
|--------------------------------------------------------------------------
*/

function normalizarTipoRubrica(tipo) {

    const valor = String(
        tipo || ""
    )
        .trim()
        .toUpperCase();

    if (
        valor.includes("DESCONTO") ||
        valor.includes("DEDU") ||
        valor.includes("RETEN")
    ) {
        return "DESCONTO";
    }

    return "PROVENTO";

}


/*
|--------------------------------------------------------------------------
| Badge do tipo
|--------------------------------------------------------------------------
*/

function criarBadgeTipoRubrica(tipo) {

    if (tipo === "DESCONTO") {

        return `
            <span class="badge badge-danger">
                Desconto
            </span>
        `;

    }

    return `
        <span class="badge badge-success">
            Provento
        </span>
    `;

}


/*
|--------------------------------------------------------------------------
| Data para input
|--------------------------------------------------------------------------
*/

function normalizarDataInput(data) {

    if (!data) {
        return "";
    }

    return String(data).substring(0, 10);

}

/*
|--------------------------------------------------------------------------
| Guardar
|--------------------------------------------------------------------------
*/

function guardar() {

    const formData = new FormData();

    formData.append(
        "acao",
        modoEdicao ? "atualizar" : "criar"
    );

    if (modoEdicao) {

        formData.append(
            "id",
            salarioAtual
        );

    }

    formData.append(
        "funcionario_id",
        $("#funcionario_id").val()
    );

    formData.append(
        "contrato_id",
        $("#contrato_id").val()
    );

    formData.append(
        "moeda",
        $("#moeda").val()
    );

    formData.append(
        "salario_base",
        $("#salario_base").val()
    );

    formData.append(
        "periodicidade",
        $("#periodicidade").val()
    );

    formData.append(
        "horas_mensais",
        $("#horas_mensais").val()
    );

    formData.append(
        "dias_mensais",
        $("#dias_mensais").val()
    );

    formData.append(
        "data_inicio",
        $("#data_inicio").val()
    );

    formData.append(
        "data_fim",
        $("#data_fim").val()
    );

    formData.append(
        "motivo_alteracao",
        $("#motivo_alteracao").val()
    );

    formData.append(
        "observacoes",
        $("#observacoes").val()
    );

    formData.append(
        "rubricas",
        JSON.stringify(
            obterRubricasFormulario()
        )
    );

    $("#btnGuardarSalario")
        .prop("disabled", true);

    $.ajax({

        url: AJAX_URL,

        method: "POST",

        data: formData,

        processData: false,

        contentType: false,

        dataType: "json",

        success(resposta) {

            if (!resposta.success) {

                Swal.fire(
                    "Erro",
                    resposta.message,
                    "error"
                );

                return;

            }

            Swal.fire(
                "Sucesso",
                resposta.message,
                "success"
            );

            $("#modalSalarioFuncionario")
                .modal("hide");

            tabelaSalarios.ajax.reload(
                null,
                false
            );

        },

        error(xhr) {

            Swal.fire(
                "Erro",
                xhr.responseText,
                "error"
            );

        },

        complete() {

            $("#btnGuardarSalario")
                .prop("disabled", false);

        }

    });

}

/*
|--------------------------------------------------------------------------
| Editar
|--------------------------------------------------------------------------
*/

function editarSalario(id) {

    $.post(

        AJAX_URL,

        {

            acao: "buscar",

            id: id

        },

        function (resposta) {

            if (!resposta.success) {

                Swal.fire(
                    "Erro",
                    resposta.message,
                    "error"
                );

                return;

            }

            const salario = resposta.data;

            modoEdicao = true;

            salarioAtual = salario.id;

            limparFormulario();

            $("#tituloModalSalario")
                .text("Editar Salário");

            $("#funcionario_id")
                .val(salario.funcionario_id)
                .trigger("change");

            carregarContratos(

                salario.funcionario_id,

                salario.contrato_id

            );

            $("#moeda")
                .val(salario.moeda);

            $("#salario_base")
                .val(salario.salario_base);

            $("#periodicidade")
                .val(salario.periodicidade);

            $("#horas_mensais")
                .val(salario.horas_mensais);

            $("#dias_mensais")
                .val(salario.dias_mensais);

            $("#data_inicio")
                .val(salario.data_inicio);

            $("#data_fim")
                .val(salario.data_fim);

            $("#motivo_alteracao")
                .val(salario.motivo_alteracao);

            $("#observacoes")
                .val(salario.observacoes);

            if (salario.rubricas) {

                salario.rubricas.forEach(

                    function (rubrica) {

                        adicionarLinhaRubrica(
                            rubrica
                        );

                    }

                );

            }

            calcularResumo();

            $("#modalSalarioFuncionario")
                .modal("show");

        },

        "json"

    );

}

function visualizarSalario(id) {

    editarSalario(id);

    setTimeout(function () {

        activarModoFormulario(false);

        $("#tituloModalSalario")
            .text("Visualizar Salário");

    }, 300);

}

function alterarEstadoSalario(
    id,
    acao
) {

    const texto =

        acao === "activar"

            ? "activar"

            : "inactivar";

    Swal.fire({

        title: "Confirma?",

        text:
            "Deseja " +
            texto +
            " este salário?",

        icon: "question",

        showCancelButton: true,

        confirmButtonText: "Sim",

        cancelButtonText: "Cancelar"

    }).then(function (r) {

        if (!r.isConfirmed)
            return;

        $.post(

            AJAX_URL,

            {

                acao: acao,

                id: id

            },

            function (resposta) {

                if (!resposta.success) {

                    Swal.fire(

                        "Erro",

                        resposta.message,

                        "error"

                    );

                    return;

                }

                Swal.fire(

                    "Sucesso",

                    resposta.message,

                    "success"

                );

                tabelaSalarios.ajax.reload(
                    null,
                    false
                );

            },

            "json"

        );

    });

}

/*
|--------------------------------------------------------------------------
| Limpar
|--------------------------------------------------------------------------
*/

function limparFormulario() {

    $("#formSalarioFuncionario")[0].reset();

    $("#id").val("");

    $("#contrato_id").html(
        '<option value="">Selecione...</option>'
    );

    $("#tbodyRubricas").html(

        `
        <tr id="linhaSemRubricas">

            <td colspan="8"
                class="text-center text-muted">

                Nenhuma rubrica adicionada.

            </td>

        </tr>
        `

    );

    modoEdicao = false;

    salarioAtual = null;

    calcularResumo();

}


/*
|--------------------------------------------------------------------------
| Somente leitura
|--------------------------------------------------------------------------
*/

function activarModoFormulario(
    editar = true
) {

    $("#formSalarioFuncionario")

        .find(

            "input, select, textarea"

        )

        .prop(

            "disabled",

            !editar

        );

    $("#btnAdicionarRubrica")

        .prop(

            "disabled",

            !editar

        );

    $(".removerRubrica")

        .prop(

            "disabled",

            !editar

        );

    $("#btnGuardarSalario")

        .toggle(editar);

}


/*
|--------------------------------------------------------------------------
| Escapar HTML
|--------------------------------------------------------------------------
*/

function escaparHtmlSalario(
    texto
) {
    return $("<div>")
        .text(
            String(texto ?? "")
        )
        .html();
}

function escaparHtml(valor) {

    return $("<div>")
        .text(valor ?? "")
        .html();

}