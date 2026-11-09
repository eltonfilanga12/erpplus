<?php

declare(strict_types=1);

require_once __DIR__
    . '/../models/Irps.php';

final class IrpsController
{
    private Irps $model;

    public function __construct(PDO $pdo)
    {
        $this->model =
            new Irps($pdo);
    }

    // ==================================================
    // DADOS INICIAIS
    // ==================================================

    public function dadosIniciais(): array
    {
        $anoAtual =
            (int)date('Y');

        return [
            'ano_atual' =>
            $anoAtual,

            'tabela_vigente' =>
            $this->model
                ->tabelaVigente(
                    $anoAtual
                ),

            'tabelas' =>
            $this->model
                ->listarTabelas(),

            'resumo' =>
            $this->model
                ->resumo()
        ];
    }

    // ==================================================
    // TABELA VIGENTE
    // ==================================================

    public function tabelaVigente(
        ?int $ano = null
    ): array {

        if (
            $ano !== null
            && $ano <= 0
        ) {
            throw new RuntimeException(
                'Ano inválido.'
            );
        }

        $tabela =
            $this->model
            ->tabelaVigente(
                $ano
            );

        if (!$tabela) {
            throw new RuntimeException(
                'Não existe tabela de IRPS '
                    . 'activa para o ano informado.'
            );
        }

        return $tabela;
    }

    // ==================================================
    // LISTAR TABELAS
    // ==================================================

    public function listar(): array
    {
        return $this->model
            ->listarTabelas();
    }

    // ==================================================
    // BUSCAR
    // ==================================================

    public function buscar(
        int $id
    ): array {

        if ($id <= 0) {
            throw new RuntimeException(
                'Tabela de IRPS inválida.'
            );
        }

        $tabela =
            $this->model
            ->buscarTabela(
                $id
            );

        if (!$tabela) {
            throw new RuntimeException(
                'Tabela de IRPS não encontrada.'
            );
        }

        return $tabela;
    }

    // ==================================================
    // ESCALÕES
    // ==================================================

    public function escaloes(
        int $tabelaId
    ): array {

        if ($tabelaId <= 0) {
            throw new RuntimeException(
                'Tabela de IRPS inválida.'
            );
        }

        return $this->model
            ->listarEscaloes(
                $tabelaId
            );
    }

    // ==================================================
    // RESUMO
    // ==================================================

    public function resumo(): array
    {
        return $this->model
            ->resumo();
    }
}
