<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/BalancoPatrimonial.php';

final class BalancoPatrimonialController
{
    private BalancoPatrimonial $balanco;

    public function __construct(PDO $pdo)
    {
        $this->balanco =
            new BalancoPatrimonial(
                $pdo
            );
    }

    // ==================================================
    // GERAR BALANÇO PATRIMONIAL
    // ==================================================

    public function gerar(
        array $filtros
    ): array {

        $empresaId =
            $this->empresaId();

        $filialSessao =
            $this->filialId();

        $dataReferencia =
            trim(
                (string)(
                    $filtros['data_referencia']
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

        if ($dataReferencia === '') {
            throw new RuntimeException(
                'Informe a data de referência.'
            );
        }

        /*
         * Um utilizador associado a uma filial
         * só pode consultar a própria filial.
         */
        if ($filialSessao !== null) {
            $filialId =
                $filialSessao;
        }

        return $this->balanco
            ->gerar(
                $empresaId,
                $dataReferencia,
                $filialId,
                $planoContasId
            );
    }

    // ==================================================
    // LISTAR PLANOS
    // ==================================================

    public function planos(): array
    {
        return $this->balanco
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

        return $this->balanco
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
