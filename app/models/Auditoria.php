<?php

declare(strict_types=1);

final class Auditoria
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
        int $empresaId,
        array $filtros = []
    ): array {
        $sql = "
            SELECT
                a.id,
                a.usuario_id,
                a.operacao,
                a.criado_em,

                COALESCE(
                    u.nome,
                    'Sistema'
                ) AS usuario_nome

            FROM auditoria a

            LEFT JOIN usuarios u
                ON u.id = a.usuario_id

            WHERE a.empresa_id = :empresa_id
        ";

        $parametros = [
            ':empresa_id' =>
            $empresaId
        ];


        /*
        |--------------------------------------------------------------------------
        | Utilizador
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filtros['usuario_id'])
        ) {
            $sql .= "
                AND a.usuario_id =
                    :usuario_id
            ";

            $parametros[':usuario_id'] =
                (int)$filtros['usuario_id'];
        }


        /*
        |--------------------------------------------------------------------------
        | Operação
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filtros['operacao'])
        ) {
            $sql .= "
                AND a.operacao =
                    :operacao
            ";

            $parametros[':operacao'] =
                strtoupper(
                    trim(
                        (string)$filtros['operacao']
                    )
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Data inicial
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filtros['data_inicial'])
        ) {
            $sql .= "
                AND a.criado_em >=
                    :data_inicial
            ";

            $parametros[':data_inicial'] =
                $filtros['data_inicial']
                . ' 00:00:00';
        }


        /*
        |--------------------------------------------------------------------------
        | Data final
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filtros['data_final'])
        ) {
            $sql .= "
                AND a.criado_em <=
                    :data_final
            ";

            $parametros[':data_final'] =
                $filtros['data_final']
                . ' 23:59:59';
        }


        $sql .= "
            ORDER BY
                a.criado_em DESC,
                a.id DESC
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute(
            $parametros
        );


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Buscar detalhe
    |--------------------------------------------------------------------------
    */

    public function buscar(
        int $id,
        int $empresaId
    ): ?array {
        $sql = "
            SELECT
                a.id,
                a.empresa_id,
                a.usuario_id,
                a.tabela,
                a.registro_id,
                a.operacao,
                a.dados_anteriores,
                a.dados_novos,
                a.criado_em,

                COALESCE(
                    u.nome,
                    'Sistema'
                ) AS usuario_nome

            FROM auditoria a

            LEFT JOIN usuarios u
                ON u.id = a.usuario_id

            WHERE a.id = :id
              AND a.empresa_id = :empresa_id

            LIMIT 1
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute([
            ':id' =>
            $id,

            ':empresa_id' =>
            $empresaId
        ]);


        $dados =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $dados ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | Utilizadores que possuem auditoria
    |--------------------------------------------------------------------------
    */

    public function listarUtilizadores(
        int $empresaId
    ): array {
        $sql = "
            SELECT DISTINCT
                u.id,
                u.nome

            FROM auditoria a

            INNER JOIN usuarios u
                ON u.id = a.usuario_id

            WHERE a.empresa_id = :empresa_id

            ORDER BY
                u.nome ASC
        ";


        $stmt =
            $this->db->prepare(
                $sql
            );


        $stmt->execute([
            ':empresa_id' =>
            $empresaId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}
