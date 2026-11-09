<?php

declare(strict_types=1);

final class Permissao
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

    public function listarPerfis(): array
    {
        $sql = "
            SELECT
                p.id,
                p.nome

            FROM perfis p

            LEFT JOIN status s
                ON s.id = p.status_id
               AND s.tipo = 'perfil'

            WHERE p.status_id IS NULL
               OR s.nome IN ('Activo', 'Ativo')

            ORDER BY p.nome ASC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar perfil
    |--------------------------------------------------------------------------
    */

    public function perfilExiste(
        int $perfilId
    ): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM perfis

            WHERE id = :perfil_id
        ");

        $stmt->execute([
            ':perfil_id' => $perfilId
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Listar módulos e permissões do perfil
    |--------------------------------------------------------------------------
    */

    public function listarPorPerfil(
        int $perfilId
    ): array {
        $sql = "
            SELECT
                m.id AS modulo_id,
                m.nome AS modulo_nome,

                COALESCE(p.visualizar, 0)
                    AS visualizar,

                COALESCE(p.criar, 0)
                    AS criar,

                COALESCE(p.editar, 0)
                    AS editar,

                COALESCE(p.eliminar, 0)
                    AS eliminar,

                COALESCE(p.exportar, 0)
                    AS exportar

            FROM modulos m

            LEFT JOIN permissoes p
                ON p.modulo_id = m.id
               AND p.perfil_id = :perfil_id

            ORDER BY
                m.nome ASC,
                m.id ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':perfil_id' => $perfilId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar permissões
    |--------------------------------------------------------------------------
    */

    public function salvar(
        int $perfilId,
        array $itens
    ): void {
        try {
            $this->db->beginTransaction();

            $sql = "
                INSERT INTO permissoes (
                    perfil_id,
                    modulo_id,
                    visualizar,
                    criar,
                    editar,
                    eliminar,
                    exportar
                ) VALUES (
                    :perfil_id,
                    :modulo_id,
                    :visualizar,
                    :criar,
                    :editar,
                    :eliminar,
                    :exportar
                )

                ON DUPLICATE KEY UPDATE
                    visualizar =
                        VALUES(visualizar),

                    criar =
                        VALUES(criar),

                    editar =
                        VALUES(editar),

                    eliminar =
                        VALUES(eliminar),

                    exportar =
                        VALUES(exportar)
            ";

            $stmt =
                $this->db->prepare($sql);

            foreach ($itens as $item) {
                $moduloId =
                    (int) (
                        $item['modulo_id']
                        ?? 0
                    );

                if ($moduloId <= 0) {
                    throw new InvalidArgumentException(
                        'Foi informado um módulo inválido.'
                    );
                }

                $stmt->execute([
                    ':perfil_id' =>
                    $perfilId,

                    ':modulo_id' =>
                    $moduloId,

                    ':visualizar' =>
                    !empty($item['visualizar']) ? 1 : 0,

                    ':criar' =>
                    !empty($item['criar']) ? 1 : 0,

                    ':editar' =>
                    !empty($item['editar']) ? 1 : 0,

                    ':eliminar' =>
                    !empty($item['eliminar']) ? 1 : 0,

                    ':exportar' =>
                    !empty($item['exportar']) ? 1 : 0
                ]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar acesso de visualização
    |--------------------------------------------------------------------------
    */

    public function possuiModulo(
        int $perfilId,
        int $moduloId
    ): bool {
        $sql = "
            SELECT visualizar

            FROM permissoes

            WHERE perfil_id = :perfil_id
              AND modulo_id = :modulo_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':perfil_id' => $perfilId,
            ':modulo_id' => $moduloId
        ]);

        return (int) $stmt->fetchColumn() === 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar ação específica
    |--------------------------------------------------------------------------
    */

    public function possuiAcao(
        int $perfilId,
        int $moduloId,
        string $acao
    ): bool {
        $acoesPermitidas = [
            'visualizar',
            'criar',
            'editar',
            'eliminar',
            'exportar'
        ];

        $acao =
            strtolower(
                trim($acao)
            );

        if (
            !in_array(
                $acao,
                $acoesPermitidas,
                true
            )
        ) {
            return false;
        }

        /*
         * A coluna é validada antes de entrar no SQL.
         */
        $sql = "
            SELECT {$acao}

            FROM permissoes

            WHERE perfil_id = :perfil_id
              AND modulo_id = :modulo_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':perfil_id' => $perfilId,
            ':modulo_id' => $moduloId
        ]);

        return (int) $stmt->fetchColumn() === 1;
    }
}
