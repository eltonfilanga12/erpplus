<?php

declare(strict_types=1);

require_once __DIR__
    . '/../core/Database.php';

final class PerfilUsuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conn();
    }

    /**
     * ================================================================
     * BUSCAR PERFIL COMPLETO DO UTILIZADOR
     * ================================================================
     */
    public function buscar(
        int $usuarioId,
        int $empresaId
    ): ?array {
        if ($usuarioId <= 0) {
            throw new InvalidArgumentException(
                'Utilizador inválido.'
            );
        }

        if ($empresaId <= 0) {
            throw new InvalidArgumentException(
                'Empresa inválida.'
            );
        }

        $sql = "
            SELECT
                u.id,
                u.perfil_id,
                u.empresa_id,
                u.filial_id,
                u.status_id,

                u.nome,
                u.username,
                u.email,
                u.telefone,
                u.avatar,

                u.email_verificado,
                u.ultimo_login_em,
                u.email_verificado_em,
                u.telefone_verificado_em,

                u.criado_em,
                u.atualizado_em,

                p.nome AS perfil_nome,
                p.descricao AS perfil_descricao,

                e.nome AS empresa_nome,
                e.nome_fantasia AS empresa_nome_fantasia,
                e.logo AS empresa_logo,
                e.cidade AS empresa_cidade,
                e.provincia AS empresa_provincia,
                e.pais AS empresa_pais,

                f.nome AS filial_nome,
                f.codigo AS filial_codigo,

                s.nome AS status_nome,
                s.tipo AS status_tipo

            FROM usuarios u

            INNER JOIN perfis p
                ON p.id = u.perfil_id

            INNER JOIN empresas e
                ON e.id = u.empresa_id

            LEFT JOIN filiais f
                ON f.id = u.filial_id

            INNER JOIN status s
                ON s.id = u.status_id
                AND s.tipo = 'usuario'

            WHERE u.id = :usuario_id
              AND u.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare(
            $sql
        );

        $stmt->execute([
            ':usuario_id' =>
            $usuarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        $usuario = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $usuario ?: null;
    }

    /**
     * ================================================================
     * BUSCAR APENAS DADOS BÁSICOS
     * ================================================================
     */
    public function buscarBasico(
        int $usuarioId,
        int $empresaId
    ): ?array {
        $sql = "
            SELECT
                id,
                empresa_id,
                perfil_id,
                filial_id,
                status_id,
                nome,
                username,
                email,
                telefone,
                avatar,
                senha_hash,
                criado_em,
                atualizado_em

            FROM usuarios

            WHERE id = :usuario_id
              AND empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare(
            $sql
        );

        $stmt->execute([
            ':usuario_id' =>
            $usuarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        $usuario = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $usuario ?: null;
    }

    /**
     * ================================================================
     * ATUALIZAR DADOS DO PERFIL
     * ================================================================
     */
    public function atualizar(
        int $usuarioId,
        int $empresaId,
        array $dados
    ): bool {
        if ($usuarioId <= 0) {
            throw new InvalidArgumentException(
                'Utilizador inválido.'
            );
        }

        if ($empresaId <= 0) {
            throw new InvalidArgumentException(
                'Empresa inválida.'
            );
        }

        $nome = trim(
            (string)(
                $dados['nome']
                ?? ''
            )
        );

        $username = trim(
            (string)(
                $dados['username']
                ?? ''
            )
        );

        $email = trim(
            (string)(
                $dados['email']
                ?? ''
            )
        );

        $telefone = trim(
            (string)(
                $dados['telefone']
                ?? ''
            )
        );

        if ($nome === '') {
            throw new InvalidArgumentException(
                'O nome é obrigatório.'
            );
        }

        if ($username === '') {
            throw new InvalidArgumentException(
                'O nome de utilizador é obrigatório.'
            );
        }

        if ($email === '') {
            throw new InvalidArgumentException(
                'O email é obrigatório.'
            );
        }

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new InvalidArgumentException(
                'Informe um endereço de email válido.'
            );
        }

        if (
            $this->usernameExiste(
                $username,
                $usuarioId
            )
        ) {
            throw new RuntimeException(
                'Este nome de utilizador já está em uso.'
            );
        }

        if (
            $this->emailExiste(
                $email,
                $usuarioId
            )
        ) {
            throw new RuntimeException(
                'Este endereço de email já está em uso.'
            );
        }

        $sql = "
            UPDATE usuarios

            SET
                nome = :nome,
                username = :username,
                email = :email,
                telefone = :telefone

            WHERE id = :usuario_id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare(
            $sql
        );

        return $stmt->execute([
            ':nome' =>
            $nome,

            ':username' =>
            $username,

            ':email' =>
            $email,

            ':telefone' =>
            $telefone !== ''
                ? $telefone
                : null,

            ':usuario_id' =>
            $usuarioId,

            ':empresa_id' =>
            $empresaId
        ]);
    }

    /**
     * ================================================================
     * VERIFICAR USERNAME DUPLICADO
     * ================================================================
     */
    public function usernameExiste(
        string $username,
        ?int $ignorarUsuarioId = null
    ): bool {
        $username = trim(
            $username
        );

        if ($username === '') {
            return false;
        }

        $sql = "
            SELECT COUNT(*)

            FROM usuarios

            WHERE LOWER(username) =
                LOWER(:username)
        ";

        $parametros = [
            ':username' =>
            $username
        ];

        if (
            $ignorarUsuarioId !== null
            && $ignorarUsuarioId > 0
        ) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] = $ignorarUsuarioId;
        }

        $stmt = $this->db->prepare(
            $sql
        );

        $stmt->execute(
            $parametros
        );

        return (int)
        $stmt->fetchColumn() > 0;
    }

    /**
     * ================================================================
     * VERIFICAR EMAIL DUPLICADO
     * ================================================================
     */
    public function emailExiste(
        string $email,
        ?int $ignorarUsuarioId = null
    ): bool {
        $email = trim(
            $email
        );

        if ($email === '') {
            return false;
        }

        $sql = "
            SELECT COUNT(*)

            FROM usuarios

            WHERE LOWER(email) =
                LOWER(:email)
        ";

        $parametros = [
            ':email' =>
            $email
        ];

        if (
            $ignorarUsuarioId !== null
            && $ignorarUsuarioId > 0
        ) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] = $ignorarUsuarioId;
        }

        $stmt = $this->db->prepare(
            $sql
        );

        $stmt->execute(
            $parametros
        );

        return (int)
        $stmt->fetchColumn() > 0;
    }

    /**
     * ================================================================
     * BUSCAR HASH DA SENHA ATUAL
     * ================================================================
     */
    public function obterSenhaHash(
        int $usuarioId,
        int $empresaId
    ): ?string {
        $sql = "
            SELECT senha_hash

            FROM usuarios

            WHERE id = :usuario_id
              AND empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare(
            $sql
        );

        $stmt->execute([
            ':usuario_id' =>
            $usuarioId,

            ':empresa_id' =>
            $empresaId
        ]);

        $hash = $stmt->fetchColumn();

        if ($hash === false) {
            return null;
        }

        return (string)$hash;
    }

    /**
     * ================================================================
     * VALIDAR SENHA ATUAL
     * ================================================================
     */
    public function validarSenhaAtual(
        int $usuarioId,
        int $empresaId,
        string $senha
    ): bool {
        $hash =
            $this->obterSenhaHash(
                $usuarioId,
                $empresaId
            );

        if (
            $hash === null
            || $hash === ''
        ) {
            return false;
        }

        return password_verify(
            $senha,
            $hash
        );
    }

    /**
     * ================================================================
     * ALTERAR SENHA
     * ================================================================
     */
    public function alterarSenha(
        int $usuarioId,
        int $empresaId,
        string $novaSenha
    ): bool {
        if ($novaSenha === '') {
            throw new InvalidArgumentException(
                'A nova senha é obrigatória.'
            );
        }

        if (
            mb_strlen(
                $novaSenha
            ) < 8
        ) {
            throw new InvalidArgumentException(
                'A nova senha deve possuir pelo menos 8 caracteres.'
            );
        }

        $novoHash = password_hash(
            $novaSenha,
            PASSWORD_DEFAULT
        );

        if ($novoHash === false) {
            throw new RuntimeException(
                'Não foi possível proteger a nova senha.'
            );
        }

        $sql = "
            UPDATE usuarios

            SET senha_hash = :senha_hash

            WHERE id = :usuario_id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare(
            $sql
        );

        return $stmt->execute([
            ':senha_hash' =>
            $novoHash,

            ':usuario_id' =>
            $usuarioId,

            ':empresa_id' =>
            $empresaId
        ]);
    }

    /**
     * ================================================================
     * ATUALIZAR AVATAR
     * ================================================================
     */
    public function atualizarAvatar(
        int $usuarioId,
        int $empresaId,
        ?string $avatar
    ): bool {
        $avatar = $avatar !== null
            ? trim($avatar)
            : null;

        if ($avatar === '') {
            $avatar = null;
        }

        $sql = "
            UPDATE usuarios

            SET avatar = :avatar

            WHERE id = :usuario_id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare(
            $sql
        );

        return $stmt->execute([
            ':avatar' =>
            $avatar,

            ':usuario_id' =>
            $usuarioId,

            ':empresa_id' =>
            $empresaId
        ]);
    }

    /**
     * ================================================================
     * REMOVER AVATAR
     * ================================================================
     */
    public function removerAvatar(
        int $usuarioId,
        int $empresaId
    ): bool {
        return $this->atualizarAvatar(
            $usuarioId,
            $empresaId,
            null
        );
    }

    /**
     * ================================================================
     * DADOS RESUMIDOS PARA O CABEÇALHO
     * ================================================================
     */
    public function cabecalho(
        int $usuarioId,
        int $empresaId
    ): array {
        $usuario =
            $this->buscar(
                $usuarioId,
                $empresaId
            );

        if (!$usuario) {
            throw new RuntimeException(
                'Perfil do utilizador não encontrado.'
            );
        }

        $localizacao = [];

        if (
            !empty($usuario['empresa_cidade'])
        ) {
            $localizacao[] =
                $usuario['empresa_cidade'];
        }

        if (
            !empty($usuario['empresa_provincia'])
        ) {
            $localizacao[] =
                $usuario['empresa_provincia'];
        }

        if (
            !empty($usuario['empresa_pais'])
        ) {
            $localizacao[] =
                $usuario['empresa_pais'];
        }

        return [
            'id' =>
            (int)$usuario['id'],

            'nome' =>
            $usuario['nome'],

            'username' =>
            $usuario['username'],

            'avatar' =>
            $usuario['avatar'],

            'perfil' =>
            $usuario['perfil_descricao']
                ?: $usuario['perfil_nome'],

            'empresa' =>
            $usuario['empresa_nome'],

            'filial' =>
            $usuario['filial_nome']
                ?? null,

            'localizacao' =>
            implode(
                ', ',
                $localizacao
            ),

            'status' =>
            $usuario['status_nome'],

            'status_tipo' =>
            $usuario['status_tipo'],

            'ultimo_login' =>
            $usuario['ultimo_login_em'],
        ];
    }
}
