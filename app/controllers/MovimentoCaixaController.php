<?php

declare(strict_types=1);

final class MovimentoCaixaController
{
    private MovimentoCaixa $movimento;

    public function __construct(PDO $pdo)
    {
        $this->movimento =
            new MovimentoCaixa($pdo);
    }


    // ==================================================
    // LISTAR
    // ==================================================

    public function listar(
        array $dados = []
    ): array {

        $empresaId =
            Auth::empresaId();

        $this->validarEmpresa(
            $empresaId
        );

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

        $this->validarEmpresa(
            $empresaId
        );

        $id =
            (int)(
                $dados['id']
                ?? 0
            );

        if ($id <= 0) {

            throw new RuntimeException(
                'Movimento de caixa inválido.'
            );
        }

        return $this->movimento->buscar(
            $id,
            (int)$empresaId
        );
    }


    // ==================================================
    // REGISTAR
    // ==================================================

    public function registrar(
        array $dados
    ): int {

        $empresaId =
            Auth::empresaId();

        $usuarioId =
            Auth::usuarioId();

        $this->validarEmpresa(
            $empresaId
        );

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


        return $this->movimento
            ->registrar(
                $dados
            );
    }


    // ==================================================
    // TRANSFERIR ENTRE CAIXAS
    // ==================================================

    public function transferir(
        array $dados
    ): array {

        $empresaId =
            Auth::empresaId();

        $usuarioId =
            Auth::usuarioId();


        $this->validarEmpresa(
            $empresaId
        );


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


        return $this->movimento
            ->transferir(
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

        $this->validarEmpresa(
            $empresaId
        );


        $caixaId =
            (int)(
                $dados['caixa_id']
                ?? 0
            );


        if ($caixaId <= 0) {

            throw new RuntimeException(
                'Caixa inválido.'
            );
        }


        return $this->movimento
            ->extrato(
                $caixaId,
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

        $this->validarEmpresa(
            $empresaId
        );


        $caixaId =
            (int)(
                $dados['caixa_id']
                ?? $dados['id']
                ?? 0
            );


        if ($caixaId <= 0) {

            throw new RuntimeException(
                'Caixa inválido.'
            );
        }


        return $this->movimento
            ->saldoAtual(
                $caixaId,
                (int)$empresaId
            );
    }


    // ==================================================
    // CAIXAS ABERTOS
    // ==================================================

    public function caixasAbertos(): array
    {

        $empresaId =
            Auth::empresaId();

        $this->validarEmpresa(
            $empresaId
        );


        return $this->movimento
            ->listarCaixasAbertos(
                (int)$empresaId
            );
    }


    // ==================================================
    // VALIDAÇÃO
    // ==================================================

    private function validarEmpresa(
        ?int $empresaId
    ): void {

        if (
            $empresaId === null
            || $empresaId <= 0
        ) {

            throw new RuntimeException(
                'Empresa do utilizador não encontrada.'
            );
        }
    }
}
