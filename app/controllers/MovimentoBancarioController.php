<?php

declare(strict_types=1);

final class MovimentoBancarioController
{
    private MovimentoBancario $movimento;

    public function __construct(PDO $pdo)
    {
        $this->movimento =
            new MovimentoBancario($pdo);
    }

    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        array $dados = []
    ): array {

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

        return $this->movimento->listar(
            (int)$empresaId,
            $dados
        );
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        array $dados
    ): ?array {

        $empresaId =
            Auth::empresaId();

        $id = (int)(
            $dados['id'] ?? 0
        );

        if (
            $empresaId === null
            || $empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        if ($id <= 0) {
            throw new RuntimeException(
                'Movimento bancário inválido.'
            );
        }

        return $this->movimento->buscar(
            $id,
            (int)$empresaId
        );
    }

    // ==================================================
    // REGISTAR MOVIMENTO
    // ==================================================

    public function registrar(
        array $dados
    ): int {

        $empresaId =
            Auth::empresaId();

        $usuarioId =
            Auth::usuarioId();

        if (
            $empresaId === null
            || $empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        if (
            $usuarioId === null
            || $usuarioId <= 0
        ) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        $dados['empresa_id'] =
            (int)$empresaId;

        $dados['usuario_id'] =
            (int)$usuarioId;

        /*
         * A filial pode vir da sessão.
         * Caso o utilizador não pertença a uma filial,
         * permanece NULL.
         */

        if (
            empty($dados['filial_id'])
            && Auth::filialId() !== null
        ) {
            $dados['filial_id'] =
                (int)Auth::filialId();
        }

        return $this->movimento->registrar(
            $dados
        );
    }

    // ==================================================
    // TRANSFERIR
    // ==================================================

    public function transferir(
        array $dados
    ): array {

        $empresaId =
            Auth::empresaId();

        $usuarioId =
            Auth::usuarioId();

        if (
            $empresaId === null
            || $empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        if (
            $usuarioId === null
            || $usuarioId <= 0
        ) {
            throw new RuntimeException(
                'Utilizador não autenticado.'
            );
        }

        $dados['empresa_id'] =
            (int)$empresaId;

        $dados['usuario_id'] =
            (int)$usuarioId;

        if (
            empty($dados['filial_id'])
            && Auth::filialId() !== null
        ) {
            $dados['filial_id'] =
                (int)Auth::filialId();
        }

        return $this->movimento->transferir(
            $dados
        );
    }

    // ==================================================
    // EXTRATO
    // ==================================================

    public function extrato(
        array $dados
    ): array {

        $empresaId =
            Auth::empresaId();

        $bancoId = (int)(
            $dados['banco_id'] ?? 0
        );

        if (
            $empresaId === null
            || $empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        if ($bancoId <= 0) {
            throw new RuntimeException(
                'Banco inválido.'
            );
        }

        return $this->movimento->extrato(
            $bancoId,
            (int)$empresaId,
            $dados
        );
    }

    // ==================================================
    // SALDO
    // ==================================================

    public function saldo(
        array $dados
    ): float {

        $empresaId =
            Auth::empresaId();

        $bancoId = (int)(
            $dados['banco_id']
            ?? $dados['id']
            ?? 0
        );

        if (
            $empresaId === null
            || $empresaId <= 0
        ) {
            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }

        if ($bancoId <= 0) {
            throw new RuntimeException(
                'Banco inválido.'
            );
        }

        return $this->movimento
            ->saldoAtual(
                $bancoId,
                (int)$empresaId
            );
    }
}
