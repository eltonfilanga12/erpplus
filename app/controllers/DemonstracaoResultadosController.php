<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/DemonstracaoResultados.php';

final class DemonstracaoResultadosController
{
    private DemonstracaoResultados $demonstracao;

    public function __construct(PDO $pdo)
    {
        $this->demonstracao =
            new DemonstracaoResultados(
                $pdo
            );
    }

    // ==================================================
    // GERAR DEMONSTRAÇÃO DE RESULTADOS
    // ==================================================

    public function gerar(
        array $filtros
    ): array {

        $empresaId =
            $this->empresaId();

        $filialSessao =
            $this->filialId();

        $dataInicio =
            trim(
                (string)(
                    $filtros['data_inicio']
                    ?? ''
                )
            );

        $dataFim =
            trim(
                (string)(
                    $filtros['data_fim']
                    ?? ''
                )
            );

        $filialId =
            !empty($filtros['filial_id'])
            ? (int)$filtros['filial_id']
            : null;

        $planoContasId =
            !empty($filtros['plano_contas_id'])
            ? (int)$filtros['plano_contas_id']
            : null;

        if (
            $dataInicio === ''
            || $dataFim === ''
        ) {
            throw new RuntimeException(
                'Informe a data inicial e a data final.'
            );
        }

        /*
         * Utilizador associado a uma filial
         * só pode consultar essa filial.
         */
        if ($filialSessao !== null) {
            $filialId =
                $filialSessao;
        }

        return $this->demonstracao
            ->gerar(
                $empresaId,
                $dataInicio,
                $dataFim,
                $filialId,
                $planoContasId
            );
    }

    // ==================================================
    // LISTAR PLANOS
    // ==================================================

    public function planos(): array
    {
        return $this->demonstracao
            ->listarPlanos(
                $this->empresaId()
            );
    }

    // ==================================================
    // LISTAR FILIAIS
    // ==================================================

    public function filiais(): array
    {
        $filialSessao =
            $this->filialId();

        if ($filialSessao !== null) {
            return [];
        }

        return $this->demonstracao
            ->listarFiliais(
                $this->empresaId()
            );
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(): array
    {
        $planos =
            $this->planos();

        $planoPadraoId =
            null;

        foreach ($planos as $plano) {

            if (
                (int)(
                    $plano['padrao']
                    ?? 0
                ) === 1
            ) {
                $planoPadraoId =
                    (int)$plano['id'];

                break;
            }
        }

        return [
            'planos' =>
            $planos,

            'filiais' =>
            $this->filiais(),

            'filial_sessao_id' =>
            $this->filialId(),

            'plano_padrao_id' =>
            $planoPadraoId
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
