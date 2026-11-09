<?php

declare(strict_types=1);

final class Armazem
{
    private PDO $db;

    public function __construct(
        PDO $db
    ) {
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
                a.id,
                a.empresa_id,
                a.filial_id,
                a.status_id,
                a.nome,
                a.codigo,
                a.endereco,
                a.responsavel,
                a.telefone,
                a.criado_em,
                a.atualizado_em,

                f.nome AS filial_nome,

                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM armazens a

            LEFT JOIN filiais f
                ON f.id = a.filial_id
               AND f.empresa_id = a.empresa_id

            INNER JOIN status s
                ON s.id = a.status_id
               AND s.tipo = 'armazem'

            WHERE a.empresa_id = :empresa_id

            ORDER BY
                a.nome ASC,
                a.id DESC
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
                a.*,
                f.nome AS filial_nome,
                s.nome AS status_nome

            FROM armazens a

            LEFT JOIN filiais f
                ON f.id = a.filial_id
               AND f.empresa_id = a.empresa_id

            INNER JOIN status s
                ON s.id = a.status_id
               AND s.tipo = 'armazem'

            WHERE a.id = :id
              AND a.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $armazem = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $armazem ?: null;
    }

    public function listarFiliais(
        int $empresaId
    ): array {
        $sql = "
            SELECT
                f.id,
                f.nome,
                f.codigo

            FROM filiais f

            INNER JOIN status s
                ON s.id = f.status_id
               AND s.tipo = 'filial'

            WHERE f.empresa_id = :empresa_id
              AND s.nome IN (
                  'Activa',
                  'Ativa'
              )

            ORDER BY f.nome ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    public function filialPertenceEmpresa(
        int $filialId,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM filiais

            WHERE id = :filial_id
              AND empresa_id = :empresa_id
        ");

        $stmt->execute([
            ':filial_id' => $filialId,
            ':empresa_id' => $empresaId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function statusExiste(
        int $statusId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM status

            WHERE id = :status_id
              AND tipo = 'armazem'
        ");

        $stmt->execute([
            ':status_id' => $statusId
        ]);

        return (int) $stmt->fetchColumn() > 0;
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

            WHERE tipo = 'armazem'

            ORDER BY
                CASE
                    WHEN nome IN (
                        'Activo',
                        'Ativo'
                    ) THEN 1

                    WHEN nome IN (
                        'Inactivo',
                        'Inativo'
                    ) THEN 2

                    WHEN nome = 'Suspenso'
                    THEN 3

                    WHEN nome = 'Encerrado'
                    THEN 4

                    ELSE 5
                END,
                nome ASC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function codigoExiste(
        int $empresaId,
        string $codigo,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM armazens

            WHERE empresa_id = :empresa_id
              AND codigo = :codigo
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':codigo' => $codigo
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

    public function nomeExiste(
        int $empresaId,
        string $nome,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM armazens

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

    public function criar(
        int $empresaId,
        array $dados
    ): int {
        $sql = "
            INSERT INTO armazens (
                empresa_id,
                filial_id,
                status_id,
                nome,
                codigo,
                endereco,
                responsavel,
                telefone
            ) VALUES (
                :empresa_id,
                :filial_id,
                :status_id,
                :nome,
                :codigo,
                :endereco,
                :responsavel,
                :telefone
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' => $empresaId,
            ':filial_id' => $dados['filial_id'],
            ':status_id' => $dados['status_id'],
            ':nome' => $dados['nome'],
            ':codigo' => $dados['codigo'],
            ':endereco' => $dados['endereco'],
            ':responsavel' => $dados['responsavel'],
            ':telefone' => $dados['telefone']
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function atualizar(
        int $id,
        int $empresaId,
        array $dados
    ): bool {
        $sql = "
            UPDATE armazens

            SET
                filial_id = :filial_id,
                status_id = :status_id,
                nome = :nome,
                codigo = :codigo,
                endereco = :endereco,
                responsavel = :responsavel,
                telefone = :telefone

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':filial_id' => $dados['filial_id'],
            ':status_id' => $dados['status_id'],
            ':nome' => $dados['nome'],
            ':codigo' => $dados['codigo'],
            ':endereco' => $dados['endereco'],
            ':responsavel' => $dados['responsavel'],
            ':telefone' => $dados['telefone'],
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);
    }

    public function eliminar(
        int $id,
        int $empresaId
    ): bool {
        $stmt = $this->db->prepare("
            DELETE FROM armazens

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
