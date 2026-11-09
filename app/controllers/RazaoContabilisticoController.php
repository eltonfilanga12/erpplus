<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/RazaoContabilistico.php';

final class RazaoContabilisticoController
{
    private RazaoContabilistico $razao;

    public function __construct(PDO $pdo)
    {
        $this->razao =
            new RazaoContabilistico(
                $pdo
            );
    }

    // ==================================================
    // GERAR RAZÃO
    // ==================================================

    public function gerar(
        array $filtros
    ): array {

        $empresaId =
            $this->empresaId();

        $filialSessao =
            $this->filialId();

        $contaContabilId =
            (int)(
                $filtros['conta_contabil_id']
                ?? 0
            );

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

        if ($contaContabilId <= 0) {
            throw new RuntimeException(
                'Selecione a conta contabilística.'
            );
        }

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
         * só pode consultar a própria filial.
         */
        if ($filialSessao !== null) {
            $filialId =
                $filialSessao;
        }

        return $this->razao
            ->gerar(
                $empresaId,
                $contaContabilId,
                $dataInicio,
                $dataFim,
                $filialId
            );
    }

    // ==================================================
    // LISTAR CONTAS
    // ==================================================

    public function contas(
        ?int $planoContasId = null
    ): array {

        if (
            $planoContasId !== null
            && $planoContasId <= 0
        ) {
            $planoContasId = null;
        }

        return $this->razao
            ->listarContas(
                $this->empresaId(),
                $planoContasId
            );
    }

    // ==================================================
    // LISTAR PLANOS
    // ==================================================

    public function planos(): array
    {
        return $this->razao
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

        return $this->razao
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

            'contas' =>
            $this->contas(
                $planoPadraoId
            ),

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
