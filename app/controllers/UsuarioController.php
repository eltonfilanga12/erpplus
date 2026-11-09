<?php

declare(strict_types=1);

final class UsuarioController
{
    private Usuario $model;

    public function __construct()
    {
        $this->model =
            new Usuario();
    }



    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    public function guardar(): array
    {
        try {

            $superAdministrador =
                $this->ehSuperAdministrador();


            /*
            |--------------------------------------------------------------------------
            | Empresa do utilizador
            |--------------------------------------------------------------------------
            |
            | Super Administrador:
            | pode escolher qualquer empresa.
            |
            | Outros utilizadores:
            | ficam obrigatoriamente na empresa da sessão.
            |
            */

            if ($superAdministrador) {

                $empresaId =
                    $this->inteiroObrigatorio(
                        $_POST['empresa_id']
                            ?? null,
                        'Selecione a empresa do utilizador.'
                    );
            } else {

                $empresaId =
                    $this->empresaId();
            }


            /*
            |--------------------------------------------------------------------------
            | Filial
            |--------------------------------------------------------------------------
            */

            $filialId =
                $this->inteiroOpcional(
                    $_POST['filial_id']
                        ?? null
                );
            $id =
                $this->inteiroOpcional(
                    $_POST['id']
                        ?? null
                );

            $nome =
                $this->textoObrigatorio(
                    $_POST['nome']
                        ?? null,
                    'Informe o nome do utilizador.',
                    180
                );

            $username =
                $this->textoObrigatorio(
                    $_POST['username']
                        ?? null,
                    'Informe o username.',
                    80
                );

            $username =
                mb_strtolower(
                    $username
                );

            $email =
                $this->emailOpcional(
                    $_POST['email']
                        ?? null
                );

            $telefone =
                $this->textoOpcional(
                    $_POST['telefone']
                        ?? null,
                    30
                );

            $perfilId =
                $this->inteiroObrigatorio(
                    $_POST['perfil_id']
                        ?? null,
                    'Selecione o perfil.'
                );

            $perfilSuperAdministrador =
                $this->perfilEhSuperAdministrador(
                    $perfilId
                );


            if (
                $perfilSuperAdministrador
                && !$superAdministrador
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Apenas um Super Administrador '
                        . 'pode criar ou atribuir '
                        . 'o perfil Super Administrador.'
                ];
            }

            $statusId =
                $this->inteiroObrigatorio(
                    $_POST['status_id']
                        ?? null,
                    'Selecione o estado.'
                );

            $senha =
                (string) (
                    $_POST['senha']
                    ?? ''
                );

            $confirmarSenha =
                (string) (
                    $_POST['confirmar_senha']
                    ?? ''
                );

            if (
                $id === null
                && trim($senha) === ''
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Informe a senha do utilizador.'
                ];
            }

            if (
                $senha !== ''
                && mb_strlen($senha) < 6
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A senha deve possuir pelo menos 6 caracteres.'
                ];
            }

            if (
                $senha !== ''
                && $senha !== $confirmarSenha
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A confirmação da senha não corresponde.'
                ];
            }

            if (
                $this->model->usernameExiste(
                    $username,
                    $id
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O username informado já está em uso.'
                ];
            }

            if (
                $email !== null
                && $this->model->emailExiste(
                    $email,
                    $id
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O email informado já está em uso.'
                ];
            }

            $dados = [
                'empresa_id' =>
                $empresaId,

                'filial_id' =>
                $filialId,

                'perfil_id' =>
                $perfilId,

                'status_id' =>
                $statusId,

                'nome' =>
                $nome,

                'username' =>
                $username,

                'email' =>
                $email,

                'telefone' =>
                $telefone,

                'senha' =>
                $senha,

                'avatar' =>
                null
            ];

            if ($id !== null) {

                if ($superAdministrador) {

                    $usuario =
                        $this->model
                        ->buscarPorIdGlobal(
                            $id
                        );
                } else {

                    $usuario =
                        $this->model
                        ->buscarPorId(
                            $id,
                            $this->empresaId()
                        );
                }


                if (!$usuario) {

                    return [
                        'sucesso' => false,

                        'mensagem' =>
                        'Utilizador não encontrado.'
                    ];
                }


                /*
                * Utilizador comum nunca pode
                * transferir utilizador entre empresas.
                */
                if (!$superAdministrador) {

                    $empresaId =
                        (int)$usuario['empresa_id'];
                }

                if ($superAdministrador) {

                    $this->model
                        ->atualizarGlobal(
                            $id,
                            $dados
                        );
                } else {

                    $this->model
                        ->atualizar(
                            $id,
                            $empresaId,
                            $dados
                        );
                }

                return [
                    'sucesso' => true,
                    'mensagem' =>
                    'Utilizador atualizado com sucesso.',
                    'id' => $id
                ];
            }

            $novoId =
                $this->model->criar(
                    $dados
                );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Utilizador criado com sucesso.',
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

    public function contexto(): array
    {
        return [
            'sucesso' => true,

            'dados' => [
                'super_administrador' =>
                $this->ehSuperAdministrador(),

                'empresa_id' =>
                $this->empresaId()
            ]
        ];
    }

    public function filiais(): array
    {
        try {

            if (
                $this->ehSuperAdministrador()
            ) {

                $empresaId =
                    $this->inteiroOpcional(
                        $_GET['empresa_id']
                            ?? $_POST['empresa_id']
                            ?? null
                    );

                if (!$empresaId) {

                    return [
                        'sucesso' => true,
                        'dados' => []
                    ];
                }
            } else {

                $empresaId =
                    $this->empresaId();
            }


            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarFiliais(
                        $empresaId
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }

    private function ehSuperAdministrador(): bool
    {
        /*
    |--------------------------------------------------------------------------
    | Perfil pelo nome existente na sessão
    |--------------------------------------------------------------------------
    */

        $perfilNome =
            trim(
                (string)(
                    $_SESSION['perfil_nome']
                    ?? $_SESSION['usuario']['perfil_nome']
                    ?? ''
                )
            );


        if ($perfilNome !== '') {

            return mb_strtolower(
                $perfilNome
            ) === 'super_administrador';
        }


        /*
    |--------------------------------------------------------------------------
    | Perfil pelo ID da sessão
    |--------------------------------------------------------------------------
    */

        $perfilId =
            (int)(
                $_SESSION['perfil_id']
                ?? $_SESSION['usuario']['perfil_id']
                ?? 0
            );


        if ($perfilId > 0) {

            $perfil =
                $this->model
                ->buscarPerfilPorId(
                    $perfilId
                );


            if ($perfil) {

                return mb_strtolower(
                    trim(
                        (string)(
                            $perfil['nome']
                            ?? ''
                        )
                    )
                ) === 'super_administrador';
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Último recurso: buscar utilizador
    |--------------------------------------------------------------------------
    */

        $usuarioId =
            (int)(
                $_SESSION['usuario_id']
                ?? $_SESSION['usuario']['id']
                ?? 0
            );


        if ($usuarioId <= 0) {

            return false;
        }


        $usuario =
            $this->model
            ->buscarPorIdGlobal(
                $usuarioId
            );


        if (!$usuario) {

            return false;
        }


        return mb_strtolower(
            trim(
                (string)(
                    $usuario['perfil_nome']
                    ?? ''
                )
            )
        ) === 'super_administrador';
    }

    private function perfilEhSuperAdministrador(
        int $perfilId
    ): bool {

        $perfil =
            $this->model
            ->buscarPerfilPorId(
                $perfilId
            );

        if (!$perfil) {
            throw new RuntimeException(
                'Perfil selecionado não encontrado.'
            );
        }

        return mb_strtolower(
            trim(
                (string)(
                    $perfil['nome']
                    ?? ''
                )
            )
        ) === 'super_administrador';
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
                    'Utilizador inválido.'
                );

            $usuario =
                $this->model->buscarPorId(
                    $id,
                    $empresaId
                );

            if (!$usuario) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Utilizador não encontrado.'
                ];
            }

            $usuarioSessaoId =
                (int) (
                    $_SESSION['usuario_id']
                    ?? $_SESSION['usuario']['id']
                    ?? 0
                );

            if ($usuarioSessaoId === $id) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Não pode eliminar a sua própria conta.'
                ];
            }

            $this->model->eliminar(
                $id,
                $empresaId
            );

            return [
                'sucesso' => true,
                'mensagem' =>
                'Utilizador eliminado com sucesso.'
            ];
        } catch (PDOException $e) {
            if (
                (string) $e->getCode()
                === '23000'
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'O utilizador possui registos relacionados e não pode ser eliminado.'
                ];
            }

            return $this->erro($e);
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Perfis
    |--------------------------------------------------------------------------
    */

    public function perfis(): array
    {
        try {

            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarPerfis(
                        $this->ehSuperAdministrador()
                    )
            ];
        } catch (Throwable $e) {

            return $this->erro(
                $e
            );
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
        $id =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $id === false
            || $id <= 0
        ) {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        return (int) $id;
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

        $id =
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $id === false
            || $id <= 0
        ) {
            throw new InvalidArgumentException(
                'Identificador inválido.'
            );
        }

        return (int) $id;
    }

    private function textoObrigatorio(
        mixed $valor,
        string $mensagem,
        int $maximo
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
            > $maximo
        ) {
            throw new InvalidArgumentException(
                'O campo informado excede o limite permitido.'
            );
        }

        return $texto;
    }

    private function textoOpcional(
        mixed $valor,
        int $maximo
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
            > $maximo
        ) {
            throw new InvalidArgumentException(
                'O campo informado excede o limite permitido.'
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
            '[UsuarioController] '
                . $e->getMessage()
        );

        $mensagem =
            strtolower(
                $e->getMessage()
            );

        if (
            str_contains(
                $mensagem,
                'username'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'O username informado já está em uso.'
            ];
        }

        if (
            str_contains(
                $mensagem,
                'email'
            )
        ) {
            return [
                'sucesso' => false,
                'mensagem' =>
                'O email informado já está em uso.'
            ];
        }

        return [
            'sucesso' => false,
            'mensagem' =>
            'Não foi possível guardar os dados do utilizador.'
        ];
    }

    private function empresaId(): int
    {
        $empresaId =
            (int) (
                $_SESSION['empresa_id']
                ?? $_SESSION['usuario']['empresa_id']
                ?? 0
            );

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa da sessão não identificada.'
            );
        }

        return $empresaId;
    }

    public function listar(): array
    {
        try {

            if (
                $this->ehSuperAdministrador()
            ) {

                $dados =
                    $this->model
                    ->listarGlobal();
            } else {

                $dados =
                    $this->model
                    ->listar(
                        $this->empresaId()
                    );
            }


            return [
                'sucesso' => true,
                'dados' => $dados
            ];
        } catch (Throwable $e) {

            return $this->erro($e);
        }
    }

    public function empresas(): array
    {
        try {

            if (
                !$this->ehSuperAdministrador()
            ) {

                return [
                    'sucesso' => false,

                    'mensagem' =>
                    'Apenas o Super Administrador '
                        . 'pode consultar todas as empresas.'
                ];
            }


            return [
                'sucesso' => true,

                'dados' =>
                $this->model
                    ->listarEmpresas()
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
                    'Utilizador inválido.'
                );

            $usuario =
                $this->model->buscarPorId(
                    $id,
                    $empresaId
                );

            if (!$usuario) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Utilizador não encontrado.'
                ];
            }

            return [
                'sucesso' => true,
                'dados' => $usuario
            ];
        } catch (Throwable $e) {
            return $this->erro($e);
        }
    }

    private function erro(
        Throwable $e
    ): array {
        error_log(
            sprintf(
                '[UsuarioController] %s em %s:%d',
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
