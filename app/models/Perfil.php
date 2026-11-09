<?php

declare(strict_types=1);

final class Perfil
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conn();

        $this->db->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Listar perfis
    |--------------------------------------------------------------------------
    */

    public function listar(): array
    {
        $sql = "
            SELECT
                p.id,
                p.nome,
                p.descricao,
                p.status_id,
                p.criado_em,

                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM perfis p

            INNER JOIN status s
                ON s.id = p.status_id
               AND s.tipo = 'perfil'

            ORDER BY
                p.nome ASC,
                p.id ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar por ID
    |--------------------------------------------------------------------------
    */

    public function buscarPorId(
        int $id
    ): ?array {
        if ($id <= 0) {
            return null;
        }

        $sql = "
            SELECT
                p.id,
                p.nome,
                p.descricao,
                p.status_id,
                p.criado_em,

                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM perfis p

            INNER JOIN status s
                ON s.id = p.status_id
               AND s.tipo = 'perfil'

            WHERE p.id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $perfil = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $perfil ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar nome duplicado
    |--------------------------------------------------------------------------
    */

    public function nomeExiste(
        string $nome,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM perfis

            WHERE LOWER(TRIM(nome)) =
                  LOWER(TRIM(:nome))
        ";

        $parametros = [
            ':nome' => $nome
        ];

        if ($ignorarId !== null) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] =
                $ignorarId;
        }

        $stmt = $this->db->prepare($sql);

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
            INSERT INTO perfis (
                nome,
                descricao,
                status_id
            ) VALUES (
                :nome,
                :descricao,
                :status_id
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':nome' =>
            $dados['nome'],

            ':descricao' =>
            $dados['descricao'],

            ':status_id' =>
            $dados['status_id']
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
        array $dados
    ): bool {
        $sql = "
            UPDATE perfis

            SET
                nome = :nome,
                descricao = :descricao,
                status_id = :status_id

            WHERE id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nome' =>
            $dados['nome'],

            ':descricao' =>
            $dados['descricao'],

            ':status_id' =>
            $dados['status_id'],

            ':id' =>
            $id
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar
    |--------------------------------------------------------------------------
    */

    public function eliminar(
        int $id
    ): bool {
        $sql = "
            DELETE FROM perfis

            WHERE id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar utilizadores associados
    |--------------------------------------------------------------------------
    */

    public function contarUtilizadores(
        int $perfilId
    ): int {
        $sql = "
            SELECT COUNT(*)

            FROM usuarios

            WHERE perfil_id = :perfil_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':perfil_id' => $perfilId
        ]);

        return (int) $stmt->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | Listar estados de perfil
    |--------------------------------------------------------------------------
    */

    public function listarStatus(): array
    {
        $sql = "
            SELECT
                id,
                tipo,
                nome,
                descricao

            FROM status

            WHERE tipo = 'perfil'

            ORDER BY
                CASE
                    WHEN nome = 'Activo'
                    THEN 1

                    WHEN nome = 'Inactivo'
                    THEN 2

                    ELSE 3
                END,
                nome ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Obter estado por nome
    |--------------------------------------------------------------------------
    */

    public function buscarStatusId(
        string $nome
    ): ?int {
        $sql = "
            SELECT id

            FROM status

            WHERE tipo = 'perfil'
              AND nome = :nome

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':nome' => $nome
        ]);

        $id = $stmt->fetchColumn();

        return $id !== false
            ? (int) $id
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar estado válido
    |--------------------------------------------------------------------------
    */

    public function statusExiste(
        int $statusId
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM status

            WHERE id = :status_id
              AND tipo = 'perfil'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':status_id' => $statusId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
