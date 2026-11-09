<?php

declare(strict_types=1);

final class PerfilController
{
    private Perfil $model;

    public function __construct()
    {
        $this->model =
            new Perfil();
    }

    /*
    |--------------------------------------------------------------------------
    | Listar
    |--------------------------------------------------------------------------
    */

    public function listar(): array
    {
        try {
            return [
                'sucesso' => true,
                'mensagem' =>
                'Perfis carregados com sucesso.',
                'dados' =>
                $this->model->listar()
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
            $id =
                $this->inteiroObrigatorio(
                    $_GET['id']
                        ?? $_POST['id']
                        ?? null,
                    'Perfil inválido.'
                );

            $perfil =
                $this->model->buscarPorId(
                    $id
                );

            if (!$perfil) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Perfil não encontrado.'
                ];
            }

            return [
                'sucesso' => true,
                'dados' => $perfil
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

    public function guardar(): array
    {
        try {
            $id =
                $this->inteiroOpcional(
                    $_POST['id']
                        ?? null
                );

            $nome =
                $this->textoObrigatorio(
                    $_POST['nome']
                        ?? null,
                    'Informe o nome do perfil.',
                    120
                );

            $descricao =
                $this->textoOpcional(
                    $_POST['descricao']
                        ?? null,
                    500
                );

            $statusId =
                $this->inteiroObrigatorio(
                    $_POST['status_id']
                        ?? null,
                    'Selecione o estado do perfil.'
                );

            if (
                !$this->model->statusExiste(
                    $statusId
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O estado selecionado não pertence aos perfis.'
                ];
            }

            if (
                $this->model->nomeExiste(
                    $nome,
                    $id
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Já existe um perfil com este nome.'
                ];
            }

            $dados = [
                'nome' =>
                $nome,

                'descricao' =>
                $descricao,

                'status_id' =>
                $statusId
            ];

            /*
             * Atualizar
             */
            if ($id !== null) {
                $perfil =
                    $this->model->buscarPorId(
                        $id
                    );

                if (!$perfil) {
                    return [
                        'sucesso' => false,
                        'mensagem' =>
                        'Perfil não encontrado.'
                    ];
                }

                /*
                 * Pode impedir alteração do
                 * Super Administrador.
                 */
                if (
                    $id === 1
                    && mb_strtolower($nome)
                    !== mb_strtolower(
                        (string) $perfil['nome']
                    )
                ) {
                    return [
                        'sucesso' => false,
                        'mensagem' =>
                        'O nome do perfil principal não pode ser alterado.'
                    ];
                }

                $this->model->atualizar(
                    $id,
                    $dados
                );

                return [
                    'sucesso' => true,
                    'mensagem' =>
                    'Perfil atualizado com sucesso.',
                    'id' => $id
                ];
            }

            /*
             * Criar
             */
            $novoId =
                $this->model->criar(
                    $dados
                );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Perfil criado com sucesso.',
                'id' => $novoId
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
            $id =
                $this->inteiroObrigatorio(
                    $_POST['id']
                        ?? null,
                    'Perfil inválido.'
                );

            $perfil =
                $this->model->buscarPorId(
                    $id
                );

            if (!$perfil) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Perfil não encontrado.'
                ];
            }

            if ($id === 1) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O perfil principal do sistema não pode ser eliminado.'
                ];
            }

            $totalUtilizadores =
                $this->model->contarUtilizadores(
                    $id
                );

            if ($totalUtilizadores > 0) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    sprintf(
                        'O perfil possui %d utilizador(es) associado(s) e não pode ser eliminado.',
                        $totalUtilizadores
                    )
                ];
            }

            $this->model->eliminar(
                $id
            );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Perfil eliminado com sucesso.'
            ];
        } catch (PDOException $e) {
            if (
                (string) $e->getCode()
                === '23000'
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O perfil possui registos relacionados e não pode ser eliminado.'
                ];
            }

            return $this->erroBanco(
                $e
            );
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
                $this->model->listarStatus()
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
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
            || $valor === ''
        ) {
            return null;
        }

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
                'Identificador inválido.'
            );
        }

        return (int) $numero;
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
                'O campo informado excede o limite permitido.'
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
                'A descrição excede o limite permitido.'
            );
        }

        return $texto;
    }

    /*
    |--------------------------------------------------------------------------
    | Tratamento de erros
    |--------------------------------------------------------------------------
    */

    private function erroBanco(
        PDOException $e
    ): array {
        error_log(
            sprintf(
                '[PerfilController] %s em %s:%d',
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
                'nome'
            )
            || str_contains(
                $mensagem,
                'duplicate'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'Já existe um perfil com este nome.'
            ];
        }

        return [
            'sucesso' => false,
            'mensagem' =>
            'Não foi possível guardar os dados do perfil.'
        ];
    }

    private function erro(
    Throwable $e
): array {
    error_log(
        sprintf(
            '[PerfilController] %s em %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        )
    );

    return [
        'sucesso' => false,
        'mensagem' => $e->getMessage(),
        'arquivo' => basename(
            $e->getFile()
        ),
        'linha' => $e->getLine()
    ];
}
}
