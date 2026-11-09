<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/DiarioGeral.php';

final class DiarioGeralController
{
    private DiarioGeral $diarioGeral;

    public function __construct(PDO $pdo)
    {
        $this->diarioGeral =
            new DiarioGeral(
                $pdo
            );
    }

    // ==================================================
    // GERAR DIÁRIO GERAL
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

        $diarioId =
            !empty($filtros['diario_id'])
            ? (int)$filtros['diario_id']
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
         * Um utilizador associado a uma filial
         * só pode consultar a própria filial.
         */
        if ($filialSessao !== null) {
            $filialId =
                $filialSessao;
        }

        return $this->diarioGeral
            ->gerar(
                $empresaId,
                $dataInicio,
                $dataFim,
                $filialId,
                $diarioId
            );
    }

    // ==================================================
    // LISTAR DIÁRIOS
    // ==================================================

    public function diarios(): array
    {
        return $this->diarioGeral
            ->listarDiarios(
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

        return $this->diarioGeral
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
            'diarios' =>
            $this->diarios(),

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
