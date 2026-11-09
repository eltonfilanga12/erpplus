<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Auth.php';

require_once __DIR__
    . '/../models/Marca.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class MarcaController
{
    private PDO $db;

    private Marca $model;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct()
    {
        $this->db =
            Database::conn();

        $this->model =
            new Marca(
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


    public function listar(): array
    {
        return [
            'sucesso' => true,

            'dados' =>
            $this->model->listar(
                $this->empresaId()
            )
        ];
    }


    public function buscar(): array
    {
        $empresaId =
            $this->empresaId();

        $id =
            (int)(
                $_GET['id']
                ?? 0
            );

        if ($id <= 0) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Marca inválida.'
            ];
        }

        $marca =
            $this->model->buscarPorId(
                $id,
                $empresaId
            );

        if (!$marca) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Marca não encontrada.'
            ];
        }

        return [
            'sucesso' => true,
            'dados' => $marca
        ];
    }

    // Adicionar dentro da classe MarcaController em MarcaController.php

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

            $servico = new MarcaImportacaoService($this->db);
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

            $servico = new MarcaImportacaoService($this->db);
            $resultado = $servico->confirmar($token, $empresaId, $usuarioId);

            foreach ($resultado['produtos'] as $item) {
                $marcaId = (int)($item['id'] ?? 0);
                if ($marcaId <= 0) continue;

                $marcaCriada = $this->model->buscarPorId($marcaId, $empresaId);

                $this->auditoria->insercao(
                    $empresaId,
                    $usuarioId,
                    'marcas',
                    $marcaId,
                    $this->dadosAuditoriaMarca($marcaCriada)
                );

                $this->log->registrar(
                    $empresaId,
                    $usuarioId,
                    'MARCAS',
                    'CRIAR',
                    'Marca importada através de ficheiro Excel: ' . ($item['nome'] ?? ''),
                    $marcaId,
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

            $servico = new MarcaImportacaoService($this->db);
            $spreadsheet = $servico->gerarModeloExcel($empresaId);

            if (ob_get_length()) {
                ob_end_clean();
            }

            $nomeArquivo = 'modelo_importacao_marcas_' . date('Ymd_His') . '.xlsx';

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

    public function status(): array
    {
        return [
            'sucesso' => true,

            'dados' =>
            $this->model->listarStatus()
        ];
    }


    public function guardar(): array
    {
        $empresaId =
            $this->empresaId();

        $usuarioId =
            $this->usuarioId();

        $id =
            (int)(
                $_POST['id']
                ?? 0
            );

        $nome =
            trim(
                (string)(
                    $_POST['nome']
                    ?? ''
                )
            );

        $descricao =
            trim(
                (string)(
                    $_POST['descricao']
                    ?? ''
                )
            );

        $statusId =
            (int)(
                $_POST['status_id']
                ?? 0
            );


        if ($nome === '') {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Informe o nome da marca.'
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
                'O estado selecionado não é válido para marcas.'
            ];
        }


        if (
            $this->model->nomeExiste(
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
                'Já existe uma marca com este nome.'
            ];
        }


        $dados = [
            'nome' =>
            $nome,

            'descricao' =>
            $descricao !== ''
                ? $descricao
                : null,

            'status_id' =>
            $statusId
        ];


        // =========================================
        // EDITAR
        // =========================================

        if ($id > 0) {

            $antes =
                $this->model->buscarPorId(
                    $id,
                    $empresaId
                );

            if (!$antes) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Marca não encontrada.'
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
                    $this->model->buscarPorId(
                        $id,
                        $empresaId
                    );

                if (!$depois) {
                    throw new RuntimeException(
                        'Não foi possível obter a marca atualizada.'
                    );
                }


                $this->auditoria
                    ->atualizacao(
                        $empresaId,
                        $usuarioId,
                        'marcas',
                        $id,
                        $this->dadosAuditoriaMarca(
                            $antes
                        ),
                        $this->dadosAuditoriaMarca(
                            $depois
                        )
                    );


                $this->log
                    ->registrar(
                        $empresaId,
                        $usuarioId,
                        'MARCAS',
                        'EDITAR',
                        'Atualizou a marca '
                            . $depois['nome']
                            . '.',
                        $id,
                        $this->filialId()
                    );


                $this->db->commit();


                return [
                    'sucesso' => true,
                    'mensagem' =>
                    'Marca atualizada com sucesso.'
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


        // =========================================
        // CRIAR
        // =========================================

        $this->db
            ->beginTransaction();

        try {

            $novoId =
                $this->model->criar(
                    $empresaId,
                    $dados
                );


            $nova =
                $this->model->buscarPorId(
                    $novoId,
                    $empresaId
                );

            if (!$nova) {
                throw new RuntimeException(
                    'Não foi possível obter a marca criada.'
                );
            }


            $this->auditoria
                ->insercao(
                    $empresaId,
                    $usuarioId,
                    'marcas',
                    $novoId,
                    $this->dadosAuditoriaMarca(
                        $nova
                    )
                );


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'MARCAS',
                    'CRIAR',
                    'Criou a marca '
                        . $nova['nome']
                        . '.',
                    $novoId,
                    $this->filialId()
                );


            $this->db->commit();


            return [
                'sucesso' => true,

                'mensagem' =>
                'Marca criada com sucesso.',

                'id' =>
                $novoId
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


    public function eliminar(): array
    {
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
                'Marca inválida.'
            ];
        }


        $marca =
            $this->model->buscarPorId(
                $id,
                $empresaId
            );

        if (!$marca) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Marca não encontrada.'
            ];
        }


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
                    'Não foi possível eliminar a marca.'
                );
            }


            $this->auditoria
                ->exclusao(
                    $empresaId,
                    $usuarioId,
                    'marcas',
                    $id,
                    $this->dadosAuditoriaMarca(
                        $marca
                    )
                );


            $this->log
                ->registrar(
                    $empresaId,
                    $usuarioId,
                    'MARCAS',
                    'ELIMINAR',
                    'Eliminou a marca '
                        . $marca['nome']
                        . '.',
                    $id,
                    $this->filialId()
                );


            $this->db->commit();


            return [
                'sucesso' => true,
                'mensagem' =>
                'Marca eliminada com sucesso.'
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
                    'Esta marca está associada a outros registos e não pode ser eliminada.'
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
    }


    private function dadosAuditoriaMarca(
        array $marca
    ): array {

        return [
            'id' =>
            (int)(
                $marca['id']
                ?? 0
            ),

            'empresa_id' =>
            (int)(
                $marca['empresa_id']
                ?? 0
            ),

            'status_id' =>
            isset($marca['status_id'])
                ? (int)$marca['status_id']
                : null,

            'nome' =>
            $marca['nome']
                ?? null,

            'descricao' =>
            $marca['descricao']
                ?? null
        ];
    }
}
