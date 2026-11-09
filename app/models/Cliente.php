<?php

declare(strict_types=1);

final class Cliente
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
                c.id,
                c.empresa_id,
                c.status_id,
                c.tipo,
                c.nome,
                c.nome_comercial,
                c.nuit,
                c.email,
                c.telefone,
                c.contacto,
                c.endereco,
                c.bairro,
                c.cidade,
                c.provincia,
                c.pais,
                c.limite_credito,
                c.saldo_credito,
                c.observacoes,
                c.criado_em,
                c.atualizado_em,

                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM clientes c

            INNER JOIN status s
                ON s.id = c.status_id
               AND s.tipo = 'cliente'

            WHERE c.empresa_id = :empresa_id

            ORDER BY
                c.nome ASC,
                c.id DESC
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

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {
        $sql = "
            SELECT
                c.*,
                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM clientes c

            INNER JOIN status s
                ON s.id = c.status_id
               AND s.tipo = 'cliente'

            WHERE c.id = :id
              AND c.empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':empresa_id' => $empresaId
        ]);

        $cliente =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        return $cliente ?: null;
    }

    // Adicionar em Cliente.php

    public function obterStatusPadraoId(): ?int
    {
        $sql = "
        SELECT id 
        FROM status 
        WHERE tipo = 'cliente' 
          AND LOWER(nome) IN ('activo', 'ativo')
        LIMIT 1
    ";

        $stmt = $this->db->query($sql);
        $id = $stmt->fetchColumn();

        return $id ? (int)$id : null;
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

            WHERE tipo = 'cliente'

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

                    WHEN nome = 'Bloqueado'
                    THEN 3

                    WHEN nome = 'Suspenso'
                    THEN 4

                    ELSE 5
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
        $stmt =
            $this->db->prepare("
                SELECT COUNT(*)

                FROM status

                WHERE id = :status_id
                  AND tipo = 'cliente'
            ");

        $stmt->execute([
            ':status_id' => $statusId
        ]);

        return (int) $stmt
            ->fetchColumn() > 0;
    }

    public function nuitExiste(
        int $empresaId,
        string $nuit,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM clientes

            WHERE empresa_id = :empresa_id
              AND nuit = :nuit
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
            ':nuit' => $nuit
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

        return (int) $stmt
            ->fetchColumn() > 0;
    }

    public function emailExiste(
        int $empresaId,
        string $email,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM clientes

            WHERE empresa_id = :empresa_id
              AND LOWER(email) = LOWER(:email)
        ";

        $parametros = [
            ':empresa_id' => $empresaId,
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

        return (int) $stmt
            ->fetchColumn() > 0;
    }

    public function criar(
        int $empresaId,
        array $dados
    ): int {
        $sql = "
            INSERT INTO clientes (
                empresa_id,
                status_id,
                tipo,
                nome,
                nome_comercial,
                nuit,
                email,
                telefone,
                contacto,
                endereco,
                bairro,
                cidade,
                provincia,
                pais,
                limite_credito,
                observacoes
            ) VALUES (
                :empresa_id,
                :status_id,
                :tipo,
                :nome,
                :nome_comercial,
                :nuit,
                :email,
                :telefone,
                :contacto,
                :endereco,
                :bairro,
                :cidade,
                :provincia,
                :pais,
                :limite_credito,
                :observacoes
            )
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':empresa_id' =>
            $empresaId,

            ':status_id' =>
            $dados['status_id'],

            ':tipo' =>
            $dados['tipo'],

            ':nome' =>
            $dados['nome'],

            ':nome_comercial' =>
            $dados['nome_comercial'],

            ':nuit' =>
            $dados['nuit'],

            ':email' =>
            $dados['email'],

            ':telefone' =>
            $dados['telefone'],

            ':contacto' =>
            $dados['contacto'],

            ':endereco' =>
            $dados['endereco'],

            ':bairro' =>
            $dados['bairro'],

            ':cidade' =>
            $dados['cidade'],

            ':provincia' =>
            $dados['provincia'],

            ':pais' =>
            $dados['pais'],

            ':limite_credito' =>
            $dados['limite_credito'],

            ':observacoes' =>
            $dados['observacoes']
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
            UPDATE clientes

            SET
                status_id = :status_id,
                tipo = :tipo,
                nome = :nome,
                nome_comercial = :nome_comercial,
                nuit = :nuit,
                email = :email,
                telefone = :telefone,
                contacto = :contacto,
                endereco = :endereco,
                bairro = :bairro,
                cidade = :cidade,
                provincia = :provincia,
                pais = :pais,
                limite_credito = :limite_credito,
                observacoes = :observacoes

            WHERE id = :id
              AND empresa_id = :empresa_id

            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        return $stmt->execute([
            ':status_id' =>
            $dados['status_id'],

            ':tipo' =>
            $dados['tipo'],

            ':nome' =>
            $dados['nome'],

            ':nome_comercial' =>
            $dados['nome_comercial'],

            ':nuit' =>
            $dados['nuit'],

            ':email' =>
            $dados['email'],

            ':telefone' =>
            $dados['telefone'],

            ':contacto' =>
            $dados['contacto'],

            ':endereco' =>
            $dados['endereco'],

            ':bairro' =>
            $dados['bairro'],

            ':cidade' =>
            $dados['cidade'],

            ':provincia' =>
            $dados['provincia'],

            ':pais' =>
            $dados['pais'],

            ':limite_credito' =>
            $dados['limite_credito'],

            ':observacoes' =>
            $dados['observacoes'],

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
        $stmt =
            $this->db->prepare("
                DELETE FROM clientes

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
