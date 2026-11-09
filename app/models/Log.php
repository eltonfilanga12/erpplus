<?php

declare(strict_types=1);

final class Log
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
        int $empresaId,
        array $filtros = []
    ): array {
        $sql = "
        SELECT
            l.id,
            l.usuario_id,
            l.acao,
            l.descricao,
            l.ip,
            l.criado_em,

            COALESCE(
                u.nome,
                'Sistema'
            ) AS usuario_nome

        FROM logs l

        LEFT JOIN usuarios u
            ON u.id = l.usuario_id

        WHERE l.empresa_id = :empresa_id
    ";

        $parametros = [
            ':empresa_id' => $empresaId
        ];


        if (
            isset($filtros['usuario_id'])
            && $filtros['usuario_id'] !== null
        ) {
            $sql .= "
            AND l.usuario_id = :usuario_id
        ";

            $parametros[':usuario_id'] =
                (int)$filtros['usuario_id'];
        }


        if (
            isset($filtros['acao_log'])
            && $filtros['acao_log'] !== null
            && trim(
                (string)$filtros['acao_log']
            ) !== ''
        ) {
            $sql .= "
            AND l.acao = :acao
        ";

            $parametros[':acao_log'] =
                trim(
                    (string)$filtros['acao_log']
                );
        }


        if (
            isset($filtros['data_inicial'])
            && $filtros['data_inicial'] !== null
            && trim(
                (string)$filtros['data_inicial']
            ) !== ''
        ) {
            $sql .= "
            AND DATE(l.criado_em) >= :data_inicial
        ";

            $parametros[':data_inicial'] =
                $filtros['data_inicial'];
        }


        if (
            isset($filtros['data_final'])
            && $filtros['data_final'] !== null
            && trim(
                (string)$filtros['data_final']
            ) !== ''
        ) {
            $sql .= "
            AND DATE(l.criado_em) <= :data_final
        ";

            $parametros[':data_final'] =
                $filtros['data_final'];
        }


        if (
            isset($filtros['pesquisa'])
            && $filtros['pesquisa'] !== null
            && trim(
                (string)$filtros['pesquisa']
            ) !== ''
        ) {
            $sql .= "
            AND (
                l.acao LIKE :pesquisa
                OR l.descricao LIKE :pesquisa
                OR l.ip LIKE :pesquisa
                OR u.nome LIKE :pesquisa
            )
        ";

            $parametros[':pesquisa'] =
                '%'
                . trim(
                    (string)$filtros['pesquisa']
                )
                . '%';
        }


        $sql .= "
        ORDER BY
            l.criado_em DESC,
            l.id DESC
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


    public function listarUtilizadores(
        int $empresaId
    ): array {
        $sql = "
            SELECT DISTINCT
                u.id,
                u.nome

            FROM logs l

            INNER JOIN usuarios u
                ON u.id = l.usuario_id

            WHERE l.empresa_id = :empresa_id

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


    public function listarAcoes(
        int $empresaId
    ): array {
        $sql = "
            SELECT DISTINCT
                acao

            FROM logs

            WHERE empresa_id = :empresa_id
              AND acao IS NOT NULL
              AND TRIM(acao) <> ''

            ORDER BY
                acao ASC
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
            PDO::FETCH_COLUMN
        );
    }
}
