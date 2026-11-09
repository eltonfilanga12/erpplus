<?php

declare(strict_types=1);

final class Modulo
{
    public static function listar(): array
    {
        $pdo = Database::conn();

        $sql = "
            SELECT *
            FROM modulos
            WHERE ativo = 1
            ORDER BY nome
        ";

        return $pdo->query($sql)->fetchAll();
    }

    public static function buscarPorSlug(string $slug): ?array
    {
        $pdo = Database::conn();

        $stmt = $pdo->prepare("
            SELECT *
            FROM modulos
            WHERE slug = :slug
            LIMIT 1
        ");

        $stmt->execute([
            ':slug' => $slug
        ]);

        $row = $stmt->fetch();

        return $row ?: null;
    }
}
