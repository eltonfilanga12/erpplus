<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/IndicadorFinanceiro.php';

final class IndicadorFinanceiroController
{
    private IndicadorFinanceiro $indicador;

    public function __construct(PDO $pdo)
    {
        $this->indicador =
            new IndicadorFinanceiro($pdo);
    }

    // ==================================================
    // RESUMO PRINCIPAL
    // ==================================================

    public function resumo(
        string $dataInicial,
        string $dataFinal
    ): array {

        return $this->indicador->resumo(
            $this->empresaId(),
            $this->filialId(),
            $dataInicial,
            $dataFinal
        );
    }

    // ==================================================
    // FLUXO MENSAL
    // ==================================================

    public function fluxoMensal(
        int $meses = 12
    ): array {

        return $this->indicador
            ->fluxoMensal(
                $this->empresaId(),
                $meses
            );
    }

    // ==================================================
    // CENTROS DE CUSTO
    // ==================================================

    public function porCentroCusto(
        string $dataInicial,
        string $dataFinal
    ): array {

        return $this->indicador
            ->porCentroCusto(
                $this->empresaId(),
                $dataInicial,
                $dataFinal
            );
    }

    // ==================================================
    // CONTAS VENCIDAS
    // ==================================================

    public function contasVencidas(
        int $limite = 20
    ): array {

        return $this->indicador
            ->contasVencidas(
                $this->empresaId(),
                $this->filialId(),
                $limite
            );
    }

    // ==================================================
    // CLIENTES COM MAIOR DÍVIDA
    // ==================================================

    public function clientesMaiorDivida(
        int $limite = 10
    ): array {

        return $this->indicador
            ->clientesMaiorDivida(
                $this->empresaId(),
                $limite
            );
    }

    // ==================================================
    // FORNECEDORES COM MAIOR DÍVIDA
    // ==================================================

    public function fornecedoresMaiorDivida(
        int $limite = 10
    ): array {

        return $this->indicador
            ->fornecedoresMaiorDivida(
                $this->empresaId(),
                $limite
            );
    }

    // ==================================================
    // PAINEL COMPLETO
    // ==================================================

    public function painel(
        string $dataInicial,
        string $dataFinal,
        int $meses = 12
    ): array {

        return [
            'resumo' =>
            $this->resumo(
                $dataInicial,
                $dataFinal
            ),

            'fluxo_mensal' =>
            $this->fluxoMensal(
                $meses
            ),

            'centros_custo' =>
            $this->porCentroCusto(
                $dataInicial,
                $dataFinal
            ),

            'contas_vencidas' =>
            $this->contasVencidas(
                20
            ),

            'clientes_maior_divida' =>
            $this->clientesMaiorDivida(
                10
            ),

            'fornecedores_maior_divida' =>
            $this->fornecedoresMaiorDivida(
                10
            )
        ];
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
