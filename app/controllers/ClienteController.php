<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Cliente.php';

require_once __DIR__
    . '/../services/AuditoriaService.php';

require_once __DIR__
    . '/../services/LogService.php';

final class ClienteController
{
    private PDO $db;

    private Cliente $model;

    private AuditoriaService $auditoria;

    private LogService $log;


    public function __construct()
    {
        $this->db =
            Database::conn();

        $this->model =
            new Cliente(
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
                    'Cliente inválido.'
                );

            $cliente =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$cliente) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Cliente não encontrado.'
                ];
            }

            return [
                'sucesso' => true,
                'dados' => $cliente
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

            $clienteAntes =
                null;

            if ($id !== null) {

                $clienteAntes =
                    $this->model->buscar(
                        $id,
                        $empresaId
                    );

                if (!$clienteAntes) {

                    return [
                        'sucesso' => false,
                        'mensagem' =>
                        'Cliente não encontrado.'
                    ];
                }
            }

            $nome =
                $this->textoObrigatorio(
                    $_POST['nome']
                        ?? null,
                    'Informe o nome do cliente.',
                    150
                );

            $statusId =
                $this->inteiroObrigatorio(
                    $_POST['status_id']
                        ?? null,
                    'Selecione o estado do cliente.'
                );

            if (
                !$this->model->statusExiste(
                    $statusId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O estado selecionado não pertence aos clientes.'
                ];
            }

            $tipo =
                strtoupper(
                    trim(
                        (string) (
                            $_POST['tipo']
                            ?? 'PARTICULAR'
                        )
                    )
                );

            if (
                !in_array(
                    $tipo,
                    [
                        'PARTICULAR',
                        'EMPRESA'
                    ],
                    true
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O tipo de cliente é inválido.'
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
                    'Já existe um cliente com este NUIT.'
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
                    'Já existe um cliente com este email.'
                ];
            }

            $limiteCredito =
                $this->decimalNaoNegativo(
                    $_POST['limite_credito']
                        ?? 0,
                    'O limite de crédito é inválido.'
                );

            $dados = [
                'status_id' =>
                $statusId,

                'tipo' =>
                $tipo,

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

                'contacto' =>
                $this->textoOpcional(
                    $_POST['contacto']
                        ?? null,
                    150
                ),

                'endereco' =>
                $this->textoOpcional(
                    $_POST['endereco']
                        ?? null,
                    255
                ),

                'bairro' =>
                $this->textoOpcional(
                    $_POST['bairro']
                        ?? null,
                    120
                ),

                'cidade' =>
                $this->textoOpcional(
                    $_POST['cidade']
                        ?? null,
                    100
                ),

                'provincia' =>
                $this->textoOpcional(
                    $_POST['provincia']
                        ?? null,
                    100
                ),

                'pais' =>
                $this->textoOpcional(
                    $_POST['pais']
                        ?? 'Moçambique',
                    100
                ) ?? 'Moçambique',

                'limite_credito' =>
                $limiteCredito,

                'observacoes' =>
                $this->textoLivreOpcional(
                    $_POST['observacoes']
                        ?? null
                )
            ];

            if ($id !== null) {

                $this->db->beginTransaction();

                try {

                    $atualizado =
                        $this->model->atualizar(
                            $id,
                            $empresaId,
                            $dados
                        );

                    if (!$atualizado) {
                        throw new RuntimeException(
                            'Não foi possível atualizar o cliente.'
                        );
                    }

                    $clienteDepois =
                        $this->model->buscar(
                            $id,
                            $empresaId
                        );

                    if (!$clienteDepois) {
                        throw new RuntimeException(
                            'Não foi possível obter o cliente atualizado.'
                        );
                    }

                    $this->auditoria
                        ->atualizacao(
                            $empresaId,
                            $usuarioId,
                            'clientes',
                            $id,
                            $this->dadosAuditoriaCliente(
                                $clienteAntes
                            ),
                            $this->dadosAuditoriaCliente(
                                $clienteDepois
                            )
                        );

                    $this->log
                        ->registrar(
                            $empresaId,
                            $usuarioId,
                            'CLIENTES',
                            'EDITAR',
                            'Atualizou o cliente '
                                . $clienteDepois['nome']
                                . '.',
                            $id,
                            $this->filialId()
                        );

                    $this->db->commit();

                    return [
                        'sucesso' => true,

                        'mensagem' =>
                        'Cliente atualizado com sucesso.',

                        'dados' => [
                            'id' => $id
                        ]
                    ];
                } catch (Throwable $e) {

                    if ($this->db->inTransaction()) {
                        $this->db->rollBack();
                    }

                    throw $e;
                }
            }

            $this->db->beginTransaction();

            try {

                $novoId =
                    $this->model->criar(
                        $empresaId,
                        $dados
                    );

                $clienteNovo =
                    $this->model->buscar(
                        $novoId,
                        $empresaId
                    );

                if (!$clienteNovo) {
                    throw new RuntimeException(
                        'Não foi possível obter o cliente criado.'
                    );
                }

                $this->auditoria
                    ->insercao(
                        $empresaId,
                        $usuarioId,
                        'clientes',
                        $novoId,
                        $this->dadosAuditoriaCliente(
                            $clienteNovo
                        )
                    );

                $this->log
                    ->registrar(
                        $empresaId,
                        $usuarioId,
                        'CLIENTES',
                        'CRIAR',
                        'Criou o cliente '
                            . $clienteNovo['nome']
                            . '.',
                        $novoId,
                        $this->filialId()
                    );

                $this->db->commit();

                return [
                    'sucesso' => true,

                    'mensagem' =>
                    'Cliente criado com sucesso.',

                    'dados' => [
                        'id' => $novoId
                    ]
                ];
            } catch (Throwable $e) {

                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }

                throw $e;
            }
        } catch (PDOException $e) {
            return $this->erroBanco($e);
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    // Adicionar em ClienteController.php

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

            $servico = new ClienteImportacaoService($this->db);
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

            $servico = new ClienteImportacaoService($this->db);
            $resultado = $servico->confirmar($token, $empresaId, $usuarioId);

            foreach ($resultado['produtos'] as $item) {
                $clienteId = (int)($item['id'] ?? 0);
                if ($clienteId <= 0) continue;

                $clienteCriado = $this->model->buscar($clienteId, $empresaId);

                $this->auditoria->insercao(
                    $empresaId,
                    $usuarioId,
                    'clientes',
                    $clienteId,
                    $this->dadosAuditoriaCliente($clienteCriado)
                );

                $this->log->registrar(
                    $empresaId,
                    $usuarioId,
                    'CLIENTES',
                    'CRIAR',
                    'Cliente importado através de ficheiro Excel: ' . ($item['nome'] ?? ''),
                    $clienteId,
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

            $servico = new ClienteImportacaoService($this->db);
            $spreadsheet = $servico->gerarModeloExcel($empresaId);

            if (ob_get_length()) {
                ob_end_clean();
            }

            $nomeArquivo = 'modelo_importacao_clientes_' . date('Ymd_His') . '.xlsx';

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

    public function eliminar(): array
    {
        try {

            $empresaId =
                $this->empresaId();

            $usuarioId =
                $this->usuarioId();

            $id =
                $this->inteiroObrigatorio(
                    $_POST['id'] ?? null,
                    'Cliente inválido.'
                );

            $cliente =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$cliente) {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Cliente não encontrado.'
                ];
            }

            $antes =
                $this->dadosAuditoriaCliente(
                    $cliente
                );

            $nome =
                (string)$cliente['nome'];

            $this->db->beginTransaction();

            try {

                $eliminado =
                    $this->model->eliminar(
                        $id,
                        $empresaId
                    );

                if (!$eliminado) {
                    throw new RuntimeException(
                        'Não foi possível eliminar o cliente.'
                    );
                }

                $this->auditoria
                    ->exclusao(
                        $empresaId,
                        $usuarioId,
                        'clientes',
                        $id,
                        $antes
                    );

                $this->log
                    ->registrar(
                        $empresaId,
                        $usuarioId,
                        'CLIENTES',
                        'ELIMINAR',
                        'Eliminou o cliente '
                            . $nome
                            . '.',
                        $id,
                        $this->filialId()
                    );

                $this->db->commit();

                return [
                    'sucesso' => true,
                    'mensagem' =>
                    'Cliente eliminado com sucesso.'
                ];
            } catch (Throwable $e) {

                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }

                throw $e;
            }
        } catch (PDOException $e) {

            if ((string)$e->getCode() === '23000') {

                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O cliente possui registos associados e não pode ser eliminado.'
                ];
            }

            return $this->erroBanco($e);
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


    private function dadosAuditoriaCliente(
        ?array $cliente
    ): array {

        if (!$cliente) {
            return [];
        }

        return [
            'id' =>
            isset($cliente['id'])
                ? (int)$cliente['id']
                : null,

            'empresa_id' =>
            isset($cliente['empresa_id'])
                ? (int)$cliente['empresa_id']
                : null,

            'status_id' =>
            isset($cliente['status_id'])
                ? (int)$cliente['status_id']
                : null,

            'tipo' =>
            $cliente['tipo'] ?? null,

            'nome' =>
            $cliente['nome'] ?? null,

            'nome_comercial' =>
            $cliente['nome_comercial'] ?? null,

            'nuit' =>
            $cliente['nuit'] ?? null,

            'email' =>
            $cliente['email'] ?? null,

            'telefone' =>
            $cliente['telefone'] ?? null,

            'contacto' =>
            $cliente['contacto'] ?? null,

            'endereco' =>
            $cliente['endereco'] ?? null,

            'bairro' =>
            $cliente['bairro'] ?? null,

            'cidade' =>
            $cliente['cidade'] ?? null,

            'provincia' =>
            $cliente['provincia'] ?? null,

            'pais' =>
            $cliente['pais'] ?? null,

            'limite_credito' =>
            $cliente['limite_credito'] ?? null,

            'observacoes' =>
            $cliente['observacoes'] ?? null
        ];
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

    private function emailOpcional(
        mixed $valor
    ): ?string {
        $email =
            trim((string) $valor);

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

        return mb_strtolower($email);
    }

    private function decimalNaoNegativo(
        mixed $valor,
        string $mensagem
    ): string {
        $texto =
            str_replace(
                ',',
                '.',
                trim((string) $valor)
            );

        if (
            $texto === ''
            || !is_numeric($texto)
            || (float) $texto < 0
        ) {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return number_format(
            (float) $texto,
            2,
            '.',
            ''
        );
    }

    private function erroBanco(
        PDOException $e
    ): array {
        error_log(
            sprintf(
                '[ClienteController] %s em %s:%d',
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
                'uq_cliente_nuit_empresa'
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
                'Já existe um cliente com este NUIT.'
            ];
        }

        if (
            str_contains(
                $mensagem,
                'uq_cliente_email_empresa'
            )
            || (
                str_contains(
                    $mensagem,
                    'duplicate'
                )
                && str_contains(
                    $mensagem,
                    'email'
                )
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Já existe um cliente com este email.'
            ];
        }

        return [
            'sucesso' => false,
            'mensagem' =>
            'Não foi possível processar os dados do cliente.'
        ];
    }

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[ClienteController] %s em %s:%d',
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
                : 'Ocorreu um erro interno ao processar o cliente.'
        ];
    }
}
