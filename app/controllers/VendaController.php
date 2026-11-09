<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Venda.php';

final class VendaController
{
    private Venda $venda;

    public function __construct(PDO $pdo)
    {
        $this->venda = new Venda($pdo);
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(): array
    {
        return $this->venda->listar(
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
                'Venda inválida.'
            );
        }

        return $this->venda->buscar(
            $id,
            $this->empresaId()
        );
    }

    public function buscarParcelas(
        int $id
    ): array {

        if ($id <= 0) {
            throw new RuntimeException(
                'Venda inválida.'
            );
        }


        return $this->venda
            ->buscarParcelas(
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
                'Venda inválida.'
            );
        }

        return $this->venda->buscarItens(
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

        $filialSessao =
            $this->filialId();

        if ($filialSessao !== null) {
            $dados['filial_id'] =
                $filialSessao;
        }

        return $this->venda->salvar(
            $dados
        );
    }

    // ==================================================
    // CANCELAR
    // ==================================================

    public function cancelar(
        int $id,
        string $motivo
    ): array {

        if ($id <= 0) {

            throw new RuntimeException(
                'Venda inválida.'
            );
        }


        $motivo =
            trim($motivo);


        if (mb_strlen($motivo) < 5) {

            throw new RuntimeException(
                'Informe o motivo do cancelamento.'
            );
        }


        return $this->venda
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
                'Venda inválida.'
            );
        }

        return $this->venda->eliminar(
            $id,
            $this->empresaId(),
            $this->usuarioId()
        );
    }

    // ==================================================
    // PRÓXIMO NÚMERO
    // ==================================================

    public function proximoNumero(
        string $tipoDocumento,
        ?string $dataVenda = null
    ): string {

        return $this->venda
            ->visualizarProximoNumeroDocumento(
                $this->empresaId(),
                $tipoDocumento,
                $dataVenda
            );
    }
    // ==================================================
    // CLIENTES
    // ==================================================

    public function clientes(): array
    {
        return $this->venda->clientes(
            $this->empresaId()
        );
    }

    // ==================================================
    // ARMAZÉNS
    // ==================================================

    public function armazens(): array
    {
        return $this->venda->armazens(
            $this->empresaId(),
            $this->filialId()
        );
    }

    // ==================================================
    // PRODUTOS
    // ==================================================

    public function produtos(): array
    {
        return $this->venda->produtos(
            $this->empresaId()
        );
    }

    // ==================================================
    // STATUS
    // ==================================================

    public function status(): array
    {
        return $this->venda->listarStatus();
    }

    // ==================================================
    // EMPRESA
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
    // UTILIZADOR
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
    // FILIAL
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
