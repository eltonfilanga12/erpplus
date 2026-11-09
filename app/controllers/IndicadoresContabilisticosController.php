<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/IndicadoresContabilisticos.php';

final class IndicadoresContabilisticosController
{
    private IndicadoresContabilisticos $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new IndicadoresContabilisticos(
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
    // GERAR INDICADORES
    // ==================================================

    public function gerar(
        array $dados
    ): array {

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

        $planoContasId =
            $this->inteiroOpcional(
                $dados['plano_contas_id']
                    ?? null
            );

        $exercicioId =
            $this->inteiroOpcional(
                $dados['exercicio_id']
                    ?? null
            );

        $compararPeriodoAnterior =
            $this->booleano(
                $dados['comparar_periodo_anterior']
                    ?? true
            );

        return $this->model
            ->gerar(
                $this->empresaId(),
                $dataInicial,
                $dataFinal,
                $filialId,
                $planoContasId,
                $exercicioId,
                $compararPeriodoAnterior
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
            filter_var(
                $valor,
                FILTER_VALIDATE_INT
            );

        if (
            $inteiro === false
            || $inteiro <= 0
        ) {
            return null;
        }

        return (int)$inteiro;
    }

    // ==================================================
    // BOOLEANO
    // ==================================================

    private function booleano(
        mixed $valor
    ): bool {

        if (is_bool($valor)) {
            return $valor;
        }

        if (is_int($valor)) {
            return $valor === 1;
        }

        $texto =
            strtolower(
                trim(
                    (string)$valor
                )
            );

        return in_array(
            $texto,
            [
                '1',
                'true',
                'sim',
                'yes',
                'on'
            ],
            true
        );
    }
}
