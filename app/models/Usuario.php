<?php

declare(strict_types=1);

final class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
            Database::conn();
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar por login
    |--------------------------------------------------------------------------
    */

    public function buscarPorLogin(
        string $login
    ): ?array {
        $sql = "
            SELECT
                u.*,
                p.nome AS perfil_nome,
                s.nome AS status_nome,
                e.nome AS empresa_nome,
                f.nome AS filial_nome

            FROM usuarios u

            INNER JOIN perfis p
                ON p.id = u.perfil_id

            INNER JOIN status s
                ON s.id = u.status_id

            LEFT JOIN empresas e
                ON e.id = u.empresa_id

            LEFT JOIN filiais f
                ON f.id = u.filial_id

            WHERE u.email = :email
               OR u.username = :username

            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':email' => $login,
            ':username' => $login
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function buscarPorIdGlobal(
        int $id
    ): ?array {
        $sql = "
        SELECT
            u.*,
            p.nome AS perfil_nome,
            s.nome AS status_nome,
            e.nome AS empresa_nome,
            f.nome AS filial_nome

        FROM usuarios u

        INNER JOIN perfis p
            ON p.id = u.perfil_id

        INNER JOIN status s
            ON s.id = u.status_id

        LEFT JOIN empresas e
            ON e.id = u.empresa_id

        LEFT JOIN filiais f
            ON f.id = u.filial_id

        WHERE u.id = :id

        LIMIT 1
    ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function listarEmpresas(): array
    {
        $stmt =
            $this->db->query("
            SELECT
                id,
                nome

            FROM empresas

            ORDER BY nome ASC
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function atualizarGlobal(
        int $id,
        array $dados
    ): bool {

        $sql = "
        UPDATE usuarios

        SET
            perfil_id = :perfil_id,
            empresa_id = :empresa_id,
            filial_id = :filial_id,
            status_id = :status_id,
            nome = :nome,
            username = :username,
            email = :email,
            telefone = :telefone
    ";

        $parametros = [
            ':perfil_id' =>
            $dados['perfil_id'],

            ':empresa_id' =>
            $dados['empresa_id'],

            ':filial_id' =>
            $dados['filial_id'],

            ':status_id' =>
            $dados['status_id'],

            ':nome' =>
            $dados['nome'],

            ':username' =>
            $dados['username'],

            ':email' =>
            $dados['email'],

            ':telefone' =>
            $dados['telefone'],

            ':id' =>
            $id
        ];


        if (!empty($dados['senha'])) {

            $sql .= ",
            senha_hash = :senha_hash
        ";

            $parametros[':senha_hash'] =
                password_hash(
                    $dados['senha'],
                    PASSWORD_DEFAULT
                );
        }


        $sql .= "
        WHERE id = :id
    ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        return $stmt->execute(
            $parametros
        );
    }

    public function listarGlobal(): array
    {
        $stmt =
            $this->db->query("
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

                u.criado_em,
                u.atualizado_em,

                p.nome AS perfil_nome,
                s.nome AS status_nome,

                e.nome AS empresa_nome,

                f.nome AS filial_nome

            FROM usuarios u

            INNER JOIN perfis p
                ON p.id = u.perfil_id

            INNER JOIN status s
                ON s.id = u.status_id

            LEFT JOIN empresas e
                ON e.id = u.empresa_id

            LEFT JOIN filiais f
                ON f.id = u.filial_id

            ORDER BY
                e.nome ASC,
                u.nome ASC
        ");

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function buscarPerfilPorId(
        int $perfilId
    ): ?array {

        $stmt =
            $this->db->prepare("
            SELECT
                id,
                nome

            FROM perfis

            WHERE id = :id

            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $perfilId
        ]);

        $perfil =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return $perfil ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Listar por empresa
    |--------------------------------------------------------------------------
    */

    public function listar(
        int $empresaId
    ): array {
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
                s.nome AS status_nome,
                f.nome AS filial_nome

            FROM usuarios u

            INNER JOIN perfis p
                ON p.id = u.perfil_id

            INNER JOIN status s
                ON s.id = u.status_id

            LEFT JOIN filiais f
                ON f.id = u.filial_id

            WHERE u.empresa_id = :empresa_id

            ORDER BY
                u.nome ASC,
                u.id DESC
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar por ID e empresa
    |--------------------------------------------------------------------------
    */

    public function buscarPorId(
        int $id,
        int $empresaId
    ): ?array {
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
                s.nome AS status_nome,
                f.nome AS filial_nome

            FROM usuarios u

            INNER JOIN perfis p
                ON p.id = u.perfil_id

            INNER JOIN status s
                ON s.id = u.status_id

            LEFT JOIN filiais f
                ON f.id = u.filial_id

            WHERE u.id = :id
              AND u.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $resultado =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar username
    |--------------------------------------------------------------------------
    */

    public function usernameExiste(
        string $username,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM usuarios

            WHERE username = :username
        ";

        $parametros = [
            ':username' => $username
        ];

        if ($ignorarId !== null) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] =
                $ignorarId;
        }

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar email
    |--------------------------------------------------------------------------
    */

    public function emailExiste(
        string $email,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM usuarios

            WHERE email = :email
        ";

        $parametros = [
            ':email' => $email
        ];

        if ($ignorarId !== null) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] =
                $ignorarId;
        }

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar telefone
    |--------------------------------------------------------------------------
    */

    public function telefoneExiste(
        string $telefone,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM usuarios

            WHERE telefone = :telefone
        ";

        $parametros = [
            ':telefone' => $telefone
        ];

        if ($ignorarId !== null) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] =
                $ignorarId;
        }

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute($parametros);

        return (int) $stmt->fetchColumn() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Criar
    |--------------------------------------------------------------------------
    */

    public function criar(
        array $dados
    ): int {
        $sql = "
            INSERT INTO usuarios (
                perfil_id,
                empresa_id,
                filial_id,
                status_id,
                nome,
                username,
                email,
                telefone,
                senha_hash,
                avatar,
                email_verificado
            ) VALUES (
                :perfil_id,
                :empresa_id,
                :filial_id,
                :status_id,
                :nome,
                :username,
                :email,
                :telefone,
                :senha_hash,
                :avatar,
                0
            )
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':perfil_id' =>
            $dados['perfil_id'],

            ':empresa_id' =>
            $dados['empresa_id'],

            ':filial_id' =>
            $dados['filial_id'],

            ':status_id' =>
            $dados['status_id'],

            ':nome' =>
            $dados['nome'],

            ':username' =>
            $dados['username'],

            ':email' =>
            $dados['email'],

            ':telefone' =>
            $dados['telefone'],

            ':senha_hash' =>
            password_hash(
                $dados['senha'],
                PASSWORD_DEFAULT
            ),

            ':avatar' =>
            $dados['avatar'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }

    /*
    |--------------------------------------------------------------------------
    | Atualizar
    |--------------------------------------------------------------------------
    */

    public function atualizar(
        int $id,
        int $empresaId,
        array $dados
    ): bool {
        $sql = "
            UPDATE usuarios

            SET
                perfil_id = :perfil_id,
                filial_id = :filial_id,
                status_id = :status_id,
                nome = :nome,
                username = :username,
                email = :email,
                telefone = :telefone
        ";

        $parametros = [
            ':perfil_id' =>
            $dados['perfil_id'],

            ':filial_id' =>
            $dados['filial_id'],

            ':status_id' =>
            $dados['status_id'],

            ':nome' =>
            $dados['nome'],

            ':username' =>
            $dados['username'],

            ':email' =>
            $dados['email'],

            ':telefone' =>
            $dados['telefone'],

            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ];

        if (!empty($dados['senha'])) {
            $sql .= ",
                senha_hash = :senha_hash
            ";

            $parametros[':senha_hash'] =
                password_hash(
                    $dados['senha'],
                    PASSWORD_DEFAULT
                );
        }

        $sql .= "
            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt =
            $this->db->prepare($sql);

        return $stmt->execute(
            $parametros
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function eliminar(
        int $id,
        int $empresaId
    ): bool {
        $sql = "
            DELETE FROM usuarios

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt =
            $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Perfis
    |--------------------------------------------------------------------------
    */

    public function listarPerfis(
        bool $incluirSuperAdministrador = false
    ): array {

        $sql = "
        SELECT
            id,
            nome

        FROM perfis

        WHERE 1 = 1
    ";

        if (!$incluirSuperAdministrador) {

            $sql .= "
            AND nome <> 'super_administrador'
        ";
        }

        $sql .= "
        ORDER BY nome ASC
    ";

        $stmt =
            $this->db->query(
                $sql
            );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    public function listarStatus(): array
    {
        $sql = "
            SELECT
                id,
                nome,
                descricao,
                tipo

            FROM status

            WHERE tipo = 'USUARIO'

            ORDER BY nome ASC
        ";

        $stmt =
            $this->db->query($sql);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Filiais da empresa
    |--------------------------------------------------------------------------
    */

    public function listarFiliais(
        int $empresaId
    ): array {
        $sql = "
            SELECT
                id,
                nome,
                codigo

            FROM filiais

            WHERE empresa_id = :empresa_id

            ORDER BY nome ASC
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}
