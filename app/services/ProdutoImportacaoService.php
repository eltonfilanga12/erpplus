<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ProdutoImportacaoService
{
    private PDO $db;
    private Produto $model;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->model = new Produto($db);
    }

    public function validarArquivo(
        array $arquivo,
        int $empresaId,
        int $usuarioId
    ): array {
        $this->validarUpload($arquivo);

        $spreadsheet = IOFactory::load($arquivo['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $linhas = $sheet->toArray(null, true, true, false);

        if (!$linhas) {
            throw new RuntimeException('O ficheiro Excel está vazio.');
        }

        $cabecalho = array_map(
            fn($valor) => $this->normalizarCabecalho((string)$valor),
            $linhas[0]
        );

        $colunas = $this->mapearColunas($cabecalho);
        $this->validarColunasObrigatorias($colunas);

        $referencias = $this->carregarReferencias($empresaId);
        $skusReservados = $this->carregarSkus($empresaId);
        $barrasReservados = $this->carregarCodigosBarras($empresaId);

        $validas = [];
        $erros = [];
        $total = 0;

        for ($indice = 1; $indice < count($linhas); $indice++) {
            $numeroLinha = $indice + 1;
            $linha = $linhas[$indice];

            if ($this->linhaVazia($linha)) {
                continue;
            }
            $total++;

            try {
                $produto = $this->converterLinha(
                    $linha,
                    $colunas,
                    $referencias,
                    $skusReservados,
                    $barrasReservados,
                    $empresaId
                );

                $validas[] = [
                    'linha' => $numeroLinha,
                    'dados' => $produto['dados'],
                    'preview' => $produto['preview'],
                ];
            } catch (Throwable $e) {
                $erros[] = [
                    'linha' => $numeroLinha,
                    'erro' => $e->getMessage(),
                    'dados' => $this->dadosLinhaParaPreview($linha, $colunas),
                ];
            }
        }

        $token = bin2hex(random_bytes(24));

        $pacote = [
            'versao' => 1,
            'token' => $token,
            'empresa_id' => $empresaId,
            'usuario_id' => $usuarioId,
            'criado_em' => time(),
            'arquivo' => basename((string)$arquivo['name']),
            'total_linhas' => $total,
            'validas' => $validas,
            'erros' => $erros,
        ];

        $this->guardarPacote($token, $pacote);

        return [
            'token' => $token,
            'arquivo' => $pacote['arquivo'],
            'total_linhas' => $total,
            'total_validas' => count($validas),
            'total_erros' => count($erros),
            'validas' => $validas,
            'erros' => $erros,
        ];
    }

    public function gerarModeloExcel(
        int $empresaId
    ): Spreadsheet {

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa inválida.'
            );
        }


        /*
     * =========================================================
     * BUSCAR LISTAS DA EMPRESA
     * =========================================================
     */

        $categorias =
            $this->buscarCategoriasEmpresa(
                $empresaId
            );


        $marcas =
            $this->buscarMarcasEmpresa(
                $empresaId
            );


        $unidades =
            $this->buscarUnidadesEmpresa(
                $empresaId
            );


        $armazens =
            $this->buscarArmazensEmpresa(
                $empresaId
            );


        $estados =
            $this->buscarStatusProduto();


        /*
     * =========================================================
     * CRIAR DOCUMENTO
     * =========================================================
     */

        $spreadsheet =
            new Spreadsheet();


        /*
     * =========================================================
     * FOLHA 1 — PRODUTOS
     * =========================================================
     */

        $produtos =
            $spreadsheet->getActiveSheet();


        $produtos->setTitle(
            'Produtos'
        );


        $cabecalhos = [
            'SKU',
            'Nome',
            'Categoria',
            'Marca',
            'Unidade',
            'Armazém',
            'Código Barras',
            'Preço Custo',
            'Preço Venda',
            'Stock Mínimo',
            'Estado',
            'Descrição'
        ];


        foreach (
            $cabecalhos as $indice => $cabecalho
        ) {

            $coluna =
                $this->numeroParaColunaExcel(
                    $indice + 1
                );


            $produtos
                ->setCellValue(
                    $coluna . '1',
                    $cabecalho
                );
        }


        /*
     * =========================================================
     * ESTILO DOS CABEÇALHOS
     * =========================================================
     */

        $ultimaColuna =
            $this->numeroParaColunaExcel(
                count($cabecalhos)
            );


        $produtos
            ->getStyle(
                'A1:' . $ultimaColuna . '1'
            )
            ->getFont()
            ->setBold(true);


        $produtos
            ->getStyle(
                'A1:' . $ultimaColuna . '1'
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );


        $produtos
            ->getStyle(
                'A1:' . $ultimaColuna . '1'
            )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB(
                '1F4E78'
            );


        $produtos
            ->getStyle(
                'A1:' . $ultimaColuna . '1'
            )
            ->getFont()
            ->getColor()
            ->setARGB(
                'FFFFFF'
            );


        $produtos
            ->getStyle(
                'A1:' . $ultimaColuna . '1'
            )
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            );


        $produtos->getRowDimension(1)
            ->setRowHeight(25);


        /*
     * =========================================================
     * LINHA DE EXEMPLO
     * =========================================================
     *
     * Não vamos preencher automaticamente Categoria etc.,
     * porque esses valores devem ser escolhidos pelo utilizador.
     */

        $produtos->setCellValue(
            'A2',
            ''
        );

        $produtos->setCellValue(
            'B2',
            'Exemplo de produto'
        );

        $produtos->setCellValue(
            'C2',
            ''
        );

        $produtos->setCellValue(
            'D2',
            ''
        );

        $produtos->setCellValue(
            'E2',
            ''
        );

        $produtos->setCellValue(
            'F2',
            ''
        );

        $produtos->setCellValue(
            'G2',
            ''
        );

        $produtos->setCellValue(
            'H2',
            100
        );

        $produtos->setCellValue(
            'I2',
            150
        );

        $produtos->setCellValue(
            'J2',
            10
        );

        $produtos->setCellValue(
            'K2',
            ''
        );

        $produtos->setCellValue(
            'L2',
            'Descrição do produto'
        );


        /*
     * =========================================================
     * FORMATAÇÃO DAS COLUNAS NUMÉRICAS
     * =========================================================
     */

        $produtos
            ->getStyle('H2:I1000')
            ->getNumberFormat()
            ->setFormatCode(
                '#,##0.00'
            );


        $produtos
            ->getStyle('J2:J1000')
            ->getNumberFormat()
            ->setFormatCode(
                '#,##0.000'
            );


        /*
     * =========================================================
     * LARGURA DAS COLUNAS
     * =========================================================
     */

        $larguras = [
            'A' => 18,
            'B' => 35,
            'C' => 28,
            'D' => 25,
            'E' => 22,
            'F' => 28,
            'G' => 22,
            'H' => 18,
            'I' => 18,
            'J' => 18,
            'K' => 18,
            'L' => 45
        ];


        foreach (
            $larguras as $coluna => $largura
        ) {

            $produtos
                ->getColumnDimension(
                    $coluna
                )
                ->setWidth(
                    $largura
                );
        }


        /*
     * =========================================================
     * CONGELAR CABEÇALHO
     * =========================================================
     */

        $produtos->freezePane('A2');


        /*
     * =========================================================
     * FOLHA 2 — INSTRUÇÕES
     * =========================================================
     */

        $instrucoes =
            $spreadsheet->createSheet();


        $instrucoes->setTitle(
            'Instruções'
        );


        $textoInstrucoes = [

            [
                'IMPORTAÇÃO DE PRODUTOS — ERP PLUS'
            ],

            [
                ''
            ],

            [
                'Como utilizar o modelo'
            ],

            [
                '1.',
                'Preencha os produtos na folha "Produtos".'
            ],

            [
                '2.',
                'Não altere os nomes das colunas.'
            ],

            [
                '3.',
                'O SKU pode ficar vazio. Nesse caso o ERP irá gerar automaticamente.'
            ],

            [
                '4.',
                'Categoria deve corresponder exactamente a uma categoria existente na empresa.'
            ],

            [
                '5.',
                'Marca deve corresponder exactamente a uma marca existente na empresa.'
            ],

            [
                '6.',
                'Unidade deve corresponder exactamente a uma unidade existente na empresa.'
            ],

            [
                '7.',
                'Armazém deve corresponder exactamente a um armazém existente na empresa.'
            ],

            [
                '8.',
                'Estado deve corresponder exactamente a um estado de produto existente.'
            ],

            [
                '9.',
                'Preços podem ser informados como 1234.56 ou 1.234,56.'
            ],

            [
                '10.',
                'Não coloque empresa_id no Excel.'
            ],

            [
                '11.',
                'Produtos que já existam não serão actualizados.'
            ],

            [
                '12.',
                'Linhas com erros serão apresentadas na validação e não serão importadas.'
            ],

            [
                '13.',
                'Imagens não fazem parte desta primeira versão da importação.'
            ],

            [
                '14.',
                'Depois de preencher o ficheiro, volte ao ERP e clique em "Importar Excel".'
            ]

        ];


        foreach (
            $textoInstrucoes as $linha => $dados
        ) {

            foreach (
                $dados as $coluna => $valor
            ) {

                $instrucoes->setCellValue(
                    $this->numeroParaColunaExcel(
                        $coluna + 1
                    ) . ($linha + 1),
                    $valor
                );
            }
        }


        $instrucoes
            ->getStyle('A1:B1')
            ->getFont()
            ->setBold(true)
            ->setSize(16);


        $instrucoes
            ->getColumnDimension('A')
            ->setWidth(8);


        $instrucoes
            ->getColumnDimension('B')
            ->setWidth(100);


        $instrucoes
            ->getStyle('B1:B30')
            ->getAlignment()
            ->setWrapText(true);


        /*
     * =========================================================
     * FOLHA 3 — LISTAS
     * =========================================================
     */

        $listas =
            $spreadsheet->createSheet();


        $listas->setTitle(
            'Listas'
        );


        $colunasListas = [
            'Categorias',
            'Marcas',
            'Unidades',
            'Armazéns',
            'Estados'
        ];


        foreach (
            $colunasListas as $indice => $titulo
        ) {

            $coluna =
                $this->numeroParaColunaExcel(
                    $indice + 1
                );


            $listas->setCellValue(
                $coluna . '1',
                $titulo
            );


            $listas
                ->getStyle(
                    $coluna . '1'
                )
                ->getFont()
                ->setBold(true);
        }


        /*
     * Categorias
     */

        $linha = 2;

        foreach (
            $categorias as $categoria
        ) {

            $nome =
                trim(
                    (string)(
                        $categoria['nome']
                        ?? ''
                    )
                );


            if (
                $nome === ''
            ) {
                continue;
            }


            $listas->setCellValue(
                'A' . $linha,
                $nome
            );


            $linha++;
        }


        /*
     * Marcas
     */

        $linha = 2;

        foreach (
            $marcas as $marca
        ) {

            $nome =
                trim(
                    (string)(
                        $marca['nome']
                        ?? ''
                    )
                );


            if (
                $nome === ''
            ) {
                continue;
            }


            $listas->setCellValue(
                'B' . $linha,
                $nome
            );


            $linha++;
        }


        /*
     * Unidades
     */

        $linha = 2;

        foreach (
            $unidades as $unidade
        ) {

            $nome =
                trim(
                    (string)(
                        $unidade['nome']
                        ?? ''
                    )
                );


            $codigo =
                trim(
                    (string)(
                        $unidade['codigo']
                        ?? ''
                    )
                );


            if (
                $nome === ''
            ) {
                continue;
            }


            $texto =
                $codigo !== ''
                ? $codigo . ' — ' . $nome
                : $nome;


            $listas->setCellValue(
                'C' . $linha,
                $texto
            );


            $linha++;
        }


        /*
     * Armazéns
     */

        $linha = 2;

        foreach (
            $armazens as $armazem
        ) {

            $nome =
                trim(
                    (string)(
                        $armazem['nome']
                        ?? ''
                    )
                );


            $codigo =
                trim(
                    (string)(
                        $armazem['codigo']
                        ?? ''
                    )
                );


            if (
                $nome === ''
            ) {
                continue;
            }


            $texto =
                $codigo !== ''
                ? $codigo . ' — ' . $nome
                : $nome;


            $listas->setCellValue(
                'D' . $linha,
                $texto
            );


            $linha++;
        }


        /*
     * Estados
     */

        $linha = 2;

        foreach (
            $estados as $estado
        ) {

            $nome =
                trim(
                    (string)(
                        $estado['nome']
                        ?? ''
                    )
                );


            if (
                $nome === ''
            ) {
                continue;
            }


            $listas->setCellValue(
                'E' . $linha,
                $nome
            );


            $linha++;
        }


        foreach (
            ['A', 'B', 'C', 'D', 'E'] as $coluna
        ) {

            $listas
                ->getColumnDimension($coluna)
                ->setWidth(32);
        }


        $listas
            ->getStyle('A1:E1')
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB(
                '1F4E78'
            );


        $listas
            ->getStyle('A1:E1')
            ->getFont()
            ->getColor()
            ->setARGB(
                'FFFFFF'
            );


        /*
     * =========================================================
     * VOLTAR PARA A FOLHA PRODUTOS
     * =========================================================
     */

        $spreadsheet->setActiveSheetIndex(0);


        return $spreadsheet;
    }

    private function buscarCategoriasEmpresa(
        int $empresaId
    ): array {

        $sql = "
        SELECT
            c.id,
            c.nome
        FROM categorias c
        INNER JOIN status s
            ON s.id = c.status_id
           AND s.tipo = 'categoria'
        WHERE c.empresa_id = :empresa_id
          AND s.nome IN (
              'Activa',
              'Ativa'
          )
        ORDER BY
            c.nome ASC
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buscarUnidadesEmpresa(
        int $empresaId
    ): array {

        $sql = "
        SELECT
            u.id,
            u.codigo,
            u.nome,
            u.casas_decimais
        FROM unidades u
        INNER JOIN status s
            ON s.id = u.status_id
           AND s.tipo = 'unidade'
        WHERE u.empresa_id = :empresa_id
          AND s.nome IN (
              'Activo',
              'Ativo',
              'Activa',
              'Ativa'
          )
        ORDER BY
            u.nome ASC
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buscarMarcasEmpresa(
        int $empresaId
    ): array {

        $sql = "
        SELECT
            m.id,
            m.nome
        FROM marcas m
        INNER JOIN status s
            ON s.id = m.status_id
           AND s.tipo = 'marca'
        WHERE m.empresa_id = :empresa_id
          AND s.nome IN (
              'Activo',
              'Ativo',
              'Activa',
              'Ativa'
          )
        ORDER BY
            m.nome ASC
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buscarArmazensEmpresa(
        int $empresaId
    ): array {

        $sql = "
        SELECT
            a.id,
            a.nome,
            a.codigo,
            f.nome AS filial_nome
        FROM armazens a
        LEFT JOIN filiais f
            ON f.id = a.filial_id
           AND f.empresa_id = a.empresa_id
        INNER JOIN status s
            ON s.id = a.status_id
           AND s.tipo = 'armazem'
        WHERE a.empresa_id = :empresa_id
          AND s.nome IN (
              'Activo',
              'Ativo'
          )
        ORDER BY
            a.nome ASC
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buscarStatusProduto(): array
    {

        $sql = "
        SELECT
            id,
            nome,
            descricao

        FROM status

        WHERE tipo = 'produto'

        ORDER BY
            CASE
                WHEN nome IN (
                    'Activo',
                    'Ativo'
                )
                THEN 1

                WHEN nome IN (
                    'Inactivo',
                    'Inativo'
                )
                THEN 2

                WHEN nome = 'Esgotado'
                THEN 3

                WHEN nome = 'Descontinuado'
                THEN 4

                ELSE 5
            END,

            nome ASC
        ";


        return $this->db
            ->query($sql)
            ->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    private function numeroParaColunaExcel(
        int $numero
    ): string {

        $coluna = '';


        while (
            $numero > 0
        ) {

            $resto =
                ($numero - 1) % 26;


            $coluna =
                chr(
                    65 + $resto
                )
                . $coluna;


            $numero =
                intdiv(
                    $numero - 1,
                    26
                );
        }


        return $coluna;
    }

    public function confirmar(
        string $token,
        int $empresaId,
        int $usuarioId
    ): array {
        $pacote = $this->lerPacote($token);

        if ((int)$pacote['empresa_id'] !== $empresaId) {
            throw new RuntimeException('O ficheiro de importação não pertence à empresa actual.');
        }

        if ((int)$pacote['usuario_id'] !== $usuarioId) {
            throw new RuntimeException('O ficheiro de importação foi preparado por outro utilizador.');
        }

        if ((time() - (int)$pacote['criado_em']) > 3600) {
            $this->apagarPacote($token);
            throw new RuntimeException('A validação do ficheiro expirou. Carregue o Excel novamente.');
        }

        $validas = $pacote['validas'] ?? [];

        if (!$validas) {
            throw new RuntimeException('Não existem produtos válidos para importar.');
        }

        $criados = [];
        $falhas = [];

        $this->db->beginTransaction();

        try {
            foreach ($validas as $item) {
                $linha = (int)($item['linha'] ?? 0);
                $dados = $item['dados'] ?? [];

                try {
                    $this->verificarUnicidadeAntesDeCriar(
                        $empresaId,
                        (string)$dados['sku'],
                        (string)$dados['codigo_barras']
                    );

                    $id = $this->model->criar($empresaId, $dados);

                    if ($id <= 0) {
                        throw new RuntimeException('Não foi possível criar o produto.');
                    }

                    $criados[] = [
                        'linha' => $linha,
                        'id' => $id,
                        'sku' => $dados['sku'],
                        'nome' => $dados['nome'],
                    ];
                } catch (Throwable $e) {
                    $falhas[] = [
                        'linha' => $linha,
                        'sku' => $dados['sku'] ?? '',
                        'nome' => $dados['nome'] ?? '',
                        'erro' => $e->getMessage(),
                    ];

                    throw new RuntimeException(
                        'Falha na linha ' . $linha . ': ' . $e->getMessage()
                    );
                }
            }

            $this->db->commit();
            $this->apagarPacote($token);

            return [
                'arquivo' => $pacote['arquivo'] ?? '',
                'total_importados' => count($criados),
                'total_erros' => 0,
                'produtos' => $criados,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    private function converterLinha(
        array $linha,
        array $colunas,
        array $referencias,
        array &$skusReservados,
        array &$barrasReservados,
        int $empresaId
    ): array {
        $nome = $this->texto($this->valor($linha, $colunas, 'nome'));

        if ($nome === '') {
            throw new RuntimeException('Nome do produto é obrigatório.');
        }

        if (mb_strlen($nome) > 150) {
            throw new RuntimeException('Nome do produto ultrapassa 150 caracteres.');
        }

        $categoriaNome = $this->texto($this->valor($linha, $colunas, 'categoria'));
        if ($categoriaNome === '') {
            throw new RuntimeException('Categoria é obrigatória.');
        }

        $categoriaId = $this->resolverReferencia(
            $referencias['categorias'],
            $categoriaNome,
            'categoria'
        );

        $unidadeNome = $this->texto($this->valor($linha, $colunas, 'unidade'));
        if ($unidadeNome === '') {
            throw new RuntimeException('Unidade é obrigatória.');
        }

        $unidadeId = $this->resolverReferencia(
            $referencias['unidades'],
            $unidadeNome,
            'unidade'
        );

        $marcaNome = $this->texto($this->valor($linha, $colunas, 'marca'));
        $marcaId = null;
        if ($marcaNome !== '') {
            $marcaId = $this->resolverReferencia(
                $referencias['marcas'],
                $marcaNome,
                'marca'
            );
        }

        $armazemNome = $this->texto($this->valor($linha, $colunas, 'armazem'));
        $armazemId = null;
        if ($armazemNome !== '') {
            $armazemId = $this->resolverReferencia(
                $referencias['armazens'],
                $armazemNome,
                'armazém'
            );
        }

        $statusNome = $this->texto($this->valor($linha, $colunas, 'estado'));
        if ($statusNome === '') {
            $statusNome = 'Ativo';
        }

        $statusId = $this->resolverReferencia(
            $referencias['status'],
            $statusNome,
            'estado'
        );

        $sku = strtoupper($this->texto($this->valor($linha, $colunas, 'sku')));
        if ($sku === '') {
            $sku = $this->gerarSkuImportacao(
                $empresaId,
                $nome,
                $skusReservados
            );
        }

        if (mb_strlen($sku) > 50) {
            throw new RuntimeException('SKU ultrapassa 50 caracteres.');
        }

        $skuNormalizado = $this->normalizarChave($sku);
        if (isset($skusReservados[$skuNormalizado])) {
            throw new RuntimeException('SKU já existe: ' . $sku);
        }
        $skusReservados[$skuNormalizado] = true;

        $codigoBarras = $this->texto($this->valor($linha, $colunas, 'codigo_barras'));
        if ($codigoBarras === '') {
            $codigoBarras = $sku;
        }

        if (mb_strlen($codigoBarras) > 50) {
            throw new RuntimeException('Código de barras ultrapassa 50 caracteres.');
        }

        $barraNormalizada = $this->normalizarChave($codigoBarras);
        if (isset($barrasReservados[$barraNormalizada])) {
            throw new RuntimeException('Código de barras já existe: ' . $codigoBarras);
        }
        $barrasReservados[$barraNormalizada] = true;

        $precoCusto = $this->decimal(
            $this->valor($linha, $colunas, 'preco_custo'),
            2,
            'Preço de custo'
        );

        $precoVenda = $this->decimal(
            $this->valor($linha, $colunas, 'preco_venda'),
            2,
            'Preço de venda'
        );

        $estoqueMinimo = $this->decimal(
            $this->valor($linha, $colunas, 'estoque_minimo'),
            3,
            'Stock mínimo'
        );

        $descricao = $this->textoLivre(
            $this->valor($linha, $colunas, 'descricao')
        );

        return [
            'dados' => [
                'categoria_id' => $categoriaId,
                'marca_id' => $marcaId,
                'unidade_id' => $unidadeId,
                'armazem_padrao_id' => $armazemId,
                'status_id' => $statusId,
                'sku' => $sku,
                'nome' => $nome,
                'descricao' => $descricao,
                'imagem_principal' => null,
                'preco_custo' => $precoCusto,
                'preco_venda' => $precoVenda,
                'estoque_minimo' => $estoqueMinimo,
                'codigo_barras' => $codigoBarras,
            ],

            'preview' => [
                'sku' => $sku,
                'nome' => $nome,
                'categoria' => $categoriaNome,
                'marca' => $marcaNome !== '' ? $marcaNome : 'Sem marca',
                'unidade' => $unidadeNome,
                'armazem' => $armazemNome !== ''
                    ? $armazemNome
                    : 'Sem armazém',
                'estado' => $statusNome,
                'preco_custo' => $precoCusto,
                'preco_venda' => $precoVenda,
                'estoque_minimo' => $estoqueMinimo,
                'codigo_barras' => $codigoBarras,
            ],
        ];
    }

    private function carregarReferencias(int $empresaId): array
    {
        $categorias = $this->buscarMapa(
            "SELECT id, nome FROM categorias WHERE empresa_id = :empresa_id",
            $empresaId
        );

        $marcas = $this->buscarMapa(
            "SELECT id, nome FROM marcas WHERE empresa_id = :empresa_id",
            $empresaId
        );

        $unidades = $this->buscarMapaComCodigo(
            "SELECT id, nome, codigo FROM unidades WHERE empresa_id = :empresa_id",
            $empresaId
        );

        $armazens = $this->buscarMapaComCodigo(
            "SELECT id, nome, codigo FROM armazens WHERE empresa_id = :empresa_id",
            $empresaId
        );

        $status = $this->buscarMapaStatus();

        return compact(
            'categorias',
            'marcas',
            'unidades',
            'armazens',
            'status'
        );
    }

    private function buscarMapa(
        string $sql,
        int $empresaId
    ): array {

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        $mapa = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $chave = $this->normalizarChave(
                (string)$row['nome']
            );

            $mapa[$chave][] = [
                'id' => (int)$row['id'],
                'nome' => (string)$row['nome'],
            ];
        }

        return $mapa;
    }

    private function buscarMapaComCodigo(
        string $sql,
        int $empresaId
    ): array {

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        $mapa = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $item = [
                'id' => (int)$row['id'],
                'nome' => (string)$row['nome'],
                'codigo' => (string)($row['codigo'] ?? ''),
            ];

            $mapa[$this->normalizarChave($item['nome'])][] = $item;

            if ($item['codigo'] !== '') {
                $mapa[$this->normalizarChave($item['codigo'])][] = $item;
            }
        }

        return $mapa;
    }

    private function buscarMapaStatus(): array
    {
        $stmt = $this->db->query("
        SELECT
            id,
            nome,
            descricao
        FROM status
        WHERE tipo = 'produto'
        ORDER BY id ASC
    ");

        $mapa = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $nome = trim((string)($row['nome'] ?? ''));

            if ($nome === '') {
                continue;
            }

            $dados = [
                'id' => (int)$row['id'],
                'nome' => $nome,
                'descricao' => (string)($row['descricao'] ?? '')
            ];

            /*
         * Nome original
         */
            $chave = $this->normalizarChave($nome);

            $mapa[$chave][] = $dados;

            /*
         * Alias Activo <-> Ativo
         */
            $nomeNormalizado = mb_strtolower($nome, 'UTF-8');

            if ($nomeNormalizado === 'activo') {
                $mapa[$this->normalizarChave('Ativo')][] = $dados;
            }

            if ($nomeNormalizado === 'ativo') {
                $mapa[$this->normalizarChave('Activo')][] = $dados;
            }

            /*
         * Alias Inactivo <-> Inativo
         */
            if ($nomeNormalizado === 'inactivo') {
                $mapa[$this->normalizarChave('Inativo')][] = $dados;
            }

            if ($nomeNormalizado === 'inativo') {
                $mapa[$this->normalizarChave('Inactivo')][] = $dados;
            }
        }

        return $mapa;
    }

    private function resolverReferencia(array $mapa, string $valor, string $tipo): int
    {
        $chave = $this->normalizarChave($valor);
        $opcoes = $mapa[$chave] ?? [];

        if (!$opcoes) {
            throw new RuntimeException(
                ucfirst($tipo) . ' não encontrada: ' . $valor
            );
        }

        $ids = array_values(array_unique(array_map(
            fn($item) => (int)$item['id'],
            $opcoes
        )));

        if (count($ids) > 1) {
            throw new RuntimeException(
                'Existe mais de uma ' . $tipo . ' com o nome: ' . $valor . '. Use um valor inequívoco.'
            );
        }

        return $ids[0];
    }

    private function carregarSkus(int $empresaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT sku FROM produtos WHERE empresa_id = :empresa_id'
        );

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        $resultado = [];

        while ($sku = $stmt->fetchColumn()) {
            $resultado[$this->normalizarChave((string)$sku)] = true;
        }

        return $resultado;
    }

    private function carregarCodigosBarras(int $empresaId): array
    {
        $stmt = $this->db->prepare(
            'SELECT codigo_barras FROM produtos WHERE empresa_id = :empresa_id'
        );

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        $resultado = [];

        while ($codigo = $stmt->fetchColumn()) {

            if ((string)$codigo !== '') {
                $resultado[$this->normalizarChave((string)$codigo)] = true;
            }
        }

        return $resultado;
    }

    private function gerarSkuImportacao(
        int $empresaId,
        string $nome,
        array $reservados
    ): string {

        $prefixo = $this->gerarPrefixo($nome);

        $candidato = $this->model->gerarProximoSku(
            $empresaId,
            $prefixo
        );

        if (
            !isset(
                $reservados[$this->normalizarChave($candidato)]
            )
        ) {
            return $candidato;
        }

        if (
            !preg_match(
                '/^(.+)-(\d+)$/',
                $candidato,
                $m
            )
        ) {
            throw new RuntimeException(
                'Não foi possível gerar um SKU único para: ' . $nome
            );
        }

        $base = $m[1];
        $numero = (int)$m[2];

        do {

            $numero++;

            $candidato = sprintf(
                '%s-%03d',
                $base,
                $numero
            );
        } while (
            isset(
                $reservados[$this->normalizarChave($candidato)]
            )
        );

        return $candidato;
    }

    private function gerarPrefixo(string $nome): string
    {
        $limpo = strtoupper($this->semAcentos($nome));
        $limpo = preg_replace('/[^A-Z0-9]+/', '', $limpo) ?? '';

        if ($limpo === '') {
            return 'PROD';
        }

        return substr($limpo, 0, 4);
    }

    private function verificarUnicidadeAntesDeCriar(
        int $empresaId,
        string $sku,
        string $codigoBarras
    ): void {
        if ($this->model->skuExiste($empresaId, $sku)) {
            throw new RuntimeException('O SKU já existe: ' . $sku);
        }

        if ($this->model->codigoBarrasExiste($empresaId, $codigoBarras)) {
            throw new RuntimeException('O código de barras já existe: ' . $codigoBarras);
        }
    }

    private function decimal(mixed $valor, int $casas, string $campo): string
    {
        if ($valor === null || trim((string)$valor) === '') {
            return '0.' . str_repeat('0', $casas);
        }

        if (is_string($valor)) {
            $valor = trim($valor);
            $valor = str_replace(["\u{00A0}", ' '], '', $valor);

            if (str_contains($valor, ',') && str_contains($valor, '.')) {
                $ultimaVirgula = strrpos($valor, ',');
                $ultimoPonto = strrpos($valor, '.');

                if ($ultimaVirgula > $ultimoPonto) {
                    $valor = str_replace('.', '', $valor);
                    $valor = str_replace(',', '.', $valor);
                } else {
                    $valor = str_replace(',', '', $valor);
                }
            } elseif (str_contains($valor, ',')) {
                $valor = str_replace(',', '.', $valor);
            }
        }

        if (!is_numeric($valor)) {
            throw new RuntimeException($campo . ' inválido: ' . (string)$valor);
        }

        $numero = (float)$valor;
        if ($numero < 0) {
            throw new RuntimeException($campo . ' não pode ser negativo.');
        }

        return number_format($numero, $casas, '.', '');
    }

    private function validarUpload(array $arquivo): void
    {
        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Não foi possível receber o ficheiro Excel.');
        }

        $nome = (string)($arquivo['name'] ?? '');
        $extensao = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

        if (!in_array($extensao, ['xlsx', 'xls'], true)) {
            throw new RuntimeException('Seleccione um ficheiro Excel .xlsx ou .xls.');
        }

        if ((int)($arquivo['size'] ?? 0) > 10 * 1024 * 1024) {
            throw new RuntimeException('O ficheiro Excel não pode ultrapassar 10 MB.');
        }
    }

    private function mapearColunas(array $cabecalho): array
    {
        $aliases = [
            'sku' => ['sku', 'codigo', 'codigo interno'],
            'nome' => ['nome', 'nome do produto', 'produto'],
            'categoria' => ['categoria', 'categoria nome'],
            'marca' => ['marca'],
            'unidade' => ['unidade', 'unidade medida'],
            'armazem' => ['armazem', 'armazem padrao', 'armazém', 'armazém padrão'],
            'codigo_barras' => ['codigo barras', 'codigo de barras', 'cod barras', 'ean', 'gtin', 'código de barras'],
            'preco_custo' => ['preco custo', 'preço custo', 'preco de custo'],
            'preco_venda' => ['preco venda', 'preço venda', 'preco de venda'],
            'estoque_minimo' => ['stock minimo', 'stock mínimo', 'estoque minimo', 'estoque mínimo'],
            'estado' => ['estado', 'status'],
            'descricao' => ['descricao', 'descrição'],
        ];

        $resultado = [];
        foreach ($aliases as $chave => $lista) {
            foreach ($cabecalho as $indice => $nome) {
                if (in_array($nome, array_map([$this, 'normalizarCabecalho'], $lista), true)) {
                    $resultado[$chave] = $indice;
                    break;
                }
            }
        }

        return $resultado;
    }

    private function validarColunasObrigatorias(array $colunas): void
    {
        foreach (['nome', 'categoria', 'unidade', 'preco_venda'] as $obrigatoria) {
            if (!array_key_exists($obrigatoria, $colunas)) {
                throw new RuntimeException(
                    'A coluna obrigatória "' . $obrigatoria . '" não foi encontrada no Excel.'
                );
            }
        }
    }

    private function dadosLinhaParaPreview(array $linha, array $colunas): array
    {
        return [
            'sku' => $this->valor($linha, $colunas, 'sku'),
            'nome' => $this->valor($linha, $colunas, 'nome'),
            'categoria' => $this->valor($linha, $colunas, 'categoria'),
            'unidade' => $this->valor($linha, $colunas, 'unidade'),
        ];
    }

    private function valor(array $linha, array $colunas, string $chave): mixed
    {
        if (!array_key_exists($chave, $colunas)) {
            return null;
        }

        return $linha[$colunas[$chave]] ?? null;
    }

    private function linhaVazia(array $linha): bool
    {
        foreach ($linha as $valor) {
            if (trim((string)$valor) !== '') {
                return false;
            }
        }

        return true;
    }

    private function texto(mixed $valor): string
    {
        return trim((string)($valor ?? ''));
    }

    private function textoLivre(mixed $valor): ?string
    {
        $texto = trim((string)($valor ?? ''));
        return $texto === '' ? null : $texto;
    }

    private function normalizarCabecalho(string $valor): string
    {
        return $this->normalizarChave($valor);
    }

    private function normalizarChave(string $valor): string
    {
        $valor = trim($valor);

        $valor = $this->semAcentos($valor);

        $valor = mb_strtolower($valor);

        $valor = preg_replace(
            '/\s+/',
            ' ',
            $valor
        ) ?? '';

        return $valor;
    }

    private function semAcentos(string $valor): string
    {
        if (function_exists('iconv')) {
            $convertido = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
            if ($convertido !== false) {
                return $convertido;
            }
        }

        return $valor;
    }

    private function diretorioPacotes(): string
    {
        $diretorio = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'erpplus_produto_importacoes';

        if (!is_dir($diretorio) && !mkdir($diretorio, 0700, true) && !is_dir($diretorio)) {
            throw new RuntimeException('Não foi possível preparar o armazenamento temporário da importação.');
        }

        return $diretorio;
    }

    private function guardarPacote(string $token, array $pacote): void
    {
        $caminho = $this->diretorioPacotes() . DIRECTORY_SEPARATOR . $token . '.json';
        file_put_contents(
            $caminho,
            json_encode($pacote, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            LOCK_EX
        );
    }

    private function lerPacote(string $token): array
    {
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
            throw new RuntimeException('Token de importação inválido.');
        }

        $caminho = $this->diretorioPacotes() . DIRECTORY_SEPARATOR . $token . '.json';

        if (!is_file($caminho)) {
            throw new RuntimeException('A validação do Excel não foi encontrada ou já foi utilizada.');
        }

        $conteudo = file_get_contents($caminho);
        $pacote = json_decode((string)$conteudo, true);

        if (!is_array($pacote)) {
            throw new RuntimeException('O pacote de importação está corrompido.');
        }

        return $pacote;
    }

    private function apagarPacote(string $token): void
    {
        $caminho = $this->diretorioPacotes() . DIRECTORY_SEPARATOR . $token . '.json';
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }
}
