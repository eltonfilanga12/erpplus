<?php

declare(strict_types=1);

final class EscalaoISPCController
{
    private EscalaoISPC $escalaoModel;

    public function __construct(PDO $pdo)
    {
        $this->escalaoModel = new EscalaoISPC($pdo);
    }

    public function listar(array $dados = []): array
    {
        return $this->escalaoModel->listar();
    }
}
