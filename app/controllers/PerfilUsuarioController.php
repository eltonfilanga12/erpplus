<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/PerfilUsuario.php';

final class PerfilUsuarioController
{
    private PerfilUsuario $model;

    public function __construct()
    {
        $this->model =
            new PerfilUsuario();
    }

    /**
     * Obtém o ID do utilizador autenticado.
     */
    private function usuarioId(): int
    {
        $usuarioId = isset(
            $_SESSION['usuario_id']
        )
            ? (int)$_SESSION['usuario_id']
            : 0;

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não identificado na sessão.'
            );
        }

        return $usuarioId;
    }

    /**
     * Obtém a empresa autenticada.
     */
    private function empresaId(): int
    {
        $empresaId = isset(
            $_SESSION['empresa_id']
        )
            ? (int)$_SESSION['empresa_id']
            : 0;

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }

        return $empresaId;
    }

    /**
     * Resposta de erro.
     */
    private function erro(
        Throwable $erro
    ): array {
        error_log(
            '[PerfilUsuarioController] '
                . $erro->getMessage()
        );

        return [
            'sucesso' => false,
            'mensagem' =>
            $erro->getMessage(),
        ];
    }

    /**
     * Texto opcional.
     */
    private function textoOpcional(
        mixed $valor,
        int $maximo = 255
    ): ?string {
        $valor = trim(
            (string)$valor
        );

        if ($valor === '') {
            return null;
        }

        if (
            mb_strlen($valor)
            > $maximo
        ) {
            throw new InvalidArgumentException(
                "O campo ultrapassa o limite de {$maximo} caracteres."
            );
        }

        return $valor;
    }

    /**
     * Texto obrigatório.
     */
    private function textoObrigatorio(
        mixed $valor,
        string $mensagem,
        int $maximo = 255
    ): string {
        $valor = trim(
            (string)$valor
        );

        if ($valor === '') {
            throw new InvalidArgumentException(
                $mensagem
            );
        }

        if (
            mb_strlen($valor)
            > $maximo
        ) {
            throw new InvalidArgumentException(
                "O campo ultrapassa o limite de {$maximo} caracteres."
            );
        }

        return $valor;
    }

    /**
     * Carrega todos os dados do perfil.
     */
    public function carregar(): array
    {
        try {
            $usuarioId =
                $this->usuarioId();

            $empresaId =
                $this->empresaId();

            $perfil =
                $this->model->buscar(
                    $usuarioId,
                    $empresaId
                );

            if (!$perfil) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Perfil do utilizador não encontrado.',
                ];
            }

            return [
                'sucesso' => true,
                'dados' => [
                    'perfil' =>
                    $perfil,

                    'cabecalho' =>
                    $this->model
                        ->cabecalho(
                            $usuarioId,
                            $empresaId
                        ),
                ],
            ];
        } catch (Throwable $erro) {

            return $this->erro(
                $erro
            );
        }
    }

    /**
     * Atualiza dados pessoais.
     */
    public function atualizar(): array
    {
        try {
            $usuarioId =
                $this->usuarioId();

            $empresaId =
                $this->empresaId();

            $nome =
                $this->textoObrigatorio(
                    $_POST['nome']
                        ?? null,
                    'Informe o nome.',
                    150
                );

            $username =
                $this->textoObrigatorio(
                    $_POST['username']
                        ?? null,
                    'Informe o nome de utilizador.',
                    100
                );

            $email =
                $this->textoObrigatorio(
                    $_POST['email']
                        ?? null,
                    'Informe o email.',
                    190
                );

            $telefone =
                $this->textoOpcional(
                    $_POST['telefone']
                        ?? null,
                    30
                );

            $atualizado =
                $this->model->atualizar(
                    $usuarioId,
                    $empresaId,
                    [
                        'nome' =>
                        $nome,

                        'username' =>
                        $username,

                        'email' =>
                        $email,

                        'telefone' =>
                        $telefone,
                    ]
                );

            if (!$atualizado) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Não foi possível atualizar o perfil.',
                ];
            }

            /*
             * Atualizar dados básicos da sessão,
             * caso sejam usados no cabeçalho do ERP.
             */
            $_SESSION['nome'] =
                $nome;

            $_SESSION['username'] =
                $username;

            $_SESSION['email'] =
                $email;

            return [
                'sucesso' => true,
                'mensagem' =>
                'Perfil atualizado com sucesso.',
                'dados' =>
                $this->model->buscar(
                    $usuarioId,
                    $empresaId
                ),
            ];
        } catch (Throwable $erro) {

            return $this->erro(
                $erro
            );
        }
    }

    /**
     * Alterar senha.
     */
    public function alterarSenha(): array
    {
        try {
            $usuarioId =
                $this->usuarioId();

            $empresaId =
                $this->empresaId();

            $senhaAtual =
                $this->textoObrigatorio(
                    $_POST['senha_atual']
                        ?? null,
                    'Informe a senha atual.',
                    255
                );

            $novaSenha =
                $this->textoObrigatorio(
                    $_POST['nova_senha']
                        ?? null,
                    'Informe a nova senha.',
                    255
                );

            $confirmarSenha =
                $this->textoObrigatorio(
                    $_POST['confirmar_senha']
                        ?? null,
                    'Confirme a nova senha.',
                    255
                );

            if (
                $novaSenha
                !== $confirmarSenha
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A confirmação da nova senha não corresponde.',
                ];
            }

            if (
                mb_strlen(
                    $novaSenha
                ) < 8
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A nova senha deve possuir pelo menos 8 caracteres.',
                ];
            }

            if (
                !$this->model
                    ->validarSenhaAtual(
                        $usuarioId,
                        $empresaId,
                        $senhaAtual
                    )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A senha atual está incorreta.',
                ];
            }

            /*
             * Impedir reutilização imediata
             * da mesma senha.
             */
            if (
                $this->model
                ->validarSenhaAtual(
                    $usuarioId,
                    $empresaId,
                    $novaSenha
                )
            ) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'A nova senha deve ser diferente da senha atual.',
                ];
            }

            $alterada =
                $this->model
                ->alterarSenha(
                    $usuarioId,
                    $empresaId,
                    $novaSenha
                );

            if (!$alterada) {
                return [
                    'sucesso' => false,
                    'mensagem' =>
                    'Não foi possível alterar a senha.',
                ];
            }

            return [
                'sucesso' => true,
                'mensagem' =>
                'Senha alterada com sucesso.',
            ];
        } catch (Throwable $erro) {

            return $this->erro(
                $erro
            );
        }
    }

    /**
     * Atualizar avatar.
     */
    public function alterarAvatar(): array
    {
        try {
            $usuarioId =
                $this->usuarioId();

            $empresaId =
                $this->empresaId();

            if (
                !isset(
                    $_FILES['avatar']
                )
            ) {
                throw new InvalidArgumentException(
                    'Selecione uma fotografia.'
                );
            }

            $arquivo =
                $_FILES['avatar'];

            if (
                !isset(
                    $arquivo['error']
                )
                ||
                (int)$arquivo['error']
                !== UPLOAD_ERR_OK
            ) {
                throw new RuntimeException(
                    'Não foi possível receber a fotografia.'
                );
            }

            if (
                !isset(
                    $arquivo['tmp_name']
                )
                ||
                !is_uploaded_file(
                    $arquivo['tmp_name']
                )
            ) {
                throw new RuntimeException(
                    'O ficheiro enviado é inválido.'
                );
            }

            /*
             * Limite de 5 MB.
             */
            $tamanho =
                (int)(
                    $arquivo['size']
                    ?? 0
                );

            if (
                $tamanho <= 0
                ||
                $tamanho >
                5 * 1024 * 1024
            ) {
                throw new InvalidArgumentException(
                    'A fotografia deve possuir no máximo 5 MB.'
                );
            }

            /*
             * Validar MIME real do ficheiro.
             */
            $finfo =
                new finfo(
                    FILEINFO_MIME_TYPE
                );

            $mime =
                $finfo->file(
                    $arquivo['tmp_name']
                );

            $extensoes = [
                'image/jpeg' =>
                'jpg',

                'image/png' =>
                'png',

                'image/webp' =>
                'webp',
            ];

            if (
                !isset(
                    $extensoes[$mime]
                )
            ) {
                throw new InvalidArgumentException(
                    'Formato de fotografia inválido. Utilize JPG, PNG ou WEBP.'
                );
            }

            /*
             * Pasta física.
             *
             * Ajuste esta raiz se a estrutura
             * pública do teu projeto for diferente.
             */
            $diretorio =
                dirname(__DIR__, 2)
                . '/public/uploads/perfis';

            if (
                !is_dir(
                    $diretorio
                )
            ) {
                if (
                    !mkdir(
                        $diretorio,
                        0775,
                        true
                    )
                    &&
                    !is_dir(
                        $diretorio
                    )
                ) {
                    throw new RuntimeException(
                        'Não foi possível criar a pasta de fotografias.'
                    );
                }
            }

            $extensao =
                $extensoes[$mime];

            $nomeArquivo =
                'perfil_'
                . $usuarioId
                . '_'
                . time()
                . '_'
                . bin2hex(
                    random_bytes(5)
                )
                . '.'
                . $extensao;

            $destinoFisico =
                $diretorio
                . '/'
                . $nomeArquivo;

            if (
                !move_uploaded_file(
                    $arquivo['tmp_name'],
                    $destinoFisico
                )
            ) {
                throw new RuntimeException(
                    'Não foi possível guardar a fotografia.'
                );
            }

            /*
             * Caminho armazenado na base.
             */
            $caminhoDb =
                'public/uploads/perfis/'
                . $nomeArquivo;

            /*
             * Obter avatar anterior para
             * remover depois da atualização.
             */
            $usuarioAtual =
                $this->model
                ->buscarBasico(
                    $usuarioId,
                    $empresaId
                );

            $avatarAnterior =
                $usuarioAtual['avatar']
                ?? null;

            $atualizado =
                $this->model
                ->atualizarAvatar(
                    $usuarioId,
                    $empresaId,
                    $caminhoDb
                );

            if (!$atualizado) {

                @unlink(
                    $destinoFisico
                );

                throw new RuntimeException(
                    'Não foi possível atualizar a fotografia do perfil.'
                );
            }

            /*
             * Remover fotografia antiga,
             * apenas se estiver dentro da pasta
             * de perfis.
             */
            if (
                $avatarAnterior
                &&
                str_starts_with(
                    (string)$avatarAnterior,
                    'public/uploads/perfis/'
                )
            ) {
                $ficheiroAnterior =
                    dirname(__DIR__, 2)
                    . '/'
                    . ltrim(
                        (string)$avatarAnterior,
                        '/'
                    );

                if (
                    is_file(
                        $ficheiroAnterior
                    )
                ) {
                    @unlink(
                        $ficheiroAnterior
                    );
                }
            }

            $_SESSION['avatar'] =
                $caminhoDb;

            return [
                'sucesso' => true,
                'mensagem' =>
                'Fotografia atualizada com sucesso.',
                'dados' => [
                    'avatar' =>
                    $caminhoDb,
                ],
            ];
        } catch (Throwable $erro) {

            return $this->erro(
                $erro
            );
        }
    }

    /**
     * Remover avatar.
     */
    public function removerAvatar(): array
    {
        try {
            $usuarioId =
                $this->usuarioId();

            $empresaId =
                $this->empresaId();

            $usuario =
                $this->model
                ->buscarBasico(
                    $usuarioId,
                    $empresaId
                );

            if (!$usuario) {
                throw new RuntimeException(
                    'Utilizador não encontrado.'
                );
            }

            $avatar =
                $usuario['avatar']
                ?? null;

            $removido =
                $this->model
                ->removerAvatar(
                    $usuarioId,
                    $empresaId
                );

            if (!$removido) {
                throw new RuntimeException(
                    'Não foi possível remover a fotografia.'
                );
            }

            if (
                $avatar
                &&
                str_starts_with(
                    (string)$avatar,
                    'public/uploads/perfis/'
                )
            ) {
                $ficheiro =
                    dirname(__DIR__, 2)
                    . '/'
                    . ltrim(
                        (string)$avatar,
                        '/'
                    );

                if (
                    is_file(
                        $ficheiro
                    )
                ) {
                    @unlink(
                        $ficheiro
                    );
                }
            }

            $_SESSION['avatar'] =
                null;

            return [
                'sucesso' => true,
                'mensagem' =>
                'Fotografia removida com sucesso.',
            ];
        } catch (Throwable $erro) {

            return $this->erro(
                $erro
            );
        }
    }
}
