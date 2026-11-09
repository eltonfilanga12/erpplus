<?php

declare(strict_types=1);

final class Unidade
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
                u.id,
                u.empresa_id,
                u.status_id,
                u.codigo,
                u.nome,
                u.casas_decimais,
                u.descricao,
                u.criado_em,
                u.atualizado_em,

                s.nome AS status_nome

            FROM unidades u

            INNER JOIN status s
                ON s.id = u.status_id

            WHERE u.empresa_id = :empresa_id

            ORDER BY
                u.nome ASC,
                u.id DESC
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
            SELECT *
            FROM unidades

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $registro =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return $registro ?: null;
    }


    public function codigoExiste(
        string $codigo,
        int $empresaId,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM unidades

            WHERE empresa_id = :empresa_id
              AND LOWER(codigo) = LOWER(:codigo)
        ";

        $params = [
            ':empresa_id' => $empresaId,
            ':codigo' => $codigo
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

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }


    public function nomeExiste(
        string $nome,
        int $empresaId,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM unidades

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

        $stmt =
            $this->db->prepare($sql);

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
              AND tipo = 'unidade'
        ";

        $stmt =
            $this->db->prepare($sql);

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

            WHERE tipo = 'unidade'

            ORDER BY id ASC
        ";

        $stmt =
            $this->db->prepare($sql);

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
            INSERT INTO unidades (
                empresa_id,
                status_id,
                codigo,
                nome,
                casas_decimais,
                descricao
            )
            VALUES (
                :empresa_id,
                :status_id,
                :codigo,
                :nome,
                :casas_decimais,
                :descricao
            )
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':status_id' =>
            $dados['status_id'],

            ':codigo' =>
            $dados['codigo'],

            ':nome' =>
            $dados['nome'],

            ':casas_decimais' =>
            $dados['casas_decimais'],

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
            UPDATE unidades

            SET
                status_id = :status_id,
                codigo = :codigo,
                nome = :nome,
                casas_decimais = :casas_decimais,
                descricao = :descricao

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':status_id' =>
            $dados['status_id'],

            ':codigo' =>
            $dados['codigo'],

            ':nome' =>
            $dados['nome'],

            ':casas_decimais' =>
            $dados['casas_decimais'],

            ':descricao' =>
            $dados['descricao'],

            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);

        return true;
    }


    public function eliminar(
        int $id,
        int $empresaId
    ): bool {
        $sql = "
            DELETE FROM unidades

            WHERE id = :id
              AND empresa_id = :empresa_id
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        return $stmt->rowCount() > 0;
    }
}
