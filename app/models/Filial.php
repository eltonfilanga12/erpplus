<?php

declare(strict_types=1);

final class Filial
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

    /*
    |--------------------------------------------------------------------------
    | Listar
    |--------------------------------------------------------------------------
    */

    public function listar(
        int $empresaId
    ): array {
        $sql = "
            SELECT
                f.id,
                f.empresa_id,
                f.status_id,
                f.nome,
                f.codigo,
                f.email,
                f.telefone,
                f.provincia,
                f.cidade,
                f.bairro,
                f.endereco,
                f.gerente,
                f.criado_em,
                f.atualizado_em,

                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM filiais f

            INNER JOIN status s
                ON s.id = f.status_id
               AND s.tipo = 'filial'

            WHERE f.empresa_id = :empresa_id

            ORDER BY
                f.nome ASC,
                f.id DESC
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
    | Buscar
    |--------------------------------------------------------------------------
    */

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {
        $sql = "
            SELECT
                f.id,
                f.empresa_id,
                f.status_id,
                f.nome,
                f.codigo,
                f.email,
                f.telefone,
                f.provincia,
                f.cidade,
                f.bairro,
                f.endereco,
                f.gerente,
                f.criado_em,
                f.atualizado_em,

                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM filiais f

            INNER JOIN status s
                ON s.id = f.status_id
               AND s.tipo = 'filial'

            WHERE f.id = :id
              AND f.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $filial =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return $filial ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar código duplicado
    |--------------------------------------------------------------------------
    */

    public function codigoExiste(
        int $empresaId,
        string $codigo,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM filiais

            WHERE empresa_id = :empresa_id
              AND codigo = :codigo
        ";

        $parametros = [
            ':empresa_id' =>
            $empresaId,

            ':codigo' =>
            $codigo
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
    | Verificar nome duplicado
    |--------------------------------------------------------------------------
    */

    public function nomeExiste(
        int $empresaId,
        string $nome,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM filiais

            WHERE empresa_id = :empresa_id
              AND LOWER(TRIM(nome)) =
                  LOWER(TRIM(:nome))
        ";

        $parametros = [
            ':empresa_id' =>
            $empresaId,

            ':nome' =>
            $nome
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
    | Verificar estado
    |--------------------------------------------------------------------------
    */

    public function statusExiste(
        int $statusId
    ): bool {
        $stmt =
            $this->db->prepare("
                SELECT COUNT(*)

                FROM status

                WHERE id = :status_id
                  AND tipo = 'filial'
            ");

        $stmt->execute([
            ':status_id' => $statusId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Listar estados
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

            WHERE tipo = 'filial'

            ORDER BY
                CASE
                    WHEN nome IN (
                        'Activa',
                        'Ativa'
                    )
                    THEN 1

                    WHEN nome IN (
                        'Inactiva',
                        'Inativa'
                    )
                    THEN 2

                    WHEN nome = 'Suspensa'
                    THEN 3

                    WHEN nome = 'Encerrada'
                    THEN 4

                    ELSE 5
                END,
                nome ASC
        ";

        $stmt =
            $this->db->query($sql);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Criar
    |--------------------------------------------------------------------------
    */

    public function criar(
        int $empresaId,
        array $dados
    ): int {
        $sql = "
            INSERT INTO filiais (
                empresa_id,
                status_id,
                nome,
                codigo,
                email,
                telefone,
                provincia,
                cidade,
                bairro,
                endereco,
                gerente
            ) VALUES (
                :empresa_id,
                :status_id,
                :nome,
                :codigo,
                :email,
                :telefone,
                :provincia,
                :cidade,
                :bairro,
                :endereco,
                :gerente
            )
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':status_id' =>
            $dados['status_id'],

            ':nome' =>
            $dados['nome'],

            ':codigo' =>
            $dados['codigo'],

            ':email' =>
            $dados['email'],

            ':telefone' =>
            $dados['telefone'],

            ':provincia' =>
            $dados['provincia'],

            ':cidade' =>
            $dados['cidade'],

            ':bairro' =>
            $dados['bairro'],

            ':endereco' =>
            $dados['endereco'],

            ':gerente' =>
            $dados['gerente']
        ]);

        return (int) $this->db
            ->lastInsertId();
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
            UPDATE filiais

            SET
                status_id = :status_id,
                nome = :nome,
                codigo = :codigo,
                email = :email,
                telefone = :telefone,
                provincia = :provincia,
                cidade = :cidade,
                bairro = :bairro,
                endereco = :endereco,
                gerente = :gerente

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        return $stmt->execute([
            ':status_id' =>
            $dados['status_id'],

            ':nome' =>
            $dados['nome'],

            ':codigo' =>
            $dados['codigo'],

            ':email' =>
            $dados['email'],

            ':telefone' =>
            $dados['telefone'],

            ':provincia' =>
            $dados['provincia'],

            ':cidade' =>
            $dados['cidade'],

            ':bairro' =>
            $dados['bairro'],

            ':endereco' =>
            $dados['endereco'],

            ':gerente' =>
            $dados['gerente'],

            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);
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
        $stmt =
            $this->db->prepare("
                DELETE FROM filiais

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
