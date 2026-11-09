<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

final class CaixaController
{
    private Caixa $caixa;

    public function __construct(PDO $pdo)
    {
        $this->caixa = new Caixa($pdo);
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(): array
    {
        return $this->caixa->listar(
            $this->empresaId(),
            $this->filialId()
        );
    }

    // ==================================================
    // OBTER
    // ==================================================

    public function obter(int $id): ?array
    {
        if ($id <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }

        return $this->caixa->buscar(
            $id,
            $this->empresaId()
        );
    }

    // ==================================================
    // STATUS
    // ==================================================

    public function status(): array
    {
        return $this->caixa->listarStatus();
    }

    // ==================================================
    // GUARDAR
    // ==================================================

    public function guardar(array $dados): bool
    {
        $dados['empresa_id'] = $this->empresaId();
        $dados['usuario_id'] = $this->usuarioId();

        /*
         * Se o utilizador estiver limitado a uma filial,
         * a filial da sessão prevalece sobre qualquer valor
         * enviado pelo frontend.
         */
        $filialSessao = $this->filialId();

        if ($filialSessao !== null) {
            $dados['filial_id'] = $filialSessao;
        }

        if (!empty($dados['id'])) {
            return $this->caixa->atualizar(
                $dados
            );
        }

        return $this->caixa->criar(
            $dados
        );
    }

    // ==================================================
    // EXTRATO
    // ==================================================

    public function extrato(
        int $caixaId,
        array $filtros = []
    ): array {

        if ($caixaId <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }

        return $this->caixa->extrato(
            $caixaId,
            $this->empresaId(),
            $filtros
        );
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(int $id): bool
    {
        if ($id <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }

        return $this->caixa->eliminar(
            $id,
            $this->empresaId(),
            $this->usuarioId()
        );
    }

    // ==================================================
    // FILIAIS
    // ==================================================

    public function filiais(): array
    {
        return $this->caixa->filiais(
            $this->empresaId()
        );
    }

    // ==================================================
    // ABRIR CAIXA
    // ==================================================

    public function abrir(array $dados): bool
    {
        $dados['empresa_id'] = $this->empresaId();
        $dados['usuario_id'] = $this->usuarioId();

        $filialSessao = $this->filialId();

        if ($filialSessao !== null) {
            $dados['filial_id'] = $filialSessao;
        }

        return $this->caixa->abrir(
            $dados
        );
    }

    // ==================================================
    // MOVIMENTO
    // ==================================================

    public function movimento(array $dados): bool
    {
        $dados['empresa_id'] = $this->empresaId();
        $dados['usuario_id'] = $this->usuarioId();

        $filialSessao = $this->filialId();

        if ($filialSessao !== null) {
            $dados['filial_id'] = $filialSessao;
        }

        return $this->caixa->movimento(
            $dados
        );
    }

    // ==================================================
    // FECHAR
    // ==================================================

    public function fechar(array $dados): array
    {
        $dados['empresa_id'] = $this->empresaId();
        $dados['usuario_id'] = $this->usuarioId();

        return $this->caixa->fechar(
            $dados
        );
    }

    // ==================================================
    // TRANSFERIR
    // ==================================================

    public function transferir(array $dados): bool
    {
        $dados['empresa_id'] = $this->empresaId();
        $dados['usuario_id'] = $this->usuarioId();

        $filialSessao = $this->filialId();

        if ($filialSessao !== null) {
            $dados['filial_id'] = $filialSessao;
        }

        return $this->caixa->transferir(
            $dados
        );
    }

    // ==================================================
    // SALDO
    // ==================================================

    public function saldo(int $caixaId): float
    {
        if ($caixaId <= 0) {
            throw new RuntimeException(
                'Caixa inválido.'
            );
        }

        return $this->caixa->saldoAtual(
            $caixaId,
            $this->empresaId()
        );
    }

    // ==================================================
    // CONTEXTO DA SESSÃO
    // ==================================================

    private function empresaId(): int
    {
        $empresaId = (int)Auth::empresaId();

        if ($empresaId <= 0) {
            throw new RuntimeException(
                'Empresa não identificada na sessão.'
            );
        }

        return $empresaId;
    }

    private function usuarioId(): int
    {
        $usuarioId = (int)Auth::usuarioId();

        if ($usuarioId <= 0) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        return $usuarioId;
    }

    private function filialId(): ?int
    {
        $filialId = Auth::filialId();

        if ($filialId === null) {
            return null;
        }

        $filialId = (int)$filialId;

        return $filialId > 0
            ? $filialId
            : null;
    }
}
