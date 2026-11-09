<?php

declare(strict_types=1);

final class EscalaoISPC
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listar(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM escaloes_ispc
            ORDER BY id ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
