<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/DemonstracaoFluxoCaixa.php';

final class DemonstracaoFluxoCaixaController
{
    private DemonstracaoFluxoCaixa $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new DemonstracaoFluxoCaixa(
                $pdo
            );
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(): array
    {
        return $this->model
            ->dadosIniciais(
                $this->empresaId()
            );
    }

    // ==================================================
    // GERAR DEMONSTRAÇÃO
    // ==================================================

    public function gerar(
        array $dados
    ): array {

        $planoContasId =
            (int)(
                $dados['plano_contas_id']
                ?? 0
            );

        $dataInicial =
            trim(
                (string)(
                    $dados['data_inicial']
                    ?? ''
                )
            );

        $dataFinal =
            trim(
                (string)(
                    $dados['data_final']
                    ?? ''
                )
            );

        $filialId =
            $this->inteiroOpcional(
                $dados['filial_id']
                    ?? null
            );

        $exercicioId =
            $this->inteiroOpcional(
                $dados['exercicio_id']
                    ?? null
            );

        return $this->model
            ->gerar(
                $this->empresaId(),
                $planoContasId,
                $dataInicial,
                $dataFinal,
                $filialId,
                $exercicioId
            );
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
    // INTEIRO OPCIONAL
    // ==================================================

    private function inteiroOpcional(
        mixed $valor
    ): ?int {

        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        $inteiro =
            (int)$valor;

        return $inteiro > 0
            ? $inteiro
            : null;
    }
}
