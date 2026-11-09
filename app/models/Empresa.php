<?php

declare(strict_types=1);

final class Empresa
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

    public function listar(): array
    {
        $sql = "
            SELECT
                e.id,
                e.nome,
                e.nome_fantasia,
                e.nuit,
                e.email,
                e.website,
                e.telefone,
                e.logradouro,
                e.provincia,
                e.cidade,
                e.codigo_postal,
                e.logo,
                e.status_id,
                e.tipo_empresa,
                e.ramo_actividade,
                e.contacto_principal,
                e.slogan,
                e.descricao,
                e.pais,
                e.moeda,
                e.fuso_horario,
                e.criado_em,
                e.atualizado_em,

                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM empresas e

            INNER JOIN status s
                ON s.id = e.status_id
               AND s.tipo = 'empresa'

            ORDER BY
                e.id DESC
        ";

        $stmt = $this->db->query($sql);

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
        int $id
    ): ?array {
        $sql = "
            SELECT
                e.*,
                s.nome AS status_nome,
                s.descricao AS status_descricao

            FROM empresas e

            INNER JOIN status s
                ON s.id = e.status_id
               AND s.tipo = 'empresa'

            WHERE e.id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $empresa = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $empresa ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar NUIT duplicado
    |--------------------------------------------------------------------------
    */

    public function nuitExiste(
        string $nuit,
        ?int $ignorarId = null
    ): bool {
        $sql = "
            SELECT COUNT(*)

            FROM empresas

            WHERE nuit = :nuit
        ";

        $parametros = [
            ':nuit' => $nuit
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
    | Verificar status
    |--------------------------------------------------------------------------
    */

    public function statusExiste(
        int $statusId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM status

            WHERE id = :status_id
              AND tipo = 'empresa'
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

            WHERE tipo = 'empresa'

            ORDER BY
                CASE
                    WHEN nome = 'Activa'
                    THEN 1

                    WHEN nome = 'Inactiva'
                    THEN 2

                    WHEN nome = 'Suspensa'
                    THEN 3

                    WHEN nome = 'Encerrada'
                    THEN 4

                    ELSE 5
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
    | Criar
    |--------------------------------------------------------------------------
    */

    public function criar(
        array $dados
    ): int {
        $sql = "
            INSERT INTO empresas (
                nome,
                nome_fantasia,
                nuit,
                email,
                website,
                telefone,
                logradouro,
                provincia,
                cidade,
                codigo_postal,
                logo,
                status_id,
                tipo_empresa,
                ramo_actividade,
                contacto_principal,
                slogan,
                descricao,
                pais,
                moeda,
                fuso_horario
            ) VALUES (
                :nome,
                :nome_fantasia,
                :nuit,
                :email,
                :website,
                :telefone,
                :logradouro,
                :provincia,
                :cidade,
                :codigo_postal,
                :logo,
                :status_id,
                :tipo_empresa,
                :ramo_actividade,
                :contacto_principal,
                :slogan,
                :descricao,
                :pais,
                :moeda,
                :fuso_horario
            )
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':nome' =>
            $dados['nome'],

            ':nome_fantasia' =>
            $dados['nome_fantasia'],

            ':nuit' =>
            $dados['nuit'],

            ':email' =>
            $dados['email'],

            ':website' =>
            $dados['website'],

            ':telefone' =>
            $dados['telefone'],

            ':logradouro' =>
            $dados['logradouro'],

            ':provincia' =>
            $dados['provincia'],

            ':cidade' =>
            $dados['cidade'],

            ':codigo_postal' =>
            $dados['codigo_postal'],

            ':logo' =>
            $dados['logo'],

            ':status_id' =>
            $dados['status_id'],

            ':tipo_empresa' =>
            $dados['tipo_empresa'],

            ':ramo_actividade' =>
            $dados['ramo_actividade'],

            ':contacto_principal' =>
            $dados['contacto_principal'],

            ':slogan' =>
            $dados['slogan'],

            ':descricao' =>
            $dados['descricao'],

            ':pais' =>
            $dados['pais'],

            ':moeda' =>
            $dados['moeda'],

            ':fuso_horario' =>
            $dados['fuso_horario']
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
            UPDATE empresas

            SET
                nome = :nome,
                nome_fantasia = :nome_fantasia,
                nuit = :nuit,
                email = :email,
                website = :website,
                telefone = :telefone,
                logradouro = :logradouro,
                provincia = :provincia,
                cidade = :cidade,
                codigo_postal = :codigo_postal,
                logo = :logo,
                status_id = :status_id,
                tipo_empresa = :tipo_empresa,
                ramo_actividade = :ramo_actividade,
                contacto_principal = :contacto_principal,
                slogan = :slogan,
                descricao = :descricao,
                pais = :pais,
                moeda = :moeda,
                fuso_horario = :fuso_horario

            WHERE id = :id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nome' =>
            $dados['nome'],

            ':nome_fantasia' =>
            $dados['nome_fantasia'],

            ':nuit' =>
            $dados['nuit'],

            ':email' =>
            $dados['email'],

            ':website' =>
            $dados['website'],

            ':telefone' =>
            $dados['telefone'],

            ':logradouro' =>
            $dados['logradouro'],

            ':provincia' =>
            $dados['provincia'],

            ':cidade' =>
            $dados['cidade'],

            ':codigo_postal' =>
            $dados['codigo_postal'],

            ':logo' =>
            $dados['logo'],

            ':status_id' =>
            $dados['status_id'],

            ':tipo_empresa' =>
            $dados['tipo_empresa'],

            ':ramo_actividade' =>
            $dados['ramo_actividade'],

            ':contacto_principal' =>
            $dados['contacto_principal'],

            ':slogan' =>
            $dados['slogan'],

            ':descricao' =>
            $dados['descricao'],

            ':pais' =>
            $dados['pais'],

            ':moeda' =>
            $dados['moeda'],

            ':fuso_horario' =>
            $dados['fuso_horario'],

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
        $stmt = $this->db->prepare("
            DELETE FROM empresas
            WHERE id = :id
            LIMIT 1
        ");

        return $stmt->execute([
            ':id' => $id
        ]);
    }
}
