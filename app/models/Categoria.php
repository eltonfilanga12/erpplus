<?php

declare(strict_types=1);

final class Categoria
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;

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
                c.id,
                c.empresa_id,
                c.categoria_pai_id,
                c.status_id,
                c.nome,
                c.descricao,
                c.criado_em,
                c.atualizado_em,

                cp.nome AS categoria_pai_nome,

                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM categorias c

            LEFT JOIN categorias cp
                ON cp.id = c.categoria_pai_id
               AND cp.empresa_id = c.empresa_id

            INNER JOIN status s
                ON s.id = c.status_id
               AND s.tipo = 'categoria'

            WHERE c.empresa_id = :empresa_id

            ORDER BY
                COALESCE(cp.nome, c.nome) ASC,
                c.categoria_pai_id IS NOT NULL,
                c.nome ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {
        $sql = "
            SELECT
                c.*,
                cp.nome AS categoria_pai_nome,
                s.nome AS status_nome

            FROM categorias c

            LEFT JOIN categorias cp
                ON cp.id = c.categoria_pai_id
               AND cp.empresa_id = c.empresa_id

            INNER JOIN status s
                ON s.id = c.status_id
               AND s.tipo = 'categoria'

            WHERE c.id = :id
              AND c.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $categoria = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $categoria ?: null;
    }

    public function listarOpcoes(
        int $empresaId,
        ?int $ignorarId = null
    ): array {
        $sql = "
            SELECT
                c.id,
                c.nome,
                c.categoria_pai_id,
                cp.nome AS categoria_pai_nome

            FROM categorias c

            LEFT JOIN categorias cp
                ON cp.id = c.categoria_pai_id
               AND cp.empresa_id = c.empresa_id

            WHERE c.empresa_id = :empresa_id
        ";

        $parametros = [
            ':empresa_id' => $empresaId
        ];

        if ($ignorarId !== null) {
            $sql .= "
                AND c.id <> :ignorar_id
            ";

            $parametros[':ignorar_id'] =
                $ignorarId;
        }

        $sql .= "
            ORDER BY
                c.categoria_pai_id IS NOT NULL,
                COALESCE(cp.nome, c.nome),
                c.nome
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function listarStatus(): array
    {
        $sql = "
            SELECT
                id,
                tipo,
                nome,
                descricao

            FROM status

            WHERE tipo = 'categoria'

            ORDER BY
                CASE
                    WHEN nome IN (
                        'Activa',
                        'Ativa'
                    ) THEN 1

                    WHEN nome IN (
                        'Inactiva',
                        'Inativa'
                    ) THEN 2

                    WHEN nome = 'Arquivada'
                    THEN 3

                    ELSE 4
                END,
                nome ASC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function statusExiste(
        int $statusId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM status

            WHERE id = :status_id
              AND tipo = 'categoria'
        ");

        $stmt->execute([
            ':status_id' => $statusId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function categoriaPertenceEmpresa(
        int $categoriaId,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM categorias

            WHERE id = :categoria_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':categoria_id' => $categoriaId,
            ':empresa_id' => $empresaId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function nomeExiste(
        int $empresaId,
        string $nome,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM categorias

            WHERE empresa_id = :empresa_id
              AND LOWER(TRIM(nome)) =
                  LOWER(TRIM(:nome))
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
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

    public function possuiFilhas(
        int $id,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM categorias

            WHERE categoria_pai_id = :id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function criariaCiclo(
        int $categoriaId,
        int $categoriaPaiId,
        int $empresaId
    ): bool {
        $actualId = $categoriaPaiId;

        while ($actualId > 0) {
            if ($actualId === $categoriaId) {
                return true;
            }

            $stmt = $this->db->prepare("
                SELECT categoria_pai_id

                FROM categorias

                WHERE id = :id
                  AND empresa_id = :empresa_id

                LIMIT 1
            ");

            $stmt->execute([
                ':id' => $actualId,
                ':empresa_id' => $empresaId
            ]);

            $pai = $stmt->fetchColumn();

            if (
                $pai === false
                || $pai === null
            ) {
                break;
            }

            $actualId = (int) $pai;
        }

        return false;
    }

    public function criar(
        int $empresaId,
        array $dados
    ): int {
        $sql = "
            INSERT INTO categorias (
                empresa_id,
                categoria_pai_id,
                status_id,
                nome,
                descricao
            ) VALUES (
                :empresa_id,
                :categoria_pai_id,
                :status_id,
                :nome,
                :descricao
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':categoria_pai_id' =>
            $dados['categoria_pai_id'],

            ':status_id' =>
            $dados['status_id'],

            ':nome' =>
            $dados['nome'],

            ':descricao' =>
            $dados['descricao']
        ]);

        return (int) $this->db
            ->lastInsertId();
    }

    public function atualizar(
        int $id,
        int $empresaId,
        array $dados
    ): bool {
        $sql = "
            UPDATE categorias

            SET
                categoria_pai_id = :categoria_pai_id,
                status_id = :status_id,
                nome = :nome,
                descricao = :descricao

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':categoria_pai_id' =>
            $dados['categoria_pai_id'],

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
    }

    public function eliminar(
        int $id,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            DELETE FROM categorias

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ");

        return $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);
    }
}
