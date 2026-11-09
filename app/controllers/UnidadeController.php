<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../core/Database.php';

require_once __DIR__
    . '/../models/Unidade.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class UnidadeController
{
    private PDO $db;

    private Unidade $model;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct()
    {
        $this->db =
            Database::conn();


        $this->model =
            new Unidade(
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


    // ==================================================
    // EMPRESA
    // ==================================================

    private function empresaId(): int
    {
        $empresaId =
            (int)(
                Auth::empresaId()
                ?? 0
            );


        if ($empresaId <= 0) {

            throw new RuntimeException(
                'Não foi possível identificar a empresa do utilizador.'
            );
        }


        return $empresaId;
    }


    // ==================================================
    // UTILIZADOR
    // ==================================================

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


    // ==================================================
    // FILIAL
    // ==================================================

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


    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(): array
    {
        $empresaId =
            $this->empresaId();


        return [
            'sucesso' => true,

            'dados' =>
            $this->model->listar(
                $empresaId
            )
        ];
    }

    // Adicionar dentro da classe UnidadeController em UnidadeController.php

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

            $servico = new UnidadeImportacaoService($this->db);
            $resultado = $servico->validarArquivo($_FILES['ficheiro'], $empresaId, $usuarioId);

            return [
                'sucesso' => true,
                'mensagem' => 'Ficheiro validado com sucesso.',
                'dados' => $resultado
            ];
        } catch (Throwable $e) {
            return [
                'sucesso' => false,
                'mensagem' => $e->getMessage()
            ];
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

            $servico = new UnidadeImportacaoService($this->db);
            $resultado = $servico->confirmar($token, $empresaId, $usuarioId);

            foreach ($resultado['produtos'] as $item) {
                $unidadeId = (int)($item['id'] ?? 0);
                if ($unidadeId <= 0) continue;

                $unidadeCriada = $this->model->buscarPorId($unidadeId, $empresaId);

                $this->auditoria->insercao(
                    $empresaId,
                    $usuarioId,
                    'unidades',
                    $unidadeId,
                    $this->dadosAuditoriaUnidade($unidadeCriada)
                );

                $this->log->registrar(
                    $empresaId,
                    $usuarioId,
                    'UNIDADES',
                    'CRIAR',
                    'Unidade importada através de ficheiro Excel: ' . ($item['codigo'] ?? '') . ' - ' . ($item['nome'] ?? ''),
                    $unidadeId,
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

            $servico = new UnidadeImportacaoService($this->db);
            $spreadsheet = $servico->gerarModeloExcel($empresaId);

            if (ob_get_length()) {
                ob_end_clean();
            }

            $nomeArquivo = 'modelo_importacao_unidades_' . date('Ymd_His') . '.xlsx';

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

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(): array
    {
        $empresaId =
            $this->empresaId();


        $id =
            (int)(
                $_GET['id']
                ?? $_POST['id']
                ?? 0
            );


        if ($id <= 0) {

            return [
                'sucesso' => false,

                'mensagem' =>
                'Unidade inválida.'
            ];
        }


        $unidade =
            $this->model->buscarPorId(
                $id,
                $empresaId
            );


        if (!$unidade) {

            return [
                'sucesso' => false,

                'mensagem' =>
                'Unidade não encontrada.'
            ];
        }


        return [
            'sucesso' => true,

            'dados' =>
            $unidade
        ];
    }


    // ==================================================
    // STATUS
    // ==================================================

    public function status(): array
    {
        return [
            'sucesso' => true,

            'dados' =>
            $this->model
                ->listarStatus()
        ];
    }


    // ==================================================
    // GUARDAR
    // ==================================================

    public function guardar(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $usuarioId =
                $this->usuarioId();


            $id =
                (int)(
                    $_POST['id']
                    ?? 0
                );


            // ==========================================
            // CÓDIGO
            // ==========================================

            $codigo =
                strtoupper(
                    trim(
                        (string)(
                            $_POST['codigo']
                            ?? ''
                        )
                    )
                );


            // ==========================================
            // NOME
            // ==========================================

            $nome =
                trim(
                    (string)(
                        $_POST['nome']
                        ?? ''
                    )
                );


            // ==========================================
            // DESCRIÇÃO
            // ==========================================

            $descricao =
                trim(
                    (string)(
                        $_POST['descricao']
                        ?? ''
                    )
                );


            // ==========================================
            // CASAS DECIMAIS
            // ==========================================

            $casasDecimais =
                (int)(
                    $_POST['casas_decimais']
                    ?? 0
                );


            // ==========================================
            // STATUS
            // ==========================================

            $statusId =
                (int)(
                    $_POST['status_id']
                    ?? 0
                );


            // ==========================================
            // VALIDAÇÕES
            // ==========================================

            if ($codigo === '') {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Informe o código da unidade.'
                ];
            }


            if (
                mb_strlen(
                    $codigo
                ) > 30
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O código da unidade não pode exceder 30 caracteres.'
                ];
            }


            if ($nome === '') {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Informe o nome da unidade.'
                ];
            }


            if (
                mb_strlen(
                    $nome
                ) > 120
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O nome da unidade não pode exceder 120 caracteres.'
                ];
            }


            if (
                $casasDecimais < 0
                || $casasDecimais > 6
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'As casas decimais devem estar entre 0 e 6.'
                ];
            }


            if ($statusId <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Selecione o estado.'
                ];
            }


            if (
                !$this->model
                    ->statusExiste(
                        $statusId
                    )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O estado selecionado não é válido para unidades.'
                ];
            }


            // ==========================================
            // DUPLICIDADE DE CÓDIGO
            // ==========================================

            if (
                $this->model
                ->codigoExiste(
                    $codigo,
                    $empresaId,
                    $id > 0
                        ? $id
                        : null
                )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Já existe uma unidade com este código.'
                ];
            }


            // ==========================================
            // DUPLICIDADE DE NOME
            // ==========================================

            if (
                $this->model
                ->nomeExiste(
                    $nome,
                    $empresaId,
                    $id > 0
                        ? $id
                        : null
                )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Já existe uma unidade com este nome.'
                ];
            }


            // ==========================================
            // DADOS
            // ==========================================

            $dados = [

                'codigo' =>
                $codigo,

                'nome' =>
                $nome,

                'casas_decimais' =>
                $casasDecimais,

                'status_id' =>
                $statusId,

                'descricao' =>
                $descricao !== ''
                    ? $descricao
                    : null
            ];


            // ==========================================
            // EDITAR
            // ==========================================

            if ($id > 0) {

                $antes =
                    $this->model
                    ->buscarPorId(
                        $id,
                        $empresaId
                    );


                if (!$antes) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Unidade não encontrada.'
                    ];
                }


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
                            'Não foi possível atualizar a unidade.'
                        );
                    }


                    $depois =
                        $this->model
                        ->buscarPorId(
                            $id,
                            $empresaId
                        );


                    if (!$depois) {

                        throw new RuntimeException(
                            'Não foi possível obter a unidade atualizada.'
                        );
                    }


                    // ==================================
                    // AUDITORIA
                    // ==================================

                    $this->auditoria
                        ->atualizacao(
                            $empresaId,
                            $usuarioId,
                            'unidades',
                            $id,

                            $this
                                ->dadosAuditoriaUnidade(
                                    $antes
                                ),

                            $this
                                ->dadosAuditoriaUnidade(
                                    $depois
                                )
                        );


                    // ==================================
                    // LOG
                    // ==================================

                    $this->log
                        ->registrar(
                            $empresaId,
                            $usuarioId,
                            'UNIDADES',
                            'EDITAR',

                            'Atualizou a unidade '
                                . (
                                    $depois['codigo']
                                    ?? ''
                                )
                                . ' - '
                                . (
                                    $depois['nome']
                                    ?? ''
                                )
                                . '.',

                            $id,
                            $this->filialId()
                        );


                    $this->db
                        ->commit();


                    return [
                        'sucesso' => true,

                        'mensagem' =>
                        'Unidade atualizada com sucesso.',

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


                    throw $e;
                }
            }


            // ==========================================
            // CRIAR
            // ==========================================

            $this->db
                ->beginTransaction();


            try {

                $novoId =
                    $this->model
                    ->criar(
                        $empresaId,
                        $dados
                    );


                if ($novoId <= 0) {

                    throw new RuntimeException(
                        'Não foi possível criar a unidade.'
                    );
                }


                $nova =
                    $this->model
                    ->buscarPorId(
                        $novoId,
                        $empresaId
                    );


                if (!$nova) {

                    throw new RuntimeException(
                        'Não foi possível obter a unidade criada.'
                    );
                }


                // ======================================
                // AUDITORIA
                // ======================================

                $this->auditoria
                    ->insercao(
                        $empresaId,
                        $usuarioId,
                        'unidades',
                        $novoId,

                        $this
                            ->dadosAuditoriaUnidade(
                                $nova
                            )
                    );


                // ======================================
                // LOG
                // ======================================

                $this->log
                    ->registrar(
                        $empresaId,
                        $usuarioId,
                        'UNIDADES',
                        'CRIAR',

                        'Criou a unidade '
                            . (
                                $nova['codigo']
                                ?? ''
                            )
                            . ' - '
                            . (
                                $nova['nome']
                                ?? ''
                            )
                            . '.',

                        $novoId,
                        $this->filialId()
                    );


                $this->db
                    ->commit();


                return [
                    'sucesso' => true,

                    'mensagem' =>
                    'Unidade criada com sucesso.',

                    'id' =>
                    $novoId,

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


    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $usuarioId =
                $this->usuarioId();


            $id =
                (int)(
                    $_POST['id']
                    ?? 0
                );


            if ($id <= 0) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Unidade inválida.'
                ];
            }


            $unidade =
                $this->model
                ->buscarPorId(
                    $id,
                    $empresaId
                );


            if (!$unidade) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Unidade não encontrada.'
                ];
            }


            $dadosAnteriores =
                $this->dadosAuditoriaUnidade(
                    $unidade
                );


            $codigo =
                (string)(
                    $unidade['codigo']
                    ?? ''
                );


            $nome =
                (string)(
                    $unidade['nome']
                    ?? 'Unidade'
                );


            $this->db
                ->beginTransaction();


            try {

                $resultado =
                    $this->model
                    ->eliminar(
                        $id,
                        $empresaId
                    );


                if (!$resultado) {

                    throw new RuntimeException(
                        'Não foi possível eliminar a unidade.'
                    );
                }


                // ======================================
                // AUDITORIA
                // ======================================

                $this->auditoria
                    ->exclusao(
                        $empresaId,
                        $usuarioId,
                        'unidades',
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
                        'UNIDADES',
                        'ELIMINAR',

                        'Eliminou a unidade '
                            . $codigo
                            . ' - '
                            . $nome
                            . '.',

                        $id,
                        $this->filialId()
                    );


                $this->db
                    ->commit();


                return [
                    'sucesso' => true,

                    'mensagem' =>
                    'Unidade eliminada com sucesso.'
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
                        'Esta unidade está associada a outros registos e não pode ser eliminada.'
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


    // ==================================================
    // DADOS PARA AUDITORIA
    // ==================================================

    private function dadosAuditoriaUnidade(
        array $unidade
    ): array {

        return [

            'id' =>
            (int)(
                $unidade['id']
                ?? 0
            ),

            'empresa_id' =>
            (int)(
                $unidade['empresa_id']
                ?? 0
            ),

            'status_id' =>
            isset(
                $unidade['status_id']
            )
                ? (int)$unidade['status_id']
                : null,

            'codigo' =>
            $unidade['codigo']
                ?? null,

            'nome' =>
            $unidade['nome']
                ?? null,

            'casas_decimais' =>
            isset(
                $unidade['casas_decimais']
            )
                ? (int)$unidade['casas_decimais']
                : null,

            'descricao' =>
            $unidade['descricao']
                ?? null
        ];
    }


    // ==================================================
    // ERRO DE BANCO
    // ==================================================

    private function erroBanco(
        PDOException $e
    ): array {

        error_log(
            sprintf(
                '[UnidadeController] %s em %s:%d',
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
                'duplicate'
            )
            && str_contains(
                $mensagem,
                'codigo'
            )
        ) {

            return [
                'sucesso' => false,

                'mensagem' =>
                'Já existe uma unidade com este código.'
            ];
        }


        if (
            str_contains(
                $mensagem,
                'duplicate'
            )
            && str_contains(
                $mensagem,
                'nome'
            )
        ) {

            return [
                'sucesso' => false,

                'mensagem' =>
                'Já existe uma unidade com este nome.'
            ];
        }


        return [
            'sucesso' => false,

            'mensagem' =>
            'Não foi possível processar os dados da unidade.'
        ];
    }


    // ==================================================
    // ERRO GERAL
    // ==================================================

    private function erro(
        Throwable $e
    ): array {

        error_log(
            sprintf(
                '[UnidadeController] %s em %s:%d',
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

                : 'Ocorreu um erro interno ao processar a unidade.'
        ];
    }
}
