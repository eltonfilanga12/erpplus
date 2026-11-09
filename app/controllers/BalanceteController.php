<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Balancete.php';

final class BalanceteController
{
    private Balancete $balancete;

    public function __construct(PDO $pdo)
    {
        $this->balancete =
            new Balancete(
                $pdo
            );
    }

    // ==================================================
    // GERAR BALANCETE
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

        return $this->balancete
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
        return $this->balancete
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

        /*
         * Quando o utilizador pertence a uma filial,
         * não enviamos todas as filiais da empresa.
         */
        if ($filialSessao !== null) {
            return [];
        }

        return $this->balancete
            ->listarFiliais(
                $this->empresaId()
            );
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(): array
    {
        return [
            'planos' =>
            $this->planos(),

            'filiais' =>
            $this->filiais(),

            'filial_sessao_id' =>
            $this->filialId()
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
