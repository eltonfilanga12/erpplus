<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/Produto.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';

final class ProdutoController
{
    private PDO $db;

    private Produto $model;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct()
    {
        $this->db =
            Database::conn();


        $this->model =
            new Produto(
                $this->db
            );


        $this->auditoria =
            new AuditoriaService(
                $this->db
            );


        $this->log =
            new LogService(
                $this->db
            );
    }

    public function listar(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model->listar(
                    $this->empresaId()
                )
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function buscar(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            $id =
                $this->inteiroObrigatorio(
                    $_GET['id']
                        ?? $_POST['id']
                        ?? null,
                    'Produto inválido.'
                );

            $produto =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$produto) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Produto não encontrado.'
                ];
            }

            return [
                'sucesso' => true,
                'dados' => $produto
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function gerarSku(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $nome =
                trim(
                    (string)(
                        $_GET['nome']
                        ?? $_POST['nome']
                        ?? ''
                    )
                );


            $id =
                $this->inteiroOpcional(
                    $_GET['id']
                        ?? $_POST['id']
                        ?? null
                );


            if ($nome === '') {

                return [
                    'sucesso' => true,

                    'dados' => [
                        'sku' => '',
                        'codigo_barras' => ''
                    ]
                ];
            }


            $prefixo =
                $this->gerarPrefixoSku(
                    $nome
                );


            $sku =
                $this->model
                ->gerarProximoSku(
                    $empresaId,
                    $prefixo,
                    $id
                );


            return [
                'sucesso' => true,

                'dados' => [
                    'sku' =>
                    $sku,

                    /*
                 * Para produtos sem EAN/GTIN
                 * utilizaremos o próprio SKU
                 * como código interno Code 128.
                 */
                    'codigo_barras' =>
                    $sku
                ]
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }

    private function gerarPrefixoSku(
        string $nome
    ): string {

        $nome =
            trim($nome);


        /*
     * Remover acentos.
     */
        $convertido =
            iconv(
                'UTF-8',
                'ASCII//TRANSLIT//IGNORE',
                $nome
            );


        if ($convertido !== false) {
            $nome = $convertido;
        }


        /*
     * Somente letras e números.
     */
        $nome =
            preg_replace(
                '/[^A-Za-z0-9]/',
                '',
                $nome
            );


        $nome =
            strtoupper(
                (string)$nome
            );


        if ($nome === '') {
            return 'PROD';
        }


        /*
     * Máximo 4 caracteres.
     */
        return substr(
            $nome,
            0,
            4
        );
    }

    public function categorias(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model
                    ->listarCategorias(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function marcas(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarMarcas(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }


    public function unidades(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarUnidades(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }

    public function armazens(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model
                    ->listarArmazens(
                        $this->empresaId()
                    )
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function status(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model
                    ->listarStatus()
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function salvar(): array
    {
        $novaImagem =
            null;

        try {

            $empresaId =
                $this->empresaId();

            $usuarioId =
                $this->usuarioId();


            $id =
                $this->inteiroOpcional(
                    $_POST['id']
                        ?? null
                );


            // ==========================================
            // PRODUTO ATUAL
            // ==========================================

            $produtoActual =
                null;


            if ($id !== null) {

                $produtoActual =
                    $this->model->buscar(
                        $id,
                        $empresaId
                    );


                if (!$produtoActual) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Produto não encontrado.'
                    ];
                }
            }


            // ==========================================
            // CATEGORIA
            // ==========================================

            $categoriaId =
                $this->inteiroObrigatorio(
                    $_POST['categoria_id']
                        ?? null,
                    'Selecione a categoria.'
                );


            if (
                !$this->model
                    ->categoriaPertenceEmpresa(
                        $categoriaId,
                        $empresaId
                    )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'A categoria selecionada não pertence à empresa.'
                ];
            }


            // ==========================================
            // ARMAZÉM
            // ==========================================

            $armazemId =
                $this->inteiroOpcional(
                    $_POST['armazem_padrao_id']
                        ?? null
                );


            if (
                $armazemId !== null
                && !$this->model
                    ->armazemPertenceEmpresa(
                        $armazemId,
                        $empresaId
                    )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O armazém selecionado não pertence à empresa.'
                ];
            }


            // ==========================================
            // STATUS
            // ==========================================

            $statusId =
                $this->inteiroObrigatorio(
                    $_POST['status_id']
                        ?? null,
                    'Selecione o estado do produto.'
                );


            if (
                !$this->model
                    ->statusExiste(
                        $statusId
                    )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O estado selecionado não pertence aos produtos.'
                ];
            }


            // ==========================================
            // SKU
            // ==========================================

            $sku =
                strtoupper(
                    $this->textoObrigatorio(
                        $_POST['sku']
                            ?? null,
                        'Informe o SKU do produto.',
                        50
                    )
                );


            if (
                $this->model
                ->skuExiste(
                    $empresaId,
                    $sku,
                    $id
                )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Já existe um produto com este SKU.'
                ];
            }


            // ==========================================
            // CÓDIGO DE BARRAS
            // ==========================================

            $codigoBarras =
                $this->textoOpcional(
                    $_POST['codigo_barras']
                        ?? null,
                    50
                );


            /*
         * Se não houver EAN/GTIN,
         * utiliza o SKU como código interno.
         */
            if ($codigoBarras === null) {

                $codigoBarras =
                    $sku;
            }


            if (
                $this->model
                ->codigoBarrasExiste(
                    $empresaId,
                    $codigoBarras,
                    $id
                )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Já existe um produto com este código de barras.'
                ];
            }


            // ==========================================
            // MARCA
            // ==========================================

            $marcaId =
                $this->inteiroOpcional(
                    $_POST['marca_id']
                        ?? null
                );


            if (
                $marcaId !== null
                && !$this->model
                    ->marcaPertenceEmpresa(
                        $marcaId,
                        $empresaId
                    )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'A marca selecionada não pertence à empresa.'
                ];
            }


            // ==========================================
            // UNIDADE
            // ==========================================

            $unidadeId =
                $this->inteiroObrigatorio(
                    $_POST['unidade_id']
                        ?? null,
                    'Selecione a unidade do produto.'
                );


            if (
                !$this->model
                    ->unidadePertenceEmpresa(
                        $unidadeId,
                        $empresaId
                    )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'A unidade selecionada não pertence à empresa.'
                ];
            }


            // ==========================================
            // IMAGEM
            // ==========================================

            $imagemActual =
                $produtoActual['imagem_principal']
                ?? null;


            $removerImagem =
                (int)(
                    $_POST['remover_imagem']
                    ?? 0
                ) === 1;


            /*
         * Esta função grava a nova imagem fisicamente.
         *
         * Se depois a transação falhar,
         * vamos apagá-la no catch.
         */
            $novaImagem =
                $this->processarImagem(
                    $_FILES['imagem_principal']
                        ?? null
                );


            $imagemFinal =
                $imagemActual;


            if ($removerImagem) {

                $imagemFinal =
                    null;
            }


            /*
         * Uma nova imagem tem prioridade.
         */
            if ($novaImagem !== null) {

                $imagemFinal =
                    $novaImagem;
            }


            // ==========================================
            // DADOS
            // ==========================================

            $dados = [

                'categoria_id' =>
                $categoriaId,

                'marca_id' =>
                $marcaId,

                'unidade_id' =>
                $unidadeId,

                'armazem_padrao_id' =>
                $armazemId,

                'status_id' =>
                $statusId,

                'sku' =>
                $sku,

                'nome' =>
                $this->textoObrigatorio(
                    $_POST['nome']
                        ?? null,
                    'Informe o nome do produto.',
                    150
                ),

                'descricao' =>
                $this->textoLivreOpcional(
                    $_POST['descricao']
                        ?? null
                ),

                'imagem_principal' =>
                $imagemFinal,

                'preco_custo' =>
                $this->decimalNaoNegativo(
                    $_POST['preco_custo']
                        ?? 0,
                    2,
                    'O preço de custo é inválido.'
                ),

                'preco_venda' =>
                $this->decimalNaoNegativo(
                    $_POST['preco_venda']
                        ?? 0,
                    2,
                    'O preço de venda é inválido.'
                ),

                'estoque_minimo' =>
                $this->decimalNaoNegativo(
                    $_POST['estoque_minimo']
                        ?? 0,
                    3,
                    'O stock mínimo é inválido.'
                ),

                'codigo_barras' =>
                $codigoBarras
            ];


            // ==================================================
            // EDITAR
            // ==================================================

            if ($id !== null) {

                $this->db
                    ->beginTransaction();


                try {

                    $atualizado =
                        $this->model
                        ->atualizar(
                            $id,
                            $empresaId,
                            $dados
                        );


                    if (!$atualizado) {

                        throw new RuntimeException(
                            'Não foi possível atualizar o produto.'
                        );
                    }


                    $produtoDepois =
                        $this->model->buscar(
                            $id,
                            $empresaId
                        );


                    if (!$produtoDepois) {

                        throw new RuntimeException(
                            'Não foi possível obter o produto atualizado.'
                        );
                    }


                    // ======================================
                    // AUDITORIA
                    // ======================================

                    $this->auditoria
                        ->atualizacao(
                            $empresaId,
                            $usuarioId,
                            'produtos',
                            $id,

                            $this
                                ->dadosAuditoriaProduto(
                                    $produtoActual
                                ),

                            $this
                                ->dadosAuditoriaProduto(
                                    $produtoDepois
                                )
                        );


                    // ======================================
                    // LOG
                    // ======================================

                    $this->log
                        ->registrar(
                            $empresaId,
                            $usuarioId,
                            'PRODUTOS',
                            'EDITAR',

                            'Atualizou o produto '
                                . (
                                    $produtoDepois['sku']
                                    ?? ''
                                )
                                . ' - '
                                . (
                                    $produtoDepois['nome']
                                    ?? ''
                                )
                                . '.',

                            $id,
                            $this->filialId()
                        );


                    $this->db
                        ->commit();


                    /*
                 * Somente depois do COMMIT
                 * podemos apagar a imagem antiga.
                 */
                    if (
                        $imagemActual
                        && $imagemActual !== $imagemFinal
                    ) {

                        $this->apagarImagem(
                            $imagemActual
                        );
                    }


                    /*
                 * A nova imagem passou a pertencer
                 * oficialmente ao produto.
                 */
                    $novaImagem =
                        null;


                    return [
                        'sucesso' => true,

                        'mensagem' =>
                        'Produto atualizado com sucesso.',

                        'dados' => [
                            'id' =>
                            $id
                        ]
                    ];
                } catch (Throwable $e) {

                    if (
                        $this->db
                        ->inTransaction()
                    ) {

                        $this->db
                            ->rollBack();
                    }


                    /*
                 * Se uma nova imagem foi carregada
                 * e a transação falhou, eliminamos
                 * essa imagem para não ficar órfã.
                 */
                    if ($novaImagem !== null) {

                        $this->apagarImagem(
                            $novaImagem
                        );

                        $novaImagem =
                            null;
                    }


                    throw $e;
                }
            }


            // ==================================================
            // CRIAR
            // ==================================================

            $this->db
                ->beginTransaction();


            try {

                $novoId =
                    $this->model->criar(
                        $empresaId,
                        $dados
                    );


                if ($novoId <= 0) {

                    throw new RuntimeException(
                        'Não foi possível criar o produto.'
                    );
                }


                $produtoNovo =
                    $this->model->buscar(
                        $novoId,
                        $empresaId
                    );


                if (!$produtoNovo) {

                    throw new RuntimeException(
                        'Não foi possível obter o produto criado.'
                    );
                }


                // ==========================================
                // AUDITORIA
                // ==========================================

                $this->auditoria
                    ->insercao(
                        $empresaId,
                        $usuarioId,
                        'produtos',
                        $novoId,

                        $this
                            ->dadosAuditoriaProduto(
                                $produtoNovo
                            )
                    );


                // ==========================================
                // LOG
                // ==========================================

                $this->log
                    ->registrar(
                        $empresaId,
                        $usuarioId,
                        'PRODUTOS',
                        'CRIAR',

                        'Criou o produto '
                            . (
                                $produtoNovo['sku']
                                ?? ''
                            )
                            . ' - '
                            . (
                                $produtoNovo['nome']
                                ?? ''
                            )
                            . '.',

                        $novoId,
                        $this->filialId()
                    );


                $this->db
                    ->commit();


                /*
             * A imagem agora pertence ao produto.
             */
                $novaImagem =
                    null;


                return [
                    'sucesso' => true,

                    'mensagem' =>
                    'Produto criado com sucesso.',

                    'dados' => [
                        'id' =>
                        $novoId
                    ]
                ];
            } catch (Throwable $e) {

                if (
                    $this->db
                    ->inTransaction()
                ) {

                    $this->db
                        ->rollBack();
                }


                if ($novaImagem !== null) {

                    $this->apagarImagem(
                        $novaImagem
                    );

                    $novaImagem =
                        null;
                }


                throw $e;
            }
        } catch (PDOException $e) {

            /*
         * Segurança adicional:
         * se alguma exceção escapar da transação.
         */
            if ($novaImagem !== null) {

                $this->apagarImagem(
                    $novaImagem
                );
            }


            return $this->erroBanco(
                $e
            );
        } catch (Throwable $e) {

            if ($novaImagem !== null) {

                $this->apagarImagem(
                    $novaImagem
                );
            }


            return $this->erro(
                $e
            );
        }
    }

    public function validarImportacaoExcel(): array
    {
        try {

            $empresaId = $this->empresaId();
            $usuarioId = $this->usuarioId();

            if (
                !isset($_FILES['ficheiro']) ||
                !is_array($_FILES['ficheiro'])
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Seleccione um ficheiro Excel.'
                ];
            }

            $servico =
                new ProdutoImportacaoService($this->db);

            $resultado =
                $servico->validarArquivo(
                    $_FILES['ficheiro'],
                    $empresaId,
                    $usuarioId
                );

            return [
                'sucesso' => true,
                'mensagem' => 'Ficheiro validado com sucesso.',
                'dados' => $resultado
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }

    public function confirmarImportacaoExcel(): array
    {
        try {

            $empresaId =
                $this->empresaId();

            $usuarioId =
                $this->usuarioId();

            $token =
                trim(
                    (string)(
                        $_POST['token']
                        ?? ''
                    )
                );

            if ($token === '') {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Token de importação inválido.'
                ];
            }

            $servico =
                new ProdutoImportacaoService(
                    $this->db
                );

            $resultado =
                $servico->confirmar(
                    $token,
                    $empresaId,
                    $usuarioId
                );

            if (!isset($resultado['produtos']) || !is_array($resultado['produtos'])) {
                return [
                    'sucesso' => false,
                    'mensagem' => $resultado['mensagem'] ?? 'A importação não devolveu os produtos criados.',
                    'dados' => $resultado
                ];
            }

            foreach ($resultado['produtos'] as $produtoImportado) {

                $produtoId = (int)($produtoImportado['id'] ?? 0);

                if ($produtoId <= 0) {
                    continue;
                }

                $dadosAuditoria = [
                    'id' => $produtoId,
                    'sku' => $produtoImportado['sku'] ?? null,
                    'nome' => $produtoImportado['nome'] ?? null,
                    'linha_excel' => $produtoImportado['linha'] ?? null,
                    'origem' => 'importacao_excel'
                ];

                /*
                * AUDITORIA
                */
                try {

                    $this->auditoria->insercao(
                        $empresaId,
                        $usuarioId,
                        'produtos',
                        $produtoId,
                        $dadosAuditoria
                    );
                } catch (Throwable $e) {

                    error_log(
                        '[ERP PLUS][IMPORTAÇÃO PRODUTOS][AUDITORIA] ' .
                            $e->getMessage() .
                            ' | Produto ID: ' . $produtoId
                    );
                }


                /*
                * LOG
                */
                try {

                    $this->log->registrar(
                        $empresaId,
                        $usuarioId,
                        'PRODUTOS',
                        'CRIAR',
                        'Produto importado através de ficheiro Excel: ' .
                            ($produtoImportado['nome'] ?? ''),
                        $produtoId,
                        $this->filialId()
                    );
                } catch (Throwable $e) {

                    error_log(
                        '[ERP PLUS][IMPORTAÇÃO PRODUTOS][LOG] ' .
                            $e->getMessage() .
                            ' | Produto ID: ' . $produtoId
                    );
                }
            }


            return [
                'sucesso' => true,

                'mensagem' =>
                'Importação concluída com sucesso.',

                'dados' =>
                $resultado
            ];
        } catch (Throwable $e) {
            error_log(
                '[ERP PLUS][IMPORTAÇÃO PRODUTOS] ' .
                    $e->getMessage() .
                    ' | Arquivo: ' . $e->getFile() .
                    ' | Linha: ' . $e->getLine()
            );

            return [
                'sucesso' => false,
                'mensagem' => 'Não foi possível concluir a importação: ' . $e->getMessage(),
                'dados' => [
                    'arquivo' => $e->getFile(),
                    'linha' => $e->getLine()
                ]
            ];
        }
    }

    public function baixarModeloImportacaoExcel(): void
    {

        try {

            $empresaId =
                $this->empresaId();


            if (
                $empresaId <= 0
            ) {

                throw new RuntimeException(
                    'Empresa inválida.'
                );
            }


            $servico =
                new ProdutoImportacaoService(
                    $this->db
                );


            $spreadsheet =
                $servico->gerarModeloExcel(
                    $empresaId
                );


            /*
         * Limpar qualquer saída anterior.
         */

            if (
                ob_get_length()
            ) {
                ob_end_clean();
            }


            $nomeArquivo =
                'modelo_importacao_produtos_'
                . date('Ymd_His')
                . '.xlsx';


            header(
                'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );


            header(
                'Content-Disposition: attachment; filename="'
                    . $nomeArquivo
                    . '"'
            );


            header(
                'Cache-Control: max-age=0'
            );


            header(
                'Pragma: public'
            );


            $writer =
                new \PhpOffice\PhpSpreadsheet\Writer\Xlsx(
                    $spreadsheet
                );


            $writer->save(
                'php://output'
            );


            exit;
        } catch (
            Throwable $e
        ) {

            http_response_code(500);


            header(
                'Content-Type: application/json; charset=utf-8'
            );


            echo json_encode([
                'success' => false,
                'mensagem' =>
                $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);


            exit;
        }
    }

    public function eliminar(): array
    {
        try {

            $empresaId =
                $this->empresaId();

            $usuarioId =
                $this->usuarioId();


            $id =
                $this->inteiroObrigatorio(
                    $_POST['id']
                        ?? null,
                    'Produto inválido.'
                );


            $produto =
                $this->model->buscar(
                    $id,
                    $empresaId
                );


            if (!$produto) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Produto não encontrado.'
                ];
            }


            $imagem =
                $produto['imagem_principal']
                ?? null;


            $nomeProduto =
                trim(
                    (
                        $produto['sku']
                        ?? ''
                    )
                        . ' - '
                        . (
                            $produto['nome']
                            ?? 'Produto'
                        )
                );


            $dadosAnteriores =
                $this->dadosAuditoriaProduto(
                    $produto
                );


            $this->db
                ->beginTransaction();


            try {

                $eliminado =
                    $this->model->eliminar(
                        $id,
                        $empresaId
                    );


                if (!$eliminado) {

                    throw new RuntimeException(
                        'Não foi possível eliminar o produto.'
                    );
                }


                // ======================================
                // AUDITORIA
                // ======================================

                $this->auditoria
                    ->exclusao(
                        $empresaId,
                        $usuarioId,
                        'produtos',
                        $id,
                        $dadosAnteriores
                    );


                // ======================================
                // LOG
                // ======================================

                $this->log
                    ->registrar(
                        $empresaId,
                        $usuarioId,
                        'PRODUTOS',
                        'ELIMINAR',

                        'Eliminou o produto '
                            . $nomeProduto
                            . '.',

                        $id,
                        $this->filialId()
                    );


                $this->db
                    ->commit();


                /*
             * A imagem só é removida fisicamente
             * depois do DELETE estar confirmado.
             */
                $this->apagarImagem(
                    $imagem
                );


                return [
                    'sucesso' => true,

                    'mensagem' =>
                    'Produto eliminado com sucesso.'
                ];
            } catch (PDOException $e) {

                if (
                    $this->db
                    ->inTransaction()
                ) {

                    $this->db
                        ->rollBack();
                }


                if (
                    (string)$e->getCode()
                    === '23000'
                ) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'O produto possui stock, vendas, compras ou outros movimentos associados.'
                    ];
                }


                throw $e;
            } catch (Throwable $e) {

                if (
                    $this->db
                    ->inTransaction()
                ) {

                    $this->db
                        ->rollBack();
                }


                throw $e;
            }
        } catch (PDOException $e) {

            return $this->erroBanco(
                $e
            );
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }

    private function empresaId(): int
    {
        $empresaId =
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? $_SESSION['user']['empresa_id']
            ?? null;

        if (
            $empresaId === null
            || !is_numeric($empresaId)
            || (int) $empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }

        return (int) $empresaId;
    }

    private function usuarioId(): int
    {
        $usuarioId =
            (int)(
                Auth::usuarioId()
                ?? 0
            );


        if ($usuarioId <= 0) {

            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }


        return $usuarioId;
    }


    private function filialId(): ?int
    {
        $filialId =
            (int)(
                Auth::filialId()
                ?? 0
            );


        return $filialId > 0
            ? $filialId
            : null;
    }

    private function dadosAuditoriaProduto(
        array $produto
    ): array {

        return [

            'id' =>
            (int)(
                $produto['id']
                ?? 0
            ),

            'empresa_id' =>
            (int)(
                $produto['empresa_id']
                ?? 0
            ),

            'categoria_id' =>
            isset(
                $produto['categoria_id']
            )
                ? (int)$produto['categoria_id']
                : null,

            'marca_id' =>
            isset($produto['marca_id'])
                && $produto['marca_id'] !== null
                ? (int)$produto['marca_id']
                : null,

            'unidade_id' =>
            isset(
                $produto['unidade_id']
            )
                ? (int)$produto['unidade_id']
                : null,

            'armazem_padrao_id' =>
            isset($produto['armazem_padrao_id'])
                && $produto['armazem_padrao_id'] !== null
                ? (int)$produto['armazem_padrao_id']
                : null,

            'status_id' =>
            isset(
                $produto['status_id']
            )
                ? (int)$produto['status_id']
                : null,

            'sku' =>
            $produto['sku']
                ?? null,

            'nome' =>
            $produto['nome']
                ?? null,

            'descricao' =>
            $produto['descricao']
                ?? null,

            'imagem_principal' =>
            $produto['imagem_principal']
                ?? null,

            'preco_custo' =>
            $produto['preco_custo']
                ?? null,

            'preco_venda' =>
            $produto['preco_venda']
                ?? null,

            'estoque_minimo' =>
            $produto['estoque_minimo']
                ?? null,

            'codigo_barras' =>
            $produto['codigo_barras']
                ?? null
        ];
    }

    private function processarImagem(
        ?array $ficheiro
    ): ?string {
        if (
            !$ficheiro
            || !isset($ficheiro['error'])
            || $ficheiro['error']
            === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        if (
            $ficheiro['error']
            !== UPLOAD_ERR_OK
        ) {
            throw new RuntimeException(
                'Ocorreu um erro ao enviar a imagem.'
            );
        }

        if (
            (int) $ficheiro['size']
            > 5 * 1024 * 1024
        ) {
            throw new InvalidArgumentException(
                'A imagem não pode exceder 5 MB.'
            );
        }

        $finfo =
            new finfo(
                FILEINFO_MIME_TYPE
            );

        $mime =
            $finfo->file(
                $ficheiro['tmp_name']
            );

        $extensoes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (
            !isset($extensoes[$mime])
        ) {
            throw new InvalidArgumentException(
                'A imagem deve estar em formato JPG, PNG ou WEBP.'
            );
        }

        $pasta =
            dirname(__DIR__, 2)
            . '/public/produtos';

        if (
            !is_dir($pasta)
            && !mkdir(
                $pasta,
                0775,
                true
            )
            && !is_dir($pasta)
        ) {
            throw new RuntimeException(
                'Não foi possível criar a pasta das imagens.'
            );
        }

        $nome =
            sprintf(
                'produto_%s_%s.%s',
                date('YmdHis'),
                bin2hex(
                    random_bytes(8)
                ),
                $extensoes[$mime]
            );

        $destino =
            $pasta
            . DIRECTORY_SEPARATOR
            . $nome;

        if (
            !move_uploaded_file(
                $ficheiro['tmp_name'],
                $destino
            )
        ) {
            throw new RuntimeException(
                'Não foi possível guardar a imagem do produto.'
            );
        }

        return $nome;
    }

    private function apagarImagem(
        ?string $imagem
    ): void {
        $imagem =
            trim((string) $imagem);

        if ($imagem === '') {
            return;
        }

        $nomeSeguro =
            basename($imagem);

        $caminho =
            dirname(__DIR__, 2)
            . '/public/produtos/'
            . $nomeSeguro;

        if (
            is_file($caminho)
        ) {
            @unlink($caminho);
        }
    }

    private function inteiroObrigatorio(
        mixed $valor,
        string $mensagem
    ): int {
        $numero =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $numero === false
            || $numero <= 0
        ) {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return (int) $numero;
    }

    private function inteiroOpcional(
        mixed $valor
    ): ?int {
        if (
            $valor === null
            || trim((string) $valor) === ''
        ) {
            return null;
        }

        return $this->inteiroObrigatorio(
            $valor,
            'Identificador inválido.'
        );
    }

    private function textoObrigatorio(
        mixed $valor,
        string $mensagem,
        int $limite
    ): string {
        $texto =
            trim((string) $valor);

        if ($texto === '') {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        if (
            mb_strlen($texto)
            > $limite
        ) {
            throw new InvalidArgumentException(
                'O valor informado excede o limite permitido.'
            );
        }

        return $texto;
    }

    private function textoOpcional(
        mixed $valor,
        int $limite
    ): ?string {
        $texto =
            trim((string) $valor);

        if ($texto === '') {
            return null;
        }

        if (
            mb_strlen($texto)
            > $limite
        ) {
            throw new InvalidArgumentException(
                'O valor informado excede o limite permitido.'
            );
        }

        return $texto;
    }

    private function textoLivreOpcional(
        mixed $valor
    ): ?string {
        $texto =
            trim((string) $valor);

        return $texto === ''
            ? null
            : $texto;
    }

    private function decimalNaoNegativo(
        mixed $valor,
        int $casasDecimais,
        string $mensagem
    ): string {
        $texto =
            str_replace(
                ',',
                '.',
                trim((string) $valor)
            );

        if ($texto === '') {
            $texto = '0';
        }

        if (
            !is_numeric($texto)
            || (float) $texto < 0
        ) {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return number_format(
            (float) $texto,
            $casasDecimais,
            '.',
            ''
        );
    }

    private function erroBanco(
        PDOException $e
    ): array {
        error_log(
            sprintf(
                '[ProdutoController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        $mensagem =
            mb_strtolower(
                $e->getMessage()
            );

        if (
            str_contains(
                $mensagem,
                'uq_produtos_empresa_sku'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Já existe um produto com este SKU.'
            ];
        }

        if (
            str_contains(
                $mensagem,
                'uq_produtos_empresa_cb'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Já existe um produto com este código de barras.'
            ];
        }

        return [
            'sucesso' => false,
            'mensagem' =>
            'Não foi possível processar os dados do produto.'
        ];
    }

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[ProdutoController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        return [
            'sucesso' => false,
            'mensagem' =>
            $e instanceof InvalidArgumentException
                || $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Ocorreu um erro interno ao processar o produto.'
        ];
    }
}
