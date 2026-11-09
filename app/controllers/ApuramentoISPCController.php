<?php

declare(strict_types=1);

final class ApuramentoISPCController
{
    private ApuramentoISPC $apuramentoModel;

    public function __construct(PDO $pdo)
    {
        $this->apuramentoModel = new ApuramentoISPC($pdo);
    }

    public function listar(array $dados = []): array
    {
        $empresaId = (int)Auth::empresaId();
        if ($empresaId <= 0) {
            throw new RuntimeException('Empresa do utilizador não encontrada.');
        }
        $filialId = !empty($dados['filial_id']) ? (int)$dados['filial_id'] : null;

        return $this->apuramentoModel->listar($empresaId, $filialId);
    }

    public function buscar(array $dados): ?array
    {
        $id = (int)($dados['id'] ?? 0);
        $empresaId = (int)Auth::empresaId();
        if ($id <= 0) {
            throw new RuntimeException('Apuramento inválido.');
        }
        return $this->apuramentoModel->buscar($id, $empresaId);
    }

    public function apurar(array $dados): bool
    {
        $dados['empresa_id'] = (int)Auth::empresaId();
        $dados['usuario_id'] = (int)Auth::usuarioId();

        if (empty($dados['exercicio_fiscal'])) {
            throw new RuntimeException('Informe o exercício fiscal para o apuramento.');
        }

        return $this->apuramentoModel->calcularEApurar($dados);
    }
}