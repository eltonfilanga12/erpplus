<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Compra.php';

final class CompraController
{
    private Compra $compra;

    public function __construct(PDO $pdo)
    {
        $this->compra =
            new Compra($pdo);
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(): array
    {
        return $this->compra->listar(
            $this->empresaId(),
            $this->filialId()
        );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $id
    ): ?array {

        if ($id <= 0) {
            throw new RuntimeException(
                'Compra inválida.'
            );
        }

        return $this->compra->buscar(
            $id,
            $this->empresaId()
        );
    }

    // ==================================================
    // BUSCAR ITENS
    // ==================================================

    public function buscarItens(
        int $id
    ): array {

        if ($id <= 0) {
            throw new RuntimeException(
                'Compra inválida.'
            );
        }

        return $this->compra->buscarItens(
            $id,
            $this->empresaId()
        );
    }

    // ==================================================
    // SALVAR
    // ==================================================

    public function salvar(
        array $dados
    ): int {

        $dados['empresa_id'] =
            $this->empresaId();

        $dados['usuario_id'] =
            $this->usuarioId();

        /*
         * Utilizador ligado a uma filial:
         * a compra ficará obrigatoriamente nessa filial.
         */

        $filialSessao =
            $this->filialId();

        if ($filialSessao !== null) {
            $dados['filial_id'] =
                $filialSessao;
        }

        return $this->compra->salvar(
            $dados
        );
    }

    // ==================================================
    // FILIAIS
    // ==================================================

    public function filiais(): array
    {
        return $this->compra
            ->filiais(
                $this->empresaId()
            );
    }


    public function cancelar(
        int $id,
        string $motivo
    ): array {

        if ($id <= 0) {
            throw new RuntimeException(
                'Compra inválida.'
            );
        }


        return $this->compra
            ->cancelar(
                $id,
                $this->empresaId(),
                $this->usuarioId(),
                $motivo
            );
    }
    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $id
    ): bool {

        if ($id <= 0) {
            throw new RuntimeException(
                'Compra inválida.'
            );
        }

        return $this->compra->eliminar(
            $id,
            $this->empresaId(),
            $this->usuarioId()
        );
    }

    // ==================================================
    // FORNECEDORES
    // ==================================================

    public function fornecedores(): array
    {
        return $this->compra->fornecedores(
            $this->empresaId()
        );
    }

    // ==================================================
    // ARMAZÉNS
    // ==================================================

    public function armazens(): array
    {
        return $this->compra->armazens(
            $this->empresaId()
        );
    }

    // ==================================================
    // PRODUTOS
    // ==================================================

    public function produtos(): array
    {
        return $this->compra->produtos(
            $this->empresaId()
        );
    }

    // ==================================================
    // STATUS
    // ==================================================

    public function status(): array
    {
        return $this->compra
            ->listarStatus();
    }

    // ==================================================
    // EMPRESA DA SESSÃO
    // ==================================================

    private function empresaId(): int
    {
        $empresaId =
            Auth::empresaId();

        if (
            $empresaId === null
            || $empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        return (int)$empresaId;
    }

    // ==================================================
    // UTILIZADOR DA SESSÃO
    // ==================================================

    private function usuarioId(): int
    {
        $usuarioId =
            Auth::usuarioId();

        if (
            $usuarioId === null
            || $usuarioId <= 0
        ) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        return (int)$usuarioId;
    }

    // ==================================================
    // FILIAL DA SESSÃO
    // ==================================================

    private function filialId(): ?int
    {
        $filialId =
            Auth::filialId();

        return (
            $filialId !== null
            && $filialId > 0
        )
            ? (int)$filialId
            : null;
    }
}
