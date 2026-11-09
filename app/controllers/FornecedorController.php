<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Fornecedor.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';


final class FornecedorController
{
    private PDO $db;

    private Fornecedor $model;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct()
    {
        $this->db =
            Database::conn();


        $this->model =
            new Fornecedor(
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
    // LISTAR
    // ==================================================

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

            return $this->erro(
                $e
            );
        }
    }


    // ==================================================
    // BUSCAR
    // ==================================================

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
                    'Fornecedor inválido.'
                );


            $fornecedor =
                $this->model->buscar(
                    $id,
                    $empresaId
                );


            if (!$fornecedor) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Fornecedor não encontrado.'
                ];
            }


            return [
                'sucesso' => true,

                'dados' =>
                $fornecedor
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }

    // Adicionar dentro da classe FornecedorController em FornecedorController.php

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

            $servico = new FornecedorImportacaoService($this->db);
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

            $servico = new FornecedorImportacaoService($this->db);
            $resultado = $servico->confirmar($token, $empresaId, $usuarioId);

            foreach ($resultado['produtos'] as $item) {
                $fornecedorId = (int)($item['id'] ?? 0);
                if ($fornecedorId <= 0) continue;

                $fornecedorCriado = $this->model->buscar($fornecedorId, $empresaId);

                $this->auditoria->insercao(
                    $empresaId,
                    $usuarioId,
                    'fornecedores',
                    $fornecedorId,
                    $this->dadosAuditoriaFornecedor($fornecedorCriado)
                );

                $this->log->registrar(
                    $empresaId,
                    $usuarioId,
                    'FORNECEDORES',
                    'CRIAR',
                    'Fornecedor importado através de ficheiro Excel: ' . ($item['nome'] ?? ''),
                    $fornecedorId,
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

            $servico = new FornecedorImportacaoService($this->db);
            $spreadsheet = $servico->gerarModeloExcel($empresaId);

            if (ob_get_length()) {
                ob_end_clean();
            }

            $nomeArquivo = 'modelo_importacao_fornecedores_' . date('Ymd_His') . '.xlsx';

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
    // EXTRATO
    // ==================================================

    public function extrato(): array
    {
        try {

            $empresaId =
                $this->empresaId();


            $fornecedorId =
                $this->inteiroObrigatorio(
                    $_GET['fornecedor_id']
                        ?? $_POST['fornecedor_id']
                        ?? null,
                    'Fornecedor inválido.'
                );


            $dataInicial =
                trim(
                    (string)(
                        $_GET['data_inicial']
                        ?? $_POST['data_inicial']
                        ?? ''
                    )
                );


            $dataFinal =
                trim(
                    (string)(
                        $_GET['data_final']
                        ?? $_POST['data_final']
                        ?? ''
                    )
                );


            $dataInicial =
                $dataInicial !== ''
                ? $dataInicial
                : null;


            $dataFinal =
                $dataFinal !== ''
                ? $dataFinal
                : null;


            if (
                $dataInicial !== null
                && !preg_match(
                    '/^\d{4}-\d{2}-\d{2}$/',
                    $dataInicial
                )
            ) {
                throw new InvalidArgumentException(
                    'Data inicial inválida.'
                );
            }


            if (
                $dataFinal !== null
                && !preg_match(
                    '/^\d{4}-\d{2}-\d{2}$/',
                    $dataFinal
                )
            ) {
                throw new InvalidArgumentException(
                    'Data final inválida.'
                );
            }


            if (
                $dataInicial !== null
                && $dataFinal !== null
                && $dataInicial > $dataFinal
            ) {
                throw new InvalidArgumentException(
                    'A data inicial não pode ser posterior à data final.'
                );
            }


            return [
                'sucesso' => true,

                'dados' =>
                $this->model->extrato(
                    $fornecedorId,
                    $empresaId,
                    $dataInicial,
                    $dataFinal
                )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
        }
    }


    // ==================================================
    // STATUS
    // ==================================================

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

            return $this->erro(
                $e
            );
        }
    }


    // ==================================================
    // SALVAR
    // ==================================================

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


            /*
             * Se for edição,
             * o fornecedor precisa existir.
             */
            $fornecedorAntes =
                null;


            if ($id !== null) {

                $fornecedorAntes =
                    $this->model->buscar(
                        $id,
                        $empresaId
                    );


                if (!$fornecedorAntes) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Fornecedor não encontrado.'
                    ];
                }
            }


            // ==========================================
            // VALIDAÇÕES
            // ==========================================

            $nome =
                $this->textoObrigatorio(
                    $_POST['nome']
                        ?? null,
                    'Informe o nome do fornecedor.',
                    150
                );


            $statusId =
                $this->inteiroObrigatorio(
                    $_POST['status_id']
                        ?? null,
                    'Selecione o estado do fornecedor.'
                );


            if (
                !$this->model->statusExiste(
                    $statusId
                )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O estado selecionado não pertence aos fornecedores.'
                ];
            }


            $nuit =
                $this->textoOpcional(
                    $_POST['nuit']
                        ?? null,
                    30
                );


            if (
                $nuit !== null
                && $this->model->nuitExiste(
                    $empresaId,
                    $nuit,
                    $id
                )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Já existe um fornecedor com este NUIT.'
                ];
            }


            $email =
                $this->emailOpcional(
                    $_POST['email']
                        ?? null
                );


            if (
                $email !== null
                && $this->model->emailExiste(
                    $empresaId,
                    $email,
                    $id
                )
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Já existe um fornecedor com este email.'
                ];
            }


            $dados = [

                'status_id' =>
                $statusId,

                'nome' =>
                $nome,

                'nome_comercial' =>
                $this->textoOpcional(
                    $_POST['nome_comercial']
                        ?? null,
                    150
                ),

                'nuit' =>
                $nuit,

                'email' =>
                $email,

                'telefone' =>
                $this->textoOpcional(
                    $_POST['telefone']
                        ?? null,
                    30
                ),

                'endereco' =>
                $this->textoOpcional(
                    $_POST['endereco']
                        ?? null,
                    255
                ),

                'cidade' =>
                $this->textoOpcional(
                    $_POST['cidade']
                        ?? null,
                    100
                ),

                'pais' =>
                $this->textoOpcional(
                    $_POST['pais']
                        ?? 'Moçambique',
                    100
                )
                    ?? 'Moçambique',

                'contacto' =>
                $this->textoOpcional(
                    $_POST['contacto']
                        ?? null,
                    150
                ),

                'observacoes' =>
                $this->textoLivreOpcional(
                    $_POST['observacoes']
                        ?? null
                )
            ];


            // ==========================================
            // EDIÇÃO
            // ==========================================

            if ($id !== null) {

                $this->db
                    ->beginTransaction();


                try {

                    $atualizado =
                        $this->model->atualizar(
                            $id,
                            $empresaId,
                            $dados
                        );


                    if (!$atualizado) {
                        throw new RuntimeException(
                            'Não foi possível atualizar o fornecedor.'
                        );
                    }


                    $fornecedorDepois =
                        $this->model->buscar(
                            $id,
                            $empresaId
                        );


                    if (!$fornecedorDepois) {
                        throw new RuntimeException(
                            'Não foi possível obter os dados do fornecedor após a atualização.'
                        );
                    }


                    // ==================================
                    // AUDITORIA
                    // ==================================

                    $this->auditoria
                        ->atualizacao(
                            $empresaId,
                            $usuarioId,
                            'fornecedores',
                            $id,

                            $this
                                ->dadosAuditoriaFornecedor(
                                    $fornecedorAntes
                                ),

                            $this
                                ->dadosAuditoriaFornecedor(
                                    $fornecedorDepois
                                )
                        );


                    // ==================================
                    // LOG
                    // ==================================

                    $this->log
                        ->registrar(
                            $empresaId,
                            $usuarioId,
                            'FORNECEDORES',
                            'EDITAR',

                            'Atualizou o fornecedor '
                                . (
                                    $fornecedorDepois['nome']
                                    ?? $nome
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
                        'Fornecedor atualizado com sucesso.',

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
            // CRIAÇÃO
            // ==========================================

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
                        'Não foi possível criar o fornecedor.'
                    );
                }


                $novoFornecedor =
                    $this->model->buscar(
                        $novoId,
                        $empresaId
                    );


                if (!$novoFornecedor) {
                    throw new RuntimeException(
                        'Não foi possível obter o fornecedor criado.'
                    );
                }


                // ======================================
                // AUDITORIA
                // ======================================

                $this->auditoria
                    ->insercao(
                        $empresaId,
                        $usuarioId,
                        'fornecedores',
                        $novoId,

                        $this
                            ->dadosAuditoriaFornecedor(
                                $novoFornecedor
                            )
                    );


                // ======================================
                // LOG
                // ======================================

                $this->log
                    ->registrar(
                        $empresaId,
                        $usuarioId,
                        'FORNECEDORES',
                        'CRIAR',

                        'Criou o fornecedor '
                            . (
                                $novoFornecedor['nome']
                                ?? $nome
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
                    'Fornecedor criado com sucesso.',

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
                $this->inteiroObrigatorio(
                    $_POST['id']
                        ?? null,
                    'Fornecedor inválido.'
                );


            $fornecedor =
                $this->model->buscar(
                    $id,
                    $empresaId
                );


            if (!$fornecedor) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Fornecedor não encontrado.'
                ];
            }


            $dadosAnteriores =
                $this->dadosAuditoriaFornecedor(
                    $fornecedor
                );


            $nomeFornecedor =
                (string)(
                    $fornecedor['nome']
                    ?? 'Fornecedor'
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
                        'Não foi possível eliminar o fornecedor.'
                    );
                }


                // ======================================
                // AUDITORIA
                // ======================================

                $this->auditoria
                    ->exclusao(
                        $empresaId,
                        $usuarioId,
                        'fornecedores',
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
                        'FORNECEDORES',
                        'ELIMINAR',

                        'Eliminou o fornecedor '
                            . $nomeFornecedor
                            . '.',

                        $id,
                        $this->filialId()
                    );


                $this->db
                    ->commit();


                return [
                    'sucesso' => true,

                    'mensagem' =>
                    'Fornecedor eliminado com sucesso.'
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

            /*
             * Fornecedor relacionado com:
             * compras, contas a pagar,
             * lançamentos contabilísticos, etc.
             */
            if (
                (string)$e->getCode()
                === '23000'
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'O fornecedor possui registos associados e não pode ser eliminado.'
                ];
            }


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

    private function dadosAuditoriaFornecedor(
        ?array $fornecedor
    ): array {

        if (!$fornecedor) {
            return [];
        }


        return [

            'id' =>
            isset($fornecedor['id'])
                ? (int)$fornecedor['id']
                : null,

            'empresa_id' =>
            isset($fornecedor['empresa_id'])
                ? (int)$fornecedor['empresa_id']
                : null,

            'status_id' =>
            isset($fornecedor['status_id'])
                ? (int)$fornecedor['status_id']
                : null,

            'nome' =>
            $fornecedor['nome']
                ?? null,

            'nome_comercial' =>
            $fornecedor['nome_comercial']
                ?? null,

            'nuit' =>
            $fornecedor['nuit']
                ?? null,

            'email' =>
            $fornecedor['email']
                ?? null,

            'telefone' =>
            $fornecedor['telefone']
                ?? null,

            'endereco' =>
            $fornecedor['endereco']
                ?? null,

            'cidade' =>
            $fornecedor['cidade']
                ?? null,

            'pais' =>
            $fornecedor['pais']
                ?? null,

            'contacto' =>
            $fornecedor['contacto']
                ?? null,

            'observacoes' =>
            $fornecedor['observacoes']
                ?? null
        ];
    }


    // ==================================================
    // EMPRESA
    // ==================================================

    private function empresaId(): int
    {
        $empresaId =
            $_SESSION['empresa_id']
            ?? $_SESSION['usuario']['empresa_id']
            ?? $_SESSION['user']['empresa_id']
            ?? null;


        if (
            $empresaId === null
            || !is_numeric(
                $empresaId
            )
            || (int)$empresaId <= 0
        ) {

            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }


        return (int)$empresaId;
    }


    // ==================================================
    // UTILIZADOR
    // ==================================================

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
            || !is_numeric(
                $usuarioId
            )
            || (int)$usuarioId <= 0
        ) {

            throw new RuntimeException(
                'Utilizador não identificado na sessão.'
            );
        }


        return (int)$usuarioId;
    }


    // ==================================================
    // FILIAL
    // ==================================================

    private function filialId(): ?int
    {
        $filialId =
            $_SESSION['filial_id']
            ?? $_SESSION['usuario']['filial_id']
            ?? $_SESSION['user']['filial_id']
            ?? null;


        if (
            $filialId === null
            || !is_numeric(
                $filialId
            )
            || (int)$filialId <= 0
        ) {
            return null;
        }


        return (int)$filialId;
    }


    // ==================================================
    // HELPERS
    // ==================================================

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


        return (int)$numero;
    }


    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || trim(
                (string)$valor
            ) === ''
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
            trim(
                (string)$valor
            );


        if ($texto === '') {

            throw new InvalidArgumentException(
                $mensagem
            );
        }


        if (
            mb_strlen(
                $texto
            ) > $limite
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
            trim(
                (string)$valor
            );


        if ($texto === '') {
            return null;
        }


        if (
            mb_strlen(
                $texto
            ) > $limite
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
            trim(
                (string)$valor
            );


        return $texto === ''
            ? null
            : $texto;
    }


    private function emailOpcional(
        mixed $valor
    ): ?string {

        $email =
            trim(
                (string)$valor
            );


        if ($email === '') {
            return null;
        }


        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            throw new InvalidArgumentException(
                'Informe um email válido.'
            );
        }


        return mb_strtolower(
            $email
        );
    }


    // ==================================================
    // ERRO DE BANCO
    // ==================================================

    private function erroBanco(
        PDOException $e
    ): array {

        error_log(
            sprintf(
                '[FornecedorController] %s em %s:%d',
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
                'uq_fornecedor_nuit_empresa'
            )
            || (
                str_contains(
                    $mensagem,
                    'duplicate'
                )
                && str_contains(
                    $mensagem,
                    'nuit'
                )
            )
        ) {

            return [
                'sucesso' => false,

                'mensagem' =>
                'Já existe um fornecedor com este NUIT.'
            ];
        }


        if (
            str_contains(
                $mensagem,
                'duplicate'
            )
            && str_contains(
                $mensagem,
                'email'
            )
        ) {

            return [
                'sucesso' => false,

                'mensagem' =>
                'Já existe um fornecedor com este email.'
            ];
        }


        return [
            'sucesso' => false,

            'mensagem' =>
            'Não foi possível processar os dados do fornecedor.'
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
                '[FornecedorController] %s em %s:%d',
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
                : 'Ocorreu um erro interno ao processar o fornecedor.'
        ];
    }
}
