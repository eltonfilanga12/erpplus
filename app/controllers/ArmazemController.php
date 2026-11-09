<?php

declare(strict_types=1);

final class ArmazemController
{
    private Armazem $model;

    public function __construct()
    {
        $this->model =
            new Armazem(
                Database::conn()
            );
    }

    public function listar(): array
    {
        try {
            $empresaId =
                $this->empresaId();

            return [
                'sucesso' => true,
                'dados' =>
                $this->model->listar(
                    $empresaId
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
                    'Armazém inválido.'
                );

            $armazem =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$armazem) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Armazém não encontrado.'
                ];
            }

            return [
                'sucesso' => true,
                'dados' => $armazem
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    public function filiais(): array
    {
        try {
            return [
                'sucesso' => true,
                'dados' =>
                $this->model->listarFiliais(
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
                $this->model->listarStatus()
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
                    'Armazém não encontrado.'
                ];
            }

            $nome =
                $this->textoObrigatorio(
                    $_POST['nome']
                        ?? null,
                    'Informe o nome do armazém.',
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
                    'Já existe um armazém com este nome.'
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
                    'Já existe um armazém com este código.'
                ];
            }

            $filialId =
                $this->inteiroOpcional(
                    $_POST['filial_id']
                        ?? null
                );

            if (
                $filialId !== null
                && !$this->model
                    ->filialPertenceEmpresa(
                        $filialId,
                        $empresaId
                    )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A filial selecionada não pertence à empresa.'
                ];
            }

            $statusId =
                $this->inteiroObrigatorio(
                    $_POST['status_id']
                        ?? null,
                    'Selecione o estado do armazém.'
                );

            if (
                !$this->model->statusExiste(
                    $statusId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O estado selecionado não pertence aos armazéns.'
                ];
            }

            $dados = [
                'filial_id' =>
                $filialId,

                'status_id' =>
                $statusId,

                'nome' =>
                $nome,

                'codigo' =>
                $codigo,

                'endereco' =>
                $this->textoOpcional(
                    $_POST['endereco']
                        ?? null,
                    255
                ),

                'responsavel' =>
                $this->textoOpcional(
                    $_POST['responsavel']
                        ?? null,
                    150
                ),

                'telefone' =>
                $this->textoOpcional(
                    $_POST['telefone']
                        ?? null,
                    30
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
                    'Armazém atualizado com sucesso.',
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
                'Armazém criado com sucesso.',
                'dados' => [
                    'id' => $novoId
                ]
            ];
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
                    'Armazém inválido.'
                );

            $armazem =
                $this->model->buscar(
                    $id,
                    $empresaId
                );

            if (!$armazem) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Armazém não encontrado.'
                ];
            }

            $this->model->eliminar(
                $id,
                $empresaId
            );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Armazém eliminado com sucesso.'
            ];
        } catch (PDOException $e) {
            if (
                (string) $e->getCode()
                === '23000'
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O armazém possui registos associados e não pode ser eliminado.'
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

    private function erroBanco(
        PDOException $e
    ): array {
        error_log(
            sprintf(
                '[ArmazemController] %s em %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            )
        );

        if (
            str_contains(
                mb_strtolower($e->getMessage()),
                'duplicate'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Já existe um armazém com este código.'
            ];
        }

        return [
            'sucesso' => false,
            'mensagem' =>
            'Não foi possível processar os dados do armazém.'
        ];
    }

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[ArmazemController] %s em %s:%d',
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
                : 'Ocorreu um erro interno ao processar o armazém.'
        ];
    }
}
