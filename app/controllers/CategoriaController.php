<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Categoria.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class CategoriaController
{
    private PDO $db;

    private Categoria $model;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct()
    {
        $this->db =
            Database::conn();

        $this->model =
            new Categoria(
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
                    'Categoria inválida.'
                );

            $categoria =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$categoria) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Categoria não encontrada.'
                ];
            }

            return [
                'sucesso' => true,
                'dados' => $categoria
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    private function usuarioId(): int
    {
        $usuarioId =
            $_SESSION['usuario_id']
            ?? $_SESSION['utilizador_id']
            ?? $_SESSION['usuario']['id']
            ?? $_SESSION['user']['id']
            ?? null;

        if (
            $usuarioId === null
            || !is_numeric($usuarioId)
            || (int)$usuarioId <= 0
        ) {
            throw new RuntimeException(
                'Utilizador não identificado na sessão.'
            );
        }

        return (int)$usuarioId;
    }

    // Adicionar dentro da classe CategoriaController em CategoriaController.php

    public function validarImportacaoExcel(): array
    {
        try {
            $empresaId = $this->empresaId();
            $usuarioId = $this->usuarioId();

            if (!isset($_FILES['ficheiro']) || !is_array($_FILES['ficheiro'])) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Selecione um ficheiro Excel.'
                ];
            }

            $servico = new CategoriaImportacaoService($this->db);
            $resultado = $servico->validarArquivo($_FILES['ficheiro'], $empresaId, $usuarioId);

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
            $empresaId = $this->empresaId();
            $usuarioId = $this->usuarioId();
            $token = trim((string)($_POST['token'] ?? ''));

            if ($token === '') {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Token de importação inválido.'
                ];
            }

            $servico = new CategoriaImportacaoService($this->db);
            $resultado = $servico->confirmar($token, $empresaId, $usuarioId);

            foreach ($resultado['produtos'] as $item) {
                $categoriaId = (int)($item['id'] ?? 0);
                if ($categoriaId <= 0) continue;

                $categoriaCriada = $this->model->buscar($categoriaId, $empresaId);

                $this->auditoria->insercao(
                    $empresaId,
                    $usuarioId,
                    'categorias',
                    $categoriaId,
                    $this->dadosAuditoriaCategoria($categoriaCriada)
                );

                $this->log->registrar(
                    $empresaId,
                    $usuarioId,
                    'CATEGORIAS',
                    'CRIAR',
                    'Categoria importada através de ficheiro Excel: ' . ($item['nome'] ?? ''),
                    $categoriaId,
                    $this->filialId()
                );
            }

            return [
                'sucesso' => true,
                'mensagem' => 'Importação concluída com sucesso.',
                'dados' => $resultado
            ];
        } catch (Throwable $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Não foi possível concluir a importação: ' . $e->getMessage()
            ];
        }
    }

    public function baixarModeloImportacaoExcel(): void
    {
        try {
            $empresaId = $this->empresaId();
            if ($empresaId <= 0) {
                throw new RuntimeException('Empresa inválida.');
            }

            $servico = new CategoriaImportacaoService($this->db);
            $spreadsheet = $servico->gerarModeloExcel($empresaId);

            if (ob_get_length()) {
                ob_end_clean();
            }

            $nomeArquivo = 'modelo_importacao_categorias_' . date('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'mensagem' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }


    private function filialId(): ?int
    {
        $filialId =
            $_SESSION['filial_id']
            ?? $_SESSION['usuario']['filial_id']
            ?? $_SESSION['user']['filial_id']
            ?? null;

        if (
            $filialId === null
            || !is_numeric($filialId)
            || (int)$filialId <= 0
        ) {
            return null;
        }

        return (int)$filialId;
    }


    private function dadosAuditoriaCategoria(
        array $categoria
    ): array {

        return [
            'id' =>
            (int)(
                $categoria['id']
                ?? 0
            ),

            'empresa_id' =>
            (int)(
                $categoria['empresa_id']
                ?? 0
            ),

            'categoria_pai_id' =>
            isset(
                $categoria['categoria_pai_id']
            )
                && $categoria['categoria_pai_id']
                !== null
                ? (int)$categoria['categoria_pai_id']
                : null,

            'status_id' =>
            isset(
                $categoria['status_id']
            )
                ? (int)$categoria['status_id']
                : null,

            'nome' =>
            $categoria['nome']
                ?? null,

            'descricao' =>
            $categoria['descricao']
                ?? null
        ];
    }

    public function opcoes(): array
    {
        try {
            $ignorarId =
                $this->inteiroOpcional(
                    $_GET['ignorar_id']
                        ?? null
                );

            return [
                'sucesso' => true,
                'dados' =>
                $this->model->listarOpcoes(
                    $this->empresaId(),
                    $ignorarId
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

            if (
                $id !== null
                && !$this->model->buscar(
                    $id,
                    $empresaId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Categoria não encontrada.'
                ];
            }

            $nome =
                $this->textoObrigatorio(
                    $_POST['nome']
                        ?? null,
                    'Informe o nome da categoria.',
                    120
                );

            if (
                $this->model->nomeExiste(
                    $empresaId,
                    $nome,
                    $id
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Já existe uma categoria com este nome.'
                ];
            }

            $statusId =
                $this->inteiroObrigatorio(
                    $_POST['status_id']
                        ?? null,
                    'Selecione o estado da categoria.'
                );

            if (
                !$this->model->statusExiste(
                    $statusId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O estado selecionado não pertence às categorias.'
                ];
            }

            $categoriaPaiId =
                $this->inteiroOpcional(
                    $_POST['categoria_pai_id']
                        ?? null
                );

            if (
                $categoriaPaiId !== null
                && !$this->model
                    ->categoriaPertenceEmpresa(
                        $categoriaPaiId,
                        $empresaId
                    )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A categoria pai não pertence à empresa.'
                ];
            }

            if (
                $id !== null
                && $categoriaPaiId === $id
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Uma categoria não pode ser pai de si própria.'
                ];
            }

            if (
                $id !== null
                && $categoriaPaiId !== null
                && $this->model->criariaCiclo(
                    $id,
                    $categoriaPaiId,
                    $empresaId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A categoria pai selecionada criaria uma relação circular.'
                ];
            }

            $dados = [
                'categoria_pai_id' =>
                $categoriaPaiId,

                'status_id' =>
                $statusId,

                'nome' =>
                $nome,

                'descricao' =>
                $this->textoLivreOpcional(
                    $_POST['descricao']
                        ?? null
                )
            ];

            if ($id !== null) {

                $antes =
                    $this->model->buscar(
                        $id,
                        $empresaId
                    );

                if (!$antes) {
                    return [
                        'sucesso' => false,
                        'mensagem' =>
                        'Categoria não encontrada.'
                    ];
                }


                $this->db
                    ->beginTransaction();

                try {

                    $this->model->atualizar(
                        $id,
                        $empresaId,
                        $dados
                    );


                    $depois =
                        $this->model->buscar(
                            $id,
                            $empresaId
                        );

                    if (!$depois) {
                        throw new RuntimeException(
                            'Não foi possível obter a categoria atualizada.'
                        );
                    }


                    $this->auditoria
                        ->atualizacao(
                            $empresaId,
                            $usuarioId,
                            'categorias',
                            $id,
                            $this->dadosAuditoriaCategoria(
                                $antes
                            ),
                            $this->dadosAuditoriaCategoria(
                                $depois
                            )
                        );


                    $this->log
                        ->registrar(
                            $empresaId,
                            $usuarioId,
                            'CATEGORIAS',
                            'EDITAR',
                            'Atualizou a categoria '
                                . $depois['nome']
                                . '.',
                            $id,
                            $this->filialId()
                        );


                    $this->db->commit();


                    return [
                        'sucesso' => true,

                        'mensagem' =>
                        'Categoria atualizada com sucesso.',

                        'dados' => [
                            'id' => $id
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

                    throw $e;
                }
            }

            $this->db
                ->beginTransaction();

            try {

                $novoId =
                    $this->model->criar(
                        $empresaId,
                        $dados
                    );


                $nova =
                    $this->model->buscar(
                        $novoId,
                        $empresaId
                    );

                if (!$nova) {
                    throw new RuntimeException(
                        'Não foi possível obter a categoria criada.'
                    );
                }


                $this->auditoria
                    ->insercao(
                        $empresaId,
                        $usuarioId,
                        'categorias',
                        $novoId,
                        $this->dadosAuditoriaCategoria(
                            $nova
                        )
                    );


                $this->log
                    ->registrar(
                        $empresaId,
                        $usuarioId,
                        'CATEGORIAS',
                        'CRIAR',
                        'Criou a categoria '
                            . $nova['nome']
                            . '.',
                        $novoId,
                        $this->filialId()
                    );


                $this->db->commit();


                return [
                    'sucesso' => true,

                    'mensagem' =>
                    'Categoria criada com sucesso.',

                    'dados' => [
                        'id' => $novoId
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

                throw $e;
            }
        } catch (PDOException $e) {
            return $this->erroBanco($e);
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function eliminar(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            $id =
                $this->inteiroObrigatorio(
                    $_POST['id']
                        ?? null,
                    'Categoria inválida.'
                );

            $categoria =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$categoria) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Categoria não encontrada.'
                ];
            }

            if (
                $this->model->possuiFilhas(
                    $id,
                    $empresaId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Esta categoria possui subcategorias e não pode ser eliminada.'
                ];
            }

            $usuarioId =
                $this->usuarioId();

            $antes =
                $this->dadosAuditoriaCategoria(
                    $categoria
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
                        'Não foi possível eliminar a categoria.'
                    );
                }


                $this->auditoria
                    ->exclusao(
                        $empresaId,
                        $usuarioId,
                        'categorias',
                        $id,
                        $antes
                    );


                $this->log
                    ->registrar(
                        $empresaId,
                        $usuarioId,
                        'CATEGORIAS',
                        'ELIMINAR',
                        'Eliminou a categoria '
                            . $categoria['nome']
                            . '.',
                        $id,
                        $this->filialId()
                    );


                $this->db->commit();


                return [
                    'sucesso' => true,
                    'mensagem' =>
                    'Categoria eliminada com sucesso.'
                ];
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
            if (
                (string) $e->getCode()
                === '23000'
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A categoria possui produtos ou outros registos associados.'
                ];
            }

            return $this->erroBanco($e);
        } catch (Throwable $e) {
            return $this->erro($e);
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

    private function textoLivreOpcional(
        mixed $valor
    ): ?string {
        $texto =
            trim((string) $valor);

        return $texto === ''
            ? null
            : $texto;
    }

    private function erroBanco(
        PDOException $e
    ): array {
        error_log(
            sprintf(
                '[CategoriaController] %s em %s:%d',
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
                'uq_categoria_empresa_nome'
            )
            || str_contains(
                $mensagem,
                'duplicate'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Já existe uma categoria com este nome.'
            ];
        }

        return [
            'sucesso' => false,
            'mensagem' =>
            'Não foi possível processar os dados da categoria.'
        ];
    }

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[CategoriaController] %s em %s:%d',
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
                : 'Ocorreu um erro interno ao processar a categoria.'
        ];
    }
}
