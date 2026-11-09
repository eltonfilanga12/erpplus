<?php

declare(strict_types=1);

final class Menu
{
    public static function listar(): array
    {
        $pdo = Database::conn();

        $sql = "

        SELECT *

        FROM menus

        WHERE ativo = 1

        ORDER BY menu_pai, ordem

        ";

        return $pdo->query($sql)->fetchAll();
    }

    public static function listarPorPerfil(int $perfilId): array
    {
        $pdo = Database::conn();

        $sql = "

        SELECT

            menus.*

        FROM menus

        INNER JOIN permissoes

            ON permissoes.modulo_id = menus.modulo_id

        WHERE

            permissoes.perfil_id = :perfil

            AND permissoes.visualizar = 1

            AND menus.ativo = 1

        ORDER BY

            menus.menu_pai,
            menus.ordem

        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':perfil' => $perfilId
        ]);

        return $stmt->fetchAll();
    }
}
