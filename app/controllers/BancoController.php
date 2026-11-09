<?php

declare(strict_types=1);

final class BancoController
{
    private Banco $banco;

    public function __construct(PDO $pdo)
    {
        $this->banco = new Banco($pdo);
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(array $dados = []): array
    {
        $empresaId = (int)Auth::empresaId();

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        $filialId = !empty($dados['filial_id'])
            ? (int)$dados['filial_id']
            : null;

        return $this->banco->listar(
            $empresaId,
            $filialId
        );
    }

    // ==================================================
    // FILIAIS
    // ==================================================

    public function filiais(): array
    {
        $empresaId =
            (int)Auth::empresaId();


        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }


        return $this->banco
            ->listarFiliais(
                $empresaId
            );
    }

    public function listarStatus(): array
    {
        return $this->banco->listarStatus();
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(array $dados): ?array
    {
        $id = (int)($dados['id'] ?? 0);
        $empresaId = (int)Auth::empresaId();

        if ($id <= 0) {
            throw new RuntimeException(
                'Banco inválido.'
            );
        }

        return $this->banco->buscar(
            $id,
            $empresaId
        );
    }

    // ==================================================
    // GUARDAR
    // ==================================================

    public function guardar(array $dados): bool
    {
        $dados['empresa_id'] = (int)Auth::empresaId();
        $dados['usuario_id'] = (int)Auth::usuarioId();

        if (!empty($dados['id'])) {
            return $this->banco->atualizar(
                $dados
            );
        }

        return $this->banco->criar(
            $dados
        );
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(array $dados): bool
    {
        $id = (int)($dados['id'] ?? 0);
        $empresaId = (int)Auth::empresaId();
        $usuarioId = (int)Auth::usuarioId();

        if ($id <= 0) {
            throw new RuntimeException(
                'Banco inválido.'
            );
        }

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        return $this->banco->eliminar(
            $id,
            $empresaId,
            $usuarioId
        );
    }

    // ==================================================
    // SALDO
    // ==================================================

    public function saldo(array $dados): float
    {
        return $this->banco->saldoAtual(
            (int)($dados['id'] ?? 0),
            (int)Auth::empresaId()
        );
    }
}
