<?php

declare(strict_types=1);

final class FluxoCaixaController
{
    private FluxoCaixa $fluxoCaixa;

    public function __construct(PDO $pdo)
    {
        $this->fluxoCaixa =
            new FluxoCaixa($pdo);
    }

    // ==================================================
    // DASHBOARD COMPLETO
    // Cards + gráfico
    // ==================================================

    public function dashboard(
        array $dados = []
    ): array {

        $empresaId =
            $this->empresaId();

        return $this->fluxoCaixa->dashboard(
            $empresaId,
            $this->prepararFiltros($dados)
        );
    }

    // ==================================================
    // FLUXO CONSOLIDADO
    // Previsto + realizado
    // ==================================================

    public function consolidado(
        array $dados = []
    ): array {

        $empresaId =
            $this->empresaId();

        return $this->fluxoCaixa->consolidado(
            $empresaId,
            $this->prepararFiltros($dados)
        );
    }

    // ==================================================
    // FLUXO PREVISTO
    // Contas a receber + contas a pagar
    // ==================================================

    public function previsto(
        array $dados = []
    ): array {

        $empresaId =
            $this->empresaId();

        return $this->fluxoCaixa->listarPrevisto(
            $empresaId,
            $this->prepararFiltros($dados)
        );
    }

    // ==================================================
    // FLUXO REALIZADO
    // Movimentos de caixa + movimentos bancários
    // ==================================================

    public function realizado(
        array $dados = []
    ): array {

        $empresaId =
            $this->empresaId();

        return $this->fluxoCaixa->listarRealizado(
            $empresaId,
            $this->prepararFiltros($dados)
        );
    }

    // ==================================================
    // DISPONIBILIDADES
    // Saldos totais
    // ==================================================

    public function disponibilidades(
        array $dados = []
    ): array {

        $empresaId =
            $this->empresaId();

        return $this->fluxoCaixa->disponibilidades(
            $empresaId,
            $this->prepararFiltros($dados)
        );
    }

    // ==================================================
    // SALDOS DOS CAIXAS
    // ==================================================

    public function saldosCaixas(
        array $dados = []
    ): array {

        $empresaId =
            $this->empresaId();

        return $this->fluxoCaixa
            ->listarSaldosCaixas(
                $empresaId,
                $this->prepararFiltros($dados)
            );
    }

    // ==================================================
    // SALDOS DOS BANCOS
    // ==================================================

    public function saldosBancos(
        array $dados = []
    ): array {

        $empresaId =
            $this->empresaId();

        return $this->fluxoCaixa
            ->listarSaldosBancos(
                $empresaId,
                $this->prepararFiltros($dados)
            );
    }

    // ==================================================
    // RESUMO PREVISTO
    // ==================================================

    public function resumoPrevisto(
        array $dados = []
    ): array {

        $empresaId =
            $this->empresaId();

        return $this->fluxoCaixa
            ->resumoPrevisto(
                $empresaId,
                $this->prepararFiltros($dados)
            );
    }

    // ==================================================
    // RESUMO REALIZADO
    // ==================================================

    public function resumoRealizado(
        array $dados = []
    ): array {

        $empresaId =
            $this->empresaId();

        return $this->fluxoCaixa
            ->resumoRealizado(
                $empresaId,
                $this->prepararFiltros($dados)
            );
    }

    // ==================================================
    // GRÁFICO
    // ==================================================

    public function grafico(
        array $dados = []
    ): array {

        $empresaId =
            $this->empresaId();

        return $this->fluxoCaixa->grafico(
            $empresaId,
            $this->prepararFiltros($dados)
        );
    }

    // ==================================================
    // DETALHES DE UM ITEM DO FLUXO
    // ==================================================

    public function detalhes(
        array $dados
    ): ?array {

        $empresaId =
            $this->empresaId();

        $id = (int)(
            $dados['id'] ?? 0
        );

        $origem = strtoupper(
            trim((string)(
                $dados['origem'] ?? ''
            ))
        );

        $situacao = strtoupper(
            trim((string)(
                $dados['situacao'] ?? ''
            ))
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Registo do fluxo inválido.'
            );
        }

        if (
            !in_array(
                $origem,
                [
                    'CAIXA',
                    'BANCO',
                    'CONTA_RECEBER',
                    'CONTA_PAGAR'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Origem do movimento inválida.'
            );
        }

        if (
            !in_array(
                $situacao,
                [
                    'PREVISTO',
                    'REALIZADO'
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Situação do movimento inválida.'
            );
        }

        /*
         * Os métodos previsto e realizado já garantem
         * o filtro pela empresa do utilizador.
         */

        if ($situacao === 'PREVISTO') {
            $lista =
                $this->fluxoCaixa
                ->listarPrevisto(
                    $empresaId,
                    [
                        'origem' =>
                        $origem
                    ]
                );
        } else {
            $lista =
                $this->fluxoCaixa
                ->listarRealizado(
                    $empresaId,
                    [
                        'origem' =>
                        $origem
                    ]
                );
        }

        foreach ($lista as $item) {
            if (
                (int)($item['id'] ?? 0)
                === $id
            ) {
                return $item;
            }
        }

        return null;
    }

    // ==================================================
    // RETORNAR EMPRESA DA SESSÃO
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
    // PREPARAR FILTROS
    // ==================================================

    private function prepararFiltros(
        array $dados
    ): array {

        $filtros = [
            'data_inicio' =>
            !empty($dados['data_inicio'])
                ? trim(
                    (string)$dados['data_inicio']
                )
                : null,

            'data_fim' =>
            !empty($dados['data_fim'])
                ? trim(
                    (string)$dados['data_fim']
                )
                : null,

            'tipo_fluxo' =>
            !empty($dados['tipo_fluxo'])
                ? strtoupper(
                    trim(
                        (string)$dados['tipo_fluxo']
                    )
                )
                : (
                    !empty($dados['tipo'])
                    ? strtoupper(
                        trim(
                            (string)$dados['tipo']
                        )
                    )
                    : 'TODOS'
                ),

            'natureza' =>
            !empty($dados['natureza'])
                ? strtoupper(
                    trim(
                        (string)$dados['natureza']
                    )
                )
                : null,

            'origem' =>
            !empty($dados['origem'])
                ? strtoupper(
                    trim(
                        (string)$dados['origem']
                    )
                )
                : null,

            'filial_id' =>
            !empty($dados['filial_id'])
                ? (int)$dados['filial_id']
                : null
        ];

        /*
         * Se o utilizador estiver associado a uma filial,
         * restringimos automaticamente os dados.
         *
         * Um utilizador sem filial pode visualizar todas
         * as filiais da própria empresa.
         */

        $filialSessao =
            Auth::filialId();

        if (
            $filialSessao !== null
            && $filialSessao > 0
        ) {
            $filtros['filial_id'] =
                (int)$filialSessao;
        }

        return $filtros;
    }
}
