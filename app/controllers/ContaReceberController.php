<?php

declare(strict_types=1);

final class ContaReceberController
{
    private ContaReceber $contaReceber;

    public function __construct(PDO $pdo)
    {
        $this->contaReceber =
            new ContaReceber($pdo);
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(): array
    {
        return $this->contaReceber->listar(
            $this->empresaId(),
            $this->filialId()
        );
    }

    public function parcelas(
        int $contaId
    ): array {

        if ($contaId <= 0) {
            throw new RuntimeException(
                'Conta a receber inválida.'
            );
        }


        return $this->contaReceber
            ->parcelas(
                $contaId,
                $this->empresaId()
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
                'Conta a receber inválida.'
            );
        }

        return $this->contaReceber->buscar(
            $id,
            $this->empresaId()
        );
    }

    // ==================================================
    // SALVAR
    // ==================================================

    public function salvar(
        array $dados
    ): int|bool {

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

        return $this->contaReceber
            ->salvar($dados);
    }

    // ==================================================
    // ELIMINAR
    // ==================================================

    public function eliminar(
        int $id
    ): bool {

        if ($id <= 0) {
            throw new RuntimeException(
                'Conta a receber inválida.'
            );
        }

        return $this->contaReceber->eliminar(
            $id,
            $this->empresaId(),
            $this->usuarioId()
        );
    }

    // ==================================================
    // RECEBER
    // ==================================================

    public function receber(
        array $dados
    ): bool {

        $dados['empresa_id'] =
            $this->empresaId();

        $dados['usuario_id'] =
            $this->usuarioId();

        /*
         * A filial e o centro de custo corretos
         * serão herdados da conta dentro do Model.
         */

        return $this->contaReceber
            ->receber($dados);
    }

    // ==================================================
    // CLIENTES
    // ==================================================

    public function clientes(): array
    {
        return $this->contaReceber
            ->clientes(
                $this->empresaId()
            );
    }

    // ==================================================
    // HISTÓRICO DE RECEBIMENTOS
    // ==================================================

    public function historicoRecebimentos(array $dados): array
    {
        $contaId = (int)($dados['conta_id'] ?? 0);

        return $this->contaReceber->obterHistoricoRecebimentos(
            $contaId,
            $this->empresaId()
        );
    }

    // ==================================================
    // FORMAS DE PAGAMENTO
    // ==================================================

    public function formasPagamento(): array
    {
        return $this->contaReceber
            ->formasPagamento();
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
