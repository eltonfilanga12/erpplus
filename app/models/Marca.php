<?php

declare(strict_types=1);

final class Marca
{
    private PDO $db;

    public function __construct(
        ?PDO $db = null
    ) {
        $this->db =
            $db
            ?? Database::conn();

        $this->db->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );
    }

    public function listar(
        int $empresaId
    ): array {
        $sql = "
            SELECT
                m.id,
                m.empresa_id,
                m.status_id,
                m.nome,
                m.descricao,
                m.criado_em,
                m.atualizado_em,

                s.nome AS status_nome

            FROM marcas m

            INNER JOIN status s
                ON s.id = m.status_id

            WHERE m.empresa_id = :empresa_id

            ORDER BY
                m.nome ASC,
                m.id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function buscarPorId(
        int $id,
        int $empresaId
    ): ?array {
        $sql = "
            SELECT
                m.*

            FROM marcas m

            WHERE m.id = :id
              AND m.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $marca = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $marca ?: null;
    }

    public function nomeExiste(
        string $nome,
        int $empresaId,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM marcas

            WHERE empresa_id = :empresa_id
              AND LOWER(nome) = LOWER(:nome)
        ";

        $params = [
            ':empresa_id' => $empresaId,
            ':nome' => $nome
        ];

        if (
            $ignorarId !== null
            && $ignorarId > 0
        ) {
            $sql .= "
                AND id <> :ignorar_id
            ";

            $params[':ignorar_id'] =
                $ignorarId;
        }

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function statusExiste(
        int $statusId
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM status

            WHERE id = :id
              AND tipo = 'marca'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $statusId
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function listarStatus(): array
    {
        $sql = "
            SELECT
                id,
                nome,
                descricao

            FROM status

            WHERE tipo = 'marca'

            ORDER BY id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function criar(
        int $empresaId,
        array $dados
    ): int {
        $sql = "
            INSERT INTO marcas (
                empresa_id,
                status_id,
                nome,
                descricao
            )
            VALUES (
                :empresa_id,
                :status_id,
                :nome,
                :descricao
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':status_id' =>
            $dados['status_id'],

            ':nome' =>
            $dados['nome'],

            ':descricao' =>
            $dados['descricao']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function atualizar(
        int $id,
        int $empresaId,
        array $dados
    ): bool {
        $sql = "
            UPDATE marcas

            SET
                status_id = :status_id,
                nome = :nome,
                descricao = :descricao

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':status_id' =>
            $dados['status_id'],

            ':nome' =>
            $dados['nome'],

            ':descricao' =>
            $dados['descricao'],

            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);

        return $stmt->rowCount() >= 0;
    }

    public function eliminar(
        int $id,
        int $empresaId
    ): bool {
        $sql = "
            DELETE FROM marcas

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->rowCount() > 0;
    }
}
