'use strict';

(() => {
    const PRODUTO_URL =
        '/erpplus/app/ajax/produto_ajax.php';

    const PRODUTO_IMAGEM_URL =
        '/erpplus/public/produtos/';

    const PRODUTO_IMAGEM_PADRAO =
        '/erpplus/public/assets/img/default-product.jpg';

    let produtos = [];
    let produtoDataTable = null;
    let produtoModuloInicializado = false;
    let produtoPreviewObjectUrl = null;
    let produtoSkuTimer =
        null;

    let produtoGerandoSku =
        false;

    let produtoImportacaoToken = '';

    let produtoImportacaoDados = null;

    let produtoImportacaoProcessando = false;

    if (
        document.readyState === 'loading'
    ) {
        document.addEventListener(
            'DOMContentLoaded',
            iniciarModuloProdutos
        );
    } else {
        iniciarModuloProdutos();
    }

    async function iniciarModuloProdutos() {
        if (produtoModuloInicializado) {
            return;
        }

        if (
            !document.getElementById(
                'produtoTabela'
            )
        ) {
            return;
        }

        produtoModuloInicializado = true;

        configurarEventosProduto();

        await Promise.all([
            carregarCategoriasProduto(),
            carregarMarcasProduto(),
            carregarUnidadesProduto(),
            carregarArmazensProduto(),
            carregarStatusProduto(),
            carregarProdutos()
        ]);
    }

    function configurarEventosProduto() {
        document
            .getElementById(
                'produtoBtnNovo'
            )
            ?.addEventListener(
                'click',
                abrirNovoProduto
            );

        document
            .getElementById(
                'produtoBtnImportar'
            )
            ?.addEventListener(
                'click',
                abrirImportacaoProduto
            );


        document
            .getElementById(
                'produtoBtnModeloExcel'
            )
            ?.addEventListener(
                'click',
                baixarModeloExcelProduto
            );


        document
            .getElementById(
                'produtoBtnValidarExcel'
            )
            ?.addEventListener(
                'click',
                validarImportacaoProduto
            );


        document
            .getElementById(
                'produtoBtnConfirmarImportacao'
            )
            ?.addEventListener(
                'click',
                confirmarImportacaoProduto
            );


        document
            .getElementById(
                'produtoBtnVoltarImportacao'
            )
            ?.addEventListener(
                'click',
                voltarImportacaoProduto
            );


        document
            .getElementById(
                'produtoFicheiroExcel'
            )
            ?.addEventListener(
                'change',
                alterarFicheiroExcelProduto
            );

        document
            .getElementById(
                'produtoForm'
            )
            ?.addEventListener(
                'submit',
                guardarProduto
            );

        document
            .getElementById(
                'produtoCodigoBarras'
            )
            ?.addEventListener(
                'input',
                function () {

                    /*
                     * O utilizador alterou manualmente.
                     * A partir daqui não substituímos
                     * pelo SKU.
                     */
                    this.dataset
                        .codigoAutomatico =
                        '0';

                    atualizarPreviewCodigoBarrasProduto();
                }
            );

        const produtoNome =
            document.getElementById(
                'produtoNome'
            );


        if (produtoNome) {

            produtoNome.addEventListener(
                'input',
                agendarGeracaoSkuProduto
            );
        }

        document
            .getElementById(
                'produtoFormEliminar'
            )
            ?.addEventListener(
                'submit',
                eliminarProduto
            );

        document
            .getElementById(
                'produtoImagem'
            )
            ?.addEventListener(
                'change',
                alterarPreviewProduto
            );

        document
            .getElementById(
                'produtoBtnRemoverImagem'
            )
            ?.addEventListener(
                'click',
                removerImagemProduto
            );

        document.addEventListener(
            'click',
            processarCliqueProduto
        );
    }

    function agendarGeracaoSkuProduto() {

        clearTimeout(
            produtoSkuTimer
        );


        produtoSkuTimer =
            setTimeout(
                gerarSkuAutomaticoProduto,
                400
            );
    }

    async function gerarSkuAutomaticoProduto() {

        const nome =
            String(
                document.getElementById(
                    'produtoNome'
                )?.value || ''
            ).trim();


        const id =
            String(
                document.getElementById(
                    'produtoId'
                )?.value || ''
            ).trim();


        const skuInput =
            document.getElementById(
                'produtoSku'
            );


        const codigoInput =
            document.getElementById(
                'produtoCodigoBarras'
            );


        const ajuda =
            document.getElementById(
                'produtoSkuAjuda'
            );


        if (!skuInput) {
            return;
        }


        if (nome === '') {

            skuInput.value = '';

            if (
                codigoInput
                && codigoInput.dataset
                    .codigoAutomatico === '1'
            ) {

                codigoInput.value = '';
            }

            return;
        }


        try {

            produtoGerandoSku =
                true;


            if (ajuda) {

                ajuda.innerHTML = `
                <i class="fa fa-spinner fa-spin"></i>
                A gerar SKU...
            `;
            }


            const resultado =
                await requisicaoProduto(
                    'gerar_sku',
                    {
                        parametros: {
                            nome:
                                nome,

                            id:
                                id
                        }
                    }
                );


            const sku =
                String(
                    resultado.dados?.sku
                    || ''
                );


            skuInput.value =
                sku;


            /*
             * Só altera automaticamente o código
             * se ele estiver vazio ou já tiver sido
             * gerado automaticamente anteriormente.
             */
            if (codigoInput) {

                if (
                    codigoInput.value.trim() === ''
                    || codigoInput.dataset
                        .codigoAutomatico === '1'
                ) {

                    codigoInput.value =
                        sku;

                    codigoInput.dataset
                        .codigoAutomatico =
                        '1';
                }
            }


            if (ajuda) {

                ajuda.innerHTML = `
                SKU gerado automaticamente:
                <strong>${escaparHtmlProduto(
                    sku
                )}</strong>
            `;
            }


            atualizarPreviewCodigoBarrasProduto();


        } catch (erro) {

            console.error(
                'Erro ao gerar SKU:',
                erro
            );


            if (ajuda) {

                ajuda.textContent =
                    'Não foi possível gerar o SKU.';
            }


        } finally {

            produtoGerandoSku =
                false;
        }
    }

    function atualizarPreviewCodigoBarrasProduto() {

        const valor =
            String(
                document.getElementById(
                    'produtoCodigoBarras'
                )?.value || ''
            ).trim();


        const container =
            document.getElementById(
                'produtoBarcodeContainer'
            );


        const svg =
            document.getElementById(
                'produtoBarcodePreview'
            );


        if (
            !container
            || !svg
        ) {
            return;
        }


        if (valor === '') {

            container.classList.add(
                'd-none'
            );

            svg.innerHTML = '';

            return;
        }


        if (
            typeof JsBarcode
            !== 'function'
        ) {

            container.classList.add(
                'd-none'
            );

            return;
        }


        try {

            /*
             * Se for um EAN-13 válido,
             * utiliza o formato original.
             *
             * Para SKU e outros códigos,
             * utiliza CODE128.
             */
            const formato =
                codigoEan13ValidoProduto(valor)
                    ? 'EAN13'
                    : 'CODE128';


            JsBarcode(
                svg,
                valor,
                {
                    format:
                        formato,

                    displayValue:
                        true,

                    height:
                        55,

                    margin:
                        5,

                    fontSize:
                        14
                }
            );


            container.classList.remove(
                'd-none'
            );


        } catch (erro) {

            console.error(
                'Erro ao gerar código de barras:',
                erro
            );

            container.classList.add(
                'd-none'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAR EAN-13
    |--------------------------------------------------------------------------
    */

    function codigoEan13ValidoProduto(
        codigo
    ) {

        codigo =
            String(codigo || '')
                .trim();


        /*
         * Um código EAN-13 deve possuir
         * exactamente 13 números.
         */
        if (!/^\d{13}$/.test(codigo)) {
            return false;
        }


        let soma = 0;


        /*
         * Calcula utilizando somente
         * os primeiros 12 números.
         */
        for (
            let indice = 0;
            indice < 12;
            indice++
        ) {

            const digito =
                Number(
                    codigo[indice]
                );


            soma +=
                indice % 2 === 0
                    ? digito
                    : digito * 3;
        }


        /*
         * Calcula o último número,
         * chamado dígito verificador.
         */
        const digitoVerificador =
            (
                10
                - (
                    soma % 10
                )
            ) % 10;


        return (
            digitoVerificador
            === Number(
                codigo[12]
            )
        );
    }
    async function requisicaoProduto(
        acao,
        opcoes = {}
    ) {
        const metodo =
            String(
                opcoes.method || 'GET'
            ).toUpperCase();

        let url =
            PRODUTO_URL;

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
                'Resposta inválida de produto_ajax.php:',
                texto
            );

            throw new Error(
                'O servidor devolveu HTML ou uma resposta inválida em vez de JSON.'
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

    async function carregarProdutos() {

        const tbody =
            document.getElementById(
                'produtoTabelaBody'
            );

        if (!tbody) {
            return;
        }

        esconderErroProduto(
            'produtoErroLista'
        );

        try {

            const resultado =
                await requisicaoProduto(
                    'listar'
                );

            produtos =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            // Destruir DataTable antes de alterar o tbody
            destruirDataTableProduto();

            // Nunca deixar colspan antes de iniciar DataTables
            tbody.innerHTML = '';

            if (produtos.length > 0) {

                tbody.innerHTML =
                    produtos
                        .map(
                            (
                                produto,
                                indice
                            ) =>
                                criarLinhaProduto(
                                    produto,
                                    indice
                                )
                        )
                        .join('');
            }

            iniciarDataTableProduto();

        } catch (erro) {

            destruirDataTableProduto();

            tbody.innerHTML = '';

            mostrarErroProduto(
                'produtoErroLista',
                erro.message
            );
        }
    }

    function criarLinhaProduto(
        produto,
        indice
    ) {
        const id =
            Number(produto.id);

        const numero =
            String(indice + 1)
                .padStart(4, '0');

        const imagem =
            produto.imagem_principal
                ? PRODUTO_IMAGEM_URL
                + encodeURIComponent(
                    produto.imagem_principal
                )
                : PRODUTO_IMAGEM_PADRAO;

        return `
            <tr>
                <td>
                    ${numero}
                </td>

                <td>
                    <img
                        src="${imagem}"
                        alt="${escaparHtmlProduto(
            produto.nome || 'Produto'
        )}"
                        class="img-thumbnail"
                        style="
                            width: 48px;
                            height: 48px;
                            object-fit: cover;
                        "
                        onerror="
                            this.onerror=null;
                            this.src='${PRODUTO_IMAGEM_PADRAO}';
                        "
                    >
                </td>

                <td>
                    <span class="font-weight-bold">
                        ${escaparHtmlProduto(
            produto.sku || '-'
        )}
                    </span>
                </td>

                <td>
                    <strong>
                        ${escaparHtmlProduto(
            produto.nome || '-'
        )}
                    </strong>

                    ${produto.codigo_barras
                ? `
                                <div class="small text-muted">
                                    ${escaparHtmlProduto(
                    produto.codigo_barras
                )}
                                </div>
                            `
                : ''
            }
                </td>

                <td>
                    ${escaparHtmlProduto(
                produto.categoria_nome || '-'
            )}
                </td>

                <td>
    ${escaparHtmlProduto(
                produto.marca_nome
                || 'Sem marca'
            )}
</td>

<td>
    ${produto.unidade_codigo
                ? `
                <strong>
                    ${escaparHtmlProduto(
                    produto.unidade_codigo
                )}
                </strong>
                <div class="small text-muted">
                    ${escaparHtmlProduto(
                    produto.unidade_nome
                    || ''
                )}
                </div>
            `
                : '-'
            }
</td>

                <td>
                    ${escaparHtmlProduto(
                produto.armazem_nome
                || 'Sem armazém padrão'
            )}
                </td>

                <td>
                    ${formatarMoedaProduto(
                produto.preco_venda
            )}
                </td>

                <td>
                    ${formatarQuantidadeProduto(
                produto.estoque_minimo,
                produto.unidade_codigo,
                produto.unidade_casas_decimais
            )}
                </td>

                <td>
                    ${criarBadgeProduto(
                produto.status_nome
            )}
                </td>

                <td>
                    ${formatarDataProduto(
                produto.criado_em
            )}
                </td>

                <td class="text-right">
                    <button
                        type="button"
                        class="btn btn-sm btn-info"
                        title="Editar"
                        data-produto-acao="editar"
                        data-produto-id="${id}"
                    >
                        <i class="fa fa-edit"></i>
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        title="Eliminar"
                        data-produto-acao="eliminar"
                        data-produto-id="${id}"
                    >
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    async function carregarCategoriasProduto() {
        const select =
            document.getElementById(
                'produtoCategoriaId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {
            const resultado =
                await requisicaoProduto(
                    'categorias'
                );

            const categorias =
                Array.isArray(resultado.dados)
                    ? resultado.dados
                    : [];

            select.replaceChildren(
                new Option(
                    categorias.length
                        ? 'Selecione a categoria'
                        : 'Nenhuma categoria disponível',
                    ''
                )
            );

            categorias.forEach(categoria => {
                const texto =
                    categoria.categoria_pai_nome
                        ? `${categoria.categoria_pai_nome} › ${categoria.nome}`
                        : categoria.nome;

                select.add(
                    new Option(
                        texto,
                        String(categoria.id)
                    )
                );
            });
        } catch (erro) {
            select.replaceChildren(
                new Option(
                    'Erro ao carregar categorias',
                    ''
                )
            );

            console.error(
                'Erro ao carregar categorias:',
                erro
            );
        } finally {
            select.disabled = false;
        }
    }

    async function carregarMarcasProduto() {

        const select =
            document.getElementById(
                'produtoMarcaId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {

            const resultado =
                await requisicaoProduto(
                    'marcas'
                );

            const marcas =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            select.replaceChildren(
                new Option(
                    'Sem marca',
                    ''
                )
            );

            marcas.forEach(
                marca => {

                    select.add(
                        new Option(
                            marca.nome,
                            String(marca.id)
                        )
                    );
                }
            );

        } catch (erro) {

            select.replaceChildren(
                new Option(
                    'Erro ao carregar marcas',
                    ''
                )
            );

            console.error(
                'Erro ao carregar marcas:',
                erro
            );

        } finally {

            select.disabled = false;
        }
    }

    async function carregarUnidadesProduto() {

        const select =
            document.getElementById(
                'produtoUnidadeId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {

            const resultado =
                await requisicaoProduto(
                    'unidades'
                );

            const unidades =
                Array.isArray(
                    resultado.dados
                )
                    ? resultado.dados
                    : [];

            select.replaceChildren(
                new Option(
                    unidades.length
                        ? 'Selecione a unidade'
                        : 'Nenhuma unidade disponível',
                    ''
                )
            );

            unidades.forEach(
                unidade => {

                    select.add(
                        new Option(
                            `${unidade.codigo} — ${unidade.nome}`,
                            String(unidade.id)
                        )
                    );
                }
            );

        } catch (erro) {

            select.replaceChildren(
                new Option(
                    'Erro ao carregar unidades',
                    ''
                )
            );

            console.error(
                'Erro ao carregar unidades:',
                erro
            );

        } finally {

            select.disabled = false;
        }
    }

    async function carregarArmazensProduto() {
        const select =
            document.getElementById(
                'produtoArmazemId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {
            const resultado =
                await requisicaoProduto(
                    'armazens'
                );

            const armazens =
                Array.isArray(resultado.dados)
                    ? resultado.dados
                    : [];

            select.replaceChildren(
                new Option(
                    'Sem armazém padrão',
                    ''
                )
            );

            armazens.forEach(armazem => {
                let texto =
                    armazem.nome;

                if (armazem.filial_nome) {
                    texto +=
                        ` — ${armazem.filial_nome}`;
                }

                if (armazem.codigo) {
                    texto +=
                        ` (${armazem.codigo})`;
                }

                select.add(
                    new Option(
                        texto,
                        String(armazem.id)
                    )
                );
            });
        } catch (erro) {
            select.replaceChildren(
                new Option(
                    'Erro ao carregar armazéns',
                    ''
                )
            );

            console.error(
                'Erro ao carregar armazéns:',
                erro
            );
        } finally {
            select.disabled = false;
        }
    }

    async function carregarStatusProduto() {
        const select =
            document.getElementById(
                'produtoStatusId'
            );

        if (!select) {
            return;
        }

        select.disabled = true;

        try {
            const resultado =
                await requisicaoProduto(
                    'status'
                );

            const estados =
                Array.isArray(resultado.dados)
                    ? resultado.dados
                    : [];

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
                        normalizarTextoProduto(
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

    function abrirNovoProduto() {
        limparFormularioProduto();

        atualizarTextoProduto(
            'produtoModalTitulo',
            'Novo Produto'
        );

        const status =
            document.getElementById(
                'produtoStatusId'
            );

        if (status?.dataset.activoId) {
            status.value =
                status.dataset.activoId;
        }

        $('#produtoModal')
            .modal('show');
    }

    async function editarProduto(id) {
        esconderErroProduto(
            'produtoErroFormulario'
        );

        try {
            const resultado =
                await requisicaoProduto(
                    'buscar',
                    {
                        parametros: {
                            id
                        }
                    }
                );

            const produto =
                resultado.dados || {};

            limparFormularioProduto();

            definirValorProduto(
                'produtoId',
                produto.id
            );

            definirValorProduto(
                'produtoCategoriaId',
                produto.categoria_id
            );

            definirValorProduto(
                'produtoMarcaId',
                produto.marca_id
            );

            definirValorProduto(
                'produtoUnidadeId',
                produto.unidade_id
            );

            definirValorProduto(
                'produtoArmazemId',
                produto.armazem_padrao_id
            );

            definirValorProduto(
                'produtoStatusId',
                produto.status_id
            );

            definirValorProduto(
                'produtoSku',
                produto.sku
            );

            definirValorProduto(
                'produtoNome',
                produto.nome
            );

            definirValorProduto(
                'produtoCodigoBarras',
                produto.codigo_barras
            );


            /*
            |--------------------------------------------------------------------------
            | Identificar se o código de barras é automático
            |--------------------------------------------------------------------------
            */

            const codigoInput =
                document.getElementById(
                    'produtoCodigoBarras'
                );


            if (codigoInput) {

                codigoInput.dataset
                    .codigoAutomatico =
                    (
                        String(
                            produto.codigo_barras
                            || ''
                        )
                        === String(
                            produto.sku
                            || ''
                        )
                    )
                        ? '1'
                        : '0';
            }


            /*
            |--------------------------------------------------------------------------
            | Atualizar desenho do código de barras
            |--------------------------------------------------------------------------
            */

            atualizarPreviewCodigoBarrasProduto();

            definirValorProduto(
                'produtoPrecoCusto',
                produto.preco_custo
            );

            definirValorProduto(
                'produtoPrecoVenda',
                produto.preco_venda
            );

            definirValorProduto(
                'produtoEstoqueMinimo',
                produto.estoque_minimo
            );

            definirValorProduto(
                'produtoDescricao',
                produto.descricao
            );

            definirValorProduto(
                'produtoImagemActual',
                produto.imagem_principal
            );

            definirValorProduto(
                'produtoRemoverImagem',
                '0'
            );

            actualizarPreviewImagemExistente(
                produto.imagem_principal
            );

            atualizarTextoProduto(
                'produtoModalTitulo',
                'Editar Produto'
            );

            $('#produtoModal')
                .modal('show');
        } catch (erro) {
            mostrarMensagemErroProduto(
                erro.message
            );
        }
    }

    async function guardarProduto(evento) {
        evento.preventDefault();

        esconderErroProduto(
            'produtoErroFormulario'
        );

        const botao =
            document.getElementById(
                'produtoBtnGuardar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoProduto(
                botao,
                'A guardar...'
            );

            const dados =
                new FormData(
                    evento.currentTarget
                );

            const resultado =
                await requisicaoProduto(
                    'salvar',
                    {
                        method: 'POST',
                        dados
                    }
                );

            retirarFocoProduto();

            $('#produtoModal')
                .modal('hide');

            mostrarMensagemSucessoProduto(
                resultado.mensagem
            );

            await carregarProdutos();
        } catch (erro) {
            mostrarErroProduto(
                'produtoErroFormulario',
                erro.message
            );
        } finally {
            desbloquearBotaoProduto(
                botao,
                htmlOriginal
            );
        }
    }

    function abrirEliminarProduto(id) {
        const produto =
            produtos.find(
                item =>
                    Number(item.id)
                    === Number(id)
            );

        definirValorProduto(
            'produtoEliminarId',
            id
        );

        atualizarTextoProduto(
            'produtoEliminarNome',
            produto?.nome || ''
        );

        esconderErroProduto(
            'produtoErroEliminar'
        );

        $('#produtoModalEliminar')
            .modal('show');
    }

    async function eliminarProduto(evento) {
        evento.preventDefault();

        esconderErroProduto(
            'produtoErroEliminar'
        );

        const botao =
            document.getElementById(
                'produtoBtnEliminar'
            );

        const htmlOriginal =
            botao?.innerHTML || '';

        try {
            bloquearBotaoProduto(
                botao,
                'A eliminar...'
            );

            const resultado =
                await requisicaoProduto(
                    'eliminar',
                    {
                        method: 'POST',
                        dados:
                            new FormData(
                                evento.currentTarget
                            )
                    }
                );

            retirarFocoProduto();

            $('#produtoModalEliminar')
                .modal('hide');

            mostrarMensagemSucessoProduto(
                resultado.mensagem
            );

            await carregarProdutos();
        } catch (erro) {
            mostrarErroProduto(
                'produtoErroEliminar',
                erro.message
            );
        } finally {
            desbloquearBotaoProduto(
                botao,
                htmlOriginal
            );
        }
    }

    function processarCliqueProduto(evento) {
        const botao =
            evento.target.closest(
                '[data-produto-acao]'
            );

        if (!botao) {
            return;
        }

        const id =
            Number(
                botao.dataset.produtoId
            );

        if (!id) {
            return;
        }

        if (
            botao.dataset.produtoAcao
            === 'editar'
        ) {
            editarProduto(id);
        }

        if (
            botao.dataset.produtoAcao
            === 'eliminar'
        ) {
            abrirEliminarProduto(id);
        }
    }

    function alterarPreviewProduto(evento) {
        const ficheiro =
            evento.target.files?.[0];

        if (!ficheiro) {
            return;
        }

        const tiposPermitidos = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (
            !tiposPermitidos.includes(
                ficheiro.type
            )
        ) {
            evento.target.value = '';

            mostrarMensagemErroProduto(
                'A imagem deve estar em formato JPG, PNG ou WEBP.'
            );

            return;
        }

        if (
            ficheiro.size
            > 5 * 1024 * 1024
        ) {
            evento.target.value = '';

            mostrarMensagemErroProduto(
                'A imagem não pode exceder 5 MB.'
            );

            return;
        }

        libertarObjectUrlProduto();

        produtoPreviewObjectUrl =
            URL.createObjectURL(
                ficheiro
            );

        definirImagemPreviewProduto(
            produtoPreviewObjectUrl
        );

        definirValorProduto(
            'produtoRemoverImagem',
            '0'
        );

        document
            .getElementById(
                'produtoBtnRemoverImagem'
            )
            ?.classList.remove(
                'd-none'
            );
    }

    function removerImagemProduto() {
        libertarObjectUrlProduto();

        const inputImagem =
            document.getElementById(
                'produtoImagem'
            );

        if (inputImagem) {
            inputImagem.value = '';
        }

        definirImagemPreviewProduto(
            PRODUTO_IMAGEM_PADRAO
        );

        definirValorProduto(
            'produtoRemoverImagem',
            '1'
        );

        document
            .getElementById(
                'produtoBtnRemoverImagem'
            )
            ?.classList.add(
                'd-none'
            );
    }

    function actualizarPreviewImagemExistente(
        imagem
    ) {
        if (imagem) {
            definirImagemPreviewProduto(
                PRODUTO_IMAGEM_URL
                + encodeURIComponent(imagem)
            );

            document
                .getElementById(
                    'produtoBtnRemoverImagem'
                )
                ?.classList.remove(
                    'd-none'
                );

            return;
        }

        definirImagemPreviewProduto(
            PRODUTO_IMAGEM_PADRAO
        );

        document
            .getElementById(
                'produtoBtnRemoverImagem'
            )
            ?.classList.add(
                'd-none'
            );
    }

    function definirImagemPreviewProduto(src) {
        const imagem =
            document.getElementById(
                'produtoPreview'
            );

        if (imagem) {
            imagem.src = src;
        }
    }

    function limparFormularioProduto() {
        libertarObjectUrlProduto();

        document
            .getElementById(
                'produtoForm'
            )
            ?.reset();

        definirValorProduto(
            'produtoId',
            ''
        );

        definirValorProduto(
            'produtoImagemActual',
            ''
        );

        definirValorProduto(
            'produtoRemoverImagem',
            '0'
        );

        definirValorProduto(
            'produtoMarcaId',
            ''
        );

        definirValorProduto(
            'produtoUnidadeId',
            ''
        );

        definirValorProduto(
            'produtoPrecoCusto',
            '0.00'
        );

        definirValorProduto(
            'produtoPrecoVenda',
            '0.00'
        );

        definirValorProduto(
            'produtoEstoqueMinimo',
            '0.000'
        );

        definirImagemPreviewProduto(
            PRODUTO_IMAGEM_PADRAO
        );

        document
            .getElementById(
                'produtoBtnRemoverImagem'
            )
            ?.classList.add(
                'd-none'
            );

        esconderErroProduto(
            'produtoErroFormulario'
        );
    }

    function libertarObjectUrlProduto() {
        if (produtoPreviewObjectUrl) {
            URL.revokeObjectURL(
                produtoPreviewObjectUrl
            );

            produtoPreviewObjectUrl = null;
        }
    }

    function abrirImportacaoProduto() {

        produtoImportacaoToken = '';

        produtoImportacaoDados = null;

        produtoImportacaoProcessando = false;


        const ficheiro =
            document.getElementById(
                'produtoFicheiroExcel'
            );

        if (ficheiro) {
            ficheiro.value = '';
        }


        const label =
            document.querySelector(
                'label[for="produtoFicheiroExcel"]'
            );

        if (label) {
            label.textContent =
                'Seleccione o ficheiro';
        }


        document
            .getElementById(
                'produtoImportacaoPasso1'
            )
            ?.classList.remove('d-none');


        document
            .getElementById(
                'produtoImportacaoPasso2'
            )
            ?.classList.add('d-none');


        document
            .getElementById(
                'produtoBtnValidarExcel'
            )
            ?.classList.remove('d-none');


        document
            .getElementById(
                'produtoBtnConfirmarImportacao'
            )
            ?.classList.add('d-none');


        document
            .getElementById(
                'produtoBtnVoltarImportacao'
            )
            ?.classList.add('d-none');


        esconderErroImportacaoProduto();

        esconderSucessoImportacaoProduto();


        $('#produtoModalImportar')
            .modal('show');
    }

    function alterarFicheiroExcelProduto(evento) {

        const ficheiro =
            evento.target.files?.[0];

        const label =
            document.querySelector(
                'label[for="produtoFicheiroExcel"]'
            );

        if (!label) {
            return;
        }

        label.textContent =
            ficheiro
                ? ficheiro.name
                : 'Seleccione o ficheiro';
    }

    function baixarModeloExcelProduto() {

        const url =
            PRODUTO_URL
            + '?acao=baixar_modelo_excel&_='
            + Date.now();

        window.location.href = url;
    }

    async function validarImportacaoProduto() {

        if (produtoImportacaoProcessando) {
            return;
        }


        esconderErroImportacaoProduto();

        esconderSucessoImportacaoProduto();


        const input =
            document.getElementById(
                'produtoFicheiroExcel'
            );


        const ficheiro =
            input?.files?.[0];


        if (!ficheiro) {

            mostrarErroImportacaoProduto(
                'Seleccione um ficheiro Excel antes de continuar.'
            );

            return;
        }


        const extensao =
            ficheiro.name
                .split('.')
                .pop()
                .toLowerCase();


        if (
            !['xlsx', 'xls']
                .includes(extensao)
        ) {

            mostrarErroImportacaoProduto(
                'O ficheiro deve estar no formato XLSX ou XLS.'
            );

            return;
        }


        if (
            ficheiro.size
            > 10 * 1024 * 1024
        ) {

            mostrarErroImportacaoProduto(
                'O ficheiro não pode exceder 10 MB.'
            );

            return;
        }


        const botao =
            document.getElementById(
                'produtoBtnValidarExcel'
            );


        const htmlOriginal =
            botao?.innerHTML || '';


        try {

            produtoImportacaoProcessando =
                true;


            bloquearBotaoProduto(
                botao,
                'A validar...'
            );


            const dados =
                new FormData();


            dados.append(
                'ficheiro',
                ficheiro
            );


            const resultado =
                await requisicaoProduto(
                    'validar_importacao',
                    {
                        method: 'POST',
                        dados
                    }
                );


            produtoImportacaoDados =
                resultado.dados || {};


            produtoImportacaoToken =
                String(
                    produtoImportacaoDados.token
                    || ''
                );


            if (
                !produtoImportacaoToken
            ) {

                throw new Error(
                    'O servidor não devolveu o token da importação.'
                );
            }


            renderizarResultadoImportacaoProduto(
                produtoImportacaoDados
            );


            document
                .getElementById(
                    'produtoImportacaoPasso1'
                )
                ?.classList.add('d-none');


            document
                .getElementById(
                    'produtoImportacaoPasso2'
                )
                ?.classList.remove('d-none');


            document
                .getElementById(
                    'produtoBtnValidarExcel'
                )
                ?.classList.add('d-none');


            document
                .getElementById(
                    'produtoBtnVoltarImportacao'
                )
                ?.classList.remove('d-none');


            const totalValidos =
                obterTotalValidosImportacao(
                    produtoImportacaoDados
                );


            if (totalValidos > 0) {

                document
                    .getElementById(
                        'produtoBtnConfirmarImportacao'
                    )
                    ?.classList.remove('d-none');

            } else {

                document
                    .getElementById(
                        'produtoBtnConfirmarImportacao'
                    )
                    ?.classList.add('d-none');
            }


        } catch (erro) {

            mostrarErroImportacaoProduto(
                erro.message
            );

        } finally {

            produtoImportacaoProcessando =
                false;


            desbloquearBotaoProduto(
                botao,
                htmlOriginal
            );
        }
    }

    function obterTotalValidosImportacao(dados) {

        if (
            Array.isArray(dados.validas)
        ) {
            return dados.validas.length;
        }

        if (
            Array.isArray(dados.validos)
        ) {
            return dados.validos.length;
        }

        if (
            Array.isArray(dados.produtos_validos)
        ) {
            return dados.produtos_validos.length;
        }

        return Number(
            dados.total_validas
            ?? dados.total_validos
            ?? 0
        );
    }

    function renderizarResultadoImportacaoProduto(dados) {

        const validos =
            Array.isArray(dados.validas)
                ? dados.validas
                : (
                    Array.isArray(dados.validos)
                        ? dados.validos
                        : (
                            Array.isArray(dados.produtos_validos)
                                ? dados.produtos_validos
                                : []
                        )
                );


        const erros =
            Array.isArray(dados.erros)
                ? dados.erros
                : [];


        const total =
            Number(
                dados.total_linhas
                ?? dados.total
                ?? (
                    validos.length
                    + erros.length
                )
            );


        const totalValidos =
            Number(
                dados.total_validas
                ?? dados.total_validos
                ?? validos.length
            );


        const totalErros =
            Number(
                dados.total_erros
                ?? erros.length
            );


        const elementoTotal =
            document.getElementById(
                'produtoImportacaoTotal'
            );


        const elementoValidos =
            document.getElementById(
                'produtoImportacaoValidos'
            );


        const elementoErros =
            document.getElementById(
                'produtoImportacaoErros'
            );


        if (elementoTotal) {
            elementoTotal.textContent =
                total;
        }


        if (elementoValidos) {
            elementoValidos.textContent =
                totalValidos;
        }


        if (elementoErros) {
            elementoErros.textContent =
                totalErros;
        }


        renderizarProdutosValidosImportacao(
            validos
        );


        renderizarErrosImportacaoProduto(
            erros
        );


        const containerValidos =
            document.getElementById(
                'produtoImportacaoValidosContainer'
            );


        if (containerValidos) {

            containerValidos.classList.toggle(
                'd-none',
                validos.length === 0
            );
        }


        const containerErros =
            document.getElementById(
                'produtoImportacaoErrosContainer'
            );


        if (containerErros) {

            containerErros.classList.toggle(
                'd-none',
                erros.length === 0
            );
        }
    }

    function renderizarProdutosValidosImportacao(validas) {
        const tbody = document.getElementById('produtoImportacaoValidos');

        if (!tbody) {
            console.error(
                'Elemento #produtoImportacaoValidos não encontrado.'
            );
            return;
        }

        tbody.innerHTML = '';

        if (!Array.isArray(validas) || validas.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    Nenhum produto válido encontrado.
                </td>
            </tr>
        `;
            return;
        }

        validas.forEach(item => {

            const dados = item.dados || {};
            const preview = item.preview || {};

            const tr = document.createElement('tr');

            tr.innerHTML = `
            <td>
                ${escaparHtmlProduto(item.linha ?? '')}
            </td>

            <td>
                ${escaparHtmlProduto(
                preview.sku ?? dados.sku ?? ''
            )}
            </td>

            <td>
                ${escaparHtmlProduto(
                preview.nome ?? dados.nome ?? ''
            )}
            </td>

            <td>
                ${escaparHtmlProduto(
                preview.categoria ?? ''
            )}
            </td>

            <td>
                ${escaparHtmlProduto(
                preview.marca ?? 'Sem marca'
            )}
            </td>

            <td>
                ${escaparHtmlProduto(
                preview.unidade ?? ''
            )}
            </td>

            <td class="text-right">
                ${formatarMoedaProduto(
                preview.preco_venda ??
                dados.preco_venda ??
                0
            )}
            </td>
        `;

            tbody.appendChild(tr);
        });
    }

    function renderizarErrosImportacaoProduto(
        erros
    ) {

        const tbody =
            document.getElementById(
                'produtoImportacaoErros'
            );


        if (!tbody) {
            console.warn(
                'Elemento #produtoImportacaoErros não encontrado.'
            );

            return;
        }


        if (
            !Array.isArray(erros)
            || erros.length === 0
        ) {

            tbody.innerHTML = `
            <tr>
                <td
                    colspan="4"
                    class="text-center text-muted"
                >
                    Nenhum erro encontrado.
                </td>
            </tr>
        `;

            return;
        }


        tbody.innerHTML =
            erros
                .map(item => {

                    const dados =
                        item.dados
                            && typeof item.dados === 'object'
                            ? item.dados
                            : {};


                    const linha =
                        item.linha
                        ?? item.row
                        ?? '-';


                    const sku =
                        dados.sku
                        ?? item.sku
                        ?? '';


                    const nome =
                        dados.nome
                        ?? item.nome
                        ?? '';


                    let mensagens =
                        item.erro
                        ?? item.erros
                        ?? item.mensagens
                        ?? item.mensagem
                        ?? 'Erro de validação.';


                    if (
                        !Array.isArray(
                            mensagens
                        )
                    ) {
                        mensagens = [
                            mensagens
                        ];
                    }


                    const listaErros =
                        mensagens
                            .map(
                                mensagem =>
                                    `<div>${escaparHtmlProduto(
                                        mensagem
                                    )}</div>`
                            )
                            .join('');


                    return `
                    <tr>

                        <td>
                            ${escaparHtmlProduto(
                        linha
                    )}
                        </td>

                        <td>
                            ${escaparHtmlProduto(
                        sku
                    )}
                        </td>

                        <td>
                            ${escaparHtmlProduto(
                        nome
                    )}
                        </td>

                        <td class="text-danger">
                            ${listaErros}
                        </td>

                    </tr>
                `;
                })
                .join('');
    }

    function formatarValorImportacaoProduto(
        valor
    ) {

        const numero =
            Number(
                String(
                    valor ?? '0'
                )
                    .replace(/\s/g, '')
                    .replace(',', '.')
            );


        if (
            !Number.isFinite(numero)
        ) {
            return '0,00';
        }


        return numero.toLocaleString(
            'pt-MZ',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );
    }

    async function confirmarImportacaoProduto() {

        if (
            produtoImportacaoProcessando
        ) {
            return;
        }


        if (
            !produtoImportacaoToken
        ) {

            mostrarErroImportacaoProduto(
                'A validação da importação expirou. Seleccione novamente o ficheiro.'
            );

            return;
        }


        const totalValidos =
            obterTotalValidosImportacao(
                produtoImportacaoDados || {}
            );


        if (
            totalValidos <= 0
        ) {

            mostrarErroImportacaoProduto(
                'Não existem produtos válidos para importar.'
            );

            return;
        }


        if (
            typeof Swal !== 'undefined'
        ) {

            const confirmacao =
                await Swal.fire({
                    icon: 'question',
                    title: 'Confirmar importação?',
                    html:
                        `Serão registados <strong>${totalValidos}</strong> produtos.<br><br>`
                        + 'Os produtos existentes não serão alterados.',
                    showCancelButton: true,
                    confirmButtonText:
                        'Sim, importar',
                    cancelButtonText:
                        'Cancelar'
                });


            if (
                !confirmacao.isConfirmed
            ) {
                return;
            }
        }


        const botao =
            document.getElementById(
                'produtoBtnConfirmarImportacao'
            );


        const htmlOriginal =
            botao?.innerHTML || '';


        try {

            produtoImportacaoProcessando =
                true;


            bloquearBotaoProduto(
                botao,
                'A importar...'
            );


            const dados =
                new FormData();


            dados.append(
                'token',
                produtoImportacaoToken
            );


            const resultado =
                await requisicaoProduto(
                    'confirmar_importacao',
                    {
                        method: 'POST',
                        dados
                    }
                );


            mostrarSucessoImportacaoProduto(
                resultado.mensagem
                || 'Importação concluída com sucesso.'
            );


            produtoImportacaoToken =
                '';


            produtoImportacaoDados =
                null;


            document
                .getElementById(
                    'produtoBtnConfirmarImportacao'
                )
                ?.classList.add('d-none');


            await carregarProdutos();


            setTimeout(
                () => {

                    $('#produtoModalImportar')
                        .modal('hide');

                },
                1200
            );


        } catch (erro) {

            mostrarErroImportacaoProduto(
                erro.message
            );

        } finally {

            produtoImportacaoProcessando =
                false;


            desbloquearBotaoProduto(
                botao,
                htmlOriginal
            );
        }
    }

    function voltarImportacaoProduto() {

        produtoImportacaoToken = '';

        produtoImportacaoDados = null;


        document
            .getElementById(
                'produtoImportacaoPasso2'
            )
            ?.classList.add('d-none');


        document
            .getElementById(
                'produtoImportacaoPasso1'
            )
            ?.classList.remove('d-none');


        document
            .getElementById(
                'produtoBtnConfirmarImportacao'
            )
            ?.classList.add('d-none');


        document
            .getElementById(
                'produtoBtnVoltarImportacao'
            )
            ?.classList.add('d-none');


        document
            .getElementById(
                'produtoBtnValidarExcel'
            )
            ?.classList.remove('d-none');


        const input =
            document.getElementById(
                'produtoFicheiroExcel'
            );


        if (input) {
            input.value = '';
        }


        const label =
            document.querySelector(
                'label[for="produtoFicheiroExcel"]'
            );


        if (label) {
            label.textContent =
                'Seleccione o ficheiro';
        }


        esconderErroImportacaoProduto();
    }

    function mostrarErroImportacaoProduto(
        mensagem
    ) {

        const elemento =
            document.getElementById(
                'produtoImportacaoErro'
            );


        if (!elemento) {
            return;
        }


        elemento.textContent =
            mensagem;


        elemento.classList.remove(
            'd-none'
        );
    }


    function esconderErroImportacaoProduto() {

        document
            .getElementById(
                'produtoImportacaoErro'
            )
            ?.classList.add(
                'd-none'
            );
    }


    function mostrarSucessoImportacaoProduto(
        mensagem
    ) {

        const elemento =
            document.getElementById(
                'produtoImportacaoSucesso'
            );


        if (!elemento) {
            return;
        }


        elemento.textContent =
            mensagem;


        elemento.classList.remove(
            'd-none'
        );
    }


    function esconderSucessoImportacaoProduto() {

        document
            .getElementById(
                'produtoImportacaoSucesso'
            )
            ?.classList.add(
                'd-none'
            );
    }

    function destruirDataTableProduto() {
        if (
            typeof $ !== 'undefined'
            && $.fn.DataTable
            && $.fn.DataTable.isDataTable(
                '#produtoTabela'
            )
        ) {
            $('#produtoTabela')
                .DataTable()
                .destroy();
        }

        produtoDataTable = null;
    }

    function iniciarDataTableProduto() {

        if (
            typeof $ === 'undefined'
            || !$.fn.DataTable
        ) {
            return;
        }

        // Proteção contra inicialização duplicada
        if (
            $.fn.DataTable.isDataTable(
                '#produtoTabela'
            )
        ) {

            $('#produtoTabela')
                .DataTable()
                .destroy();
        }

        produtoDataTable =
            $('#produtoTabela')
                .DataTable({

                    pageLength: 10,

                    lengthMenu: [
                        [5, 10, 25, 50, 100],
                        [5, 10, 25, 50, 100]
                    ],

                    responsive: true,

                    autoWidth: false,

                    order: [
                        [3, 'asc']
                    ],

                    columnDefs: [
                        {
                            targets: [1, 12],
                            orderable: false,
                            searchable: false
                        }
                    ],

                    language: {

                        emptyTable:
                            'Nenhum produto encontrado.',

                        zeroRecords:
                            'Nenhum produto encontrado.',

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
                                'Lista de Produtos',

                            exportOptions: {
                                columns: [
                                    0,
                                    2,
                                    3,
                                    4,
                                    5,
                                    6,
                                    7,
                                    8,
                                    9,
                                    10,
                                    11
                                ]
                            }
                        },

                        {
                            extend:
                                'pdfHtml5',

                            text:
                                '<i class="fa fa-file-pdf"></i> PDF',

                            title:
                                'Lista de Produtos',

                            orientation:
                                'landscape',

                            pageSize:
                                'A4',

                            exportOptions: {
                                columns: [
                                    0,
                                    2,
                                    3,
                                    4,
                                    5,
                                    6,
                                    7,
                                    8,
                                    9,
                                    10,
                                    11
                                ]
                            }
                        },

                        {
                            extend:
                                'print',

                            text:
                                '<i class="fa fa-print"></i> Imprimir',

                            title:
                                'Lista de Produtos',

                            exportOptions: {
                                columns: [
                                    0,
                                    2,
                                    3,
                                    4,
                                    5,
                                    6,
                                    7,
                                    8,
                                    9,
                                    10,
                                    11
                                ]
                            }
                        }
                    ]
                });
    }

    function definirValorProduto(
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

    function atualizarTextoProduto(
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

    function mostrarErroProduto(
        id,
        mensagem
    ) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            mostrarMensagemErroProduto(
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

    function esconderErroProduto(id) {
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

    function bloquearBotaoProduto(
        botao,
        texto
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = true;

        botao.innerHTML = `
            <i class="fa fa-spinner fa-spin"></i>
            ${escaparHtmlProduto(texto)}
        `;
    }

    function desbloquearBotaoProduto(
        botao,
        html
    ) {
        if (!botao) {
            return;
        }

        botao.disabled = false;
        botao.innerHTML = html;
    }

    function retirarFocoProduto() {
        if (
            document.activeElement
            instanceof HTMLElement
        ) {
            document.activeElement.blur();
        }
    }

    function escaparHtmlProduto(valor) {
        const elemento =
            document.createElement(
                'div'
            );

        elemento.textContent =
            valor ?? '';

        return elemento.innerHTML;
    }

    function normalizarTextoProduto(valor) {
        return String(valor || '')
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(
                /[\u0300-\u036f]/g,
                ''
            );
    }

    function formatarMoedaProduto(valor) {
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

    function formatarQuantidadeProduto(
        valor,
        unidade,
        casasDecimais = 3
    ) {

        const numero =
            Number(valor || 0);

        const casas =
            Math.min(
                Math.max(
                    Number(casasDecimais || 0),
                    0
                ),
                6
            );

        return (
            numero.toLocaleString(
                'pt-MZ',
                {
                    minimumFractionDigits: 0,
                    maximumFractionDigits:
                        casas
                }
            )
            + ' '
            + escaparHtmlProduto(
                unidade || ''
            )
        ).trim();
    }

    function formatarDataProduto(valor) {
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
            return escaparHtmlProduto(
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

    function criarBadgeProduto(status) {
        const nome =
            String(status || '-');

        const normalizado =
            normalizarTextoProduto(nome);

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
            normalizado === 'esgotado'
        ) {
            classe =
                'badge-warning';
        } else if (
            normalizado === 'descontinuado'
        ) {
            classe =
                'badge-danger';
        }

        return `
            <span class="badge ${classe}">
                ${escaparHtmlProduto(nome)}
            </span>
        `;
    }

    function mostrarMensagemSucessoProduto(
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

    function mostrarMensagemErroProduto(
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