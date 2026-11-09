<?php

declare(strict_types=1);

final class FilialController
{
    private Filial $model;

    public function __construct()
    {
        $this->model =
            new Filial(
                Database::conn()
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Listar
    |--------------------------------------------------------------------------
    */

    public function listar(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            return [
                'sucesso' => true,
                'mensagem' =>
                'Filiais carregadas com sucesso.',
                'dados' =>
                $this->model->listar(
                    $empresaId
                )
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar
    |--------------------------------------------------------------------------
    */

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
                    'Filial inválida.'
                );

            $filial =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$filial) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Filial não encontrada.'
                ];
            }

            return [
                'sucesso' => true,
                'dados' => $filial
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    public function salvar(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            $id =
                $this->inteiroOpcional(
                    $_POST['id']
                        ?? null
                );

            if ($id !== null) {
                $filialAtual =
                    $this->model->buscar(
                        $id,
                        $empresaId
                    );

                if (!$filialAtual) {
                    return [
                        'sucesso' => false,
                        'mensagem' =>
                        'Filial não encontrada.'
                    ];
                }
            }

            $nome =
                $this->textoObrigatorio(
                    $_POST['nome']
                        ?? null,
                    'Informe o nome da filial.',
                    150
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
                    'Já existe uma filial com este nome.'
                ];
            }

            $codigo =
                $this->textoOpcional(
                    $_POST['codigo']
                        ?? null,
                    30
                );

            if (
                $codigo !== null
                && $this->model->codigoExiste(
                    $empresaId,
                    $codigo,
                    $id
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Já existe uma filial com este código.'
                ];
            }

            $statusId =
                $this->inteiroObrigatorio(
                    $_POST['status_id']
                        ?? null,
                    'Selecione o estado da filial.'
                );

            if (
                !$this->model->statusExiste(
                    $statusId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O estado selecionado não pertence às filiais.'
                ];
            }

            $dados = [
                'status_id' =>
                $statusId,

                'nome' =>
                $nome,

                'codigo' =>
                $codigo,

                'email' =>
                $this->emailOpcional(
                    $_POST['email']
                        ?? null
                ),

                'telefone' =>
                $this->textoOpcional(
                    $_POST['telefone']
                        ?? null,
                    30
                ),

                'provincia' =>
                $this->textoOpcional(
                    $_POST['provincia']
                        ?? null,
                    100
                ),

                'cidade' =>
                $this->textoOpcional(
                    $_POST['cidade']
                        ?? null,
                    100
                ),

                'bairro' =>
                $this->textoOpcional(
                    $_POST['bairro']
                        ?? null,
                    100
                ),

                'endereco' =>
                $this->textoOpcional(
                    $_POST['endereco']
                        ?? null,
                    255
                ),

                'gerente' =>
                $this->textoOpcional(
                    $_POST['gerente']
                        ?? null,
                    150
                )
            ];

            if ($id !== null) {
                $this->model->atualizar(
                    $id,
                    $empresaId,
                    $dados
                );

                return [
                    'sucesso' => true,
                    'mensagem' =>
                    'Filial atualizada com sucesso.',
                    'dados' => [
                        'id' => $id
                    ]
                ];
            }

            $novoId =
                $this->model->criar(
                    $empresaId,
                    $dados
                );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Filial criada com sucesso.',
                'dados' => [
                    'id' => $novoId
                ]
            ];
        } catch (PDOException $e) {
            return $this->erroBanco(
                $e
            );
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function eliminar(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            $id =
                $this->inteiroObrigatorio(
                    $_POST['id']
                        ?? null,
                    'Filial inválida.'
                );

            $filial =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$filial) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Filial não encontrada.'
                ];
            }

            $this->model->eliminar(
                $id,
                $empresaId
            );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Filial eliminada com sucesso.'
            ];
        } catch (PDOException $e) {
            if (
                (string) $e->getCode()
                === '23000'
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A filial possui registos associados e não pode ser eliminada.'
                ];
            }

            return $this->erroBanco($e);
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Empresa da sessão
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Validações
    |--------------------------------------------------------------------------
    */

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
            trim(
                (string) $valor
            );

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
            trim(
                (string) $valor
            );

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

    private function emailOpcional(
        mixed $valor
    ): ?string {
        $email =
            trim(
                (string) $valor
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

    /*
    |--------------------------------------------------------------------------
    | Erros
    |--------------------------------------------------------------------------
    */

    private function erroBanco(
        PDOException $e
    ): array {
        error_log(
            sprintf(
                '[FilialController] %s em %s:%d',
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
            || str_contains(
                $mensagem,
                'codigo'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Já existe uma filial com este código.'
            ];
        }

        return [
            'sucesso' => false,
            'mensagem' =>
            'Não foi possível guardar os dados da filial.'
        ];
    }

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[FilialController] %s em %s:%d',
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
                : 'Ocorreu um erro interno ao processar a filial.'
        ];
    }
}
